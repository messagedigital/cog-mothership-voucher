<?php

namespace Message\Mothership\Voucher\EventListener;

use Message\Mothership\Voucher\Event;
use Message\Mothership\Voucher\Exception;
use Message\Mothership\Commerce\Order;
use Message\Mothership\Commerce\Order\Entity\CollectionInterface;
use Message\Mothership\Commerce\Order\Entity\Item\Item;
use Message\User\AnonymousUser;
use Message\Cog\Event as CogEvent;

/**
 * Class EVoucherListener
 * @package Message\Mothership\Voucher\EventListener
 *
 * @author Thomas Marchant <thomas@mothership.ec>
 */
class EVoucherListener extends CogEvent\EventListener implements CogEvent\SubscriberInterface
{
	/**
	 * @return array
	 */
	static public function getSubscribedEvents()
	{
		return [
			Order\Events::CREATE_COMPLETE => [
				'sendEVoucherOnOrderComplete',
				'setVoucherItemStatus',
			],
		];
	}

	public function sendEVoucherOnOrderComplete(Order\Event\Event $event)
	{
		if ($this->_eVouchersDisabled()) {
			return;
		}

		$voucherItems = $this->_getVoucherItems($event->getOrder()->items);

		foreach ($voucherItems as $voucherItem) {
			$user = $voucherItem->authorship->createdBy() ?: $event->getOrder()->user;

			if (is_numeric($user)) {
				$user = $this->get('user.loader')->getByID($user);
			}

			$voucherID = $voucherItem->personalisation->voucher_id;

			if (!$voucherID) {
				throw new \LogicException('Voucher ID could not be determined for item');
			}

			$voucher = $this->get('voucher.loader')->getByID($voucherID);

			if (!$voucher) {
				throw new \LogicException('Could not load voucher with ID `' . $voucherID . '`');
			}

			try {
				if (!$user || $user instanceof AnonymousUser) {
					throw new Exception\EVoucherSendException(
						'Could not send e-voucher with ID of `' . $voucherID . '` as user is ' . ($user ? 'anonymous' : 'null'),
						'ms.voucher.evoucher.error.email',
						['%code%' => $voucher->id]
					);
				}

				$this->get('voucher.e_voucher.mailer')->sendVoucher($voucher, $user);
			} catch (Exception\VoucherDisplayException $e) {
				$message = $this->get('translator')->trans($e->getTranslation(), $e->getParams());
				$this->get('http.session')->getFlashBag()->add('error', $message);
				$this->get('log.errors')->warning($e->getMessage());
			}
		}
	}

	/**
	 * @deprecated Some gateways have problems with sending the email this early as they do not have access to the session
	 *             in the callback. Use `sendEVoucherOnOrderComplete` instead
	 *
	 * @param Event\VoucherEvent $event
	 */
	public function sendEVoucher(Event\VoucherEvent $event)
	{
		if ($this->_eVouchersDisabled()) {
			return;
		}

		$user = $this->get('user.loader')->getByID($event->getVoucher()->authorship->createdBy());

		if (!$user || $user instanceof AnonymousUser) {
			throw new Exception\EVoucherSendException(
				'Could not send e-voucher with ID of `' . $event->getVoucher()->id . '` as user is ' . ($user ? 'anonymous' : 'null'),
				'ms.voucher.evoucher.error.email',
				['%code%' => $event->getVoucher()->id]
			);
		}

		$this->get('voucher.e_voucher.mailer')->sendVoucher($event->getVoucher(), $user);
	}

	public function setVoucherItemStatus(Order\Event\Event $event)
	{
		if ($this->_eVouchersDisabled()) {
			return;
		}

		$vouchers = $this->_getVoucherItems($event->getOrder()->items);

		if (!empty($vouchers)) {
			$this->get('order.item.edit')->updateStatus($vouchers, Order\Statuses::RECEIVED);
		}

		if (count($vouchers) === count($event->getOrder()->items)) {
			$this->get('order.edit')->updateStatus($event->getOrder(), Order\Statuses::RECEIVED);
		}
	}

	private function _getVoucherItems(CollectionInterface $items)
	{
		$vouchers = [];

		foreach ($items as $item) {
			if (!$item instanceof Item) {
				continue;
			}
			if ($item->getProduct()->getType()->getName() === 'voucher') {
				$vouchers[] = $item;
			}
		}

		return $vouchers;
	}

	private function _eVouchersDisabled()
	{
		return !isset($this->get('cfg')->voucher->eVoucher) ||
			false === $this->get('cfg')->voucher->eVoucher ||
			$this->_isEPOS();
	}

	private function _isEPOS()
	{
		$controller = $this->get('request')->attributes->get('_controller');

		if ($controller) {
			$controller = explode('\\', $controller);

			array_walk($controller, function (&$segment) {
				$segment = strtolower($segment);
			});

			return in_array('epos', $controller);
		}

		throw new \LogicException('No controller set on request');
	}
}
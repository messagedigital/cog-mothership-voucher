<?php

namespace Message\Mothership\Voucher\EventListener;

use Message\Mothership\Voucher\Event;
use Message\Mothership\Voucher\Exception;
use Message\Mothership\Commerce\Order;
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
			Event\Events::VOUCHER_CREATE => [
				'sendEVoucher'
			],
			Order\Events::CREATE_COMPLETE => [
				'setVoucherItemStatus'
			],
		];
	}

	/**
	 * @param Event\VoucherEvent $event
	 */
	public function sendEVoucher(Event\VoucherEvent $event)
	{
		if ($this->_eVouchersDisabled()) {
			return;
		}

		try {
			$user = $this->get('user.loader')->getByID($event->getVoucher()->authorship->createdBy());

			if (!$user || $user instanceof AnonymousUser) {
				throw new Exception\EVoucherSendException(
					'Could not send e-voucher as user is ' . ($user ? 'anonymous' : 'null'),
					'ms.voucher.evoucher.error.email',
					['%code%' => $event->getVoucher()->id]
				);
			}

			$this->get('voucher.e_voucher.mailer')->sendVoucher($event->getVoucher(), $user);
		} catch (Exception\VoucherDisplayException $e) {
			$message = $this->get('translator')->trans($e->getTranslation(), $e->getParams());
			$this->get('http.session')->getFlashBag()->add('error', $message);
			$this->get('log.errors')->warning($e->getMessage());
		}
	}

	public function setVoucherItemStatus(Order\Event\Event $event)
	{
		if ($this->_eVouchersDisabled()) {
			return;
		}

		$vouchers = [];

		foreach ($event->getOrder()->items as $item) {
			if ($item->getProduct()->getType()->getName() === 'voucher') {
				$vouchers[] = $item;
			}
		}

		if (!empty($vouchers)) {
			$this->get('order.item.edit')->updateStatus($vouchers, Order\Statuses::RECEIVED);
		}

		if (count($vouchers) === count($event->getOrder()->items)) {
			$this->get('order.edit')->updateStatus($event->getOrder(), Order\Statuses::RECEIVED);
		}
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
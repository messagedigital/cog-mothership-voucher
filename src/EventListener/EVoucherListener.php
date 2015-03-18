<?php

namespace Message\Mothership\Voucher\EventListener;

use Message\Mothership\Voucher\Event;
use Message\Mothership\Commerce\Order;
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

		$user = $this->get('user.loader')->getByID($event->getVoucher()->authorship->createdBy());

		$this->get('voucher.e_voucher.mailer')->sendVoucher($event->getVoucher(), $user);
	}

	public function setVoucherItemStatus(Order\Event\Event $event)
	{
		if ($this->_eVouchersDisabled())
		{
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

			return (!empty($controller[2]) && $controller[2] === 'EPOS');
		}

		throw new \LogicException('No controller set on request');
	}
}
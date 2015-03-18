<?php

namespace Message\Mothership\Voucher\EventListener;

use Message\Mothership\Voucher\Event;
use Message\Cog\Event as CogEvent;

/**
 * Class EVoucherListener
 * @package Message\Mothership\Voucher\EventListener
 *
 * @author Thomas Marchant <thomas@mothership.ec>
 */
class EVoucherListener extends CogEvent\EventListener implements CogEvent\SubscriberInterface
{
	private $_acceptedRoutes = [

	];

	/**
	 * @return array
	 */
	static public function getSubscribedEvents()
	{
		return [
			Event\Events::VOUCHER_CREATE => [
				'sendEVoucher'
			]
		];
	}

	/**
	 * @param Event\VoucherEvent $event
	 */
	public function sendEVoucher(Event\VoucherEvent $event)
	{
		if ($this->_isEPOS()) {
			return;
		}

		if (!isset($this->get('cfg')->voucher->eVoucher) || false === $this->get('cfg')->voucher->eVoucher) {
			return;
		}

		$user = $this->get('user.loader')->getByID($event->getVoucher()->authorship->createdBy());

		$this->get('voucher.e_voucher.mailer')->sendVoucher($event->getVoucher(), $user);
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
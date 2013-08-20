<?php

namespace Message\Mothership\Voucher;

use Message\Mothership\Commerce\Order;

use Message\Cog\Event\SubscriberInterface;
use Message\Cog\Event\EventListener as BaseListener;

class EventListener extends BaseListener implements SubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			Order\Events::CREATE_END => array(
				array('generateVouchers'),
			),
		);
	}

	public function generateVouchers(Order\Event\Event $event)
	{
		$voucherProductIDs = $this->get('cfg')->voucher->productIds;

		// Skip if no voucher product IDs are defined in the config
		if (!$voucherProductIDs || empty($voucherProductIDs)) {
			return false;
		}

		// Cast voucher product IDs to an array
		if (!is_array($voucherProductIDs)) {
			$voucherProductIDs = array($voucherProductIDs);
		}

		foreach ($event->getOrder()->items as $item) {
			if (!in_array($item->productID, $voucherProductIDs)) {
				continue;
			}

			$voucher = new Voucher;
			$voucher->currencyID      = $event->getOrder()->currencyID;
			$voucher->amount          = $item->listPrice;
			$voucher->id              = $this->get('voucher.id_generator')->generate();
			$voucher->purchasedAsItem = $item;

			$this->get('voucher.create')
				->setTransaction($event->getTransaction())
				->create($voucher);
		}
	}
}
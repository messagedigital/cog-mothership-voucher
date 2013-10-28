<?php

namespace Message\Mothership\Voucher;

use Message\Mothership\Commerce\Order;

use Message\Cog\Event\SubscriberInterface;
use Message\Cog\Event\EventListener as BaseListener;

/**
 * Event listeners for voucher functionality.
 *
 * This includes:
 *
 *  * An event listener to automatically create a gift voucher when an order is
 *    created with a gift voucher product as an item.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class EventListener extends BaseListener implements SubscriberInterface
{
	/**
	 * {@inheritDoc}
	 */
	static public function getSubscribedEvents()
	{
		return array(
			Order\Entity\Item\Events::CREATE_PRE_PERSONALISATION_INSERTS => array(
				array('generateVouchers'),
			),
			Order\Events::ASSEMBLER_UPDATE => array(
				array('recalculateVouchers'),
			),
		);
	}

	/**
	 * Generate vouchers for any items added to an order that are voucher
	 * products.
	 *
	 * The product ID for the item must be listed in the "product-ids" config
	 * element in the "voucher" group.
	 *
	 * The voucher amount is set to the *list price* of the item, not the net
	 * or gross amount.
	 *
	 * The voucher ID is then set as the personalisation key "voucher_id" on the
	 * relevant item.
	 *
	 * The queries for adding the voucher are added to the same transaction as
	 * the item creation queries.
	 *
	 * @param Order\Event\EntityEvent $event The event object
	 */
	public function generateVouchers(Order\Event\EntityEvent $event)
	{
		$item = $event->getEntity();

		if (!($item instanceof Order\Entity\Item\Item)) {
			return false;
		}

		$voucherProductIDs = $this->get('cfg')->voucher->productIDs;

		// Skip if no voucher product IDs are defined in the config
		if (!$voucherProductIDs || empty($voucherProductIDs)) {
			return false;
		}

		// Cast voucher product IDs to an array
		if (!is_array($voucherProductIDs)) {
			$voucherProductIDs = array($voucherProductIDs);
		}

		if (!in_array($item->productID, $voucherProductIDs)) {
			return false;
		}

		$voucher = new Voucher;
		$voucher->currencyID      = $item->order->currencyID;
		$voucher->amount          = $item->listPrice;
		$voucher->id              = $this->get('voucher.id_generator')->generate();
		$voucher->purchasedAsItem = $item;

		$create = $this->get('voucher.create');
		$create->setTransaction($event->getTransaction());
		$create->create($voucher);

		$item->personalisation->voucher_id = $voucher->id;
	}

	public function recalculateVouchers()
	{
		$order = $this->get('basket')->getOrder();

		foreach ($order->payments as $payment) {
			if ($payment->method->getName() != 'voucher') {
				continue;
			}

			$voucherID = $payment->reference;
			$order->payments->remove($voucherID);
			$voucher = $this->get('voucher.loader')->getByID($voucherID);
			if ($voucher && $voucher->isUsable()) {
				$paymentMethod = $this->get('order.payment.methods')->get('voucher');
				if ($this->get('basket')->getOrder()->getAmountDue() >= $voucher->getBalance()) {
					$amount = $voucher->getBalance();
				} else {
					$amount = $this->get('basket')->getOrder()->getAmountDue();
				}
				$this->get('basket')->addPayment($paymentMethod, $amount, $voucher->id, true);
			}
		}
	}
}
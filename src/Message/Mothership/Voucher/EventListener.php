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
			Order\Events::CREATE_END => array(
				array('generateVouchers'),
			),
			Order\Events::ASSEMBLER_UPDATE => array(
				array('recalculateVouchers'),
			),
		);
	}

	/**
	 * Generate vouchers for any item in a new order that is a voucher product.
	 *
	 * The product ID for the item must be listed in the "product-ids" config
	 * element in the "voucher" group.
	 *
	 * The voucher amount is set to the *list price* of the item, not the net
	 * or gross amount.
	 *
	 * The queries for adding the voucher are added to the same transaction as
	 * the order creation queries.
	 *
	 * @param Order\Event\TransactionalEvent $event The event object
	 */
	public function generateVouchers(Order\Event\TransactionalEvent $event)
	{
		$voucherProductIDs = $this->get('cfg')->voucher->productIDs;

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

			$create = $this->get('voucher.create');
			$create->setTransaction($event->getTransaction());
			$create->create($voucher);
		}
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
				if ($this->get('basket')->getOrder()->getPaymentTotal() >= $voucher->getBalance()) {
					$amount = $voucher->getBalance();
				} else {
					$amount = $this->get('basket')->getOrder()->getPaymentTotal();
				}
				$this->get('basket')->addPayment($paymentMethod, $amount, $voucher->id, true);
			}
		}
	}
}
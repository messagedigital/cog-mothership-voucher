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
	protected $_busy = false;

	/**
	 * {@inheritDoc}
	 */
	static public function getSubscribedEvents()
	{
		return array(
			Order\Entity\Item\Events::CREATE_PRE_PERSONALISATION_INSERTS => array(
				array('generateVouchers'),
			),
			Order\Events::CREATE_END => array(
				array('setUsedTimestamp'),
			),
			Order\Events::ASSEMBLER_UPDATE => array(
				array('recalculateVouchers', -1000),
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

	/**
	 * Sets the "used" timestamp on vouchers that have been used on an order,
	 * only when the voucher has now been used up entirely.
	 *
	 * The timestamp is set to the "created at" timestamp for the order.
	 *
	 * @param Order\Event\TransactionalEvent $event
	 */
	public function setUsedTimestamp(Order\Event\TransactionalEvent $event)
	{
		$order         = $event->getOrder();
		$voucherLoader = $this->get('voucher.loader');
		$voucherEdit   = $this->get('voucher.edit');

		// Set voucher edit decorator to use the transaction from the event
		$voucherEdit->setTransaction($event->getTransaction());

		foreach ($order->payments as $payment) {
			// Skip if the payment isn't a voucher payment
			if ('voucher' !== $payment->method->getName()) {
				continue;
			}

			$voucher = $voucherLoader->getByID($payment->reference);

			// Skip if the voucher could not be found
			if (!($voucher instanceof Voucher)) {
				continue;
			}

			// Skip if the voucher wasn't fully used
			if ($payment->amount != $voucher->getBalance()) {
				continue;
			}

			$voucherEdit->setUsed($voucher, $order->authorship->createdAt());
		}
	}

	public function recalculateVouchers(Order\Event\AssemblerEvent $event)
	{
		// Don't execute this listener if it's busy (it's already running)
		if (true === $this->_busy) {
			return;
		}

		// Mark this listener as busy
		$this->_busy = true;

		$basket        = $event->getAssembler();
		$order         = $basket->getOrder();
		$method        = $this->get('order.payment.methods')->get('voucher');
		$voucherLoader = $this->get('voucher.loader');
		$leftToPay     = $order->totalGross;
		$vouchers      = [];

		// Grab the vouchers for all voucher payments
		foreach ($order->payments->getByProperty('method', $method) as $payment) {
			if (!$voucher = $voucherLoader->getByID($payment->id)) {
				continue;
			}

			$vouchers[$payment->id] = $voucher;
		}

		// Sort vouchers by expiry date ascending, then value ascending
		uasort($vouchers, function($a, $b) {
			if ($a->expiresAt == $b->expiresAt) {
				if ($a->getBalance() == $b->getBalance()) {
					return 0;
				}

				return ($a->getBalance() < $b->getBalance()) ? -1 : 1;
			}

			return ($a->expiresAt < $b->expiresAt) ? -1 : 1;
		});

		// Check all the vouchers
		foreach ($vouchers as $id => $voucher) {
			$payment = $order->payments->get($voucher->id);

			// If there is nothing left to pay or the voucher isn't usable, remove it
			if ($leftToPay <= 0
			 || !$voucher->isUsable()) {
				$basket->removeEntity('payments', $payment);

				// Prevent further listeners from firing (a new event will be dispatched)
				$event->stopPropagation();

				// Continue to the next iteration
				continue;
			}

			$amount = ($leftToPay >= $voucher->getBalance())
				? $voucher->getBalance()
				: $leftToPay;

			// Reduce the amount left to pay by the voucher amount
			$leftToPay -= $amount;

			// If the amount to use has changed, update the basket
			if ($amount != $payment->amount) {
				$payment->amount = $amount;

				$basket->replaceEntity('payments', $payment);

				// Prevent further listeners from firing (a new event will be dispatched)
				$event->stopPropagation();
			}
		}

		// Release this listener
		$this->_busy = false;
	}
}
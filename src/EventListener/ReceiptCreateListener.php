<?php

namespace Message\Mothership\Voucher\EventListener;

use Message\Mothership\Epos\Branch;
use Message\Mothership\Epos\Receipt;

use Message\Mothership\Commerce\Order\Transaction;
use Message\Mothership\Commerce\Order\Order;
use Message\Mothership\Commerce\Order\Entity\Payment\Payment;

use Message\Cog\Event\EventListener as BaseListener;
use Message\Cog\Event\SubscriberInterface;

/**
 * Event listeners for creating voucher receipts for transactions.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class ReceiptCreateListener extends BaseListener implements SubscriberInterface
{
	/**
	 * {@inheritDoc}
	 */
	static public function getSubscribedEvents()
	{
		return array(
			Transaction\Events::CREATE_COMPLETE => array(
				array('createNewSaleReceipt'),
			),
		);
	}

	/**
	 *
	 *
	 * @param Transaction\Event $event
	 */
	public function createNewSaleReceipt(Transaction\Event\Event $event)
	{
		$transaction = $event->getTransaction();

		if (Transaction\Types::ORDER !== $transaction->type) {
			return false;
		}

		$receiptCreate = $this->get('order.receipt.create');
		$template = $this->get('receipt.templates')->get('voucher_usage');
		$factory = $this->get('receipt.factory');

		$orders = $transaction->records->getByType(Order::RECORD_TYPE);
		$order = array_shift($orders);

		foreach ($transaction->records->getByType(Payment::RECORD_TYPE) as $payment) {
			if ('voucher' !== $payment->method->getName()) {
				continue;
			}

			$template->setTransaction($transaction);
			$template->setVoucherPayment($payment);

			$receipt = $factory->build($template);
			$orderReceipt = new Receipt\OrderEntity\Receipt($receipt);

			// Add the receipt to the order
			$orderReceipt->order = $order;
			$receiptCreate->create($orderReceipt);
		}

		// TODO: add to order
		// TODO: add to transaction as a new record
	}
}
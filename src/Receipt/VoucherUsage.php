<?php

namespace Message\Mothership\Voucher\Receipt;

use Message\Mothership\Voucher\Loader;

use Message\Mothership\Epos\Receipt\Adapter\BuilderInterface;
use Message\Mothership\Epos\Receipt\Template\AbstractTransactionTemplate;
use Message\Mothership\Epos\Receipt\Template\TemplateInterface;

use Message\Mothership\Commerce\Order\Order;
use Message\Mothership\Commerce\Order\Entity\Item\Item;
use Message\Mothership\Commerce\Order\Entity\Payment\Payment;

/**
 * Receipt for when a voucher is used on a transaction as a form of tender.
 *
 * The receipt shows the previous balance, the adjustment and the new balance.
 *
 * Two copies are generated: one for the store, and one for the customer.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 *
 * @todo  GENERATE TWO COPIES!
 * @todo encapsulate receipt footer to avoid code duplication
 * @todo move the font size stuff to the buider
 */
class VoucherUsage extends AbstractTransactionTemplate implements TemplateInterface
{
	protected $_merchantName;
	protected $_voucherLoader;

	protected $_voucherPayment;
	protected $_voucher;

	/**
	 * Constructor.
	 *
	 * @param string $merchantName The merchant name to be printed on receipts
	 */
	public function __construct($merchantName, Loader $voucherLoader)
	{
		$this->_merchantName = $merchantName;
		$this->_voucherLoader = $voucherLoader;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return 'voucher_usage';
	}

	/**
	 * Set the voucher payment entity that was used on this transaction for
	 * which we are generating a voucher usage receipt for.
	 *
	 * @param  Payment $payment
	 *
	 * @return VoucherUsage Returns $this for chainability
	 */
	public function setVoucherPayment(Payment $payment)
	{
		if ('voucher' !== $payment->method->getName()) {
			throw new \InvalidArgumentException('Voucher usage receipt only accepts voucher payments');
		}

		if (!$voucher = $this->_voucherLoader->getByID($payment->reference)) {
			throw new \InvalidArgumentException(sprintf('Could not load voucher `%s`', $payment->reference));
		}

		$this->_voucherPayment = $payment;
		$this->_voucher        = $voucher;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function build(BuilderInterface $builder)
	{
		if (!$this->_transaction) {
			throw new \LogicException('Transaction must be set before receipt template can be built');
		}

		// So we can use the variables in closures
		$transaction    = $this->_transaction;
		$voucherCode    = $this->_voucherPayment->reference;
		$paymentAmount  = $this->_voucherPayment->amount;

		// Find the order from the transaction
		$orders = $this->_transaction->records->getByType(Order::RECORD_TYPE);
		$order  = array_shift($orders);

		$builder->justify($builder::JUSTIFY_CENTER, function($builder) use ($voucherCode) {
			$builder->append('VOUCHER ADJUSTMENT' . "\n\n");
			$builder->append(chr(29) . '!' . chr(34) . "\r\r"); // I think this makes font big
			$builder->append(strtoupper($voucherCode) . "\n\n");
			$builder->append(chr(27) . '@'); //  ESC @ / RESET
			$builder->barcode($voucherCode, $builder::BARCODE_CODE39, false);
		});

		$builder->append("\n\n\n");

		$builder->split('Previous balance:', number_format($this->_voucher->getBalance() + $paymentAmount, 2));
		$builder->split('Adjustment:', number_format(-$paymentAmount, 2));

		$builder->bold(function($builder) use ($order) {
			$builder->split('New balance:', number_format($this->_voucher->getBalance(), 2));
		});

		$builder->append("\n\n");

		$builder->justify($builder::JUSTIFY_CENTER, function($builder) use ($transaction, $order) {
			$builder->bold(function($builder) {
				$builder->softWrap(['Thank you for shopping at', $this->_merchantName]);
			});

			$builder->append('You were served today by ' . $transaction->authorship->createdUser()->forename . "\n\n");

			$builder->append($transaction->authorship->createdAt()->format('H:i j F Y') . "\n");

			$builder->append('Order #' . $order->id . "\n\n");
		});

		$builder->barcode($order->id, $builder::BARCODE_CODE39);
	}
}
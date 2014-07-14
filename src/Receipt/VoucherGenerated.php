<?php

namespace Message\Mothership\Voucher\Receipt;

use Message\Mothership\Voucher\Voucher;

use Message\Mothership\Epos\Receipt\Adapter\BuilderInterface;
use Message\Mothership\Epos\Receipt\Template\TemplateInterface;

use Message\Mothership\Commerce\Order\Order;
use Message\Mothership\Commerce\Order\Entity\Item\Item;
use Message\Mothership\Commerce\Order\Entity\Payment\Payment;

/**
 * Receipt for when a voucher is purchased in a transaction and a voucher is
 * generated for it.
 *
 * Two copies are generated: one for the store, and one for the customer.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 *
 * @todo move the font size stuff to the buider
 */
class VoucherGenerated implements TemplateInterface
{
	protected $_merchantName;
	protected $_voucher;

	/**
	 * Constructor.
	 *
	 * @param string $merchantName The merchant name to be printed on receipts
	 */
	public function __construct($merchantName)
	{
		$this->_merchantName = $merchantName;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return 'voucher_generated';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCopies()
	{
		return ['store', 'customer'];
	}

	/**
	 * Set the voucher that was generated for this receipt.
	 *
	 * @param  Voucher $payment
	 *
	 * @return VoucherGenerated Returns $this for chainability
	 */
	public function setVoucher(Voucher $voucher)
	{
		$this->_voucher = $voucher;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function build(BuilderInterface $builder)
	{
		if (!$this->_voucher) {
			throw new \LogicException('Voucher must be set before receipt template can be built');
		}

		// So we can use the variables in closures
		$voucher = $this->_voucher;

		$builder->justify($builder::JUSTIFY_CENTER, function($builder) use ($voucher) {
			$builder->append(number_format($voucher->amount, 2) . $voucher->currencyID . ' Voucher' . "\n\n");
			$builder->append(chr(29) . '!' . chr(34) . "\r\r"); // I think this makes font big
			$builder->append(strtoupper($voucher->id) . "\n\n");
			$builder->append(chr(27) . '@'); //  ESC @ / RESET
			$builder->barcode($voucher->id, $builder::BARCODE_CODE39, false);
		});

		if ($voucher->expiresAt) {
			$builder->append("\n\n");
			$builder->split('Expires at:', $voucher->expiresAt->format('h:ia d/m/Y'));
		}
	}
}
<?php

namespace Message\Mothership\Voucher\TenderMethod;

use Message\Mothership\Epos\TenderMethod\MethodInterface;

use Message\Cog\Module\ReferenceParserInterface;

use Symfony\Component\HttpKernel\Controller\ControllerReference;

/**
 * Voucher tender method.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 *
 * @deprecated Moved to EPOS module, use Message\Mothership\Epos\TenderMethod\Voucher instead
 */
class Voucher implements MethodInterface
{
	protected $_referenceParser;

	public function __construct(ReferenceParserInterface $referenceParser)
	{
		$this->_referenceParser = $referenceParser;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return 'voucher';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDisplayName()
	{
		return 'Gift Voucher';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPaymentMethod()
	{
		return 'voucher';
	}

	/**
	 * {@inheritdoc}
	 *
	 * Payments are only added externally for payments, not refunds.
	 */
	public function isPaymentAddedExternally($type)
	{
		return (self::PAYMENT_TYPE_PAYMENT === $type);
	}

	/**
	 * {@inheritdoc}
	 *
	 * Change is not allowed.
	 */
	public function isChangeAllowed($type)
	{
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getNumberPadReplacementController($type)
	{
		if (self::PAYMENT_TYPE_REFUND === $type) {
			return null;
		}

		$reference = $this->_referenceParser->parse(
			'Message:Mothership:Voucher::Controller:Epos#tenderVoucher'
		);

		return new ControllerReference(
			$reference->getSymfonyLogicalControllerName()
		);
	}
}
<?php

namespace Message\Mothership\Voucher\TenderMethod;

use Message\Mothership\Epos\TenderMethod\MethodInterface;

use Message\Cog\Module\ReferenceParserInterface;

use Symfony\Component\HttpKernel\Controller\ControllerReference;

/**
 * Voucher tender method.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
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
	 *
	 * Returns null to stop the default tendering logic adding the payment. The
	 * payment is added by the vouchers tendering functionality so it doesn't
	 * need re-adding.
	 */
	public function getPaymentMethod()
	{
		return null;
	}

	/**
	 * {@inheritdoc}
	 *
	 * Change is not allowed.
	 */
	public function isChangeAllowed()
	{
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getNumberPadReplacementController()
	{
		$reference = $this->_referenceParser->parse(
			'Message:Mothership:Voucher::Controller:Epos#tenderVoucher'
		);

		return new ControllerReference(
			$reference->getSymfonyLogicalControllerName()
		);
	}
}
<?php

namespace Message\Mothership\Voucher\Controller;

use Message\Mothership\Voucher\Voucher;

use Message\Mothership\Epos\Branch;
use Message\Mothership\Commerce\Payment\Payment;
use Message\Mothership\Commerce\Order\Order;
use Message\Mothership\Commerce\Order\Entity\Payment\Payment as OrderPayment;

use Message\Cog\Controller\Controller;
use Message\Cog\HTTP\Request;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controllers for voucher-specific functionality in EPOS.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 *
 * @deprecated  Controller moved to EPOS module
 */
class Epos extends Controller implements Branch\BranchTillAwareInterface
{
	protected $_branch;
	protected $_till;
	protected $_type = 'sale';

	/**
	 * {@inheritDoc}
	 */
	public function setBranch(Branch\BranchInterface $branch)
	{
		$this->_branch = $branch;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setTill($till)
	{
		$this->_till = $till;
	}

	/**
	 * Set the type for this voucher tendering.
	 *
	 * @param string $type Allowed values: `sale` or `return`
	 *
	 * @throws \InvalidArgumentException If `$type` is not an allowed value
	 */
	public function setType($type)
	{
		if (!in_array($type, ['sale', 'return'])) {
			throw new \InvalidArgumentException(sprintf('Unknown tender type: `%s`', $type));
		}

		$this->_type = $type;
	}

	public function addPayment(Payment $payment)
	{
		$payment->amount = min($payment->amount, $this->getAmountDue());

		// If this is a sale, add to the sale
		if ('sale' === $this->_type) {
			$this->get('epos.sale')->addEntity('payments', new OrderPayment($payment));
		}
		// Otherwise, add to the return
		else {
			$this->get('epos.return')->addPayment($payment);
		}
	}

	public function removeVoucherPayment($id)
	{
		if ('sale' === $this->_type) {
			return $this->get('epos.sale')->removeEntity('payments', $id);
		}

		$payment = new Payment;
		$payment->method    = $this->get('order.payment.methods')->get('voucher');
		$payment->reference = $id;

		$this->get('epos.return')->removePayment($payment);
	}

	public function getAmountDue()
	{
		if ('sale' === $this->_type) {
			return $this->get('epos.sale')->getOrder()->getAmountDue();
		}

		$balance = $this->get('epos.return')->getReturn()->item->balance
			?: $this->get('epos.return')->getReturn()->item->calculatedBalance;

		return $balance - $this->_getTotalVoucherPayment();
	}

	public function getVoucherPayments()
	{
		$return   = [];
		$payments = 'sale' === $this->_type
			? $this->get('epos.sale')->getOrder()->payments->all()
			: $this->get('epos.return')->getReturn()->payments;

		foreach ($payments as $key => $payment) {
			if ('voucher' !== $payment->method->getName()) {
				continue;
			}

			$return[$key] = $payment;
		}

		return $return;
	}

	public function tenderVoucher(Request $request, Voucher $foundVoucher = null, $type = null)
	{
		if ($type) {
			$this->setType($type);
		}
		else {
			$this->setType(
				false === strpos($request->headers->get('referer'), 'return')
					? 'sale'
					: 'return'
			);
		}

		$foundVoucherForm   = null;
		$searchForm         = $this->_getSearchForm();
		$vouchers           = [];
		$voucherRemoveForms = [];
		$voucherLoader      = $this->get('voucher.loader');

		foreach ($this->getVoucherPayments() as $payment) {
			if (($voucher = $voucherLoader->getByID($payment->reference)) instanceof Voucher) {
				$vouchers[$voucher->id] = $voucher;

				$voucherRemoveForms[$voucher->id] = $this->createForm($this->get('voucher.form.epos.remove'), $voucher, [
					'action' => $this->generateUrl('ms.epos.sale.modal.tender.voucher.remove', [
						'branch' => $this->_branch->getName(),
						'till'   => $this->_till,
						'type'   => $this->_type,
					]),
				])->createView();
			}
		}

		if ($foundVoucher) {
			$foundVoucherForm = $this->createForm($this->get('voucher.form.epos.apply'), $foundVoucher, [
				'action' => $this->generateUrl('ms.epos.sale.modal.tender.voucher.apply', [
					'branch' => $this->_branch->getName(),
					'till'   => $this->_till,
					'type'   => $this->_type,
				]),
				'disabled' => array_key_exists($foundVoucher->id, $vouchers),
			]);
		}

		return $this->render('Message:Mothership:Voucher::epos:tender-voucher', [
			'search_form'          => $searchForm,
			'found_voucher'        => $foundVoucher,
			'found_voucher_form'   => $foundVoucherForm,
			'vouchers'             => $vouchers,
			'voucher_remove_forms' => $voucherRemoveForms,
		]);
	}

	public function findVoucher(Request $request, $type)
	{
		$this->setType($type);

		$foundVoucher = null;
		$searchForm   = $this->_getSearchForm();

		$searchForm->handleRequest();

		if ($searchForm->isValid()) {
			$id = $searchForm->getData()['id'];

			$foundVoucher = $this->get('voucher.loader')->getByID($id);
			if (!($foundVoucher instanceof Voucher)) {
				$this->addFlash('error', $this->trans('ms.voucher.voucher-not-found', ['%id%' => $id]));
			}
			elseif ($error = $this->get('voucher.validator')->getError($foundVoucher, $this->get('epos.sale')->getOrder())) {
				$this->addFlash('error', $error);
				$foundVoucher = null;
			}
		}

		$view = $this->forward('Message:Mothership:Voucher::Controller:Epos#tenderVoucher', [
			'foundVoucher' => $foundVoucher ?: null,
			'type'         => $this->_type,
		]);

		if ('json' === $request->getFormat($request->getAllowedContentTypes()[0])) {
			return new JsonResponse([
				'self' => $view->getContent(),
			]);
		}

		return $view;
	}

	public function applyVoucher(Request $request, $type)
	{
		$this->setType($type);

		$voucherLoader = $this->get('voucher.loader');
		$form          = $this->createForm($this->get('voucher.form.epos.apply'));

		$form->handleRequest();

		if ($form->isValid()) {
			$voucher = $form->getData();

			// Reload the voucher, the form doesn't pass **all** the data
			$voucher = $voucherLoader->getByID($voucher->id);

			if (!($voucher instanceof Voucher)) {
				throw new \LogicException('Voucher ID to be used is not valid: something has changed');
			}

			// Create payment instance for the voucher
			$payment = new Payment;
			$payment->method    = $this->get('order.payment.methods')->get('voucher');
			$payment->reference = $voucher->id;
			$payment->amount    = $voucher->getBalance();

			$this->addPayment($payment);
		}

		$view = $this->forward('Message:Mothership:Voucher::Controller:Epos#tenderVoucher');

		if ('json' === $request->getFormat($request->getAllowedContentTypes()[0])) {
			// Calculate the maximum possible tender amount for vouchers
			$maximumPayment = $voucher->getBalance();

			foreach ($this->getVoucherPayments() as $payment) {
				if ($payment->reference === $voucher->id) {
					continue;
				}

				$paymentVoucher = $voucherLoader->getByID($payment->reference);
				$maximumPayment += $paymentVoucher->getBalance();
			}

			return new JsonResponse([
				'self'           => $view->getContent(),
				'tenderAmount'   => $this->_getTotalVoucherPayment(),
				'maximumPayment' => $maximumPayment,
			]);
		}

		return $view;
	}

	public function removeVoucher(Request $request, $type)
	{
		$this->setType($type);

		$form  = $this->createForm($this->get('voucher.form.epos.remove'));
		$order = $this->get('epos.sale');

		$form->handleRequest();

		if ($form->isValid()) {
			$voucherID = $form->getData()['id'];

			$this->removeVoucherPayment($voucherID);
		}

		$view = $this->forward('Message:Mothership:Voucher::Controller:Epos#tenderVoucher');

		if ('json' === $request->getFormat($request->getAllowedContentTypes()[0])) {
			return new JsonResponse([
				'self'         => $view->getContent(),
				'tenderAmount' => $this->_getTotalVoucherPayment(),
			]);
		}

		return $view;
	}

	protected function _getSearchForm()
	{
		$searchForm = $this->createForm($this->get('voucher.form.epos.search'), null, [
			'action' => $this->generateUrl('ms.epos.sale.modal.tender.voucher.search', [
				'branch' => $this->_branch->getName(),
				'till'   => $this->_till,
				'type'   => $this->_type,
			]),
		]);

		return $searchForm;
	}

	protected function _getTotalVoucherPayment()
	{
		$tenderAmount = 0;

		foreach ($this->getVoucherPayments() as $payment) {
			$tenderAmount += $payment->amount;
		}

		return $tenderAmount;
	}
}
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
 */
class Epos extends Controller implements Branch\BranchTillAwareInterface
{
	protected $_branch;
	protected $_till;

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

	public function tenderVoucher(Voucher $foundVoucher = null)
	{
		$foundVoucherForm   = null;
		$searchForm         = $this->_getSearchForm();
		$vouchers           = [];
		$voucherRemoveForms = [];
		$voucherLoader      = $this->get('voucher.loader');

		foreach ($this->get('epos.sale')->getOrder()->payments as $payment) {
			if ('voucher' !== $payment->method->getName()) {
				continue;
			}

			if (($voucher = $voucherLoader->getByID($payment->reference)) instanceof Voucher) {
				$vouchers[$voucher->id] = $voucher;

				$voucherRemoveForms[$voucher->id] = $this->createForm($this->get('voucher.form.epos.remove'), $voucher, [
					'action' => $this->generateUrl('ms.epos.sale.modal.tender.voucher.remove', [
						'branch' => $this->_branch->getName(),
						'till'   => $this->_till,
					]),
				])->createView();
			}
		}

		if ($foundVoucher) {
			$foundVoucherForm = $this->createForm($this->get('voucher.form.epos.apply'), $foundVoucher, [
				'action' => $this->generateUrl('ms.epos.sale.modal.tender.voucher.apply', [
					'branch' => $this->_branch->getName(),
					'till'   => $this->_till,
				]),
				'disabled' => array_key_exists($foundVoucher->id, $vouchers),
			]);
		}

		return $this->render('Message:Mothership:Voucher::epos:tender-voucher', [
			'search_form'        => $searchForm,
			'found_voucher'      => $foundVoucher,
			'found_voucher_form' => $foundVoucherForm,
			'vouchers'           => $vouchers,
			'voucher_remove_forms'    => $voucherRemoveForms,
		]);
	}

	public function findVoucher(Request $request)
	{
		$foundVoucher = null;
		$searchForm   = $this->_getSearchForm();

		$searchForm->handleRequest();

		if ($searchForm->isValid()) {
			$id = $searchForm->getData()['id'];

			$foundVoucher = $this->get('voucher.loader')->getByID($id);

			if (!($foundVoucher instanceof Voucher)) {
				$this->addFlash('error', $this->trans('ms.voucher.voucher-not-found', ['%id%' => $id]));
			}
			elseif ($error = $this->get('voucher.validator')->getError($foundVoucher)) {
				$this->addFlash('error', $error);
				$foundVoucher = null;
			}
		}

		$view = $this->forward('Message:Mothership:Voucher::Controller:Epos#tenderVoucher', [
			'foundVoucher' => $foundVoucher ?: null,
		]);

		if ('json' === $request->getFormat($request->getAllowedContentTypes()[0])) {
			return new JsonResponse([
				'self' => $view->getContent(),
			]);
		}

		return $view;
	}

	public function applyVoucher(Request $request)
	{
		$voucherLoader = $this->get('voucher.loader');
		$form          = $this->createForm($this->get('voucher.form.epos.apply'));
		$order         = $this->get('epos.sale');

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
			$payment->amount    = min($voucher->getBalance(), $order->getOrder()->getAmountDue());

			$order->addEntity('payments', new OrderPayment($payment));
		}

		$view = $this->forward('Message:Mothership:Voucher::Controller:Epos#tenderVoucher');

		if ('json' === $request->getFormat($request->getAllowedContentTypes()[0])) {
			// Calculate the maximum possible tender amount for vouchers
			$maximumPayment = $voucher->getBalance();
			foreach ($order->getOrder()->payments->all() as $payment) {
				if ('voucher' !== $payment->method->getName()
				 || $payment->reference === $voucher->id) {
					continue;
				}

				$paymentVoucher = $voucherLoader->getByID($payment->reference);
				$maximumPayment += $paymentVoucher->getBalance();
			}

			return new JsonResponse([
				'self'           => $view->getContent(),
				'tenderAmount'   => $this->_getTotalVoucherPayment($order->getOrder()),
				'maximumPayment' => $maximumPayment,
			]);
		}

		return $view;
	}

	public function removeVoucher(Request $request)
	{
		$form  = $this->createForm($this->get('voucher.form.epos.remove'));
		$order = $this->get('epos.sale');

		$form->handleRequest();

		if ($form->isValid()) {
			$voucherID = $form->getData()['id'];

			$order->removeEntity('payments', $voucherID);
		}

		$view = $this->forward('Message:Mothership:Voucher::Controller:Epos#tenderVoucher');

		if ('json' === $request->getFormat($request->getAllowedContentTypes()[0])) {
			return new JsonResponse([
				'self'         => $view->getContent(),
				'tenderAmount' => $this->_getTotalVoucherPayment($order->getOrder()),
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
			]),
		]);

		return $searchForm;
	}

	protected function _getTotalVoucherPayment(Order $order)
	{
		$tenderAmount = 0;

		foreach ($order->payments as $orderPayment) {
			if ('voucher' !== $orderPayment->method->getName()) {
				continue;
			}

			$tenderAmount += $orderPayment->amount;
		}

		return $tenderAmount;
	}
}
<?php

namespace Message\Mothership\Voucher\Controller;

use Message\Mothership\Voucher\Voucher;

use Message\Mothership\Epos\Branch;
use Message\Mothership\Commerce\Order\Entity\Payment\Payment;

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
		$foundVoucherForm = null;
		$searchForm       = $this->_getSearchForm();

		if ($foundVoucher) {
			$foundVoucherForm = $this->createForm($this->get('voucher.form.epos.apply'), $foundVoucher, [
				'action' => $this->generateUrl('ms.epos.sale.modal.tender.voucher.apply', [
					'branch' => $this->_branch->getName(),
					'till'   => $this->_till,
				]),
			]);
		}

		return $this->render('Message:Mothership:Voucher::epos:tender-voucher', [
			'search_form'   => $searchForm,
			'found_voucher' => $foundVoucher,
			'found_voucher_form' => $foundVoucherForm,
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
			// make add form for the voucher

			if (!($foundVoucher instanceof Voucher)) {
				$this->addFlash('error', $this->trans('ms.voucher.voucher-not-found', ['%id%' => $id]));
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
		$form  = $this->createForm($this->get('voucher.form.epos.apply'));
		$order = $this->get('epos.sale');

		$form->handleRequest();

		if ($form->isValid()) {
			$voucher = $form->getData();

			// Reload the voucher, the form doesn't pass **all** the data
			$voucher = $this->get('voucher.loader')->getByID($voucher->id);

			if (!($voucher instanceof Voucher)) {
				throw new \LogicException('Voucher ID to be used is not valid: something has changed');
			}

			// Create payment instance for the voucher
			$payment = new Payment;
			$payment->method    = $this->get('order.payment.methods')->get('voucher');
			$payment->reference = $voucher->id;
			$payment->amount    = min($voucher->getBalance(), $order->getOrder()->getAmountDue());

			$order->addEntity('payments', $payment);
		}

		$view = $this->forward('Message:Mothership:Voucher::Controller:Epos#tenderVoucher');

		if ('json' === $request->getFormat($request->getAllowedContentTypes()[0])) {
			return new JsonResponse([
				'self'         => $view->getContent(),
				'tenderAmount' => isset($payment) ? $payment->amount : 0,
			]);
		}

		return $view;
	}

	public function _getSearchForm()
	{
		$searchForm = $this->createForm($this->get('voucher.form.epos.search'), null, [
			'action' => $this->generateUrl('ms.epos.sale.modal.tender.voucher.search', [
				'branch' => $this->_branch->getName(),
				'till'   => $this->_till,
			]),
		]);

		return $searchForm;
	}
}
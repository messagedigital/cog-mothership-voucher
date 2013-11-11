<?php

namespace Message\Mothership\Voucher\Controller;

use Message\Cog\Controller\Controller;

/**
 * Controller to manage adding gift vouchers to orders
 */
class AddVoucher extends Controller
{
	public function index()
	{
		return $this->render('Message:Mothership:Voucher::voucher-input', array(
			'form' => $this->voucherForm(),
		));

	}

	public function voucherForm()
	{
		$form = $this->get('form');
		$form->setName('voucher_form')
			->setAction($this->generateUrl('ms.voucher.process'))
			->setMethod('post');
		$form->add('voucher', 'text', $this->trans('ms.voucher.add.add'));

		return $form;
	}

	public function voucherProcess()
	{
		$form = $this->voucherForm();
		if ($form->isValid() && $data = $form->getFilteredData()) {
			$voucher = $this->get('voucher.loader')->getByID($data['voucher']);
			if ($voucher && $voucher->isUsable()) {

				$paymentMethod = $this->get('order.payment.methods')->get('voucher');

				if ($this->get('basket')->getOrder()->getAmountDue() >= $voucher->getBalance()) {
					$amount = $voucher->getBalance();
				} else {
					$amount = $this->get('basket')->getOrder()->getAmountDue();
				}
				$this->addFlash('success', $this->trans('ms.voucher.add.success'));
				$this->get('basket')->addPayment($paymentMethod, $amount, $voucher->id);
			} else {
				$this->addFlash('error', $this->trans('ms.voucher.add.error.unusable'));
			}
		} else {
			$this->addFlash('error', $this->trans('ms.voucher.add.error.invalid'));
		}

		return $this->redirectToReferer();
	}
}
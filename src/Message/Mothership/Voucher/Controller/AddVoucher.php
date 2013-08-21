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
		$form->add('voucher', 'text', 'I have a gift voucher');

		return $form;
	}

	public function voucherProcess()
	{
		$form = $this->voucherForm();
		if ($form->isValid() && $data = $form->getFilteredData()) {
			$voucher = $this->get('voucher.loader')->getByID($data['voucher']);
			if ($voucher && $voucher->isUsable()) {

				$paymentMethod = $this->get('order.payment.methods')->get('voucher');

				if ($this->get('basket')->getOrder()->getPaymentTotal() >= $voucher->getBalance()) {
					$amount = $voucher->getBalance();
				} else {
					$amount = $this->get('basket')->getOrder()->getPaymentTotal();
				}
				$this->addFlash('success', 'Voucher applied to order successfully');
				$this->get('basket')->addPayment($paymentMethod, $amount, $voucher->id);
			} else {
				$this->addFlash('error', 'Voucher could not be applied to order');
			}
		} else {
			$this->addFlash('error', 'Please enter a valid gift voucher code');
		}

		return $this->redirectToReferer();
	}
}
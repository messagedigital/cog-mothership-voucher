<?php

namespace Message\Mothership\Voucher\Controller;

use Message\Cog\Controller\Controller;
use Message\Mothership\Voucher;

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
		$form->add('voucher', 'text', $this->trans('ms.voucher.add.label'));

		return $form;
	}

	public function voucherProcess()
	{
		$form = $this->voucherForm();

		if ($form->isValid() && $data = $form->getFilteredData()) {
			$voucher = $this->get('voucher.loader')->getByID($data['voucher']);

			if (!$voucher) {
				$this->addFlash('error', $this->trans('ms.voucher.voucher-not-found', array(
					'%id%' => $data['voucher'],
				)));
			}
			else if ($error = $this->get('voucher.validator')->getError($voucher)) {
				$this->addFlash('error', $error);
			}
			else if ($this->get('basket')->getOrder()->currencyID !== $voucher->currencyID){
				$this->addFlash('error', $this->trans('ms.voucher.add.error.incompatible-currencies', [
						'%id%' => $data['voucher'],
					]));
			}
			else {
				$paymentMethod = $this->get('order.payment.methods')->get('voucher');

				if ($this->get('basket')->getOrder()->getAmountDue() >= $voucher->getBalance()) {
					$amount = $voucher->getBalance();
				} else {
					$amount = $this->get('basket')->getOrder()->getAmountDue();
				}

				$this->addFlash('success', $this->trans('ms.voucher.add.success', array(
					'%id%' => $voucher->id,
				)));

				$this->get('basket')->addPayment($paymentMethod, $amount, $voucher->id);
			}

		} else {
			$this->addFlash('error', $this->trans('ms.voucher.add.error.invalid', array(
				'%id%' => $data['voucher'],
			)));
		}

		return $this->redirectToReferer();
	}
}
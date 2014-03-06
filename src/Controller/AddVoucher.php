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

			if ($voucher && $voucher->isUsable()) {

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

			} elseif ($voucher && $voucher->getUnusableReason() == $voucher::REASON_NO_BALANCE) {
				$this->addFlash('error', $this->trans('ms.voucher.add.error.no-balance', array(
					'%id%' => $voucher->id,
				)));

			} elseif ($voucher && $voucher->getUnusableReason() == $voucher::REASON_NOT_STARTED) {
				$date = $voucher->startsAt->getTimestamp();
				$this->addFlash('error', $this->trans('ms.voucher.add.error.not-started', array(
					'%start_date%' => date("Y-m-d g:i a", $date),
					'%id%' => $voucher->id,
				)));

			} elseif ($voucher && $voucher->getUnusableReason() == $voucher::REASON_EXPIRED) {
				$this->addFlash('error', $this->trans('ms.voucher.add.error.expired', array(
					'%id%' => $voucher->id,
				)));

			} elseif (!$voucher) {
				$this->addFlash('error', $this->trans('ms.voucher.voucher-not-found', array(
					'%id%' => $data['voucher'],
				)));

			} else {
				$this->addFlash('error', $this->trans('ms.voucher.add.error.unusable', array(
					'%id%' => $data['voucher'],
				)));
			}

		} else {
			$this->addFlash('error', $this->trans('ms.voucher.add.error.invalid', array(
				'%id%' => $data['voucher'],
			)));
		}

		return $this->redirectToReferer();
	}
}
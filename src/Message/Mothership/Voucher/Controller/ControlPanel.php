<?php

namespace Message\Mothership\Voucher\Controller;

use Message\Mothership\Voucher\Voucher;

use Message\Cog\Controller\Controller;

class ControlPanel extends Controller
{
	public function index()
	{
		return $this->render('::control-panel:index', array(
			'vouchers' => $this->get('voucher.loader')->getOutstanding(),
		));
	}

	public function sidebar()
	{
		return $this->render('Message:Mothership:Voucher::control-panel:sidebar', array(
			'searchForm' => $this->_getSearchForm(),
		));
	}

	public function search()
	{
		$form = $this->_getSearchForm();

		if ($form->isValid() && $data = $form->getFilteredData()) {
			$voucher = $this->get('voucher.loader')->getByID($data['id']);

			if ($voucher instanceof Voucher) {
				return $this->redirectToRoute('ms.cp.voucher.view', array('id' => $voucher->id));
			}

			$this->addFlash('error', $this->trans('ms.voucher.voucher-not-found', array(
				'%id%' => $data['id'],
			)));
		}

		return $this->redirectToReferer();
	}

	public function view($id)
	{
		$voucher = $this->get('voucher.loader')->getByID($id);

		if (!($voucher instanceof Voucher)) {
			$this->addFlash('error', $this->trans('ms.voucher.voucher-not-found', array(
				'%id%' => $id,
			)));

			return $this->redirectToReferer();
		}

		return $this->render('::control-panel:view', array(
			'voucher' => $voucher,
		));
	}

	public function create()
	{
		return $this->render('::control-panel:create', array(
			'form' => $this->_getCreateForm(),
		));
	}

	public function createAction()
	{
		$form = $this->_getCreateForm();

		if ($form->isValid() && $data = $form->getFilteredData()) {
			$voucher = new Voucher;
			$voucher->id = $this->get('voucher.id_generator')->generate();
			$voucher->currencyID = 'GBP'; // make this configurable in future
			$voucher->amount = $data['amount'];

			if ($expiry = $data['expiry']) {
				$voucher->expiresAt = $expiry;
			}

			$voucher = $this->get('voucher.create')->create($voucher);

			return $this->redirectToRoute('ms.cp.voucher.view', array('id' => $voucher->id));
		}

		return $this->redirectToReferer();
	}

	protected function _getSearchForm()
	{
		$searchForm = $this->get('form')
			->setName('voucher-search')
			->setMethod('POST')
			->setAction($this->generateUrl('ms.cp.voucher.search'));

		$searchForm->add('id', 'search', $this->trans('ms.voucher.id.label'), array(
			'attr' => array(
				'placeholder' => $this->trans('ms.voucher.id.search-placeholder'),
			),
		))->val()
			->length($this->get('cfg')->voucher->idLength);

		return $searchForm;
	}

	protected function _getCreateForm()
	{
		$form = $this->get('form')
			->setName('voucher-create')
			->setMethod('POST')
			->setAction($this->generateUrl('ms.cp.voucher.create.action'));

		$form->add(
			'amount',
			'money',
			$this->trans('ms.voucher.amount.label'),
			array(
				'currency' => 'GBP',
				'precision' => 2,
				'attr'     => array(
					'data-help-key' => 'ms.voucher.amount.help',
				)
			)
		);

		$form->add('expiry', 'datetime', $this->trans('ms.voucher.expiry.label'), array(
			'attr' => array('data-help-key' => 'ms.voucher.expiry.help')
		))->val()
			->optional()
			#->after(new \DateTime) // disabled due to bug messagedigital/cog#169
			;

		return $form;
	}
}
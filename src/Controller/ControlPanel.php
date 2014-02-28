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
		$form = $this->createForm($this->get('voucher.form.create'));

		$form->handleRequest();

		if ($form->isValid()) {
			$voucher = $form->getData();
$voucher->id = $this->get('voucher.id_generator')->generate();
$voucher->currencyID = 'GBP'; // make this configurable in future

			$voucher = $this->get('voucher.create')->create($voucher);

			return $this->redirectToRoute('ms.cp.voucher.view', array('id' => $voucher->id));
		}

		return $this->render('::control-panel:create', array(
			'form' => $form,
		));
	}

	public function invalidate($id)
	{
		$voucher = $this->get('voucher.loader')->getByID($id);

		$this->get('voucher.edit')->setExpiry($voucher, new \DateTime);

		$this->addFlash('success', $this->trans('ms.voucher.voucher-invalidated'));

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
}

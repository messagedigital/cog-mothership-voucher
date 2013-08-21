<?php

namespace Message\Mothership\Voucher\Controller;

use Message\Cog\Controller\Controller;

class ControlPanel extends Controller
{
	public function index()
	{
		return $this->render('::control-panel:index', array(
			'vouchers' => $this->get('voucher.loader')->getOutstanding(),
		));
	}
}
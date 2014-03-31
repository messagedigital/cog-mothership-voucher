<?php

namespace Message\Mothership\Voucher\Bootstrap;

use Message\Mothership\Voucher;

use Message\Cog\Bootstrap\ServicesInterface;

class Services implements ServicesInterface
{
	public function registerServices($services)
	{
		$services['voucher.loader'] = $services->factory(function($c) {
			return new Voucher\Loader($c['db.query'], $c['order.item.loader'], $c['order.payment.loader']);
		});

		$services['voucher.create'] = $services->factory(function($c) {
			$create = new Voucher\Create($c['db.query'], $c['voucher.loader'], $c['user.current']);

			// If config is set for ID length, define it here
			if ($idLength = $c['cfg']->voucher->idLength) {
				$create->setIdLength($idLength);
			}

			// If config is set for expiry interval, define it here
			if ($interval = $c['cfg']->voucher->expiryInterval) {
				$interval = \DateInterval::createFromDateString($interval);
				$create->setExpiryInterval($interval);
			}

			return $create;
		});

		$services['voucher.edit'] = $services->factory(function($c) {
			return new Voucher\Edit($c['db.query'], $c['user.current']);
		});

		$services['voucher.id_generator'] = $services->factory(function($c) {
			return new Voucher\IdGenerator($c['security.string-generator'], $c['voucher.loader'], $c['cfg']->voucher->idLength);
		});

		// Add voucher payment method
		$services->extend('order.payment.methods', function($methods) {
			$methods->add(new Voucher\PaymentMethod\Voucher);

			return $methods;
		});

		$services['voucher.form.epos.search'] = $services->factory(function() {
			return new Voucher\Form\Epos\VoucherSearch;
		});

		$services['voucher.form.epos.apply'] = $services->factory(function() {
			return new Voucher\Form\Epos\VoucherApply;
		});

		$services['voucher.form.epos.remove'] = $services->factory(function() {
			return new Voucher\Form\Epos\VoucherRemove;
		});

		if (isset($services['epos.tender.methods'])) {
			$services->extend('epos.tender.methods', function($methods, $c) {
				$methods->add(new Voucher\TenderMethod\Voucher($c['reference_parser']));

				return $methods;
			});
		}
	}
}
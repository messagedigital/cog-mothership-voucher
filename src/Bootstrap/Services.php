<?php

namespace Message\Mothership\Voucher\Bootstrap;

use Message\Mothership\Voucher;

use Message\Cog\Bootstrap\ServicesInterface;
use Message\Cog\AssetManagement\FileReferenceAsset;
use Message\Mothership\Voucher\ProductType\VoucherType;

use Message\Mothership\Report\Report\Collection as ReportCollection;

class Services implements ServicesInterface
{
	public function registerServices($services)
	{
		$this->registerReports($services);

		$services['voucher.loader'] = $services->factory(function($c) {
			return new Voucher\Loader($c['db.query.builder.factory'], $c['order.item.loader'], $c['payment.loader']);
		});

		$services['voucher.create'] = $services->factory(function($c) {
			$create = new Voucher\Create($c['db.query'], $c['voucher.loader'], $c['user.current'], $c['event.dispatcher']);

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

		$services['voucher.validator'] = $services->factory(function($c) {
			return new Voucher\Validator($c['translator']);
		});

		// Add voucher payment method
		$services->extend('order.payment.methods', function($methods) {
			$methods->add(new Voucher\PaymentMethod\Voucher);

			return $methods;
		});

		$services['voucher.e_voucher.mailer'] = function($c) {
			return new Voucher\Mailer\EVoucherMailer($c['mail.dispatcher'], $c['mail.message'], $c['translator']);
		};

		$services['voucher.form.epos.search'] = $services->factory(function() {
			return new Voucher\Form\Epos\VoucherSearch;
		});

		$services['voucher.form.epos.apply'] = $services->factory(function() {
			return new Voucher\Form\Epos\VoucherApply;
		});

		$services['voucher.form.epos.remove'] = $services->factory(function() {
			return new Voucher\Form\Epos\VoucherRemove;
		});

		$services->extend('asset.manager', function($manager, $c) {
			if ($manager->has('epos_extra')) {
				$collection = $manager->get('epos_extra');
				$collection->add(new FileReferenceAsset(
					$c['reference_parser'],
					'@Message:Mothership:Voucher::resources:assets:js:epos.js'
				));
			}

			return $manager;
		});

		if (isset($services['receipt.templates'])) {
			$services->extend('receipt.templates', function($templates, $c) {
				$templates->add(new Voucher\Receipt\VoucherUsage(
					$c['cfg']->merchant->companyName,
					$c['voucher.loader']
				));

				$templates->add(new Voucher\Receipt\VoucherGenerated(
					$c['cfg']->merchant->companyName
				));

				return $templates;
			});
		}

		$services['voucher.form.create'] = $services->factory(function ($c) {
			return new Voucher\Form\CreateForm;
		});

		/**
		 * @deprecated Use $c['voucher.loader']->getProductIDs().
		 * Really you shouldn't need to get these ever anyway.
		 */
		$services['voucher.product_ids'] = $services->factory(function ($c) {
			return $c['db.query.builder']
				->select('`product_id`')
				->from('`product`')
				->where("`type` = '" . VoucherType::TYPE_NAME . "'")
				->getQuery()
				->run()
				->flatten();
		});

		$services['product.types'] = $services->extend('product.types', function ($types, $c) {
			$types->add(new Voucher\ProductType\VoucherType);

			return $types;
		});
	}

	public function registerReports($services)
	{
		$services['voucher.voucher_summary'] = $services->factory(function($c) {
			return new Voucher\Report\VoucherSummary(
				$c['db.query.builder.factory'],
				$c['routing.generator']
			);
		});

		$services['voucher.reports'] = function($c) {
			$reports = new ReportCollection;
			$reports
				->add($c['voucher.voucher_summary'])
			;

			return $reports;
		};
	}
}
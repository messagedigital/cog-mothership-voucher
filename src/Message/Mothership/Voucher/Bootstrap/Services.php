<?php

namespace Message\Mothership\Voucher\Bootstrap;

use Message\Mothership\Voucher;

use Message\Cog\Bootstrap\ServicesInterface;

class Services implements ServicesInterface
{
	public function registerServices($container)
	{
		$container['voucher.loader'] = function($c) {
			return new Voucher\Loader($c['db.query'], $c['order.item.loader'], $c['order.payment.loader']);
		};

		$container['voucher.create'] = function($c) {
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
		};

		$container['voucher.id_generator'] = function($c) {
			return new Voucher\IdGenerator($c['security.salt'], $c['voucher.loader'], $c['cfg']->voucher->idLength);
		};

		// Add voucher payment method
		$container['order.payment.methods'] = $container->share($container->extend('order.payment.methods', function($methods) {
			$methods->add(new Voucher\PaymentMethod\Voucher);

			return $methods;
		}));
	}
}
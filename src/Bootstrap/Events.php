<?php

namespace Message\Mothership\Voucher\Bootstrap;

use Message\Mothership\Voucher;

use Message\Mothership\ControlPanel\Event\Event;

use Message\Cog\Bootstrap\EventsInterface;

class Events implements EventsInterface
{
	public function registerEvents($dispatcher)
	{
		$dispatcher->addSubscriber(new Voucher\EventListener);

		$dispatcher->addListener(Event::BUILD_MAIN_MENU, function($event) {
			$event->addItem('ms.cp.voucher.index', 'Vouchers', array(
				'ms.cp.voucher'
			));
		});

		$dispatcher->addSubscriber(new Voucher\EventListener\ReceiptCreateListener);
	}
}
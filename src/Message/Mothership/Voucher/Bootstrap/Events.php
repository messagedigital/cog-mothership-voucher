<?php

namespace Message\Mothership\Voucher\Bootstrap;

use Message\Mothership\Voucher;

use Message\Cog\Bootstrap\EventsInterface;

class Events implements EventsInterface
{
	public function registerEvents($dispatcher)
	{
		$dispatcher->addSubscriber(new Voucher\EventListener);
	}
}
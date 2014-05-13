<?php

namespace Message\Mothership\Voucher\Bootstrap;

use Message\Mothership\Voucher;

use Message\Mothership\ControlPanel\Event\Event;

use Message\Cog\Bootstrap\EventsInterface;
use Message\Cog\Service\ContainerInterface;
use Message\Cog\Service\ContainerAwareInterface;

/**
 * Bootstrap for event listeners in this cogule.
 */
class Events implements EventsInterface, ContainerAwareInterface
{
	protected $_services;

	/**
	 * {@inheritDoc}
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->_services = $container;
	}

	/**
	 * {@inheritDoc}
	 */
	public function registerEvents($dispatcher)
	{
		$dispatcher->addSubscriber(new Voucher\EventListener);

		$dispatcher->addListener(Event::BUILD_MAIN_MENU, function($event) {
			$event->addItem('ms.cp.voucher.index', 'Vouchers', array(
				'ms.cp.voucher'
			));
		});

		$dispatcher->addSubscriber(new Voucher\EventListener\ReceiptCreateListener);
		$dispatcher->addSubscriber(new Voucher\EventListener\VoucherGenerateListener(
			$this->_services['voucher.create'],
			$this->_services['voucher.id_generator'],
			is_array($this->_services['cfg']->voucher->productIDs)
				? $this->_services['cfg']->voucher->productIDs
				: [$this->_services['cfg']->voucher->productIDs]
		));
	}
}
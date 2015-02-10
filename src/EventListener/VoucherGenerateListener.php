<?php

namespace Message\Mothership\Voucher\EventListener;

use Message\Mothership\Voucher\Voucher;
use Message\Mothership\Voucher\Create;
use Message\Mothership\Voucher\IdGenerator;

use Message\Mothership\Commerce\Order;
use Message\Mothership\Commerce\Refund;

use Message\Cog\Event\EventListener;
use Message\Cog\Event\SubscriberInterface;
use Message\Mothership\Voucher\Loader as VoucherLoader;

/**
 * Event listeners for generating vouchers.
 *
 * Vouchers are generated when a voucher product is purchased, or a refund is
 * created with a method of "voucher".
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class VoucherGenerateListener implements SubscriberInterface
{
	protected $_create;
	protected $_idGenerator;
	protected $_voucherProductIDs = null;
	protected $_voucherLoader;

	/**
	 * {@inheritDoc}
	 */
	static public function getSubscribedEvents()
	{
		return array(
			Order\Entity\Item\Events::CREATE_PRE_PERSONALISATION_INSERTS => array(
				array('generateForSales'),
			),
			Refund\Events::CREATE_START => array(
				array('generateForVoucherRefunds'),
			),
		);
	}

	/**
	 * Constructor.
	 *
	 * @param Create      $create      Voucher creator
	 * @param IdGenerator $idGenerator Voucher ID generator
	 * @param array       $voucherProductIDs Voucher ID generator
	 */
	public function __construct(Create $create, IdGenerator $idGenerator, VoucherLoader $voucherLoader)
	{
		$this->_create            = $create;
		$this->_idGenerator       = $idGenerator;
		$this->_voucherLoader     = $voucherLoader;
	}

	private function _loadProductIDs()
	{
		if($this->voucherProductIDs === null) {
			$this->_voucherProductIDs = $this->_voucherLoader->getProductIDs();
		}
	}

	/**
	 * Generate vouchers for any items added to an order that are voucher
	 * products.
	 *
	 * The product ID for the item must be listed in the "product-ids" config
	 * element in the "voucher" group.
	 *
	 * The voucher amount is set to the *list price* of the item, not the net
	 * or gross amount.
	 *
	 * The voucher ID is then set as the personalisation key "voucher_id" on the
	 * relevant item.
	 *
	 * The queries for adding the voucher are added to the same transaction as
	 * the item creation queries.
	 *
	 * @param Order\Event\EntityEvent $event The event object
	 */
	public function generateForSales(Order\Event\EntityEvent $event)
	{
		$this->_loadProductIDs();

		$item = $event->getEntity();

		if (!($item instanceof Order\Entity\Item\Item)) {
			return false;
		}

		// Skip if no voucher product IDs are defined in the config
		if (!$this->_voucherProductIDs || empty($this->_voucherProductIDs)) {
			return false;
		}

		if (!in_array($item->productID, $this->_voucherProductIDs)) {
			return false;
		}

		$unit = $item->getUnit();

		$voucher = new Voucher;
		
		if ($unit && isset($unit->options['currency']) && isset($unit->options['amount'])) {
			$voucher->currencyID = $unit->options['currency'];
			$voucher->amount     = $unit->options['amount'];
		} else {
			$voucher->currencyID      = $item->order->currencyID;
			$voucher->amount          = $item->actualPrice;
		}
		$voucher->id              = $this->_idGenerator->generate();
		$voucher->purchasedAsItem = $item;

		$this->_create->setTransaction($event->getTransaction());
		$this->_create->create($voucher);

		$item->personalisation->voucher_id = $voucher->id;
	}

	/**
	 * Generate a voucher for a new refund with a method of "voucher" and set
	 * the generated voucher code to the refund reference.
	 *
	 * @param Refund\Event\TransactionalEvent $event
	 */
	public function generateForVoucherRefunds(Refund\Event\TransactionalEvent $event)
	{
		$refund = $event->getRefund();

		// Skip unless the refund is using the "voucher" method
		if ('voucher' !== $refund->method->getName()) {
			return false;
		}

		$voucher             = new Voucher;
		$voucher->currencyID = $refund->currencyID;
		$voucher->amount     = $refund->amount;
		$voucher->id         = $this->_idGenerator->generate();

		$this->_create->setTransaction($event->getTransaction());
		$this->_create->create($voucher);

		$refund->reference = $voucher->id;

		$event->setRefund($refund);
	}
}

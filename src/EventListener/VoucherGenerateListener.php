<?php

namespace Message\Mothership\Voucher\EventListener;

use Message\Mothership\Voucher\Voucher;
use Message\Mothership\Voucher\Create;
use Message\Mothership\Voucher\IdGenerator;

use Message\Mothership\Commerce\Order;

use Message\Cog\Event\EventListener;
use Message\Cog\Event\SubscriberInterface;

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
	protected $_voucherProductIDs;

	/**
	 * {@inheritDoc}
	 */
	static public function getSubscribedEvents()
	{
		return array(
			Order\Entity\Item\Events::CREATE_PRE_PERSONALISATION_INSERTS => array(
				array('generateForSales'),
			),
			Order\Events::ENTITY_CREATE => array(
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
	public function __construct(Create $create, IdGenerator $idGenerator, array $voucherProductIDs)
	{
		$this->_create            = $create;
		$this->_idGenerator       = $idGenerator;
		$this->_voucherProductIDs = $voucherProductIDs;
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

		$voucher = new Voucher;
		$voucher->currencyID      = $item->order->currencyID;
		$voucher->amount          = $item->listPrice;
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
	 * @param Transaction\Event $event
	 */
	public function generateForVoucherRefunds(Order\Event\EntityEvent $event)
	{
		$refund = $event->getEntity();

		// Skip unless the entity is a refund with a method of "voucher"
		if (!($refund instanceof Order\Entity\Refund\Refund)
		 || 'voucher' !== $refund->method->getName()) {
			return false;
		}

		$voucher             = new Voucher;
		$voucher->currencyID = $refund->order->currencyID;
		$voucher->amount     = $refund->amount;
		$voucher->id         = $this->_idGenerator->generate();

		$this->_create->setTransaction($event->getTransaction());
		$this->_create->create($voucher);

		$refund->reference = $voucher->id;
	}
}
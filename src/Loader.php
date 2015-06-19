<?php

namespace Message\Mothership\Voucher;

use Message\Mothership\Commerce\Order\Entity\Item\Loader as ItemLoader;
use Message\Mothership\Commerce\Payment;

use Message\Cog\DB;
use Message\Cog\ValueObject\DateTimeImmutable;
use Message\Mothership\Voucher\ProductType\VoucherType;

/**
 * Face-value voucher loader.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class Loader
{
	private $_queryBuilderFactory;
	private $_queryBuilder;

	private $_itemLoader;
	private $_paymentLoader;
	private $_returnAsArray;

	public function __construct(DB\QueryBuilderFactory $queryBuilderFactory, ItemLoader $itemLoader, Payment\Loader $paymentLoader)
	{
		$this->_queryBuilderFactory = $queryBuilderFactory;
		$this->_itemLoader          = $itemLoader;
		$this->_paymentLoader       = $paymentLoader;
	}

	public function getByID($id, $returnAsArray = false)
	{
		$this->_setQueryBuilder();

		$this->_returnAsArray = $returnAsArray || is_array($id);

		if (empty($id)) {
			return $this->_returnAsArray ? [] : false;
		}

		$this->_queryBuilder
			->where('voucher.voucher_id IN (?sj)', [(array) $id])
		;

		return $this->_load();
	}

	public function getProductIDs()
	{
		return $this->_queryBuilderFactory->getQueryBuilder()
			->select('`product_id`')
			->from('`product`')
			->where("`type` = '" . VoucherType::TYPE_NAME . "'")
			->getQuery()
			->run()
			->flatten()
		;
	}

	/**
	 * Get outstanding vouchers (vouchers that are not fully used and have not
	 * expired).
	 *
	 * @return array[Voucher] Outstanding vouchers
	 */
	public function getOutstanding()
	{
		$this->_setQueryBuilder();

		$this->_returnAsArray = true;

		$this->_queryBuilder
			->where('voucher.used_at IS NULL')
			->where('(voucher.expires_at IS NULL OR voucher.expires_at > UNIX_TIMESTAMP())')
		;

		return $this->_load();
	}

	private function _setQueryBuilder()
	{
		$this->_queryBuilder = $this->_queryBuilderFactory
			->getQueryBuilder()
			->select(['*', 'voucher.voucher_id AS id', 'voucher.currency_id AS currencyID'])
			->from('voucher')
		;
	}

	private function _load()
	{
		$result = $this->_queryBuilder
			->getQuery()
			->run()
		;

		if (0 === count($result)) {
			return $this->_returnAsArray ? [] : false;
		}

		$vouchers = $result->bindTo('Message\\Mothership\\Voucher\\Voucher');
		$return   = array();

		foreach ($result as $key => $row) {
			// Cast decimals to float
			$vouchers[$key]->amount = (float) $row->amount;

			// Set create metadata
			$vouchers[$key]->authorship->create(
				new DateTimeImmutable(date('c', $row->created_at)),
				$row->created_by
			);

			// Cast dates to DateTimeImmutable
			if ($row->starts_at) {
				$vouchers[$key]->startsAt = new DateTimeImmutable(date('c', $row->starts_at));
			}

			if ($row->expires_at) {
				$vouchers[$key]->expiresAt = new DateTimeImmutable(date('c', $row->expires_at));
			}

			if ($row->used_at) {
				$vouchers[$key]->usedAt = new DateTimeImmutable(date('c', $row->used_at));
			}

			// Get the item the voucher was purchased as
			if ($row->purchased_as_item_id) {
				$vouchers[$key]->purchasedAsItem = $this->_itemLoader->getByID($row->purchased_as_item_id);
			}

			// Get order payments where this voucher was used
			$payments = $this->_paymentLoader->getByMethodAndReference('voucher', $row->id);

			// Ensure the payments are in an array
			if (!is_array($payments)) {
				$payments = array($payments);
			}

			// Set the payments as the usage for the voucher
			foreach ($payments as $payment) {
				if ($payment instanceof Payment\Payment) {
					$vouchers[$key]->usage[] = $payment;
				}
			}

			$return[$row->id] = $vouchers[$key];
		}

		$this->_queryBuilder = null;

		return $this->_returnAsArray || count($return) > 1 ? $return : reset($return);
	}
}
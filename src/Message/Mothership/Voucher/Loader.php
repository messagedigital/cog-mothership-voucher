<?php

namespace Message\Mothership\Voucher;

use Message\Mothership\Commerce\Order\Entity\Item\Loader as ItemLoader;
use Message\Mothership\Commerce\Order\Entity\Payment\Loader as PaymentLoader;
use Message\Mothership\Commerce\Order\Entity\Payment\Payment;

use Message\Cog\DB;
use Message\Cog\ValueObject\DateTimeImmutable;

/**
 * Face-value voucher loader.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class Loader
{
	protected $_query;
	protected $_itemLoader;
	protected $_paymentLoader;

	public function __construct(DB\Query $query, ItemLoader $itemLoader, PaymentLoader $paymentLoader)
	{
		$this->_query         = $query;
		$this->_itemLoader    = $itemLoader;
		$this->_paymentLoader = $paymentLoader;
	}

	public function getByID($id)
	{
		return $this->_load($id, false);
	}

	/**
	 * Get outstanding vouchers (vouchers that are not fully used and have not
	 * expired).
	 *
	 * @return array[Voucher] Outstanding vouchers
	 */
	public function getOutstanding()
	{
		$result = $this->_query->run('
			SELECT
				voucher_id
			FROM
				voucher
			WHERE
				used_at IS NULL
			AND (expires_at IS NULL OR expires_at > UNIX_TIMESTAMP())
		');

		return $this->_load($result->flatten(), true);
	}

	public function _load($ids, $alwaysReturnArray = false)
	{
		if (!is_array($ids)) {
			$ids = (array) $ids;
		}

		if (!$ids) {
			return $alwaysReturnArray ? array() : false;
		}

		$result = $this->_query->run('
			SELECT
				*,
				voucher_id  AS id,
				currency_id AS currencyID
			FROM
				voucher
			WHERE
				voucher_id IN (?sj)
		', array($ids));

		if (0 === count($result)) {
			return $alwaysReturnArray ? array() : false;
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
				if ($payment instanceof Payment) {
					$vouchers[$key]->usage[] = $payment;
				}
			}

			$return[$row->id] = $vouchers[$key];
		}

		return $alwaysReturnArray || count($return) > 1 ? $return : reset($return);
	}
}
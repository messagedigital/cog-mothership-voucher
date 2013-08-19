<?php

namespace Message\Mothership\Voucher;

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

	public function _load($ids, $alwaysReturnArray = false)
	{
	// public $authorship;

	// public $code;
	// public $currencyID;
	// public $amount;

	// public $expiresAt;
	// public $usedAt;
	// public $purchasedAsItem;
	// public $usage = array();

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

			$vouchers[$key]->authorship->create(
				new DateTimeImmutable(date('c', $row->created_at)),
				$row->created_by
			);

			$vouchers[$key]->expiresAt = new DateTimeImmutable(date('c', $row->expires_at));
			$vouchers[$key]->usedAt    = new DateTimeImmutable(date('c', $row->used_at));

			$vouchers[$key]->purchasedAsItem = $this->_itemLoader->getByID($row->purchased_as_item_id);

			$vouchers[$key]->usage = $this->_paymentLoader->getByTypeAndReference('voucher', $row->id);

			$return[$row->id] = $vouchers[$key];
		}

		return $alwaysReturnArray || count($return) > 1 ? $return : reset($return);
	}
}
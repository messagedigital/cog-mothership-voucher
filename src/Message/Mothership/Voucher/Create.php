<?php

namespace Message\Mothership\Voucher;

use Message\Cog\ValueObject\DateTimeImmutable;

/**
 * Face-value voucher creator.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class Create implements DB\TransactionalInterface
{
	protected $_query;
	protected $_loader;
	protected $_currentUser;

	protected $_idLength;
	protected $_expiryInterval;

	public function __construct(DB\Query $query, Loader $loader, UserInterface $currentUser)
	{
		$this->_query       = $query;
		$this->_loader      = $loader;
		$this->_currentUser = $currentUser;
	}

	public function setTransaction(DB\Transaction $trans)
	{
		$this->_query = $trans;
	}

	public function setIdLength($length)
	{
		$this->_idLength = $length;
	}

	public function setExpiryInterval(\DateInterval $interval)
	{
		$this->_expiryInterval = $interval;
	}

	public function create(Voucher $voucher)
	{
		// Set created metadata if not already set
		if (!$voucher->authorship->createdAt()) {
			$voucher->authorship->create(
				new DateTimeImmutable,
				$this->_currentUser->id
			);
		}

		// Set the expiry date if it's not already set & there is an expiry interval defined
		if ($this->_expiryInterval && !$voucher->expiresAt) {
			$voucher->expiresAt = $voucher->authorship->createdAt()->add($this->_expiryInterval);
		}

		$this->_validate($voucher);

		// set expiry date if one is not set using the CFG

		$this->_query->run('
			INSERT INTO
				voucher
			SET
				voucher_id           = :voucherID?s,
				created_at           = :createdAt?d,
				created_by           = :createdBy?in,
				expires_at           = :expiresAt?dn,
				purchased_as_item_id = :purchasedAsItemID?in,
				currency_id          = :currencyID?s,
				amount               = :amount?f
		', array(
			'voucherID'         => $voucher->id,
			'createdAt'         => $voucher->authorship->createdAt(),
			'createdBy'         => $voucher->authorship->createdBy(),
			'expiresAt'         => $voucher->expiresAt,
			'purchasedAsItemID' => $voucher->purchasedAsItem ? $voucher->purchasedAsItem->id : null,
			'currencyID'        => $voucher->currencyID,
			'amount'            => $voucher->amount,
		));

		if ($this->_query instanceof DB\Transaction) {
			return $voucher;
		}

		return $this->_loader->getByID($voucher->id);
	}

	/**
	 * Validate a voucher to ensure it can be created.
	 *
	 * @param  Voucher $voucher The voucher to validate
	 *
	 * @throws \InvalidArgumentException If the voucher ID is not set
	 * @throws \InvalidArgumentException If the amount is not a positive value
	 * @throws \InvalidArgumentException If the length of the ID is different to
	 *                                   the configured ID length (if defined)
	 * @throws [exceptionType] If [this condition is met]
	 */
	protected function _validate(Voucher $voucher)
	{
		if (!$voucher->id) {
			throw new \InvalidArgumentException('Cannot create voucher: ID is not set');
		}

		if (!$voucher->id || $voucher->amount <= 0) {
			throw new \InvalidArgumentException('Cannot create voucher: amount is not a positive value');
		}

		if ($this->_idLength && strlen($voucher->id) !== $this->_idLength) {
			throw new \InvalidArgumentException(sprintf(
				'Cannot create voucher: ID must be `%s` characters long, `%s` given',
				$this->_idLength,
				$voucher->id
			));
		}

		if (!$voucher->currencyID) {
			throw new \InvalidArgumentException('Cannot create voucher: currency ID is not set');
		}

		if ($this->_loader->getByID($voucher->id) instanceof Voucher) {
			throw new \InvalidArgumentException(sprintf(
				'Cannot create voucher: a voucher already exists with ID `%s`',
				$voucher->id
			));
		}

		if ($voucher->usedAt) {
			throw new \InvalidArgumentException('Cannot create voucher: it is already marked as used');
		}
	}
}
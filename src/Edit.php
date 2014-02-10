<?php

namespace Message\Mothership\Voucher;

use Message\User\UserInterface;

use Message\Cog\DB;
use Message\Cog\ValueObject\DateTimeImmutable;

/**
 * Face-value voucher editor.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class Edit implements DB\TransactionalInterface
{
	protected $_query;
	protected $_currentUser;

	/**
	 * Constructor.
	 *
	 * @param DB\Query      $query       Database query instance
	 * @param UserInterface $currentUser The currently logged-in user
	 */
	public function __construct(DB\Query $query, UserInterface $currentUser)
	{
		$this->_query       = $query;
		$this->_currentUser = $currentUser;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param DB\Transaction $trans The transaction to use
	 */
	public function setTransaction(DB\Transaction $trans)
	{
		$this->_query = $trans;
	}

	/**
	 * Set the used date/time for a voucher.
	 *
	 * @param Voucher   $voucher The voucher
	 * @param \DateTime $used    The used date/time
	 *
	 * @return Voucher           The updated voucher
	 *
	 * @throws \InvalidArgumentException If the voucher is already marked as used
	 * @throws \InvalidArgumentException If the used date/time is in the future
	 */
	public function setUsed(Voucher $voucher, \DateTime $used)
	{
		if ($voucher->usedAt instanceof \DateTime) {
			throw new \InvalidArgumentException(sprintf('Voucher `%s` already marked as used.', $voucher->id));
		}

		if ($used > new \DateTime) {
			throw new \InvalidArgumentException(sprintf('Cannot set voucher used date in the future for voucher `%s`', $voucher->id));
		}

		$voucher->usedAt = $used;

		$this->_query->run('
			UPDATE
				voucher
			SET
				used_at = ?d
			WHERE
				voucher_id = ?s
		', array($used, $voucher->id));

		return $voucher;
	}

	/**
	 * Set the expiry date/time for a voucher.
	 *
	 * @param Voucher   $voucher The voucher
	 * @param \DateTime $expiry  The expiry date/time
	 *
	 * @return Voucher           The updated voucher
	 */
	public function setExpiry(Voucher $voucher, \DateTime $expiry)
	{
		$voucher->expiresAt = $expiry;

		$this->_query->run('
			UPDATE
				voucher
			SET
				expires_at = ?d
			WHERE
				voucher_id = ?s
		', array($expiry, $voucher->id));

		return $voucher;
	}

}
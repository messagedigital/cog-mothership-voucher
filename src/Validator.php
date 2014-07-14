<?php

namespace Message\Mothership\Voucher;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * Validates vouchers and generates the relevant error strings if they are not
 * valid.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class Validator
{
	const TRANS_KEY_NO_BALANCE  = 'ms.voucher.add.error.no-balance';
	const TRANS_KEY_EXPIRED     = 'ms.voucher.add.error.expired';
	const TRANS_KEY_NOT_STARTED = 'ms.voucher.add.error.not-started';

	protected $_translator;

	/**
	 * Constructor.
	 *
	 * @param TranslatorInterface $translator Translator to use to get errors
	 */
	public function __construct(TranslatorInterface $translator)
	{
		$this->_translator = $translator;
	}

	/**
	 * Check whether a voucher is usable or not, this checks all of the specific
	 * validity criteria at the same time.
	 *
	 * @param  Voucher  $voucher
	 *
	 * @return boolean
	 */
	public function isUsable(Voucher $voucher)
	{
		return $this->hasBalance($voucher)
			&& $this->isStarted($voucher)
			&& $this->isNotExpired($voucher);
	}

	/**
	 * Check whether a voucher has any balance remaining.
	 *
	 * @param  Voucher $voucher
	 *
	 * @return boolean
	 */
	public function hasBalance(Voucher $voucher)
	{
		return $voucher->getBalance() > 0;
	}

	/**
	 * Check whether a voucher has started. If no start date/time is defined,
	 * this method will return `true`.
	 *
	 * @param  Voucher $voucher
	 *
	 * @return boolean
	 */
	public function isStarted(Voucher $voucher)
	{
		return !$voucher->startsAt || $voucher->startsAt <= new \DateTime;
	}

	/**
	 * Check whether a voucher has not yet expired. If no expiry date/time is
	 * defined, this method will return `true`.
	 *
	 * @param  Voucher $voucher
	 *
	 * @return boolean
	 */
	public function isNotExpired(Voucher $voucher)
	{
		return !$voucher->expiresAt || $voucher->expiresAt > new \DateTime;
	}

	/**
	 * Get the relevant error for an invalid voucher as a string, or `null` if
	 * the voucher is valid
	 *
	 * @param  Voucher $voucher
	 *
	 * @return string|null
	 */
	public function getError(Voucher $voucher)
	{
		if (!$this->isStarted($voucher)) {
			return $this->_translator->trans(self::TRANS_KEY_NOT_STARTED, [
				'%id%'         => $voucher->id,
				'%start_date%' => $voucher->startsAt->format('Y-m-d g:i a'),
			]);
		}

		if (!$this->isNotExpired($voucher)) {
			return $this->_translator->trans(self::TRANS_KEY_EXPIRED, [
				'%id%' => $voucher->id,
			]);
		}

		if (!$this->hasBalance($voucher)) {
			return $this->_translator->trans(self::TRANS_KEY_NO_BALANCE, [
				'%id%' => $voucher->id,
			]);
		}

		return null;
	}
}
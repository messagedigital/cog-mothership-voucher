<?php

namespace Message\Mothership\Voucher\Test;

use Message\Mothership\Voucher\Validator;
use Message\Mothership\Voucher\Voucher;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
	protected $_validator;

	public function setUp()
	{
		$this->_translator = $this->getMock('Symfony\\Component\\Translation\\TranslatorInterface');
		$this->_validator = new Validator($this->_translator);
	}

	public function testHasBalance()
	{
		$this->assertFalse($this->_validator->hasBalance(new Voucher));

		$voucher = new Voucher;
		$voucher->amount = 10;

		$this->assertTrue($this->_validator->hasBalance($voucher));

		$payment = $this->getMock('Message\\Mothership\\Order\\Entity\\Payment\\Payment');
		$payment->amount = 1;

		$voucher->usage[] = $payment;

		$this->assertTrue($this->_validator->hasBalance($voucher));

		$payment = $this->getMock('Message\\Mothership\\Order\\Entity\\Payment\\Payment');
		$payment->amount = 9;

		$voucher->usage[] = $payment;

		$this->assertFalse($this->_validator->hasBalance($voucher));
	}

	public function testIsStartedWithNoStartDate()
	{
		$this->assertTrue($this->_validator->isStarted(new Voucher));
	}

	public function testIsStartedWithStartDates()
	{
		$voucher = new Voucher;
		$voucher->startsAt = new \DateTime('-1 day');

		$this->assertTrue($this->_validator->isStarted($voucher));

		$voucher->startsAt = new \DateTime('+1 hour');

		$this->assertFalse($this->_validator->isStarted($voucher));

		$voucher->startsAt = new \DateTime;

		$this->assertTrue($this->_validator->isStarted($voucher));
	}

	public function testIsNotExpiredWithNoExpiry()
	{
		$this->assertTrue($this->_validator->isNotExpired(new Voucher));
	}

	public function testIsNotExpiredWithExpiryDates()
	{
		$voucher = new Voucher;
		$voucher->expiresAt = new \DateTime('-1 day');

		$this->assertFalse($this->_validator->isNotExpired($voucher));

		$voucher->expiresAt = new \DateTime('+1 hour');

		$this->assertTrue($this->_validator->isNotExpired($voucher));

		$voucher->expiresAt = new \DateTime;

		$this->assertFalse($this->_validator->isNotExpired($voucher));
	}

	public function testIsUsable()
	{
		$this->assertTrue($this->_validator->isUsable($this->_getUsableVoucher()));

		// Try a voucher with everything but a balance
		$voucher = $this->_getUsableVoucher();
		$payment = $this->getMock('Message\\Mothership\\Order\\Entity\\Payment\\Payment');
		$payment->amount = 500;

		$voucher->usage[] = $payment;

		$this->assertFalse($this->_validator->isUsable($voucher));

		// Try a voucher with everything but is not started
		$voucher = $this->_getUsableVoucher();
		$voucher->startsAt = new \DateTime('+1 day');

		$this->assertFalse($this->_validator->isUsable($voucher));

		// Try a voucher with everything but is expired
		$voucher = $this->_getUsableVoucher();
		$voucher->expiresAt = new \DateTime('-1 month');

		$this->assertFalse($this->_validator->isUsable($voucher));
	}

	public function testGetErrors()
	{
		$voucher = $this->_getUsableVoucher();
		$voucher->id = 'ABC1234';

		$invalidStartDate = new \DateTime('+1 hour');

		$this->assertNull($this->_validator->getError($voucher));

		$this->_translator
			->expects($this->at(0))
			->method('trans')
			->with(Validator::TRANS_KEY_NO_BALANCE, ['%id%' => $voucher->id])
			->will($this->returnValue('test string 1'));

		$this->_translator
			->expects($this->at(1))
			->method('trans')
			->with(Validator::TRANS_KEY_EXPIRED, ['%id%' => $voucher->id])
			->will($this->returnValue('test string 2'));

		$this->_translator
			->expects($this->at(2))
			->method('trans')
			->with(Validator::TRANS_KEY_NOT_STARTED, [
				'%id%'         => $voucher->id,
				'%start_date%' => $invalidStartDate->format('Y-m-d g:i a'),
			])
			->will($this->returnValue('test string 3'));

		$payment = $this->getMock('Message\\Mothership\\Order\\Entity\\Payment\\Payment');
		$payment->amount = 500;

		$voucher->usage[] = $payment;

		$this->assertSame('test string 1', $this->_validator->getError($voucher));

		$voucher->expiresAt = new \DateTime('-1 day');

		$this->assertSame('test string 2', $this->_validator->getError($voucher));

		$voucher->startsAt = $invalidStartDate;

		$this->assertSame('test string 3', $this->_validator->getError($voucher));
	}

	protected function _getUsableVoucher()
	{
		$voucher = new Voucher;
		$voucher->expiresAt = new \DateTime('+5 days');
		$voucher->startsAt  = new \DateTime('-2 days');
		$voucher->amount    = 500;

		return $voucher;
	}
}
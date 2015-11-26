<?php

namespace Message\Mothership\Voucher\Exception;

/**
 * Class EVoucherSendException
 * @package Message\Mothership\Voucher\Exception
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Exception to throw when an e-voucher cannot be distributed. Implements Cog's TranslationExceptionInterface
 * so takes a second parameter of a translation string, and a third parameter of translation params.
 */
class EVoucherSendException extends VoucherDisplayException
{

}
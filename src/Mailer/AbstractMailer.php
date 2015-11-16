<?php

namespace Message\Mothership\Voucher\Mailer;

use Message\Cog\Mail\Message;
use Message\Cog\Mail\Mailer;
use Message\Cog\Localisation\Translator;

/**
 * Class AbstractMailer
 * @package Message\Mothership\Voucher\Mailer
 *
 * @author Thomas Marchant <thomas@mothership.ec>
 */
abstract class AbstractMailer
{
	protected $_message;
	private $_mailer;

	/**
	 * @param Mailer $mailer
	 * @param Message $message
	 * @param Translator $translator
	 */
	public function __construct(Mailer $mailer, Message $message, Translator $translator)
	{
		$this->_mailer     = $mailer;
		$this->_message    = $message;
		$this->_translator = $translator;
	}

	protected function _send()
	{
		$failed = [];
<<<<<<< HEAD
		$this->_mailer->Send($this->_message, $failed);
=======
		$this->_mailer->send($this->_message, $failed);
>>>>>>> develop

		if (count($failed) > 0) {
			throw new \RuntimeException('Failed to send email to ' . implode(', ', $failed));
		}
	}
}
<?php

namespace Message\Mothership\Voucher\Mailer;

use Message\Mothership\Voucher\Voucher;
use Message\User;

/**
 * Class EVoucherMailer
 * @package Message\Mothership\Voucher\Mailer
 *
 * @author Thomas Marchant <thomas@mothership.ec>
 */
class EVoucherMailer extends AbstractMailer
{
	public function sendVoucher(Voucher $voucher, User\UserInterface $user)
	{
		if ($user instanceof User\AnonymousUser) {
			throw new \LogicException('Cannot send email to anonymous user!');
		}

		$this->_message->setTo($user->email, $user->getName());
		$this->_message->setSubject($this->_translator->trans('ms.voucher.evoucher.email.subject'));

		$this->_message->setView('Message:Mothership:Voucher::mail:evoucher', [
			'voucher' => $voucher,
			'user'    => $user
		]);

		$this->_send();
	}
}
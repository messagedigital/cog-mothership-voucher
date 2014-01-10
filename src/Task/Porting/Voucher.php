<?php

namespace Message\Mothership\Voucher\Task\Porting;

use Message\Cog\Console\Task\Task;
use Symfony\Component\Console\Input\InputArgument;
use Message\Cog\DB\Adapter\MySQLi\Connection;

class Voucher extends Task
{

	protected function configure()
	{
		$this
			->addArgument(
				'old',
				InputArgument::REQUIRED,
				'please pass in the name of the service as the last parameter'
			);
	}

	/**
	 * Gets the DB connection to port the data from
	 *
	 * @return Connection 		instance of the DB Connection
	 */
	public function getFromConnection()
	{
		$serviceName = $this->getRawInput()->getArgument('old');
		$service = $this->get($serviceName);

		if (!$service instanceof Connection) {
			throw new \Exception('service must be instance of Message\Cog\DB\Adapter\MySQLi\Connection');
		}

		return $service;
	}

	/**
	 * Gets the DB connection to port the data into
	 *
	 * @return Connection 		instance of the DB Connection
	 */
	public function getToConnection()
	{
		return new \Message\Cog\DB\Adapter\MySQLi\Connection(array(
				'host'		=> $this->get('cfg')->db->hostname,
				'user'		=> $this->get('cfg')->db->user,
				'password' 	=> $this->get('cfg')->db->pass,
				'db'		=> $this->get('cfg')->db->name,
				'charset'	=> 'utf-8',
		));
	}

    public function process()
    {
        $uwOld = $this->getFromConnection();
		$uwNew = $this->getToCOnnection();

		$new = new \Message\Cog\DB\Transaction($uwNew);
		$newQuery = new \Message\Cog\DB\Query($uwNew);
		$old = new \Message\Cog\DB\Query($uwOld);

		$sql = 'SELECT
					val_gift.gift_id             AS voucher_id,
					UNIX_TIMESTAMP(created_date) AS created_at,
					NULL                         AS created_by,
					UNIX_TIMESTAMP(expiry_date)  AS expires_at,
					UNIX_TIMESTAMP(used_date)    AS used_at,
					item_id                      AS purchased_as_item_id,
					currency_id                  AS currency_id,
					price                        AS amount
				FROM
					val_gift
				JOIN
					att_gift_price ON (val_gift.gift_id = att_gift_price.gift_id AND currency_id = \'GBP\')
				LEFT JOIN
					order_item ON (order_item.item_note = val_gift.gift_id)
				WHERE
					val_gift.gift_id IS NOT NULL AND val_gift.gift_id != \'\'';

		$result = $old->run($sql);
		$output= '';

		$new->add('TRUNCATE voucher');

		foreach($result as $row) {
			$new->add('
				INSERT INTO
					voucher
				(
					voucher_id,
					created_at,
					created_by,
					expires_at,
					used_at,
					purchased_as_item_id,
					currency_id,
					amount
				)
				VALUES
				(
					:voucher_id?,
					:created_at?,
					:created_by?,
					:expires_at?,
					:used_at?,
					:purchased_as_item_id?,
					:currency_id?,
					:amount
				)', (array) $row
			);
		}

		if ($new->commit()) {
        	$output.= '<info>Successful</info>';
		}

		return $ouput;
    }
}
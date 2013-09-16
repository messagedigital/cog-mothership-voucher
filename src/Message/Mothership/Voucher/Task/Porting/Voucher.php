<?php

namespace Message\Mothership\Voucher\Task\Porting;

use Message\Cog\Console\Task\Task;

class Voucher extends Task
{

	/**
	 * Gets the DB connection to port the data from
	 *
	 * @return Connection 		instance of the DB Connection
	 */
	public function getFromConnection()
	{
        return new \Message\Cog\DB\Adapter\MySQLi\Connection(array(
				'host'		=> '192.168.201.99',
				'user'		=> 'danny',
				'password' 	=> 'chelsea',
				'db'		=> 'uniform_wares',
				'charset'	=> 'utf-8',
		));
	}

	/**
	 * Gets the DB connection to port the data into
	 *
	 * @return Connection 		instance of the DB Connection
	 */
	public function getToConnection()
	{

		return new \Message\Cog\DB\Adapter\MySQLi\Connection(array(
				'host'		=> '192.168.201.99',
				'user'		=> 'danny',
				'password' 	=> 'chelsea',
				'db'		=> 'mothership_cms',
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
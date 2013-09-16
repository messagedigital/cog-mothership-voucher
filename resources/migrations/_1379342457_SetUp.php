<?php

use Message\Cog\Migration\Adapter\MySQL\Migration;

class _1379342457_SetUp extends Migration
{
	public function up()
	{
		$this->run("
			CREATE TABLE `voucher` (
			  `voucher_id` varchar(30) NOT NULL DEFAULT '',
			  `created_at` int(11) unsigned NOT NULL,
			  `created_by` int(11) unsigned DEFAULT NULL,
			  `expires_at` int(11) unsigned DEFAULT NULL,
			  `used_at` int(11) unsigned DEFAULT NULL,
			  `purchased_as_item_id` int(11) unsigned DEFAULT NULL,
			  `currency_id` char(3) NOT NULL DEFAULT '',
			  `amount` decimal(10,2) unsigned NOT NULL,
			  PRIMARY KEY (`voucher_id`),
			  KEY `created_at` (`created_at`),
			  KEY `created_by` (`created_by`),
			  KEY `expires_at` (`expires_at`),
			  KEY `used_at` (`used_at`),
			  KEY `purchased_as_item_id` (`purchased_as_item_id`),
			  KEY `currency_id` (`currency_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
	}

	public function down()
	{
		$this->run('
			DROP TABLE IF EXISTS
				voucher
		');
	}
}

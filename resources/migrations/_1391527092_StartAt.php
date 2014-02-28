<?php

use Message\Cog\Migration\Adapter\MySQL\Migration;

class _1391527092_StartAt extends Migration
{
	public function up()
	{
		$this->run('
			ALTER TABLE voucher ADD starts_at int(11) unsigned DEFAULT NULL AFTER created_by, add key (starts_at);
		');
	}

	public function down()
	{
		$this->run('
			ALTER TABLE voucher DROP starts_at;
		');
	}
}

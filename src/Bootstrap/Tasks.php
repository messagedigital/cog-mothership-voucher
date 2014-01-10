<?php

namespace Message\Mothership\Voucher\Bootstrap;

use Message\Cog\Bootstrap\TasksInterface;
use Message\Mothership\Voucher\Task;

class Tasks implements TasksInterface
{
    public function registerTasks($tasks)
    {
        $tasks->add(new Task\Porting\Voucher('vouchers:porting:port_vouchers'), 'Ports gift voucher data from pre mothership');

    }
}
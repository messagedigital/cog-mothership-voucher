<?php

namespace Message\Mothership\Voucher\Bootstrap;

use Message\Cog\Bootstrap\TasksInterface;
use Message\Mothership\Voucher\Task;

class Tasks implements TasksInterface
{
    public function registerTasks($tasks)
    {
        $tasks->add(new Task\UpdateVoucherProductTypes('vouchers:set_product_types'), 'Sets product types for vouchers in config');

    }
}
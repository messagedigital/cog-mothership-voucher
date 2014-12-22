<?php

namespace Message\Mothership\Voucher\Task;

use Message\Mothership\Voucher\ProductType\VoucherType;
use Message\Cog\Console\Task\Task;

/**
 * Class UpdateVoucherProductTypes
 * @package Message\Mothership\Voucher\Task
 *
 * @author Thomas Marchant <thomas@message.co.uk>
 *
 * Set voucher products from old configs to have a type of 'voucher'
 */
class UpdateVoucherProductTypes extends Task
{
	public function process()
	{
		if (empty($this->get('cfg')->voucher) || empty($this->get('cfg')->voucher->productIDs)) {
			$this->writeln('<info>No voucher product IDs found</info>');
		}

		$productIDs = (array) $this->get('cfg')->voucher->productIDs;

		$this->writeln('Voucher product IDs: ' . implode(', ', $productIDs));

		$this->writeln('Running query');

		$this->get('db.query')->run("
			UPDATE
				product
			SET
				type = :type?s
			WHERE
				product_id IN (:productIDs?ji)
		", [
			'type'       => VoucherType::TYPE_NAME,
			'productIDs' => $productIDs,
		]);

		$this->writeln('Update complete');
	}
}
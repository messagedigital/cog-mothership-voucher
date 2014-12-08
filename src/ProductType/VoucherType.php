<?php

namespace Message\Mothership\Voucher\ProductType;

use Message\Mothership\Commerce\Product\Type\ProductTypeInterface;
use Message\Mothership\Commerce\Product\Product;
use Message\Cog\Field\Factory;

class VoucherType implements ProductTypeInterface
{
	const TYPE_NAME = 'voucher';

	public function getName()
	{
		return self::TYPE_NAME;
	}

	public function getDisplayName()
	{
		return 'Voucher product';
	}

	public function getDescription()
	{
		return 'A product relating to a voucher';
	}

	public function setFields(Factory $factory, Product $product = null)
	{}

	public function getProductDisplayName(Product $product)
	{
		return $product->name;
	}
}
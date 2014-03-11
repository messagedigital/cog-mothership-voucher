<?php

namespace Message\Mothership\Voucher;

use Message\Cog\Security\StringGenerator;

/**
 * Generates IDs that can be used by a new voucher.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class IdGenerator
{
	protected $_stringGenerator;
	protected $_loader;
	protected $_length;

	/**
	 * Constructor.
	 *
	 * @param Salt   $stringGenerator Random string generator
	 * @param Loader $loader          Voucher loader
	 * @param int    $length          String length of voucher IDs
	 */
	public function __construct(StringGenerator $stringGenerator, Loader $loader, $length)
	{
		$this->_stringGenerator = $stringGenerator;
		$this->_loader          = $loader;
		$this->_length          = (int) $length;
	}

	/**
	 * Generate a new ID to use on a gift voucher that is not being used by any
	 * other voucher.
	 *
	 * @return string The unique ID
	 */
	public function generate()
	{
		do {
			$id = $this->_stringGenerator->setPattern('/^[A-HJ-KM-NP-Z2-9]+$/')->generate($this->_length);
			if (!$this->_idExists($id)) {
				break;
			}

		} while($this->_idExists($id));

		return $id;
	}

	/**
	 * Check if a given ID is already being used by another voucher.
	 *
	 * @param  string $id The voucher ID to check
	 *
	 * @return boolean    True if it is already in use, false otherwise
	 */
	protected function _idExists($id)
	{
		$voucher = $this->_loader->getByID($id);

		return ($voucher instanceof Voucher);
	}
}
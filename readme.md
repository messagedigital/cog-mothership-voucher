# Mothership Vouchers

This cogule provides face-value voucher functionality for Mothership commerce systems installations. Face-value vouchers can represent "gift vouchers", "credit notes", or any other way the client may wish to market them. They are essentially a code that represents a pre-payment for an order, and they can be partially used and retain a balance.

## Todo

- Implement electronic gift vouchers (products tagged for automatic dispatch?)
	- event listener to email e-vouchers if purchased (can come later)
- Stop discounts being applied to voucher products
	- How to abstract this from commerce sensibly?
	- New event listener to clear the discount amounts?

## License

Mothership E-Commerce
Copyright (C) 2015 Jamie Freeman

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.

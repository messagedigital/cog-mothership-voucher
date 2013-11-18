# Mothership Vouchers

This cogule provides face-value voucher functionality for Mothership commerce systems installations. Face-value vouchers can represent "gift vouchers", "credit notes", or any other way the client may wish to market them. They are essentially a code that represents a pre-payment for an order, and they can be partially used and retain a balance.

## Todo

- Implement electronic gift vouchers (products tagged for automatic dispatch?)
	- event listener to email e-vouchers if purchased (can come later)
- Stop discounts being applied to voucher products
	- How to abstract this from commerce sensibly?
	- New event listener to clear the discount amounts?
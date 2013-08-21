# Mothership Vouchers

This cogule provides face-value voucher functionality for Mothership commerce systems installations. Face-value vouchers can represent "gift vouchers", "credit notes", or any other way the client may wish to market them. They are essentially a code that represents a pre-payment for an order, and they can be partially used and retain a balance.

## Todo

- Implement electronic gift vouchers (products tagged for automatic dispatch?)
	- event listener to email e-vouchers if purchased (can come later)
- order assembler things of vouchers being used as "payments" on the prepared order
- SOP needs to somehow let you print the voucher if there is one in the order
- Offers interface
	- Create voucher
	- Invalidate (expire) voucher
	- View voucher details (usage etc)
- Edit decorator
	- Set used
	- Set/change expiry?
- New user group with access level
- Stop discounts being applied to voucher products
	- How to abstract this from commerce sensibly?
	- New event listener to clear the discount amounts?
- Force voucher products tax rates to be 0? or does this only apply to the UK
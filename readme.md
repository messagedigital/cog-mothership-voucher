# Mothership Vouchers

This cogule provides face-value voucher functionality for Mothership commerce systems installations. Face-value vouchers can represent "gift vouchers", "credit notes", or any other way the client may wish to market them. They are essentially a code that represents a pre-payment for an order, and they can be partially used and retain a balance.

## Todo

- Implement electronic gift vouchers (products tagged for automatic dispatch?)
- order assembler things of vouchers being used as "payments" on the prepared order
- event listener for when order is created to check if any items are vouchers and generate them if so
- event listener to email e-vouchers if purchased (can come later)
- SOP needs to somehow let you print the voucher if there is one in the order
- Offers interface
	- Create voucher
	- Invalidate (expire) voucher
	- View voucher details (usage etc)
- Edit decorator
	- Set used
	- Set/change expiry?
- New user group with access level
# Mothership Vouchers

This cogule provides face-value voucher functionality for Mothership commerce systems installations. Face-value vouchers can represent "gift vouchers" or "credit notes". They are essentially the same thing.

## Todo

- New cogule for vouchers
- New payment method for voucher
- Model(s) for voucher
	- authorship data + expiry + used
	- gift voucher is always ONE currency
- config for setting up product IDs for gift vouchers
- config for setting which of these are electronic
- config for expiry time
- order assembler things of vouchers being used as "payments" on the prepared order
- event listener for when order is created to check if any items are vouchers and generate them if so
- event listener to email e-vouchers if purchased (can come later)
- SOP needs to somehow let you print the voucher if there is one in the order
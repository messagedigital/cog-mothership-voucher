# Changelog

## 2.1.2

- Fix issue where vouchers with slashes and dots in the voucher code would break the URLs in the admin panel (note: vouchers theoretically *shouldn't* have these characters, see <a href="https://github.com/mothership-ec/cog/issues/449">this issue</a>)

## 2.1.1

- Fix issue where EPOS orders would break on voucher validation by passing validator the epos order

## 2.1.0

- Electronic vouchers
- Voucher generation checks voucher product type as well as product IDs set in the config

## 2.0.0

- Initial open sourced release

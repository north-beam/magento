# Changelog

## 1.1.0 - 2026-10-23

- Declare PHP 8.2, 8.3, and 8.4 support in `composer.json` (`~7.4.0||~8.1.0||~8.2.0||~8.3.0||~8.4.0`), so merchants can move to a PHP line that still receives security fixes (for example the October 2026 PHP2Shell fixes, CVE-2026-109618 / CVE-2026-109619).
- Declare the `scopeConfig`, `order`, and `logger` properties on the `SendOrder` and `CancelOrder` observers. PHP 8.2 deprecated creating them dynamically.

## 1.0.0

- Initial release.

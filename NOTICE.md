# NOTICE - Third Party Licenses and Attributions

このファイルには、PHP-Training プロジェクトで使用されている第三者のコード、ライブラリ、リソースに関する著作権表示とライセンス情報が含まれています。

## PHP Dependencies (Composer)

### nesbot/carbon
- **License**: MIT
- **Copyright**: Copyright (c) Brian Nesbitt
- **Repository**: https://github.com/briannesbitt/Carbon
- **Files**: `learn_1/vendor/nesbot/carbon/`

### Symfony Components
- **License**: MIT
- **Copyright**: Copyright (c) Fabien Potencier
- **Repository**: https://github.com/symfony/symfony
- **Packages Used**:
  - symfony/translation
  - symfony/polyfill-mbstring
  - symfony/polyfill-php80
  - symfony/deprecation-contracts
  - symfony/translation-contracts

### carbonphp/carbon-doctrine-types
- **License**: MIT
- **Repository**: https://github.com/CarbonPHP/carbon-doctrine-types

### psr/clock
- **License**: MIT
- **Repository**: https://github.com/php-fig/clock

---

## CSS/JavaScript Frameworks (CDN)

### Bootstrap
- **License**: MIT
- **Copyright**: Copyright (c) 2011-2023 The Bootstrap Authors
- **Website**: https://getbootstrap.com/
- **Usage**: Used via CDN in `learn_2/` files
- **CDN**: https://stackpath.bootstrapcdn.com/bootstrap/

### Tailwind CSS
- **License**: MIT
- **Copyright**: Copyright (c) Tailwind Labs, Inc.
- **Website**: https://tailwindcss.com/
- **Usage**: Used via CDN in multiple files
- **CDN**: https://cdn.jsdelivr.net/npm/tailwindcss/

### jQuery
- **License**: MIT
- **Copyright**: Copyright OpenJS Foundation and other contributors
- **Website**: https://jquery.com/
- **Usage**: Used via CDN in `learn_2/` files
- **CDN**: https://code.jquery.com/

### Popper.js
- **License**: MIT
- **Copyright**: Copyright (c) 2019 Federico Zivolo
- **Website**: https://popper.js.org/
- **Usage**: Bootstrap dependency
- **CDN**: https://cdn.jsdelivr.net/npm/popper.js/

---

## UI Component Libraries

### Tailblocks
- **License**: MIT
- **Copyright**: Copyright (c) 2020 Tailblocks (@knyttneve)
- **Website**: https://tailblocks.cc/
- **Attribution**: © 2020 Tailblocks — @knyttneve
- **Files**: `nww/dashboard.blade.php`
- **Usage**: UI component templates based on Tailblocks

---

## Framework-Based Code

### Laravel Framework Templates
- **License**: MIT
- **Copyright**: Copyright (c) Taylor Otwell
- **Repository**: https://github.com/laravel/framework
- **Website**: https://laravel.com/
- **Files Influenced**:
  - `learn_7/welcome.blade.php` - Based on Laravel welcome template
  - `learn_8/welcome.blade.php` - Based on Laravel welcome template
  - `learn_7/PostController.php` - Laravel controller pattern
  - `learn_8/AuthServiceProvider.php` - Laravel service provider pattern
  - `learn_8/Kernel.php` - Laravel kernel pattern
  - Various other files following Laravel conventions

**Note**: These files are inspired by or based on Laravel framework templates and follow Laravel's architectural patterns. Laravel is licensed under the MIT License.

---

## Fonts

### Google Fonts (via Bunny Fonts CDN)
- **License**: SIL Open Font License, CC0, Apache 2.0 (varies by font)
- **Website**: https://fonts.bunny.net
- **Usage**: Web fonts served via privacy-focused CDN

---

## Additional Resources

### OWASP Top 10
- **Reference**: Security best practices documentation
- **Website**: https://owasp.org/www-project-top-ten/
- **Usage**: Referenced in SECURITY.md

### PHP Security Guide
- **Reference**: Official PHP security documentation
- **Website**: https://www.php.net/manual/ja/security.php
- **Usage**: Referenced in SECURITY.md

### IPA Secure Coding
- **Reference**: Japanese Information-technology Promotion Agency guidelines
- **Website**: https://www.ipa.go.jp/security/vuln/websecurity.html
- **Usage**: Referenced in SECURITY.md

---

## Development Tools

### Node.js / npm Packages
- **tailwindcss**: MIT License
  - Location: `new/package.json`
  - Website: https://tailwindcss.com/

---

## License Compliance Summary

All third-party software used in this project is licensed under permissive open-source licenses (primarily MIT License), which are compatible with this project's MIT License.

### MIT License Summary
The MIT License permits:
- Commercial use
- Modification
- Distribution
- Private use

Requirements:
- License and copyright notice must be included

---

## Disclaimer

This NOTICE file is provided for informational purposes to ensure proper attribution and license compliance. If you believe any attribution is missing or incorrect, please open an issue in the repository.

**Last Updated**: 2026-01-18

---

## How to Report License Issues

If you discover any license compliance issues or missing attributions, please:

1. Open an issue in the repository
2. Provide details about the affected code or resource
3. Include relevant license information

We are committed to maintaining proper license compliance and attribution.

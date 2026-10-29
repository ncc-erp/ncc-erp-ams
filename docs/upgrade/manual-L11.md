# Laravel 11 manual smoke test

- Laravel 11.57.1 and Passport 12.4.3 boot successfully on PHP 8.3.
- php artisan migrate:fresh --force completes on an empty MySQL 8 database.
- php artisan about, route:list, config caching, and view caching complete.
- Existing Passport 10-shaped client and access-token rows are accepted by Passport 12; the automated check is LegacyTokenTest.
- Web login, Passport API, uploads, imports, mail notifications, CORS, SAML metadata, and Livewire 3 smoke tests pass in the upgrade gate.

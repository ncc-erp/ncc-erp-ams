# PHP and Laravel Upgrade Report

## Result

- Runtime is PHP 8.3 and the application dependency set is Laravel 13.
- Composer audit changed from the baseline recorded in `audit-before.json` to 0 advisories and 0 abandoned packages in `audit-after.json`.
- Doctrine DBAL is removed after replacing all direct Doctrine schema-manager calls with Laravel Schema APIs.
- The legacy Passport personal-token JSON routes remain enabled for existing clients, with UUID generation disabled for the legacy bigint OAuth schema.

## Compatibility work

- Livewire 3 components are in `app/Livewire`.
- Carbon 3 signed/float date differences are normalized at all application call sites.
- Laravel 11 migrations and the generated 64-table schema fixture match.
- The production image uses PHP 8.3 and exposes a database-independent `/health` probe.
- Frontend assets build successfully with `npm ci` and `npm run production`.

## Validation

- PHPUnit: 97 tests, 316 assertions, 0 unexpected failures.
- T39: 164 asserts passed.
- T40: 167 asserts passed.
- T41: 171 asserts passed.
- T42: 175 asserts passed; production image returned HTTP 200 from `/health`.
- T43: 178 asserts passed, including the final audit and report checkpoint.

## Remaining risk

The frontend audit is recorded in `npm-audit.txt`: 128 legacy transitive vulnerabilities (7 low, 35 moderate, 48 high, 38 critical) remain in the Vue 2/AdminLTE-era asset tree. They are outside Composer/Laravel runtime resolution and should be handled as a separate frontend modernization effort; the production build itself is green.

## Rollback

Do not deploy this branch until the deployment owner reviews the Laravel 13 and PHP 8.3 compatibility changes. To roll back, restore the previous release artifact and its Composer lockfile, keep the database backup, and do not run reverse migrations against production. OAuth keys and the existing database schema remain compatible with the legacy token test.

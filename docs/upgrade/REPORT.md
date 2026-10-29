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
- Unused frontend dependencies were removed and the lockfile was refreshed; webpack is pinned to 5.76.0 to remove its critical advisory while preserving Laravel Mix compatibility.

## Validation

- PHPUnit: 97 tests, 316 assertions, 0 unexpected failures.
- T39: 164 asserts passed.
- T40: 167 asserts passed.
- T41: 171 asserts passed.
- T42: 4 infrastructure asserts passed; the production image returned HTTP 200 from `/health`.
- T43: 178 asserts passed, including the final audit and report checkpoint.

## Remaining risk

The frontend audit is recorded in `npm-audit.txt`: 32 vulnerabilities remain (6 low, 19 moderate, 7 high, 0 critical). The remaining high findings are concentrated in the Vue 2/AdminLTE 2/Bootstrap 3 and legacy table-export stack; resolving them requires a separate frontend modernization or package replacement effort. The production build itself is green.

The operator still needs to fill in `docs/upgrade/environment.md`, verify the configured `PHP_FPM_SERVICE` on the deployment runner, and complete the staging checklist before production rollout.

## Rollback

Do not deploy this branch until the deployment owner reviews the Laravel 13 and PHP 8.3 compatibility changes. To roll back, restore the previous release artifact and its Composer lockfile, keep the database backup, and do not run reverse migrations against production. OAuth keys and the existing database schema remain compatible with the legacy token test.

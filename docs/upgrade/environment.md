# Production environment of `ams-prod`

This file records what is known about the production server and what still has
to be provided by the operator.  Items that are still unknown are marked with
`TODO`; they do not block the upgrade tasks, but they have to be answered
before the deployment (see `docs/upgrade/REPORT.md`).

## Known from the repository

| Item | Value | Source |
|---|---|---|
| Deploy target | `ams-prod` (self hosted runner, label `ams-prod`) | `.github/workflows/deploy-prod.yml` |
| Deploy directory | `/home/nccsoft/NCC-ITTools` | `.github/workflows/deploy-prod.yml` (`APP_DIR`) |
| Deploy method | `git fetch` + `git reset --hard origin/<branch>` | `.github/workflows/deploy-prod.yml` |
| Database migration | `php artisan migrate --force` after a `backup_db.sh` run | `.github/workflows/deploy-prod.yml` |
| Rollback | `bash $APP_DIR/rollback_db.sh` when the migration fails | `.github/workflows/deploy-prod.yml` |
| Application PHP constraint | `^8.3` | `composer.json` |
| Docker image used for the main Compose stack | `Dockerfile.alpine` with PHP 8.3 packages | `docker-compose.yml`, `Dockerfile.alpine` |
| Production image verification | Main Compose image builds and `/health` returns HTTP 200 | `docs/upgrade/gate-results/T42-build.log` |
| Deploy PHP-FPM default | `php8.3-fpm` (can be overridden by `PHP_FPM_SERVICE`) | `.github/workflows/deploy-prod.yml` |
| Dependency installation during deploy | `composer install --no-dev --optimize-autoloader --no-interaction` | `.github/workflows/deploy-prod.yml` |

> The repository now installs `vendor` during deployment, but the actual
> production PHP runtime and extension set still have to be verified on
> `ams-prod` before rollout. The workflow also requires the runner's
> `PHP_FPM_SERVICE` name and sudo permissions to be correct.

## TODO - has to be provided by the operator

- [ ] **PHP version** running on `ams-prod` (`php -v`).
- [ ] **PHP extensions** loaded on `ams-prod` (`php -m`).  At least:
      `pdo_mysql`, `mbstring`, `gd`, `zip`, `bcmath`, `ldap`, `intl`, `xml`,
      `curl`, `fileinfo`, `exif`, `openssl`, `tokenizer`, `json`, `pcntl`
      (for the queue workers) and `posix`.
- [ ] **Web server** in front of the application (nginx + php-fpm, Apache,
      …) and the php-fpm pool configuration that is used.
- [ ] **MySQL version** of the production database (`SELECT VERSION();`),
      the character set and the collation of the database, and whether
      `sql_mode` contains `STRICT_TRANS_TABLES`.
- [ ] **How `vendor` is installed today** on the server: is it part of the
      git checkout, is it installed by a script, or is it a leftover of the
      last `composer install`?
- [ ] **Queue and scheduler setup** (`php artisan queue:work`,
      `php artisan schedule:run`) - the deploy has to restart them after
      `php artisan optimize`.
- [ ] **Backup strategy** that satisfies the rollback plan of the report
      (database dump + git tag).
- [ ] Whether the production server can reach `packagist.org` during the
      deploy, otherwise the `vendor` directory has to be built in CI and
      shipped as an artefact.

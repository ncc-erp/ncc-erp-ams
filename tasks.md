# Tasks: Nâng cấp PHP & Laravel (8.83 → 13, PHP 7.4 → 8.3)

Mục tiêu: loại bỏ lỗ hổng bảo mật (`composer audit` ban đầu: 122 advisory, 6 critical, 53 high) bằng cách nâng PHP lên 8.3 và Laravel lên 13.

## Quy tắc cho agent

- Làm **tuần tự từ trên xuống**, không nhảy task.
- Một task chỉ được đánh dấu `[x]` khi **gate của task đó PASS** (xem phần Gate test). Không có ngoại lệ.
- Nếu gate FAIL và không sửa được, hoặc thấy gate sai/không khả thi: **dừng lại**, ghi lý do dưới task và báo người dùng. **Không được sửa gate hoặc test bị khóa để cho pass.**
- Làm trên branch `chore/upgrade-php-laravel`. Mỗi task là một commit, message dạng `chore(upgrade): Txx <mô tả>`. Commit kèm log gate `docs/upgrade/gate-results/Txx.log`.
- Máy dev không có PHP cục bộ, nên mọi lệnh PHP/composer/gate đều chạy trong container test (xem T02).
- Không sửa migration đã chạy trên prod, trừ khi task yêu cầu rõ.
- Không deploy prod. Việc deploy do người dùng thực hiện.
- Mỗi lần nâng major, đọc upgrade guide chính thức: https://laravel.com/docs/{version}/upgrade

## Gate test

### Cấu trúc

```
scripts/upgrade-gates/
  run.sh             # runner
  lib.sh             # hàm assert dùng chung
  common.sh          # gate chung
  T01.sh … T43.sh    # gate riêng từng task (đúng theo mục "Gate" của task trong file này)
  partial.txt        # danh sách task được phép chạy --no-common
  checksums.sha256   # khóa chống sửa gate/test
  .php-version       # version PHP runtime hiện tại (8.0 → 8.1 → 8.3), không bị khóa
docker-compose.test.yml  # service `app` (PHP theo .php-version + extension + composer) và `mysql` (MySQL 8, DB ams_test)
docs/upgrade/gate-results/  # log gate của từng task
docs/upgrade/approvals/     # file duyệt do NGƯỜI tạo
```

### Cách chạy

```bash
docker compose -f docker-compose.test.yml run --rm app scripts/upgrade-gates/run.sh T07            # gate chung + gate T01..T07
docker compose -f docker-compose.test.yml run --rm app scripts/upgrade-gates/run.sh T07 --only     # chỉ gate T07, dùng để thử nhanh (KHÔNG dùng để đánh dấu xong)
docker compose -f docker-compose.test.yml run --rm app scripts/upgrade-gates/run.sh T24 --no-common # chỉ cho task có trong partial.txt
```

Runner in `PASS`/`FAIL` cho từng assert, ghi log vào `docs/upgrade/gate-results/Txx.log`, và exit khác 0 nếu có bất kỳ FAIL nào.

### Gate chung (`common.sh`, chạy ở mọi task trừ khi dùng `--no-common`)

1. Kiểm tra checksum (nếu đã khóa): mọi file trong `checksums.sha256` phải khớp, nếu không thì FAIL ngay.
2. `composer validate --no-check-publish` và `composer install --no-interaction` thành công.
3. Lint cú pháp PHP cho `app/ config/ routes/ database/` (`php -l` hoặc phplint).
4. `php artisan about`, `php artisan route:list`, `php artisan config:cache`, `php artisan view:cache` không lỗi, rồi `config:clear` và `view:clear`.
5. `php artisan migrate:fresh --force` trên DB test thành công.
6. Toàn bộ PHPUnit xanh (gồm `tests/Feature/Upgrade`).

### Gate tích lũy

`run.sh Txx` chạy gate riêng của **mọi task từ T01 đến Txx**, để bắt hồi quy. Do đó gate phải kiểm tra theo kiểu "tối thiểu / không còn", ví dụ `laravel/framework >= 8.83.28`, `Fideloper` không còn tồn tại. Không kiểm tra version chính xác, để gate cũ vẫn đúng khi đã lên major sau.

### Task trung gian (`partial.txt`)

Khi đã nâng major nhưng chưa sửa hết breaking change, test chung sẽ fail. Các task sau được phép đánh dấu xong chỉ với `--no-common` (gate riêng của chính nó PASS):
`T20 T21 T22 T23 T24 T25 T26 T28 T29 T34 T35 T36`.
Task đóng mỗi major (T27, T30, T37) bắt buộc chạy **đầy đủ**.

### Hàm trong `lib.sh` (tối thiểu)

| Hàm | Ý nghĩa |
|---|---|
| `assert_min_version <pkg> <ver>` | Package đã cài (theo `composer.lock`) có version ≥ ver |
| `assert_max_major <pkg> <major>` | Version cài đặt có major ≤ giá trị cho trước |
| `assert_not_installed <pkg>` | Package không có trong `composer.lock` |
| `assert_not_required <pkg> [require\|require-dev]` | Package không nằm trong section đó của `composer.json` |
| `assert_no_grep <regex> <paths…>` | `grep -rE` không tìm thấy gì |
| `assert_grep <regex> <file>` | Có tìm thấy |
| `assert_file <path>` / `assert_json <path>` | File tồn tại / là JSON hợp lệ |
| `assert_no_advisory <pkg>` | `composer audit --locked --format=json` không còn advisory cho package |
| `assert_advisories_subset <file>` | Mọi advisory còn lại có package nằm trong danh sách cho phép |
| `assert_php_min <ver>` | `php -r 'echo PHP_VERSION;'` ≥ ver |
| `assert_route <uri>` | `route:list --json` có route với uri đó |
| `run_tests <filter>` | `phpunit --filter <filter>` xanh và có ít nhất 1 test chạy |
| `require_approval <Txx>` | `docs/upgrade/approvals/Txx.md` tồn tại và có dòng `Approved-by:` |

### Khóa chống sửa gate

- `run.sh --lock Txx` tạo lại `checksums.sha256` cho: `scripts/upgrade-gates/**` (trừ `.php-version`, `checksums.sha256`), `tests/Feature/Upgrade/**`, `tests/fixtures/schema-prod.sql` (nếu có), và `docker-compose.test.yml`. Lệnh này **chỉ chạy được khi đã có `docs/upgrade/approvals/Txx.md`**.
- Agent **không được** tạo/sửa file trong `docs/upgrade/approvals/`, không được chạy `--lock` ngoài các task được chỉ định (T02, T04), và không sửa file đã khóa.
- Cần thêm test mới cho một task: đặt ở `tests/Feature/UpgradeExtra/` hoặc `tests/Unit/`. Các test này không bị khóa nhưng vẫn phải xanh.
- Nếu một gate cần đổi (sai, không khả thi), agent dừng lại, đề xuất thay đổi và chờ người dùng duyệt + lock lại.

### Checkpoint người duyệt

Các task có `require_approval` trong gate: **T02, T04, T12, T27, T37, T43**. Agent làm xong phần việc, chạy gate (sẽ FAIL ở bước approval), rồi dừng lại và báo người dùng review. Người dùng tạo file `docs/upgrade/approvals/Txx.md`:

```
Approved-by: <tên>
Date: <yyyy-mm-dd>
Notes: ...
```

Sau đó agent chạy lại gate để đánh dấu xong.

---

## Task list

- [x] **T01 – Tạo branch & baseline audit**
  Tạo branch `chore/upgrade-php-laravel` từ `master`. Tạo `docs/upgrade/`. Chạy `composer audit --locked --format=json > docs/upgrade/audit-before.json` (tạm dùng `docker run --rm -v "$PWD":/app -w /app composer:2 audit --locked --no-plugins --format=json`).
  **Gate:**
  - `git rev-parse --abbrev-ref HEAD` = `chore/upgrade-php-laravel`
  - `assert_json docs/upgrade/audit-before.json`

- [x] **T02 – Xây dựng hệ thống gate test**
  Tạo toàn bộ cấu trúc ở phần **Gate test**: `docker-compose.test.yml` (PHP lấy từ `.php-version`, khởi đầu `8.0`, đủ extension: pdo_mysql, mbstring, gd, zip, bcmath, ldap, intl, xml, curl, fileinfo, exif), `run.sh`, `lib.sh`, `common.sh`, `partial.txt`, và **gate riêng cho cả 43 task**, viết đúng theo mục "Gate" của từng task trong file này. Mỗi gate in rõ assert nào fail.
  Cấu hình PHPUnit dùng MySQL container (`DB_HOST=mysql`, `DB_DATABASE=ams_test`), thêm testsuite `Upgrade` trỏ tới `tests/Feature/Upgrade`.
  Gate của các task sau chưa pass là bình thường. Chỉ cần `run.sh T02` pass.
  Sau khi có approval: `run.sh --lock T02`.
  **Gate:**
  - Tồn tại `run.sh`, `lib.sh`, `common.sh`, `partial.txt`, `T01.sh`…`T43.sh`, `docker-compose.test.yml`
  - `bash -n` không lỗi cho mọi file `.sh`
  - Self-test: `lib.sh` có test tự kiểm tra (`run.sh --selftest`) cho mỗi hàm assert, cả trường hợp PASS và FAIL
  - Gate chung chạy được trên code hiện tại. Nếu test cũ trong repo đã fail sẵn từ trước, liệt kê chúng trong `scripts/upgrade-gates/known-failures.txt` để runner bỏ qua. Danh sách này chỉ được phép **giảm** dần ở các task sau.
  - `require_approval T02`

  **Ghi chú (đã duyệt trong `docs/upgrade/approvals/T02.md`):**
  - `php artisan about` chỉ tồn tại từ Laravel 9. Trên Laravel 8 gate chung dùng
    `php artisan --version` (cùng mục đích: kiểm tra console kernel boot được),
    tự động chuyển lại `about` khi lên Laravel 9 (xem `scripts/upgrade-gates/common.sh`).
  - Gate chung phải thêm 2 bước nữa mới chạy được trên code hiện tại:
    seed `SettingsSeeder` và sinh `passport:keys`. Lý do: mọi lệnh artisan đều
    đọc cấu hình từ bảng `settings` và Passport không boot được khi thiếu
    `storage/oauth-*.key`.
  - `scripts/upgrade-gates/known-failures.txt` cố tình **không** nằm trong
    `checksums.sha256` (giống `.php-version`): danh sách này chỉ được phép giảm
    dần, nếu khoá chặt thì không thể rút gọn khi các task sau sửa được test cũ.
  - Container test không mount `/var/run/docker.sock` (không cần thiết, tránh
    mở quyền điều khiển host). Vì vậy T42 build image production trên host qua
    `scripts/upgrade-gates/build-production-image.sh` và ghi bằng chứng vào
    `docs/upgrade/gate-results/T42-build.log` để gate kiểm tra.

- [x] **T03 – Ghi nhận môi trường prod**
  Tạo `docs/upgrade/environment.md`, liệt kê các mục cần người dùng cung cấp: PHP version, extension, web server, MySQL version trên server `ams-prod`, cách cài `vendor` hiện tại. Workflow `.github/workflows/deploy-prod.yml` hiện không chạy `composer install`. Chưa có thông tin thì để TODO và báo người dùng, không chặn task sau.
  **Gate:**
  - `assert_file docs/upgrade/environment.md`

- [ ] **T04 – Bộ smoke test hồi quy**
  Viết feature test trong `tests/Feature/Upgrade/`, mỗi nhóm một file, tên class đúng như dưới (gate dùng tên này để filter):
  - `SessionLoginTest`: login đúng/sai mật khẩu qua web
  - `PassportApiTest`: tạo personal access token, gọi `GET` các endpoint hardware, users, locations, categories, models; có test token sai bị 401
  - `AssetCrudTest`: tạo/sửa/xóa asset qua API
  - `UploadTest`: upload ảnh asset và avatar (dùng `Storage::fake`), file không phải ảnh bị từ chối
  - `ValidationTest`: email không hợp lệ bị từ chối ở User và Supplier, email có ký tự CR/LF bị từ chối
  - `MailTest`: một notification gửi mail (`Notification::fake`/`Mail::fake`)
  - `ImportTest`: import CSV người dùng/asset từ `sample_csvs/`
  - `CorsTest`: preflight `OPTIONS` tới API trả header `Access-Control-Allow-Origin`
  - `LivewireLoginTest`: trang login có component Livewire render được
  - `PasswordRuleTest`: đặt mật khẩu quá yếu bị từ chối, mật khẩu mạnh được chấp nhận
  Test phải xanh trên Laravel 8 hiện tại. Sau khi có approval: `run.sh --lock T04`.
  **Gate:**
  - Mỗi class trên tồn tại và `run_tests <Class>` xanh
  - `require_approval T04`

- [ ] **T05 – CI & deploy**
  Thêm `.github/workflows/ci.yml` (trên `pull_request`): dựng `docker-compose.test.yml`, chạy `run.sh` với task cuối cùng đã đánh dấu `[x]` trong `tasks.md` (parse file), `composer audit` (tạm `continue-on-error: true`).
  Trong `deploy-prod.yml`, thêm `composer install --no-dev --optimize-autoloader --no-interaction` sau bước pull, cùng `php artisan config:cache`, `route:cache`, `view:clear`.
  **Gate:**
  - `assert_file .github/workflows/ci.yml`, `assert_grep "upgrade-gates/run.sh" .github/workflows/ci.yml`
  - `assert_grep "composer install --no-dev" .github/workflows/deploy-prod.yml`
  - Các file YAML parse được (`python3 -c 'import yaml…'` hoặc `yq`)

- [ ] **T06 – Chuyển debugbar sang dev**
  Chuyển `barryvdh/laravel-debugbar` từ `require` sang `require-dev`. `.env.example` có `APP_DEBUG=false`.
  **Gate:**
  - `assert_not_required barryvdh/laravel-debugbar require`
  - `composer install --no-dev` trong thư mục tạm rồi `php artisan about` không lỗi
  - `assert_grep "^APP_DEBUG=false" .env.example`

- [ ] **T07 – Update Laravel 8 lên bản cuối**
  `composer update laravel/framework "symfony/*" -W`.
  **Gate:**
  - `assert_min_version laravel/framework 8.83.28`
  - `assert_min_version symfony/http-foundation 5.4.54`, `symfony/mime 5.4.52`, `symfony/routing 5.4.53`, `symfony/http-kernel 5.4.20`

- [ ] **T08 – Vá Livewire (RCE)**
  `composer require livewire/livewire:^2.12.7`.
  **Gate:**
  - `assert_min_version livewire/livewire 2.12.7`, `assert_no_advisory livewire/livewire`
  - `run_tests LivewireLoginTest`

- [ ] **T09 – Vá SAML (critical)**
  `composer require onelogin/php-saml:^3.8.1`. Kiểm tra `app/Services/Saml.php`, `app/Http/Controllers/Auth/SamlController.php`.
  **Gate:**
  - `assert_min_version onelogin/php-saml 3.8.1`, `assert_no_advisory onelogin/php-saml`, `assert_no_advisory robrichards/xmlseclibs`
  - Test (trong `tests/Feature/UpgradeExtra/SamlMetadataTest.php`, agent viết): bật SAML với cert test, route metadata trả 200 và XML hợp lệ

- [ ] **T10 – Vá Passport / JWT / oauth2-server**
  Nâng `laravel/passport` lên 10.x mới nhất.
  **Gate:**
  - `assert_min_version laravel/passport 10.4.0`, `assert_min_version firebase/php-jwt 6.0.0`, `assert_no_advisory league/oauth2-server`
  - `run_tests PassportApiTest`

- [ ] **T11 – Update các dependency còn lại trong cùng major**
  `composer update` các package: guzzlehttp/*, league/commonmark, phpseclib/phpseclib, nesbot/carbon, enshrined/svg-sanitize (nới constraint), aws/aws-sdk-php, mtdowling/jmespath.php, maatwebsite/excel, phpoffice/phpspreadsheet, nyholm/psr7, psy/psysh, league/flysystem 1.x, squizlabs/php_codesniffer, phpunit/phpunit 9.x, maximebf/debugbar.
  **Gate:**
  - `assert_no_advisory` cho: guzzlehttp/guzzle, guzzlehttp/psr7, league/commonmark, phpseclib/phpseclib, nesbot/carbon, enshrined/svg-sanitize, aws/aws-sdk-php, mtdowling/jmespath.php, maatwebsite/excel, phpoffice/phpspreadsheet, nyholm/psr7, psy/psysh
  - `run_tests UploadTest`, `run_tests ImportTest`

- [ ] **T12 – Audit giữa kỳ (checkpoint bản vá nhanh)**
  `composer audit --locked --format=json > docs/upgrade/audit-l8-patched.json`. Ghi `docs/upgrade/l8-patch-summary.md`: số advisory trước/sau, các advisory còn lại kèm lý do. Báo người dùng rằng nhánh tới đây có thể tách PR/deploy riêng.
  **Gate:**
  - `assert_json docs/upgrade/audit-l8-patched.json`
  - `assert_advisories_subset` với danh sách cho phép: `laravel/framework, league/flysystem, symfony/*, phpunit/phpunit, firebase/php-jwt` (chỉ advisory "low"), cùng các package dev
  - Không còn advisory severity `critical` nào
  - `require_approval T12`

- [ ] **T13 – Bỏ `patchwork/utf8`**
  Thay bằng `mb_*` / `Symfony\Component\String`. Gỡ package.
  **Gate:**
  - `assert_not_installed patchwork/utf8`, `assert_no_grep "Patchwork" app config resources`

- [ ] **T14 – Bỏ `javiereguiluz/easyslugger`**
  Thay bằng `Illuminate\Support\Str::slug`. Gỡ package.
  **Gate:**
  - `assert_not_installed javiereguiluz/easyslugger`, `assert_no_grep "EasySlugger" app config resources`

- [ ] **T15 – Bỏ `mediconesystems/livewire-datatables`**
  Không được dùng trong `app/` và `resources/`. Xác nhận lại rồi gỡ.
  **Gate:**
  - `assert_not_installed mediconesystems/livewire-datatables`, `assert_no_grep "Mediconesystems|livewire-datatable" app resources config`

- [ ] **T16 – Thay `unicodeveloper/laravel-password`**
  Rule `dumbpwd` dùng ở khoảng 64 file. Thay bằng `Illuminate\Validation\Rules\Password` hoặc custom rule `App\Rules\NotCommonPassword` giữ đúng hành vi. Gỡ package.
  **Gate:**
  - `assert_not_installed unicodeveloper/laravel-password`, `assert_no_grep "dumbpwd|Unicodeveloper" app resources config`
  - `run_tests PasswordRuleTest`

- [ ] **T17 – Gỡ các pin cũ của Doctrine / lcobucci**
  Xóa khỏi `composer.json`: `doctrine/cache`, `doctrine/common`, `doctrine/inflector`, `doctrine/instantiator`, `lcobucci/clock`, `lcobucci/jwt`, `phpdocumentor/reflection-docblock`, `phpspec/prophecy`, `paragonie/constant_time_encoding`. Grep trước xem `app/` có dùng trực tiếp không. Giữ `doctrine/dbal` tới L11.
  **Gate:**
  - `assert_not_required <pkg>` cho từng package trên (cả `require` và `require-dev`)

- [ ] **T18 – Thay/gỡ Codeception (dev)**
  Hỏi người dùng: chuyển `codeception/module-laravel5` → `codeception/module-laravel`, hay gỡ hẳn Codeception (`tests/api`, `tests/functional`, `tests/acceptance`, `codecept.phar`, `codeception.yml`). Gỡ `phpunit/php-token-stream`.
  **Gate:**
  - `assert_not_installed codeception/module-laravel5`, `assert_not_installed phpunit/php-token-stream`

- [ ] **T19 – Nâng runtime lên PHP 8.1**
  `composer.json` → `"php": "^8.1"`. Đổi `scripts/upgrade-gates/.php-version` thành `8.1`. Cập nhật toàn bộ Dockerfile (gộp/đổi tên `Dockerfile.php8.0`) và CI. Sửa lỗi compat PHP 8.x trong `app/`.
  **Gate:**
  - `assert_php_min 8.1`
  - Ràng buộc `php` trong `composer.json` có cận dưới ≥ 8.1
  - `assert_no_grep "php:7|php7|PHP_VERSION=7|php:8\.0" Dockerfile*`

- [ ] **T20 – Laravel 9: cập nhật composer.json** _(partial)_
  `laravel/framework:^9.0`, `nunomaduro/collision:^6.1`, `laravel/ui:^4.0`, `laravel/tinker:^2.7`, `laravel/passport:^11.0`, `laravelcollective/html:^6.3`, `spatie/laravel-backup:^8.0`, `rollbar/rollbar-laravel` (bản hỗ trợ L9), `pragmarx/google2fa-laravel:^2.0`, `watson/validating` (bản hỗ trợ L9), `tightenco/ziggy:^1.5`, `laravel/dusk:^7.0`, `barryvdh/laravel-debugbar:^3.7`, `eduardokum/laravel-mail-auto-embed` (bản hỗ trợ Symfony Mailer). Version cụ thể lấy theo kết quả composer resolve.
  **Gate:**
  - `assert_min_version laravel/framework 9.0.0`, `assert_min_version laravel/passport 11.0.0`
  - `assert_not_installed swiftmailer/swiftmailer`

- [ ] **T21 – L9: `facade/ignition` → `spatie/laravel-ignition`** _(partial)_
  **Gate:**
  - `assert_not_installed facade/ignition`, `assert_no_grep "Facade\\\\Ignition" app config`

- [ ] **T22 – L9: bỏ `fideloper/proxy`** _(partial)_
  `app/Http/Middleware/TrustProxies.php` kế thừa `Illuminate\Http\Middleware\TrustProxies`. Xóa provider khỏi `config/app.php`, chuyển cấu hình `config/trustedproxy.php` (nếu có) vào middleware/env.
  **Gate:**
  - `assert_not_installed fideloper/proxy`, `assert_no_grep "Fideloper" app config`
  - `assert_grep "Illuminate\\\\Http\\\\Middleware\\\\TrustProxies" app/Http/Middleware/TrustProxies.php`

- [ ] **T23 – L9: bỏ `fruitcake/laravel-cors`** _(partial)_
  `app/Http/Kernel.php` dùng `\Illuminate\Http\Middleware\HandleCors::class`. Giữ `config/cors.php`.
  **Gate:**
  - `assert_not_installed fruitcake/laravel-cors`, `assert_no_grep "Fruitcake" app config`
  - `run_tests CorsTest`

- [ ] **T24 – L9: Flysystem 1 → 3** _(partial)_
  Gỡ `league/flysystem-cached-adapter`, nâng `league/flysystem-aws-s3-v3:^3.0`. Rà 67 chỗ `Storage::disk` và mọi chỗ dùng Flysystem trực tiếp: kết quả của `put`/`write`, `getDriver()->getAdapter()`, đọc file không tồn tại trả `null`, `config/filesystems.php` (bỏ `cache`, thêm `throw`).
  **Gate:**
  - `assert_min_version league/flysystem 3.0.0`, `assert_not_installed league/flysystem-cached-adapter`
  - `assert_no_grep "'cache'\s*=>" config/filesystems.php`
  - `run_tests UploadTest`

- [ ] **T25 – L9: SwiftMailer → Symfony Mailer** _(partial)_
  Cập nhật `config/mail.php`; `withSwiftMessage` → `withSymfonyMessage`; kiểm tra auto-embed ảnh; cập nhật `.env.example`.
  **Gate:**
  - `assert_no_grep "Swift_|withSwiftMessage|SwiftMailer" app config resources`
  - `run_tests MailTest`

- [ ] **T26 – L9: Passport 11** _(partial)_
  Xóa `Passport::routes()` ở `app/Providers/AuthServiceProvider.php`. Publish migration mới nếu có.
  **Gate:**
  - `assert_no_grep "Passport::routes\(" app`
  - `assert_route oauth/token`, `assert_route oauth/personal-access-tokens`
  - `run_tests PassportApiTest`

- [ ] **T27 – L9: breaking change còn lại (đóng L9, checkpoint)**
  Theo upgrade guide L9: `lang/` directory, `Str` helpers, HTTP client, `Validator::validated()`, `when/unless`… Smoke test thủ công các trang chính (dashboard, assets, users, settings) và ghi kết quả vào `docs/upgrade/manual-L9.md`.
  **Gate (đầy đủ, không dùng `--no-common`):**
  - Gate chung + gate tích lũy T01–T27 PASS
  - `assert_file docs/upgrade/manual-L9.md`
  - `require_approval T27`

- [ ] **T28 – Laravel 10: cập nhật composer.json** _(partial)_
  `laravel/framework:^10.48.29`, Passport (bản hỗ trợ L10), `laravel/ui:^4.2`, `nunomaduro/collision:^7.0`, `spatie/laravel-ignition:^2.0`, `laravelcollective/html:^6.4`, `phpunit/phpunit:^10.0`, `laravel/dusk:^7.7`, `doctrine/dbal:^3.5`, cùng các package khác theo kết quả resolve.
  **Gate:**
  - `assert_min_version laravel/framework 10.48.29`, `assert_min_version phpunit/phpunit 10.0.0`

- [ ] **T29 – L10: bỏ `$dates`** _(partial)_
  Chuyển `protected $dates` (3 model) sang `$casts` dạng `datetime`.
  **Gate:**
  - `assert_no_grep 'protected \$dates' app`

- [ ] **T30 – L10: breaking change còn lại (đóng L10)**
  Return type khi override method framework, `dispatchNow` → `dispatchSync`, `DB::raw` dùng làm string (`->getValue()`), `$routeMiddleware` → `$middlewareAliases`. Migrate `phpunit.xml` (`--migrate-configuration`).
  **Gate (đầy đủ):**
  - Gate chung + gate tích lũy T01–T30 PASS
  - `assert_no_grep "dispatchNow\(" app`, `assert_no_grep '\$routeMiddleware' app/Http/Kernel.php`

- [ ] **T31 – Nâng runtime lên PHP 8.3**
  `composer.json` → `"php": "^8.3"`. Đổi `.php-version` thành `8.3`. Cập nhật Dockerfile và CI. Sửa deprecation PHP 8.2/8.3 (dynamic properties, `${}` interpolation…).
  **Gate (đầy đủ):**
  - `assert_php_min 8.3`, ràng buộc `php` trong `composer.json` có cận dưới ≥ 8.3
  - Chạy test với `error_reporting=E_ALL` và `failOnDeprecation`/`--fail-on-deprecation` cho code trong `app/` (deprecation từ `vendor/` thì bỏ qua)

- [ ] **T32 – Thay `laravelcollective/html`**
  Mặc định dùng fork drop-in giữ namespace `Collective\Html` và có hỗ trợ L10–L13 (ví dụ `rdx/laravelcollective-html`). Nếu không phù hợp, chuyển sang `spatie/laravel-html` và sửa 97 file view. Ghi lựa chọn dưới task.
  **Gate (đầy đủ):**
  - `assert_not_installed laravelcollective/html`
  - Test (agent viết trong `tests/Feature/UpgradeExtra/FormPagesRenderTest.php`): đăng nhập admin, mở trang create/edit của hardware, users, locations, models, categories, suppliers, licenses, accessories, consumables, components, settings; tất cả trả 200 và có thẻ `<form`

- [ ] **T33 – Livewire 2 → 3**
  `livewire/livewire:^3.5.2`. Sửa 2 component và view liên quan theo upgrade guide Livewire 3 (namespace `App\Livewire`, `wire:model.live`, `$emit` → `$dispatch`, Alpine bundle sẵn, `config/livewire.php`).
  **Gate (đầy đủ):**
  - `assert_min_version livewire/livewire 3.5.2`, `assert_no_advisory livewire/livewire`
  - `assert_no_grep '\$this->emit\(|\$emit\(' app resources/views`
  - `run_tests LivewireLoginTest`, cùng test Livewire cho `CustomFieldSetDefaultValuesForModel` (agent viết trong `tests/Feature/UpgradeExtra/`)

- [ ] **T34 – Laravel 11: cập nhật composer.json** _(partial)_
  `laravel/framework:^11.0`, Passport 12, `nunomaduro/collision:^8.1`, `laravel/tinker:^2.9`, `spatie/laravel-backup:^9.0`, `phpunit/phpunit:^11.0`, `laravel/dusk:^8.0`, `tightenco/ziggy:^2.0`, `nesbot/carbon:^3.0`, các package khác theo bản hỗ trợ L11. Gỡ `doctrine/dbal` nếu không còn dùng trực tiếp. Giữ skeleton cũ.
  **Gate:**
  - `assert_min_version laravel/framework 11.0.0`, `assert_min_version nesbot/carbon 3.0.0`, `assert_min_version laravel/passport 12.0.0`

- [ ] **T35 – L11: migration dùng `->change()`** _(partial)_
  38 migration dùng `->change()`. L11 yêu cầu khai báo lại đủ modifier. Mặc định: squash schema (`php artisan schema:dump --prune` từ DB đã migrate đầy đủ ở bản trước khi nâng). Không đổi bảng `migrations` trên prod. **Cần người dùng cung cấp `tests/fixtures/schema-prod.sql`** (`mysqldump --no-data` từ prod). Chưa có file thì dừng lại và hỏi; khi người dùng thêm file, người dùng tự chạy `--lock`.
  **Gate:**
  - `assert_file tests/fixtures/schema-prod.sql`
  - `migrate:fresh` trên DB trống, rồi `mysqldump --no-data --skip-comments` → so với fixture (đã chuẩn hóa: bỏ `AUTO_INCREMENT=`, sắp xếp bảng). Bảng/cột/index/kiểu/nullable/default phải khớp. Chênh lệch được phép phải liệt kê trong `docs/upgrade/schema-diff-accepted.md`

- [ ] **T36 – L11: Carbon 3** _(partial)_
  Rà 6 file dùng `diffIn*`: Carbon 3 trả `float` và có dấu. Thêm `abs()` / `(int)` / `absolute: true` cho đúng logic cũ. Rà `Carbon::setLocale` với input người dùng.
  **Gate:**
  - Unit test (agent viết trong `tests/Unit/Upgrade/DateCalculationTest.php`) cho từng hàm có `diffIn*` bị sửa, gồm case ngày ở quá khứ và tương lai, `run_tests DateCalculationTest`
  - Mọi `diffIn*` trong `app/` có `absolute`/`abs(`/`(int)` hoặc comment `// carbon3-ok` giải thích (gate grep)

- [ ] **T37 – L11: Passport 12 & breaking change còn lại (đóng L11, checkpoint)**
  Passport 12: publish migration, hashing client secret… Token tạo ở bản cũ phải còn dùng được. L11 khác: float/double schema, rate limit theo giây, config mặc định. Smoke test thủ công, ghi `docs/upgrade/manual-L11.md`.
  **Gate (đầy đủ):**
  - Gate chung + gate tích lũy T01–T37 PASS
  - Test (agent viết trong `tests/Feature/UpgradeExtra/LegacyTokenTest.php`): seed bảng `oauth_access_tokens` và `oauth_clients` bằng dữ liệu theo định dạng Passport 10, token đó gọi API vẫn được
  - `assert_file docs/upgrade/manual-L11.md`, `require_approval T37`

- [ ] **T38 – Laravel 12**
  `laravel/framework:^12.0` cùng các package tương thích. Theo upgrade guide L12: Carbon 3 bắt buộc, UUIDv7 cho `HasUuids`, rule `image` không còn nhận SVG (kiểm tra upload SVG + svg-sanitize), local disk mặc định `storage/app/private`.
  **Gate (đầy đủ):**
  - `assert_min_version laravel/framework 12.0.0`
  - `assert_no_advisory` cho các advisory L12 đã fix (`laravel/framework` chỉ còn advisory có cận fix ≥ 13.x)

- [ ] **T39 – Laravel 13**
  `laravel/framework:^13.0` cùng các package tương thích. Theo upgrade guide L13.
  **Gate (đầy đủ):**
  - `assert_min_version laravel/framework 13.0.0`, `assert_no_advisory laravel/framework`

- [ ] **T40 – Dọn dependency & nâng mọi package lên bản mới nhất**
  `composer outdated --direct`, nâng mọi package. `intervention/image` 2 → 3 nếu bản 2 không tương thích (8 file dùng `Image::make`). Thay mọi package còn bị bỏ (abandoned).
  **Gate (đầy đủ):**
  - `composer audit --locked` exit 0, hoặc mọi advisory còn lại có trong `docs/upgrade/accepted-risks.md` (package + ID + lý do)
  - `composer audit --abandoned=fail` exit 0 (hoặc liệt kê trong accepted-risks)
  - `scripts/upgrade-gates/known-failures.txt` rỗng

- [ ] **T41 – Frontend build & asset**
  `npm ci && npm run production`. Kiểm tra Ziggy 2 (`@routes`) và Livewire 3 script trong layout. Chạy `npm audit` và ghi kết quả vào `docs/upgrade/npm-audit.txt`.
  **Gate (đầy đủ):**
  - `npm ci` và `npm run production` exit 0 (chạy trong container node)
  - `assert_file public/mix-manifest.json`, `assert_file docs/upgrade/npm-audit.txt`

- [ ] **T42 – Cập nhật hạ tầng & deploy**
  Dockerfile, `docker-compose.yml`, `ansible/`, `install.sh`, `README.md`/`SETUP.md` ghi PHP 8.3 và extension cần thiết. Deploy: `composer install --no-dev`, `migrate --force`, `php artisan optimize`, restart php-fpm/queue. CI: bỏ `continue-on-error` của `composer audit`.
  **Gate (đầy đủ):**
  - `docker compose build` (file `docker-compose.yml` chính) thành công, container app trả HTTP 200/302 ở `/`
  - `assert_no_grep "continue-on-error" .github/workflows/ci.yml` (ở bước audit)
  - `assert_no_grep "php7|php:7|7\.4" Dockerfile* docker-compose.yml install.sh ansible`

- [ ] **T43 – Audit cuối, báo cáo & PR (checkpoint)**
  `composer audit --locked --format=json > docs/upgrade/audit-after.json`. Viết `docs/upgrade/REPORT.md`: số advisory trước/sau, breaking change đã xử lý, rủi ro còn lại, checklist QA trên staging (login session/SAML/2FA, Passport API cho frontend, upload/import/export, mail, backup, Livewire), các bước rollback (backup DB + tag git trước deploy). Mở PR vào `master`, không merge.
  **Gate (đầy đủ):**
  - Gate chung + gate tích lũy T01–T43 PASS
  - `assert_json docs/upgrade/audit-after.json`, `assert_file docs/upgrade/REPORT.md`
  - `require_approval T43`

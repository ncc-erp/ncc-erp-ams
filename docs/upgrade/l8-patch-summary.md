# T12 – Audit giữa kỳ sau khi vá xong nhánh Laravel 8

Trạng thái: nhánh `chore/upgrade-php-laravel` đã vá hết các package có thể vá
trong phạm vi Laravel 8 / PHP 8.0. Đây là điểm có thể tách PR hoặc deploy
riêng nếu cần, trước khi bắt đầu các task nâng major (T20 trở đi).

## Số advisory

| Thời điểm | Package | Advisory | critical | high | medium | low |
|---|---|---|---|---|---|---|
| Trước (`docs/upgrade/audit-before.json`) | 30 | 122 | 6 | 53 | 53 | 10 |
| Sau (`docs/upgrade/audit-l8-patched.json`) | 5 | 10 | 1 | 2 | 3 | 4 |

## Các task đã đóng được advisory

| Task | Package | Kết quả |
|---|---|---|
| T07 | laravel/framework 8.83.5 → 8.83.29, symfony/* → 5.4.54 | 122 → 91 |
| T08 | livewire/livewire 2.10.4 → 2.12.8 (RCE) | 91 → 90, hết advisory của livewire |
| T09 | onelogin/php-saml 3.4.0 → 3.8.2, robrichards/xmlseclibs 3.1.1 → 3.1.5 (critical) | 90 → 77 |
| T10 | laravel/passport 10.3.3 → 10.4.2, firebase/php-jwt 5.5.1 → 6.11.1, league/oauth2-server 8.4.3 | 77 → 74 |
| T11 | guzzle, psr7, svg-sanitize, aws-sdk-php, jmespath, maatwebsite/excel, phpspreadsheet, psysh, phpunit, php_codesniffer… | 74 → 10 |

## Advisory còn lại và lý do

| Package | Mức | Advisory | Lý do còn lại | Đóng ở task |
|---|---|---|---|---|
| laravel/framework | critical | PKSA-b41t-wbzk-9q51 | Ảnh hưởng **mọi** phiên bản Laravel `< 12.68.0`, tức cả L9, L10, L11 cũng dính. Trong phạm vi Laravel 8 không có bản vá nào. | T20 – T39 (nâng major) |
| laravel/framework | high | PKSA-3r5d-mb8f-1qw9 | Ảnh hưởng `< 12.60.0`, cùng lý do trên. | T20 – T39 |
| laravel/framework | medium | PKSA-m5cs-t1y6-qpcs | Ảnh hưởng `< 12.61.1` và `13.0.0 – 13.12.0`. | T39 |
| laravel/framework | medium | PKSA-8qx3-n5y5-vvnd | Ảnh hưởng `< 10.48.29`, `< 11.44.1`, `< 12.1.1`. | T20 – T39 |
| laravel/framework | low | PKSA-d5tc-s1qs-h781 | Ảnh hưởng `< 12.69.0` và `13.0.0 – 13.30.0`. | T38 – T39 |
| aws/aws-sdk-php | high | PKSA-4t1p-xpk2-nsss | Bản vá (`>= 3.371.4`) yêu cầu **PHP >= 8.1**, runtime hiện tại là PHP 8.0. | T19 |
| aws/aws-sdk-php | medium | PKSA-dxyf-6n16-t87m | Cùng lý do trên (`>= 3.368.0` cũng cần PHP 8.1). | T19 |
| firebase/php-jwt | low | PKSA-y2cr-5h3j-g3ys | Cần `>= 7.0`, nhưng `laravel/passport` 10.4 yêu cầu `firebase/php-jwt ^6.0`. | T26 / T34 (Passport 11 / 12) |
| league/flysystem | low | PKSA-w9tt-7782-78jx | Cần `>= 3.35.3`, hiện đang dùng flysystem 1.x. | T24 (flysystem 3) |
| symfony/dom-crawler | low | PKSA-5r1g-c7b7-y1zg | Bản vá `>= 5.4.52`, constraint hiện tại là `^4.4` (dev dependency của Codeception). | T18 (gỡ Codeception) |

Danh sách allowlist dùng cho gate T12: `docs/upgrade/advisories-allowed-l8.txt`.

## Kết luận

- Không còn advisory `critical`/`high` nào **có thể đóng trong phạm vi Laravel 8 + PHP 8.0**.
- Advisory `critical` còn lại của `laravel/framework` chỉ đóng được bằng việc nâng
  major, đó chính là phần còn lại của lộ trình (T20 – T43).
- Nhánh này có thể tách thành PR "patch bảo mật trong phạm vi Laravel 8" và
  deploy riêng nếu cần giảm rủi ro trước khi làm nâng major.

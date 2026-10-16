# قرارداد ارتباطی TaranehYar ↔ HostDL (نسخه 1)

همه درخواست‌ها به‌صورت `POST` (فرم-urlencoded) به endpoint مربوطه روی هاست دانلود ارسال می‌شوند. افزونه فیلد `token` را به همه درخواست‌ها اضافه می‌کند.

## فیلدهای مشترک

| فیلد | توضیح |
|---|---|
| `token` | توکن امنیتی — باید با `$smp_token` در `config.php` هاست دانلود یکسان باشد |
| `tag_site` | نام سایت مشتری برای تگ‌گذاری ID3 (اختیاری) |
| `archive_path` | مسیر نسبی آرشیو، مثل `music/a/alireza-rozegar` (اختیاری برای سازگاری با درخواست‌های قدیمی) |
| `archive_root` | فقط برای `curl-music.php`: یکی از `music`، `remix` یا `nohe`؛ با ریشهٔ `archive_path` تطبیق داده می‌شود |

در صورت توکن نامعتبر: `403` · در صورت تنظیم‌نشدن توکن: `503` · مسیر آرشیو نامعتبر: `400` · موفقیت: `200` + `OK`.

مسیرهای جدید باید دقیقاً به شکل `{root}/{letter}/{artist-slug}` باشند. ریشه‌های مجاز عبارت‌اند از `music`، `remix`، `nohe`، `music-video`، `album` و `leech`؛ برای `curl-music.php` مقدار `archive_root` باید با ریشهٔ مسیر یکسان باشد. حروف کوچک انگلیسی/عدد و خط تیره مجازند و `letter` باید حرف اول slug باشد. اگر `archive_path` ارسال نشود، HostDL از مسیر تاریخ شمسی قدیمی استفاده می‌کند تا صف‌های ذخیره‌شده در نسخه‌های پیشین نیز کار کنند.

## `curl-music.php` — تک آهنگ / ریمیکس / نوحه

| فیلد | توضیح |
|---|---|
| `archive_root` | ریشهٔ مقصد: `music` برای تک‌آهنگ، `remix` برای ریمیکس و `nohe` برای نوحه/مداحی |
| `file_name` | نام پایه فایل خروجی (مثل `Artist - Track`) |
| `artist_en`, `song_en` | برای تگ ID3 |
| `link320`, `link128` | لینک دانلود فایل صوتی (هر کدام می‌تواند خالی باشد) |
| `cover` | لینک کاور (برای تگ ID3، پس از تگ‌گذاری حذف می‌شود) |

**مسیر جدید خروجی:** `music/{letter}/{artist-slug}/...` برای تک‌آهنگ، `remix/{letter}/{artist-slug}/...` برای ریمیکس و `nohe/{letter}/{artist-slug}/...` برای نوحه/مداحی؛ در هرکدام فایل‌های ۳۲۰ و ۱۲۸ ذخیره می‌شوند.

**سازگاری قدیمی:** درخواست‌های نسخه‌های قبلی که `archive_root` و `archive_path` ندارند همچنان در `music/{Y/m/d}/...` ذخیره می‌شوند. در درخواست جدید، `archive_path` باید با `archive_root` مطابقت داشته باشد.

## `curl-video.php` — موزیک ویدیو

| فیلد | توضیح |
|---|---|
| `file_name`, `artist_en`, `song_en`, `cover`, `tag_site` | مانند بالا |
| `link1080`, `link720`, `link480` | لینک ویدیوها |
| `link320` | لینک فایل صوتی MP3 |

**مسیر جدید خروجی:** `music-video/{letter}/{artist-slug}/{file_name} [1080].mp4`، `[720].mp4`، `[480].mp4` و `{file_name}.mp3`.

**مسیر قدیمی در صورت نبودن `archive_path`:** `video/{Y/m/d}/...`.

## `curl-album.php` — آلبوم

| فیلد | توضیح |
|---|---|
| `album_name` | نام پایه آلبوم (مثل `Artist - Album`) |
| `artist_en`, `song_en`, `cover`, `tag_site` | مانند بالا |
| `track_name_{N}` | نام ترک N (N=۱..۱۵) |
| `track_file_{N}` | نام پایه فایل ترک N (N=۱..۱۵) |
| `track_link320_{N}`, `track_link128_{N}` | لینک ترک‌ها |

**مسیر جدید خروجی:**
- پوشه‌ها: `album/{letter}/{artist-slug}/{album_name} [320]/` و `[128]/`
- ترک‌ها: `{track_file}.mp3` و `{track_file} [128].mp3`
- زیپ‌ها: `album/{letter}/{artist-slug}/{album_name} [320].zip` و `[128].zip`

در نبودن `archive_path` ساختار تاریخ‌محور قدیمی `album/{Y/m/d}/...` حفظ می‌شود.

## `curl-leech.php` — لیچ فایل

| فیلد | توضیح |
|---|---|
| `file_name`, `artist_en`, `song_en`, `cover`, `tag_site` | مانند بالا |
| `linkfilemp3`, `linkfilemp4` | لینک فایل‌ها (معمولاً لینک موقت از سایت اصلی) |

**مسیر جدید خروجی:** `leech/{letter}/{artist-slug}/{file_name}.mp3` و `{file_name}.mp4`.

در نبودن `archive_path`، سازگاری با مسیر قبلی `music/{Y/m/d}/...` حفظ می‌شود.

## نکات امنیتی

- همه endpointها بدون توکن معتبر هیچ کاری انجام نمی‌دهند.
- `archive_path` پیش از استفاده به‌عنوان مسیر فایل اعتبارسنجی می‌شود؛ مسیر مطلق، traversal، جداکننده معکوس و ریشه ناسازگار با endpoint رد می‌شود.
- نام فایل‌ها با `smp_safe_name()` از مسیر و کاراکترهای خطرناک پاک می‌شوند (ضد Path Traversal).
- دانلود منابع فقط از آدرس‌های http/https عمومی (ضد SSRF) و بدون دنبال‌کردن ریدایرکت انجام می‌شود.

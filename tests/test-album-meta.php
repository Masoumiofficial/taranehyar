<?php
/**
 * تست‌های ساختار متای آلبوم (ترانه‌یار 1.0.3) — بدون نیاز به وردپرس:
 *  - `album` = تعداد ترک‌های واردشده (عدد)
 *  - اندیس‌های صفرمبنا و پیوسته تا ۱۰ برای ۱۱ ترک
 *  - نام ترک‌ها و لینک‌های جداگانهٔ ۱۲۸/۳۲۰ (مقصد فایل‌های HostDL)
 *  - آلبوم خالی
 *  - حفظ آرایهٔ قدیمی ترک‌ها برای `album_dl`
 * اجرا با هر PHP:  php tests/test-album-meta.php
 */
require __DIR__ . '/../taranehyar/includes/class-ty-album-meta.php';

$fails = 0;
function album_ok($condition, $label) {
    global $fails;
    if ($condition) { echo "PASS: $label\n"; } else { echo "FAIL: $label\n"; $fails++; }
}

$host         = 'https://dl.example.ir';
$archive_path = 'album/a/test-artist';
$album_name   = 'Test Artist - Test Album';
$artist       = 'خوانندهٔ تست';
$song         = 'آلبوم تست';

// قراردادهای URL موجود برای ZIP و پوشه حفظ شوند.
$zip_url = TaranehYar_Album_Meta::file_url($host, $archive_path, 'Test Album [128].zip');
$dir_url = TaranehYar_Album_Meta::file_url($host, $archive_path, 'Test Album [128]/');
album_ok($zip_url === $host . '/album/a/test-artist/Test Album [128].zip', 'album ZIP URL remains a file URL');
album_ok($dir_url === $host . '/album/a/test-artist/Test Album [128]/', 'album folder URL keeps its trailing slash');

// ── ۱۱ ترک: شمارش، اندیس صفرمبنا، عنوان‌ها و لینک‌های جدا ۱۲۸/۳۲۰ ──
$entries = array();
for ($i = 1; $i <= 11; $i++) {
    $entries[] = array(
        'title' => "ترک شماره {$i}",
        'file'  => "Test Artist - Track {$i}",
    );
}
$built = TaranehYar_Album_Meta::build($entries, $artist, $song, $host, $archive_path, $album_name);

album_ok($built['album'] === 11, 'album meta equals the entered track count (11)');
album_ok(is_int($built['album']), 'album meta is stored as a number');
album_ok($built['meta']['artist'] === $artist, 'artist meta holds the Persian singer name from the form');
album_ok($built['meta']['song'] === $song, 'song meta holds the Persian album name from the form');
album_ok($built['meta']['musics_type'] === 'album', 'musics_type meta is album');
album_ok($built['meta']['plym'] === 0, 'plym meta is numeric 0');

$indices_ok = true;
$titles_ok  = true;
for ($i = 0; $i <= 10; $i++) {
    if (!isset($built['meta']["album_{$i}_song_names"], $built['meta']["album_{$i}_albumlink128"], $built['meta']["album_{$i}_albumlink320"])) {
        $indices_ok = false;
    } elseif ($built['meta']["album_{$i}_song_names"] !== "ترک شماره " . ($i + 1)) {
        $titles_ok = false;
    }
}
album_ok($indices_ok, 'zero-based contiguous indices 0..10 all exist (11 tracks)');
album_ok($titles_ok, 'per-track song_names hold the entered Persian track titles');
album_ok(
    !isset($built['meta']['album_11_song_names']) && !isset($built['meta']['album_11_albumlink128']) && !isset($built['meta']['album_11_albumlink320']),
    'no keys are generated past the last track (index 11 absent)'
);

$links_ok = true;
for ($i = 0; $i <= 10; $i++) {
    $l128 = $built['meta']["album_{$i}_albumlink128"];
    $l320 = $built['meta']["album_{$i}_albumlink320"];
    $exp128 = $host . '/album/a/test-artist/Test Artist - Test Album [128]/Test Artist - Track ' . ($i + 1) . ' [128].mp3';
    $exp320 = $host . '/album/a/test-artist/Test Artist - Test Album [320]/Test Artist - Track ' . ($i + 1) . '.mp3';
    if ($l128 !== $exp128 || $l320 !== $exp320 || $l128 === $l320) {
        $links_ok = false;
    }
}
album_ok($links_ok, '128 and 320 links are separate HostDL destination URLs (folder [128] vs [320])');

// ── حفظ آرایهٔ قدیمی ترک‌ها برای `album_dl` ──
$legacy_ok = is_array($built['tracks_data']) && count($built['tracks_data']) === 11;
if ($legacy_ok) {
    for ($i = 0; $i < 11; $i++) {
        $row = $built['tracks_data'][$i];
        if (!isset($row['title'], $row['al_url320'], $row['al_url128'])
            || $row['title'] !== $built['meta']["album_{$i}_song_names"]
            || $row['al_url320'] !== $built['meta']["album_{$i}_albumlink320"]
            || $row['al_url128'] !== $built['meta']["album_{$i}_albumlink128"]
            || array_keys($row) !== array('title', 'al_url320', 'al_url128')
        ) {
            $legacy_ok = false;
        }
    }
}
album_ok($legacy_ok, 'album_dl keeps the historical track array (title / al_url320 / al_url128)');

// ── آلبوم خالی ──
$empty = TaranehYar_Album_Meta::build(array(), $artist, $song, $host, $archive_path, $album_name);
album_ok($empty['album'] === 0, 'empty album stores 0 as the track count');
album_ok($empty['tracks_data'] === array(), 'empty album keeps album_dl as an empty array');
album_ok(!isset($empty['meta']['album_0_song_names']), 'empty album generates no per-track keys');
album_ok($empty['meta']['artist'] === $artist && $empty['meta']['song'] === $song, 'empty album still records artist and song');
album_ok($empty['meta']['musics_type'] === 'album' && $empty['meta']['plym'] === 0, 'empty album keeps musics_type=album and plym=0');

// ── عنوان‌های خالی حذف و اندیس‌ها پیوسته می‌مانند ──
$gapped = TaranehYar_Album_Meta::build(array(
    array('title' => 'اول',  'file' => 'A - 1'),
    array('title' => '',     'file' => 'A - 2'),
    array('title' => '   ',  'file' => 'A - 3'),
    array('title' => 'دوم',  'file' => 'A - 4'),
), $artist, $song, $host, $archive_path, $album_name);
album_ok($gapped['album'] === 2, 'tracks without a title are skipped');
album_ok(
    $gapped['meta']['album_0_song_names'] === 'اول'
        && $gapped['meta']['album_1_song_names'] === 'دوم'
        && !isset($gapped['meta']['album_2_song_names']),
    'indices stay contiguous after skipping empty titles'
);

echo $fails === 0 ? "\nALL TESTS PASSED\n" : "\n$fails TEST(S) FAILED\n";
exit($fails === 0 ? 0 : 1);

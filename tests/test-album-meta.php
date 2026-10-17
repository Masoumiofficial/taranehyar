<?php
/**
 * تست‌های متای آلبوم با اندیس یک‌مبنا: شمارش، ۱۲۸/۳۲۰، آلبوم خالی و album_dl.
 * اجرا با PHP: php tests/test-album-meta.php
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

$zip_url = TaranehYar_Album_Meta::file_url($host, $archive_path, 'Test Album [128].zip');
$dir_url = TaranehYar_Album_Meta::file_url($host, $archive_path, 'Test Album [128]/');
album_ok($zip_url === $host . '/album/a/test-artist/Test%20Album%20%5B128%5D.zip', 'album ZIP URL percent-encodes spaces and brackets');
album_ok($dir_url === $host . '/album/a/test-artist/Test%20Album%20%5B128%5D/', 'album folder URL keeps its encoded path and trailing slash');

$track_file_1 = TaranehYar_Album_Meta::track_file_name($album_name, 1);
$track_file_2 = TaranehYar_Album_Meta::track_file_name($album_name, 2);
album_ok($track_file_1 === 'Test Artist - Test Album - Track 01' && $track_file_1 !== $track_file_2, 'album track file names remain unique when Persian titles normalize identically');

// پنج ترک باید دقیقاً اندیس‌های ۱ تا ۵ داشته باشند.
$entries = array();
for ($i = 1; $i <= 5; $i++) {
    $entries[] = array(
        'title' => "ترک شماره {$i}",
        'file'  => TaranehYar_Album_Meta::track_file_name($album_name, $i),
    );
}
$built = TaranehYar_Album_Meta::build($entries, $artist, $song, $host, $archive_path, $album_name);

album_ok($built['album'] === 5, 'album meta equals the entered track count (5)');
album_ok(is_int($built['album']), 'album meta is numeric');
album_ok($built['meta']['artist'] === $artist, 'artist meta holds the Persian singer name');
album_ok($built['meta']['song'] === $song, 'song meta holds the Persian album name');
album_ok($built['meta']['musics_type'] === 'album', 'musics_type meta is album');
album_ok($built['meta']['plym'] === 0, 'plym meta is numeric 0');

$indices_ok = true;
$titles_ok = true;
$links_ok = true;
for ($i = 1; $i <= 5; $i++) {
    $name_key = "album_{$i}_song_names";
    $url128_key = "album_{$i}_albumlink128";
    $url320_key = "album_{$i}_albumlink320";
    if (!isset($built['meta'][$name_key], $built['meta'][$url128_key], $built['meta'][$url320_key])) {
        $indices_ok = false;
        continue;
    }
    if ($built['meta'][$name_key] !== "ترک شماره {$i}") {
        $titles_ok = false;
    }
    $track_file = 'Test Artist - Test Album - Track ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
    $expected128 = $host . '/album/a/test-artist/Test%20Artist%20-%20Test%20Album%20%5B128%5D/' . rawurlencode($track_file . ' [128].mp3');
    $expected320 = $host . '/album/a/test-artist/Test%20Artist%20-%20Test%20Album%20%5B320%5D/' . rawurlencode($track_file . '.mp3');
    if ($built['meta'][$url128_key] !== $expected128 || $built['meta'][$url320_key] !== $expected320 || $built['meta'][$url128_key] === $built['meta'][$url320_key]) {
        $links_ok = false;
    }
}
album_ok($indices_ok, 'five tracks generate contiguous one-based indices 1..5');
album_ok($titles_ok, 'track titles match their one-based indices');
album_ok($links_ok, '128 and 320 links point to the correct separate HostDL destinations');
album_ok(!isset($built['meta']['album_0_song_names']) && !isset($built['meta']['album_6_song_names']), 'no zero-based or out-of-range track keys are generated');

$legacy_ok = is_array($built['tracks_data']) && count($built['tracks_data']) === 5;
if ($legacy_ok) {
    foreach ($built['tracks_data'] as $offset => $row) {
        $index = $offset + 1;
        if (array_keys($row) !== array('title', 'al_url320', 'al_url128')
            || $row['title'] !== $built['meta']["album_{$index}_song_names"]
            || $row['al_url320'] !== $built['meta']["album_{$index}_albumlink320"]
            || $row['al_url128'] !== $built['meta']["album_{$index}_albumlink128"]
        ) {
            $legacy_ok = false;
            break;
        }
    }
}
album_ok($legacy_ok, 'album_dl keeps the historical title / al_url320 / al_url128 array');

$sample_entries = array();
for ($i = 1; $i <= 11; $i++) {
    $sample_entries[] = array(
        'title' => "ترک شماره {$i}",
        'file'  => TaranehYar_Album_Meta::track_file_name('Mohsen Yeganeh - Rage Khab Album', $i),
    );
}
$sample_album = TaranehYar_Album_Meta::build($sample_entries, $artist, $song, $host, $archive_path, 'Mohsen Yeganeh - Rage Khab Album');
$sample_urls128 = array_column($sample_album['tracks_data'], 'al_url128');
$sample_urls320 = array_column($sample_album['tracks_data'], 'al_url320');
album_ok($sample_album['album'] === 11 && count(array_unique($sample_urls128)) === 11 && count(array_unique($sample_urls320)) === 11, 'all 11 tracks receive distinct 128 and 320 destinations');

$empty = TaranehYar_Album_Meta::build(array(), $artist, $song, $host, $archive_path, $album_name);
album_ok($empty['album'] === 0, 'empty album stores 0 as track count');
album_ok($empty['tracks_data'] === array(), 'empty album keeps album_dl as an empty array');
album_ok(!isset($empty['meta']['album_1_song_names']), 'empty album generates no track keys');
album_ok($empty['meta']['artist'] === $artist && $empty['meta']['song'] === $song, 'empty album still records singer and album names');

$gapped = TaranehYar_Album_Meta::build(array(
    array('title' => 'اول', 'file' => 'A - 1'),
    array('title' => '', 'file' => 'A - 2'),
    array('title' => '   ', 'file' => 'A - 3'),
    array('title' => 'دوم', 'file' => 'A - 4'),
), $artist, $song, $host, $archive_path, $album_name);
album_ok($gapped['album'] === 2, 'blank track titles are skipped');
album_ok($gapped['meta']['album_1_song_names'] === 'اول' && $gapped['meta']['album_2_song_names'] === 'دوم' && !isset($gapped['meta']['album_3_song_names']), 'indices remain contiguous and one-based after blank tracks');

echo $fails === 0 ? "\nALL TESTS PASSED\n" : "\n$fails TEST(S) FAILED\n";
exit($fails === 0 ? 0 : 1);

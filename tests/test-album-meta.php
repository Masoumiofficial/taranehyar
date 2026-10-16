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
album_ok($zip_url === $host . '/album/a/test-artist/Test Album [128].zip', 'album ZIP URL remains a file URL');
album_ok($dir_url === $host . '/album/a/test-artist/Test Album [128]/', 'album folder URL keeps its trailing slash');

// پنج ترک باید دقیقاً اندیس‌های ۱ تا ۵ داشته باشند.
$entries = array();
for ($i = 1; $i <= 5; $i++) {
    $entries[] = array(
        'title' => "ترک شماره {$i}",
        'file'  => "Test Artist - Track {$i}",
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
    $expected128 = $host . '/album/a/test-artist/Test Artist - Test Album [128]/Test Artist - Track ' . $i . ' [128].mp3';
    $expected320 = $host . '/album/a/test-artist/Test Artist - Test Album [320]/Test Artist - Track ' . $i . '.mp3';
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

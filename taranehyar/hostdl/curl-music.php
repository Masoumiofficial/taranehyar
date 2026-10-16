<?php
/**
 * TaranehYar | HostDL - endpoint تک آهنگ / ریمیکس / نوحه
 *
 * ورودی (POST): token, file_name, artist_en, song_en, link320, link128, cover, tag_site
 * خروجی جدید:   {music|remix|nohe}/{letter}/{artist-slug}/{file_name}.mp3 و [128].mp3
 * سازگاری قدیم: music/{Y/m/d}/... برای درخواست‌های بدون archive_path/archive_root
 */
require_once __DIR__ . '/bootstrap.php';
smp_require_auth();

$file_name = smp_safe_name(isset($_POST['file_name']) ? $_POST['file_name'] : '');
$artist_en = isset($_POST['artist_en']) ? trim((string) $_POST['artist_en']) : '';
$song_en   = isset($_POST['song_en']) ? trim((string) $_POST['song_en']) : '';
$link320   = isset($_POST['link320']) ? trim((string) $_POST['link320']) : '';
$link128   = isset($_POST['link128']) ? trim((string) $_POST['link128']) : '';
$cover     = isset($_POST['cover']) ? trim((string) $_POST['cover']) : '';
$tag_site  = isset($_POST['tag_site']) ? trim((string) $_POST['tag_site']) : '';

$archive_root = isset($_POST['archive_root']) ? trim((string) $_POST['archive_root']) : 'music';
$music_roots  = array('music', 'remix', 'nohe');
if (!in_array($archive_root, $music_roots, true)) {
    http_response_code(400);
    exit('Invalid archive_root');
}

// Requests from older plugin versions still land in the historical music date folder.
$legacy_root = $archive_root === 'music' ? 'music' : $archive_root;
$dir_file = smp_archive_directory($archive_root, $legacy_root . '/' . jdate_win('Y/m/d', time()));
smp_mkdir($dir_file);

$path320 = __DIR__ . "/{$dir_file}/{$file_name}.mp3";
$path128 = __DIR__ . "/{$dir_file}/{$file_name} [128].mp3";
$cover_temp = __DIR__ . "/{$dir_file}/{$file_name}.cover.jpg";

$has_cover = ($cover !== '') ? smp_download($cover, $cover_temp) : false;

if ($link320 !== '' && smp_download($link320, $path320)) {
    smp_write_id3($path320, $artist_en, $song_en, $has_cover ? $cover_temp : '', $tag_site);
}
if ($link128 !== '' && smp_download($link128, $path128)) {
    smp_write_id3($path128, $artist_en, $song_en, $has_cover ? $cover_temp : '', $tag_site);
}

if ($has_cover) @unlink($cover_temp);

http_response_code(200);
echo 'OK';

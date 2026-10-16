<?php
/**
 * تست‌های عملکردی توابع هاست دانلود — بدون نیاز به وردپرس یا وب‌سرور
 * اجرا با هر PHP:  php tests/test-hostdl.php
 */
require __DIR__ . '/../taranehyar/hostdl/bootstrap.php';

$fails = 0;
function ok($cond, $label) {
    global $fails;
    if ($cond) { echo "PASS: $label\n"; } else { echo "FAIL: $label\n"; $fails++; }
}

// ── smp_safe_name: ضد Path Traversal ──
ok(smp_safe_name('../../etc/passwd') === 'passwd', 'traversal ../ removed (basename)');
ok(smp_safe_name('..\\..\\windows\\cmd') === 'cmd', 'backslash traversal removed');
ok(smp_safe_name('Artist - Track') === 'Artist - Track', 'normal name unchanged');
ok(smp_safe_name('Track [128]') === 'Track [128]', 'brackets kept');
ok(smp_safe_name('A/B.mp3') === 'B.mp3', 'slash path stripped');
ok(smp_safe_name('name;rm -rf /') === 'namerm -rf', 'special chars removed');
ok(smp_safe_name('') === 'file', 'empty falls back to file');
ok(smp_safe_name('...') === 'file', 'dots-only falls back');
ok(strpos(smp_safe_name('a' . chr(0) . 'b'), chr(0)) === false, 'null byte removed');

// ── jdate: خروجی شمسی ──
$d = jdate_win('Y/m/d', time());
ok(preg_match('#^\d{4}/\d{2}/\d{2}$#', $d) === 1, "jdate format Y/m/d ($d)");

// ── قرارداد نام‌گذاری خروجی (هم‌سانی با متای وردپرس) ──
$album = smp_safe_name('Artist - Album');
ok($album . ' [320]' === 'Artist - Album [320]', 'album 320 dir pattern');
ok($album . ' [320].zip' === 'Artist - Album [320].zip', 'album 320 zip pattern');
$track = smp_safe_name('Artist - Track');
ok($track . ' [128].mp3' === 'Artist - Track [128].mp3', '128k file pattern');

// ── archive_path: strict relative path validation and legacy fallback ──
ok(smp_validate_archive_path('music/a/alireza-rozegar', 'music') === 'music/a/alireza-rozegar', 'valid music archive path');
ok(smp_validate_archive_path('remix/a/alireza-rozegar', 'remix') === 'remix/a/alireza-rozegar', 'valid separate remix archive path');
ok(smp_validate_archive_path('nohe/a/alireza-rozegar', 'nohe') === 'nohe/a/alireza-rozegar', 'valid separate nohe archive path');
ok(smp_validate_archive_path('music/a/alireza-rozegar/', 'music') === 'music/a/alireza-rozegar', 'trailing slash is normalized');
ok(smp_validate_archive_path('music-video/a/alireza-rozegar', 'music-video') === 'music-video/a/alireza-rozegar', 'valid music-video archive path');
ok(smp_validate_archive_path('album/a/alireza-rozegar', 'album') === 'album/a/alireza-rozegar', 'valid album archive path');
ok(smp_validate_archive_path('leech/a/alireza-rozegar', 'leech') === 'leech/a/alireza-rozegar', 'valid leech archive path');
ok(smp_validate_archive_path('album/a/alireza-rozegar', 'music') === false, 'endpoint-specific root is enforced');
ok(smp_validate_archive_path('nohe/a/alireza-rozegar', 'remix') === false, 'remix and nohe roots cannot be mixed');
ok(smp_validate_archive_path('music/b/alireza-rozegar', 'music') === false, 'archive shard must match slug initial');
ok(smp_validate_archive_path('music/a/../private', 'music') === false, 'path traversal is rejected');
ok(smp_validate_archive_path('/music/a/alireza-rozegar', 'music') === false, 'absolute archive paths are rejected');
ok(smp_validate_archive_path('music/a/alireza' . chr(92) . 'rozegar', 'music') === false, 'backslashes are rejected');
ok(smp_validate_archive_path('music/a/alireza-rozegar/extra', 'music') === false, 'extra path segments are rejected');
$_POST = array();
ok(smp_archive_directory('music', 'music/1405/01/02') === 'music/1405/01/02', 'old request falls back to date-based archive');
$_POST['archive_path'] = 'music/a/alireza-rozegar';
ok(smp_archive_directory('music', 'music/1405/01/02') === 'music/a/alireza-rozegar', 'new request uses validated archive_path');

echo $fails === 0 ? "\nALL TESTS PASSED\n" : "\n$fails TEST(S) FAILED\n";
exit($fails === 0 ? 0 : 1);

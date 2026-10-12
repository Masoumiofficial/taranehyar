<?php
/**
 * Tests for artist-derived, date-free archive paths.
 * Run with: php tests/test-archive.php
 */
require __DIR__ . '/../taranehyar/includes/class-ty-archive.php';

$fails = 0;
function archive_ok($condition, $label) {
    global $fails;
    if ($condition) { echo "PASS: $label\n"; } else { echo "FAIL: $label\n"; $fails++; }
}

archive_ok(
    TaranehYar_Archive::path('music', 'Alireza Rozegar') === 'music/a/alireza-rozegar',
    'music archive is sharded by artist_en slug'
);
archive_ok(
    TaranehYar_Archive::path('music-video', 'Alireza Rozegar') === 'music-video/a/alireza-rozegar',
    'music video uses its own archive root'
);
archive_ok(
    TaranehYar_Archive::path('album', 'Alireza Rozegar') === 'album/a/alireza-rozegar',
    'album uses its own archive root'
);
archive_ok(
    TaranehYar_Archive::path('leech', 'Alireza Rozegar') === 'leech/a/alireza-rozegar',
    'leech uses its own archive root'
);
archive_ok(
    TaranehYar_Archive::artist_slug('  ALIREZA / ROZEGAR  ') === 'alireza-rozegar',
    'artist slug is lowercase and separators are normalized'
);
archive_ok(
    TaranehYar_Archive::path('video', 'Alireza Rozegar') === false,
    'unsupported archive roots are rejected'
);
archive_ok(
    TaranehYar_Archive::path('music', '') === 'music/a/artist',
    'empty artist slug has a safe deterministic fallback'
);

echo $fails === 0 ? "\nALL TESTS PASSED\n" : "\n$fails TEST(S) FAILED\n";
exit($fails === 0 ? 0 : 1);

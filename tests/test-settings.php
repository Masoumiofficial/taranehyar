<?php
/**
 * Unit tests for configurable theme metadata keys and singer taxonomy.
 * Run with: php tests/test-settings.php
 */
define('ABSPATH', __DIR__ . '/');
$GLOBALS['ty_test_options'] = array();

function get_option($key, $default = false) {
    return array_key_exists($key, $GLOBALS['ty_test_options']) ? $GLOBALS['ty_test_options'][$key] : $default;
}
function sanitize_key($key) {
    return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $key));
}
function sanitize_text_field($text) {
    return trim(strip_tags((string) $text));
}

require __DIR__ . '/../taranehyar/includes/class-ty-settings.php';

$fails = 0;
function settings_ok($condition, $label) {
    global $fails;
    if ($condition) { echo "PASS: $label\n"; } else { echo "FAIL: $label\n"; $fails++; }
}

$settings = new TaranehYar_Settings();
settings_ok(TaranehYar_Settings::meta_key('artist_en') === 'artist_en', 'default artist_en key');
settings_ok(TaranehYar_Settings::meta_key('video1080p') === 'video1080p', 'default video key');
settings_ok(TaranehYar_Settings::meta_key('unknown') === '', 'unknown mapping is rejected');

$GLOBALS['ty_test_options']['ty_options'] = array(
    'meta_key_artist_en' => 'custom_artist',
    'meta_key_album' => 'album_field',
    'singer_taxonomy' => 'performers',
);
settings_ok(TaranehYar_Settings::meta_key('artist_en') === 'custom_artist', 'custom artist metadata key');
settings_ok(TaranehYar_Settings::meta_key('album') === 'album_field', 'custom album metadata key');
settings_ok(TaranehYar_Settings::get('singer_taxonomy', 'singer') === 'performers', 'custom singer taxonomy setting');

$clean = $settings->sanitize(array(
    'meta_key_artist_en' => 'Artist EN / Custom',
    'singer_taxonomy' => 'Singer Type!',
));
settings_ok($clean['meta_key_artist_en'] === 'artistencustom', 'metadata setting is sanitized as a key');
settings_ok($clean['singer_taxonomy'] === 'singertype', 'taxonomy setting is sanitized as a key');

echo $fails === 0 ? "\nALL TESTS PASSED\n" : "\n$fails TEST(S) FAILED\n";
exit($fails === 0 ? 0 : 1);

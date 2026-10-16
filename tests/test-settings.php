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
function sanitize_textarea_field($text) {
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

$GLOBALS['ty_test_options']['ty_options']['apitoken'] = 'test-token-not-real';
$token_clean = $settings->sanitize(array('apitoken' => ''));
settings_ok($token_clean['apitoken'] === 'test-token-not-real', 'blank token field preserves the saved HostDL token');

$seo_clean = $settings->sanitize(array(
    'seo_title_remix' => 'دانلود ریمیکس {track} از {artist}',
    'seo_intro_nohe' => "متن نوحه\nبا جزئیات",
    'seo_intro_en_remix' => "Download the remix {track_en}\nby {artist_en}.",
    'seo_intro_2_remix' => 'ریمیکس «{track}» از {artist}.',
    'seo_intro_en_3_album' => 'Album: {album_en} by {artist_en}.',
    'seo_copy_default_fa_remix' => '2',
    'seo_copy_default_en_album' => 'invalid',

    'seo_title_tag_enabled' => '1',
    'seo_meta_description_enabled' => '1',
    'seo_jsonld_enabled' => '1',
    'seo_album_tracklist_enabled' => 'invalid',
));
settings_ok($seo_clean['seo_title_remix'] === 'دانلود ریمیکس {track} از {artist}', 'SEO templates retain supported placeholders');
settings_ok($seo_clean['seo_intro_nohe'] === "متن نوحه\nبا جزئیات", 'intro templates keep multiline text safely');
settings_ok($seo_clean['seo_intro_en_remix'] === "Download the remix {track_en}\nby {artist_en}.", 'English post templates are sanitized and preserve placeholders');
settings_ok($seo_clean['seo_intro_2_remix'] === 'ریمیکس «{track}» از {artist}.', 'additional Persian copy options are persisted as templates');
settings_ok($seo_clean['seo_intro_en_3_album'] === 'Album: {album_en} by {artist_en}.', 'additional English copy options are persisted as templates');
settings_ok($seo_clean['seo_copy_default_fa_remix'] === '2' && $seo_clean['seo_copy_default_en_album'] === '1', 'global default copy choices are validated');
settings_ok($seo_clean['seo_title_tag_enabled'] === '1', 'document title fallback toggle accepts enabled state');
settings_ok($seo_clean['seo_meta_description_enabled'] === '1', 'base description meta toggle accepts enabled state');
settings_ok($seo_clean['seo_jsonld_enabled'] === '1', 'JSON-LD toggle accepts enabled state');
settings_ok($seo_clean['seo_album_tracklist_enabled'] === '0', 'invalid checkbox values sanitize to disabled');
settings_ok(isset($seo_clean['seo_title_music'], $seo_clean['seo_description_album']), 'SEO template settings are registered for all content groups');
$english_intro_keys = array('seo_intro_en_music', 'seo_intro_en_remix', 'seo_intro_en_nohe', 'seo_intro_en_video', 'seo_intro_en_album');
$english_intro_registered = true;
foreach ($english_intro_keys as $english_intro_key) { if (!in_array($english_intro_key, TaranehYar_Settings::ALL_KEYS, true)) $english_intro_registered = false; }
settings_ok($english_intro_registered, 'English introduction templates are saved for every content type');
$copy_setting_keys = array(
    'seo_intro_2_music', 'seo_intro_3_remix', 'seo_intro_en_2_nohe', 'seo_intro_en_3_video',
    'seo_copy_default_fa_album', 'seo_copy_default_en_music',
);
$copy_settings_registered = true;
foreach ($copy_setting_keys as $copy_setting_key) { if (!in_array($copy_setting_key, TaranehYar_Settings::ALL_KEYS, true)) $copy_settings_registered = false; }
settings_ok($copy_settings_registered, 'template variants and global defaults are registered for all post types');
settings_ok(!in_array('seo_include_lyrics', TaranehYar_Settings::ALL_KEYS, true), 'lyrics are no longer configured for insertion into the post body');

echo $fails === 0 ? "\nALL TESTS PASSED\n" : "\n$fails TEST(S) FAILED\n";
exit($fails === 0 ? 0 : 1);

<?php
/**
 * Unit tests for content-type SEO defaults, placeholders and taxonomy terms.
 * Run with: php tests/test-seo.php
 */
define( 'ABSPATH', __DIR__ . '/' );
define( 'TY_PLUGIN_URL', 'https://example.test/plugins/taranehyar/' );
define( 'TY_VERSION', '1.0.6' );
$GLOBALS['ty_seo_test_options'] = array();
$GLOBALS['ty_seo_test_styles'] = array();
$GLOBALS['ty_seo_test_meta'] = array( 42 => array(
    'ty_seo_title' => 'عنوان SEO سفارشی',
    'ty_seo_description' => 'توضیحات ساخت‌یافته',
    'ty_content_type' => 'remix',
    'art_name' => 'خواننده آزمایشی',
    'track_name' => 'ریمیکس آزمایشی',
    'music320' => 'https://files.example.test/track-320.mp3',
    'fifu_image_url' => 'https://files.example.test/cover.jpg',
) );
function get_option( $key, $default = array() ) {
    return $key === 'ty_options' ? $GLOBALS['ty_seo_test_options'] : $default;
}
function wp_enqueue_style( $handle, $src, $deps = array(), $version = false ) {
    $GLOBALS['ty_seo_test_styles'][ $handle ] = array( $src, $deps, $version );
}
function wp_unslash( $value ) { return $value; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\\-]/', '', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function is_admin() { return false; }
function is_singular( $post_type = '' ) { return $post_type === 'post'; }
function get_queried_object_id() { return 42; }
function get_post_meta( $post_id, $key, $single = false ) {
    return isset( $GLOBALS['ty_seo_test_meta'][ $post_id ][ $key ] ) ? $GLOBALS['ty_seo_test_meta'][ $post_id ][ $key ] : '';
}
function get_post( $post_id ) { return (object) array( 'post_content' => 'متن نوشته', 'post_date' => '2026-10-16 12:00:00' ); }
function get_permalink( $post_id ) { return 'https://example.test/posts/' . $post_id . '/'; }
function get_the_post_thumbnail_url( $post_id, $size = 'post-thumbnail' ) { return ''; }
function has_excerpt( $post_id ) { return false; }
function get_the_excerpt( $post_id ) { return ''; }
function get_post_time( $format, $gmt = false, $post = null ) { return '2026-10-16T12:00:00+00:00'; }
function wp_strip_all_tags( $text ) { return strip_tags( (string) $text ); }
function esc_url_raw( $url ) { return (string) $url; }
function wp_parse_url( $url ) { return parse_url( $url ); }
function is_wp_error( $value ) { return false; }
function get_term( $id, $taxonomy ) { return (object) array( 'term_id' => $id, 'name' => $id === 13 ? 'ریمیکس' : 'موسیقی' ); }
function get_category_link( $id ) { return 'https://example.test/category/' . $id . '/'; }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function wpautop( $value ) { return '<p>' . $value . '</p>'; }
class TaranehYar_Settings {
    public static function get( $key, $default = '' ) {
        return isset( $GLOBALS['ty_seo_test_options'][ $key ] ) ? $GLOBALS['ty_seo_test_options'][ $key ] : $default;
    }
    public static function meta_key( $key ) { return $key; }
}
require __DIR__ . '/../taranehyar/includes/class-ty-seo.php';
require __DIR__ . '/../taranehyar/includes/class-ty-post-handler.php';

$fails = 0;
function seo_ok( $condition, $label ) {
    global $fails;
    if ( $condition ) { echo "PASS: $label\n"; } else { echo "FAIL: $label\n"; $fails++; }
}

$vars = array(
    'artist' => 'خواننده آزمایشی',
    'track' => 'قطعه آزمایشی',
    'artist_en' => 'Test Artist',
    'track_en' => 'Test Track',
    'album' => 'آلبوم آزمایشی',
    'site' => 'سایت نمونه',
    'qualities' => '۱۲۸ و ۳۲۰',
    'quality_sentence' => 'فایل صوتی در دسترس است.',
    'quality_sentence_en' => 'Submitted formats: 320 kbps and 128 kbps MP3.',
    'track_count_en' => '5',
    'track_count' => '۵',
    'info' => 'اطلاعات اثر',
);

$music = TaranehYar_SEO::profile( 'music', $vars );
$remix = TaranehYar_SEO::profile( 'remix', $vars );
$nohe = TaranehYar_SEO::profile( 'nohe', $vars );
$album = TaranehYar_SEO::profile( 'album', $vars );
$video = TaranehYar_SEO::profile( 'video', $vars );

seo_ok( strpos( $music['post_title'], 'دانلود آهنگ' ) !== false, 'single-track title uses the song phrase' );
seo_ok( strpos( $remix['post_title'], 'دانلود ریمیکس' ) !== false, 'remix title uses the remix phrase' );
seo_ok( strpos( $remix['description'], 'دانلود ریمیکس' ) !== false && strpos( $remix['description'], 'قطعه آزمایشی' ) !== false, 'remix description resolves its placeholders and remains type-specific' );
seo_ok( strpos( $remix['focus'], 'دانلود ریمیکس' ) !== false, 'remix focus phrase is type-specific' );
seo_ok( strpos( $nohe['title'], 'دانلود نوحه و مداحی' ) !== false, 'nohe SEO title uses the devotional phrase' );
seo_ok( strpos( $nohe['description'], 'دانلود نوحه و مداحی' ) !== false, 'nohe description uses the devotional phrase' );
seo_ok( stripos( $remix['intro_en'], 'remix' ) !== false && strpos( $remix['intro_en'], 'Test Track' ) !== false, 'remix gets a precise English post introduction' );
seo_ok( stripos( $nohe['intro_en'], 'devotional recitation' ) !== false && strpos( $nohe['intro_en'], 'Test Artist' ) !== false, 'nohe gets a precise English devotional introduction' );
seo_ok( strpos( $video['intro_en'], 'Music video:' ) === 0 && strpos( $video['intro_en'], 'Test Track' ) !== false, 'video gets an English introduction with its real title' );
seo_ok( strpos( $album['intro_en'], 'Album entry:' ) === 0 && strpos( $album['intro_en'], 'Track count: 5' ) !== false, 'album English introduction uses the submitted album and numeric track count' );
seo_ok( strpos( $remix['intro'] . $remix['intro_en'], '۱۲۸' ) === false && strpos( $remix['intro_en'], 'Submitted formats:' ) === false, 'Persian and English introductions keep file qualities out of the copy' );
seo_ok( strpos( $remix['description'], 'فایل صوتی در دسترس است.' ) !== false, 'SEO description can retain the submitted quality sentence' );
$legacy_quality_intro = TaranehYar_SEO::profile( 'remix', $vars, array(
    'intro' => 'ریمیکس {track} از {artist}. {qualities} {quality_sentence}',
    'intro_en' => 'Remix: {track_en} by {artist_en}. {qualities} {quality_sentence_en}',
) );
seo_ok( strpos( $legacy_quality_intro['intro'], 'کیفیت' ) === false && strpos( $legacy_quality_intro['intro'], '۱۲۸' ) === false && strpos( $legacy_quality_intro['intro_en'], 'Submitted formats:' ) === false && strpos( $legacy_quality_intro['intro_en'], '320' ) === false, 'legacy custom intro placeholders no longer repeat qualities' );
$GLOBALS['ty_seo_test_options']['seo_intro_2_remix'] = 'ریمیکس {track} از {artist}. {qualities} {quality_sentence}';
$cleaned_saved_intro = TaranehYar_SEO::intro_template( 'remix', 'fa', 2 );
seo_ok( strpos( $cleaned_saved_intro, '{quality_sentence}' ) === false && strpos( $cleaned_saved_intro, '{qualities}' ) === false && substr( $cleaned_saved_intro, -1 ) === '.', 'saved legacy templates have quality placeholders removed cleanly' );
seo_ok( strpos( $album['post_title'], 'دانلود آلبوم' ) !== false && strpos( $album['description'], '۵ قطعه' ) !== false, 'album template uses album name and track count' );
seo_ok( strpos( $album['description'], 'لینک' ) === false && strpos( $album['description'], 'دانلود' ) === false, 'album description avoids claiming download availability before HostDL completes' );
seo_ok( strpos( $remix['title'], '{artist}' ) === false && strpos( $remix['title'], '{track}' ) === false, 'known placeholders are fully substituted' );
$unused = TaranehYar_SEO::profile( 'music', array( 'artist' => 'خواننده' ), array( 'title' => '{album} {track_count} {artist}' ) );
seo_ok( strpos( $unused['title'], '{album}' ) === false && strpos( $unused['title'], '{track_count}' ) === false && strpos( $unused['title'], 'خواننده' ) !== false, 'non-applicable placeholders safely become empty strings' );
seo_ok( TaranehYar_SEO::option_key( 'remix', 'title' ) === 'seo_title_remix', 'SEO template option keys are isolated per content type' );
seo_ok( TaranehYar_SEO::option_key( 'unknown', 'title' ) === '', 'unknown template types are rejected' );
seo_ok( TaranehYar_SEO::intro_option_key( 'remix', 'fa', 2 ) === 'seo_intro_2_remix', 'second Persian copy option maps to its settings key' );
seo_ok( TaranehYar_SEO::intro_option_key( 'album', 'en', 3 ) === 'seo_intro_en_3_album', 'third English copy option maps to its settings key' );
seo_ok( strpos( TaranehYar_SEO::default_intro_template( 'music', 'fa', 2 ), '{artist}' ) !== false, 'alternative defaults contain dynamic artist placeholders' );
$GLOBALS['ty_seo_test_options']['seo_intro_3_music'] = '';
seo_ok( TaranehYar_SEO::intro_template( 'music', 'fa', 3 ) === '', 'an intentionally blank saved template remains blank' );
$custom = TaranehYar_SEO::profile( 'remix', $vars, array( 'title' => 'الگوی دلخواه {artist} / {track}' ) );
seo_ok( $custom['title'] === 'الگوی دلخواه خواننده آزمایشی / قطعه آزمایشی', 'custom per-type SEO template renders user-defined text' );

$remix_tags = TaranehYar_SEO::tags_for( 'remix', 'خواننده', 'Artist', 'قطعه' );
$nohe_tags = TaranehYar_SEO::tags_for( 'nohe', 'مداح', 'Maddah', 'نوحه' );
seo_ok( in_array( 'دانلود ریمیکس', $remix_tags, true ) && ! in_array( 'دانلود آهنگ', $remix_tags, true ), 'remix tags do not inherit generic song-download labels' );
seo_ok( in_array( 'دانلود نوحه', $nohe_tags, true ) && in_array( 'دانلود مداحی', $nohe_tags, true ) && in_array( 'نوحه', $nohe_tags, true ), 'nohe tags include devotional search terms' );
seo_ok( in_array( 'دانلود آهنگ', TaranehYar_SEO::tags_for( 'music', 'خواننده', '', 'قطعه' ), true ), 'single-track tags retain the song-download label' );

$seo = new TaranehYar_SEO();
$seo->enqueue_content_styles();
seo_ok( isset( $GLOBALS['ty_seo_test_styles']['ty-release-content'] ) && $GLOBALS['ty_seo_test_styles']['ty-release-content'][0] === TY_PLUGIN_URL . 'assets/css/content.css', 'single-post pages enqueue the new release-card stylesheet' );
seo_ok( $seo->filter_document_title( 'عنوان قالب' ) === 'عنوان SEO سفارشی', 'SEO title fallback applies when no SEO plugin owns the title' );
$GLOBALS['ty_seo_test_options']['seo_title_tag_enabled'] = '0';
seo_ok( $seo->filter_document_title( 'عنوان قالب' ) === 'عنوان قالب', 'document-title fallback can be disabled from settings' );

$handler = new TaranehYar_Post_Handler();
$call_private = Closure::bind( function ( $method, $args ) {
    return call_user_func_array( array( $this, $method ), $args );
}, $handler, 'TaranehYar_Post_Handler' );
$category = $call_private( 'primary_category_details', array( array( 10, 13 ) ) );
seo_ok( $category['id'] === 13 && $category['url'] === 'https://example.test/category/13/', 'internal category target prefers the configured most-specific category' );
$blank_copy_profile = $call_private( 'seo_profile', array( 'remix', $vars, array( 'intro' => '', 'intro_en' => '' ) ) );
seo_ok( $blank_copy_profile['intro'] === '' && $blank_copy_profile['intro_en'] === '', 'an explicit empty selected template suppresses the introduction' );
$GLOBALS['ty_seo_test_options']['seo_intro_2_remix'] = 'ریمیکس «{track}» از {artist}.';
$GLOBALS['ty_seo_test_options']['seo_intro_en_2_remix'] = 'Remix: {track_en} by {artist_en}.';
$GLOBALS['ty_seo_test_options']['seo_copy_default_fa_remix'] = '2';
$GLOBALS['ty_seo_test_options']['seo_copy_default_en_remix'] = '2';
$_POST = array( 'post_text_fa' => '2', 'post_text_en' => '2' );
$selected_copy = $call_private( 'selected_intro_overrides', array( 'remix' ) );
seo_ok( $selected_copy['intro'] === 'ریمیکس «{track}» از {artist}.' && $selected_copy['intro_en'] === 'Remix: {track_en} by {artist_en}.', 'post form selectors load the chosen global templates, not per-post text' );
$rendered_selected_copy = TaranehYar_SEO::profile( 'remix', $vars, $selected_copy );
seo_ok( strpos( $rendered_selected_copy['intro'], 'قطعه آزمایشی' ) !== false && strpos( $rendered_selected_copy['intro_en'], 'Test Artist' ) !== false, 'selected global templates resolve the changing artist and track values' );
$_POST['post_text_fa'] = 'arbitrary raw text is ignored';
$invalid_copy = $call_private( 'selected_intro_overrides', array( 'remix' ) );
seo_ok( strpos( $invalid_copy['intro'], 'arbitrary raw text' ) === false && $invalid_copy['intro'] === $selected_copy['intro'], 'invalid or legacy free-text submissions fall back to the configured default choice' );
$remix_content = $call_private( 'release_content', array( 'remix', array( 'intro' => 'ریمیکس آزمایشی' ), array( 'نام اثر' => 'قطعه' ), '', array(), $category ) );
seo_ok( strpos( $remix_content, 'category/13/' ) !== false && strpos( $remix_content, 'مشاهدهٔ ریمیکس‌های بیشتر' ) !== false, 'remix post body links to the remix category with a type-specific label' );
seo_ok( strpos( $remix_content, 'مشاهدهٔ آهنگ‌های بیشتر' ) === false, 'remix category link does not use the generic song label' );
seo_ok( strpos( $remix_content, 'ty-lyrics-section' ) === false && strpos( $remix_content, 'متن ترانه' ) === false, 'lyrics are not duplicated into post content when stored in the separate music_txt field' );
$credits = array( 'ترانه‌سرا' => 'شاعر آزمایشی', 'آهنگساز' => 'آهنگساز آزمایشی' );
$about_with_credit_sentence = $call_private( 'about_text_without_duplicate_credits', array(
    'صدای اثر آرام است. عوامل تولید: ترانه‌سرا: شاعر آزمایشی و آهنگساز: آهنگساز آزمایشی در ساخت آن همکاری کردند.',
    $credits,
) );
seo_ok( $about_with_credit_sentence === 'صدای اثر آرام است.', 'complete credit sentences are removed while unrelated about text remains' );
$unrelated_about = 'The song quality and lyricist discussion remains.';
seo_ok( $call_private( 'about_text_without_duplicate_credits', array( $unrelated_about, array( 'songwriter' => 'Ali' ) ) ) === $unrelated_about, 'credit-name matching does not remove unrelated words containing the same letters' );
$styled_content = $call_private( 'release_content', array(
    'music',
    array( 'intro' => 'معرفی فارسی', 'intro_en' => 'English introduction' ),
    array( 'کیفیت‌های ورودی' => '۱۲۸ و ۳۲۰' ),
    'صدای اثر آرام است. عوامل تولید: ترانه‌سرا: شاعر آزمایشی و آهنگساز: آهنگساز آزمایشی در ساخت آن همکاری کردند.',
    $credits,
    array(),
) );
seo_ok( strpos( $styled_content, 'ty-release-intro-card' ) !== false && strpos( $styled_content, 'ty-release-about' ) !== false && strpos( $styled_content, 'ty-release-facts' ) !== false, 'introduction, about text and structured facts render in distinct cards' );
seo_ok( strpos( $styled_content, '</section><!--more-->' ) !== false, 'WordPress more marker follows the complete introduction card' );
seo_ok( substr_count( $styled_content, 'شاعر آزمایشی' ) === 1 && substr_count( $styled_content, 'آهنگساز آزمایشی' ) === 1 && strpos( $styled_content, 'صدای اثر آرام است.' ) !== false, 'producer credits appear only once in facts while unrelated about text remains' );
$localized_content = $call_private( 'release_content', array( 'nohe', array( 'intro' => 'متن فارسی', 'intro_en' => 'Download <script>alert(1)</script>.' ), array(), '', array(), array() ) );
seo_ok( strpos( $localized_content, 'lang="en" dir="ltr"' ) !== false && strpos( $localized_content, '&lt;script&gt;' ) !== false, 'English introduction is visible, left-to-right, and safely escaped' );

$schema = new TaranehYar_SEO();
$call_seo_private = Closure::bind( function ( $method, $args ) {
    return call_user_func_array( array( $this, $method ), $args );
}, $schema, 'TaranehYar_SEO' );
$recording = $call_seo_private( 'schema_for_post', array( 42 ) );
seo_ok( $recording['@type'] === 'MusicRecording' && $recording['name'] === 'ریمیکس آزمایشی', 'remix schema describes the recording entity' );
seo_ok( $recording['genre'] === 'ریمیکس' && count( $recording['associatedMedia'] ) === 1, 'recording schema has type-specific genre and only available audio metadata' );
$GLOBALS['ty_seo_test_meta'][42]['ty_content_type'] = 'video';
$GLOBALS['ty_seo_test_meta'][42]['video1080p'] = 'https://files.example.test/video-1080.mp4';
$video_schema = $call_seo_private( 'schema_for_post', array( 42 ) );
seo_ok( $video_schema['@type'] === 'VideoObject' && $video_schema['uploadDate'] === '2026-10-16T12:00:00+00:00', 'video schema includes VideoObject type and upload date' );
seo_ok( $video_schema['contentUrl'] === 'https://files.example.test/video-1080.mp4', 'video schema uses the highest available video source' );

$GLOBALS['ty_seo_test_meta'][42]['ty_content_type'] = 'album';
$GLOBALS['ty_seo_test_meta'][42]['artist'] = 'خواننده آزمایشی';
$GLOBALS['ty_seo_test_meta'][42]['song'] = 'آلبوم آزمایشی';
$GLOBALS['ty_seo_test_meta'][42]['album'] = 2;
$GLOBALS['ty_seo_test_meta'][42]['album_1_song_names'] = 'ترک اول';
$GLOBALS['ty_seo_test_meta'][42]['album_1_albumlink128'] = 'https://files.example.test/track-1-128.mp3';
$GLOBALS['ty_seo_test_meta'][42]['album_1_albumlink320'] = 'https://files.example.test/track-1-320.mp3';
$GLOBALS['ty_seo_test_meta'][42]['album_2_song_names'] = 'ترک دوم';
$album_schema = $call_seo_private( 'schema_for_post', array( 42 ) );
seo_ok( $album_schema['@type'] === 'MusicAlbum' && $album_schema['numTracks'] === 2, 'album schema uses the configured numeric track count' );
seo_ok( count( $album_schema['track'] ) === 2 && $album_schema['track'][0]['position'] === 1 && $album_schema['track'][1]['name'] === 'ترک دوم', 'album schema reads one-based track keys in order' );
$GLOBALS['ty_seo_test_options']['seo_album_tracklist_enabled'] = '0';
$album_schema_without_list = $call_seo_private( 'schema_for_post', array( 42 ) );
seo_ok( ! isset( $album_schema_without_list['track'] ), 'hidden album tracklists are not marked up as visible Schema.org tracks' );

echo $fails === 0 ? "\nALL TESTS PASSED\n" : "\n$fails TEST(S) FAILED\n";
exit( $fails === 0 ? 0 : 1 );

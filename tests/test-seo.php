<?php
/**
 * Unit tests for content-type SEO defaults, placeholders and taxonomy terms.
 * Run with: php tests/test-seo.php
 */
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['ty_seo_test_options'] = array();
$GLOBALS['ty_seo_test_meta'] = array( 42 => array(
    'ty_seo_title' => 'عنوان SEO سفارشی',
    'ty_seo_description' => 'توضیحات ساخت‌یافته',
    'ty_content_type' => 'remix',
    'art_name' => 'خواننده آزمایشی',
    'track_name' => 'ریمیکس آزمایشی',
    'music320' => 'https://files.example.test/track-320.mp3',
    'fifu_image_url' => 'https://files.example.test/cover.jpg',
) );
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
    'track_count' => '۵',
    'info' => 'اطلاعات اثر',
);

$music = TaranehYar_SEO::profile( 'music', $vars );
$remix = TaranehYar_SEO::profile( 'remix', $vars );
$nohe = TaranehYar_SEO::profile( 'nohe', $vars );
$album = TaranehYar_SEO::profile( 'album', $vars );

seo_ok( strpos( $music['post_title'], 'دانلود آهنگ' ) !== false, 'single-track title uses the song phrase' );
seo_ok( strpos( $remix['post_title'], 'دانلود ریمیکس' ) !== false, 'remix title uses the remix phrase' );
seo_ok( strpos( $remix['description'], 'دانلود ریمیکس' ) !== false && strpos( $remix['description'], 'قطعه آزمایشی' ) !== false, 'remix description resolves its placeholders and remains type-specific' );
seo_ok( strpos( $remix['focus'], 'دانلود ریمیکس' ) !== false, 'remix focus phrase is type-specific' );
seo_ok( strpos( $nohe['title'], 'دانلود نوحه و مداحی' ) !== false, 'nohe SEO title uses the devotional phrase' );
seo_ok( strpos( $nohe['description'], 'دانلود نوحه و مداحی' ) !== false, 'nohe description uses the devotional phrase' );
seo_ok( strpos( $album['post_title'], 'دانلود آلبوم' ) !== false && strpos( $album['description'], '۵ قطعه' ) !== false, 'album template uses album name and track count' );
seo_ok( strpos( $remix['title'], '{artist}' ) === false && strpos( $remix['title'], '{track}' ) === false, 'known placeholders are fully substituted' );
$unused = TaranehYar_SEO::profile( 'music', array( 'artist' => 'خواننده' ), array( 'title' => '{album} {track_count} {artist}' ) );
seo_ok( strpos( $unused['title'], '{album}' ) === false && strpos( $unused['title'], '{track_count}' ) === false && strpos( $unused['title'], 'خواننده' ) !== false, 'non-applicable placeholders safely become empty strings' );
seo_ok( TaranehYar_SEO::option_key( 'remix', 'title' ) === 'seo_title_remix', 'SEO template option keys are isolated per content type' );
seo_ok( TaranehYar_SEO::option_key( 'unknown', 'title' ) === '', 'unknown template types are rejected' );
$custom = TaranehYar_SEO::profile( 'remix', $vars, array( 'title' => 'الگوی دلخواه {artist} / {track}' ) );
seo_ok( $custom['title'] === 'الگوی دلخواه خواننده آزمایشی / قطعه آزمایشی', 'custom per-type SEO template renders user-defined text' );

$remix_tags = TaranehYar_SEO::tags_for( 'remix', 'خواننده', 'Artist', 'قطعه' );
$nohe_tags = TaranehYar_SEO::tags_for( 'nohe', 'مداح', 'Maddah', 'نوحه' );
seo_ok( in_array( 'دانلود ریمیکس', $remix_tags, true ) && ! in_array( 'دانلود آهنگ', $remix_tags, true ), 'remix tags do not inherit generic song-download labels' );
seo_ok( in_array( 'دانلود نوحه', $nohe_tags, true ) && in_array( 'دانلود مداحی', $nohe_tags, true ) && in_array( 'نوحه', $nohe_tags, true ), 'nohe tags include devotional search terms' );
seo_ok( in_array( 'دانلود آهنگ', TaranehYar_SEO::tags_for( 'music', 'خواننده', '', 'قطعه' ), true ), 'single-track tags retain the song-download label' );

$seo = new TaranehYar_SEO();
seo_ok( $seo->filter_document_title( 'عنوان قالب' ) === 'عنوان SEO سفارشی', 'SEO title fallback applies when no SEO plugin owns the title' );
$GLOBALS['ty_seo_test_options']['seo_title_tag_enabled'] = '0';
seo_ok( $seo->filter_document_title( 'عنوان قالب' ) === 'عنوان قالب', 'document-title fallback can be disabled from settings' );

$handler = new TaranehYar_Post_Handler();
$call_private = Closure::bind( function ( $method, $args ) {
    return call_user_func_array( array( $this, $method ), $args );
}, $handler, 'TaranehYar_Post_Handler' );
$category = $call_private( 'primary_category_details', array( array( 10, 13 ) ) );
seo_ok( $category['id'] === 13 && $category['url'] === 'https://example.test/category/13/', 'internal category target prefers the configured most-specific category' );
$remix_content = $call_private( 'release_content', array( 'remix', array( 'intro' => 'ریمیکس آزمایشی' ), array( 'نام اثر' => 'قطعه' ), '', '', array(), $category ) );
seo_ok( strpos( $remix_content, 'category/13/' ) !== false && strpos( $remix_content, 'مشاهدهٔ ریمیکس‌های بیشتر' ) !== false, 'remix post body links to the remix category with a type-specific label' );
seo_ok( strpos( $remix_content, 'مشاهدهٔ آهنگ‌های بیشتر' ) === false, 'remix category link does not use the generic song label' );

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

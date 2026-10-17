<?php
/**
 * Unit tests for content-type SEO, credit fields, storage and taxonomy terms.
 * Run with: php tests/test-seo.php
 */
define( 'ABSPATH', __DIR__ . '/' );
define( 'TY_PLUGIN_URL', 'https://example.test/plugins/taranehyar/' );
define( 'TY_VERSION', '2.0.1' );
$GLOBALS['ty_seo_test_options'] = array();
$GLOBALS['ty_seo_test_styles'] = array();
$GLOBALS['ty_seo_test_post_updates'] = array();
$GLOBALS['ty_seo_test_saved_meta'] = array();
$GLOBALS['ty_seo_test_terms'] = array();
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
function wp_get_object_terms( $post_id, $taxonomy ) { return array( (object) array( 'name' => 'خواننده آزمایشی' ) ); }
function get_term_link( $term, $taxonomy ) { return 'https://example.test/singer/test-singer/'; }
function get_search_link( $query ) { return 'https://example.test/?s=' . rawurlencode( (string) $query ); }
function wp_update_post( $postarr ) {
    $GLOBALS['ty_seo_test_post_updates'][] = $postarr;
    return isset( $postarr['ID'] ) ? $postarr['ID'] : 0;
}
function update_post_meta( $post_id, $key, $value ) {
    $GLOBALS['ty_seo_test_saved_meta'][ $post_id ][ $key ] = $value;
    return true;
}
function wp_set_object_terms( $post_id, $terms, $taxonomy ) {
    $GLOBALS['ty_seo_test_terms'][] = array( $post_id, $terms, $taxonomy );
    return array();
}
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function wpautop( $value ) { return '<p>' . $value . '</p>'; }
require __DIR__ . '/../taranehyar/includes/class-ty-settings.php';
require __DIR__ . '/../taranehyar/includes/class-ty-seo.php';
require __DIR__ . '/../taranehyar/includes/class-ty-post-handler.php';
require __DIR__ . '/../taranehyar/includes/class-ty-admin-pages.php';

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
    'album_en' => 'Test Album',
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
seo_ok( strpos( $remix['intro_en'], 'Download New Remix Test Artist – Test Track' ) === 0, 'remix gets a precise English post introduction' );
seo_ok( stripos( $nohe['intro_en'], 'devotional recitation' ) !== false && strpos( $nohe['intro_en'], 'Test Artist' ) !== false, 'nohe gets a precise English devotional introduction' );
seo_ok( strpos( $video['intro_en'], 'Download New Music Video Test Artist – Test Track' ) === 0, 'video gets an English introduction with its real title' );
seo_ok( strpos( $album['intro_en'], 'Download New Album Test Artist – Test Album' ) === 0, 'album English introduction uses the submitted album title' );
$english_intros = array( $music['intro_en'], $remix['intro_en'], $nohe['intro_en'], $video['intro_en'], $album['intro_en'] );
seo_ok( count( array_filter( $english_intros, function ( $text ) { return preg_match( '/\\.$/u', $text ) === 1; } ) ) === 0, 'English introduction defaults end without a period' );
seo_ok( strpos( $remix['intro'] . $remix['intro_en'], '۱۲۸' ) === false && strpos( $remix['intro_en'], 'Submitted formats:' ) === false, 'Persian and English introductions keep file qualities out of the copy' );
seo_ok( strpos( $remix['description'], 'فایل صوتی در دسترس است.' ) !== false, 'SEO description can retain the submitted quality sentence' );
$legacy_quality_intro = TaranehYar_SEO::profile( 'remix', $vars, array(
    'intro' => 'ریمیکس {track} از {artist}. {qualities} {quality_sentence}',
    'intro_en' => 'Remix: {track_en} by {artist_en}. {qualities} {quality_sentence_en}',
) );
seo_ok( strpos( $legacy_quality_intro['intro'], 'کیفیت' ) === false && strpos( $legacy_quality_intro['intro'], '۱۲۸' ) === false && strpos( $legacy_quality_intro['intro_en'], 'Submitted formats:' ) === false && strpos( $legacy_quality_intro['intro_en'], '320' ) === false && substr( $legacy_quality_intro['intro_en'], -1 ) !== '.', 'legacy custom intro placeholders no longer repeat qualities and English copy has no terminal period' );
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
seo_ok( strpos( TaranehYar_SEO::default_intro_template( 'music', 'fa', 2 ), 'دانلود' ) === 0 && strpos( TaranehYar_SEO::default_intro_template( 'music', 'fa', 2 ), '{artist}' ) !== false, 'alternative defaults link the download word and retain dynamic artist placeholders' );
$GLOBALS['ty_seo_test_options']['seo_intro_music'] = 'آهنگ «{track}» از {artist}.';
$GLOBALS['ty_seo_test_options']['seo_intro_en_2_remix'] = 'This post introduces the remix “{track_en}” by {artist_en}';
seo_ok(
    TaranehYar_SEO::intro_template( 'music', 'fa', 1 ) === TaranehYar_SEO::default_intro_template( 'music', 'fa', 1 )
        && TaranehYar_SEO::intro_template( 'remix', 'en', 2 ) === TaranehYar_SEO::default_intro_template( 'remix', 'en', 2 ),
    'previous built-in saved templates upgrade automatically to the new layouts'
);
$GLOBALS['ty_seo_test_options']['seo_intro_3_music'] = '';
seo_ok( TaranehYar_SEO::intro_template( 'music', 'fa', 3 ) === '', 'an intentionally blank saved template remains blank' );
$custom = TaranehYar_SEO::profile( 'remix', $vars, array( 'title' => 'الگوی دلخواه {artist} / {track}' ) );
seo_ok( $custom['title'] === 'الگوی دلخواه خواننده آزمایشی / قطعه آزمایشی', 'custom per-type SEO template renders user-defined text' );

$post_handler_source = file_get_contents( __DIR__ . '/../taranehyar/includes/class-ty-post-handler.php' );
seo_ok(
    ! method_exists( 'TaranehYar_SEO', 'tags_for' )
        && strpos( $post_handler_source, 'wp_set_post_tags(' ) === false
        && strpos( $post_handler_source, 'TaranehYar_SEO::tags_for(' ) === false,
    'automatic WordPress post-tag generation is removed'
);

$seo = new TaranehYar_SEO();
$seo->enqueue_content_styles();
seo_ok( isset( $GLOBALS['ty_seo_test_styles']['ty-release-content'] ) && $GLOBALS['ty_seo_test_styles']['ty-release-content'][0] === TY_PLUGIN_URL . 'assets/css/content.css', 'single-post pages enqueue the new release-card stylesheet' );
$content_css = file_get_contents( __DIR__ . '/../taranehyar/assets/css/content.css' );
seo_ok( preg_match( '/\\.ty-release-content\\s*\\{[^}]*text-align:\\s*center;/s', $content_css ) === 1 && strpos( $content_css, 'ty-release-facts' ) === false && strpos( $content_css, 'ty-category-link' ) === false, 'post copy is centered and the inline facts panel has been removed' );
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
seo_ok( $selected_copy['intro'] === 'ریمیکس «{track}» از {artist}.' && $selected_copy['intro_en'] === 'Remix: {track_en} by {artist_en}', 'post form selectors load the chosen global templates, not per-post text, and remove the final English period' );
$rendered_selected_copy = TaranehYar_SEO::profile( 'remix', $vars, $selected_copy );
seo_ok( strpos( $rendered_selected_copy['intro'], 'قطعه آزمایشی' ) !== false && strpos( $rendered_selected_copy['intro_en'], 'Test Artist' ) !== false, 'selected global templates resolve the changing artist and track values' );
$_POST['post_text_fa'] = 'arbitrary raw text is ignored';
$invalid_copy = $call_private( 'selected_intro_overrides', array( 'remix' ) );
seo_ok( strpos( $invalid_copy['intro'], 'arbitrary raw text' ) === false && $invalid_copy['intro'] === $selected_copy['intro'], 'invalid or legacy free-text submissions fall back to the configured default choice' );

$_POST = array(
    'lyricist' => '  <b>شاعر تازه</b>  ',
    'composer' => 'آهنگساز تازه',
    'arranger' => 'تنظیم‌کننده تازه',
    'mixer' => 'میکس‌کننده تازه',
    'producer' => 'تولیدکننده تازه',
);
$submitted_credits = $call_private( 'submitted_credit_values', array() );
seo_ok(
    $submitted_credits['lyricist'] === 'شاعر تازه'
        && $submitted_credits['composer'] === 'آهنگساز تازه'
        && $submitted_credits['arranger'] === 'تنظیم‌کننده تازه'
        && $submitted_credits['mixer'] === 'میکس‌کننده تازه'
        && $submitted_credits['producer'] === 'تولیدکننده تازه',
    'all enabled credits accept canonical form fields and are sanitized'
);
$labeled_submitted_credits = $call_private( 'labeled_credit_values', array( $submitted_credits ) );
seo_ok(
    $labeled_submitted_credits['ترانه‌سرا'] === 'شاعر تازه'
        && $labeled_submitted_credits['آهنگساز'] === 'آهنگساز تازه'
        && $labeled_submitted_credits['تنظیم‌کننده'] === 'تنظیم‌کننده تازه'
        && $labeled_submitted_credits['میکس و مسترینگ'] === 'میکس‌کننده تازه'
        && $labeled_submitted_credits['تهیه‌کننده / تولیدکننده'] === 'تولیدکننده تازه',
    'submitted role keys map to clear Persian labels for theme metadata'
);
$_POST = array( 'lyrc' => 'شاعر قدیمی', 'melody' => 'آهنگساز قدیمی', 'arrng' => 'تنظیم قدیمی', 'mix' => 'میکس قدیمی' );
$legacy_credits = $call_private( 'submitted_credit_values', array() );
seo_ok(
    $legacy_credits['lyricist'] === 'شاعر قدیمی'
        && $legacy_credits['composer'] === 'آهنگساز قدیمی'
        && $legacy_credits['arranger'] === 'تنظیم قدیمی'
        && $legacy_credits['mixer'] === 'میکس قدیمی',
    'historical credit form keys remain accepted'
);

$admin_pages = new TaranehYar_Admin_Pages();
$render_credit_fields = Closure::bind( function () {
    $this->credit_fields();
}, $admin_pages, 'TaranehYar_Admin_Pages' );
ob_start();
$render_credit_fields();
$credit_form_html = ob_get_clean();
$all_credit_inputs_rendered = true;
foreach ( array( 'lyricist', 'composer', 'arranger', 'mixer', 'producer' ) as $credit_input ) {
    if ( strpos( $credit_form_html, 'name="' . $credit_input . '"' ) === false ) $all_credit_inputs_rendered = false;
}
seo_ok( $all_credit_inputs_rendered, 'the submission form renders all five credits when their settings are enabled' );
$GLOBALS['ty_seo_test_options']['credit_producer_enabled'] = '0';
$_POST['producer'] = 'نباید ذخیره شود';
$disabled_credits = $call_private( 'submitted_credit_values', array() );
ob_start();
$render_credit_fields();
$disabled_credit_form_html = ob_get_clean();
seo_ok(
    $disabled_credits['producer'] === '' && strpos( $disabled_credit_form_html, 'name="producer"' ) === false,
    'disabling a credit hides the form field and ignores forged submissions'
);
$GLOBALS['ty_seo_test_options']['credit_producer_enabled'] = '1';

$GLOBALS['ty_seo_test_options']['meta_key_lyricist'] = 'custom_lyricist_meta';
$GLOBALS['ty_seo_test_options']['meta_key_mixer'] = 'custom_mixer_meta';
$call_private( 'save_credit_values', array( 77, array(
    'lyricist' => 'شاعر ذخیره‌شده',
    'composer' => 'آهنگساز ذخیره‌شده',
    'arranger' => 'تنظیم ذخیره‌شده',
    'mixer' => 'میکس ذخیره‌شده',
    'producer' => 'تهیه‌کننده ذخیره‌شده',
) ) );
seo_ok(
    $GLOBALS['ty_seo_test_saved_meta'][77]['custom_lyricist_meta'] === 'شاعر ذخیره‌شده'
        && $GLOBALS['ty_seo_test_saved_meta'][77]['composer'] === 'آهنگساز ذخیره‌شده'
        && $GLOBALS['ty_seo_test_saved_meta'][77]['arranger'] === 'تنظیم ذخیره‌شده'
        && $GLOBALS['ty_seo_test_saved_meta'][77]['custom_mixer_meta'] === 'میکس ذخیره‌شده'
        && $GLOBALS['ty_seo_test_saved_meta'][77]['producer'] === 'تهیه‌کننده ذخیره‌شده',
    'all production credits are saved under configurable post-meta keys'
);
$credit_taxonomies = array();
foreach ( $GLOBALS['ty_seo_test_terms'] as $term_update ) {
    if ( $term_update[0] === 77 ) $credit_taxonomies[] = $term_update[2];
}
seo_ok(
    $credit_taxonomies === array( 'songwriter', 'composer', 'regulator', 'mixmaster', 'producer' ),
    'historical credit taxonomies remain intact and producer gains its own taxonomy term'
);
$GLOBALS['ty_seo_test_options']['credit_producer_enabled'] = '0';
$call_private( 'save_credit_values', array( 78, array( 'producer' => 'نباید ذخیره شود' ) ) );
seo_ok( empty( $GLOBALS['ty_seo_test_saved_meta'][78] ), 'disabled credits are not persisted even if passed directly to the saver' );
$GLOBALS['ty_seo_test_options']['credit_producer_enabled'] = '1';
$remix_terms = array( 'artist' => 'ریمیکس‌خوان آزمایشی', 'track' => 'ریمیکس آزمایشی', 'artist_en' => 'Test Remix Artist', 'track_en' => 'Test Remix' );
$remix_intro = TaranehYar_SEO::profile( 'remix', array_merge( $vars, $remix_terms ) );
$remix_content = $call_private( 'release_content', array( 'remix', $remix_intro, array(), '', array(), $category, $remix_terms ) );
seo_ok(
    strpos( $remix_content, '<a class="ty-release-keyword-link" href="https://example.test/category/13/">دانلود</a> ریمیکس جدید' ) !== false
        && strpos( $remix_content, 'ریمیکس‌های بیشتر' ) === false,
    'remix download text links to its configured category without a separate category CTA'
);
$music_terms = array( 'artist' => 'ناصر زینعلی 13', 'track' => 'زود رفت', 'artist_en' => 'Naser Zeynali-13', 'track_en' => 'Zood Raft' );
$music_intro = TaranehYar_SEO::profile( 'music', array_merge( $vars, $music_terms ) );
$music_content = $call_private( 'release_content', array(
    'music',
    $music_intro,
    array( 'خواننده' => 'ناصر زینعلی 13', 'نام اثر' => 'زود رفت', 'کیفیت‌های ورودی' => '۱۲۸ و ۳۲۰' ),
    'توضیح مستقل دربارهٔ این اثر.',
    array(),
    $category,
    $music_terms,
) );
seo_ok(
    strpos( $music_content, '<p style="text-align: center;" lang="fa" dir="rtl"><a class="ty-release-keyword-link" href="https://example.test/category/13/">دانلود</a> آهنگ جدید <span class="ty-singer-link-placeholder"><strong>ناصر زینعلی 13</strong></span> به نام <strong>زود رفت</strong></p>' ) !== false,
    'Persian introduction matches the centered download, linked bold singer, and bold title layout'
);
seo_ok(
    strpos( $music_content, '<p style="text-align: center;" lang="en" dir="ltr">Download New Music <span class="ty-singer-link-placeholder"><strong>Naser Zeynali-13</strong></span> – <strong>Zood Raft</strong></p>' ) !== false,
    'English introduction repeats the bold linked singer and bold title in a centered paragraph'
);
seo_ok( strpos( $music_content, '<a class="ty-release-keyword-link" href="https://example.test/category/13/">زود رفت</a>' ) === false, 'the work title is bold rather than linked in the requested intro' );
seo_ok( strpos( $music_content, '<h2>دربارهٔ اثر و آهنگ «زود رفت» از ناصر زینعلی 13</h2>' ) !== false, 'about heading remains available without a duplicate category link' );
seo_ok( strpos( $music_content, 'ty-release-facts' ) === false && strpos( $music_content, '<dt>خواننده</dt>' ) === false && strpos( $music_content, 'کیفیت‌های ورودی' ) === false, 'mapped facts and credits are omitted from post content for the theme box' );
$linked_music_content = $call_private( 'link_singer_in_content', array( 42, $music_content, 'ناصر زینعلی 13' ) );
seo_ok(
    substr_count( $linked_music_content, 'href="https://example.test/singer/test-singer/"' ) === 2
        && strpos( $linked_music_content, '<a class="ty-singer-link" href="https://example.test/singer/test-singer/"><strong>ناصر زینعلی 13</strong></a>' ) !== false
        && strpos( $linked_music_content, 'class="ty-singer-link-placeholder"' ) === false,
    'both Persian and English singer links resolve to the singer archive while preserving bold markup'
);
seo_ok( ! empty( $GLOBALS['ty_seo_test_post_updates'] ) && $GLOBALS['ty_seo_test_post_updates'][0]['post_content'] === $linked_music_content, 'resolved singer links are saved into the inserted post content' );
$category_targets = array(
    'music' => 'https://example.test/category/music/',
    'remix' => 'https://example.test/category/remix/',
    'nohe' => 'https://example.test/category/nohe/',
    'video' => 'https://example.test/category/video/',
    'album' => 'https://example.test/category/album/',
);
$category_links_work_for_all_types = true;
foreach ( $category_targets as $content_type => $category_url ) {
    $terms = array_merge( $music_terms, array( 'track' => $content_type === 'album' ? 'آلبوم آزمایشی' : 'قطعه آزمایشی', 'track_en' => $content_type === 'album' ? 'Test Album' : 'Test Track' ) );
    $intro_profile = TaranehYar_SEO::profile( $content_type, array_merge( $vars, $terms, array( 'album' => 'آلبوم آزمایشی', 'album_en' => 'Test Album' ) ) );
    $rendered = $call_private( 'release_content', array( $content_type, $intro_profile, array(), '', array(), array( 'url' => $category_url ), $terms ) );
    if ( strpos( $rendered, '<a class="ty-release-keyword-link" href="' . $category_url . '">دانلود</a>' ) === false ) {
        $category_links_work_for_all_types = false;
    }
}
seo_ok( $category_links_work_for_all_types, 'music, remix, nohe, video, and album download words link to their own configured category' );
seo_ok( strpos( $remix_content, 'ty-lyrics-section' ) === false && strpos( $remix_content, 'متن ترانه' ) === false, 'lyrics are not duplicated into post content when stored in the separate music_txt field' );
$credits = array(
    'ترانه‌سرا' => 'شاعر آزمایشی',
    'آهنگساز' => 'آهنگساز آزمایشی',
    'تنظیم‌کننده' => 'تنظیم‌کننده آزمایشی',
    'میکس و مسترینگ' => 'میکس‌کننده آزمایشی',
    'تهیه‌کننده / تولیدکننده' => 'تهیه‌کننده آزمایشی',
);
$about_with_credit_sentence = $call_private( 'about_text_without_duplicate_credits', array(
    'صدای اثر آرام است. عوامل تولید: ترانه‌سرا: شاعر آزمایشی، آهنگساز: آهنگساز آزمایشی، تنظیم: تنظیم‌کننده آزمایشی، میکس و مسترینگ: میکس‌کننده آزمایشی و تهیه‌کننده: تهیه‌کننده آزمایشی در ساخت آن همکاری کردند.',
    $credits,
) );
seo_ok( $about_with_credit_sentence === 'صدای اثر آرام است.', 'complete credit sentences are removed while unrelated about text remains' );
$producer_sentence = $call_private( 'about_text_without_duplicate_credits', array(
    'تهیه‌کننده: تهیه‌کننده آزمایشی.',
    array( 'تهیه‌کننده / تولیدکننده' => 'تهیه‌کننده آزمایشی' ),
) );
seo_ok( $producer_sentence === '', 'producer credit labels are removed when duplicated in the free-text description' );
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
seo_ok( strpos( $styled_content, '<p style="text-align: center;"' ) !== false && strpos( $styled_content, 'ty-release-about' ) !== false && strpos( $styled_content, 'ty-release-facts' ) === false, 'centered intro and optional about text remain while the facts card is omitted' );
seo_ok( strpos( $styled_content, '</p><!--more-->' ) !== false, 'WordPress more marker follows the centered introduction paragraphs' );
$credits_omitted_from_content = true;
foreach ( array_values( $credits ) as $credit_value ) {
    if ( strpos( $styled_content, $credit_value ) !== false ) $credits_omitted_from_content = false;
}
seo_ok( $credits_omitted_from_content && strpos( $styled_content, 'صدای اثر آرام است.' ) !== false, 'credits stay out of post content while unrelated about text remains' );
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

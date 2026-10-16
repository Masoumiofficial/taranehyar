<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/class-ty-album-meta.php';

class TaranehYar_Post_Handler {

    /** پسوندهای مجاز برای هر نوع فایل */
    const ALLOWED_AUDIO = array( 'mp3' );
    const ALLOWED_VIDEO = array( 'mp4' );
    const ALLOWED_IMAGE = array( 'jpg', 'jpeg', 'png', 'webp' );

    public function init() {
        add_action( 'admin_post_ty_submit_music', array( $this, 'handle_music' ) );
        add_action( 'admin_post_ty_submit_remix', array( $this, 'handle_remix' ) );
        add_action( 'admin_post_ty_submit_nohe',  array( $this, 'handle_nohe' ) );
        add_action( 'admin_post_ty_submit_video', array( $this, 'handle_video' ) );
        add_action( 'admin_post_ty_submit_album', array( $this, 'handle_album' ) );
        add_action( 'admin_post_ty_submit_leech', array( $this, 'handle_leech' ) );
    }

    /* ═══════════════════════════════════════════════
       HELPERS — امنیت و اعتبارسنجی
    ═══════════════════════════════════════════════ */

    private function verify( $action ) {
        if ( ! current_user_can( 'publish_posts' ) || ! check_admin_referer( $action ) ) {
            wp_die( 'دسترسی غیرمجاز' );
        }
    }

    private function redirect( $page, $msg, $err = false ) {
        $url = admin_url( 'admin.php?page=' . $page . '&ty_msg=' . urlencode( $msg ) );
        if ( $err ) $url .= '&ty_err=1';
        wp_redirect( $url );
        exit;
    }

    private function cfg( $key, $default = '' ) {
        return TaranehYar_Settings::get( $key, $default );
    }

    /** Write a template-dependent custom field using its configured key. */
    private function update_mapped_meta( $post_id, $key, $value ) {
        $meta_key = TaranehYar_Settings::meta_key( $key );
        if ( $meta_key !== '' ) {
            update_post_meta( $post_id, $meta_key, $value );
        }
    }

    /** Build a date-free archive path from the English artist name. */
    private function archive_path( $root, $artist_en ) {
        return TaranehYar_Archive::path( $root, $artist_en );
    }

    /** Join an archive path and file name to the configured download host URL. */
    private function download_file_url( $host, $archive_path, $filename ) {
        return TaranehYar_Album_Meta::file_url( $host, $archive_path, $filename );
    }

    /** Return the configured singer taxonomy, preserving the historical default. */
    private function singer_taxonomy() {
        $configured = $this->cfg( 'singer_taxonomy', 'singer' );
        $taxonomy = is_scalar( $configured ) ? sanitize_key( $configured ) : '';
        return $taxonomy !== '' ? $taxonomy : 'singer';
    }

    /**
     * بررسی پسوند فایل آپلودی نسبت به لیست مجاز.
     * این تابع جلوی آپلود فایل‌های اجرایی (php, exe, ...) رو می‌گیره.
     */
    private function validate_uploaded_file( $file_key, array $allowed_exts ) {
        if ( empty( $_FILES[ $file_key ]['tmp_name'] ) || $_FILES[ $file_key ]['error'] !== UPLOAD_ERR_OK ) {
            return null; // فایلی آپلود نشده، مشکلی نیست
        }
        $name = $_FILES[ $file_key ]['name'];
        $ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );

        if ( ! in_array( $ext, $allowed_exts, true ) ) {
            return new WP_Error( 'invalid_ext', "فرمت فایل آپلودی ({$ext}) مجاز نیست. فرمت‌های مجاز: " . implode( ', ', $allowed_exts ) );
        }

        $filetype = wp_check_filetype( $name );
        if ( empty( $filetype['type'] ) ) {
            return new WP_Error( 'invalid_mime', 'نوع فایل آپلودی قابل شناسایی نیست.' );
        }

        return true;
    }

    /**
     * ساخت نام فایل امن از ورودی کاربر (حذف کاراکترهای مسیر و .. برای جلوگیری از Path Traversal).
     */
    private function safe_filename( $name ) {
        $name = str_replace( array( '..', "\\", "\0" ), '', (string) $name );
        $name = preg_replace( '#[/\\\\]+#', '-', $name );
        $name = preg_replace( '/[^A-Za-z0-9\-\_\.\(\)\[\] ]/u', '', $name );
        $name = trim( preg_replace( '/\s+/', ' ', $name ), " .-" );
        return $name !== '' ? $name : 'file';
    }

    /**
     * بررسی آدرس ورودی (ضد SSRF) — ارجاع به TaranehYar_Http.
     */
    private function validate_remote_url( $url ) {
        return TaranehYar_Http::validate_remote_url( $url );
    }

    /**
     * دانلود فایل به‌صورت stream (بدون لود کامل در حافظه).
     * بازگشت: true در موفقیت، WP_Error در خطا.
     */
    private function download_file( $url, $dest ) {
        if ( empty( $url ) ) return new WP_Error( 'empty_url', 'آدرس خالی است.' );

        $valid = $this->validate_remote_url( $url );
        if ( is_wp_error( $valid ) ) return $valid;

        $args = array(
            'timeout'   => 300,
            'stream'    => true,
            'filename'  => $dest,
            'sslverify' => true,
        );
        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            @unlink( $dest );
            return $response;
        }
        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            @unlink( $dest );
            return new WP_Error( 'http_error', "دانلود فایل با خطا مواجه شد (HTTP {$code}): {$url}" );
        }
        if ( ! file_exists( $dest ) || filesize( $dest ) === 0 ) {
            return new WP_Error( 'empty_file', "فایل دانلودشده خالی است: {$url}" );
        }
        return true;
    }

    /**
     * آپلود مستقیم یا دانلود از لینک، با اعتبارسنجی پسوند فایل.
     * بازگشت: true در موفقیت، WP_Error در خطا، null اگر چیزی ارسال نشده.
     */
    private function upload_or_download( $file_key, $url_val, $dest, array $allowed_exts ) {
        if ( ! empty( $_FILES[ $file_key ]['tmp_name'] ) ) {
            $valid = $this->validate_uploaded_file( $file_key, $allowed_exts );
            if ( is_wp_error( $valid ) ) return $valid;
            if ( ! move_uploaded_file( $_FILES[ $file_key ]['tmp_name'], $dest ) ) {
                return new WP_Error( 'move_failed', 'انتقال فایل آپلودی ناموفق بود.' );
            }
            return true;
        } elseif ( ! empty( $url_val ) ) {
            $ext = strtolower( pathinfo( wp_parse_url( $url_val, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
            if ( $ext && ! in_array( $ext, $allowed_exts, true ) ) {
                return new WP_Error( 'invalid_ext', "پسوند لینک ({$ext}) مجاز نیست." );
            }
            return $this->download_file( $url_val, $dest );
        }
        return null;
    }

    private function attach_image_to_post( $filepath, $post_id ) {
        if ( ! file_exists( $filepath ) ) return;
        $filetype   = wp_check_filetype( basename( $filepath ), null );
        $upload_dir = wp_upload_dir();
        $attachment = array(
            'guid'           => $upload_dir['url'] . '/' . basename( $filepath ),
            'post_mime_type' => $filetype['type'],
            'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filepath ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );
        $attach_id = wp_insert_attachment( $attachment, $filepath, $post_id );
        require_once ABSPATH . 'wp-admin/includes/image.php';
        wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $filepath ) );
        set_post_thumbnail( $post_id, $attach_id );
    }

    private function resize_image( $filepath, $w, $h ) {
        if ( ! file_exists( $filepath ) ) return;
        $editor = wp_get_image_editor( $filepath );
        if ( is_wp_error( $editor ) ) return;
        $editor->resize( $w, $h, true );
        $editor->save( $filepath );
    }

    /**
     * آماده‌سازی منبع فایل برای ارسال به هاست دانلود.
     * - آپلود مستقیم: ذخیره موقت + URL عمومی (پس از انتقال، حذف می‌شود)
     * - لینک: ارسال همان لینک به هاست دانلود (بدون دانلود روی سایت اصلی)
     *
     * بازگشت: array( $send_url, true|WP_Error|null )
     */
    private function prepare_source( $file_key, $url_val, $local_base, array $allowed_exts, $uploads_dir, $uploads_url, array &$cleanup ) {
        if ( ! empty( $_FILES[ $file_key ]['tmp_name'] ) ) {
            $valid = $this->validate_uploaded_file( $file_key, $allowed_exts );
            if ( is_wp_error( $valid ) ) return array( '', $valid );
            $ext  = strtolower( pathinfo( $_FILES[ $file_key ]['name'], PATHINFO_EXTENSION ) );
            $dest = $uploads_dir . '/' . $local_base . '.' . $ext;
            if ( ! move_uploaded_file( $_FILES[ $file_key ]['tmp_name'], $dest ) ) {
                return array( '', new WP_Error( 'move_failed', 'انتقال فایل آپلودی ناموفق بود.' ) );
            }
            $cleanup[] = $dest;
            return array( $uploads_url . '/' . $local_base . '.' . $ext, true );
        }
        if ( ! empty( $url_val ) ) {
            $valid = $this->validate_remote_url( $url_val );
            if ( is_wp_error( $valid ) ) return array( '', $valid );
            return array( $url_val, true );
        }
        return array( '', null );
    }

    private function check_duplicate_title( $title ) {
        global $wpdb;
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_title FROM {$wpdb->posts} WHERE post_title = %s AND post_status NOT IN ('trash','auto-draft')",
                $title
            )
        );
        return ! empty( trim( (string) $existing ) );
    }

    private function post_status_and_msg( $is_draft ) {
        if ( $is_draft === 'draft' ) return array( 'draft', 'نوشته ارسالی شما پیشنویس شد.' );
        return array( 'publish', 'نوشته ارسالی شما در سایت منتشر شد.' );
    }

    /** خواندن دسته‌بندی‌های پیکربندی‌شده از تنظیمات (به‌جای هاردکد) */
    private function get_category_ids( $key, $default_csv ) {
        $raw = $this->cfg( $key, $default_csv );
        $ids = array_map( 'intval', array_filter( array_map( 'trim', explode( ',', $raw ) ) ) );
        return ! empty( $ids ) ? $ids : array_map( 'intval', explode( ',', $default_csv ) );
    }

    /** Return the most-specific configured category (last ID) and its canonical URL. */
    private function primary_category_details( array $category_ids ) {
        foreach ( array_reverse( $category_ids ) as $category_id ) {
            $category_id = (int) $category_id;
            if ( $category_id <= 0 ) continue;
            $term = get_term( $category_id, 'category' );
            if ( is_wp_error( $term ) || ! $term || empty( $term->term_id ) ) continue;
            $url = get_category_link( $category_id );
            if ( is_wp_error( $url ) || ! is_string( $url ) || $url === '' ) continue;
            return array( 'id' => $category_id, 'name' => (string) $term->name, 'url' => $url );
        }
        return array( 'id' => 0, 'name' => '', 'url' => '' );
    }

    /** Resolve a selected global copy-template ID; raw per-post copy text is never accepted. */
    private function selected_intro_overrides( $type ) {
        $overrides = array();
        foreach ( array( 'fa' => 'intro', 'en' => 'intro_en' ) as $language => $field ) {
            $post_key = $language === 'en' ? 'post_text_en' : 'post_text_fa';
            $submitted = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ?? '' ) );
            if ( ! in_array( $submitted, array( '0', '1', '2', '3' ), true ) ) {
                $submitted = TaranehYar_SEO::default_intro_variant( $type, $language );
            }
            $overrides[ $field ] = $submitted === '0'
                ? ''
                : TaranehYar_SEO::intro_template( $type, $language, (int) $submitted );
        }
        return $overrides;
    }

    /** Render per-type templates from settings with plain text placeholders. */
    private function seo_profile( $type, array $variables, array $post_overrides = array() ) {
        $overrides = array();
        foreach ( TaranehYar_SEO::TEMPLATE_FIELDS as $field => $label ) {
            $key = TaranehYar_SEO::option_key( $type, $field );
            $overrides[ $field ] = $this->cfg( $key, TaranehYar_SEO::default_template( $type, $field ) );
        }
        foreach ( $post_overrides as $field => $template ) {
            if ( isset( TaranehYar_SEO::TEMPLATE_FIELDS[ $field ] ) && is_string( $template ) ) {
                $overrides[ $field ] = $template;
            }
        }
        return TaranehYar_SEO::profile( $type, $variables, $overrides );
    }

    /** Save canonical values and, when available, per-post metadata for SEO plugins. */
    private function save_seo_metadata( $post_id, $type, array $seo ) {
        update_post_meta( $post_id, 'ty_content_type', $type );
        update_post_meta( $post_id, 'ty_seo_title', $seo['title'] );
        update_post_meta( $post_id, 'ty_seo_description', $seo['description'] );
        update_post_meta( $post_id, 'ty_focus_phrase', $seo['focus'] );

        if ( defined( 'WPSEO_VERSION' ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_title', $seo['title'] );
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', $seo['description'] );
            update_post_meta( $post_id, '_yoast_wpseo_focuskw', $seo['focus'] );
            update_post_meta( $post_id, '_yoast_wpseo_opengraph-title', $seo['title'] );
            update_post_meta( $post_id, '_yoast_wpseo_opengraph-description', $seo['description'] );
            update_post_meta( $post_id, '_yoast_wpseo_twitter-title', $seo['title'] );
            update_post_meta( $post_id, '_yoast_wpseo_twitter-description', $seo['description'] );
        } elseif ( defined( 'RANK_MATH_VERSION' ) ) {
            update_post_meta( $post_id, '_rank_math_title', $seo['title'] );
            update_post_meta( $post_id, '_rank_math_description', $seo['description'] );
            update_post_meta( $post_id, '_rank_math_focus_keyword', $seo['focus'] );
            update_post_meta( $post_id, '_rank_math_facebook_title', $seo['title'] );
            update_post_meta( $post_id, '_rank_math_facebook_description', $seo['description'] );
            update_post_meta( $post_id, '_rank_math_twitter_title', $seo['title'] );
            update_post_meta( $post_id, '_rank_math_twitter_description', $seo['description'] );
        } elseif ( defined( 'SEOPRESS_VERSION' ) ) {
            update_post_meta( $post_id, '_seopress_titles_title', $seo['title'] );
            update_post_meta( $post_id, '_seopress_titles_desc', $seo['description'] );
            update_post_meta( $post_id, '_seopress_analysis_target_kw', $seo['focus'] );
            update_post_meta( $post_id, '_seopress_social_fb_title', $seo['title'] );
            update_post_meta( $post_id, '_seopress_social_fb_desc', $seo['description'] );
            update_post_meta( $post_id, '_seopress_social_twitter_title', $seo['title'] );
            update_post_meta( $post_id, '_seopress_social_twitter_desc', $seo['description'] );
        }
    }

    /** Build human-readable, escaped post content from the submitted facts. */
    private function release_content( $type, array $seo, array $details, $info = '', array $credits = array(), array $category = array() ) {
        $content = '<article class="ty-release-content">';
        if ( $seo['intro'] !== '' ) {
            $content .= '<div class="ty-release-intro" lang="fa" dir="rtl">' . wpautop( esc_html( $seo['intro'] ) ) . '</div><!--more-->';
        }
        if ( ! empty( $seo['intro_en'] ) ) {
            $content .= '<div class="ty-release-intro ty-release-intro-en" lang="en" dir="ltr">' . wpautop( esc_html( $seo['intro_en'] ) ) . '</div>';
        }
        if ( trim( (string) $info ) !== '' ) {
            $content .= '<section><h2>درباره اثر</h2><p>' . esc_html( $info ) . '</p></section>';
        }
        if ( TaranehYar_Settings::get( 'seo_category_links_enabled', '1' ) === '1' && ! empty( $category['url'] ) ) {
            $labels = array(
                'music' => 'مشاهدهٔ آهنگ‌های بیشتر',
                'remix' => 'مشاهدهٔ ریمیکس‌های بیشتر',
                'nohe'  => 'مشاهدهٔ نوحه و مداحی‌های بیشتر',
                'video' => 'مشاهدهٔ موزیک‌ویدیوهای بیشتر',
                'album' => 'مشاهدهٔ آلبوم‌های بیشتر',
            );
            $link_label = isset( $labels[ $type ] ) ? $labels[ $type ] : 'مشاهدهٔ مطالب مرتبط';
            if ( ! empty( $category['name'] ) ) {
                $link_label .= ' در دستهٔ ' . $category['name'];
            }
            $content .= '<p class="ty-category-link"><a href="' . esc_url( $category['url'] ) . '">' . esc_html( $link_label ) . '</a></p>';
        }

        if ( TaranehYar_Settings::get( 'seo_visible_details_enabled', '1' ) === '1' ) {
            $rows = '';
            foreach ( $details as $label => $value ) {
                if ( is_scalar( $value ) && trim( (string) $value ) !== '' ) {
                    $rows .= '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( (string) $value ) . '</dd></div>';
                }
            }
            foreach ( $credits as $label => $value ) {
                if ( is_scalar( $value ) && trim( (string) $value ) !== '' ) {
                    $rows .= '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( (string) $value ) . '</dd></div>';
                }
            }
            if ( $rows !== '' ) {
                $content .= '<section class="ty-release-facts"><h2>مشخصات اثر</h2><dl>' . $rows . '</dl></section>';
            }
        }

        return $content . '</article>';
    }

    /**
     * مسیر و آدرس آپلودهای وردپرس (سازگار با WP_CONTENT_DIR سفارشی).
     */
    private function upload_base() {
        $uploads = wp_upload_dir();
        return array(
            'path' => $uploads['basedir'],
            'url'  => $uploads['baseurl'],
        );
    }

    private function ensure_upload_dir( $year, $months ) {
        $dir = trailingslashit( $this->upload_base()['path'] ) . "{$year}/{$months}";
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }
        return $dir;
    }

    private function collect_warning( array &$warnings, $result, $label ) {
        if ( is_wp_error( $result ) ) {
            $warnings[] = $label . ': ' . $result->get_error_message();
        }
    }

    /* ═══════════════════════════════════════════════
       تک آهنگ / ریمیکس / نوحه  (منطق مشترک)
    ═══════════════════════════════════════════════ */

    private function handle_music_type( $nonce_action, $redirect_page, $curl_key, $cat_key, $cat_default, $musics_type, $type_label, $seo_type ) {
        $this->verify( $nonce_action );
        set_time_limit( 600 );

        $name       = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $track      = sanitize_text_field( wp_unslash( $_POST['track'] ?? '' ) );
        $enname     = sanitize_text_field( wp_unslash( $_POST['enname'] ?? '' ) );
        $entrack    = sanitize_text_field( wp_unslash( $_POST['entrack'] ?? '' ) );
        $cover      = esc_url_raw( $_POST['cover'] ?? '' );
        $cover_mp3  = esc_url_raw( $_POST['cover_mp3'] ?? '' );
        $info       = sanitize_text_field( wp_unslash( $_POST['info'] ?? '' ) );
        $lyric      = sanitize_textarea_field( wp_unslash( $_POST['lyric'] ?? '' ) );
        $link320    = esc_url_raw( $_POST['link320'] ?? '' );
        $link128    = esc_url_raw( $_POST['link128'] ?? '' );
        $songwriter = sanitize_text_field( wp_unslash( $_POST['lyrc'] ?? '' ) );
        $composer   = sanitize_text_field( wp_unslash( $_POST['melody'] ?? '' ) );
        $regulator  = sanitize_text_field( wp_unslash( $_POST['arrng'] ?? '' ) );
        $mixmaster  = sanitize_text_field( wp_unslash( $_POST['mix'] ?? '' ) );
        $post_copy_overrides = $this->selected_intro_overrides( $seo_type );
        $is_draft   = sanitize_text_field( $_POST['is_draft'] ?? 'publish' );

        if ( empty( $name ) || empty( $track ) || empty( $enname ) || empty( $entrack ) ) {
            $this->redirect( $redirect_page, 'خطا: فیلدهای ستاره‌دار (نام خواننده، نام اثر) اجباری هستند.', true );
        }

        foreach ( array( $cover, $cover_mp3, $link320, $link128 ) as $u ) {
            $v = $this->validate_remote_url( $u );
            if ( is_wp_error( $v ) ) {
                $this->redirect( $redirect_page, 'خطا: ' . $v->get_error_message(), true );
            }
        }

        $urldlhost = $this->cfg( 'urldlhost' );

        $cat_ids = $this->get_category_ids( $cat_key, $cat_default );
        $category = $this->primary_category_details( $cat_ids );
        $quality_values = array();
        if ( ! empty( $link320 ) || ! empty( $_FILES['filelink320']['tmp_name'] ) ) $quality_values[] = '۳۲۰';
        if ( ! empty( $link128 ) || ! empty( $_FILES['filelink128']['tmp_name'] ) ) $quality_values[] = '۱۲۸';
        $quality_sentence = ! empty( $quality_values )
            ? 'کیفیت‌های واردشده: ' . implode( ' و ', $quality_values ) . '.'
            : '';
        $quality_values_en = array();
        if ( ! empty( $link320 ) || ! empty( $_FILES['filelink320']['tmp_name'] ) ) $quality_values_en[] = '320 kbps MP3';
        if ( ! empty( $link128 ) || ! empty( $_FILES['filelink128']['tmp_name'] ) ) $quality_values_en[] = '128 kbps MP3';
        $quality_sentence_en = ! empty( $quality_values_en ) ? 'Submitted formats: ' . implode( ' and ', $quality_values_en ) . '.' : '';

        $seo_site = $this->cfg( 'namesite' );
        if ( $seo_site === '' && function_exists( 'get_bloginfo' ) ) $seo_site = get_bloginfo( 'name' );
        $seo = $this->seo_profile( $seo_type, array(
            'artist'             => $name,
            'track'              => $track,
            'artist_en'          => $enname,
            'track_en'           => $entrack,
            'site'               => $seo_site,
            'qualities'          => implode( ' و ', $quality_values ),
            'quality_sentence'   => $quality_sentence,
            'quality_sentence_en' => $quality_sentence_en,
            'info'               => $info,
            'category'           => $category['name'],
        ), $post_copy_overrides );
        foreach ( array( 'post_title', 'title', 'description', 'focus' ) as $seo_field ) {
            $seo[ $seo_field ] = sanitize_text_field( $seo[ $seo_field ] );
        }
        $seo['intro'] = sanitize_textarea_field( $seo['intro'] );
        $seo['intro_en'] = sanitize_textarea_field( $seo['intro_en'] );
        $title = $seo['post_title'] !== '' ? $seo['post_title'] : "{$type_label} {$name} — {$track}";
        if ( $this->check_duplicate_title( $title ) ) {
            $this->redirect( $redirect_page, 'خطا: پستی با این عنوان قبلاً وجود دارد!', true );
        }

        $year   = date( 'Y' ); $months = date( 'm' );
        $uploads_dir = $this->ensure_upload_dir( $year, $months );

        $trackname    = $this->safe_filename( "{$enname} - {$entrack}" );
        $archive_root = in_array( $seo_type, array( 'music', 'remix', 'nohe' ), true ) ? $seo_type : 'music';
        $archive_path = $this->archive_path( $archive_root, $enname );
        $filename320 = $this->download_file_url( $urldlhost, $archive_path, $trackname . '.mp3' );
        $filename128 = $this->download_file_url( $urldlhost, $archive_path, $trackname . ' [128].mp3' );
        $img_url     = trailingslashit( $this->upload_base()['url'] ) . "{$year}/{$months}/{$trackname}.jpg";
        $img_path    = $uploads_dir . "/{$trackname}.jpg";

        $content = $this->release_content(
            $seo_type,
            $seo,
            array(
                'خواننده' => $name,
                'نام اثر' => $track,
                'نوع محتوا' => $type_label,
                'کیفیت‌های ورودی' => implode( ' و ', $quality_values ),
            ),
            $info,
            array(
                'ترانه‌سرا' => $songwriter,
                'آهنگساز' => $composer,
                'تنظیم‌کننده' => $regulator,
                'میکس و مسترینگ' => $mixmaster,
            ),
            $category
        );

        list( $post_status, $req_msg ) = $this->post_status_and_msg( $is_draft );

        $post_id = wp_insert_post( array(
            'post_author'   => get_current_user_id(),
            'post_status'   => $post_status,
            'post_type'     => 'post',
            'post_title'    => sanitize_text_field( $title ),
            'post_excerpt'  => $seo['description'],
            'post_category' => $cat_ids,
            'post_name'     => sanitize_title_with_dashes( $trackname ),
            'post_content'  => $content,
        ), true );

        if ( is_wp_error( $post_id ) ) {
            $this->redirect( $redirect_page, 'خطا در ایجاد پست: ' . $post_id->get_error_message(), true );
        }

        wp_set_post_tags( $post_id, TaranehYar_SEO::tags_for( $seo_type, $name, $enname, $track ) );
        wp_set_object_terms( $post_id, $name, $this->singer_taxonomy() );
        if ( $songwriter ) wp_set_object_terms( $post_id, $songwriter, 'songwriter' );
        if ( $composer )   wp_set_object_terms( $post_id, $composer,   'composer' );
        if ( $regulator )  wp_set_object_terms( $post_id, $regulator,  'regulator' );
        if ( $mixmaster )  wp_set_object_terms( $post_id, $mixmaster,  'mixmaster' );

        $this->save_seo_metadata( $post_id, $seo_type, $seo );
        $this->update_mapped_meta( $post_id, 'art_name',       $name );
        $this->update_mapped_meta( $post_id, 'track_name',     $track );
        $this->update_mapped_meta( $post_id, 'artist_en',      $enname );
        $this->update_mapped_meta( $post_id, 'song_en',        $entrack );
        $this->update_mapped_meta( $post_id, 'music_txt',      $lyric );
        $this->update_mapped_meta( $post_id, 'fifu_image_alt', $title );
        update_post_meta( $post_id, 'fifu_image_url', $img_url );
        $this->update_mapped_meta( $post_id, 'musics_type',    $musics_type );
        update_post_meta( $post_id, 'online_ply',     'off' );
        update_post_meta( $post_id, 'pplayer_in',     'off' );
        update_post_meta( $post_id, 'select_effect',  'imghvr-shutter-in-out-diag-1' );

        $warnings    = array();
        $cleanup     = array();
        $uploads_url = trailingslashit( $this->upload_base()['url'] ) . "{$year}/{$months}";

        list( $send320, $r ) = $this->prepare_source( 'filelink320', $link320, "temp-{$post_id}-320", self::ALLOWED_AUDIO, $uploads_dir, $uploads_url, $cleanup );
        $this->collect_warning( $warnings, $r, 'فایل ۳۲۰' );
        if ( $send320 !== '' ) $this->update_mapped_meta( $post_id, 'music320', $filename320 );

        list( $send128, $r ) = $this->prepare_source( 'filelink128', $link128, "temp-{$post_id}-128", self::ALLOWED_AUDIO, $uploads_dir, $uploads_url, $cleanup );
        $this->collect_warning( $warnings, $r, 'فایل ۱۲۸' );
        if ( $send128 !== '' ) $this->update_mapped_meta( $post_id, 'music128', $filename128 );

        $r = $this->upload_or_download( 'filecover', $cover, $img_path, self::ALLOWED_IMAGE );
        $this->collect_warning( $warnings, $r, 'کاور' );
        $send_cover = '';
        if ( $r === true ) {
            $this->resize_image( $img_path, 470, 470 );
            $this->attach_image_to_post( $img_path, $post_id );
            $send_cover = $img_url;
        }

        // انتقال فایل‌ها به هاست دانلود در صف پس‌زمینه (بدون timeout شدن درخواست ادمین)
        TaranehYar_Queue::add( array(
            'type'         => $type_label,
            'title'        => $title,
            'endpoint_key' => $curl_key,
            'archive_path' => $archive_path,
            'data'         => array(
                'archive_root' => $archive_root,
                'file_name' => $trackname,
                'artist_en' => $enname,
                'song_en'   => $entrack,
                'link320'   => $send320,
                'link128'   => $send128,
                'cover'     => $send_cover,
                'cover_mp3' => $cover_mp3,
                'tag_site'  => $this->cfg( 'namesite2', $this->cfg( 'urlsite' ) ),
            ),
            'cleanup'      => $cleanup,
        ) );

        $final_msg = $req_msg . ' ⏳ انتقال فایل‌ها به هاست دانلود در صف پس‌زمینه قرار گرفت (وضعیت از منوی مستقل «وضعیت انتقال‌ها» قابل پیگیری است).';
        $has_err   = false;
        if ( ! empty( $warnings ) ) {
            $final_msg .= ' ⚠ هشدار: ' . implode( ' | ', $warnings );
            $has_err = true;
        }

        $this->redirect( $redirect_page, $final_msg, $has_err );
    }

    public function handle_music() {
        $this->handle_music_type( 'ty_submit_music', 'ty-dashboard', 'urlfunction_music', 'cat_music', '10,12', 'mp3', 'تک آهنگ', 'music' );
    }

    public function handle_remix() {
        $this->handle_music_type( 'ty_submit_remix', 'ty-remix', 'urlfunction_remix', 'cat_remix', '10,13', 'mp3', 'ریمیکس', 'remix' );
    }

    public function handle_nohe() {
        $this->handle_music_type( 'ty_submit_nohe', 'ty-nohe', 'urlfunction_nohe', 'cat_nohe', '10,14', 'mp3', 'نوحه', 'nohe' );
    }

    /* ═══════════════════════════════════════════════
       موزیک ویدیو
    ═══════════════════════════════════════════════ */
    public function handle_video() {
        $this->verify( 'ty_submit_video' );
        set_time_limit( 600 );

        $name     = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $track    = sanitize_text_field( wp_unslash( $_POST['track'] ?? '' ) );
        $enname   = sanitize_text_field( wp_unslash( $_POST['enname'] ?? '' ) );
        $entrack  = sanitize_text_field( wp_unslash( $_POST['entrack'] ?? '' ) );
        $cover    = esc_url_raw( $_POST['cover'] ?? '' );
        $info     = sanitize_text_field( wp_unslash( $_POST['info'] ?? '' ) );
        $link1080 = esc_url_raw( $_POST['link1080'] ?? '' );
        $link720  = esc_url_raw( $_POST['link720'] ?? '' );
        $link480  = esc_url_raw( $_POST['link480'] ?? '' );
        $link320  = esc_url_raw( $_POST['link320'] ?? '' );
        $post_copy_overrides = $this->selected_intro_overrides( 'video' );
        $is_draft = sanitize_text_field( $_POST['is_draft'] ?? 'publish' );

        if ( empty( $name ) || empty( $track ) || empty( $enname ) || empty( $entrack ) ) {
            $this->redirect( 'ty-video', 'خطا: فیلدهای اجباری را پر کنید.', true );
        }

        foreach ( array( $cover, $link1080, $link720, $link480, $link320 ) as $u ) {
            $v = $this->validate_remote_url( $u );
            if ( is_wp_error( $v ) ) {
                $this->redirect( 'ty-video', 'خطا: ' . $v->get_error_message(), true );
            }
        }

        $urldlhost = $this->cfg( 'urldlhost' );
        $year   = date( 'Y' ); $months = date( 'm' );
        $uploads_dir = $this->ensure_upload_dir( $year, $months );

        $trackname   = $this->safe_filename( "{$enname} - {$entrack}" );
        $archive_path = $this->archive_path( 'music-video', $enname );
        $img_url     = trailingslashit( $this->upload_base()['url'] ) . "{$year}/{$months}/{$trackname}.jpg";
        $img_path  = $uploads_dir . "/{$trackname}.jpg";
        $cat_ids = $this->get_category_ids( 'cat_video', '10,15' );
        $category = $this->primary_category_details( $cat_ids );

        $quality_values = array();
        if ( ! empty( $link1080 ) ) $quality_values[] = '۱۰۸۰p';
        if ( ! empty( $link720 ) ) $quality_values[] = '۷۲۰p';
        if ( ! empty( $link480 ) ) $quality_values[] = '۴۸۰p';
        if ( ! empty( $link320 ) ) $quality_values[] = 'صوتی';
        $quality_sentence = ! empty( $quality_values ) ? 'کیفیت‌های واردشده: ' . implode( ' و ', $quality_values ) . '.' : '';
        $quality_values_en = array();
        if ( ! empty( $link1080 ) ) $quality_values_en[] = '1080p video';
        if ( ! empty( $link720 ) ) $quality_values_en[] = '720p video';
        if ( ! empty( $link480 ) ) $quality_values_en[] = '480p video';
        if ( ! empty( $link320 ) ) $quality_values_en[] = 'MP3 audio';
        $quality_sentence_en = ! empty( $quality_values_en ) ? 'Submitted formats: ' . implode( ', ', $quality_values_en ) . '.' : '';
        $seo_site = $this->cfg( 'namesite' );
        if ( $seo_site === '' && function_exists( 'get_bloginfo' ) ) $seo_site = get_bloginfo( 'name' );
        $seo = $this->seo_profile( 'video', array(
            'artist'              => $name,
            'track'               => $track,
            'artist_en'           => $enname,
            'track_en'            => $entrack,
            'site'                => $seo_site,
            'qualities'           => implode( ' و ', $quality_values ),
            'quality_sentence'    => $quality_sentence,
            'quality_sentence_en' => $quality_sentence_en,
            'info'                => $info,
            'category'            => $category['name'],
        ), $post_copy_overrides );
        foreach ( array( 'post_title', 'title', 'description', 'focus' ) as $seo_field ) {
            $seo[ $seo_field ] = sanitize_text_field( $seo[ $seo_field ] );
        }
        $seo['intro'] = sanitize_textarea_field( $seo['intro'] );
        $seo['intro_en'] = sanitize_textarea_field( $seo['intro_en'] );
        $title = $seo['post_title'] !== '' ? $seo['post_title'] : "دانلود موزیک ویدیو {$name} به نام {$track}";
        if ( $this->check_duplicate_title( $title ) ) {
            $this->redirect( 'ty-video', 'خطا: پستی با این عنوان قبلاً وجود دارد!', true );
        }

        $content = $this->release_content(
            'video',
            $seo,
            array(
                'خواننده' => $name,
                'نام ویدیو' => $track,
                'نوع محتوا' => 'موزیک‌ویدیو',
                'کیفیت‌های ورودی' => implode( ' و ', $quality_values ),
            ),
            $info,
            array(),
            $category
        );
        list( $post_status, $req_msg ) = $this->post_status_and_msg( $is_draft );

        $post_id = wp_insert_post( array(
            'post_author'   => get_current_user_id(),
            'post_status'   => $post_status,
            'post_type'     => 'post',
            'post_title'    => sanitize_text_field( $title ),
            'post_excerpt'  => $seo['description'],
            'post_category' => $cat_ids,
            'post_name'     => sanitize_title_with_dashes( $trackname ),
            'post_content'  => $content,
        ), true );

        if ( is_wp_error( $post_id ) ) {
            $this->redirect( 'ty-video', 'خطا در ایجاد پست: ' . $post_id->get_error_message(), true );
        }

        $this->save_seo_metadata( $post_id, 'video', $seo );
        $this->update_mapped_meta( $post_id, 'art_name',       $name );
        $this->update_mapped_meta( $post_id, 'track_name',     $track );
        $this->update_mapped_meta( $post_id, 'artist_en',      $enname );
        $this->update_mapped_meta( $post_id, 'song_en',        $entrack );
        $this->update_mapped_meta( $post_id, 'fifu_image_alt', $title );
        update_post_meta( $post_id, 'fifu_image_url', $img_url );
        $this->update_mapped_meta( $post_id, 'musics_type', 'mp4' );
        update_post_meta( $post_id, 'online_ply', 'off' );
        wp_set_object_terms( $post_id, $name, $this->singer_taxonomy() );
        wp_set_post_tags( $post_id, TaranehYar_SEO::tags_for( 'video', $name, $enname, $track ) );
        if ( ! empty( $link1080 ) ) {
            $this->update_mapped_meta( $post_id, 'video1080p', $this->download_file_url( $urldlhost, $archive_path, $trackname . ' [1080].mp4' ) );
        }
        if ( ! empty( $link720 ) ) {
            $this->update_mapped_meta( $post_id, 'video720p', $this->download_file_url( $urldlhost, $archive_path, $trackname . ' [720].mp4' ) );
        }
        if ( ! empty( $link480 ) ) {
            $this->update_mapped_meta( $post_id, 'video480p', $this->download_file_url( $urldlhost, $archive_path, $trackname . ' [480].mp4' ) );
        }
        if ( ! empty( $link320 ) ) {
            $this->update_mapped_meta( $post_id, 'music320', $this->download_file_url( $urldlhost, $archive_path, $trackname . '.mp3' ) );
        }

        $warnings = array();

        $r = $this->upload_or_download( 'filecover', $cover, $img_path, self::ALLOWED_IMAGE );
        $this->collect_warning( $warnings, $r, 'کاور' );
        $send_cover = '';
        if ( $r === true ) {
            $this->resize_image( $img_path, 470, 470 );
            $this->attach_image_to_post( $img_path, $post_id );
            $send_cover = $img_url;
        }

        TaranehYar_Queue::add( array(
            'type'         => 'موزیک ویدیو',
            'title'        => $title,
            'endpoint_key' => 'urlfunction_video',
            'archive_path' => $archive_path,
            'data'         => array(
                'file_name' => $trackname,
                'artist_en' => $enname,
                'song_en'   => $entrack,
                'link1080'  => $link1080,
                'link720'   => $link720,
                'link480'   => $link480,
                'link320'   => $link320,
                'cover'     => $send_cover,
                'tag_site'  => $this->cfg( 'namesite2', $this->cfg( 'urlsite' ) ),
            ),
        ) );

        $final_msg = $req_msg . ' ⏳ انتقال فایل‌ها به هاست دانلود در صف پس‌زمینه قرار گرفت (وضعیت از منوی مستقل «وضعیت انتقال‌ها» قابل پیگیری است).';
        $has_err   = false;
        if ( ! empty( $warnings ) ) {
            $final_msg .= ' ⚠ هشدار: ' . implode( ' | ', $warnings );
            $has_err = true;
        }

        $this->redirect( 'ty-video', $final_msg, $has_err );
    }

    /* ═══════════════════════════════════════════════
       آلبوم
    ═══════════════════════════════════════════════ */
    public function handle_album() {
        $this->verify( 'ty_submit_album' );
        set_time_limit( 600 );

        $artist    = sanitize_text_field( wp_unslash( $_POST['artist'] ?? '' ) );
        $song      = sanitize_text_field( wp_unslash( $_POST['song'] ?? '' ) );
        $artist_en = sanitize_text_field( wp_unslash( $_POST['artist_en'] ?? '' ) );
        $song_en   = sanitize_text_field( wp_unslash( $_POST['song_en'] ?? '' ) );
        $cover     = esc_url_raw( $_POST['cover'] ?? '' );
        $info      = sanitize_text_field( wp_unslash( $_POST['info'] ?? '' ) );
        $cover_mp3 = esc_url_raw( $_POST['cover_mp3'] ?? '' );
        $post_copy_overrides = $this->selected_intro_overrides( 'album' );
        $is_draft  = sanitize_text_field( $_POST['is_draft'] ?? 'publish' );

        if ( empty( $artist ) || empty( $song ) || empty( $artist_en ) || empty( $song_en ) ) {
            $this->redirect( 'ty-album', 'خطا: فیلدهای اجباری را پر کنید.', true );
        }

        foreach ( array( $cover, $cover_mp3 ) as $u ) {
            $v = $this->validate_remote_url( $u );
            if ( is_wp_error( $v ) ) {
                $this->redirect( 'ty-album', 'خطا: ' . $v->get_error_message(), true );
            }
        }

        $urldlhost = $this->cfg( 'urldlhost' );
        $year   = date( 'Y' ); $months = date( 'm' );
        $uploads_dir = $this->ensure_upload_dir( $year, $months );

        $album_name    = $this->safe_filename( "{$artist_en} - {$song_en}" );
        $album_name128 = "{$album_name} [128]";
        $album_name320 = "{$album_name} [320]";
        $archive_path  = $this->archive_path( 'album', $artist_en );
        $img_path      = $uploads_dir . "/{$album_name}.jpg";
        $img_url       = trailingslashit( $this->upload_base()['url'] ) . "{$year}/{$months}/{$album_name}.jpg";
        $album_url320  = $this->download_file_url( $urldlhost, $archive_path, $album_name320 . '.zip' );
        $album_url128  = $this->download_file_url( $urldlhost, $archive_path, $album_name128 . '.zip' );

        $track_entries = array();
        $track_warning = false;
        for ( $i = 1; $i <= TaranehYar_Album_Meta::MAX_TRACKS; $i++ ) {
            $tname = sanitize_text_field( wp_unslash( $_POST["track_name_{$i}"] ?? '' ) );
            $tl320 = esc_url_raw( $_POST["track_link320_{$i}"] ?? '' );
            $tl128 = esc_url_raw( $_POST["track_link128_{$i}"] ?? '' );
            if ( $tname === '' ) continue;

            foreach ( array( $tl320, $tl128 ) as $u ) {
                if ( is_wp_error( $this->validate_remote_url( $u ) ) ) $track_warning = true;
            }
            $track_entries[] = array(
                'title' => $tname,
                'file'  => $this->safe_filename( "{$artist_en} - {$tname}" ),
            );
        }
        $built = TaranehYar_Album_Meta::build(
            $track_entries,
            $artist,
            $song,
            $urldlhost,
            $archive_path,
            $album_name
        );

        $cat_ids = $this->get_category_ids( 'cat_album', '2' );
        $category = $this->primary_category_details( $cat_ids );
        $seo_site = $this->cfg( 'namesite' );
        if ( $seo_site === '' && function_exists( 'get_bloginfo' ) ) $seo_site = get_bloginfo( 'name' );
        $seo = $this->seo_profile( 'album', array(
            'artist'           => $artist,
            'album'            => $song,
            'track'            => $song,
            'artist_en'        => $artist_en,
            'album_en'         => $song_en,
            'track_en'         => $song_en,
            'site'             => $seo_site,
            'track_count'      => (string) $built['album'],
            'track_count_en'   => (string) $built['album'],
            'qualities'            => '',
            'quality_sentence'   => '',
            'quality_sentence_en' => '',
            'info'               => $info,
            'category'           => $category['name'],
        ), $post_copy_overrides );
        foreach ( array( 'post_title', 'title', 'description', 'focus' ) as $seo_field ) {
            $seo[ $seo_field ] = sanitize_text_field( $seo[ $seo_field ] );
        }
        $seo['intro'] = sanitize_textarea_field( $seo['intro'] );
        $seo['intro_en'] = sanitize_textarea_field( $seo['intro_en'] );
        $title = $seo['post_title'] !== '' ? $seo['post_title'] : "دانلود آلبوم {$artist} به نام {$song}";
        if ( $this->check_duplicate_title( $title ) ) {
            $this->redirect( 'ty-album', 'خطا: پستی با این عنوان قبلاً وجود دارد!', true );
        }

        $content = $this->release_content(
            'album',
            $seo,
            array(
                'خواننده' => $artist,
                'نام آلبوم' => $song,
                'تعداد قطعه‌ها' => (string) $built['album'],
            ),
            $info,
            array(),
            $category
        );
        if ( TaranehYar_Settings::get( 'seo_album_tracklist_enabled', '1' ) === '1' && ! empty( $built['tracks_data'] ) ) {
            $content .= '<section class="ty-album-tracklist"><h2>فهرست قطعه‌های آلبوم</h2><ol>';
            foreach ( $built['tracks_data'] as $position => $track_data ) {
                $content .= '<li><span>' . esc_html( $track_data['title'] ) . '</span>';
                $content .= ' <a href="' . esc_url( $track_data['al_url128'] ) . '">دانلود ۱۲۸</a>';
                $content .= ' <a href="' . esc_url( $track_data['al_url320'] ) . '">دانلود ۳۲۰</a></li>';
            }
            $content .= '</ol><p class="ty-album-bulk-download">دانلود یکجای آلبوم: '
                . '<a href="' . esc_url( $album_url128 ) . '">فایل ZIP کیفیت ۱۲۸</a> | '
                . '<a href="' . esc_url( $album_url320 ) . '">فایل ZIP کیفیت ۳۲۰</a></p></section>';
        }

        list( $post_status, $req_msg ) = $this->post_status_and_msg( $is_draft );

        $post_id = wp_insert_post( array(
            'post_author'   => get_current_user_id(),
            'post_status'   => $post_status,
            'post_type'     => 'post',
            'post_title'    => sanitize_text_field( $title ),
            'post_excerpt'  => $seo['description'],
            'post_category' => $cat_ids,
            'post_name'     => sanitize_title_with_dashes( $album_name ),
            'post_content'  => $content,
        ), true );

        if ( is_wp_error( $post_id ) ) {
            $this->redirect( 'ty-album', 'خطا در ایجاد پست: ' . $post_id->get_error_message(), true );
        }

        $this->save_seo_metadata( $post_id, 'album', $seo );
        $this->update_mapped_meta( $post_id, 'art_name',       $artist );
        $this->update_mapped_meta( $post_id, 'track_name',     $song );
        $this->update_mapped_meta( $post_id, 'artist_en',      $artist_en );
        $this->update_mapped_meta( $post_id, 'song_en',        $song_en );
        $this->update_mapped_meta( $post_id, 'fifu_image_alt', $title );
        update_post_meta( $post_id, 'fifu_image_url', $img_url );
        $this->update_mapped_meta( $post_id, 'musics_type', 'album' );
        update_post_meta( $post_id, 'online_ply',  'off' );
        update_post_meta( $post_id, 'slider_song', 'off' );
        update_post_meta( $post_id, 'pplayer_in',  'off' );
        $this->update_mapped_meta( $post_id, 'album320', $album_url320 );
        $this->update_mapped_meta( $post_id, 'album128', $album_url128 );
        update_post_meta( $post_id, 'talbume320', $this->download_file_url( $urldlhost, $archive_path, $album_name320 . '/' ) );
        update_post_meta( $post_id, 'talbume128', $this->download_file_url( $urldlhost, $archive_path, $album_name128 . '/' ) );

        wp_set_object_terms( $post_id, $artist, $this->singer_taxonomy() );
        wp_set_post_tags( $post_id, TaranehYar_SEO::tags_for( 'album', $artist, $artist_en, $song ) );

        /*
         * متاهای ساختار قالب: یک‌مبنا برای کلیدهای album_{n}_...؛
         * آرایهٔ سازگاری album_dl حفظ می‌شود.
         */
        $this->update_mapped_meta( $post_id, 'album', $built['album'] );
        foreach ( $built['meta'] as $meta_key => $meta_value ) {
            // musics_type has an existing configurable mapping; never bypass it.
            if ( $meta_key === 'musics_type' ) continue;
            update_post_meta( $post_id, $meta_key, $meta_value );
        }
        // Keep the historical field as an alias for themes that already consume it.
        update_post_meta( $post_id, 'album_dl', $built['tracks_data'] );

        $warnings = array();
        if ( $track_warning ) $warnings[] = 'برخی لینک‌های ترک‌ها نامعتبر بودند و نادیده گرفته شدند';

        $r = $this->upload_or_download( 'filecover', $cover, $img_path, self::ALLOWED_IMAGE );
        $this->collect_warning( $warnings, $r, 'کاور' );
        $send_cover = '';
        if ( $r === true ) {
            $this->attach_image_to_post( $img_path, $post_id );
            $send_cover = $img_url;
        }

        $curl_data = array(
            'album_name' => $album_name,
            'artist_en'  => $artist_en,
            'song_en'    => $song_en,
            'cover'      => $send_cover,
            'cover_mp3'  => $cover_mp3,
            'tag_site'   => $this->cfg( 'namesite2', $this->cfg( 'urlsite' ) ),
        );
        for ( $i = 1; $i <= 15; $i++ ) {
            $tname = sanitize_text_field( wp_unslash( $_POST["track_name_{$i}"] ?? '' ) );
            $curl_data["track_name_{$i}"]    = $tname;
            $curl_data["track_file_{$i}"]    = $tname !== '' ? $this->safe_filename( "{$artist_en} - {$tname}" ) : '';
            $curl_data["track_link320_{$i}"] = esc_url_raw( $_POST["track_link320_{$i}"] ?? '' );
            $curl_data["track_link128_{$i}"] = esc_url_raw( $_POST["track_link128_{$i}"] ?? '' );
        }

        TaranehYar_Queue::add( array(
            'type'         => 'آلبوم',
            'title'        => $title,
            'endpoint_key' => 'urlfunction_album',
            'archive_path' => $archive_path,
            'data'         => $curl_data,
        ) );

        $final_msg = $req_msg . ' ⏳ انتقال فایل‌ها به هاست دانلود در صف پس‌زمینه قرار گرفت (وضعیت از منوی مستقل «وضعیت انتقال‌ها» قابل پیگیری است).';
        $has_err   = false;
        if ( ! empty( $warnings ) ) {
            $final_msg .= ' ⚠ هشدار: ' . implode( ' | ', $warnings );
            $has_err = true;
        }

        $this->redirect( 'ty-album', $final_msg, $has_err );
    }

    /* ═══════════════════════════════════════════════
       لیچ فایل
    ═══════════════════════════════════════════════ */
    public function handle_leech() {
        $this->verify( 'ty_submit_leech' );
        set_time_limit( 600 );

        $enname      = sanitize_text_field( wp_unslash( $_POST['enname'] ?? '' ) );
        $entrack     = sanitize_text_field( wp_unslash( $_POST['entrack'] ?? '' ) );
        $cover       = esc_url_raw( $_POST['cover'] ?? '' );
        $linkfilemp3 = esc_url_raw( $_POST['linkfilemp3'] ?? '' );
        $linkfilemp4 = esc_url_raw( $_POST['linkfilemp4'] ?? '' );

        if ( empty( $enname ) || empty( $entrack ) ) {
            $this->redirect( 'ty-leech', 'خطا: فیلدهای اجباری را پر کنید.', true );
        }

        foreach ( array( $cover, $linkfilemp3, $linkfilemp4 ) as $u ) {
            $v = $this->validate_remote_url( $u );
            if ( is_wp_error( $v ) ) {
                $this->redirect( 'ty-leech', 'خطا: ' . $v->get_error_message(), true );
            }
        }

        $year = date( 'Y' ); $months = date( 'm' );
        $uploads_dir = $this->ensure_upload_dir( $year, $months );
        $uploads_url = trailingslashit( $this->upload_base()['url'] ) . "{$year}/{$months}";
        $filenames = $this->safe_filename( "{$enname} - {$entrack}" );
        $archive_path = $this->archive_path( 'leech', $enname );

        $uid      = uniqid();
        $warnings = array();
        $cleanup  = array();

        list( $cover_send, $r1 ) = $this->prepare_source( 'filecover', $cover, "leech-{$uid}", self::ALLOWED_IMAGE, $uploads_dir, $uploads_url, $cleanup );
        $this->collect_warning( $warnings, $r1, 'کاور' );

        list( $mp3_send, $r2 ) = $this->prepare_source( 'filemp3', $linkfilemp3, "leech-{$uid}-mp3", self::ALLOWED_AUDIO, $uploads_dir, $uploads_url, $cleanup );
        $this->collect_warning( $warnings, $r2, 'فایل MP3' );

        list( $mp4_send, $r3 ) = $this->prepare_source( 'filemp4', $linkfilemp4, "leech-{$uid}-mp4", self::ALLOWED_VIDEO, $uploads_dir, $uploads_url, $cleanup );
        $this->collect_warning( $warnings, $r3, 'فایل MP4' );

        TaranehYar_Queue::add( array(
            'type'         => 'لیچ فایل',
            'title'        => $filenames,
            'endpoint_key' => 'urlfunction_leech',
            'archive_path' => $archive_path,
            'data'         => array(
                'file_name'   => $filenames,
                'artist_en'   => $enname,
                'song_en'     => $entrack,
                'linkfilemp3' => $mp3_send,
                'linkfilemp4' => $mp4_send,
                'cover'       => $cover_send,
                'tag_site'    => $this->cfg( 'namesite2', $this->cfg( 'urlsite' ) ),
            ),
            'cleanup'      => $cleanup,
        ) );

        $final_msg = 'درخواست لیچ ثبت شد و در صف پس‌زمینه قرار گرفت (وضعیت از منوی مستقل «وضعیت انتقال‌ها» قابل پیگیری است).';
        $has_err   = false;
        if ( ! empty( $warnings ) ) {
            $final_msg .= ' ⚠ مشکلاتی پیش آمد: ' . implode( ' | ', $warnings );
            $has_err = true;
        }

        $this->redirect( 'ty-leech', $final_msg, $has_err );
    }
}

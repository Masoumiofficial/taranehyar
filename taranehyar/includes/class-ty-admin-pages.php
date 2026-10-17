<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TaranehYar_Admin_Pages {

    public function init() {
        add_action( 'admin_menu', array( $this, 'register_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_ty_transfer_run', array( $this, 'handle_transfer_run' ) );
        add_action( 'admin_post_ty_transfer_retry', array( $this, 'handle_transfer_retry' ) );
        add_action( 'admin_post_ty_transfer_discard_failed', array( $this, 'handle_transfer_discard_failed' ) );
        add_action( 'admin_post_ty_transfer_discard_queue', array( $this, 'handle_transfer_discard_queue' ) );
        add_action( 'admin_post_ty_transfer_clear_history', array( $this, 'handle_transfer_clear_history' ) );
    }

    public function register_menus() {
        add_menu_page( 'پنل موزیک', 'پنل موزیک', 'publish_posts', 'ty-dashboard',
            array( $this, 'page_music' ), 'dashicons-format-audio', 25 );
        add_submenu_page( 'ty-dashboard', 'تک آهنگ',     'تک آهنگ',     'publish_posts',    'ty-dashboard', array( $this, 'page_music' ) );
        add_submenu_page( 'ty-dashboard', 'ریمیکس',      'ریمیکس',      'publish_posts',    'ty-remix',     array( $this, 'page_remix' ) );
        add_submenu_page( 'ty-dashboard', 'نوحه',        'نوحه',        'publish_posts',    'ty-nohe',      array( $this, 'page_nohe' ) );
        add_submenu_page( 'ty-dashboard', 'موزیک ویدیو', 'موزیک ویدیو', 'publish_posts',    'ty-video',     array( $this, 'page_video' ) );
        add_submenu_page( 'ty-dashboard', 'آلبوم',       'آلبوم',       'publish_posts',    'ty-album',     array( $this, 'page_album' ) );
        add_submenu_page( 'ty-dashboard', 'لیچ فایل',   'لیچ فایل',    'publish_posts',    'ty-leech',     array( $this, 'page_leech' ) );
        add_submenu_page( 'ty-dashboard', 'وضعیت انتقال‌ها', '📡 وضعیت انتقال‌ها', 'manage_options', 'ty-transfers', array( $this, 'page_transfers' ) );
        add_submenu_page( 'ty-dashboard', 'تنظیمات',    'تنظیمات',     'manage_options', 'ty-settings',  array( new TaranehYar_Settings(), 'render_settings_page' ) );
    }

    public function enqueue_assets( $hook ) {
        $valid = array( 'toplevel_page_ty-dashboard' );
        $slugs = array( 'ty-remix','ty-nohe','ty-video','ty-album','ty-leech','ty-transfers','ty-settings' );
        foreach ( $slugs as $s ) $valid[] = 'پنل-موزیک_page_' . $s;
        foreach ( $slugs as $s ) $valid[] = 'admin_page_' . $s;
        if ( ! in_array( $hook, $valid ) && strpos( $hook, 'ty' ) === false ) return;
        wp_enqueue_style( 'ty-admin', TY_PLUGIN_URL . 'assets/css/admin.css', array(), TY_VERSION );
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_script( 'ty-admin', TY_PLUGIN_URL . 'assets/js/admin.js', array(), TY_VERSION, true );
    }

    /* ═══════════════════════════════════════════════
       HELPERS
    ═══════════════════════════════════════════════ */

    private function nav_tabs( $active ) {
        $tabs = array(
            'ty-dashboard' => array( 'icon' => '🎵', 'label' => 'تک‌آهنگ' ),
            'ty-remix'     => array( 'icon' => '🎛️', 'label' => 'ریمیکس' ),
            'ty-nohe'      => array( 'icon' => '🕌', 'label' => 'نوحه' ),
            'ty-video'     => array( 'icon' => '🎬', 'label' => 'موزیک‌ویدیو' ),
            'ty-album'     => array( 'icon' => '💿', 'label' => 'آلبوم' ),
            'ty-leech'     => array( 'icon' => '⬇️', 'label' => 'لیچ فایل' ),
        );
        if ( current_user_can( 'manage_options' ) ) {
            $tabs['ty-transfers'] = array( 'icon' => '📡', 'label' => 'وضعیت انتقال‌ها' );
            $tabs['ty-settings'] = array( 'icon' => '⚙️', 'label' => 'تنظیمات' );
        }
        echo '<div class="ty-tabs">';
        foreach ( $tabs as $slug => $t ) {
            $cls = ( $slug === $active ) ? 'ty-tab active' : 'ty-tab';
            $url = admin_url( 'admin.php?page=' . $slug );
            echo '<a href="' . esc_url( $url ) . '" class="' . $cls . '"><span class="tab-dot"></span>' . esc_html( $t['icon'] . ' ' . $t['label'] ) . '</a>';
        }
        echo '</div>';
    }

    private function header( $icon, $title, $desc ) {
        echo '<div class="ty-header">';
        echo '<div class="ty-header-icon">' . $icon . '</div>';
        echo '<div class="ty-header-text"><h1>' . esc_html($title) . '</h1><p>' . esc_html($desc) . '</p></div>';
        echo '<button type="button" class="ty-theme-toggle" id="ty-theme-toggle" title="تغییر حالت روز/شب" aria-label="تغییر حالت روز/شب">
            <span class="ty-theme-icon-light">☀️</span>
            <span class="ty-theme-icon-dark">🌙</span>
        </button>';
        echo '</div>';
    }

    private function notice() {
        if ( isset( $_GET['ty_msg'] ) ) {
            $msg = sanitize_text_field( $_GET['ty_msg'] );
            $type = isset( $_GET['ty_err'] ) ? 'error' : 'success';
            $icon = $type === 'success' ? '✅' : '❌';
            echo '<div class="ty-notice ' . $type . '">' . $icon . ' ' . esc_html($msg) . '</div>';
        }
    }

    private function card_open( $title ) {
        echo '<div class="ty-card"><div class="ty-card-title">' . $title . '</div>';
    }

    private function card_close() { echo '</div>'; }

    private function field( $label, $html, $req = false ) {
        $req_star = $req ? '<span class="req">*</span>' : '';
        echo '<div class="ty-field"><label>' . $req_star . $label . '</label>' . $html . '</div>';
    }

    private function input( $name, $placeholder = '', $type = 'text', $req = false ) {
        $r = $req ? ' required' : '';
        $dir = ( $type === 'url' ) ? ' dir="ltr"' : '';
        return '<input type="' . $type . '" name="' . esc_attr($name) . '" placeholder="' . esc_attr($placeholder) . '"' . $dir . $r . '>';
    }

    private function file_upload( $name, $label = 'انتخاب فایل یا بکشید اینجا' ) {
        return '<div class="ty-file-upload"><input type="file" name="' . esc_attr($name) . '">
            <span class="upload-icon">📎</span>
            <span class="upload-text">' . esc_html($label) . '</span></div>';
    }

    private function select_draft() {
        return '<select name="is_draft"><option value="publish">✅ منتشر شود</option><option value="draft">📝 پیشنویس شود</option></select>';
    }

    private function copy_template_select( $seo_type, $language, $name, $selected ) {
        $templates = TaranehYar_SEO::intro_templates( $seo_type, $language );
        $preview_id = 'ty-copy-preview-' . $language . '-' . $seo_type;
        $none_label = $language === 'en' ? 'No introduction' : 'بدون مقدمه';
        $html = '<select class="ty-copy-template-select" name="' . esc_attr( $name ) . '" data-ty-copy-template data-content-type="' . esc_attr( $seo_type ) . '" data-language="' . esc_attr( $language ) . '" data-preview-id="' . esc_attr( $preview_id ) . '">';
        $html .= '<option value="0" data-template=""' . selected( (string) $selected, '0', false ) . '>' . esc_html( $none_label ) . '</option>';
        foreach ( array( 1, 2, 3 ) as $variant ) {
            $template = isset( $templates[ $variant ] ) ? (string) $templates[ $variant ] : '';
            $number = array( 1 => '۱', 2 => '۲', 3 => '۳' )[ $variant ];
            $display_template = $template !== '' ? $template : ( $language === 'en' ? '[blank — no text]' : '[قالب خالی؛ متنی درج نمی‌شود]' );
            $option_label = $language === 'en' ? 'Text ' . $variant . ' — ' . $display_template : 'متن ' . $number . ' — ' . $display_template;
            $html .= '<option value="' . $variant . '" data-template="' . esc_attr( $template ) . '"' . selected( (string) $selected, (string) $variant, false ) . '>' . esc_html( $option_label ) . '</option>';
        }
        $html .= '</select>';
        $preview = isset( $templates[ (int) $selected ] ) ? (string) $templates[ (int) $selected ] : '';
        $preview_text = $preview !== '' ? $preview : $none_label;
        $html .= '<div class="ty-copy-template-preview" id="' . esc_attr( $preview_id ) . '" aria-live="polite">' . esc_html( $preview_text ) . '</div>';
        return $html;
    }

    private function post_copy_fields( $seo_type ) {
        $fa_default = TaranehYar_SEO::default_intro_variant( $seo_type, 'fa' );
        $en_default = TaranehYar_SEO::default_intro_variant( $seo_type, 'en' );

        $this->card_open( '✍️ انتخاب متن فارسی و انگلیسی پست' );
        echo '<p class="ty-hint">متن‌ها را یک‌بار در تنظیمات ← سئو، GEO و AIO می‌نویسید. در هر نوشته فقط الگو را انتخاب کنید؛ نام خواننده و اثر خودکار جایگزین می‌شوند. کیفیت‌ها در باکس مشخصات اثر جداگانه نمایش داده می‌شوند تا متن معرفی تکراری نشود.</p>';
        echo '<div class="ty-grid ty-grid-2">';
        $this->field( 'متن فارسی نوشته', $this->copy_template_select( $seo_type, 'fa', 'post_text_fa', $fa_default ) );
        $this->field( 'English post text', $this->copy_template_select( $seo_type, 'en', 'post_text_en', $en_default ) );
        echo '</div>';
        $this->card_close();
    }

    private function submit_btn( $label = 'ارسال پست' ) {
        echo '<div class="ty-submit-row"><button type="submit" class="ty-btn ty-btn-primary">🚀 ' . esc_html($label) . '</button></div>';
    }

    private function form_open( $action_val ) {
        $url = admin_url('admin-post.php');
        echo '<form enctype="multipart/form-data" method="POST" action="' . esc_url($url) . '">';
        echo '<input type="hidden" name="action" value="' . esc_attr($action_val) . '">';
        wp_nonce_field( $action_val );
    }

    /* Render only the production-credit fields enabled in settings. */
    private function credit_fields() {
        $enabled_fields = array();
        foreach ( TaranehYar_Settings::CREDIT_FIELDS as $role => $credit_field ) {
            if ( TaranehYar_Settings::credit_field_enabled( $role ) ) {
                $enabled_fields[ $role ] = $credit_field;
            }
        }
        if ( empty( $enabled_fields ) ) return;

        echo '<div class="ty-grid ty-grid-3" style="margin-top:14px">';
        foreach ( $enabled_fields as $credit_field ) {
            $this->field( $credit_field['label'], $this->input( $credit_field['post_key'], $credit_field['label'] ) );
        }
        echo '</div>';
    }

    /* ═══════════════════════════════════════════════
       SHARED MUSIC FORM BODY (تک آهنگ / ریمیکس / نوحه)
    ═══════════════════════════════════════════════ */

    private function music_form_body( $seo_type = 'music' ) {

        $this->card_open('⚙️ تنظیمات پست');
        echo '<div class="ty-grid ty-grid-2">';
        $this->field( 'وضعیت انتشار', $this->select_draft() );
        echo '<p class="ty-hint">عنوان نوشته، عنوان SEO و توضیحات متا از الگوی اختصاصی این نوع محتوا در صفحهٔ تنظیمات ساخته می‌شوند.</p>';
        echo '</div>';
        $this->card_close();
        $this->post_copy_fields( $seo_type );

        $this->card_open('🎤 اطلاعات اثر');
        echo '<div class="ty-grid ty-grid-2">';
        $this->field( 'نام خواننده', $this->input('name','نام خواننده','text',true), true );
        $this->field( 'نام اثر', $this->input('track','نام اثر','text',true), true );
        $this->field( 'Artist (EN)', $this->input('enname','Artist Name','text',true), true );
        $this->field( 'Track (EN)', $this->input('entrack','Track Name','text',true), true );
        echo '</div>';
        echo '<div class="ty-grid" style="margin-top:14px">';
        $this->field( 'اطلاعات پست', $this->input('info','توضیحات کوتاه درباره اثر') );
        echo '</div>';
        $this->credit_fields();
        $this->card_close();

        $this->card_open('🖼️ کاور');
        echo '<div class="ty-grid ty-grid-2">';
        echo '<div>';
        $this->field( 'آپلود مستقیم', $this->file_upload('filecover','انتخاب تصویر کاور') );
        echo '<div class="ty-or">یا</div>';
        $this->field( 'لینک کاور پست', $this->input('cover','https://...','url') );
        echo '</div>';
        echo '<div>';
        $this->field( 'لینک کاور فایل MP3', $this->input('cover_mp3','https://...','url') );
        echo '</div>';
        echo '</div>';
        $this->card_close();

        $this->card_open('🎧 فایل‌های صوتی');
        echo '<div class="ty-grid ty-grid-2">';
        echo '<div>';
        $this->field( 'آپلود فایل ۳۲۰', $this->file_upload('filelink320','انتخاب فایل MP3 کیفیت ۳۲۰') );
        echo '<div class="ty-or">یا</div>';
        $this->field( 'لینک ۳۲۰', $this->input('link320','https://...','url') );
        echo '</div>';
        echo '<div>';
        $this->field( 'آپلود فایل ۱۲۸', $this->file_upload('filelink128','انتخاب فایل MP3 کیفیت ۱۲۸') );
        echo '<div class="ty-or">یا</div>';
        $this->field( 'لینک ۱۲۸', $this->input('link128','https://...','url') );
        echo '</div>';
        echo '</div>';
        $this->card_close();

        $this->card_open('📝 متن آهنگ');
        $this->field( 'متن ترانه', '<textarea name="lyric" placeholder="متن آهنگ را اینجا وارد کنید..."></textarea>' );
        echo '<p class="ty-hint">متن ترانه فقط در متای قالب (<code>music_txt</code>) ذخیره می‌شود و به متن اصلی نوشته اضافه نمی‌شود؛ این کار از نمایش دوبارهٔ ترانه جلوگیری می‌کند.</p>';
        $this->card_close();
    }

    /* ═══════════════════════════════════════════════
       PAGES
    ═══════════════════════════════════════════════ */

    public function page_music() {
        echo '<div class="ty-wrap" data-theme="light">';
        $this->header('🎵','پنل ارسال تک آهنگ','ارسال آهنگ جدید به سایت با تمام جزئیات');
        $this->nav_tabs('ty-dashboard');
        $this->notice();
        $this->form_open('ty_submit_music');
        $this->music_form_body( 'music' );
        $this->submit_btn('ارسال تک آهنگ');
        echo '</form></div>';
    }

    public function page_remix() {
        echo '<div class="ty-wrap" data-theme="light">';
        $this->header('🎛️','پنل ارسال ریمیکس','آپلود و ارسال ریمیکس به سایت');
        $this->nav_tabs('ty-remix');
        $this->notice();
        $this->form_open('ty_submit_remix');
        $this->music_form_body( 'remix' );
        $this->submit_btn('ارسال ریمیکس');
        echo '</form></div>';
    }

    public function page_nohe() {
        echo '<div class="ty-wrap" data-theme="light">';
        $this->header('🕌','پنل ارسال نوحه','آپلود و ارسال نوحه به سایت');
        $this->nav_tabs('ty-nohe');
        $this->notice();
        $this->form_open('ty_submit_nohe');
        $this->music_form_body( 'nohe' );
        $this->submit_btn('ارسال نوحه');
        echo '</form></div>';
    }

    public function page_video() {
        echo '<div class="ty-wrap" data-theme="light">';
        $this->header('🎬','پنل ارسال موزیک ویدیو','آپلود و انتشار موزیک ویدیو');
        $this->nav_tabs('ty-video');
        $this->notice();
        $this->form_open('ty_submit_video');

        $this->card_open('⚙️ تنظیمات پست');
        echo '<div class="ty-grid ty-grid-2">';
        $this->field( 'وضعیت انتشار', $this->select_draft() );
        echo '<p class="ty-hint">عنوان و توضیحات موزیک‌ویدیو از الگوی اختصاصی و قابل‌ویرایش تنظیمات ساخته می‌شوند.</p>';
        echo '</div>';
        $this->card_close();
        $this->post_copy_fields( 'video' );

        $this->card_open('🎤 اطلاعات اثر');
        echo '<div class="ty-grid ty-grid-2">';
        $this->field( 'نام خواننده', $this->input('name','نام خواننده','text',true), true );
        $this->field( 'نام اثر', $this->input('track','نام اثر','text',true), true );
        $this->field( 'Artist (EN)', $this->input('enname','Artist','text',true), true );
        $this->field( 'Track (EN)', $this->input('entrack','Track Name','text',true), true );
        echo '</div>';
        echo '<div class="ty-grid" style="margin-top:14px">';
        $this->field( 'اطلاعات پست', $this->input('info','توضیحات') );
        echo '</div>';
        $this->credit_fields();
        $this->card_close();

        $this->card_open('🖼️ کاور');
        echo '<div class="ty-grid ty-grid-2">';
        echo '<div>';
        $this->field( 'آپلود کاور', $this->file_upload('filecover') );
        echo '<div class="ty-or">یا</div>';
        $this->field( 'لینک کاور', $this->input('cover','https://...','url') );
        echo '</div>';
        echo '<div></div>';
        echo '</div>';
        $this->card_close();

        $this->card_open('🎬 فایل‌های ویدیو');
        echo '<div class="ty-grid ty-grid-2">';
        $this->field( 'لینک MP4 کیفیت ۱۰۸۰p', $this->input('link1080','https://...','url') );
        $this->field( 'لینک MP4 کیفیت ۷۲۰p', $this->input('link720','https://...','url') );
        $this->field( 'لینک MP4 کیفیت ۴۸۰p', $this->input('link480','https://...','url') );
        $this->field( 'لینک MP3 صوتی', $this->input('link320','https://...','url') );
        echo '</div>';
        $this->card_close();

        $this->submit_btn('ارسال موزیک ویدیو');
        echo '</form></div>';
    }

    public function page_album() {
        echo '<div class="ty-wrap" data-theme="light">';
        $this->header('💿','پنل ارسال آلبوم','ارسال آلبوم با لیست کامل ترک‌ها');
        $this->nav_tabs('ty-album');
        $this->notice();
        $this->form_open('ty_submit_album');

        $this->card_open('⚙️ تنظیمات پست');
        echo '<div class="ty-grid ty-grid-3">';
        $this->field( 'وضعیت', $this->select_draft() );
        $this->field( 'نام هنرمند', $this->input('artist','نام هنرمند','text',true), true );
        $this->field( 'نام آلبوم', $this->input('song','نام آلبوم','text',true), true );
        echo '</div>';
        echo '<div class="ty-grid ty-grid-3" style="margin-top:14px">';
        $this->field( 'Artist (EN)', $this->input('artist_en','Artist','text',true), true );
        $this->field( 'Album (EN)', $this->input('song_en','Album Name','text',true), true );
        $this->field( 'لینک کاور MP3', $this->input('cover_mp3','https://...','url') );
        echo '</div>';
        $this->card_close();
        $this->post_copy_fields( 'album' );

        $this->card_open('🖼️ کاور آلبوم');
        echo '<div class="ty-grid ty-grid-2">';
        echo '<div>';
        $this->field( 'اطلاعات آلبوم', $this->input('info','توضیحات') );
        echo '</div>';
        echo '<div>';
        $this->field( 'لینک کاور', $this->input('cover','https://...','url') );
        echo '</div>';
        echo '</div>';
        $this->card_close();

        $this->card_open('🎵 لیست ترک‌ها');
        echo '<div style="display:grid;gap:8px;">';
        echo '<div class="ty-track-row" style="background:rgba(108,99,255,0.08);border-color:rgba(108,99,255,0.2);">';
        echo '<span class="ty-track-num" style="color:#6C63FF;font-size:10px;">#</span>';
        echo '<span style="font-size:12px;color:#8B8BA7;font-weight:600;">نام ترک</span>';
        echo '<span style="font-size:12px;color:#8B8BA7;font-weight:600;">لینک ۳۲۰</span>';
        echo '<span style="font-size:12px;color:#8B8BA7;font-weight:600;">لینک ۱۲۸</span>';
        echo '</div>';
        for ( $i = 1; $i <= 15; $i++ ) {
            echo '<div class="ty-track-row">';
            echo '<span class="ty-track-num">' . $i . '</span>';
            echo '<input type="text" name="track_name_' . $i . '" placeholder="نام ترک ' . $i . '">';
            echo '<input type="url" name="track_link320_' . $i . '" placeholder="https://... (320)">';
            echo '<input type="url" name="track_link128_' . $i . '" placeholder="https://... (128)">';
            echo '</div>';
        }
        echo '</div>';
        $this->card_close();

        $this->submit_btn('ارسال آلبوم');
        echo '</form></div>';
    }

    private function transfer_action_form( $action, $label, $fields = array(), $class = 'ty-btn ty-btn-small', $confirm = '' ) {
        $html = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="ty-transfer-action"';
        if ( $confirm !== '' ) $html .= ' data-confirm="' . esc_attr( $confirm ) . '"';
        $html .= '><input type="hidden" name="action" value="' . esc_attr( $action ) . '">';
        ob_start();
        wp_nonce_field( $action );
        $nonce = ob_get_clean();
        $html .= $nonce;
        foreach ( $fields as $name => $value ) {
            $html .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
        }
        $html .= '<button type="submit" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';
        return $html;
    }

    private function verify_transfer_request( $action ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'دسترسی غیرمجاز' );
        }
        check_admin_referer( $action );
    }

    private function transfer_redirect( $message, $is_error = false ) {
        $args = array( 'page' => 'ty-transfers', 'ty_msg' => $message );
        if ( $is_error ) $args['ty_err'] = '1';
        wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_transfer_run() {
        $this->verify_transfer_request( 'ty_transfer_run' );
        if ( TaranehYar_Queue::schedule_now() ) {
            $this->transfer_redirect( 'اجرای صف برای همین لحظه زمان‌بندی شد؛ نتیجه را در همین صفحه پیگیری کنید.' );
        }
        $this->transfer_redirect( 'صف انتقال خالی است.', true );
    }

    public function handle_transfer_retry() {
        $this->verify_transfer_request( 'ty_transfer_retry' );
        $id = sanitize_text_field( wp_unslash( $_POST['job_id'] ?? '' ) );
        if ( TaranehYar_Queue::retry_failed( $id ) ) {
            $this->transfer_redirect( 'انتقال ناموفق دوباره در صف قرار گرفت.' );
        }
        $this->transfer_redirect( 'این مورد دیگر در فهرست خطاهای قابل‌تلاش مجدد نیست.', true );
    }

    public function handle_transfer_discard_failed() {
        $this->verify_transfer_request( 'ty_transfer_discard_failed' );
        $id = sanitize_text_field( wp_unslash( $_POST['job_id'] ?? '' ) );
        if ( TaranehYar_Queue::discard_failed( $id ) ) {
            $this->transfer_redirect( 'مورد ناموفق و فایل موقت آن حذف شد.' );
        }
        $this->transfer_redirect( 'مورد خطا پیدا نشد.', true );
    }

    public function handle_transfer_discard_queue() {
        $this->verify_transfer_request( 'ty_transfer_discard_queue' );
        $count = TaranehYar_Queue::discard_pending();
        $this->transfer_redirect( sprintf( '%d مورد در انتظار لغو شد.', $count ) );
    }

    public function handle_transfer_clear_history() {
        $this->verify_transfer_request( 'ty_transfer_clear_history' );
        $count = TaranehYar_Queue::clear_history();
        $this->transfer_redirect( sprintf( 'گزارش‌ها پاک شد؛ %d مورد خطا و فایل موقت قابل‌بازیابی آن حذف شد. صف در انتظار تغییری نکرد.', $count ) );
    }

    public function page_transfers() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'دسترسی غیرمجاز' );
        $queue = TaranehYar_Queue::get_queue();
        $failed = TaranehYar_Queue::get_failed( 20 );
        $history = TaranehYar_Queue::get_log( 100 );
        $success_count = 0;
        foreach ( $history as $entry ) {
            if ( isset( $entry['status'] ) && $entry['status'] === 'success' ) $success_count++;
        }
        $next_run = function_exists( 'wp_next_scheduled' ) ? wp_next_scheduled( 'ty_process_queue' ) : false;

        echo '<div class="ty-wrap" data-theme="light">';
        $this->header( '📡', 'وضعیت انتقال‌ها به هاست دانلود', 'مدیریت صف، تلاش مجدد، لغو موارد در انتظار و بررسی گزارش‌ها' );
        $this->nav_tabs( 'ty-transfers' );
        $this->notice();

        echo '<div class="ty-transfer-stats">';
        echo '<div class="ty-transfer-stat"><strong>' . esc_html( count( $queue ) ) . '</strong><span>در انتظار انتقال</span></div>';
        echo '<div class="ty-transfer-stat ty-transfer-stat-error"><strong>' . esc_html( count( $failed ) ) . '</strong><span>قابل تلاش مجدد</span></div>';
        echo '<div class="ty-transfer-stat ty-transfer-stat-success"><strong>' . esc_html( $success_count ) . '</strong><span>موفق در گزارش اخیر</span></div>';
        echo '<div class="ty-transfer-stat"><strong>' . ( $next_run ? esc_html( wp_date( 'Y/m/d H:i:s', (int) $next_run ) ) : '—' ) . '</strong><span>اجرای زمان‌بندی‌شدهٔ بعدی</span></div>';
        echo '</div>';

        $this->card_open( '🕹️ کنترل صف انتقال' );
        echo '<div class="ty-transfer-controls">';
        echo $this->transfer_action_form( 'ty_transfer_run', '▶ اجرای صف اکنون', array(), 'ty-btn ty-btn-primary' );
        if ( ! empty( $queue ) ) {
            echo $this->transfer_action_form( 'ty_transfer_discard_queue', 'لغو همهٔ موارد در انتظار', array(), 'ty-btn ty-btn-danger', 'همهٔ موارد در انتظار لغو و فایل‌های موقت مربوط حذف شوند؟' );
        }
        if ( ! empty( $history ) || ! empty( $failed ) ) {
            echo $this->transfer_action_form( 'ty_transfer_clear_history', 'پاک‌کردن تاریخچه و خطاها', array(), 'ty-btn ty-btn-secondary', 'گزارش‌ها و موارد خطای قابل‌بازیابی پاک شوند؟ صف در انتظار باقی می‌ماند.' );
        }
        echo '</div>';
        echo '<p class="ty-hint">پردازش پس‌زمینه به WP-Cron وابسته است. اگر صف اجرا نشد، دکمهٔ «اجرای صف اکنون» را بزنید و مطمئن شوید Cron وردپرس روی هاست فعال است. فایل‌های موقت انتقال ناموفق تا زمان تلاش مجدد یا حذف مورد نگه داشته می‌شوند.</p>';
        $this->card_close();

        $this->card_open( '⏳ صف در انتظار (' . count( $queue ) . ')' );
        if ( empty( $queue ) ) {
            echo '<p class="ty-hint">در حال حاضر کاری در صف نیست.</p>';
        } else {
            echo '<div class="ty-table-scroll"><table class="ty-transfer-table"><thead><tr><th>نوع</th><th>عنوان</th><th>مسیر آرشیو</th><th>ثبت</th><th>وضعیت</th></tr></thead><tbody>';
            foreach ( $queue as $item ) {
                $created = isset( $item['created'] ) ? wp_date( 'Y/m/d H:i', (int) $item['created'] ) : '—';
                $path = isset( $item['archive_path'] ) && $item['archive_path'] !== '' ? $item['archive_path'] : 'مسیر قدیمی/تاریخ‌محور';
                echo '<tr><td>' . esc_html( isset( $item['type'] ) ? $item['type'] : '' ) . '</td><td>' . esc_html( isset( $item['title'] ) ? $item['title'] : '' ) . '</td><td><code dir="ltr">' . esc_html( $path ) . '</code></td><td>' . esc_html( $created ) . '</td><td><span class="ty-queue-pill ty-queue-queued">در صف</span></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        $this->card_close();

        $this->card_open( '⚠️ انتقال‌های ناموفق و قابل‌بازیابی (' . count( $failed ) . ')' );
        if ( empty( $failed ) ) {
            echo '<p class="ty-hint">مورد ناموفقی برای تلاش مجدد وجود ندارد.</p>';
        } else {
            echo '<div class="ty-table-scroll"><table class="ty-transfer-table"><thead><tr><th>نوع</th><th>عنوان</th><th>پیام خطا</th><th>زمان خطا</th><th>عملیات</th></tr></thead><tbody>';
            foreach ( $failed as $item ) {
                $failed_at = isset( $item['failed_at'] ) ? wp_date( 'Y/m/d H:i', (int) $item['failed_at'] ) : '—';
                $message = isset( $item['last_error'] ) ? $item['last_error'] : 'انتقال ناموفق بود.';
                $id = isset( $item['id'] ) ? (string) $item['id'] : '';
                echo '<tr><td>' . esc_html( isset( $item['type'] ) ? $item['type'] : '' ) . '</td><td>' . esc_html( isset( $item['title'] ) ? $item['title'] : '' ) . '</td><td class="ty-transfer-error-message">' . esc_html( $message ) . '</td><td>' . esc_html( $failed_at ) . '</td><td><div class="ty-transfer-row-actions">';
                echo $this->transfer_action_form( 'ty_transfer_retry', 'تلاش مجدد', array( 'job_id' => $id ), 'ty-btn ty-btn-small' );
                echo $this->transfer_action_form( 'ty_transfer_discard_failed', 'حذف', array( 'job_id' => $id ), 'ty-btn ty-btn-small ty-btn-danger', 'این مورد خطا و فایل‌های موقت نگه‌داشته‌شده حذف شوند؟' );
                echo '</div></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        $this->card_close();

        $this->card_open( '🧾 گزارش آخرین انتقال‌ها (' . count( $history ) . ')' );
        if ( empty( $history ) ) {
            echo '<p class="ty-hint">گزارشی ثبت نشده است.</p>';
        } else {
            echo '<div class="ty-table-scroll"><table class="ty-transfer-table"><thead><tr><th>وضعیت</th><th>نوع</th><th>عنوان</th><th>جزئیات</th><th>زمان</th></tr></thead><tbody>';
            foreach ( $history as $entry ) {
                $status = isset( $entry['status'] ) ? $entry['status'] : '';
                $time = isset( $entry['time'] ) ? wp_date( 'Y/m/d H:i:s', (int) $entry['time'] ) : '—';
                echo '<tr><td><span class="ty-queue-pill ty-queue-' . esc_attr( $status ) . '">' . esc_html( TaranehYar_Queue::status_label( $status ) ) . '</span></td><td>' . esc_html( isset( $entry['type'] ) ? $entry['type'] : '' ) . '</td><td>' . esc_html( isset( $entry['title'] ) ? $entry['title'] : '' ) . '</td><td class="ty-transfer-error-message">' . esc_html( isset( $entry['message'] ) ? $entry['message'] : '' ) . '</td><td>' . esc_html( $time ) . '</td></tr>';
            }
            echo '</tbody></table></div>';
        }
        $this->card_close();
        echo '</div>';
    }

    public function page_leech() {
        echo '<div class="ty-wrap" data-theme="light">';
        $this->header('⬇️','پنل لیچ فایل','انتقال فایل از سرور به هاست دانلود');
        $this->nav_tabs('ty-leech');
        $this->notice();
        $this->form_open('ty_submit_leech');

        $this->card_open('🎤 اطلاعات فایل');
        echo '<div class="ty-grid ty-grid-2">';
        $this->field( 'Artist (EN)', $this->input('enname','Artist','text',true), true );
        $this->field( 'Track (EN)', $this->input('entrack','Track','text',true), true );
        echo '</div>';
        $this->card_close();

        $this->card_open('🖼️ کاور');
        echo '<div class="ty-grid ty-grid-2">';
        echo '<div>';
        $this->field( 'آپلود کاور', $this->file_upload('filecover') );
        echo '</div>';
        echo '<div>';
        $this->field( 'لینک کاور', $this->input('cover','https://...','url') );
        echo '</div>';
        echo '</div>';
        $this->card_close();

        $this->card_open('📁 فایل‌ها');
        echo '<div class="ty-grid ty-grid-2">';
        echo '<div>';
        $this->field( 'آپلود MP3', $this->file_upload('filemp3','انتخاب فایل MP3') );
        echo '<div class="ty-or">یا</div>';
        $this->field( 'لینک MP3', $this->input('linkfilemp3','https://...','url') );
        echo '</div>';
        echo '<div>';
        $this->field( 'آپلود MP4', $this->file_upload('filemp4','انتخاب فایل MP4') );
        echo '<div class="ty-or">یا</div>';
        $this->field( 'لینک MP4', $this->input('linkfilemp4','https://...','url') );
        echo '</div>';
        echo '</div>';
        $this->card_close();

        $this->submit_btn('شروع لیچ');
        echo '</form></div>';
    }
}

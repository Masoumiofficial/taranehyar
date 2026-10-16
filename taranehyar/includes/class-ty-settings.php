<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TaranehYar_Settings {

    const META_KEY_DEFAULTS = array(
        'musics_type'    => 'musics_type',
        'artist_en'      => 'artist_en',
        'song_en'        => 'song_en',
        'art_name'       => 'art_name',
        'track_name'     => 'track_name',
        'music_txt'      => 'music_txt',
        'music128'       => 'music128',
        'music320'       => 'music320',
        'album'          => 'album',
        'album128'       => 'album128',
        'album320'       => 'album320',
        'video480p'      => 'video480p',
        'video720p'      => 'video720p',
        'video1080p'     => 'video1080p',
        'fifu_image_alt' => 'fifu_image_alt',
    );

    const ALL_KEYS = array(
        'urldlhost','urlfunction_music','urlfunction_remix','urlfunction_video',
        'urlfunction_album','urlfunction_nohe','urlfunction_leech',
        'apitoken',
        'namesite','namesite2','urlsite',
        'cat_music','cat_remix','cat_nohe','cat_video','cat_album',
        'singer_taxonomy',
        'meta_key_musics_type','meta_key_artist_en','meta_key_song_en',
        'meta_key_art_name','meta_key_track_name','meta_key_music_txt',
        'meta_key_music128','meta_key_music320','meta_key_album',
        'meta_key_album128','meta_key_album320','meta_key_video480p',
        'meta_key_video720p','meta_key_video1080p','meta_key_fifu_image_alt',
        'seo_title_tag_enabled','seo_meta_description_enabled','seo_jsonld_enabled','seo_category_links_enabled','seo_visible_details_enabled',
        'seo_album_tracklist_enabled',
        'seo_post_title_music','seo_title_music','seo_description_music','seo_intro_music','seo_focus_music',
        'seo_post_title_remix','seo_title_remix','seo_description_remix','seo_intro_remix','seo_focus_remix',
        'seo_post_title_nohe','seo_title_nohe','seo_description_nohe','seo_intro_nohe','seo_focus_nohe',
        'seo_post_title_video','seo_title_video','seo_description_video','seo_intro_video','seo_focus_video',
        'seo_post_title_album','seo_title_album','seo_description_album','seo_intro_album','seo_intro_en_album','seo_focus_album',
        'seo_intro_en_music','seo_intro_en_remix','seo_intro_en_nohe','seo_intro_en_video',
        'seo_intro_2_music','seo_intro_3_music','seo_intro_2_remix','seo_intro_3_remix',
        'seo_intro_2_nohe','seo_intro_3_nohe','seo_intro_2_video','seo_intro_3_video',
        'seo_intro_2_album','seo_intro_3_album',
        'seo_intro_en_2_music','seo_intro_en_3_music','seo_intro_en_2_remix','seo_intro_en_3_remix',
        'seo_intro_en_2_nohe','seo_intro_en_3_nohe','seo_intro_en_2_video','seo_intro_en_3_video',
        'seo_intro_en_2_album','seo_intro_en_3_album',
        'seo_copy_default_fa_music','seo_copy_default_en_music',
        'seo_copy_default_fa_remix','seo_copy_default_en_remix',
        'seo_copy_default_fa_nohe','seo_copy_default_en_nohe',
        'seo_copy_default_fa_video','seo_copy_default_en_video',
        'seo_copy_default_fa_album','seo_copy_default_en_album',
    );

    public function init() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
    }

    public function enqueue( $hook ) {
        if ( strpos( $hook, 'ty' ) === false ) return;
        wp_enqueue_style( 'ty-admin', TY_PLUGIN_URL . 'assets/css/admin.css', array(), TY_VERSION );
        wp_enqueue_script( 'ty-admin', TY_PLUGIN_URL . 'assets/js/admin.js', array(), TY_VERSION, true );
    }

    public function register_settings() {
        register_setting( 'ty_options_group', 'ty_options', array( $this, 'sanitize' ) );
    }

    public function sanitize( $input ) {
        $input = is_array( $input ) ? $input : array();
        $clean = array();
        foreach ( self::ALL_KEYS as $k ) {
            $value = isset( $input[ $k ] ) && is_scalar( $input[ $k ] ) ? (string) $input[ $k ] : '';
            if ( $k === 'apitoken' && $value === '' ) {
                $clean[ $k ] = self::get( 'apitoken' );
                continue;
            }
            if ( strpos( $k, 'seo_copy_default_' ) === 0 ) {
                $clean[ $k ] = in_array( $value, array( '0', '1', '2', '3' ), true ) ? $value : '1';
            } elseif ( in_array( $k, array( 'seo_title_tag_enabled', 'seo_meta_description_enabled', 'seo_jsonld_enabled', 'seo_category_links_enabled', 'seo_visible_details_enabled', 'seo_album_tracklist_enabled' ), true ) ) {
                $clean[ $k ] = $value === '1' ? '1' : '0';
            } elseif ( $k === 'singer_taxonomy' || strpos( $k, 'meta_key_' ) === 0 ) {
                $clean[ $k ] = sanitize_key( $value );
            } elseif ( strpos( $k, 'seo_intro_' ) === 0 ) {
                $clean[ $k ] = sanitize_textarea_field( $value );
            } else {
                $clean[ $k ] = sanitize_text_field( $value );
            }
        }
        return $clean;
    }

    private function row( $opts, $key, $label, $placeholder = '', $default_value = '' ) {
        $value = isset( $opts[ $key ] ) && $opts[ $key ] !== '' ? $opts[ $key ] : $default_value;
        $id = 'ty-setting-' . $key;
        $type = $key === 'apitoken' ? 'password' : 'text';
        $class = strpos( $key, 'seo_' ) === 0 ? 'ty-seo-template' : 'ty-setting-input' . ( $key === 'namesite' ? ' ty-setting-rtl' : ' ty-setting-ltr' );
        $autocomplete = $key === 'apitoken' ? ' autocomplete="new-password"' : '';
        if ( $key === 'apitoken' ) {
            $value = '';
            $placeholder = 'برای حفظ توکن فعلی خالی بگذارید؛ برای تعویض، توکن جدید وارد کنید';
        }
        echo '<tr><th><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td><input id="' . esc_attr( $id ) . '" type="' . esc_attr( $type ) . '" class="' . esc_attr( $class ) . '" name="ty_options[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '"' . $autocomplete . '></td></tr>';
    }

    private function textarea_row( $opts, $key, $label, $placeholder = '', $default_value = '' ) {
        $value = array_key_exists( $key, $opts ) ? $opts[ $key ] : $default_value;
        $language_class = strpos( $key, 'seo_intro_en_' ) === 0 ? ' ty-seo-template-en' : '';
        $direction = $language_class !== '' ? 'ltr' : 'rtl';
        echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><textarea class="ty-seo-template' . $language_class . '" id="' . esc_attr( $key ) . '" dir="' . esc_attr( $direction ) . '" name="ty_options[' . esc_attr( $key ) . ']" rows="3" placeholder="' . esc_attr( $placeholder ) . '">' . esc_textarea( $value ) . '</textarea></td></tr>';
    }

    private function select_row( $opts, $key, $label, array $choices, $default = '1' ) {
        $value = isset( $opts[ $key ] ) ? (string) $opts[ $key ] : (string) $default;
        if ( ! array_key_exists( $value, $choices ) ) $value = (string) $default;
        echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><select class="ty-setting-select" id="' . esc_attr( $key ) . '" name="ty_options[' . esc_attr( $key ) . ']">';
        foreach ( $choices as $choice_value => $choice_label ) {
            echo '<option value="' . esc_attr( $choice_value ) . '"' . selected( $value, (string) $choice_value, false ) . '>' . esc_html( $choice_label ) . '</option>';
        }
        echo '</select></td></tr>';
    }

    private function copy_template_settings( $opts, $type ) {
        $choices = array( '0' => 'بدون متن' );
        foreach ( array( 1, 2, 3 ) as $variant ) {
            $choices[ (string) $variant ] = 'متن ' . array( 1 => '۱', 2 => '۲', 3 => '۳' )[ $variant ];
        }

        echo '<div class="ty-copy-template-settings">';
        echo '<p class="ty-hint">قالب‌ها را یک‌بار اینجا تنظیم کنید؛ نام خواننده و اثر هنگام ساخت پست خودکار جایگزین می‌شوند. کیفیت فایل‌ها در باکس مشخصات اثر نمایش داده می‌شود و در متن معرفی تکرار نمی‌شود.</p>';
        foreach ( array( 'fa' => 'فارسی', 'en' => 'English' ) as $language => $language_label ) {
            echo '<section class="ty-copy-template-language" lang="' . esc_attr( $language === 'en' ? 'en' : 'fa' ) . '">';
            echo '<h3>' . esc_html( 'قالب‌های متن ' . $language_label ) . '</h3>';
            $default_key = 'seo_copy_default_' . $language . '_' . $type;
            $this->select_row( $opts, $default_key, 'متن پیش‌فرض فرم', $choices, '1' );
            echo '<table class="ty-settings-table ty-copy-template-table">';
            foreach ( array( 1, 2, 3 ) as $variant ) {
                $key = TaranehYar_SEO::intro_option_key( $type, $language, $variant );
                $default = TaranehYar_SEO::default_intro_template( $type, $language, $variant );
                $number = array( 1 => '۱', 2 => '۲', 3 => '۳' )[ $variant ];
                $label = ( $language === 'en' ? 'Text ' : 'متن ' ) . $number;
                $this->textarea_row( $opts, $key, $label, $default, $default );
            }
            echo '</table></section>';
        }
        echo '</div>';
    }

    private function checkbox_row( $opts, $key, $label, $default = '1' ) {
        $value = isset( $opts[ $key ] ) ? (string) $opts[ $key ] : $default;
        echo '<tr><th>' . esc_html( $label ) . '</th><td><input type="hidden" name="ty_options[' . esc_attr( $key ) . ']" value="0"><label><input type="checkbox" name="ty_options[' . esc_attr( $key ) . ']" value="1" ' . checked( $value, '1', false ) . '> فعال</label></td></tr>';
    }

    public function render_settings_page() {
        $opts = get_option( 'ty_options', array() );
        $opts = is_array( $opts ) ? $opts : array();
        ?>
        <div class="ty-wrap" dir="rtl" data-theme="light">
            <div class="ty-header">
                <div class="ty-header-icon">⚙️</div>
                <div class="ty-header-text"><h1>تنظیمات افزونه</h1><p>اتصال هاست، دسته‌بندی‌ها، متادیتای قالب و الگوهای محتوای پست</p></div>
                <button type="button" class="ty-theme-toggle" id="ty-theme-toggle" title="تغییر حالت روز/شب" aria-label="تغییر حالت روز/شب">
                    <span class="ty-theme-icon-light">☀️</span>
                    <span class="ty-theme-icon-dark">🌙</span>
                </button>
            </div>

            <?php
            $tabs = array(
                'ty-dashboard' => '🎵 تک‌آهنگ', 'ty-remix' => '🎛️ ریمیکس', 'ty-nohe' => '🕌 نوحه',
                'ty-video' => '🎬 موزیک‌ویدیو', 'ty-album' => '💿 آلبوم', 'ty-leech' => '⬇️ لیچ',
            );
            if ( current_user_can( 'manage_options' ) ) {
                $tabs['ty-transfers'] = '📡 وضعیت انتقال‌ها';
                $tabs['ty-settings'] = '⚙️ تنظیمات';
            }
            echo '<div class="ty-tabs">';
            foreach ( $tabs as $slug => $label ) {
                $cls = ( $slug === 'ty-settings' ) ? 'ty-tab active' : 'ty-tab';
                echo '<a href="' . esc_url( admin_url( 'admin.php?page=' . $slug ) ) . '" class="' . $cls . '"><span class="tab-dot"></span>' . esc_html( $label ) . '</a>';
            }
            echo '</div>';

            if ( isset( $_GET['settings-updated'] ) ) {
                echo '<div class="ty-notice success">✅ تنظیمات با موفقیت ذخیره شد.</div>';
            }
            ?>

            <nav class="ty-settings-shortcuts" aria-label="بخش‌های تنظیمات">
                <a href="#ty-settings-host">اتصال هاست</a>
                <a href="#ty-settings-site">اطلاعات سایت</a>
                <a href="#ty-settings-categories">دسته‌بندی‌ها</a>
                <a href="#ty-settings-meta">متادیتای قالب</a>
                <a href="#ty-settings-seo">SEO / GEO / AIO و متن پست</a>
            </nav>
            <div class="ty-settings-tools" aria-label="نمایش بخش‌ها">
                <span>بخش‌ها را برای دیدن تنظیمات باز کنید:</span>
                <button type="button" class="ty-settings-tool" data-ty-settings-expand>بازکردن همه</button>
                <button type="button" class="ty-settings-tool" data-ty-settings-collapse>بستن همه</button>
            </div>

            <form method="post" action="options.php" class="ty-settings-form">
                <?php settings_fields( 'ty_options_group' ); ?>

                <details class="ty-settings-group" id="ty-settings-host" open>
                    <summary><span>🌐 اتصال هاست دانلود</span><small>دامنه، endpointها و توکن</small></summary>
                    <div class="ty-settings-group-body">
                        <table class="ty-settings-table">
                            <?php
                            $this->row( $opts, 'urldlhost', 'آدرس پایهٔ هاست دانلود', 'https://dl.example.ir' );
                            $this->row( $opts, 'urlfunction_music', 'Endpoint تک‌آهنگ (curl-music.php)', 'https://dl.example.ir/curl-music.php' );
                            $this->row( $opts, 'urlfunction_remix', 'Endpoint ریمیکس (curl-music.php)', 'https://dl.example.ir/curl-music.php' );
                            $this->row( $opts, 'urlfunction_nohe', 'Endpoint نوحه/مداحی (curl-music.php)', 'https://dl.example.ir/curl-music.php' );
                            $this->row( $opts, 'urlfunction_video', 'Endpoint موزیک‌ویدیو (curl-video.php)', 'https://dl.example.ir/curl-video.php' );
                            $this->row( $opts, 'urlfunction_album', 'Endpoint آلبوم (curl-album.php)', 'https://dl.example.ir/curl-album.php' );
                            $this->row( $opts, 'urlfunction_leech', 'Endpoint لیچ (curl-leech.php)', 'https://dl.example.ir/curl-leech.php' );
                            $this->row( $opts, 'apitoken', 'توکن اتصال به HostDL', 'برای حفظ توکن فعلی، خالی بگذارید' );
                            ?>
                        </table>
                        <p class="ty-hint">ریمیکس و نوحه با پارامتر امن <code>archive_root</code> به پوشه‌های مستقل <code>remix/{letter}/{artist}</code> و <code>nohe/{letter}/{artist}</code> می‌روند؛ تک‌آهنگ همچنان در <code>music/{letter}/{artist}</code> ذخیره می‌شود. برای فعال‌شدن مسیرهای جدید، بستهٔ HostDL نسخهٔ جدید را نیز روی هاست دانلود به‌روزرسانی کنید. درخواست‌های قدیمی همچنان مسیر تاریخ‌محور خود را حفظ می‌کنند.</p>
                        <p class="ty-hint">مقدار توکن در این صفحه نمایش داده نمی‌شود. توکن افزونه باید با <code>$smp_token</code> در <code>config.php</code> هاست دانلود یکسان باشد؛ خالی‌گذاشتن این فیلد، توکن ذخیره‌شده را حفظ می‌کند.</p>
                    </div>
                </details>

                <details class="ty-settings-group" id="ty-settings-site">
                    <summary><span>🏷️ اطلاعات سایت</span><small>نام و دامنه برای متن‌ها و برچسب‌گذاری فایل‌ها</small></summary>
                    <div class="ty-settings-group-body">
                        <table class="ty-settings-table">
                            <?php
                            $this->row( $opts, 'namesite', 'نام سایت (فارسی)' );
                            $this->row( $opts, 'namesite2', 'نام سایت (انگلیسی)' );
                            $this->row( $opts, 'urlsite', 'دامنهٔ سایت (بدون https://)', 'example.ir' );
                            ?>
                        </table>
                    </div>
                </details>

                <details class="ty-settings-group" id="ty-settings-categories">
                    <summary><span>📁 دسته‌بندی‌ها</span><small>شناسه‌های WordPress، از والد به دستهٔ اختصاصی</small></summary>
                    <div class="ty-settings-group-body">
                        <table class="ty-settings-table">
                            <?php
                            $this->row( $opts, 'cat_music', 'دسته‌بندی تک‌آهنگ', 'مثال: 10,12' );
                            $this->row( $opts, 'cat_remix', 'دسته‌بندی ریمیکس', 'مثال: 10,13' );
                            $this->row( $opts, 'cat_nohe', 'دسته‌بندی نوحه/مداحی', 'مثال: 10,14' );
                            $this->row( $opts, 'cat_video', 'دسته‌بندی موزیک‌ویدیو', 'مثال: 10,15' );
                            $this->row( $opts, 'cat_album', 'دسته‌بندی آلبوم', 'مثال: 5431,2' );
                            ?>
                        </table>
                        <p class="ty-hint">شناسه‌ها را از «نوشته‌ها ← دسته‌ها» بردارید؛ چند شناسه را با ویرگول جدا کنید. آخرین شناسه به‌عنوان دستهٔ اختصاصی برای پیوند داخلی انتخاب می‌شود.</p>
                    </div>
                </details>

                <details class="ty-settings-group" id="ty-settings-meta">
                    <summary><span>🧩 سازگاری با قالب</span><small>کلیدهای متا و تکسونومی خواننده</small></summary>
                    <div class="ty-settings-group-body">
                        <table class="ty-settings-table">
                            <?php
                            $this->row( $opts, 'singer_taxonomy', 'تکسونومی خواننده', 'singer', 'singer' );
                            foreach ( self::META_KEY_DEFAULTS as $meta_key => $default_key ) {
                                $option_key = 'meta_key_' . $meta_key;
                                $this->row( $opts, $option_key, 'کلید متای ' . $meta_key, $default_key, $default_key );
                            }
                            ?>
                        </table>
                        <p class="ty-hint">کلید پیش‌فرض <code>album</code> تعداد ترک‌هاست؛ آرایهٔ قدیمی <code>album_dl</code> حفظ می‌شود. کلیدهای هر ترک یک‌مبنا هستند، مانند <code>album_1_song_names</code>، <code>album_1_albumlink128</code> و <code>album_1_albumlink320</code> تا حداکثر ۱۵ ترک.</p>
                    </div>
                </details>

                <details class="ty-settings-group" id="ty-settings-seo" open>
                    <summary><span>🔎 سئو، GEO و AIO</span><small>قواعد نوشته، متادیتا، نمایش اطلاعات و Schema</small></summary>
                    <div class="ty-settings-group-body">
                        <p class="ty-hint">پیش‌فرض‌های فارسی و انگلیسی برای هر نوع محتوا دقیق و قابل ویرایش‌اند. متغیرهایی مثل <code>{artist}</code> و <code>{track_en}</code> هنگام ساخت پست با اطلاعات واقعی جایگزین می‌شوند؛ متغیر نامرتبط خالی می‌شود. قالب‌های سئو فقط روی پست‌های جدید اعمال می‌شوند و تنظیمات سراسری افزونه‌های SEO را تغییر نمی‌دهند.</p>
                        <table class="ty-settings-table">
                            <?php
                            $this->checkbox_row( $opts, 'seo_title_tag_enabled', 'عنوان تگ title (فقط اگر افزونهٔ SEO شناخته‌شده فعال نیست)' );
                            $this->checkbox_row( $opts, 'seo_meta_description_enabled', 'متای توضیحات پایه (فقط اگر افزونهٔ SEO شناخته‌شده فعال نیست)' );
                            $this->checkbox_row( $opts, 'seo_jsonld_enabled', 'Schema.org برای موزیک، آلبوم و ویدیو' );
                            $this->checkbox_row( $opts, 'seo_category_links_enabled', 'پیوند داخلی به دستهٔ مرتبط' );
                            $this->checkbox_row( $opts, 'seo_visible_details_enabled', 'نمایش مشخصات اثر و عوامل' );
                            $this->checkbox_row( $opts, 'seo_album_tracklist_enabled', 'نمایش فهرست ترک آلبوم' );
                            ?>
                        </table>
                        <?php foreach ( TaranehYar_SEO::CONTENT_TYPES as $seo_type => $seo_type_label ) : ?>
                            <details class="ty-seo-type-group" <?php echo $seo_type === 'music' ? 'open' : ''; ?>>
                                <summary><?php echo esc_html( $seo_type_label ); ?></summary>
                                <table class="ty-settings-table">
                                    <?php foreach ( TaranehYar_SEO::TEMPLATE_FIELDS as $field => $field_label ) :
                                        if ( in_array( $field, array( 'intro', 'intro_en' ), true ) ) continue;
                                        $option_key = TaranehYar_SEO::option_key( $seo_type, $field );
                                        $default_template = TaranehYar_SEO::default_template( $seo_type, $field );
                                        $this->row( $opts, $option_key, $field_label, $default_template, $default_template );
                                    endforeach; ?>
                                </table>
                                <?php $this->copy_template_settings( $opts, $seo_type ); ?>
                            </details>
                        <?php endforeach; ?>
                        <p class="ty-hint">این ابزارها محتوای دقیق و دادهٔ معنایی مکمل تولید می‌کنند؛ جایگزین محتوای واقعی یا تضمین رتبه نیستند.</p>
                    </div>
                </details>

                <div class="ty-submit-row">
                    <?php submit_button( '💾 ذخیره تنظیمات', 'primary', 'submit', false, array( 'class' => 'ty-btn ty-btn-primary' ) ); ?>
                </div>
            </form>

            <div class="ty-card ty-about-card">
                <div class="ty-card-title">👨‍💻 درباره افزونه</div>
                <p class="ty-about-text">
                    افزونه <strong class="ty-accent">ترانه‌یار | TaranehYar</strong> — نسخه <?php echo esc_html( TY_VERSION ); ?><br>
                    طراحی و توسعه توسط <a href="https://sajadmasoumi.com" target="_blank" rel="noopener" class="ty-accent-link">سجاد معصومی</a>
                </p>
            </div>
        </div>
        <?php
    }

    public static function get( $key, $default = '' ) {
        $options = get_option( 'ty_options', array() );
        return isset( $options[ $key ] ) && $options[ $key ] !== '' ? $options[ $key ] : $default;
    }

    /** Return a sanitized configured key, falling back to its canonical default. */
    public static function meta_key( $key ) {
        if ( ! isset( self::META_KEY_DEFAULTS[ $key ] ) ) {
            return '';
        }
        $default = self::META_KEY_DEFAULTS[ $key ];
        $configured = self::get( 'meta_key_' . $key, $default );
        $configured = is_scalar( $configured ) ? sanitize_key( $configured ) : '';
        return $configured !== '' ? $configured : $default;
    }
}

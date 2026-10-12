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
            if ( $k === 'singer_taxonomy' || strpos( $k, 'meta_key_' ) === 0 ) {
                $clean[ $k ] = sanitize_key( $value );
            } else {
                $clean[ $k ] = sanitize_text_field( $value );
            }
        }
        return $clean;
    }

    private function row( $opts, $key, $label, $placeholder = '', $default_value = '' ) {
        $value = isset( $opts[ $key ] ) && $opts[ $key ] !== '' ? $opts[ $key ] : $default_value;
        $val = esc_attr( $value );
        echo '<tr><th>' . esc_html( $label ) . '</th><td><input type="text" name="ty_options[' . esc_attr( $key ) . ']" value="' . $val . '" placeholder="' . esc_attr( $placeholder ) . '"></td></tr>';
    }

    public function render_settings_page() {
        $opts = get_option( 'ty_options', array() );
        ?>
        <div class="ty-wrap" dir="rtl" data-theme="dark">
            <div class="ty-header">
                <div class="ty-header-icon">⚙️</div>
                <div class="ty-header-text"><h1>تنظیمات افزونه</h1><p>پیکربندی هاست دانلود، دسته‌بندی‌ها و اطلاعات سایت</p></div>
                <div class="ty-theme-toggle" id="ty-theme-toggle" title="تغییر حالت روز/شب">
                    <span class="ty-theme-icon-light">☀️</span>
                    <span class="ty-theme-icon-dark">🌙</span>
                </div>
            </div>

            <?php
            $tabs = array(
                'ty-dashboard' => '🎵 تک آهنگ', 'ty-remix' => '🎛️ ریمیکس', 'ty-nohe' => '🕌 نوحه',
                'ty-video' => '🎬 موزیک ویدیو', 'ty-album' => '💿 آلبوم', 'ty-leech' => '⬇️ لیچ', 'ty-settings' => '⚙️ تنظیمات',
            );
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

            <form method="post" action="options.php">
                <?php settings_fields( 'ty_options_group' ); ?>

                <div class="ty-card">
                    <div class="ty-card-title">🌐 هاست دانلود</div>
                    <table class="ty-settings-table">
                        <?php
                        $this->row( $opts, 'urldlhost', 'آدرس هاست دانلود', 'https://dl.example.ir' );
                        $this->row( $opts, 'urlfunction_music', 'آدرس curl-music.php', 'https://dl.example.ir/curl-music.php' );
                        $this->row( $opts, 'urlfunction_remix', 'آدرس curl-music.php (ریمیکس)', 'https://dl.example.ir/curl-music.php' );
                        $this->row( $opts, 'urlfunction_video', 'آدرس curl-video.php', 'https://dl.example.ir/curl-video.php' );
                        $this->row( $opts, 'urlfunction_album', 'آدرس curl-album.php', 'https://dl.example.ir/curl-album.php' );
                        $this->row( $opts, 'urlfunction_nohe',  'آدرس curl-music.php (نوحه)',  'https://dl.example.ir/curl-music.php' );
                        $this->row( $opts, 'urlfunction_leech', 'آدرس curl-leech.php', 'https://dl.example.ir/curl-leech.php' );
                        $this->row( $opts, 'apitoken', 'توکن امنیتی (مشابه فایل config.php هاست دانلود)', 'یک رشته تصادفی طولانی' );
                        ?>
                    </table>
                    <p class="ty-hint">💡 تک آهنگ، ریمیکس و نوحه همگی از <code>curl-music.php</code> استفاده می‌کنند. توکن امنیتی باید دقیقاً با مقدار <code>$smp_token</code> در فایل <code>config.php</code> روی هاست دانلود یکسان باشد؛ در غیر این صورت درخواست‌ها پذیرفته نمی‌شوند.</p>
                </div>

                <div class="ty-card">
                    <div class="ty-card-title">🏷️ اطلاعات سایت</div>
                    <table class="ty-settings-table">
                        <?php
                        $this->row( $opts, 'namesite',  'نام سایت (فارسی)' );
                        $this->row( $opts, 'namesite2', 'نام سایت (انگلیسی)' );
                        $this->row( $opts, 'urlsite',   'دامنه سایت (بدون https://)', 'example.ir' );
                        ?>
                    </table>
                </div>

                <div class="ty-card">
                    <div class="ty-card-title">🧩 سازگاری با قالب (کلیدهای متا و تکسونومی)</div>
                    <table class="ty-settings-table">
                        <?php
                        $this->row( $opts, 'singer_taxonomy', 'تکسونومی خواننده', 'singer', 'singer' );
                        foreach ( self::META_KEY_DEFAULTS as $meta_key => $default_key ) {
                            $option_key = 'meta_key_' . $meta_key;
                            $this->row( $opts, $option_key, 'کلید متای ' . $meta_key, $default_key, $default_key );
                        }
                        ?>
                    </table>
                    <p class="ty-hint">💡 کلیدهای پیش‌فرض با نام‌های رایج قالب‌های موزیک تنظیم شده‌اند. در صورت نیاز، نام کلیدهای متا و تکسونومی خواننده را با قالب خود هماهنگ کنید.</p>
                </div>

                <div class="ty-card">
                    <div class="ty-card-title">📁 دسته‌بندی‌ها (Category ID)</div>
                    <table class="ty-settings-table">
                        <?php
                        $this->row( $opts, 'cat_music', 'دسته‌بندی تک آهنگ', 'مثال: 10,12' );
                        $this->row( $opts, 'cat_remix', 'دسته‌بندی ریمیکس', 'مثال: 10,13' );
                        $this->row( $opts, 'cat_nohe',  'دسته‌بندی نوحه',   'مثال: 10,14' );
                        $this->row( $opts, 'cat_video', 'دسته‌بندی موزیک ویدیو', 'مثال: 10,15' );
                        $this->row( $opts, 'cat_album', 'دسته‌بندی آلبوم', 'مثال: 5431,2' );
                        ?>
                    </table>
                    <p class="ty-hint">💡 شناسه دسته‌ها را از منوی «نوشته‌ها ← دسته‌ها» بردارید؛ چند شناسه را با ویرگول جدا کنید.</p>
                </div>

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

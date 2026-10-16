<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * تنظیم‌پذیری عنوان/توضیحات و خروجی ساخت‌یافتهٔ محتوای موسیقی.
 *
 * GEO/AIO در این افزونه بر محتوای قابل‌مشاهده و دقیق، پیوند داخلی واقعی، متادیتای
 * قابل‌خواندن و Schema.org مکمل تکیه دارد؛ از keyword stuffing و امتیازهای ساختگی
 * افزونه‌های SEO استفاده نمی‌شود.
 */
class TaranehYar_SEO {

    const CONTENT_TYPES = array(
        'music' => 'تک‌آهنگ',
        'remix' => 'ریمیکس',
        'nohe'  => 'نوحه / مداحی',
        'video' => 'موزیک‌ویدیو',
        'album' => 'آلبوم',
    );

    const TEMPLATE_FIELDS = array(
        'post_title'  => 'عنوان نوشته',
        'title'       => 'عنوان SEO',
        'description' => 'توضیحات متا',
        'intro'       => 'متن آغازین فارسی پست',
        'intro_en'    => 'متن آغازین انگلیسی پست',
        'focus'       => 'عبارت کلیدی کانونی',
    );

    const DEFAULT_TEMPLATES = array(
        'music' => array(
            'post_title'  => 'دانلود آهنگ {artist} به نام {track}',
            'title'       => 'دانلود آهنگ {artist} به نام {track} | {site}',
            'description' => 'دانلود آهنگ «{track}» از {artist}. {quality_sentence}',
            'intro'       => 'آهنگ «{track}» از {artist}. {quality_sentence}',
            'intro_en'    => '“{track_en}” by {artist_en}. {quality_sentence_en}',
            'focus'       => 'دانلود آهنگ {artist} {track}',
        ),
        'remix' => array(
            'post_title'  => 'دانلود ریمیکس {track} از {artist}',
            'title'       => 'دانلود ریمیکس {track} از {artist} | {site}',
            'description' => 'دانلود ریمیکس «{track}» از {artist}. {quality_sentence}',
            'intro'       => 'ریمیکس «{track}» از {artist}. {quality_sentence}',
            'intro_en'    => 'Remix: “{track_en}” by {artist_en}. {quality_sentence_en}',
            'focus'       => 'دانلود ریمیکس {artist} {track}',
        ),
        'nohe' => array(
            'post_title'  => 'دانلود مداحی {track} با صدای {artist}',
            'title'       => 'دانلود نوحه و مداحی {track} از {artist} | {site}',
            'description' => 'دانلود نوحه و مداحی «{track}» با صدای {artist}. {quality_sentence}',
            'intro'       => 'نوحه/مداحی «{track}» با صدای {artist}. {quality_sentence}',
            'intro_en'    => 'Devotional recitation: “{track_en}” by {artist_en}. {quality_sentence_en}',
            'focus'       => 'دانلود مداحی {artist} {track}',
        ),
        'video' => array(
            'post_title'  => 'دانلود موزیک ویدیو {artist} به نام {track}',
            'title'       => 'دانلود موزیک ویدیو {artist} به نام {track} | {site}',
            'description' => 'مشاهده و دانلود موزیک‌ویدیوی «{track}» از {artist}. {quality_sentence}',
            'intro'       => 'موزیک‌ویدیوی «{track}» از {artist}. {quality_sentence}',
            'intro_en'    => 'Music video: “{track_en}” by {artist_en}. {quality_sentence_en}',
            'focus'       => 'دانلود موزیک ویدیو {artist} {track}',
        ),
        'album' => array(
            'post_title'  => 'دانلود آلبوم {album} از {artist}',
            'title'       => 'دانلود آلبوم {album} از {artist} | {site}',
            'description' => 'آلبوم «{album}» از {artist} با {track_count} قطعهٔ ثبت‌شده.',
            'intro'       => 'اطلاعات آلبوم «{album}» از {artist}؛ تعداد قطعه‌های ثبت‌شده: {track_count}.',
            'intro_en'    => 'Album entry: “{album_en}” by {artist_en}. Track count: {track_count_en}.',
            'focus'       => 'دانلود آلبوم {artist} {album}',
        ),
    );

    /** Alternative copy patterns, stored once in settings and selected from each post form. */
    const ALT_INTRO_TEMPLATES = array(
        'music' => array(
            'fa' => array(
                2 => 'در این نوشته، آهنگ «{track}» با صدای {artist} معرفی می‌شود. {quality_sentence}',
                3 => 'نام اثر: «{track}» | خواننده: {artist}. {quality_sentence}',
            ),
            'en' => array(
                2 => 'This post introduces “{track_en}” by {artist_en}. {quality_sentence_en}',
                3 => 'Track: “{track_en}” | Artist: {artist_en}. {quality_sentence_en}',
            ),
        ),
        'remix' => array(
            'fa' => array(
                2 => 'ریمیکس «{track}» با اجرای {artist} معرفی می‌شود. {quality_sentence}',
                3 => 'نام ریمیکس: «{track}» | هنرمند: {artist}. {quality_sentence}',
            ),
            'en' => array(
                2 => 'This post introduces the remix “{track_en}” by {artist_en}. {quality_sentence_en}',
                3 => 'Remix: “{track_en}” | Artist: {artist_en}. {quality_sentence_en}',
            ),
        ),
        'nohe' => array(
            'fa' => array(
                2 => 'نوحه/مداحی «{track}» با صدای {artist} معرفی می‌شود. {quality_sentence}',
                3 => 'عنوان نوحه: «{track}» | مداح: {artist}. {quality_sentence}',
            ),
            'en' => array(
                2 => 'This post introduces the devotional recitation “{track_en}” by {artist_en}. {quality_sentence_en}',
                3 => 'Recitation: “{track_en}” | Performer: {artist_en}. {quality_sentence_en}',
            ),
        ),
        'video' => array(
            'fa' => array(
                2 => 'موزیک‌ویدیوی «{track}» از {artist} معرفی می‌شود. {quality_sentence}',
                3 => 'عنوان ویدیو: «{track}» | هنرمند: {artist}. {quality_sentence}',
            ),
            'en' => array(
                2 => 'This post introduces the music video “{track_en}” by {artist_en}. {quality_sentence_en}',
                3 => 'Music video: “{track_en}” | Artist: {artist_en}. {quality_sentence_en}',
            ),
        ),
        'album' => array(
            'fa' => array(
                2 => 'آلبوم «{album}» اثر {artist} شامل {track_count} قطعهٔ ثبت‌شده است.',
                3 => 'نام آلبوم: «{album}» | هنرمند: {artist} | تعداد قطعه‌ها: {track_count}.',
            ),
            'en' => array(
                2 => 'Album “{album_en}” by {artist_en} contains {track_count_en} submitted tracks.',
                3 => 'Album: “{album_en}” | Artist: {artist_en} | Track count: {track_count_en}.',
            ),
        ),
    );

    public function init() {
        add_filter( 'pre_get_document_title', array( $this, 'filter_document_title' ), 30 );
        add_action( 'wp_head', array( $this, 'render_head' ), 30 );
    }

    public static function option_key( $type, $field ) {
        if ( ! isset( self::DEFAULT_TEMPLATES[ $type ][ $field ] ) ) {
            return '';
        }
        return 'seo_' . $field . '_' . $type;
    }

    public static function default_template( $type, $field ) {
        return isset( self::DEFAULT_TEMPLATES[ $type ][ $field ] )
            ? self::DEFAULT_TEMPLATES[ $type ][ $field ]
            : '';
    }

    public static function intro_option_key( $type, $language, $variant = 1 ) {
        $variant = (int) $variant;
        if ( ! isset( self::DEFAULT_TEMPLATES[ $type ] ) || ! in_array( $language, array( 'fa', 'en' ), true ) || $variant < 1 || $variant > 3 ) {
            return '';
        }
        $field = $language === 'en' ? 'intro_en' : 'intro';
        if ( $variant === 1 ) {
            return self::option_key( $type, $field );
        }
        return $language === 'en'
            ? 'seo_intro_en_' . $variant . '_' . $type
            : 'seo_intro_' . $variant . '_' . $type;
    }

    public static function default_intro_template( $type, $language, $variant = 1 ) {
        if ( ! isset( self::DEFAULT_TEMPLATES[ $type ] ) ) {
            $type = 'music';
        }
        if ( ! in_array( $language, array( 'fa', 'en' ), true ) ) {
            return '';
        }
        $variant = (int) $variant;
        if ( $variant === 1 ) {
            return self::default_template( $type, $language === 'en' ? 'intro_en' : 'intro' );
        }
        return isset( self::ALT_INTRO_TEMPLATES[ $type ][ $language ][ $variant ] )
            ? self::ALT_INTRO_TEMPLATES[ $type ][ $language ][ $variant ]
            : '';
    }

    /** Read an intro template while preserving an intentionally saved empty string. */
    public static function intro_template( $type, $language, $variant = 1 ) {
        $key = self::intro_option_key( $type, $language, $variant );
        if ( $key === '' ) {
            return '';
        }
        $options = function_exists( 'get_option' ) ? get_option( 'ty_options', array() ) : array();
        if ( is_array( $options ) && array_key_exists( $key, $options ) ) {
            return is_scalar( $options[ $key ] ) ? (string) $options[ $key ] : '';
        }
        return self::default_intro_template( $type, $language, $variant );
    }

    public static function default_intro_variant( $type, $language ) {
        if ( ! isset( self::DEFAULT_TEMPLATES[ $type ] ) || ! in_array( $language, array( 'fa', 'en' ), true ) ) {
            return '1';
        }
        $key = 'seo_copy_default_' . $language . '_' . $type;
        $value = class_exists( 'TaranehYar_Settings' ) ? (string) TaranehYar_Settings::get( $key, '1' ) : '1';
        return in_array( $value, array( '0', '1', '2', '3' ), true ) ? $value : '1';
    }

    /** Return the configured template choices for a language/content type. */
    public static function intro_templates( $type, $language ) {
        $templates = array( 0 => '' );
        foreach ( array( 1, 2, 3 ) as $variant ) {
            $templates[ $variant ] = self::intro_template( $type, $language, $variant );
        }
        return $templates;
    }

    /** Replace plain-text {placeholders}; callers escape before writing HTML. */
    public static function render_template( $template, array $variables ) {
        $replace = array();
        foreach ( $variables as $key => $value ) {
            if ( is_scalar( $value ) || $value === null ) {
                $replace[ '{' . $key . '}' ] = (string) $value;
            }
        }
        return trim( strtr( (string) $template, $replace ) );
    }

    /** Resolve all configured templates for a content type into plain text. */
    public static function profile( $type, array $variables, array $overrides = array() ) {
        if ( ! isset( self::DEFAULT_TEMPLATES[ $type ] ) ) {
            $type = 'music';
        }
        $variables = array_merge( array(
            'artist' => '', 'track' => '', 'artist_en' => '', 'track_en' => '',
            'album' => '', 'album_en' => '', 'site' => '', 'qualities' => '',
            'quality_sentence' => '', 'quality_sentence_en' => '', 'track_count' => '', 'track_count_en' => '', 'info' => '', 'category' => '',
        ), $variables );
        $result = array();
        foreach ( self::TEMPLATE_FIELDS as $field => $label ) {
            $template = isset( $overrides[ $field ] )
                ? $overrides[ $field ]
                : self::default_template( $type, $field );
            $result[ $field ] = self::render_template( $template, $variables );
        }
        return $result;
    }

    /** Type-specific concise tags: categories and terms remain the primary taxonomy. */
    public static function tags_for( $type, $artist, $artist_en, $track ) {
        $base = array(
            'music' => array( 'دانلود آهنگ', 'آهنگ ایرانی' ),
            'remix' => array( 'دانلود ریمیکس', 'ریمیکس' ),
            'nohe'  => array( 'دانلود نوحه', 'دانلود مداحی', 'نوحه', 'مداحی' ),
            'video' => array( 'دانلود موزیک ویدیو', 'موزیک ویدیو' ),
            'album' => array( 'دانلود آلبوم', 'آلبوم موسیقی' ),
        );
        $tags = isset( $base[ $type ] ) ? $base[ $type ] : $base['music'];
        foreach ( array( $artist, $artist_en, $track ) as $term ) {
            $term = trim( (string) $term );
            if ( $term !== '' ) {
                $tags[] = $term;
            }
        }
        return array_values( array_unique( $tags ) );
    }

    /** Emit a description fallback only when no recognized SEO plugin owns wp_head. */
    private function has_seo_plugin() {
        return defined( 'WPSEO_VERSION' )
            || defined( 'RANK_MATH_VERSION' )
            || defined( 'SEOPRESS_VERSION' )
            || defined( 'AIOSEO_VERSION' )
            || class_exists( '\\The_SEO_Framework\\Load' );
    }

    /** Use the generated SEO title only as a fallback when no SEO plugin owns it. */
    public function filter_document_title( $title ) {
        if ( is_admin() || ! is_singular( 'post' ) || $this->has_seo_plugin()
            || TaranehYar_Settings::get( 'seo_title_tag_enabled', '1' ) !== '1' ) {
            return $title;
        }
        $post_id = (int) get_queried_object_id();
        $seo_title = $post_id > 0 ? get_post_meta( $post_id, 'ty_seo_title', true ) : '';
        return is_string( $seo_title ) && $seo_title !== '' ? $seo_title : $title;
    }

    public function render_head() {
        if ( is_admin() || ! is_singular( 'post' ) ) {
            return;
        }
        $post_id = (int) get_queried_object_id();
        if ( $post_id <= 0 ) {
            return;
        }

        if ( TaranehYar_Settings::get( 'seo_meta_description_enabled', '1' ) === '1' && ! $this->has_seo_plugin() ) {
            $description = get_post_meta( $post_id, 'ty_seo_description', true );
            if ( is_string( $description ) && $description !== '' ) {
                echo '<meta name="description" content="' . esc_attr( $description ) . "\" />\n";
            }
        }

        if ( TaranehYar_Settings::get( 'seo_jsonld_enabled', '1' ) !== '1' ) {
            return;
        }
        $schema = $this->schema_for_post( $post_id );
        if ( empty( $schema ) ) {
            return;
        }
        $json_flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        echo '<script type="application/ld+json">' . wp_json_encode( $schema, $json_flags ) . "</script>\n";
    }

    private function schema_for_post( $post_id ) {
        $type = get_post_meta( $post_id, 'ty_content_type', true );
        if ( ! isset( self::CONTENT_TYPES[ $type ] ) ) {
            $type = $this->infer_content_type( $post_id );
        }

        if ( $type === 'album' ) {
            return $this->album_schema( $post_id );
        }
        if ( $type === 'video' ) {
            return $this->video_schema( $post_id );
        }
        return $this->recording_schema( $post_id, $type );
    }

    private function infer_content_type( $post_id ) {
        $music_type = $this->mapped_meta( $post_id, 'musics_type' );
        if ( $music_type === 'album' ) return 'album';
        if ( $music_type === 'mp4' ) return 'video';

        $post_categories = function_exists( 'wp_get_post_categories' ) ? wp_get_post_categories( $post_id ) : array();
        if ( ! is_array( $post_categories ) ) return 'music';
        $matches = array();
        $category_defaults = array(
            'remix' => '10,13', 'nohe' => '10,14', 'video' => '10,15',
            'album' => '2', 'music' => '10,12',
        );
        foreach ( array( 'remix', 'nohe', 'video', 'album', 'music' ) as $type ) {
            $key = 'cat_' . $type;
            $ids = array_values( array_filter( array_map( 'intval', explode( ',', (string) TaranehYar_Settings::get( $key, $category_defaults[ $type ] ) ) ) ) );
            if ( empty( $ids ) ) continue;
            $specific_category = end( $ids );
            if ( in_array( $specific_category, $post_categories, true ) ) {
                $matches[] = $type;
            }
        }
        return count( $matches ) === 1 ? $matches[0] : 'music';
    }

    private function mapped_meta( $post_id, $key ) {
        $meta_key = TaranehYar_Settings::meta_key( $key );
        return $meta_key !== '' ? get_post_meta( $post_id, $meta_key, true ) : '';
    }

    private function common_schema_values( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) return array();

        $url = get_permalink( $post_id );
        $image = function_exists( 'get_the_post_thumbnail_url' ) ? get_the_post_thumbnail_url( $post_id, 'full' ) : '';
        if ( empty( $image ) ) {
            $image = get_post_meta( $post_id, 'fifu_image_url', true );
        }
        $description = get_post_meta( $post_id, 'ty_seo_description', true );
        if ( empty( $description ) ) {
            $description = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : $post->post_content;
        }

        return array(
            '@context'      => 'https://schema.org',
            '@id'           => $url . '#primary-entity',
            'url'           => $url,
            'description'   => wp_strip_all_tags( (string) $description ),
            'inLanguage'    => 'fa-IR',
            'datePublished' => get_post_time( 'c', true, $post_id ),
            'image'         => $this->safe_url( $image ),
        );
    }

    private function recording_schema( $post_id, $type ) {
        $data = $this->common_schema_values( $post_id );
        if ( empty( $data ) ) return array();

        $artist = $this->mapped_meta( $post_id, 'art_name' );
        $name   = $this->mapped_meta( $post_id, 'track_name' );
        if ( $artist === '' || $name === '' ) return array();

        $genre = array(
            'remix' => 'ریمیکس',
            'nohe'  => 'نوحه و مداحی',
        );
        $audio = array();
        foreach ( array( 'music320' => '320 kbps', 'music128' => '128 kbps' ) as $key => $quality ) {
            $url = $this->safe_url( $this->mapped_meta( $post_id, $key ) );
            if ( $url !== '' ) {
                $audio[] = array(
                    '@type'         => 'AudioObject',
                    'name'          => $quality,
                    'contentUrl'    => $url,
                    'encodingFormat'=> 'audio/mpeg',
                );
            }
        }

        $data['@type']    = 'MusicRecording';
        $data['name']     = (string) $name;
        $data['byArtist'] = array( '@type' => 'MusicGroup', 'name' => (string) $artist );
        $data['genre']    = isset( $genre[ $type ] ) ? $genre[ $type ] : 'موسیقی';
        $data['associatedMedia'] = $audio;
        return $this->clean_schema( $data );
    }

    private function album_schema( $post_id ) {
        $data = $this->common_schema_values( $post_id );
        if ( empty( $data ) ) return array();

        $artist = get_post_meta( $post_id, 'artist', true );
        if ( $artist === '' ) $artist = $this->mapped_meta( $post_id, 'art_name' );
        $name = get_post_meta( $post_id, 'song', true );
        if ( $name === '' ) $name = $this->mapped_meta( $post_id, 'track_name' );
        if ( $artist === '' || $name === '' ) return array();

        $count_key = TaranehYar_Settings::meta_key( 'album' );
        $count = $count_key !== '' ? (int) get_post_meta( $post_id, $count_key, true ) : 0;
        $zero_based = get_post_meta( $post_id, 'album_0_song_names', true ) !== '';
        $tracks = array();
        $tracklist_enabled = TaranehYar_Settings::get( 'seo_album_tracklist_enabled', '1' ) === '1';
        $limit = $tracklist_enabled ? min( max( 0, $count ), TaranehYar_Album_Meta::MAX_TRACKS ) : 0;
        for ( $position = 1; $position <= $limit; $position++ ) {
            $index = $zero_based ? $position - 1 : $position;
            $title = get_post_meta( $post_id, "album_{$index}_song_names", true );
            if ( ! is_string( $title ) || trim( $title ) === '' ) continue;
            $audio = array();
            foreach ( array( '128' => 'albumlink128', '320' => 'albumlink320' ) as $quality => $suffix ) {
                $url = $this->safe_url( get_post_meta( $post_id, "album_{$index}_{$suffix}", true ) );
                if ( $url !== '' ) {
                    $audio[] = array(
                        '@type'          => 'AudioObject',
                        'name'           => $quality . ' kbps',
                        'contentUrl'     => $url,
                        'encodingFormat' => 'audio/mpeg',
                    );
                }
            }
            $tracks[] = array(
                '@type'          => 'MusicRecording',
                'name'           => (string) $title,
                'position'       => count( $tracks ) + 1,
                'byArtist'       => array( '@type' => 'MusicGroup', 'name' => (string) $artist ),
                'associatedMedia'=> $audio,
            );
        }

        $data['@type']    = 'MusicAlbum';
        $data['name']     = (string) $name;
        $data['byArtist'] = array( '@type' => 'MusicGroup', 'name' => (string) $artist );
        $data['numTracks']= $count;
        $data['track']    = $tracks;
        return $this->clean_schema( $data );
    }

    private function video_schema( $post_id ) {
        $data = $this->common_schema_values( $post_id );
        if ( empty( $data ) ) return array();
        $artist = $this->mapped_meta( $post_id, 'art_name' );
        $track = $this->mapped_meta( $post_id, 'track_name' );
        if ( $artist === '' || $track === '' ) return array();

        $content_url = '';
        foreach ( array( 'video1080p', 'video720p', 'video480p' ) as $quality ) {
            $content_url = $this->safe_url( $this->mapped_meta( $post_id, $quality ) );
            if ( $content_url !== '' ) break;
        }
        $data['@type'] = 'VideoObject';
        $data['name'] = (string) $track . ' — ' . (string) $artist;
        $data['creator'] = array( '@type' => 'MusicGroup', 'name' => (string) $artist );
        $data['contentUrl'] = $content_url;
        $data['uploadDate'] = $data['datePublished'];
        $data['thumbnailUrl'] = $data['image'];
        unset( $data['image'] );
        return $this->clean_schema( $data );
    }

    private function safe_url( $url ) {
        if ( ! is_string( $url ) || $url === '' ) return '';
        $url = esc_url_raw( $url );
        $parts = wp_parse_url( $url );
        if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) || ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ) {
            return '';
        }
        return $url;
    }

    private function clean_schema( array $schema ) {
        foreach ( $schema as $key => $value ) {
            if ( is_array( $value ) ) {
                $schema[ $key ] = $this->clean_nested_schema( $value );
                if ( $schema[ $key ] === array() ) unset( $schema[ $key ] );
            } elseif ( $value === null || $value === '' ) {
                unset( $schema[ $key ] );
            }
        }
        return $schema;
    }

    private function clean_nested_schema( $value ) {
        if ( ! is_array( $value ) ) return $value;
        foreach ( $value as $key => $item ) {
            if ( is_array( $item ) ) {
                $value[ $key ] = $this->clean_nested_schema( $item );
                if ( $value[ $key ] === array() ) unset( $value[ $key ] );
            } elseif ( $item === null || $item === '' ) {
                unset( $value[ $key ] );
            }
        }
        return $value;
    }
}

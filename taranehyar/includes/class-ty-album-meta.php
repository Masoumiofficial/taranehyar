<?php
/**
 * ساخت متادیتای آلبوم مطابق ساختار قالب — بدون وابستگی به وردپرس (قابل تست).
 *
 * اندیس متاهای ترک برای قالب از ۱ شروع می‌شود: album_1_... تا album_N_...
 * آرایهٔ تاریخی ترک‌ها برای سازگاری در `album_dl` حفظ می‌شود.
 */
class TaranehYar_Album_Meta {

    /** بیشینهٔ ترک‌های فرم آلبوم (هم‌سان با endpoint هاست دانلود) */
    const MAX_TRACKS = 15;

    /** ساخت نام فایل ASCII و یکتا برای هر ترک آلبوم. */
    public static function track_file_name( $album_name, $position ) {
        $index = max( 1, (int) $position );
        return trim( (string) $album_name ) . ' - Track ' . str_pad( (string) $index, 2, '0', STR_PAD_LEFT );
    }

    /** ساخت URL مقصد یک فایل در هاست دانلود و encode کردن تک‌تک بخش‌های مسیر. */
    public static function file_url( $host, $archive_path, $filename ) {
        $base = rtrim( (string) $host, '/' );
        $parts = array();
        foreach ( array( $archive_path, $filename ) as $path ) {
            foreach ( explode( '/', trim( (string) $path, '/' ) ) as $segment ) {
                if ( $segment !== '' ) {
                    $parts[] = rawurlencode( $segment );
                }
            }
        }
        $url = $base . '/' . implode( '/', $parts );
        if ( substr( (string) $filename, -1 ) === '/' ) {
            $url .= '/';
        }
        return $url;
    }

    /**
     * ساخت متاهای آلبوم از ترک‌های واردشده.
     * هر ترک: array( 'title' => نام ترک, 'file' => نام فایل پایهٔ HostDL ).
     * عنوان خالی کنار گذاشته می‌شود و متاها پیوسته و یک‌مبنا خواهند بود.
     */
    public static function build( array $tracks, $artist, $song, $host, $archive_path, $album_name ) {
        $dir320 = "{$album_name} [320]";
        $dir128 = "{$album_name} [128]";

        $meta = array(
            'artist'      => (string) $artist,
            'song'        => (string) $song,
            'musics_type' => 'album',
            'plym'        => 0,
        );

        $tracks_data = array();
        $count       = 0;

        foreach ( $tracks as $track ) {
            if ( ! is_array( $track ) ) {
                continue;
            }
            $title = isset( $track['title'] ) ? trim( (string) $track['title'] ) : '';
            if ( $title === '' ) {
                continue;
            }
            $file = isset( $track['file'] ) ? (string) $track['file'] : '';
            $index = $count + 1;

            $link320 = self::file_url( $host, $archive_path, "{$dir320}/{$file}.mp3" );
            $link128 = self::file_url( $host, $archive_path, "{$dir128}/{$file} [128].mp3" );

            $meta[ "album_{$index}_song_names" ]   = $title;
            $meta[ "album_{$index}_albumlink128" ] = $link128;
            $meta[ "album_{$index}_albumlink320" ] = $link320;

            // آرایهٔ قدیمی ترک‌ها — برای سازگاری قالب‌های مصرف‌کنندهٔ `album_dl`
            $tracks_data[] = array(
                'title'     => $title,
                'al_url320' => $link320,
                'al_url128' => $link128,
            );

            $count++;
            if ( $count >= self::MAX_TRACKS ) {
                break;
            }
        }

        return array(
            'album'       => $count,
            'meta'        => $meta,
            'tracks_data' => $tracks_data,
        );
    }
}

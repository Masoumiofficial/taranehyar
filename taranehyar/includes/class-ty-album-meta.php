<?php
/**
 * ساخت متادیتای آلبوم مطابق ساختار قالب — بدون وابستگی به وردپرس (قابل تست).
 *
 * ساختار نهایی ذخیره‌شده روی پست:
 *  - `album`                 تعداد ترک‌های واردشده (عدد؛ مثلاً ۱۱ ترک ← 11)
 *  - `artist`                نام فارسی خواننده از فرم آلبوم
 *  - `song`                  نام فارسی آلبوم از فرم آلبوم
 *  - `musics_type`           مقدار `album`
 *  - `plym`                  مقدار عددی 0
 *  - `album_{i}_song_names`    نام ترکِ i (اندیس صفرمبنا و پیوسته)
 *  - `album_{i}_albumlink128`  نشانی مقصد فایل ۱۲۸ همان ترک روی هاست دانلود
 *  - `album_{i}_albumlink320`  نشانی مقصد فایل ۳۲۰ همان ترک روی هاست دانلود
 *  - آرایهٔ قدیمی ترک‌ها (title / al_url320 / al_url128) برای `album_dl`
 *
 * نکته: نشانی‌های ترک‌ها از مقصد واقعی فایل‌های HostDL ساخته می‌شوند
 * (کیفیت ۱۲۸ در پوشهٔ `[128]` با پسوند ` [128].mp3` و کیفیت ۳۲۰ در پوشهٔ `[320]`)
 * و هیچ نشانی نمونه‌ای در کد hardcode نشده است.
 */

class TaranehYar_Album_Meta {

    /** بیشینهٔ ترک‌های فرم آلبوم (هم‌سان با endpoint هاست دانلود) */
    const MAX_TRACKS = 15;

    /**
     * پیوند نام فایل به نشانی پایهٔ هاست دانلود و مسیر آرشیو.
     * همان قرارداد URL مقصد فایل‌های HostDL است.
     *
     * @param string $host         نشانی هاست دانلود (مثلاً https://dl.example.ir)
     * @param string $archive_path مسیر آرشیو (مثلاً album/a/artist-slug)
     * @param string $filename     نام فایل نسبی داخل آرشیو
     * @return string
     */
    public static function file_url( $host, $archive_path, $filename ) {
        $base = rtrim( (string) $host, '/' );
        $path = trim( (string) $archive_path, '/' );
        $name = ltrim( (string) $filename, '/' );
        $url  = $base;
        if ( $path !== '' ) {
            $url .= '/' . $path;
        }
        return $url . '/' . $name;
    }

    /**
     * ساخت متاهای آلبوم از فهرست ترک‌های واردشده.
     *
     * @param array  $tracks       ترک‌های واردشده به ترتیب فرم؛ هر عنوان:
     *                             array( 'title' => نام ترک, 'file' => نام فایل پایهٔ HostDL )
     *                             (عنوان‌های خالی حذف و اندیس‌ها پیوسته نگه داشته می‌شوند)
     * @param string $artist       نام فارسی خواننده از فرم آلبوم
     * @param string $song         نام فارسی آلبوم از فرم آلبوم
     * @param string $host         نشانی هاست دانلود
     * @param string $archive_path مسیر آرشیو آلبوم
     * @param string $album_name   نام پایهٔ فایل آلبوم (مثلاً "Artist - Album")
     * @return array {
     *     @type int   $album       تعداد ترک‌های واردشده (برای کلید `album`)
     *     @type array $meta        متاهای مستقیم قالب (artist, song, musics_type, plym, album_{i}_*)
     *     @type array $tracks_data آرایهٔ قدیمی ترک‌ها برای `album_dl`
     * }
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
        $index       = 0;

        foreach ( $tracks as $track ) {
            if ( ! is_array( $track ) ) {
                continue;
            }
            $title = isset( $track['title'] ) ? trim( (string) $track['title'] ) : '';
            if ( $title === '' ) {
                continue;
            }
            $file = isset( $track['file'] ) ? (string) $track['file'] : '';

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

            $index++;
            if ( $index >= self::MAX_TRACKS ) {
                break;
            }
        }

        return array(
            'album'       => $index,
            'meta'        => $meta,
            'tracks_data' => $tracks_data,
        );
    }
}

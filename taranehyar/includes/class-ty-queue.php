<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * صف پس‌زمینهٔ انتقال فایل‌ها به هاست دانلود، همراه با گزارش و امکان تلاش مجدد.
 *
 * فایل‌های موقت در صورت خطای انتقال نگه داشته می‌شوند تا مدیر بتواند کار را
 * دوباره اجرا کند؛ پس از موفقیت، حذف می‌شوند.
 */
class TaranehYar_Queue {

    const OPT_QUEUE  = 'ty_queue';
    const OPT_LOG    = 'ty_queue_log';
    const OPT_FAILED = 'ty_queue_failed';
    const MAX_LOG    = 100;
    const MAX_FAILED = 20;

    public function init() {
        add_action( 'ty_process_queue', array( $this, 'run' ) );
    }

    /** افزودن کار به صف و زمان‌بندی پردازش آن. */
    public static function add( $job ) {
        $queue = self::get_queue();
        $item = array(
            'id'           => uniqid( 'job_', true ),
            'type'         => isset( $job['type'] ) ? (string) $job['type'] : '',
            'title'        => isset( $job['title'] ) ? (string) $job['title'] : '',
            'endpoint_key' => isset( $job['endpoint_key'] ) ? (string) $job['endpoint_key'] : '',
            'archive_path' => isset( $job['archive_path'] ) ? (string) $job['archive_path'] : '',
            'data'         => isset( $job['data'] ) && is_array( $job['data'] ) ? $job['data'] : array(),
            'cleanup'      => isset( $job['cleanup'] ) ? array_values( (array) $job['cleanup'] ) : array(),
            'created'      => time(),
        );

        $queue[] = $item;
        update_option( self::OPT_QUEUE, $queue, false );
        self::log( $item, 'queued', 'در صف پردازش قرار گرفت.' );

        if ( ! wp_next_scheduled( 'ty_process_queue' ) ) {
            wp_schedule_single_event( time() + 5, 'ty_process_queue' );
        }
        if ( function_exists( 'spawn_cron' ) ) {
            spawn_cron();
        }

        return $item['id'];
    }

    public static function get_queue() {
        $queue = get_option( self::OPT_QUEUE, array() );
        return is_array( $queue ) ? array_values( $queue ) : array();
    }

    /** Failed jobs newest-first; payloads are never rendered in the admin UI. */
    public static function get_failed( $limit = 20 ) {
        $failed = get_option( self::OPT_FAILED, array() );
        if ( ! is_array( $failed ) ) return array();
        return array_slice( array_reverse( $failed ), 0, max( 0, (int) $limit ) );
    }

    /** Request an immediate WP-Cron run without making the admin request wait. */
    public static function schedule_now() {
        if ( empty( self::get_queue() ) ) return false;
        if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
            wp_clear_scheduled_hook( 'ty_process_queue' );
        }
        $scheduled = wp_schedule_single_event( time() - 1, 'ty_process_queue' );
        if ( function_exists( 'spawn_cron' ) ) {
            spawn_cron();
        }
        return $scheduled !== false;
    }

    /** پردازش کارهای صف (حداکثر حدود پنج دقیقه در هر نوبت). */
    public function run() {
        $queue = self::get_queue();
        if ( empty( $queue ) ) return;

        $deadline = time() + 280;
        while ( ! empty( $queue ) && time() < $deadline ) {
            $item = array_shift( $queue );
            update_option( self::OPT_QUEUE, $queue, false );
            $this->process_item( $item );
        }

        if ( ! empty( $queue ) ) {
            wp_schedule_single_event( time() + 30, 'ty_process_queue' );
        }
    }

    private function process_item( $item ) {
        self::log( $item, 'processing', 'در حال انتقال به هاست دانلود…' );

        $data = isset( $item['data'] ) && is_array( $item['data'] ) ? $item['data'] : array();
        // Jobs created before archive_path was introduced keep their historical HostDL paths.
        if ( ! empty( $item['archive_path'] ) ) {
            $data['archive_path'] = $item['archive_path'];
        }

        $result = TaranehYar_Http::post_to_dlhost( $item['endpoint_key'], $data );
        if ( is_wp_error( $result ) ) {
            $message = $result->get_error_message();
            self::log( $item, 'error', $message );
            $item['last_error'] = $message;
            $item['failed_at'] = time();
            self::remember_failed( $item );
            return;
        }

        self::forget_failed( isset( $item['id'] ) ? $item['id'] : '', false );
        self::cleanup_files( isset( $item['cleanup'] ) ? (array) $item['cleanup'] : array() );
        self::log( $item, 'success', 'با موفقیت به هاست دانلود منتقل شد.' );
    }

    private static function remember_failed( $item ) {
        $failed = get_option( self::OPT_FAILED, array() );
        if ( ! is_array( $failed ) ) $failed = array();
        foreach ( $failed as $index => $existing ) {
            if ( isset( $existing['id'], $item['id'] ) && $existing['id'] === $item['id'] ) {
                $failed[ $index ] = $item;
                update_option( self::OPT_FAILED, $failed, false );
                return;
            }
        }
        $failed[] = $item;
        while ( count( $failed ) > self::MAX_FAILED ) {
            $expired = array_shift( $failed );
            if ( is_array( $expired ) && isset( $expired['cleanup'] ) ) {
                self::cleanup_files( (array) $expired['cleanup'] );
            }
        }
        update_option( self::OPT_FAILED, $failed, false );
    }

    /** Add a failed job as a new queue attempt; preserve its temporary sources. */
    public static function retry_failed( $failed_id ) {
        $failed_items = get_option( self::OPT_FAILED, array() );
        if ( ! is_array( $failed_items ) ) return false;
        foreach ( $failed_items as $item ) {
            if ( ! is_array( $item ) || empty( $item['id'] ) || $item['id'] !== (string) $failed_id ) continue;
            $new_id = self::add( array(
                'type'         => isset( $item['type'] ) ? $item['type'] : '',
                'title'        => isset( $item['title'] ) ? $item['title'] : '',
                'endpoint_key' => isset( $item['endpoint_key'] ) ? $item['endpoint_key'] : '',
                'archive_path' => isset( $item['archive_path'] ) ? $item['archive_path'] : '',
                'data'         => isset( $item['data'] ) ? $item['data'] : array(),
                'cleanup'      => isset( $item['cleanup'] ) ? $item['cleanup'] : array(),
            ) );
            self::forget_failed( $failed_id, false );
            return $new_id;
        }
        return false;
    }

    /** Remove one failed item and its retained temporary sources. */
    public static function discard_failed( $failed_id ) {
        return self::forget_failed( (string) $failed_id, true );
    }

    private static function forget_failed( $failed_id, $cleanup ) {
        if ( $failed_id === '' ) return false;
        $failed = get_option( self::OPT_FAILED, array() );
        if ( ! is_array( $failed ) ) return false;
        $kept = array();
        $removed = false;
        foreach ( $failed as $item ) {
            if ( is_array( $item ) && isset( $item['id'] ) && $item['id'] === $failed_id ) {
                $removed = true;
                if ( $cleanup && isset( $item['cleanup'] ) ) {
                    self::cleanup_files( (array) $item['cleanup'] );
                }
                continue;
            }
            $kept[] = $item;
        }
        if ( $removed ) update_option( self::OPT_FAILED, $kept, false );
        return $removed;
    }

    /** Cancel pending items and remove any temporary upload files they own. */
    public static function discard_pending() {
        $queue = self::get_queue();
        foreach ( $queue as $item ) {
            self::cleanup_files( isset( $item['cleanup'] ) ? (array) $item['cleanup'] : array() );
            self::log( $item, 'cancelled', 'درخواست توسط مدیر از صف حذف شد.' );
        }
        update_option( self::OPT_QUEUE, array(), false );
        if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
            wp_clear_scheduled_hook( 'ty_process_queue' );
        }
        return count( $queue );
    }

    /** Clear history and retryable failures; pending work is intentionally kept. */
    public static function clear_history() {
        $failed = get_option( self::OPT_FAILED, array() );
        if ( is_array( $failed ) ) {
            foreach ( $failed as $item ) {
                if ( is_array( $item ) && isset( $item['cleanup'] ) ) {
                    self::cleanup_files( (array) $item['cleanup'] );
                }
            }
        }
        $count = is_array( $failed ) ? count( $failed ) : 0;
        update_option( self::OPT_FAILED, array(), false );
        update_option( self::OPT_LOG, array(), false );
        return $count;
    }

    /** Only delete known temporary files that resolve inside the WordPress uploads directory. */
    private static function cleanup_files( $files ) {
        if ( ! function_exists( 'wp_upload_dir' ) || ! is_array( $files ) ) return;
        $uploads = wp_upload_dir();
        $base = isset( $uploads['basedir'] ) ? realpath( $uploads['basedir'] ) : false;
        if ( ! $base ) return;
        $prefix = rtrim( $base, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
        foreach ( $files as $file ) {
            if ( ! is_string( $file ) || $file === '' ) continue;
            $real = realpath( $file );
            if ( ! $real || strpos( $real, $prefix ) !== 0 ) continue;
            $name = basename( $real );
            if ( ! preg_match( '/^(?:temp-[0-9]+-[0-9]+|leech-[A-Za-z0-9._-]+)\.(?:mp3|mp4|jpe?g|png|webp)$/i', $name ) ) continue;
            if ( is_file( $real ) ) @unlink( $real );
        }
    }

    /** ثبت وضعیت یک کار در گزارش (حداکثر ۱۰۰ رکورد آخر). */
    public static function log( $item, $status, $message ) {
        $log = get_option( self::OPT_LOG, array() );
        if ( ! is_array( $log ) ) $log = array();
        $entry = array(
            'id'      => isset( $item['id'] ) ? (string) $item['id'] : '',
            'type'    => isset( $item['type'] ) ? (string) $item['type'] : '',
            'title'   => isset( $item['title'] ) ? (string) $item['title'] : '',
            'status'  => (string) $status,
            'message' => (string) $message,
            'time'    => time(),
        );
        foreach ( $log as $index => $existing ) {
            if ( isset( $existing['id'] ) && $existing['id'] === $entry['id'] ) {
                $log[ $index ] = $entry;
                update_option( self::OPT_LOG, array_slice( $log, -self::MAX_LOG ), false );
                return;
            }
        }
        $log[] = $entry;
        update_option( self::OPT_LOG, array_slice( $log, -self::MAX_LOG ), false );
    }

    /** آخرین رکوردهای گزارش (جدیدترین اول). */
    public static function get_log( $limit = 50 ) {
        $log = get_option( self::OPT_LOG, array() );
        if ( ! is_array( $log ) ) return array();
        return array_slice( array_reverse( $log ), 0, max( 0, (int) $limit ) );
    }

    public static function status_label( $status ) {
        $labels = array(
            'queued'     => 'در صف',
            'processing' => 'در حال انتقال',
            'success'    => 'موفق',
            'error'      => 'خطا',
            'cancelled'  => 'لغو شد',
        );
        return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
    }
}

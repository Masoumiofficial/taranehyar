<?php
/**
 * Tests that the background transfer queue carries archive paths, retains failed
 * jobs for retry, and exposes safe admin controls.
 * Run with: php tests/test-queue.php
 */
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['ty_queue_options'] = array();
$GLOBALS['ty_queue_schedules'] = array();
$GLOBALS['ty_queue_cleared_hooks'] = array();
$GLOBALS['ty_queue_test_uploads'] = sys_get_temp_dir() . '/ty-queue-uploads-' . uniqid('', true);
@mkdir($GLOBALS['ty_queue_test_uploads'], 0700, true);

function get_option( $key, $default = false ) {
    return array_key_exists( $key, $GLOBALS['ty_queue_options'] ) ? $GLOBALS['ty_queue_options'][ $key ] : $default;
}
function update_option( $key, $value, $autoload = null ) {
    $GLOBALS['ty_queue_options'][ $key ] = $value;
    return true;
}
function wp_next_scheduled( $hook ) { return false; }
function wp_schedule_single_event( $timestamp, $hook ) {
    $GLOBALS['ty_queue_schedules'][] = array( 'time' => $timestamp, 'hook' => $hook );
    return true;
}
function wp_clear_scheduled_hook( $hook ) {
    $GLOBALS['ty_queue_cleared_hooks'][] = $hook;
    return 1;
}
function spawn_cron() { return true; }
function wp_upload_dir() { return array('basedir' => $GLOBALS['ty_queue_test_uploads']); }
function queue_fixture_file($name) {
    $path = $GLOBALS['ty_queue_test_uploads'] . DIRECTORY_SEPARATOR . $name;
    file_put_contents($path, 'temporary');
    return $path;
}
class WP_Error {
    private $message;
    public function __construct( $code = '', $message = '' ) { $this->message = $message; }
    public function get_error_message() { return $this->message; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }

class TaranehYar_Http {
    public static $requests = array();
    public static $result = true;
    public static function post_to_dlhost( $endpoint_key, $data ) {
        self::$requests[] = array( 'endpoint_key' => $endpoint_key, 'data' => $data );
        return self::$result;
    }
}

require __DIR__ . '/../taranehyar/includes/class-ty-queue.php';

$fails = 0;
function queue_ok( $condition, $label ) {
    global $fails;
    if ( $condition ) { echo "PASS: $label\n"; } else { echo "FAIL: $label\n"; $fails++; }
}

TaranehYar_Queue::add( array(
    'type' => 'music',
    'title' => 'Alireza - Song',
    'endpoint_key' => 'urlfunction_music',
    'archive_path' => 'music/a/alireza-rozegar',
    'data' => array( 'file_name' => 'Alireza - Song', 'archive_root' => 'music' ),
) );
$queued = TaranehYar_Queue::get_queue();
queue_ok( isset( $queued[0]['archive_path'] ) && $queued[0]['archive_path'] === 'music/a/alireza-rozegar', 'archive path is stored in queue item' );

$queue = new TaranehYar_Queue();
$queue->run();
queue_ok(
    isset( TaranehYar_Http::$requests[0]['data']['archive_path'] ) && TaranehYar_Http::$requests[0]['data']['archive_path'] === 'music/a/alireza-rozegar',
    'archive path is sent to HostDL with the queued request'
);

TaranehYar_Queue::add( array(
    'type' => 'ریمیکس',
    'title' => 'Alireza - Remix',
    'endpoint_key' => 'urlfunction_remix',
    'archive_path' => 'remix/a/alireza-rozegar',
    'data' => array( 'file_name' => 'Alireza - Remix', 'archive_root' => 'remix' ),
) );
$queue->run();
queue_ok(
    TaranehYar_Http::$requests[1]['data']['archive_root'] === 'remix'
        && TaranehYar_Http::$requests[1]['data']['archive_path'] === 'remix/a/alireza-rozegar',
    'remix root and archive path reach HostDL'
);

TaranehYar_Queue::add( array(
    'type' => 'legacy',
    'title' => 'Legacy Song',
    'endpoint_key' => 'urlfunction_music',
    'data' => array( 'file_name' => 'Legacy Song' ),
) );
$queue->run();
queue_ok(
    ! array_key_exists( 'archive_path', TaranehYar_Http::$requests[2]['data'] ),
    'legacy queue item stays compatible and omits archive_path'
);

TaranehYar_Http::$result = new WP_Error( 'host', 'HostDL HTTP 503' );
$retry_file = queue_fixture_file( 'temp-91-320.mp3' );
$failed_id = TaranehYar_Queue::add( array(
    'type' => 'نوحه',
    'title' => 'Maddah - Nohe',
    'endpoint_key' => 'urlfunction_nohe',
    'archive_path' => 'nohe/m/maddah',
    'data' => array( 'file_name' => 'Maddah - Nohe', 'archive_root' => 'nohe' ),
    'cleanup' => array( $retry_file ),
) );
$queue->run();
$failed = TaranehYar_Queue::get_failed( 20 );
queue_ok( count( $failed ) === 1 && $failed[0]['id'] === $failed_id, 'failed transfer is retained for management' );
queue_ok( isset( $failed[0]['last_error'] ) && $failed[0]['last_error'] === 'HostDL HTTP 503', 'failure message is recorded for the admin page' );
queue_ok( file_exists( $retry_file ), 'temporary upload source is retained after a failed transfer for retry' );

TaranehYar_Http::$result = true;
$retry_id = TaranehYar_Queue::retry_failed( $failed_id );
$retry_queue = TaranehYar_Queue::get_queue();
queue_ok( $retry_id !== false && count( $retry_queue ) === 1 && $retry_queue[0]['id'] === $retry_id, 'failed transfer can be requeued as a new attempt' );
queue_ok( empty( TaranehYar_Queue::get_failed() ), 'retry removes the old item from the retry list' );
queue_ok( file_exists( $retry_file ), 'retry preserves its temporary source until the new attempt runs' );
$queue->run();
queue_ok(
    TaranehYar_Http::$requests[4]['data']['archive_root'] === 'nohe'
        && TaranehYar_Http::$requests[4]['data']['archive_path'] === 'nohe/m/maddah',
    'retried nohe transfer keeps its separate archive root'
);
queue_ok( ! file_exists( $retry_file ), 'temporary upload source is removed only after a successful retry' );

TaranehYar_Http::$result = new WP_Error( 'host', 'HostDL unavailable' );
$discard_id = TaranehYar_Queue::add( array(
    'type' => 'music', 'title' => 'Failed item to discard', 'endpoint_key' => 'urlfunction_music',
    'data' => array( 'file_name' => 'discard' ),
) );
$queue->run();
queue_ok( TaranehYar_Queue::discard_failed( $discard_id ), 'admin can discard a failed item' );
queue_ok( empty( TaranehYar_Queue::get_failed() ), 'discarded failed item leaves the retry list' );

TaranehYar_Http::$result = true;
$pending_file = queue_fixture_file( 'leech-test-pending.mp4' );
$outside_dir = sys_get_temp_dir() . '/ty-queue-outside-' . uniqid('', true);
@mkdir( $outside_dir, 0700, true );
$outside_file = $outside_dir . '/temp-123-128.mp3';
file_put_contents( $outside_file, 'do not delete outside uploads' );
TaranehYar_Queue::add( array(
    'type' => 'music', 'title' => 'Pending item', 'endpoint_key' => 'urlfunction_music', 'data' => array(),
    'cleanup' => array( $pending_file, $outside_file ),
) );
$cleared_failures = TaranehYar_Queue::clear_history();
queue_ok( $cleared_failures === 0 && count( TaranehYar_Queue::get_queue() ) === 1 && empty( TaranehYar_Queue::get_log() ), 'clearing history does not alter pending transfers' );
queue_ok( TaranehYar_Queue::schedule_now(), 'admin control can schedule an immediate queue run' );
queue_ok( TaranehYar_Queue::discard_pending() === 1, 'pending jobs can be cancelled in bulk' );
queue_ok( empty( TaranehYar_Queue::get_queue() ), 'bulk cancellation empties the pending queue' );
queue_ok( in_array( 'ty_process_queue', $GLOBALS['ty_queue_cleared_hooks'], true ), 'bulk cancellation clears the scheduled queue event' );
queue_ok( ! file_exists( $pending_file ), 'cancellation removes a known temporary file from uploads' );
queue_ok( file_exists( $outside_file ), 'cleanup refuses to delete files outside the uploads directory' );
queue_ok( TaranehYar_Queue::get_log( 1 )[0]['status'] === 'cancelled', 'cancelled jobs remain visible in the transfer history' );
@unlink( $outside_file );
@rmdir( $outside_dir );
@rmdir( $GLOBALS['ty_queue_test_uploads'] );

echo $fails === 0 ? "\nALL TESTS PASSED\n" : "\n$fails TEST(S) FAILED\n";
exit( $fails === 0 ? 0 : 1 );

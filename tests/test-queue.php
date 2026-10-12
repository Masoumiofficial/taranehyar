<?php
/**
 * Tests that archive_path travels through the background queue while older jobs
 * without it retain the HostDL date-based behavior.
 * Run with: php tests/test-queue.php
 */
define('ABSPATH', __DIR__ . '/');
$GLOBALS['ty_queue_options'] = array();

function get_option($key, $default = false) {
    return array_key_exists($key, $GLOBALS['ty_queue_options']) ? $GLOBALS['ty_queue_options'][$key] : $default;
}
function update_option($key, $value, $autoload = null) {
    $GLOBALS['ty_queue_options'][$key] = $value;
    return true;
}
function wp_next_scheduled($hook) { return false; }
function wp_schedule_single_event($timestamp, $hook) { return true; }
function spawn_cron() { return true; }

class TaranehYar_Http {
    public static $requests = array();
    public static function post_to_dlhost($endpoint_key, $data) {
        self::$requests[] = array('endpoint_key' => $endpoint_key, 'data' => $data);
        return true;
    }
}

require __DIR__ . '/../taranehyar/includes/class-ty-queue.php';

$fails = 0;
function queue_ok($condition, $label) {
    global $fails;
    if ($condition) { echo "PASS: $label\n"; } else { echo "FAIL: $label\n"; $fails++; }
}

TaranehYar_Queue::add(array(
    'type' => 'music',
    'title' => 'Alireza - Song',
    'endpoint_key' => 'urlfunction_music',
    'archive_path' => 'music/a/alireza-rozegar',
    'data' => array('file_name' => 'Alireza - Song'),
));
$queued = get_option(TaranehYar_Queue::OPT_QUEUE, array());
queue_ok(isset($queued[0]['archive_path']) && $queued[0]['archive_path'] === 'music/a/alireza-rozegar', 'archive path is stored in queue item');

$queue = new TaranehYar_Queue();
$queue->run();
queue_ok(
    isset(TaranehYar_Http::$requests[0]['data']['archive_path']) && TaranehYar_Http::$requests[0]['data']['archive_path'] === 'music/a/alireza-rozegar',
    'archive path is sent to HostDL with the queued request'
);

TaranehYar_Queue::add(array(
    'type' => 'legacy',
    'title' => 'Legacy Song',
    'endpoint_key' => 'urlfunction_music',
    'data' => array('file_name' => 'Legacy Song'),
));
$queue->run();
queue_ok(
    !array_key_exists('archive_path', TaranehYar_Http::$requests[1]['data']),
    'legacy queue item stays compatible and omits archive_path'
);

echo $fails === 0 ? "\nALL TESTS PASSED\n" : "\n$fails TEST(S) FAILED\n";
exit($fails === 0 ? 0 : 1);

<?php
/**
 * Test-only helpers for .github/playground/bookings.py (7.35.1.29, CVE-2026-105067;
 * 7.35.1.31, the 2026-10-21 audit fixes; 7.35.1.32, the PR #48 review follow-ups).
 *
 * Installed as a must-use plugin in a throwaway WordPress Playground site only;
 * it is not part of the plugin and never ships. The test fixtures use harmless
 * marker strings ("pgmarker", an apostrophe in a key), not SQL payloads.
 */

// The fork forces booking_status off (option_mec_options, default priority). Turn it
// back on afterwards so the booking handlers register and can be tested. A request
// with pgtest_bookings_off=1 sees the production setting instead.
add_filter('option_mec_options', function ($options) {
    if (!empty($_REQUEST['pgtest_bookings_off'])) return $options;
    if (!is_array($options)) $options = [];
    $options['settings']['booking_status'] = 1;
    return $options;
}, 20);

/** Log every mec_bookings query, and which object each edit_post check was for. */
function pgtest_log($line)
{
    if (wp_doing_ajax() && isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'pgtest_') === 0) return;
    file_put_contents('/logs/bookings.log', str_replace("\n", ' ', $line) . "\n", FILE_APPEND);
}

$GLOBALS['pgtest_queries'] = null; // an array while pgtest_capture() records

add_filter('query', function ($query) {
    if (strpos($query, 'mec_bookings') !== false)
    {
        if (is_array($GLOBALS['pgtest_queries'])) $GLOBALS['pgtest_queries'][] = $query;
        pgtest_log('query: ' . $query);
    }
    return $query;
});

// 7.35.1.32: log every e-mail instead of sending it.
add_filter('pre_wp_mail', function ($return, $atts) {
    pgtest_log('mail: ' . implode(',', (array) $atts['to']) . ' ' . $atts['subject']);
    return true;
}, 10, 2);

add_filter('map_meta_cap', function ($caps, $cap, $user_id, $args) {
    if ($cap !== 'edit_post' || !isset($args[0])) return $caps;
    if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'mec_event_bookings') pgtest_log('edit_post: ' . var_export($args[0], true));

    // The WPML case: this user may edit the original event only, not its translation.
    $ids = get_option('pgtest_ids');
    $user = get_userdata($user_id);
    if ($ids && $user && $user->user_login === 'pgtranslator' && is_int($args[0]) && $args[0] === (int) $ids['wpml_original']) return ['exist'];

    return $caps;
}, 10, 4);

// A minimal WPML stand-in for requests that ask for it: get_original_event() only
// needs class SitePress and these two filters.
if (!empty($_POST['pgtest_wpml']) && !class_exists('SitePress'))
{
    class SitePress {}

    add_filter('wpml_element_trid', function ($trid, $element_id) {
        $ids = get_option('pgtest_ids');
        return in_array((int) $element_id, [(int) $ids['wpml_original'], (int) $ids['wpml_translation']], true) ? 9001 : $trid;
    }, 10, 2);

    add_filter('wpml_get_element_translations', function ($translations, $trid) {
        if ($trid !== 9001) return $translations;
        $ids = get_option('pgtest_ids');
        return [
            'lt' => (object) ['element_id' => (int) $ids['wpml_original'], 'original' => 1],
            'en' => (object) ['element_id' => (int) $ids['wpml_translation'], 'original' => 0],
        ];
    }, 10, 2);
}

function pgtest_admin_only()
{
    if (!current_user_can('manage_options')) wp_send_json(['error' => 'not an administrator'], 403);
}

/** Run $callback and return [its result, the mec_bookings queries it ran]. */
function pgtest_capture($callback)
{
    $GLOBALS['pgtest_queries'] = [];
    $result = $callback();
    $queries = $GLOBALS['pgtest_queries'];
    $GLOBALS['pgtest_queries'] = null;
    return [$result, $queries];
}

function pgtest_booking($event_id, $email, $timestamp)
{
    global $wpdb;

    $booking_id = wp_insert_post(['post_type' => 'mec-books', 'post_status' => 'publish', 'post_title' => 'Booking ' . $email, 'post_author' => 1]);
    if (!$booking_id || is_wp_error($booking_id)) throw new Exception('booking post was not created');
    update_post_meta($booking_id, 'mec_attendees', [['name' => 'Attendee ' . $email, 'email' => $email]]);
    update_post_meta($booking_id, 'mec_event_id', $event_id);
    update_post_meta($booking_id, 'mec_date', $timestamp . ':' . ($timestamp + 3600));

    $ok = $wpdb->insert($wpdb->prefix . 'mec_bookings', [
        'booking_id' => $booking_id, 'event_id' => $event_id, 'ticket_ids' => ',1,', 'status' => 'publish',
        'confirmed' => 1, 'verified' => 1, 'all_occurrences' => 0,
        'date' => gmdate('Y-m-d H:i:s', $timestamp), 'timestamp' => $timestamp,
    ]);
    if (!$ok) throw new Exception('mec_bookings row was not created: ' . $wpdb->last_error);

    return $booking_id;
}

function pgtest_event($title, $author)
{
    $id = wp_insert_post(['post_type' => 'mec-events', 'post_status' => 'publish', 'post_title' => $title, 'post_author' => $author]);
    if (!$id || is_wp_error($id)) throw new Exception('event was not created');
    return $id;
}

// Fixtures: called once by the administrator. The passwords come from the test
// script (random, throwaway site).
add_action('wp_ajax_pgtest_setup', function () {
    pgtest_admin_only();
    global $wpdb;

    $users = [];
    foreach (['pgsub', 'pgauthor', 'pgtranslator'] as $login)
    {
        $uid = wp_insert_user(['user_login' => $login, 'user_pass' => wp_unslash($_POST['password']), 'user_email' => $login . '@example.test', 'role' => 'subscriber']);
        if (is_wp_error($uid)) wp_send_json(['error' => $uid->get_error_message()], 500);
        $users[$login] = $uid;
    }

    // Two occurrences a week apart, in the future so the cancel link is still valid.
    $t1 = (int) (floor(time() / 3600) * 3600) + 30 * DAY_IN_SECONDS;
    $t2 = $t1 + WEEK_IN_SECONDS;

    $ids = ['t1' => $t1, 't2' => $t2, 'users' => $users];
    $ids['event'] = pgtest_event('Admin event', 1);
    $ids['booking'] = pgtest_booking($ids['event'], 'admin-event-1@example.test', $t1);
    $ids['booking2'] = pgtest_booking($ids['event'], 'admin-event-2@example.test', $t2);
    $wpdb->update($wpdb->prefix . 'mec_bookings', ['user_id' => 1], ['booking_id' => $ids['booking2']]);
    $ids['author_event'] = pgtest_event('Subscriber-authored event', $users['pgauthor']);
    pgtest_booking($ids['author_event'], 'author-event@example.test', $t1);
    $ids['wpml_original'] = pgtest_event('WPML original', 1);
    $ids['wpml_translation'] = pgtest_event('WPML translation', 1);
    pgtest_booking($ids['wpml_translation'], 'translation-event@example.test', $t1);
    $ids['page'] = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Not an event']);

    // 7.35.1.31: content a subscriber owns but may not edit, for the FES post-type check.
    $ids['sub_page'] = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Subscriber page', 'post_author' => $users['pgsub']]);
    $ids['sub_post'] = wp_insert_post(['post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Subscriber post', 'post_author' => $users['pgsub']]);
    $ids['sub_attachment'] = wp_insert_post(['post_type' => 'attachment', 'post_status' => 'inherit', 'post_title' => 'Subscriber attachment', 'post_author' => $users['pgsub']]);

    // 7.35.1.32: an event for the FES remove action to delete.
    $ids['remove_event'] = pgtest_event('Event to remove', 1);

    update_option('pgtest_ids', $ids);
    wp_send_json($ids);
});

add_action('wp_ajax_pgtest_nonces', function () {
    wp_send_json([
        'bookings' => wp_create_nonce('mec_event_bookings'), 'fes' => wp_create_nonce('mec_fes_nonce'),
        'fes_form' => wp_create_nonce('mec_fes_form'), 'report' => wp_create_nonce('mec_settings_nonce'),
        'purge' => wp_create_nonce('mec_export_purge'), 'ix_import' => wp_create_nonce('mec_ix_g_calendar_import'),
        'ix_export' => wp_create_nonce('mec_ix_g_calendar_export'), 'fes_remove' => wp_create_nonce('mec_fes_remove'),
    ]);
});

add_action('wp_ajax_pgtest_state', function () {
    pgtest_admin_only();
    global $wpdb;

    $ids = get_option('pgtest_ids');
    $posts = [];
    foreach (['event', 'author_event', 'page', 'sub_page', 'sub_post', 'sub_attachment', 'remove_event'] as $key)
    {
        $post = !empty($ids[$key]) ? get_post($ids[$key]) : null;
        if ($post) $posts[$key] = ['title' => $post->post_title, 'status' => $post->post_status];
    }

    $ix = (new MEC_main())->get_ix_options();
    wp_send_json([
        'php' => PHP_VERSION, 'registered' => (bool) has_action('wp_ajax_mec_event_bookings'), 'fes_export' => (bool) has_action('wp_ajax_mec_fes_csv_export'),
        'report_ajax' => array_values(array_filter(['report_event_dates', 'mec_attendees', 'mec_mass_email', 'mec_report_mass'], function ($action) {
            return (bool) has_action('wp_ajax_' . $action);
        })),
        'posts' => $posts,
        'ix' => (object) array_intersect_key(is_array($ix) ? $ix : [], array_flip(['sync_g_import', 'sync_g_export', 'google_import_api_key', 'google_export_client_id'])),
        'booking_rows' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mec_bookings"),
        'booking_posts' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mec-books'"),
    ]);
});

// Is the OAuth state that the Google export put into its authorization link valid?
add_action('wp_ajax_pgtest_oauth_state', function () {
    pgtest_admin_only();
    wp_send_json(['valid' => (bool) wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['state'] ?? '')), 'mec_ix_g_calendar_oauth')]);
});

// MEC_main::get_bookings() and the prepared lookups, called directly.
add_action('wp_ajax_pgtest_unit', function () {
    pgtest_admin_only();
    global $wpdb;

    $ids = get_option('pgtest_ids');
    $main = new MEC_main();
    $eid = (int) $ids['event'];
    $t1 = (int) $ids['t1'];
    $checks = [];
    $check = function ($name, $ok, $detail = null) use (&$checks) {
        $checks[] = ['name' => $name, 'ok' => (bool) $ok, 'detail' => $detail];
    };
    $booking_ids = function ($bookings) {
        return array_map(function ($post) { return (int) $post->ID; }, (array) $bookings);
    };

    // A bad event ID or a non-numeric timestamp never reaches the database.
    foreach ([0, -3, 'abc', '', null] as $bad)
    {
        [$result, $queries] = pgtest_capture(function () use ($main, $bad) { return $main->get_bookings($bad); });
        $check('get_bookings(' . var_export($bad, true) . ') returns [] without a query', $result === [] && !$queries, $queries);
    }
    foreach ([$t1 . 'pgmarker', 'pgmarker', $t1 . ' pgmarker'] as $bad)
    {
        [$result, $queries] = pgtest_capture(function () use ($main, $eid, $bad) { return $main->get_bookings($eid, $bad); });
        $check('get_bookings(event, ' . var_export($bad, true) . ') returns [] without a query', $result === [] && !$queries, $queries);
    }

    // The default limit '-1' means no limit: both bookings, no LIMIT clause.
    [$result, $queries] = pgtest_capture(function () use ($main, $eid) { return $main->get_bookings($eid); });
    $check('get_bookings(event) keeps the default unlimited limit', count($result) === 2 && count($queries) === 1 && stripos($queries[0], 'LIMIT') === false, $queries);
    [$result, $queries] = pgtest_capture(function () use ($main, $eid) { return $main->get_bookings($eid, null, -1); });
    $check('get_bookings(event, null, -1) is unlimited', count($result) === 2 && stripos(implode(' ', $queries), 'LIMIT') === false, $queries);

    // Numeric strings are turned into integers before they are put into the SQL.
    [$result, $queries] = pgtest_capture(function () use ($main, $ids) { return $main->get_bookings($ids['event'] . 'pgmarker', null, '1e0'); });
    $sql = implode(' ', $queries);
    $check('get_bookings() puts the event ID and limit into the SQL as integers', count($result) === 1 && preg_match('/`event_id`=' . $eid . ' .* LIMIT 1$/', $sql) && strpos($sql, 'pgmarker') === false && strpos($sql, '1e0') === false, $queries);

    $float_ts = sprintf('%.7e', $t1); // a numeric string that is not an integer literal
    [$result, $queries] = pgtest_capture(function () use ($main, $eid, $float_ts) { return $main->get_bookings($eid, $float_ts); });
    $sql = implode(' ', $queries);
    $check('get_bookings() puts a numeric timestamp into the SQL as an integer', $booking_ids($result) === [(int) $ids['booking']] && strpos($sql, '`timestamp`=' . $t1) !== false && strpos($sql, $float_ts) === false, [$float_ts, $queries]);

    [$result, $queries] = pgtest_capture(function () use ($main, $eid, $ids) { return $main->get_bookings($eid, (string) $ids['t2'], '-1', '1pgmarker', false); });
    $sql = implode(' ', $queries);
    $check('get_bookings() puts the user ID into the SQL as an integer', $booking_ids($result) === [(int) $ids['booking2']] && preg_match('/`user_id`=1(\s|$)/', $sql) && strpos($sql, 'pgmarker') === false, $queries);

    // get_attach_id() still finds the attachment, also when the URL has an apostrophe.
    foreach (['https://example.test/wp-content/uploads/pg-image.jpg', "https://example.test/wp-content/uploads/pg-o'image.jpg"] as $url)
    {
        $att = wp_insert_post(['post_type' => 'attachment', 'post_status' => 'inherit', 'post_title' => 'pg image']);
        $wpdb->update($wpdb->posts, ['guid' => $url], ['ID' => $att]);
        $check('get_attach_id() finds ' . $url, (int) $main->get_attach_id($url) === (int) $att, $main->get_attach_id($url));
    }
    $check('get_attach_id() returns nothing for an unknown URL', !$main->get_attach_id('https://example.test/none.jpg'));

    // The verify and cancel endpoints still find their booking by key.
    $endpoint = function ($var, $key) use ($main) {
        global $wp_query;
        $saved = $wp_query->query_vars;
        set_query_var($var, $key);
        ob_start();
        $main->do_endpoints();
        $html = wp_strip_all_tags(ob_get_clean());
        $wp_query->query_vars = $saved;
        return trim($html);
    };
    foreach (['pgverify123', "pg-o'verify"] as $i => $key)
    {
        $bid = $i ? $ids['booking2'] : $ids['booking'];
        update_post_meta($bid, 'mec_verification_key', $key);
        update_post_meta($bid, 'mec_verified', '1');
        delete_post_meta($bid, 'mec_verified_user');
        $html = $endpoint('verify', $key);
        $check('the verify endpoint finds the booking for key ' . $key, get_post_meta($bid, 'mec_verified_user', true) == 1 && strpos($html, 'verified successfully') !== false, $html);
    }
    foreach (['pgcancel123', "pg-o'cancel"] as $i => $key)
    {
        $bid = $i ? $ids['booking2'] : $ids['booking'];
        update_post_meta($bid, 'mec_cancellation_key', $key);
        $html = $endpoint('cancel', $key);
        $check('the cancel endpoint finds the booking for key ' . $key, strpos($html, 'want to cancel the booking') !== false, $html);
    }
    $check('the verify endpoint ignores an unknown key', $endpoint('verify', 'pg-unknown') === '');

    wp_send_json(['checks' => $checks]);
});

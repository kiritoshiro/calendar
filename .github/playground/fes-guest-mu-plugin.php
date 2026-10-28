<?php
/**
 * Test-only helpers for .github/playground/fes_guest.py (7.35.1.33, FES guest
 * accounts and the event gallery upload).
 *
 * Installed as a must-use plugin in a throwaway WordPress Playground site only;
 * it is not part of the plugin and never ships. Users and addresses are
 * harmless test values under example.test.
 */

// Guest submission, guest account creation and the event gallery on; FES limited
// to administrators and subscribers, so a contributor is not an FES user. A
// request with pgfes_guest_off=1 sees guest submission switched off.
add_filter('option_mec_options', function ($options) {
    if (!is_array($options)) $options = [];
    $options['settings']['fes_guest_status'] = empty($_REQUEST['pgfes_guest_off']) ? 1 : 0;
    $options['settings']['fes_guest_name_email'] = 1;
    $options['settings']['fes_guest_user_creation'] = 1;
    $options['settings']['event_gallery_status'] = 1;
    $options['settings']['fes_access_roles'] = ['administrator', 'subscriber'];
    return $options;
}, 20);

// No e-mail leaves the site; register_new_user() sends two.
add_filter('pre_wp_mail', '__return_true');

function pgfes_admin_only()
{
    if (!current_user_can('manage_options')) wp_send_json(['error' => 'not an administrator'], 403);
}

// Users: called once by the administrator. The password comes from the test script.
add_action('wp_ajax_pgfes_setup', function () {
    pgfes_admin_only();

    $users = [];
    foreach (['pgfsub' => 'subscriber', 'pgfcontrib' => 'contributor', 'pgfexisting' => 'subscriber'] as $login => $role)
    {
        $uid = wp_insert_user(['user_login' => $login, 'user_pass' => wp_unslash($_POST['password']), 'user_email' => $login . '@example.test', 'role' => $role]);
        if (is_wp_error($uid)) wp_send_json(['error' => $uid->get_error_message()], 500);
        $users[$login] = $uid;
    }

    wp_send_json(['users' => $users]);
});

// The nonces the FES form and the event meta box print, for the caller (also a guest).
$pgfes_nonces = function () {
    wp_send_json(['fes_form' => wp_create_nonce('mec_fes_form'), 'gallery' => wp_create_nonce('mec_event_gallery_image_upload')]);
};
add_action('wp_ajax_pgfes_nonces', $pgfes_nonces);
add_action('wp_ajax_nopriv_pgfes_nonces', $pgfes_nonces);

// A moderator publishes an event, as the Publish button does.
add_action('wp_ajax_pgfes_publish', function () {
    pgfes_admin_only();
    $id = absint($_POST['id'] ?? 0);
    $result = wp_update_post(['ID' => $id, 'post_status' => 'publish'], true);
    wp_send_json(['ok' => !is_wp_error($result)]);
});

// An event (found by its unique title) and the users with test addresses.
add_action('wp_ajax_pgfes_state', function () {
    pgfes_admin_only();
    global $wpdb;

    $out = ['attachments' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment'"), 'users' => []];

    $title = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
    if ($title)
    {
        $id = (int) $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'mec-events' AND post_title = %s ORDER BY ID DESC LIMIT 1", $title));
        $post = $id ? get_post($id) : null;
        if ($post)
        {
            clean_post_cache($id);
            $post = get_post($id);
            $out['event'] = [
                'id' => $id, 'status' => $post->post_status, 'author' => (int) $post->post_author,
                'guest_email' => get_post_meta($id, 'fes_guest_email', true),
                'guest_flag' => (bool) get_post_meta($id, 'mec_fes_guest_submission', true),
            ];
        }
    }

    foreach (get_users(['search' => '*@example.test', 'search_columns' => ['user_email']]) as $user)
    {
        $out['users'][$user->user_email] = ['id' => (int) $user->ID, 'roles' => array_values($user->roles)];
    }

    wp_send_json($out);
});

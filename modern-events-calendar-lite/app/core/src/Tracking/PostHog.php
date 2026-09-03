<?php

namespace MEC\Tracking;

/**
 * Lightweight, dependency-free PostHog tracking.
 *
 * Sends product-analytics events to PostHog's public capture endpoint using
 * WordPress's HTTP API (wp_remote_post) so we avoid Composer / the posthog-php
 * SDK, which requires PHP 8.2 while MEC still supports PHP 5.6.
 *
 * The project token is a PUBLIC key (phc_...) and is read from the
 * MEC_POSTHOG_API_KEY constant (define it in wp-config.php) or the
 * `mec_posthog_api_key` filter. Nothing is sent when no key is configured.
 */
class PostHog
{
    /**
     * Default US Cloud ingestion host.
     */
    const DEFAULT_HOST = 'https://us.i.posthog.com';

    /**
     * Bundled PostHog project token (public phc_ write key) shipped with the
     * plugin so tracking works on every install without per-site config.
     * This is PUBLIC by design (same key that ships in JS/mobile bundles);
     * it can only ingest events, not read data. Override per-site via the
     * MEC_POSTHOG_API_KEY constant or the `mec_posthog_api_key` filter.
     */
    const DEFAULT_API_KEY = 'phc_mMkTxML4n4W8QRTKx3UMJjs3Rm9zQXSCXuH2f9pacZn6';

    /**
     * Option key storing the anonymous per-install ID (used as distinct_id so
     * installs don't collide the way raw WordPress user IDs would).
     */
    const INSTALL_ID_OPTION = 'mec_posthog_install_id';

    /**
     * Option key storing the user's tracking-consent choice. Empty until the
     * consent popup is answered; then 'accepted' or 'declined'.
     */
    const CONSENT_OPTION = 'mec_posthog_consent';

    /**
     * Settings whose value changes we track individually (filterable). An
     * on->off flip here is a strong "feature abandoned" (churn) signal.
     *
     * @var array
     */
    protected $watch_settings = array(
        'booking_status', 'wc_status', 'mec_cart_status', 'fes_guest_status',
        'auto_emails_module_status', 'sms_status',
    );

    /**
     * Snapshot of the settings array taken just before a save is written,
     * used to detect which specific settings changed.
     *
     * @var array
     */
    protected $old_settings = array();

    /**
     * Register hooks.
     *
     * @return void
     */
    public function init()
    {
        // Snapshot the previous settings before the write (both hooks fire only
        // inside MEC_main::save_options()), so we can diff specific keys.
        add_action('mec_save_options', array($this, 'snapshot_old'), 1);

        // Fired right after update_option('mec_options', ...) in MEC_main::save_options().
        add_action('mec_saved_options', array($this, 'track_settings_saved'), 10, 1);
    }

    /**
     * Capture the settings array as it is right before the save is persisted.
     *
     * @return void
     */
    public function snapshot_old()
    {
        $opt = get_option('mec_options', array());
        $this->old_settings = (is_array($opt) && isset($opt['settings']) && is_array($opt['settings'])) ? $opt['settings'] : array();
    }

    /**
     * Track a "settings saved" event.
     *
     * @param array $final The final MEC options array that was saved.
     * @return void
     */
    public function track_settings_saved($final)
    {
        $settings = (is_array($final) && isset($final['settings']) && is_array($final['settings'])) ? $final['settings'] : array();

        // Detect which watched settings changed (old snapshot vs new).
        $watch = apply_filters('mec_posthog_watch_settings', $this->watch_settings);
        $changes = array();
        foreach ($watch as $key)
        {
            $before = isset($this->old_settings[$key]) ? $this->old_settings[$key] : null;
            $after  = isset($settings[$key]) ? $settings[$key] : null;
            if ((string) $before !== (string) $after)
            {
                $changes[$key] = array('from' => $before, 'to' => $after);
            }
        }

        // Cheap config snapshot (flags only, no heavy DB queries).
        $collector = new Collector();
        $flags = $collector->feature_flags();

        $properties = array_merge($flags, array(
            'module_count'     => $collector->module_count(),
            'enabled_gateways' => $collector->enabled_gateways(),
            'changed_settings' => $changes ? array_keys($changes) : null,
        ));

        // Keep the current config on the person too.
        $set = array_merge($flags, array('module_count' => $collector->module_count()));

        $this->capture('mec_settings_saved', $properties, $set);

        // Dedicated event when Booking is specifically toggled on/off, so
        // "how many installs enabled/disabled booking" is a clean trend.
        if (isset($changes['booking_status']))
        {
            $this->capture('mec_booking_toggled', array(
                'enabled' => (int) $changes['booking_status']['to'],
            ));
        }
    }

    /**
     * Send an event to PostHog.
     *
     * @param string $event      Event name.
     * @param array  $properties Event properties.
     * @param array  $set        Extra person properties merged into $set.
     * @return void
     */
    public function capture($event, $properties = array(), $set = array())
    {
        $api_key = $this->get_api_key();
        if (!$api_key) return;

        // Opt-in gate: only send once the user has accepted tracking.
        if (get_option(self::CONSENT_OPTION) !== 'accepted' && !apply_filters('mec_posthog_force', false)) return;

        // Common properties attached to every event.
        global $wp_version;
        $admin_email = get_option('admin_email');

        // Person properties: attach email/site (and any caller-supplied config)
        // to the PostHog person so it is searchable and segmentable, while
        // distinct_id stays the stable install UUID.
        $person = array_merge(array('email' => $admin_email, 'site_url' => home_url()), (array) $set);

        $properties = array_merge(array(
            'plugin_version' => defined('MEC_VERSION') ? MEC_VERSION : null,
            'wp_version'     => isset($wp_version) ? $wp_version : null,
            'php_version'    => PHP_VERSION,
            'site_url'       => home_url(),
            'admin_email'    => $admin_email ?: null,
            '$set'           => $person,
        ), $properties);

        $body = array(
            'api_key'     => $api_key,
            'event'       => $event,
            'distinct_id' => $this->get_install_id(),
            'properties'  => array_filter($properties, function ($value) {
                return !is_null($value);
            }),
        );

        // Blocking + logging while debugging (so events are easy to verify locally);
        // fire-and-forget otherwise so tracking never slows down a settings save.
        $blocking = apply_filters('mec_posthog_blocking', (defined('WP_DEBUG') && WP_DEBUG), $event);

        $response = wp_remote_post($this->get_host() . '/i/v0/e/', array(
            'headers'  => array('Content-Type' => 'application/json'),
            'body'     => wp_json_encode($body),
            'timeout'  => 5,
            'blocking' => (bool) $blocking,
        ));

        if ($blocking && defined('WP_DEBUG') && WP_DEBUG)
        {
            if (is_wp_error($response)) error_log('MEC PostHog: ' . $response->get_error_message());
            else error_log('MEC PostHog: HTTP ' . wp_remote_retrieve_response_code($response) . ' - ' . wp_remote_retrieve_body($response));
        }
    }

    /**
     * The PostHog project API key (public phc_ token). Falls back to the
     * bundled default so every install tracks to the same project.
     *
     * @return string
     */
    protected function get_api_key()
    {
        $key = defined('MEC_POSTHOG_API_KEY') ? MEC_POSTHOG_API_KEY : self::DEFAULT_API_KEY;

        return (string) apply_filters('mec_posthog_api_key', $key);
    }

    /**
     * A stable, anonymous per-install identifier used as the PostHog
     * distinct_id, generated once and stored in the options table. This keeps
     * each site a distinct PostHog person instead of colliding on WordPress
     * user IDs (which repeat across every install).
     *
     * @return string
     */
    protected function get_install_id()
    {
        $id = get_option(self::INSTALL_ID_OPTION);
        if (!$id)
        {
            $id = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : md5(home_url() . uniqid('', true));
            update_option(self::INSTALL_ID_OPTION, $id, false);
        }

        return $id;
    }

    /**
     * The PostHog ingestion host (no trailing slash).
     *
     * @return string
     */
    protected function get_host()
    {
        $host = defined('MEC_POSTHOG_HOST') ? MEC_POSTHOG_HOST : self::DEFAULT_HOST;

        return untrailingslashit(apply_filters('mec_posthog_host', $host));
    }
}

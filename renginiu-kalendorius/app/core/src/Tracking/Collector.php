<?php

namespace MEC\Tracking;

/**
 * Gathers MEC product-usage data for analytics.
 *
 * Pure data collection: every method returns an array and sends nothing. The
 * PostHog transport (PostHog::capture) and the weekly Snapshot job consume
 * these arrays. Nothing here reads secrets, customer PII, or event content.
 */
class Collector
{
    /**
     * Cached MEC settings sub-array.
     *
     * @var array
     */
    protected $settings;

    /**
     * Cached raw mec_options (settings + gateways + …).
     *
     * @var array
     */
    protected $options;

    /**
     * Map of gateway numeric id (in mec_options['gateways']) => friendly name.
     *
     * @var array
     */
    protected static $gateway_ids = array(
        1 => 'pay_locally',
        2 => 'paypal_express',
        3 => 'paypal_credit_card',
        4 => 'free',
        5 => 'stripe',
        6 => 'woocommerce',
        7 => 'stripe_connect',
        8 => 'bank_transfer',
        9 => 'paypal_standard',
    );

    /**
     * On/off setting keys we report, grouped only for readability.
     *
     * @var array
     */
    protected static $flag_keys = array(
        // Tier 1 / core
        'booking_status', 'wc_status', 'mec_cart_status', 'fes_guest_status',
        // Tier 2 modules
        'speakers_status', 'sponsors_status', 'organizers_status', 'countdown_status',
        'google_maps_status', 'weather_module_status', 'qrcode_module_status', 'local_time_module_status',
        'event_gallery_status', 'social_network_status', 'next_event_module_status', 'export_module_status',
        'progress_bar_status', 'banner_status', 'faq_status', 'trailer_url_status',
        // Tier 4 communications / value-add
        'auto_emails_module_status', 'sms_status', 'certificate_status',
        // Tier 5 environmental (security / dev)
        'restful_api_status', 'google_recaptcha_status', 'mtcaptcha_status',
        // Tier 3 marketing (MEC's own settings-driven integrations)
        'mchimp_status', 'campm_status', 'mailerlite_status', 'constantcontact_status',
        'active_campaign_status', 'aweber_status', 'mailpoet_status', 'sendfox_status',
        // Tier 3 community / LMS (MEC settings flags)
        'bp_status', 'ld_status', 'pmp_status',
    );

    /**
     * Subset of flag keys counted as "modules" for the power-user signal.
     *
     * @var array
     */
    protected static $module_keys = array(
        'speakers_status', 'sponsors_status', 'organizers_status', 'countdown_status',
        'google_maps_status', 'weather_module_status', 'qrcode_module_status', 'local_time_module_status',
        'event_gallery_status', 'social_network_status', 'next_event_module_status', 'export_module_status',
        'progress_bar_status', 'banner_status', 'faq_status', 'trailer_url_status',
    );

    public function __construct()
    {
        // Read fresh from the option (update_option refreshes WP's cache), rather
        // than \MEC\Settings\Settings which caches mec_options at construction and
        // can be stale right after a save.
        $this->options = get_option('mec_options', array());
        if (!is_array($this->options)) $this->options = array();

        $this->settings = (isset($this->options['settings']) && is_array($this->options['settings'])) ? $this->options['settings'] : array();
    }

    /**
     * All tracked on/off flags as ints (0/1).
     *
     * @return array
     */
    public function feature_flags()
    {
        $flags = array();
        foreach (self::$flag_keys as $key)
        {
            $flags[$key] = (isset($this->settings[$key]) && $this->settings[$key]) ? 1 : 0;
        }

        return $flags;
    }

    /**
     * How many Tier-2 modules are enabled (power-user signal).
     *
     * @return int
     */
    public function module_count()
    {
        $count = 0;
        foreach (self::$module_keys as $key)
        {
            if (isset($this->settings[$key]) && $this->settings[$key]) $count++;
        }

        return $count;
    }

    /**
     * Enabled payment gateways (names). No credentials.
     *
     * @return array List of enabled gateway names.
     */
    public function enabled_gateways()
    {
        $enabled = array();
        $options = $this->get_gateways_options();
        foreach (self::$gateway_ids as $id => $name)
        {
            if (isset($options[$id]['status']) && $options[$id]['status']) $enabled[] = $name;
        }

        return $enabled;
    }

    /**
     * Per-gateway transaction counts across all bookings (behavioral).
     *
     * @return array name => count
     */
    public function gateway_transactions()
    {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT COUNT(meta_id) AS c, meta_value AS gateway FROM {$wpdb->postmeta} WHERE meta_key = 'mec_gateway' GROUP BY meta_value",
            ARRAY_A
        );

        $out = array();
        if (is_array($rows))
        {
            foreach ($rows as $row)
            {
                // Meta value is the gateway class name, e.g. MEC_gateway_stripe.
                $name = str_replace('MEC_gateway_', '', (string) $row['gateway']);
                if ($name === '') $name = 'unknown';
                $out[$name] = (int) $row['c'];
            }
        }

        return $out;
    }

    /**
     * External plugin / theme integrations present on the site.
     *
     * @return array key => 0/1
     */
    public function integrations()
    {
        $theme = wp_get_theme();

        return array(
            'woocommerce'  => class_exists('WooCommerce') ? 1 : 0,
            'elementor'    => did_action('elementor/loaded') ? 1 : 0,
            'divi'         => ($theme && $theme->get_template() === 'Divi') ? 1 : 0,
            'beaver'       => class_exists('FLBuilder') ? 1 : 0,
            'wpbakery'     => function_exists('vc_map') ? 1 : 0,
            'kingcomposer' => function_exists('kc_add_map') ? 1 : 0,
            'wpml'         => class_exists('SitePress') ? 1 : 0,
            'polylang'     => function_exists('pll_default_language') ? 1 : 0,
            'buddypress'   => (function_exists('buddypress') || class_exists('BuddyPress') || function_exists('bp_is_active') || class_exists('BuddyBoss')) ? 1 : 0,
            'learndash'    => defined('LEARNDASH_VERSION') ? 1 : 0,
            'pmpro'        => defined('PMPRO_VERSION') ? 1 : 0,
            'acf'          => class_exists('ACF') ? 1 : 0,
        );
    }

    /**
     * Environment / technical signals.
     *
     * @return array
     */
    public function environment()
    {
        global $wp_version;
        $theme = wp_get_theme();

        return array(
            'php_version'  => PHP_VERSION,
            'wp_version'   => isset($wp_version) ? $wp_version : null,
            'mec_version'  => defined('MEC_VERSION') ? MEC_VERSION : null,
            'multisite'    => (function_exists('is_multisite') && is_multisite()) ? 1 : 0,
            'multilingual' => $this->main() && $this->main()->is_multilingual() ? 1 : 0,
            'theme'        => $theme ? $theme->get('Name') : null,
            'locale'       => get_locale(),
        );
    }

    /**
     * Behavioral counts (heavier queries) — for the weekly snapshot only.
     *
     * @return array
     */
    public function behavior()
    {
        return array(
            'bookings_30d'     => $this->count_confirmed_bookings_30d(),
            'events_published' => $this->count_published_events(),
            'fes_events'       => $this->count_fes_events(),
            'gateway_txns'     => $this->gateway_transactions(),
        );
    }

    /**
     * Confirmed bookings created in the last 30 days.
     *
     * @return int
     */
    protected function count_confirmed_bookings_30d()
    {
        $main = $this->main();
        if (!$main) return 0;

        $q = new \WP_Query(array(
            'post_type'        => $main->get_book_post_type(),
            'post_status'      => array('publish', 'future', 'pending', 'private'),
            'posts_per_page'   => 1,
            'fields'           => 'ids',
            'no_found_rows'    => false,
            'date_query'       => array(array('after' => '30 days ago')),
            'meta_query'       => array(array('key' => 'mec_confirmed', 'value' => 1, 'compare' => '=')),
        ));

        return (int) $q->found_posts;
    }

    /**
     * Published events.
     *
     * @return int
     */
    protected function count_published_events()
    {
        $main = $this->main();
        if (!$main) return 0;

        $counts = wp_count_posts($main->get_main_post_type());

        return isset($counts->publish) ? (int) $counts->publish : 0;
    }

    /**
     * Events submitted through the Frontend Event Submission form.
     *
     * @return int
     */
    protected function count_fes_events()
    {
        $main = $this->main();
        if (!$main) return 0;

        $q = new \WP_Query(array(
            'post_type'        => $main->get_main_post_type(),
            'post_status'      => 'any',
            'posts_per_page'   => 1,
            'fields'           => 'ids',
            'no_found_rows'    => false,
            'meta_query'       => array(array('key' => 'mec_created_by_fes', 'value' => 1, 'compare' => '=')),
        ));

        return (int) $q->found_posts;
    }

    /**
     * Configured SMS provider name (never credentials). MEC currently ships Twilio.
     *
     * @return string|null
     */
    public function sms_provider()
    {
        if (empty($this->settings['sms_status'])) return null;

        return apply_filters('mec_posthog_sms_provider', 'twilio');
    }

    /**
     * Raw gateways options array from mec_options.
     *
     * @return array
     */
    protected function get_gateways_options()
    {
        return (isset($this->options['gateways']) && is_array($this->options['gateways'])) ? $this->options['gateways'] : array();
    }

    /**
     * The MEC_main instance.
     *
     * @return \MEC_main|null
     */
    protected function main()
    {
        if (class_exists('\MEC\Base') && method_exists('\MEC\Base', 'get_main')) return \MEC\Base::get_main();

        return null;
    }
}

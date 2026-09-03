<?php

namespace MEC\Tracking;

/**
 * Weekly usage snapshot.
 *
 * Sends a periodic `mec_usage_snapshot` event carrying feature flags,
 * integrations, environment, and behavioral counts (bookings, events, gateway
 * transactions, FES submissions). Scheduled daily via WP-Cron but throttled to
 * run at most once per week, so it stays cheap and resilient to missed runs.
 * Consent is enforced downstream by PostHog::capture().
 */
class Snapshot
{
    const HOOK = 'mec_posthog_snapshot';
    const LAST_RUN_OPTION = 'mec_posthog_last_snapshot';

    /**
     * Register hooks.
     *
     * @return void
     */
    public function init()
    {
        // Runs in cron context (Base boots on every request, including wp-cron.php).
        add_action(self::HOOK, array($this, 'run'));

        // Ensure the schedule exists whenever an admin loads the dashboard.
        add_action('admin_init', array($this, 'maybe_schedule'));

        // Clean up on plugin deactivation.
        if (defined('MEC_ABSPATH') && defined('MEC_FILENAME'))
        {
            register_deactivation_hook(MEC_ABSPATH . MEC_FILENAME, array($this, 'unschedule'));
        }
    }

    /**
     * Ensure the daily cron event is scheduled.
     *
     * @return void
     */
    public function maybe_schedule()
    {
        if (!wp_next_scheduled(self::HOOK))
        {
            wp_schedule_event(time(), 'daily', self::HOOK);
        }
    }

    /**
     * Remove the scheduled event.
     *
     * @return void
     */
    public function unschedule()
    {
        wp_clear_scheduled_hook(self::HOOK);
    }

    /**
     * Build and send the usage snapshot, throttled to once per interval.
     *
     * @return void
     */
    public function run()
    {
        $interval = (int) apply_filters('mec_posthog_snapshot_interval', WEEK_IN_SECONDS);
        $last     = (int) get_option(self::LAST_RUN_OPTION, 0);
        if ($last && (time() - $last) < $interval) return;

        $collector = new Collector();

        $flags = $collector->feature_flags();
        $env   = $collector->environment();

        $properties = array_merge($flags, $env, array(
            'module_count'     => $collector->module_count(),
            'enabled_gateways' => $collector->enabled_gateways(),
            'sms_provider'     => $collector->sms_provider(),
            'integrations'     => $collector->integrations(),
        ), $collector->behavior());

        // Person properties: current config state so each install is segmentable.
        $set = array_merge($flags, $env, array(
            'module_count'     => $collector->module_count(),
            'enabled_gateways' => $collector->enabled_gateways(),
            'integrations'     => $collector->integrations(),
        ));

        (new PostHog())->capture('mec_usage_snapshot', $properties, $set);

        update_option(self::LAST_RUN_OPTION, time(), false);
    }
}

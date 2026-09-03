<?php

namespace MEC\Tracking;

/**
 * Tracking-consent popup for MEC admin pages.
 *
 * Shows a one-time modal on MEC backend pages asking the user to accept or
 * decline anonymous usage tracking. The choice is stored in the
 * PostHog::CONSENT_OPTION option; the popup is never shown again once answered,
 * and PostHog::capture() only sends events when the choice is 'accepted'.
 */
class Consent
{
    /**
     * Register hooks.
     *
     * @return void
     */
    public function init()
    {
        // Must be registered on every request (admin-ajax.php does not run admin_footer).
        add_action('wp_ajax_mec_save_tracking_consent', array($this, 'save_consent'));

        // Render the popup in the admin footer on MEC backend pages.
        add_action('admin_footer', array($this, 'maybe_render'));
    }

    /**
     * Whether the consent popup should be shown on the current page.
     *
     * @return bool
     */
    protected function should_show()
    {
        // Already answered (accepted or declined) -> never show again.
        if (get_option(PostHog::CONSENT_OPTION)) return false;

        // Only for users who can manage MEC settings.
        if (!current_user_can('mec_settings') && !current_user_can('administrator')) return false;

        // Only on MEC backend pages (reuse MEC's own asset gate).
        $factory = \MEC::getInstance('app.libraries.factory');
        if (!$factory || !$factory->should_include_assets('backend')) return false;

        return (bool) apply_filters('mec_posthog_show_consent', true);
    }

    /**
     * Output the consent popup markup + inline styles/script.
     *
     * @return void
     */
    public function maybe_render()
    {
        if (!$this->should_show()) return;

        $privacy_url = apply_filters('mec_posthog_privacy_url', 'https://webnus.net/privacy-policy/');
        ?>
        <div id="mec_posthog_consent_overlay" style="position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;background:rgba(15,23,42,.55);">
            <div id="mec_posthog_consent_popup" role="dialog" aria-modal="true" aria-labelledby="mec_posthog_consent_title" style="max-width:460px;width:calc(100% - 40px);background:#fff;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,.3);padding:28px;font-size:14px;line-height:1.6;color:#334155;">
                <h2 id="mec_posthog_consent_title" style="margin:0 0 12px;font-size:19px;color:#0f172a;"><?php echo esc_html__('Help improve Modern Events Calendar', 'modern-events-calendar-lite'); ?></h2>
                <p style="margin:0 0 12px;">
                    <?php echo esc_html__('Opt-in to share non-sensitive usage data (only feature usage) to help us improve user experience and guide future development.', 'modern-events-calendar-lite'); ?>
                </p>
                <p style="margin:0 0 16px;">
                    <strong><?php echo esc_html__('Privacy first:', 'modern-events-calendar-lite'); ?></strong>
                    <?php echo esc_html__('Your data is strictly for internal development and will never be sold or used for ads.', 'modern-events-calendar-lite'); ?>
                    <a href="<?php echo esc_url($privacy_url); ?>" target="_blank" rel="noopener"><?php echo esc_html__('Privacy Policy', 'modern-events-calendar-lite'); ?></a>
                </p>
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="button" id="mec_posthog_consent_decline"><?php echo esc_html__('Decline', 'modern-events-calendar-lite'); ?></button>
                    <button type="button" class="button button-primary" id="mec_posthog_consent_accept"><?php echo esc_html__('Accept', 'modern-events-calendar-lite'); ?></button>
                </div>
            </div>
        </div>
        <script>
        (function ($) {
            function mecConsentSend(choice) {
                $('#mec_posthog_consent_accept, #mec_posthog_consent_decline').prop('disabled', true);
                $.post(mec_admin_localize.ajax_url, {
                    action: 'mec_save_tracking_consent',
                    _wpnonce: mec_admin_localize.ajax_nonce,
                    choice: choice
                }).always(function () {
                    $('#mec_posthog_consent_overlay').remove();
                });
            }
            $(document).on('click', '#mec_posthog_consent_accept', function () { mecConsentSend('accepted'); });
            $(document).on('click', '#mec_posthog_consent_decline', function () { mecConsentSend('declined'); });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * AJAX handler: persist the consent choice.
     *
     * @return void
     */
    public function save_consent()
    {
        $wpnonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : null;
        if (!trim((string) $wpnonce) || !wp_verify_nonce($wpnonce, 'mec_settings_nonce')) wp_send_json(array('success' => 0, 'code' => 'NONCE_IS_INVALID'));

        if (!current_user_can('mec_settings') && !current_user_can('administrator')) wp_send_json(array('success' => 0, 'code' => 'ADMIN_ONLY'));

        $choice = isset($_REQUEST['choice']) ? sanitize_text_field($_REQUEST['choice']) : '';
        if (!in_array($choice, array('accepted', 'declined'), true)) wp_send_json(array('success' => 0, 'code' => 'INVALID_CHOICE'));

        update_option(PostHog::CONSENT_OPTION, $choice);
        update_option('mec_posthog_consent_time', current_time('mysql'));

        // Fire an immediate opt-in event so the person exists in PostHog right away.
        if ($choice === 'accepted') (new PostHog())->capture('mec_tracking_opted_in');

        wp_send_json(array('success' => 1, 'choice' => $choice));
    }
}

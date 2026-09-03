<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * @author Webnus <info@webnus.net>
 */
class MEC_feature_report extends MEC_base
{
    /**
     * @var MEC_factory
     */
    private $factory;

    /**
     * @var MEC_db
     */
    private $db;

    /**
     * @var MEC_main
     */
    private $main;

    private $settings;
    private $ml_settings;

    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct()
    {
        // Import MEC Factory
        $this->factory = $this->getFactory();

        // Import MEC DB
        $this->db = $this->getDB();

        // Import MEC Main
        $this->main = $this->getMain();

        // MEC Settings
        $this->settings = $this->main->get_settings();

        // MEC Multilingual Settings
        $this->ml_settings = $this->main->get_ml_settings();
    }

    /**
     * Initialize search feature
     * @author Webnus <info@webnus.net>
     */
    public function init()
    {
        $this->factory->action('admin_menu', [$this, 'menu'], 11);

        // Close Custom Text Notification
        $this->factory->action('wp_ajax_report_event_dates', [$this, 'report_event_dates']);

        // Event Attendees
        $this->factory->action('wp_ajax_mec_attendees', [$this, 'attendees']);

        // Selective Email
        $this->factory->action('wp_ajax_mec_mass_email', [$this, 'mass_email']);

        // Mass Action
        $this->factory->action('wp_ajax_mec_report_mass', [$this, 'mass_actions']);

        // Export & Purge (admin-post)
        $this->factory->action('admin_post_mec_export_purge', [$this, 'export_purge']);
    }

    public function menu()
    {
        if (isset($this->settings['booking_status']) && $this->settings['booking_status'])
        {
            add_submenu_page('mec-intro', esc_html__('MEC - Report', 'modern-events-calendar-lite'), esc_html__('Report', 'modern-events-calendar-lite'), 'mec_report', 'MEC-report', [$this, 'report']);
        }
    }

    /**
     * Show report page
     * @return void
     * @author Webnus <info@webnus.net>
     */
    public function report()
    {
        $path = MEC::import('app.features.report.tpl', true, true);

        ob_start();
        include $path;
        do_action('mec_display_report_page', $path);
        echo MEC_kses::full(ob_get_clean());
    }

    /**
     * Handle Export & Purge historical bookings
     */
    public function export_purge()
    {
        // Permissions
        if (!current_user_can('mec_report')) wp_die(esc_html__('You do not have permission.', 'modern-events-calendar-lite'));

        // Validate Nonce
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '';
        if (!wp_verify_nonce($nonce, 'mec_export_purge')) wp_die(esc_html__('Invalid request.', 'modern-events-calendar-lite'));

        $cutoff_raw = isset($_POST['mec_export_purge_cutoff']) ? sanitize_text_field($_POST['mec_export_purge_cutoff']) : '';
        $emails_raw = isset($_POST['mec_export_purge_emails']) ? sanitize_text_field($_POST['mec_export_purge_emails']) : '';

        if (!$cutoff_raw) wp_die(esc_html__('Please provide a cutoff date.', 'modern-events-calendar-lite'));

        $cutoff_ts = strtotime($cutoff_raw);
        if (!$cutoff_ts) wp_die(esc_html__('Invalid cutoff date.', 'modern-events-calendar-lite'));

        $cutoff_ts = strtotime('tomorrow', $cutoff_ts) - 1;

        $db = $this->getDB();
        $rows = $db->select("SELECT DISTINCT `booking_id` FROM `#__mec_bookings` WHERE `timestamp` < '" . esc_sql($cutoff_ts) . "'");
        $booking_post_ids = [];
        foreach ((array) $rows as $r)
        {
            if (isset($r->booking_id)) $booking_post_ids[] = (int) $r->booking_id;
        }

        $booking_post_ids = array_values(array_unique(array_filter($booking_post_ids)));

        $csv_rows = [];
        if (!empty($booking_post_ids))
        {
            $book_feature = new MEC_feature_books();
            $rows_all = $book_feature->csvexcel($booking_post_ids);

            $header = $rows_all[0] ?? [];
            $start_col_index = null;
            foreach ($header as $idx => $label)
            {
                if (strip_tags($label) === esc_html__('Start Date & Time', 'modern-events-calendar-lite'))
                {
                    $start_col_index = $idx;
                    break;
                }
            }

            if ($start_col_index === null) $start_col_index = 2;

            $csv_rows[] = $header;
            for ($i = 1; $i < count($rows_all); $i++)
            {
                $row = $rows_all[$i];
                $start_str = $row[$start_col_index] ?? '';
                $start_ts = $start_str ? strtotime($start_str) : 0;
                if ($start_ts && $start_ts < $cutoff_ts) $csv_rows[] = $row;
            }
        }

        if (count($csv_rows) <= 1)
        {
            $url = $this->main->add_qs_vars([
                'page' => 'MEC-report',
                'tab' => 'export_purge',
                'mec_export_purge_done' => 1,
                'mec_export_purge_count' => 0,
            ], $this->main->URL('backend') . 'admin.php');
            wp_safe_redirect($url);
            exit;
        }

        $upload_dir = wp_upload_dir();
        $subdir = '/mec-exports/' . date('Y/m');
        $dir = trailingslashit($upload_dir['basedir']) . ltrim($subdir, '/');
        if (!file_exists($dir)) wp_mkdir_p($dir);

        $filename = 'mec_export_purge_' . date('Ymd_His') . '.csv';
        $filepath = trailingslashit($dir) . $filename;

        $handle = fopen($filepath, 'w');
        if (!$handle) wp_die(esc_html__('Unable to write export file.', 'modern-events-calendar-lite'));

        // UTF-8 BOM
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($csv_rows as $r) fputcsv($handle, $r, "\t");
        fclose($handle);

        $fileurl = trailingslashit($upload_dir['baseurl']) . ltrim($subdir, '/') . '/' . $filename;

        $recipients = [];
        foreach (preg_split('/[,;\s]+/', $emails_raw) as $e)
        {
            $e = trim($e);
            if ($e && is_email($e)) $recipients[] = $e;
        }

        if (!empty($recipients))
        {
            add_filter('wp_mail_content_type', [$this->main, 'html_email_type']);
            $subject = sprintf(esc_html__('%s: Bookings Export up to %s', 'modern-events-calendar-lite'), get_bloginfo('name'), date_i18n(get_option('date_format'), $cutoff_ts));
            $body = sprintf(
                esc_html__('Attached is the CSV export of bookings prior to %s. A copy is saved here: %s', 'modern-events-calendar-lite'),
                date_i18n(get_option('date_format'), $cutoff_ts),
                esc_url($fileurl)
            );
            $headers = ['Content-Type: text/html; charset=UTF-8'];
            wp_mail($recipients, $subject, wpautop($body), $headers, [$filepath]);
            remove_filter('wp_mail_content_type', [$this->main, 'html_email_type']);
        }

        foreach($booking_post_ids as $booking_post_id)
        {
            wp_delete_post($booking_post_id, true);
        }

        $db->q("DELETE FROM `#__mec_bookings` WHERE `timestamp` < '" . esc_sql($cutoff_ts) . "'");

        // Redirect with admin notice
        $count = max(0, count($csv_rows) - 1);
        $url = $this->main->add_qs_vars([
            'page' => 'MEC-report',
            'tab' => 'export_purge',
            'mec_export_purge_done' => 1,
            'mec_export_purge_count' => $count,
            'mec_export_purge_url' => rawurlencode($fileurl),
        ], $this->main->URL('backend') . 'admin.php');

        wp_safe_redirect($url);
        exit;
    }

    /* Report Event Dates */
    public function report_event_dates()
    {
        // Current User is not Permitted
        if (!current_user_can('mec_report')) $this->main->response(['success' => 0, 'code' => 'ADMIN_ONLY']);
        if (!wp_verify_nonce(sanitize_text_field($_REQUEST['nonce']), 'mec_settings_nonce')) exit();

        $event_id = sanitize_text_field($_POST['event_id']);

        $booking_options = get_post_meta($event_id, 'mec_booking', true);
        $bookings_all_occurrences = $booking_options['bookings_all_occurrences'] ?? 0;

        if ($event_id != 'none')
        {
            $dates = $this->db->select("SELECT `tstart`, `tend` FROM `#__mec_dates` WHERE `post_id`='" . $event_id . "' LIMIT 100");
            $occurrence = count($dates) ? reset($dates)->tstart : '';

            $date_format = isset($this->ml_settings['booking_date_format1']) && trim($this->ml_settings['booking_date_format1'])
                ? $this->ml_settings['booking_date_format1']
                : 'Y-m-d';

            if (get_post_meta($event_id, 'mec_repeat_type', true) === 'custom_days') $date_format .= ' ' . get_option('time_format');

            echo '<select name="mec-report-event-dates" class="mec-reports-selectbox mec-reports-selectbox-dates" onchange="mec_event_attendees(' . esc_attr($event_id) . ', this.value);">';
            echo '<option value="none">' . esc_html__("Select Date", "mec") . '</option>';

            if ($bookings_all_occurrences)
            {
                echo '<option value="all">' . esc_html__("All", "mec") . '</option>';
            }

            foreach ($dates as $date)
            {
                $start = [
                    'date' => date('Y-m-d', $date->tstart),
                    'hour' => date('h', $date->tstart),
                    'minutes' => date('i', $date->tstart),
                    'ampm' => date('A', $date->tstart),
                ];

                $end = [
                    'date' => date('Y-m-d', $date->tend),
                    'hour' => date('h', $date->tend),
                    'minutes' => date('i', $date->tend),
                    'ampm' => date('A', $date->tend),
                ];

                echo '<option value="' . esc_attr($date->tstart) . '" ' . ($occurrence == $date->tstart ? 'class="selected-day"' : '') . '>' . strip_tags($this->main->date_label($start, $end, $date_format, ' - ', false)) . '</option>';
            }

            echo '</select>';
        }
        else
        {
            echo '';
        }

        wp_die();
    }

    public function attendees()
    {
        $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : 0;

        $occurrence = isset($_POST['occurrence']) ? sanitize_text_field($_POST['occurrence']) : null;
        $occurrence = explode(':', $occurrence)[0];

        if ($occurrence == 'all') $occurrence = strtotime('+100 years');
        else if ($occurrence == 'none') $occurrence = null;

        $attendees = $this->main->get_event_attendees($id, $occurrence);

        $html = '';
        if (count($attendees))
        {
            $html .= $this->main->get_attendees_table($attendees, $id, $occurrence);
            $email_button = '<p>' . esc_html__('If you want to send an email, first select your attendees and then click in the button below, please.', 'modern-events-calendar-lite') . '</p><button data-id="' . esc_attr($id) . '" onclick="mec_submit_event_email(' . esc_attr($id) . ');">' . esc_html__('Send Email', 'modern-events-calendar-lite') . '</button>';

            // Certificate
            if ($occurrence && isset($this->settings['certificate_status']) && $this->settings['certificate_status'])
            {
                $certificates = get_posts([
                    'post_type' => $this->main->get_certificate_post_type(),
                    'status' => 'publish',
                    'numberposts' => -1,
                    'orderby' => 'post_title',
                    'order' => 'ASC',
                ]);

                $certificate_options = '';
                foreach ($certificates as $certificate)
                {
                    $certificate_options .= '<option value="' . esc_attr($certificate->ID) . '">' . esc_html($certificate->post_title) . '</option>';
                }

                $email_button .= '<div class="mec-report-certificate-wrap">
                    <h3>' . esc_html__('Certificate', 'modern-events-calendar-lite') . '</h3>
                    <select id="certificate_select" name="certificate" title="' . esc_attr__('Certificate', 'modern-events-calendar-lite') . '">
                        <option value="">-----</option>
                        ' . $certificate_options . '
                    </select>
                    <button data-id="' . esc_attr($id) . '" onclick="mec_certificate_send();">' . esc_html__('Send Certificate', 'modern-events-calendar-lite') . '</button>
                    <div id="mec-certificate-message"></div>
                </div>';
            }
        }
        else
        {
            $html .= '<p>' . esc_html__("No Attendees Found!", 'modern-events-calendar-lite') . '</p>';
            $email_button = '';
        }

        echo json_encode(['html' => $html, 'email_button' => $email_button]);
        exit;
    }

    public function mass_email()
    {
        if (!wp_verify_nonce(sanitize_text_field($_REQUEST['nonce']), 'mec_settings_nonce')) exit();

        // Current User is not Permitted
        if (!current_user_can('mec_report')) $this->main->response(['success' => 0, 'code' => 'NO_ACCESS']);

        $mail_recipients_info = isset($_POST['mail_recipients_info']) ? trim(sanitize_text_field($_POST['mail_recipients_info']), ', ') : '';
        $mail_subject = isset($_POST['mail_subject']) ? sanitize_text_field($_POST['mail_subject']) : '';
        $mail_content = isset($_POST['mail_content']) ? MEC_kses::page($_POST['mail_content']) : '';
        $mail_copy = isset($_POST['mail_copy']) ? sanitize_text_field($_POST['mail_copy']) : 0;

        $render_recipients = array_unique(explode(',', $mail_recipients_info));
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        // Changing some sender email info.
        $notifications = $this->getNotifications();
        $notifications->mec_sender_email_notification_filter();

        // Send to Admin
        if ($mail_copy) $render_recipients[] = 'Admin:.:' . get_option('admin_email');

        // Set Email Type to HTML
        add_filter('wp_mail_content_type', [$this->main, 'html_email_type']);

        foreach ($render_recipients as $recipient)
        {
            $render_recipient = explode(':.:', $recipient);

            $to = isset($render_recipient[1]) ? trim($render_recipient[1]) : '';
            if (!trim($to)) continue;

            $message = $mail_content;
            $message = str_replace('%%name%%', (isset($render_recipient[0]) ? trim($render_recipient[0]) : ''), $message);

            $mail_arg = [
                'to' => $to,
                'subject' => $mail_subject,
                'message' => $message,
                'headers' => $headers,
                'attachments' => [],
            ];

            $mail_arg = apply_filters('mec_before_send_mass_email', $mail_arg, 'mass_email');

            // Send the mail
            wp_mail($mail_arg['to'], html_entity_decode(stripslashes($mail_arg['subject']), ENT_HTML5), wpautop(stripslashes($mail_arg['message'])), $mail_arg['headers'], $mail_arg['attachments']);
        }

        // Remove the HTML Email filter
        remove_filter('wp_mail_content_type', [$this->main, 'html_email_type']);

        wp_die(true);
    }

    public function mass_actions()
    {
        // Invalid Request
        if (!wp_verify_nonce(sanitize_text_field($_REQUEST['_wpnonce'] ?? ''), 'mec_report_mass')) $this->main->response(['success' => 0, 'code' => 'INVALID_NONCE']);

        // Current User is not Permitted
        if (!current_user_can('mec_report')) $this->main->response(['success' => 0, 'code' => 'NO_ACCESS']);

        $task = isset($_POST['task']) ? sanitize_text_field($_POST['task']) : 'suggest';
        $events = isset($_POST['events']) && is_array($_POST['events']) ? $_POST['events'] : [];

        // Invalid Events
        if (!count($events)) $this->main->response(['success' => 0, 'code' => 'INVALID_EVENTS']);

        // Suggest New Event
        if ($task === 'suggest')
        {
            // New Event to Suggest
            $new_event = isset($_POST['new_event']) ? sanitize_text_field($_POST['new_event']) : '';

            // Invalid Event
            if (!$new_event) $this->main->response(['success' => 0, 'code' => 'INVALID_EVENT']);

            // Notifications Library
            $notifications = $this->getNotifications();

            $attendees_count = 0;
            $sent = [];
            foreach ($events as $id)
            {
                $attendees = $this->main->get_event_attendees($id);
                foreach ($attendees as $attendee)
                {
                    $attendees_count++;

                    $email = $attendee['email'] ?? '';
                    if (!$email || in_array($email, $sent)) continue;

                    // Do not send multiple emails to same email
                    $sent[] = $email;

                    // Suggest the Event
                    $notifications->suggest_event([
                        'email' => $email,
                        'name' => $attendee['name'] ?? '',
                    ], $new_event, $attendee['book_id'] ?? '');
                }
            }

            $this->main->response(['success' => 1, 'code' => 'EMAILS_SENT', 'message' => sprintf(esc_html__('%s unique emails are sent successfully to %s attendees.', 'modern-events-calendar-lite'), count($sent), $attendees_count)]);
        }

        wp_die(true);
    }
}

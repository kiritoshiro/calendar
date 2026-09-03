<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC Import / Export class. Requires PHP >= 5.3 otherwise it doesn't activate
 * @author Webnus <info@webnus.net>
 */
class MEC_feature_ix extends MEC_base
{
    public $factory;
    public $main;
    public $db;
    public $action;
    public $ix;
    public $response;

    /**
     * Facebook App Access Token
     * @var string
     */
    private $fb_access_token = '';

    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct()
    {
        // Import MEC Factory
        $this->factory = $this->getFactory();

        // Import MEC Main
        $this->main = $this->getMain();

        // Import MEC DB
        $this->db = $this->getDB();
    }

    /**
     * Initialize IX feature
     * @author Webnus <info@webnus.net>
     */
    public function init()
    {
        // Disable Import / Export Feature if autoload feature is not exists
        if (!function_exists('spl_autoload_register')) return;

        $this->factory->action('admin_menu', [$this, 'menus'], 20);

        // Import APIs
        $this->factory->action('init', [$this, 'include_google_api']);
        $this->factory->action('init', [$this, 'include_meetup_api']);

        // MEC IX Action
        $mec_ix_action = isset($_GET['mec-ix-action']) ? sanitize_text_field($_GET['mec-ix-action']) : '';

        // Export All Events
        if ($mec_ix_action == 'export-events') $this->factory->action('init', [$this, 'export_all_events_do'], 9999);
        else if ($mec_ix_action == 'export-bookings') $this->factory->action('init', [$this, 'export_all_bookings_do'], 9999);
        else if ($mec_ix_action == 'google-calendar-export-get-token') $this->factory->action('init', [$this, 'g_calendar_export_get_token'], 9999);

        // AJAX Actions
        $this->factory->action('wp_ajax_mec_ix_add_to_g_calendar', [$this, 'g_calendar_export_do']);
        $this->factory->action('wp_ajax_mec_ix_g_calendar_authenticate', [$this, 'g_calendar_export_authenticate']);

        // Import XML File
        $this->factory->action('mec_import_file', [$this, 'import_do']);

        // Third Party Plugins
        $this->factory->action('wp_ajax_mec_ix_thirdparty_import', [$this, 'thirdparty_import_do']);
    }

    /**
     * Import Google API libraries
     * @author Webnus <info@webnus.net>
     */
    public function include_google_api()
    {
        if (class_exists('Google_Service_Calendar')) return;

        MEC::import('app.api.Google.autoload', false);
    }

    /**
     * Import Meetup API libraries
     * @author Webnus <info@webnus.net>
     */
    public function include_meetup_api()
    {
        if (class_exists('Meetup')) return;

        MEC::import('app.api.Meetup.meetup', false);
    }

    /**
     * Add the IX menu
     * @author Webnus <info@webnus.net>
     */
    public function menus()
    {
        $capability = current_user_can('administrator') ? 'manage_options' : 'mec_import_export';
        add_submenu_page('mec-intro', esc_html__('MEC - Import / Export', 'modern-events-calendar-lite'), esc_html__('Import / Export', 'modern-events-calendar-lite'), $capability, 'MEC-ix', [$this, 'ix']);
    }

    /**
     * Show content of Import / Export Menu
     * @return void
     * @author Webnus <info@webnus.net>
     */
    public function ix()
    {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';

        if ($tab == 'MEC-export') $this->ix_export();
        else if ($tab == 'MEC-sync') $this->ix_sync();
        else if ($tab == 'MEC-g-calendar-export') $this->ix_g_calendar_export();
        else if ($tab == 'MEC-f-calendar-import') $this->ix_f_calendar_import();
        else if ($tab == 'MEC-meetup-import') $this->ix_meetup_import();
        else if ($tab == 'MEC-import') $this->ix_import();
        else if ($tab == 'MEC-thirdparty') $this->ix_thirdparty();
        else if ($tab == 'MEC-test-data') $this->ix_test_data();
        else $this->ix_g_calendar_import();
    }

    /**
     * Show content of export tab
     * @return void
     * @author Webnus <info@webnus.net>
     */
    public function ix_export()
    {
        $path = MEC::import('app.features.ix.export', true, true);

        ob_start();
        include $path;
        echo MEC_kses::full(ob_get_clean());
    }

    /**
     * Show content of export tab
     * @return void
     * @author Webnus <info@webnus.net>
     */
    public function ix_sync()
    {
        // Current Action
        $this->action = isset($_POST['mec-ix-action']) ? sanitize_text_field($_POST['mec-ix-action']) : '';
        $this->ix = ((isset($_POST['ix']) and is_array($_POST['ix'])) ? array_map('sanitize_text_field', $_POST['ix']) : []);

        if ($this->action == 'save-sync-options')
        {
            // Save options
            $this->main->save_ix_options([
                'sync_g_import' => $this->ix['sync_g_import'] ?? 0,
                'sync_g_import_auto' => $this->ix['sync_g_import_auto'] ?? 0,
                'sync_g_export' => $this->ix['sync_g_export'] ?? 0,
                'sync_g_export_auto' => $this->ix['sync_g_export_auto'] ?? 0,
                'sync_g_export_attendees' => $this->ix['sync_g_export_attendees'] ?? 0,
                'sync_f_import' => $this->ix['sync_f_import'] ?? 0,
                'sync_meetup_import' => $this->ix['sync_meetup_import'] ?? 0,
                'sync_meetup_import_auto' => $this->ix['sync_meetup_import_auto'] ?? 0,
            ]);
        }

        $path = MEC::import('app.features.ix.sync', true, true);

        ob_start();
        include $path;
        echo MEC_kses::full(ob_get_clean());
    }

    /**
     * Show content of import tab
     * @return void
     * @author Webnus <info@webnus.net>
     */
    public function ix_import()
    {
        // Current Action
        $this->action = isset($_POST['mec-ix-action']) ? sanitize_text_field($_POST['mec-ix-action']) : '';
        $this->ix = ((isset($_POST['ix']) and is_array($_POST['ix'])) ? array_map('sanitize_text_field', $_POST['ix']) : []);

        $this->response = [];

        $nonce = (isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '');
        if (wp_verify_nonce($nonce, 'mec_import_start_upload'))
        {
            if (in_array($this->action, ['import-start-xml', 'import-start-ics'])) $this->response = $this->import_start();
            else if ($this->action == 'import-start-bookings') $this->response = $this->import_start_bookings();
            else if (!empty($this->action)) $this->response = apply_filters('mec_import_item_action', [], $this->action);
        }

        $path = MEC::import('app.features.ix.import', true, true);

        ob_start();
        include $path;
        echo MEC_kses::full(ob_get_clean());
    }

    public function import_start_bookings()
    {
        $feed_file = $_FILES['feed'];

        // File is not uploaded
        if (!isset($feed_file['name']) or trim($feed_file['name']) == '') return ['success' => 0, 'message' => esc_html__('Please upload a CSV file.', 'modern-events-calendar-lite')];

        // File name validation
        $name_ex = explode('.', $feed_file['name']);
        $name_end = end($name_ex);
        if ($name_end != 'csv') return ['success' => 0, 'message' => esc_html__('Please upload a CSV file.', 'modern-events-calendar-lite')];

        // Upload the File
        $upload_dir = wp_upload_dir();

        $target_path = $upload_dir['basedir'] . '/' . basename($feed_file['name']);
        $uploaded = move_uploaded_file($feed_file['tmp_name'], $target_path);

        // Error on Upload
        if (!$uploaded) return ['success' => 0, 'message' => esc_html__("File upload failed. Please check your file permissions and try again.", 'modern-events-calendar-lite')];

        if ($type = mime_content_type($target_path) and $type == 'text/x-php')
        {
            unlink($target_path);
            return ['success' => 0, 'message' => esc_html__("Please upload a CSV file.", 'modern-events-calendar-lite')];
        }

        $bookings = [];
        if (($h = fopen($target_path, 'r')) !== false)
        {
            // MEC Libraries
            $gateway = new MEC_gateway();
            $book = $this->getBook();

            $delimiters = [";" => 0, "," => 0, "\t" => 0, "|" => 0];

            $first = fgets($h);
            foreach ($delimiters as $delimiter => &$count)
            {
                $count = count(str_getcsv($first, $delimiter, '"', '\\'));
            }

            $separator = array_search(max($delimiters), $delimiters);
            rewind($h);

            $reg_fields = $this->main->get_reg_fields();
            $bfixed_fields = $this->main->get_bfixed_fields();

            $columns = [];
            $column_key = function ($labels) use (&$columns)
            {
                foreach ((array) $labels as $label)
                {
                    $key = array_search(strtolower(trim($label)), array_map('strtolower', $columns));
                    if ($key !== false) return $key;
                }

                return false;
            };
            $column_value = function ($data, $labels, $fallback = null) use ($column_key)
            {
                $key = $column_key($labels);
                if ($key !== false) return $data[$key] ?? '';

                return !is_null($fallback) ? ($data[$fallback] ?? '') : '';
            };
            $r = 0;
            while (($data = fgetcsv($h, 0, $separator, '"', '\\')) !== false)
            {
                $r++;

                $booking_id = $data[0] ?? '';
                if ($r === 1 && !is_numeric($booking_id))
                {
                    $columns = array_map(static function ($column)
                    {
                        return trim($column, "\xEF\xBB\xBF \t\n\r\0\x0B");
                    }, $data);
                    continue;
                }

                $event_title = $column_value($data, [esc_html__('Event', 'modern-events-calendar-lite'), 'Event'], 1);
                $event_id = post_exists($event_title, '', '', $this->main->get_main_post_type());

                // Event not Found
                if (!$event_id) continue;

                $tickets = get_post_meta($event_id, 'mec_tickets', true);
                if (!is_array($tickets)) $tickets = [];

                $ticket_id = null;
                $ticket_name = $column_value($data, [$this->main->m('ticket', esc_html__('Ticket', 'modern-events-calendar-lite')), esc_html__('Ticket', 'modern-events-calendar-lite'), 'Ticket'], 6);

                foreach ($tickets as $tid => $ticket)
                {
                    if (strtolower($ticket['name']) == strtolower($ticket_name))
                    {
                        $ticket_id = $tid;
                        break;
                    }
                }

                // Ticket ID not found!
                if (is_null($ticket_id)) continue;

                $ticket_count = max(1, (int) $column_value($data, [esc_html__('Seats', 'modern-events-calendar-lite'), 'Seats'], null));
                $transaction_id = $column_value($data, [esc_html__('Transaction ID', 'modern-events-calendar-lite'), 'Transaction ID'], 7);

                // Transaction Exists
                $transaction_exists = $book->get_transaction($transaction_id);
                if (is_array($transaction_exists) and count($transaction_exists)) continue;

                $start_datetime = $column_value($data, [esc_html__('Start Date & Time', 'modern-events-calendar-lite'), 'Start Date & Time'], 2);
                $end_datetime = $column_value($data, [esc_html__('End Date & Time', 'modern-events-calendar-lite'), 'End Date & Time'], 3);
                $name = $column_value($data, [esc_html__('Name', 'modern-events-calendar-lite'), 'Name'], 10);
                $email = $column_value($data, [esc_html__('Email', 'modern-events-calendar-lite'), 'Email'], 11);

                $confirmed_label = $column_value($data, [esc_html__('Confirmation', 'modern-events-calendar-lite'), 'Confirmation'], 13);
                if ($confirmed_label == esc_html__('Confirmed', 'modern-events-calendar-lite')) $confirmed = 1;
                else if ($confirmed_label == esc_html__('Rejected', 'modern-events-calendar-lite')) $confirmed = -1;
                else $confirmed = 0;

                $verified_label = $column_value($data, [esc_html__('Verification', 'modern-events-calendar-lite'), 'Verification'], 14);
                if ($verified_label == esc_html__('Verified', 'modern-events-calendar-lite')) $verified = 1;
                else if ($verified_label == esc_html__('Canceled', 'modern-events-calendar-lite')) $verified = -1;
                else $verified = 0;

                $other_dates_str = $column_value($data, [esc_html__('Other Dates', 'modern-events-calendar-lite'), 'Other Dates'], 15);
                $other_dates = [];

                if (trim($other_dates_str))
                {
                    $other_dates_ex1 = explode("\n", $other_dates_str);
                    foreach ($other_dates_ex1 as $other_date_ex1)
                    {
                        $other_date_ex2 = explode(' -> ', trim($other_date_ex1));
                        $other_dates[] = strtotime($other_date_ex2[0]) . ':' . strtotime($other_date_ex2[1]);
                    }
                }

                $main_date = strtotime($start_datetime) . ':' . strtotime($end_datetime);

                $all_dates = [];
                if (count($other_dates)) $all_dates = array_merge([$main_date], $other_dates);

                $ticket_variations = explode(',', $column_value($data, [esc_html__('Ticket Variation', 'modern-events-calendar-lite'), 'Ticket Variation'], 12));
                $variations = $this->main->ticket_variations($event_id, $ticket_id);

                $v = [];
                foreach ($variations as $vid => $variation)
                {
                    foreach ($ticket_variations as $ticket_variation)
                    {
                        $variation_ex = explode(':', $ticket_variation);
                        if (!isset($variation_ex[1])) continue;

                        $variation_name = $variation_ex[0];
                        $variation_count = trim($variation_ex[1], '() ');

                        if (strtolower($variation['title']) == strtolower($variation_name))
                        {
                            $v[$vid] = $variation_count;
                        }
                    }
                }

                if (!isset($bookings[$transaction_id]))
                {
                    $bookings[$transaction_id] = [
                        'tickets' => [],
                        'fields' => [],
                    ];
                }

                $reg = [];
                if (is_array($reg_fields) && count($reg_fields))
                {
                    foreach ($reg_fields as $regf_id => $reg_field)
                    {
                        if (!is_numeric($regf_id)) continue;

                        $reg_field_label = $reg_field['label'] ?? '';
                        if (!trim($reg_field_label)) continue;

                        $reg_field_type = $reg_field['type'] ?? '';
                        if (!trim($reg_field_type) || in_array($reg_field_type, ['mec_email', 'name'])) continue;

                        $reg_field_column_key = array_search($reg_field_label, $columns);
                        if ($reg_field_column_key === false) continue;

                        $reg[$regf_id] = $data[$reg_field_column_key] ?? '';
                    }
                }

                $bookings[$transaction_id]['tickets'][] = [
                    'email' => $email,
                    'name' => $name,
                    'variations' => $v,
                    'id' => $ticket_id,
                    'reg' => $reg,
                    'count' => $ticket_count,
                ];

                if (!isset($bookings[$transaction_id]['date'])) $bookings[$transaction_id]['date'] = $main_date;
                if (!isset($bookings[$transaction_id]['other_dates'])) $bookings[$transaction_id]['other_dates'] = $other_dates;
                if (!isset($bookings[$transaction_id]['all_dates'])) $bookings[$transaction_id]['all_dates'] = $all_dates;
                if (!isset($bookings[$transaction_id]['event_id'])) $bookings[$transaction_id]['event_id'] = $event_id;
                if (!isset($bookings[$transaction_id]['confirmed'])) $bookings[$transaction_id]['confirmed'] = $confirmed;
                if (!isset($bookings[$transaction_id]['verified'])) $bookings[$transaction_id]['verified'] = $verified;

                if (is_array($bfixed_fields) && count($bfixed_fields))
                {
                    foreach ($bfixed_fields as $bff_id => $bfixed_field)
                    {
                        if (!is_numeric($bff_id)) continue;

                        $bfixed_field_label = $bfixed_field['label'] ?? '';
                        if (!trim($bfixed_field_label)) continue;

                        $bfixed_field_column_key = array_search($bfixed_field_label, $columns);
                        if ($bfixed_field_column_key === false) continue;

                        $bookings[$transaction_id]['fields'][$bff_id] = $data[$bfixed_field_column_key] ?? '';
                    }
                }
            }

            fclose($h);

            // MEC User
            $u = $this->getUser();
            $disable_booking_confirmation = '__return_false';
            add_filter('mec_booking_confirmation', $disable_booking_confirmation);

            foreach ($bookings as $transaction_id => $transaction)
            {
                $event_id = $transaction['event_id'];
                $tickets = $transaction['tickets'];

                $event_tickets = get_post_meta($event_id, 'mec_tickets', true);
                if (!is_array($event_tickets)) $event_tickets = [];

                $raw_tickets = [];
                $raw_variations = [];

                foreach ($tickets as $ticket)
                {
                    $ticket_count = $ticket['count'] ?? 1;
                    if (!isset($raw_tickets[$ticket['id']])) $raw_tickets[$ticket['id']] = $ticket_count;
                    else $raw_tickets[$ticket['id']] += $ticket_count;

                    if (isset($ticket['variations']) and is_array($ticket['variations']) and count($ticket['variations']))
                    {
                        // Variations Per Ticket
                        if (!isset($raw_variations[$ticket['id']])) $raw_variations[$ticket['id']] = [];

                        foreach ($ticket['variations'] as $variation_id => $variation_count)
                        {
                            if (!trim($variation_count)) continue;

                            if (!isset($raw_variations[$ticket['id']][$variation_id])) $raw_variations[$ticket['id']][$variation_id] = $variation_count;
                            else $raw_variations[$ticket['id']][$variation_id] += $variation_count;
                        }
                    }
                }

                $attention_date = $transaction['date'] ?? '';
                $attention_times = explode(':', $attention_date);
                $date = date('Y-m-d H:i:s', trim($attention_times[0]));

                $other_dates = (isset($transaction['other_dates']) and is_array($transaction['other_dates'])) ? $transaction['other_dates'] : [];
                $all_dates = (isset($transaction['all_dates']) and is_array($transaction['all_dates'])) ? $transaction['all_dates'] : [];
                $timestamps = (isset($transaction['timestamps']) and is_array($transaction['timestamps'])) ? $transaction['timestamps'] : [$attention_date];

                // Calculate price of bookings
                $price_details = $book->get_price_details($raw_tickets, $event_id, $event_tickets, $raw_variations, $timestamps);

                $transaction['all_dates'] = $all_dates;
                $transaction['other_dates'] = $other_dates;
                $transaction['timestamps'] = $timestamps;
                $transaction['price_details'] = $price_details;
                $transaction['total'] = $price_details['total'];
                $transaction['discount'] = 0;
                $transaction['price'] = $price_details['total'];
                $transaction['payable'] = $price_details['payable'];
                $transaction['coupon'] = null;

                update_option($transaction_id, $transaction, 'no');

                $attendees = $transaction['tickets'] ?? [];

                $main_attendee = $attendees[0] ?? [];
                $name = $main_attendee['name'] ?? '';

                $ticket_ids = '';
                $attendees_info = [];

                foreach ($attendees as $i => $attendee)
                {
                    if (!is_numeric($i)) continue;

                    $ticket_ids .= $attendee['id'] . ',';
                    if (!array_key_exists($attendee['email'], $attendees_info)) $attendees_info[$attendee['email']] = ['count' => $attendee['count']];
                    else $attendees_info[$attendee['email']]['count'] = ($attendees_info[$attendee['email']]['count'] + $attendee['count']);
                }

                $ticket_ids = ',' . trim($ticket_ids, ', ') . ',';
                $user_id = $gateway->register_user($main_attendee);

                $book_subject = $name . ' - ' . ($main_attendee['email'] ?? $u->get($user_id)->user_email);
                $book_id = $book->add(
                    [
                        'post_author' => $user_id,
                        'post_type' => $this->main->get_book_post_type(),
                        'post_title' => $book_subject,
                        'post_date' => $date,
                        'attendees_info' => $attendees_info,
                        'mec_attendees' => $attendees,
                        'mec_gateway' => 'MEC_gateway',
                        'mec_gateway_label' => $gateway->title(),
                    ],
                    $transaction_id,
                    $ticket_ids
                );

                // Assign User
                $u->assign($book_id, $user_id);

                update_post_meta($book_id, 'mec_confirmed', $transaction['confirmed']);
                update_post_meta($book_id, 'mec_verified', $transaction['verified']);
            }

            remove_filter('mec_booking_confirmation', $disable_booking_confirmation);
        }

        // Delete File
        unlink($target_path);

        return ['success' => (count($bookings) ? 1 : 0), 'message' => (count($bookings) ? esc_html__('The bookings are imported successfully!', 'modern-events-calendar-lite') : esc_html__('No bookings found to import!', 'modern-events-calendar-lite'))];
    }

    public function import_start()
    {
        $feed_file = $_FILES['feed'];

        // File is not uploaded
        if (!isset($feed_file['name']) or trim($feed_file['name']) == '') return ['success' => 0, 'message' => esc_html__('Please upload the feed file.', 'modern-events-calendar-lite')];

        // File name validation
        $ex = explode('.', $feed_file['name']);
        $name_end = end($ex);
        if (!in_array($name_end, ['xml', 'ics'])) return ['success' => 0, 'message' => esc_html__('Please upload an XML or an ICS file.', 'modern-events-calendar-lite')];

        // File Type is not valid
        if (!isset($feed_file['type']) or !in_array(strtolower($feed_file['type']), ['text/xml', 'text/calendar'])) return ['success' => 0, 'message' => esc_html__('The file type should be XML or ICS.', 'modern-events-calendar-lite')];

        // Upload the file using WP upload handler for better compatibility on shared hostings.
        if (!function_exists('wp_handle_upload')) require_once ABSPATH . 'wp-admin/includes/file.php';

        $uploaded_file = wp_handle_upload($feed_file, ['test_form' => false, 'test_type' => false]);
        if (!is_array($uploaded_file) or isset($uploaded_file['error']) or !isset($uploaded_file['file']) or !file_exists($uploaded_file['file']))
        {
            return ['success' => 0, 'message' => esc_html__("File upload failed. Please check your file permissions and try again.", 'modern-events-calendar-lite')];
        }

        $target_path = $uploaded_file['file'];

        if ($type = mime_content_type($target_path) and $type == 'text/x-php')
        {
            unlink($target_path);
            return ['success' => 0, 'message' => esc_html__("Please upload an XML or an ICS file.", 'modern-events-calendar-lite')];
        }

        if ($type === 'text/calendar' and is_string($this->main->parse_ics($target_path)))
        {
            unlink($target_path);
            return ['success' => 0, 'message' => sprintf(__("The ICS file is not valid. Reported Error: %s", 'modern-events-calendar-lite'), '<strong>' . $this->main->parse_ics($target_path) . '</strong>')];
        }

        // Import
        do_action('mec_import_file', $target_path);

        // Delete File
        unlink($target_path);

        return ['success' => 1, 'message' => esc_html__('The events are imported successfully!', 'modern-events-calendar-lite')];
    }

    public function import_do($feed)
    {
        // Increase the resources
        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', 300);

        do_action('mec_custom_max_execution');

        $file = $this->getFile();
        $extension = $file->getExt($feed);

        /**
         * @var MEC_db $db
         */
        $db = $this->getDB();

        /**
         * @var MEC_main $main
         */
        $main = $this->getMain();

        // Settings
        $settings = $main->get_settings();

        // WP Upload Path
        $wp_upload_dir = wp_upload_dir();

        $posts = [];
        if (strtolower($extension) == 'xml')
        {
            $xml_string = str_replace(':i:', 'iii', $file->read($feed));
            $xml_string = str_replace(':fi:', 'fif', $xml_string);
            $xml_string = str_replace(':v:', 'vvv', $xml_string);

            $XML = simplexml_load_string($xml_string);
            if ($XML === false) return false;

            foreach ($XML->children() as $event)
            {
                $feed_event_id = (int) $event->ID;

                // Event Data
                $meta = $event->meta;
                $mec = $event->mec;

                // Event location
                $location = ($event->locations ? $event->locations->item[0] : null);
                $location_id = ($location and isset($location->name)) ? $main->save_location([
                    'name' => trim((string) $location->name),
                    'address' => (string) $location->address,
                    'latitude' => (string) $location->latitude,
                    'longitude' => (string) $location->longitude,
                    'thumbnail' => (string) $location->thumbnail,
                ]) : 1;

                // Event Organizer
                $organizer = ($event->organizers ? $event->organizers->item[0] : null);
                $organizer_id = ($organizer and isset($organizer->name)) ? $main->save_organizer([
                    'name' => trim((string) $organizer->name),
                    'email' => (string) $organizer->email,
                    'tel' => (string) $organizer->tel,
                    'url' => (string) $organizer->url,
                    'thumbnail' => (string) $organizer->thumbnail,
                ]) : 1;

                // Event Categories
                $category_ids = [];
                if (isset($event->categories))
                {
                    foreach ($event->categories->children() as $category)
                    {
                        $category_id = $main->save_category([
                            'name' => trim((string) $category->name),
                        ]);

                        if ($category_id) $category_ids[] = $category_id;
                    }
                }

                // Event Tags
                $tag_ids = [];
                if (isset($event->tags))
                {
                    foreach ($event->tags->children() as $tag)
                    {
                        $tag_id = $main->save_tag([
                            'name' => trim((string) $tag->name),
                        ]);

                        if ($tag_id) $tag_ids[] = $tag_id;
                    }
                }

                // Event Labels
                $label_ids = [];
                if (isset($event->labels))
                {
                    foreach ($event->labels->children() as $label)
                    {
                        $label_id = $main->save_label([
                            'name' => trim((string) $label->name),
                            'color' => (string) $label->color,
                        ]);

                        if ($label_id) $label_ids[] = $label_id;
                    }
                }

                // Event Speakers
                $speaker_ids = [];
                if (isset($event->speakers))
                {
                    foreach ($event->speakers->children() as $speaker)
                    {
                        $speaker_id = $main->save_speaker([
                            'name' => trim((string) $speaker->name),
                            'job_title' => (string) (isset($speaker->job_title) ? $speaker->job_title : ''),
                            'tel' => (string) (isset($speaker->tel) ? $speaker->tel : ''),
                            'email' => (string) (isset($speaker->email) ? $speaker->email : ''),
                            'facebook' => (string) (isset($speaker->facebook) ? $speaker->facebook : ''),
                            'twitter' => (string) (isset($speaker->twitter) ? $speaker->twitter : ''),
                            'instagram' => (string) (isset($speaker->instagram) ? $speaker->instagram : ''),
                            'linkedin' => (string) (isset($speaker->linkedin) ? $speaker->linkedin : ''),
                            'website' => (string) (isset($speaker->website) ? $speaker->website : ''),
                            'thumbnail' => (string) (isset($speaker->thumbnail) ? $speaker->thumbnail : ''),
                        ]);

                        if ($speaker_id) $speaker_ids[] = $speaker_id;
                    }
                }

                // Event Sponsors
                $sponsor_ids = [];
                if (isset($event->sponsors) and isset($settings['sponsors_status']) and $settings['sponsors_status'])
                {
                    foreach ($event->sponsors->children() as $sponsor)
                    {
                        $sponsor_id = $main->save_sponsor([
                            'name' => trim((string) $sponsor->name),
                            'link' => (string) (isset($sponsor->link) ? $sponsor->link : ''),
                            'logo' => (string) (isset($sponsor->logo) ? $sponsor->logo : ''),
                        ]);

                        if ($sponsor_id) $sponsor_ids[] = $sponsor_id;
                    }
                }

                // Start
                $start_date = (string) $meta->mec_date->start->date;
                $start_hour = (int) $meta->mec_date->start->hour;
                $start_minutes = (int) $meta->mec_date->start->minutes;
                $start_ampm = (string) $meta->mec_date->start->ampm;

                // End
                $end_date = (string) $meta->mec_date->end->date;
                $end_hour = (int) $meta->mec_date->end->hour;
                $end_minutes = (int) $meta->mec_date->end->minutes;
                $end_ampm = (string) $meta->mec_date->end->ampm;

                // Time Options
                $allday = (int) $meta->mec_date->allday;
                $time_comment = (string) $meta->mec_date->comment;
                $hide_time = (int) $meta->mec_date->hide_time;
                $hide_end_time = (int) $meta->mec_date->hide_end_time;

                // Repeat Options
                $repeat_status = (int) $meta->mec_repeat_status;
                $repeat_type = (string) $meta->mec_repeat_type;
                $repeat_interval = (int) $meta->mec_repeat_interval;
                $finish = (string) $mec->end;
                $year = (string) $mec->year;
                $month = (string) $mec->month;
                $day = (string) $mec->day;
                $week = (string) $mec->week;
                $weekday = (string) $mec->weekday;
                $weekdays = (string) $mec->weekdays;
                $days = (string) $mec->days;
                $not_in_days = (string) $mec->not_in_days;

                $additional_organizer_ids = [];
                if (isset($meta->mec_additional_organizer_ids))
                {
                    foreach ($meta->mec_additional_organizer_ids->children() as $o)
                    {
                        $additional_organizer_ids[] = (int) $o;
                    }
                }

                $hourly_schedules = [];
                if (isset($meta->mec_hourly_schedules))
                {
                    foreach ($meta->mec_hourly_schedules->children() as $s)
                    {
                        $hourly_schedules[] = [
                            'from' => (string) $s->from,
                            'to' => (string) $s->to,
                            'title' => (string) $s->title,
                            'description' => (string) $s->description,
                        ];
                    }
                }

                $tickets = [];
                if (isset($meta->mec_tickets))
                {
                    foreach ($meta->mec_tickets->children() as $t)
                    {
                        $tickets[] = [
                            'name' => (string) $t->name,
                            'description' => (string) $t->description,
                            'price' => (string) $t->price,
                            'price_label' => (string) $t->price_label,
                            'limit' => (string) $t->limit,
                            'unlimited' => (int) $t->unlimited,
                        ];
                    }
                }

                $fees = [];
                if (isset($meta->mec_fees))
                {
                    foreach ($meta->mec_fees->children() as $f)
                    {
                        if ($f->getName() !== 'item') continue;

                        $fees[] = [
                            'title' => (string) $f->title,
                            'amount' => (string) $f->amount,
                            'type' => (string) $f->type,
                        ];
                    }
                }

                $reg_fields = [];
                if (isset($meta->mec_reg_fields))
                {
                    foreach ($meta->mec_reg_fields->children() as $r)
                    {
                        if ($r->getName() !== 'item') continue;

                        $options = [];
                        foreach ($r->options->children() as $o) $options[] = (string) $o->label;

                        $reg_fields[] = [
                            'mandatory' => (int) $r->mandatory,
                            'type' => (string) $r->type,
                            'label' => (string) $r->label,
                            'options' => $options,
                        ];
                    }
                }

                $advanced_days = [];
                if (isset($meta->mec_advanced_days))
                {
                    foreach ($meta->mec_advanced_days->children() as $t)
                    {
                        $advanced_days[] = (string) $t;
                    }
                }

                // Event Fields
                $event_fields = [];
                if (isset($event->fields))
                {
                    // Global Fields
                    $global_fields = $this->main->get_event_fields();
                    if (!is_array($global_fields)) $global_fields = [];

                    foreach ($event->fields->children() as $field)
                    {
                        $field_id = isset($field->id) ? (int) $field->id : null;
                        if (!$field_id) continue;

                        $field_type = isset($field->type) ? (string) $field->type : null;
                        if (!$field_type) continue;

                        $field_val = isset($field->value) ? (string) $field->value : null;
                        if (!$field_val) continue;

                        $global_field = ($global_fields[$field_id] ?? null);
                        if (!$global_field) continue;

                        if (!is_array($global_field) or (is_array($global_field) and !isset($global_field['type'])) or (is_array($global_field) and isset($global_field['type']) and $global_field['type'] !== $field_type)) continue;

                        if (in_array($field_type, ['checkbox']))
                        {
                            $raw_field_value = explode(',', trim($field_val, ', '));

                            $field_value = [];
                            foreach ($raw_field_value as $field_k => $field_v)
                            {
                                if (trim($field_v) !== '') $field_value[] = trim($field_v);
                            }
                        }
                        else $field_value = $field_val;

                        $event_fields[$field_id] = $field_value;
                    }
                }

                $args = [
                    'title' => (string) $event->title,
                    'content' => (string) $event->content,
                    'status' => (string) ($event->post ? $event->post->post_status : 'publish'),
                    'location_id' => $location_id,
                    'organizer_id' => $organizer_id,
                    'date' => [
                        'start' => [
                            'date' => $start_date,
                            'hour' => $start_hour,
                            'minutes' => $start_minutes,
                            'ampm' => $start_ampm,
                        ],
                        'end' => [
                            'date' => $end_date,
                            'hour' => $end_hour,
                            'minutes' => $end_minutes,
                            'ampm' => $end_ampm,
                        ],
                        'repeat' => [],
                        'allday' => $allday,
                        'comment' => $time_comment,
                        'hide_time' => $hide_time,
                        'hide_end_time' => $hide_end_time,
                    ],
                    'start' => $start_date,
                    'start_time_hour' => $start_hour,
                    'start_time_minutes' => $start_minutes,
                    'start_time_ampm' => $start_ampm,
                    'end' => $end_date,
                    'end_time_hour' => $end_hour,
                    'end_time_minutes' => $end_minutes,
                    'end_time_ampm' => $end_ampm,
                    'repeat_status' => $repeat_status,
                    'repeat_type' => $repeat_type,
                    'interval' => $repeat_interval,
                    'finish' => $finish,
                    'year' => $year,
                    'month' => $month,
                    'day' => $day,
                    'week' => $week,
                    'weekday' => $weekday,
                    'weekdays' => $weekdays,
                    'days' => $days,
                    'not_in_days' => $not_in_days,
                    'meta' => [
                        'mec_source' => 'mec-calendar',
                        'mec_feed_event_id' => $feed_event_id,
                        'mec_dont_show_map' => (int) $meta->mec_dont_show_map,
                        'mec_color' => (string) $meta->mec_color,
                        'mec_read_more' => (string) $meta->mec_read_more,
                        'mec_more_info' => (string) $meta->mec_more_info,
                        'mec_more_info_title' => (string) $meta->mec_more_info_title,
                        'mec_more_info_target' => (string) $meta->mec_more_info_target,
                        'mec_cost' => (string) $meta->mec_cost,
                        'mec_additional_organizer_ids' => $additional_organizer_ids,
                        'mec_repeat' => [
                            'status' => (int) $meta->mec_repeat->status,
                            'type' => (string) $meta->mec_repeat->type,
                            'interval' => (int) $meta->mec_repeat->interval,
                            'end' => (string) $meta->mec_repeat->end,
                            'end_at_date' => (string) $meta->mec_repeat->end_at_date,
                            'end_at_occurrences' => (string) $meta->mec_repeat->end_at_occurrences,
                        ],
                        'mec_allday' => $allday,
                        'mec_hide_time' => $hide_time,
                        'mec_hide_end_time' => $hide_end_time,
                        'mec_comment' => $time_comment,
                        'mec_repeat_end' => (string) $meta->mec_repeat_end,
                        'mec_repeat_end_at_occurrences' => (string) $meta->mec_repeat_end_at_occurrences,
                        'mec_repeat_end_at_date' => (string) $meta->mec_repeat_end_at_date,
                        'mec_in_days' => (string) $meta->mec_in_days,
                        'mec_not_in_days' => (string) $meta->mec_not_in_days,
                        'mec_hourly_schedules' => $hourly_schedules,
                        'mec_booking' => [
                            'bookings_limit_unlimited' => (int) $meta->mec_booking->bookings_limit_unlimited,
                            'bookings_limit' => (isset($meta->mec_booking->bookings_limit) and trim((string) $meta->mec_booking->bookings_limit) !== '') ? (int) $meta->mec_booking->bookings_limit : '',
                        ],
                        'mec_tickets' => $tickets,
                        'mec_fees_global_inheritance' => (int) $meta->mec_fees_global_inheritance,
                        'mec_fees' => $fees,
                        'mec_reg_fields_global_inheritance' => (int) $meta->mec_reg_fields_global_inheritance,
                        'mec_reg_fields' => $reg_fields,
                        'mec_advanced_days' => $advanced_days,
                        'mec_fields' => $event_fields,
                    ],
                ];

                $post_id = $db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='$feed_event_id' AND `meta_key`='mec_feed_event_id'", 'loadResult');

                // Insert the event into MEC
                $post_id = $main->save_event($args, $post_id);

                // Add it to the imported posts
                $posts[] = $post_id;

                // Set location to the post
                if ($location_id) wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

                // Set organizer to the post
                if ($organizer_id) wp_set_object_terms($post_id, (int) $organizer_id, 'mec_organizer');

                // Set categories to the post
                if (count($category_ids)) foreach ($category_ids as $category_id) wp_set_object_terms($post_id, (int) $category_id, 'mec_category', true);

                // Set tags to the post
                if (count($tag_ids)) foreach ($tag_ids as $tag_id) wp_set_object_terms($post_id, (int) $tag_id, apply_filters('mec_taxonomy_tag', ''), true);

                // Set labels to the post
                if (count($label_ids)) foreach ($label_ids as $label_id) wp_set_object_terms($post_id, (int) $label_id, 'mec_label', true);

                // Set speakers to the post
                if (count($speaker_ids)) foreach ($speaker_ids as $speaker_id) wp_set_object_terms($post_id, (int) $speaker_id, 'mec_speaker', true);

                // Set sponsors to the post
                if (count($sponsor_ids)) foreach ($sponsor_ids as $sponsor_id) wp_set_object_terms($post_id, (int) $sponsor_id, 'mec_sponsor', true);

                // Featured Image
                $featured_image = isset($event->featured_image) ? (string) $event->featured_image->full : '';
                if (!has_post_thumbnail($post_id) and trim($featured_image))
                {
                    $file_name = basename($featured_image);

                    $path = rtrim($wp_upload_dir['path'], DS . ' ') . DS . $file_name;
                    $url = rtrim($wp_upload_dir['url'], '/ ') . '/' . $file_name;

                    // Download Image
                    $buffer = $main->get_web_page($featured_image);

                    $file->write($path, $buffer);
                    $main->set_featured_image($url, $post_id);
                }
            }
        }
        else if (strtolower($extension) == 'ics')
        {
            $parsed = $main->parse_ics($feed);

            // ics file not valid
            if (is_string($parsed)) return $posts;

            $calendar_timezone = $parsed->calendarTimeZone();

            // Timezone
            $timezone = $main->get_timezone();

            $events = $parsed->events();
            foreach ($events as $event)
            {
                $feed_event_id = $event->uid;

                // Event location
                $location = $event->location;
                $location_id = $location && trim($location) ? $main->save_location([
                    'name' => trim((string) $location),
                ]) : 1;

                // Event Organizer
                $organizer = $event->organizer_array ?? [];
                $organizer_id = (isset($organizer[0]) and isset($organizer[0]['CN'])) ? $main->save_organizer([
                    'name' => trim((string) $organizer[0]['CN']),
                    'email' => (string) str_replace('MAILTO:', '', $organizer[1]),
                ]) : 1;

                // Event Categories
                $category_ids = [];
                if (isset($event->categories) and trim($event->categories))
                {
                    $cats = explode(',', $event->categories);
                    foreach ($cats as $category)
                    {
                        $category_id = $main->save_category([
                            'name' => trim((string) $category),
                        ]);

                        if ($category_id) $category_ids[] = $category_id;
                    }
                }

                // Event Timezone
                $event_timezone = $timezone;

                $ics_timezone = null;
                if (isset($event->dtstart_array) and isset($event->dtstart_array[0]) and isset($event->dtstart_array[0]['TZID'])) $ics_timezone = $event->dtstart_array[0]['TZID'];

                $allday_event = ((isset($event->dtstart_array, $event->dtstart_array[0], $event->dtstart_array[0]['VALUE']) and $event->dtstart_array[0]['VALUE'] === 'DATE') and (isset($event->dtend_array, $event->dtend_array[0], $event->dtend_array[0]['VALUE']) and $event->dtend_array[0]['VALUE'] === 'DATE'));
                $start_datetime = $event->dtstart;

                $not_in_days = null;

                // ICS file has Timezone for event
                if ($ics_timezone)
                {
                    $date_start = new DateTime($start_datetime, new DateTimeZone($ics_timezone));
                    $event_timezone = $ics_timezone;

                    $date_end = null;

                    $end_timestamp = isset($event->dtend) ? strtotime($event->dtend) : 0;
                    if ($end_timestamp)
                    {
                        $end_datetime = $event->dtend;

                        $date_end = new DateTime($end_datetime, new DateTimeZone($ics_timezone));
                    }

                    // Excluded Dates
                    if (isset($event->exdate) and trim($event->exdate))
                    {
                        $ex_dates = explode(',', $event->exdate);

                        $not_in_days = '';
                        foreach ($ex_dates as $ex_date)
                        {
                            $exd = new DateTime($ex_date, new DateTimeZone('UTC'));
                            $exd->setTimezone(new DateTimeZone($ics_timezone));

                            $not_in_days .= $exd->format('Y-m-d') . ',';
                        }

                        $not_in_days = trim($not_in_days, ', ');
                    }
                }
                // Consider UTC as default timezone
                else
                {
                    $cal_tz = 'UTC';
                    if (trim($calendar_timezone)) $cal_tz = $calendar_timezone;

                    if (isset($event->dtstart_tz) and !$allday_event) $start_datetime = $event->dtstart_tz;

                    $date_start = new DateTime($start_datetime, new DateTimeZone($cal_tz));
                    $date_start->setTimezone(new DateTimeZone($event_timezone));

                    $date_end = null;

                    $end_timestamp = isset($event->dtend) ? strtotime($event->dtend) : 0;
                    if ($end_timestamp)
                    {
                        $end_datetime = $event->dtend;
                        if (isset($event->dtend_tz) and !$allday_event) $end_datetime = $event->dtend_tz;

                        $date_end = new DateTime($end_datetime, new DateTimeZone($cal_tz));
                        $date_end->setTimezone(new DateTimeZone($event_timezone));
                    }

                    // Excluded Dates
                    if (isset($event->exdate) and trim($event->exdate))
                    {
                        $ex_dates = explode(',', $event->exdate);

                        $not_in_days = '';
                        foreach ($ex_dates as $ex_date)
                        {
                            $exd = new DateTime($ex_date, new DateTimeZone($cal_tz));
                            $exd->setTimezone(new DateTimeZone($event_timezone));

                            $not_in_days .= $exd->format('Y-m-d') . ',';
                        }

                        $not_in_days = trim($not_in_days, ', ');
                    }
                }

                $start_date = $date_start->format('Y-m-d');
                $start_hour = $date_start->format('g');
                $start_minutes = $date_start->format('i');
                $start_ampm = $date_start->format('A');

                $end_date = $end_timestamp ? $date_end->format('Y-m-d') : $start_date;
                $end_hour = $end_timestamp ? $date_end->format('g') : 8;
                $end_minutes = $end_timestamp ? $date_end->format('i') : '00';
                $end_ampm = $end_timestamp ? $date_end->format('A') : 'PM';

                // Time Options
                $allday = 0;
                $time_comment = '';
                $hide_time = 0;
                $hide_end_time = 0;

                if ($start_hour === '12' and $start_minutes === '00' and $start_ampm === 'AM' and $end_hour === '12' and $end_minutes === '00' and $end_ampm === 'AM')
                {
                    $allday = 1;

                    $start_hour = 0;
                    $start_minutes = 0;
                    $start_ampm = 'AM';

                    $end_hour = 11;
                    $end_minutes = 55;
                    $end_ampm = 'PM';

                    $diff = $this->main->date_diff($start_date, $end_date);
                    if (($diff ? $diff->days : 0) > 1)
                    {
                        $date_end->sub(new DateInterval('P1D'));
                        $end_date = $date_end->format('Y-m-d');
                    }
                }

                // Repeat Options
                $repeat_status = 0;
                $repeat_type = '';
                $repeat_interval = null;
                $finish = $end_date;
                $year = null;
                $month = null;
                $day = null;
                $week = null;
                $weekday = null;
                $weekdays = null;
                $days = null;
                $repeat_count = null;
                $advanced_days = null;

                // Recurring Event
                $rrule = (isset($event->rrule) and trim($event->rrule)) ? $event->rrule : '';
                if (trim($rrule) != '')
                {
                    $ex1 = explode(';', $rrule);

                    $rule = [];
                    foreach ($ex1 as $r)
                    {
                        $ex2 = explode('=', $r);
                        $rrule_key = strtolower($ex2[0]);
                        $rrule_value = ($rrule_key == 'until' ? $ex2[1] : strtolower($ex2[1]));
                        $rule[$rrule_key] = $rrule_value;
                    }

                    if (isset($rule['count']) and is_numeric($rule['count'])) $repeat_count = max($rule['count'], 0);

                    $repeat_status = 1;
                    if ($rule['freq'] == 'daily')
                    {
                        $repeat_type = 'daily';
                        $repeat_interval = $rule['interval'] ?? 1;
                    }
                    else if ($rule['freq'] == 'weekly')
                    {
                        $repeat_type = 'weekly';
                        $repeat_interval = isset($rule['interval']) ? $rule['interval'] * 7 : 7;
                    }
                    else if ($rule['freq'] == 'monthly' and isset($rule['byday']) and trim($rule['byday']))
                    {
                        $repeat_type = 'advanced';

                        $adv_week = (isset($rule['bysetpos']) and trim($rule['bysetpos']) != '') ? $rule['bysetpos'] : (int) substr($rule['byday'], 0, -2);
                        $adv_day = str_replace($adv_week, '', $rule['byday']);

                        $mec_adv_day = 'Sat';
                        if ($adv_day == 'su') $mec_adv_day = 'Sun';
                        else if ($adv_day == 'mo') $mec_adv_day = 'Mon';
                        else if ($adv_day == 'tu') $mec_adv_day = 'Tue';
                        else if ($adv_day == 'we') $mec_adv_day = 'Wed';
                        else if ($adv_day == 'th') $mec_adv_day = 'Thu';
                        else if ($adv_day == 'fr') $mec_adv_day = 'Fri';

                        if ($adv_week < 0) $adv_week = 'l';
                        $advanced_days = [$mec_adv_day . '.' . $adv_week];
                    }
                    else if ($rule['freq'] == 'monthly')
                    {
                        $repeat_type = 'monthly';

                        $year = '*';
                        $month = '*';

                        $s = $start_date;
                        $e = $end_date;

                        $_days = [];
                        while (strtotime($s) <= strtotime($e))
                        {
                            $_days[] = date('d', strtotime($s));
                            $s = date('Y-m-d', strtotime('+1 Day', strtotime($s)));
                        }

                        $day = ',' . implode(',', array_unique($_days)) . ',';

                        $week = '*';
                        $weekday = '*';
                        $repeat_interval = $rule['interval'] ?? 1;
                    }
                    else if ($rule['freq'] == 'yearly')
                    {
                        $repeat_type = 'yearly';

                        $year = '*';

                        $s = $start_date;
                        $e = $end_date;

                        $_months = [];
                        $_days = [];
                        while (strtotime($s) <= strtotime($e))
                        {
                            $_months[] = date('m', strtotime($s));
                            $_days[] = date('d', strtotime($s));

                            $s = date('Y-m-d', strtotime('+1 Day', strtotime($s)));
                        }

                        $month = ',' . implode(',', array_unique($_months)) . ',';
                        $day = ',' . implode(',', array_unique($_days)) . ',';

                        $week = '*';
                        $weekday = '*';
                    }

                    // Custom Week Days
                    if ($repeat_type == 'weekly' and isset($rule['byday']) and count(explode(',', $rule['byday'])) > 1)
                    {
                        $g_week_days = explode(',', $rule['byday']);
                        $week_day_mapping = ['mo' => 1, 'tu' => 2, 'we' => 3, 'th' => 4, 'fr' => 5, 'sa' => 6, 'su' => 7];

                        $weekdays = '';
                        foreach ($g_week_days as $g_week_day) $weekdays .= $week_day_mapping[$g_week_day] . ',';

                        $weekdays = ',' . trim($weekdays, ', ') . ',';
                        $repeat_interval = null;

                        $repeat_type = 'certain_weekdays';
                    }

                    $finish = isset($rule['until']) ? date('Y-m-d', strtotime($rule['until'])) : null;
                }

                // Custom Days
                if (trim($repeat_type) === '' && isset($event->rdate) && trim($event->rdate))
                {
                    $custom_dates = explode(',', $event->rdate);
                    unset($custom_dates[0]);

                    if (count($custom_dates))
                    {
                        $repeat_type = 'custom_days';
                        $repeat_status = 1;
                        $days = '';

                        $timezone = $this->main->get_TZO();
                        $UTC = new DateTimeZone('UTC');

                        foreach ($custom_dates as $custom_date)
                        {
                            [$custom_date_start, $custom_date_end] = explode('/', $custom_date);

                            $custom_start = new DateTime($custom_date_start, $UTC);
                            $custom_start->setTimezone($timezone);

                            $custom_end = new DateTime($custom_date_end, $UTC);
                            $custom_end->setTimezone($timezone);

                            $days .= $custom_start->format('Y-m-d') . ':' . $custom_end->format('Y-m-d') . ':' . $custom_start->format('h-i-A') . ':' . $custom_end->format('h-i-A') . ',';
                        }

                        $days = trim($days, ',');
                    }
                }

                $additional_organizer_ids = [];
                $hourly_schedules = [];
                $tickets = [];
                $fees = [];
                $reg_fields = [];

                $args = [
                    'title' => (string) $event->summary,
                    'content' => (string) $event->description,
                    'location_id' => $location_id,
                    'organizer_id' => $organizer_id,
                    'date' => [
                        'start' => [
                            'date' => $start_date,
                            'hour' => $start_hour,
                            'minutes' => $start_minutes,
                            'ampm' => $start_ampm,
                        ],
                        'end' => [
                            'date' => $end_date,
                            'hour' => $end_hour,
                            'minutes' => $end_minutes,
                            'ampm' => $end_ampm,
                        ],
                        'repeat' => [],
                        'allday' => $allday,
                        'comment' => $time_comment,
                        'hide_time' => $hide_time,
                        'hide_end_time' => $hide_end_time,
                    ],
                    'start' => $start_date,
                    'start_time_hour' => $start_hour,
                    'start_time_minutes' => $start_minutes,
                    'start_time_ampm' => $start_ampm,
                    'end' => $end_date,
                    'end_time_hour' => $end_hour,
                    'end_time_minutes' => $end_minutes,
                    'end_time_ampm' => $end_ampm,
                    'repeat_status' => $repeat_status,
                    'repeat_type' => $repeat_type,
                    'repeat_count' => $repeat_count,
                    'interval' => $repeat_interval,
                    'finish' => $finish,
                    'year' => $year,
                    'month' => $month,
                    'day' => $day,
                    'week' => $week,
                    'weekday' => $weekday,
                    'weekdays' => $weekdays,
                    'days' => $days,
                    'not_in_days' => $not_in_days,
                    'meta' => [
                        'mec_source' => 'ics-calendar',
                        'mec_feed_event_id' => $feed_event_id,
                        'mec_dont_show_map' => 0,
                        'mec_additional_organizer_ids' => $additional_organizer_ids,
                        'mec_allday' => $allday,
                        'mec_hide_time' => $hide_time,
                        'mec_hide_end_time' => $hide_end_time,
                        'mec_comment' => $time_comment,
                        'mec_in_days' => $days,
                        'mec_not_in_days' => $not_in_days,
                        'mec_hourly_schedules' => $hourly_schedules,
                        'mec_tickets' => $tickets,
                        'mec_fees_global_inheritance' => 1,
                        'mec_fees' => $fees,
                        'mec_reg_fields_global_inheritance' => 1,
                        'mec_reg_fields' => $reg_fields,
                        'mec_timezone' => ($event_timezone === $timezone ? 'global' : $event_timezone),
                        'mec_advanced_days' => $advanced_days,
                    ],
                ];

                $post_id = $db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='$feed_event_id' AND `meta_key`='mec_feed_event_id'", 'loadResult');

                // Insert the event into MEC
                $post_id = $main->save_event($args, $post_id);

                // Add it to the imported posts
                $posts[] = $post_id;

                // Set location to the post
                if ($location_id) wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

                // Set organizer to the post
                if ($organizer_id) wp_set_object_terms($post_id, (int) $organizer_id, 'mec_organizer');

                // Set categories to the post
                if (count($category_ids)) foreach ($category_ids as $category_id) wp_set_object_terms($post_id, (int) $category_id, 'mec_category', true);

                // Featured Image
                $featured_image = isset($event->attach) ? (string) $event->attach : '';
                if (!has_post_thumbnail($post_id) and trim($featured_image))
                {
                    $file_name = basename($featured_image);

                    $path = rtrim($wp_upload_dir['path'], DS . ' ') . DS . $file_name;
                    $url = rtrim($wp_upload_dir['url'], '/ ') . '/' . $file_name;

                    // Download Image
                    $buffer = $main->get_web_page($featured_image);

                    $file->write($path, $buffer);
                    $main->set_featured_image($url, $post_id);
                }
            }
        }

        return $posts;
    }

    /**
     * Show content of test data tab
     * @return void
     * @author Webnus <info@webnus.net>
     */
    public function ix_test_data()
    {
        // Current Action
        $this->action = isset($_POST['mec-ix-action']) ? sanitize_text_field($_POST['mec-ix-action']) : '';
        $this->ix = (isset($_POST['ix']) and is_array($_POST['ix'])) ? array_map('sanitize_text_field', $_POST['ix']) : [];

        $this->response = [];
        if ($this->action == 'test-data-generation-start') $this->response = $this->generate_test_data();

        $path = MEC::import('app.features.ix.test_data', true, true);

        ob_start();
        include $path;
        echo MEC_kses::full(ob_get_clean());
    }

    public function generate_test_data()
    {
        $number = isset($_POST['number']) ? sanitize_text_field($_POST['number']) : 10;
        $category_method = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $tag_method = isset($_POST['tag']) ? sanitize_text_field($_POST['tag']) : '';
        $organizer_method = isset($_POST['organizer']) ? sanitize_text_field($_POST['organizer']) : '';
        $location_method = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';

        $category_id = $tag_id = $organizer_id = $location_id = null;
        $tag_taxonomy = apply_filters('mec_taxonomy_tag', '');

        if ($category_method && $category_method === 'random') $category_id = $this->select_random_term('mec_category');
        else if ($category_method && $category_method === 'generate') $category_id = $this->generate_random_term('mec_category');
        else if ($category_method) $category_id = (int) $category_method;

        if ($tag_method && $tag_method === 'random') $tag_id = $this->select_random_term($tag_taxonomy);
        else if ($tag_method && $tag_method === 'generate') $tag_id = $this->generate_random_term($tag_taxonomy);
        else if ($tag_method) $tag_id = (int) $tag_method;

        if ($organizer_method && $organizer_method === 'random') $organizer_id = $this->select_random_term('mec_organizer');
        else if ($organizer_method && $organizer_method === 'generate') $organizer_id = $this->generate_random_term('mec_organizer');
        else if ($organizer_method) $organizer_id = (int) $organizer_method;

        if ($location_method && $location_method === 'random') $location_id = $this->select_random_term('mec_location');
        else if ($location_method && $location_method === 'generate') $location_id = $this->generate_random_term('mec_location');
        else if ($location_method) $location_id = (int) $location_method;

        // Generate Events
        for ($i = 1; $i <= $number; $i++)
        {
            $chars = str_shuffle('abcdefghijklmnopqrstuvwxyz');
            $name = ucfirst(substr($chars, 0, rand(6, 10)));

            $start_date = date('Y-m-d', strtotime('+' . rand(2, 30) . ' days'));
            $start_hour = 8;
            $start_minutes = 0;
            $start_ampm = 'AM';

            $end_date = date('Y-m-d', strtotime('+' . rand(0, 3) . ' days', strtotime($start_date)));
            $end_hour = 6;
            $end_minutes = 0;
            $end_ampm = 'PM';

            $event_id = $this->main->save_event([
                'title' => sprintf(esc_html__('%s - Test Event', 'modern-events-calendar-lite'), $name),
                'location_id' => $location_id,
                'organizer_id' => $organizer_id,
                'date' => [
                    'start' => [
                        'date' => $start_date,
                        'hour' => $start_hour,
                        'minutes' => $start_minutes,
                        'ampm' => $start_ampm,
                    ],
                    'end' => [
                        'date' => $end_date,
                        'hour' => $end_hour,
                        'minutes' => $end_minutes,
                        'ampm' => $end_ampm,
                    ],
                    'repeat' => [],
                    'allday' => 0,
                    'comment' => '',
                    'hide_time' => 0,
                    'hide_end_time' => 0,
                ],
                'start' => $start_date,
                'start_time_hour' => $start_hour,
                'start_time_minutes' => $start_minutes,
                'start_time_ampm' => $start_ampm,
                'end' => $end_date,
                'end_time_hour' => $end_hour,
                'end_time_minutes' => $end_minutes,
                'end_time_ampm' => $end_ampm,
                'repeat_status' => 0,
                'repeat_type' => '',
                'interval' => null,
                'finish' => $end_date,
                'year' => null,
                'month' => null,
                'day' => null,
                'week' => null,
                'weekday' => null,
                'weekdays' => null,
                'meta' => [
                    'mec_source' => 'mec-random',
                    'mec_allday' => 0,
                    'mec_advanced_days' => null,
                ],
            ]);

            // Set terms
            if ($location_id) wp_set_object_terms($event_id, (int) $location_id, 'mec_location');
            if ($organizer_id) wp_set_object_terms($event_id, (int) $organizer_id, 'mec_organizer');
            if ($category_id) wp_set_object_terms($event_id, (int) $category_id, 'mec_category');
            if ($tag_id) wp_set_object_terms($event_id, (int) $tag_id, $tag_taxonomy);

            if ($category_method && $category_method === 'random') $category_id = $this->select_random_term('mec_category');
            if ($tag_method && $tag_method === 'random') $tag_id = $this->select_random_term($tag_taxonomy);
            if ($organizer_method && $organizer_method === 'random') $organizer_id = $this->select_random_term('mec_organizer');
            if ($location_method && $location_method === 'random') $location_id = $this->select_random_term('mec_location');
        }

        return ['success' => 1, 'message' => sprintf(esc_html__("%s events successfully created.", 'modern-events-calendar-lite'), '<strong>' . $number . '</strong>')];
    }

    private function select_random_term($taxonomy)
    {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => 0,
        ]);
        if (count($terms) === 0) return 0;

        shuffle($terms);
        return array_slice($terms, 0, 1)[0]->term_id;
    }

    private function generate_random_term($taxonomy)
    {
        $chars = str_shuffle('abcdefghijklmnopqrstuvwxyz');
        $name = ucfirst(substr($chars, 0, rand(5, 8)));

        return wp_insert_term($name, $taxonomy)['term_id'];
    }

    /**
     * Show content of third party tab
     * @return void
     * @author Webnus <info@webnus.net>
     */
    public function ix_thirdparty()
    {
        // Current Action
        $this->action = isset($_POST['mec-ix-action']) ? sanitize_text_field($_POST['mec-ix-action']) : '';
        $this->ix = ((isset($_POST['ix']) and is_array($_POST['ix'])) ? array_map('sanitize_text_field', $_POST['ix']) : []);

        $this->response = [];
        if ($this->action == 'thirdparty-import-start') $this->response = $this->thirdparty_import_start();

        $path = MEC::import('app.features.ix.thirdparty', true, true);

        ob_start();
        include $path;
        echo MEC_kses::full(ob_get_clean());
    }

    public function thirdparty_import_start()
    {
        $third_party = $this->ix['third-party'] ?? null;

        if ($third_party == 'eventon' and class_exists('EventON'))
        {
            $events = get_posts([
                'posts_per_page' => -1,
                'post_type' => 'ajde_events',
            ]);
        }
        else if ($third_party == 'the-events-calendar' and class_exists('Tribe__Events__Main'))
        {
            $events = get_posts([
                'posts_per_page' => -1,
                'post_type' => 'tribe_events',
            ]);
        }
        else if ($third_party == 'bookly')
        {
            $validation = $this->bookly_import_validate_requirements();
            if (!$validation['success']) return $validation;

            $events = $this->bookly_import_get_services();
        }
        else if ($third_party == 'amelia')
        {
            $validation = $this->amelia_import_validate_requirements();
            if (!$validation['success']) return $validation;

            $events = $this->amelia_import_get_services();
        }
        else if ($third_party == 'weekly-class' and class_exists('WeeklyClass'))
        {
            $events = get_posts([
                'posts_per_page' => -1,
                'post_type' => 'class',
            ]);
        }
        else if ($third_party == 'calendarize-it' and class_exists('plugin_righthere_calendar'))
        {
            $events = get_posts([
                'posts_per_page' => -1,
                'post_type' => 'events',
            ]);
        }
        else if ($third_party == 'event-espresso' and function_exists('bootstrap_espresso'))
        {
            $events = get_posts([
                'posts_per_page' => -1,
                'post_type' => 'espresso_events',
            ]);
        }
        else if ($third_party == 'events-manager-recurring' and class_exists('EM_Formats'))
        {
            $events = get_posts([
                'posts_per_page' => -1,
                'post_type' => 'event-recurring',
            ]);
        }
        else if ($third_party == 'events-manager-single' and class_exists('EM_Formats'))
        {
            $events = get_posts([
                'posts_per_page' => -1,
                'post_type' => 'event',
                'meta_key' => '_recurrence_id',
                'meta_compare' => 'NOT EXISTS',
            ]);
        }
        else if ($third_party == 'wp-event-manager' and class_exists('WP_Event_Manager'))
        {
            $events = get_posts([
                'posts_per_page' => -1,
                'post_type' => 'event_listing',
            ]);
        }
        else return ['success' => 0, 'message' => __("Third Party plugin is not installed and activated!", 'modern-events-calendar-lite')];

        return [
            'success' => 1,
            'data' => [
                'count' => count($events),
                'events' => $events,
            ],
        ];
    }

    public function thirdparty_import_do()
    {
        // Check if our nonce is set.
        if (!isset($_POST['_wpnonce'])) $this->main->response(['success' => 0, 'code' => 'NONCE_MISSING']);

        // Verify that the nonce is valid.
        if (!wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), 'mec_ix_thirdparty_import')) $this->main->response(['success' => 0, 'code' => 'NONCE_IS_INVALID']);

        $this->ix = ((isset($_POST['ix']) and is_array($_POST['ix'])) ? array_map('sanitize_text_field', $_POST['ix']) : []);

        $step = (isset($_POST['step']) and is_numeric($_POST['step']) and $_POST['step'] > 0) ? (int) $_POST['step'] : 1;
        $count = 20;
        $offset = max(($step - 1), 0) * $count;

        $all_events = ((isset($_POST['tp-events']) and is_array($_POST['tp-events'])) ? array_map('sanitize_text_field', $_POST['tp-events']) : []);
        $events = array_slice($all_events, $offset, $count);

        $third_party = $this->ix['third-party'] ?? '';

        $response = ['success' => 0, 'message' => __('Third Party plugin is invalid!', 'modern-events-calendar-lite')];
        if ($third_party == 'eventon') $response = $this->thirdparty_eventon_import_do($events);
        else if ($third_party == 'the-events-calendar') $response = $this->thirdparty_tec_import_do($events);
        else if ($third_party == 'bookly') $response = $this->thirdparty_bookly_import_do($events);
        else if ($third_party == 'amelia') $response = $this->thirdparty_amelia_import_do($events);
        else if ($third_party == 'weekly-class') $response = $this->thirdparty_weekly_class_import_do($events);
        else if ($third_party == 'calendarize-it') $response = $this->thirdparty_calendarize_it_import_do($events);
        else if ($third_party == 'event-espresso') $response = $this->thirdparty_es_import_do($events);
        else if ($third_party == 'events-manager-recurring') $response = $this->thirdparty_emr_import_do($events);
        else if ($third_party == 'events-manager-single') $response = $this->thirdparty_ems_import_do($events);
        else if ($third_party == 'wp-event-manager') $response = $this->thirdparty_wpem_import_do($events);

        $response['next_step'] = $step + 1;
        $response['finished'] = (int) ($step * $count >= count($all_events));
        $response['all'] = (int) count($all_events);
        $response['imported'] = (int) min(($step * $count), count($all_events));

        $this->main->response($response);
    }

    public function thirdparty_bookly_import_do($IDs)
    {
        $validation = $this->bookly_import_validate_requirements();
        if (!$validation['success']) return $validation;

        $count = 0;
        $wpdb = $this->db->get_DBO();

        $settings = $this->main->get_settings();
        $time_format = ((isset($settings['time_format']) and (int) $settings['time_format'] === 24) ? 24 : 12);

        $services_table = $this->bookly_table('services');
        $staff_services_table = $this->bookly_table('staff_services');
        $staff_table = $this->bookly_table('staff');
        $schedule_items_table = $this->bookly_table('staff_schedule_items');
        $schedule_breaks_table = $this->bookly_table('schedule_item_breaks');
        $holidays_table = $this->bookly_table('holidays');
        $schedule_breaks_available = $this->bookly_table_exists('schedule_item_breaks');
        $holidays_available = $this->bookly_table_exists('holidays');

        $categories_table = $this->bookly_table('categories');
        $categories_available = $this->bookly_table_exists('categories');

        $locations_table = $this->bookly_table('locations');
        $locations_available = $this->bookly_table_exists('locations');

        foreach ($IDs as $ID)
        {
            $service_id = (int) $ID;
            if (!$service_id) continue;

            $service = $wpdb->get_row("SELECT * FROM `{$services_table}` WHERE `id`='{$service_id}'", ARRAY_A);
            if (!is_array($service) or !isset($service['id'])) continue;

            $staff_services = $wpdb->get_results("SELECT * FROM `{$staff_services_table}` WHERE `service_id`='{$service_id}'", ARRAY_A);
            if (!is_array($staff_services) or !count($staff_services)) continue;

            $staff_ids = [];
            $location_ids = [];
            foreach ($staff_services as $staff_service)
            {
                $staff_id = (int) ($staff_service['staff_id'] ?? 0);
                if ($staff_id) $staff_ids[] = $staff_id;

                if (isset($staff_service['location_id']) and !is_null($staff_service['location_id']) and trim((string) $staff_service['location_id']) !== '')
                {
                    $location_id = (int) $staff_service['location_id'];
                    if ($location_id) $location_ids[] = $location_id;
                }
            }

            $staff_ids = array_values(array_unique($staff_ids));
            if (!count($staff_ids)) continue;

            $staff_ids_str = implode(',', array_map('intval', $staff_ids));

            $staff_rows = $wpdb->get_results("SELECT * FROM `{$staff_table}` WHERE `id` IN ({$staff_ids_str})", ARRAY_A);
            if (!is_array($staff_rows)) $staff_rows = [];

            $schedule_rows = $wpdb->get_results("SELECT * FROM `{$schedule_items_table}` WHERE `staff_id` IN ({$staff_ids_str}) AND `start_time` IS NOT NULL AND `end_time` IS NOT NULL", ARRAY_A);
            if (!is_array($schedule_rows) or !count($schedule_rows)) continue;

            $schedule_item_ids = [];
            foreach ($schedule_rows as $schedule_row)
            {
                $schedule_item_id = (int) ($schedule_row['id'] ?? 0);
                if ($schedule_item_id) $schedule_item_ids[] = $schedule_item_id;
            }

            $breaks_by_schedule = [];
            if (count($schedule_item_ids) and $schedule_breaks_available)
            {
                $schedule_item_ids_str = implode(',', array_map('intval', $schedule_item_ids));
                $break_rows = $wpdb->get_results("SELECT * FROM `{$schedule_breaks_table}` WHERE `staff_schedule_item_id` IN ({$schedule_item_ids_str})", ARRAY_A);

                if (is_array($break_rows) and count($break_rows))
                {
                    foreach ($break_rows as $break_row)
                    {
                        $schedule_item_id = (int) ($break_row['staff_schedule_item_id'] ?? 0);
                        if (!$schedule_item_id) continue;

                        $start = $this->bookly_time_to_minutes($break_row['start_time'] ?? null);
                        $end = $this->bookly_time_to_minutes($break_row['end_time'] ?? null);

                        if (is_null($start) or is_null($end) or $end === $start) continue;

                        if (!isset($breaks_by_schedule[$schedule_item_id])) $breaks_by_schedule[$schedule_item_id] = [];
                        $breaks_by_schedule[$schedule_item_id][] = ['start' => $start, 'end' => $end];
                    }
                }
            }

            $availability_by_day = [0 => [], 1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => []];
            foreach ($schedule_rows as $schedule_row)
            {
                $bookly_day_index = (int) ($schedule_row['day_index'] ?? 0);
                $mec_day_index = $this->bookly_day_index_to_mec($bookly_day_index);
                if (is_null($mec_day_index)) continue;

                $start = $this->bookly_time_to_minutes($schedule_row['start_time'] ?? null);
                $end = $this->bookly_time_to_minutes($schedule_row['end_time'] ?? null);
                if (is_null($start) or is_null($end)) continue;

                $segments = $this->bookly_split_interval($mec_day_index, $start, $end);
                if (!count($segments)) continue;

                $schedule_item_id = (int) ($schedule_row['id'] ?? 0);
                $raw_breaks = $breaks_by_schedule[$schedule_item_id] ?? [];
                $breaks_by_day = [];

                if (count($raw_breaks))
                {
                    foreach ($raw_breaks as $raw_break)
                    {
                        $break_segments = $this->bookly_split_interval($mec_day_index, (int) $raw_break['start'], (int) $raw_break['end']);
                        if (!count($break_segments)) continue;

                        foreach ($break_segments as $break_segment)
                        {
                            if (!isset($breaks_by_day[$break_segment['day']])) $breaks_by_day[$break_segment['day']] = [];
                            $breaks_by_day[$break_segment['day']][] = ['start' => $break_segment['start'], 'end' => $break_segment['end']];
                        }
                    }
                }

                foreach ($segments as $segment)
                {
                    $intervals = [['start' => $segment['start'], 'end' => $segment['end']]];
                    $segment_breaks = $breaks_by_day[$segment['day']] ?? [];
                    if (count($segment_breaks)) $intervals = $this->bookly_subtract_intervals($intervals, $segment_breaks);

                    foreach ($intervals as $interval)
                    {
                        if ($interval['end'] <= $interval['start']) continue;
                        $availability_by_day[$segment['day']][] = $interval;
                    }
                }
            }

            $appointments_availability = [];
            $first_interval = null;
            for ($d = 0; $d <= 6; $d++)
            {
                $merged = $this->bookly_merge_intervals($availability_by_day[$d]);
                if (!count($merged)) continue;

                foreach ($merged as $interval)
                {
                    if ($first_interval === null) $first_interval = ['day' => $d, 'start' => $interval['start'], 'end' => $interval['end']];

                    $appointments_availability[$d][] = [
                        'start' => $this->bookly_minutes_to_timepicker($interval['start'], $time_format),
                        'end' => $this->bookly_minutes_to_timepicker($interval['end'], $time_format),
                    ];
                }
            }

            if (!count($appointments_availability) or $first_interval === null) continue;

            $max_days_for_booking = (int) get_option('bookly_gen_max_days_for_booking', 365);
            if ($max_days_for_booking <= 0) $max_days_for_booking = 365;

            $today = wp_date('Y-m-d');
            $horizon = wp_date('Y-m-d', strtotime('+' . $max_days_for_booking . ' days', strtotime($today)));

            if ($holidays_available) $holiday_rows = $wpdb->get_results("SELECT `date`, `repeat_event` FROM `{$holidays_table}` WHERE `staff_id` IN ({$staff_ids_str}) AND `date` IS NOT NULL", ARRAY_A);
            else $holiday_rows = [];
            if (!is_array($holiday_rows)) $holiday_rows = [];

            $holiday_dates = $this->bookly_expand_holidays($holiday_rows, $today, $horizon);
            $adjusted_availability = $this->bookly_prepare_adjusted_availability($holiday_dates);

            $title = $this->bookly_get_service_title($service);
            $description = $service['info'] ?? '';
            $third_party_id = $service_id;

            // Import Categories
            $category_ids = [];
            if ($categories_available and isset($this->ix['import_categories']) and $this->ix['import_categories'] and isset($service['category_id']) and (int) $service['category_id'])
            {
                $bookly_category_id = (int) $service['category_id'];
                $bookly_category = $wpdb->get_row("SELECT * FROM `{$categories_table}` WHERE `id`='{$bookly_category_id}'", ARRAY_A);

                if (is_array($bookly_category) and isset($bookly_category['name']) and trim($bookly_category['name']))
                {
                    $category_id = $this->main->save_category([
                        'name' => trim($bookly_category['name']),
                    ]);

                    if ($category_id) $category_ids[] = $category_id;
                }
            }

            // Import Organizers
            $organizer_id = 1;
            $additional_organizers_ids = [];
            if (isset($this->ix['import_organizers']) and $this->ix['import_organizers'] and count($staff_rows))
            {
                foreach ($staff_rows as $staff_index => $staff)
                {
                    $organizer_name = trim($staff['full_name'] ?? '');
                    if (!trim($organizer_name)) continue;

                    $imported_organizer_id = $this->main->save_organizer([
                        'name' => $organizer_name,
                        'tel' => trim($staff['phone'] ?? ''),
                        'email' => trim($staff['email'] ?? ''),
                    ]);

                    if (!$imported_organizer_id) continue;

                    if ($staff_index === 0) $organizer_id = $imported_organizer_id;
                    else $additional_organizers_ids[] = $imported_organizer_id;
                }
            }

            // Import Location (Best Effort)
            $location_id = 1;
            $location_ids = array_values(array_unique($location_ids));
            if ($locations_available and isset($this->ix['import_locations']) and $this->ix['import_locations'] and count($location_ids))
            {
                $bookly_location_id = (int) $location_ids[0];
                $bookly_location = $wpdb->get_row("SELECT * FROM `{$locations_table}` WHERE `id`='{$bookly_location_id}'", ARRAY_A);

                if (is_array($bookly_location))
                {
                    $location_name = '';
                    if (isset($bookly_location['name']) and trim($bookly_location['name'])) $location_name = trim($bookly_location['name']);
                    else if (isset($bookly_location['title']) and trim($bookly_location['title'])) $location_name = trim($bookly_location['title']);

                    if (trim($location_name))
                    {
                        $location_id = $this->main->save_location([
                            'name' => $location_name,
                            'address' => trim($bookly_location['address'] ?? ''),
                            'latitude' => trim($bookly_location['latitude'] ?? ($bookly_location['lat'] ?? 0)),
                            'longitude' => trim($bookly_location['longitude'] ?? ($bookly_location['lng'] ?? 0)),
                        ]);
                    }
                }
            }

            $duration = (isset($service['duration']) and is_numeric($service['duration'])) ? (int) round((((int) $service['duration']) / 60)) : 60;
            if ($duration <= 0) $duration = 10;

            $buffer = $this->bookly_resolve_buffer_minutes($service, $duration);

            $limit_period = $service['limit_period'] ?? 'off';
            $appointments_limit = (isset($service['appointments_limit']) and is_numeric($service['appointments_limit'])) ? (int) $service['appointments_limit'] : 0;
            $max_bookings_per_day = (in_array($limit_period, ['day', 'calendar_day']) and $appointments_limit > 0) ? $appointments_limit : 0;

            $global_min_time_prior_booking = (int) get_option('bookly_gen_min_time_prior_booking', 0);
            $service_min_time_prior_booking = ((isset($service['min_time_prior_booking']) and trim((string) $service['min_time_prior_booking']) !== '') ? (int) $service['min_time_prior_booking'] : null);
            $min_time_prior_booking = max(0, ($service_min_time_prior_booking === null ? $global_min_time_prior_booking : $service_min_time_prior_booking));

            $scheduling_before = (int) ceil($min_time_prior_booking / HOUR_IN_SECONDS);
            $scheduling_before_status = ($scheduling_before > 0 ? 1 : 0);
            if ($scheduling_before <= 0) $scheduling_before = 4;

            $appointments_config = [
                'saved' => 1,
                'duration' => $duration,
                'buffer' => $buffer,
                'max_bookings_per_day' => $max_bookings_per_day,
                'availability_repeat_type' => 'weekly',
                'start_date' => $today,
                'availability' => $appointments_availability,
                'adjusted_availability' => $adjusted_availability,
                'scheduling_advance_status' => 1,
                'scheduling_advance' => $max_days_for_booking,
                'scheduling_before_status' => $scheduling_before_status,
                'scheduling_before' => $scheduling_before,
            ];

            $event_start_date = $this->bookly_get_next_date_for_day((int) ($first_interval['day'] ?? 0), $today);
            $event_start_time = $this->bookly_minutes_to_event_time((int) ($first_interval['start'] ?? 480));
            $event_end_time = $this->bookly_minutes_to_event_time((int) ($first_interval['end'] ?? 540), 'end');

            $args = [
                'title' => $title,
                'content' => $description,
                'location_id' => $location_id,
                'organizer_id' => $organizer_id,
                'date' => [
                    'start' => [
                        'date' => $event_start_date,
                        'hour' => $event_start_time['hour'],
                        'minutes' => $event_start_time['minutes'],
                        'ampm' => $event_start_time['ampm'],
                    ],
                    'end' => [
                        'date' => $event_start_date,
                        'hour' => $event_end_time['hour'],
                        'minutes' => $event_end_time['minutes'],
                        'ampm' => $event_end_time['ampm'],
                    ],
                    'repeat' => [
                        'end' => 'never',
                        'end_at_date' => '',
                        'end_at_occurrences' => 10,
                    ],
                    'allday' => 0,
                    'comment' => '',
                    'hide_time' => 0,
                    'hide_end_time' => 0,
                ],
                'start' => $event_start_date,
                'start_time_hour' => $event_start_time['hour'],
                'start_time_minutes' => $event_start_time['minutes'],
                'start_time_ampm' => $event_start_time['ampm'],
                'end' => $event_start_date,
                'end_time_hour' => $event_end_time['hour'],
                'end_time_minutes' => $event_end_time['minutes'],
                'end_time_ampm' => $event_end_time['ampm'],
                'repeat_status' => 1,
                'repeat_type' => 'custom_days',
                'interval' => null,
                'finish' => null,
                'year' => null,
                'month' => null,
                'day' => null,
                'week' => null,
                'weekday' => null,
                'weekdays' => null,
                'days' => '',
                'meta' => [
                    'mec_source' => 'bookly',
                    'mec_bookly_service_id' => $third_party_id,
                    'mec_bookly_service_type' => ($service['type'] ?? 'simple'),
                    'mec_allday' => 0,
                    'hide_end_time' => 0,
                    'mec_repeat_end' => 'never',
                    'mec_repeat_end_at_occurrences' => 9,
                    'mec_repeat_end_at_date' => '',
                    'mec_in_days' => '',
                    'mec_more_info' => '',
                    'mec_cost' => ($service['price'] ?? ''),
                ],
            ];

            $post_id = $this->db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='{$third_party_id}' AND `meta_key`='mec_bookly_service_id'", 'loadResult');

            // Insert / Update the event into MEC
            $post_id = $this->main->save_event($args, $post_id);
            if (!$post_id) continue;

            // Ensure the event title always matches the service title.
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $title,
            ]);

            // Create a single ticket from the Bookly service price.
            $tickets = $this->bookly_prepare_event_tickets($service, $title);
            update_post_meta($post_id, 'mec_booking', [
                'bookings_limit_unlimited' => 1,
                'bookings_limit' => '',
            ]);
            update_post_meta($post_id, 'mec_tickets', $tickets);
            update_post_meta($post_id, 'mec_global_tickets_applied', 1);

            // Featured image from Bookly service attachment.
            if (isset($this->ix['import_featured_image']) and $this->ix['import_featured_image'])
            {
                $attachment_id = (int) ($service['attachment_id'] ?? 0);
                if ($attachment_id and get_post_type($attachment_id) === 'attachment' and wp_attachment_is_image($attachment_id))
                {
                    set_post_thumbnail($post_id, $attachment_id);
                }
            }

            // Convert Event to Appointment
            update_post_meta($post_id, 'mec_entity_type', 'appointment');
            $this->getAppointments()->save($post_id, [
                'entity_type' => 'appointment',
                'appointments' => $appointments_config,
            ]);

            // Set location to the post
            if ($location_id) wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

            // Set organizer to the post
            if ($organizer_id) wp_set_object_terms($post_id, (int) $organizer_id, 'mec_organizer');

            // Set additional organizers
            if (is_array($additional_organizers_ids) and count($additional_organizers_ids))
            {
                foreach ($additional_organizers_ids as $additional_organizers_id) wp_set_object_terms($post_id, (int) $additional_organizers_id, 'mec_organizer', true);
                update_post_meta($post_id, 'mec_additional_organizer_ids', $additional_organizers_ids);
            }
            else delete_post_meta($post_id, 'mec_additional_organizer_ids');

            // Set categories to the post
            if (count($category_ids)) foreach ($category_ids as $category_id) wp_set_object_terms($post_id, (int) $category_id, 'mec_category', true);

            $count++;
        }

        return ['success' => 1, 'data' => $count];
    }

    private function bookly_import_validate_requirements()
    {
        if (!class_exists('Bookly\\Lib\\Plugin')) return ['success' => 0, 'message' => __("Bookly plugin is not installed and activated!", 'modern-events-calendar-lite')];

        if (!$this->bookly_table_exists('services') or !$this->bookly_table_exists('staff_services'))
        {
            return ['success' => 0, 'message' => __("Bookly tables are missing. Please make sure Bookly is installed correctly.", 'modern-events-calendar-lite')];
        }

        if (!$this->getPRO()) return ['success' => 0, 'message' => __("MEC Pro is required to import Bookly services as appointment events.", 'modern-events-calendar-lite')];

        $settings = $this->main->get_settings();
        if (!isset($settings['booking_status']) or !$settings['booking_status'])
        {
            return ['success' => 0, 'message' => __("Please enable MEC Booking module first.", 'modern-events-calendar-lite')];
        }

        if (!isset($settings['appointments_status']) or !$settings['appointments_status'])
        {
            return ['success' => 0, 'message' => __("Please enable MEC Appointments module first.", 'modern-events-calendar-lite')];
        }

        return ['success' => 1];
    }

    private function bookly_import_get_services()
    {
        $wpdb = $this->db->get_DBO();
        $services_table = $this->bookly_table('services');
        $staff_services_table = $this->bookly_table('staff_services');

        $services = $wpdb->get_results("SELECT DISTINCT `s`.`id` AS `ID`, `s`.`title` AS `post_title`, `s`.`position` FROM `{$services_table}` `s` INNER JOIN `{$staff_services_table}` `ss` ON `ss`.`service_id` = `s`.`id` ORDER BY `s`.`position` ASC, `s`.`id` ASC");
        if (!is_array($services)) $services = [];

        foreach ($services as $service)
        {
            $service->post_title = $this->bookly_get_service_title([
                'id' => (int) ($service->ID ?? 0),
                'title' => (string) ($service->post_title ?? ''),
            ]);
        }

        return $services;
    }

    private function bookly_get_service_title($service)
    {
        $service_id = (int) ($service['id'] ?? 0);
        if (!$service_id and isset($service['ID'])) $service_id = (int) $service['ID'];

        $raw_title = trim((string) ($service['title'] ?? ($service['post_title'] ?? '')));
        $title = $raw_title;

        if ($service_id and class_exists('Bookly\\Lib\\Entities\\Service'))
        {
            $bookly_service = \Bookly\Lib\Entities\Service::find($service_id);
            if ($bookly_service and method_exists($bookly_service, 'getTranslatedTitle'))
            {
                $translated_title = trim((string) $bookly_service->getTranslatedTitle());
                if (trim($translated_title)) $title = $translated_title;
            }
        }
        else if ($service_id and class_exists('Bookly\\Lib\\Utils\\Common'))
        {
            $translated_title = trim((string) \Bookly\Lib\Utils\Common::getTranslatedString('service_' . $service_id, $raw_title));
            if (trim($translated_title)) $title = $translated_title;
        }

        if (!trim($title)) $title = sprintf(__('Service %s', 'modern-events-calendar-lite'), $service_id);
        return $title;
    }

    private function bookly_prepare_event_tickets($service, $service_title)
    {
        $ticket_price = 0;
        if (isset($service['price']) and trim((string) $service['price']) !== '' and is_numeric($service['price']))
        {
            $ticket_price = (float) $service['price'];
        }

        return [
            1 => [
                'id' => 1,
                'name' => $service_title,
                'description' => '',
                'price' => $ticket_price,
                'price_label' => '',
                'limit' => '',
                'unlimited' => 1,
                'seats' => 1,
                'minimum_ticket' => 0,
                'maximum_ticket' => '',
                'stop_selling_value' => 0,
                'stop_selling_type' => 'day',
                'dates' => [],
                'category_ids' => [],
            ],
        ];
    }

    private function bookly_resolve_buffer_minutes($service, $duration = 10)
    {
        $duration = max(1, (int) $duration);

        $padding_left = (isset($service['padding_left']) and is_numeric($service['padding_left'])) ? (int) $service['padding_left'] : 0;
        $padding_right = (isset($service['padding_right']) and is_numeric($service['padding_right'])) ? (int) $service['padding_right'] : 0;
        $padding_buffer = (int) round((max(0, $padding_left + $padding_right)) / 60);

        $slot_buffer = 0;
        $slot_length = trim((string) ($service['slot_length'] ?? ''));
        if ($slot_length === 'as_service_duration')
        {
            $slot_buffer = $duration;
        }
        else if ($slot_length === 'default' or $slot_length === '') $slot_buffer = 0;
        else if (is_numeric($slot_length))
        {
            $slot_length_value = (float) $slot_length;
            if ($slot_length_value > 0)
            {
                if ($slot_length_value >= 60) $slot_buffer = (int) ceil($slot_length_value / 60);
                else $slot_buffer = (int) round($slot_length_value);
            }
        }

        return max(0, max($padding_buffer, $slot_buffer));
    }

    private function bookly_table($name)
    {
        $wpdb = $this->db->get_DBO();
        return $wpdb->prefix . 'bookly_' . $name;
    }

    private function bookly_table_exists($name)
    {
        return $this->db->exists('bookly_' . $name);
    }

    private function bookly_day_index_to_mec($bookly_day_index)
    {
        if ($bookly_day_index < 1 or $bookly_day_index > 7) return null;
        return (int) (($bookly_day_index + 5) % 7);
    }

    private function bookly_time_to_minutes($time)
    {
        if (!is_string($time) or !trim($time)) return null;

        $parts = explode(':', $time);
        if (!isset($parts[0]) or !isset($parts[1])) return null;

        $hour = (int) $parts[0];
        $minutes = (int) $parts[1];

        if ($hour < 0 or $hour > 24) return null;
        if ($minutes < 0 or $minutes > 59) return null;
        if ($hour === 24 and $minutes > 0) return null;

        return ($hour * 60) + $minutes;
    }

    private function bookly_split_interval($day, $start, $end)
    {
        $intervals = [];

        // Same-day interval
        if ($end > $start)
        {
            $intervals[] = ['day' => $day, 'start' => $start, 'end' => $end];
        }
        // Overnight interval
        else if ($end < $start)
        {
            $intervals[] = ['day' => $day, 'start' => $start, 'end' => 1440];
            $intervals[] = ['day' => (($day + 1) % 7), 'start' => 0, 'end' => $end];
        }

        return $intervals;
    }

    private function bookly_subtract_intervals($base_intervals, $subtract_intervals)
    {
        $result = $base_intervals;
        foreach ($subtract_intervals as $cut)
        {
            $next = [];
            foreach ($result as $base)
            {
                // No overlap
                if ($cut['end'] <= $base['start'] or $cut['start'] >= $base['end'])
                {
                    $next[] = $base;
                    continue;
                }

                // Left remainder
                if ($cut['start'] > $base['start'])
                {
                    $next[] = ['start' => $base['start'], 'end' => min($cut['start'], $base['end'])];
                }

                // Right remainder
                if ($cut['end'] < $base['end'])
                {
                    $next[] = ['start' => max($cut['end'], $base['start']), 'end' => $base['end']];
                }
            }

            $result = $next;
        }

        return $result;
    }

    private function bookly_merge_intervals($intervals)
    {
        if (!is_array($intervals) or !count($intervals)) return [];

        usort($intervals, function ($a, $b)
        {
            if ($a['start'] == $b['start']) return ($a['end'] <=> $b['end']);
            return ($a['start'] <=> $b['start']);
        });

        $merged = [];
        foreach ($intervals as $interval)
        {
            if (!isset($interval['start']) or !isset($interval['end'])) continue;
            if ($interval['end'] <= $interval['start']) continue;

            if (!count($merged))
            {
                $merged[] = $interval;
                continue;
            }

            $last_key = count($merged) - 1;
            if ($interval['start'] <= $merged[$last_key]['end'])
            {
                $merged[$last_key]['end'] = max($merged[$last_key]['end'], $interval['end']);
            }
            else $merged[] = $interval;
        }

        return $merged;
    }

    private function bookly_minutes_to_timepicker($minutes, $time_format = 12)
    {
        $minutes = max(0, min(1440, (int) $minutes));

        $hour_24 = (int) floor($minutes / 60);
        $minute = (int) ($minutes % 60);

        if ((int) $time_format === 24)
        {
            return [
                'hour' => $hour_24,
                'minutes' => $minute,
                'ampm' => '',
            ];
        }

        $ampm = ($hour_24 >= 12 ? 'PM' : 'AM');
        $hour_12 = ($hour_24 % 12);
        if ($hour_12 === 0) $hour_12 = 12;

        return [
            'hour' => $hour_12,
            'minutes' => $minute,
            'ampm' => $ampm,
        ];
    }

    private function bookly_minutes_to_event_time($minutes, $type = 'start')
    {
        $minutes = max(0, min(1440, (int) $minutes));

        $hour_24 = (int) floor($minutes / 60);
        $minute = (int) ($minutes % 60);

        if ($hour_24 === 24)
        {
            $hour_12 = 12;
            $ampm = 'AM';
        }
        else
        {
            $ampm = ($hour_24 >= 12 ? 'PM' : 'AM');
            $hour_12 = ($hour_24 % 12);
            if ($hour_12 === 0) $hour_12 = 12;
        }

        return [
            'hour' => $hour_12,
            'minutes' => $minute,
            'ampm' => $ampm,
            'type' => $type,
        ];
    }

    private function bookly_get_next_date_for_day($mec_day_index, $from_date = null)
    {
        if (is_null($from_date) or !trim($from_date)) $from_date = wp_date('Y-m-d');

        $from_ts = strtotime($from_date);
        if (!$from_ts) $from_ts = current_time('timestamp');

        // PHP w: 0=Sunday..6=Saturday => MEC day: 0=Monday..6=Sunday
        $php_to_mec = [6, 0, 1, 2, 3, 4, 5];
        $current_mec_day = $php_to_mec[(int) wp_date('w', $from_ts)];

        $delta = ($mec_day_index - $current_mec_day + 7) % 7;
        return wp_date('Y-m-d', strtotime('+' . $delta . ' day', $from_ts));
    }

    private function bookly_expand_holidays($holiday_rows, $from_date, $to_date)
    {
        $expanded = [];
        $from_ts = strtotime($from_date);
        $to_ts = strtotime($to_date);

        if (!$from_ts or !$to_ts or $to_ts < $from_ts) return [];

        foreach ($holiday_rows as $holiday_row)
        {
            $holiday_date = trim($holiday_row['date'] ?? '');
            if (!trim($holiday_date)) continue;

            $repeat_event = (int) ($holiday_row['repeat_event'] ?? 0);

            if (!$repeat_event)
            {
                $ts = strtotime($holiday_date);
                if ($ts and $ts >= $from_ts and $ts <= $to_ts) $expanded[] = date('Y-m-d', $ts);
                continue;
            }

            $month_day = date('m-d', strtotime($holiday_date));
            if (!trim($month_day)) continue;

            $start_year = (int) date('Y', $from_ts);
            $end_year = (int) date('Y', $to_ts);
            for ($year = $start_year; $year <= $end_year; $year++)
            {
                $candidate = $year . '-' . $month_day;
                $candidate_ts = strtotime($candidate);
                if (!$candidate_ts) continue;

                if ($candidate_ts >= $from_ts and $candidate_ts <= $to_ts) $expanded[] = date('Y-m-d', $candidate_ts);
            }
        }

        $expanded = array_values(array_unique($expanded));
        sort($expanded);

        return $expanded;
    }

    private function bookly_prepare_adjusted_availability($holiday_dates)
    {
        $adjusted = [];
        $i = 0;

        foreach ($holiday_dates as $holiday_date)
        {
            $adjusted[$i] = ['date' => $holiday_date];
            $i++;
        }

        return $adjusted;
    }

    public function thirdparty_amelia_import_do($IDs)
    {
        $validation = $this->amelia_import_validate_requirements();
        if (!$validation['success']) return $validation;

        $count = 0;
        $wpdb = $this->db->get_DBO();

        $settings = $this->main->get_settings();
        $time_format = ((isset($settings['time_format']) and (int) $settings['time_format'] === 24) ? 24 : 12);

        $amelia_settings = $this->amelia_get_settings();
        $general_settings = (isset($amelia_settings['general']) and is_array($amelia_settings['general'])) ? $amelia_settings['general'] : [];

        $max_days_for_booking = (int) ($general_settings['numberOfDaysAvailableForBooking'] ?? 365);
        if ($max_days_for_booking <= 0) $max_days_for_booking = 365;

        $global_min_time_prior_booking = (int) ($general_settings['minimumTimeRequirementPriorToBooking'] ?? 0);
        if ($global_min_time_prior_booking < 0) $global_min_time_prior_booking = 0;

        $today = wp_date('Y-m-d');
        $horizon = wp_date('Y-m-d', strtotime('+' . $max_days_for_booking . ' days', strtotime($today)));

        $services_table = $this->amelia_table('services');
        $provider_services_table = $this->amelia_table('providers_to_services');
        $users_table = $this->amelia_table('users');
        $weekdays_table = $this->amelia_table('providers_to_weekdays');
        $timeouts_table = $this->amelia_table('providers_to_timeouts');
        $periods_table = $this->amelia_table('providers_to_periods');
        $period_services_table = $this->amelia_table('providers_to_periods_services');
        $period_locations_table = $this->amelia_table('providers_to_periods_location');
        $specialdays_table = $this->amelia_table('providers_to_specialdays');
        $specialday_periods_table = $this->amelia_table('providers_to_specialdays_periods');
        $specialday_period_services_table = $this->amelia_table('providers_to_specialdays_periods_services');
        $specialday_period_locations_table = $this->amelia_table('providers_to_specialdays_periods_location');
        $daysoff_table = $this->amelia_table('providers_to_daysoff');
        $provider_locations_table = $this->amelia_table('providers_to_locations');
        $categories_table = $this->amelia_table('categories');
        $locations_table = $this->amelia_table('locations');

        $timeouts_available = $this->amelia_table_exists('providers_to_timeouts');
        $periods_available = $this->amelia_table_exists('providers_to_periods');
        $period_services_available = $this->amelia_table_exists('providers_to_periods_services');
        $period_locations_available = $this->amelia_table_exists('providers_to_periods_location');
        $specialdays_available = $this->amelia_table_exists('providers_to_specialdays');
        $specialday_periods_available = $this->amelia_table_exists('providers_to_specialdays_periods');
        $specialday_period_services_available = $this->amelia_table_exists('providers_to_specialdays_periods_services');
        $specialday_period_locations_available = $this->amelia_table_exists('providers_to_specialdays_periods_location');
        $daysoff_available = $this->amelia_table_exists('providers_to_daysoff');
        $provider_locations_available = $this->amelia_table_exists('providers_to_locations');
        $categories_available = $this->amelia_table_exists('categories');
        $locations_available = $this->amelia_table_exists('locations');

        foreach ($IDs as $ID)
        {
            $service_id = (int) $ID;
            if (!$service_id) continue;

            $service = $wpdb->get_row("SELECT * FROM `{$services_table}` WHERE `id`='{$service_id}'", ARRAY_A);
            if (!is_array($service) or !isset($service['id'])) continue;
            if (isset($service['status']) and trim((string) $service['status']) === 'disabled') continue;

            $provider_service_rows = $wpdb->get_results("SELECT * FROM `{$provider_services_table}` WHERE `serviceId`='{$service_id}'", ARRAY_A);
            if (!is_array($provider_service_rows) or !count($provider_service_rows)) continue;

            $provider_ids = [];
            foreach ($provider_service_rows as $provider_service_row)
            {
                $provider_id = (int) ($provider_service_row['userId'] ?? 0);
                if ($provider_id) $provider_ids[] = $provider_id;
            }

            $provider_ids = array_values(array_unique($provider_ids));
            if (!count($provider_ids)) continue;

            $provider_ids_str = implode(',', array_map('intval', $provider_ids));
            $provider_rows = $wpdb->get_results("SELECT * FROM `{$users_table}` WHERE `id` IN ({$provider_ids_str}) AND `type`='provider' AND (`status` IS NULL OR `status`!='disabled')", ARRAY_A);
            if (!is_array($provider_rows) or !count($provider_rows)) continue;

            $provider_ids = [];
            foreach ($provider_rows as $provider_row)
            {
                $provider_id = (int) ($provider_row['id'] ?? 0);
                if ($provider_id) $provider_ids[] = $provider_id;
            }

            $provider_ids = array_values(array_unique($provider_ids));
            if (!count($provider_ids)) continue;

            $provider_ids_str = implode(',', array_map('intval', $provider_ids));

            $weekday_rows = $wpdb->get_results("SELECT * FROM `{$weekdays_table}` WHERE `userId` IN ({$provider_ids_str}) AND `startTime` IS NOT NULL AND `endTime` IS NOT NULL", ARRAY_A);
            if (!is_array($weekday_rows) or !count($weekday_rows)) continue;

            $weekdays_by_provider = [];
            $weekday_ids = [];
            foreach ($weekday_rows as $weekday_row)
            {
                $provider_id = (int) ($weekday_row['userId'] ?? 0);
                if (!$provider_id) continue;

                $weekday_id = (int) ($weekday_row['id'] ?? 0);
                if ($weekday_id) $weekday_ids[] = $weekday_id;

                if (!isset($weekdays_by_provider[$provider_id])) $weekdays_by_provider[$provider_id] = [];
                $weekdays_by_provider[$provider_id][] = $weekday_row;
            }

            $weekday_ids = array_values(array_unique($weekday_ids));
            $timeouts_by_weekday = [];
            if ($timeouts_available and count($weekday_ids))
            {
                $weekday_ids_str = implode(',', array_map('intval', $weekday_ids));
                $timeout_rows = $wpdb->get_results("SELECT * FROM `{$timeouts_table}` WHERE `weekDayId` IN ({$weekday_ids_str})", ARRAY_A);
                if (is_array($timeout_rows) and count($timeout_rows))
                {
                    foreach ($timeout_rows as $timeout_row)
                    {
                        $weekday_id = (int) ($timeout_row['weekDayId'] ?? 0);
                        if (!$weekday_id) continue;

                        if (!isset($timeouts_by_weekday[$weekday_id])) $timeouts_by_weekday[$weekday_id] = [];
                        $timeouts_by_weekday[$weekday_id][] = $timeout_row;
                    }
                }
            }

            $periods_by_weekday = [];
            $period_services_by_period = [];
            $period_locations_by_period = [];
            $period_ids = [];

            if ($periods_available and count($weekday_ids))
            {
                $weekday_ids_str = implode(',', array_map('intval', $weekday_ids));
                $period_rows = $wpdb->get_results("SELECT * FROM `{$periods_table}` WHERE `weekDayId` IN ({$weekday_ids_str})", ARRAY_A);
                if (is_array($period_rows) and count($period_rows))
                {
                    foreach ($period_rows as $period_row)
                    {
                        $weekday_id = (int) ($period_row['weekDayId'] ?? 0);
                        $period_id = (int) ($period_row['id'] ?? 0);
                        if (!$weekday_id or !$period_id) continue;

                        if (!isset($periods_by_weekday[$weekday_id])) $periods_by_weekday[$weekday_id] = [];
                        $periods_by_weekday[$weekday_id][] = $period_row;
                        $period_ids[] = $period_id;
                    }
                }
            }

            $period_ids = array_values(array_unique($period_ids));
            if ($period_services_available and count($period_ids))
            {
                $period_ids_str = implode(',', array_map('intval', $period_ids));
                $period_service_rows = $wpdb->get_results("SELECT * FROM `{$period_services_table}` WHERE `periodId` IN ({$period_ids_str})", ARRAY_A);
                if (is_array($period_service_rows) and count($period_service_rows))
                {
                    foreach ($period_service_rows as $period_service_row)
                    {
                        $period_id = (int) ($period_service_row['periodId'] ?? 0);
                        $matched_service_id = (int) ($period_service_row['serviceId'] ?? 0);
                        if (!$period_id or !$matched_service_id) continue;

                        if (!isset($period_services_by_period[$period_id])) $period_services_by_period[$period_id] = [];
                        $period_services_by_period[$period_id][] = $matched_service_id;
                    }
                }
            }

            if ($period_locations_available and count($period_ids))
            {
                $period_ids_str = implode(',', array_map('intval', $period_ids));
                $period_location_rows = $wpdb->get_results("SELECT * FROM `{$period_locations_table}` WHERE `periodId` IN ({$period_ids_str})", ARRAY_A);
                if (is_array($period_location_rows) and count($period_location_rows))
                {
                    foreach ($period_location_rows as $period_location_row)
                    {
                        $period_id = (int) ($period_location_row['periodId'] ?? 0);
                        $location_id = (int) ($period_location_row['locationId'] ?? 0);
                        if (!$period_id or !$location_id) continue;

                        if (!isset($period_locations_by_period[$period_id])) $period_locations_by_period[$period_id] = [];
                        $period_locations_by_period[$period_id][] = $location_id;
                    }
                }
            }

            $special_days_by_provider = [];
            $special_periods_by_special_day = [];
            $special_period_services_by_period = [];
            $special_period_locations_by_period = [];
            $dayoffs_by_provider = [];
            $provider_locations_by_provider = [];

            $special_day_ids = [];
            if ($specialdays_available)
            {
                $special_day_rows = $wpdb->get_results("SELECT * FROM `{$specialdays_table}` WHERE `userId` IN ({$provider_ids_str}) AND `endDate` >= '{$today}' AND `startDate` <= '{$horizon}'", ARRAY_A);
                if (is_array($special_day_rows) and count($special_day_rows))
                {
                    foreach ($special_day_rows as $special_day_row)
                    {
                        $provider_id = (int) ($special_day_row['userId'] ?? 0);
                        $special_day_id = (int) ($special_day_row['id'] ?? 0);
                        if (!$provider_id or !$special_day_id) continue;

                        if (!isset($special_days_by_provider[$provider_id])) $special_days_by_provider[$provider_id] = [];
                        $special_days_by_provider[$provider_id][] = $special_day_row;
                        $special_day_ids[] = $special_day_id;
                    }
                }
            }

            $special_day_ids = array_values(array_unique($special_day_ids));
            $special_period_ids = [];
            if ($specialday_periods_available and count($special_day_ids))
            {
                $special_day_ids_str = implode(',', array_map('intval', $special_day_ids));
                $special_period_rows = $wpdb->get_results("SELECT * FROM `{$specialday_periods_table}` WHERE `specialDayId` IN ({$special_day_ids_str})", ARRAY_A);
                if (is_array($special_period_rows) and count($special_period_rows))
                {
                    foreach ($special_period_rows as $special_period_row)
                    {
                        $special_day_id = (int) ($special_period_row['specialDayId'] ?? 0);
                        $special_period_id = (int) ($special_period_row['id'] ?? 0);
                        if (!$special_day_id or !$special_period_id) continue;

                        if (!isset($special_periods_by_special_day[$special_day_id])) $special_periods_by_special_day[$special_day_id] = [];
                        $special_periods_by_special_day[$special_day_id][] = $special_period_row;
                        $special_period_ids[] = $special_period_id;
                    }
                }
            }

            $special_period_ids = array_values(array_unique($special_period_ids));
            if ($specialday_period_services_available and count($special_period_ids))
            {
                $special_period_ids_str = implode(',', array_map('intval', $special_period_ids));
                $special_period_service_rows = $wpdb->get_results("SELECT * FROM `{$specialday_period_services_table}` WHERE `periodId` IN ({$special_period_ids_str})", ARRAY_A);
                if (is_array($special_period_service_rows) and count($special_period_service_rows))
                {
                    foreach ($special_period_service_rows as $special_period_service_row)
                    {
                        $period_id = (int) ($special_period_service_row['periodId'] ?? 0);
                        $matched_service_id = (int) ($special_period_service_row['serviceId'] ?? 0);
                        if (!$period_id or !$matched_service_id) continue;

                        if (!isset($special_period_services_by_period[$period_id])) $special_period_services_by_period[$period_id] = [];
                        $special_period_services_by_period[$period_id][] = $matched_service_id;
                    }
                }
            }

            if ($specialday_period_locations_available and count($special_period_ids))
            {
                $special_period_ids_str = implode(',', array_map('intval', $special_period_ids));
                $special_period_location_rows = $wpdb->get_results("SELECT * FROM `{$specialday_period_locations_table}` WHERE `periodId` IN ({$special_period_ids_str})", ARRAY_A);
                if (is_array($special_period_location_rows) and count($special_period_location_rows))
                {
                    foreach ($special_period_location_rows as $special_period_location_row)
                    {
                        $period_id = (int) ($special_period_location_row['periodId'] ?? 0);
                        $location_id = (int) ($special_period_location_row['locationId'] ?? 0);
                        if (!$period_id or !$location_id) continue;

                        if (!isset($special_period_locations_by_period[$period_id])) $special_period_locations_by_period[$period_id] = [];
                        $special_period_locations_by_period[$period_id][] = $location_id;
                    }
                }
            }

            if ($daysoff_available)
            {
                $dayoff_rows = $wpdb->get_results("SELECT * FROM `{$daysoff_table}` WHERE `userId` IN ({$provider_ids_str}) AND (`repeat`='1' OR (`endDate` >= '{$today}' AND `startDate` <= '{$horizon}'))", ARRAY_A);
                if (is_array($dayoff_rows) and count($dayoff_rows))
                {
                    foreach ($dayoff_rows as $dayoff_row)
                    {
                        $provider_id = (int) ($dayoff_row['userId'] ?? 0);
                        if (!$provider_id) continue;

                        if (!isset($dayoffs_by_provider[$provider_id])) $dayoffs_by_provider[$provider_id] = [];
                        $dayoffs_by_provider[$provider_id][] = $dayoff_row;
                    }
                }
            }

            if ($provider_locations_available)
            {
                $provider_location_rows = $wpdb->get_results("SELECT * FROM `{$provider_locations_table}` WHERE `userId` IN ({$provider_ids_str})", ARRAY_A);
                if (is_array($provider_location_rows) and count($provider_location_rows))
                {
                    foreach ($provider_location_rows as $provider_location_row)
                    {
                        $provider_id = (int) ($provider_location_row['userId'] ?? 0);
                        $location_id = (int) ($provider_location_row['locationId'] ?? 0);
                        if (!$provider_id or !$location_id) continue;

                        if (!isset($provider_locations_by_provider[$provider_id])) $provider_locations_by_provider[$provider_id] = [];
                        $provider_locations_by_provider[$provider_id][] = $location_id;
                    }
                }
            }

            $provider_weekly_availability = [];
            $availability_by_day = [0 => [], 1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => []];
            $source_location_ids = [];

            foreach ($provider_ids as $provider_id)
            {
                $provider_weekly = $this->amelia_build_provider_weekly_availability(
                    $provider_id,
                    $service_id,
                    $weekdays_by_provider,
                    $timeouts_by_weekday,
                    $periods_by_weekday,
                    $period_services_by_period,
                    $period_locations_by_period,
                    $source_location_ids
                );

                $provider_weekly_availability[$provider_id] = $provider_weekly;

                for ($d = 0; $d <= 6; $d++)
                {
                    if (!isset($provider_weekly[$d]) or !count($provider_weekly[$d])) continue;
                    $availability_by_day[$d] = array_merge($availability_by_day[$d], $provider_weekly[$d]);
                }

                $provider_location_ids = $provider_locations_by_provider[$provider_id] ?? [];
                if (count($provider_location_ids)) $source_location_ids = array_merge($source_location_ids, $provider_location_ids);
            }

            $appointments_availability = [];
            $first_interval = null;
            for ($d = 0; $d <= 6; $d++)
            {
                $availability_by_day[$d] = $this->amelia_merge_intervals($availability_by_day[$d]);
                if (!count($availability_by_day[$d])) continue;

                foreach ($availability_by_day[$d] as $interval)
                {
                    if ($first_interval === null) $first_interval = ['day' => $d, 'start' => $interval['start'], 'end' => $interval['end']];

                    $appointments_availability[$d][] = [
                        'start' => $this->amelia_minutes_to_timepicker($interval['start'], $time_format),
                        'end' => $this->amelia_minutes_to_timepicker($interval['end'], $time_format),
                    ];
                }
            }

            if (!count($appointments_availability) or $first_interval === null) continue;

            $global_days_off = $this->amelia_expand_global_days_off(($amelia_settings['daysOff'] ?? []), $today, $horizon);
            $adjusted_availability = [];

            $cursor = strtotime($today);
            $end_ts = strtotime($horizon);
            while ($cursor and $cursor <= $end_ts)
            {
                $date = wp_date('Y-m-d', $cursor);
                $weekday = ((int) wp_date('N', $cursor)) - 1;

                $is_global_day_off = isset($global_days_off[$date]);
                $has_adjustment = $is_global_day_off;
                $date_intervals = [];

                foreach ($provider_ids as $provider_id)
                {
                    if ($is_global_day_off) continue;

                    if ($this->amelia_is_provider_day_off_on_date($provider_id, $date, $dayoffs_by_provider))
                    {
                        $has_adjustment = true;
                        continue;
                    }

                    $special_intervals = $this->amelia_get_provider_special_day_intervals_on_date(
                        $provider_id,
                        $date,
                        $service_id,
                        $special_days_by_provider,
                        $special_periods_by_special_day,
                        $special_period_services_by_period,
                        $special_period_locations_by_period,
                        $source_location_ids
                    );

                    if (!is_null($special_intervals))
                    {
                        $has_adjustment = true;
                        if (count($special_intervals)) $date_intervals = array_merge($date_intervals, $special_intervals);
                        continue;
                    }

                    $weekly_intervals = $provider_weekly_availability[$provider_id][$weekday] ?? [];
                    if (count($weekly_intervals)) $date_intervals = array_merge($date_intervals, $weekly_intervals);
                }

                $date_intervals = $this->amelia_merge_intervals($date_intervals);

                if (!$has_adjustment)
                {
                    $baseline = $availability_by_day[$weekday] ?? [];
                    if (!$this->amelia_intervals_equal($baseline, $date_intervals)) $has_adjustment = true;
                }

                if ($has_adjustment) $adjusted_availability[] = $this->amelia_prepare_adjusted_availability($date, $date_intervals, $time_format);

                $cursor = strtotime('+1 day', $cursor);
            }

            $source_location_ids = array_values(array_unique(array_filter(array_map('intval', $source_location_ids))));
            $provider_ids = array_values(array_unique(array_filter(array_map('intval', $provider_ids))));

            $title = $this->amelia_get_service_title($service);
            $description = $service['description'] ?? '';
            $third_party_id = $service_id;

            // Import Categories
            $category_ids = [];
            if ($categories_available and isset($this->ix['import_categories']) and $this->ix['import_categories'] and isset($service['categoryId']) and (int) $service['categoryId'])
            {
                $category_id = (int) $service['categoryId'];
                $amelia_category = $wpdb->get_row("SELECT * FROM `{$categories_table}` WHERE `id`='{$category_id}'", ARRAY_A);

                if (is_array($amelia_category) and isset($amelia_category['name']) and trim($amelia_category['name']))
                {
                    $imported_category_id = $this->main->save_category([
                        'name' => trim($amelia_category['name']),
                    ]);

                    if ($imported_category_id) $category_ids[] = $imported_category_id;
                }
            }

            // Import Organizers
            $organizer_id = 1;
            $additional_organizers_ids = [];
            if (isset($this->ix['import_organizers']) and $this->ix['import_organizers'] and count($provider_rows))
            {
                foreach ($provider_rows as $provider_index => $provider_row)
                {
                    $first_name = trim((string) ($provider_row['firstName'] ?? ''));
                    $last_name = trim((string) ($provider_row['lastName'] ?? ''));
                    $organizer_name = trim(trim($first_name . ' ' . $last_name));
                    if (!trim($organizer_name)) continue;

                    $imported_organizer_id = $this->main->save_organizer([
                        'name' => $organizer_name,
                        'tel' => trim((string) ($provider_row['phone'] ?? '')),
                        'email' => trim((string) ($provider_row['email'] ?? '')),
                    ]);

                    if (!$imported_organizer_id) continue;

                    if ($provider_index === 0) $organizer_id = $imported_organizer_id;
                    else $additional_organizers_ids[] = $imported_organizer_id;
                }
            }

            // Import Locations
            $location_id = 1;
            $additional_location_ids = [];
            if ($locations_available and isset($this->ix['import_locations']) and $this->ix['import_locations'] and count($source_location_ids))
            {
                foreach ($source_location_ids as $source_location_id)
                {
                    $amelia_location = $wpdb->get_row("SELECT * FROM `{$locations_table}` WHERE `id`='{$source_location_id}'", ARRAY_A);
                    if (!is_array($amelia_location)) continue;

                    $location_name = trim((string) ($amelia_location['name'] ?? ''));
                    if (!trim($location_name)) continue;

                    $imported_location_id = $this->main->save_location([
                        'name' => $location_name,
                        'address' => trim((string) ($amelia_location['address'] ?? '')),
                        'latitude' => trim((string) ($amelia_location['latitude'] ?? 0)),
                        'longitude' => trim((string) ($amelia_location['longitude'] ?? 0)),
                    ]);

                    if (!$imported_location_id) continue;

                    if ($location_id === 1) $location_id = $imported_location_id;
                    else $additional_location_ids[] = $imported_location_id;
                }
            }

            $duration = (isset($service['duration']) and is_numeric($service['duration'])) ? (int) round((((int) $service['duration']) / 60)) : 60;
            if ($duration <= 0) $duration = 10;

            $time_before = (isset($service['timeBefore']) and is_numeric($service['timeBefore'])) ? (int) $service['timeBefore'] : 0;
            $time_after = (isset($service['timeAfter']) and is_numeric($service['timeAfter'])) ? (int) $service['timeAfter'] : 0;
            $buffer = (int) ceil(max(0, ($time_before + $time_after)) / 60);

            $max_bookings_per_day = 0;

            $scheduling_before = (int) ceil($global_min_time_prior_booking / HOUR_IN_SECONDS);
            $scheduling_before_status = ($scheduling_before > 0 ? 1 : 0);
            if ($scheduling_before <= 0) $scheduling_before = 4;

            $appointments_config = [
                'saved' => 1,
                'duration' => $duration,
                'buffer' => $buffer,
                'max_bookings_per_day' => $max_bookings_per_day,
                'availability_repeat_type' => 'weekly',
                'start_date' => $today,
                'availability' => $appointments_availability,
                'adjusted_availability' => $adjusted_availability,
                'scheduling_advance_status' => 1,
                'scheduling_advance' => $max_days_for_booking,
                'scheduling_before_status' => $scheduling_before_status,
                'scheduling_before' => $scheduling_before,
            ];

            $event_start_date = $this->amelia_get_next_date_for_day((int) ($first_interval['day'] ?? 0), $today);
            $event_start_time = $this->amelia_minutes_to_event_time((int) ($first_interval['start'] ?? 480));
            $event_end_time = $this->amelia_minutes_to_event_time((int) ($first_interval['end'] ?? 540), 'end');

            $args = [
                'title' => $title,
                'content' => $description,
                'location_id' => $location_id,
                'organizer_id' => $organizer_id,
                'date' => [
                    'start' => [
                        'date' => $event_start_date,
                        'hour' => $event_start_time['hour'],
                        'minutes' => $event_start_time['minutes'],
                        'ampm' => $event_start_time['ampm'],
                    ],
                    'end' => [
                        'date' => $event_start_date,
                        'hour' => $event_end_time['hour'],
                        'minutes' => $event_end_time['minutes'],
                        'ampm' => $event_end_time['ampm'],
                    ],
                    'repeat' => [
                        'end' => 'never',
                        'end_at_date' => '',
                        'end_at_occurrences' => 10,
                    ],
                    'allday' => 0,
                    'comment' => '',
                    'hide_time' => 0,
                    'hide_end_time' => 0,
                ],
                'start' => $event_start_date,
                'start_time_hour' => $event_start_time['hour'],
                'start_time_minutes' => $event_start_time['minutes'],
                'start_time_ampm' => $event_start_time['ampm'],
                'end' => $event_start_date,
                'end_time_hour' => $event_end_time['hour'],
                'end_time_minutes' => $event_end_time['minutes'],
                'end_time_ampm' => $event_end_time['ampm'],
                'repeat_status' => 1,
                'repeat_type' => 'custom_days',
                'interval' => null,
                'finish' => null,
                'year' => null,
                'month' => null,
                'day' => null,
                'week' => null,
                'weekday' => null,
                'weekdays' => null,
                'days' => '',
                'meta' => [
                    'mec_source' => 'amelia',
                    'mec_amelia_service_id' => $third_party_id,
                    'mec_amelia_service_status' => ($service['status'] ?? ''),
                    'mec_amelia_provider_ids' => implode(',', $provider_ids),
                    'mec_amelia_location_ids' => implode(',', $source_location_ids),
                    'mec_allday' => 0,
                    'hide_end_time' => 0,
                    'mec_repeat_end' => 'never',
                    'mec_repeat_end_at_occurrences' => 9,
                    'mec_repeat_end_at_date' => '',
                    'mec_in_days' => '',
                    'mec_more_info' => '',
                    'mec_cost' => ($service['price'] ?? ''),
                ],
            ];

            $post_id = $this->db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='{$third_party_id}' AND `meta_key`='mec_amelia_service_id'", 'loadResult');

            // Insert / Update the event into MEC
            $post_id = $this->main->save_event($args, $post_id);
            if (!$post_id) continue;

            // Keep imported title synced with Amelia service title.
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $title,
            ]);

            // Create one ticket using Amelia service base price.
            $tickets = $this->amelia_prepare_event_tickets($service, $title);
            update_post_meta($post_id, 'mec_booking', [
                'bookings_limit_unlimited' => 1,
                'bookings_limit' => '',
            ]);
            update_post_meta($post_id, 'mec_tickets', $tickets);
            update_post_meta($post_id, 'mec_global_tickets_applied', 1);

            // Featured image from Amelia service picture paths.
            if (isset($this->ix['import_featured_image']) and $this->ix['import_featured_image'] and !has_post_thumbnail($post_id))
            {
                $featured_image = $this->amelia_resolve_image_url(($service['pictureFullPath'] ?? ($service['pictureThumbPath'] ?? '')));
                if (trim($featured_image)) $this->main->set_featured_image($featured_image, $post_id);
            }

            // Convert Event to Appointment
            update_post_meta($post_id, 'mec_entity_type', 'appointment');
            $this->getAppointments()->save($post_id, [
                'entity_type' => 'appointment',
                'appointments' => $appointments_config,
            ]);

            // Set location to the post
            if ($location_id) wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

            // Set additional locations
            if (is_array($additional_location_ids) and count($additional_location_ids))
            {
                $additional_location_ids = array_values(array_unique(array_map('intval', $additional_location_ids)));
                foreach ($additional_location_ids as $additional_location_id) wp_set_object_terms($post_id, (int) $additional_location_id, 'mec_location', true);
            }

            // Set organizer to the post
            if ($organizer_id) wp_set_object_terms($post_id, (int) $organizer_id, 'mec_organizer');

            // Set additional organizers
            if (is_array($additional_organizers_ids) and count($additional_organizers_ids))
            {
                $additional_organizers_ids = array_values(array_unique(array_map('intval', $additional_organizers_ids)));
                foreach ($additional_organizers_ids as $additional_organizers_id) wp_set_object_terms($post_id, (int) $additional_organizers_id, 'mec_organizer', true);
                update_post_meta($post_id, 'mec_additional_organizer_ids', $additional_organizers_ids);
            }
            else delete_post_meta($post_id, 'mec_additional_organizer_ids');

            // Set categories to the post
            if (count($category_ids))
            {
                $category_ids = array_values(array_unique(array_map('intval', $category_ids)));
                foreach ($category_ids as $category_id) wp_set_object_terms($post_id, (int) $category_id, 'mec_category', true);
            }

            $count++;
        }

        return ['success' => 1, 'data' => $count];
    }

    private function amelia_import_validate_requirements()
    {
        if (!class_exists('AmeliaBooking\\Plugin')) return ['success' => 0, 'message' => __("Amelia plugin is not installed and activated!", 'modern-events-calendar-lite')];

        if (
            !$this->amelia_table_exists('services') or
            !$this->amelia_table_exists('providers_to_services') or
            !$this->amelia_table_exists('users') or
            !$this->amelia_table_exists('providers_to_weekdays')
        )
        {
            return ['success' => 0, 'message' => __("Amelia tables are missing. Please make sure Amelia is installed correctly.", 'modern-events-calendar-lite')];
        }

        if (!$this->getPRO()) return ['success' => 0, 'message' => __("MEC Pro is required to import Amelia services as appointment events.", 'modern-events-calendar-lite')];

        $settings = $this->main->get_settings();
        if (!isset($settings['booking_status']) or !$settings['booking_status'])
        {
            return ['success' => 0, 'message' => __("Please enable MEC Booking module first.", 'modern-events-calendar-lite')];
        }

        if (!isset($settings['appointments_status']) or !$settings['appointments_status'])
        {
            return ['success' => 0, 'message' => __("Please enable MEC Appointments module first.", 'modern-events-calendar-lite')];
        }

        return ['success' => 1];
    }

    private function amelia_import_get_services()
    {
        $wpdb = $this->db->get_DBO();
        $services_table = $this->amelia_table('services');
        $provider_services_table = $this->amelia_table('providers_to_services');

        $services = $wpdb->get_results("SELECT DISTINCT `s`.`id` AS `ID`, `s`.`name` AS `post_title`, `s`.`translations` AS `translations`, `s`.`position` FROM `{$services_table}` `s` INNER JOIN `{$provider_services_table}` `ps` ON `ps`.`serviceId` = `s`.`id` WHERE (`s`.`status` IS NULL OR `s`.`status`!='disabled') ORDER BY `s`.`position` ASC, `s`.`id` ASC");
        if (!is_array($services)) $services = [];

        foreach ($services as $service)
        {
            $service->post_title = $this->amelia_get_service_title([
                'id' => (int) ($service->ID ?? 0),
                'name' => (string) ($service->post_title ?? ''),
                'translations' => (string) ($service->translations ?? ''),
            ]);
        }

        return $services;
    }

    private function amelia_get_service_title($service)
    {
        $service_id = (int) ($service['id'] ?? 0);
        if (!$service_id and isset($service['ID'])) $service_id = (int) $service['ID'];

        $title = trim((string) ($service['name'] ?? ($service['post_title'] ?? '')));

        $translations_raw = $service['translations'] ?? '';
        if (is_string($translations_raw) and trim($translations_raw))
        {
            $translations = json_decode($translations_raw, true);
            if (is_array($translations))
            {
                $candidates = [];
                $user_locale = trim((string) get_user_locale());
                $site_locale = trim((string) get_locale());

                if ($user_locale) $candidates[] = $user_locale;
                if ($site_locale and !in_array($site_locale, $candidates)) $candidates[] = $site_locale;

                if ($user_locale and strpos($user_locale, '_') !== false)
                {
                    $short_locale = explode('_', $user_locale)[0];
                    if ($short_locale and !in_array($short_locale, $candidates)) $candidates[] = $short_locale;
                }

                if ($site_locale and strpos($site_locale, '_') !== false)
                {
                    $short_locale = explode('_', $site_locale)[0];
                    if ($short_locale and !in_array($short_locale, $candidates)) $candidates[] = $short_locale;
                }

                if (isset($translations['name']) and is_array($translations['name']))
                {
                    foreach ($candidates as $candidate)
                    {
                        if (isset($translations['name'][$candidate]) and trim((string) $translations['name'][$candidate]))
                        {
                            $title = trim((string) $translations['name'][$candidate]);
                            break;
                        }
                    }

                    if (!trim($title) and count($translations['name']))
                    {
                        foreach ($translations['name'] as $translated_title)
                        {
                            if (trim((string) $translated_title))
                            {
                                $title = trim((string) $translated_title);
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!trim($title)) $title = sprintf(__('Service %s', 'modern-events-calendar-lite'), $service_id);
        return $title;
    }

    private function amelia_prepare_event_tickets($service, $service_title)
    {
        $ticket_price = 0;
        if (isset($service['price']) and trim((string) $service['price']) !== '' and is_numeric($service['price']))
        {
            $ticket_price = (float) $service['price'];
        }

        return [
            1 => [
                'id' => 1,
                'name' => $service_title,
                'description' => '',
                'price' => $ticket_price,
                'price_label' => '',
                'limit' => '',
                'unlimited' => 1,
                'seats' => 1,
                'minimum_ticket' => 0,
                'maximum_ticket' => '',
                'stop_selling_value' => 0,
                'stop_selling_type' => 'day',
                'dates' => [],
                'category_ids' => [],
            ],
        ];
    }

    private function amelia_get_settings()
    {
        $settings = get_option('amelia_settings', []);
        if (is_string($settings)) $settings = json_decode($settings, true);
        if (!is_array($settings)) $settings = [];

        return $settings;
    }

    private function amelia_table($name)
    {
        $wpdb = $this->db->get_DBO();
        return $wpdb->prefix . 'amelia_' . $name;
    }

    private function amelia_table_exists($name)
    {
        return $this->db->exists('amelia_' . $name);
    }

    private function amelia_day_index_to_mec($amelia_day_index)
    {
        if ($amelia_day_index < 1 or $amelia_day_index > 7) return null;
        return (int) ($amelia_day_index - 1);
    }

    private function amelia_time_to_minutes($time, $is_end = false)
    {
        if (!is_string($time) or !trim($time)) return null;

        $parts = explode(':', $time);
        if (!isset($parts[0]) or !isset($parts[1])) return null;

        $hour = (int) $parts[0];
        $minutes = (int) $parts[1];
        $seconds = isset($parts[2]) ? (int) $parts[2] : 0;

        if ($hour < 0 or $hour > 24) return null;
        if ($minutes < 0 or $minutes > 59) return null;
        if ($seconds < 0 or $seconds > 59) return null;
        if ($hour === 24 and ($minutes > 0 or $seconds > 0)) return null;

        if ($is_end and $hour === 0 and $minutes === 0 and $seconds === 0) return 1440;

        $value = ($hour * 60) + $minutes + ($seconds / 60);
        return ($is_end ? (int) ceil($value) : (int) floor($value));
    }

    private function amelia_split_interval($day, $start, $end)
    {
        $intervals = [];
        if ($end > $start)
        {
            $intervals[] = ['day' => $day, 'start' => $start, 'end' => $end];
        }
        else if ($end < $start)
        {
            $intervals[] = ['day' => $day, 'start' => $start, 'end' => 1440];
            $intervals[] = ['day' => (($day + 1) % 7), 'start' => 0, 'end' => $end];
        }

        return $intervals;
    }

    private function amelia_subtract_intervals($base_intervals, $subtract_intervals)
    {
        $result = $base_intervals;
        foreach ($subtract_intervals as $cut)
        {
            $next = [];
            foreach ($result as $base)
            {
                if ($cut['end'] <= $base['start'] or $cut['start'] >= $base['end'])
                {
                    $next[] = $base;
                    continue;
                }

                if ($cut['start'] > $base['start'])
                {
                    $next[] = ['start' => $base['start'], 'end' => min($cut['start'], $base['end'])];
                }

                if ($cut['end'] < $base['end'])
                {
                    $next[] = ['start' => max($cut['end'], $base['start']), 'end' => $base['end']];
                }
            }

            $result = $next;
        }

        return $result;
    }

    private function amelia_merge_intervals($intervals)
    {
        if (!is_array($intervals) or !count($intervals)) return [];

        usort($intervals, function ($a, $b)
        {
            if ($a['start'] == $b['start']) return ($a['end'] <=> $b['end']);
            return ($a['start'] <=> $b['start']);
        });

        $merged = [];
        foreach ($intervals as $interval)
        {
            if (!isset($interval['start']) or !isset($interval['end'])) continue;
            if ($interval['end'] <= $interval['start']) continue;

            if (!count($merged))
            {
                $merged[] = ['start' => (int) $interval['start'], 'end' => (int) $interval['end']];
                continue;
            }

            $last_key = count($merged) - 1;
            if ($interval['start'] <= $merged[$last_key]['end'])
            {
                $merged[$last_key]['end'] = max($merged[$last_key]['end'], (int) $interval['end']);
            }
            else $merged[] = ['start' => (int) $interval['start'], 'end' => (int) $interval['end']];
        }

        return $merged;
    }

    private function amelia_minutes_to_timepicker($minutes, $time_format = 12)
    {
        $minutes = max(0, min(1440, (int) $minutes));

        $hour_24 = (int) floor($minutes / 60);
        $minute = (int) ($minutes % 60);

        if ((int) $time_format === 24)
        {
            return [
                'hour' => $hour_24,
                'minutes' => $minute,
                'ampm' => '',
            ];
        }

        $ampm = ($hour_24 >= 12 ? 'PM' : 'AM');
        $hour_12 = ($hour_24 % 12);
        if ($hour_12 === 0) $hour_12 = 12;

        return [
            'hour' => $hour_12,
            'minutes' => $minute,
            'ampm' => $ampm,
        ];
    }

    private function amelia_minutes_to_event_time($minutes, $type = 'start')
    {
        $minutes = max(0, min(1440, (int) $minutes));

        $hour_24 = (int) floor($minutes / 60);
        $minute = (int) ($minutes % 60);

        if ($hour_24 === 24)
        {
            $hour_12 = 12;
            $ampm = 'AM';
        }
        else
        {
            $ampm = ($hour_24 >= 12 ? 'PM' : 'AM');
            $hour_12 = ($hour_24 % 12);
            if ($hour_12 === 0) $hour_12 = 12;
        }

        return [
            'hour' => $hour_12,
            'minutes' => $minute,
            'ampm' => $ampm,
            'type' => $type,
        ];
    }

    private function amelia_get_next_date_for_day($mec_day_index, $from_date = null)
    {
        if (is_null($from_date) or !trim($from_date)) $from_date = wp_date('Y-m-d');

        $from_ts = strtotime($from_date);
        if (!$from_ts) $from_ts = current_time('timestamp');

        $php_to_mec = [6, 0, 1, 2, 3, 4, 5];
        $current_mec_day = $php_to_mec[(int) wp_date('w', $from_ts)];

        $delta = ($mec_day_index - $current_mec_day + 7) % 7;
        return wp_date('Y-m-d', strtotime('+' . $delta . ' day', $from_ts));
    }

    private function amelia_period_matches_service($period_id, $service_id, $period_services_by_period)
    {
        if (!isset($period_services_by_period[$period_id])) return true;

        $services = array_values(array_unique(array_filter(array_map('intval', $period_services_by_period[$period_id]))));
        if (!count($services)) return true;

        return in_array((int) $service_id, $services);
    }

    private function amelia_build_provider_weekly_availability($provider_id, $service_id, $weekdays_by_provider, $timeouts_by_weekday, $periods_by_weekday, $period_services_by_period, $period_locations_by_period, &$source_location_ids)
    {
        $provider_weekly = [0 => [], 1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => []];

        $provider_weekdays = $weekdays_by_provider[$provider_id] ?? [];
        foreach ($provider_weekdays as $weekday_row)
        {
            $amelia_day_index = (int) ($weekday_row['dayIndex'] ?? 0);
            $mec_day_index = $this->amelia_day_index_to_mec($amelia_day_index);
            if (is_null($mec_day_index)) continue;

            $weekday_id = (int) ($weekday_row['id'] ?? 0);
            if (!$weekday_id) continue;

            $segments = [];
            $weekday_periods = $periods_by_weekday[$weekday_id] ?? [];
            if (count($weekday_periods))
            {
                foreach ($weekday_periods as $period_row)
                {
                    $period_id = (int) ($period_row['id'] ?? 0);
                    if (!$period_id) continue;
                    if (!$this->amelia_period_matches_service($period_id, $service_id, $period_services_by_period)) continue;

                    $start = $this->amelia_time_to_minutes($period_row['startTime'] ?? null, false);
                    $end = $this->amelia_time_to_minutes($period_row['endTime'] ?? null, true);
                    if (is_null($start) or is_null($end) or $start === $end) continue;

                    $period_location_id = (int) ($period_row['locationId'] ?? 0);
                    if ($period_location_id) $source_location_ids[] = $period_location_id;

                    $period_location_ids = $period_locations_by_period[$period_id] ?? [];
                    if (count($period_location_ids)) $source_location_ids = array_merge($source_location_ids, $period_location_ids);

                    $split = $this->amelia_split_interval($mec_day_index, $start, $end);
                    if (count($split)) $segments = array_merge($segments, $split);
                }
            }
            else
            {
                $start = $this->amelia_time_to_minutes($weekday_row['startTime'] ?? null, false);
                $end = $this->amelia_time_to_minutes($weekday_row['endTime'] ?? null, true);
                if (is_null($start) or is_null($end) or $start === $end) continue;

                $split = $this->amelia_split_interval($mec_day_index, $start, $end);
                if (count($split)) $segments = array_merge($segments, $split);
            }

            if (!count($segments)) continue;

            $timeouts_by_day = [];
            $weekday_timeouts = $timeouts_by_weekday[$weekday_id] ?? [];
            foreach ($weekday_timeouts as $timeout_row)
            {
                $timeout_start = $this->amelia_time_to_minutes($timeout_row['startTime'] ?? null, false);
                $timeout_end = $this->amelia_time_to_minutes($timeout_row['endTime'] ?? null, true);
                if (is_null($timeout_start) or is_null($timeout_end) or $timeout_start === $timeout_end) continue;

                $timeout_segments = $this->amelia_split_interval($mec_day_index, $timeout_start, $timeout_end);
                foreach ($timeout_segments as $timeout_segment)
                {
                    if (!isset($timeouts_by_day[$timeout_segment['day']])) $timeouts_by_day[$timeout_segment['day']] = [];
                    $timeouts_by_day[$timeout_segment['day']][] = ['start' => $timeout_segment['start'], 'end' => $timeout_segment['end']];
                }
            }

            foreach ($segments as $segment)
            {
                $intervals = [['start' => $segment['start'], 'end' => $segment['end']]];
                $segment_timeouts = $timeouts_by_day[$segment['day']] ?? [];
                if (count($segment_timeouts)) $intervals = $this->amelia_subtract_intervals($intervals, $segment_timeouts);

                foreach ($intervals as $interval)
                {
                    if ($interval['end'] <= $interval['start']) continue;
                    $provider_weekly[$segment['day']][] = $interval;
                }
            }
        }

        for ($d = 0; $d <= 6; $d++) $provider_weekly[$d] = $this->amelia_merge_intervals($provider_weekly[$d]);
        return $provider_weekly;
    }

    private function amelia_get_provider_special_day_intervals_on_date($provider_id, $date, $service_id, $special_days_by_provider, $special_periods_by_special_day, $special_period_services_by_period, $special_period_locations_by_period, &$source_location_ids)
    {
        $special_days = $special_days_by_provider[$provider_id] ?? [];
        if (!count($special_days)) return null;

        $matched_special_day = false;
        $intervals = [];
        $mec_day_index = ((int) wp_date('N', strtotime($date))) - 1;

        foreach ($special_days as $special_day_row)
        {
            $start_date = trim((string) ($special_day_row['startDate'] ?? ''));
            $end_date = trim((string) ($special_day_row['endDate'] ?? ''));
            if (!trim($start_date) or !trim($end_date)) continue;
            if ($date < $start_date or $date > $end_date) continue;

            $matched_special_day = true;
            $special_day_id = (int) ($special_day_row['id'] ?? 0);
            if (!$special_day_id) continue;

            $special_periods = $special_periods_by_special_day[$special_day_id] ?? [];
            if (!count($special_periods)) continue;

            foreach ($special_periods as $special_period_row)
            {
                $special_period_id = (int) ($special_period_row['id'] ?? 0);
                if (!$special_period_id) continue;
                if (!$this->amelia_period_matches_service($special_period_id, $service_id, $special_period_services_by_period)) continue;

                $start = $this->amelia_time_to_minutes($special_period_row['startTime'] ?? null, false);
                $end = $this->amelia_time_to_minutes($special_period_row['endTime'] ?? null, true);
                if (is_null($start) or is_null($end) or $start === $end) continue;

                $special_location_id = (int) ($special_period_row['locationId'] ?? 0);
                if ($special_location_id) $source_location_ids[] = $special_location_id;

                $special_location_ids = $special_period_locations_by_period[$special_period_id] ?? [];
                if (count($special_location_ids)) $source_location_ids = array_merge($source_location_ids, $special_location_ids);

                $split = $this->amelia_split_interval($mec_day_index, $start, $end);
                foreach ($split as $segment)
                {
                    if ((int) $segment['day'] !== (int) $mec_day_index) continue;
                    $intervals[] = ['start' => $segment['start'], 'end' => $segment['end']];
                }
            }
        }

        if (!$matched_special_day) return null;
        return $this->amelia_merge_intervals($intervals);
    }

    private function amelia_is_provider_day_off_on_date($provider_id, $date, $dayoffs_by_provider)
    {
        $dayoffs = $dayoffs_by_provider[$provider_id] ?? [];
        if (!count($dayoffs)) return false;

        $date_ts = strtotime($date);
        if (!$date_ts) return false;

        foreach ($dayoffs as $dayoff_row)
        {
            $start_date = trim((string) ($dayoff_row['startDate'] ?? ''));
            $end_date = trim((string) ($dayoff_row['endDate'] ?? ''));
            if (!trim($start_date) or !trim($end_date)) continue;

            $repeat = (int) ($dayoff_row['repeat'] ?? 0);
            if (!$repeat)
            {
                if ($date >= $start_date and $date <= $end_date) return true;
                continue;
            }

            $start_md = wp_date('m-d', strtotime($start_date));
            $end_md = wp_date('m-d', strtotime($end_date));
            $current_md = wp_date('m-d', $date_ts);

            if ($start_md <= $end_md)
            {
                if ($current_md >= $start_md and $current_md <= $end_md) return true;
            }
            else
            {
                if ($current_md >= $start_md or $current_md <= $end_md) return true;
            }
        }

        return false;
    }

    private function amelia_expand_global_days_off($days_off, $from_date, $to_date)
    {
        $result = [];
        if (!is_array($days_off) or !count($days_off)) return $result;

        $from_ts = strtotime($from_date);
        $to_ts = strtotime($to_date);
        if (!$from_ts or !$to_ts or $to_ts < $from_ts) return $result;

        $raw_values = [];
        foreach ($days_off as $day_off)
        {
            if (is_string($day_off) and trim($day_off))
            {
                $raw_values[] = trim($day_off);
                continue;
            }

            if (is_array($day_off))
            {
                foreach ($day_off as $day_off_item)
                {
                    if (is_string($day_off_item) and trim($day_off_item))
                    {
                        $raw_values[] = trim($day_off_item);
                    }
                }
            }
        }

        $raw_values = array_values(array_unique($raw_values));
        if (!count($raw_values)) return $result;

        foreach ($raw_values as $raw_value)
        {
            $parts = explode('-', $raw_value);

            if (count($parts) === 3)
            {
                $ts = strtotime($raw_value);
                if ($ts and $ts >= $from_ts and $ts <= $to_ts) $result[wp_date('Y-m-d', $ts)] = true;
                continue;
            }

            if (count($parts) !== 2) continue;

            $month_day = sprintf('%02d-%02d', (int) $parts[0], (int) $parts[1]);
            $start_year = (int) wp_date('Y', $from_ts);
            $end_year = (int) wp_date('Y', $to_ts);

            for ($year = $start_year; $year <= $end_year; $year++)
            {
                $candidate = $year . '-' . $month_day;
                $candidate_ts = strtotime($candidate);
                if (!$candidate_ts) continue;

                if ($candidate_ts >= $from_ts and $candidate_ts <= $to_ts) $result[wp_date('Y-m-d', $candidate_ts)] = true;
            }
        }

        return $result;
    }

    private function amelia_intervals_equal($intervals_a, $intervals_b)
    {
        $intervals_a = $this->amelia_merge_intervals($intervals_a);
        $intervals_b = $this->amelia_merge_intervals($intervals_b);

        if (count($intervals_a) !== count($intervals_b)) return false;

        foreach ($intervals_a as $i => $interval_a)
        {
            $interval_b = $intervals_b[$i] ?? null;
            if (!is_array($interval_b)) return false;

            if ((int) ($interval_a['start'] ?? -1) !== (int) ($interval_b['start'] ?? -2)) return false;
            if ((int) ($interval_a['end'] ?? -1) !== (int) ($interval_b['end'] ?? -2)) return false;
        }

        return true;
    }

    private function amelia_prepare_adjusted_availability($date, $intervals, $time_format = 12)
    {
        $adjusted = ['date' => $date];

        if (!is_array($intervals) or !count($intervals)) return $adjusted;

        $intervals = $this->amelia_merge_intervals($intervals);
        foreach ($intervals as $i => $interval)
        {
            $adjusted[$i] = [
                'start' => $this->amelia_minutes_to_timepicker((int) $interval['start'], $time_format),
                'end' => $this->amelia_minutes_to_timepicker((int) $interval['end'], $time_format),
            ];
        }

        return $adjusted;
    }

    private function amelia_resolve_image_url($image)
    {
        $image = trim((string) $image);
        if (!trim($image)) return '';

        if (filter_var($image, FILTER_VALIDATE_URL)) return $image;

        $upload_dir = wp_upload_dir();
        $baseurl = rtrim((string) ($upload_dir['baseurl'] ?? ''), '/');
        $basedir = rtrim((string) ($upload_dir['basedir'] ?? ''), '/');

        if ($basedir and strpos($image, $basedir) === 0 and file_exists($image))
        {
            $relative = ltrim(str_replace($basedir, '', $image), '/');
            return $baseurl . '/' . $relative;
        }

        if (strpos($image, '/wp-content/uploads/') === 0) return home_url($image);

        if (strpos($image, ABSPATH) === 0 and file_exists($image))
        {
            $relative = ltrim(str_replace(ABSPATH, '', $image), '/');
            return home_url('/' . $relative);
        }

        if (strpos($image, '/') === 0)
        {
            if (file_exists($image))
            {
                if ($basedir and strpos($image, $basedir) === 0)
                {
                    $relative = ltrim(str_replace($basedir, '', $image), '/');
                    return $baseurl . '/' . $relative;
                }

                if (strpos($image, ABSPATH) === 0)
                {
                    $relative = ltrim(str_replace(ABSPATH, '', $image), '/');
                    return home_url('/' . $relative);
                }
            }

            return home_url($image);
        }

        $upload_path_candidate = $basedir . '/' . ltrim($image, '/');
        if ($basedir and file_exists($upload_path_candidate))
        {
            $relative = ltrim(str_replace($basedir, '', $upload_path_candidate), '/');
            return $baseurl . '/' . $relative;
        }

        return $baseurl . '/' . ltrim($image, '/');
    }

    public function thirdparty_eventon_import_do($IDs)
    {
        $count = 0;
        foreach ($IDs as $ID)
        {
            $post = get_post($ID);
            $metas = $this->main->get_post_meta($ID);

            // Event Title and Content
            $title = $post->post_title;
            $description = $post->post_content;
            $third_party_id = $ID;

            // Event location
            $locations = wp_get_post_terms($ID, 'event_location');
            $location_id = 1;

            // Import Event Locations into MEC locations
            if (isset($this->ix['import_locations']) and $this->ix['import_locations'] and isset($locations[0]))
            {
                $l_metas = evo_get_term_meta('event_location', $locations[0]->term_id);
                $location_id = $this->main->save_location([
                    'name' => trim($locations[0]->name),
                    'address' => ($l_metas['location_address'] ?? ''),
                    'latitude' => ($l_metas['location_lat'] ?? 0),
                    'longitude' => ($l_metas['location_lon'] ?? 0),
                ]);
            }

            // Event Organizer
            $organizers = wp_get_post_terms($ID, 'event_organizer');
            $organizer_id = 1;

            // Import Event Organizer into MEC organizers
            if (isset($this->ix['import_organizers']) and $this->ix['import_organizers'] and isset($organizers[0]))
            {
                $o_metas = evo_get_term_meta('event_organizer', $organizers[0]->term_id);
                $organizer_id = $this->main->save_organizer([
                    'name' => trim($organizers[0]->name),
                    'tel' => ($o_metas['evcal_org_contact'] ?? ''),
                    'url' => ($o_metas['evcal_org_exlink'] ?? ''),
                ]);
            }

            // Event Categories
            $categories = wp_get_post_terms($ID, 'event_type');
            $category_ids = [];

            // Import Event Categories into MEC categories
            if (isset($this->ix['import_categories']) and $this->ix['import_categories'] and count($categories))
            {
                foreach ($categories as $category)
                {
                    $category_id = $this->main->save_category([
                        'name' => trim($category->name),
                    ]);

                    if ($category_id) $category_ids[] = $category_id;
                }
            }

            // Event Start Date and Time
            $date_start = new DateTime(date('Y-m-d G:i', $metas['evcal_srow']));
            if (isset($metas['evo_event_timezone']) and trim($metas['evo_event_timezone'])) $date_start->setTimezone(new DateTimeZone($metas['evo_event_timezone']));

            $start_date = $date_start->format('Y-m-d');
            $start_hour = $date_start->format('g');
            $start_minutes = $date_start->format('i');
            $start_ampm = $date_start->format('A');

            // Event End Date and Time
            $date_end = new DateTime(date('Y-m-d G:i', $metas['evcal_erow']));
            if (isset($metas['evo_event_timezone']) and trim($metas['evo_event_timezone'])) $date_end->setTimezone(new DateTimeZone($metas['evo_event_timezone']));

            $end_date = $date_end->format('Y-m-d');
            $end_hour = $date_end->format('g');
            $end_minutes = $date_end->format('i');
            $end_ampm = $date_end->format('A');

            // Event Time Options
            $hide_end_time = (isset($metas['evo_hide_endtime']) and $metas['evo_hide_endtime'] == 'yes') ? 1 : 0;
            $allday = (isset($metas['evcal_allday']) and trim($metas['evcal_allday']) == 'yes') ? $metas['evcal_allday'] : 0;

            // Recurring Event
            if (isset($metas['evcal_repeat']) and $metas['evcal_repeat'] == 'yes')
            {
                $repeat_status = 1;
                $interval = null;
                $year = null;
                $month = null;
                $day = null;
                $week = null;
                $weekday = null;
                $weekdays = null;
                $days = null;
                $finish = null;

                $occurrences = (isset($metas['repeat_intervals']) and is_array($metas['repeat_intervals'])) ? $metas['repeat_intervals'] : [];
                if (count($occurrences))
                {
                    $t = $occurrences[(count($occurrences) - 1)][1];
                    $finish = date('Y-m-d', $t);
                }

                $freq = (isset($metas['evcal_rep_freq']) and trim($metas['evcal_rep_freq'])) ? $metas['evcal_rep_freq'] : 'daily';

                if ($freq == 'daily')
                {
                    $repeat_type = 'daily';
                    $interval = $metas['evcal_rep_gap'] ?? 1;
                }
                else if ($freq == 'weekly')
                {
                    $repeat_type = 'weekly';
                    $interval = isset($metas['evcal_rep_gap']) ? $metas['evcal_rep_gap'] * 7 : 7;
                }
                else if ($freq == 'monthly')
                {
                    $repeat_type = 'monthly';

                    $year = '*';
                    $month = '*';

                    $s = $start_date;
                    $e = $end_date;

                    $_days = [];
                    while (strtotime($s) <= strtotime($e))
                    {
                        $_days[] = date('d', strtotime($s));
                        $s = date('Y-m-d', strtotime('+1 Day', strtotime($s)));
                    }

                    $day = ',' . implode(',', array_unique($_days)) . ',';

                    $week = '*';
                    $weekday = '*';
                }
                else if ($freq == 'yearly')
                {
                    $repeat_type = 'yearly';

                    $year = '*';

                    $s = $start_date;
                    $e = $end_date;

                    $_months = [];
                    $_days = [];
                    while (strtotime($s) <= strtotime($e))
                    {
                        $_months[] = date('m', strtotime($s));
                        $_days[] = date('d', strtotime($s));

                        $s = date('Y-m-d', strtotime('+1 Day', strtotime($s)));
                    }

                    $month = ',' . implode(',', array_unique($_months)) . ',';
                    $day = ',' . implode(',', array_unique($_days)) . ',';

                    $week = '*';
                    $weekday = '*';
                }
                else if ($freq == 'custom')
                {
                    $repeat_type = 'custom_days';
                    $occurrences = (isset($metas['repeat_intervals']) and is_array($metas['repeat_intervals'])) ? $metas['repeat_intervals'] : [];

                    $days = '';
                    $x = 1;
                    foreach ($occurrences as $occurrence)
                    {
                        if ($x == 1)
                        {
                            $finish = date('Y-m-d', $occurrence[0]);

                            $x++;
                            continue;
                        }

                        $days .= date('Y-m-d', $occurrence[0]) . ',';
                        $x++;
                    }

                    $days = trim($days, ', ');
                }
                else $repeat_type = '';

                // Custom Week Days
                if ($repeat_type == 'weekly' and isset($metas['evo_rep_WKwk']) and is_array($metas['evo_rep_WKwk']) and count($metas['evo_rep_WKwk']) > 1)
                {
                    $week_day_mapping = ['d1' => 1, 'd2' => 2, 'd3' => 3, 'd4' => 4, 'd5' => 5, 'd6' => 6, 'd0' => 7];

                    $weekdays = '';
                    foreach ($metas['evo_rep_WKwk'] as $week_day) $weekdays .= $week_day_mapping['d' . $week_day] . ',';

                    $weekdays = ',' . trim($weekdays, ', ') . ',';
                    $interval = null;

                    $repeat_type = 'certain_weekdays';
                }
            }
            // Single Event
            else
            {
                $repeat_status = 0;
                $repeat_type = '';
                $interval = null;
                $finish = $end_date;
                $year = null;
                $month = null;
                $day = null;
                $week = null;
                $weekday = null;
                $weekdays = null;
                $days = null;
            }

            // Hourly Schedule
            $hourly_schedules = [];
            if (isset($metas['_sch_blocks']) and is_array($metas['_sch_blocks']) and count($metas['_sch_blocks']))
            {
                foreach ($metas['_sch_blocks'] as $sch_block)
                {
                    foreach ($sch_block as $sch)
                    {
                        if (!is_array($sch)) continue;
                        $hourly_schedules[] = [
                            'from' => $sch['evo_sch_stime'],
                            'to' => $sch['evo_sch_etime'],
                            'title' => $sch['evo_sch_title'],
                            'description' => $sch['evo_sch_desc'],
                        ];
                    }
                }
            }

            // Read More Link
            $more_info_link = isset($metas['evcal_lmlink']) && trim($metas['evcal_lmlink']) ? $metas['evcal_lmlink'] : '';

            $args = [
                'title' => $title,
                'content' => $description,
                'location_id' => $location_id,
                'organizer_id' => $organizer_id,
                'date' => [
                    'start' => [
                        'date' => $start_date,
                        'hour' => $start_hour,
                        'minutes' => $start_minutes,
                        'ampm' => $start_ampm,
                    ],
                    'end' => [
                        'date' => $end_date,
                        'hour' => $end_hour,
                        'minutes' => $end_minutes,
                        'ampm' => $end_ampm,
                    ],
                    'repeat' => [
                        'end' => 'date',
                        'end_at_date' => $finish,
                        'end_at_occurrences' => 10,
                    ],
                    'allday' => $allday,
                    'comment' => '',
                    'hide_time' => 0,
                    'hide_end_time' => $hide_end_time,
                ],
                'start' => $start_date,
                'start_time_hour' => $start_hour,
                'start_time_minutes' => $start_minutes,
                'start_time_ampm' => $start_ampm,
                'end' => $end_date,
                'end_time_hour' => $end_hour,
                'end_time_minutes' => $end_minutes,
                'end_time_ampm' => $end_ampm,
                'repeat_status' => $repeat_status,
                'repeat_type' => $repeat_type,
                'interval' => $interval,
                'finish' => $finish,
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'week' => $week,
                'weekday' => $weekday,
                'weekdays' => $weekdays,
                'days' => $days,
                'meta' => [
                    'mec_source' => 'eventon',
                    'mec_eventon_id' => $third_party_id,
                    'mec_allday' => $allday,
                    'hide_end_time' => $hide_end_time,
                    'mec_repeat_end' => 'date',
                    'mec_repeat_end_at_occurrences' => 9,
                    'mec_repeat_end_at_date' => $finish,
                    'mec_in_days' => $days,
                    'mec_hourly_schedules' => $hourly_schedules,
                    'mec_more_info' => $more_info_link,
                ],
            ];

            $post_id = $this->db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='$third_party_id' AND `meta_key`='mec_eventon_id'", 'loadResult');

            // Insert the event into MEC
            $post_id = $this->main->save_event($args, $post_id);

            // Set location to the post
            if ($location_id) wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

            // Set organizer to the post
            if ($organizer_id) wp_set_object_terms($post_id, (int) $organizer_id, 'mec_organizer');

            // Set categories to the post
            if (count($category_ids)) foreach ($category_ids as $category_id) wp_set_object_terms($post_id, (int) $category_id, 'mec_category', true);

            // Set Features Image
            if (isset($this->ix['import_featured_image']) and $this->ix['import_featured_image'] and $thumbnail_id = get_post_thumbnail_id($ID))
            {
                set_post_thumbnail($post_id, $thumbnail_id);
            }

            if (!empty($this->ix['import_tickets']) || !empty($this->ix['import_bookings']))
            {
                $ticket_map = $this->thirdparty_tec_import_tickets($third_party_id, $post_id);

                if (!empty($this->ix['import_bookings']))
                {
                    $this->thirdparty_tec_import_bookings($third_party_id, $post_id, $ticket_map, strtotime($metas['_EventStartDate']) . ':' . strtotime($metas['_EventEndDate']));
                }
            }

            $count++;
        }

        return ['success' => 1, 'data' => $count];
    }

    /**
     * @throws Exception
     */
    public function thirdparty_tec_import_do($IDs)
    {
        $wpml = false;
        if (function_exists('wpml_get_content_trid')) $wpml = true;

        $count = 0;
        foreach ($IDs as $ID)
        {
            $post = get_post($ID);
            $metas = $this->main->get_post_meta($ID);

            // Event Title and Content
            $title = $post->post_title;
            $description = $post->post_content;
            $third_party_id = $ID;

            // Event Author
            $author = null;
            if (isset($this->ix['import_author']) and $this->ix['import_author'] and $post->post_author)
            {
                $author = $post->post_author;
            }

            // Event location
            $location = get_post($metas['_EventVenueID']);
            $location_id = 1;

            // Import Event Locations into MEC locations
            if (isset($this->ix['import_locations']) and $this->ix['import_locations'] and isset($location->ID))
            {
                $l_metas = $this->main->get_post_meta($location->ID);
                $location_id = $this->main->save_location([
                    'name' => trim($location->post_title),
                    'address' => $l_metas['_VenueAddress'] ?? '',
                    'latitude' => 0,
                    'longitude' => 0,
                ]);
            }

            // Event Organizer
            $organizer = get_post($metas['_EventOrganizerID']);
            $organizer_id = 1;

            // Import Event Organizer into MEC organizers
            if (isset($this->ix['import_organizers']) and $this->ix['import_organizers'] and isset($organizer->ID))
            {
                $o_metas = $this->main->get_post_meta($organizer->ID);
                $organizer_id = $this->main->save_organizer([
                    'name' => trim($organizer->post_title),
                    'tel' => $o_metas['_OrganizerPhone'] ?? '',
                    'email' => $o_metas['_OrganizerEmail'] ?? '',
                    'url' => $o_metas['_OrganizerWebsite'] ?? '',
                ]);
            }

            // Event Categories
            $categories = wp_get_post_terms($ID, 'tribe_events_cat');
            $category_ids = [];

            // Import Event Categories into MEC categories
            if (isset($this->ix['import_categories']) and $this->ix['import_categories'] and count($categories))
            {
                foreach ($categories as $category)
                {
                    $category_id = $this->main->save_category([
                        'name' => trim($category->name),
                    ]);

                    if ($category_id) $category_ids[] = $category_id;
                }
            }

            // Event Start Date and Time
            $date_start = new DateTime(date('Y-m-d G:i', strtotime($metas['_EventStartDate'])));

            $start_date = $date_start->format('Y-m-d');
            $start_hour = $date_start->format('g');
            $start_minutes = $date_start->format('i');
            $start_ampm = $date_start->format('A');

            // Event End Date and Time
            $date_end = new DateTime(date('Y-m-d G:i', strtotime($metas['_EventEndDate'])));

            $end_date = $date_end->format('Y-m-d');
            $end_hour = $date_end->format('g');
            $end_minutes = $date_end->format('i');
            $end_ampm = $date_end->format('A');

            // Event Time Options
            $hide_end_time = 0;
            $allday = (isset($metas['_EventAllDay']) and trim($metas['_EventAllDay']) == 'yes') ? 1 : 0;

            // Recurring Event
            if (
                isset($metas['_EventRecurrence']['rules']) && is_array($metas['_EventRecurrence']) && count($metas['_EventRecurrence']) &&
                is_array($metas['_EventRecurrence']['rules']) && count($metas['_EventRecurrence']['rules'])
            )
            {
                $repeat_status = 1;
                $repeat_type = '';
                $finish = null;
                $year = null;
                $month = null;
                $day = null;
                $week = null;
                $weekday = null;
                $weekdays = null;
                $days = null;
                $advanced_days = null;
                $end_occurrence = 10;

                $rule = $metas['_EventRecurrence']['rules'][0];

                $end_type = 'never';
                $finish_type = isset($rule['end-type']) ? strtolower($rule['end-type']) : 'never';

                if ($finish_type === 'on' and isset($rule['end']) and trim($rule['end']))
                {
                    $end_type = 'date';
                    $finish = $rule['end'];
                }
                else if ($finish_type === 'after' and isset($rule['end-count']) and trim($rule['end-count']))
                {
                    $end_type = 'occurrences';
                    $end_occurrence = (int) $rule['end-count'];
                }

                $interval = (int) $rule['custom']['interval'];

                $type = strtolower($rule['custom']['type']);
                if ($type === 'daily')
                {
                    $repeat_type = 'daily';
                }
                else if ($type === 'weekly')
                {
                    $repeat_type = 'certain_weekdays';

                    if (count($rule['custom']['week']['day']) === 1)
                    {
                        $repeat_type = 'weekly';
                        $interval = $interval * 7;
                    }
                    else
                    {
                        $weekdays = ',' . trim(implode(',', $rule['custom']['week']['day']), ', ') . ',';
                        $interval = null;
                    }
                }
                else if ($type === 'monthly')
                {
                    if (isset($rule['custom']['month']['same-day']) && $rule['custom']['month']['same-day'] === 'no')
                    {
                        $repeat_type = 'advanced';

                        $week_no = $rule['custom']['month']['number'];
                        if ($week_no == 'Last' || $week_no == '5') $week_no = 'l';

                        $week_day = $rule['custom']['month']['day'];

                        if ($week_day == '0') $week_day = 'Sun';
                        else if ($week_day == '1') $week_day = 'Mon';
                        else if ($week_day == '2') $week_day = 'Tue';
                        else if ($week_day == '3') $week_day = 'Wed';
                        else if ($week_day == '4') $week_day = 'Thu';
                        else if ($week_day == '5') $week_day = 'Fri';
                        else $week_day = 'Sat';

                        $advanced_days = [$week_day . '.' . $week_no];
                    }
                    else
                    {
                        $repeat_type = 'monthly';

                        $year = '*';
                        $month = '*';

                        $s = $start_date;
                        $e = $end_date;

                        $_days = [];
                        while (strtotime($s) <= strtotime($e))
                        {
                            $_days[] = date('d', strtotime($s));
                            $s = date('Y-m-d', strtotime('+1 Day', strtotime($s)));
                        }

                        $day = ',' . implode(',', array_unique($_days)) . ',';

                        $week = '*';
                        $weekday = '*';
                    }
                }
                else if ($type === 'yearly')
                {
                    $repeat_type = 'yearly';

                    $year = '*';

                    $s = $start_date;
                    $e = $end_date;

                    $_months = [];
                    $_days = [];
                    while (strtotime($s) <= strtotime($e))
                    {
                        $_months[] = date('m', strtotime($s));
                        $_days[] = date('d', strtotime($s));

                        $s = date('Y-m-d', strtotime('+1 Day', strtotime($s)));
                    }

                    $_months = array_unique($_months);

                    $month = ',' . implode(',', [$_months[0]]) . ',';
                    $day = ',' . implode(',', array_unique($_days)) . ',';

                    $week = '*';
                    $weekday = '*';
                }
            }
            else
            {
                // Single Event
                $repeat_status = 0;
                $repeat_type = '';
                $interval = null;
                $finish = $end_date;
                $year = null;
                $month = null;
                $day = null;
                $week = null;
                $weekday = null;
                $weekdays = null;
                $days = null;
                $advanced_days = null;

                $end_type = 'date';
                $end_occurrence = 10;
            }

            $args = [
                'title' => $title,
                'content' => $description,
                'author' => $author,
                'location_id' => $location_id,
                'organizer_id' => $organizer_id,
                'date' => [
                    'start' => [
                        'date' => $start_date,
                        'hour' => $start_hour,
                        'minutes' => $start_minutes,
                        'ampm' => $start_ampm,
                    ],
                    'end' => [
                        'date' => $end_date,
                        'hour' => $end_hour,
                        'minutes' => $end_minutes,
                        'ampm' => $end_ampm,
                    ],
                    'repeat' => [
                        'end' => $end_type,
                        'end_at_date' => $finish,
                        'end_at_occurrences' => $end_occurrence,
                    ],
                    'allday' => $allday,
                    'comment' => '',
                    'hide_time' => 0,
                    'hide_end_time' => $hide_end_time,
                ],
                'start' => $start_date,
                'start_time_hour' => $start_hour,
                'start_time_minutes' => $start_minutes,
                'start_time_ampm' => $start_ampm,
                'end' => $end_date,
                'end_time_hour' => $end_hour,
                'end_time_minutes' => $end_minutes,
                'end_time_ampm' => $end_ampm,
                'repeat_status' => $repeat_status,
                'repeat_type' => $repeat_type,
                'interval' => $interval,
                'finish' => $finish,
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'week' => $week,
                'weekday' => $weekday,
                'weekdays' => $weekdays,
                'days' => $days,
                'meta' => [
                    'mec_source' => 'the-events-calendar',
                    'mec_tec_id' => $third_party_id,
                    'mec_allday' => $allday,
                    'hide_end_time' => $hide_end_time,
                    'mec_repeat_end' => $end_type,
                    'mec_repeat_end_at_occurrences' => $end_occurrence - 1,
                    'mec_repeat_end_at_date' => $finish,
                    'mec_in_days' => $days,
                    'mec_more_info' => $metas['_EventURL'],
                    'mec_cost' => trim($metas['_EventCurrencySymbol'] . $metas['_EventCost']),
                    'mec_advanced_days' => $advanced_days,
                ],
            ];

            $post_id = $this->db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='$third_party_id' AND `meta_key`='mec_tec_id'", 'loadResult');

            // Insert the event into MEC
            $post_id = $this->main->save_event($args, $post_id);

            // WPML Translations
            if ($wpml)
            {
                // Original Post ID
                $original_thirdparty_id = apply_filters('wpml_original_element_id', null, $third_party_id, 'post_' . $post->post_type);

                $original_mec_id = $original_thirdparty_id
                    ? $this->db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='$original_thirdparty_id' AND `meta_key`='mec_tec_id'", 'loadResult')
                    : 0;

                $original_mec_language_info = apply_filters('wpml_element_language_details', null, [
                        'element_id' => $original_mec_id,
                        'element_type' => 'post_' . $this->main->get_main_post_type(),
                    ]
                );

                // Set the desired language
                $event_lang = apply_filters('wpml_post_language_details', '', $third_party_id);
                $language_code = is_array($event_lang) && isset($event_lang['language_code']) ? $event_lang['language_code'] : '';

                // Update the post language info
                $language_args = [
                    'element_id' => $post_id,
                    'element_type' => 'post_' . $this->main->get_main_post_type(),
                    'trid' => $original_mec_language_info->trid ?? null,
                    'language_code' => $language_code,
                    'source_language_code' => $original_mec_language_info->language_code ?? '',
                ];

                do_action('wpml_set_element_language_details', $language_args);
            }

            // Set location to the post
            if ($location_id) wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

            // Set organizer to the post
            if ($organizer_id) wp_set_object_terms($post_id, (int) $organizer_id, 'mec_organizer');

            // Set categories to the post
            if (count($category_ids)) foreach ($category_ids as $category_id) wp_set_object_terms($post_id, (int) $category_id, 'mec_category', true);

            // Set Features Image
            if (isset($this->ix['import_featured_image']) and $this->ix['import_featured_image'] and $thumbnail_id = get_post_thumbnail_id($ID))
            {
                set_post_thumbnail($post_id, $thumbnail_id);
            }

            $count++;
        }

        return ['success' => 1, 'data' => $count];
    }

    private function thirdparty_tec_import_tickets($tec_event_id, $mec_event_id)
    {
        $ticket_map = get_post_meta($mec_event_id, 'mec_tec_ticket_map', true);
        if (!is_array($ticket_map)) $ticket_map = [];

        if (!class_exists('Tribe__Tickets__Tickets') || !method_exists('Tribe__Tickets__Tickets', 'get_all_event_tickets')) return $ticket_map;

        $tec_tickets = Tribe__Tickets__Tickets::get_all_event_tickets($tec_event_id);
        if (!is_array($tec_tickets) || !count($tec_tickets)) return $ticket_map;

        $mec_tickets = get_post_meta($mec_event_id, 'mec_tickets', true);
        if (!is_array($mec_tickets)) $mec_tickets = [];

        $next_id = 1;
        foreach (array_keys($mec_tickets) as $ticket_id)
        {
            if (is_numeric($ticket_id)) $next_id = max($next_id, ((int) $ticket_id) + 1);
        }

        foreach ($tec_tickets as $tec_ticket)
        {
            $tec_ticket_id = (int) $this->thirdparty_tec_get_value($tec_ticket, 'ID', 0);
            if (!$tec_ticket_id) continue;

            $mec_ticket_id = isset($ticket_map[$tec_ticket_id]) ? (int) $ticket_map[$tec_ticket_id] : 0;
            if (!$mec_ticket_id || !isset($mec_tickets[$mec_ticket_id]))
            {
                $mec_ticket_id = $this->thirdparty_tec_find_mec_ticket_id($mec_tickets, $tec_ticket_id, $tec_ticket);
            }

            if (!$mec_ticket_id)
            {
                while (isset($mec_tickets[$next_id])) $next_id++;
                $mec_ticket_id = $next_id++;
            }

            $mec_tickets[$mec_ticket_id] = $this->thirdparty_tec_prepare_ticket($tec_ticket, $mec_ticket_id, $tec_ticket_id);
            $ticket_map[$tec_ticket_id] = $mec_ticket_id;
        }

        update_post_meta($mec_event_id, 'mec_tickets', $mec_tickets);
        update_post_meta($mec_event_id, 'mec_tec_ticket_map', $ticket_map);

        return $ticket_map;
    }

    private function thirdparty_tec_find_mec_ticket_id($mec_tickets, $tec_ticket_id, $tec_ticket)
    {
        foreach ($mec_tickets as $mec_ticket_id => $mec_ticket)
        {
            if (!is_array($mec_ticket)) continue;
            if (isset($mec_ticket['tec_ticket_id']) && (int) $mec_ticket['tec_ticket_id'] === (int) $tec_ticket_id) return (int) $mec_ticket_id;
        }

        $ticket_name = strtolower(trim((string) $this->thirdparty_tec_get_value($tec_ticket, 'name', '')));
        if (!trim($ticket_name)) return 0;

        $matches = [];
        foreach ($mec_tickets as $mec_ticket_id => $mec_ticket)
        {
            if (!is_array($mec_ticket)) continue;
            if (strtolower(trim((string) ($mec_ticket['name'] ?? ''))) === $ticket_name) $matches[] = (int) $mec_ticket_id;
        }

        return count($matches) === 1 ? $matches[0] : 0;
    }

    private function thirdparty_tec_prepare_ticket($tec_ticket, $mec_ticket_id, $tec_ticket_id)
    {
        $price = $this->thirdparty_tec_get_value($tec_ticket, 'price', '');
        if ($price === '') $price = $this->thirdparty_tec_get_value($tec_ticket, 'regular_price', 0);
        $price = is_numeric($price) ? (float) $price : 0;

        $capacity = $this->thirdparty_tec_get_value($tec_ticket, 'capacity', '');
        $managing_stock = $this->thirdparty_tec_get_value($tec_ticket, 'managing_stock', null);
        $unlimited = (!$managing_stock || $capacity === '' || (is_numeric($capacity) && (int) $capacity < 0));
        $limit = $unlimited ? '' : max(0, (int) $capacity);

        return [
            'id' => $mec_ticket_id,
            'name' => wp_strip_all_tags((string) $this->thirdparty_tec_get_value($tec_ticket, 'name', sprintf(__('Ticket %s', 'modern-events-calendar-lite'), $tec_ticket_id))),
            'description' => wp_kses_post((string) $this->thirdparty_tec_get_value($tec_ticket, 'description', '')),
            'price' => $price,
            'price_label' => '',
            'limit' => $limit,
            'unlimited' => $unlimited ? 1 : 0,
            'seats' => 1,
            'minimum_ticket' => 0,
            'maximum_ticket' => '',
            'stop_selling_value' => 0,
            'stop_selling_type' => 'day',
            'dates' => [],
            'category_ids' => [],
            'tec_ticket_id' => $tec_ticket_id,
        ];
    }

    private function thirdparty_tec_get_value($source, $key, $default = '')
    {
        if (is_array($source) && array_key_exists($key, $source)) return $source[$key];
        if (is_object($source) && isset($source->{$key})) return $source->{$key};

        if (is_object($source) && method_exists($source, $key))
        {
            try
            {
                return $source->{$key}();
            }
            catch (Exception $e)
            {
                return $default;
            }
        }

        return $default;
    }

    private function thirdparty_tec_import_bookings($tec_event_id, $mec_event_id, $ticket_map, $booking_date)
    {
        if (!class_exists('Tribe__Tickets__Tickets') || !method_exists('Tribe__Tickets__Tickets', 'get_event_attendees')) return 0;
        if (!is_array($ticket_map) || !count($ticket_map)) return 0;

        $tec_attendees = Tribe__Tickets__Tickets::get_event_attendees($tec_event_id);
        if (!is_array($tec_attendees) || !count($tec_attendees)) return 0;

        $orders = [];
        foreach ($tec_attendees as $tec_attendee)
        {
            if (!is_array($tec_attendee)) continue;

            $tec_ticket_id = (int) ($tec_attendee['product_id'] ?? ($tec_attendee['ticket_id'] ?? 0));
            if (!$tec_ticket_id || !isset($ticket_map[$tec_ticket_id])) continue;

            $name = $this->thirdparty_tec_attendee_name($tec_attendee);
            $email = $this->thirdparty_tec_attendee_email($tec_attendee);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

            $order_id = (string) ($tec_attendee['order_id'] ?? '');
            if (!trim($order_id)) $order_id = 'attendee-' . (string) ($tec_attendee['attendee_id'] ?? md5(wp_json_encode($tec_attendee)));

            if (!isset($orders[$order_id]))
            {
                $orders[$order_id] = [
                    'status' => $tec_attendee['order_status'] ?? '',
                    'attendees' => [],
                ];
            }

            $orders[$order_id]['attendees'][] = [
                'email' => $email,
                'name' => $name,
                'variations' => [],
                'id' => (int) $ticket_map[$tec_ticket_id],
                'reg' => [],
                'count' => 1,
                'event_id' => $mec_event_id,
                'tec_attendee_id' => $tec_attendee['attendee_id'] ?? '',
            ];
        }

        if (!count($orders)) return 0;

        $booking_date = $this->thirdparty_tec_normalize_booking_date($booking_date);
        $event_tickets = get_post_meta($mec_event_id, 'mec_tickets', true);
        if (!is_array($event_tickets)) $event_tickets = [];

        $book = $this->getBook();
        $gateway = new MEC_gateway();
        $u = $this->getUser();
        $disable_booking_confirmation = '__return_false';
        add_filter('mec_booking_confirmation', $disable_booking_confirmation);

        $imported = 0;
        foreach ($orders as $order_id => $order)
        {
            $transaction_id = 'TEC' . substr(md5($mec_event_id . ':' . $order_id), 0, 17);
            if ($this->thirdparty_tec_booking_exists($mec_event_id, $order_id, $transaction_id)) continue;

            $attendees = $order['attendees'];
            $main_attendee = $attendees[0] ?? [];
            if (!count($main_attendee)) continue;

            $raw_tickets = [];
            $ticket_ids = '';
            $attendees_info = [];
            foreach ($attendees as $attendee)
            {
                $ticket_id = $attendee['id'];
                $raw_tickets[$ticket_id] = ($raw_tickets[$ticket_id] ?? 0) + 1;
                $ticket_ids .= $ticket_id . ',';

                if (!isset($attendees_info[$attendee['email']])) $attendees_info[$attendee['email']] = ['count' => 1];
                else $attendees_info[$attendee['email']]['count']++;
            }

            $price_details = $book->get_price_details($raw_tickets, $mec_event_id, $event_tickets, [], [$booking_date]);
            $transaction = [
                'event_id' => $mec_event_id,
                'date' => $booking_date,
                'timestamps' => [$booking_date],
                'tickets' => $attendees,
                'price_details' => $price_details,
                'total' => $price_details['total'],
                'discount' => 0,
                'price' => $price_details['total'],
                'payable' => $price_details['payable'],
                'coupon' => null,
                'locale' => get_locale(),
                'tec_event_id' => $tec_event_id,
                'tec_order_id' => $order_id,
            ];

            update_option($transaction_id, $transaction, 'no');

            [$confirmed, $verified] = $this->thirdparty_tec_status_to_mec($this->thirdparty_tec_order_status($order_id, $order['status']));
            $order_date = $this->thirdparty_tec_order_date($order_id, $booking_date);
            $user_id = $gateway->register_user($main_attendee, ['event_id' => $mec_event_id]);
            $book_subject = $main_attendee['name'] . ' - ' . ($main_attendee['email'] ?? ($u->get($user_id)->user_email ?? ''));

            $book_id = $book->add(
                [
                    'post_author' => $user_id,
                    'post_type' => $this->main->get_book_post_type(),
                    'post_title' => $book_subject,
                    'post_date' => $order_date,
                    'attendees_info' => $attendees_info,
                    'mec_attendees' => $attendees,
                    'mec_gateway' => 'MEC_gateway',
                    'mec_gateway_label' => $this->thirdparty_tec_gateway_label($order_id),
                ],
                $transaction_id,
                ',' . trim($ticket_ids, ', ') . ','
            );

            if (!$book_id) continue;

            $u->assign($book_id, $user_id);
            update_post_meta($book_id, 'mec_confirmed', $confirmed);
            update_post_meta($book_id, 'mec_verified', $verified);
            update_post_meta($book_id, 'mec_booking_time', $order_date);
            update_post_meta($book_id, 'mec_book_date_submit', date('YmdHis', strtotime($order_date)));
            update_post_meta($book_id, 'mec_source', 'the-events-calendar');
            update_post_meta($book_id, 'mec_tec_event_id', $tec_event_id);
            update_post_meta($book_id, 'mec_tec_order_id', $order_id);

            $this->getBookingRecord()->update($book_id);
            $imported++;
        }

        remove_filter('mec_booking_confirmation', $disable_booking_confirmation);

        return $imported;
    }

    private function thirdparty_tec_attendee_name($attendee)
    {
        $attendee_id = $attendee['attendee_id'] ?? 0;
        $name = $attendee['holder_name'] ?? ($attendee['attendee_name'] ?? ($attendee['purchaser_name'] ?? ''));
        if (!trim((string) $name) && $attendee_id) $name = get_post_meta($attendee_id, '_tribe_tickets_full_name', true);
        if (!trim((string) $name)) $name = $attendee['purchaser_email'] ?? ($attendee['holder_email'] ?? '');

        return trim((string) $name);
    }

    private function thirdparty_tec_attendee_email($attendee)
    {
        $attendee_id = $attendee['attendee_id'] ?? 0;
        $email = $attendee['holder_email'] ?? ($attendee['attendee_email'] ?? ($attendee['purchaser_email'] ?? ''));
        if (!trim((string) $email) && $attendee_id) $email = get_post_meta($attendee_id, '_tribe_tickets_email', true);

        return trim((string) $email);
    }

    private function thirdparty_tec_booking_exists($mec_event_id, $order_id, $transaction_id)
    {
        $transaction = $this->getBook()->get_transaction($transaction_id);
        if (is_array($transaction) && count($transaction)) return true;

        $bookings = get_posts([
            'post_type' => $this->main->get_book_post_type(),
            'post_status' => 'any',
            'numberposts' => 1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'mec_event_id',
                    'value' => $mec_event_id,
                ],
                [
                    'key' => 'mec_tec_order_id',
                    'value' => (string) $order_id,
                ],
            ],
        ]);

        return count($bookings) > 0;
    }

    private function thirdparty_tec_order_status($order_id, $fallback = '')
    {
        if (function_exists('wc_get_order') && is_numeric($order_id))
        {
            $order = wc_get_order($order_id);
            if ($order && method_exists($order, 'get_status')) return $order->get_status();
        }

        return $fallback;
    }

    private function thirdparty_tec_status_to_mec($status)
    {
        $status = strtolower(trim(str_replace('wc-', '', (string) $status)));

        if (in_array($status, ['cancelled', 'canceled', 'failed', 'refunded', 'not-going', 'no'], true)) return [-1, -1];
        if (in_array($status, ['pending', 'pending-payment', 'on-hold', 'created'], true)) return [0, 0];

        return [1, 1];
    }

    private function thirdparty_tec_order_date($order_id, $fallback_date)
    {
        if (function_exists('wc_get_order') && is_numeric($order_id))
        {
            $order = wc_get_order($order_id);
            if ($order && method_exists($order, 'get_date_created'))
            {
                $date = $order->get_date_created();
                if ($date && method_exists($date, 'date')) return $date->date('Y-m-d H:i:s');
            }
        }

        $times = explode(':', $fallback_date);
        $timestamp = isset($times[0]) && is_numeric($times[0]) ? (int) $times[0] : current_time('timestamp');

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function thirdparty_tec_gateway_label($order_id)
    {
        if (function_exists('wc_get_order') && is_numeric($order_id))
        {
            $order = wc_get_order($order_id);
            if ($order && method_exists($order, 'get_payment_method_title'))
            {
                $label = $order->get_payment_method_title();
                if (trim((string) $label)) return $label;
            }
        }

        return esc_html__('Imported from The Events Calendar', 'modern-events-calendar-lite');
    }

    private function thirdparty_tec_normalize_booking_date($booking_date)
    {
        $times = explode(':', (string) $booking_date);
        $start = isset($times[0]) && is_numeric($times[0]) ? (int) $times[0] : 0;
        $end = isset($times[1]) && is_numeric($times[1]) ? (int) $times[1] : 0;

        if (!$start) $start = current_time('timestamp');
        if (!$end || $end < $start) $end = $start;

        return $start . ':' . $end;
    }

    public function thirdparty_weekly_class_import_do($IDs)
    {
        $count = 0;
        foreach ($IDs as $ID)
        {
            $post = get_post($ID);
            $metas = $this->main->get_post_meta($ID);

            // Event Title and Content
            $title = $post->post_title;
            $description = $post->post_content;
            $third_party_id = $ID;

            // Event location
            $locations = wp_get_post_terms($ID, 'wcs-room');
            $location_id = 1;

            // Import Event Locations into MEC locations
            if (isset($this->ix['import_locations']) and $this->ix['import_locations'] and isset($locations[0]))
            {
                $location_id = $this->main->save_location([
                    'name' => trim($locations[0]->name),
                    'address' => '',
                    'latitude' => '',
                    'longitude' => '',
                ]);
            }

            // Event Organizer
            $organizers = wp_get_post_terms($ID, 'wcs-instructor');
            $organizer_id = 1;

            // Import Event Organizer into MEC organizers
            if (isset($this->ix['import_organizers']) and $this->ix['import_organizers'] and isset($organizers[0]))
            {
                $organizer_id = $this->main->save_organizer([
                    'name' => trim($organizers[0]->name),
                    'tel' => '',
                    'url' => '',
                ]);
            }

            // Event Categories
            $categories = wp_get_post_terms($ID, 'wcs-type');
            $category_ids = [];

            // Import Event Categories into MEC categories
            if (isset($this->ix['import_categories']) and $this->ix['import_categories'] and count($categories))
            {
                foreach ($categories as $category)
                {
                    $category_id = $this->main->save_category([
                        'name' => trim($category->name),
                    ]);

                    if ($category_id) $category_ids[] = $category_id;
                }
            }

            // Event Start Date and Time
            $date_start = new DateTime(date('Y-m-d G:i', $metas['_wcs_timestamp']));

            $start_date = $date_start->format('Y-m-d');
            $start_hour = $date_start->format('g');
            $start_minutes = $date_start->format('i');
            $start_ampm = $date_start->format('A');

            // Event End Date and Time
            $date_end = new DateTime(date('Y-m-d G:i', ($metas['_wcs_timestamp'] + ($metas['_wcs_duration'] * 60))));

            $end_date = $date_end->format('Y-m-d');
            $end_hour = $date_end->format('g');
            $end_minutes = $date_end->format('i');
            $end_ampm = $date_end->format('A');

            // Event Time Options
            $hide_end_time = 0;
            $allday = 0;

            // Recurring Event
            if (isset($metas['_wcs_interval']) and $metas['_wcs_interval'])
            {
                $repeat_status = 1;
                $interval = null;
                $year = null;
                $month = null;
                $day = null;
                $week = null;
                $weekday = null;
                $weekdays = null;
                $days = null;
                $finish = (isset($metas['_wcs_repeat_until']) and trim($metas['_wcs_repeat_until'])) ? date('Y-m-d', strtotime($metas['_wcs_repeat_until'])) : null;

                $freq = trim($metas['_wcs_interval']) ? $metas['_wcs_interval'] : 2;

                if ($freq == 2) // Daily
                {
                    $repeat_type = 'daily';
                    $interval = 1;
                }
                else if ($freq == 1 or $freq == 3) // Weekly or Every Two Weeks
                {
                    $repeat_type = 'weekly';
                    $interval = $freq == 3 ? 14 : 7;
                }
                else if ($freq == 4) // Monthly
                {
                    $repeat_type = 'monthly';

                    $year = '*';
                    $month = '*';

                    $s = $start_date;
                    $e = $end_date;

                    $_days = [];
                    while (strtotime($s) <= strtotime($e))
                    {
                        $_days[] = date('d', strtotime($s));
                        $s = date('Y-m-d', strtotime('+1 Day', strtotime($s)));
                    }

                    $day = ',' . implode(',', array_unique($_days)) . ',';

                    $week = '*';
                    $weekday = '*';
                }
                else if ($freq == 5) // Yearly
                {
                    $repeat_type = 'yearly';

                    $year = '*';

                    $s = $start_date;
                    $e = $end_date;

                    $_months = [];
                    $_days = [];
                    while (strtotime($s) <= strtotime($e))
                    {
                        $_months[] = date('m', strtotime($s));
                        $_days[] = date('d', strtotime($s));

                        $s = date('Y-m-d', strtotime('+1 Day', strtotime($s)));
                    }

                    $month = ',' . implode(',', array_unique($_months)) . ',';
                    $day = ',' . implode(',', array_unique($_days)) . ',';

                    $week = '*';
                    $weekday = '*';
                }
                else $repeat_type = '';

                // Custom Week Days
                if ($repeat_type == 'daily' and isset($metas['_wcs_repeat_days']) and is_array($metas['_wcs_repeat_days']) and count($metas['_wcs_repeat_days']) > 1 and count($metas['_wcs_repeat_days']) < 7)
                {
                    $week_day_mapping = ['d1' => 1, 'd2' => 2, 'd3' => 3, 'd4' => 4, 'd5' => 5, 'd6' => 6, 'd0' => 7];

                    $weekdays = '';
                    foreach ($metas['_wcs_repeat_days'] as $week_day) $weekdays .= $week_day_mapping['d' . $week_day] . ',';

                    $weekdays = ',' . trim($weekdays, ', ') . ',';
                    $interval = null;

                    $repeat_type = 'certain_weekdays';
                }
            }
            // Single Event
            else
            {
                $repeat_status = 0;
                $repeat_type = '';
                $interval = null;
                $finish = $end_date;
                $year = null;
                $month = null;
                $day = null;
                $week = null;
                $weekday = null;
                $weekdays = null;
                $days = null;
            }

            $args = [
                'title' => $title,
                'content' => $description,
                'location_id' => $location_id,
                'organizer_id' => $organizer_id,
                'date' => [
                    'start' => [
                        'date' => $start_date,
                        'hour' => $start_hour,
                        'minutes' => $start_minutes,
                        'ampm' => $start_ampm,
                    ],
                    'end' => [
                        'date' => $end_date,
                        'hour' => $end_hour,
                        'minutes' => $end_minutes,
                        'ampm' => $end_ampm,
                    ],
                    'repeat' => [
                        'end' => 'date',
                        'end_at_date' => $finish,
                        'end_at_occurrences' => 10,
                    ],
                    'allday' => $allday,
                    'comment' => '',
                    'hide_time' => 0,
                    'hide_end_time' => $hide_end_time,
                ],
                'start' => $start_date,
                'start_time_hour' => $start_hour,
                'start_time_minutes' => $start_minutes,
                'start_time_ampm' => $start_ampm,
                'end' => $end_date,
                'end_time_hour' => $end_hour,
                'end_time_minutes' => $end_minutes,
                'end_time_ampm' => $end_ampm,
                'repeat_status' => $repeat_status,
                'repeat_type' => $repeat_type,
                'interval' => $interval,
                'finish' => $finish,
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'week' => $week,
                'weekday' => $weekday,
                'weekdays' => $weekdays,
                'days' => $days,
                'meta' => [
                    'mec_source' => 'weekly_class',
                    'mec_weekly_class_id' => $third_party_id,
                    'mec_allday' => $allday,
                    'hide_end_time' => $hide_end_time,
                    'mec_repeat_end' => ($finish ? 'date' : 'never'),
                    'mec_repeat_end_at_occurrences' => 9,
                    'mec_repeat_end_at_date' => $finish,
                    'mec_in_days' => $days,
                ],
            ];

            $post_id = $this->db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='$third_party_id' AND `meta_key`='mec_weekly_class_id'", 'loadResult');

            // Insert the event into MEC
            $post_id = $this->main->save_event($args, $post_id);

            // Set location to the post
            if ($location_id) wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

            // Set organizer to the post
            if ($organizer_id) wp_set_object_terms($post_id, (int) $organizer_id, 'mec_organizer');

            // Set categories to the post
            if (count($category_ids)) foreach ($category_ids as $category_id) wp_set_object_terms($post_id, (int) $category_id, 'mec_category', true);

            // Set Features Image
            if (isset($this->ix['import_featured_image']) and $this->ix['import_featured_image'] and $thumbnail_id = get_post_thumbnail_id($ID))
            {
                set_post_thumbnail($post_id, $thumbnail_id);
            }

            $count++;
        }

        return ['success' => 1, 'data' => $count];
    }

    public function thirdparty_calendarize_it_import_do($IDs)
    {
        $count = 0;
        foreach ($IDs as $ID)
        {
            $post = get_post($ID);
            $metas = $this->main->get_post_meta($ID);

            // Event Title and Content
            $title = $post->post_title;
            $description = $post->post_content;
            $third_party_id = $ID;

            // Event location
            $locations = wp_get_post_terms($ID, 'venue');
            $location_id = 1;

            // Import Event Locations into MEC locations
            if (isset($this->ix['import_locations']) and $this->ix['import_locations'] and isset($locations[0]))
            {
                $location_id = $this->main->save_location([
                    'name' => trim($locations[0]->name),
                    'address' => trim(get_term_meta($locations[0]->term_id, 'address', true)),
                    'latitude' => trim(get_term_meta($locations[0]->term_id, 'glat', true)),
                    'longitude' => trim(get_term_meta($locations[0]->term_id, 'glon', true)),
                ]);
            }

            // Event Organizer
            $organizers = wp_get_post_terms($ID, 'organizer');
            $organizer_id = 1;

            // Import Event Organizer into MEC organizers
            if (isset($this->ix['import_organizers']) and $this->ix['import_organizers'] and isset($organizers[0]))
            {
                $organizer_id = $this->main->save_organizer([
                    'name' => trim($organizers[0]->name),
                    'tel' => trim(get_term_meta($organizers[0]->term_id, 'phone', true)),
                    'email' => trim(get_term_meta($organizers[0]->term_id, 'email', true)),
                    'url' => trim(get_term_meta($organizers[0]->term_id, 'website', true)),
                ]);
            }

            // Event Categories
            $categories = wp_get_post_terms($ID, 'calendar');
            $category_ids = [];

            // Import Event Categories into MEC categories
            if (isset($this->ix['import_categories']) and $this->ix['import_categories'] and count($categories))
            {
                foreach ($categories as $category)
                {
                    $category_id = $this->main->save_category([
                        'name' => trim($category->name),
                    ]);

                    if ($category_id) $category_ids[] = $category_id;
                }
            }

            // Event Start Date and Time
            $date_start = new DateTime(date('Y-m-d G:i', strtotime($metas['fc_start_datetime'])));

            $start_date = $date_start->format('Y-m-d');
            $start_hour = $date_start->format('g');
            $start_minutes = $date_start->format('i');
            $start_ampm = $date_start->format('A');

            // Event End Date and Time
            $date_end = new DateTime(date('Y-m-d G:i', strtotime($metas['fc_end_datetime'])));

            $end_date = $date_end->format('Y-m-d');
            $end_hour = $date_end->format('g');
            $end_minutes = $date_end->format('i');
            $end_ampm = $date_end->format('A');

            // Event Time Options
            $hide_end_time = 0;
            $allday = $metas['fc_allday'] ?? 0;

            // Recurring Event
            if (isset($metas['fc_rrule']) and trim($metas['fc_rrule']))
            {
                $rules = explode(';', trim($metas['fc_rrule'], '; '));

                $rule = [];
                foreach ($rules as $rule_row)
                {
                    $ex = explode('=', $rule_row);
                    $key = strtolower($ex[0]);
                    $value = $key == 'until' ? $ex[1] : strtolower($ex[1]);

                    $rule[$key] = $value;
                }

                $repeat_status = 1;
                $interval = null;
                $year = null;
                $month = null;
                $day = null;
                $week = null;
                $weekday = null;
                $weekdays = null;
                $days = null;
                $finish = isset($rule['until']) ? date('Y-m-d', strtotime($rule['until'])) : null;

                if ($rule['freq'] == 'daily')
                {
                    $repeat_type = 'daily';
                    $interval = $rule['interval'] ?? 1;

                    if (isset($rule['count'])) $finish = date('Y-m-d', strtotime('+' . $rule['count'] . ' days', strtotime($start_date)));
                }
                else if ($rule['freq'] == 'weekly')
                {
                    $repeat_type = 'weekly';
                    $interval = isset($rule['interval']) ? $rule['interval'] * 7 : 7;

                    if (isset($rule['count'])) $finish = date('Y-m-d', strtotime('+' . $rule['count'] . ' weeks', strtotime($start_date)));
                }
                else if ($rule['freq'] == 'monthly')
                {
                    $repeat_type = 'monthly';

                    $year = '*';
                    $month = '*';

                    $s = $start_date;
                    $e = $end_date;

                    $_days = [];
                    while (strtotime($s) <= strtotime($e))
                    {
                        $_days[] = date('d', strtotime($s));
                        $s = date('Y-m-d', strtotime('+1 Day', strtotime($s)));
                    }

                    $day = ',' . implode(',', array_unique($_days)) . ',';

                    $week = '*';
                    $weekday = '*';

                    if (isset($rule['count'])) $finish = date('Y-m-d', strtotime('+' . $rule['count'] . ' months', strtotime($start_date)));
                }
                else if ($rule['freq'] == 'yearly')
                {
                    $repeat_type = 'yearly';

                    $year = '*';

                    $s = $start_date;
                    $e = $end_date;

                    $_months = [];
                    $_days = [];
                    while (strtotime($s) <= strtotime($e))
                    {
                        $_months[] = date('m', strtotime($s));
                        $_days[] = date('d', strtotime($s));

                        $s = date('Y-m-d', strtotime('+1 Day', strtotime($s)));
                    }

                    $month = ',' . implode(',', array_unique($_months)) . ',';
                    $day = ',' . implode(',', array_unique($_days)) . ',';

                    $week = '*';
                    $weekday = '*';

                    if (isset($rule['count'])) $finish = date('Y-m-d', strtotime('+' . $rule['count'] . ' years', strtotime($start_date)));
                }
            }
            // Custom Days
            else if (isset($metas['fc_rdate']) and trim($metas['fc_rdate']))
            {
                $fc_rdates = explode(',', $metas['fc_rdate']);
                $str_days = '';
                foreach ($fc_rdates as $fc_rdate) $str_days .= date('Y-m-d', strtotime($fc_rdate)) . ',';

                $repeat_status = 1;
                $repeat_type = 'custom_days';
                $interval = null;
                $finish = $end_date;
                $year = null;
                $month = null;
                $day = null;
                $week = null;
                $weekday = null;
                $weekdays = null;
                $days = trim($str_days, ', ');
            }
            // Single Event
            else
            {
                $repeat_status = 0;
                $repeat_type = '';
                $interval = null;
                $finish = $end_date;
                $year = null;
                $month = null;
                $day = null;
                $week = null;
                $weekday = null;
                $weekdays = null;
                $days = null;
            }

            $args = [
                'title' => $title,
                'content' => $description,
                'location_id' => $location_id,
                'organizer_id' => $organizer_id,
                'date' => [
                    'start' => [
                        'date' => $start_date,
                        'hour' => $start_hour,
                        'minutes' => $start_minutes,
                        'ampm' => $start_ampm,
                    ],
                    'end' => [
                        'date' => $end_date,
                        'hour' => $end_hour,
                        'minutes' => $end_minutes,
                        'ampm' => $end_ampm,
                    ],
                    'repeat' => [
                        'end' => 'date',
                        'end_at_date' => $finish,
                        'end_at_occurrences' => 10,
                    ],
                    'allday' => $allday,
                    'comment' => '',
                    'hide_time' => 0,
                    'hide_end_time' => $hide_end_time,
                ],
                'start' => $start_date,
                'start_time_hour' => $start_hour,
                'start_time_minutes' => $start_minutes,
                'start_time_ampm' => $start_ampm,
                'end' => $end_date,
                'end_time_hour' => $end_hour,
                'end_time_minutes' => $end_minutes,
                'end_time_ampm' => $end_ampm,
                'repeat_status' => $repeat_status,
                'repeat_type' => $repeat_type,
                'interval' => $interval,
                'finish' => $finish,
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'week' => $week,
                'weekday' => $weekday,
                'weekdays' => $weekdays,
                'days' => $days,
                'meta' => [
                    'mec_source' => 'calendarize_it',
                    'mec_calendarize_it_id' => $third_party_id,
                    'mec_allday' => $allday,
                    'hide_end_time' => $hide_end_time,
                    'mec_repeat_end' => ($finish ? 'date' : 'never'),
                    'mec_repeat_end_at_occurrences' => 9,
                    'mec_repeat_end_at_date' => $finish,
                    'mec_in_days' => $days,
                ],
            ];

            $post_id = $this->db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='$third_party_id' AND `meta_key`='mec_calendarize_it_id'", 'loadResult');

            // Insert the event into MEC
            $post_id = $this->main->save_event($args, $post_id);

            // Set location to the post
            if ($location_id) wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

            // Set organizer to the post
            if ($organizer_id) wp_set_object_terms($post_id, (int) $organizer_id, 'mec_organizer');

            // Set categories to the post
            if (count($category_ids)) foreach ($category_ids as $category_id) wp_set_object_terms($post_id, (int) $category_id, 'mec_category', true);

            // Set Features Image
            if (isset($this->ix['import_featured_image']) and $this->ix['import_featured_image'] and $thumbnail_id = get_post_thumbnail_id($ID))
            {
                set_post_thumbnail($post_id, $thumbnail_id);
            }

            $count++;
        }

        return ['success' => 1, 'data' => $count];
    }

    public function thirdparty_es_import_do($IDs)
    {
        // Timezone
        $timezone = $this->main->get_timezone();

        $count = 0;
        foreach ($IDs as $ID)
        {
            $post = get_post($ID);

            // Event Title and Content
            $title = $post->post_title;
            $description = $post->post_content;
            $third_party_id = $ID;

            // Event location
            $venue_id = $this->db->select("SELECT `VNU_ID` FROM `#__esp_event_venue` WHERE `EVT_ID`='" . $ID . "' ORDER BY `EVV_ID` ASC LIMIT 1", 'loadResult');
            $location_id = 1;

            // Import Event Locations into MEC locations
            if (isset($this->ix['import_locations']) and $this->ix['import_locations'] and $venue_id)
            {
                $v_meta = $this->db->select("SELECT * FROM `#__esp_venue_meta` WHERE `VNU_ID`='" . $venue_id . "'", 'loadAssoc');
                $location_id = $this->main->save_location([
                    'name' => get_the_title($venue_id),
                    'address' => trim($v_meta['VNU_address'] . ' ' . $v_meta['VNU_address2']),
                    'latitude' => '',
                    'longitude' => '',
                ]);
            }

            // Event Categories
            $categories = wp_get_post_terms($ID, 'espresso_event_categories');
            $category_ids = [];

            // Import Event Categories into MEC categories
            if (isset($this->ix['import_categories']) and $this->ix['import_categories'] and count($categories))
            {
                foreach ($categories as $category)
                {
                    $category_id = $this->main->save_category([
                        'name' => trim($category->name),
                    ]);

                    if ($category_id) $category_ids[] = $category_id;
                }
            }

            $datetimes = $venue_id = $this->db->select("SELECT * FROM `#__esp_datetime` WHERE `EVT_ID`='" . $ID . "' ORDER BY `DTT_EVT_start` ASC", 'loadAssocList');

            $dt_start = null;
            $dt_end = null;
            $custom_days = [];

            $i = 1;
            foreach ($datetimes as $datetime)
            {
                if (!$dt_start) $dt_start = $datetime['DTT_EVT_start'];
                if (!$dt_end) $dt_end = $datetime['DTT_EVT_end'];

                // Add to Custom Days
                if ($i > 1) $custom_days[] = [date('Y-m-d', strtotime($datetime['DTT_EVT_start'])), date('Y-m-d', strtotime($datetime['DTT_EVT_end']))];

                $i++;
            }

            // Event Start Date and Time
            $date_start = new DateTime(date('Y-m-d G:i', strtotime($dt_start)), new DateTimeZone('UTC'));
            $date_start->setTimezone(new DateTimeZone($timezone));

            $start_date = $date_start->format('Y-m-d');
            $start_hour = $date_start->format('g');
            $start_minutes = $date_start->format('i');
            $start_ampm = $date_start->format('A');

            // Event End Date and Time
            $date_end = new DateTime(date('Y-m-d G:i', strtotime($dt_end)), new DateTimeZone('UTC'));
            $date_end->setTimezone(new DateTimeZone($timezone));

            $end_date = $date_end->format('Y-m-d');
            $end_hour = $date_end->format('g');
            $end_minutes = $date_end->format('i');
            $end_ampm = $date_end->format('A');

            // Event Time Options
            $hide_end_time = 0;
            $allday = 0;

            // Custom Days
            if (count($custom_days))
            {
                $str_days = '';
                foreach ($custom_days as $custom_day) $str_days .= date('Y-m-d', strtotime($custom_day[0])) . ':' . date('Y-m-d', strtotime($custom_day[1])) . ',';

                $repeat_status = 1;
                $repeat_type = 'custom_days';
                $interval = null;
                $finish = $end_date;
                $year = null;
                $month = null;
                $day = null;
                $week = null;
                $weekday = null;
                $weekdays = null;
                $days = trim($str_days, ', ');
            }
            // Single Event
            else
            {
                $repeat_status = 0;
                $repeat_type = '';
                $interval = null;
                $finish = $end_date;
                $year = null;
                $month = null;
                $day = null;
                $week = null;
                $weekday = null;
                $weekdays = null;
                $days = null;
            }

            $args = [
                'title' => $title,
                'content' => $description,
                'location_id' => $location_id,
                'organizer_id' => 1,
                'date' => [
                    'start' => [
                        'date' => $start_date,
                        'hour' => $start_hour,
                        'minutes' => $start_minutes,
                        'ampm' => $start_ampm,
                    ],
                    'end' => [
                        'date' => $end_date,
                        'hour' => $end_hour,
                        'minutes' => $end_minutes,
                        'ampm' => $end_ampm,
                    ],
                    'repeat' => [
                        'end' => 'date',
                        'end_at_date' => $finish,
                        'end_at_occurrences' => 10,
                    ],
                    'allday' => $allday,
                    'comment' => '',
                    'hide_time' => 0,
                    'hide_end_time' => $hide_end_time,
                ],
                'start' => $start_date,
                'start_time_hour' => $start_hour,
                'start_time_minutes' => $start_minutes,
                'start_time_ampm' => $start_ampm,
                'end' => $end_date,
                'end_time_hour' => $end_hour,
                'end_time_minutes' => $end_minutes,
                'end_time_ampm' => $end_ampm,
                'repeat_status' => $repeat_status,
                'repeat_type' => $repeat_type,
                'interval' => $interval,
                'finish' => $finish,
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'week' => $week,
                'weekday' => $weekday,
                'weekdays' => $weekdays,
                'days' => $days,
                'meta' => [
                    'mec_source' => 'eventespresso',
                    'mec_eventespresso_id' => $third_party_id,
                    'mec_allday' => $allday,
                    'hide_end_time' => $hide_end_time,
                    'mec_repeat_end' => ($finish ? 'date' : 'never'),
                    'mec_repeat_end_at_occurrences' => 9,
                    'mec_repeat_end_at_date' => $finish,
                    'mec_in_days' => $days,
                ],
            ];

            $post_id = $this->db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='$third_party_id' AND `meta_key`='mec_eventespresso_id'", 'loadResult');

            // Insert the event into MEC
            $post_id = $this->main->save_event($args, $post_id);

            // Set location to the post
            if ($location_id) wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

            // Set categories to the post
            if (count($category_ids)) foreach ($category_ids as $category_id) wp_set_object_terms($post_id, (int) $category_id, 'mec_category', true);

            // Set Features Image
            if (isset($this->ix['import_featured_image']) and $this->ix['import_featured_image'] and $thumbnail_id = get_post_thumbnail_id($ID))
            {
                set_post_thumbnail($post_id, $thumbnail_id);
            }

            $count++;
        }

        return ['success' => 1, 'data' => $count];
    }

    public function thirdparty_emr_import_do($IDs)
    {
        $count = 0;
        foreach ($IDs as $ID)
        {
            $post = get_post($ID);
            $metas = $this->main->get_post_meta($ID);

            // Event Title and Content
            $title = $post->post_title;
            $description = $post->post_content;
            $third_party_id = $ID;

            // Event location
            $location = $this->db->select("SELECT * FROM `#__em_locations` WHERE `location_id`='" . (isset($metas['_location_id']) ? $metas['_location_id'] : 0) . "'", 'loadAssoc');
            $location_id = 1;

            // Import Event Locations into MEC locations
            if (isset($this->ix['import_locations']) and $this->ix['import_locations'] and isset($location['post_id']))
            {
                $address = $location['location_address'] . ' ' . $location['location_region'] . ' ' . $location['location_town'] . ' ' . $location['location_state'] . ' ' . $location['location_country'];
                $location_id = $this->main->save_location([
                    'name' => trim($location['location_name']),
                    'address' => trim($address),
                    'latitude' => trim($location['location_latitude']),
                    'longitude' => trim($location['location_longitude']),
                ]);
            }

            // Event Categories
            $categories = wp_get_post_terms($ID, 'event-categories');
            $category_ids = [];

            // Import Event Categories into MEC categories
            if (isset($this->ix['import_categories']) and $this->ix['import_categories'] and count($categories))
            {
                foreach ($categories as $category)
                {
                    $category_id = $this->main->save_category([
                        'name' => trim($category->name),
                    ]);

                    if ($category_id) $category_ids[] = $category_id;
                }
            }

            // Event Start Date and Time
            $date_start = new DateTime(date('Y-m-d G:i', strtotime($metas['_event_start_local'])));

            $start_date = $date_start->format('Y-m-d');
            $start_hour = $date_start->format('g');
            $start_minutes = $date_start->format('i');
            $start_ampm = $date_start->format('A');

            // Event End Date and Time
            $date_end = new DateTime(date('Y-m-d', strtotime('+' . (isset($metas['_recurrence_days']) ? $metas['_recurrence_days'] : 0) . ' days', strtotime($metas['_event_start_local']))) . ' ' . $metas['_event_end_time']);

            $end_date = $date_end->format('Y-m-d');
            $end_hour = $date_end->format('g');
            $end_minutes = $date_end->format('i');
            $end_ampm = $date_end->format('A');

            // Event Time Options
            $hide_end_time = 0;
            $allday = $metas['_event_all_day'] ?? 0;

            $repeat_status = 1;
            $interval = null;
            $year = null;
            $month = null;
            $day = null;
            $week = null;
            $weekday = null;
            $weekdays = null;
            $days = null;
            $finish = date('Y-m-d', strtotime($metas['_event_end_local']));
            $repeat_type = '';
            $advanced_days = null;

            if ($metas['_recurrence_freq'] == 'daily')
            {
                $repeat_type = 'daily';
                $interval = $metas['_recurrence_interval'] ?? 1;
            }
            else if ($metas['_recurrence_freq'] == 'weekly')
            {
                $repeat_type = 'certain_weekdays';
                $interval = 1;
                $weekdays = ',' . str_replace('0', '7', $metas['_recurrence_byday']) . ',';
            }
            else if ($metas['_recurrence_freq'] == 'monthly')
            {
                $repeat_type = 'advanced';

                $week_no = $metas['_recurrence_byweekno'];
                if ($week_no == '-1' || $week_no == '5') $week_no = 'l';

                $week_day = $metas['_recurrence_byday'];

                if ($week_day == '0') $week_day = 'Sun';
                else if ($week_day == '1') $week_day = 'Mon';
                else if ($week_day == '2') $week_day = 'Tue';
                else if ($week_day == '3') $week_day = 'Wed';
                else if ($week_day == '4') $week_day = 'Thu';
                else if ($week_day == '5') $week_day = 'Fri';
                else $week_day = 'Sat';

                $advanced_days = [$week_day . '.' . $week_no];
            }
            else if ($metas['_recurrence_freq'] == 'yearly')
            {
                $repeat_type = 'yearly';

                $year = '*';

                $s = $start_date;
                $e = $end_date;

                $_months = [];
                $_days = [];
                while (strtotime($s) <= strtotime($e))
                {
                    $_months[] = date('m', strtotime($s));
                    $_days[] = date('d', strtotime($s));

                    $s = date('Y-m-d', strtotime('+1 Day', strtotime($s)));
                }

                $month = ',' . implode(',', array_unique($_months)) . ',';
                $day = ',' . implode(',', array_unique($_days)) . ',';

                $week = '*';
                $weekday = '*';
            }

            $args = [
                'title' => $title,
                'content' => $description,
                'location_id' => $location_id,
                'organizer_id' => 1,
                'date' => [
                    'start' => [
                        'date' => $start_date,
                        'hour' => $start_hour,
                        'minutes' => $start_minutes,
                        'ampm' => $start_ampm,
                    ],
                    'end' => [
                        'date' => $end_date,
                        'hour' => $end_hour,
                        'minutes' => $end_minutes,
                        'ampm' => $end_ampm,
                    ],
                    'repeat' => [
                        'end' => 'date',
                        'end_at_date' => $finish,
                        'end_at_occurrences' => 10,
                    ],
                    'allday' => $allday,
                    'comment' => '',
                    'hide_time' => 0,
                    'hide_end_time' => $hide_end_time,
                ],
                'start' => $start_date,
                'start_time_hour' => $start_hour,
                'start_time_minutes' => $start_minutes,
                'start_time_ampm' => $start_ampm,
                'end' => $end_date,
                'end_time_hour' => $end_hour,
                'end_time_minutes' => $end_minutes,
                'end_time_ampm' => $end_ampm,
                'repeat_status' => $repeat_status,
                'repeat_type' => $repeat_type,
                'interval' => $interval,
                'finish' => $finish,
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'week' => $week,
                'weekday' => $weekday,
                'weekdays' => $weekdays,
                'days' => $days,
                'meta' => [
                    'mec_source' => 'event_manager_recurring',
                    'mec_emr_id' => $third_party_id,
                    'mec_allday' => $allday,
                    'hide_end_time' => $hide_end_time,
                    'mec_repeat_end' => ($finish ? 'date' : 'never'),
                    'mec_repeat_end_at_occurrences' => 9,
                    'mec_repeat_end_at_date' => $finish,
                    'mec_in_days' => $days,
                    'mec_advanced_days' => $advanced_days,
                ],
            ];

            $post_id = $this->db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='$third_party_id' AND `meta_key`='mec_emr_id'", 'loadResult');

            // Insert the event into MEC
            $post_id = $this->main->save_event($args, $post_id);

            // Set location to the post
            if ($location_id) wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

            // Set categories to the post
            if (count($category_ids)) foreach ($category_ids as $category_id) wp_set_object_terms($post_id, (int) $category_id, 'mec_category', true);

            // Set Features Image
            if (isset($this->ix['import_featured_image']) and $this->ix['import_featured_image'] and $thumbnail_id = get_post_thumbnail_id($ID))
            {
                set_post_thumbnail($post_id, $thumbnail_id);
            }

            $count++;
        }

        return ['success' => 1, 'data' => $count];
    }

    public function thirdparty_ems_import_do($IDs)
    {
        $count = 0;
        foreach ($IDs as $ID)
        {
            $post = get_post($ID);
            $metas = $this->main->get_post_meta($ID);

            // Event Title and Content
            $title = $post->post_title;
            $description = $post->post_content;
            $third_party_id = $ID;

            // Event location
            $location = $this->db->select("SELECT * FROM `#__em_locations` WHERE `location_id`='" . (isset($metas['_location_id']) ? $metas['_location_id'] : 0) . "'", 'loadAssoc');
            $location_id = 1;

            // Import Event Locations into MEC locations
            if (isset($this->ix['import_locations']) and $this->ix['import_locations'] and isset($location['post_id']))
            {
                $address = $location['location_address'] . ' ' . $location['location_region'] . ' ' . $location['location_town'] . ' ' . $location['location_state'] . ' ' . $location['location_country'];
                $location_id = $this->main->save_location([
                    'name' => trim($location['location_name']),
                    'address' => trim($address),
                    'latitude' => trim($location['location_latitude']),
                    'longitude' => trim($location['location_longitude']),
                ]);
            }

            // Event Categories
            $categories = wp_get_post_terms($ID, 'event-categories');
            $category_ids = [];

            // Import Event Categories into MEC categories
            if (isset($this->ix['import_categories']) and $this->ix['import_categories'] and count($categories))
            {
                foreach ($categories as $category)
                {
                    $category_id = $this->main->save_category([
                        'name' => trim($category->name),
                    ]);

                    if ($category_id) $category_ids[] = $category_id;
                }
            }

            // Event Start Date and Time
            $date_start = new DateTime(date('Y-m-d G:i', strtotime($metas['_event_start_local'])));

            $start_date = $date_start->format('Y-m-d');
            $start_hour = $date_start->format('g');
            $start_minutes = $date_start->format('i');
            $start_ampm = $date_start->format('A');

            // Event End Date and Time
            $date_end = new DateTime(date('Y-m-d G:i', strtotime($metas['_event_end_local'])));

            $end_date = $date_end->format('Y-m-d');
            $end_hour = $date_end->format('g');
            $end_minutes = $date_end->format('i');
            $end_ampm = $date_end->format('A');

            // Event Time Options
            $hide_end_time = 0;
            $allday = $metas['_event_all_day'] ?? 0;

            // Single Event
            $repeat_status = 0;
            $repeat_type = '';
            $interval = null;
            $finish = $end_date;
            $year = null;
            $month = null;
            $day = null;
            $week = null;
            $weekday = null;
            $weekdays = null;
            $days = null;

            $args = [
                'title' => $title,
                'content' => $description,
                'location_id' => $location_id,
                'organizer_id' => 1,
                'date' => [
                    'start' => [
                        'date' => $start_date,
                        'hour' => $start_hour,
                        'minutes' => $start_minutes,
                        'ampm' => $start_ampm,
                    ],
                    'end' => [
                        'date' => $end_date,
                        'hour' => $end_hour,
                        'minutes' => $end_minutes,
                        'ampm' => $end_ampm,
                    ],
                    'repeat' => [
                        'end' => 'date',
                        'end_at_date' => $finish,
                        'end_at_occurrences' => 10,
                    ],
                    'allday' => $allday,
                    'comment' => '',
                    'hide_time' => 0,
                    'hide_end_time' => $hide_end_time,
                ],
                'start' => $start_date,
                'start_time_hour' => $start_hour,
                'start_time_minutes' => $start_minutes,
                'start_time_ampm' => $start_ampm,
                'end' => $end_date,
                'end_time_hour' => $end_hour,
                'end_time_minutes' => $end_minutes,
                'end_time_ampm' => $end_ampm,
                'repeat_status' => $repeat_status,
                'repeat_type' => $repeat_type,
                'interval' => $interval,
                'finish' => $finish,
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'week' => $week,
                'weekday' => $weekday,
                'weekdays' => $weekdays,
                'days' => $days,
                'meta' => [
                    'mec_source' => 'event_manager_single',
                    'mec_ems_id' => $third_party_id,
                    'mec_allday' => $allday,
                    'hide_end_time' => $hide_end_time,
                    'mec_repeat_end' => ($finish ? 'date' : 'never'),
                    'mec_repeat_end_at_occurrences' => 9,
                    'mec_repeat_end_at_date' => $finish,
                    'mec_in_days' => $days,
                ],
            ];

            $post_id = $this->db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='$third_party_id' AND `meta_key`='mec_ems_id'", 'loadResult');

            // Insert the event into MEC
            $post_id = $this->main->save_event($args, $post_id);

            // Set location to the post
            if ($location_id) wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

            // Set categories to the post
            if (count($category_ids)) foreach ($category_ids as $category_id) wp_set_object_terms($post_id, (int) $category_id, 'mec_category', true);

            // Set Features Image
            if (isset($this->ix['import_featured_image']) and $this->ix['import_featured_image'] and $thumbnail_id = get_post_thumbnail_id($ID))
            {
                set_post_thumbnail($post_id, $thumbnail_id);
            }

            $count++;
        }

        return ['success' => 1, 'data' => $count];
    }

    public function thirdparty_wpem_import_do($IDs)
    {
        $count = 0;
        foreach ($IDs as $ID)
        {
            $post = get_post($ID);
            $metas = $this->main->get_post_meta($ID);

            // Event Title and Content
            $title = $post->post_title;
            $description = $post->post_content;
            $third_party_id = $ID;

            // Event location
            $location = get_post($metas['_event_venue_ids']);
            $location_id = 1;

            // Import Event Locations into MEC locations
            if (isset($this->ix['import_locations']) and $this->ix['import_locations'] and isset($location->ID))
            {
                $l_metas = $this->main->get_post_meta($location->ID);
                $thumbnail = has_post_thumbnail($location->ID) ? $this->main->get_post_thumbnail_url($location->ID, 'full') : '';

                $location_id = $this->main->save_location([
                    'name' => trim($location->post_title),
                    'address' => (isset($l_metas['_venue_description']) ? $l_metas['_venue_description'] : ''),
                    'latitude' => 0,
                    'longitude' => 0,
                    'thumbnail' => $thumbnail,
                ]);
            }

            // Event Organizer
            $organizers = $metas['_event_organizer_ids'];
            $organizer = (isset($organizers[0]) ? get_post($organizers[0]) : new stdClass());

            if (isset($organizers[0])) unset($organizers[0]);
            $wpem_additional_organizers_ids = $organizers;

            $organizer_id = 1;
            $additional_organizers_ids = [];

            // Import Event Organizer into MEC organizers
            if (isset($this->ix['import_organizers']) and $this->ix['import_organizers'] and isset($organizer->ID))
            {
                $o_metas = $this->main->get_post_meta($organizer->ID);

                $organizer_id = $this->main->save_organizer([
                    'name' => trim($organizer->post_title),
                    'tel' => '',
                    'email' => ($o_metas['_organizer_email'] ?? ''),
                    'url' => ($o_metas['_organizer_website'] ?? ''),
                ]);

                if (is_array($wpem_additional_organizers_ids) and count($wpem_additional_organizers_ids))
                {
                    foreach ($wpem_additional_organizers_ids as $wpem_additional_organizers_id)
                    {
                        $o_organizer = get_post($wpem_additional_organizers_id);
                        $o_metas = $this->main->get_post_meta($wpem_additional_organizers_id);

                        $additional_organizers_ids[] = $this->main->save_organizer([
                            'name' => trim($o_organizer->post_title),
                            'tel' => '',
                            'email' => ($o_metas['_organizer_email'] ?? ''),
                            'url' => ($o_metas['_organizer_website'] ?? ''),
                        ]);
                    }
                }
            }

            // Event Categories
            $categories = wp_get_post_terms($ID, 'event_listing_category');
            $category_ids = [];

            // Import Event Categories into MEC categories
            if (isset($this->ix['import_categories']) and $this->ix['import_categories'] and count($categories))
            {
                foreach ($categories as $category)
                {
                    $category_id = $this->main->save_category([
                        'name' => trim($category->name),
                    ]);

                    if ($category_id) $category_ids[] = $category_id;
                }
            }

            // Event Start Date and Time
            $date_start = new DateTime(date('Y-m-d G:i', strtotime($metas['_event_start_date'])));

            $start_date = $date_start->format('Y-m-d');
            $start_hour = $date_start->format('g');
            $start_minutes = $date_start->format('i');
            $start_ampm = $date_start->format('A');

            // Event End Date and Time
            $date_end = new DateTime(date('Y-m-d G:i', strtotime($metas['_event_end_date'])));

            $end_date = $date_end->format('Y-m-d');
            $end_hour = $date_end->format('g');
            $end_minutes = $date_end->format('i');
            $end_ampm = $date_end->format('A');

            // Event Time Options
            $hide_end_time = 0;
            $allday = 0;

            // Single Event
            $repeat_status = 0;
            $repeat_type = '';
            $interval = null;
            $finish = $end_date;
            $year = null;
            $month = null;
            $day = null;
            $week = null;
            $weekday = null;
            $weekdays = null;
            $days = null;

            $args = [
                'title' => $title,
                'content' => $description,
                'location_id' => $location_id,
                'organizer_id' => $organizer_id,
                'date' => [
                    'start' => [
                        'date' => $start_date,
                        'hour' => $start_hour,
                        'minutes' => $start_minutes,
                        'ampm' => $start_ampm,
                    ],
                    'end' => [
                        'date' => $end_date,
                        'hour' => $end_hour,
                        'minutes' => $end_minutes,
                        'ampm' => $end_ampm,
                    ],
                    'repeat' => [
                        'end' => 'date',
                        'end_at_date' => $finish,
                        'end_at_occurrences' => 10,
                    ],
                    'allday' => $allday,
                    'comment' => '',
                    'hide_time' => 0,
                    'hide_end_time' => $hide_end_time,
                ],
                'start' => $start_date,
                'start_time_hour' => $start_hour,
                'start_time_minutes' => $start_minutes,
                'start_time_ampm' => $start_ampm,
                'end' => $end_date,
                'end_time_hour' => $end_hour,
                'end_time_minutes' => $end_minutes,
                'end_time_ampm' => $end_ampm,
                'repeat_status' => $repeat_status,
                'repeat_type' => $repeat_type,
                'interval' => $interval,
                'finish' => $finish,
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'week' => $week,
                'weekday' => $weekday,
                'weekdays' => $weekdays,
                'days' => $days,
                'meta' => [
                    'mec_source' => 'the-events-calendar',
                    'mec_tec_id' => $third_party_id,
                    'mec_allday' => $allday,
                    'hide_end_time' => $hide_end_time,
                    'mec_repeat_end' => 'date',
                    'mec_repeat_end_at_occurrences' => 9,
                    'mec_repeat_end_at_date' => $finish,
                    'mec_in_days' => $days,
                    'mec_more_info' => '',
                    'mec_cost' => '',
                ],
            ];

            $post_id = $this->db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='$third_party_id' AND `meta_key`='mec_tec_id'", 'loadResult');

            // Insert the event into MEC
            $post_id = $this->main->save_event($args, $post_id);

            // Set location to the post
            if ($location_id) wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

            // Set organizer to the post
            if ($organizer_id) wp_set_object_terms($post_id, (int) $organizer_id, 'mec_organizer');

            // Set additional organizers
            if (is_array($additional_organizers_ids) and count($additional_organizers_ids))
            {
                foreach ($additional_organizers_ids as $additional_organizers_id) wp_set_object_terms($post_id, (int) $additional_organizers_id, 'mec_organizer', true);
                update_post_meta($post_id, 'mec_additional_organizer_ids', $additional_organizers_ids);
            }

            // Set categories to the post
            if (count($category_ids)) foreach ($category_ids as $category_id) wp_set_object_terms($post_id, (int) $category_id, 'mec_category', true);

            // Set Features Image
            if (isset($this->ix['import_featured_image']) and $this->ix['import_featured_image'] and $thumbnail_id = get_post_thumbnail_id($ID))
            {
                set_post_thumbnail($post_id, $thumbnail_id);
            }

            $count++;
        }

        return ['success' => 1, 'data' => $count];
    }

    /**
     * Show content of export tab
     * @return void
     * @author Webnus <info@webnus.net>
     */
    public function ix_g_calendar_export()
    {
        // Current Action
        $this->action = isset($_POST['mec-ix-action']) ? sanitize_text_field($_POST['mec-ix-action']) : (isset($_GET['mec-ix-action']) ? sanitize_text_field($_GET['mec-ix-action']) : '');

        $path = MEC::import('app.features.ix.export_g_calendar', true, true);

        ob_start();
        include $path;
        echo MEC_kses::full(ob_get_clean());
    }

    /**
     * Show content of import tab
     * @return void
     * @author Webnus <info@webnus.net>
     */
    public function ix_g_calendar_import()
    {
        // Current Action
        $this->action = isset($_POST['mec-ix-action']) ? sanitize_text_field($_POST['mec-ix-action']) : '';
        $this->ix = ((isset($_POST['ix']) and is_array($_POST['ix'])) ? array_map('sanitize_text_field', $_POST['ix']) : []);

        $this->response = [];
        if ($this->action == 'google-calendar-import-start') $this->response = $this->g_calendar_import_start();
        else if ($this->action == 'google-calendar-import-do') $this->response = $this->g_calendar_import_do();

        $path = MEC::import('app.features.ix.import_g_calendar', true, true);

        ob_start();
        include $path;
        echo MEC_kses::full(ob_get_clean());
    }

    public function g_calendar_import_start()
    {
        $api_key = $this->ix['google_import_api_key'] ?? null;
        $calendar_id = $this->ix['google_import_calendar_id'] ?? null;
        $start_date = $this->ix['google_import_start_date'] ?? 'Today';
        $end_date = (isset($this->ix['google_import_end_date']) and trim($this->ix['google_import_end_date'])) ? $this->ix['google_import_end_date'] : 'Tomorrow';

        if (!trim($api_key) or !trim($calendar_id)) return ['success' => 0, 'error' => __('API key and Calendar ID are required.', 'modern-events-calendar-lite')];

        // Save options
        $this->main->save_ix_options(['google_import_api_key' => $api_key, 'google_import_calendar_id' => $calendar_id, 'google_import_start_date' => $start_date, 'google_import_end_date' => $end_date]);

        // GMT Offset
        $gmt_offset = $this->main->get_gmt_offset();

        $client = new Google_Client();
        $client->setApplicationName('Modern Events Calendar');
        $client->setAccessType('online');
        $client->setScopes(['https://www.googleapis.com/auth/calendar.readonly']);
        $client->setDeveloperKey($api_key);

        $service = new Google_Service_Calendar($client);
        $data = [];

        try
        {
            $args = [];
            $args['timeMin'] = date('Y-m-d\TH:i:s', strtotime($start_date)) . $gmt_offset;
            $args['timeMax'] = date('Y-m-d\TH:i:s', strtotime($end_date)) . $gmt_offset;
            $args['maxResults'] = 50000;

            $response = $service->events->listEvents($calendar_id, $args);

            $data['id'] = $calendar_id;
            $data['title'] = $response->getSummary();
            $data['timezone'] = $response->getTimeZone();
            $data['events'] = [];

            foreach ($response->getItems() as $event)
            {
                $title = $event->getSummary();
                if (trim($title) == '') continue;

                $recurring_event_id = $event->getRecurringEventId();

                // Update Date & Time
                if (isset($data['events'][$recurring_event_id]))
                {
                    $data['events'][$recurring_event_id]['start'] = $event->getStart();
                    $data['events'][$recurring_event_id]['end'] = $event->getEnd();
                }
                // Import Only Main Events
                else if (!$recurring_event_id) $data['events'][$event->id] = ['id' => $event->id, 'title' => $title, 'start' => $event->getStart(), 'end' => $event->getEnd()];
            }

            $data['count'] = count($data['events']);
        }
        catch (Exception $e)
        {
            $error = $e->getMessage();
            return ['success' => 0, 'error' => $error];
        }

        return ['success' => 1, 'data' => $data];
    }

    public function g_calendar_import_do()
    {
        $g_events = ((isset($_POST['g-events']) and is_array($_POST['g-events'])) ? array_map('sanitize_text_field', $_POST['g-events']) : []);
        if (!count($g_events)) return ['success' => 0, 'error' => __('Please select events to import!', 'modern-events-calendar-lite')];

        $api_key = $this->ix['google_import_api_key'] ?? null;
        $calendar_id = $this->ix['google_import_calendar_id'] ?? null;

        if (!trim($api_key) or !trim($calendar_id)) return ['success' => 0, 'error' => __('API key and Calendar ID are required.', 'modern-events-calendar-lite')];

        // Timezone
        $timezone = $this->main->get_timezone();

        $client = new Google_Client();
        $client->setApplicationName('Modern Events Calendar');
        $client->setAccessType('online');
        $client->setScopes(['https://www.googleapis.com/auth/calendar.readonly']);
        $client->setDeveloperKey($api_key);

        $service = new Google_Service_Calendar($client);
        $post_ids = [];

        foreach ($g_events as $g_event)
        {
            try
            {
                $event = $service->events->get($calendar_id, $g_event, ['timeZone' => $timezone]);
            }
            catch (Exception $e)
            {
                continue;
            }

            // Event Title and Content
            $title = $event->getSummary();
            $description = $event->getDescription();
            $gcal_ical_uid = $event->getICalUID();
            $gcal_id = $event->getId();

            // Event location
            $location = $event->getLocation();
            $location_id = 1;

            // Import Event Locations into MEC locations
            if (isset($this->ix['import_locations']) and $this->ix['import_locations'] and trim($location))
            {
                $location_ex = explode(',', $location);
                $location_id = $this->main->save_location([
                    'name' => trim($location_ex[0]),
                    'address' => $location,
                ]);
            }

            // Event Organizer
            $organizer = $event->getOrganizer();
            $organizer_id = 1;

            // Import Event Organizer into MEC organizers
            if (isset($this->ix['import_organizers']) and $this->ix['import_organizers'])
            {
                $organizer_id = $this->main->save_organizer([
                    'name' => $organizer->getDisplayName(),
                    'email' => $organizer->getEmail(),
                ]);
            }

            // Event Start Date and Time
            $start = $event->getStart();

            $g_start_date = $start->getDate();
            $g_start_datetime = $start->getDateTime();

            $date_start = new DateTime((trim($g_start_datetime) ? $g_start_datetime : $g_start_date));
            $start_date = $date_start->format('Y-m-d');
            $start_hour = 8;
            $start_minutes = '00';
            $start_ampm = 'AM';

            if (trim($g_start_datetime))
            {
                $start_hour = $date_start->format('g');
                $start_minutes = $date_start->format('i');
                $start_ampm = $date_start->format('A');
            }

            // Event End Date and Time
            $end = $event->getEnd();

            $g_end_date = $end->getDate();
            $g_end_datetime = $end->getDateTime();

            $date_end = new DateTime((trim($g_end_datetime) ? $g_end_datetime : $g_end_date));
            $end_date = $date_end->format('Y-m-d');
            $end_hour = 6;
            $end_minutes = '00';
            $end_ampm = 'PM';

            if (trim($g_end_datetime))
            {
                $end_hour = $date_end->format('g');
                $end_minutes = $date_end->format('i');
                $end_ampm = $date_end->format('A');
            }

            // Event Time Options
            $allday = 0;

            // Both Start and Date times are empty, so it's all day event
            if (!trim($g_end_datetime) and !trim($g_start_datetime))
            {
                $allday = 1;

                $start_hour = 0;
                $start_minutes = 0;
                $start_ampm = 'AM';

                $end_hour = 11;
                $end_minutes = 55;
                $end_ampm = 'PM';
            }

            // Recurring Event
            if ($event->getRecurrence())
            {
                $repeat_status = 1;
                $r_rules = $event->getRecurrence();

                $i = 0;

                do
                {
                    $g_recurrence_rule = $r_rules[$i];
                    $main_rule_ex = explode(':', $g_recurrence_rule);
                    $rules = explode(';', $main_rule_ex[1]);

                    $i++;
                } while ($main_rule_ex[0] != 'RRULE' and isset($r_rules[$i]));

                $rule = [];
                foreach ($rules as $rule_row)
                {
                    $ex = explode('=', $rule_row);
                    $key = strtolower($ex[0]);
                    $value = isset($ex[1]) ? ($key == 'until' ? $ex[1] : strtolower($ex[1])) : '';

                    $rule[$key] = $value;
                }

                $interval = null;
                $year = null;
                $month = null;
                $day = null;
                $week = null;
                $weekday = null;
                $weekdays = null;
                $advanced_days = null;

                $repeat_count = null;
                if (isset($rule['count']) and is_numeric($rule['count'])) $repeat_count = max($rule['count'], 0);

                if (isset($rule['freq']) && $rule['freq'] == 'daily')
                {
                    $repeat_type = 'daily';
                    $interval = $rule['interval'] ?? 1;
                }
                else if (isset($rule['freq']) && $rule['freq'] == 'weekly')
                {
                    $repeat_type = 'weekly';
                    $interval = isset($rule['interval']) ? $rule['interval'] * 7 : 7;
                }
                else if (isset($rule['freq']) && $rule['freq'] == 'monthly' and isset($rule['byday']) and trim($rule['byday']))
                {
                    $repeat_type = 'advanced';

                    $adv_week = (isset($rule['bysetpos']) and trim($rule['bysetpos']) != '') ? $rule['bysetpos'] : (int) substr($rule['byday'], 0, -2);
                    $adv_day = str_replace($adv_week, '', $rule['byday']);

                    $mec_adv_day = 'Sat';
                    if ($adv_day == 'su') $mec_adv_day = 'Sun';
                    else if ($adv_day == 'mo') $mec_adv_day = 'Mon';
                    else if ($adv_day == 'tu') $mec_adv_day = 'Tue';
                    else if ($adv_day == 'we') $mec_adv_day = 'Wed';
                    else if ($adv_day == 'th') $mec_adv_day = 'Thu';
                    else if ($adv_day == 'fr') $mec_adv_day = 'Fri';

                    if ($adv_week < 0) $adv_week = 'l';

                    $advanced_days = [$mec_adv_day . '.' . $adv_week];
                }
                else if (isset($rule['freq']) && $rule['freq'] == 'monthly')
                {
                    $repeat_type = 'monthly';
                    $interval = $rule['interval'] ?? 1;

                    $year = '*';
                    $month = '*';

                    $s = $start_date;
                    $e = $end_date;

                    $_days = [];
                    while (strtotime($s) <= strtotime($e))
                    {
                        $_days[] = date('d', strtotime($s));
                        $s = date('Y-m-d', strtotime('+1 Day', strtotime($s)));
                    }

                    $day = ',' . implode(',', array_unique($_days)) . ',';

                    $week = '*';
                    $weekday = '*';
                }
                else if (isset($rule['freq']) && $rule['freq'] == 'yearly')
                {
                    $repeat_type = 'yearly';

                    $year = '*';

                    $s = $start_date;
                    $e = $end_date;

                    $_months = [];
                    $_days = [];
                    while (strtotime($s) <= strtotime($e))
                    {
                        $_months[] = date('m', strtotime($s));
                        $_days[] = date('d', strtotime($s));

                        $s = date('Y-m-d', strtotime('+1 Day', strtotime($s)));
                    }

                    $month = ',' . implode(',', array_unique($_months)) . ',';
                    $day = ',' . implode(',', array_unique($_days)) . ',';

                    $week = '*';
                    $weekday = '*';
                }
                else $repeat_type = '';

                // Custom Week Days
                if ($repeat_type == 'weekly' and isset($rule['byday']) and count(explode(',', $rule['byday'])) > 1)
                {
                    $g_week_days = explode(',', $rule['byday']);
                    $week_day_mapping = ['mo' => 1, 'tu' => 2, 'we' => 3, 'th' => 4, 'fr' => 5, 'sa' => 6, 'su' => 7];

                    $weekdays = '';
                    foreach ($g_week_days as $g_week_day) $weekdays .= $week_day_mapping[$g_week_day] . ',';

                    $weekdays = ',' . trim($weekdays, ', ') . ',';
                    $interval = null;

                    $repeat_type = 'certain_weekdays';
                }

                $finish = isset($rule['until']) ? date('Y-m-d', strtotime($rule['until'])) : null;

                // It's all day event, so we should reduce one day from the end date! Google provides 2020-12-12 while the event ends at 2020-12-11
                if ($allday)
                {
                    $diff = $this->main->date_diff($start_date, $end_date);
                    if (($diff ? $diff->days : 0) >= 1)
                    {
                        $date_end->sub(new DateInterval('P1D'));
                        $end_date = $date_end->format('Y-m-d');
                    }
                }
            }
            // Single Event
            else
            {
                // It's a one-day single event but google sends 2020-12-12 as end date if start date is 2020-12-11
                if (trim($g_end_datetime) == '' and date('Y-m-d', strtotime('-1 day', strtotime($end_date))) == $start_date)
                {
                    $end_date = $start_date;
                }
                // It's all day event, so we should reduce one day from the end date! Google provides 2020-12-12 while the event ends at 2020-12-11
                else if ($allday)
                {
                    $diff = $this->main->date_diff($start_date, $end_date);
                    if (($diff ? $diff->days : 0) > 1)
                    {
                        $date_end->sub(new DateInterval('P1D'));
                        $end_date = $date_end->format('Y-m-d');
                    }
                }

                $repeat_status = 0;
                $g_recurrence_rule = '';
                $repeat_type = '';
                $interval = null;
                $finish = $end_date;
                $year = null;
                $month = null;
                $day = null;
                $week = null;
                $weekday = null;
                $weekdays = null;
                $advanced_days = null;
                $repeat_count = null;
            }

            $args = [
                'title' => $title,
                'content' => $description,
                'location_id' => $location_id,
                'organizer_id' => $organizer_id,
                'date' => [
                    'start' => [
                        'date' => $start_date,
                        'hour' => $start_hour,
                        'minutes' => $start_minutes,
                        'ampm' => $start_ampm,
                    ],
                    'end' => [
                        'date' => $end_date,
                        'hour' => $end_hour,
                        'minutes' => $end_minutes,
                        'ampm' => $end_ampm,
                    ],
                    'repeat' => [],
                    'allday' => $allday,
                    'comment' => '',
                    'hide_time' => 0,
                    'hide_end_time' => 0,
                ],
                'start' => $start_date,
                'start_time_hour' => $start_hour,
                'start_time_minutes' => $start_minutes,
                'start_time_ampm' => $start_ampm,
                'end' => $end_date,
                'end_time_hour' => $end_hour,
                'end_time_minutes' => $end_minutes,
                'end_time_ampm' => $end_ampm,
                'repeat_status' => $repeat_status,
                'repeat_type' => $repeat_type,
                'repeat_count' => $repeat_count,
                'interval' => $interval,
                'finish' => $finish,
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'week' => $week,
                'weekday' => $weekday,
                'weekdays' => $weekdays,
                'meta' => [
                    'mec_source' => 'google-calendar',
                    'mec_gcal_ical_uid' => $gcal_ical_uid,
                    'mec_gcal_id' => $gcal_id,
                    'mec_gcal_calendar_id' => $calendar_id,
                    'mec_g_recurrence_rule' => $g_recurrence_rule,
                    'mec_allday' => $allday,
                    'mec_advanced_days' => $advanced_days,
                ],
            ];

            $post_id = $this->db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='$gcal_id' AND `meta_key`='mec_gcal_id'", 'loadResult');

            // Imported From Google
            if (!post_exists($title, $description, '', $this->main->get_main_post_type())) $args['meta']['mec_imported_from_google'] = 1;

            // Insert the event into MEC
            $post_id = $this->main->save_event($args, $post_id);
            $post_ids[] = $post_id;

            // Set location to the post
            if ($location_id) wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

            // Set organizer to the post
            if ($organizer_id) wp_set_object_terms($post_id, (int) $organizer_id, 'mec_organizer');

            // MEC Dates
            $dates = $this->db->select("SELECT `dstart` FROM `#__mec_dates` WHERE `post_id`='" . $post_id . "' ORDER BY `tstart` ASC LIMIT 50", 'loadColumn');

            // Event Instances
            $instances = $service->events->instances($calendar_id, $gcal_id, ['maxResults' => 50]);

            $gdates = [];
            foreach ($instances as $instance)
            {
                $start = $instance->getStart();
                $date = $start->getDate();

                $gdates[] = $date;
            }

            $exdates = [];
            $previous_not_found = null;
            $next_found = null;

            foreach ($dates as $date)
            {
                if (!in_array($date, $gdates)) $previous_not_found = $date;
                else if ($previous_not_found)
                {
                    $exdates[] = $previous_not_found;
                    $previous_not_found = null;
                }
            }

            // Update MEC EXDATES
            $exdates = array_unique($exdates);
            if (count($exdates))
            {
                $args['not_in_days'] = implode(',', $exdates);

                $this->main->save_event($args, $post_id);
            }
        }

        return ['success' => 1, 'data' => $post_ids];
    }

    /**
     * Show content of meetup import tab
     * @return void
     * @throws Exception
     * @author Webnus <info@webnus.net>
     */
    public function ix_meetup_import()
    {
        // Current Action
        $this->action = isset($_POST['mec-ix-action']) ? sanitize_text_field($_POST['mec-ix-action']) : 'meetup-import-config';
        if (isset($_GET['mec-ix-action']) and trim($_GET['mec-ix-action'])) $this->action = $_GET['mec-ix-action'];

        $this->ix = isset($_POST['ix']) && is_array($_POST['ix']) ? array_map('sanitize_text_field', $_POST['ix']) : [];

        $this->response = [];

        if ($this->action == 'meetup-import-login') $this->response = $this->meetup_import_login();
        else if ($this->action == 'meetup-import-start') $this->response = $this->meetup_import_start();
        else if ($this->action == 'meetup-import-do') $this->response = $this->meetup_import_do();

        $path = MEC::import('app.features.ix.import_meetup', true, true);

        ob_start();
        include $path;
        echo MEC_kses::full(ob_get_clean());
    }

    public function meetup_import_login()
    {
        // Meetup Keys
        $public_key = $this->ix['meetup_public_key'] ?? null;
        $secret_key = $this->ix['meetup_secret_key'] ?? null;
        $group_name = $this->ix['meetup_group_name'] ?? null;

        // Save options
        $this->main->save_ix_options([
            'meetup_public_key' => $public_key,
            'meetup_secret_key' => $secret_key,
            'meetup_group_name' => $group_name,
        ]);

        // Meetup API
        $meetup = $this->getMeetup();

        // Redirect URL
        $redirect_url = $meetup->get_redirect_url();

        return [
            'login' => 'https://secure.meetup.com/oauth2/authorize?client_id=' . $public_key . '&response_type=code&redirect_uri=' . urlencode($redirect_url),
        ];
    }

    public function meetup_import_start()
    {
        // One Time Key
        $code = $_GET['code'] ?? '';

        // No Access
        if (!trim($code)) return ['success' => 0, 'error' => __('Something happened. Please make sure you allow the website to access your meetup account.', 'modern-events-calendar-lite')];

        // IX Options
        $ix = $this->main->get_ix_options();

        // Meetup API
        $meetup = $this->getMeetup();

        // Token
        $token = $meetup->get_tokens_by_code($code);

        // Group Name
        $group_name = $ix['meetup_group_name'] ?? '';

        // Group Info
        $group_info = $meetup->get_group_name($token, $group_name);

        // No Access
        if (!isset($group_info['data']['groupByUrlname']) || !is_array($group_info['data']['groupByUrlname'])) return ['success' => 0, 'error' => __('Meetup group not found!', 'modern-events-calendar-lite')];

        // Upcoming Events
        $events = $meetup->get_group_events($token, $group_name);
        $data = [];

        try
        {
            $m_events = [];
            if (isset($events['data']['groupByUrlname']['upcomingEvents']['edges']))
            {
                foreach ($events['data']['groupByUrlname']['upcomingEvents']['edges'] as $edge)
                {
                    $start = $edge['node']['dateTime'];
                    $end = $edge['node']['endTime'];

                    $m_events[] = [
                        'id' => $edge['node']['id'],
                        'title' => $edge['node']['title'],
                        'url' => $edge['node']['eventUrl'],
                        'start' => date('Y-m-d H:i:s', strtotime($start)),
                        'end' => date('Y-m-d H:i:s', strtotime($end)),
                    ];
                }
            }

            $data['title'] = $group_info['data']['groupByUrlname']['name'];
            $data['events'] = $m_events;
            $data['count'] = count($m_events);
        }
        catch (Exception $e)
        {
            $error = $e->getMessage();
            return ['success' => 0, 'error' => $error];
        }

        return ['success' => 1, 'data' => $data];
    }

    /**
     * @throws Exception
     */
    public function meetup_import_do()
    {
        $m_events = isset($_POST['m-events']) && is_array($_POST['m-events']) ? array_map('sanitize_text_field', $_POST['m-events']) : [];
        if (!count($m_events)) return ['success' => 0, 'error' => __('Please select events to import!', 'modern-events-calendar-lite')];

        // Meetup API
        $meetup = $this->getMeetup();

        // IX Options
        $ix = $this->main->get_ix_options();
        $group_name = $ix['meetup_group_name'] ?? null;

        // Token
        $token = $meetup->get_token();

        // Invalid Token
        if (!trim($token)) return ['success' => 0, 'error' => __('Invalid API Token.', 'modern-events-calendar-lite')];

        // Timezone
        $timezone = $this->main->get_timezone();

        // MEC File
        $file = $this->getFile();
        $wp_upload_dir = wp_upload_dir();

        $post_ids = [];
        foreach ($m_events as $m_event)
        {
            try
            {
                $data = $meetup->get_event($token, $m_event);
                $event = isset($data['data']['event']) && is_array($data['data']['event']) ? $data['data']['event'] : [];

                if (!count($event)) continue;
            }
            catch (Exception $e)
            {
                continue;
            }

            // Event Title and Content
            $title = $event['title'];
            $description = $event['description'];
            $mcal_id = $event['id'];

            // Event location
            $location = $event['venue'] ?? [];
            $location_id = 1;

            // Import Event Locations into MEC locations
            if (isset($this->ix['import_locations']) && $this->ix['import_locations'] && count($location))
            {
                $address = $location['address'] ?? '';
                $address .= isset($location['city']) ? ', ' . $location['city'] : '';
                $address .= isset($location['state']) ? ', ' . $location['state'] : '';
                $address .= isset($location['country']) ? ', ' . $location['country'] : '';

                $location_id = $this->main->save_location([
                    'name' => trim($location['name']),
                    'latitude' => trim($location['lat']),
                    'longitude' => trim($location['lng']),
                    'address' => $address,
                ]);
            }

            // Event Organizer
            $organizers = $event['hosts'] ?? [];
            $main_organizer_id = 1;
            $additional_organizer_ids = [];

            // Import Event Organizer into MEC organizers
            if (isset($this->ix['import_organizers']) && $this->ix['import_organizers'] && count($organizers))
            {
                $o = 1;
                foreach ($organizers as $organizer)
                {
                    $organizer_id = $this->main->save_organizer([
                        'name' => $organizer['name'],
                        'thumbnail' => '',
                    ]);

                    if ($o == 1) $main_organizer_id = $organizer_id;
                    else $additional_organizer_ids[] = $organizer_id;

                    $o++;
                }
            }

            // Timezone
            $TZ = $event['timezone'] ?? 'UTC';

            // Event Start Date and Time
            $start = strtotime($event['dateTime']);

            $date_start = new DateTime(date('Y-m-d H:i:s', $start), new DateTimeZone($TZ));
            $date_start->setTimezone(new DateTimeZone($timezone));

            $start_date = $date_start->format('Y-m-d');
            $start_hour = $date_start->format('g');
            $start_minutes = $date_start->format('i');
            $start_ampm = $date_start->format('A');

            // Event End Date and Time
            $end = strtotime($event['endTime']);

            $date_end = new DateTime(date('Y-m-d H:i:s', $end), new DateTimeZone($TZ));
            $date_end->setTimezone(new DateTimeZone($timezone));

            $end_date = $date_end->format('Y-m-d');
            $end_hour = $date_end->format('g');
            $end_minutes = $date_end->format('i');
            $end_ampm = $date_end->format('A');

            // Meetup Link
            $more_info = $event['eventUrl'] ?? '';

            // Fee Options
            $fee = 0;
            if (isset($event['feeSettings']) && is_array($event['feeSettings']))
            {
                $fee = $event['feeSettings']['amount'] . ' ' . $event['feeSettings']['currency'];
            }

            // Event Time Options
            $allday = 0;

            // Single Event
            $repeat_status = 0;
            $repeat_type = '';
            $interval = null;
            $finish = $end_date;
            $year = null;
            $month = null;
            $day = null;
            $week = null;
            $weekday = null;
            $weekdays = null;

            $args = [
                'title' => $title,
                'content' => $description,
                'location_id' => $location_id,
                'organizer_id' => $main_organizer_id,
                'date' => [
                    'start' => [
                        'date' => $start_date,
                        'hour' => $start_hour,
                        'minutes' => $start_minutes,
                        'ampm' => $start_ampm,
                    ],
                    'end' => [
                        'date' => $end_date,
                        'hour' => $end_hour,
                        'minutes' => $end_minutes,
                        'ampm' => $end_ampm,
                    ],
                    'repeat' => [],
                    'allday' => $allday,
                    'comment' => '',
                    'hide_time' => 0,
                    'hide_end_time' => 0,
                ],
                'start' => $start_date,
                'start_time_hour' => $start_hour,
                'start_time_minutes' => $start_minutes,
                'start_time_ampm' => $start_ampm,
                'end' => $end_date,
                'end_time_hour' => $end_hour,
                'end_time_minutes' => $end_minutes,
                'end_time_ampm' => $end_ampm,
                'repeat_status' => $repeat_status,
                'repeat_type' => $repeat_type,
                'interval' => $interval,
                'finish' => $finish,
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'week' => $week,
                'weekday' => $weekday,
                'weekdays' => $weekdays,
                'meta' => [
                    'mec_source' => 'meetup',
                    'mec_meetup_id' => $mcal_id,
                    'mec_meetup_series_id' => '',
                    'mec_more_info' => $more_info,
                    'mec_more_info_title' => __('Check at Meetup', 'modern-events-calendar-lite'),
                    'mec_more_info_target' => '_self',
                    'mec_cost' => $fee,
                    'mec_meetup_url' => $group_name,
                    'mec_allday' => $allday,
                ],
            ];

            $post_id = $this->db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='$mcal_id' AND `meta_key`='mec_meetup_id'", 'loadResult');

            // Insert the event into MEC
            $post_id = $this->main->save_event($args, $post_id);
            $post_ids[] = $post_id;

            // Set location to the post
            if ($location_id) wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

            // Set organizer to the post
            if ($main_organizer_id) wp_set_object_terms($post_id, (int) $main_organizer_id, 'mec_organizer');

            // Set Additional Organizers
            if (count($additional_organizer_ids))
            {
                foreach ($additional_organizer_ids as $additional_organizer_id) wp_set_object_terms($post_id, (int) $additional_organizer_id, 'mec_organizer', true);
                update_post_meta($post_id, 'mec_additional_organizer_ids', $additional_organizer_ids);
            }

            // Featured Image
            if (!has_post_thumbnail($post_id) && isset($event['imageUrl']))
            {
                $photo = $this->main->get_web_page($event['imageUrl']);
                $file_name = md5($post_id) . '.' . $this->main->get_image_type_by_buffer($photo);

                $path = rtrim($wp_upload_dir['path'], DS . ' ') . DS . $file_name;
                $url = rtrim($wp_upload_dir['url'], '/ ') . '/' . $file_name;

                $file->write($path, $photo);
                $this->main->set_featured_image($url, $post_id);
            }
        }

        return ['success' => 1, 'data' => $post_ids];
    }

    public function export_all_events_do()
    {
        // Current User Doesn't Have Access
        $capability = (current_user_can('administrator') ? 'manage_options' : 'mec_import_export');
        if (!current_user_can($capability)) return;

        $format = isset($_GET['format']) ? sanitize_text_field($_GET['format']) : 'csv';

        // Increase PHP limits for export process
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        switch ($format)
        {
            case 'ical':

                $output = '';

                // Process in batches
                $batch_size = 50;
                $paged = 1;

                do
                {
                    $events = $this->main->get_events($batch_size, ['publish'], $paged, false);
                    if (empty($events)) break;

                    foreach($events as $event) $output .= $this->main->ical_single($event->ID, '', '', true);

                    $paged++;
                    unset($events);
                } while (true);

                $ical_calendar = $this->main->ical_calendar($output);

                header('Content-type: application/force-download; charset=utf-8');
                header('Content-Disposition: attachment; filename="mec-events-' . date('YmdTHi') . '.ics"');

                echo MEC_kses::full($ical_calendar);
                exit;

            case 'csv':

                $filename = 'mec-events-' . md5(time() . mt_rand(100, 999)) . '.csv';
                $events_feature = new MEC_feature_events();

                $rows = $events_feature->csvexcel(true);
                $this->main->generate_download_csv($rows, $filename);

                exit;

            case 'g-cal-csv':

                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=mec-events-' . md5(time() . mt_rand(100, 999)) . '.csv');

                $events_feature = new MEC_feature_events();
                $events_feature->gcalcsv(true);
                exit;

            case 'ms-excel':

                $filename = 'mec-events-' . md5(time() . mt_rand(100, 999)) . '.xlsx';
                $events_feature = new MEC_feature_events();

                $rows = $events_feature->csvexcel(true);
                $this->main->generate_download_excel($rows, $filename);

                exit;

            case 'xml':

                $output = [];

                // Process in batches
                $batch_size = 50;
                $paged = 1;

                do
                {
                    $events = $this->main->get_events($batch_size, ['publish'], $paged, false);
                    if (empty($events)) break;

                    foreach($events as $event) $output[] = $this->main->export_single($event->ID);

                    $paged++;
                    unset($events);
                } while (true);

                $xml_feed = $this->main->xml_convert(['events' => $output]);

                header('Content-type: application/force-download; charset=utf-8');
                header('Content-Disposition: attachment; filename="mec-events-' . date('YmdTHi') . '.xml"');

                echo $xml_feed;
                exit;

            case 'json':

                $output = [];

                // Process in batches
                $batch_size = 50;
                $paged = 1;

                do
                {
                    $events = $this->main->get_events($batch_size, ['publish'], $paged, false);
                    if (empty($events)) break;

                    foreach($events as $event) $output[] = $this->main->export_single($event->ID);

                    $paged++;
                    unset($events);
                } while (true);

                header('Content-type: application/force-download; charset=utf-8');
                header('Content-Disposition: attachment; filename="mec-events-' . date('YmdTHi') . '.json"');

                echo json_encode($output);
                exit;
        }
    }

    public function export_all_bookings_do()
    {
        // Current User Doesn't Have Access
        $capability = (current_user_can('administrator') ? 'manage_options' : 'mec_import_export');
        if (!current_user_can($capability)) return false;

        $format = isset($_GET['format']) ? sanitize_text_field($_GET['format']) : 'csv';
        switch ($format)
        {
            case 'ms-excel':

                $booking_ids = $this->bookings_csvexcel();

                $book = new MEC_feature_books();
                $rows = $book->csvexcel($booking_ids);

                $filename = 'bookings-' . md5(time() . mt_rand(100, 999)) . '.xlsx';
                $this->main->generate_download_excel($rows, $filename);

                exit;

            case 'csv':

                $booking_ids = $this->bookings_csvexcel();

                $book = new MEC_feature_books();
                $rows = $book->csvexcel($booking_ids);

                $filename = 'bookings-' . md5(time() . mt_rand(100, 999)) . '.csv';
                $this->main->generate_download_csv($rows, $filename);

                exit;
        }
    }

    public function bookings_csvexcel()
    {
        $bookings = get_posts(['post_type' => $this->main->get_book_post_type(), 'numberposts' => -1, 'post_status' => 'publish']);

        $booking_ids = [];
        foreach ($bookings as $booking) $booking_ids[] = $booking->ID;

        return $booking_ids;
    }

    public function g_calendar_export_authenticate()
    {
        $ix = ((isset($_POST['ix']) and is_array($_POST['ix'])) ? array_map('sanitize_text_field', $_POST['ix']) : []);

        $client_id = $ix['google_export_client_id'] ?? null;
        $client_secret = $ix['google_export_client_secret'] ?? null;
        $calendar_id = $ix['google_export_calendar_id'] ?? null;
        $auth_url = '';

        if (!trim($client_id) or !trim($client_secret) or !trim($calendar_id)) $this->main->response(['success' => 0, 'message' => __('All of Client ID, Client Secret, and Calendar ID are required!', 'modern-events-calendar-lite')]);

        // Save options
        $this->main->save_ix_options(['google_export_client_id' => $client_id, 'google_export_client_secret' => $client_secret, 'google_export_calendar_id' => $calendar_id]);

        try
        {
            $client = new Google_Client();
            $client->setApplicationName(get_bloginfo('name'));
            $client->setAccessType('offline');
            $client->setApprovalPrompt('force');
            $client->setScopes(['https://www.googleapis.com/auth/calendar']);
            $client->setClientId($client_id);
            $client->setClientSecret($client_secret);
            $client->setRedirectUri($this->main->add_qs_vars(['mec-ix-action' => 'google-calendar-export-get-token'], $this->main->URL('backend') . 'admin.php?page=MEC-ix&tab=MEC-g-calendar-export'));

            $auth_url = filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL);
        }
        catch (Exception $ex)
        {
            $this->main->response(['success' => 0, 'message' => $ex->getMessage()]);
        }

        $this->main->response(['success' => 1, 'message' => sprintf(esc_html__('All seems good! Please click %s to authenticate your app.', 'modern-events-calendar-lite'), '<a href="' . esc_url($auth_url) . '">' . esc_html__('here', 'modern-events-calendar-lite') . '</a>')]);
    }

    public function g_calendar_export_get_token()
    {
        $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';

        $ix = $this->main->get_ix_options();
        $client_id = $ix['google_export_client_id'] ?? null;
        $client_secret = $ix['google_export_client_secret'] ?? null;

        try
        {
            $client = new Google_Client();
            $client->setApplicationName(get_bloginfo('name'));
            $client->setAccessType('offline');
            $client->setApprovalPrompt('force');
            $client->setScopes(['https://www.googleapis.com/auth/calendar']);
            $client->setClientId($client_id);
            $client->setClientSecret($client_secret);
            $client->setRedirectUri($this->main->add_qs_vars(['mec-ix-action' => 'google-calendar-export-get-token'], $this->main->URL('backend') . 'admin.php?page=MEC-ix&tab=MEC-g-calendar-export'));

            $authentication = $client->authenticate($code);
            $token = $client->getAccessToken();

            $auth = json_decode($authentication, true);
            $refresh_token = $auth['refresh_token'];

            // Save options
            $this->main->save_ix_options(['google_export_token' => $token, 'google_export_refresh_token' => $refresh_token]);

            $url = $this->main->remove_qs_var('code', $this->main->remove_qs_var('mec-ix-action'));
            header('location: ' . $url);
            exit;
        }
        catch (Exception $ex)
        {
            echo esc_html($ex->getMessage());
            exit;
        }
    }

    public function g_calendar_export_do()
    {
        $mec_event_ids = ((isset($_POST['mec-events']) and is_array($_POST['mec-events'])) ? array_map('sanitize_text_field', $_POST['mec-events']) : []);
        $export_attendees = (isset($_POST['export_attendees']) ? sanitize_text_field($_POST['export_attendees']) : 0);

        $ix = $this->main->get_ix_options();

        $client_id = $ix['google_export_client_id'] ?? null;
        $client_secret = $ix['google_export_client_secret'] ?? null;
        $token = $ix['google_export_token'] ?? null;
        $refresh_token = $ix['google_export_refresh_token'] ?? null;
        $calendar_id = $ix['google_export_calendar_id'] ?? null;

        if (!trim($client_id) or !trim($client_secret) or !trim($calendar_id)) $this->main->response(['success' => 0, 'message' => __('Client App, Client Secret, and Calendar ID are required.', 'modern-events-calendar-lite')]);

        $client = new Google_Client();
        $client->setApplicationName('Modern Events Calendar');
        $client->setAccessType('offline');
        $client->setScopes(['https://www.googleapis.com/auth/calendar']);
        $client->setClientId($client_id);
        $client->setClientSecret($client_secret);
        $client->setRedirectUri($this->main->add_qs_vars(['mec-ix-action' => 'google-calendar-export-get-token'], $this->main->URL('backend') . 'admin.php?page=MEC-ix&tab=MEC-g-calendar-export'));
        $client->setAccessToken($token);
        $client->refreshToken($refresh_token);

        $service = new Google_Service_Calendar($client);

        // MEC Render Library
        $render = $this->getRender();

        $g_events_not_inserted = [];
        $g_events_inserted = [];
        $g_events_updated = [];

        foreach ($mec_event_ids as $mec_event_id)
        {
            $data = $render->data($mec_event_id);

            $dates = $render->dates($mec_event_id, $data);
            $date = $dates[0] ?? [];

            // No Date
            if (!count($date)) continue;

            // Timezone Options
            $timezone = $this->main->get_timezone($mec_event_id);

            $location = $data->locations[$data->meta['mec_location_id']] ?? [];
            $organizer = $data->organizers[$data->meta['mec_organizer_id']] ?? [];

            $recurrence = $this->main->get_ical_rrules($data);

            $start = [
                'dateTime' => date('Y-m-d\TH:i:s', $date['start']['timestamp']),
                'timeZone' => $timezone,
            ];

            $end = [
                'dateTime' => date('Y-m-d\TH:i:s', $date['end']['timestamp']),
                'timeZone' => $timezone,
            ];

            $allday = $data->meta['mec_allday'] ?? 0;
            if ($allday)
            {
                $start['dateTime'] = date('Y-m-d\T00:00:00', $date['start']['timestamp']);
                $end['dateTime'] = date('Y-m-d\T00:00:00', strtotime('+1 Day', strtotime($end['dateTime'])));
            }

            // Event Data
            $event_data = [
                'summary' => $data->title,
                'location' => ($location['address'] ?? ($location['name'] ?? '')),
                'description' => strip_tags(strip_shortcodes($data->content)),
                'start' => $start,
                'end' => $end,
                'recurrence' => $recurrence,
                'attendees' => [],
                'reminders' => [],
            ];

            $event = new Google_Service_Calendar_Event($event_data);
            $iCalUID = 'mec-ical-' . $data->ID;

            $mec_iCalUID = get_post_meta($data->ID, 'mec_gcal_ical_uid', true);
            $mec_calendar_id = get_post_meta($data->ID, 'mec_gcal_calendar_id', true);

            /**
             * Event is imported from same google calendar,
             * and now it's exporting to its calendar again,
             * so we're trying to update existing one by setting event iCal ID
             */
            if ($mec_calendar_id == $calendar_id and trim($mec_iCalUID)) $iCalUID = $mec_iCalUID;

            $event->setICalUID($iCalUID);

            // Set the organizer if exists
            if (isset($organizer['name']))
            {
                $g_organizer = new Google_Service_Calendar_EventOrganizer();
                $g_organizer->setDisplayName($organizer['name']);
                $g_organizer->setEmail($organizer['email']);

                $event->setOrganizer($g_organizer);
            }

            // Set the attendees
            if ($export_attendees)
            {
                $attendees = [];
                foreach ($this->main->get_event_attendees($data->ID) as $att)
                {
                    $attendee = new Google_Service_Calendar_EventAttendee();
                    $attendee->setDisplayName($att['name']);
                    $attendee->setEmail($att['email']);
                    $attendee->setResponseStatus('accepted');

                    $attendees[] = $attendee;
                }

                $event->setAttendees($attendees);
            }

            try
            {
                $g_event = $service->events->insert($calendar_id, $event);

                // Set Google Calendar ID to MEC database for updating it in the future instead of adding it twice
                update_post_meta($data->ID, 'mec_gcal_ical_uid', $g_event->getICalUID());
                update_post_meta($data->ID, 'mec_gcal_calendar_id', $calendar_id);
                update_post_meta($data->ID, 'mec_gcal_id', $g_event->getId());

                $g_events_inserted[] = ['title' => $data->title, 'message' => $g_event->htmlLink];
            }
            catch (Exception $ex)
            {
                // Event already existed
                if ($ex->getCode() == 409)
                {
                    try
                    {
                        $g_event_id = get_post_meta($data->ID, 'mec_gcal_id', true);
                        $g_event = $service->events->get($calendar_id, $g_event_id);

                        // Update Event Data
                        $g_event->setSummary($event_data['summary']);
                        $g_event->setLocation($event_data['location']);
                        $g_event->setDescription($event_data['description']);
                        $g_event->setRecurrence($event_data['recurrence']);

                        $start = new Google_Service_Calendar_EventDateTime();
                        $start->setDateTime($event_data['start']['dateTime']);
                        $start->setTimeZone($event_data['start']['timeZone']);
                        $g_event->setStart($start);

                        $end = new Google_Service_Calendar_EventDateTime();
                        $end->setDateTime($event_data['end']['dateTime']);
                        $end->setTimeZone($event_data['end']['timeZone']);
                        $g_event->setEnd($end);

                        $g_updated_event = $service->events->update($calendar_id, $g_event_id, $g_event);
                        $g_events_updated[] = ['title' => $data->title, 'message' => $g_updated_event->htmlLink];
                    }
                    catch (Exception $ex)
                    {
                        $g_events_not_inserted[] = ['title' => $data->title, 'message' => $ex->getMessage()];
                    }
                }
                else $g_events_not_inserted[] = ['title' => $data->title, 'message' => $ex->getMessage()];
            }
        }

        $results = '<ul>';
        foreach ($g_events_not_inserted as $g_event_not_inserted) $results .= '<li><strong>' . MEC_kses::element($g_event_not_inserted['title']) . '</strong>: ' . MEC_kses::element($g_event_not_inserted['message']) . '</li>';
        $results .= '<ul>';

        $message = (count($g_events_inserted) ? sprintf(esc_html__('%s events added to Google Calendar successfully.', 'modern-events-calendar-lite'), '<strong>' . count($g_events_inserted) . '</strong>') : '');
        $message .= (count($g_events_updated) ? ' ' . sprintf(esc_html__('%s Updated previously added events.', 'modern-events-calendar-lite'), '<strong>' . count($g_events_updated) . '</strong>') : '');
        $message .= (count($g_events_not_inserted) ? ' ' . sprintf(esc_html__('%s events failed to add for following reasons: %s', 'modern-events-calendar-lite'), '<strong>' . count($g_events_not_inserted) . '</strong>', $results) : '');

        $this->main->response(['success' => ((count($g_events_inserted) or count($g_events_updated)) ? 1 : 0), 'message' => trim($message)]);
    }

    /**
     * Show content of Facebook Import tab
     * @return void
     * @author Webnus <info@webnus.net>
     */
    public function ix_f_calendar_import()
    {
        // Current Action
        $this->action = isset($_POST['mec-ix-action']) ? sanitize_text_field($_POST['mec-ix-action']) : '';
        $this->ix = ((isset($_POST['ix']) and is_array($_POST['ix'])) ? array_map('sanitize_text_field', $_POST['ix']) : []);

        $this->response = [];
        if ($this->action == 'facebook-calendar-import-start') $this->response = $this->f_calendar_import_start();
        else if ($this->action == 'facebook-calendar-import-do') $this->response = $this->f_calendar_import_do();

        $path = MEC::import('app.features.ix.import_f_calendar', true, true);

        ob_start();
        include $path;
        echo MEC_kses::full(ob_get_clean());
    }

    public function f_calendar_import_start()
    {
        $fb_page_link = $this->ix['facebook_import_page_link'] ?? null;
        $this->fb_access_token = $this->ix['facebook_app_token'] ?? null;

        if (!trim($fb_page_link)) return ['success' => 0, 'message' => __("Please insert your Facebook page's link.", 'modern-events-calendar-lite')];

        // Save options
        $this->main->save_ix_options(['facebook_import_page_link' => $fb_page_link]);
        $this->main->save_ix_options(['facebook_app_token' => $this->fb_access_token]);

        $fb_page = $this->f_calendar_import_get_page($fb_page_link);

        $fb_page_id = $fb_page['id'] ?? 0;
        if (!$fb_page_id)
        {
            $message = esc_html__("We were not able to recognize your Facebook page. Please check again and provide a valid link.", 'modern-events-calendar-lite');
            if (isset($fb_page['error']) and isset($fb_page['error']['message'])) $message = $fb_page['error']['message'];

            return ['success' => 0, 'message' => $message];
        }

        $events = [];
        $next_page = 'https://graph.facebook.com/v18.0/' . $fb_page_id . '/events/?access_token=' . $this->fb_access_token;

        do
        {
            $events_result = $this->main->get_web_page($next_page);
            $fb_events = json_decode($events_result, true);

            // Exit the loop if no event found
            if (!isset($fb_events['data'])) break;

            foreach ($fb_events['data'] as $fb_event)
            {
                $events[] = ['id' => $fb_event['id'], 'name' => $fb_event['name']];
            }

            $next_page = $fb_events['paging']['next'] ?? null;
        } while ($next_page);

        if (!count($events)) return ['success' => 0, 'message' => __("No events found!", 'modern-events-calendar-lite')];
        else return ['success' => 1, 'message' => '', 'data' => ['events' => $events, 'count' => count($events), 'name' => $fb_page['name']]];
    }

    public function f_calendar_import_do()
    {
        $f_events = ((isset($_POST['f-events']) and is_array($_POST['f-events'])) ? array_map('sanitize_text_field', $_POST['f-events']) : []);
        if (!count($f_events)) return ['success' => 0, 'message' => __('Please select events to import!', 'modern-events-calendar-lite')];

        $fb_page_link = $this->ix['facebook_import_page_link'] ?? null;
        $this->fb_access_token = $this->ix['facebook_app_token'] ?? null;
        if (!trim($fb_page_link)) return ['success' => 0, 'message' => __("Please insert your facebook page's link.", 'modern-events-calendar-lite')];

        $fb_page = $this->f_calendar_import_get_page($fb_page_link);

        $fb_page_id = $fb_page['id'] ?? 0;
        if (!$fb_page_id) return ['success' => 0, 'message' => __("We were not able to recognize your Facebook page. Please check again and provide a valid link.", 'modern-events-calendar-lite')];

        // Timezone
        $timezone = $this->main->get_timezone();

        // MEC File
        $file = $this->getFile();
        $wp_upload_dir = wp_upload_dir();

        $post_ids = [];
        foreach ($f_events as $f_event_id)
        {
            $events_result = $this->main->get_web_page('https://graph.facebook.com/v18.0/' . $f_event_id . '?fields=name,place,description,start_time,end_time,cover,event_times&access_token=' . $this->fb_access_token);
            $event = json_decode($events_result, true);

            // An error Occurred
            if (isset($event['error']) and is_array($event['error']) and count($event['error'])) continue;

            // Event organizer
            $organizer_id = 1;

            // Event location
            $location = $event['place'] ?? [];
            $location_id = 1;

            // Import Event Locations into MEC locations
            if (isset($this->ix['import_locations']) and $this->ix['import_locations'] and count($location))
            {
                $location_name = $location['name'];
                $location_address = trim($location_name . ' ' . ($location['location']['city'] ?? '') . ' ' . ($location['location']['state'] ?? '') . ' ' . ($location['location']['country'] ?? '') . ' ' . ($location['location']['zip'] ?? ''), '');
                $location_id = $this->main->save_location([
                    'name' => trim($location_name),
                    'address' => $location_address,
                    'latitude' => !empty($location['location']['latitude']) ? $location['location']['latitude'] : '',
                    'longitude' => !empty($location['location']['longitude']) ? $location['location']['longitude'] : '',
                ]);
            }

            // Event Title and Content
            $title = $event['name'];
            $description = $event['description'] ?? '';

            // Event Times (Custom Events)
            $event_times = ((isset($event['event_times']) and is_array($event['event_times'])) ? $event['event_times'] : []);

            if (count($event_times))
            {
                $days = '';
                $main_datetime = [];

                $i = 1;
                foreach ($event_times as $event_time)
                {
                    if ($i == count($event_times)) $main_datetime = $event_time;
                    else
                    {
                        $ds = new DateTime($event_time['start_time']);
                        $ds->setTimezone(new DateTimeZone($timezone));

                        $de = new DateTime($event_time['end_time']);
                        $de->setTimezone(new DateTimeZone($timezone));

                        $days .= $ds->format('Y-m-d') . ':' . $de->format('Y-m-d') . ':' . $ds->format('h-i-A') . ':' . $de->format('h-i-A') . ',';
                    }

                    $i++;
                }

                $date_start = new DateTime($main_datetime['start_time']);
                $date_start->setTimezone(new DateTimeZone($timezone));

                $start_date = $date_start->format('Y-m-d');
                $start_hour = $date_start->format('g');
                $start_minutes = $date_start->format('i');
                $start_ampm = $date_start->format('A');

                $date_end = new DateTime($main_datetime['end_time']);
                $date_end->setTimezone(new DateTimeZone($timezone));

                $end_date = $date_end->format('Y-m-d');
                $end_hour = $date_end->format('g');
                $end_minutes = $date_end->format('i');
                $end_ampm = $date_end->format('A');

                $repeat_status = 1;
                $repeat_type = 'custom_days';
                $days = trim($days, ', ');
            }
            else
            {
                $date_start = new DateTime($event['start_time']);
                $date_start->setTimezone(new DateTimeZone($timezone));

                $start_date = $date_start->format('Y-m-d');
                $start_hour = $date_start->format('g');
                $start_minutes = $date_start->format('i');
                $start_ampm = $date_start->format('A');

                $end_timestamp = isset($event['end_time']) ? strtotime($event['end_time']) : 0;
                if ($end_timestamp)
                {
                    $date_end = new DateTime($event['end_time']);
                    $date_end->setTimezone(new DateTimeZone($timezone));
                }

                $end_date = $end_timestamp ? $date_end->format('Y-m-d') : $start_date;
                $end_hour = $end_timestamp ? $date_end->format('g') : 8;
                $end_minutes = $end_timestamp ? $date_end->format('i') : '00';
                $end_ampm = $end_timestamp ? $date_end->format('A') : 'PM';

                $repeat_status = 0;
                $repeat_type = '';
                $days = null;
            }

            // Event Time Options
            $allday = 0;

            // Import Facebook Link as Event Link
            $read_more = '';
            if (isset($this->ix['import_link_event']) and $this->ix['import_link_event']) $read_more = 'https://www.facebook.com/events/' . $f_event_id . '/';

            // Import Facebook Link as More Info
            $more_info = '';
            if (isset($this->ix['import_link_more_info']) and $this->ix['import_link_more_info']) $more_info = 'https://www.facebook.com/events/' . $f_event_id . '/';

            $args = [
                'title' => $title,
                'content' => $description,
                'location_id' => $location_id,
                'organizer_id' => $organizer_id,
                'date' => [
                    'start' => [
                        'date' => $start_date,
                        'hour' => $start_hour,
                        'minutes' => $start_minutes,
                        'ampm' => $start_ampm,
                    ],
                    'end' => [
                        'date' => $end_date,
                        'hour' => $end_hour,
                        'minutes' => $end_minutes,
                        'ampm' => $end_ampm,
                    ],
                    'repeat' => [],
                    'allday' => $allday,
                    'comment' => '',
                    'hide_time' => 0,
                    'hide_end_time' => 0,
                ],
                'start' => $start_date,
                'start_time_hour' => $start_hour,
                'start_time_minutes' => $start_minutes,
                'start_time_ampm' => $start_ampm,
                'end' => $end_date,
                'end_time_hour' => $end_hour,
                'end_time_minutes' => $end_minutes,
                'end_time_ampm' => $end_ampm,
                'repeat_status' => $repeat_status,
                'repeat_type' => $repeat_type,
                'interval' => null,
                'finish' => $end_date,
                'year' => null,
                'month' => null,
                'day' => null,
                'week' => null,
                'weekday' => null,
                'weekdays' => null,
                'days' => $days,
                'meta' => [
                    'mec_source' => 'facebook-calendar',
                    'mec_facebook_page_id' => $fb_page_id,
                    'mec_facebook_event_id' => $f_event_id,
                    'mec_allday' => $allday,
                    'mec_read_more' => $read_more,
                    'mec_more_info' => $more_info,
                    'mec_in_days' => $days,
                ],
            ];

            $post_id = $this->db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`='$f_event_id' AND `meta_key`='mec_facebook_event_id'", 'loadResult');

            // Insert the event into MEC
            $post_id = $this->main->save_event($args, $post_id);
            $post_ids[] = $post_id;

            // Set location to the post
            if ($location_id) wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

            if (!has_post_thumbnail($post_id) and isset($event['cover']) and is_array($event['cover']) and count($event['cover']))
            {
                $photo = $this->main->get_web_page($event['cover']['source']);
                $file_name = md5($post_id) . '.' . $this->main->get_image_type_by_buffer($photo);

                $path = rtrim($wp_upload_dir['path'], DS . ' ') . DS . $file_name;
                $url = rtrim($wp_upload_dir['url'], '/ ') . '/' . $file_name;

                $file->write($path, $photo);
                $this->main->set_featured_image($url, $post_id);
            }
        }

        return ['success' => 1, 'data' => $post_ids];
    }

    public function f_calendar_import_get_page($link)
    {
        $this->fb_access_token = $this->ix['facebook_app_token'] ?? null;
        $fb_page_result = $this->main->get_web_page('https://graph.facebook.com/v18.0/?access_token=' . $this->fb_access_token . '&id=' . $link);

        return json_decode($fb_page_result, true);
    }
}

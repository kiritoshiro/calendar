<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC fes (Frontend Event Submission) class.
 * @author Webnus <info@webnus.net>
 */
class MEC_feature_fes extends MEC_base
{
    public $factory;
    public $main;
    public $db;
    public $settings;
    public $PT;
    public $render;
    public $relative_link = false;

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

        // MEC Settings
        $this->settings = $this->main->get_settings();

        // Event Post Type
        $this->PT = $this->main->get_main_post_type();
    }

    /**
     * Initialize colors feature
     * @author Webnus <info@webnus.net>
     */
    public function init()
    {
        // Frontend Event Submission Form
        $this->factory->shortcode('MEC_fes_form', [$this, 'vform']);

        // Event Single Page
        $this->factory->shortcode('MEC_fes_list', [$this, 'vlist']);

        // Process the event form
        $this->factory->action('wp_ajax_mec_fes_form', [$this, 'fes_form']);
        $this->factory->action('wp_ajax_nopriv_mec_fes_form', [$this, 'fes_form']);

        // Upload featured image
        $this->factory->action('wp_ajax_mec_fes_upload_featured_image', [$this, 'fes_upload']);
        $this->factory->action('wp_ajax_nopriv_mec_fes_upload_featured_image', [$this, 'fes_upload']);

        // Export the event
        $this->factory->action('wp_ajax_mec_fes_csv_export', [$this, 'mec_fes_csv_export']);

        // Remove the event
        $this->factory->action('wp_ajax_mec_fes_remove', [$this, 'fes_remove']);

        // Event Published
        $this->factory->action('transition_post_status', [$this, 'status_changed'], 10, 3);

        $this->factory->filter('ajax_query_attachments_args', [$this, 'current_user_attachments']);
    }

    /**
     * @return bool
     */
    public function current_user_can_submit_event()
    {
        $capability = true;
        $user = wp_get_current_user();

        if ($user->ID and isset($this->settings['fes_access_roles']) and is_array($this->settings['fes_access_roles']))
        {
            $capability = false;
            if (isset($user->roles) and is_array($user->roles))
            {
                foreach ($user->roles as $user_role)
                {
                    if (in_array($user_role, $this->settings['fes_access_roles'])) $capability = true;
                    if ($capability) break;
                }
            }
        }

        if (is_plugin_active('buddyboss-platform/bp-loader.php') &&
            is_plugin_active('mec-buddyboss/mec-buddyboss.php'))
        {

            return true;
        }

        return apply_filters('mec_fes_form_current_user_can_submit_event', $capability, $this->settings);
    }

    /**
     * @return bool
     */
    public function validate_fes_term_creation_request()
    {
        $nonce = isset($_REQUEST['fes_nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['fes_nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'mec_fes_nonce')) return false;

        return $this->current_user_can_submit_event();
    }

    /**
     * @param int $post_id
     * @return bool
     */
    public function current_user_can_upsert_event($post_id)
    {
        if ($post_id == -1) return true;

        $original_post_id = $this->main->get_original_event($post_id);
        if (current_user_can('edit_post', $original_post_id)) return true;

        $post = get_post($post_id);
        if (isset($post->post_author) && (int) $post->post_author === get_current_user_id()) return true;

        return false;
    }

    /**
     * Generate frontend event submission form view
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vform($atts = [])
    {
        // Force to array
        if (!is_array($atts)) $atts = [];

        if (isset($_GET['vlist']) and sanitize_text_field($_GET['vlist']) == 1)
        {
            return $this->vlist($atts);
        }

        // Force to Relative Link
        $this->relative_link = (isset($atts['relative-link']) and $atts['relative-link']);

        // Show login/register message if user is not logged in and guest submission is not enabled.
        if (!is_user_logged_in() and (!isset($this->settings['fes_guest_status']) or (isset($this->settings['fes_guest_status']) and $this->settings['fes_guest_status'] == '0')))
        {
            // Show message
            $message = sprintf(esc_html__('Please %s/%s in order to submit new events.', 'modern-events-calendar-lite'), '<a href="' . wp_login_url($this->main->get_full_url()) . '">' . esc_html__('Login', 'modern-events-calendar-lite') . '</a>', '<a href="' . wp_registration_url() . '">' . esc_html__('Register', 'modern-events-calendar-lite') . '</a>');

            ob_start();
            include MEC::import('app.features.fes.message', true, true);
            return ob_get_clean();
        }

        $can_user_submit_event = $this->current_user_can_submit_event();
        if (true !== $can_user_submit_event)
        {
            return '<div class="mec-error">' . esc_html__('You do not have access to create an event', 'modern-events-calendar-lite') . '</div>';
        }

        $post_id = isset($_GET['post_id']) ? sanitize_text_field($_GET['post_id']) : -1;

        // Selected post is not an event
        if ($post_id > 0 and get_post_type($post_id) != $this->PT)
        {
            // Show message
            $message = esc_html__("Sorry! Selected post is not an event.", 'modern-events-calendar-lite');

            ob_start();
            include MEC::import('app.features.fes.message', true, true);
            return ob_get_clean();
        }

        // Show a warning to current user if modification of post is not possible for him/her
        if (!$this->current_user_can_upsert_event($post_id))
        {
            // Show message
            $message = esc_html__("Sorry! You don't have access to modify this event.", 'modern-events-calendar-lite');

            ob_start();
            include MEC::import('app.features.fes.message', true, true);
            return ob_get_clean();
        }

        $post = get_post($post_id);

        if ($post_id == -1)
        {
            $post = new stdClass();
            $post->ID = -1;
        }

        $path = MEC::import('app.features.fes.form', true, true);
        $path = apply_filters('mec_fes_form_template_path', $path);

        ob_start();
        include $path;
        return ob_get_clean();
    }

    /**
     * Generate frontend event submission list view
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vlist($atts = [])
    {
        // Force to array
        if (!is_array($atts)) $atts = [];

        $post_id = isset($_GET['post_id']) ? sanitize_text_field($_GET['post_id']) : null;

        // Force to Relative Link
        $this->relative_link = (isset($atts['relative-link']) and $atts['relative-link']);

        // Show a warning to current user if modification of post is not possible for him/her
        if ($post_id > 0 and !$this->current_user_can_upsert_event($post_id))
        {
            // Show message
            $message = esc_html__("Sorry! You don't have access to modify this event.", 'modern-events-calendar-lite');

            ob_start();
            include MEC::import('app.features.fes.message', true, true);
            return ob_get_clean();
        }
        else if ($post_id == -1 or ($post_id > 0 and $this->current_user_can_upsert_event($post_id)))
        {
            return $this->vform($atts);
        }

        // Show login/register message if user is not logged in
        if (!is_user_logged_in())
        {
            // Show message
            $message = sprintf(esc_html__('Please %s/%s in order to manage events.', 'modern-events-calendar-lite'), '<a href="' . wp_login_url($this->main->get_full_url()) . '">' . esc_html__('Login', 'modern-events-calendar-lite') . '</a>', '<a href="' . wp_registration_url() . '">' . esc_html__('Register', 'modern-events-calendar-lite') . '</a>');

            ob_start();
            include MEC::import('app.features.fes.message', true, true);
            return ob_get_clean();
        }

        $can_user_submit_event = $this->current_user_can_submit_event();
        if (true !== $can_user_submit_event)
        {
            return '<div class="mec-error">' . esc_html__('You do not have access to view the list of events', 'modern-events-calendar-lite') . '</div>';
        }

        $path = MEC::import('app.features.fes.list', true, true);

        ob_start();
        include $path;
        return ob_get_clean();
    }

    public function fes_remove()
    {
        // Check if our nonce is set.
        if (!isset($_POST['_wpnonce'])) $this->main->response(['success' => 0, 'code' => 'NONCE_MISSING']);

        // Verify that the nonce is valid.
        if (!wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), 'mec_fes_remove')) $this->main->response(['success' => 0, 'code' => 'NONCE_IS_INVALID']);

        $post_id = isset($_POST['post_id']) ? sanitize_text_field($_POST['post_id']) : 0;

        // Verify current user can remove the event
        if (!current_user_can('delete_post', $post_id)) $this->main->response(['success' => 0, 'code' => 'USER_CANNOT_REMOVE_EVENT']);

        // Trash the event
        wp_delete_post($post_id);

        $this->main->response(['success' => 1, 'message' => __('Event removed!', 'modern-events-calendar-lite')]);
    }

    public function mec_fes_csv_export()
    {
        if ((!isset($_REQUEST['mec_event_id'])) || (!isset($_REQUEST['fes_nonce'])) || (!wp_verify_nonce(sanitize_text_field($_REQUEST['fes_nonce']), 'mec_fes_nonce')))
        {
            die(json_encode(['ex' => "error"]));
        }

        $event_id = intval(sanitize_text_field($_REQUEST['mec_event_id']));
        $timestamp = isset($_REQUEST['timestamp']) ? sanitize_text_field($_REQUEST['timestamp']) : 0;
        $booking_ids = '';
        $type = isset($_REQUEST['type']) ? sanitize_text_field($_REQUEST['type']) : 'csv';

        if ($timestamp)
        {
            $bookings = $this->main->get_bookings($event_id, $timestamp);
            foreach ($bookings as $booking)
            {
                $booking_ids .= $booking->ID . ',';
            }
        }

        $post_ids = trim($booking_ids) ? explode(',', trim($booking_ids, ', ')) : [];

        if (!count($post_ids) && !$timestamp)
        {
            $books = $this->db->select("SELECT `post_id` FROM `#__postmeta` WHERE `meta_key`='mec_event_id' AND `meta_value`={$event_id}", 'loadAssocList');
            foreach ($books as $book)
            {
                if (isset($book['post_id']))
                {
                    $post_ids[] = $book['post_id'];
                }
            }
        }

        // Gather all reg_fields and fixed_fields labels for dynamic columns
        $all_reg_labels = [];
        $all_fixed_labels = [];
        foreach ($post_ids as $post_id)
        {
            $event_id = get_post_meta($post_id, 'mec_event_id', true);
            $reg_fields = $this->main->get_reg_fields($event_id);
            if (is_array($reg_fields) && isset($reg_fields[':i:'])) unset($reg_fields[':i:']);
            foreach ($reg_fields as $field_key => $field)
            {
                if (!empty($field['label']))
                {
                    // Skip Name and Email fields to avoid duplication with main columns
                    $label = $field['label'];
                    $label_lower = strtolower(trim($label));
                    $field_key_lower = strtolower(trim($field_key));

                    // Check for various Name and Email variations
                    $skip_field = false;
                    $name_variations = ['name', 'full name', 'fullname', 'first name', 'firstname', 'last name', 'lastname'];
                    $email_variations = ['email', 'e-mail', 'email address', 'emailaddress'];

                    foreach ($name_variations as $variation)
                    {
                        if ($label_lower === $variation || $field_key_lower === $variation ||
                            strpos($label_lower, $variation) !== false || strpos($field_key_lower, $variation) !== false)
                        {
                            $skip_field = true;
                            break;
                        }
                    }

                    if (!$skip_field)
                    {
                        foreach ($email_variations as $variation)
                        {
                            if ($label_lower === $variation || $field_key_lower === $variation ||
                                strpos($label_lower, $variation) !== false || strpos($field_key_lower, $variation) !== false)
                            {
                                $skip_field = true;
                                break;
                            }
                        }
                    }

                    // Also check against translated labels
                    if (!$skip_field && ($label === esc_html__('Name', 'modern-events-calendar-lite') || $label === esc_html__('Email', 'modern-events-calendar-lite') ||
                            $label === __('Name', 'modern-events-calendar-lite') || $label === __('Email', 'modern-events-calendar-lite')))
                    {
                        $skip_field = true;
                    }

                    if (!$skip_field)
                    {
                        $all_reg_labels[$field_key] = $label;
                    }
                }
            }
            $fixed_fields_raw = $this->main->get_bfixed_fields($event_id);
            if (is_array($fixed_fields_raw))
            {
                foreach ($fixed_fields_raw as $field_id => $field_data)
                {
                    if (!empty($field_data['label']) && is_numeric($field_id))
                    {
                        // Skip Name and Email fields to avoid duplication with main columns
                        $label = $field_data['label'];
                        $label_lower = strtolower(trim($label));

                        // Check for various Name and Email variations
                        $skip_field = false;
                        $name_variations = ['name', 'full name', 'fullname', 'first name', 'firstname', 'last name', 'lastname'];
                        $email_variations = ['email', 'e-mail', 'email address', 'emailaddress'];

                        foreach ($name_variations as $variation)
                        {
                            if ($label_lower === $variation || strpos($label_lower, $variation) !== false)
                            {
                                $skip_field = true;
                                break;
                            }
                        }

                        if (!$skip_field)
                        {
                            foreach ($email_variations as $variation)
                            {
                                if ($label_lower === $variation || strpos($label_lower, $variation) !== false)
                                {
                                    $skip_field = true;
                                    break;
                                }
                            }
                        }

                        // Also check against translated labels
                        if (!$skip_field && ($label === esc_html__('Name', 'modern-events-calendar-lite') || $label === esc_html__('Email', 'modern-events-calendar-lite') ||
                                $label === __('Name', 'modern-events-calendar-lite') || $label === __('Email', 'modern-events-calendar-lite')))
                        {
                            $skip_field = true;
                        }

                        if (!$skip_field)
                        {
                            $all_fixed_labels[$field_id] = $label;
                        }
                    }
                }
            }
        }

        $columns = [
            __('ID', 'modern-events-calendar-lite'),
            esc_html__('Event', 'modern-events-calendar-lite'),
            esc_html__('Date', 'modern-events-calendar-lite'),
            esc_html__('Order Time', 'modern-events-calendar-lite'),
            $this->main->m('ticket', esc_html__('Ticket', 'modern-events-calendar-lite')),
            esc_html__('Transaction ID', 'modern-events-calendar-lite'),
            esc_html__('Total Price', 'modern-events-calendar-lite'),
            esc_html__('Single Ticket Price', 'modern-events-calendar-lite'),
            esc_html__('Gateway', 'modern-events-calendar-lite'),
            esc_html__('Name', 'modern-events-calendar-lite'),
            esc_html__('Email', 'modern-events-calendar-lite'),
            esc_html__('Ticket Variation', 'modern-events-calendar-lite'),
            esc_html__('Confirmation', 'modern-events-calendar-lite'),
            esc_html__('Verification', 'modern-events-calendar-lite'),
        ];
        foreach ($all_reg_labels as $label)
        {
            $columns[] = $label;
        }
        foreach ($all_fixed_labels as $label)
        {
            $columns[] = $label;
        }

        $uniqueBookings = [];
        $book_object = new \MEC_book();

        foreach ($post_ids as $post_id)
        {
            $post_id = (int) $post_id;

            $event_id = get_post_meta($post_id, 'mec_event_id', true);
            $transaction_id = get_post_meta($post_id, 'mec_transaction_id', true);
            $order_time = get_post_meta($post_id, 'mec_booking_time', true);
            $tickets = get_post_meta($event_id, 'mec_tickets', true);
            $attendees = get_post_meta($post_id, 'mec_attendees', true);
            if (!is_array($attendees) || !count($attendees))
            {
                $attendees = [get_post_meta($post_id, 'mec_attendee', true)];
            }

            $price = get_post_meta($post_id, 'mec_price', true);
            $gateway_label = get_post_meta($post_id, 'mec_gateway_label', true);
            $transaction = $book_object->get_transaction($transaction_id);

            $reg_fields = $this->main->get_reg_fields($event_id);
            if (is_array($reg_fields) && isset($reg_fields[':i:'])) unset($reg_fields[':i:']);
            $fixed_fields_raw = $this->main->get_bfixed_fields($event_id);
            if (!is_array($fixed_fields_raw)) $fixed_fields_raw = [];

            foreach ($attendees as $key => $attendee)
            {
                if ($key === 'attachments') continue;
                if (isset($attendee[0]['MEC_TYPE_OF_DATA'])) continue;

                $ticket_id = $attendee['id'] ?? get_post_meta($post_id, 'mec_ticket_id', true);
                $ticket_seats = (isset($tickets[$ticket_id]['seats']) && is_numeric($tickets[$ticket_id]['seats']) && (int) $tickets[$ticket_id]['seats'] > 0) ? (int) $tickets[$ticket_id]['seats'] : 1;
                $transactionKey = $transaction_id . '-' . $ticket_id . '-' . ($attendee['email'] ?? '');

                // Ticket Variation output
                $ticket_variations_output = '';
                if (isset($attendee['variations']) && is_array($attendee['variations']) && count($attendee['variations']))
                {
                    $ticket_variations = $this->main->ticket_variations($event_id, $ticket_id);
                    foreach ($attendee['variations'] as $variation_id => $variation_count)
                    {
                        if ((int) $variation_count > 0)
                        {
                            $ticket_variations_output .= (isset($ticket_variations[$variation_id]) ? $ticket_variations[$variation_id]['title'] : 'N/A') . ': (' . $variation_count . '), ';
                        }
                    }
                }
                $ticket_variations_output = html_entity_decode(trim($ticket_variations_output, ', '), ENT_QUOTES | ENT_HTML5);

                // Reg Fields output
                $per_attendee_fields = isset($attendee['reg']) ? $attendee['reg'] : [];
                $reg_field_values = [];
                foreach ($all_reg_labels as $field_key => $label)
                {
                    $value = isset($per_attendee_fields[$field_key]) ? $per_attendee_fields[$field_key] : '';
                    $reg_field_values[] = is_array($value) ? implode(', ', $value) : $value;
                }

                // Fixed Fields output
                $fixed_field_values = [];
                if (!empty($all_fixed_labels) && isset($transaction['fields']) && is_array($transaction['fields']))
                {
                    foreach ($all_fixed_labels as $field_id => $label)
                    {
                        $value = isset($transaction['fields'][$field_id]) ? $transaction['fields'][$field_id] : '';
                        $fixed_field_values[] = is_array($value) ? implode(', ', $value) : $value;
                    }
                }
                else
                {
                    foreach ($all_fixed_labels as $field_id => $label)
                    {
                        $fixed_field_values[] = '';
                    }
                }

                // Single Ticket Price
                $single_ticket_price = $book_object->get_ticket_total_price($transaction, $attendee, $post_id);

                $confirmed = get_post_meta($post_id, 'mec_confirmed', true) == '1' ? esc_html__('Confirmed', 'modern-events-calendar-lite') : esc_html__('Pending', 'modern-events-calendar-lite');
                $verified = get_post_meta($post_id, 'mec_verified', true) == '1' ? esc_html__('Verified', 'modern-events-calendar-lite') : esc_html__('Waiting', 'modern-events-calendar-lite');

                if (!isset($uniqueBookings[$transactionKey]))
                {
                    $uniqueBookings[$transactionKey] = [
                        'count' => $ticket_seats,
                        'booking' => [
                            $post_id,
                            html_entity_decode(get_the_title($event_id), ENT_QUOTES | ENT_HTML5),
                            get_the_date('', $post_id),
                            $order_time,
                            ($tickets[$ticket_id]['name'] ?? esc_html__('Unknown', 'modern-events-calendar-lite')),
                            $transaction_id,
                            $this->main->render_price(($price ? $price : 0), $post_id),
                            $this->main->render_price($single_ticket_price, $post_id),
                            html_entity_decode($gateway_label, ENT_QUOTES | ENT_HTML5),
                            ($attendee['name'] ?? ''),
                            ($attendee['email'] ?? ''),
                            $ticket_variations_output,
                            $confirmed,
                            $verified,
                        ],
                        'reg_fields' => $reg_field_values,
                        'fixed_fields' => $fixed_field_values,
                    ];
                }
                else
                {
                    $uniqueBookings[$transactionKey]['count'] += $ticket_seats;
                }
            }
        }

        switch ($type)
        {
            case 'ms-excel':
                $filename = 'attendees-' . md5(time() . mt_rand(100, 999)) . '.xlsx';
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $filename . '"');

                $temp_file = tempnam(sys_get_temp_dir(), 'xlsx');
                $zip = new ZipArchive();
                $zip->open($temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                $zip->addFromString(
                    '[Content_Types].xml',
                    '<?xml version="1.0" encoding="UTF-8"?>
                <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
                    <Default Extension="xml" ContentType="application/xml"/>
                    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
                    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
                    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
                    <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
                </Types>'
                );

                $zip->addEmptyDir('_rels');
                $zip->addEmptyDir('xl');
                $zip->addEmptyDir('xl/_rels');
                $zip->addEmptyDir('xl/worksheets');

                $zip->addFromString(
                    '_rels/.rels',
                    '<?xml version="1.0" encoding="UTF-8"?>
                <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
                    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
                </Relationships>'
                );

                $zip->addFromString(
                    'xl/_rels/workbook.xml.rels',
                    '<?xml version="1.0" encoding="UTF-8"?>
                <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
                    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
                    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
                </Relationships>'
                );

                $zip->addFromString(
                    'xl/workbook.xml',
                    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
                    <sheets>
                        <sheet name="Sheet1" sheetId="1" r:id="rId1"/>
                    </sheets>
                </workbook>'
                );

                $sharedStrings = [];
                $sharedStringsIndex = 0;
                $sharedStringsMap = [];

                foreach ($columns as $column)
                {
                    if (!isset($sharedStringsMap[$column]))
                    {
                        $sharedStringsMap[$column] = $sharedStringsIndex++;
                        $sharedStrings[] = htmlspecialchars($column, ENT_XML1);
                    }
                }

                $sheetData = '<sheetData>';
                $rowNum = 1;

                $sheetData .= '<row r="' . $rowNum . '">';
                foreach ($columns as $column)
                {
                    $sheetData .= '<c t="s"><v>' . $sharedStringsMap[$column] . '</v></c>';
                }
                $sheetData .= '</row>';
                $rowNum++;

                foreach ($uniqueBookings as $booking)
                {
                    $bookingData = $booking['booking'];
                    if ($booking['count'] > 1)
                    {
                        $bookingData[4] .= ' (x' . $booking['count'] . ')';
                    }

                    $data = array_merge($bookingData, $booking['reg_fields'], $booking['fixed_fields']);

                    $sheetData .= '<row r="' . $rowNum . '">';
                    foreach ($data as $value)
                    {
                        if (!isset($sharedStringsMap[$value]))
                        {
                            $sharedStringsMap[$value] = $sharedStringsIndex++;
                            $sharedStrings[] = htmlspecialchars($value, ENT_XML1);
                        }
                        $sheetData .= '<c t="s"><v>' . $sharedStringsMap[$value] . '</v></c>';
                    }
                    $sheetData .= '</row>';
                    $rowNum++;
                }
                $sheetData .= '</sheetData>';

                $sharedStringsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($sharedStrings) . '" uniqueCount="' . count($sharedStrings) . '">';
                foreach ($sharedStrings as $string)
                {
                    $sharedStringsXml .= '<si><t>' . $string . '</t></si>';
                }
                $sharedStringsXml .= '</sst>';

                $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);

                $worksheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
                    ' . $sheetData . '
                </worksheet>';

                $zip->addFromString('xl/worksheets/sheet1.xml', $worksheetXml);

                $zip->close();

                readfile($temp_file);
                unlink($temp_file);
                exit;

            default:
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=attendees-' . md5(time() . mt_rand(100, 999)) . '.csv');

                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($output, $columns);

                foreach ($uniqueBookings as $booking)
                {
                    $bookingData = $booking['booking'];
                    if ($booking['count'] > 1)
                    {
                        $bookingData[4] .= ' (x' . $booking['count'] . ')';
                    }

                    $row = array_merge($bookingData, $booking['reg_fields'], $booking['fixed_fields']);
                    fputcsv($output, $row);
                }

                fclose($output);
                exit;
        }
    }

    private function getTicketName($event_id, $attendee_id)
    {
        if (!$event_id || !$attendee_id)
        {
            return esc_html__('Unknown', 'modern-events-calendar-lite');
        }

        $tickets = get_post_meta($event_id, 'mec_tickets', true);
        if (!is_array($tickets))
        {
            return esc_html__('Unknown', 'modern-events-calendar-lite');
        }

        return isset($tickets[$attendee_id]['name']) ? $tickets[$attendee_id]['name'] : esc_html__('Unknown', 'modern-events-calendar-lite');
    }

    public function fes_upload()
    {
        // Check if our nonce is set.
        if (!isset($_POST['_wpnonce'])) $this->main->response(['success' => 0, 'code' => 'NONCE_MISSING']);

        // Verify that the nonce is valid.
        if (!wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), 'mec_fes_upload_featured_image')) $this->main->response(['success' => 0, 'code' => 'NONCE_IS_INVALID']);

        // Include the function
        if (!function_exists('wp_handle_upload')) require_once ABSPATH . 'wp-admin/includes/file.php';

        $uploaded_file = $_FILES['file'] ?? null;

        // No file
        if (!$uploaded_file) $this->main->response(['success' => 0, 'code' => 'NO_FILE', 'message' => esc_html__('Please upload an image.', 'modern-events-calendar-lite')]);

        $allowed = ['gif', 'jpeg', 'jpg', 'png', 'webp'];

        $ex = explode('.', $uploaded_file['name']);
        $extension = end($ex);

        // Invalid Extension
        if (!in_array($extension, $allowed)) $this->main->response(['success' => 0, 'code' => 'INVALID_EXTENSION', 'message' => sprintf(esc_html__('image extension is invalid. You can upload %s images.', 'modern-events-calendar-lite'), implode(', ', $allowed))]);

        // Maximum File Size
        $max_file_size = isset($this->settings['fes_max_file_size']) ? (int) ($this->settings['fes_max_file_size'] * 1000) : (5000 * 1000);

        // Invalid Size
        if ($uploaded_file['size'] > $max_file_size) $this->main->response(['success' => 0, 'code' => 'IMAGE_IS_TOO_BIG', 'message' => sprintf(esc_html__('Image is too big. Maximum size is %s KB.', 'modern-events-calendar-lite'), ($max_file_size / 1000))]);

        $movefile = wp_handle_upload($uploaded_file, ['test_form' => false]);

        $success = 0;
        $data = [];

        if ($movefile and !isset($movefile['error']))
        {
            $success = 1;
            $message = esc_html__('Image uploaded!', 'modern-events-calendar-lite');

            $data['url'] = $movefile['url'];
        }
        else
        {
            $message = $movefile['error'];
        }

        $this->main->response(['success' => $success, 'message' => $message, 'data' => $data]);
    }

    public function fes_form()
    {
        // Check if our nonce is set.
        if (!isset($_POST['_wpnonce'])) $this->main->response(['success' => 0, 'code' => 'NONCE_MISSING']);

        // Verify that the nonce is valid.
        if (!wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), 'mec_fes_form')) $this->main->response(['success' => 0, 'code' => 'NONCE_IS_INVALID']);

        $raw_mec = isset($_POST['mec']) && is_array($_POST['mec']) ? wp_unslash($_POST['mec']) : [];
        $mec = isset($_POST['mec']) ? $this->main->sanitize_deep_array($_POST['mec']) : [];

        // Post ID
        $post_id = isset($mec['post_id']) ? (int) sanitize_text_field($mec['post_id']) : -1;

        // Show a warning to current user if modification of post is not possible for him/her
        if (!$this->current_user_can_upsert_event($post_id)) $this->main->response(['success' => 0, 'message' => esc_html__("Sorry! You don't have access to modify this event.", 'modern-events-calendar-lite'), 'code' => 'NO_ACCESS']);

        // Validate Captcha
        if ($this->getCaptcha()->status('fes') and !$this->getCaptcha()->is_valid())
        {
            $this->main->response(['success' => 0, 'message' => __('Invalid Captcha! Please try again.', 'modern-events-calendar-lite'), 'code' => 'CAPTCHA_IS_INVALID']);
        }

        // Agreement Status
        $agreement_status = (isset($this->settings['fes_agreement']) and $this->settings['fes_agreement']);
        if ($agreement_status)
        {
            $checked = (isset($mec['agreement']) and $mec['agreement']);
            if (!$checked) $this->main->response(['success' => 0, 'message' => __('You should accept the terms and conditions.', 'modern-events-calendar-lite'), 'code' => 'TERMS_CONDITIONS']);
        }

        $start_date = (isset($mec['date']['start']['date']) and trim($mec['date']['start']['date'])) ? $this->main->standardize_format(sanitize_text_field($mec['date']['start']['date'])) : date('Y-m-d');
        $end_date = (isset($mec['date']['end']['date']) and trim($mec['date']['end']['date'])) ? $this->main->standardize_format(sanitize_text_field($mec['date']['end']['date'])) : date('Y-m-d');

        $post_title = isset($mec['title']) ? sanitize_text_field($mec['title']) : '';
        $post_content = isset($raw_mec['content']) ? MEC_kses::page($raw_mec['content']) : '';
        $post_excerpt = isset($raw_mec['excerpt']) ? MEC_kses::page($raw_mec['excerpt']) : '';
        $post_tags = isset($mec['tags']) ? sanitize_text_field($mec['tags']) : '';
        $post_categories = isset($mec['categories']) ? array_map('sanitize_text_field', $mec['categories']) : [];
        $post_speakers = isset($mec['speakers']) ? array_map('sanitize_text_field', $mec['speakers']) : [];
        $post_sponsors = isset($mec['sponsors']) ? array_map('sanitize_text_field', $mec['sponsors']) : [];
        $post_labels = isset($mec['labels']) ? array_map('sanitize_text_field', $mec['labels']) : [];
        $featured_image = isset($mec['featured_image']) ? sanitize_text_field($mec['featured_image']) : '';

        $read_more = isset($mec['read_more']) ? sanitize_url($mec['read_more']) : '';
        $more_info = (isset($mec['more_info']) and trim($mec['more_info'])) ? sanitize_url($mec['more_info']) : '';
        $more_info_title = isset($mec['more_info_title']) ? sanitize_text_field($mec['more_info_title']) : '';
        $more_info_target = isset($this->settings['fes_event_link_target']) && $this->settings['fes_event_link_target'] ? $this->settings['fes_event_link_target'] : '';

        $cost = isset($mec['cost']) ? sanitize_text_field($mec['cost']) : '';

        // Title is Required
        if (!trim($post_title)) $this->main->response(['success' => 0, 'message' => __('Please fill event title field!', 'modern-events-calendar-lite'), 'code' => 'TITLE_IS_EMPTY']);

        // Body is Required
        $is_required_content = apply_filters(
            'mec_fes_form_is_required_fields',
            isset($this->settings['fes_required_body']) && $this->settings['fes_required_body'],
            'content'
        );
        $normalized_post_content = trim(preg_replace('/\x{00a0}+/u', ' ', wp_strip_all_tags(html_entity_decode($post_content, ENT_QUOTES, 'UTF-8'))));
        if ($is_required_content && $normalized_post_content === '') $this->main->response(['success' => 0, 'message' => __('Please fill event body field!', 'modern-events-calendar-lite'), 'code' => 'BODY_IS_EMPTY']);

        // excerpt is Required
        $is_required_excerpt = apply_filters(
            'mec_fes_form_is_required_fields',
            isset($this->settings['fes_required_excerpt']) && $this->settings['fes_required_excerpt'],
            'excerpt'
        );
        $normalized_post_excerpt = trim(preg_replace('/\x{00a0}+/u', ' ', wp_strip_all_tags(html_entity_decode($post_excerpt, ENT_QUOTES, 'UTF-8'))));
        if ($is_required_excerpt && $normalized_post_excerpt === '') $this->main->response(['success' => 0, 'message' => __('Please fill event excerpt field!', 'modern-events-calendar-lite'), 'code' => 'EXCERPT_IS_EMPTY']);

        // Dates are Required
        $is_required_dates = apply_filters(
            'mec_fes_form_is_required_fields',
            (isset($this->settings['fes_required_dates']) and $this->settings['fes_required_dates']),
            'dates'
        );
        if ($is_required_dates)
        {
            $start_date_is_filled = (isset($mec['date']['start']['date']) and trim($mec['date']['start']['date']));
            $end_date_is_filled = (isset($mec['date']['end']['date']) and trim($mec['date']['end']['date']));

            if (!$start_date_is_filled) $this->main->response(['success' => 0, 'message' => __('Please fill event start date!', 'modern-events-calendar-lite'), 'code' => 'START_DATE_IS_EMPTY']);
            if (!$end_date_is_filled) $this->main->response(['success' => 0, 'message' => __('Please fill event end date!', 'modern-events-calendar-lite'), 'code' => 'END_DATE_IS_EMPTY']);
        }

        // Category is Required
        $is_required_category = apply_filters(
            'mec_fes_form_is_required_fields',
            isset($this->settings['fes_section_categories']) && $this->settings['fes_section_categories'] && isset($this->settings['fes_required_category']) && $this->settings['fes_required_category'],
            'category'
        );
        if ($is_required_category and !count($post_categories)) $this->main->response(['success' => 0, 'message' => __('Please select at-least one category!', 'modern-events-calendar-lite'), 'code' => 'CATEGORY_IS_EMPTY']);

        // Location is Required
        $is_required_location = apply_filters(
            'mec_fes_form_is_required_fields',
            (isset($this->settings['fes_section_location']) and $this->settings['fes_section_location'] and isset($this->settings['fes_required_location']) and $this->settings['fes_required_location']),
            'location'
        );
        if ($is_required_location)
        {
            $location_id_is_filled = (isset($mec['location_id']) and trim($mec['location_id']) and $mec['location_id'] != 1);
            $location_add_request = (isset($mec['location'], $mec['location']['address']) and trim($mec['location']['address']));

            if (!$location_id_is_filled and !$location_add_request) $this->main->response(['success' => 0, 'message' => __('Please select the event location!', 'modern-events-calendar-lite'), 'code' => 'LOCATION_IS_EMPTY']);
        }

        // Organizer is Required
        $is_required_organizer = apply_filters(
            'mec_fes_form_is_required_fields',
            (isset($this->settings['fes_section_organizer']) and $this->settings['fes_section_organizer'] and isset($this->settings['fes_required_organizer']) and $this->settings['fes_required_organizer']),
            'organizer'
        );
        if ($is_required_organizer)
        {
            $organizer_id_is_filled = (isset($mec['organizer_id']) and trim($mec['organizer_id']) and $mec['organizer_id'] != 1);
            $organizer_add_request = (isset($mec['organizer'], $mec['organizer']['name']) and trim($mec['organizer']['name']));

            if (!$organizer_id_is_filled and !$organizer_add_request) $this->main->response(['success' => 0, 'message' => __('Please select the event organizer!', 'modern-events-calendar-lite'), 'code' => 'ORGANIZER_IS_EMPTY']);
        }

        // Label is Required
        $is_required_label = apply_filters(
            'mec_fes_form_is_required_fields',
            isset($this->settings['fes_section_labels']) && $this->settings['fes_section_labels'] && isset($this->settings['fes_required_label']) && $this->settings['fes_required_label'],
            'label'
        );
        if ($is_required_label and !count($post_labels)) $this->main->response(['success' => 0, 'message' => __('Please select at-least one label!', 'modern-events-calendar-lite'), 'code' => 'LABEL_IS_EMPTY']);

        // Featured Image is Required
        $is_required_featured_image = apply_filters(
            'mec_fes_form_is_required_fields',
            isset($this->settings['fes_section_featured_image']) && $this->settings['fes_section_featured_image'] && isset($this->settings['fes_required_featured_image']) && $this->settings['fes_required_featured_image'],
            'featured_image'
        );
        if ($is_required_featured_image and !trim($featured_image)) $this->main->response(['success' => 0, 'message' => __('Please upload a featured image!', 'modern-events-calendar-lite'), 'code' => 'FEATURED_IMAGE_IS_EMPTY']);

        // Event link is required
        $is_required_event_link = apply_filters(
            'mec_fes_form_is_required_fields',
            isset($this->settings['fes_required_event_link']) && $this->settings['fes_required_event_link'],
            'event_link'
        );
        if ($is_required_event_link and !trim($read_more)) $this->main->response(['success' => 0, 'message' => __('Please fill event link!', 'modern-events-calendar-lite'), 'code' => 'EVENT_LINK_IS_EMPTY']);

        // More Info is required
        $is_required_more_info = apply_filters(
            'mec_fes_form_is_required_fields',
            isset($this->settings['fes_required_more_info']) && $this->settings['fes_required_more_info'],
            'more_info'
        );
        if ($is_required_more_info and !trim($more_info)) $this->main->response(['success' => 0, 'message' => __('Please fill more info!', 'modern-events-calendar-lite'), 'code' => 'MORE_INFO_IS_EMPTY']);

        // Cost is required
        $is_required_cost = apply_filters(
            'mec_fes_form_is_required_fields',
            isset($this->settings['fes_required_cost']) && $this->settings['fes_required_cost'],
            'cost'
        );
        if ($is_required_cost and trim($cost) === '') $this->main->response(['success' => 0, 'message' => __('Please fill cost!', 'modern-events-calendar-lite'), 'code' => 'COST_IS_EMPTY']);

        // Post Status
        $status = 'pending';
        if (current_user_can('publish_posts')) $status = 'publish';

        $method = 'updated';

        // Create new event
        if ($post_id == -1)
        {
            // Force Status
            if (isset($this->settings['fes_new_event_status']) && trim($this->settings['fes_new_event_status'])) $status = $this->settings['fes_new_event_status'];

            $post = ['post_title' => $post_title, 'post_content' => $post_content, 'post_excerpt' => $post_excerpt, 'post_type' => $this->PT, 'post_status' => $status];
            $post_id = wp_insert_post($post);

            $method = 'added';

            // FES Flag
            update_post_meta($post_id, 'mec_created_by_fes', 1);

            // Default Category
            if (isset($this->settings['fes_default_category']) && $this->settings['fes_default_category'] && !count($post_categories))
            {
                $post_categories[$this->settings['fes_default_category']] = 1;
            }
        }
        // Update
        else
        {
            // Force Status
            if (isset($this->settings['fes_update_event_status']) && trim($this->settings['fes_update_event_status'])) $status = $this->settings['fes_update_event_status'];
        }

        wp_update_post(['ID' => $post_id, 'post_title' => $post_title, 'post_content' => $post_content, 'post_excerpt' => $post_excerpt, 'post_status' => $status]);

        // Categories Section
        if (!isset($this->settings['fes_section_categories']) or (isset($this->settings['fes_section_categories']) and $this->settings['fes_section_categories']))
        {
            // Categories
            $categories = [];
            foreach ($post_categories as $post_category => $value) $categories[] = (int) $post_category;

            wp_set_post_terms($post_id, $categories, 'mec_category');
        }

        // Speakers Section
        if (!isset($this->settings['fes_section_speaker']) or (isset($this->settings['fes_section_speaker']) and $this->settings['fes_section_speaker']))
        {
            // Speakers
            if (isset($this->settings['speakers_status']) and $this->settings['speakers_status'])
            {
                $speakers = [];
                foreach ($post_speakers as $post_speaker => $value) $speakers[] = (int) $post_speaker;

                wp_set_post_terms($post_id, $speakers, 'mec_speaker');
            }
        }

        // Sponsors Section
        if ($this->getPRO() and isset($this->settings['fes_section_sponsor']) and $this->settings['fes_section_sponsor'])
        {
            // Sponsors
            if (isset($this->settings['sponsors_status']) and $this->settings['sponsors_status'])
            {
                $sponsors = [];
                foreach ($post_sponsors as $post_sponsor => $value) $sponsors[] = (int) $post_sponsor;

                wp_set_post_terms($post_id, $sponsors, 'mec_sponsor');
            }
        }

        // Labels Section
        if (!isset($this->settings['fes_section_labels']) or (isset($this->settings['fes_section_labels']) and $this->settings['fes_section_labels']))
        {
            // Labels
            $labels = [];
            foreach ($post_labels as $post_label => $value) $labels[] = (int) $post_label;

            wp_set_post_terms($post_id, $labels, 'mec_label');
            do_action('mec_label_change_to_radio', $labels, $post_labels, $post_id);
        }

        // Color Section
        if (!isset($this->settings['fes_section_event_color']) or (isset($this->settings['fes_section_event_color']) and $this->settings['fes_section_event_color']))
        {
            // Color
            $color = isset($mec['color']) ? sanitize_text_field(trim($mec['color'], '# ')) : '';
            update_post_meta($post_id, 'mec_color', $color);
        }

        // Tags Section
        if (!isset($this->settings['fes_section_tags']) or (isset($this->settings['fes_section_tags']) and $this->settings['fes_section_tags']))
        {
            // Tags
            wp_set_post_terms($post_id, $post_tags, apply_filters('mec_taxonomy_tag', ''));
        }

        // Featured Image Section
        if (!isset($this->settings['fes_section_featured_image']) || $this->settings['fes_section_featured_image'])
        {
            // Featured Image
            if (trim($featured_image)) $this->main->set_featured_image($featured_image, $post_id, ['gif', 'jpeg', 'jpg', 'png', 'webp']);
            else delete_post_thumbnail($post_id);

            // Featured Image Caption
            if (isset($this->settings['featured_image_caption']) && $this->settings['featured_image_caption'])
            {
                $attachment_id = get_post_thumbnail_id($post_id);
                if ($attachment_id)
                {
                    $featured_image_caption = isset($mec['featured_image_caption']) ? sanitize_text_field($mec['featured_image_caption']) : '';
                    $this->db->q("UPDATE `#__posts` SET `post_excerpt`='" . esc_sql($featured_image_caption) . "' WHERE `ID`=" . ((int) $attachment_id));
                }
            }
        }

        // Links Section
        if (!isset($this->settings['fes_section_event_links']) or (isset($this->settings['fes_section_event_links']) and $this->settings['fes_section_event_links']))
        {
            update_post_meta($post_id, 'mec_read_more', $read_more);
            update_post_meta($post_id, 'mec_more_info', $more_info);
            update_post_meta($post_id, 'mec_more_info_title', $more_info_title);
            update_post_meta($post_id, 'mec_more_info_target', $more_info_target);
        }

        // Cost Section
        if (!isset($this->settings['fes_section_cost']) or (isset($this->settings['fes_section_cost']) and $this->settings['fes_section_cost']))
        {
            $cost = apply_filters(
                'mec_event_cost_sanitize',
                sanitize_text_field($cost),
                $cost
            );

            $cost_auto_calculate = (isset($mec['cost_auto_calculate']) ? sanitize_text_field($mec['cost_auto_calculate']) : 0);
            $currency_options = ((isset($mec['currency']) and is_array($mec['currency'])) ? array_map('sanitize_text_field', $mec['currency']) : []);

            update_post_meta($post_id, 'mec_cost', $cost);
            update_post_meta($post_id, 'mec_cost_auto_calculate', $cost_auto_calculate);
            update_post_meta($post_id, 'mec_currency', $currency_options);
        }

        // Guest Name and Email
        $fes_guest_email = isset($mec['fes_guest_email']) ? sanitize_email($mec['fes_guest_email']) : '';
        $fes_guest_name = isset($mec['fes_guest_name']) ? sanitize_text_field($mec['fes_guest_name']) : '';
        $note = isset($mec['note']) ? sanitize_text_field($mec['note']) : '';

        update_post_meta($post_id, 'fes_guest_email', $fes_guest_email);
        update_post_meta($post_id, 'fes_guest_name', $fes_guest_name);
        update_post_meta($post_id, 'mec_note', $note);

        // Location Section
        if (!isset($this->settings['fes_section_location']) or (isset($this->settings['fes_section_location']) and $this->settings['fes_section_location']))
        {
            // Location
            $location_id = isset($mec['location_id']) ? sanitize_text_field($mec['location_id']) : 1;

            // Selected a saved location
            if ($location_id)
            {
                // Set term to the post
                wp_set_object_terms($post_id, (int) $location_id, 'mec_location');
            }
            else
            {
                $address = (isset($mec['location']['address']) and trim($mec['location']['address'])) ? sanitize_text_field($mec['location']['address']) : '';
                $name = (isset($mec['location']['name']) and trim($mec['location']['name'])) ? sanitize_text_field($mec['location']['name']) : (trim($address) ? $address : esc_html__('Location Name', 'modern-events-calendar-lite'));

                $term = get_term_by('name', $name, 'mec_location');

                // Term already exists
                if (is_object($term) and isset($term->term_id))
                {
                    // Set term to the post
                    wp_set_object_terms($post_id, (int) $term->term_id, 'mec_location');
                }
                else
                {
                    $term = wp_insert_term($name, 'mec_location');

                    $location_id = $term['term_id'];
                    if ($location_id)
                    {
                        // Set term to the post
                        wp_set_object_terms($post_id, (int) $location_id, 'mec_location');

                        $opening_hour = (isset($mec['location']['opening_hour']) and trim($mec['location']['opening_hour'])) ? sanitize_text_field($mec['location']['opening_hour']) : '';
                        $latitude = (isset($mec['location']['latitude']) and trim($mec['location']['latitude'])) ? sanitize_text_field($mec['location']['latitude']) : 0;
                        $longitude = (isset($mec['location']['longitude']) and trim($mec['location']['longitude'])) ? sanitize_text_field($mec['location']['longitude']) : 0;
                        $url = (isset($mec['location']['url']) and trim($mec['location']['url'])) ? sanitize_url($mec['location']['url']) : '';
                        $tel = (isset($mec['location']['tel']) and trim($mec['location']['tel'])) ? sanitize_text_field($mec['location']['tel']) : '';
                        $thumbnail = (isset($mec['location']['thumbnail']) and trim($mec['location']['thumbnail'])) ? sanitize_text_field($mec['location']['thumbnail']) : '';

                        if (!trim($latitude) or !trim($longitude))
                        {
                            $geo_point = $this->main->get_lat_lng($address);

                            $latitude = $geo_point[0];
                            $longitude = $geo_point[1];
                        }

                        update_term_meta($location_id, 'address', $address);
                        update_term_meta($location_id, 'opening_hour', $opening_hour);
                        update_term_meta($location_id, 'latitude', $latitude);
                        update_term_meta($location_id, 'longitude', $longitude);
                        update_term_meta($location_id, 'url', $url);
                        update_term_meta($location_id, 'tel', $tel);
                        update_term_meta($location_id, 'thumbnail', $thumbnail);
                    }
                    else $location_id = 1;
                }
            }

            update_post_meta($post_id, 'mec_location_id', $location_id);

            $dont_show_map = isset($mec['dont_show_map']) ? sanitize_text_field($mec['dont_show_map']) : 0;
            update_post_meta($post_id, 'mec_dont_show_map', $dont_show_map);
        }

        // Organizer Section
        if (!isset($this->settings['fes_section_organizer']) or (isset($this->settings['fes_section_organizer']) and $this->settings['fes_section_organizer']))
        {
            // Organizer
            $organizer_id = isset($mec['organizer_id']) ? sanitize_text_field($mec['organizer_id']) : 1;

            // Selected a saved organizer
            if (isset($organizer_id) and $organizer_id)
            {
                // Set term to the post
                wp_set_object_terms($post_id, (int) $organizer_id, 'mec_organizer');
            }
            else
            {
                $name = (isset($mec['organizer']['name']) and trim($mec['organizer']['name'])) ? sanitize_text_field($mec['organizer']['name']) : esc_html__('Organizer Name', 'modern-events-calendar-lite');

                $term = get_term_by('name', $name, 'mec_organizer');

                // Term already exists
                if (is_object($term) and isset($term->term_id))
                {
                    // Set term to the post
                    wp_set_object_terms($post_id, (int) $term->term_id, 'mec_organizer');
                }
                else
                {
                    $term = wp_insert_term($name, 'mec_organizer');

                    $organizer_id = $term['term_id'];
                    if ($organizer_id)
                    {
                        // Set term to the post
                        wp_set_object_terms($post_id, (int) $organizer_id, 'mec_organizer');

                        $tel = (isset($mec['organizer']['tel']) and trim($mec['organizer']['tel'])) ? sanitize_text_field($mec['organizer']['tel']) : '';
                        $email = (isset($mec['organizer']['email']) and trim($mec['organizer']['email'])) ? sanitize_text_field($mec['organizer']['email']) : '';
                        $url = (isset($mec['organizer']['url']) and trim($mec['organizer']['url'])) ? sanitize_url($mec['organizer']['url']) : '';
                        $page_label = (isset($mec['organizer']['page_label']) and trim($mec['organizer']['page_label'])) ? sanitize_text_field($mec['organizer']['page_label']) : '';
                        $thumbnail = (isset($mec['organizer']['thumbnail']) and trim($mec['organizer']['thumbnail'])) ? sanitize_text_field($mec['organizer']['thumbnail']) : '';

                        update_term_meta($organizer_id, 'tel', $tel);
                        update_term_meta($organizer_id, 'email', $email);
                        update_term_meta($organizer_id, 'url', $url);
                        update_term_meta($organizer_id, 'page_label', $page_label);
                        update_term_meta($organizer_id, 'thumbnail', $thumbnail);
                    }
                    else $organizer_id = 1;
                }
            }

            update_post_meta($post_id, 'mec_organizer_id', $organizer_id);

            // Additional Organizers
            $additional_organizer_ids = $mec['additional_organizer_ids'] ?? [];

            foreach ($additional_organizer_ids as $additional_organizer_id) wp_set_object_terms($post_id, (int) $additional_organizer_id, 'mec_organizer', true);
            update_post_meta($post_id, 'mec_additional_organizer_ids', $additional_organizer_ids);

            // Additional locations
            $additional_location_ids = $mec['additional_location_ids'] ?? [];

            foreach ($additional_location_ids as $additional_location_id) wp_set_object_terms($post_id, (int) $additional_location_id, 'mec_location', true);
            update_post_meta($post_id, 'mec_additional_location_ids', $additional_location_ids);
        }

        // Entity Type
        $entity_type = isset($mec['entity_type']) && in_array($mec['entity_type'], ['event', 'appointment']) ? $mec['entity_type'] : 'event';

        // Date Options
        $date = $mec['date'] ?? [];

        $start_date = date('Y-m-d', strtotime($start_date));

        // Set the start date
        $date['start']['date'] = $start_date;

        $start_time_hour = isset($date['start']) ? sanitize_text_field($date['start']['hour']) : '8';
        $start_time_minutes = isset($date['start']) ? sanitize_text_field($date['start']['minutes']) : '00';
        $start_time_ampm = (isset($date['start']) and isset($date['start']['ampm'])) ? sanitize_text_field($date['start']['ampm']) : 'AM';

        $end_date = date('Y-m-d', strtotime($end_date));

        // Fix end_date if it's smaller than start_date
        if (strtotime($end_date) < strtotime($start_date)) $end_date = $start_date;

        // Set the end date
        $date['end']['date'] = $end_date;

        $end_time_hour = isset($date['end']) ? sanitize_text_field($date['end']['hour']) : '6';
        $end_time_minutes = isset($date['end']) ? sanitize_text_field($date['end']['minutes']) : '00';
        $end_time_ampm = (isset($date['end']) and isset($date['end']['ampm'])) ? sanitize_text_field($date['end']['ampm']) : 'PM';

        if (isset($this->settings['time_format']) and $this->settings['time_format'] == 24)
        {
            $day_start_seconds = $this->main->time_to_seconds($this->main->to_24hours($start_time_hour, null), $start_time_minutes);
            $day_end_seconds = $this->main->time_to_seconds($this->main->to_24hours($end_time_hour, null), $end_time_minutes);
        }
        else
        {
            $day_start_seconds = $this->main->time_to_seconds($this->main->to_24hours($start_time_hour, $start_time_ampm), $start_time_minutes);
            $day_end_seconds = $this->main->time_to_seconds($this->main->to_24hours($end_time_hour, $end_time_ampm), $end_time_minutes);
        }

        if ($end_date === $start_date and $day_end_seconds < $day_start_seconds)
        {
            $day_end_seconds = $day_start_seconds;

            $end_time_hour = $start_time_hour;
            $end_time_minutes = $start_time_minutes;
            $end_time_ampm = $start_time_ampm;

            $date['end']['hour'] = $start_time_hour;
            $date['end']['minutes'] = $start_time_minutes;
            $date['end']['ampm'] = $start_time_ampm;
        }

        // If 24 hours format is enabled then convert it back to 12 hours
        if (isset($this->settings['time_format']) and $this->settings['time_format'] == 24)
        {
            if ($start_time_hour < 12) $start_time_ampm = 'AM';
            else if ($start_time_hour == 12) $start_time_ampm = 'PM';
            else if ($start_time_hour > 12)
            {
                $start_time_hour -= 12;
                $start_time_ampm = 'PM';
            }
            else if ($start_time_hour == 0)
            {
                $start_time_hour = 12;
                $start_time_ampm = 'AM';
            }

            if ($end_time_hour < 12) $end_time_ampm = 'AM';
            else if ($end_time_hour == 12) $end_time_ampm = 'PM';
            else if ($end_time_hour > 12)
            {
                $end_time_hour -= 12;
                $end_time_ampm = 'PM';
            }
            else if ($end_time_hour == 0)
            {
                $end_time_hour = 12;
                $end_time_ampm = 'AM';
            }

            // Set converted values to date array
            $date['start']['hour'] = $start_time_hour;
            $date['start']['ampm'] = $start_time_ampm;

            $date['end']['hour'] = $end_time_hour;
            $date['end']['ampm'] = $end_time_ampm;
        }

        $allday = isset($date['allday']) ? 1 : 0;
        $one_occurrence = isset($date['one_occurrence']) ? 1 : 0;
        $hide_time = isset($date['hide_time']) ? 1 : 0;
        $hide_end_time = isset($date['hide_end_time']) ? 1 : 0;
        $comment = isset($date['comment']) ? sanitize_text_field($date['comment']) : '';
        $timezone = (isset($mec['timezone']) and trim($mec['timezone']) != '') ? sanitize_text_field($mec['timezone']) : 'global';
        $countdown_method = (isset($mec['countdown_method']) and trim($mec['countdown_method']) != '') ? sanitize_text_field($mec['countdown_method']) : 'global';
        $style_per_event = (isset($mec['style_per_event']) and trim($mec['style_per_event']) != '') ? sanitize_text_field($mec['style_per_event']) : 'global';
        $trailer_url = (isset($mec['trailer_url']) and trim($mec['trailer_url']) != '') ? sanitize_url($mec['trailer_url']) : '';
        $trailer_title = isset($mec['trailer_title']) ? sanitize_text_field($mec['trailer_title']) : '';
        $public = (isset($mec['public']) and trim($mec['public']) != '') ? sanitize_text_field($mec['public']) : 1;

        // Set start time and end time if event is all day
        if ($allday == 1)
        {
            $start_time_hour = '8';
            $start_time_minutes = '00';
            $start_time_ampm = 'AM';

            $end_time_hour = '6';
            $end_time_minutes = '00';
            $end_time_ampm = 'PM';
        }

        // Previous Date Times
        $prev_start_datetime = get_post_meta($post_id, 'mec_start_datetime', true);
        $prev_end_datetime = get_post_meta($post_id, 'mec_end_datetime', true);

        $start_datetime = $start_date . ' ' . sprintf('%02d', $start_time_hour) . ':' . sprintf('%02d', $start_time_minutes) . ' ' . $start_time_ampm;
        $end_datetime = $end_date . ' ' . sprintf('%02d', $end_time_hour) . ':' . sprintf('%02d', $end_time_minutes) . ' ' . $end_time_ampm;

        update_post_meta($post_id, 'mec_start_date', $start_date);
        update_post_meta($post_id, 'mec_start_time_hour', $start_time_hour);
        update_post_meta($post_id, 'mec_start_time_minutes', $start_time_minutes);
        update_post_meta($post_id, 'mec_start_time_ampm', $start_time_ampm);
        update_post_meta($post_id, 'mec_start_day_seconds', $day_start_seconds);
        update_post_meta($post_id, 'mec_start_datetime', $start_datetime);

        update_post_meta($post_id, 'mec_end_date', $end_date);
        update_post_meta($post_id, 'mec_end_time_hour', $end_time_hour);
        update_post_meta($post_id, 'mec_end_time_minutes', $end_time_minutes);
        update_post_meta($post_id, 'mec_end_time_ampm', $end_time_ampm);
        update_post_meta($post_id, 'mec_end_day_seconds', $day_end_seconds);
        update_post_meta($post_id, 'mec_end_datetime', $end_datetime);

        update_post_meta($post_id, 'mec_date', $date);
        update_post_meta($post_id, 'mec_entity_type', $entity_type);

        // Repeat Options
        $repeat = $date['repeat'] ?? [];
        $certain_weekdays = $repeat['certain_weekdays'] ?? [];

        $repeat_status = isset($repeat['status']) ? 1 : 0;
        $repeat_type = ($repeat_status and isset($repeat['type'])) ? sanitize_text_field($repeat['type']) : '';

        $repeat_interval = ($repeat_status and isset($repeat['interval']) and trim($repeat['interval'])) ? sanitize_text_field($repeat['interval']) : 1;

        // Advanced Repeat
        $advanced = isset($repeat['advanced']) ? sanitize_text_field($repeat['advanced']) : '';

        if (!is_numeric($repeat_interval)) $repeat_interval = null;

        if ($repeat_type == 'weekly') $interval_multiply = 7;
        else $interval_multiply = 1;

        // Reset certain weekdays if repeat type is not set to certain weekdays
        if ($repeat_type != 'certain_weekdays') $certain_weekdays = [];

        if (!is_null($repeat_interval)) $repeat_interval = $repeat_interval * $interval_multiply;

        // String To Array
        if ($repeat_type == 'advanced' and trim($advanced)) $advanced = explode('-', $advanced);
        else $advanced = [];

        $repeat_end = ($repeat_status and isset($repeat['end'])) ? sanitize_text_field($repeat['end']) : '';
        $repeat_end_at_occurrences = ($repeat_status && isset($repeat['end_at_occurrences']) && is_numeric($repeat['end_at_occurrences'])) ? $repeat['end_at_occurrences'] - 1 : 9;
        $repeat_end_at_date = ($repeat_status and isset($repeat['end_at_date'])) ? $this->main->standardize_format(sanitize_text_field($repeat['end_at_date'])) : '';

        update_post_meta($post_id, 'mec_date', $date);
        update_post_meta($post_id, 'mec_repeat', $repeat);
        update_post_meta($post_id, 'mec_certain_weekdays', $certain_weekdays);
        update_post_meta($post_id, 'mec_allday', $allday);
        update_post_meta($post_id, 'one_occurrence', $one_occurrence);
        update_post_meta($post_id, 'mec_hide_time', $hide_time);
        update_post_meta($post_id, 'mec_hide_end_time', $hide_end_time);
        update_post_meta($post_id, 'mec_comment', $comment);
        update_post_meta($post_id, 'mec_timezone', $timezone);
        update_post_meta($post_id, 'mec_countdown_method', $countdown_method);
        update_post_meta($post_id, 'mec_style_per_event', $style_per_event);
        update_post_meta($post_id, 'mec_trailer_url', $trailer_url);
        update_post_meta($post_id, 'mec_trailer_title', $trailer_title);
        update_post_meta($post_id, 'mec_public', $public);
        update_post_meta($post_id, 'mec_repeat_status', $repeat_status);
        update_post_meta($post_id, 'mec_repeat_type', $repeat_type);
        update_post_meta($post_id, 'mec_repeat_interval', $repeat_interval);
        update_post_meta($post_id, 'mec_repeat_end', $repeat_end);
        update_post_meta($post_id, 'mec_repeat_end_at_occurrences', $repeat_end_at_occurrences);
        update_post_meta($post_id, 'mec_repeat_end_at_date', $repeat_end_at_date);
        update_post_meta($post_id, 'mec_advanced_days', $advanced);

        // Event Sequence (Used in iCal feed)
        $sequence = (int) get_post_meta($post_id, 'mec_sequence', true);
        update_post_meta($post_id, 'mec_sequence', ($sequence + 1));

        // Creating $event array for inserting in mec_events table
        $event = ['post_id' => $post_id, 'start' => $start_date, 'repeat' => $repeat_status, 'rinterval' => (!in_array($repeat_type, ['daily', 'weekly', 'monthly']) ? null : $repeat_interval), 'time_start' => $day_start_seconds, 'time_end' => $day_end_seconds];

        $year = null;
        $month = null;
        $day = null;
        $week = null;
        $weekday = null;
        $weekdays = null;

        // MEC weekdays
        $mec_weekdays = $this->main->get_weekdays();

        // MEC weekends
        $mec_weekends = $this->main->get_weekends();

        $plus_date = null;
        if ($repeat_type == 'daily')
        {
            $plus_date = '+' . $repeat_end_at_occurrences * $repeat_interval . ' Days';
        }
        else if ($repeat_type == 'weekly')
        {
            $plus_date = '+' . $repeat_end_at_occurrences * ($repeat_interval) . ' Days';
        }
        else if ($repeat_type == 'weekday')
        {
            $repeat_interval = 1;
            $plus_date = '+' . $repeat_end_at_occurrences * $repeat_interval . ' Weekdays';

            $weekdays = ',' . implode(',', $mec_weekdays) . ',';
        }
        else if ($repeat_type == 'weekend')
        {
            $repeat_interval = 1;
            $plus_date = '+' . round($repeat_end_at_occurrences / 2) * ($repeat_interval * 7) . ' Days';

            $weekdays = ',' . implode(',', $mec_weekends) . ',';
        }
        else if ($repeat_type == 'certain_weekdays')
        {
            $repeat_interval = 1;
            $plus_date = '+' . ceil(($repeat_end_at_occurrences * $repeat_interval) * (7 / count($certain_weekdays))) . ' days';

            $weekdays = ',' . implode(',', $certain_weekdays) . ',';
        }
        else if ($repeat_type == 'monthly')
        {
            $plus_date = '+' . $repeat_end_at_occurrences * $repeat_interval . ' Months';

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
        else if ($repeat_type == 'yearly')
        {
            $plus_date = '+' . $repeat_end_at_occurrences * $repeat_interval . ' Years';

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
        else if ($repeat_type == "advanced")
        {
            // Render class object
            $this->render = $this->getRender();

            // Get finish date
            $event_info = ['start' => $date['start'], 'end' => $date['end']];
            $dates = $this->render->generate_advanced_days($advanced, $event_info, $repeat_end_at_occurrences + 1, date('Y-m-d', current_time('timestamp', 0)), 'events');

            $period_date = $this->main->date_diff($start_date, end($dates)['end']['date']);

            $plus_date = '+' . $period_date->days . ' Days';
        }

        // "In Days" and "Not In Days"
        $in_days_arr = (isset($mec['in_days']) and is_array($mec['in_days']) and count($mec['in_days'])) ? array_unique($mec['in_days']) : [];
        $not_in_days_arr = (isset($mec['not_in_days']) and is_array($mec['not_in_days']) and count($mec['not_in_days'])) ? array_unique($mec['not_in_days']) : [];

        $in_days = '';
        if (count($in_days_arr))
        {
            if (isset($in_days_arr[':i:'])) unset($in_days_arr[':i:']);

            $in_days_arr = array_map(function ($value)
            {
                $ex = explode(':', $value);

                $in_days_times = '';
                if (isset($ex[2]) and isset($ex[3]))
                {
                    $in_days_start_time = $ex[2];
                    $in_days_end_time = $ex[3];

                    // If 24 hours format is enabled then convert it back to 12 hours
                    if (isset($this->settings['time_format']) and $this->settings['time_format'] == 24)
                    {
                        $ex_start_time = explode('-', $in_days_start_time);
                        $ex_end_time = explode('-', $in_days_end_time);

                        $in_days_start_hour = $ex_start_time[0];
                        $in_days_start_minutes = $ex_start_time[1];
                        $in_days_start_ampm = $ex_start_time[2];

                        $in_days_end_hour = $ex_end_time[0];
                        $in_days_end_minutes = $ex_end_time[1];
                        $in_days_end_ampm = $ex_end_time[2];

                        if (trim($in_days_start_ampm) == '')
                        {
                            if ($in_days_start_hour < 12) $in_days_start_ampm = 'AM';
                            else if ($in_days_start_hour == 12) $in_days_start_ampm = 'PM';
                            else if ($in_days_start_hour > 12)
                            {
                                $in_days_start_hour -= 12;
                                $in_days_start_ampm = 'PM';
                            }
                            else if ($in_days_start_hour == 0)
                            {
                                $in_days_start_hour = 12;
                                $in_days_start_ampm = 'AM';
                            }
                        }

                        if (trim($in_days_end_ampm) == '')
                        {
                            if ($in_days_end_hour < 12) $in_days_end_ampm = 'AM';
                            else if ($in_days_end_hour == 12) $in_days_end_ampm = 'PM';
                            else if ($in_days_end_hour > 12)
                            {
                                $in_days_end_hour -= 12;
                                $in_days_end_ampm = 'PM';
                            }
                            else if ($in_days_end_hour == 0)
                            {
                                $in_days_end_hour = 12;
                                $in_days_end_ampm = 'AM';
                            }
                        }

                        if (strlen($in_days_start_hour) == 1) $in_days_start_hour = '0' . $in_days_start_hour;
                        if (strlen($in_days_start_minutes) == 1) $in_days_start_minutes = '0' . $in_days_start_minutes;

                        if (strlen($in_days_end_hour) == 1) $in_days_end_hour = '0' . $in_days_end_hour;
                        if (strlen($in_days_end_minutes) == 1) $in_days_end_minutes = '0' . $in_days_end_minutes;

                        $in_days_start_time = $in_days_start_hour . '-' . $in_days_start_minutes . '-' . $in_days_start_ampm;
                        $in_days_end_time = $in_days_end_hour . '-' . $in_days_end_minutes . '-' . $in_days_end_ampm;
                    }

                    $in_days_times = ':' . $in_days_start_time . ':' . $in_days_end_time;
                }

                return $this->main->standardize_format($ex[0]) . ':' . $this->main->standardize_format($ex[1]) . $in_days_times;
            }, $in_days_arr);

            usort($in_days_arr, function ($a, $b)
            {
                $ex_a = explode(':', $a);
                $ex_b = explode(':', $b);

                $date_a = $ex_a[0];
                $date_b = $ex_b[0];

                $in_day_a_time_label = '';
                if (isset($ex_a[2]))
                {
                    $in_day_a_time = $ex_a[2];
                    $pos = strpos($in_day_a_time, '-');
                    if ($pos !== false) $in_day_a_time_label = substr_replace($in_day_a_time, ':', $pos, 1);

                    $in_day_a_time_label = str_replace('-', ' ', $in_day_a_time_label);
                }

                $in_day_b_time_label = '';
                if (isset($ex_b[2]))
                {
                    $in_day_b_time = $ex_b[2];
                    $pos = strpos($in_day_b_time, '-');
                    if ($pos !== false) $in_day_b_time_label = substr_replace($in_day_b_time, ':', $pos, 1);

                    $in_day_b_time_label = str_replace('-', ' ', $in_day_b_time_label);
                }

                return strtotime(trim($date_a . ' ' . $in_day_a_time_label)) - strtotime(trim($date_b . ' ' . $in_day_b_time_label));
            });

            if (!isset($in_days_arr[':i:'])) $in_days_arr[':i:'] = ':val:';
            foreach ($in_days_arr as $key => $in_day_arr)
            {
                if (is_numeric($key)) $in_days .= $in_day_arr . ',';
            }
        }

        $not_in_days = '';
        if (count($not_in_days_arr))
        {
            foreach ($not_in_days_arr as $key => $not_in_day_arr)
            {
                if (is_numeric($key)) $not_in_days .= $this->main->standardize_format($not_in_day_arr) . ',';
            }
        }

        $in_days = trim($in_days, ', ');
        $not_in_days = trim($not_in_days, ', ');

        update_post_meta($post_id, 'mec_in_days', $in_days);
        update_post_meta($post_id, 'mec_not_in_days', $not_in_days);

        // Repeat End Date
        if ($repeat_end == 'never') $repeat_end_date = '0000-00-00';
        else if ($repeat_end == 'date') $repeat_end_date = $repeat_end_at_date;
        else if ($repeat_end == 'occurrences')
        {
            if ($plus_date) $repeat_end_date = date('Y-m-d', strtotime($plus_date, strtotime($end_date)));
            else $repeat_end_date = '0000-00-00';
        }
        else $repeat_end_date = '0000-00-00';

        // If event is not repeating then set the end date of event correctly
        if (!$repeat_status or $repeat_type == 'custom_days') $repeat_end_date = $end_date;

        // Add parameters to the $event
        $event['end'] = $repeat_end_date;
        $event['year'] = $year;
        $event['month'] = $month;
        $event['day'] = $day;
        $event['week'] = $week;
        $event['weekday'] = $weekday;
        $event['weekdays'] = $weekdays;
        $event['days'] = $in_days;
        $event['not_in_days'] = $not_in_days;

        // Update MEC Events Table
        $mec_event_id = $this->db->select($this->db->prepare("SELECT `id` FROM `#__mec_events` WHERE `post_id` = %d", $post_id), 'loadResult');

        if (!$mec_event_id)
        {
            $q1 = "";
            $q2 = "";

            foreach ($event as $key => $value)
            {
                $q1 .= "`$key`,";

                if (is_null($value)) $q2 .= "NULL,";
                else $q2 .= "'$value',";
            }

            $this->db->q("INSERT INTO `#__mec_events` (" . trim($q1, ', ') . ") VALUES (" . trim($q2, ', ') . ")", 'INSERT');
        }
        else
        {
            $q = "";

            foreach ($event as $key => $value)
            {
                if (is_null($value)) $q .= "`$key`=NULL,";
                else $q .= "`$key`='$value',";
            }

            $this->db->q("UPDATE `#__mec_events` SET " . trim($q, ', ') . " WHERE `id`='$mec_event_id'");
        }

        // Update Schedule
        $schedule = $this->getSchedule();
        $schedule->reschedule($post_id, $schedule->get_reschedule_maximum($repeat_type));

        // Hourly Schedule
        if (!isset($this->settings['fes_section_hourly_schedule']) or (isset($this->settings['fes_section_hourly_schedule']) and $this->settings['fes_section_hourly_schedule']))
        {
            // Hourly Schedule Options
            $raw_hourly_schedules = $mec['hourly_schedules'] ?? [];
            unset($raw_hourly_schedules[':d:']);

            $hourly_schedules = [];
            foreach ($raw_hourly_schedules as $raw_hourly_schedule)
            {
                unset($raw_hourly_schedule['schedules'][':i:']);
                $hourly_schedules[] = $raw_hourly_schedule;
            }

            update_post_meta($post_id, 'mec_hourly_schedules', $hourly_schedules);
        }

        // Booking Options
        if (!isset($this->settings['fes_section_booking']) or (isset($this->settings['fes_section_booking']) and $this->settings['fes_section_booking']))
        {
            // Booking and Ticket Options
            $booking = $mec['booking'] ?? [];
            update_post_meta($post_id, 'mec_booking', $booking);

            // Tickets
            if (!isset($this->settings['fes_section_tickets']) or (isset($this->settings['fes_section_tickets']) and $this->settings['fes_section_tickets']))
            {
                $tickets = $mec['tickets'] ?? [];
                unset($tickets[':i:']);

                // Unset Ticket Dats
                if (count($tickets))
                {
                    $new_tickets = [];
                    foreach ($tickets as $key => $ticket)
                    {
                        unset($ticket['dates'][':j:']);

                        $ticket_start_time_ampm = ((intval($ticket['ticket_start_time_hour']) > 0 and intval($ticket['ticket_start_time_hour']) < 13) and isset($ticket['ticket_start_time_ampm'])) ? $ticket['ticket_start_time_ampm'] : '';
                        $ticket_render_start_time = date('h:ia', strtotime(sprintf('%02d', $ticket['ticket_start_time_hour']) . ':' . sprintf('%02d', $ticket['ticket_start_time_minute']) . $ticket_start_time_ampm));
                        $ticket_end_time_ampm = ((intval($ticket['ticket_end_time_hour']) > 0 and intval($ticket['ticket_end_time_hour']) < 13) and isset($ticket['ticket_end_time_ampm'])) ? $ticket['ticket_end_time_ampm'] : '';
                        $ticket_render_end_time = date('h:ia', strtotime(sprintf('%02d', $ticket['ticket_end_time_hour']) . ':' . sprintf('%02d', $ticket['ticket_end_time_minute']) . $ticket_end_time_ampm));

                        $ticket['ticket_start_time_hour'] = substr($ticket_render_start_time, 0, 2);
                        $ticket['ticket_start_time_ampm'] = strtoupper(substr($ticket_render_start_time, 5, 6));
                        $ticket['ticket_end_time_hour'] = substr($ticket_render_end_time, 0, 2);
                        $ticket['ticket_end_time_ampm'] = strtoupper(substr($ticket_render_end_time, 5, 6));
                        $ticket['price'] = trim($ticket['price']);
                        $ticket['limit'] = trim($ticket['limit']);
                        $ticket['minimum_ticket'] = trim($ticket['minimum_ticket']);
                        $ticket['stop_selling_value'] = trim($ticket['stop_selling_value']);

                        // Bellow conditional block code is used to change ticket dates format to compatible ticket past dates structure for store in db.
                        foreach ($ticket['dates'] as $dates_ticket_key => $dates_ticket_values)
                        {
                            if (isset($dates_ticket_values['start']) and trim($dates_ticket_values['start']))
                            {
                                $ticket['dates'][$dates_ticket_key]['start'] = $this->main->standardize_format($dates_ticket_values['start']);
                            }

                            if (isset($dates_ticket_values['end']) and trim($dates_ticket_values['end']))
                            {
                                $ticket['dates'][$dates_ticket_key]['end'] = $this->main->standardize_format($dates_ticket_values['end']);
                            }
                        }

                        $new_tickets[$key] = $ticket;
                    }

                    $tickets = $new_tickets;
                }

                update_post_meta($post_id, 'mec_tickets', $tickets);
                update_post_meta($post_id, 'mec_global_tickets_applied', 1);
            }

            // Fees
            if (!isset($this->settings['fes_section_fees']) or (isset($this->settings['fes_section_fees']) and $this->settings['fes_section_fees']))
            {
                // Fee options
                $fees_global_inheritance = isset($mec['fees_global_inheritance']) ? sanitize_text_field($mec['fees_global_inheritance']) : 1;
                update_post_meta($post_id, 'mec_fees_global_inheritance', $fees_global_inheritance);

                $fees = $mec['fees'] ?? [];
                update_post_meta($post_id, 'mec_fees', $fees);
            }

            // Variation
            if (!isset($this->settings['fes_section_ticket_variations']) or (isset($this->settings['fes_section_ticket_variations']) and $this->settings['fes_section_ticket_variations']))
            {
                // Ticket Variation options
                $ticket_variations_global_inheritance = isset($mec['ticket_variations_global_inheritance']) ? sanitize_text_field($mec['ticket_variations_global_inheritance']) : 1;
                update_post_meta($post_id, 'mec_ticket_variations_global_inheritance', $ticket_variations_global_inheritance);

                $ticket_variations = $mec['ticket_variations'] ?? [];
                update_post_meta($post_id, 'mec_ticket_variations', $ticket_variations);
            }

            // Booking Form
            if (!isset($this->settings['fes_section_reg_form']) or (isset($this->settings['fes_section_reg_form']) and $this->settings['fes_section_reg_form']))
            {
                // Registration Fields options
                $reg_fields_global_inheritance = isset($mec['reg_fields_global_inheritance']) ? sanitize_text_field($mec['reg_fields_global_inheritance']) : 1;
                update_post_meta($post_id, 'mec_reg_fields_global_inheritance', $reg_fields_global_inheritance);

                $reg_fields = $mec['reg_fields'] ?? [];
                if ($reg_fields_global_inheritance) $reg_fields = [];
                else
                {
                    $reg_fields = $this->main->sanitize_booking_paragraph_fields($reg_fields, $raw_mec['reg_fields'] ?? []);
                    $reg_fields = $this->main->sanitize_booking_condition_fields($reg_fields, 'reg');
                }

                // Trigger action for form builder compatibility
                do_action('mec_save_reg_fields', $post_id, $reg_fields);

                update_post_meta($post_id, 'mec_reg_fields', $reg_fields);

                $bfixed_fields = $mec['bfixed_fields'] ?? [];
                if ($reg_fields_global_inheritance) $bfixed_fields = [];
                else
                {
                    $bfixed_fields = $this->main->sanitize_booking_paragraph_fields($bfixed_fields, $raw_mec['bfixed_fields'] ?? []);
                    $bfixed_fields = $this->main->sanitize_booking_condition_fields($bfixed_fields, 'bfixed');
                }

                update_post_meta($post_id, 'mec_bfixed_fields', $bfixed_fields);
            }
        }

        // Organizer Payment Options
        $op = $mec['op'] ?? [];
        update_post_meta($post_id, 'mec_op', $op);
        update_user_meta(get_post_field('post_author', $post_id), 'mec_op', $op);

        // MEC Fields
        $fields = (isset($mec['fields']) and is_array($mec['fields'])) ? $mec['fields'] : [];
        update_post_meta($post_id, 'mec_fields', $fields);

        // Save fields one by one
        foreach ($fields as $field_id => $values)
        {
            if (is_array($values))
            {
                $values = array_unique($values);
                $values = implode(',', $values);
            }

            update_post_meta($post_id, 'mec_fields_' . $field_id, sanitize_text_field($values));
        }

        // Downloadable File
        if (isset($mec['downloadable_file']))
        {
            $dl_file = sanitize_text_field($mec['downloadable_file']);
            update_post_meta($post_id, 'mec_dl_file', $dl_file);
        }

        // Public Download Module File
        if (isset($mec['public_download_module_file']))
        {
            $public_dl_file = sanitize_text_field($mec['public_download_module_file']);
            update_post_meta($post_id, 'mec_public_dl_file', $public_dl_file);

            $public_dl_title = isset($mec['public_download_module_title']) ? sanitize_text_field($mec['public_download_module_title']) : '';
            update_post_meta($post_id, 'mec_public_dl_title', $public_dl_title);

            $public_dl_description = isset($mec['public_download_module_description']) ? sanitize_text_field($mec['public_download_module_description']) : '';
            update_post_meta($post_id, 'mec_public_dl_description', $public_dl_description);
        }

        // Event Gallery
        $gallery = isset($mec['event_gallery']) && is_array($mec['event_gallery']) ? $mec['event_gallery'] : [];
        update_post_meta($post_id, 'mec_event_gallery', $gallery);

        // Related Events
        $related_events = (isset($mec['related_events']) and is_array($mec['related_events'])) ? $mec['related_events'] : [];
        update_post_meta($post_id, 'mec_related_events', $related_events);

        // Event Banner
        $event_banner = (isset($mec['banner']) and is_array($mec['banner'])) ? $mec['banner'] : [];
        update_post_meta($post_id, 'mec_banner', $event_banner);

        // Event Dates Changed?
        if ($prev_start_datetime and $prev_end_datetime and !$repeat_status and $prev_start_datetime != $start_datetime and $prev_end_datetime != $end_datetime)
        {
            $this->main->event_date_updated($post_id, $prev_start_datetime, $prev_end_datetime);
        }

        // Appointments
        $this->getAppointments()->save($post_id, $mec);

        do_action('save_fes_meta_action', $post_id, $mec);

        // For Event Notification Badge.
        if (isset($_REQUEST['mec']['post_id']) and trim(sanitize_text_field($_REQUEST['mec']['post_id'])) == '-1') update_post_meta($post_id, 'mec_event_date_submit', date('YmdHis', current_time('timestamp', 0)));

        $message = '';
        if ($status == 'pending') $message = esc_html__('Event submitted. It will publish as soon as possible.', 'modern-events-calendar-lite');
        else if ($status == 'publish') $message = esc_html__('The event published.', 'modern-events-calendar-lite');

        // Trigger Event
        if ($method == 'updated') do_action('mec_fes_updated', $post_id, 'update');
        else do_action('mec_fes_added', $post_id, '');

        // Save Event Data
        do_action('mec_save_event_data', $post_id, $mec);

        $redirect_to = isset($this->settings['fes_thankyou_page']) && trim($this->settings['fes_thankyou_page']) ? get_permalink(intval($this->settings['fes_thankyou_page'])) : '';
        if (isset($this->settings['fes_thankyou_page_url']) and trim($this->settings['fes_thankyou_page_url'])) $redirect_to = esc_url($this->settings['fes_thankyou_page_url']);

        $this->main->response([
            'success' => 1,
            'message' => $message,
            'data' => [
                'post_id' => $post_id,
                'redirect_to' => $redirect_to,
            ],
        ]);
    }

    public function link_add_event()
    {
        if (!$this->relative_link and isset($this->settings['fes_form_page']) and trim($this->settings['fes_form_page'])) return get_permalink($this->settings['fes_form_page']);
        else return $this->main->add_qs_var('post_id', '-1', $this->main->remove_qs_var('vlist'));
    }

    public function link_edit_event($post_id)
    {
        if (!$this->relative_link and isset($this->settings['fes_form_page']) and trim($this->settings['fes_form_page'])) return $this->main->add_qs_var('post_id', $post_id, get_permalink($this->settings['fes_form_page']));
        else return $this->main->add_qs_var('post_id', $post_id, $this->main->remove_qs_var('vlist'));
    }

    public function link_list_events()
    {
        if (!$this->relative_link and isset($this->settings['fes_list_page']) and trim($this->settings['fes_list_page'])) return get_permalink($this->settings['fes_list_page']);
        else return $this->main->add_qs_var('vlist', 1, $this->main->remove_qs_var('post_id'));
    }

    /**
     * @param string $new_status
     * @param string $old_status
     * @param WP_Post $post
     */
    public function status_changed($new_status, $old_status, $post)
    {
        // User creation is not enabled
        if (!isset($this->settings['fes_guest_user_creation']) or (isset($this->settings['fes_guest_user_creation']) and !$this->settings['fes_guest_user_creation'])) return;

        if (('publish' === $new_status && 'publish' !== $old_status) && $this->PT === $post->post_type)
        {
            $guest_email = get_post_meta($post->ID, 'fes_guest_email', true);
            if (!trim($guest_email) || !is_email($guest_email)) return;

            $user_id = 0;
            $user_exists = email_exists($guest_email);

            if ($user_exists and $user_exists == $post->post_author) return;
            else if ($user_exists) $user_id = $user_exists;
            else
            {
                $registered = register_new_user($guest_email, $guest_email);
                if (!is_wp_error($registered))
                {
                    $user_id = $registered;

                    $guest_name = get_post_meta($post->ID, 'fes_guest_name', true);
                    $ex = explode(' ', $guest_name);

                    $first_name = $ex[0];
                    unset($ex[0]);

                    $last_name = implode(' ', $ex);

                    wp_update_user([
                        'ID' => $user_id,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                    ]);

                    $user = new WP_User($user_id);
                    $user->set_role('author');
                }
            }

            if ($user_id)
            {
                $db = $this->getDB();
                $db->q("UPDATE `#__posts` SET `post_author`='$user_id' WHERE `ID`='" . $post->ID . "'");
            }
        }
    }

    public function current_user_attachments($query = [])
    {
        $fes = $_REQUEST['mec_fes'] ?? 0;
        $user_id = get_current_user_id();

        if ($fes && $user_id && !current_user_can('manage_options'))
        {
            $query['author'] = $user_id;
        }

        return $query;
    }
}

// FES Categories Custom Walker
class FES_Custom_Walker extends Walker_Category
{
    /**
     * This class is a custom walker for front end event submission hierarchical categories customizing
     */
    private $post_id;

    function __construct($post_id)
    {
        $this->post_id = $post_id;
    }

    function start_lvl(&$output, $depth = 0, $args = [])
    {
        $indent = str_repeat("\t", $depth);
        $output .= "$indent<div class='mec-fes-category-children'>";
    }

    function end_lvl(&$output, $depth = 0, $args = [])
    {
        $indent = str_repeat("\t", $depth);
        $output .= "$indent</div>";
    }

    function start_el(&$output, $data_object, $depth = 0, $args = [], $current_object_id = 0)
    {
        $post_categories = get_the_terms($this->post_id, 'mec_category');

        $categories = [];
        if ($post_categories) foreach ($post_categories as $post_category) $categories[] = $post_category->term_id;

        $output .= '<label for="mec_fes_categories' . esc_attr($data_object->term_id) . '">
        <input type="checkbox" name="mec[categories][' . esc_attr($data_object->term_id) . ']"
        id="mec_fes_categories' . esc_attr($data_object->term_id) . '" value="1"' . (in_array($data_object->term_id, $categories) ? 'checked="checked"' : '') . '/>' . esc_html($data_object->name);
    }

    function end_el(&$output, $data_object, $depth = 0, $args = [])
    {
        $output .= '</label>';
    }
}

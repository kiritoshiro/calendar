<?php
/** no direct access **/
defined('MECEXEC') or die();

class MEC_feature_attendees extends MEC_base
{
    public $factory;
    public $main;
    public $db;
    public $settings;
    public $PT;

    public function __construct()
    {
        $this->factory = $this->getFactory();
        $this->main = $this->getMain();
        $this->db = $this->getDB();
        $this->settings = $this->main->get_settings();
        $this->PT = $this->main->get_main_post_type();
    }

    public function init()
    {
        if (!$this->getPRO()) return false;
        if (!isset($this->settings['booking_status']) || !$this->settings['booking_status']) return false;

        $this->factory->action('admin_menu', [$this, 'menu'], 30);
        $this->factory->action('admin_init', [$this, 'maybe_export_attendees'], 1);
        $this->factory->action('admin_head', [$this, 'hide_menu_item']);
        $this->factory->action('admin_notices', [$this, 'admin_notices']);
        $this->factory->action('current_screen', [$this, 'setup_screen_options']);
        $this->factory->filter('submenu_file', [$this, 'submenu_file']);
        $this->factory->filter('parent_file', [$this, 'parent_file']);
        $this->factory->filter('admin_body_class', [$this, 'admin_body_class']);
        $this->factory->filter('set-screen-option', [$this, 'set_screen_option'], 10, 3);

        return true;
    }

    public function menu()
    {
        $capability = 'read';
        $edit_parent_slug = 'edit.php?post_type=' . $this->PT;
        $menu_parent_slug = 'mec-intro';

        add_submenu_page(
            $edit_parent_slug,
            esc_html__('Attendees', 'modern-events-calendar-lite'),
            esc_html__('Attendees', 'modern-events-calendar-lite'),
            $capability,
            'mec-attendees',
            [$this, 'page']
        );

        add_submenu_page(
            $menu_parent_slug,
            esc_html__('Attendees', 'modern-events-calendar-lite'),
            esc_html__('Attendees', 'modern-events-calendar-lite'),
            $capability,
            'mec-attendees',
            [$this, 'page']
        );
    }

    public function admin_notices()
    {
        if (!$this->is_event_list_page()) return;

        $error = isset($_GET['mec_attendees_error']) ? sanitize_text_field($_GET['mec_attendees_error']) : '';
        if (!$error) return;

        $message = '';

        if ($error === 'invalid_event')
        {
            $message = esc_html__('The requested event is invalid.', 'modern-events-calendar-lite');
        }
        elseif ($error === 'no_access')
        {
            $message = esc_html__('You do not have permission to access attendees for that event.', 'modern-events-calendar-lite');
        }

        if ($message === '') return;

        echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
    }

    public function hide_menu_item()
    {
        ?>
        <style>
            #adminmenu .wp-submenu li:has(> a[href*="page=mec-attendees"]) {
                display: none !important;
            }
        </style>
        <?php
    }

    public function submenu_file($submenu_file)
    {
        if ($this->is_attendees_page()) return 'mec-attendees';
        return $submenu_file;
    }

    public function parent_file($parent_file)
    {
        if ($this->is_attendees_page()) return 'mec-intro';
        return $parent_file;
    }

    public function admin_body_class($classes)
    {
        if ($this->is_attendees_page()) $classes .= ' mec-attendees-page';
        return $classes;
    }

    public function setup_screen_options($screen)
    {
        if (!$this->is_attendees_page()) return;
        if (!is_a($screen, '\WP_Screen') && function_exists('get_current_screen')) $screen = get_current_screen();
        if (!is_a($screen, '\WP_Screen')) return;

        add_screen_option('per_page', [
            'label' => esc_html__('Number of items per page', 'modern-events-calendar-lite'),
            'default' => 20,
            'option' => 'mec_attendees_per_page',
        ]);
    }

    public function set_screen_option($status, $option, $value)
    {
        if ($option !== 'mec_attendees_per_page') return $status;

        $value = (int) $value;
        if ($value < 1) $value = 20;
        if ($value > 999) $value = 999;

        return $value;
    }

    public function page()
    {
        if (!$this->can_access_page())
        {
            $this->redirect_to_events_list('no_access');
        }

        $event = $this->get_event_from_request();
        if (!$event)
        {
            $this->redirect_to_events_list('invalid_event');
        }

        $occurrence_context = $this->get_occurrence_context($event->ID, isset($_REQUEST['occurrence']) ? sanitize_text_field($_REQUEST['occurrence']) : '');
        $canonical_url = $occurrence_context['redirect'];

        $occurrence = $occurrence_context['occurrence'];
        $search_term = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $status_filter = $this->get_request_status_filter();
        $occurrence_options = $this->get_occurrence_options($event->ID, $occurrence, $occurrence_context);
        $occurrence_navigation = $this->get_occurrence_navigation($event->ID, $occurrence, $search_term, $status_filter);
        $summary = $this->get_summary_data($event, $occurrence, $occurrence_context);

        $list_table = new MEC_Attendees_List_Table($this, $event->ID, $occurrence, $search_term, $status_filter);
        $list_table->prepare_items();

        $form_action = trim($this->main->URL('admin'), '/ ') . '/edit.php';
        $events_url = $this->main->add_qs_vars([
            'post_type' => $this->PT,
        ], $form_action);

        ?>
        <div class="wrap mec-attendees-page-wrap">
            <p><a class="button left-arrow no-print" href="<?php echo esc_url($events_url); ?>"><?php esc_html_e('All Events', 'modern-events-calendar-lite'); ?></a></p>
            <div class="mec-attendees-page-wrap-top">
                <h1 class="wp-heading-inline"><?php echo esc_html(sprintf(__('Attendees for: %s [#%d]', 'modern-events-calendar-lite'), get_the_title($event), $event->ID)); ?></h1>
                <?php if (!empty($occurrence_navigation['previous'])): ?>
                    <a class="button left-arrow no-print" href="<?php echo esc_url($occurrence_navigation['previous']['url']); ?>"><?php esc_html_e('Previous', 'modern-events-calendar-lite'); ?></a>
                <?php endif; ?>
                <?php if (count($occurrence_options)): ?>
                    <form method="get" action="<?php echo esc_url($form_action); ?>" class="no-print">
                        <input type="hidden" name="post_type" value="<?php echo esc_attr($this->PT); ?>">
                        <input type="hidden" name="page" value="mec-attendees">
                        <input type="hidden" name="event_id" value="<?php echo esc_attr($event->ID); ?>">
                        <?php if ($status_filter !== ''): ?>
                            <input type="hidden" name="status_filter" value="<?php echo esc_attr($status_filter); ?>">
                        <?php endif; ?>
                        <?php if ($search_term !== ''): ?>
                            <input type="hidden" name="s" value="<?php echo esc_attr($search_term); ?>">
                        <?php endif; ?>
                        <select name="occurrence" onchange="this.form.submit()">
                            <?php foreach ($occurrence_options as $option): ?>
                                <option value="<?php echo esc_attr($option['value']); ?>" <?php selected((string) $option['value'], (string) $occurrence); ?>>
                                    <?php echo esc_html($option['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>
                <?php if (!empty($occurrence_navigation['next'])): ?>
                    <a class="button right-arrow no-print" href="<?php echo esc_url($occurrence_navigation['next']['url']); ?>"><?php esc_html_e('Next', 'modern-events-calendar-lite'); ?></a>
                <?php endif; ?>
            </div>
            <hr class="wp-header-end">

            <div class="mec-attendees-summary">
                <div class="postbox">
                    <div class="inside">
                        <div class="mec-attendees-summary-inside">
                            <div class="mec-attendees-summary-columns">
                                <h2><?php esc_html_e('Event Details', 'modern-events-calendar-lite'); ?></h2>
                                <p><strong><?php esc_html_e('Event Date', 'modern-events-calendar-lite'); ?>:</strong> <?php echo esc_html($summary['selected_date']); ?></p>
                                <p><strong><?php esc_html_e('Post type', 'modern-events-calendar-lite'); ?>:</strong> <?php echo esc_html($this->PT); ?></p>
                                <p class="no-print">
                                    <a href="<?php echo esc_url(get_edit_post_link($event->ID)); ?>"><?php esc_html_e('Edit Event', 'modern-events-calendar-lite'); ?></a>
                                    |
                                    <a href="<?php echo esc_url(get_permalink($event->ID)); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View Event', 'modern-events-calendar-lite'); ?></a>
                                </p>
                            </div>
                            <div class="mec-attendees-summary-columns">
                                <h2><?php esc_html_e('Ticket Overview', 'modern-events-calendar-lite'); ?></h2>
                                <?php if (count($summary['ticket_overview'])): ?>
                                    <table class="widefat striped">
                                        <tbody>
                                        <?php foreach ($summary['ticket_overview'] as $ticket): ?>
                                            <tr>
                                                <td><?php echo esc_html($ticket['label']); ?></td>
                                                <td><?php echo esc_html(sprintf(_n('%d ticket', '%d tickets', $ticket['count'], 'modern-events-calendar-lite'), $ticket['count'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr>
                                            <th><?php esc_html_e('Total', 'modern-events-calendar-lite'); ?></th>
                                            <th><?php echo esc_html(sprintf(_n('%d ticket', '%d tickets', $summary['ticket_total'], 'modern-events-calendar-lite'), $summary['ticket_total'])); ?></th>
                                        </tr>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p><?php esc_html_e('No tickets found for this occurrence.', 'modern-events-calendar-lite'); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="mec-attendees-summary-columns">
                                <h2><?php esc_html_e('Attendance Overview', 'modern-events-calendar-lite'); ?></h2>
                                <table class="widefat striped">
                                    <tbody>
                                    <tr>
                                        <td><?php esc_html_e('Attendees', 'modern-events-calendar-lite'); ?></td>
                                        <td><?php echo esc_html($summary['attendee_total']); ?></td>
                                    </tr>
                                    <?php foreach ($summary['confirmation_counts'] as $label => $count): ?>
                                        <tr>
                                            <td><?php echo esc_html($label); ?></td>
                                            <td><?php echo esc_html($count); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php foreach ($summary['verification_counts'] as $label => $count): ?>
                                        <tr>
                                            <td><?php echo esc_html($label); ?></td>
                                            <td><?php echo esc_html($count); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if ($summary['has_checkin']): ?>
                                        <tr>
                                            <td><?php esc_html_e('Checked in', 'modern-events-calendar-lite'); ?></td>
                                            <td><?php echo esc_html($summary['checked_in_total']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mec-attendees-filters no-print">
                <?php foreach ($summary['filter_chips'] as $chip): ?>
                    <a
                        href="<?php echo esc_url($this->build_attendees_url($event->ID, $occurrence, [
                            'status_filter' => $chip['key'] === 'all' ? null : $chip['key'],
                            's' => $search_term !== '' ? $search_term : null,
                        ])); ?>"
                        class="button<?php echo $status_filter === $chip['key'] || ($status_filter === '' && $chip['key'] === 'all') ? ' button-primary' : ''; ?>"
                    ><?php echo esc_html(sprintf('%s (%d)', $chip['label'], $chip['count'])); ?></a>
                <?php endforeach; ?>
            </div>

            <form method="get" action="<?php echo esc_url($form_action); ?>">
                <input type="hidden" name="post_type" value="<?php echo esc_attr($this->PT); ?>">
                <input type="hidden" name="page" value="mec-attendees">
                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->ID); ?>">
                <input type="hidden" name="occurrence" value="<?php echo esc_attr($occurrence); ?>">
                <?php if ($status_filter !== ''): ?>
                    <input type="hidden" name="status_filter" value="<?php echo esc_attr($status_filter); ?>">
                <?php endif; ?>
                <?php wp_nonce_field('bulk-attendees'); ?>
                <?php $list_table->search_box(esc_html__('Search attendees', 'modern-events-calendar-lite'), 'mec-attendees-search'); ?>
                <?php $list_table->display(); ?>
            </form>
        </div>

        <style>
            @media print {
                #adminmenumain,
                #wpadminbar,
                #screen-meta-links,
                #wpfooter,
                .notice,
                .search-box,
                .tablenav,
                .check-column {
                    display: none !important;
                }

                html.wp-toolbar {
                    padding-top: 0 !important;
                }

                #wpcontent,
                #wpbody-content {
                    margin: 0 !important;
                    padding: 0 !important;
                }
            }
        </style>

        <script>
            jQuery(document).ready(function ($) {
                <?php if ($canonical_url): ?>
                if (window.history && typeof window.history.replaceState === 'function')
                {
                    window.history.replaceState({}, document.title, <?php echo wp_json_encode($canonical_url); ?>);
                }
                <?php endif; ?>

                $(document).on('click', '.mec-attendees-print-button', function (event) {
                    event.preventDefault();
                    window.print();
                });
            });
        </script>
        <?php
    }

    public function maybe_export_attendees()
    {
        if (!$this->is_attendees_page() || !$this->can_access_page()) return;

        $export_all = isset($_REQUEST['mec_export_all']) ? sanitize_text_field(wp_unslash($_REQUEST['mec_export_all'])) : '';
        if (in_array($export_all, ['csv-export', 'ms-excel-export'], true))
        {
            check_admin_referer('bulk-attendees');

            $event = $this->get_event_from_request();
            if (!$event) return;

            $occurrence = isset($_REQUEST['occurrence']) ? sanitize_text_field(wp_unslash($_REQUEST['occurrence'])) : '';
            $search_term = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '';
            $status_filter = $this->get_request_status_filter();
            $selected = $this->get_filtered_attendee_row_ids($event->ID, $occurrence, $search_term, $status_filter);

            if (!count($selected)) return;

            $this->export_attendees($export_all, $selected);
            return;
        }

        $action = $this->get_request_bulk_action();
        if (!in_array($action, ['csv-export', 'ms-excel-export'], true)) return;

        check_admin_referer('bulk-attendees');

        $selected = isset($_REQUEST['attendee']) && is_array($_REQUEST['attendee']) ? array_map('sanitize_text_field', wp_unslash($_REQUEST['attendee'])) : [];
        if (!count($selected)) return;

        $this->export_attendees($action, $selected);
    }

    public function get_attendee_items($event_id, $occurrence)
    {
        $bookings = $this->main->get_bookings($event_id, $occurrence, '-1', null, false);
        $tickets = get_post_meta($event_id, 'mec_tickets', true);
        if (!is_array($tickets)) $tickets = [];

        $items = [];

        foreach ($bookings as $booking)
        {
            $attendees = get_post_meta($booking->ID, 'mec_attendees', true);
            if (!is_array($attendees) || !count($attendees)) $attendees = [get_post_meta($booking->ID, 'mec_attendee', true)];

            $confirmation_label = $this->main->get_confirmation_label(get_post_meta($booking->ID, 'mec_confirmed', true));
            $verification_label = $this->main->get_verification_label(get_post_meta($booking->ID, 'mec_verified', true));
            $transaction_id = get_post_meta($booking->ID, 'mec_transaction_id', true);

            $position = 0;
            foreach ($attendees as $key => $attendee)
            {
                if ($key === 'attachments') continue;
                if (isset($attendee[0]['MEC_TYPE_OF_DATA'])) continue;
                if (!is_array($attendee)) continue;

                $position++;

                $ticket_id = $attendee['id'] ?? 0;
                $ticket_name = $tickets[$ticket_id]['name'] ?? esc_html__('Unknown', 'modern-events-calendar-lite');
                $seats = (isset($tickets[$ticket_id]['seats']) && is_numeric($tickets[$ticket_id]['seats']) && (int) $tickets[$ticket_id]['seats'] > 0) ? (int) $tickets[$ticket_id]['seats'] : 1;
                $checked_in = $this->is_attendee_checked_in($booking->ID, $attendee, $position);

                $items[] = [
                    'row_id' => $booking->ID . ':' . $key,
                    'booking_id' => $booking->ID,
                    'attendee_key' => (string) $key,
                    'name' => trim((string) ($attendee['name'] ?? '')),
                    'email' => trim((string) ($attendee['email'] ?? '')),
                    'ticket_id' => $ticket_id,
                    'ticket_name' => $ticket_name,
                    'seats' => $seats,
                    'transaction_id' => $transaction_id,
                    'confirmation_status' => (int) get_post_meta($booking->ID, 'mec_confirmed', true),
                    'verification_status' => (int) get_post_meta($booking->ID, 'mec_verified', true),
                    'confirmation_label' => $confirmation_label,
                    'verification_label' => $verification_label,
                    'checked_in' => $checked_in,
                ];
            }
        }

        return $items;
    }

    public function export_attendees($action, array $selected_rows)
    {
        $filters = $this->normalize_selected_attendees($selected_rows);
        if (!count($filters)) return;

        $bookings_feature = new MEC_feature_books();
        $rows = $bookings_feature->csvexcel(array_keys($filters), $filters);

        if ($action === 'ms-excel-export')
        {
            $filename = 'attendees-' . md5(time() . mt_rand(100, 999)) . '.xlsx';
            $this->main->generate_download_excel($rows, $filename);
            exit;
        }

        $filename = 'attendees-' . md5(time() . mt_rand(100, 999)) . '.csv';
        $this->main->generate_download_csv($rows, $filename);
        exit;
    }

    public function filter_attendee_items(array $items, $search_term = '', $status_filter = '')
    {
        if ($status_filter !== '')
        {
            $items = array_values(array_filter($items, function ($item) use ($status_filter)
            {
                return $this->item_matches_status_filter($item, $status_filter);
            }));
        }

        if ($search_term !== '')
        {
            $items = array_values(array_filter($items, function ($item) use ($search_term)
            {
                return (stripos($item['name'], $search_term) !== false) || (stripos($item['email'], $search_term) !== false);
            }));
        }

        return $items;
    }

    public function get_summary_data($event, $occurrence, array $occurrence_context)
    {
        $ticket_overview = [];
        $ticket_total = 0;
        $attendee_total = 0;
        $checked_in_total = 0;
        $has_checkin = class_exists('\MEC_Invoice\Attendee');

        $confirmation_counts = [
            $this->main->get_confirmation_label(1) => 0,
            $this->main->get_confirmation_label(0) => 0,
            $this->main->get_confirmation_label(-1) => 0,
        ];
        $verification_counts = [
            $this->main->get_verification_label(1) => 0,
            $this->main->get_verification_label(0) => 0,
            $this->main->get_verification_label(-1) => 0,
        ];

        $tickets = get_post_meta($event->ID, 'mec_tickets', true);
        if (!is_array($tickets)) $tickets = [];

        foreach ($tickets as $ticket_id => $ticket)
        {
            if (!is_numeric($ticket_id)) continue;

            $ticket_overview[$ticket_id] = [
                'label' => $ticket['name'] ?? sprintf(esc_html__('Ticket %s', 'modern-events-calendar-lite'), $ticket_id),
                'count' => 0,
            ];
        }

        $bookings = $this->main->get_bookings($event->ID, $occurrence, '-1', null, false);
        foreach ($bookings as $booking)
        {
            $attendees = get_post_meta($booking->ID, 'mec_attendees', true);
            if (!is_array($attendees) || !count($attendees)) $attendees = [get_post_meta($booking->ID, 'mec_attendee', true)];

            $confirmed = get_post_meta($booking->ID, 'mec_confirmed', true);
            $verified = get_post_meta($booking->ID, 'mec_verified', true);

            $confirmation_label = $this->main->get_confirmation_label($confirmed);
            $verification_label = $this->main->get_verification_label($verified);

            if (!isset($confirmation_counts[$confirmation_label])) $confirmation_counts[$confirmation_label] = 0;
            if (!isset($verification_counts[$verification_label])) $verification_counts[$verification_label] = 0;

            $position = 0;
            foreach ($attendees as $key => $attendee)
            {
                if ($key === 'attachments') continue;
                if (isset($attendee[0]['MEC_TYPE_OF_DATA'])) continue;
                if (!is_array($attendee)) continue;

                $position++;
                $ticket_id = $attendee['id'] ?? 0;
                $seats = 1;
                if ($ticket_id && isset($tickets[$ticket_id]['seats']) && $tickets[$ticket_id]['seats']) $seats = (int) $tickets[$ticket_id]['seats'];

                $attendee_total += $seats;
                $confirmation_counts[$confirmation_label] += $seats;
                $verification_counts[$verification_label] += $seats;

                if (!isset($ticket_overview[$ticket_id]))
                {
                    $ticket_overview[$ticket_id] = [
                        'label' => $tickets[$ticket_id]['name'] ?? esc_html__('Unknown', 'modern-events-calendar-lite'),
                        'count' => 0,
                    ];
                }

                $ticket_overview[$ticket_id]['count'] += $seats;
                $ticket_total += $seats;

                if ($has_checkin && $this->is_attendee_checked_in($booking->ID, $attendee, $position)) $checked_in_total += $seats;
            }
        }

        $selected_date = $occurrence_context['label'];
        if ($selected_date === '') $selected_date = esc_html__('Please select a date', 'modern-events-calendar-lite');

        return [
            'selected_date' => $selected_date,
            'ticket_overview' => array_values($ticket_overview),
            'ticket_total' => $ticket_total,
            'attendee_total' => $attendee_total,
            'confirmation_counts' => $confirmation_counts,
            'verification_counts' => $verification_counts,
            'checked_in_total' => $checked_in_total,
            'has_checkin' => $has_checkin,
            'filter_chips' => [
                ['key' => 'all', 'label' => esc_html__('All', 'modern-events-calendar-lite'), 'count' => $attendee_total],
                ['key' => 'confirmed', 'label' => $this->main->get_confirmation_label(1), 'count' => (int) ($confirmation_counts[$this->main->get_confirmation_label(1)] ?? 0)],
                ['key' => 'pending', 'label' => $this->main->get_confirmation_label(0), 'count' => (int) ($confirmation_counts[$this->main->get_confirmation_label(0)] ?? 0)],
                ['key' => 'rejected', 'label' => $this->main->get_confirmation_label(-1), 'count' => (int) ($confirmation_counts[$this->main->get_confirmation_label(-1)] ?? 0)],
                ['key' => 'verified', 'label' => $this->main->get_verification_label(1), 'count' => (int) ($verification_counts[$this->main->get_verification_label(1)] ?? 0)],
                ['key' => 'waiting', 'label' => $this->main->get_verification_label(0), 'count' => (int) ($verification_counts[$this->main->get_verification_label(0)] ?? 0)],
                ['key' => 'canceled', 'label' => $this->main->get_verification_label(-1), 'count' => (int) ($verification_counts[$this->main->get_verification_label(-1)] ?? 0)],
            ],
        ];
    }

    public function get_occurrence_context($event_id, $requested_occurrence = '')
    {
        $event_id = (int) $event_id;
        $requested_occurrence = trim((string) $requested_occurrence);

        $rows = $this->get_occurrence_rows($event_id);

        $selected = null;
        $requested_found = false;

        if ($requested_occurrence !== '' && is_numeric($requested_occurrence))
        {
            foreach ($rows as $row)
            {
                if ((string) $row['tstart'] === $requested_occurrence)
                {
                    $selected = $row;
                    $requested_found = true;
                    break;
                }
            }
        }

        if (!$selected && count($rows))
        {
            $now = current_time('timestamp', 0);

            foreach ($rows as $row)
            {
                if ((int) $row['tstart'] >= $now)
                {
                    $selected = $row;
                    break;
                }
            }

            if (!$selected) $selected = end($rows);
        }

        if (!$selected)
        {
            $start = strtotime((string) get_post_meta($event_id, 'mec_start_datetime', true));
            $end = strtotime((string) get_post_meta($event_id, 'mec_end_datetime', true));

            if ($start)
            {
                $selected = [
                    'tstart' => $start,
                    'tend' => ($end ?: $start),
                ];
            }
        }

        $occurrence = $selected['tstart'] ?? '';
        $label = '';

        if ($selected && isset($selected['tstart'], $selected['tend']))
        {
            $label = $this->main->date_i18n(get_option('date_format') . ' ' . get_option('time_format'), (int) $selected['tstart']);
            if ((int) $selected['tend'] && (int) $selected['tend'] !== (int) $selected['tstart'])
            {
                $label .= ' - ' . $this->main->date_i18n(get_option('date_format') . ' ' . get_option('time_format'), (int) $selected['tend']);
            }
        }

        $redirect = '';
        if ($occurrence !== '' && ($requested_occurrence === '' || !$requested_found))
        {
            $redirect = $this->main->add_qs_vars([
                'post_type' => $this->PT,
                'page' => 'mec-attendees',
                'event_id' => $event_id,
                'occurrence' => $occurrence,
            ], trim($this->main->URL('admin'), '/ ') . '/edit.php');
        }

        return [
            'occurrence' => $occurrence,
            'start' => $selected['tstart'] ?? '',
            'end' => $selected['tend'] ?? '',
            'label' => $label,
            'redirect' => $redirect,
        ];
    }

    public function get_occurrence_options($event_id, $selected_occurrence, array $occurrence_context)
    {
        $rows = $this->get_occurrence_rows($event_id);
        $selected_occurrence = (string) $selected_occurrence;

        if (!count($rows))
        {
            if (!$selected_occurrence) return [];

            return [[
                'value' => $selected_occurrence,
                'label' => $occurrence_context['label'] ?: $selected_occurrence,
            ]];
        }

        $selected_index = 0;
        foreach ($rows as $index => $row)
        {
            if ((string) $row['tstart'] === $selected_occurrence)
            {
                $selected_index = $index;
                break;
            }
        }

        $start = max(0, $selected_index - 12);
        $length = min(count($rows) - $start, 25);
        $window = array_slice($rows, $start, $length);

        $options = [];
        foreach ($window as $row)
        {
            $options[] = [
                'value' => $row['tstart'],
                'label' => $this->format_occurrence_label($row),
            ];
        }

        return $options;
    }

    public function get_occurrence_navigation($event_id, $selected_occurrence, $search_term = '', $status_filter = '')
    {
        $rows = $this->get_occurrence_rows($event_id);
        $selected_occurrence = (string) $selected_occurrence;
        $selected_index = null;

        foreach ($rows as $index => $row)
        {
            if ((string) $row['tstart'] === $selected_occurrence)
            {
                $selected_index = $index;
                break;
            }
        }

        if ($selected_index === null) return ['previous' => null, 'next' => null];

        $extra = [];
        if ($search_term !== '') $extra['s'] = $search_term;
        if ($status_filter !== '') $extra['status_filter'] = $status_filter;

        return [
            'previous' => $selected_index > 0 ? [
                'url' => $this->build_attendees_url($event_id, $rows[$selected_index - 1]['tstart'], $extra),
            ] : null,
            'next' => isset($rows[$selected_index + 1]) ? [
                'url' => $this->build_attendees_url($event_id, $rows[$selected_index + 1]['tstart'], $extra),
            ] : null,
        ];
    }

    public function item_matches_status_filter(array $item, $status_filter)
    {
        switch ($status_filter)
        {
            case 'confirmed': return (int) $item['confirmation_status'] === 1;
            case 'pending': return (int) $item['confirmation_status'] === 0;
            case 'rejected': return (int) $item['confirmation_status'] === -1;
            case 'verified': return (int) $item['verification_status'] === 1;
            case 'waiting': return (int) $item['verification_status'] === 0;
            case 'canceled': return (int) $item['verification_status'] === -1;
            default: return true;
        }
    }

    private function get_event_from_request()
    {
        $event_id = isset($_REQUEST['event_id']) ? (int) sanitize_text_field($_REQUEST['event_id']) : 0;
        if (!$event_id) return false;

        $event = get_post($event_id);
        if (!$event || $event->post_type !== $this->PT) return false;
        if (!current_user_can('edit_post', $event_id)) return false;
        if (!$this->can_access_page()) return false;

        return $event;
    }

    private function can_access_page()
    {
        return current_user_can('manage_options') || current_user_can('mec_bookings') || current_user_can('edit_others_posts');
    }

    private function redirect_to_events_list($error = '')
    {
        $url = $this->main->add_qs_vars([
            'post_type' => $this->PT,
            'mec_attendees_error' => $error,
        ], trim($this->main->URL('admin'), '/ ') . '/edit.php');

        wp_safe_redirect($url);
        exit;
    }

    private function is_event_list_page()
    {
        return is_admin() && isset($_GET['post_type']) && sanitize_text_field($_GET['post_type']) === $this->PT && !isset($_GET['page']);
    }

    private function is_attendees_page()
    {
        return is_admin() && isset($_GET['post_type'], $_GET['page']) && sanitize_text_field($_GET['post_type']) === $this->PT && sanitize_text_field($_GET['page']) === 'mec-attendees';
    }

    private function get_request_status_filter()
    {
        $status_filter = isset($_REQUEST['status_filter']) ? sanitize_text_field(wp_unslash($_REQUEST['status_filter'])) : '';
        if (!in_array($status_filter, ['confirmed', 'pending', 'rejected', 'verified', 'waiting', 'canceled'], true)) return '';

        return $status_filter;
    }

    private function get_request_bulk_action()
    {
        $action = isset($_REQUEST['action']) ? sanitize_text_field(wp_unslash($_REQUEST['action'])) : '-1';
        if ($action && $action !== '-1') return $action;

        $action2 = isset($_REQUEST['action2']) ? sanitize_text_field(wp_unslash($_REQUEST['action2'])) : '-1';
        if ($action2 && $action2 !== '-1') return $action2;

        return '';
    }

    private function build_attendees_url($event_id, $occurrence, array $args = [])
    {
        $query = [
            'post_type' => $this->PT,
            'page' => 'mec-attendees',
            'event_id' => (int) $event_id,
        ];

        if ($occurrence !== null && $occurrence !== '') $query['occurrence'] = $occurrence;

        foreach ($args as $key => $value)
        {
            if ($value === null || $value === '') continue;
            $query[$key] = $value;
        }

        return $this->main->add_qs_vars($query, trim($this->main->URL('admin'), '/ ') . '/edit.php');
    }

    private function get_occurrence_rows($event_id)
    {
        $rows = $this->db->select("SELECT `tstart`, `tend` FROM `#__mec_dates` WHERE `post_id`='" . esc_sql((int) $event_id) . "' ORDER BY `tstart` ASC", 'loadAssocList');
        if (!is_array($rows)) return [];

        return $rows;
    }

    private function format_occurrence_label(array $row)
    {
        if (!isset($row['tstart'])) return '';

        $end = isset($row['tend']) && (int) $row['tend'] ? (int) $row['tend'] : (int) $row['tstart'];
        $label = $this->main->date_label(
            ['date' => date('Y-m-d H:i:s', (int) $row['tstart'])],
            ['date' => date('Y-m-d H:i:s', $end)],
            get_option('date_format') . ' ' . get_option('time_format'),
            ' - ',
            true,
            0,
            null,
            true
        );

        return trim(wp_strip_all_tags($label));
    }

    private function normalize_selected_attendees(array $selected_rows)
    {
        $filters = [];

        foreach ($selected_rows as $row_id)
        {
            $parts = explode(':', $row_id);
            $booking_id = isset($parts[0]) ? (int) $parts[0] : 0;
            $attendee_key = isset($parts[1]) ? sanitize_text_field($parts[1]) : '';

            if (!$booking_id || $attendee_key === '') continue;

            if (!isset($filters[$booking_id])) $filters[$booking_id] = [];
            if (!in_array($attendee_key, $filters[$booking_id], true)) $filters[$booking_id][] = $attendee_key;
        }

        return $filters;
    }

    private function get_filtered_attendee_row_ids($event_id, $occurrence, $search_term = '', $status_filter = '')
    {
        $items = $this->get_attendee_items($event_id, $occurrence);
        $items = $this->filter_attendee_items($items, $search_term, $status_filter);

        return array_values(array_filter(array_map(function ($item)
        {
            return $item['row_id'] ?? '';
        }, $items)));
    }

    private function is_attendee_checked_in($booking_id, array $attendee, $position)
    {
        if (!class_exists('\MEC_Invoice\Attendee')) return false;

        $invoice_id = get_post_meta($booking_id, 'invoiceID', true);
        $email = $attendee['email'] ?? '';

        if (!$invoice_id || !$email) return false;

        return (bool) MEC_Invoice\Attendee::hasCheckedIn($invoice_id, $email, $position);
    }
}

if (!class_exists('WP_List_Table')) require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

class MEC_Attendees_List_Table extends WP_List_Table
{
    /**
     * @var MEC_feature_attendees
     */
    private $feature;

    /**
     * @var int
     */
    private $event_id;

    /**
     * @var int|string|null
     */
    private $occurrence;

    /**
     * @var string
     */
    private $search_term;

    /**
     * @var string
     */
    private $status_filter;

    public function __construct(MEC_feature_attendees $feature, $event_id, $occurrence, $search_term = '', $status_filter = '')
    {
        parent::__construct([
            'singular' => 'attendee',
            'plural' => 'attendees',
            'ajax' => false,
        ]);

        $this->feature = $feature;
        $this->event_id = (int) $event_id;
        $this->occurrence = $occurrence;
        $this->search_term = (string) $search_term;
        $this->status_filter = (string) $status_filter;
    }

    public function get_columns()
    {
        return [
            'cb' => '<input type="checkbox" />',
            'attendee_information' => esc_html__('Attendee Information', 'modern-events-calendar-lite'),
            'booking' => esc_html__('Booking', 'modern-events-calendar-lite'),
            'ticket' => esc_html($this->feature->main->m('ticket', esc_html__('Ticket', 'modern-events-calendar-lite'))),
            'seats' => esc_html__('Seats', 'modern-events-calendar-lite'),
            'status' => esc_html__('Status', 'modern-events-calendar-lite'),
        ];
    }

    protected function get_bulk_actions()
    {
        return [
            'csv-export' => esc_html__('CSV Export', 'modern-events-calendar-lite'),
            'ms-excel-export' => esc_html__('MS Excel Export', 'modern-events-calendar-lite'),
        ];
    }

    protected function column_cb($item)
    {
        return '<input type="checkbox" name="attendee[]" value="' . esc_attr($item['row_id']) . '" />';
    }

    public function column_attendee_information($item)
    {
        $email = trim($item['email']);
        $name = trim($item['name']);

        $output = '<strong>' . esc_html($name !== '' ? $name : esc_html__('Unknown', 'modern-events-calendar-lite')) . '</strong>';

        if ($email !== '')
        {
            $output .= '<br><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
        }

        return $output;
    }

    public function column_booking($item)
    {
        $booking_link = get_edit_post_link($item['booking_id']);
        $reference = sprintf(esc_html__('Booking #%s', 'modern-events-calendar-lite'), $item['booking_id']);

        $output = $booking_link ? '<a href="' . esc_url($booking_link) . '">' . esc_html($reference) . '</a>' : esc_html($reference);
        if ($item['transaction_id'])
        {
            $output .= '<br><span class="description">' . esc_html(sprintf(__('Transaction %s', 'modern-events-calendar-lite'), $item['transaction_id'])) . '</span>';
        }

        return $output;
    }

    public function column_ticket($item)
    {
        return esc_html($item['ticket_name']);
    }

    public function column_seats($item)
    {
        $seats = isset($item['seats']) ? (int) $item['seats'] : 1;
        if ($seats < 1) $seats = 1;

        return esc_html(sprintf(_n('%d seat', '%d seats', $seats, 'modern-events-calendar-lite'), $seats));
    }

    public function column_status($item)
    {
        $parts = [];

        if ($item['confirmation_label']) $parts[] = $item['confirmation_label'];
        if ($item['verification_label']) $parts[] = $item['verification_label'];

        $status = implode(' / ', $parts);
        if ($status === '') $status = esc_html__('Unknown', 'modern-events-calendar-lite');

        return esc_html($status);
    }

    public function no_items()
    {
        esc_html_e('No attendees found.', 'modern-events-calendar-lite');
    }

    protected function extra_tablenav($which)
    {
        if (!$this->has_items()) return;

        echo '<button type="button" class="button mec-attendees-print-button" data-which="' . esc_attr($which) . '">' . esc_html__('Print', 'modern-events-calendar-lite') . '</button>';
        if ($which === 'top')
        {
            echo ' <button type="submit" class="button" name="mec_export_all" value="csv-export">' . esc_html__('Export All CSV', 'modern-events-calendar-lite') . '</button>';
            echo ' <button type="submit" class="button" name="mec_export_all" value="ms-excel-export">' . esc_html__('Export All Excel', 'modern-events-calendar-lite') . '</button>';
        }
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = [];

        $this->_column_headers = [$columns, $hidden, $sortable];

        $items = $this->feature->get_attendee_items($this->event_id, $this->occurrence);

        $items = $this->feature->filter_attendee_items($items, $this->search_term, $this->status_filter);

        usort($items, function ($a, $b)
        {
            return strcasecmp($a['name'], $b['name']);
        });

        $per_page = $this->get_items_per_page('mec_attendees_per_page', 20);
        if ($per_page < 1) $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = count($items);

        $this->items = array_slice($items, ($current_page - 1) * $per_page, $per_page);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => $per_page ? (int) ceil($total_items / $per_page) : 0,
        ]);
    }
}

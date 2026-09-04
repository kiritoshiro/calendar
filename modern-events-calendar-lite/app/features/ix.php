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

        // MEC IX Action
        $mec_ix_action = isset($_GET['mec-ix-action']) ? sanitize_text_field($_GET['mec-ix-action']) : '';

        // Google Calendar export action
        if ($mec_ix_action == 'google-calendar-export-get-token') {
            $this->factory->action('init', [$this, 'g_calendar_export_get_token'], 9999);
        }
        // AJAX Actions
        $this->factory->action('wp_ajax_mec_ix_add_to_g_calendar', [$this, 'g_calendar_export_do']);
        $this->factory->action('wp_ajax_mec_ix_g_calendar_authenticate', [$this, 'g_calendar_export_authenticate']);

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

        if ($tab == 'MEC-sync') {
            $this->ix_sync();
        } elseif ($tab == 'MEC-g-calendar-export') {
            $this->ix_g_calendar_export();
        } else {
            // Import is Google Calendar only; unknown tabs fall back to it.
            $this->ix_g_calendar_import();
        }
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
            ]);
        }

        $path = MEC::import('app.features.ix.sync', true, true);

        ob_start();
        include $path;
        echo MEC_kses::full(ob_get_clean());
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

}

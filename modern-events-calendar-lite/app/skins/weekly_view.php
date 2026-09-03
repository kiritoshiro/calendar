<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC Weekly view class.
 * @author Webnus <info@webnus.net>
 */
class MEC_skin_weekly_view extends MEC_skins
{
    /**
     * @var string
     */
    public $skin = 'weekly_view';

    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Registers skin actions into WordPress
     * @author Webnus <info@webnus.net>
     */
    public function actions()
    {
        $this->factory->action('wp_ajax_mec_weekly_view_load_month', [$this, 'load_month']);
        $this->factory->action('wp_ajax_nopriv_mec_weekly_view_load_month', [$this, 'load_month']);
    }

    /**
     * Initialize the skin
     * @param array $atts
     * @author Webnus <info@webnus.net>
     */
    public function initialize($atts)
    {
        $this->atts = $atts;

        // Skin Options
        $this->skin_options = (isset($this->atts['sk-options']) and isset($this->atts['sk-options'][$this->skin])) ? $this->atts['sk-options'][$this->skin] : [];

        // Icons
        $this->icons = $this->main->icons(
            isset($this->atts['icons']) && is_array($this->atts['icons']) ? $this->atts['icons'] : []
        );

        $this->style = $this->skin_options['style'] ?? 'classic';

        // Search Form Options
        $this->sf_options = (isset($this->atts['sf-options']) and isset($this->atts['sf-options'][$this->skin])) ? $this->atts['sf-options'][$this->skin] : [];

        // Search Form Status
        $this->sf_status = $this->atts['sf_status'] ?? true;
        $this->sf_display_label = $this->atts['sf_display_label'] ?? false;
        $this->sf_dropdown_method = $this->atts['sf_dropdown_method'] ?? '1';
        $this->sf_reset_button = $this->atts['sf_reset_button'] ?? false;
        $this->sf_refine = $this->atts['sf_refine'] ?? false;

        // Generate an ID for the skin
        $this->id = $this->get_skin_dom_id();

        // Set the ID
        if (!isset($this->atts['id'])) $this->atts['id'] = $this->id;

        // Next/Previous Month
        $this->next_previous_button = $this->skin_options['next_previous_button'] ?? true;

        // HTML class
        $this->html_class = '';
        if (isset($this->atts['html-class']) and trim($this->atts['html-class']) != '') $this->html_class = $this->atts['html-class'];

        // Booking Button
        $this->booking_button = isset($this->skin_options['booking_button']) ? (int) $this->skin_options['booking_button'] : 0;

        // SED Method
        $this->sed_method = $this->get_sed_method();

        // Image popup
        $this->image_popup = $this->skin_options['image_popup'] ?? '0';

        // reason_for_cancellation
        $this->reason_for_cancellation = $this->skin_options['reason_for_cancellation'] ?? false;

        // display_label
        $this->display_label = $this->skin_options['display_label'] ?? false;

        // From Widget
        $this->widget = isset($this->atts['widget']) && trim($this->atts['widget']);

        // From Full Calendar
        $this->from_full_calendar = isset($this->skin_options['from_fc']) && trim($this->skin_options['from_fc']);

        // Display Price
        $this->display_price = isset($this->skin_options['display_price']) && trim($this->skin_options['display_price']);

        // Detailed Time
        $this->display_detailed_time = isset($this->skin_options['detailed_time']) && trim($this->skin_options['detailed_time']);

        // Init MEC
        $this->args['mec-init'] = true;
        $this->args['mec-skin'] = $this->skin;

        // Post Type
        $this->args['post_type'] = $this->main->get_main_post_type();

        // Post Status
        $this->args['post_status'] = 'publish';

        // Keyword Query
        $this->args['s'] = $this->keyword_query();

        // Taxonomy
        $this->args['tax_query'] = $this->tax_query();

        // Meta
        $this->args['meta_query'] = $this->meta_query();

        // Tag
        if (apply_filters('mec_taxonomy_tag', '') === 'post_tag') $this->args['tag'] = $this->tag_query();

        // Author
        $this->args['author'] = $this->author_query();
        $this->args['author__not_in'] = $this->author_query_ex();

        // Pagination Options
        $this->paged = get_query_var('paged', 1);
        $this->limit = (isset($this->skin_options['limit']) and trim($this->skin_options['limit'])) ? $this->skin_options['limit'] : 12;

        $this->args['posts_per_page'] = $this->limit;
        $this->args['paged'] = $this->paged;

        // Sort Options
        $this->args['orderby'] = 'mec_start_day_seconds ID';
        $this->args['order'] = 'ASC';
        $this->args['meta_key'] = 'mec_start_day_seconds';

        // Show Only Expired Events
        $this->show_only_expired_events = (isset($this->atts['show_only_past_events']) and trim($this->atts['show_only_past_events'])) ? '1' : '0';

        // Show Past Events
        if ($this->show_only_expired_events)
        {
            $this->order_method = 'DESC';
            $this->atts['show_past_events'] = '1';
        }

        // Show Past Events
        $this->args['mec-past-events'] = $this->atts['show_past_events'] ?? '0';

        // Start Date
        list($this->year, $this->month, $this->day) = $this->get_start_date();

        if ($this->from_full_calendar) $this->day = date('d', current_time('timestamp'));

        $this->today = $this->year . '-' . $this->month . '-' . $this->day;
        $this->start_date = $this->year . '-' . $this->month . '-01';

        // Set the maximum date in current month
        if ($this->show_only_expired_events) $this->maximum_date = date('Y-m-d H:i:s', current_time('timestamp'));

        // We will extend the end date in the loop
        $this->end_date = $this->start_date;

        // Show Ongoing Events
        $this->show_ongoing_events = (isset($this->atts['show_only_ongoing_events']) and trim($this->atts['show_only_ongoing_events'])) ? '1' : '0';
        if ($this->show_ongoing_events) $this->args['mec-show-ongoing-events'] = $this->show_ongoing_events;

        // Include Ongoing Events
        $this->include_ongoing_events = (isset($this->atts['show_ongoing_events']) and trim($this->atts['show_ongoing_events'])) ? '1' : '0';
        if ($this->include_ongoing_events) $this->args['mec-include-ongoing-events'] = $this->include_ongoing_events;

        $this->weeks = $this->main->split_to_weeks($this->start_date, date('Y-m-t', strtotime($this->start_date)));

        $this->week_of_days = [];
        foreach ($this->weeks as $week_number => $week) foreach ($week as $day) $this->week_of_days[$day] = $week_number;

        $this->maximum_dates = $this->atts['maximum_dates'] ?? 1;

        // Auto Month Rotation
        $this->auto_month_rotation = !isset($this->settings['auto_month_rotation']) || $this->settings['auto_month_rotation'];

        do_action('mec-weekly-initialize-end', $this);
    }

    /**
     * Search and returns the filtered events
     * @return array of objects
     * @author Webnus <info@webnus.net>
     */
    public function search()
    {
        if ($this->show_only_expired_events)
        {
            $start = date('Y-m-d H:i:s', current_time('timestamp'));
            $end = $this->main->array_key_first($this->week_of_days);
        }
        else
        {
            $start = $this->main->array_key_first($this->week_of_days);
            $end = $this->maximum_date ?: $this->main->array_key_last($this->week_of_days);
        }

        // Date Events
        $dates = $this->period($start, $end, true);

        $s = $this->start_date;
        $sorted = [];
        while (date('m', strtotime($s)) == $this->month)
        {
            if (isset($dates[$s])) $sorted[$s] = $dates[$s];
            else $sorted[$s] = [];

            $s = date('Y-m-d', strtotime('+1 Day', strtotime($s)));
        }

        $dates = array_merge($sorted, $dates);
        uksort($dates, [$this, 'sort_dates']);

        // Limit
        $this->args['posts_per_page'] = $this->limit;

        $events = [];
        $qs = [];

        foreach ($dates as $date => $IDs)
        {
            // Check Finish Date
            if (isset($this->maximum_date) and trim($this->maximum_date) and strtotime($date) > strtotime($this->maximum_date))
            {
                $events[$date] = [];
                continue;
            }

            // Include Available Events
            $this->args['post__in'] = array_unique($IDs);

            // Count of events per day
            $IDs_count = array_count_values($IDs);

            // Extending the end date
            $this->end_date = $date;

            // The Query
            $this->args = apply_filters('mec_skin_query_args', $this->args, $this);

            // Query Key
            $q_key = base64_encode(json_encode($this->args));

            // Get From Cache
            if (isset($qs[$q_key])) $query = $qs[$q_key];
            // Search & Cache
            else
            {
                $query = new WP_Query($this->args);
                $qs[$q_key] = $query;
            }

            if (is_array($IDs) and count($IDs) and $query->have_posts())
            {
                if (!isset($events[$date])) $events[$date] = [];

                // Day Events
                $d = [];

                // The Loop
                while ($query->have_posts())
                {
                    $query->the_post();
                    $ID = get_the_ID();

                    $ID_count = $IDs_count[$ID] ?? 1;
                    for ($i = 1; $i <= $ID_count; $i++)
                    {
                        $rendered = $this->render->data($ID);

                        $repeat_type = !empty($rendered->meta['mec_repeat_type']) ? $rendered->meta['mec_repeat_type'] : '';
                        $occurrence = $date;

                        if (strtotime($occurrence) and in_array($repeat_type, ['certain_weekdays', 'custom_days', 'weekday', 'weekend'])) $occurrence = date('Y-m-d', strtotime($occurrence));
                        else if (strtotime($occurrence)) $occurrence = date('Y-m-d', strtotime('-1 day', strtotime($occurrence)));
                        else $occurrence = null;

                        $dates = $this->render->dates(get_the_ID(), $rendered, $this->maximum_dates, $occurrence);

                        $data = new stdClass();
                        $data->ID = $ID;
                        $data->data = $rendered;

                        $data->dates = $dates;
                        $data->date = $this->get_render_date($date, $ID, $rendered);

                        $d[] = $this->render->after_render($data, $this, $i);
                    }
                }

                usort($d, [$this, 'sort_day_events']);
                $events[$date] = $d;
            }
            else
            {
                $events[$date] = [];
            }

            // Restore original Post Data
            wp_reset_postdata();
        }

        // Initialize Occurrences' Data
        MEC_feature_occurrences::fetch($events);
        // custom sort events by publish date
        $events = apply_filters('mec_skin_events', $events, $this);

        return $events;
    }

    /**
     * Returns start day of skin for filtering events
     * @return array
     * @author Webnus <info@webnus.net>
     */
    public function get_start_date()
    {
        // Default date
        $date = current_time('Y-m-d');

        // Weekdays
        $weekdays = $this->main->get_weekday_labels();

        if (isset($this->skin_options['start_date_type']) and $this->skin_options['start_date_type'] == 'start_current_week')
        {
            if (date('w') == $this->main->get_first_day_of_week()) $date = date('Y-m-d', strtotime('This ' . $weekdays[0]));
            else $date = date('Y-m-d', strtotime('Last ' . $weekdays[0]));
        }
        else if (isset($this->skin_options['start_date_type']) and $this->skin_options['start_date_type'] == 'start_next_week') $date = date('Y-m-d', strtotime('Next ' . $weekdays[0]));
        else if (isset($this->skin_options['start_date_type']) and $this->skin_options['start_date_type'] == 'start_last_week') $date = date('Y-m-d', strtotime('Last ' . $weekdays[0]));
        else if (isset($this->skin_options['start_date_type']) and $this->skin_options['start_date_type'] == 'start_last_month') $date = date('Y-m-d', strtotime('first day of last month'));
        else if (isset($this->skin_options['start_date_type']) and $this->skin_options['start_date_type'] == 'start_current_month') $date = date('Y-m-d', strtotime('first day of this month'));
        else if (isset($this->skin_options['start_date_type']) and $this->skin_options['start_date_type'] == 'start_next_month') $date = date('Y-m-d', strtotime('first day of next month'));
        else if (isset($this->skin_options['start_date_type']) and $this->skin_options['start_date_type'] == 'date') $date = date('Y-m-d', strtotime($this->skin_options['start_date']));

        // Hide past events
        if (isset($this->atts['show_past_events']) and !trim($this->atts['show_past_events']))
        {
            $today = current_time('Y-m-d');
            if (strtotime($date) < strtotime($today)) $date = $today;
        }

        // Show only expired events
        if (isset($this->show_only_expired_events) and $this->show_only_expired_events)
        {
            $yesterday = date('Y-m-d', strtotime('Yesterday'));
            if (strtotime($date) > strtotime($yesterday)) $date = $yesterday;
        }

        $time = strtotime($date);
        return [date('Y', $time), date('m', $time), date('d', $time)];
    }

    /**
     * Load month for AJAX request
     * @return void
     * @author Webnus <info@webnus.net>
     */
    public function load_month()
    {
        // adventistai.lt: the public mini calendar is Lithuanian by default.
        // admin-ajax.php otherwise adopts a logged-in user's profile locale,
        // which made month navigation switch the header to English. Keep the
        // default filterable for multilingual installations, validate it
        // before passing it to WordPress, and never derive it from the user.
        $calendar_locale = apply_filters('mec_adventistai_calendar_locale', 'lt_LT', $this);
        if (!is_string($calendar_locale) || !preg_match('/^[a-z]{2,3}(?:_[A-Z]{2})?$/', $calendar_locale)) $calendar_locale = 'lt_LT';
        if (function_exists('switch_to_locale') && get_locale() !== $calendar_locale) switch_to_locale($calendar_locale);

        $this->sf = (isset($_REQUEST['sf']) and is_array($_REQUEST['sf'])) ? $this->main->sanitize_deep_array($_REQUEST['sf']) : [];
        $apply_sf_date = isset($_REQUEST['apply_sf_date']) ? sanitize_text_field($_REQUEST['apply_sf_date']) : 1;
        $atts = $this->sf_apply(((isset($_REQUEST['atts']) and is_array($_REQUEST['atts'])) ? $this->main->sanitize_deep_array($_REQUEST['atts']) : []), $this->sf, $apply_sf_date);

        $navigator_click = isset($_REQUEST['navigator_click']) && sanitize_text_field($_REQUEST['navigator_click']);

        // Initialize the skin
        $this->initialize($atts);

        //  Weekly view search repeat if not found in current month
        $c = 0;
        $break = false;

        do
        {
            if ($c > 12) $break = true;
            if ($c and !$break)
            {
                if (intval($this->month == 12))
                {
                    $this->year = intval($this->year) + 1;
                    $this->month = '01';
                }

                $this->month = sprintf("%02d", intval($this->month) + 1);
            }
            else
            {
                // Start Date
                $this->year = isset($_REQUEST['mec_year']) ? sanitize_text_field($_REQUEST['mec_year']) : current_time('Y');
                $this->month = isset($_REQUEST['mec_month']) ? sanitize_text_field($_REQUEST['mec_month']) : current_time('m');
            }

            $this->week = 1;

            $this->start_date = $this->year . '-' . $this->month . '-01';

            // We will extend the end date in the loop
            $this->end_date = $this->start_date;

            // Weeks
            $this->weeks = $this->main->split_to_weeks($this->start_date, date('Y-m-t', strtotime($this->start_date)));

            // Get week of days
            $this->week_of_days = [];
            foreach ($this->weeks as $week_number => $week) foreach ($week as $day) $this->week_of_days[$day] = $week_number;

            // Sometimes some months have 6 weeks but next month has 5 or even 4 weeks
            if (!isset($this->weeks[$this->week])) $this->week = $this->week - 1;
            if (!isset($this->weeks[$this->week])) $this->week = $this->week - 1;

            $this->today = $this->weeks[$this->week][0];

            // Return the events
            $this->atts['return_items'] = true;

            // Fetch the events
            $this->fetch();

            // Break the loop if not result
            if ($break) break;
            if ($navigator_click) break;

            // Auto Rotation is Disabled
            if (!$this->auto_month_rotation) break;

            $c++;
        } while (!array_filter($this->events) and count($this->sf));

        // Return the output
        $output = $this->output();

        echo json_encode($output);
        exit;
    }

    /* =====================================================================
     * Month-grid + single-day agenda (adventistai.lt customization)
     * Replaces the week-by-week scroller with a Sunday-start month grid
     * (per-day event counts, Saturday column tinted) and an agenda panel
     * showing only the selected day (today by default). Added straight
     * into this class per site owner's request — a future MEC update
     * will overwrite this file and need the change re-applied.
     * ================================================================= */

    /**
     * Builds the day-cell buttons for a Sunday-start month grid. Computed
     * independently of $this->weeks (which follows the site's configured
     * first-day-of-week and may not be Sunday-aligned); $this->events is
     * still used for per-day counts since it's keyed by plain date and
     * always covers every real day of the displayed month.
     */
    public function magenda_render_grid_days($today)
    {
        $year  = (int) $this->year;
        $month = (int) $this->month;

        $first_ts      = mktime(0, 0, 0, $month, 1, $year);
        $days_in_month = (int) date('t', $first_ts);
        $offset        = (int) date('w', $first_ts); // 0 (Sun) .. 6 (Sat)
        $total_cells   = (int) ceil(($offset + $days_in_month) / 7) * 7;

        ob_start();
        for ($i = 0; $i < $total_cells; $i++)
        {
            $ts       = strtotime(sprintf('%+d days', $i - $offset), $first_ts);
            $date     = date('Y-m-d', $ts);
            $count    = isset($this->events[$date]) ? count($this->events[$date]) : 0;
            $day_num  = (int) date('j', $ts);
            $in_month = ((int) date('n', $ts) === $month);
            $is_sat   = ((int) date('w', $ts) === 6);

            $classes = 'mec-magenda-day';
            if (!$in_month) $classes .= ' mec-magenda-day-pad';
            if ($count) $classes .= ' mec-magenda-day-has-events';
            if ($date === $today) $classes .= ' mec-magenda-day-today';
            if ($is_sat) $classes .= ' mec-magenda-day-saturday';
            ?>
            <button type="button" class="<?php echo esc_attr($classes); ?>" data-date="<?php echo esc_attr($date); ?>">
                <span class="mec-magenda-day-num"><?php echo esc_html($day_num); ?></span>
                <?php if ($count): ?><span class="mec-magenda-day-count"><?php echo esc_html($count); ?></span><?php endif; ?>
            </button>
            <?php
        }
        return ob_get_clean();
    }

    /** Standard Lithuanian short forms for month names (shared logic with
     *  the monthly-view tab bar's own copy — kept local here too since
     *  this class doesn't otherwise depend on MEC_skin_monthly_view). */
    public function magenda_short_month_label($month_num, $full_label)
    {
        $abbr = apply_filters('mec_ymtabs_short_month_names', null);

        if (!is_array($abbr))
        {
            $lt = array(
                1 => 'Saus.', 2 => 'Vas.', 3 => 'Kov.', 4 => 'Bal.',
                5 => 'Geg.', 6 => 'Birž.', 7 => 'Liep.', 8 => 'Rugp.',
                9 => 'Rugs.', 10 => 'Spal.', 11 => 'Lapkr.', 12 => 'Gruod.',
            );
            $abbr = (strpos(get_locale(), 'lt') === 0) ? $lt : array();
        }

        if (isset($abbr[$month_num])) return $abbr[$month_num];

        return mb_substr($full_label, 0, 4).'.';
    }

    public function magenda_render_shell($date_events_html)
    {
        // The grid should default to the actual current calendar month,
        // not whatever month $this->year/$this->month landed on. MEC
        // computes those from the *week* containing today (per this
        // skin's own week-oriented start-date logic), and that week's
        // first day can fall in the previous month — e.g. for the first
        // few days of a month, "today"'s week may start on a Sunday
        // still in the prior month, which would otherwise show that
        // prior month here instead of the one actually in progress.
        // This method only ever runs for the initial, non-AJAX render
        // (month navigation afterwards is handled entirely client-side),
        // so it's safe to just always anchor to today here. Overwriting
        // the instance properties (not just a local copy) so every other
        // method that reads them — magenda_render_grid_days() included —
        // picks up the same corrected month automatically.
        $today_ts = current_time('timestamp');
        $this->year  = date('Y', $today_ts);
        $this->month = date('m', $today_ts);
        $this->start_date = date('Y-m-d', $today_ts);

        $year  = (int) $this->year;
        $month = (int) $this->month;

        $weekday_labels = array(
            $this->main->m('weekdays_su', esc_html__('SU', 'modern-events-calendar-lite')),
            $this->main->m('weekdays_mo', esc_html__('MO', 'modern-events-calendar-lite')),
            $this->main->m('weekdays_tu', esc_html__('TU', 'modern-events-calendar-lite')),
            $this->main->m('weekdays_we', esc_html__('WE', 'modern-events-calendar-lite')),
            $this->main->m('weekdays_th', esc_html__('TH', 'modern-events-calendar-lite')),
            $this->main->m('weekdays_fr', esc_html__('FR', 'modern-events-calendar-lite')),
            $this->main->m('weekdays_sa', esc_html__('SA', 'modern-events-calendar-lite')),
        );

        $month_full = wp_date('F', strtotime($this->start_date));

        $today         = current_time('Y-m-d');
        $today_in_view = ((int) date('Y', strtotime($today)) === $year && (int) date('n', strtotime($today)) === $month);
        $default_day   = $today_in_view ? $today : sprintf('%04d-%02d-01', $year, $month);

        // The exact "All Day" label MEC itself would print (respects any
        // admin override of that message), so the client-side "Šiandien"
        // swap only ever matches a genuine all-day event, never a real
        // time range like "14:00 - 16:00".
        $all_day_label = trim(wp_strip_all_tags($this->main->m('all_day', esc_html__('All Day', 'modern-events-calendar-lite'))));
        $today_word    = (strpos(get_locale(), 'lt') === 0) ? 'Šiandien' : __('Today', 'modern-events-calendar-lite');

        $atts_qs = http_build_query(array('atts' => $this->atts), '', '&');

        ob_start();
        ?>
        <div class="mec-magenda"
             data-mec-id="<?php echo esc_attr($this->id); ?>"
             data-mec-atts="<?php echo esc_attr($atts_qs); ?>"
             data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
             data-fdow="0"
             data-today="<?php echo esc_attr($today); ?>"
             data-default-day="<?php echo esc_attr($default_day); ?>"
             data-cur-year="<?php echo esc_attr($year); ?>"
             data-cur-month="<?php echo esc_attr(sprintf('%02d', $month)); ?>"
             data-all-day-label="<?php echo esc_attr($all_day_label); ?>"
             data-today-word="<?php echo esc_attr($today_word); ?>">

            <div class="mec-magenda-header">
                <button type="button" class="mec-magenda-nav mec-magenda-prev" aria-label="<?php esc_attr_e('Previous month', 'modern-events-calendar-lite'); ?>">&#8249;</button>
                <button type="button" class="mec-magenda-label mec-magenda-month-label" data-open="month"><?php echo esc_html($month_full); ?></button>
                <button type="button" class="mec-magenda-label mec-magenda-year-label" data-open="year"><?php echo esc_html($year); ?></button>
                <button type="button" class="mec-magenda-nav mec-magenda-next" aria-label="<?php esc_attr_e('Next month', 'modern-events-calendar-lite'); ?>">&#8250;</button>
            </div>

            <div class="mec-magenda-picker mec-magenda-picker-month" hidden>
                <?php for ($m = 1; $m <= 12; $m++):
                    $m_label = wp_date('F', mktime(0, 0, 0, $m, 1, $year));
                ?>
                <button type="button" class="mec-magenda-picker-item<?php echo ($m === $month) ? ' mec-magenda-picker-item-active' : ''; ?>" data-month="<?php echo esc_attr(sprintf('%02d', $m)); ?>"><?php echo esc_html($this->magenda_short_month_label($m, $m_label)); ?></button>
                <?php endfor; ?>
            </div>

            <div class="mec-magenda-picker mec-magenda-picker-year" hidden>
                <?php for ($y = $year - 6; $y <= $year + 6; $y++): ?>
                <button type="button" class="mec-magenda-picker-item<?php echo ($y === $year) ? ' mec-magenda-picker-item-active' : ''; ?>" data-year="<?php echo esc_attr($y); ?>"><?php echo esc_html($y); ?></button>
                <?php endfor; ?>
            </div>

            <div class="mec-magenda-weekdays">
                <?php foreach ($weekday_labels as $i => $label): ?>
                <span class="<?php echo ($i === 6) ? 'mec-magenda-weekday-saturday' : ''; ?>"><?php echo esc_html($label); ?></span>
                <?php endforeach; ?>
            </div>

            <div class="mec-magenda-grid"><?php echo $this->magenda_render_grid_days($today); ?></div>

            <div class="mec-magenda-agenda"><?php echo MEC_kses::full($date_events_html); ?></div>

        </div>
        <?php
        return ob_get_clean();
    }
}

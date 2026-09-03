<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC monthly view class.
 * @author Webnus <info@webnus.net>
 */
class MEC_skin_general_calendar extends MEC_skins
{
    /**
     * @var string
     */
    public $skin = 'general_calendar';
    public $activate_first_date = false;
    public $display_all = false;

    /**
     * Registers skin actions into WordPress
     * @author Webnus <info@webnus.net>
     */
    public function actions()
    {
        $this->factory->action('wp_ajax_mec_general_calendar_load_month', [$this, 'load_month']);
        $this->factory->action('wp_ajax_nopriv_mec_general_calendar_load_month', [$this, 'load_month']);
        $this->factory->action('rest_api_init', [$this, 'mec_general_calendar_get_events_api']);

        $this->factory->action('wp_ajax_mec_gcalbar_get_year', [$this, 'gcalbar_ajax_get_year']);
        $this->factory->action('wp_ajax_nopriv_mec_gcalbar_get_year', [$this, 'gcalbar_ajax_get_year']);
    }

    public function mec_general_calendar_get_events_api()
    {
        register_rest_route('mec/v1', '/events', [
            'methods' => 'GET',
            'callback' => [$this, 'get_general_calendar_events'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function switch_language($language = null)
    {
        if (class_exists('SitePress') && trim((string) $language))
        {
            do_action('wpml_switch_language', sanitize_text_field($language));
            return;
        }

        if (function_exists('PLL') && trim((string) $language)) pll_switch_language(sanitize_text_field($language));
        else if (function_exists('PLL') && isset(PLL()->curlang->locale)) $language = PLL()->curlang->locale;

        if ($language) switch_to_locale($language);
    }

    /**
     * @param WP_REST_Request $request
     * @return array
     * @throws Exception
     */
    public function get_general_calendar_events($request)
    {
        // Params
        $startParam = $request->get_param('startParam');
        $endParam = $request->get_param('endParam');
        $categories = $request->get_param('categories') ? $request->get_param('categories') : null;
        $multiCategories = $request->get_param('multiCategories') ? json_decode($request->get_param('multiCategories')) : null;
        $location = $request->get_param('location') ? $request->get_param('location') : null;
        $organizer = $request->get_param('organizer') ? $request->get_param('organizer') : null;
        $speaker = $request->get_param('speaker') ? $request->get_param('speaker') : null;
        $label = $request->get_param('label') ? $request->get_param('label') : null;
        $tag = $request->get_param('tag') ? $request->get_param('tag') : null;
        $cost_min = $request->get_param('cost_min') ? $request->get_param('cost_min') : null;
        $cost_max = $request->get_param('cost_max') ? $request->get_param('cost_max') : null;
        $show_past_events = $request->get_param('show_past_events');
        $show_only_past_events = $request->get_param('show_only_past_events');
        $show_only_one_occurrence = $request->get_param('show_only_one_occurrence');
        $display_label = $request->get_param('display_label');
        $reason_for_cancellation = $request->get_param('reason_for_cancellation');
        $is_category_page = $request->get_param('is_category_page') ? $request->get_param('is_category_page') : null;
        $cat_id = $request->get_param('cat_id') ? $request->get_param('cat_id') : null;
        $local_time = $request->get_param('local_time') ? $request->get_param('local_time') : null;
        $filter_category = $request->get_param('filter_category') ? explode(',', $request->get_param('filter_category')) : null;
        $filter_ex_category = $request->get_param('filter_ex_category') ? explode(',', $request->get_param('filter_ex_category')) : null;
        $filter_location = $request->get_param('filter_location') ? explode(',', $request->get_param('filter_location')) : null;
        $filter_ex_location = $request->get_param('filter_ex_location') ? explode(',', $request->get_param('filter_ex_location')) : null;
        $filter_organizer = $request->get_param('filter_organizer') ? explode(',', $request->get_param('filter_organizer')) : null;
        $filter_ex_organizer = $request->get_param('filter_ex_organizer') ? explode(',', $request->get_param('filter_ex_organizer')) : null;
        $filter_label = $request->get_param('filter_label') ? explode(',', $request->get_param('filter_label')) : null;
        $filter_ex_label = $request->get_param('filter_ex_label') ? explode(',', $request->get_param('filter_ex_label')) : null;
        $filter_tag = $request->get_param('filter_tag') ? explode(',', $request->get_param('filter_tag')) : null;
        $filter_ex_tag = $request->get_param('filter_ex_tag') ? explode(',', $request->get_param('filter_ex_tag')) : null;
        $filter_author = $request->get_param('filter_author') ? explode(',', $request->get_param('filter_author')) : null;
        $filter_ex_author = $request->get_param('filter_ex_author') ? explode(',', $request->get_param('filter_ex_author')) : null;
        $locale = $request->get_param('locale');
        $lang = $request->get_param('lang') ?: $locale;
        $type_event = $request->get_param('type_event');
        $image_size = $request->get_param('image_size');

        $this->switch_language($lang);

        // Attributes
        $skin_options = [
            'limit' => 100,
        ];

        if (!is_null($image_size)) {
            $skin_options['image_size'] = sanitize_text_field($image_size);
        }

        $atts = [
            'show_past_events' => $show_past_events,
            'show_only_past_events' => $show_only_past_events,
            'show_only_one_occurrence' => $show_only_one_occurrence,
            'start_date_type' => 'start_current_month',
            'show_ongoing_events' => '1',
            'sk-options' => [
                'general_calendar' => $skin_options,
            ],
        ];

        // Initialize the skin
        $this->initialize($atts);

        // Fetch the events
        $upcoming_events = $this->get_events(
            $startParam,
            $endParam,
            $categories,
            $multiCategories,
            $location,
            $organizer,
            $speaker,
            $label,
            $tag,
            $cost_min,
            $cost_max,
            $is_category_page,
            $cat_id,
            $show_only_one_occurrence,
            $filter_category,
            $filter_ex_category,
            $filter_location,
            $filter_ex_location,
            $filter_organizer,
            $filter_ex_organizer,
            $filter_label,
            $filter_ex_label,
            $filter_tag,
            $filter_ex_tag,
            $filter_author,
            $filter_ex_author,
            $locale,
            $type_event
        );

        $localtime = $this->skin_options['include_local_time'] ?? false;
        $events = [];
        foreach ($upcoming_events as $content)
        {
            $event_a = [];
            foreach ($content as $event)
            {
                $loc = '';
                if (isset($event->data->locations) && !empty($event->data->locations))
                {
                    foreach ($event->data->locations as $location) if ($location['address']) $loc = $location['address'];
                }

                $labels = '';
                if (isset($event->data->labels) && !empty($event->data->labels) && $display_label)
                {
                    foreach ($event->data->labels as $label) $labels .= '<span class="mec-general-calendar-label '. trim($label['style']) .'" style="background-color:' . esc_attr($label['color']) . ';">' . trim($label['name']) . '</span>';
                }

                $event_title = $event->data->title;
                $event_link = $this->main->get_event_date_permalink($event, $event->date['start']['date']);
                $event_color = $this->get_event_color_dot($event, true);
                $event_content = $event->data->content;
                $event_date_start = $this->main->date_i18n('c', $event->date['start']['timestamp']);
                $event_date_start_str = $event->date['start']['timestamp'];
                $event_date_end = $this->main->date_i18n('c', $event->date['end']['timestamp']);
                $event_date_end_str = $event->date['end']['timestamp'];
                $event_image = $this->get_featured_image_url($event, 'full');
                $gridsquare = $this->get_thumbnail_image($event, 'gridsquare');
                $event_time = $event->data->time;

                $event_a['id'] = $event->data->ID;
                $event_a['title'] = html_entity_decode($event_title);
                $event_a['start'] = $event_date_start;
                $event_a['end'] = $event_date_end;
                $event_a['startStr'] = $event_date_start_str;
                $event_a['endStr'] = $event_date_end_str;
                $event_a['image'] = $event_image;
                $event_a['url'] = $event_link;
                $event_a['backgroundColor'] = $event_color;
                $event_a['borderColor'] = $event_color;
                $event_a['description'] = $event_content;
                $event_a['localtime'] = $localtime;
                $event_a['location'] = $loc;
                $event_a['start_date'] = date_i18n(get_option('date_format'), $event_date_start_str);
                $event_a['start_time'] = $event_time['start'];
                $event_a['end_date'] = date_i18n(get_option('date_format'), $event_date_end_str);
                $event_a['end_time'] = $event_time['end'];
                $event_a['startDateStr'] = strtotime($event_a['start_date']);
                $event_a['endDateStr'] = strtotime($event_a['end_date']);
                $event_a['startDay'] = date_i18n("l", $event_date_start_str);
                $event_a['labels'] = $labels;
                $event_a['reason_for_cancellation'] = $this->main->display_cancellation_reason($event, $reason_for_cancellation);
                $event_a['locaTimeHtml'] = ($local_time == '1' ? $this->main->module('local-time.type2', ['event' => $event]) : '');
                $event_a['gridsquare'] = $gridsquare;

                $event_a = apply_filters('mec_general_calendar_event_data', $event_a, $event);
                $events[] = $event_a;
            }
        }

        return array_values(
            array_reduce($events, function ($r, $a)
            {
                if (!isset($r[$a['id'] . $a['endStr']])) $r[$a['id'] . $a['endStr']] = $a;
                return $r;
            }, [])
        );
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

        // Search Form Options
        $this->sf_options = (isset($this->atts['sf-options']) and isset($this->atts['sf-options'][$this->skin])) ? $this->atts['sf-options'][$this->skin] : [];

        // Search Form Status
        $this->sf_status = $this->atts['sf_status'] ?? true;
        $this->sf_display_label = $this->atts['sf_display_label'] ?? false;
        $this->sf_dropdown_method = $this->atts['sf_dropdown_method'] ?? '1';
        $this->sf_reset_button = $this->atts['sf_reset_button'] ?? false;
        $this->sf_refine = $this->atts['sf_refine'] ?? false;

        // The events
        $this->events_str = '';

        // Generate an ID for the skin
        $this->id = $this->get_skin_dom_id();

        // Set the ID
        if (!isset($this->atts['id'])) $this->atts['id'] = $this->id;

        // The style
        $this->style = $this->skin_options['style'] ?? 'modern';
        if ($this->style == 'fluent' and !is_plugin_active('mec-fluent-layouts/mec-fluent-layouts.php')) $this->style = 'modern';

        // Next/Previous Month
        $this->next_previous_button = $this->skin_options['next_previous_button'] ?? true;

        // Display All Events
        $this->display_all = (((in_array($this->style, ['clean', 'modern']) and isset($this->skin_options['display_all']))) && $this->skin_options['display_all']);

        // Override the style if the style forced by us in a widget etc
        if (isset($this->atts['style']) && trim($this->atts['style']) != '') $this->style = $this->atts['style'];

        // HTML class
        $this->html_class = '';
        if (isset($this->atts['html-class']) and trim($this->atts['html-class']) != '') $this->html_class = $this->atts['html-class'];

        // Booking Button
        $this->booking_button = isset($this->skin_options['booking_button']) ? (int) $this->skin_options['booking_button'] : 0;

        // SED Method
        $this->sed_method = $this->get_sed_method();

        // reason_for_cancellation
        $this->reason_for_cancellation = $this->skin_options['reason_for_cancellation'] ?? false;

        // display_label
        $this->display_label = $this->skin_options['display_label'] ?? false;

        // Image popup
        $this->image_popup = $this->skin_options['image_popup'] ?? '0';

        // From Widget
        $this->widget = isset($this->atts['widget']) && trim($this->atts['widget']);

        // From Full Calendar
        $this->from_full_calendar = (isset($this->skin_options['from_fc']) and trim($this->skin_options['from_fc']));

        // Display Price
        $this->display_price = (isset($this->skin_options['display_price']) and trim($this->skin_options['display_price']));

        // Detailed Time
        $this->display_detailed_time = (isset($this->skin_options['detailed_time']) and trim($this->skin_options['detailed_time']));

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
        [$this->year, $this->month, $this->day] = $this->get_start_date();

        $this->start_date = date('Y-m-d', strtotime($this->year . '-' . $this->month . '-' . $this->day));

        // We will extend the end date in the loop
        $this->end_date = $this->start_date;

        // Activate First Date With Event
        $this->activate_first_date = (isset($this->skin_options['activate_first_date']) and $this->skin_options['activate_first_date']);
    }

    public function sanitize_author_ids($authors): array
    {
        if (is_null($authors) || $authors === 'undefined') return [];

        if (!is_array($authors)) $authors = explode(',', (string) $authors);

        $authors = array_map('absint', $authors);
        $authors = array_filter($authors);

        return array_values(array_unique($authors));
    }

    /**
     * /**
     * Search and returns the filtered events
     * @param $start
     * @param $end
     * @param null $categories
     * @param null $multiCategories
     * @param null $location
     * @param null $organizer
     * @param null $speaker
     * @param null $label
     * @param null $tag
     * @param null $cost_min
     * @param null $cost_max
     * @param null $is_category_page
     * @param null $cat_id
     * @param null $show_only_one_occurrence
     * @param null $filter_category
     * @param null $filter_ex_category
     * @param null $filter_location
     * @param null $filter_ex_location
     * @param null $filter_organizer
     * @param null $filter_ex_organizer
     * @param null $filter_label
     * @param null $filter_ex_label
     * @param null $filter_tag
     * @param null $filter_ex_tag
     * @param null $filter_author
     * @param null $filter_ex_author
     * @param string $locale
     * @param null $type_event
     * @return array of objects
     * @throws Exception
     * @author Webnus <info@webnus.net>
     */
    public function get_events(
        $start,
        $end,
        $categories = null,
        $multiCategories = null,
        $location = null,
        $organizer = null,
        $speaker = null,
        $label = null,
        $tag = null,
        $cost_min = null,
        $cost_max = null,
        $is_category_page = null,
        $cat_id = null,
        $show_only_one_occurrence = null,
        $filter_category = null,
        $filter_ex_category = null,
        $filter_location = null,
        $filter_ex_location = null,
        $filter_organizer = null,
        $filter_ex_organizer = null,
        $filter_label = null,
        $filter_ex_label = null,
        $filter_tag = null,
        $filter_ex_tag = null,
        $filter_author = null,
        $filter_ex_author = null,
        $locale = 'en',
        $type_event = null
    )
    {
        $start = date('Y-m-d', strtotime($start));
        $end = date('Y-m-d', strtotime($end));

        if ($this->show_only_expired_events)
        {
            $start = date('Y-m-d H:i:s', current_time('timestamp'));
            $end = date('Y-m-d', strtotime('first day of this month'));

            $this->weeks = $this->main->split_to_weeks($end, $start);

            $this->week_of_days = [];
            foreach ($this->weeks as $week_number => $week) foreach ($week as $day) $this->week_of_days[$day] = $week_number;

            $end = $this->main->array_key_first($this->week_of_days);
        }

        // Taxonomy Query
        $tax_query = [
            'relation' => 'AND',
        ];

        if (!is_null($is_category_page) && $is_category_page != 'undefined' && !is_null($cat_id) && $cat_id != 'undefined')
        {
            $tax_query[] = [
                'taxonomy' => 'mec_category',
                'field' => 'id',
                'terms' => [$cat_id],
                'operator' => 'IN',
            ];
        }

        if (!is_null($categories) && $categories != 'undefined')
        {
            $tax_query[] = [
                'taxonomy' => 'mec_category',
                'field' => 'id',
                'terms' => [$categories],
                'operator' => 'IN',
            ];
        }

        if (!is_null($multiCategories) && $multiCategories != 'undefined' && count($multiCategories) > 0)
        {
            $tax_query[] = [
                'taxonomy' => 'mec_category',
                'field' => 'id',
                'terms' => $multiCategories,
                'operator' => 'IN',
            ];
        }

        if (!is_null($filter_category) && $filter_category != 'undefined')
        {
            $tax_query[] = [
                'taxonomy' => 'mec_category',
                'field' => 'id',
                'terms' => is_array($filter_category) ? $filter_category : [$filter_category],
                'operator' => 'IN',
            ];
        }

        if (!is_null($filter_ex_category) && $filter_ex_category != 'undefined')
        {
            $tax_query[] = [
                'taxonomy' => 'mec_category',
                'field' => 'id',
                'terms' => is_array($filter_ex_category) ? $filter_ex_category : [$filter_ex_category],
                'operator' => 'NOT IN',
            ];
        }

        if (!is_null($location) && $location != 'undefined')
        {
            $tax_query[] = [
                'taxonomy' => 'mec_location',
                'field' => 'id',
                'terms' => [$location],
                'operator' => 'IN',
            ];
        }

        if (!is_null($filter_location) && $filter_location != 'undefined')
        {
            $tax_query[] = [
                'taxonomy' => 'mec_location',
                'field' => 'id',
                'terms' => is_array($filter_location) ? $filter_location : [$filter_location],
                'operator' => 'IN',
            ];
        }

        if (!is_null($filter_ex_location) && $filter_ex_location != 'undefined')
        {
            $tax_query[] = [
                'taxonomy' => 'mec_location',
                'field' => 'id',
                'terms' => is_array($filter_ex_location) ? $filter_ex_location : [$filter_ex_location],
                'operator' => 'NOT IN',
            ];
        }

        if (!is_null($organizer) && $organizer != 'undefined')
        {
            $tax_query[] = [
                'taxonomy' => 'mec_organizer',
                'field' => 'id',
                'terms' => [$organizer],
                'operator' => 'IN',
            ];
        }

        if (!is_null($filter_organizer) && $filter_organizer != 'undefined')
        {
            $tax_query[] = [
                'taxonomy' => 'mec_organizer',
                'field' => 'id',
                'terms' => is_array($filter_organizer) ? $filter_organizer : [$filter_organizer],
                'operator' => 'IN',
            ];
        }

        if (!is_null($filter_ex_organizer) && $filter_ex_organizer != 'undefined')
        {
            $tax_query[] = [
                'taxonomy' => 'mec_organizer',
                'field' => 'id',
                'terms' => is_array($filter_ex_organizer) ? $filter_ex_organizer : [$filter_ex_organizer],
                'operator' => 'NOT IN',
            ];
        }

        if (!is_null($speaker) && $speaker != 'undefined')
        {
            $tax_query[] = [
                'taxonomy' => 'mec_speaker',
                'field' => 'id',
                'terms' => [$speaker],
                'operator' => 'IN',
            ];
        }

        if (!is_null($label) && $label != 'undefined')
        {
            $tax_query[] = [
                'taxonomy' => 'mec_label',
                'field' => 'id',
                'terms' => [$label],
                'operator' => 'IN',
            ];
        }

        if (!is_null($filter_label) && $filter_label != 'undefined')
        {
            $tax_query[] = [
                'taxonomy' => 'mec_label',
                'field' => 'id',
                'terms' => is_array($filter_label) ? $filter_label : [$filter_label],
                'operator' => 'IN',
            ];
        }

        if (!is_null($filter_ex_label) && $filter_ex_label != 'undefined')
        {
            $tax_query[] = [
                'taxonomy' => 'mec_label',
                'field' => 'id',
                'terms' => is_array($filter_ex_label) ? $filter_ex_label : [$filter_ex_label],
                'operator' => 'NOT IN',
            ];
        }

        if (!is_null($filter_ex_tag) && $filter_ex_tag != 'undefined')
        {
            $tax_query[] = [
                'taxonomy' => apply_filters('mec_taxonomy_tag', ''),
                'field' => 'name',
                'terms' => is_array($filter_ex_tag) ? $filter_ex_tag : [$filter_ex_tag],
                'operator' => 'NOT IN',
            ];
        }

        $mec_tag_query = null;
        if (!is_null($tag) && $tag != 'undefined')
        {
            $term = get_term_by('id', $tag, apply_filters('mec_taxonomy_tag', ''));
            if ($term) $mec_tag_query = $term->slug;
        }

        if (!is_null($filter_tag) && $filter_tag != 'undefined')
        {
            $term = get_term_by('id', $filter_tag, apply_filters('mec_taxonomy_tag', ''));
            if ($term) $mec_tag_query = $term->slug;
        }

        // Meta Query
        $meta_query = [
            'relation' => 'AND',
        ];

        if (!is_null($cost_min) && $cost_min != 'undefined')
        {
            $meta_query[] = [
                'key' => 'mec_cost',
                'value' => $cost_min,
                'type' => 'numeric',
                'compare' => '>=',
            ];
        }

        if (!is_null($cost_max) && $cost_max != 'undefined')
        {
            $meta_query[] = [
                'key' => 'mec_cost',
                'value' => $cost_max,
                'type' => 'numeric',
                'compare' => '<=',
            ];
        }

        if (!is_null($type_event) && $type_event != 'undefined' && $type_event != 'all')
        {
            $meta_query[] = [
                'key' => 'mec_event_status',
                'value' => $type_event,
                'compare' => '=',
            ];
        }

        $this->args['tax_query'] = $tax_query;
        $this->args['meta_query'] = $meta_query;
        $this->args['tag'] = $mec_tag_query;
        if ($lang) $this->args['lang'] = sanitize_text_field($lang);
        $this->args['author'] = implode(',', $this->sanitize_author_ids($filter_author));
        $this->args['author__not_in'] = $this->sanitize_author_ids($filter_ex_author);

        $dates = $this->period($start, $end, true);
        ksort($dates);

        if ($this->show_only_expired_events && $this->loadMoreRunning) $this->show_only_expired_events = '1';

        // Limit
        $this->args['posts_per_page'] = $this->limit;

        $i = 0;
        $found = 0;
        $events = [];

        foreach ($dates as $date => $IDs)
        {
            // No Event
            if (!is_array($IDs) || !count($IDs)) continue;

            // Check Finish Date
            if (isset($this->maximum_date) and trim($this->maximum_date) and strtotime($date) > strtotime($this->maximum_date)) break;

            // Include Available Events
            $this->args['post__in'] = array_unique($IDs);

            // Count of events per day
            $IDs_count = array_count_values($IDs);

            // Extending the end date
            $this->end_date = $date;

            // Continue to load rest of events in the first date
            if ($i === 0) $this->args['offset'] = $this->offset;
            // Load all events in the rest of dates
            else
            {
                $this->offset = 0;
                $this->args['offset'] = 0;
            }

            // The Query
            $this->args['posts_per_page'] = 1000;
            $this->args = apply_filters('mec_skin_query_args', $this->args, $this);

            $query = new WP_Query($this->args);
            if ($query->have_posts())
            {
                if (!isset($events[$date])) $events[$date] = [];

                // Day Events
                $d = [];

                // The Loop
                while ($query->have_posts())
                {
                    $query->the_post();
                    $ID = get_the_ID();

                    $one_occurrence = get_post_meta($ID, 'one_occurrence', true);
                    if ($show_only_one_occurrence != '0' and !is_null($show_only_one_occurrence))
                    {
                        if ($one_occurrence != '1')
                        {
                            if (!isset($IDs_count[$ID])) continue;
                        }
                        else
                        {
                            continue;
                        }
                    }

                    $ID_count = $IDs_count[$ID] ?? 1;
                    for ($i = 1; $i <= $ID_count; $i++)
                    {
                        $rendered = $this->render->data($ID);

                        $data = new stdClass();
                        $data->ID = $ID;
                        $data->data = $rendered;

                        $data->date = $this->get_render_date($date, $ID, $rendered);
                        if ($data->date['start']['date'] === $date) $data->date['start']['date'] = $this->main->get_start_of_multiple_days($ID, $date);
                        $d[] = $this->render->after_render($data, $this, $i);

                        $found++;
                    }

                    if ($found >= 1000)
                    {
                        // Next Offset
                        $this->next_offset = ($query->post_count - ($query->current_post + 1)) >= 0 ? ($query->current_post + 1) + $this->offset : 0;

                        usort($d, [$this, 'sort_day_events']);
                        $events[$date] = $d;

                        // Restore original Post Data
                        wp_reset_postdata();

                        break 2;
                    }
                }

                usort($d, [$this, 'sort_day_events']);
                $events[$date] = $d;
            }

            // Restore original Post Data
            wp_reset_postdata();
            $i++;
        }

        // Initialize Occurrences' Data
        MEC_feature_occurrences::fetch($events);

        // custom sort events by publish date
        $events = apply_filters('mec_skin_events', $events, $this);

        // Set Offset for Last Page
        if ($found < $this->limit)
        {
            // Next Offset
            $this->next_offset = $found + ((isset($date) and $this->start_date === $date) ? $this->offset : 0);
        }

        // Set found events
        $this->found = $found;
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

        if (isset($this->skin_options['start_date_type']) and $this->skin_options['start_date_type'] == 'start_current_month') $date = date('Y-m-d', strtotime('first day of this month'));
        else if (isset($this->skin_options['start_date_type']) and $this->skin_options['start_date_type'] == 'start_next_month') $date = date('Y-m-d', strtotime('first day of next month'));
        else if (isset($this->skin_options['start_date_type']) and $this->skin_options['start_date_type'] == 'start_last_month') $date = date('Y-m-d', strtotime('first day of last month'));
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

    /* =====================================================================
     * Year/month tab bar (adventistai.lt customization)
     * Same year-tab strip as the Monthly View skin — it reuses that
     * skin's .mec-ymtabs CSS (see frontend.css) so the two look
     * identical, just placed above this skin's FullCalendar-powered
     * "General Calendar" view instead. Navigation goes through
     * FullCalendar's own JS API (calendar.gotoDate) rather than MEC's
     * .mec-load-month AJAX mechanism, since this skin doesn't use that.
     * Deliberately added straight into this class (rather than a
     * separate must-use plugin) per site owner's request — this will be
     * overwritten by a future MEC update and need re-applying.
     * ================================================================= */

    /** Standard Lithuanian short forms for month names (genitive-stem, as
     *  Lithuanian dates/calendars actually abbreviate them). Kept in sync
     *  with MEC_skin_monthly_view::ymtabs_lithuanian_month_abbr(). */
    public function gcalbar_lithuanian_month_abbr()
    {
        return array(
            1 => 'Saus.', 2 => 'Vas.', 3 => 'Kov.', 4 => 'Bal.',
            5 => 'Geg.', 6 => 'Birž.', 7 => 'Liep.', 8 => 'Rugp.',
            9 => 'Rugs.', 10 => 'Spal.', 11 => 'Lapkr.', 12 => 'Gruod.',
        );
    }

    public function gcalbar_short_month_label($month_num, $full_label)
    {
        $abbr = apply_filters('mec_ymtabs_short_month_names', null);

        if(!is_array($abbr))
        {
            $abbr = (strpos(get_locale(), 'lt') === 0) ? $this->gcalbar_lithuanian_month_abbr() : array();
        }

        if(isset($abbr[$month_num])) return $abbr[$month_num];

        return mb_substr($full_label, 0, 4).'.';
    }

    /** One query per month (an event spanning several months is counted
     *  in each one it touches). Uses tstart/tend since those are the
     *  indexed columns on #__mec_dates. Same approach as
     *  MEC_skin_monthly_view::ymtabs_counts_for_year(). */
    public function gcalbar_counts_for_year($year)
    {
        global $wpdb;

        $table  = $wpdb->prefix.'mec_dates';
        $counts = array_fill(1, 12, 0);

        for($m = 1; $m <= 12; $m++)
        {
            $month_start_ts = strtotime(sprintf('%04d-%02d-01 00:00:00', $year, $m));
            $month_end_ts   = strtotime(date('Y-m-t 23:59:59', $month_start_ts));

            $sql = $wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) FROM {$table} WHERE status = %s AND public = %d AND tstart <= %d AND tend >= %d",
                'publish', 1, $month_end_ts, $month_start_ts
            );

            $counts[$m] = (int) $wpdb->get_var($sql);
        }

        return $counts;
    }

    public function gcalbar_render_bar()
    {
        $year  = (int) $this->year;
        $month = (int) $this->month;

        ob_start();
        ?>
        <div class="mec-ymtabs" id="mec-gcalbar-<?php echo esc_attr($this->id); ?>" data-mec-id="<?php echo esc_attr($this->id); ?>" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">

            <div class="mec-ymtabs-yearbar">
                <button type="button" class="mec-ymtabs-year-nav mec-ymtabs-year-prev" data-mec-year-jump="<?php echo esc_attr($year - 1); ?>" aria-label="<?php esc_attr_e('Previous year', 'modern-events-calendar-lite'); ?>">&#9664;</button>
                <span class="mec-ymtabs-year-label"><?php echo esc_html($year); ?></span>
                <button type="button" class="mec-ymtabs-year-nav mec-ymtabs-year-next" data-mec-year-jump="<?php echo esc_attr($year + 1); ?>" aria-label="<?php esc_attr_e('Next year', 'modern-events-calendar-lite'); ?>">&#9654;</button>
            </div>

            <div class="mec-ymtabs-months">
                <?php echo $this->gcalbar_render_months_row($year, $year, $month); ?>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    public function gcalbar_render_months_row($year, $active_year, $active_month)
    {
        $counts = $this->gcalbar_counts_for_year($year);

        ob_start();
        for($m = 1; $m <= 12; $m++)
        {
            $is_active = ($year == $active_year && $m == $active_month);
            $mm        = sprintf('%02d', $m);
            $label     = wp_date('F', mktime(0, 0, 0, $m, 1, $year));
            $short     = $this->gcalbar_short_month_label($m, $label);
            $count     = isset($counts[$m]) ? $counts[$m] : 0;
            ?>
            <a href="#"
               class="mec-ymtabs-item<?php echo $is_active ? ' mec-ymtabs-item-active' : ''; ?>"
               data-mec-year="<?php echo esc_attr($year); ?>"
               data-mec-month="<?php echo esc_attr($mm); ?>">
                <span class="mec-ymtabs-item-label">
                    <span class="mec-ymtabs-item-label-full"><?php echo esc_html($label); ?></span>
                    <span class="mec-ymtabs-item-label-short"><?php echo esc_html($short); ?></span>
                </span>
                <span class="mec-ymtabs-item-count"><?php echo esc_html($count); ?></span>
            </a>
            <?php
        }
        return ob_get_clean();
    }

    /** AJAX: swap the 12-tab row for a different year without touching
     *  the calendar currently on screen. */
    public function gcalbar_ajax_get_year()
    {
        // adventistai.lt: force the SITE's locale for this AJAX request —
        // see the matching note in weekly_view.php's load_month() for why.
        $site_locale = get_option('WPLANG');
        if (empty($site_locale)) $site_locale = 'en_US';
        if (get_locale() !== $site_locale) switch_to_locale($site_locale);

        $year         = isset($_REQUEST['year']) ? (int) $_REQUEST['year'] : (int) current_time('Y');
        $active_year  = isset($_REQUEST['active_year']) ? (int) $_REQUEST['active_year'] : $year;
        $active_month = isset($_REQUEST['active_month']) ? (int) $_REQUEST['active_month'] : 0;

        wp_send_json(array(
            'year' => $year,
            'html' => $this->gcalbar_render_months_row($year, $active_year, $active_month),
        ));
    }
}

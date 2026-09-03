<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC feed class.
 * @author Webnus <info@webnus.net>
 */
class MEC_feature_feed extends MEC_base
{
    /**
     * @var MEC_factory
     */
    public $factory;

    /**
     * @var MEC_main
     */
    public $main;

    /**
     * @var MEC_feed
     */
    public $feed;
    public $PT;
    public $events;
    public $settings;

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

        // Import MEC Feed
        $this->feed = $this->getFeed();

        // MEC Post Type Name
        $this->PT = $this->main->get_main_post_type();

        // General Settings
        $this->settings = $this->main->get_settings();
    }

    /**
     * Initialize feed feature
     * @author Webnus <info@webnus.net>
     */
    public function init()
    {
        remove_all_actions('do_feed_rss2');
        $this->factory->action('do_feed_rss2', array($this, 'rss2'));

        // Include Featured Image
        if(!isset($this->settings['include_image_in_feed']) or (isset($this->settings['include_image_in_feed']) and $this->settings['include_image_in_feed']))
        {
            add_filter('get_the_excerpt', array($this, 'include_featured_image'), 10, 2);
        }

        if(!is_admin()) $this->factory->action('init', array($this, 'ical'), 999);
    }

    /**
     * Do the feed
     * @author Webnus <info@webnus.net>
     * @param string $for_comments
     */
    public function rss2($for_comments)
    {
        $rss2 = MEC::import('app.features.feed.rss2', true, true);

        if(get_query_var('post_type') == $this->PT)
        {
            // Fetch Events
            $this->events = $this->fetch();

            // Include Feed template
            include_once $rss2;
        }
        elseif(get_query_var('taxonomy') == 'mec_category')
        {
            $q = get_queried_object();
            $term_id = $q->term_id;

            // Fetch Events
            $this->events = $this->fetch($term_id);

            // Include Feed template
            include_once $rss2;
        }
        else do_feed_rss2($for_comments); // Call default function
    }

    /**
     * Returns the events
     * @author Webnus <info@webnus.net>
     * @param $category
     * @return array
     */
    public function fetch($category = NULL)
    {
        $args = [
            'sk-options' => [
                'list' => [
                    'limit' => get_option('posts_per_rss', 12),
                ]
            ],
            'category' => $category
        ];

        $EO = new MEC_skin_list(); // Events Object
        $EO->initialize($args);
        $EO->search();

        return $EO->fetch();
    }

    /**
     * @param string $excerpt
     * @param WP_Post $post
     * @return string
     */
    public function include_featured_image($excerpt, $post = NULL)
    {
        // Only RSS
        if(!is_feed()) return $excerpt;

        // Get Current Post
        if(!$post) $post = get_post();
        if(!$post) return $excerpt;

        // It's not event
        if($post->post_type != $this->main->get_main_post_type()) return $excerpt;

        $image = get_the_post_thumbnail($post);
        if(trim($image)) $excerpt = $image.' '.$excerpt;

        return $excerpt;
    }

    /**
     * Parse term filters coming from the iCal feed request.
     *
     * @param string $request_key
     * @param string $taxonomy
     * @return array [array $term_ids, bool $requested]
     */
    protected function parse_feed_filter_terms($request_key, $taxonomy)
    {
        $requested = false;
        $term_ids = [];

        if(isset($_GET[$request_key]) || isset($_POST[$request_key]) || isset($_REQUEST[$request_key]))
        {
            $raw = isset($_GET[$request_key]) ? $_GET[$request_key] : (isset($_POST[$request_key]) ? $_POST[$request_key] : $_REQUEST[$request_key]);

            if(is_array($raw)) $raw = implode(',', $raw);

            $raw = sanitize_text_field(wp_unslash($raw));

            if($raw === '') return [$term_ids, false];

            $requested = true;

            $parts = preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY);

            $ids = [];
            $slugs = [];

            foreach($parts as $part)
            {
                if($part === '') continue;

                if(is_numeric($part)) $ids[] = absint($part);
                else $slugs[] = sanitize_title($part);
            }

            if(count($slugs))
            {
                $terms = get_terms([
                    'taxonomy' => $taxonomy,
                    'hide_empty' => false,
                    'fields' => 'ids',
                    'slug' => $slugs,
                ]);

                if(!is_wp_error($terms) && is_array($terms)) $ids = array_merge($ids, $terms);
            }

            $term_ids = array_values(array_unique(array_filter(array_map('absint', $ids))));
        }

        return [$term_ids, $requested];
    }

    /**
     * @throws Exception
     */
    public function ical()
    {
        $ical_feed = isset($_GET['mec-ical-feed']) && sanitize_text_field($_GET['mec-ical-feed']);
        if(!$ical_feed) return false;

        // Feed is not enabled
        if(!isset($this->settings['ical_feed']) or (isset($this->settings['ical_feed']) and !$this->settings['ical_feed'])) return false;

        $only_upcoming_events = (isset($this->settings['ical_feed_upcoming']) and $this->settings['ical_feed_upcoming']);
        if($only_upcoming_events)
        {
            $event_ids = $this->main->get_upcoming_event_ids(current_time('timestamp'), 'publish');
        }
        else
        {
            $events = $this->main->get_events('-1');

            $event_ids = [];
            foreach($events as $event) $event_ids[] = $event->ID;
        }

        // Filtered Events
        $filtered_ids = null;

        list($locations, $locations_requested) = $this->parse_feed_filter_terms('mec_locations', 'mec_location');
        list($categories, $categories_requested) = $this->parse_feed_filter_terms('mec_categories', 'mec_category');
        list($organizers, $organizers_requested) = $this->parse_feed_filter_terms('mec_organizers', 'mec_organizer');

        if($locations_requested || $categories_requested || $organizers_requested)
        {
            $tax_query = [];
            $invalid_filter = false;

            if($locations_requested)
            {
                if(!count($locations)) $invalid_filter = true;
                else
                {
                    $tax_query[] = [
                        'taxonomy' => 'mec_location',
                        'field' => 'term_id',
                        'terms' => $locations,
                        'operator' => 'IN',
                    ];
                }
            }

            if(!$invalid_filter && $categories_requested)
            {
                if(!count($categories)) $invalid_filter = true;
                else
                {
                    $tax_query[] = [
                        'taxonomy' => 'mec_category',
                        'field' => 'term_id',
                        'terms' => $categories,
                        'operator' => 'IN',
                    ];
                }
            }

            if(!$invalid_filter && $organizers_requested)
            {
                if(!count($organizers)) $invalid_filter = true;
                else
                {
                    $tax_query[] = [
                        'taxonomy' => 'mec_organizer',
                        'field' => 'term_id',
                        'terms' => $organizers,
                        'operator' => 'IN',
                    ];
                }
            }

            if($invalid_filter) $filtered_ids = [];
            elseif(count($tax_query))
            {
                $filtered_ids = get_posts([
                    'post_type' => $this->PT,
                    'posts_per_page' => -1,
                    'post_status' => ['publish'],
                    'fields' => 'ids',
                    'no_found_rows' => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                    'tax_query' => $tax_query,
                ]);
            }
            else $filtered_ids = [];
        }

        if(is_array($filtered_ids))
        {
            // No Events Found
            if(!count($filtered_ids)) $event_ids = [];
            else
            {
                $new_event_ids = [];
                foreach($filtered_ids as $filtered_id)
                {
                    if(in_array($filtered_id, $event_ids)) $new_event_ids[] = $filtered_id;
                }

                $event_ids = $new_event_ids;
            }
        }

        // Remove appointment events from the feed
        $appointments = $this->getAppointments();
        foreach($event_ids as $key => $event_id)
        {
            $event_status = get_post_status($event_id);

            if ($event_status !== 'publish') unset($event_ids[$key]);
            if ($appointments->get_entity_type($event_id) === 'appointment') unset($event_ids[$key]);
        }
        $event_ids = array_values($event_ids);

        $output = '';
        foreach($event_ids as $event_id) $output .= $this->main->ical_single($event_id, '', '', !$only_upcoming_events);

        // Include in iCal
        $ical_calendar = $this->main->ical_calendar($output);

        // Content Type
        nocache_headers();
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: inline; filename="mec-events-'.date('YmdTHi').'.ics"');

        // Print the Calendar
        echo MEC_kses::full($ical_calendar);
        exit;
    }
}

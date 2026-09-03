<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC render class.
 * @author Webnus <info@webnus.net>
 */
class MEC_render extends MEC_base
{
    public $db;
    public $main;
    public $file;
    public $settings;
    public $post_atts;

    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct()
    {
        // Add image size for list and carousel
        add_image_size('thumblist', '300', '300', true);
        add_image_size('thumbrelated', '500', '500', true);
        add_image_size('meccarouselthumb', '474', '324', true);
        add_image_size('gridsquare', '391', '260', true);
        add_image_size('tileview', '300', '400', true);

        // Import MEC skin class
        MEC::import('app.libraries.skins');

        // MEC main library
        $this->main = $this->getMain();

        // MEC file library
        $this->file = $this->getFile();

        // MEC DB library
        $this->db = $this->getDB();

        // MEC Settings
        $this->settings = $this->main->get_settings();
    }

    /**
     * Do the shortcode and return its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function shortcode($atts)
    {
        $calendar_id = isset($atts['id']) ? (int) $atts['id'] : 0;
        $atts['id'] = $calendar_id;

        global $MEC_Shortcode_id;
        $MEC_Shortcode_id = $calendar_id;
        $atts = apply_filters('mec_calendar_atts', $this->parse($calendar_id, $atts));

        if (empty($atts['instance_id'])) {
            $atts['instance_id'] = function_exists('wp_unique_id') ? wp_unique_id('mec') : uniqid('mec', false);
        }

        $skin = $atts['skin'] ?? $this->get_default_layout();
        return $this->skin($skin, $atts);
    }

    /**
     * Do the shortcode and return its json output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function shortcode_json($atts)
    {
        $calendar_id = $atts['id'] ?? 0;
        $atts = apply_filters('mec_calendar_atts', $this->parse($calendar_id, $atts));

        if (empty($atts['instance_id'])) {
            $atts['instance_id'] = function_exists('wp_unique_id') ? wp_unique_id('mec') : uniqid('mec', false);
        }

        $skin = $atts['skin'] ?? $this->get_default_layout();
        $json = $this->skin($skin, $atts);

        $path = MEC::import('app.skins.' . $skin, true, true);
        $skin_path = apply_filters('mec_skin_path', $skin);

        if ($skin_path != $skin and $this->file->exists($skin_path)) $path = $skin_path;
        if (!$this->file->exists($path))
        {
            return esc_html__('Skin controller does not exist.', 'modern-events-calendar-lite');
        }

        include_once $path;

        $skin_class_name = 'MEC_skin_' . $skin;

        // Create Skin Object Class
        $SKO = new $skin_class_name();
        $SKO->sf_base_atts = $atts;

        // Apply URL search filters on initial render (so no flash of unfiltered results)
        $sf = (isset($_REQUEST['sf']) and is_array($_REQUEST['sf'])) ? $this->main->sanitize_deep_array($_REQUEST['sf']) : [];
        if (!empty($sf))
        {
            // Respect date application like AJAX paths do
            $apply_sf_date = isset($_REQUEST['apply_sf_date']) ? sanitize_text_field($_REQUEST['apply_sf_date']) : 1;
            $atts = $SKO->sf_apply($atts, $sf, $apply_sf_date);
        }

        // Initialize the skin
        $SKO->initialize($atts);

        $atts['content_html'] = $SKO->output();

        if ('full_calendar' == $skin)
        {

            $default_atts = $SKO->prepare_skin_options($skin, $atts);
            $default_view = $default_atts['default_view'] ?? 'list';

            $skin_class_name = 'MEC_skin_' . $default_view;

            // Create Skin Object Class
            $default_SKO = new $skin_class_name();

            // Initialize the skin
            $default_SKO->initialize($default_atts);

            // Fetch the events
            $atts['content_json'] = $default_SKO->fetch();
        }
        else
        {

            // Fetch the events
            $atts['content_json'] = $SKO->fetch();
        }

        return $atts;
    }

    /**
     * Do the widget and return its output
     * @param int $calendar_id
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function widget($calendar_id, $atts = [])
    {
        $atts = apply_filters('mec_calendar_atts', $this->parse($calendar_id, $atts));

        $skin = $atts['skin'] ?? $this->get_default_layout();
        return $this->skin($skin, $atts);
    }

    /**
     * Do the yearly_view skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vyear($atts = [])
    {
        $atts = apply_filters('mec_vyear_atts', $atts);
        $skin = 'yearly_view';

        return $this->skin($skin, $atts);
    }

    /**
     * Do the monthly_view skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vmonth($atts = [])
    {
        $atts = apply_filters('mec_vmonth_atts', $atts);
        $skin = 'monthly_view';

        return $this->skin($skin, $atts);
    }

    /**
     * Do the full_calendar skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vfull($atts = [])
    {
        $atts = apply_filters('mec_vfull_atts', $atts);
        $skin = 'full_calendar';

        return $this->skin($skin, $atts);
    }

    /**
     * Do the default_full_calendar skin and returns its output (archive page)
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vdefaultfull($atts = [])
    {
        $atts = apply_filters('mec_vdefaultfull_atts', $atts);
        $skin = 'default_full_calendar';

        return $this->skin($skin, $atts);
    }


    /**
     * Do the weekly_view skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vweek($atts = [])
    {
        $atts = apply_filters('mec_vweek_atts', $atts);
        $skin = 'weekly_view';

        return $this->skin($skin, $atts);
    }

    /**
     * Do the timetable skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vtimetable($atts = [])
    {
        $atts = apply_filters('mec_vtimetable_atts', $atts);
        $skin = 'timetable';

        return $this->skin($skin, $atts);
    }

    /**
     * Do the Masonry skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vmasonry($atts = [])
    {
        $atts = apply_filters('mec_vmasonry_atts', $atts);
        $skin = 'masonry';

        return $this->skin($skin, $atts);
    }

    /**
     * Do the daily_view skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vday($atts = [])
    {
        $atts = apply_filters('mec_vday_atts', $atts);
        $skin = 'daily_view';

        return $this->skin($skin, $atts);
    }

    /**
     * Do the map skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vmap($atts = [])
    {
        $atts = apply_filters('mec_vmap_atts', $atts);
        $skin = 'map';

        return $this->skin($skin, $atts);
    }

    /**
     * Do the list skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vlist($atts = [])
    {
        $atts = apply_filters('mec_vlist_atts', $atts);
        $skin = 'list';

        return $this->skin($skin, $atts);
    }

    /**
     * Do the tile skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vtile($atts = [])
    {
        $atts = apply_filters('mec_vtile_atts', $atts);
        $skin = 'tile';

        return $this->skin($skin, $atts);
    }

    /**
     * Do the custom skin and returns its output
     * @param array $atts
     * @param string $type
     * @param boolean $category
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vcustom($atts, $type = 'archive', $category = false, $tag = false)
    {
        $k = 'custom_' . $type;

        $shortcode = (isset($this->settings[$k]) && !empty($this->settings[$k])) ? stripslashes($this->settings[$k]) : '';

        // Add Category
        if ($category and is_tax('mec_category') and get_queried_object_id()) $shortcode = str_replace(']', ' category="' . get_queried_object_id() . '"]', $shortcode);
        if ($tag and is_tax('mec_tag') and get_queried_object_id()) $shortcode = str_replace(']', ' tag="' . get_queried_object_id() . '"]', $shortcode);

        if (trim($shortcode) && $this->is_allowed_custom_shortcode($shortcode)) return do_shortcode($shortcode);
        return '';
    }

    protected function is_allowed_custom_shortcode($shortcode)
    {
        preg_match_all('/\[\/?([A-Za-z0-9_-]+)/', $shortcode, $matches);
        $tags = array_unique($matches[1] ?? []);

        if (!count($tags)) return false;

        foreach ($tags as $tag)
        {
            if (!shortcode_exists($tag)) return false;

            $allowed = (bool) preg_match('/^mec([_-]|$)/i', $tag);
            if (!apply_filters('mec_allowed_custom_shortcode_tag', $allowed, $tag, $shortcode)) return false;
        }

        return true;
    }

    /**
     * Do the grid skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vgrid($atts = [])
    {
        $atts = apply_filters('mec_vgrid_atts', $atts);
        $skin = 'grid';

        return $this->skin($skin, $atts);
    }

    /**
     * Do the agenda skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vagenda($atts = [])
    {
        $atts = apply_filters('mec_vagenda_atts', $atts);
        $skin = 'agenda';

        return $this->skin($skin, $atts);
    }

    /**
     * Do the agenda skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vgeneral_calendar($atts = [])
    {
        $atts = ['is_categoy_page' => is_tax('mec_category')];
        $atts = apply_filters('mec_vgeneral_calendar_atts', $atts);
        $skin = 'general_calendar';

        return $this->skin($skin, $atts);
    }

    public function get_skins()
    {
        return [
            'monthly_view',
            'full_calendar',
            'yearly_view',
            'weekly_view',
            'daily_view',
            'timetable',
            'masonry',
            'list',
            'grid',
            'agenda',
            'map',
            'general_calendar',
            'custom',
        ];
    }

    /**
     * Do the default archive skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vdefault($atts = [])
    {
        $skins = $this->get_skins();
        foreach ($skins as $skin)
        {
            $skin_style = (isset($this->settings[$skin . '_archive_skin']) and trim($this->settings[$skin . '_archive_skin']) != '') ? $this->settings[$skin . '_archive_skin'] : null;
            if (!empty($skin_style))
            {
                $atts['sk-options'][$skin]['style'] = $skin_style;
            }
        }

        // Apply archive events method when archive skin is not custom
        $archive_skin = $this->settings['default_skin_archive'] ?? '';
        if ($archive_skin !== 'custom')
        {
            $archive_events_method = isset($this->settings['archive_events_method']) ? (string) $this->settings['archive_events_method'] : '3';
            if ($archive_events_method === '2') $atts['show_only_past_events'] = 1; // Expired only
            else if ($archive_events_method === '3') // Include past + future
            {
                $atts['show_past_events'] = 1;
                $atts['sk-options'][$archive_skin]['start_date_type'] = 'start_current_month';
            }
            else
            {
                // Upcoming: ensure past not included explicitly
                unset($atts['show_only_past_events']);

                // Avoid forcing include past
                $atts['show_past_events'] = 0;
            }
        }

        $monthly_skin = (isset($this->settings['monthly_view_archive_skin']) and trim($this->settings['monthly_view_archive_skin']) != '') ? $this->settings['monthly_view_archive_skin'] : 'clean';
        $list_skin = (isset($this->settings['list_archive_skin']) and trim($this->settings['list_archive_skin']) != '') ? $this->settings['list_archive_skin'] : 'standard';
        $grid_skin = (isset($this->settings['grid_archive_skin']) and trim($this->settings['grid_archive_skin']) != '') ? $this->settings['grid_archive_skin'] : 'classic';
        $timetable_skin = (isset($this->settings['timetable_archive_skin']) and trim($this->settings['timetable_archive_skin']) != '') ? $this->settings['timetable_archive_skin'] : 'modern';

        if (!isset($this->settings['default_skin_archive']) or (isset($this->settings['default_skin_archive']) and trim($this->settings['default_skin_archive']) == ''))
        {
            return $this->vdefaultfull($atts);
        }

        if ($this->settings['default_skin_archive'] == 'monthly_view') $content = $this->vmonth(array_merge(['sk-options' => ['monthly_view' => ['style' => $monthly_skin]]], $atts));
        else if ($this->settings['default_skin_archive'] == 'full_calendar')
        {
            // Liquid (and other addon styles) need the real full_calendar skin.
            // default_full_calendar is a classic-only archive wrapper and ignores full_calendar_archive_skin=liquid.
            $full_calendar_style = (isset($this->settings['full_calendar_archive_skin']) and trim((string) $this->settings['full_calendar_archive_skin']) !== '') ? $this->settings['full_calendar_archive_skin'] : 'classic';
            $content = (false !== strpos($full_calendar_style, 'liquid')) ? $this->vfull($atts) : $this->vdefaultfull($atts);
        }
        else if ($this->settings['default_skin_archive'] == 'yearly_view') $content = $this->vyear($atts);
        else if ($this->settings['default_skin_archive'] == 'weekly_view') $content = $this->vweek($atts);
        else if ($this->settings['default_skin_archive'] == 'daily_view') $content = $this->vday($atts);
        else if ($this->settings['default_skin_archive'] == 'timetable') $content = $this->vtimetable(array_merge(['sk-options' => ['timetable' => ['style' => $timetable_skin]]], $atts));
        else if ($this->settings['default_skin_archive'] == 'masonry') $content = $this->vmasonry($atts);
        else if ($this->settings['default_skin_archive'] == 'list') $content = $this->vlist(array_merge(['sk-options' => ['list' => ['style' => $list_skin]]], $atts));
        else if ($this->settings['default_skin_archive'] == 'grid') $content = $this->vgrid(array_merge(['sk-options' => ['grid' => ['style' => $grid_skin]]], $atts));
        else if ($this->settings['default_skin_archive'] == 'agenda') $content = $this->vagenda($atts);
        else if ($this->settings['default_skin_archive'] == 'map') $content = $this->vmap($atts);
        else if ($this->settings['default_skin_archive'] == 'general_calendar') $content = $this->vgeneral_calendar($atts);
        else if ($this->settings['default_skin_archive'] == 'custom') $content = $this->vcustom($atts);
        else $content = apply_filters('mec_default_skin_content', '');

        return $content;
    }

    /**
     * Do the single skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vsingle($atts)
    {
        // Force to array
        if (!is_array($atts)) $atts = [];

        // Get event ID
        $event_id = $atts['id'] ?? 0;

        $defaults = ['maximum_dates' => $this->settings['booking_maximum_dates'] ?? 6];
        $atts = apply_filters('mec_vsingle_atts', $this->parse($event_id, wp_parse_args($atts, $defaults)));

        $skin = 'single';
        return $this->skin($skin, $atts);
    }

    /**
     * Do the category archive skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vcategory($atts = [])
    {
        // Skin
        $skin = (isset($this->settings['default_skin_category']) and trim($this->settings['default_skin_category']) != '') ? $this->settings['default_skin_category'] : 'list';

        $skins = $this->get_skins();
        foreach ($skins as $sk)
        {
            $skin_style = (isset($this->settings[$sk . '_archive_skin']) and trim($this->settings[$sk . '_archive_skin']) != '') ? $this->settings[$sk . '_archive_skin'] : null;
            if (!empty($skin_style))
            {
                $atts['sk-options'][$sk]['style'] = $skin_style;
            }
        }

        // Show Only Expired Events
        if (isset($this->settings['category_events_method']) and $this->settings['category_events_method'] == 2) $atts['show_only_past_events'] = 1;

        $monthly_skin = (isset($this->settings['monthly_view_category_skin']) and trim($this->settings['monthly_view_category_skin']) != '') ? $this->settings['monthly_view_category_skin'] : 'classic';
        $list_skin = (isset($this->settings['list_category_skin']) and trim($this->settings['list_category_skin']) != '') ? $this->settings['list_category_skin'] : 'standard';
        $grid_skin = (isset($this->settings['grid_category_skin']) and trim($this->settings['grid_category_skin']) != '') ? $this->settings['grid_category_skin'] : 'classic';
        $timetable_skin = (isset($this->settings['timetable_category_skin']) and trim($this->settings['timetable_category_skin']) != '') ? $this->settings['timetable_category_skin'] : 'modern';

        if ($skin == 'full_calendar') $content = $this->vfull($atts);
        else if ($skin == 'yearly_view') $content = $this->vyear($atts);
        else if ($skin == 'masonry') $content = $this->vmasonry($atts);
        else if ($skin == 'timetable') $content = $this->vtimetable(array_merge($atts, ['sk-options' => ['timetable' => ['style' => $timetable_skin]]]));
        else if ($skin == 'monthly_view') $content = $this->vmonth(array_merge($atts, ['sk-options' => ['monthly_view' => ['style' => $monthly_skin]]]));
        else if ($skin == 'weekly_view') $content = $this->vweek($atts);
        else if ($skin == 'daily_view') $content = $this->vday($atts);
        else if ($skin == 'list') $content = $this->vlist(array_merge($atts, ['sk-options' => ['list' => ['style' => $list_skin]]]));
        else if ($skin == 'grid') $content = $this->vgrid(array_merge($atts, ['sk-options' => ['grid' => ['style' => $grid_skin]]]));
        else if ($skin == 'agenda') $content = $this->vagenda($atts);
        else if ($skin == 'map') $content = $this->vmap($atts);
        else if ($skin == 'general_calendar') $content = $this->vgeneral_calendar($atts);
        else if ($skin == 'custom') $content = $this->vcustom($atts, 'archive_category', true);
        else $content = apply_filters('mec_default_skin_content', '');

        return $content;
    }

    /**
     * Do the tag archive skin and returns its output
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function vtag($atts = [])
    {
        if (($this->settings['default_skin_archive'] ?? '') === 'custom') return $this->vcustom($atts, 'archive', false, true);

        return $this->vdefault($atts);
    }

    /**
     * Merge args
     * @param int $post_id
     * @param array $atts
     * @return array
     * @author Webnus <info@webnus.net>
     */
    public function parse($post_id, $atts = [])
    {
        if ($this->post_atts) return wp_parse_args($atts, $this->post_atts);

        $post_atts = [];
        if ($post_id) $post_atts = $this->main->get_post_meta($post_id);

        return wp_parse_args($atts, $post_atts);
    }

    /**
     * Run the skin and returns its output
     * @param string $skin
     * @param array $atts
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function skin($skin, $atts = [])
    {
        // Pro is Required for Some Skins
        if (!$this->main->getPRO() and in_array($skin, ['agenda', 'yearly_view', 'timetable', 'masonry', 'map', 'available_spot']))
        {
            return '';
        }

        if (empty($atts['instance_id'])) {
            $atts['instance_id'] = function_exists('wp_unique_id') ? wp_unique_id('mec') : uniqid('mec', false);
        }

        $path = MEC::import('app.skins.' . $skin, true, true);
        $skin_path = apply_filters('mec_skin_path', $skin);

        if ($skin_path != $skin and $this->file->exists($skin_path)) $path = $skin_path;
        if (!$this->file->exists($path))
        {
            return esc_html__('Skin controller does not exist.', 'modern-events-calendar-lite');
        }

        include_once $path;

        $skin_class_name = 'MEC_skin_' . $skin;

        // Create Skin Object Class
        $SKO = new $skin_class_name();
        $SKO->sf_base_atts = $atts;

        // Apply URL search filters on initial render to avoid flash of unfiltered content
        // Mirrors AJAX paths that call sf_apply() before initializing the skin.
        $sf = (isset($_REQUEST['sf']) and is_array($_REQUEST['sf'])) ? $this->main->sanitize_deep_array($_REQUEST['sf']) : [];
        if (!empty($sf))
        {
            $apply_sf_date = isset($_REQUEST['apply_sf_date']) ? sanitize_text_field($_REQUEST['apply_sf_date']) : 1;
            $atts = $SKO->sf_apply($atts, $sf, $apply_sf_date);
        }

        // Initialize the skin
        $SKO->initialize($atts);

        // Search Events If Not Found In Current Month
        $c = 0;
        $break = false;

        $original_year = $SKO->year;
        $original_month = $SKO->month;

        do
        {
            if ($c > 12 || $skin !== 'monthly_view') $break = true;

            if ($c && !$break)
            {
                if (intval($SKO->month) == 12)
                {
                    $SKO->year = intval($SKO->year) + 1;
                    $SKO->month = '01';
                }
                else $SKO->month = sprintf("%02d", intval($SKO->month) + 1);

                $SKO->start_date = date('Y-m-d', strtotime($SKO->year . '-' . $SKO->month . '-01'));

                $day = current_time('d');
                $SKO->active_day = $SKO->year . '-' . $SKO->month . '-' . $day;
            }

            // Fetch the events
            $events = $SKO->fetch();

            if ($break) break;

            // Auto Rotation is Disabled
            if (!isset($atts['auto_month_rotation']) || !$atts['auto_month_rotation']) break;

            $c++;
        } while (!count($events));

        if ($skin === 'monthly_view' && (!isset($events) || !count($events)))
        {
            $SKO->month = $original_month;
            $SKO->year = $original_year;
            $SKO->start_date = date('Y-m-d', strtotime($SKO->year . '-' . $SKO->month . '-01'));
            $SKO->active_day = $SKO->year . '-' . $SKO->month . '-' . current_time('d');
        }

        // Return the output
        return $SKO->output();
    }

    /**
     * Returns default skin
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function get_default_layout()
    {
        return apply_filters('mec_default_layout', 'list');
    }

    /**
     * Renders and returns all event data
     * @param int $post_id
     * @param string $content
     * @return \stdClass
     * @throws Exception
     * @author Webnus <info@webnus.net>
     */
    public function data($post_id, $content = null)
    {
        $cached = wp_cache_get($post_id, 'mec-events-data');
        if ($cached) return $cached;

        $data = new stdClass();

        // Post Data
        $data->ID = $post_id;
        $data->title = get_the_title($post_id);
        if (is_plugin_active('divi-single-builder/divi-single-builder.php'))
        {
            $data->content = get_the_content($post_id);
        }
        else
        {
            $content_fetched = $this->main->get_post_content($post_id);
            $data->content = is_null($content) ? (is_string($content_fetched) ? $content_fetched : '') : $content;
        }

        // All Post Data
        $post = get_post($post_id);
        $data->post = $post;

        // All Meta Data
        $meta = $this->main->get_post_meta($post_id, true);
        if (isset($meta['mec_notifications'])) unset($meta['mec_notifications']);
        if (isset($meta['mec_fees']) and is_array($meta['mec_fees']) and isset($meta['mec_fees'][':i:'])) unset($meta['mec_fees'][':i:']);

        $data->meta = $meta;

        // All MEC Data
        $data->mec = $this->main->get_mec_events_data($post_id);

        $allday = $data->meta['mec_allday'] ?? 0;
        $hide_time = $data->meta['mec_hide_time'] ?? 0;
        $hide_end_time = $this->main->hide_end_time_status($post_id);

        $start_timestamp = ((isset($meta['mec_start_day_seconds']) and isset($meta['mec_start_date'])) ? (strtotime($meta['mec_start_date']) + $meta['mec_start_day_seconds']) : (isset($meta['mec_start_date']) ? strtotime($meta['mec_start_date']) : 0));
        $end_timestamp = ((isset($meta['mec_end_day_seconds']) and isset($meta['mec_end_date'])) ? (strtotime($meta['mec_end_date']) + $meta['mec_end_day_seconds']) : (isset($meta['mec_end_date']) ? strtotime($meta['mec_end_date']) : 0));

        $start_time = $this->main->get_time($start_timestamp);
        $end_time = $this->main->get_time($end_timestamp);

        if ($hide_time)
        {
            $data->time = [
                'start' => '',
                'end' => '',
                'start_raw' => $start_time,
                'end_raw' => $end_time,
                'start_timestamp' => $start_timestamp,
                'end_timestamp' => $end_timestamp,
            ];
        }
        else if ($allday)
        {
            $data->time = [
                'start' => $this->main->m('all_day', esc_html__('All Day', 'modern-events-calendar-lite')),
                'end' => '',
                'start_raw' => $start_time,
                'end_raw' => $end_time,
                'start_timestamp' => $start_timestamp,
                'end_timestamp' => $end_timestamp,
            ];
        }
        else
        {
            $data->time = [
                'start' => $start_time,
                'end' => ($hide_end_time ? '' : $end_time),
                'start_raw' => $start_time,
                'end_raw' => $end_time,
                'start_timestamp' => $start_timestamp,
                'end_timestamp' => $end_timestamp,
            ];
        }

        // Hourly Schedules
        $meta_hourly_schedules = $meta['mec_hourly_schedules'] ?? [];
        $first_key = key($meta_hourly_schedules);

        $hourly_schedules = [];
        if (count($meta_hourly_schedules) and !isset($meta_hourly_schedules[$first_key]['schedules']))
        {
            $hourly_schedules[] = [
                'title' => esc_html__('Day 1', 'modern-events-calendar-lite'),
                'schedules' => $meta_hourly_schedules,
            ];
        }
        else $hourly_schedules = $meta_hourly_schedules;

        $data->hourly_schedules = $hourly_schedules;

        $tickets = (isset($meta['mec_tickets']) and is_array($meta['mec_tickets'])) ? $meta['mec_tickets'] : [];
        if (isset($tickets[':i:'])) unset($tickets[':i:']);

        $new_tickets = [];
        foreach ($tickets as $ticket_id => $ticket)
        {
            if (!is_numeric($ticket_id)) continue;

            $ticket['id'] = $ticket_id;
            $new_tickets[$ticket_id] = $ticket;
        }

        $data->tickets = $new_tickets;
        $data->color = $meta['mec_color'] ?? '';
        $data->permalink = ((isset($meta['mec_read_more']) and filter_var($meta['mec_read_more'], FILTER_VALIDATE_URL)) ? $meta['mec_read_more'] : get_post_permalink($post_id));

        // Thumbnails
        $thumbnail = get_the_post_thumbnail($post, 'thumbnail', ['data-mec-postid' => $post_id]);
        $thumblist = get_the_post_thumbnail($post, 'thumblist', ['data-mec-postid' => $post_id]);
        $gridsquare = get_the_post_thumbnail($post, 'gridsquare', ['data-mec-postid' => $post_id]);
        $meccarouselthumb = get_the_post_thumbnail($post, 'meccarouselthumb', ['data-mec-postid' => $post_id]);
        $medium = get_the_post_thumbnail($post, 'medium', ['data-mec-postid' => $post_id]);
        $large = get_the_post_thumbnail($post, 'large', ['data-mec-postid' => $post_id]);
        $full = get_the_post_thumbnail($post, 'full', ['data-mec-postid' => $post_id]);
        $tileview = get_the_post_thumbnail($post, 'tileview', ['data-mec-postid' => $post_id]);

        if (trim($thumbnail) == '' and trim($medium) != '') $thumbnail = preg_replace("/height=\"[0-9]*\"/", 'height="150"', preg_replace("/width=\"[0-9]*\"/", 'width="150"', $medium));
        else if (trim($thumbnail) == '' and trim($large) != '') $thumbnail = preg_replace("/height=\"[0-9]*\"/", 'height="150"', preg_replace("/width=\"[0-9]*\"/", 'width="150"', $large));

        $dataThumbnails = apply_filters('mec-render-data-thumbnails', [
            'thumbnail' => $thumbnail,
            'thumblist' => $thumblist,
            'gridsquare' => $gridsquare,
            'meccarouselthumb' => $meccarouselthumb,
            'medium' => $medium,
            'large' => $large,
            'full' => $full,
            'tileview' => $tileview,
        ], $post_id);

        $data->thumbnails = $dataThumbnails;

        // Featured image URLs
        $dataFeaturedImage = apply_filters('mec-render-data-featured-image', [
            'thumbnail' => esc_url($this->main->get_post_thumbnail_url($post_id, 'thumbnail')),
            'thumblist' => esc_url($this->main->get_post_thumbnail_url($post_id, 'thumblist')),
            'gridsquare' => esc_url($this->main->get_post_thumbnail_url($post_id, 'gridsquare')),
            'meccarouselthumb' => esc_url($this->main->get_post_thumbnail_url($post_id, 'meccarouselthumb')),
            'medium' => esc_url($this->main->get_post_thumbnail_url($post_id, 'medium')),
            'large' => esc_url($this->main->get_post_thumbnail_url($post_id, 'large')),
            'full' => esc_url($this->main->get_post_thumbnail_url($post_id, 'full')),
            'tileview' => esc_url($this->main->get_post_thumbnail_url($post_id, 'tileview')),
        ], $post_id);

        $data->featured_image = $dataFeaturedImage;

        $taxonomies = ['mec_label', 'mec_location', 'mec_category', apply_filters('mec_taxonomy_tag', '')];

        if (!isset($this->settings['organizers_status']) || $this->settings['organizers_status']) $taxonomies[] = 'mec_organizer';
        if ($this->getPRO() and isset($this->settings['sponsors_status']) and $this->settings['sponsors_status']) $taxonomies[] = 'mec_sponsor';

        $terms = wp_get_post_terms($post_id, $taxonomies, ['fields' => 'all']);
        foreach ($terms as $term)
        {
            // First Validation
            if (!isset($term->taxonomy)) continue;

            if ($term->taxonomy == 'mec_label') $data->labels[$term->term_id] = ['id' => $term->term_id, 'name' => $term->name, 'color' => get_metadata('term', $term->term_id, 'color', true), 'style' => get_metadata('term', $term->term_id, 'style', true)];
            else if ($term->taxonomy == 'mec_organizer') $data->organizers[$term->term_id] = ['id' => $term->term_id, 'name' => $term->name, 'tel' => get_metadata('term', $term->term_id, 'tel', true), 'email' => get_metadata('term', $term->term_id, 'email', true), 'url' => get_metadata('term', $term->term_id, 'url', true), 'page_label' => get_metadata('term', $term->term_id, 'page_label', true), 'thumbnail' => get_metadata('term', $term->term_id, 'thumbnail', true)];
            else if ($term->taxonomy == 'mec_location')
            {
                $locations = ['id' => $term->term_id, 'name' => $term->name, 'address' => get_metadata('term', $term->term_id, 'address', true), 'opening_hour' => get_metadata('term', $term->term_id, 'opening_hour', true), 'latitude' => get_metadata('term', $term->term_id, 'latitude', true), 'longitude' => get_metadata('term', $term->term_id, 'longitude', true), 'url' => get_metadata('term', $term->term_id, 'url', true), 'tel' => get_metadata('term', $term->term_id, 'tel', true), 'thumbnail' => get_metadata('term', $term->term_id, 'thumbnail', true)];
                $data->locations[$term->term_id] = apply_filters('mec_map_load_location_terms', $locations, $term);
            }
            else if ($term->taxonomy == 'mec_category')
            {
                $data->categories[$term->term_id] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'icon' => get_metadata('term', $term->term_id, 'mec_cat_icon', true),
                    'color' => get_metadata('term', $term->term_id, 'mec_cat_color', true),
                ];
            }
            else if ($term->taxonomy == apply_filters('mec_taxonomy_tag', '')) $data->tags[$term->term_id] = ['id' => $term->term_id, 'name' => $term->name];
            else if ($term->taxonomy == 'mec_sponsor')
            {
                $data->sponsors[$term->term_id] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'link' => get_metadata('term', $term->term_id, 'link', true),
                    'logo' => get_metadata('term', $term->term_id, 'logo', true),
                ];
            }
        }

        // Speakers
        if (isset($this->settings['speakers_status']) and $this->settings['speakers_status'])
        {
            $terms = wp_get_post_terms($post_id, 'mec_speaker', [
                'fields' => 'all',
            ]);

            usort($terms, function ($a, $b)
            {
                $a_index = get_metadata('term', $a->term_id, 'mec_index', true);
                $b_index = get_metadata('term', $b->term_id, 'mec_index', true);

                $a_index = $a_index === '' ? 99 : (float) $a_index;
                $b_index = $b_index === '' ? 99 : (float) $b_index;

                if ($a_index === $b_index) return strnatcasecmp($a->name, $b->name);
                return $a_index <=> $b_index;
            });

            foreach ($terms as $term)
            {
                $speaker_type = get_metadata('term', $term->term_id, 'type', true);

                $data->speakers[$term->term_id] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'type' => $speaker_type ?: 'person',
                    'job_title' => get_metadata('term', $term->term_id, 'job_title', true),
                    'tel' => get_metadata('term', $term->term_id, 'tel', true),
                    'email' => get_metadata('term', $term->term_id, 'email', true),
                    'facebook' => get_metadata('term', $term->term_id, 'facebook', true),
                    'twitter' => get_metadata('term', $term->term_id, 'twitter', true),
                    'gplus' => get_metadata('term', $term->term_id, 'gplus', true),
                    'thumbnail' => get_metadata('term', $term->term_id, 'thumbnail', true),
                ];
            }
        }

        // Event Fields
        $fields = $this->main->get_event_fields();
        if (!is_array($fields)) $fields = [];

        $fields_data = (isset($data->meta['mec_fields']) and is_array($data->meta['mec_fields'])) ? $data->meta['mec_fields'] : get_post_meta($post_id, 'mec_fields', true);
        if (!is_array($fields_data)) $fields_data = [];

        foreach ($fields as $f => $field)
        {
            if (!is_numeric($f)) continue;
            if (!isset($field['label'])) continue;

            $field_value = isset($fields_data[$f]) ? (is_array($fields_data[$f]) ? implode(', ', $fields_data[$f]) : $fields_data[$f]) : '';
            $data->fields[] = [
                'id' => $f,
                'type' => ($field['type'] ?? null),
                'label' => esc_html__(stripslashes($field['label']), 'modern-events-calendar-lite'),
                'value' => stripslashes($field_value),
            ];
        }

        // Timezone Object
        $data->TZO = $this->main->get_TZO($post_id);

        // Add mec event past index to array.
        $end_date = (isset($data->meta['mec_date']['end']) and isset($data->meta['mec_date']['end']['date'])) ? $data->meta['mec_date']['end']['date'] : current_time('Y-m-d H:i:s');

        $e_time = '';
        $e_time .= sprintf("%02d", ($data->meta['mec_date']['end']['hour'] ?? '6')) . ':';
        $e_time .= sprintf("%02d", ($data->meta['mec_date']['end']['minutes'] ?? '0'));
        $e_time .= isset($data->meta['mec_date']['end']['ampm']) ? trim($data->meta['mec_date']['end']['ampm']) : 'PM';

        $end_time = date('D M j Y G:i:s', strtotime($end_date . ' ' . $e_time));

        $d1 = new DateTime(current_time("D M j Y G:i:s"));
        $d2 = new DateTime($end_time);

        if ($d2 < $d1) $data->meta['event_past'] = true;
        else $data->meta['event_past'] = false;

        // Apply Filters
        $data = apply_filters('mec_render_event_data', $data, $post_id);

        // Set to cache
        wp_cache_set($post_id, $data, 'mec-events-data', 43200);

        //Edited Occurrences
        $settings = $this->main->get_settings();
        $edit_per_occurrences = [];

        if (isset($settings['per_occurrences_status']) and $settings['per_occurrences_status'])
        {
            $occ = new MEC_feature_occurrences();
            $occurrences = $occ->get_all_occurrences($post_id);

            foreach ($occurrences as $occurrence)
            {
                $date_start = date('Y-m-d', $occurrence['occurrence']);
                $params = json_decode($occurrence['params'], true);
                $params['location'] = (isset($params['location_id']) ? $this->main->get_location_data($params['location_id']) : []);
                $params['organizer'] = (isset($params['organizer_id']) ? $this->main->get_organizer_data($params['organizer_id']) : []);
                $edit_per_occurrences[$date_start] = $params;
            }
        }

        $data->edited_occurrences = $edit_per_occurrences;

        return $data;
    }

    /**
     * @param $event
     * @param MEC_skins $skin
     * @param int $serie
     * @return mixed
     */
    public function after_render($event, $skin, $serie = 1)
    {
        // If event is custom days and current date is available
        if (isset($event->data) and isset($event->data->meta) and isset($event->data->meta['mec_repeat_type']) and $event->data->meta['mec_repeat_type'] === 'custom_days' and isset($event->data->mec) and isset($event->data->mec->days) and isset($event->date) and is_array($event->date) and isset($event->date['start']) and isset($event->date['start']['date']))
        {
            // Time is already available
            if (isset($event->date['start']['hour']))
            {
                $hide_time = $event->data->meta['mec_hide_time'] ?? 0;
                $hide_end_time = $this->main->hide_end_time_status($event->ID);

                $s_hour = $event->date['start']['hour'];
                if (strtoupper($event->date['start']['ampm']) == 'AM' and $s_hour == '0') $s_hour = 12;

                $e_hour = $event->date['end']['hour'];
                if (strtoupper($event->date['end']['ampm']) == 'AM' and $e_hour == '0') $e_hour = 12;

                $start_time = $event->date['start']['date'] . ' ' . sprintf("%02d", $s_hour) . ':' . sprintf("%02d", $event->date['start']['minutes']) . ' ' . $event->date['start']['ampm'];
                $end_time = $event->date['end']['date'] . ' ' . sprintf("%02d", $e_hour) . ':' . sprintf("%02d", $event->date['end']['minutes']) . ' ' . $event->date['end']['ampm'];

                $start_timestamp = strtotime($start_time);
                $end_timestamp = strtotime($end_time);

                $st = $this->main->get_time($start_timestamp);
                $et = $this->main->get_time($end_timestamp);

                $allday = $event->data->meta['mec_allday'] ?? 0;
                if ($allday)
                {
                    $st = $this->main->m('all_day', esc_html__('All Day', 'modern-events-calendar-lite'));
                    $et = '';
                }

                $event->data->time = [
                    'start' => ($hide_time ? '' : $st),
                    'end' => (($hide_time or $hide_end_time) ? '' : $et),
                    'start_raw' => $st,
                    'end_raw' => $et,
                    'start_timestamp' => $start_timestamp,
                    'end_timestamp' => $end_timestamp,
                ];
            }
            // Detect the time when not available
            else
            {
                $multiple_day_show_method = \MEC\Settings\Settings::getInstance()->get_settings('multiple_day_show_method');
                $days_str = $event->data->mec->days;

                if (trim($days_str))
                {
                    $original_start_date = $event->data->meta['mec_start_date'];
                    $p_start_date = $event->date['start']['date'];
                    $allday = $event->data->meta['mec_allday'] ?? 0;

                    // Do not change the hour if it is the first serie of the event
                    if (!($original_start_date == $p_start_date and $serie == 1))
                    {
                        if ($original_start_date == $p_start_date) $serie -= 1;
                        $periods = explode(',', $days_str);

                        $datetime_timestamp = strtotime($p_start_date);

                        $p = 0;
                        foreach ($periods as $period)
                        {
                            $ex = explode(':', $period);
                            $s_date = $ex[0] ?? false;
                            $e_date = $ex[1] ?? false;

                            if (!$s_date || ($p_start_date !== $s_date && 'all_days' !== $multiple_day_show_method)) continue;

                            $sd_timestamp = strtotime($s_date);
                            if ($e_date)
                            {
                                $ed_timestamp = strtotime($e_date);
                                if (!($datetime_timestamp >= $sd_timestamp && $datetime_timestamp <= $ed_timestamp && isset($ex[2]) && isset($ex[3]))) continue;
                            }

                            $pos = strpos($ex[2], '-');
                            if ($pos !== false) $ex[2] = substr_replace($ex[2], ':', $pos, 1);

                            $pos = strpos($ex[3], '-');
                            if ($pos !== false) $ex[3] = substr_replace($ex[3], ':', $pos, 1);

                            $start_time = $s_date . ' ' . str_replace('-', ' ', $ex[2]);
                            $end_time = $e_date . ' ' . str_replace('-', ' ', $ex[3]);

                            // Expired Occurrence
                            if (strtotime($start_time) < current_time('timestamp') && (!isset($skin->atts['show_past_events']) || !$skin->atts['show_past_events']) && !$skin->show_only_expired_events) continue;

                            $p++;
                            if ($p !== $serie) continue;

                            $this->add_time_to_event($event, $start_time, $end_time, $allday);
                        }
                    }

                    // Do not show expired occurrences
                    if (!$skin->args['mec-past-events'])
                    {
                        $periods = explode(',', $days_str);
                        $current_time = current_time('timestamp');

                        if ($event->data->time['start_timestamp'] < $current_time)
                        {
                            foreach ($periods as $period)
                            {
                                $ex = explode(':', $period);
                                $s_date = $ex[0] ?? '';
                                $e_date = $ex[1] ?? '';

                                if (!$s_date or !$e_date) continue;

                                $s_time = $ex[2] ?? '';
                                $e_time = $ex[3] ?? '';

                                $pos = strpos($s_time, '-');
                                if ($pos !== false) $s_time = substr_replace($s_time, ':', $pos, 1);

                                $pos = strpos($e_time, '-');
                                if ($pos !== false) $e_time = substr_replace($e_time, ':', $pos, 1);

                                $start_time = trim($s_date . ' ' . str_replace('-', ' ', $s_time));
                                $end_time = trim($e_date . ' ' . str_replace('-', ' ', $e_time));

                                if (strtotime($start_time) < $current_time) continue;

                                $this->add_time_to_event($event, $start_time, $end_time, $allday);
                            }
                        }
                    }
                }
            }
        }
        // If not custom days
        else if (isset($event->data) and isset($event->data->time) and isset($event->data->time['start_raw']) and isset($event->data->time['end_raw']) and isset($event->date) and isset($event->date['start']) and isset($event->date['end']))
        {
            $start_time = $event->date['start']['date'] . ' ' . $event->data->time['start_raw'];
            $end_time = $event->date['end']['date'] . ' ' . $event->data->time['end_raw'];

            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);

            if ((!$start_timestamp or !$end_timestamp) and isset($event->data->meta['mec_date']) and isset($event->data->meta['mec_date']['start']) and isset($event->data->meta['mec_date']['start']['hour']) and isset($event->data->meta['mec_date']['end']) and isset($event->data->meta['mec_date']['end']['hour']))
            {
                $start_time = $event->date['start']['date'] . ' ' . sprintf("%02d", $event->data->meta['mec_date']['start']['hour']) . ':' . sprintf("%02d", $event->data->meta['mec_date']['start']['minutes']) . ' ' . $event->data->meta['mec_date']['start']['ampm'];
                $end_time = $event->date['end']['date'] . ' ' . sprintf("%02d", $event->data->meta['mec_date']['end']['hour']) . ':' . sprintf("%02d", $event->data->meta['mec_date']['end']['minutes']) . ' ' . $event->data->meta['mec_date']['end']['ampm'];

                $start_timestamp = strtotime($start_time);
                $end_timestamp = strtotime($end_time);
            }

            if ($start_timestamp and $end_timestamp)
            {
                $event->data->time['start_timestamp'] = $start_timestamp;
                $event->data->time['end_timestamp'] = $end_timestamp;
            }
        }

        // Fill Start and End Dates
        if (!isset($event->date['start']['hour']) or !isset($event->date['end']['hour']))
        {
            $s_hour = $s_minutes = $s_ampm = $e_hour = $e_minutes = $e_ampm = null;

            if (isset($event->data->meta['mec_date']) and isset($event->data->meta['mec_date']['start']) and is_array($event->data->meta['mec_date']['start']))
            {
                $s_hour = sprintf("%02d", $event->data->meta['mec_date']['start']['hour']);
                $s_minutes = sprintf("%02d", $event->data->meta['mec_date']['start']['minutes']);
                $s_ampm = strtolower($event->data->meta['mec_date']['start']['ampm']);
            }

            if (isset($event->data->meta['mec_date']) and isset($event->data->meta['mec_date']['end']) and is_array($event->data->meta['mec_date']['end']))
            {
                $e_hour = sprintf("%02d", $event->data->meta['mec_date']['end']['hour']);
                $e_minutes = sprintf("%02d", $event->data->meta['mec_date']['end']['minutes']);
                $e_ampm = strtolower($event->data->meta['mec_date']['end']['ampm']);
            }

            if (isset($event->data->time) and isset($event->data->time['start_timestamp']) and $event->data->time['start_timestamp'])
            {
                $s_hour = date('h', $event->data->time['start_timestamp']);
                $s_minutes = date('i', $event->data->time['start_timestamp']);
                $s_ampm = date('a', $event->data->time['start_timestamp']);
            }

            if (isset($event->data->time) and isset($event->data->time['end_timestamp']) and $event->data->time['end_timestamp'])
            {
                $e_hour = date('h', $event->data->time['end_timestamp']);
                $e_minutes = date('i', $event->data->time['end_timestamp']);
                $e_ampm = date('a', $event->data->time['end_timestamp']);
            }

            $start_time = '';
            $end_time = '';

            if (isset($event->date['start']['date']))
            {
                $start_time = $event->date['start']['date'] . ' ' . sprintf("%02d", $s_hour) . ':' . sprintf("%02d", $s_minutes) . ' ' . $s_ampm;
            }

            if (isset($event->date['end']['date']))
            {
                $end_time = $event->date['end']['date'] . ' ' . sprintf("%02d", $e_hour) . ':' . sprintf("%02d", $e_minutes) . ' ' . $e_ampm;
            }

            if ($s_hour and $s_minutes and $s_ampm and strtotime($start_time))
            {
                $d = ((isset($event->date['start']) and is_array($event->date['start'])) ? $event->date['start'] : []);
                $event->date['start'] = array_merge($d, [
                    'hour' => sprintf("%02d", $s_hour),
                    'minutes' => sprintf("%02d", $s_minutes),
                    'ampm' => $s_ampm,
                    'timestamp' => strtotime($start_time),
                ]);
            }

            if ($e_hour and $e_minutes and $e_ampm and strtotime($end_time))
            {
                $d = ((isset($event->date['end']) and is_array($event->date['end'])) ? $event->date['end'] : []);
                $event->date['end'] = array_merge($d, [
                    'hour' => sprintf("%02d", $e_hour),
                    'minutes' => sprintf("%02d", $e_minutes),
                    'ampm' => $e_ampm,
                    'timestamp' => strtotime($end_time),
                ]);
            }
        }

        if ($skin->skin != 'single' and !($skin->multiple_days_method == 'first_day' or ($skin->multiple_days_method == 'first_day_listgrid' and in_array($skin->skin, ['list', 'grid', 'slider', 'carousel', 'agenda', 'tile']))))
        {
            // MEC Cache
            $cache = $this->getCache();

            // Cache Key
            $key = $event->data->ID . '-' . $event->date['end']['date'];

            // Is Midnight Event
            $midnight = $this->main->is_midnight_event($event);

            // Improve Time for Multiple Day Events
            if ($cache->has($key) or ($event->date['start']['date'] !== $event->date['end']['date'] and !$midnight))
            {
                $allday = $event->data->meta['mec_allday'] ?? 0;
                $hide_time = $event->data->meta['mec_hide_time'] ?? 0;
                $hide_end_time = $this->main->hide_end_time_status($event->ID);

                // Get From Cache (Last Day)
                if ($cache->has($key) and $event->date['start']['date'] === $event->date['end']['date'])
                {
                    [$new_start_time, $new_end_time] = $cache->get($key);

                    // Delete the Cache
                    $cache->delete($key);
                }
                // Get From Cache (Between Days)
                else if ($cache->has($key) and $event->date['start']['date'] !== $event->date['end']['date'])
                {
                    $new_start_time = $this->main->get_time(0);
                    $new_end_time = $this->main->get_time((24 * 3600));

                    $allday = 1;
                }
                // First Day
                else
                {
                    $new_start_time = $event->data->time['start_raw'];
                    $new_end_time = $skin->skin === 'general_calendar' ? $event->data->time['end_raw'] : $this->main->get_time((24 * 3600));
                    $second_start_time = $this->main->get_time(0);
                    $second_end_time = $event->data->time['end_raw'];

                    // Set to Cache
                    $cache->set($key, [$second_start_time, $second_end_time]);
                }

                // Flag to Multiple Day
                $event->data->multipleday = 1;

                $event->data->time['start_raw'] = $new_start_time;
                $event->data->time['end_raw'] = $new_end_time;

                if ($hide_time)
                {
                    $event->data->time['start'] = '';
                    $event->data->time['end'] = '';
                }
                else if ($allday)
                {
                    $event->data->time['start'] = $this->main->m('all_day', esc_html__('All Day', 'modern-events-calendar-lite'));
                    $event->data->time['end'] = '';
                }
                else
                {
                    $event->data->time['start'] = $new_start_time;
                    $event->data->time['end'] = ($hide_end_time ? '' : $new_end_time);
                }
            }
        }

        return $event;
    }

    public function add_time_to_event(&$event, $start_datetime, $end_datetime, $allday = false)
    {
        $hide_time = $event->data->meta['mec_hide_time'] ?? 0;
        $hide_end_time = $this->main->hide_end_time_status($event->ID);

        $start_timestamp = strtotime($start_datetime);
        $end_timestamp = strtotime($end_datetime);

        $st = $this->main->get_time($start_timestamp);
        $et = $this->main->get_time($end_timestamp);

        if ($allday)
        {
            $st = $this->main->m('all_day', esc_html__('All Day', 'modern-events-calendar-lite'));
            $et = '';
        }

        $event->data->time = [
            'start' => ($hide_time ? '' : $st),
            'end' => (($hide_time or $hide_end_time) ? '' : $et),
            'start_raw' => $st,
            'end_raw' => $et,
            'start_timestamp' => $start_timestamp,
            'end_timestamp' => $end_timestamp,
        ];
    }

    /**
     * Renders and Returns event dats
     * @param int $event_id
     * @param object $event
     * @param int $maximum
     * @param string $today
     * @return array
     * @author Webnus <info@webnus.net>
     */
    public function dates($event_id, $event = null, $maximum = 6, $today = '')
    {
        if (!$today) $today = date('Y-m-d');

        // Original Start Date
        $original_start_date = $today;
        $dates = [];

        // Get event data if it is NULL
        if (is_null($event))
        {
            $event = new stdClass();
            $event->meta = $this->main->get_post_meta($event_id, true);
            $event->mec = $this->db->select("SELECT * FROM `#__mec_events` WHERE `post_id`='$event_id'", "loadObject");
        }

        $start_date = $event->meta['mec_date']['start'] ?? [];
        $end_date = $event->meta['mec_date']['end'] ?? [];
        $first_occurrence = $event->meta['mec_start_date'] ?? $today;

        // Return empty array if date is not valid
        if (!isset($start_date['date']) || !strtotime($start_date['date'])) return $dates;

        // Return empty array if mec data is not exists on mec_events table
        if (!isset($event->mec->end)) return $dates;

        $allday = $event->meta['mec_allday'] ?? 0;
        $hide_time = $event->meta['mec_hide_time'] ?? 0;

        $event_period = $this->main->date_diff($start_date['date'], $end_date['date']);
        $event_period_days = $event_period ? $event_period->days : 0;

        $finish_date = ['date' => $event->mec->end, 'hour' => $event->meta['mec_date']['end']['hour'], 'minutes' => $event->meta['mec_date']['end']['minutes'], 'ampm' => $event->meta['mec_date']['end']['ampm']];

        $settings = $this->main->get_settings();
        if (empty($settings['exceptional_days']))
        {
            $exceptional_days = [];
        }
        else
        {
            $exceptional_days = isset($event->mec->not_in_days) && trim($event->mec->not_in_days) ? explode(',', trim($event->mec->not_in_days, ', ')) : [];
            $exceptional_days = $this->main->add_global_exceptional_days($exceptional_days);
        }

        // Event Passed
        $past = $this->main->is_past($finish_date['date'], $today);

        // Event is not passed for custom days
        if ($past && isset($event->meta['mec_repeat_type']) && $event->meta['mec_repeat_type'] == 'custom_days') $past = 0;

        // Normal event
        if (isset($event->mec->repeat) && $event->mec->repeat == '0')
        {
            $dates[] = $this->add_timestamps([
                'start' => $start_date,
                'end' => $end_date,
                'allday' => $allday,
                'hide_time' => $hide_time,
                'past' => $past,
            ]);
        }
        else if ($past)
        {
            $dates[] = $this->add_timestamps([
                'start' => $start_date,
                'end' => $end_date,
                'allday' => $allday,
                'hide_time' => $hide_time,
                'past' => $past,
            ]);
        }
        else
        {
            $repeat_type = $event->meta['mec_repeat_type'];

            if (in_array($repeat_type, ['daily', 'weekly']))
            {
                $repeat_interval = $event->meta['mec_repeat_interval'];

                $date_interval = $this->main->date_diff($start_date['date'], date('Y-m-d', strtotime($today)));
                $passed_days = $date_interval ? $date_interval->days : 0;

                // Check if date interval is negative (It means the event didn't start yet)
                if ($date_interval and $date_interval->invert == 1) $remained_days_to_next_repeat = $passed_days;
                else $remained_days_to_next_repeat = $repeat_interval - fmod($passed_days, $repeat_interval);

                $start_date = date('Y-m-d', strtotime('+' . $remained_days_to_next_repeat . ' Days', strtotime($today)));

                if (
                    !$this->main->is_date_after($finish_date['date'], $start_date) &&
                    $this->main->is_date_after($first_occurrence, $start_date, true) &&
                    !in_array($start_date, $exceptional_days)
                ) $dates[] = $this->add_timestamps([
                    'start' => ['date' => $start_date, 'hour' => $event->meta['mec_date']['start']['hour'], 'minutes' => $event->meta['mec_date']['start']['minutes'], 'ampm' => $event->meta['mec_date']['start']['ampm']],
                    'end' => ['date' => date('Y-m-d', strtotime('+' . $event_period_days . ' Days', strtotime($start_date))), 'hour' => $event->meta['mec_date']['end']['hour'], 'minutes' => $event->meta['mec_date']['end']['minutes'], 'ampm' => $event->meta['mec_date']['end']['ampm']],
                    'allday' => $allday,
                    'hide_time' => $hide_time,
                    'past' => 0,
                ]);

                for ($i = 2; $i <= $maximum; $i++)
                {
                    $start_date = date('Y-m-d', strtotime('+' . $repeat_interval . ' Days', strtotime($start_date)));

                    // Event Not Started
                    if (!$this->main->is_date_after($first_occurrence, $start_date, true)) continue;

                    // Event finished
                    if ($this->main->is_past($finish_date['date'], $start_date)) break;

                    if (!in_array($start_date, $exceptional_days)) $dates[] = $this->add_timestamps([
                        'start' => ['date' => $start_date, 'hour' => $event->meta['mec_date']['start']['hour'], 'minutes' => $event->meta['mec_date']['start']['minutes'], 'ampm' => $event->meta['mec_date']['start']['ampm']],
                        'end' => ['date' => date('Y-m-d', strtotime('+' . $event_period_days . ' Days', strtotime($start_date))), 'hour' => $event->meta['mec_date']['end']['hour'], 'minutes' => $event->meta['mec_date']['end']['minutes'], 'ampm' => $event->meta['mec_date']['end']['ampm']],
                        'allday' => $allday,
                        'hide_time' => $hide_time,
                        'past' => 0,
                    ]);
                }
            }
            else if (in_array($repeat_type, ['weekday', 'weekend', 'certain_weekdays']))
            {
                $date_interval = $this->main->date_diff($start_date['date'], $today);
                $passed_days = $date_interval ? $date_interval->days : 0;

                // Check if date interval is negative (It means the event didn't start yet)
                if ($date_interval and $date_interval->invert == 1) $today = date('Y-m-d', strtotime('+' . $passed_days . ' Days', strtotime($original_start_date)));

                $event_days = explode(',', trim($event->mec->weekdays, ', '));

                $today_id = date('N', strtotime($today));
                $found = 0;
                $i = 0;

                while ($found < $maximum)
                {
                    if ($this->main->is_past($finish_date['date'], $today)) break;

                    if (!in_array($today_id, $event_days))
                    {
                        $today = date('Y-m-d', strtotime('+1 Days', strtotime($today)));
                        $today_id = date('N', strtotime($today));

                        $i++;
                        continue;
                    }

                    $start_date = $today;
                    if (!in_array($start_date, $exceptional_days)) $dates[] = $this->add_timestamps([
                        'start' => ['date' => $start_date, 'hour' => $event->meta['mec_date']['start']['hour'], 'minutes' => $event->meta['mec_date']['start']['minutes'], 'ampm' => $event->meta['mec_date']['start']['ampm']],
                        'end' => ['date' => date('Y-m-d', strtotime('+' . $event_period_days . ' Days', strtotime($start_date))), 'hour' => $event->meta['mec_date']['end']['hour'], 'minutes' => $event->meta['mec_date']['end']['minutes'], 'ampm' => $event->meta['mec_date']['end']['ampm']],
                        'allday' => $allday,
                        'hide_time' => $hide_time,
                        'past' => 0,
                    ]);

                    $today = date('Y-m-d', strtotime('+1 Days', strtotime($today)));
                    $today_id = date('N', strtotime($today));

                    $found++;
                    $i++;
                }
            }
            else if ($repeat_type == 'monthly')
            {
                $repeat_interval = ((isset($event->meta) and isset($event->meta['mec_repeat_interval'])) ? max(1, $event->meta['mec_repeat_interval']) : 1);

                // Start from Event Start Date
                if (strtotime($start_date['date']) > strtotime($original_start_date)) $original_start_date = $start_date['date'];

                $event_days = explode(',', trim($event->mec->day, ', '));
                $event_start_day = $event_days[0];

                $diff = $this->main->date_diff($start_date['date'], $end_date['date']);
                $event_period_days = $diff->days;

                $found = 0;
                $i = 0;

                while ($found < $maximum)
                {
                    $t = strtotime('+' . $i . ' Months', strtotime($original_start_date));
                    if (!$t) break;

                    $today = date('Y-m-d', $t);
                    if ($this->main->is_past($finish_date['date'], $today)) break;

                    $year = date('Y', strtotime($today));
                    $month = date('m', strtotime($today));
                    $day = $event_start_day;
                    $hour = isset($event->meta['mec_date']['end']['hour']) ? sprintf('%02d', $event->meta['mec_date']['end']['hour']) : '06';
                    $minutes = isset($event->meta['mec_date']['end']['minutes']) ? sprintf('%02d', $event->meta['mec_date']['end']['minutes']) : '00';
                    $ampm = isset($event->meta['mec_date']['end']['ampm']) ? strtoupper($event->meta['mec_date']['end']['ampm']) : 'PM';

                    // Fix for 31st, 30th, 29th of some months
                    if (!checkdate((int) $month, (int) $day, (int) $year))
                    {
                        $i += $repeat_interval;
                        continue;
                    }

                    $start_date = $year . '-' . $month . '-' . $day;
                    $end_time = $hour . ':' . $minutes . ' ' . $ampm;

                    // Wrong Date & Time
                    if (!strtotime($start_date . ' ' . $end_time)) break;

                    if (strtotime($start_date . ' ' . $end_time) < strtotime($original_start_date))
                    {
                        $i += $repeat_interval;
                        continue;
                    }

                    if (!in_array($start_date, $exceptional_days)) $dates[] = $this->add_timestamps([
                        'start' => ['date' => $start_date, 'hour' => $event->meta['mec_date']['start']['hour'], 'minutes' => $event->meta['mec_date']['start']['minutes'], 'ampm' => $event->meta['mec_date']['start']['ampm']],
                        'end' => ['date' => date('Y-m-d', strtotime('+' . $event_period_days . ' Days', strtotime($start_date))), 'hour' => $event->meta['mec_date']['end']['hour'], 'minutes' => $event->meta['mec_date']['end']['minutes'], 'ampm' => $event->meta['mec_date']['end']['ampm']],
                        'allday' => $allday,
                        'hide_time' => $hide_time,
                        'past' => 0,
                    ]);

                    $found++;
                    $i += $repeat_interval;
                }
            }
            else if ($repeat_type == 'yearly')
            {
                // Start from Event Start Date
                if (strtotime($start_date['date']) > strtotime($original_start_date)) $original_start_date = $start_date['date'];

                $event_days = explode(',', trim($event->mec->day, ', '));
                $event_months = explode(',', trim($event->mec->month, ', '));

                $event_start_day = $event_days[0];
                $event_period_days = $this->main->date_diff($start_date['date'], $end_date['date'])->days;

                $event_start_year = date('Y', strtotime($original_start_date));
                $event_start_month = date('n', strtotime($original_start_date));

                $found = 0;
                $i = 0;

                while ($found < $maximum)
                {
                    $today = date('Y-m-d', strtotime($event_start_year . '-' . $event_start_month . '-' . $event_start_day));
                    if ($this->main->is_past($finish_date['date'], $today)) break;

                    $year = date('Y', strtotime($today));
                    $month = date('m', strtotime($today));

                    if (!in_array($month, $event_months))
                    {
                        if ($event_start_month == '12')
                        {
                            $event_start_month = 1;
                            $event_start_year += 1;
                        }
                        else $event_start_month += 1;

                        $i++;
                        continue;
                    }

                    $day = $event_start_day;

                    // Fix for 31st, 30th, 29th of some months
                    while (!checkdate($month, $day, $year)) $day--;

                    $event_date = $year . '-' . $month . '-' . $day;
                    if (strtotime($event_date) >= strtotime($original_start_date))
                    {
                        $start_date = $event_date;
                        if (!in_array($start_date, $exceptional_days)) $dates[] = $this->add_timestamps([
                            'start' => ['date' => $start_date, 'hour' => $event->meta['mec_date']['start']['hour'], 'minutes' => $event->meta['mec_date']['start']['minutes'], 'ampm' => $event->meta['mec_date']['start']['ampm']],
                            'end' => ['date' => date('Y-m-d', strtotime('+' . $event_period_days . ' Days', strtotime($start_date))), 'hour' => $event->meta['mec_date']['end']['hour'], 'minutes' => $event->meta['mec_date']['end']['minutes'], 'ampm' => $event->meta['mec_date']['end']['ampm']],
                            'allday' => $allday,
                            'hide_time' => $hide_time,
                            'past' => 0,
                        ]);

                        $found++;
                    }

                    if ($event_start_month == '12')
                    {
                        $event_start_month = 1;
                        $event_start_year += 1;
                    }
                    else $event_start_month += 1;

                    $i++;
                }
            }
            else if ($repeat_type == 'custom_days')
            {
                $custom_days = explode(',', $event->mec->days);

                // Add current time if we're checking today's events
                if ($today == current_time('Y-m-d')) $today .= ' ' . current_time('H:i:s');

                $found = 0;
                if ((strtotime($event->mec->start) + $event->meta['mec_start_day_seconds']) >= strtotime($today) and !in_array($event->mec->start, $exceptional_days))
                {
                    $dates[] = $this->add_timestamps([
                        'start' => ['date' => $event->mec->start, 'hour' => $event->meta['mec_date']['start']['hour'], 'minutes' => $event->meta['mec_date']['start']['minutes'], 'ampm' => $event->meta['mec_date']['start']['ampm']],
                        'end' => ['date' => $event->mec->end, 'hour' => $event->meta['mec_date']['end']['hour'], 'minutes' => $event->meta['mec_date']['end']['minutes'], 'ampm' => $event->meta['mec_date']['end']['ampm']],
                        'allday' => $allday,
                        'hide_time' => $hide_time,
                        'past' => 0,
                    ]);

                    $found++;
                }

                foreach ($custom_days as $custom_day)
                {
                    // Found maximum dates
                    if ($found >= $maximum) break;

                    $cday = explode(':', $custom_day);

                    $c_start = $cday[0];
                    if (isset($cday[2])) $c_start .= ' ' . str_replace('-', ' ', substr_replace($cday[2], ':', strpos($cday[2], '-'), 1));

                    // Date is past
                    if (strtotime($c_start) < strtotime($today)) continue;

                    $cday_start_hour = $event->meta['mec_date']['start']['hour'];
                    $cday_start_minutes = $event->meta['mec_date']['start']['minutes'];
                    $cday_start_ampm = $event->meta['mec_date']['start']['ampm'];

                    $cday_end_hour = $event->meta['mec_date']['end']['hour'];
                    $cday_end_minutes = $event->meta['mec_date']['end']['minutes'];
                    $cday_end_ampm = $event->meta['mec_date']['end']['ampm'];

                    if (isset($cday[2]) and isset($cday[3]))
                    {
                        $cday_start_ex = explode('-', $cday[2]);
                        $cday_start_hour = $cday_start_ex[0];
                        $cday_start_minutes = $cday_start_ex[1];
                        $cday_start_ampm = $cday_start_ex[2];

                        $cday_end_ex = explode('-', $cday[3]);
                        $cday_end_hour = $cday_end_ex[0];
                        $cday_end_minutes = $cday_end_ex[1];
                        $cday_end_ampm = $cday_end_ex[2];
                    }

                    if (!in_array($cday[0], $exceptional_days)) $dates[] = $this->add_timestamps([
                        'start' => ['date' => $cday[0], 'hour' => $cday_start_hour, 'minutes' => $cday_start_minutes, 'ampm' => $cday_start_ampm],
                        'end' => ['date' => $cday[1], 'hour' => $cday_end_hour, 'minutes' => $cday_end_minutes, 'ampm' => $cday_end_ampm],
                        'allday' => $allday,
                        'hide_time' => $hide_time,
                        'past' => 0,
                    ]);

                    $found++;
                }

                // No future date found so the event is passed
                if (!count($dates))
                {
                    $dates[] = $this->add_timestamps([
                        'start' => $start_date,
                        'end' => $finish_date,
                        'allday' => $allday,
                        'hide_time' => $hide_time,
                        'past' => $past,
                    ]);
                }
            }
            else if ($repeat_type == 'advanced')
            {
                // Start from Event Start Date
                if (strtotime($start_date['date']) > strtotime($today)) $today = $start_date['date'];

                // Get user specifed days of month for repeat
                $advanced_days = get_post_meta($event_id, 'mec_advanced_days', true);

                // Generate dates for event
                $event_info = ['start' => $start_date, 'end' => $end_date, 'allday' => $allday, 'hide_time' => $hide_time, 'finish_date' => $finish_date['date'], 'exceptional_days' => $exceptional_days, 'mec_repeat_end' => ((isset($event->meta['mec_repeat']) and isset($event->meta['mec_repeat']['end'])) ? $event->meta['mec_repeat']['end'] : ''), 'occurrences' => ((isset($event->meta['mec_repeat']) and isset($event->meta['mec_repeat']['end_at_occurrences'])) ? $event->meta['mec_repeat']['end_at_occurrences'] : '')];

                $dates = $this->generate_advanced_days($advanced_days, $event_info, $maximum, $today);
            }
        }

        return $dates;
    }

    /**
     *  Render advanced dates
     * @param array $advanced_days
     * @param array $event_info
     * @param int $maximum
     * @param string $referer_date
     * @param string $mode
     * @return array
     * @author Webnus <info@webnus.net>
     */
    public function generate_advanced_days($advanced_days = [], $event_info = [], $maximum = 6, $referer_date = null, $mode = 'render')
    {
        if (!count($advanced_days)) return [];
        if (!trim($referer_date)) $referer_date = date('Y-m-d', current_time('timestamp', 0));

        $levels = ['first', 'second', 'third', 'fourth', 'last'];
        $year = date('Y', strtotime($event_info['start']['date']));
        $dates = [];

        // Set last month for include current month results
        $month = date('m', strtotime('first day of last month', strtotime($event_info['start']['date'])));

        if ($month == '12') $year = $year - 1;

        $maximum = intval($maximum);
        $i = 0;

        // Event info
        $exceptional_days = array_key_exists('exceptional_days', $event_info) ? $event_info['exceptional_days'] : [];
        $start_date = $event_info['start'];
        $end_date = $event_info['end'];
        $allday = array_key_exists('allday', $event_info) ? $event_info['allday'] : 0;
        $hide_time = array_key_exists('hide_time', $event_info) ? $event_info['hide_time'] : 0;
        $finish_date = array_key_exists('finish_date', $event_info) ? $event_info['finish_date'] : '0000-00-00';
        $event_period = $this->main->date_diff($start_date['date'], $end_date['date']);
        $event_period_days = $event_period ? $event_period->days : 0;
        $mec_repeat_end = array_key_exists('mec_repeat_end', $event_info) ? $event_info['mec_repeat_end'] : '';
        $occurrences = array_key_exists('occurrences', $event_info) ? $event_info['occurrences'] : 0;

        // Include default start date to results
        if (!$this->main->is_past($start_date['date'], $referer_date) and !in_array($start_date['date'], $exceptional_days))
        {
            $dates[] = $this->add_timestamps([
                'start' => $start_date,
                'end' => $end_date,
                'allday' => $allday,
                'hide_time' => $hide_time,
                'past' => 0,
            ]);

            if ($mode == 'render') $i++;
        }

        while ($i < $maximum)
        {
            $start = null;

            foreach ($advanced_days as $day)
            {
                if ($i >= $maximum) break;

                // Explode $day value for example (Sun.1) to Sun and 1
                $d = explode('.', $day);

                // Set indexes for {$levels} index if number day is Last(Sun.l) then indexes set 4th {$levels} index
                $index = intval($d[1]) ? (intval($d[1]) - 1) : 4;

                // Generate date
                $date = date('Y-m-t', strtotime("{$year}-{$month}-01"));

                // Generate start date for example "first Sun of next month"
                $start = date('Y-m-d', strtotime("{$levels[$index]} {$d[0]} of next month", strtotime($date)));
                $end = date('Y-m-d', strtotime("+{$event_period_days} Days", strtotime($start)));

                // Occurence equals to the main start date
                if ($start === $start_date['date']) continue;

                // When ends repeat date set
                if ($mode == 'render' and $this->main->is_past($finish_date, $start)) continue;

                // Jump to next level if start date is past
                if ($this->main->is_past($start, $referer_date) or in_array($start, $exceptional_days)) continue;

                // Add dates
                $dates[] = $this->add_timestamps([
                    'start' => [
                        'date' => $start,
                        'hour' => $start_date['hour'],
                        'minutes' => $start_date['minutes'],
                        'ampm' => $start_date['ampm'],
                    ],
                    'end' => [
                        'date' => $end,
                        'hour' => $end_date['hour'],
                        'minutes' => $end_date['minutes'],
                        'ampm' => $end_date['ampm'],
                    ],
                    'allday' => $allday,
                    'hide_time' => $hide_time,
                    'past' => 0,
                ]);

                $i++;
            }

            // When ends repeat date set
            if ($mode == 'render' and $this->main->is_past($finish_date, $start)) break;

            // Change month and years for next resualts
            if (intval($month) == 12)
            {
                $year = intval($year) + 1;
                $month = '00';
            }

            $month = sprintf("%02d", intval($month) + 1);
        }

        if (($mode == 'render') and (trim($mec_repeat_end) == 'occurrences') and (count($dates) > $occurrences))
        {
            $max = strtotime(reset($dates)['start']['date']);
            $pos = 0;

            for ($i = 1; $i < count($dates); $i++)
            {
                if (strtotime($dates[$i]['start']['date']) > $max)
                {
                    $max = strtotime($dates[$i]['start']['date']);
                    $pos = $i;
                }
            }

            unset($dates[$pos]);
        }

        // Remove Duplicates
        $uniques = [];
        $timestamps = [];

        foreach ($dates as $key => $date)
        {
            $start_timestamp = $date['start']['timestamp'];
            $end_timestamp = $date['end']['timestamp'];
            $timestamp_key = $start_timestamp . '-' . $end_timestamp;

            if (isset($timestamps[$timestamp_key])) continue;

            $timestamps[$timestamp_key] = true;
            $uniques[] = $date;
        }

        // Sort
        usort($uniques, [$this, 'sort_dates']);

        return $uniques;
    }

    public function sort_dates($a, $b)
    {
        $a_timestamp = $a['start']['timestamp'];
        $b_timestamp = $b['end']['timestamp'];

        if ($a_timestamp == $b_timestamp) return 0;
        return ($a_timestamp > $b_timestamp) ? +1 : -1;
    }

    /**
     * Render markers
     * @param array $events
     * @param string $skin_style
     * @return array
     * @author Webnus <info@webnus.net>
     */
    public function markers($events, $skin_style = 'classic')
    {
        $date_format = (isset($this->settings['google_maps_date_format1']) and trim($this->settings['google_maps_date_format1'])) ? $this->settings['google_maps_date_format1'] : 'M d Y';

        $requested_location_id = isset($_REQUEST['sf'], $_REQUEST['sf']['location']) ? $_REQUEST['sf']['location'] : null;

        $markers = [];
        foreach ($events as $event)
        {
            if (!is_object($event)) continue;
            if (!isset($event->data->locations) or (isset($event->data->locations) and !is_array($event->data->locations))) continue;

            $locations = [];

            $main_location_id = get_post_meta($event->ID, 'mec_location_id', true);
            if (isset($event->date, $event->date['start'], $event->date['start']['timestamp'])) $main_location_id = MEC_feature_occurrences::param($event->ID, $event->date['start']['timestamp'], 'location_id', $main_location_id);

            $main_location_id = apply_filters('wpml_object_id', $main_location_id, 'mec_location', true);

            if ($main_location_id && $main_location_id > 1) $locations[] = $main_location_id;

            $additional_location_ids = get_post_meta($event->ID, 'mec_additional_location_ids', true);
            if (!is_array($additional_location_ids)) $additional_location_ids = [];

            $locations = array_merge($locations, $additional_location_ids);
            $locations = array_unique($locations);

            if ($requested_location_id && !is_array($requested_location_id))
            {
                if ($main_location_id !== $requested_location_id && !in_array($requested_location_id, $additional_location_ids)) continue;
                $locations = [$requested_location_id];
            }

            // No Locations
            if (!count($locations)) continue;

            foreach ($locations as $location_id)
            {
                $location_id = (int) $location_id;

                $latitude = get_term_meta($location_id, 'latitude', true);
                $longitude = get_term_meta($location_id, 'longitude', true);

                // No latitude/Longitude
                if (trim($latitude) == '' or trim($longitude) == '') continue;

                $location = get_term($location_id);
                $name = $location->name ?? '';

                $latitude = floatval($latitude);
                $longitude = floatval($longitude);

                $key = $latitude . ',' . $longitude;
                if (!isset($markers[$key]))
                {
                    $markers[$key] = [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'name' => $name,
                        'address' => get_term_meta($location_id, 'address', true),
                        'event_ids' => [$event->data->ID],
                        'lightbox' => $this->main->get_marker_lightbox($event, $date_format, $skin_style),
                    ];
                }
                else
                {
                    // Only add event if it's not already added to this marker
                    if (!in_array($event->data->ID, $markers[$key]['event_ids']))
                    {
                        $markers[$key]['event_ids'][] = $event->data->ID;
                        $markers[$key]['lightbox'] .= $this->main->get_marker_lightbox($event, $date_format, $skin_style);
                    }
                }
            }
        }

        $points = [];
        foreach ($markers as $key => $marker)
        {
            $points[$key] = $marker;

            $points[$key]['lightbox'] = '<div><div class="mec-event-detail mec-map-view-event-detail"><i class="mec-sl-map-marker"></i> ' . (trim($marker['address']) ? esc_html($marker['address']) : esc_html($marker['name'])) . '</div><div>' . MEC_kses::element($marker['lightbox']) . '</div></div>';
            $points[$key]['count'] = count($marker['event_ids']);
            $points[$key]['infowindow'] = $this->main->get_marker_infowindow($marker);
        }

        return apply_filters('mec_render_markers', $points);
    }

    public function add_timestamps($date)
    {
        $start = (isset($date['start']) and is_array($date['start'])) ? $date['start'] : [];
        $end = (isset($date['end']) and is_array($date['end'])) ? $date['end'] : [];

        if (!count($start) or !count($end)) return $date;

        $s_hour = $start['hour'];
        if (strtoupper($start['ampm']) == 'AM' and $s_hour == '0') $s_hour = 12;

        $e_hour = $end['hour'];
        if (strtoupper($end['ampm']) == 'AM' and $e_hour == '0') $e_hour = 12;

        $allday = ($date['allday'] ?? 0);

        // All Day Event
        if ($allday)
        {
            $s_hour = 12;
            $start['minutes'] = 1;
            $start['ampm'] = 'AM';

            $e_hour = 11;
            $end['minutes'] = 59;
            $end['ampm'] = 'PM';
        }

        $start_time = $start['date'] . ' ' . sprintf("%02d", $s_hour) . ':' . sprintf("%02d", $start['minutes']) . ' ' . $start['ampm'];
        $end_time = $end['date'] . ' ' . sprintf("%02d", $e_hour) . ':' . sprintf("%02d", $end['minutes']) . ' ' . $end['ampm'];

        $start['timestamp'] = strtotime($start_time);
        $end['timestamp'] = strtotime($end_time);

        $hide_time = $date['hide_time'] ?? 0;
        $past = $date['past'] ?? 0;

        return [
            'start' => $start,
            'end' => $end,
            'allday' => $allday,
            'hide_time' => $hide_time,
            'past' => $past,
        ];
    }
}

<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC countdown class.
 * @author Webnus <info@webnus.net>
 */
class MEC_skin_countdown extends MEC_skins
{
    /**
     * @var string
     */
    public $skin = 'countdown';

    public $event_id;
    public $date_format_style11;
    public $date_format_style21;
    public $date_format_style31;
    public $date_format_style32;
    public $date_format_style33;
    public $bg_color;
    public $category;

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
    }
    
    /**
     * Initialize the skin
     * @author Webnus <info@webnus.net>
     * @param array $atts
     */
    public function initialize($atts)
    {
        $this->atts = $atts;
        $this->skin_options = (isset($this->atts['sk-options']) and isset($this->atts['sk-options'][$this->skin])) ? $this->atts['sk-options'][$this->skin] : [];

        // Icons
        $this->icons = $this->main->icons(
            isset($this->atts['icons']) && is_array($this->atts['icons']) ? $this->atts['icons'] : []
        );
        
        // Date Formats
        $this->date_format_style11 = (isset($this->skin_options['date_format_style11']) and trim($this->skin_options['date_format_style11'])) ? $this->skin_options['date_format_style11'] : 'j F Y';
        $this->date_format_style21 = (isset($this->skin_options['date_format_style21']) and trim($this->skin_options['date_format_style21'])) ? $this->skin_options['date_format_style21'] : 'j F Y';
        
        $this->date_format_style31 = (isset($this->skin_options['date_format_style31']) and trim($this->skin_options['date_format_style31'])) ? $this->skin_options['date_format_style31'] : 'd';
        $this->date_format_style32 = (isset($this->skin_options['date_format_style32']) and trim($this->skin_options['date_format_style32'])) ? $this->skin_options['date_format_style32'] : 'F';
        $this->date_format_style33 = (isset($this->skin_options['date_format_style33']) and trim($this->skin_options['date_format_style33'])) ? $this->skin_options['date_format_style33'] : 'Y';
        
        // Background Color
        $this->bg_color = (isset($this->skin_options['bg_color']) and trim($this->skin_options['bg_color'])) ? $this->skin_options['bg_color'] : '#437df9';
        
        // Search Form Status
        $this->sf_status = false;
        
        $this->id = $this->get_skin_dom_id();
        
        // Set the ID
        if(!isset($this->atts['id'])) $this->atts['id'] = $this->id;
        
        // The style
        $this->style = $this->skin_options['style'] ?? 'style1';
        if($this->style == 'fluent' and !is_plugin_active('mec-fluent-layouts/mec-fluent-layouts.php')) $this->style = 'style1';

        // reason_for_cancellation
        $this->reason_for_cancellation = $this->skin_options['reason_for_cancellation'] ?? false;

        // display_label
        $this->display_label = $this->skin_options['display_label'] ?? false;
        
        // Override the style if the style forced by us in a widget etc
        if(isset($this->atts['style']) and trim($this->atts['style']) != '') $this->style = $this->atts['style'];
        
        // HTML class
        $this->html_class = '';
        if(isset($this->atts['html-class']) and trim($this->atts['html-class']) != '') $this->html_class = $this->atts['html-class'];
        
        // From Widget
        $this->widget = isset($this->atts['widget']) && trim($this->atts['widget']);
        
        // Init MEC
        $this->args['mec-skin'] = $this->skin;
        
        // Event ID
        $this->event_id = $this->skin_options['event_id'] ?? '-1';
        if(!get_post($this->event_id)) $this->event_id = '-1';

        // Category
        $this->category = isset($this->skin_options['category']) ? trim($this->skin_options['category'], ', ') : '';
    }
    
    /**
     * Search and returns the filtered events
     * @author Webnus <info@webnus.net>
     * @return array of objects
     */
    public function search()
    {
        $events = [];
        
        // Get next upcoming event ID
        if($this->event_id == '-1')
        {
            $events[] = $this->main->get_next_upcoming_event([
                'category' => $this->category,
            ]);
        }
        else
        {
            $rendered = $this->render->data($this->event_id, ($this->atts['content'] ?? ''));

            $now = current_time('Y-m-d H:i:s');

            $data = new stdClass();
            $data->ID = $this->event_id;
            $data->data = $rendered;
            $data->dates = $this->render->dates($this->event_id, $rendered, $this->maximum_dates, $now);
            $data->date = [];

            foreach($data->dates as $date)
            {
                if($this->main->is_past($date['start']['timestamp'], $now)) continue;

                $data->date = $date;
                break;
            }

            $events[] = $this->render->after_render($data, $this);
        }

        // custom sort events by publish date
        $events = apply_filters('mec_skin_events', $events, $this);

        return $events;
    }
}

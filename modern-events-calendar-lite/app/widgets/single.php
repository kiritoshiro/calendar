<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC Single Widget
 * @author Webnus <info@webnus.net>
 */
class MEC_single_widget extends WP_Widget
{
	/**
	 * Unique identifier.
	 */
	protected $widget_slug = 'MEC_single_widget';

	/**
	 * Constructor method
	 * @author Webnus <info@webnus.net>
	 */
	public function __construct()
	{
		parent::__construct($this->get_widget_slug(), esc_html__('MEC Single Sidebar Items', 'modern-events-calendar-lite'), ['classname' => $this->get_widget_slug() . '-class', 'description' => esc_html__('To manage event details page elements.', 'modern-events-calendar-lite')]);

		// Refreshing the widget's cached output with each new post
		add_action('save_post', [$this, 'flush_widget_cache']);
		add_action('deleted_post', [$this, 'flush_widget_cache']);
		add_action('switch_theme', [$this, 'flush_widget_cache']);
	}

	/**
	 * @return string
	 */
	public function get_widget_slug()
	{
		return $this->widget_slug;
	}

	/**
	 * How to display the widget on the screen.
	 * @param array $args
	 * @param array $instance
	 * @author Webnus <info@webnus.net>
	 */
	public function widget($args, $instance)
	{
		/** @var MEC_main $main */
		$main = MEC::getInstance('app.libraries.main');

		// Not Single Event Page
		if (!is_singular($main->get_main_post_type())) return;

		// General Settings
		$settings = $main->get_settings();

		$layout = ($settings['single_single_style'] ?? 'modern');

		// Style Per Event
		$style_per_event = '';
		if (isset($settings['style_per_event']) && $settings['style_per_event'])
		{
			$event = $GLOBALS['mec-widget-event'] ?? null;

			$style_per_event = get_post_meta($event->data->ID, 'mec_style_per_event', true);
			if ($style_per_event === 'global') $style_per_event = '';
		}

		echo MEC_kses::full($this->get_layout_output($style_per_event ?: $layout, $settings));
	}

	public function get_layout_output($layout, $settings)
	{
		$single = $GLOBALS['mec-widget-single'] ?? null;
		$event = $GLOBALS['mec-widget-event'] ?? null;

		if (!$single || !$event) return null;

		$occurrence = $GLOBALS['mec-widget-occurrence'] ?? null;
		$occurrence_full = $GLOBALS['mec-widget-occurrence_full'] ?? null;
		$occurrence_end_date = $GLOBALS['mec-widget-occurrence_end_date'] ?? null;
		$occurrence_end_full = $GLOBALS['mec-widget-occurrence_end_full'] ?? null;
		$cost = $GLOBALS['mec-widget-cost'] ?? null;
		$more_info = $GLOBALS['mec-widget-more_info'] ?? null;
		$location_id = $GLOBALS['mec-widget-location_id'] ?? null;
		$location = $GLOBALS['mec-widget-location'] ?? null;
		$organizer_id = $GLOBALS['mec-widget-organizer_id'] ?? null;
		$organizer = $GLOBALS['mec-widget-organizer'] ?? null;
		$more_info_target = $GLOBALS['mec-widget-more_info_target'] ?? null;
		$more_info_title = $GLOBALS['mec-widget-more_info_title'] ?? null;
		$banner_module = $GLOBALS['mec-banner_module'] ?? 0;
		$icons = $GLOBALS['mec-icons'] ?? $single->main->icons();

		$path = MEC::import('app.widgets.single.' . $layout, true, true);

		ob_start();
		include file_exists($path) ? $path : MEC::import('app.widgets.single.default', true, true);
		return ob_get_clean();
	}

	/**
	 * @param array $instance
	 * @return void
	 */
	public function form($instance)
	{
		?>
		<p class="description"><?php esc_html_e('You can manage the options in MEC -> Settings -> Single Event -> Sidebar page.'); ?></p>
		<?php
	}

	public function flush_widget_cache()
	{
		wp_cache_delete($this->get_widget_slug(), 'widget');
	}

	/**
	 * Update the widget settings.
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 * @author Webnus <info@webnus.net>
	 */
	public function update($new_instance, $old_instance)
	{
		$this->flush_widget_cache();

		$alloptions = wp_cache_get('alloptions', 'options');
		if (isset($alloptions['MEC_single_widget'])) delete_option('MEC_single_widget');

		return [];
	}

	public function is_enabled($k)
	{
		/** @var MEC_main $main */
		$main = MEC::getInstance('app.libraries.main');

		// General Settings
		$general = $main->get_settings();

		// Return from General Settings
		if (isset($general['ss_' . $k])) return (bool) $general['ss_' . $k];

		// Widget Settings
		$settings = $this->get_settings();

		$arr = end($settings);
		$ids = [];

		if (is_array($arr) || is_object($arr))
		{
			foreach ($arr as $key => $value)
			{
				if ($key === $k) $ids[] = $value;
			}
		}

		return isset($ids[0]) && $ids[0] === 'on';
	}
}

<?php

namespace MEC\SingleBuilder\Widgets\EventTags;

use MEC\Base;
use MEC\SingleBuilder\Widgets\WidgetBase;

class EventTags extends WidgetBase
{

	/**
	 *  Get HTML Output
	 *
	 * @param int $event_id
	 * @param array $atts
	 *
	 * @return string
	 */
	public function output($event_id = 0, $atts = array())
	{

		if (!$event_id) {

			$event_id = $this->get_event_id();
		}

		if (!$event_id) {
			return '';
		}

		$settings = $this->settings;
		$event_detail = $this->get_event_detail($event_id);
		$data = (isset($event_detail->data->meta['mec_fields']) and is_array($event_detail->data->meta['mec_fields'])) ? $event_detail->data->meta['mec_fields'] : get_post_meta($event_detail->ID, 'mec_fields', true);

		//		$tags = get_the_tags( $event_id );

		$tags = get_the_terms($event_id, 'post_tag');

		if (empty($tags)) {
			$tags = get_the_terms($event_id, 'mec_tag');
		}

		$html = '';
		if (true === $this->is_editor_mode && empty($tags)) {

			$html = '<div class="mec-content-notification"><p>'
				. '<span>' . esc_html__('To show this widget, you need to set "Tags" for your latest event.', 'modern-events-calendar-lite') . '</span>'
				. '<a href="https://webnus.net/dox/modern-events-calendar/tags/" target="_blank">' . esc_html__('Read More', 'modern-events-calendar-lite') . ' </a>'
				. '</p></div>';
		} else {

			ob_start();
			echo '<div class="mec-events-meta-group mec-events-meta-group-tags">';
			if (isset($atts['mec_tags_show_title']) && $atts['mec_tags_show_title']) {
				echo '<span class="mec-events-meta-group-tags-label">' . esc_html__('Tags: ', 'modern-events-calendar-lite') . '</span>';
			}
			if ($tags && !is_wp_error($tags)) {
				echo implode(
					', ',
					array_map(
						function ($tag) {
							return '<a href="' . esc_url(get_tag_link($tag->term_id)) . '">' . esc_html($tag->name) . ' </a>';
						},
						$tags
					)
				);
			} else {
				echo esc_html__('No Tags Available', 'modern-events-calendar-lite');
			}
			echo '</div>';
			$html = ob_get_clean();
		}

		return $html;
	}
}

<?php

namespace MEC\SingleBuilder\Widgets\EventGoogleMap;

use MEC\Base;
use MEC\SingleBuilder\Widgets\WidgetBase;

class EventGoogleMap extends WidgetBase {

	/**
	 *  Get HTML Output
	 *
	 * @param int $event_id
	 * @param array $atts
	 *
	 * @return string
	 */
	public function output( $event_id = 0, $atts = array() ){

		if( !$event_id ){

			$event_id = $this->get_event_id();
		}

		if(!$event_id){
			return '';
		}

		$settings = $this->settings;
		$event_detail = $this->get_event_detail($event_id);
		$primary_location_id = isset($event_detail->data->meta['mec_location_id']) ? $event_detail->data->meta['mec_location_id'] : '';
		$have_location = $primary_location_id && $primary_location_id > 1 ? true : false;

		$html = '';
		if( isset($settings['google_maps_status']) && $settings['google_maps_status'] && $have_location ){

			$html = Base::get_main()->module('googlemap.details', array('event' => [$event_detail]));
			if( $html ){

				$html = '<div class="mec-events-meta-group mec-events-meta-group-gmap">'
					. $html .
				'</div>';
			}
		}

		if ( true === $this->is_editor_mode && ( !isset($settings['google_maps_status']) || !$settings['google_maps_status'] ) ) {

			$html = '<div class="mec-content-notification"><p>'
				.'<span>'. esc_html__('To show this widget, you need to enable "Map" module', 'modern-events-calendar-lite').'</span>'
				. '<a href="https://webnus.net/dox/modern-events-calendar/event-modules/#Map" target="_blank">' . esc_html__('Read More', 'modern-events-calendar-lite') . ' </a>'
				.'</p></div>';
		} elseif ( true === $this->is_editor_mode && empty( $html ) ){

			$html = '<div class="mec-content-notification"><p>'
					.'<span>'. esc_html__('To show this widget, you need to enable "Map" module', 'modern-events-calendar-lite').'</span>'
					. '<a href="https://webnus.net/dox/modern-events-calendar/event-modules/#Map" target="_blank">' . esc_html__('Read More', 'modern-events-calendar-lite') . ' </a>'
				.'</p></div>';
		}

		return $html;
	}
}

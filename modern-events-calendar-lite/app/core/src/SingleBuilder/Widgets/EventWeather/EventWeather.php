<?php

namespace MEC\SingleBuilder\Widgets\EventWeather;

use MEC\Base;
use MEC\SingleBuilder\Widgets\WidgetBase;

class EventWeather extends WidgetBase {

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

		$html = '';
		if( isset($settings['weather_module_status']) && $settings['weather_module_status'] ){

			ob_start();
				echo Base::get_main()->module('weather.details', array('event' => $event_detail));
			$html = ob_get_clean();
		}

		if ( true === $this->is_editor_mode && empty( $html ) ) {

			$html = '<div class="mec-content-notification"><p>'
					.'<span>'. esc_html__('To show this widget, you need to enable "Weather" module.', 'modern-events-calendar-lite').'</span>'
					. '<a href="https://webnus.net/dox/modern-events-calendar/event-modules/#Weather" target="_blank">' . esc_html__('Read More', 'modern-events-calendar-lite') . ' </a>'
				.'</p></div>';
		}

		return $html;
	}
}

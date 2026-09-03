<?php

namespace MEC\SingleBuilder\Widgets\EventLocalTime;

use MEC\Base;
use MEC\SingleBuilder\Widgets\WidgetBase;

class EventLocalTime extends WidgetBase {

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
		if ( true === $this->is_editor_mode && ( !isset($settings['local_time_module_status']) || !$settings['local_time_module_status'] ) ) {

			$html = '<div class="mec-content-notification"><p>'
					.'<span>'. esc_html__('To show this widget, you need to enable "Local Time" module.', 'modern-events-calendar-lite').'</span>'
					. '<a href="https://webnus.net/dox/modern-events-calendar/event-modules/#Local_Time" target="_blank">' . esc_html__('Read More', 'modern-events-calendar-lite') . ' </a>'
				.'</p></div>';
		} else {

			$html = '<div class="mec-event-meta mec-local-time-wrapper">'
				.Base::get_main()->module('local-time.details', array('event'=>$event_detail)) .
			'</div>';
		}

		return $html;
	}
}

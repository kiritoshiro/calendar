<?php

namespace MEC\SingleBuilder\Widgets\EventAttendees;

use MEC\Base;
use MEC\SingleBuilder\Widgets\WidgetBase;

class EventAttendees extends WidgetBase {

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
		$events_detail = $this->get_event_detail($event_id);

		$html = '';
		if( isset($settings['bp_status']) && $settings['bp_status'] ){

			ob_start();
				echo Base::get_main()->module('attendees-list.details', array('event'=>$events_detail));
			$html = ob_get_clean();
		}

		if ( true === $this->is_editor_mode && empty( $html ) ) {

			$html = '<div class="mec-content-notification"><p>'
					.'<span>'. esc_html__('To show this widget, you need to enable "Attendees Module" and have at least a booking for your latest event.', 'modern-events-calendar-lite').'</span>'
					. '<a href="https://webnus.net/dox/modern-events-calendar/show-attendees-list-on-the-sidebar/" target="_blank">' . esc_html__('Read More', 'modern-events-calendar-lite') . ' </a>'
				.'</p></div>';
		}

		return $html;
	}
}


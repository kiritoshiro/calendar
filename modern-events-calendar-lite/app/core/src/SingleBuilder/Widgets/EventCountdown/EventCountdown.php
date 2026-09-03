<?php

namespace MEC\SingleBuilder\Widgets\EventCountdown;

use MEC\Base;
use MEC\SingleBuilder\Widgets\WidgetBase;

class EventCountdown extends WidgetBase {

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
		if ( true === $this->is_editor_mode && ( !isset($settings['countdown_status']) || !$settings['countdown_status'] ) ) {

			$html = '<div class="mec-content-notification"><p>'
					.'<span>'. esc_html__('To show this widget, you need to enable "Countdown" module', 'modern-events-calendar-lite').'</span>'
					. '<a href="https://webnus.net/dox/modern-events-calendar/event-modules/#Countdown" target="_blank">' . esc_html__('Read More', 'modern-events-calendar-lite') . ' </a>'
				.'</p></div>';
		} else {

			$wrap_class = (true === $this->is_editor_mode) ? 'mec-wrap' : '';

			if ( Base::get_main()->can_show_countdown_module( $events_detail ) ) {

				$html = '<div class="'. esc_attr( $wrap_class ) .' mec-events-meta-group mec-events-meta-group-countdown">'
						. Base::get_main()->module('countdown.details', array('event'=>array($events_detail))) .
					'</div>';
			}
		}

		return $html;
	}
}

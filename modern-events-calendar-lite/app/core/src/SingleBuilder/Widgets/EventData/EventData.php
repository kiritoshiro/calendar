<?php

namespace MEC\SingleBuilder\Widgets\EventData;

use MEC\Base;
use MEC\SingleBuilder\Widgets\WidgetBase;

class EventData extends WidgetBase {

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
		$data = (isset($event_detail->data->meta['mec_fields']) and is_array($event_detail->data->meta['mec_fields'])) ? $event_detail->data->meta['mec_fields'] : get_post_meta($event_detail->ID, 'mec_fields', true);


		$html = '';
		if( !empty($data) && ( isset($settings['display_event_fields']) && $settings['display_event_fields'] ) ){

			$single         = new \MEC_skin_single();
			ob_start();
				$single->display_data_fields( $event_detail );
			$html = ob_get_clean();
		}

		if ( true === $this->is_editor_mode && empty( $html )  ) {

			$html = '<div class="mec-content-notification"><p>'
					.'<span>'. esc_html__('To show this widget, you need to set "Event DataContent" for your latest event.', 'modern-events-calendar-lite').'</span>'
					. '<a href="https://webnus.net/dox/modern-events-calendar/add-event/#Event_Data_Custom_Fields" target="_blank">' . esc_html__('Read More', 'modern-events-calendar-lite') . ' </a>'
				.'</p></div>';
		}

		return $html;
	}
}

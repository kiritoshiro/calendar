<?php


namespace MEC\BookingForm;

class DisplayFields {

	public static function display_fields( $group_id, $form_type, $fields = null, $j = null, $settings = array(), $data = array() ) {

		if ( !is_array( $fields ) || empty( $fields ) ) {

			return;
		}

		$is_editor = isset( $_GET['action'] ) && 'elementor' === $_GET['action'];
		$is_dashboard = is_admin() && !wp_doing_ajax() && !$is_editor;

		$lock_prefilled = $settings['lock_prefilled'] ?? false;

		if( 'reg' === $form_type && 'book' === $group_id ){

			$field_base_name = $group_id . '[tickets][' . esc_attr($j) . ']';
		}elseif( 'bfixed' === $form_type && 'book' === $group_id ){

			$field_base_name = $group_id . '[fields]';
		}else{

			$field_base_name = $group_id . '[' . esc_attr($form_type) . ']';
		}

		// Main Library
		$main = \MEC\Base::get_main();

		?>
		<!-- Custom fields begin -->
		<?php
			$inline_third_open = false;
			$inline_third_counter = 0;
		foreach ( $fields as $f_id => $field ) :
			if(in_array($f_id, [':i:',':fi:','_i_','_fi_',], true)){

				continue;
			}

			$type = $field['type'] ?? false;
			if ( false === $type ) {
				continue;
			}

			$condition = ( isset( $field['condition'] ) && is_array( $field['condition'] ) ) ? $field['condition'] : null;
			$condition_source_field_id = $condition['source_field_id'] ?? '';
			$condition_match_type = $condition['match_type'] ?? '';
			$condition_option_key = $condition['option_key'] ?? '';

			$j          = !is_null($j) ? $j : $f_id;
			$field_id = !empty($field['key']) ? $field['key'] : $f_id;
			$html_id  = 'mec_field_' . $group_id . '_' . $type . '_' . $j;
			$required = ( ( isset( $field['required'] ) && $field['required'] ) || ( isset( $field['mandatory'] ) && $field['mandatory'] ) ) ? 'required="required"' : '';
			$field_label = $field['label'] ?? null;

			$field_name = strtolower( str_replace( [
					' ',
					',',
					':',
					'"',
					"'",
			], '_', $field_label ) );

			$field_id = strtolower( str_replace( [
				' ',
				',',
				':',
				'"',
				"'",
			], '_', $field_id ) );

			if( !isset( $field['inline'] ) && in_array( $type, array( 'name', 'mec_email' )) ){

				$field['inline'] = 'enable';
			}

			$classes = [];

			$single_row = isset($field['single_row']) && $field['single_row'] == 'enable';
			$full_width = isset($field['full_width']) && $field['full_width'] == 'enable';

			if ( isset( $field['inline'] ) && 'enable' === $field['inline'] ) {
				$classes[] = 'col-md-6';
			} elseif ( isset( $field['inline_third'] ) && 'enable' === $field['inline_third'] ) {
				$classes[] = 'col-md-4';
			} else {
				$classes[] = 'col-md-12'; // 'col-md-6'
			}

			if( $is_dashboard ){

				$classes[] = 'mec-form-row';
			}

			if( isset( $field['mandatory'] ) && $field['mandatory'] ){

				$classes[] = 'mec-reg-mandatory';
			}

                        if( $single_row ){

                                $classes[] = 'clearfix';
                        }
                        $pattern = isset($field['pattern']) ? trim($field['pattern']) : '';
                        $pattern_attribute = $pattern ? ' data-pattern="' . esc_attr($pattern) . '"' : '';
                        $is_col_4 = isset($field['inline_third']) && $field['inline_third'] === 'enable';

			if ($is_col_4) {
			    if (!$inline_third_open) {
			        echo '<div class="mec-inline-third-row">';
			        $inline_third_open = true;
			        $inline_third_counter = 0;
			    }
			    $inline_third_counter++;
			    if ($inline_third_counter === 3) {
			        $inline_third_open = false;
			        $inline_third_counter = 0;
			        $close_row_after_li = true;
			    } else {
			        $close_row_after_li = false;
			    }
			} else {
			    if ($inline_third_open) {
			        echo '</div>';
			        $inline_third_open = false;
			        $inline_third_counter = 0;
			    }
			    $close_row_after_li = false;
			}
			?>
                        <li class="mec-<?php echo esc_attr( $group_id ); ?>-field-<?php echo esc_attr( $field['type'] ); ?> mec-<?php echo esc_attr( $group_id ); ?>-<?php echo esc_attr($form_type); ?>-field-<?php echo esc_attr( $field['type'] ); ?> <?php echo esc_attr( join( ' ', $classes ) ); ?>" data-field-id="<?php echo esc_attr( $f_id ); ?>" data-field-type="<?php echo esc_attr( $type ); ?>" data-ticket-id="<?php echo esc_attr($j); ?>"<?php echo $pattern_attribute; ?><?php echo $condition ? ' data-condition-source-field-id="' . esc_attr( $condition_source_field_id ) . '" data-condition-match-type="' . esc_attr( $condition_match_type ) . '" data-condition-option-key="' . esc_attr( $condition_option_key ) . '"' : ''; ?>>
				<?php
					global $current_user;
					$attributes = '';
					$has_icon = false;
					$class = '';
					$value = '';
					switch ( $type ) {
					case 'name':
						$field_type     = 'text';
						$field_id       = 'name';
						$field['label'] = $field['label'] ?? esc_html__('Last Name', 'modern-events-calendar-lite');
						$value      	= trim($current_user->first_name . ' ' . $current_user->last_name);
						$has_icon 		= $field['has_icon'] ?? true;
						$icon_content 	= \MEC\Base::get_main()->svg('form/user-icon');
						break;
					case 'first_name':
						$field_type     = 'text';
						$field_id       = 'first_name';
						$field['label'] = $field['label'] ?? esc_html__('First Name', 'modern-events-calendar-lite');
						$value      = $current_user->first_name;
						break;
					case 'last_name':
						$field_type     = 'text';
						$field_id       = 'last_name';
						$field['label'] = $field['label'] ?? esc_html__('Last Name', 'modern-events-calendar-lite');
						$value      	= $current_user->last_name;
						break;
					case 'mec_email':
						$field_type     = 'email';
						$field_id       = $type;
						$field['label'] = $field['label'] ?? esc_html__('Email', 'modern-events-calendar-lite');
						$value          = isset( $current_user->user_email ) ? $current_user->user_email : '';
						$has_icon 		= $field['has_icon'] ?? true;
						$icon_content 	= \MEC\Base::get_main()->svg('form/email-icon');
					case 'email':
						$field_type     = 'email';
						$field['label'] = $field['label'] ?? 'Email';
						$value          = $main->get_from_mapped_field($field, ($current_user->user_email ?: ''));
						break;
					case 'text':
						$field_type     = 'text';
						$field['label'] = $field['label'] ?? '';
						$value         = $main->get_from_mapped_field($field);
						break;
					case 'date':
						$field_type     = 'date';
						$field['label'] = $field['label'] ?? 'Date';
						$value          = $main->get_from_mapped_field($field);
						$class          = 'mec-date-picker';
						$attributes     = ' min="' . esc_attr( date( 'Y-m-d', strtotime( '-100 years' ) ) ) . '" max="' . esc_attr( date( 'Y-m-d', strtotime( '+100 years' ) ) ) . '" onload="mec_add_datepicker()"';
						break;
					case 'file':
						$field_type     = 'file';
						$field['label'] = $field['label'] ?? 'File';
						$value          = '';
						break;
					case 'tel':
						$field_type     = 'tel';
						$field['label'] = $field['label'] ?? 'Tel';
						$value         = $main->get_from_mapped_field($field);
						break;
					case 'textarea':
						$field_type     = 'textarea';
						$field['label'] = $field['label'] ?? '';
						$value         = $main->get_from_mapped_field($field);
						break;
					case 'select':
						$field_type     = 'select';
						$field['label'] = $field['label'] ?? '';
						$value         = $main->get_from_mapped_field($field);
						$selected      = '';
						break;
					case 'radio':
					case 'checkbox':
						$field_type = $type;
						$value     = $main->get_from_mapped_field($field);
						break;
					case 'agreement':

						$value = '';
						break;

				}

				$primary_field_ids = [
					'mec_email',
					'name',
					'first_name',
					'last_name'
				];
				$primary_field_id = $field_id;
				if( 'fixed' === $form_type || ( 'reg' === $form_type && in_array($field_id, $primary_field_ids ,true) ) ){

					$field_id = 'mec_email' === $field_id ? 'email' : $field_id;
					$value = $data[$field_id] ?? $value;
				} else {

					$value = $data[$form_type][$field_id] ?? $value;
				}

				$lock_field = !empty( $value );
				$lock_field = ( $lock_field && ( $lock_prefilled == 1 || ( $lock_prefilled == 2 && $j == 0 ) ) ) ? 'readonly' : '';

				if( 'reg' === $form_type && !in_array($primary_field_id,$primary_field_ids,true) )  {

					$field_name = $field_base_name . '[reg][' . esc_attr($field_id) . ']';
				}else{

					$field_name = $field_base_name . '[' . esc_attr($field_id) . ']';
				}

				// Display Label
				if ( isset( $field['label'] ) && !empty( $field['label'] ) && 'agreement' !== $type ) {

					$label_field = '<label for="' . esc_attr( $html_id ) . '" style="display:block" class="' . ( $required ? 'required' : '' ) . '">'
						 . esc_html__( $field['label'], 'modern-events-calendar-lite')
						 . ( $required ? '<span class="wbmec-mandatory">*</span>' : '' )
						 . '</label>';

					echo $is_dashboard ? '<div class="mec-col-2">'.\MEC_kses::form($label_field).'</div>' : \MEC_kses::form($label_field);
				}

				$input_html = '';
				$field_class = $class;
				
				// Add width style if inline or inline_third is enabled
				$width_style = '';
				if ( ( isset( $field['inline'] ) && 'enable' === $field['inline'] ) || ( isset( $field['inline_third'] ) && 'enable' === $field['inline_third'] ) ) {
					$width_style = ' style="width: 95% !important;"';
				}
				
				// Display Input
				switch ( $type ) {
					case 'name':
					case 'first_name':
					case 'last_name':
					case 'mec_email':

						$placeholder = ( isset( $field['placeholder'] ) && $field['placeholder'] ) ? esc_html__( $field['placeholder'], 'modern-events-calendar-lite') : esc_html__( $field['label'], 'modern-events-calendar-lite');
						$input_html = '<input id="' . esc_attr( $html_id ) . '" class="' . esc_attr( $field_class ) . '" type="' . esc_attr( $field_type ) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr(trim( $value )) . '" placeholder="' . esc_attr( $placeholder ) . '" ' . $required . '  ' . $lock_field . '  ' . $attributes . $width_style . '  />';

						break;
					case 'text':
					case 'date':
					case 'file':
					case 'email':
					case 'tel':

						$placeholder = ( isset( $field['placeholder'] ) && $field['placeholder'] ) ? esc_html__( $field['placeholder'], 'modern-events-calendar-lite') : esc_html__( $field['label'], 'modern-events-calendar-lite');
						$input_html = '<input id="' . esc_attr( $html_id ) . '" class="' . esc_attr( $field_class ) . '" type="' . esc_attr( $field_type ) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr(trim( $value )) . '" placeholder="' . esc_attr( $placeholder ) . '" ' . $required . '  ' . $lock_field . '  ' . $attributes . $width_style . '  />';

						break;
					case 'textarea':

						$placeholder = ( isset( $field['placeholder'] ) && $field['placeholder'] ) ? esc_html__( $field['placeholder'], 'modern-events-calendar-lite') : esc_html__( $field['label'], 'modern-events-calendar-lite');
						$input_html = '<textarea id="' . esc_attr( $html_id ) . '" class="' . esc_attr( $field_class ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr(trim( $value )) . '" placeholder="' . esc_attr( $placeholder ) . '" ' . $required . '  ' . $lock_field . '  ' . $attributes . $width_style . '  ></textarea>';

						break;
					case 'select':

						$placeholder = '';
						$input_html = '<select id="' . esc_attr( $html_id ) . '" class="' . esc_attr( $field_class ) . '" name="'.esc_attr($field_name).'" placeholder="' . esc_attr( $placeholder ) . '" ' . $required . '  ' . $lock_field . '  ' . $attributes . $width_style . ' >';
						$rd = 0;
						$selected = $value;
                                               $options = isset($field['options']) ? $field['options'] : [];
                                               foreach ( $options as $option_key => $field_option ) {
                                                       $rd++;
                                                       $field_label = is_array( $field_option ) ? ( $field_option['label'] ?? '' ) : $field_option;
                                                       $option_text  = esc_html__( $field_label, 'modern-events-calendar-lite');
                                                       $option_value = ( $rd === 1 and isset( $field['ignore'] ) and $field['ignore'] ) ? '' : esc_attr__( $field_label, 'modern-events-calendar-lite');

							$input_html .= '<option value="' . esc_attr($option_value) . '" data-option-key="' . esc_attr( $option_key ) . '" ' . selected( $selected, $option_value, false ) . '>' . esc_html($option_text) . '</option>';
						}
						$input_html .= '</select>';

						break;
					case 'radio':
                                               $options = isset($field['options']) ? $field['options'] : [];
                                               foreach ( $options as $option_key => $field_option ) {
                                                       $field_label   = is_array( $field_option ) ? ( $field_option['label'] ?? '' ) : $field_option;
                                                       $current_value = esc_html__( $field_label, 'modern-events-calendar-lite');
                                                       $checked       = in_array( $current_value, (array) $value );
                                                       $input_html   .= '<label>'
                                                                . '<input type="' . esc_attr( $field_type ) . '" id="mec_' . esc_attr( $form_type . '_field_' . $type . $j . '_' . $field_id . '_' . strtolower( str_replace( ' ', '_', $field_label ) ) ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $current_value ) . '" data-option-key="' . esc_attr( $option_key ) . '" ' . checked( $checked, true, false ) . '/>'
                                                                . esc_html__( $field_label, 'modern-events-calendar-lite')
                                                                . '</label>';
                                               }

						break;

					case 'checkbox':
                                               $options = isset($field['options']) ? $field['options'] : [];
                                               foreach ( $options as $option_key => $field_option ) {
                                                       $field_label   = is_array( $field_option ) ? ( $field_option['label'] ?? '' ) : $field_option;
                                                       $current_value = esc_html__( $field_label, 'modern-events-calendar-lite');
                                                       $checked       = in_array( $current_value, (array) $value );
                                                       $input_html   .= '<label>'
                                                                . '<input type="' . esc_attr( $field_type ) . '" id="mec_' . esc_attr( $form_type . '_field_' . $type . $j . '_' . $field_id . '_' . strtolower( str_replace( ' ', '_', $field_label ) ) ) . '" name="' . esc_attr( $field_name ) . '[]" value="' . esc_attr( $current_value ) . '" data-option-key="' . esc_attr( $option_key ) . '" ' . checked( $checked, true, false ) . '/>'
                                                                . esc_html__( $field_label, 'modern-events-calendar-lite')
                                                                . '</label>';
                                               }

						break;
					case 'agreement':

						$checked = isset( $field['status'] ) ? $field['status'] : 'checked';
						$input_html = '<label for="' . esc_attr($html_id . $f_id) . '">'
							 . '<input type="checkbox" id="' . esc_attr($html_id . $f_id) . '" name="' . esc_attr( $field_name ) . '" value="1" ' . checked( $checked, 'checked', false ) . ' onchange="mec_agreement_change(this);"/>'
							 . ( $required ? '<span class="wbmec-mandatory">*</span>' : '' )
							 . sprintf( esc_html__( stripslashes( $field['label'] ), 'modern-events-calendar-lite'), '<a href="' . get_the_permalink( $field['page'] ) . '" target="_blank">' . get_the_title( $field['page'] ) . '</a>' )
							 . '</label>';

						break;

						case 'p':

							$input_html = '<p>' . \MEC_kses::page( do_shortcode( stripslashes( $field['content'] ?? '' ) ) ) . '</p>';

							break;
				}

				if( !empty( $has_icon ) ) {

					$wrapper_class = "mec-{$group_id}-{$type}-field-wrapper";
					$icon_class = "mec-{$group_id}-{$type}-field-icon";
					$input_html = '<span class="mec-field-wrapper '. $wrapper_class .'">'
					 	. '<span class="mec-field-icon '. $icon_class .'">' . $icon_content .' </span>'
						. $input_html
					.'</span>';
				}
				echo $is_dashboard ? '<div class="mec-col-2">'.\MEC_kses::form($input_html).'</div>' : \MEC_kses::form($input_html);
				?>
			</li>

			<?php
			if ($close_row_after_li) {
				echo '</div>';
			}
			if( $single_row ){

				echo '<span class="clearfix"></span>';
			}
			?>
		<?php endforeach;
		if ($inline_third_open) {
		    echo '</div>';
		}

	}

}

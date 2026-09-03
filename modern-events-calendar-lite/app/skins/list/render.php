<?php
/** no direct access **/
defined('MECEXEC') or die();

/** @var MEC_skin_list $this */

$styling = $this->main->get_styling();
$settings = $this->main->get_settings();
$current_month_divider = isset($_REQUEST['current_month_divider']) ? sanitize_text_field($_REQUEST['current_month_divider']) : 0;
$display_label = $this->skin_options['display_label'] ?? false;
$reason_for_cancellation = $this->skin_options['reason_for_cancellation'] ?? false;
$event_colorskin = isset($styling['mec_colorskin']) || isset($styling['color']) ? 'colorskin-custom' : '';
$map_events = [];
?>
<div class="mec-wrap <?php echo esc_attr($event_colorskin); ?>">
	<div class="mec-event-list-<?php echo esc_attr($this->style); ?>">
		<?php foreach($this->events as $date=>$events): ?>

            <?php $month_id = date('Ym', strtotime($date)); if($this->month_divider and $month_id != $current_month_divider): $current_month_divider = $month_id; ?>
            <div class="mec-month-divider" data-toggle-divider="mec-toggle-<?php echo date('Ym', strtotime($date)); ?>-<?php echo esc_attr($this->id); ?>"><h5 style="display: inline;"><?php echo esc_html($this->main->date_i18n('F Y', strtotime($date))); ?></h5><i class="mec-sl-arrow-down"></i></div>
            <?php endif; ?>

            <?php
                foreach($events as $event)
                {
                    $map_events[] = $event;

                    $location_id = $this->main->get_master_location_id($event);
                    $location = ($location_id ? $this->main->get_location_data($location_id) : array());

                    $organizer_id = $this->main->get_master_organizer_id($event);
                    $organizer = ($organizer_id ? $this->main->get_organizer_data($organizer_id) : array());
                    $start_time = (isset($event->data->time) ? $event->data->time['start'] : '');
                    $end_time = (isset($event->data->time) ? $event->data->time['end'] : '');
                    $event_color = $this->get_event_color_dot($event);
                    $event_start_date = !empty($event->date['start']['date']) ? $event->date['start']['date'] : '';
                    $mec_data = $this->display_custom_data($event);
                    $custom_data_class = !empty($mec_data) ? 'mec-custom-data' : '';

                    // MEC Schema
                    do_action('mec_schema', $event);
            ?>
            <article class="<?php echo (isset($event->data->meta['event_past']) and trim($event->data->meta['event_past'])) ? 'mec-past-event ' : ''; ?>mec-event-article <?php echo esc_attr($custom_data_class); ?> mec-clear <?php echo esc_attr($this->get_event_classes($event)); ?> mec-divider-toggle mec-toggle-<?php echo date('Ym', strtotime($date)); ?>-<?php echo esc_attr($this->id); ?>" itemscope>
                <?php if($this->style == 'modern'): ?>
                    <div class="col-md-2 col-sm-2">

                        <?php if($this->main->is_multipleday_occurrence($event, true)): ?>
                        <div class="mec-event-date">
                            <div class="event-d mec-color mec-multiple-dates">
                                <?php echo esc_html($this->main->date_i18n($this->date_format_modern_1, strtotime($event->date['start']['date']))); ?> -
                                <?php echo esc_html($this->main->date_i18n($this->date_format_modern_1, strtotime($event->date['end']['date']))); ?>
                            </div>
                            <div class="event-f"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_2, strtotime($event->date['start']['date']))); ?></div>
                            <div class="event-da"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_3, strtotime($event->date['start']['date']))); ?></div>
                        </div>
                        <?php elseif($this->main->is_multipleday_occurrence($event)): ?>
                        <div class="mec-event-date mec-multiple-date-event">
                            <div class="event-d mec-color"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_1, strtotime($event->date['start']['date']))); ?></div>
                            <div class="event-f"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_2, strtotime($event->date['start']['date']))); ?></div>
                            <div class="event-da"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_3, strtotime($event->date['start']['date']))); ?></div>
                        </div>
                        <div class="mec-event-date mec-multiple-date-event">
                            <div class="event-d mec-color"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_1, strtotime($event->date['end']['date']))); ?></div>
                            <div class="event-f"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_2, strtotime($event->date['end']['date']))); ?></div>
                            <div class="event-da"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_3, strtotime($event->date['end']['date']))); ?></div>
                        </div>
                        <?php else: ?>
                        <div class="mec-event-date">
                            <div class="event-d mec-color"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_1, strtotime($event->date['start']['date']))); ?></div>
                            <div class="event-f"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_2, strtotime($event->date['start']['date']))); ?></div>
                            <div class="event-da"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_3, strtotime($event->date['start']['date']))); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <?php do_action('list_std_title_hook', $event); ?>
                        <?php $soldout = $this->main->get_flags($event); ?>
                        <h4 class="mec-event-title"><?php echo MEC_kses::element($this->display_link($event)); ?><?php echo MEC_kses::element($soldout.$event_color); echo MEC_kses::element($this->main->get_normal_labels($event, $display_label).$this->main->display_cancellation_reason($event, $reason_for_cancellation)); ?><?php echo MEC_kses::embed($this->display_custom_data($event)); ?><?php do_action('mec_shortcode_virtual_badge', $event->data->ID); ?><?php echo MEC_kses::element($this->get_label_captions($event,'mec-fc-style')); ?></h4>
                        <?php if($this->localtime) echo MEC_kses::full($this->main->module('local-time.type2', array('event' => $event))); ?>
                        <div class="mec-event-detail">
                            <div class="mec-event-loc-place"><?php echo (isset($location['name']) ? esc_html($location['name']) : '') . (isset($location['address']) && !empty($location['address']) ? ' | '.esc_html($location['address']) : ''); ?></div>
                            <?php if($this->include_events_times and trim($start_time)) echo MEC_kses::element($this->main->display_time($start_time, $end_time)); ?>
                            <?php echo MEC_kses::element($this->display_categories($event)); ?>
                            <?php echo MEC_kses::element($this->display_organizers($event)); ?>
                            <?php echo MEC_kses::element($this->display_cost($event)); ?>
                        </div>
                        <ul class="mec-event-sharing"><?php echo MEC_kses::full($this->main->module('links.list', array('event' => $event))); ?></ul>
                    </div>
                    <div class="col-md-4 col-sm-4 mec-btn-wrapper">
                        <?php echo MEC_kses::element($this->booking_button($event, 'icon')); ?>
                        <?php echo MEC_kses::element($this->display_link($event, ((is_array($event->data->tickets) and count($event->data->tickets) and !strpos($soldout, '%%soldout%%') and !$this->booking_button and !$this->main->is_expired($event)) ? $this->main->m('register_button', esc_html__('REGISTER', 'modern-events-calendar-lite')) : $this->main->m('view_detail', esc_html__('View Detail', 'modern-events-calendar-lite'))), 'mec-booking-button')); ?>
                        <?php do_action('mec_list_modern_style', $event); ?>
                    </div>
                <?php elseif($this->style == 'classic'): ?>
                    <?php $thumbnail = $this->get_thumbnail_image($event, 'thumbnail'); ?>
                    <div class="mec-event-image"><?php echo MEC_kses::element($this->display_link($event, $thumbnail)); ?></div>
                    <?php if(isset($settings['multiple_day_show_method']) && $settings['multiple_day_show_method'] == 'all_days'): ?>
                        <div class="mec-event-date mec-color"><?php echo $this->icons->display('calendar'); ?> <?php echo esc_html($this->main->date_i18n($this->date_format_classic_1, strtotime($event->date['start']['date']))); ?></div>
                        <div class="mec-event-time mec-color"><?php if($this->include_events_times and trim($start_time)) { echo $this->icons->display('clock'); echo MEC_kses::element($this->main->display_time($start_time, $end_time)); } ?></div>
                    <?php else: ?>
                        <div class="mec-event-date mec-color"><?php echo $this->icons->display('calendar'); ?> <?php echo MEC_kses::element($this->main->dateify($event, $this->date_format_classic_1)); ?></div>
                        <div class="mec-event-time mec-color"><?php if($this->include_events_times and trim($start_time)) { echo $this->icons->display('clock'); echo MEC_kses::element($this->main->display_time($start_time, $end_time)); } ?></div>
                    <?php endif; ?>
                    <?php echo MEC_kses::element($this->get_label_captions($event)); ?>
                    <?php if($this->localtime) echo MEC_kses::full($this->main->module('local-time.type2', array('event' => $event))); ?>
                    <h4 class="mec-event-title"><?php echo MEC_kses::element($this->display_link($event)); ?><?php echo MEC_kses::embed($this->display_custom_data($event)); ?><?php echo MEC_kses::element($this->main->get_flags($event).$event_color.$this->main->get_normal_labels($event, $display_label).$this->main->display_cancellation_reason($event, $reason_for_cancellation)); ?><?php do_action('mec_shortcode_virtual_badge', $event->data->ID ); ?></h4>
                    <?php if(isset($location['name'])): ?><div class="mec-event-detail"><div class="mec-event-loc-place"><?php echo $this->icons->display('map-marker'); ?> <?php echo esc_html($location['name']); ?></div></div><?php endif; ?>
                    <?php echo MEC_kses::element($this->display_categories($event)); ?>
                    <?php echo MEC_kses::element($this->display_organizers($event)); ?>
                    <?php echo MEC_kses::element($this->display_cost($event)); ?>
                    <?php do_action('mec_list_classic_after_location', $event, $this->skin_options); ?>
                    <?php echo MEC_kses::form($this->booking_button($event)); ?>
                <?php elseif($this->style == 'minimal'): ?>
                    <?php echo MEC_kses::element($this->get_label_captions($event)); ?>
                    <div class="col-md-9 col-sm-9">
                        <?php if($this->main->is_multipleday_occurrence($event, true)): ?>
                        <div class="mec-event-date mec-bg-color">
                            <span class="mec-multiple-dates"><?php echo esc_html($this->main->date_i18n($this->date_format_minimal_1, strtotime($event->date['start']['date']))); ?> - <?php echo esc_html($this->main->date_i18n($this->date_format_minimal_1, strtotime($event->date['end']['date']))); ?></span>
                            <?php echo esc_html($this->main->date_i18n($this->date_format_minimal_2, strtotime($event->date['start']['date']))); ?>
                        </div>
                        <?php elseif($this->main->is_multipleday_occurrence($event)): ?>
                        <div class="mec-event-date mec-bg-color">
                            <span><?php echo esc_html($this->main->date_i18n($this->date_format_minimal_1, strtotime($event->date['start']['date']))); ?></span>
                            <?php echo esc_html($this->main->date_i18n($this->date_format_minimal_2, strtotime($event->date['start']['date']))); ?>
                        </div>
                        <div class="mec-event-date mec-bg-color">
                            <span><?php echo esc_html($this->main->date_i18n($this->date_format_minimal_1, strtotime($event->date['end']['date']))); ?></span>
                            <?php echo esc_html($this->main->date_i18n($this->date_format_minimal_2, strtotime($event->date['end']['date']))); ?>
                        </div>
                        <?php else: ?>
                        <div class="mec-event-date mec-bg-color">
                            <span><?php echo esc_html($this->main->date_i18n($this->date_format_minimal_1, strtotime($event->date['start']['date']))); ?></span>
                            <?php echo esc_html($this->main->date_i18n($this->date_format_minimal_2, strtotime($event->date['start']['date']))); ?>
                        </div>
                        <?php endif; ?>

                        <?php if($this->include_events_times and trim($start_time)) echo MEC_kses::element($this->main->display_time($start_time, $end_time)); ?>
                        <h4 class="mec-event-title"><?php echo MEC_kses::element($this->display_link($event)); ?><?php echo MEC_kses::embed($this->display_custom_data($event)); ?><?php echo MEC_kses::element($this->main->get_flags($event).$event_color.$this->main->get_normal_labels($event, $display_label).$this->main->display_cancellation_reason($event, $reason_for_cancellation)); ?><?php do_action('mec_shortcode_virtual_badge', $event->data->ID ); ?></h4>
                        <div class="mec-event-detail">
                            <span class="mec-day-wrapper"><?php echo esc_html($this->main->date_i18n($this->date_format_minimal_3, strtotime($event->date['start']['date']))); ?></span><?php echo (isset($location['name']) ? '<span class="mec-comma-wrapper">,</span> <span class="mec-event-loc-place">' . esc_html($location['name']) .'</span>' : ''); ?><?php if($this->localtime) echo MEC_kses::full($this->main->module('local-time.type2', array('event' => $event))); ?>
                        </div>
                        <?php do_action('mec_list_minimal_after_details', $event); ?>
                        <?php echo MEC_kses::element($this->display_categories($event)); ?>
                        <?php echo MEC_kses::element($this->display_organizers($event)); ?>
                        <?php echo MEC_kses::element($this->display_cost($event)); ?>
                        <?php echo MEC_kses::form($this->booking_button($event)); ?>
                    </div>
                    <div class="col-md-3 col-sm-3 btn-wrapper"><?php do_action('before_mec_list_minimal_button', $event); ?><?php echo MEC_kses::element($this->display_link($event, $this->main->m('event_detail', esc_html__('EVENT DETAIL', 'modern-events-calendar-lite')), 'mec-detail-button')); ?></div>
                <?php elseif($this->style == 'standard'): ?>
                    <?php
                        $excerpt = get_the_excerpt($event->data->post);

                        // Safe Excerpt for UTF-8 Strings
                        if(!trim($excerpt))
                        {
                            $ex = explode(' ', strip_tags(strip_shortcodes($event->data->post->post_content)));
                            $words = array_slice($ex, 0, 10);

                            $excerpt = implode(' ', $words);
                            if(trim($excerpt)) $excerpt .= ' <span>[…]</span>';
                        }
                    ?>
                    <div class="mec-topsec">
                    <?php $thumblist = $this->get_thumbnail_image($event, 'thumblist'); if (!empty($thumblist)) : ?>
                    <div class="col-md-3 mec-event-image-wrap mec-col-table-c">
                            <div class="mec-event-image"><?php echo MEC_kses::element($this->display_link($event, $thumblist, '')); ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="<?php echo (!empty($thumblist)) ? 'col-md-6' : 'col-md-9'; ?> mec-col-table-c mec-event-content-wrap">
                            <div class="mec-event-content">
                                <?php $soldout = $this->main->get_flags($event); ?>
                                <?php echo MEC_kses::element($this->display_status_bar($event)); ?>
                                <h3 class="mec-event-title"><?php echo MEC_kses::element($this->display_link($event)); ?><?php echo MEC_kses::embed($this->display_custom_data($event)); ?><?php echo MEC_kses::element($soldout.$event_color.$this->main->get_normal_labels($event, $display_label).$this->main->display_cancellation_reason($event, $reason_for_cancellation)); ?><?php do_action('mec_shortcode_virtual_badge', $event->data->ID ); ?></h3>
                                <div class="mec-event-description"><?php echo MEC_kses::element($excerpt); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3 mec-col-table-c mec-event-meta-wrap">
                            <div class="mec-event-meta mec-color-before">
                                <div class="mec-date-details">
                                    <?php if(isset($settings['multiple_day_show_method']) && $settings['multiple_day_show_method'] == 'all_days') : ?>
                                        <span class="mec-event-d"><?php echo $this->icons->display('calendar'); ?><?php echo esc_html($this->main->date_i18n($this->date_format_standard_1, strtotime($event->date['start']['date']))); ?></span>
                                    <?php else: ?>
                                        <span class="mec-event-d"><?php echo $this->icons->display('calendar'); ?><?php echo MEC_kses::element($this->main->dateify($event, $this->date_format_standard_1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php echo MEC_kses::element($this->get_label_captions($event)); ?>
                                <?php echo MEC_kses::element($this->main->display_time($start_time, $end_time, ['display_svg' => true, 'icon' => ($this->icons->has('clock') ? $this->icons->display('clock') : '')])); ?>
                                <?php if($this->localtime) echo MEC_kses::full($this->main->module('local-time.type1', array('event' => $event, 'display_svg' => true ))); ?>
                                <?php if(isset($location['name'])): ?>
                                <div class="mec-venue-details">
                                    <?php echo $this->icons->has('location-pin') ? $this->icons->display('location-pin') : '<svg xmlns="http://www.w3.org/2000/svg" width="12.308" height="16" viewBox="0 0 12.308 16"><path id="location" d="M6.6,15.839a.7.7,0,0,1-.89,0C5.476,15.644,0,11,0,6.029A6.1,6.1,0,0,1,6.154,0a6.1,6.1,0,0,1,6.154,6.029C12.308,11,6.832,15.644,6.6,15.839ZM6.154,1.333a4.747,4.747,0,0,0-4.786,4.7c0,3.6,3.52,7.215,4.786,8.4,1.266-1.184,4.787-4.8,4.787-8.4A4.747,4.747,0,0,0,6.154,1.333Zm0,7.383A2.7,2.7,0,0,1,3.419,6.049,2.7,2.7,0,0,1,6.154,3.383,2.7,2.7,0,0,1,8.889,6.049,2.7,2.7,0,0,1,6.154,8.716Zm0-4A1.334,1.334,0,1,0,7.521,6.049,1.353,1.353,0,0,0,6.154,4.716Z" fill="#40d9f1" fill-rule="evenodd"/></svg>'; ?>
                                    <span><?php echo esc_html($location['name']); ?></span>
                                    <?php if(isset($location['address']) && trim($location['address'])): ?>
                                    <address class="mec-event-address"><span><?php echo esc_html($location['address']); ?></span></address>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <?php echo MEC_kses::element($this->display_categories($event)); ?>
                                <?php echo MEC_kses::element($this->display_organizers($event)); ?>
                                <?php echo MEC_kses::element($this->display_cost($event)); ?>
                                <?php do_action('mec_list_standard_right_box', $event); ?>

                                <?php
                                include_once ABSPATH . 'wp-admin/includes/plugin.php';
                                if (function_exists('is_plugin_active') && is_plugin_active('mec-customize/mec-customize.php')) {
        ?>
                                <style>
                                    .mec-event-meta dl, .mec-event-meta dd{
                                        margin: 0!important;
                                    }
                                    .mec-event-meta > div{
                                        border-style: solid;
                                        border-width: 0px 0px 1px 0px;
                                        border-color: var(--e-global-color-primary);
                                        padding-bottom: 10px;
                                    }
                                    .mec-event-meta .mec-event-data-fields-customize{
                                        border-style: none;
                                        border-width: 0px;
                                        border-color: unset;
                                        padding-bottom: 0px;
                                    }
                                    .mec-event-data-fields-customize{
                                        padding: 0px 0px 0px 0px;
                                        margin: 0px 0px 0px 0px;
                                        border-style: none;
                                    }
                                    .mec-event-data-fields-customize ul{
                                        overflow: hidden;
                                        padding-left: 0;
                                        margin-left: 0;
                                        padding-top: 0px !important;
                                    }
                                    .mec-event-data-fields-customize ul li{
                                        width: 100%;
                                        display: block;
                                        height: auto;
                                        padding-bottom: 10px;
                                        border-style: solid;
                                        border-width: 0px 0px 1px 0px;
                                        border-color: var(--e-global-color-primary);
                                        list-style: none;
                                        margin-bottom: 10px;
                                    }
                                    .mec-event-data-fields-customize ul li .mec-event-data-field-name
                                    {
                                        font-size: 18px !important;
                                        font-weight: 900 !important;
                                        line-height: 1.2em !important;
                                        color: #000 !important;
                                        padding: 0px 0px 0px 0px;
                                        margin: 0px 0px 0px 0px;
                                    }
                                    .mec-event-data-fields-customize ul li .mec-event-data-field-value
                                    {
                                        font-size: 18px !important;
                                        font-weight: 900 !important;
                                        line-height: 1.2em !important;
                                        color: #000 !important;
                                        padding: 0px 0px 0px 0px;
                                        margin: 0px 0px 0px 0px;
                                    }
                                    .mec-event-more-info-customize {
                                        text-align: left;
                                        padding: 0px 0px 18px 0px;
                                        margin: 0px 0px 0px 0px;
                                        border-style: solid;
                                        border-width: 0px 0px 1px 0px;
                                        border-color: var(--e-global-color-primary);
                                    }

                                    .mec-event-more-info-customize .mec-event-meta dd
                                    {
                                        display: inline-block;
                                        margin: 0;
                                    }
                                </style>

                                <?php

                                $main = MEC::getInstance('app.libraries.main');
                                $settings = $main->get_settings();
                                $display = !isset($settings['display_event_fields']) || $settings['display_event_fields'];
                                if(!$display and !$sidebar and !$shortcode) return;

                                $fields = $main->get_event_fields();
                                if(!is_array($fields) || !count($fields)) return;

                                // Start Timestamp
                                $start_timestamp = (isset($event->date) and isset($event->date['start']) and isset($event->date['start']['timestamp'])) ? $event->date['start']['timestamp'] : NULL;

                                $data = (isset($event->data) and isset($event->data->meta) and isset($event->data->meta['mec_fields']) and is_array($event->data->meta['mec_fields'])) ? $event->data->meta['mec_fields'] : get_post_meta($event->ID, 'mec_fields', true);
                                if($start_timestamp) $data = MEC_feature_occurrences::param($event->ID, $start_timestamp, 'fields', $data);

                                if(!is_array($data) || !count($data)) return;

                                foreach($fields as $n => $item)
                                {
                                    // n meaning number
                                    if(!is_numeric($n)) continue;

                                    $result = $data[$n] ?? '';
                                    if((!is_array($result) && trim($result) == '') || (is_array($result) && !count($result))) continue;

                                    $content = $item['type'] ?? 'text';
                                    if($content === 'checkbox')
                                    {
                                        $cleaned = [];
                                        foreach($result as $k => $v)
                                        {
                                            if(trim($v) !== '') $cleaned[] = $v;
                                        }

                                        $value = $cleaned;
                                        if(!count($value))
                                        {
                                            $content = NULL;
                                        }
                                    }
                                }

                                if(isset($content) && $content != NULL && (isset($settings['display_event_fields_backend']) and $settings['display_event_fields_backend'] == 1) or !isset($settings['display_event_fields_backend']))
                                {
                                $date_format = get_option('date_format');
                                ?>
                    <div class="mec-event-data-fields-customize mec-frontbox">
                        <div class="mec-data-fields-box">
                            <ul class="mec-event-data-field-items">
                                <?php foreach($fields as $f => $field): if(!is_numeric($f)) continue; ?>
                                    <?php
                                    $value = $data[$f] ?? '';
                                    $type = $field['type'] ?? 'text';

                                    if($type !== 'p' && ((!is_array($value) && trim($value) == '') || (is_array($value) && !count($value)))) continue;

                                    if($type === 'checkbox')
                                    {
                                        $cleaned = [];
                                        foreach($value as $k => $v)
                                        {
                                            if(trim($v) !== '') $cleaned[] = $v;
                                        }

                                        $value = $cleaned;
                                        if(!count($value)) continue;
                                    }

                                    $icon = $field['icon'] ?? '';
                                    ?>
                                    <li class="mec-event-data-field-item mec-field-item-<?php echo esc_attr($type); ?>">
                                        <?php if(trim($icon)): ?>
                                            <img class="mec-custom-field-icon" src="<?php echo esc_url($icon); ?>" alt="<?php echo (isset($field['label']) ? esc_attr($field['label']) : ''); ?>">
                                        <?php endif; ?>

                                        <?php if(isset($field['label'])): ?>
                                            <span class="mec-event-data-field-name"><?php esc_html_e(stripslashes($field['label']), 'modern-events-calendar-lite'); ?></span>
                                        <?php endif; ?>

                                        <?php if($type === 'email'): ?>
                                            <span class="mec-event-data-field-value"><a href="mailto:<?php echo esc_attr($value); ?>"><?php echo esc_html($value); ?></a></span>
                                        <?php elseif($type === 'tel'): ?>
                                            <span class="mec-event-data-field-value"><a href="tel:<?php echo esc_attr($value); ?>"><?php echo esc_html($value); ?></a></span>
                                        <?php elseif($type === 'p'): ?>
                                            <span class="mec-event-data-field-value"><?php echo $field['content'] ?? '' ?></span>
                                        <?php elseif($type === 'url'): ?>
                                            <span class="mec-event-data-field-value"><a href="<?php echo esc_url($value); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($value); ?></a></span>
                                        <?php elseif($type === 'date'): $value = $this->main->to_standard_date($value); ?>
                                            <span class="mec-event-data-field-value"><?php echo esc_html($this->main->date_i18n($date_format, strtotime($value))); ?></span>
                                        <?php elseif($type === 'textarea'): ?>
                                            <span class="mec-event-data-field-value"><?php echo !is_array($value) ? wpautop(stripslashes($value)) : ''; ?></span>
                                        <?php else: ?>
                                            <span class="mec-event-data-field-value"><?php echo (is_array($value) ? esc_html(stripslashes(implode(', ', $value))) : esc_html(stripslashes($value))); ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php
                    }

                                $main = MEC::getInstance('app.libraries.main');
                                $icons 	      = $main->icons($settings_mec['icons'] ?? []);
                                $settings = $main->get_settings();

                                $more_info = (isset($event->data->meta['mec_more_info']) and trim($event->data->meta['mec_more_info']) and $event->data->meta['mec_more_info'] != 'http://') ? $event->data->meta['mec_more_info'] : '';
                                if(isset($event->date) and isset($event->date['start']) and isset($event->date['start']['timestamp'])) $more_info = MEC_feature_occurrences::param($event->ID, $event->date['start']['timestamp'], 'more_info', $more_info);

                                $more_info_target = MEC_feature_occurrences::param($event->ID, $event->date['start']['timestamp'], 'more_info_target', $event->data->meta['mec_more_info_target'] ?? '');
                                if(!trim($more_info_target) && isset($settings['fes_event_link_target']) && trim($settings['fes_event_link_target'])) $more_info_target = $settings['fes_event_link_target'];

                                $more_info_title = MEC_feature_occurrences::param($event->ID, $event->date['start']['timestamp'], 'more_info_title', ((isset($event->data->meta['mec_more_info_title']) and trim($event->data->meta['mec_more_info_title'])) ? $event->data->meta['mec_more_info_title'] : esc_html__('Read More', 'modern-events-calendar-lite')));

                                if($more_info)
                                {
                                ?>
                                <div class="mec-event-more-info mec-event-more-info-customize">
                                    <?php echo $icons->display('info'); ?>
                                    <dl><dd class="mec-events-event-more-info"><a class="mec-more-info-button mec-color-hover" target="<?php echo esc_attr($more_info_target); ?>" href="<?php echo esc_url($more_info); ?>"><?php echo esc_html($more_info_title); ?></a></dd></dl>
                                </div>
                                <?php
                                }
                                }

                                ?>
<!--                                --><?php //do_action('mec_customize_fields', $event); ?>
                            </div>
                        </div>
                    </div>
                    <div class="mec-event-footer">
                        <?php if(isset($settings['social_network_status']) and $settings['social_network_status'] != '0') : ?>
                        <ul class="mec-event-sharing-wrap">
                            <li class="mec-event-share">
                                <a href="#" class="mec-event-share-icon">
                                    <i class="mec-sl-share" title="<?php esc_attr_e('Share', 'modern-events-calendar-lite') ?>" alt="<?php esc_attr_e('Share', 'modern-events-calendar-lite') ?>"></i>
                                </a>
                            </li>
                            <li>
                                <ul class="mec-event-sharing">
                                    <?php echo MEC_kses::full($this->main->module('links.list', array('event' => $event))); ?>
                                </ul>
                            </li>
                        </ul>
                        <?php endif; ?>
                        <?php echo MEC_kses::full($this->main->display_progress_bar($event)); ?>
                        <?php do_action('mec_standard_booking_button', $event); ?>
                        <?php echo MEC_kses::form($this->booking_button($event)); ?>
                        <?php echo MEC_kses::element($this->display_link($event, ((is_array($event->data->tickets) and count($event->data->tickets) and !strpos($soldout, '%%soldout%%') and !$this->booking_button and !$this->main->is_expired($event)) ? $this->main->m('register_button', esc_html__('REGISTER', 'modern-events-calendar-lite')) : $this->main->m('view_detail', esc_html__('View Detail', 'modern-events-calendar-lite'))), 'mec-booking-button')); ?>
                    </div>
                <?php elseif($this->style == 'accordion'): ?>
                    <!-- toggles wrap start -->
                    <div class="mec-events-toggle">
                        <!-- toggle item start -->
                        <div class="mec-toggle-item">
                            <div class="mec-toggle-item-inner<?php if($this->toggle_month_divider == '1') echo ' mec-toogle-inner-month-divider'; ?>" tabindex="0">
                                <?php if($this->toggle_month_divider == '1'): ?>
                                <div class="mec-toggle-month-inner-image">
                                    <?php $thumb = $this->get_thumbnail_image($event, 'thumbnail'); ?>
                                    <a href="<?php echo esc_url($this->main->get_event_date_permalink($event, $event->date['start']['date'])); ?>"><?php echo MEC_kses::element($thumb); ?></a>
                                </div>
                                <?php endif; ?>
                                <div class="mec-toggle-item-col">
                                    <?php if(isset($settings['multiple_day_show_method']) && $settings['multiple_day_show_method'] == 'all_days') : ?>
                                        <div class="mec-event-date"><?php echo esc_html($this->main->date_i18n($this->date_format_acc_1, strtotime($event->date['start']['date']))); ?></div>
                                        <div class="mec-event-month"><?php echo esc_html($this->main->date_i18n($this->date_format_acc_2, strtotime($event->date['start']['date']))); ?></div>
                                    <?php else: ?>
                                        <div class="mec-event-month"><?php echo MEC_kses::element($this->main->dateify($event, $this->date_format_acc_1.' '.$this->date_format_acc_2)); ?></div>
                                    <?php endif; ?>
                                    <?php echo MEC_kses::element($this->main->display_time($start_time, $end_time)); ?>
                                </div>
                                <h3 class="mec-toggle-title">
                                    <?php
                                        echo apply_filters(
                                            'mec_events_toggle_title',
                                            MEC_kses::element($event->data->title),
                                            $event,
                                            $this
                                        );
                                    ?><?php echo MEC_kses::element($this->main->get_flags($event).$event_color); ?></h3>
                                <?php echo MEC_kses::element($this->get_label_captions($event,'mec-fc-style')); ?>
                                <?php echo MEC_kses::element($this->main->get_normal_labels($event, $display_label).$this->main->display_cancellation_reason($event, $reason_for_cancellation)); ?><?php do_action('mec_shortcode_virtual_badge', $event->data->ID); ?><i class="mec-sl-arrow-down"></i>
                            </div>
                            <div class="mec-content-toggle" aria-hidden="true" style="display: none;">
                                <div class="mec-toggle-content">
                                    <?php echo MEC_kses::full($this->render->vsingle(array('id' => $event->data->ID, 'layout' => 'm2', 'occurrence' => $date))); ?>
                                </div>
                            </div>
                        </div><!-- toggle item end -->
                    </div><!-- toggles wrap end -->
                <?php elseif($this->style === 'admin'): ?>
                    <div class="col-md-2 col-sm-2">
                        <?php if($this->main->is_multipleday_occurrence($event, true)): ?>
                            <div class="mec-event-date">
                                <div class="event-d mec-color mec-multiple-dates">
                                    <?php echo esc_html($this->main->date_i18n($this->date_format_modern_1, strtotime($event->date['start']['date']))); ?> -
                                    <?php echo esc_html($this->main->date_i18n($this->date_format_modern_1, strtotime($event->date['end']['date']))); ?>
                                </div>
                                <div class="event-f"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_2, strtotime($event->date['start']['date']))); ?></div>
                                <div class="event-da"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_3, strtotime($event->date['start']['date']))); ?></div>
                            </div>
                        <?php elseif($this->main->is_multipleday_occurrence($event)): ?>
                            <div class="mec-event-date mec-multiple-date-event">
                                <div class="event-d mec-color"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_1, strtotime($event->date['start']['date']))); ?></div>
                                <div class="event-f"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_2, strtotime($event->date['start']['date']))); ?></div>
                                <div class="event-da"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_3, strtotime($event->date['start']['date']))); ?></div>
                            </div>
                            <div class="mec-event-date mec-multiple-date-event">
                                <div class="event-d mec-color"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_1, strtotime($event->date['end']['date']))); ?></div>
                                <div class="event-f"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_2, strtotime($event->date['end']['date']))); ?></div>
                                <div class="event-da"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_3, strtotime($event->date['end']['date']))); ?></div>
                            </div>
                        <?php else: ?>
                            <div class="mec-event-date">
                                <div class="event-d mec-color"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_1, strtotime($event->date['start']['date']))); ?></div>
                                <div class="event-f"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_2, strtotime($event->date['start']['date']))); ?></div>
                                <div class="event-da"><?php echo esc_html($this->main->date_i18n($this->date_format_modern_3, strtotime($event->date['start']['date']))); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8 col-sm-8">
                        <?php $soldout = $this->main->get_flags($event); ?>
                        <h4 class="mec-event-title">
                            <a class="event-link-admin" href="<?php echo esc_url(get_edit_post_link($event->ID)); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo apply_filters('mec_occurrence_event_title', $event->data->title, $event); ?>
                            </a>
                            <?php echo MEC_kses::element($soldout.$event_color); echo MEC_kses::element($this->main->get_normal_labels($event, $display_label).$this->main->display_cancellation_reason($event, $reason_for_cancellation)); ?>
                            <?php do_action('mec_shortcode_virtual_badge', $event->data->ID); ?>
                            <?php echo MEC_kses::element($this->get_label_captions($event,'mec-fc-style')); ?>
                        </h4>
                        <div class="mec-event-detail">
                            <div class="mec-event-loc-place"><?php echo (isset($location['name']) ? esc_html($location['name']) : '') . (isset($location['address']) && !empty($location['address']) ? ' | '.esc_html($location['address']) : ''); ?></div>
                            <?php if($this->include_events_times and trim($start_time)) echo MEC_kses::element($this->main->display_time($start_time, $end_time)); ?>
                            <?php echo MEC_kses::element($this->display_categories($event)); ?>
                            <?php echo MEC_kses::element($this->display_organizers($event)); ?>
                            <?php echo MEC_kses::element($this->display_cost($event)); ?>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-2">
                        <?php if(isset($event->date['start']['timestamp']) && current_user_can(current_user_can('administrator') ? 'manage_options' : 'mec_bookings') && $total_attendees = $this->main->get_total_attendees_by_event_occurrence($event->data->ID, $event->date['start']['timestamp'])): ?>
                        <a href="<?php echo trim($this->main->URL('admin'), '/ ').'/?mec-dl-bookings=1&event_id='.$event->data->ID.'&occurrence='.$event->date['start']['timestamp']; ?>"><?php echo esc_html__('Download Attendees', 'modern-events-calendar-lite'); ?> (<?php echo esc_html($total_attendees); ?>)</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </article>
            <?php } ?>
		<?php endforeach; ?>
	</div>
</div>

<?php
if(isset($this->map_on_top) and $this->map_on_top and isset($map_events) and !empty($map_events))
{
    // Include Map Assets such as JS and CSS libraries (pass settings so OSM assets can load)
    $this->main->load_map_assets(true, $settings);

    // It changing geolocation focus, because after done filtering, if it doesn't. then the map position will not set correctly.
    if((isset($_REQUEST['action']) and sanitize_text_field($_REQUEST['action']) == 'mec_list_load_more') and isset($_REQUEST['sf'])) $this->geolocation_focus = true;

    $events_data = $this->render->markers($map_events, $this->style);
    $map_type = $settings['default_maps_view'] ?? 'google';

    $map_javascript = '<script>
    (function($){
        var attempts = 0;
        var maxAttempts = 50;
        var mapType = "'.esc_js($map_type).'";
        var containerSelector = "#mec_googlemap_canvas'.esc_js($this->id).'";

        function initWhenVisible(fn){
            var intervalId = setInterval(function(){
                if($(containerSelector).is(":visible")){
                    try { fn(); } catch(e) {}
                    clearInterval(intervalId);
                }
            }, 300);
        }

        function tryInit(){
            attempts++;
            if(attempts > maxAttempts){
                init();
                return;
            }

            if(typeof jQuery === "undefined"){ setTimeout(tryInit, 100); return; }

            if(mapType === "openstreetmap"){
                if(typeof $.fn.mecOpenstreetMaps === "undefined"){ setTimeout(tryInit, 100); return; }
            }else{
                if(typeof $.fn.mecGoogleMaps === "undefined"){ setTimeout(tryInit, 100); return; }
            }

            init();
        }

        function init(){
            if(mapType === "openstreetmap"){
                initWhenVisible(function(){
                    $(containerSelector).mecOpenstreetMaps({
                        show_on_openstreetmap_text: "'.esc_js( __( 'Show on OpenstreetMap', 'mec-advanced-map' ) ) . '",
                        id: "'.esc_js($this->id).'",
                        atts: "'.http_build_query(array('atts' => $this->atts), '', '&').'",
                        zoom: '.(isset($settings['google_maps_zoomlevel']) ? (int)$settings['google_maps_zoomlevel'] : 14).',
                        scrollwheel: '.((isset($settings['default_maps_scrollwheel']) and $settings['default_maps_scrollwheel']) ? 'true' : 'false').',
                        markers: '.json_encode($events_data).',
                        HTML5geolocation: "'.esc_js($this->geolocation).'",
                        ajax_url: "'.admin_url('admin-ajax.php', NULL).'",
                        sf: { container: "'.($this->sf_status ? '#mec_search_form_'.esc_js($this->id) : '').'" }
                    });
                });
            } else {
                var jsonPush = gmapSkin('.json_encode($events_data).');
                var mecmap = $(containerSelector).mecGoogleMaps({
                    id: "'.esc_js($this->id).'",
                    autoinit: false,
                    atts: "'.http_build_query(array('atts' => $this->atts), '', '&').'",
                    zoom: '.(isset($settings['google_maps_zoomlevel']) ? $settings['google_maps_zoomlevel'] : 14).',
                    icon: "'.apply_filters('mec_marker_icon', $this->main->asset('img/m-04.png')).'",
                    styles: '.((isset($settings['google_maps_style']) and trim($settings['google_maps_style']) != '') ? $this->main->get_googlemap_style($settings['google_maps_style']) : "''").',
                    markers: jsonPush,
                    clustering_images: "'.esc_js($this->main->asset('img/cluster1/m')).'",
                    getDirection: 0,
                    ajax_url: "'.admin_url('admin-ajax.php', NULL).'",
                    geolocation: "'.esc_js($this->geolocation).'",
                    geolocation_focus: '.esc_js($this->geolocation_focus).'
                });

                initWhenVisible(function(){
                    if(mecmap && typeof mecmap.init === "function") mecmap.init();
                });
            }
        }

        $(document).ready(function(){ tryInit(); });
    })(jQuery);
    </script>';

    $map_javascript = apply_filters('mec_map_load_script', $map_javascript, $this, $settings,$this->map_on_top);

    // Include javascript code into the page
    if($this->main->is_ajax()) echo MEC_kses::full($map_javascript);
    else $this->factory->params('footer', $map_javascript);
}

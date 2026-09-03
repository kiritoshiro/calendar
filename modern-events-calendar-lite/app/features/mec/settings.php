<?php
/** no direct access **/
defined('MECEXEC') or die();

/** @var MEC_feature_mec $this */

$settings = $this->main->get_settings();
$archive_skins = $this->main->get_archive_skins();
$category_skins = $this->main->get_category_skins();

$currencies = $this->main->get_currencies();

// WordPress Pages
$pages = get_pages();

echo MEC_kses::full($this->main->mec_custom_msg_2('yes', 'yes'));
echo MEC_kses::full($this->main->mec_custom_msg('', ''));

// Display Addons Notification
$get_n_option = get_option('mec_addons_notification_option');

$shortcodes = get_posts(array(
    'post_type' => 'mec_calendars',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'order' => 'DESC'
));
?>
<div class="wns-be-container wns-be-container-sticky">
    <div id="wns-be-infobar">
        <div class="mec-search-settings-wrap">
            <i class="mec-sl-magnifier"></i>
            <input id="mec-search-settings" type="text" placeholder="<?php esc_html_e('Search...' , 'modern-events-calendar-lite'); ?>">
        </div>
        <a id="" class="dpr-btn dpr-save-btn"><?php esc_html_e('Save Changes', 'modern-events-calendar-lite'); ?></a>
    </div>

    <div class="wns-be-sidebar">
        <?php $this->main->get_sidebar_menu('settings'); ?>
    </div>

    <div class="wns-be-main general-settings">
        <div id="wns-be-notification"></div>
        <div id="wns-be-content">
            <div class="wns-be-group-tab">
                <div class="mec-container">

                    <form id="mec_settings_form">

                        <div id="general_option" class="mec-options-fields active">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('General', 'modern-events-calendar-lite'); ?></h4>

                            <div class="mec-backend-tab-wrap mec-basvanced-toggle" data-for="#general_option">
                                <div class="mec-backend-tab">
                                    <div class="mec-backend-tab-item mec-b-active-tab"><?php esc_html_e('Basic', 'modern-events-calendar-lite'); ?></div>
                                    <div class="mec-backend-tab-item"><?php esc_html_e('Advanced', 'modern-events-calendar-lite'); ?></div>
                                </div>
                            </div>

                            <div class="mec-form-row mec-basvanced-basic">
                                <label class="mec-col-3" for="mec_settings_hide_time_method"><?php esc_html_e('Hide Events', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select id="mec_settings_time_format" name="mec[settings][hide_time_method]" onchange="if(this.value === 'plusn') jQuery('#mec_settings_hide_time_n_options').show(); else jQuery('#mec_settings_hide_time_n_options').hide();">
                                        <option value="start" <?php if(isset($settings['hide_time_method']) && 'start' == $settings['hide_time_method']) echo 'selected="selected"'; ?>><?php esc_html_e('On Event Start', 'modern-events-calendar-lite'); ?></option>
                                        <option value="plus1" <?php if(isset($settings['hide_time_method']) && 'plus1' == $settings['hide_time_method']) echo 'selected="selected"'; ?>><?php esc_html_e('+1 Hour after start', 'modern-events-calendar-lite'); ?></option>
                                        <option value="plus2" <?php if(isset($settings['hide_time_method']) && 'plus2' == $settings['hide_time_method']) echo 'selected="selected"'; ?>><?php esc_html_e('+2 Hours after start', 'modern-events-calendar-lite'); ?></option>
                                        <option value="plusn" <?php if(isset($settings['hide_time_method']) && 'plusn' == $settings['hide_time_method']) echo 'selected="selected"'; ?>><?php esc_html_e('+N Hours after start', 'modern-events-calendar-lite'); ?></option>
                                        <option value="end" <?php if(isset($settings['hide_time_method']) && 'end' == $settings['hide_time_method']) echo 'selected="selected"'; ?>><?php esc_html_e('On Event End', 'modern-events-calendar-lite'); ?></option>
                                        <?php do_action('mec_hide_time_methods', $settings); ?>
                                    </select>
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Hide Events', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("When should events be hidden from the Archive page and shortcodes?", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#1-_Hide_Events/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mec-form-row mec-basvanced-basic" style="<?php echo isset($settings['hide_time_method']) && 'plusn' == $settings['hide_time_method'] ? '' : 'display: none;'; ?>" id="mec_settings_hide_time_n_options">
                                <label class="mec-col-3" for="mec_settings_hide_time_n"><?php esc_html_e('Hide Events Hour', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <input type="number" min="2" step="1" id="mec_settings_hide_time_n" name="mec[settings][hide_time_n]" value="<?php echo isset($settings['hide_time_n']) && is_numeric($settings['hide_time_n']) && $settings['hide_time_n'] > 0 ? $settings['hide_time_n'] : 2; ?>">
                                </div>
                            </div>

                            <div class="mec-form-row mec-basvanced-basic">

                                <label class="mec-col-3" for="mec_settings_multiple_day_show_method"><?php esc_html_e('Multiple Day Events Show', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select id="mec_settings_multiple_day_show_method" name="mec[settings][multiple_day_show_method]">
                                        <option value="first_day_listgrid" <?php if(isset($settings['multiple_day_show_method']) and $settings['multiple_day_show_method'] == 'first_day_listgrid') echo 'selected="selected"'; ?>><?php esc_html_e('First day on list/grid/slider/agenda skins', 'modern-events-calendar-lite'); ?></option>
                                        <option value="first_day" <?php if(isset($settings['multiple_day_show_method']) and $settings['multiple_day_show_method'] == 'first_day') echo 'selected="selected"'; ?>><?php esc_html_e('First day on all skins', 'modern-events-calendar-lite'); ?></option>
                                        <option value="all_days" <?php if(isset($settings['multiple_day_show_method']) and $settings['multiple_day_show_method'] == 'all_days') echo 'selected="selected"'; ?>><?php esc_html_e('All days', 'modern-events-calendar-lite'); ?></option>
                                    </select>
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Multiple Day Events', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("How should multi-day events be displayed in different skins? This option does not affect the General view.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#2-_Multiple_Day_Events_Show/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>

                            </div>

                            <div class="mec-form-row mec-basvanced-advanced w-hidden">
                                <label class="mec-col-3"><?php esc_html_e('Exclude Date Suffix', 'modern-events-calendar-lite'); ?></label>
                                <label>
                                    <input type="hidden" name="mec[settings][date_suffix]" value="0" />
                                    <input value="1" type="checkbox" name="mec[settings][date_suffix]" <?php if(isset($settings['date_suffix']) and $settings['date_suffix']) echo 'checked="checked"'; ?> /><?php esc_html_e('Remove suffix from calendars', 'modern-events-calendar-lite'); ?>
                                </label>
                                <span class="mec-tooltip">
                                    <div class="box left">
                                        <h5 class="title"><?php esc_html_e('Remove "Th" on calendar', 'modern-events-calendar-lite'); ?></h5>
                                        <div class="content"><p><?php esc_attr_e("Enabling this option will remove the 'th' from the monthly view skin dates. Ex: 12th will become 12.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-options/#1-_Exclude_Date_Suffix/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                    </div>
                                    <i title="" class="dashicons-before dashicons-editor-help"></i>
                                </span>
                            </div>

                            <div class="mec-form-row mec-basvanced-advanced w-hidden">
                                <label class="mec-col-3"><?php esc_html_e('Event End Time', 'modern-events-calendar-lite'); ?></label>
                                <label>
                                    <input type="hidden" name="mec[settings][hide_event_end_time]" value="0" />
                                    <input value="1" type="checkbox" name="mec[settings][hide_event_end_time]" <?php if(isset($settings['hide_event_end_time']) and $settings['hide_event_end_time']) echo 'checked="checked"'; ?> /><?php esc_html_e('Hide for all events', 'modern-events-calendar-lite'); ?>
                                </label>
                                <span class="mec-tooltip">
                                    <div class="box left">
                                        <h5 class="title"><?php esc_html_e('Hide Event End Time', 'modern-events-calendar-lite'); ?></h5>
                                        <div class="content"><p><?php esc_attr_e("Enabling this option will hide the event end time from all events. If you leave it unckecked you're still able to disable it per event.", 'modern-events-calendar-lite'); ?></p></div>
                                    </div>
                                    <i title="" class="dashicons-before dashicons-editor-help"></i>
                                </span>
                            </div>

                            <div class="mec-form-row mec-basvanced-advanced w-hidden">
                                <label class="mec-col-3" for="mec_settings_schema"><?php esc_html_e('Schema', 'modern-events-calendar-lite'); ?></label>
                                <label id="mec_settings_schema">
                                    <input type="hidden" name="mec[settings][schema]" value="0" />
                                    <input value="1" type="checkbox" name="mec[settings][schema]" <?php if(!isset($settings['schema']) or (isset($settings['schema']) and $settings['schema'])) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable Schema Code', 'modern-events-calendar-lite'); ?>
                                </label>
                                <span class="mec-tooltip">
                                    <div class="box left">
                                        <h5 class="title"><?php esc_html_e('Schema', 'modern-events-calendar-lite'); ?></h5>
                                        <div class="content"><p><?php esc_attr_e("This option will enable Event Schema Markup on your site.", 'modern-events-calendar-lite'); ?><a href="https://developers.google.com/search/docs/advanced/structured-data/event" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                    </div>
                                    <i title="" class="dashicons-before dashicons-editor-help"></i>
                                </span>
                            </div>

                            <?php $weekdays = $this->main->get_weekday_i18n_labels(); ?>
                            <div class="mec-form-row mec-basvanced-basic">

                                <label class="mec-col-3" for="mec_settings_weekdays"><?php esc_html_e('Weekdays', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <div class="mec-box">
                                        <?php $mec_weekdays = $this->main->get_weekdays(); foreach($weekdays as $weekday): ?>
                                        <label for="mec_settings_weekdays_<?php echo esc_attr($weekday[0]); ?>">
                                            <input type="checkbox" id="mec_settings_weekdays_<?php echo esc_attr($weekday[0]); ?>" name="mec[settings][weekdays][]" value="<?php echo esc_attr($weekday[0]); ?>" <?php echo (in_array($weekday[0], $mec_weekdays) ? 'checked="checked"' : ''); ?> />
                                            <?php echo esc_html($weekday[1]); ?>
                                        </label>
                                        <?php endforeach; ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Weekdays', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e("You can set the weekdays depending on your region from WordPress Dashboard > Settings > General > Week Starts On.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#3-_Weekdays/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>

                            </div>

                            <div class="mec-form-row mec-basvanced-basic">

                                <label class="mec-col-3" for="mec_settings_weekends"><?php esc_html_e('Weekends', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                <div class="mec-box">
                                    <?php $mec_weekends = $this->main->get_weekends(); foreach($weekdays as $weekday): ?>
                                    <label for="mec_settings_weekends_<?php echo esc_attr($weekday[0]); ?>">
                                        <input type="checkbox" id="mec_settings_weekends_<?php echo esc_attr($weekday[0]); ?>" name="mec[settings][weekends][]" value="<?php echo esc_attr($weekday[0]); ?>" <?php echo (in_array($weekday[0], $mec_weekends) ? 'checked="checked"' : ''); ?> />
                                        <?php echo esc_html($weekday[1]); ?>
                                    </label>
                                    <?php endforeach; ?>
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Weekends', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("You can set the weekend days depending on your region.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#4-_Weekends/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>
                                </div>

                            </div>

                            <div class="mec-form-row mec-basvanced-basic">
                                <label class="mec-col-3" for="mec_settings_datepicker_format"><?php esc_html_e('Date Picker Format', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select id="mec_settings_datepicker_format" name="mec[settings][datepicker_format]">
                                        <?php
                                            $selected = (isset($settings['datepicker_format']) and trim($settings['datepicker_format'])) ? trim($settings['datepicker_format']) : 'yy-mm-dd&Y-m-d';
                                            $current_time = current_time('timestamp', 0);
                                        ?>
                                        <!-- ++++ dd-mm-yy ++++ -->
                                        <option value="yy-mm-dd&Y-m-d" <?php selected($selected, 'yy-mm-dd&Y-m-d'); ?>><?php echo date('Y-m-d', $current_time) . ' ' . esc_html__('(Y-m-d)', 'modern-events-calendar-lite'); ?></option>
                                        <option value="dd-mm-yy&d-m-Y" <?php selected($selected, 'dd-mm-yy&d-m-Y'); ?>><?php echo date('d-m-Y', $current_time) . ' ' . esc_html__('(d-m-Y)', 'modern-events-calendar-lite'); ?></option>

                                        <!-- ++++ dd/mm/yy ++++ -->
                                        <option value="yy/mm/dd&Y/m/d" <?php selected($selected, 'yy/mm/dd&Y/m/d'); ?>><?php echo date('Y/m/d', $current_time) . ' ' . esc_html__('(Y/m/d)', 'modern-events-calendar-lite'); ?></option>
                                        <option value="mm/dd/yy&m/d/Y" <?php selected($selected, 'mm/dd/yy&m/d/Y'); ?>><?php echo date('m/d/Y', $current_time) . ' ' . esc_html__('(m/d/Y)', 'modern-events-calendar-lite'); ?></option>

                                        <!-- ++++ dd.mm.yy ++++ -->
                                        <option value="yy.mm.dd&Y.m.d" <?php selected($selected, 'yy.mm.dd&Y.m.d'); ?>><?php echo date('Y.m.d', $current_time) . ' ' . esc_html__('(Y.m.d)', 'modern-events-calendar-lite'); ?></option>
                                        <option value="dd.mm.yy&d.m.Y" <?php selected($selected, 'dd.mm.yy&d.m.Y'); ?>><?php echo date('d.m.Y', $current_time) . ' ' . esc_html__('(d.m.Y)', 'modern-events-calendar-lite'); ?></option>
                                    </select>
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Date Picker Format', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("Set the date format of the datepicker module that appears on the event add/edit page and the FES form.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#5-_Datepicker_Format/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="mec-form-row mec-basvanced-basic">
                                <label class="mec-col-3" for="mec_settings_time_format"><?php esc_html_e('Time Picker Format', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select id="mec_settings_time_format" name="mec[settings][time_format]">
                                        <option value="12" <?php if(isset($settings['time_format']) and '12' == $settings['time_format']) echo 'selected="selected"'; ?>><?php esc_html_e('12 hours format with AM/PM', 'modern-events-calendar-lite'); ?></option>
                                        <option value="24" <?php if(isset($settings['time_format']) and '24' == $settings['time_format']) echo 'selected="selected"'; ?>><?php esc_html_e('24 hours format', 'modern-events-calendar-lite'); ?></option>
                                    </select>
                                    <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Time Picker Format', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e("This option affects the selection of the Start/End time in the FES Form and also on the event add/edit page on the backend.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/frontend-event-submission/#1-_Time_Format/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                </div>
                            </div>

                            <div class="mec-form-row mec-basvanced-basic">
                                <label class="mec-col-3" for="mec_settings_midnight_hour"><?php esc_html_e('Midnight Hour', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select id="mec_settings_midnight_hour" name="mec[settings][midnight_hour]">
                                        <option value="0" <?php if(isset($settings['midnight_hour']) and !$settings['midnight_hour']) echo 'selected="selected"'; ?>><?php esc_html_e('12 AM', 'modern-events-calendar-lite'); ?></option>
                                        <option value="1" <?php if(isset($settings['midnight_hour']) and $settings['midnight_hour'] == '1') echo 'selected="selected"'; ?>><?php esc_html_e('1 AM', 'modern-events-calendar-lite'); ?></option>
                                        <option value="2" <?php if(isset($settings['midnight_hour']) and $settings['midnight_hour'] == '2') echo 'selected="selected"'; ?>><?php esc_html_e('2 AM', 'modern-events-calendar-lite'); ?></option>
                                        <option value="3" <?php if(isset($settings['midnight_hour']) and $settings['midnight_hour'] == '3') echo 'selected="selected"'; ?>><?php esc_html_e('3 AM', 'modern-events-calendar-lite'); ?></option>
                                        <option value="4" <?php if(isset($settings['midnight_hour']) and $settings['midnight_hour'] == '4') echo 'selected="selected"'; ?>><?php esc_html_e('4 AM', 'modern-events-calendar-lite'); ?></option>
                                        <option value="5" <?php if(isset($settings['midnight_hour']) and $settings['midnight_hour'] == '5') echo 'selected="selected"'; ?>><?php esc_html_e('5 AM', 'modern-events-calendar-lite'); ?></option>
                                    </select>
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Midnight Hour', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("12 AM is midnight by default but you can change it if your event ends after 12 AM and you don't want those events to be considered as multi-day events! This option does not affect the General view.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#6-_Midnight_Hour/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="mec-basvanced-advanced w-hidden">
                                <div class="mec-form-row">
                                    <label class="mec-col-3" for="mec_settings_event_as_popup"><?php esc_html_e('"Add Event" Wizard', 'modern-events-calendar-lite'); ?></label>
                                    <label id="mec_settings_event_as_popup">
                                        <input type="hidden" name="mec[settings][event_as_popup]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][event_as_popup]" <?php if(!isset($settings['event_as_popup']) or (isset($settings['event_as_popup']) and $settings['event_as_popup'])) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>

                                <div class="mec-form-row">
                                    <label class="mec-col-3" for="mec_settings_sh_as_popup"><?php esc_html_e('"Add Shortcode" Wizard', 'modern-events-calendar-lite'); ?></label>
                                    <label id="mec_settings_sh_as_popup">
                                        <input type="hidden" name="mec[settings][sh_as_popup]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][sh_as_popup]" <?php if(!isset($settings['sh_as_popup']) or (isset($settings['sh_as_popup']) and $settings['sh_as_popup'])) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>

                                <div class="mec-form-row">
                                    <label class="mec-col-3" for="mec_settings_include_image_in_feed"><?php esc_html_e('Include Event Featured Image in RSS Feed', 'modern-events-calendar-lite'); ?></label>
                                    <label id="mec_settings_sh_as_popup">
                                        <input type="hidden" name="mec[settings][include_image_in_feed]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][include_image_in_feed]" <?php if(!isset($settings['include_image_in_feed']) or (isset($settings['include_image_in_feed']) and $settings['include_image_in_feed'])) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>

                                <div class="mec-form-row">
                                    <label class="mec-col-3" for="mec_settings_fallback_featured_image_status"><?php esc_html_e('Fallback Featured Image', 'modern-events-calendar-lite'); ?></label>
                                    <label id="mec_settings_sh_as_popup">
                                        <input type="hidden" name="mec[settings][fallback_featured_image_status]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][fallback_featured_image_status]" <?php if(isset($settings['fallback_featured_image_status']) and $settings['fallback_featured_image_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>

                                <div class="mec-form-row">

                                    <label class="mec-col-3" for="mec_settings_tag_method"><?php esc_html_e('Tag Method', 'modern-events-calendar-lite'); ?></label>
                                    <div class="mec-col-9">
                                        <select id="mec_settings_tag_method" name="mec[settings][tag_method]">
                                            <option value="post_tag" <?php if(isset($settings['tag_method']) and $settings['tag_method'] == 'post_tag') echo 'selected="selected"'; ?>><?php esc_html_e('Post Tags', 'modern-events-calendar-lite'); ?></option>
                                            <option value="mec_tag" <?php if(isset($settings['tag_method']) and $settings['tag_method'] == 'mec_tag') echo 'selected="selected"'; ?>><?php esc_html_e('Independent Tags', 'modern-events-calendar-lite'); ?></option>
                                        </select>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Tag Method', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e("To share WP Post tags with MEC events, set this option on Post Tags.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-options/#7-_Tag_Method/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>

                                </div>

                                <div class="mec-form-row" style="padding-bottom: 3px;">
                                    <label class="mec-col-3" for="mec_settings_admin_calendar"><?php esc_html_e('Admin Calendar', 'modern-events-calendar-lite'); ?></label>
                                    <div class="mec-col-9">
                                        <label id="mec_settings_admin_calendar">
                                            <input type="hidden" name="mec[settings][admin_calendar]" value="0" />
                                            <input value="1" type="checkbox" name="mec[settings][admin_calendar]" <?php if(isset($settings['admin_calendar']) and $settings['admin_calendar']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <p style="margin-top: 0;"><?php esc_html_e('If enabled, a calendar view will be added with month navigation to the backend event manager.', 'modern-events-calendar-lite'); ?></p>
                                    </div>
                                </div>
                                <?php /*
                                <div class="mec-form-row" style="padding-bottom: 3px;">
                                    <label class="mec-col-3" for="mec_settings_admin_upcoming_events"><?php esc_html_e('Admin Upcoming Events', 'modern-events-calendar-lite'); ?></label>
                                    <div class="mec-col-9">
                                        <label id="mec_settings_admin_upcoming_events">
                                            <input type="hidden" name="mec[settings][admin_upcoming_events]" value="0" />
                                            <input value="1" type="checkbox" name="mec[settings][admin_upcoming_events]" <?php if(isset($settings['admin_upcoming_events']) and $settings['admin_upcoming_events']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <p style="margin-top: 0;"><?php esc_html_e('If enabled, an upcoming view will be added to the backend event manager.', 'modern-events-calendar-lite'); ?></p>
                                    </div>
                                </div> */ ?>
                                <div class="mec-form-row">
                                    <label class="mec-col-3" for="mec_settings_display_credit_url"><?php esc_html_e('Display powered by URL', 'modern-events-calendar-lite'); ?></label>
                                    <div class="mec-col-9">
                                        <label id="mec_settings_display_credit_url">
                                            <input type="hidden" name="mec[settings][display_credit_url]" value="0" />
                                            <input value="1" type="checkbox" name="mec[settings][display_credit_url]"
                                                <?php if( isset($settings['display_credit_url']) && $settings['display_credit_url'] ) echo 'checked="checked"'; ?> />
                                                <?php esc_html_e('Enable', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>

                                <h5 class="mec-form-subtitle"><?php echo esc_html__('iCal Feed', 'modern-events-calendar-lite'); ?></h5>
                                <div class="mec-form-row" style="padding-bottom: 3px;">
                                    <label class="mec-col-3" for="mec_settings_ical_feed"><?php esc_html_e('iCal Feed', 'modern-events-calendar-lite'); ?></label>
                                    <div class="mec-col-9">
                                        <label>
                                            <input type="hidden" name="mec[settings][ical_feed]" value="0" />
                                            <input onchange="jQuery('#mec_ical_feed_container_toggle').toggle();" value="1" type="checkbox" name="mec[settings][ical_feed]" id="mec_settings_ical_feed" <?php if(isset($settings['ical_feed']) and $settings['ical_feed']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <div id="mec_ical_feed_container_toggle" class="<?php if(!isset($settings['ical_feed']) or (isset($settings['ical_feed']) and !$settings['ical_feed'])) echo 'mec-util-hidden'; ?>">
                                    <div class="mec-form-row">
                                        <div class="mec-col-12">
                                            <p style="margin-top: 0;"><?php echo sprintf(esc_html__('Users are able to use %s URL to subscribe to your events.', 'modern-events-calendar-lite'), '<a href="'.trim($this->main->URL('site'), '/ ').'/?mec-ical-feed=1&nc='.time().'" target="_blank">'.trim($this->main->URL('site'), '/ ').'/?mec-ical-feed=1</a>'); ?></p>
                                        </div>
                                    </div>
                                    <div class="mec-form-row">
                                        <label class="mec-col-3" for="mec_settings_ical_feed_upcoming"><?php esc_html_e('Include Only Upcoming Events', 'modern-events-calendar-lite'); ?></label>
                                        <div class="mec-col-9">
                                            <label>
                                                <input type="hidden" name="mec[settings][ical_feed_upcoming]" value="0" />
                                                <input value="1" type="checkbox" name="mec[settings][ical_feed_upcoming]" id="mec_settings_ical_feed_upcoming" <?php if(isset($settings['ical_feed_upcoming']) and $settings['ical_feed_upcoming']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable', 'modern-events-calendar-lite'); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mec-form-row">
                                        <label class="mec-col-3" for="mec_settings_ical_feed_subscribe_to_calendar"><?php esc_html_e('Subscribe To Calendar', 'modern-events-calendar-lite'); ?></label>
                                        <div class="mec-col-9">
                                            <label>
                                                <input type="hidden" name="mec[settings][ical_feed_subscribe_to_calendar]" value="0" />
                                                <input value="1" type="checkbox" name="mec[settings][ical_feed_subscribe_to_calendar]" id="mec_settings_ical_feed_subscribe_to_calendar" <?php if(isset($settings['ical_feed_subscribe_to_calendar']) and $settings['ical_feed_subscribe_to_calendar']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable', 'modern-events-calendar-lite'); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mec-form-row">
                                        <label class="mec-col-12"><?php esc_html_e('Filtered Feeds', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-form-row">
                                        <div class="mec-col-12">
                                            <p>
                                            <?php echo sprintf(
                                                esc_html__('You can create an unlimited number of filtered feeds using a combination of filter parameters. You can add %s to the URL to filter events by location. You should insert the location IDs separated by commas. Additionally, to filter events by categories, you can add %s to the URL. Similarly, to filter events by organizers, you can add %s to the URL to filter events by multiple organizers. Combining two or more filter parameters will filter events by all selected options.', 'modern-events-calendar-lite'),
                                                '<code>&mec_locations=1,2,3</code>',
                                                '<code>&mec_categories=1,2,3</code>',
                                                '<code>&mec_organizers=1,2,3</code>'
                                            ); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <h5 class="mec-form-subtitle"><?php echo esc_html__('Maintenance', 'modern-events-calendar-lite'); ?></h5>
                                <div class="mec-form-row">
                                    <label class="mec-col-3" for="mec_settings_events_trash_interval"><?php esc_html_e('Move to trash events older than', 'modern-events-calendar-lite'); ?></label>
                                    <div class="mec-col-9">
                                        <select id="mec_settings_events_trash_interval" name="mec[settings][events_trash_interval]">
                                            <option value="0" <?php echo isset($settings['events_trash_interval']) && $settings['events_trash_interval'] == 0 ? 'selected' : ''; ?>><?php esc_html_e('Disabled', 'modern-events-calendar-lite'); ?></option>
                                            <option value="1" <?php echo isset($settings['events_trash_interval']) && $settings['events_trash_interval'] == 1 ? 'selected' : ''; ?>><?php esc_html_e('1 Day', 'modern-events-calendar-lite'); ?></option>
                                            <option value="2" <?php echo isset($settings['events_trash_interval']) && $settings['events_trash_interval'] == 2 ? 'selected' : ''; ?>><?php esc_html_e('2 Days', 'modern-events-calendar-lite'); ?></option>
                                            <option value="3" <?php echo isset($settings['events_trash_interval']) && $settings['events_trash_interval'] == 3 ? 'selected' : ''; ?>><?php esc_html_e('3 Days', 'modern-events-calendar-lite'); ?></option>
                                            <option value="7" <?php echo isset($settings['events_trash_interval']) && $settings['events_trash_interval'] == 7 ? 'selected' : ''; ?>><?php esc_html_e('7 Days', 'modern-events-calendar-lite'); ?></option>
                                            <option value="30" <?php echo isset($settings['events_trash_interval']) && $settings['events_trash_interval'] == 30 ? 'selected' : ''; ?>><?php esc_html_e('1 Month', 'modern-events-calendar-lite'); ?></option>
                                            <option value="60" <?php echo isset($settings['events_trash_interval']) && $settings['events_trash_interval'] == 60 ? 'selected' : ''; ?>><?php esc_html_e('2 Months', 'modern-events-calendar-lite'); ?></option>
                                            <option value="90" <?php echo isset($settings['events_trash_interval']) && $settings['events_trash_interval'] == 90 ? 'selected' : ''; ?>><?php esc_html_e('3 Months', 'modern-events-calendar-lite'); ?></option>
                                            <option value="180" <?php echo isset($settings['events_trash_interval']) && $settings['events_trash_interval'] == 180 ? 'selected' : ''; ?>><?php esc_html_e('6 Months', 'modern-events-calendar-lite'); ?></option>
                                            <option value="270" <?php echo isset($settings['events_trash_interval']) && $settings['events_trash_interval'] == 270 ? 'selected' : ''; ?>><?php esc_html_e('9 Months', 'modern-events-calendar-lite'); ?></option>
                                            <option value="360" <?php echo isset($settings['events_trash_interval']) && $settings['events_trash_interval'] == 360 ? 'selected' : ''; ?>><?php esc_html_e('1 Year', 'modern-events-calendar-lite'); ?></option>
                                            <option value="720" <?php echo isset($settings['events_trash_interval']) && $settings['events_trash_interval'] == 720 ? 'selected' : ''; ?>><?php esc_html_e('2 Years', 'modern-events-calendar-lite'); ?></option>
                                            <option value="1080" <?php echo isset($settings['events_trash_interval']) && $settings['events_trash_interval'] == 1080 ? 'selected' : ''; ?>><?php esc_html_e('3 Years', 'modern-events-calendar-lite'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <label class="mec-col-3" for="mec_settings_events_purge_interval"><?php esc_html_e('Permanently delete events older than', 'modern-events-calendar-lite'); ?></label>
                                    <div class="mec-col-9">
                                        <select id="mec_settings_events_purge_interval" name="mec[settings][events_purge_interval]">
                                            <option value="0" <?php echo isset($settings['events_purge_interval']) && $settings['events_purge_interval'] == 0 ? 'selected' : ''; ?>><?php esc_html_e('Disabled', 'modern-events-calendar-lite'); ?></option>
                                            <option value="1" <?php echo isset($settings['events_purge_interval']) && $settings['events_purge_interval'] == 1 ? 'selected' : ''; ?>><?php esc_html_e('1 Day', 'modern-events-calendar-lite'); ?></option>
                                            <option value="2" <?php echo isset($settings['events_purge_interval']) && $settings['events_purge_interval'] == 2 ? 'selected' : ''; ?>><?php esc_html_e('2 Days', 'modern-events-calendar-lite'); ?></option>
                                            <option value="3" <?php echo isset($settings['events_purge_interval']) && $settings['events_purge_interval'] == 3 ? 'selected' : ''; ?>><?php esc_html_e('3 Days', 'modern-events-calendar-lite'); ?></option>
                                            <option value="7" <?php echo isset($settings['events_purge_interval']) && $settings['events_purge_interval'] == 7 ? 'selected' : ''; ?>><?php esc_html_e('7 Days', 'modern-events-calendar-lite'); ?></option>
                                            <option value="30" <?php echo isset($settings['events_purge_interval']) && $settings['events_purge_interval'] == 30 ? 'selected' : ''; ?>><?php esc_html_e('1 Month', 'modern-events-calendar-lite'); ?></option>
                                            <option value="60" <?php echo isset($settings['events_purge_interval']) && $settings['events_purge_interval'] == 60 ? 'selected' : ''; ?>><?php esc_html_e('2 Months', 'modern-events-calendar-lite'); ?></option>
                                            <option value="90" <?php echo isset($settings['events_purge_interval']) && $settings['events_purge_interval'] == 90 ? 'selected' : ''; ?>><?php esc_html_e('3 Months', 'modern-events-calendar-lite'); ?></option>
                                            <option value="180" <?php echo isset($settings['events_purge_interval']) && $settings['events_purge_interval'] == 180 ? 'selected' : ''; ?>><?php esc_html_e('6 Months', 'modern-events-calendar-lite'); ?></option>
                                            <option value="270" <?php echo isset($settings['events_purge_interval']) && $settings['events_purge_interval'] == 270 ? 'selected' : ''; ?>><?php esc_html_e('9 Months', 'modern-events-calendar-lite'); ?></option>
                                            <option value="360" <?php echo isset($settings['events_purge_interval']) && $settings['events_purge_interval'] == 360 ? 'selected' : ''; ?>><?php esc_html_e('1 Year', 'modern-events-calendar-lite'); ?></option>
                                            <option value="720" <?php echo isset($settings['events_purge_interval']) && $settings['events_purge_interval'] == 720 ? 'selected' : ''; ?>><?php esc_html_e('2 Years', 'modern-events-calendar-lite'); ?></option>
                                            <option value="1080" <?php echo isset($settings['events_purge_interval']) && $settings['events_purge_interval'] == 1080 ? 'selected' : ''; ?>><?php esc_html_e('3 Years', 'modern-events-calendar-lite'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <h5 class="mec-form-subtitle"><?php esc_html_e('Assets (CSS and JavaScript files)', 'modern-events-calendar-lite'); ?></h5>

                                    <div class="mec-form-row">
                                        <label>
                                            <input type="hidden" name="mec[settings][assets_disable_stripe_js]" value="0" />
                                            <input value="1" type="checkbox" name="mec[settings][assets_disable_stripe_js]" <?php if(isset($settings['assets_disable_stripe_js']) and $settings['assets_disable_stripe_js']) echo 'checked="checked"'; ?> /><?php esc_html_e('Disable Load Stripe JS', 'modern-events-calendar-lite'); ?>
                                        </label>
                                        <span class="mec-tooltip">
                                            <div class="box right">
                                                <h5 class="title"><?php esc_html_e('Disable Load Stripe JS', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e("You can prevent the loading of the JS file related to Stripe.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#12-_Assets_CSS_and_JavaScript_files/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>

                                    <div class="mec-form-row">
                                        <label>
                                            <input type="hidden" name="mec[settings][assets_per_page_status]" value="0" />
                                            <input onchange="jQuery('#mec_assets_per_page_container_toggle').toggle();" value="1" type="checkbox" name="mec[settings][assets_per_page_status]" <?php if(isset($settings['assets_per_page_status']) and $settings['assets_per_page_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable Assets Per Page', 'modern-events-calendar-lite'); ?>
                                        </label>
                                        <span class="mec-tooltip">
                                                <div class="box right">
                                                    <h5 class="title"><?php esc_html_e('Assets Per Page', 'modern-events-calendar-lite'); ?></h5>
                                                    <div class="content"><p><?php esc_attr_e("By activating this option, you can prevent MEC assets from being loaded on all pages of your site. Instead, an option on each page will allow you to MEV assets on that specific page.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#How_to_Use_Assets_Per_Page_Option/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                                </div>
                                                <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                    <div id="mec_assets_per_page_container_toggle" class="<?php if((isset($settings['assets_per_page_status']) and !$settings['assets_per_page_status']) or !isset($settings['assets_per_page_status'])) echo 'mec-util-hidden'; ?>">
                                        <p class="notice-red" style="color: #b94a48; text-shadow: unset;"><?php echo esc_html__("By enabling this option MEC won't include any JavaScript or CSS files in frontend of your website unless you enable the assets inclusion in page options.", 'modern-events-calendar-lite'); ?></p>
                                    </div>

                                    <div class="mec-form-row">
                                        <label>
                                            <input type="hidden" name="mec[settings][assets_in_footer_status]" value="0" />
                                            <input value="1" type="checkbox" name="mec[settings][assets_in_footer_status]" <?php if(isset($settings['assets_in_footer_status']) and $settings['assets_in_footer_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Load Assets in Footer', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <h5 class="mec-form-subtitle"><?php esc_html_e('User Profile', 'modern-events-calendar-lite'); ?></h5>
                                    <div class="mec-form-row">
                                        <p><?php echo sprintf(esc_html__('Put %s shortcode into your desired page. Then users are able to see the history of their bookings.', 'modern-events-calendar-lite'), '<code>[MEC_profile]</code>'); ?></p>
                                        <p><?php echo sprintf(esc_html__('Use %s attribute to hide canceled bookings. Like %s', 'modern-events-calendar-lite'), '<code>hide-canceleds="1"</code>', '<code>[MEC_profile hide-canceleds="1"]</code>'); ?></p>
                                        <p><?php echo sprintf(esc_html__('Use %s attribute to show upcoming bookings. Like %s', 'modern-events-calendar-lite'), '<code>show-upcomings="1"</code>', '<code>[MEC_profile show-upcomings="1"]</code>'); ?></p>
                                    </div>
                                </div>

                                <div>
                                    <h5 class="mec-form-subtitle"><?php esc_html_e('User Events', 'modern-events-calendar-lite'); ?></h5>
                                    <div class="mec-form-row">
                                        <p><?php echo sprintf(esc_html__('Put %s shortcode into your desired page. Then users are able to see the their own events.', 'modern-events-calendar-lite'), '<code>[MEC_userevents]</code>'); ?></p>
                                    </div>
                                    <div class="mec-form-row">
                                        <select name="mec[settings][userevents_shortcode]" id="mec_settings_userevents_shortcode">
                                            <?php foreach($shortcodes as $shortcode): $skin = get_post_meta($shortcode->ID, 'skin', true); if(!in_array($skin, array('monthly_view', 'daily_view', 'weekly_view', 'list', 'grid', 'agenda'))) continue; ?>
                                            <option value="<?php echo esc_attr($shortcode->ID); ?>" <?php echo ((isset($settings['userevents_shortcode']) and $settings['userevents_shortcode'] == $shortcode->ID) ? 'selected="selected"' : ''); ?>><?php echo esc_html($shortcode->post_title); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="mec-tooltip">
                                            <div class="box right">
                                                <h5 class="title"><?php esc_html_e('User Events Skin', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e("In which skin should user events be displayed?", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#14-_User_Events/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mec-form-row mec-basvanced-advanced w-hidden">
                                <h5 class="mec-form-subtitle"><?php esc_html_e('Not Found Message', 'modern-events-calendar-lite'); ?></h5>
                                <div class="mec-col-12">
                                    <textarea class="widefat" style="max-width: unset; margin-bottom: 10px;" name="mec[settings][not_found_message]" placeholder="<?php esc_attr_e('No event found!'); ?>"><?php echo isset($settings['not_found_message']) ? esc_textarea(stripslashes($settings['not_found_message'])) : ''; ?></textarea>
                                    <p><?php esc_html_e('Feel free to use HTML codes.', 'modern-events-calendar-lite'); ?></p>
                                </div>
                            </div>

                            <div class="mec-form-row mec-basvanced-advanced w-hidden">
                                <h5 class="mec-form-subtitle"><?php esc_html_e('Data Removal', 'modern-events-calendar-lite'); ?></h5>
                                <label class="mec-col-3" for="mec_settings_remove_data_on_uninstall"><?php esc_html_e('Remove MEC Data on Plugin Uninstall', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select id="mec_settings_remove_data_on_uninstall" name="mec[settings][remove_data_on_uninstall]">
                                        <option value="0" <?php if(isset($settings['remove_data_on_uninstall']) and !$settings['remove_data_on_uninstall']) echo 'selected="selected"'; ?>><?php esc_html_e('Disabled', 'modern-events-calendar-lite'); ?></option>
                                        <option value="1" <?php if(isset($settings['remove_data_on_uninstall']) and $settings['remove_data_on_uninstall'] == '1') echo 'selected="selected"'; ?>><?php esc_html_e('Enabled', 'modern-events-calendar-lite'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="mec-form-row mec-basvanced-advanced w-hidden">
                                <h5 class="mec-form-subtitle"><?php esc_html_e('Database Setup', 'modern-events-calendar-lite'); ?></h5>
                                <label class="mec-col-3" for="mec_settings_database_setup"><?php esc_html_e('Re-run Install', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <button id="database_setup_button" class="button" type="button"><?php esc_html_e('Install again', 'modern-events-calendar-lite'); ?></button>
                                    <p style="margin-top: 5px;"><?php esc_html_e("If for any reason, your installation of MEC is missing the database tables, this will re-execute the install process.", 'modern-events-calendar-lite'); ?></p>
                                    <div id="database_setup_message"></div>
                                </div>
                            </div>

                        </div>

                        <div id="archive_options" class="mec-options-fields">
                            <h4 class="mec-form-subtitle"><?php esc_html_e('Archive Pages', 'modern-events-calendar-lite'); ?></h4>

                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_archive_title"><?php esc_html_e('Archive Page Title', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <input type="text" id="mec_settings_archive_title" name="mec[settings][archive_title]" value="<?php echo ((isset($settings['archive_title']) and trim($settings['archive_title']) != '') ? esc_attr($settings['archive_title']) : 'Events'); ?>" />
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Archive Page Title', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("Write a SEO title for the event archive page. This will be displayed on the browser tab.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#1-_Archive_Page_Title/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_archive_title_tag"><?php esc_html_e('Tag of Archive Page Title', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select id="mec_settings_archive_title_tag" name="mec[settings][archive_title_tag]">
                                        <option value="h1" <?php if(isset($settings['archive_title_tag']) and 'h1' == $settings['archive_title_tag']) echo 'selected="selected"'; ?>><?php esc_html_e('Heading 1'); ?></option>
                                        <option value="h2" <?php if(isset($settings['archive_title_tag']) and 'h2' == $settings['archive_title_tag']) echo 'selected="selected"'; ?>><?php esc_html_e('Heading 2'); ?></option>
                                        <option value="h3" <?php if(isset($settings['archive_title_tag']) and 'h3' == $settings['archive_title_tag']) echo 'selected="selected"'; ?>><?php esc_html_e('Heading 3'); ?></option>
                                        <option value="h4" <?php if(isset($settings['archive_title_tag']) and 'h4' == $settings['archive_title_tag']) echo 'selected="selected"'; ?>><?php esc_html_e('Heading 4'); ?></option>
                                        <option value="h5" <?php if(isset($settings['archive_title_tag']) and 'h5' == $settings['archive_title_tag']) echo 'selected="selected"'; ?>><?php esc_html_e('Heading 5'); ?></option>
                                        <option value="h6" <?php if(isset($settings['archive_title_tag']) and 'h6' == $settings['archive_title_tag']) echo 'selected="selected"'; ?>><?php esc_html_e('Heading 6'); ?></option>
                                        <option value="div" <?php if(isset($settings['archive_title_tag']) and 'div' == $settings['archive_title_tag']) echo 'selected="selected"'; ?>><?php esc_html_e('Division'); ?></option>
                                        <option value="p" <?php if(isset($settings['archive_title_tag']) and 'p' == $settings['archive_title_tag']) echo 'selected="selected"'; ?>><?php esc_html_e('Paragraph'); ?></option>
                                        <option value="strong" <?php if(isset($settings['archive_title_tag']) and 'strong' == $settings['archive_title_tag']) echo 'selected="selected"'; ?>><?php esc_html_e('Inline Bold Text'); ?></option>
                                        <option value="span" <?php if(isset($settings['archive_title_tag']) and 'span' == $settings['archive_title_tag']) echo 'selected="selected"'; ?>><?php esc_html_e('Inline Text'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_archive_sidebar"><?php esc_html_e('MEC Archive Sidebar', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select id="mec_settings_archive_sidebar" name="mec[settings][archive_sidebar]">
                                        <option value="0" <?php if(isset($settings['archive_sidebar']) and '0' == $settings['archive_sidebar']) echo 'selected="selected"'; ?>><?php esc_html_e('Hide'); ?></option>
                                        <option value="1" <?php if(isset($settings['archive_sidebar']) and '1' == $settings['archive_sidebar']) echo 'selected="selected"'; ?>><?php esc_html_e('Display'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_default_skin_archive"><?php esc_html_e('Archive Page Skin', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9 tooltip-move-up">
                                    <select id="mec_settings_default_skin_archive" name="mec[settings][default_skin_archive]" onchange="mec_archive_skin_style_changed(this.value);">
                                        <?php foreach($archive_skins as $archive_skin): ?>
                                            <option value="<?php echo esc_attr($archive_skin['skin']); ?>" <?php if(isset($settings['default_skin_archive']) and $archive_skin['skin'] == $settings['default_skin_archive']) echo 'selected="selected"'; ?>><?php echo esc_html($archive_skin['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Archive Page Skin', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("Select the skin for the main Events archive and event tag archive pages.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#3-_Archive_Page_Skin/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a><a href="https://webnus.net/modern-events-calendar/" target="_blank"><?php esc_html_e('See Demo', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                    <span class="mec-archive-skins mec-archive-custom-skins">
                                        <input type="text" placeholder="<?php esc_html_e('Put shortcode...', 'modern-events-calendar-lite'); ?>" id="mec_settings_custom_archive" name="mec[settings][custom_archive]" value='<?php echo ((isset($settings['custom_archive']) and trim($settings['custom_archive']) != '') ? $settings['custom_archive'] : ''); ?>' />
                                    </span>
                                    <span class="mec-archive-skins mec-archive-full_calendar-skins">
                                        <select id="mec_settings_full_calendar_skin_archive" name="mec[settings][full_calendar_archive_skin]">
                                            <option value="classic" <?php if (isset($settings['full_calendar_archive_skin']) and $settings['full_calendar_archive_skin'] == 'classic') {
                                                echo 'selected="selected"';
                                            } ?>><?php _e('Classic', 'modern-events-calendar-lite'); ?></option>
                                            <?php do_action('mec_full_calendar_skin_style_options', (isset($settings['full_calendar_archive_skin']) ? $settings['full_calendar_archive_skin'] : NULL)); ?>
                                        </select>
                                    </span>
                                    <span class="mec-archive-skins mec-archive-yearly_view-skins">
                                        <select id="mec_settings_yearly_skin_archive" name="mec[settings][yearly_view_archive_skin]">
                                            <option value="modern" <?php if(isset($settings['yearly_view_archive_skin']) and $settings['yearly_view_archive_skin'] == 'modern') echo 'selected="selected"'; ?>><?php esc_html_e('Modern', 'modern-events-calendar-lite'); ?></option>
                                            <?php do_action('mec_yearly_skin_style_options', (isset($settings['yearly_view_archive_skin']) ? $settings['yearly_view_archive_skin'] : NULL)); ?>
                                        </select>
                                    </span>
                                    <span class="mec-archive-skins mec-archive-monthly_view-skins">
                                        <select id="mec_settings_monthly_view_skin_archive" name="mec[settings][monthly_view_archive_skin]">
                                            <option value="classic" <?php if(isset($settings['monthly_view_archive_skin']) &&  $settings['monthly_view_archive_skin'] == 'classic') echo 'selected="selected"'; ?>><?php echo esc_html__('Classic' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="clean" <?php if(isset($settings['monthly_view_archive_skin']) &&  $settings['monthly_view_archive_skin'] == 'clean') echo 'selected="selected"'; ?>><?php echo esc_html__('Clean' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="modern" <?php if(isset($settings['monthly_view_archive_skin']) &&  $settings['monthly_view_archive_skin'] == 'modern') echo 'selected="selected"'; ?>><?php echo esc_html__('Modern' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="novel" <?php if(isset($settings['monthly_view_archive_skin']) &&  $settings['monthly_view_archive_skin'] == 'novel') echo 'selected="selected"'; ?>><?php echo esc_html__('Novel' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="simple" <?php if(isset($settings['monthly_view_archive_skin']) &&  $settings['monthly_view_archive_skin'] == 'simple') echo 'selected="selected"'; ?>><?php echo esc_html__('Simple' , 'modern-events-calendar-lite'); ?></option>
                                        </select>
                                    </span>
                                    <span class="mec-archive-skins mec-archive-weekly_view-skins">
                                        <select id="mec_settings_weekly_view_skin_archive" name="mec[settings][weekly_view_archive_skin]">
                                            <option value="classic" <?php if(isset($settings['weekly_view_archive_skin']) and $settings['weekly_view_archive_skin'] == 'classic') echo 'selected="selected"'; ?>><?php esc_html_e('Classic', 'modern-events-calendar-lite'); ?></option>
                                            <?php do_action('mec_weekly_view_skin_style_options', (isset($settings['weekly_view_archive_skin']) ? $settings['weekly_view_archive_skin'] : NULL)); ?>
                                        </select>
                                    </span>
                                    <span class="mec-archive-skins mec-archive-daily_view-skins">
                                        <select id="mec_skin_daily_view_archive_skin_archive" name="mec[settings][daily_view_archive_skin]">
                                            <option value="classic" <?php if (isset($settings['daily_view_archive_skin']) and $settings['daily_view_archive_skin'] == 'classic') {
                                                echo 'selected="selected"';
                                            } ?>><?php _e('Classic', 'modern-events-calendar-lite'); ?></option>
                                            <?php do_action('mec_daily_view_skin_style_options', (isset($settings['daily_view_archive_skin']) ? $settings['daily_view_archive_skin'] : NULL)); ?>
                                        </select>
                                    </span>
                                    <span class="mec-archive-skins mec-archive-timetable-skins">
                                        <select id="mec_settings_timetable_skin_archive" name="mec[settings][timetable_archive_skin]">
                                            <option value="modern" <?php if(isset($settings['timetable_archive_skin']) &&  $settings['timetable_archive_skin'] == 'modern') echo 'selected="selected"'; ?>><?php echo esc_html__('Modern' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="clean" <?php if(isset($settings['timetable_archive_skin']) &&  $settings['timetable_archive_skin'] == 'clean') echo 'selected="selected"'; ?>><?php echo esc_html__('Clean' , 'modern-events-calendar-lite'); ?></option>
                                        </select>
                                    </span>
                                    <span class="mec-archive-skins mec-archive-masonry-skins">
                                        <input type="text" placeholder="<?php esc_html_e('There is no skins', 'modern-events-calendar-lite'); ?>" disabled />
                                    </span>
                                    <span class="mec-archive-skins mec-archive-list-skins">
                                        <select id="mec_settings_list_skin_archive" name="mec[settings][list_archive_skin]">
                                            <option value="classic" <?php if(isset($settings['list_archive_skin']) &&  $settings['list_archive_skin'] == 'classic') echo 'selected="selected"'; ?>><?php echo esc_html__('Classic' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="minimal" <?php if(isset($settings['list_archive_skin']) &&  $settings['list_archive_skin'] == 'minimal') echo 'selected="selected"'; ?>><?php echo esc_html__('Minimal' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="modern" <?php if(isset($settings['list_archive_skin']) &&  $settings['list_archive_skin'] == 'modern') echo 'selected="selected"'; ?>><?php echo esc_html__('Modern' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="standard" <?php if(isset($settings['list_archive_skin']) &&  $settings['list_archive_skin'] == 'standard') echo 'selected="selected"'; ?>><?php echo esc_html__('Standard' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="accordion" <?php if(isset($settings['list_archive_skin']) &&  $settings['list_archive_skin'] == 'accordion') echo 'selected="selected"'; ?>><?php echo esc_html__('Toggle' , 'modern-events-calendar-lite'); ?></option>
                                            <?php do_action( 'mec_list_skin_style_options', (isset( $settings['list_archive_skin'] ) ? $settings['list_archive_skin'] : NULL ) ); ?>
                                        </select>
                                    </span>
                                    <span class="mec-archive-skins mec-archive-grid-skins">
                                        <select id="mec_settings_grid_skin_archive" name="mec[settings][grid_archive_skin]">
                                            <option value="classic" <?php if(isset($settings['grid_archive_skin']) &&  $settings['grid_archive_skin'] == 'classic') echo 'selected="selected"'; ?>><?php echo esc_html__('Classic' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="clean" <?php if(isset($settings['grid_archive_skin'])  &&  $settings['grid_archive_skin'] == 'clean') echo 'selected="selected"'; ?>><?php echo esc_html__('Clean' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="minimal" <?php if(isset($settings['grid_archive_skin'])  &&  $settings['grid_archive_skin'] == 'minimal') echo 'selected="selected"'; ?>><?php echo esc_html__('Minimal' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="modern" <?php if(isset($settings['grid_archive_skin'])  &&  $settings['grid_archive_skin'] == 'modern') echo 'selected="selected"'; ?>><?php echo esc_html__('Modern' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="simple" <?php if(isset($settings['grid_archive_skin'])  &&  $settings['grid_archive_skin'] == 'simple') echo 'selected="selected"'; ?>><?php echo esc_html__('Simple' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="colorful" <?php if(isset($settings['grid_archive_skin'])  &&  $settings['grid_archive_skin'] == 'colorful') echo 'selected="selected"'; ?>><?php echo esc_html__('colorful' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="novel" <?php if(isset($settings['grid_archive_skin'])  &&  $settings['grid_archive_skin'] == 'novel') echo 'selected="selected"'; ?>><?php echo esc_html__('Novel' , 'modern-events-calendar-lite'); ?></option>
                                            <?php do_action( 'mec_grid_skin_style_options', (isset( $settings['grid_archive_skin'] ) ? $settings['grid_archive_skin'] : NULL ) ); ?>
                                        </select>
                                    </span>
                                    <span class="mec-archive-skins mec-archive-agenda-skins">
                                        <input type="text" placeholder="<?php esc_html_e('Clean Style', 'modern-events-calendar-lite'); ?>" disabled />
                                    </span>
                                    <span class="mec-archive-skins mec-archive-map-skins">
                                        <select id="mec_settings_map_skin_archive" name="mec[settings][map_archive_skin]">
                                            <option value="classic" <?php if(isset($settings['map_archive_skin']) and $settings['map_archive_skin'] == 'classic') echo 'selected="selected"'; ?>><?php esc_html_e('Classic', 'modern-events-calendar-lite'); ?></option>
                                            <?php do_action('mec_map_skin_style_options', (isset($settings['map_archive_skin']) ? $settings['map_archive_skin'] : NULL)); ?>
                                        </select>
                                    </span>

                                </div>
                            </div>

                            <div class="mec-form-row mec-archive-events-method-row <?php echo (isset($settings['default_skin_archive']) && $settings['default_skin_archive'] === 'custom') ? 'mec-util-hidden' : ''; ?>">
                                <label class="mec-col-3" for="mec_settings_archive_events_method"><?php esc_html_e('Archive Events Method', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select id="mec_settings_archive_events_method" name="mec[settings][archive_events_method]">
                                        <option value="3" <?php if(!isset($settings['archive_events_method']) || (isset($settings['archive_events_method']) && (string)$settings['archive_events_method'] === '3')) echo 'selected="selected"'; ?>><?php esc_html_e('All Events', 'modern-events-calendar-lite'); ?></option>
                                        <option value="1" <?php if(isset($settings['archive_events_method']) && (string)$settings['archive_events_method'] === '1') echo 'selected="selected"'; ?>><?php esc_html_e('Upcoming Events', 'modern-events-calendar-lite'); ?></option>
                                        <option value="2" <?php if(isset($settings['archive_events_method']) && (string)$settings['archive_events_method'] === '2') echo 'selected="selected"'; ?>><?php esc_html_e('Expired Events', 'modern-events-calendar-lite'); ?></option>
                                    </select>
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Archive Events Method', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e('Which events should appear on the archive page?', 'modern-events-calendar-lite'); ?></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_default_skin_category"><?php esc_html_e('Category Page Skin', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9 tooltip-move-up">
                                    <select id="mec_settings_default_skin_category" name="mec[settings][default_skin_category]" onchange="mec_category_skin_style_changed(this.value);">
                                        <?php foreach($category_skins as $category_skin): ?>
                                            <option value="<?php echo esc_attr($category_skin['skin']); ?>" <?php if(isset($settings['default_skin_category']) and $category_skin['skin'] == $settings['default_skin_category']) echo 'selected="selected"'; if(!isset($settings['default_skin_category']) and $category_skin['skin'] == 'list') echo 'selected="selected"'; ?>><?php echo esc_html($category_skin['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Category Page Skin', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("The event category page skin can be modified here.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#4-_Category_Page_Skin/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a><a href="https://webnus.net/modern-events-calendar/" target="_blank"><?php esc_html_e('See Demo', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                    <span class="mec-category-skins mec-category-custom-skins">
                                        <input type="text" placeholder="<?php esc_html_e('Put shortcode...', 'modern-events-calendar-lite'); ?>" id="mec_settings_custom_archive_category" name="mec[settings][custom_archive_category]" value='<?php echo ((isset($settings['custom_archive_category']) and trim($settings['custom_archive_category']) != '') ? stripslashes($settings['custom_archive_category']) : ''); ?>' />
                                    </span>
                                    <span class="mec-category-skins mec-category-full_calendar-skins">
                                        <select id="mec_settings_full_calendar_skin_category" name="mec[settings][full_calendar_category_skin]">
                                            <option value="classic" <?php if (isset($settings['full_calendar_category_skin']) and $settings['full_calendar_category_skin'] == 'classic') {
                                                echo 'selected="selected"';
                                            } ?>><?php _e('Classic', 'modern-events-calendar-lite'); ?></option>
                                            <?php do_action('mec_full_calendar_skin_style_options', (isset($settings['full_calendar_category_skin']) ? $settings['full_calendar_category_skin'] : NULL)); ?>
                                        </select>
                                    </span>
                                    <span class="mec-category-skins mec-category-yearly_view-skins">
                                        <select id="mec_settings_yearly_skin_category" name="mec[settings][yearly_view_category_skin]">
                                            <option value="modern" <?php if(isset($settings['yearly_view_category_skin']) and $settings['yearly_view_category_skin'] == 'modern') echo 'selected="selected"'; ?>><?php esc_html_e('Modern', 'modern-events-calendar-lite'); ?></option>
                                            <?php do_action('mec_yearly_skin_style_options', (isset($settings['yearly_view_category_skin']) ? $settings['yearly_view_category_skin'] : NULL)); ?>
                                        </select>
                                    </span>
                                    <span class="mec-category-skins mec-category-monthly_view-skins">
                                        <select id="mec_settings_monthly_view_skin_category" name="mec[settings][monthly_view_category_skin]">
                                            <option value="classic" <?php if(isset($settings['monthly_view_category_skin']) &&  $settings['monthly_view_category_skin'] == 'classic') echo 'selected="selected"'; ?>><?php echo esc_html__('Classic' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="clean" <?php if(isset($settings['monthly_view_category_skin']) &&  $settings['monthly_view_category_skin'] == 'clean') echo 'selected="selected"'; ?>><?php echo esc_html__('Clean' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="modern" <?php if(isset($settings['monthly_view_category_skin']) &&  $settings['monthly_view_category_skin'] == 'modern') echo 'selected="selected"'; ?>><?php echo esc_html__('Modern' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="novel" <?php if(isset($settings['monthly_view_category_skin']) &&  $settings['monthly_view_category_skin'] == 'novel') echo 'selected="selected"'; ?>><?php echo esc_html__('Novel' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="simple" <?php if(isset($settings['monthly_view_category_skin']) &&  $settings['monthly_view_category_skin'] == 'simple') echo 'selected="selected"'; ?>><?php echo esc_html__('Simple' , 'modern-events-calendar-lite'); ?></option>
                                        </select>
                                    </span>
                                    <span class="mec-category-skins mec-category-weekly_view-skins">
                                        <select id="mec_settings_weekly_view_skin_category" name="mec[settings][weekly_view_category_skin]">
                                            <option value="classic" <?php if(isset($settings['weekly_view_category_skin']) and $settings['weekly_view_category_skin'] == 'classic') echo 'selected="selected"'; ?>><?php esc_html_e('Classic', 'modern-events-calendar-lite'); ?></option>
                                            <?php do_action('mec_weekly_view_skin_style_options', (isset($settings['weekly_view_category_skin']) ? $settings['weekly_view_category_skin'] : NULL)); ?>
                                        </select>
                                    </span>
                                    <span class="mec-category-skins mec-category-daily_view-skins">
                                        <select id="mec_skin_daily_view_skin_category" name="mec[settings][daily_view_category_skin]">
                                            <option value="classic" <?php if (isset($settings['daily_view_category_skin']) and $settings['daily_view_category_skin'] == 'classic') {
                                                echo 'selected="selected"';
                                            } ?>><?php _e('Classic', 'modern-events-calendar-lite'); ?></option>
                                            <?php do_action('mec_daily_view_skin_style_options', (isset($settings['daily_view_category_skin']) ? $settings['daily_view_category_skin'] : NULL)); ?>
                                        </select>
                                    </span>
                                    <span class="mec-category-skins mec-category-timetable-skins">
                                        <select id="mec_settings_timetable_skin_category" name="mec[settings][timetable_category_skin]">
                                            <option value="modern" <?php if(isset($settings['timetable_category_skin']) &&  $settings['timetable_category_skin'] == 'modern') echo 'selected="selected"'; ?>><?php echo esc_html__('Modern' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="clean" <?php if(isset($settings['timetable_category_skin']) &&  $settings['timetable_category_skin'] == 'clean') echo 'selected="selected"'; ?>><?php echo esc_html__('Clean' , 'modern-events-calendar-lite'); ?></option>
                                        </select>
                                    </span>
                                    <span class="mec-category-skins mec-category-masonry-skins">
                                        <input type="text" placeholder="<?php esc_html_e('There is no skins', 'modern-events-calendar-lite'); ?>" disabled />
                                    </span>
                                    <span class="mec-category-skins mec-category-list-skins">
                                        <select id="mec_settings_list_skin_category" name="mec[settings][list_category_skin]">
                                            <option value="classic" <?php if(isset($settings['list_category_skin']) &&  $settings['list_category_skin'] == 'classic') echo 'selected="selected"'; ?>><?php echo esc_html__('Classic' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="minimal" <?php if(isset($settings['list_category_skin']) &&  $settings['list_category_skin'] == 'minimal') echo 'selected="selected"'; ?>><?php echo esc_html__('Minimal' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="modern" <?php if(isset($settings['list_category_skin']) &&  $settings['list_category_skin'] == 'modern') echo 'selected="selected"'; ?>><?php echo esc_html__('Modern' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="standard" <?php if(isset($settings['list_category_skin']) &&  $settings['list_category_skin'] == 'standard') echo 'selected="selected"'; ?>><?php echo esc_html__('Standard' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="accordion" <?php if(isset($settings['list_category_skin']) &&  $settings['list_category_skin'] == 'accordion') echo 'selected="selected"'; ?>><?php echo esc_html__('Toggle' , 'modern-events-calendar-lite'); ?></option>
                                            <?php do_action( 'mec_list_skin_style_options', (isset( $settings['list_category_skin'] ) ? $settings['list_category_skin'] : NULL ) ); ?>
                                        </select>
                                    </span>
                                    <span class="mec-category-skins mec-category-grid-skins">
                                        <select id="mec_settings_grid_skin_category" name="mec[settings][grid_category_skin]">
                                            <option value="classic" <?php if(isset($settings['grid_category_skin']) &&  $settings['grid_category_skin'] == 'classic') echo 'selected="selected"'; ?>><?php echo esc_html__('Classic' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="clean" <?php if(isset($settings['grid_category_skin'])  &&  $settings['grid_category_skin'] == 'clean') echo 'selected="selected"'; ?>><?php echo esc_html__('Clean' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="minimal" <?php if(isset($settings['grid_category_skin'])  &&  $settings['grid_category_skin'] == 'minimal') echo 'selected="selected"'; ?>><?php echo esc_html__('Minimal' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="modern" <?php if(isset($settings['grid_category_skin'])  &&  $settings['grid_category_skin'] == 'modern') echo 'selected="selected"'; ?>><?php echo esc_html__('Modern' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="simple" <?php if(isset($settings['grid_category_skin'])  &&  $settings['grid_category_skin'] == 'simple') echo 'selected="selected"'; ?>><?php echo esc_html__('Simple' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="colorful" <?php if(isset($settings['grid_category_skin'])  &&  $settings['grid_category_skin'] == 'colorful') echo 'selected="selected"'; ?>><?php echo esc_html__('colorful' , 'modern-events-calendar-lite'); ?></option>
                                            <option value="novel" <?php if(isset($settings['grid_category_skin'])  &&  $settings['grid_category_skin'] == 'novel') echo 'selected="selected"'; ?>><?php echo esc_html__('Novel' , 'modern-events-calendar-lite'); ?></option>
                                            <?php do_action( 'mec_grid_skin_style_options', (isset( $settings['grid_category_skin'] ) ? $settings['grid_category_skin'] : NULL ) ); ?>
                                        </select>
                                    </span>
                                    <span class="mec-category-skins mec-category-agenda-skins">
                                        <input type="text" placeholder="<?php esc_html_e('Clean Style', 'modern-events-calendar-lite'); ?>" disabled />
                                    </span>
                                    <span class="mec-category-skins mec-category-map-skins">
                                        <select id="mec_settings_map_skin_archive" name="mec[settings][map_archive_skin]">
                                            <option value="classic" <?php if(isset($settings['map_archive_skin']) and $settings['map_archive_skin'] == 'classic') echo 'selected="selected"'; ?>><?php esc_html_e('Classic', 'modern-events-calendar-lite'); ?></option>
                                            <?php do_action('mec_map_skin_style_options', ($settings['map_archive_skin'] ?? NULL)); ?>
                                        </select>
                                    </span>
                                </div>
                            </div>

                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_category_events_method"><?php esc_html_e('Category Events Method', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select id="mec_settings_category_events_method" name="mec[settings][category_events_method]">
                                        <option value="1" <?php if(!isset($settings['category_events_method']) or (isset($settings['category_events_method']) and $settings['category_events_method'] == 1)) echo 'selected="selected"'; ?>><?php esc_html_e('Upcoming Events', 'modern-events-calendar-lite'); ?></option>
                                        <option value="2" <?php if(isset($settings['category_events_method']) and $settings['category_events_method'] == 2) echo 'selected="selected"'; ?>><?php esc_html_e('Expired Events', 'modern-events-calendar-lite'); ?></option>
                                    </select>
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Category Events Method', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("Which events should appear on the category page?", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#5-_Category_Events_Method/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_archive_status"><?php esc_html_e('Events Archive Status', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select id="mec_settings_archive_status" name="mec[settings][archive_status]">
                                        <option value="1" <?php if(isset($settings['archive_status']) and $settings['archive_status'] == '1') echo 'selected="selected"'; ?>><?php esc_html_e('Enabled (Recommended)', 'modern-events-calendar-lite'); ?></option>
                                        <option value="0" <?php if(isset($settings['archive_status']) and !$settings['archive_status']) echo 'selected="selected"'; ?>><?php esc_html_e('Disabled', 'modern-events-calendar-lite'); ?></option>
                                    </select>
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Events Archive Status', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("You can disable the MEC default archive page and create a dedicated archive page if you disable this option. Obviously, the page you create must have a slug equal to what defined in Slugs/Permalinks > Main Slug.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#6-_Events_Archive_Status/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>
                            </div>

                            <h5 class="mec-form-subtitle"><?php esc_html_e('Taxonomy Shortcodes', 'modern-events-calendar-lite'); ?></h5>

                            <div class="mec-form-row">
                                <label class="mec-col-3"><?php esc_html_e('Category Shortcode', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <p><?php echo sprintf(esc_html__("To display a list of your event categories on the frontend, simply include the shortcode %s in your page content.", 'modern-events-calendar-lite'), '<code>[MEC_taxonomy_category]</code>'); ?></p>
                                </div>
                            </div>

                        </div>

                        <div id="slug_option" class="mec-options-fields">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('Slugs/Permalinks', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_slug"><?php esc_html_e('Main Slug', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <input type="text" id="mec_settings_slug" name="mec[settings][slug]" value="<?php echo ((isset($settings['slug']) and trim($settings['slug']) != '') ? esc_attr($settings['slug']) : 'events'); ?>" />
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Main Slug', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("You can change the base event post type slug from this field to customize the events and archive page URLs. Please note that you should not have a page with this slug on your website. The default value is 'events'.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#SlugsPermalinks/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                    <p><?php esc_attr_e("Valid characters are lowercase a-z, - character and numbers.", 'modern-events-calendar-lite'); ?></p>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_category_slug"><?php esc_html_e('Category Slug', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <input type="text" id="mec_settings_category_slug" name="mec[settings][category_slug]" value="<?php echo ((isset($settings['category_slug']) and trim($settings['category_slug']) != '') ? esc_attr($settings['category_slug']) : 'mec-category'); ?>" />
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Category Slug', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("You can change the event category page slug from this field. Please note that you should not have a page with this slug on your website. The default value is 'mec-category'.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#SlugsPermalinks/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                    <p><?php esc_attr_e("Valid characters are lowercase a-z, - character and numbers.", 'modern-events-calendar-lite'); ?></p>
                                </div>
                            </div>
                        </div>

                        <div id="currency_option" class="mec-options-fields">
                            <h4 class="mec-form-subtitle"><?php esc_html_e('Currency', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_currency"><?php esc_html_e('Currency', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select name="mec[settings][currency]" id="mec_settings_currency">
                                        <?php foreach($currencies as $currency=>$currency_name): ?>
                                            <option value="<?php echo esc_attr($currency); ?>" <?php echo ((isset($settings['currency']) and $settings['currency'] == $currency) ? 'selected="selected"' : ''); ?>><?php echo esc_html($currency_name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_currency_symptom"><?php esc_html_e('Currency Sign', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <input type="text" name="mec[settings][currency_symptom]" id="mec_settings_currency_symptom" value="<?php echo (isset($settings['currency_symptom']) ? esc_attr($settings['currency_symptom']) : ''); ?>" />
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Currency Sign', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("If you cannot find the currency label in the above drop-down menu, you can manually add it here. Leave it empty to inherit from the option above.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/general-settings/#Currency_Sign/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_currency_sign"><?php esc_html_e('Currency Position', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select name="mec[settings][currency_sign]" id="mec_settings_currency_sign">
                                        <option value="before" <?php echo ((isset($settings['currency_sign']) and $settings['currency_sign'] == 'before') ? 'selected="selected"' : ''); ?>><?php esc_html_e('$10 (Before)', 'modern-events-calendar-lite'); ?></option>
                                        <option value="before_space" <?php echo ((isset($settings['currency_sign']) and $settings['currency_sign'] == 'before_space') ? 'selected="selected"' : ''); ?>><?php esc_html_e('$ 10 (Before with Space)', 'modern-events-calendar-lite'); ?></option>
                                        <option value="after" <?php echo ((isset($settings['currency_sign']) and $settings['currency_sign'] == 'after') ? 'selected="selected"' : ''); ?>><?php esc_html_e('10$ (After)', 'modern-events-calendar-lite'); ?></option>
                                        <option value="after_space" <?php echo ((isset($settings['currency_sign']) and $settings['currency_sign'] == 'after_space') ? 'selected="selected"' : ''); ?>><?php esc_html_e('10 $ (After with Space)', 'modern-events-calendar-lite'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_thousand_separator"><?php esc_html_e('Thousand Separator', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <input type="text" name="mec[settings][thousand_separator]" id="mec_settings_thousand_separator" value="<?php echo (isset($settings['thousand_separator']) ? esc_attr($settings['thousand_separator']) : ','); ?>" />
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_decimal_separator"><?php esc_html_e('Decimal Separator', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <input type="text" name="mec[settings][decimal_separator]" id="mec_settings_decimal_separator" value="<?php echo (isset($settings['decimal_separator']) ? esc_attr($settings['decimal_separator']) : '.'); ?>" />
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_currency_decimals"><?php esc_html_e('Decimals', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <input type="number" name="mec[settings][currency_decimals]" id="mec_settings_currency_decimals" value="<?php echo (isset($settings['currency_decimals']) ? esc_attr((int)$settings['currency_decimals']) : 2); ?>" min="0" />
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label for="mec_settings_decimal_separator_status">
                                        <input type="hidden" name="mec[settings][decimal_separator_status]" value="1" />
                                        <input type="checkbox" name="mec[settings][decimal_separator_status]" id="mec_settings_decimal_separator_status" <?php echo ((isset($settings['decimal_separator_status']) and $settings['decimal_separator_status'] == '0') ? 'checked="checked"' : ''); ?> value="0" />
                                        <?php esc_html_e('No decimal', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="captcha_option" class="mec-options-fields">
                            <h4 class="mec-form-subtitle"><?php esc_html_e('Security Captcha', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <label>
                                    <input type="hidden" name="mec[settings][google_recaptcha_status]" value="0" />
                                    <input id="mec_google_recaptcha_checkbox" onchange="jQuery('#mec_google_recaptcha_container_toggle').toggle(); jQuery('#mec_mtcaptcha_checkbox, #mec_google_recaptcha_v3_checkbox').prop('checked', false); jQuery('#mec_mtcaptcha_container_toggle, #mec_google_recaptcha_v3_container_toggle').hide();" value="1" type="checkbox" name="mec[settings][google_recaptcha_status]" <?php if(isset($settings['google_recaptcha_status']) and $settings['google_recaptcha_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable Google Recaptcha V2', 'modern-events-calendar-lite'); ?>
                                </label>
                            </div>
                            <div id="mec_google_recaptcha_container_toggle" class="<?php if((isset($settings['google_recaptcha_status']) and !$settings['google_recaptcha_status']) or !isset($settings['google_recaptcha_status'])) echo 'mec-util-hidden'; ?>">

                                <?php if($this->getPRO()): ?>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][google_recaptcha_booking]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][google_recaptcha_booking]" <?php if(isset($settings['google_recaptcha_booking']) and $settings['google_recaptcha_booking']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable on booking form', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <?php endif; ?>

                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][google_recaptcha_fes]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][google_recaptcha_fes]" <?php if(isset($settings['google_recaptcha_fes']) and $settings['google_recaptcha_fes']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable on "Frontend Event Submission" form', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div class="mec-form-row">
                                    <label class="mec-col-3" for="mec_settings_google_recaptcha_sitekey"><?php esc_html_e('Site Key', 'modern-events-calendar-lite'); ?></label>
                                    <div class="mec-col-9">
                                        <input type="password" id="mec_settings_google_recaptcha_sitekey" name="mec[settings][google_recaptcha_sitekey]" value="<?php echo ((isset($settings['google_recaptcha_sitekey']) and trim($settings['google_recaptcha_sitekey']) != '') ? $settings['google_recaptcha_sitekey'] : ''); ?>" />
                                        <div class="mec-show-hide-password"><?php esc_html_e('Show / Hide', 'modern-events-calendar-lite'); ?></div>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <label class="mec-col-3" for="mec_settings_google_recaptcha_secretkey"><?php esc_html_e('Secret Key', 'modern-events-calendar-lite'); ?></label>
                                    <div class="mec-col-9">
                                        <input type="password" id="mec_settings_google_recaptcha_secretkey" name="mec[settings][google_recaptcha_secretkey]" value="<?php echo ((isset($settings['google_recaptcha_secretkey']) and trim($settings['google_recaptcha_secretkey']) != '') ? $settings['google_recaptcha_secretkey'] : ''); ?>" />
                                        <div class="mec-show-hide-password"><?php esc_html_e('Show / Hide', 'modern-events-calendar-lite'); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <label>
                                    <input type="hidden" name="mec[settings][google_recaptcha_v3_status]" value="0" />
                                    <input id="mec_google_recaptcha_v3_checkbox" onchange="jQuery('#mec_google_recaptcha_v3_container_toggle').toggle(); jQuery('#mec_mtcaptcha_checkbox, #mec_google_recaptcha_checkbox').prop('checked', false); jQuery('#mec_google_recaptcha_container_toggle, #mec_mtcaptcha_container_toggle').hide();" value="1" type="checkbox" name="mec[settings][google_recaptcha_v3_status]" <?php if(isset($settings['google_recaptcha_v3_status']) and $settings['google_recaptcha_v3_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable Google Recaptcha V3', 'modern-events-calendar-lite'); ?>
                                </label>
                            </div>
                            <div id="mec_google_recaptcha_v3_container_toggle" class="<?php if((isset($settings['google_recaptcha_v3_status']) and !$settings['google_recaptcha_v3_status']) or !isset($settings['google_recaptcha_v3_status'])) echo 'mec-util-hidden'; ?>">

                                <?php if($this->getPRO()): ?>
                                    <div class="mec-form-row">
                                        <label>
                                            <input type="hidden" name="mec[settings][google_recaptcha_v3_booking]" value="0" />
                                            <input value="1" type="checkbox" name="mec[settings][google_recaptcha_v3_booking]" <?php if(isset($settings['google_recaptcha_v3_booking']) and $settings['google_recaptcha_v3_booking']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable on booking form', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                <?php endif; ?>

                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][google_recaptcha_v3_fes]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][google_recaptcha_v3_fes]" <?php if(isset($settings['google_recaptcha_v3_fes']) and $settings['google_recaptcha_v3_fes']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable on "Frontend Event Submission" form', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div class="mec-form-row">
                                    <label class="mec-col-3" for="mec_settings_google_recaptcha_v3_sitekey"><?php esc_html_e('Site Key', 'modern-events-calendar-lite'); ?></label>
                                    <div class="mec-col-9">
                                        <input type="password" id="mec_settings_google_recaptcha_v3_sitekey" name="mec[settings][google_recaptcha_v3_sitekey]" value="<?php echo ((isset($settings['google_recaptcha_v3_sitekey']) and trim($settings['google_recaptcha_v3_sitekey']) != '') ? $settings['google_recaptcha_v3_sitekey'] : ''); ?>" />
                                        <div class="mec-show-hide-password"><?php esc_html_e('Show / Hide', 'modern-events-calendar-lite'); ?></div>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <label class="mec-col-3" for="mec_settings_google_recaptcha_v3_secretkey"><?php esc_html_e('Secret Key', 'modern-events-calendar-lite'); ?></label>
                                    <div class="mec-col-9">
                                        <input type="password" id="mec_settings_google_recaptcha_v3_secretkey" name="mec[settings][google_recaptcha_v3_secretkey]" value="<?php echo ((isset($settings['google_recaptcha_v3_secretkey']) and trim($settings['google_recaptcha_v3_secretkey']) != '') ? $settings['google_recaptcha_v3_secretkey'] : ''); ?>" />
                                        <div class="mec-show-hide-password"><?php esc_html_e('Show / Hide', 'modern-events-calendar-lite'); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <label>
                                    <input type="hidden" name="mec[settings][mtcaptcha_status]" value="0" />
                                    <input id="mec_mtcaptcha_checkbox" onchange="jQuery('#mec_mtcaptcha_container_toggle').toggle(); jQuery('#mec_google_recaptcha_checkbox, #mec_google_recaptcha_v3_checkbox').prop('checked', false); jQuery('#mec_google_recaptcha_container_toggle, #mec_google_recaptcha_v3_container_toggle').hide();" value="1" type="checkbox" name="mec[settings][mtcaptcha_status]" <?php if(isset($settings['mtcaptcha_status']) and $settings['mtcaptcha_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable MTCaptcha', 'modern-events-calendar-lite'); ?>
                                </label>
                            </div>
                            <div id="mec_mtcaptcha_container_toggle" class="<?php if((isset($settings['mtcaptcha_status']) and !$settings['mtcaptcha_status']) or !isset($settings['mtcaptcha_status'])) echo 'mec-util-hidden'; ?>">
                                <?php if($this->getPRO()): ?>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][mtcaptcha_booking]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][mtcaptcha_booking]" <?php if(isset($settings['mtcaptcha_booking']) and $settings['mtcaptcha_booking']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable on booking form', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <?php endif; ?>

                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][mtcaptcha_fes]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][mtcaptcha_fes]" <?php if(isset($settings['mtcaptcha_fes']) and $settings['mtcaptcha_fes']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable on "Frontend Event Submission" form', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div class="mec-form-row">
                                    <label class="mec-col-3" for="mec_settings_mtcaptcha_sitekey"><?php esc_html_e('Site Key', 'modern-events-calendar-lite'); ?></label>
                                    <div class="mec-col-9">
                                        <input type="password" id="mec_settings_mtcaptcha_sitekey" name="mec[settings][mtcaptcha_sitekey]" value="<?php echo ((isset($settings['mtcaptcha_sitekey']) and trim($settings['mtcaptcha_sitekey']) != '') ? $settings['mtcaptcha_sitekey'] : ''); ?>" />
                                        <div class="mec-show-hide-password"><?php esc_html_e('Show / Hide', 'modern-events-calendar-lite'); ?></div>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <label class="mec-col-3" for="mec_settings_mtcaptcha_privatekey"><?php esc_html_e('Private Key', 'modern-events-calendar-lite'); ?></label>
                                    <div class="mec-col-9">
                                        <input type="password" id="mec_settings_mtcaptcha_privatekey" name="mec[settings][mtcaptcha_privatekey]" value="<?php echo ((isset($settings['mtcaptcha_privatekey']) and trim($settings['mtcaptcha_privatekey']) != '') ? $settings['mtcaptcha_privatekey'] : ''); ?>" />
                                        <div class="mec-show-hide-password"><?php esc_html_e('Show / Hide', 'modern-events-calendar-lite'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="search_options" class="mec-options-fields">
                        <h4 class="mec-form-subtitle"><?php esc_html_e('Search', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-backend-tab-wrap mec-basvanced-toggle" data-for="#search_options">
                                <div class="mec-backend-tab">
                                    <div class="mec-backend-tab-item mec-b-active-tab"><?php esc_html_e('Basic', 'modern-events-calendar-lite'); ?></div>
                                    <div class="mec-backend-tab-item"><?php esc_html_e('Advanced', 'modern-events-calendar-lite'); ?></div>
                                </div>
                            </div>

                            <div class="mec-basvanced-basic">
                                <h5 class="mec-form-subtitle"><?php esc_html_e('Search Bar', 'modern-events-calendar-lite'); ?></h5>

                                <div class="mec-form-row">
                                    <p><?php echo sprintf(esc_html__('Put %s shortcode into your desired page. Then users are able to search events', 'modern-events-calendar-lite'), '<code>[MEC_search_bar]</code>'); ?></p>
                                </div>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][search_bar_ajax_mode]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][search_bar_ajax_mode]" <?php if(isset($settings['search_bar_ajax_mode']) and $settings['search_bar_ajax_mode']) echo 'checked="checked"'; ?> /><?php esc_html_e('Ajax Live mode', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <span class="mec-tooltip">
                                        <div class="box">
                                            <h5 class="title"><?php esc_html_e('Ajax mode', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("By enableing this option, the search button will disappear and the search bar will function live. To use this feature, the text input field must be on.", 'modern-events-calendar-lite'); ?></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][search_bar_modern_type]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][search_bar_modern_type]" <?php if(isset($settings['search_bar_modern_type']) and $settings['search_bar_modern_type']) echo 'checked="checked"'; ?> /><?php esc_html_e('Modern Type', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <h5 class="mec-form-subtitle"><?php esc_html_e('Search bar fields', 'modern-events-calendar-lite'); ?></h5>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][search_bar_category]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][search_bar_category]" <?php if(!isset($settings['search_bar_category']) or (isset($settings['search_bar_category']) and $settings['search_bar_category'])) echo 'checked="checked"'; ?> /><?php esc_html_e('Category', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][search_bar_location]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][search_bar_location]" <?php if(isset($settings['search_bar_location']) and $settings['search_bar_location']) echo 'checked="checked"'; ?> /><?php esc_html_e('Location', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][search_bar_organizer]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][search_bar_organizer]" <?php if(isset($settings['search_bar_organizer']) and $settings['search_bar_organizer']) echo 'checked="checked"'; ?> /><?php esc_html_e('Organizer', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <?php if(isset($settings['speakers_status']) and $settings['speakers_status']) : ?>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][search_bar_speaker]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][search_bar_speaker]" <?php if(isset($settings['search_bar_speaker']) and $settings['search_bar_speaker']) echo 'checked="checked"'; ?> /><?php esc_html_e('Speaker', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <?php endif; ?>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][search_bar_tag]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][search_bar_tag]" <?php if(isset($settings['search_bar_tag']) and $settings['search_bar_tag']) echo 'checked="checked"'; ?> /><?php esc_html_e('Tag', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][search_bar_label]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][search_bar_label]" <?php if(isset($settings['search_bar_label']) and $settings['search_bar_label']) echo 'checked="checked"'; ?> /><?php esc_html_e('Label', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][search_bar_text_field]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][search_bar_text_field]" <?php if(!isset($settings['search_bar_text_field']) or (isset($settings['search_bar_text_field']) and $settings['search_bar_text_field'])) echo 'checked="checked"'; ?> /><?php esc_html_e('Text input', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="mec-basvanced-advanced w-hidden">
                                <h5 class="mec-form-subtitle"><?php esc_html_e('Advanced Search Options', 'modern-events-calendar-lite'); ?></h5>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][auto_month_rotation]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][auto_month_rotation]" <?php if(!isset($settings['auto_month_rotation']) or $settings['auto_month_rotation']) echo 'checked="checked"'; ?> /><?php esc_html_e("Automatically search and display next month's events if no events are found for the requested month.", 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][sf_update_url]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][sf_update_url]" <?php if(isset($settings['sf_update_url']) && $settings['sf_update_url']) echo 'checked="checked"'; ?> /><?php esc_html_e('Update browser URL with active filters', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <span class="mec-tooltip">
                                        <div class="box">
                                            <h5 class="title"><?php esc_html_e('URL Sync', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e('When enabled, applying filters updates the page URL with query parameters so the filtered view can be shared or bookmarked. Resetting filters restores the original URL.', 'modern-events-calendar-lite'); ?></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>
                                <h5 class="mec-form-subtitle"><?php esc_html_e('Geo-point', 'modern-events-calendar-lite'); ?></h5>
                                <div class="mec-form-row">
                                    <label class="mec-col-3" for="mec_settings_google_geocoding_api_key"><?php esc_html_e('Google Geocoding API Key', 'modern-events-calendar-lite'); ?></label>
                                    <div class="mec-col-9">
                                        <input type="text" id="mec_settings_google_geocoding_api_key" name="mec[settings][google_geocoding_api_key]" value="<?php echo (isset($settings['google_geocoding_api_key']) && trim($settings['google_geocoding_api_key']) !== '') ? esc_attr($settings['google_geocoding_api_key']) : ''; ?>" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if($this->main->getPRO()): ?>

                            <div id="restful_api_options" class="mec-options-fields">
                                <h4 class="mec-form-subtitle"><?php esc_html_e('RESTful API', 'modern-events-calendar-lite'); ?></h4>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][restful_api_status]" value="0" />
                                        <input onchange="jQuery('#mec_restful_container_toggle').toggle();" value="1" type="checkbox" name="mec[settings][restful_api_status]" <?php if(isset($settings['restful_api_status']) and $settings['restful_api_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable API', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div id="mec_restful_container_toggle" class="<?php if(!isset($settings['restful_api_status']) || !$settings['restful_api_status']) echo 'mec-util-hidden'; ?>">
                                    <div class="mec-form-row">
                                        <div class="mec-col-3"><strong><?php esc_html_e('API Endpoint', 'modern-events-calendar-lite'); ?></strong></div>
                                        <div class="mec-col-9"><code><?php echo $this->getRestful()->get_endpoint_url(); ?></code></div>
                                    </div>
                                    <h5 class="mec-form-subtitle"><?php esc_html_e('API Keys', 'modern-events-calendar-lite'); ?></h5>
                                    <div class="mec-form-row">
                                        <button type="button" class="button button-secondary" id="mec_add_new_api_key"><?php esc_html_e('Add', 'modern-events-calendar-lite'); ?></button>
                                    </div>
                                    <input type="hidden" name="mec[settings][api_keys_present]" value="1" />
                                    <div class="mec-api-key-wrapper">
                                        <?php $i = 0; if(isset($settings['api_keys']) && is_array($settings['api_keys'])): ?>
                                            <?php foreach($settings['api_keys'] as $k => $api_key): if(!is_numeric($k) || (($api_key['key'] ?? '') === ':k:')) continue; ?>
                                                <div class="mec-form-row mec-api-key-row">
                                                    <div class="mec-col-3"><input type="text" name="mec[settings][api_keys][<?php echo esc_attr($i); ?>][name]" title="<?php esc_attr_e('Display Name', 'modern-events-calendar-lite'); ?>" placeholder="<?php esc_attr_e('Display Name', 'modern-events-calendar-lite'); ?>" value="<?php echo esc_attr($api_key['name']); ?>"></div>
                                                    <div class="mec-col-9"><input type="hidden" name="mec[settings][api_keys][<?php echo esc_attr($i); ?>][key]" value="<?php echo esc_attr($api_key['key']); ?>"><code><?php echo esc_html($api_key['key']); ?></code><button type="button" class="button button-secondary mec-remove-api-key"><?php esc_html_e('Remove', 'modern-events-calendar-lite'); ?></button></div>
                                                </div>
                                            <?php $i++; endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" id="mec_next_api_key_id" value="<?php echo esc_attr($i); ?>">
                                    <div id="mec_api_key_template" class="mec-util-hidden">
                                        <div class="mec-form-row mec-api-key-row">
                                            <div class="mec-col-3"><input type="text" disabled="disabled" name="mec[settings][api_keys][:i:][name]" title="<?php esc_attr_e('Display Name', 'modern-events-calendar-lite'); ?>" placeholder="<?php esc_attr_e('Display Name', 'modern-events-calendar-lite'); ?>"></div>
                                            <div class="mec-col-9"><input type="hidden" disabled="disabled" name="mec[settings][api_keys][:i:][key]" value=":k:"><code>:k:</code><button type="button" class="button button-secondary mec-remove-api-key"><?php esc_html_e('Remove', 'modern-events-calendar-lite'); ?></button></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="campaign_monitor_option" class="mec-options-fields">
                                <h4 class="mec-form-subtitle"><?php esc_html_e('Campaign Monitor Integration', 'modern-events-calendar-lite'); ?></h4>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][campm_status]" value="0" />
                                        <input onchange="jQuery('#mec_campm_status_container_toggle').toggle();" value="1" type="checkbox" name="mec[settings][campm_status]" <?php if(isset($settings['campm_status']) and $settings['campm_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable Campaign Monitor Integration', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div id="mec_campm_status_container_toggle" class="<?php if((isset($settings['campm_status']) and !$settings['campm_status']) or !isset($settings['campm_status'])) echo 'mec-util-hidden'; ?>">
                                    <div class="mec-form-row">
                                        <label class="mec-col-3" for="mec_settings_campm_api_key"><?php esc_html_e('API Key', 'modern-events-calendar-lite'); ?></label>
                                        <div class="mec-col-9">
                                            <input type="text" id="mec_settings_campm_api_key" name="mec[settings][campm_api_key]" value="<?php echo ((isset($settings['campm_api_key']) and trim($settings['campm_api_key']) != '') ? $settings['campm_api_key'] : ''); ?>" />
                                        </div>
                                    </div>
                                    <div class="mec-form-row">
                                        <label class="mec-col-3" for="mec_settings_campm_list_id"><?php esc_html_e('List ID', 'modern-events-calendar-lite'); ?></label>
                                        <div class="mec-col-9">
                                            <input type="text" id="mec_settings_campm_list_id" name="mec[settings][campm_list_id]" value="<?php echo ((isset($settings['campm_list_id']) and trim($settings['campm_list_id']) != '') ? $settings['campm_list_id'] : ''); ?>" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="mailerlite_option" class="mec-options-fields">
                                <h4 class="mec-form-subtitle"><?php esc_html_e('MailerLite Integration', 'modern-events-calendar-lite'); ?></h4>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][mailerlite_status]" value="0" />
                                        <input onchange="jQuery('#mec_mailerlite_status_container_toggle').toggle();" value="1" type="checkbox" name="mec[settings][mailerlite_status]" <?php if(isset($settings['mailerlite_status']) and $settings['mailerlite_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable MailerLite Integration', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div id="mec_mailerlite_status_container_toggle" class="<?php if((isset($settings['mailerlite_status']) and !$settings['mailerlite_status']) or !isset($settings['mailerlite_status'])) echo 'mec-util-hidden'; ?>">
                                    <div class="mec-form-row">
                                        <label class="mec-col-3" for="mec_settings_mailerlite_api_key"><?php esc_html_e('API Key', 'modern-events-calendar-lite'); ?></label>
                                        <div class="mec-col-9">
                                            <input type="text" id="mec_settings_mailerlite_api_key" name="mec[settings][mailerlite_api_key]" value="<?php echo ((isset($settings['mailerlite_api_key']) and trim($settings['mailerlite_api_key']) != '') ? $settings['mailerlite_api_key'] : ''); ?>" />
                                        </div>
                                    </div>
                                    <div class="mec-form-row">
                                        <label class="mec-col-3" for="mec_settings_mailerlite_list_id"><?php esc_html_e('Group ID', 'modern-events-calendar-lite'); ?></label>
                                        <div class="mec-col-9">
                                            <input type="text" id="mec_settings_mailerlite_list_id" name="mec[settings][mailerlite_list_id]" value="<?php echo ((isset($settings['mailerlite_list_id']) and trim($settings['mailerlite_list_id']) != '') ? $settings['mailerlite_list_id'] : ''); ?>" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="active_campaign_option" class="mec-options-fields">
                                <h4 class="mec-form-subtitle"><?php esc_html_e('Active Campaign Integration', 'modern-events-calendar-lite'); ?></h4>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][active_campaign_status]" value="0" />
                                        <input onchange="jQuery('#mec_active_campaign_status_container_toggle').toggle();" value="1" type="checkbox" name="mec[settings][active_campaign_status]" <?php if(isset($settings['active_campaign_status']) and $settings['active_campaign_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable Active Campaign Integration', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div id="mec_active_campaign_status_container_toggle" class="<?php if((isset($settings['active_campaign_status']) and !$settings['active_campaign_status']) or !isset($settings['active_campaign_status'])) echo 'mec-util-hidden'; ?>">
                                    <div class="mec-form-row">
                                        <label class="mec-col-3" for="mec_settings_active_campaign_api_url"><?php esc_html_e('API URL', 'modern-events-calendar-lite'); ?></label>
                                        <div class="mec-col-9">
                                            <input type="text" id="mec_settings_active_campaign_api_url" name="mec[settings][active_campaign_api_url]" value="<?php echo ((isset($settings['active_campaign_api_url']) and trim($settings['active_campaign_api_url']) != '') ? $settings['active_campaign_api_url'] : ''); ?>" />
                                        </div>
                                    </div>
                                    <div class="mec-form-row">
                                        <label class="mec-col-3" for="mec_settings_active_campaign_api_key"><?php esc_html_e('API Key', 'modern-events-calendar-lite'); ?></label>
                                        <div class="mec-col-9">
                                            <input type="text" id="mec_settings_active_campaign_api_key" name="mec[settings][active_campaign_api_key]" value="<?php echo ((isset($settings['active_campaign_api_key']) and trim($settings['active_campaign_api_key']) != '') ? $settings['active_campaign_api_key'] : ''); ?>" />
                                        </div>
                                    </div>
                                    <div class="mec-form-row">
                                        <label class="mec-col-3" for="mec_settings_active_campaign_list_id"><?php esc_html_e('List ID', 'modern-events-calendar-lite'); ?></label>
                                        <div class="mec-col-9">
                                            <input type="text" id="mec_settings_active_campaign_list_id" name="mec[settings][active_campaign_list_id]" value="<?php echo ((isset($settings['active_campaign_list_id']) and trim($settings['active_campaign_list_id']) != '') ? $settings['active_campaign_list_id'] : ''); ?>" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="aweber_option" class="mec-options-fields">
                                <h4 class="mec-form-subtitle"><?php esc_html_e('AWeber Integration', 'modern-events-calendar-lite'); ?></h4>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][aweber_status]" value="0" />
                                        <input onchange="jQuery('#mec_aweber_status_container_toggle').toggle();" value="1" type="checkbox" name="mec[settings][aweber_status]" <?php if(isset($settings['aweber_status']) and $settings['aweber_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable AWeber Integration', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div id="mec_aweber_status_container_toggle" class="<?php if((isset($settings['aweber_status']) and !$settings['aweber_status']) or !isset($settings['aweber_status'])) echo 'mec-util-hidden'; ?>">
                                    <div class="mec-form-row">
                                        <label class="mec-col-3" for="mec_settings_aweber_list_id"><?php esc_html_e('List ID', 'modern-events-calendar-lite'); ?></label>
                                        <div class="mec-col-9">
                                            <input type="text" id="mec_settings_aweber_list_id" name="mec[settings][aweber_list_id]" value="<?php echo ((isset($settings['aweber_list_id']) and trim($settings['aweber_list_id']) != '') ? $settings['aweber_list_id'] : ''); ?>" />
                                            <p class="description"><?php echo sprintf(esc_html__("%s plugin should be installed and connected to your AWeber account.", 'modern-events-calendar-lite'), '<a href="https://wordpress.org/plugins/aweber-web-form-widget/" target="_blank">AWeber for WordPress</a>'); ?></p>
                                            <p class="description"><?php echo sprintf(esc_html__('More information about the list ID can be found %s.', 'modern-events-calendar-lite'), '<a href="https://help.aweber.com/hc/en-us/articles/204028426" target="_blank">'.esc_html__('here', 'modern-events-calendar-lite').'</a>'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="mailpoet_option" class="mec-options-fields">
                                <h4 class="mec-form-subtitle"><?php esc_html_e('MailPoet Integration', 'modern-events-calendar-lite'); ?></h4>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][mailpoet_status]" value="0" />
                                        <input onchange="jQuery('#mec_mailpoet_status_container_toggle').toggle();" value="1" type="checkbox" name="mec[settings][mailpoet_status]" <?php if(isset($settings['mailpoet_status']) and $settings['mailpoet_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable MailPoet Integration', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div id="mec_mailpoet_status_container_toggle" class="<?php if((isset($settings['mailpoet_status']) and !$settings['mailpoet_status']) or !isset($settings['mailpoet_status'])) echo 'mec-util-hidden'; ?>">
                                    <?php if(class_exists(\MailPoet\API\API::class)): $mailpoet_api = \MailPoet\API\API::MP('v1'); $mailpoets_lists = $mailpoet_api->getLists(); ?>
                                    <div class="mec-form-row">
                                        <label class="mec-col-3" for="mec_settings_mailpoet_list_id"><?php esc_html_e('List', 'modern-events-calendar-lite'); ?></label>
                                        <div class="mec-col-9">
                                            <select name="mec[settings][mailpoet_list_id]" id="mec_settings_mailpoet_list_id">
                                                <option value="">-----</option>
                                                <?php foreach($mailpoets_lists as $mailpoets_list): ?>
                                                <option value="<?php echo esc_attr($mailpoets_list['id']); ?>" <?php echo ((isset($settings['mailpoet_list_id']) and trim($settings['mailpoet_list_id']) == $mailpoets_list['id']) ? 'selected="selected"' : ''); ?>><?php echo esc_html($mailpoets_list['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <p class="description"><?php echo sprintf(esc_html__("%s plugin should be installed and activated.", 'modern-events-calendar-lite'), '<a href="https://wordpress.org/plugins/mailpoet/" target="_blank">MailPoet</a>'); ?></p>
                                </div>
                            </div>

                            <div id="sendfox_option" class="mec-options-fields">
                                <h4 class="mec-form-subtitle"><?php esc_html_e('Sendfox Integration', 'modern-events-calendar-lite'); ?></h4>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][sendfox_status]" value="0" />
                                        <input onchange="jQuery('#mec_sendfox_status_container_toggle').toggle();" value="1" type="checkbox" name="mec[settings][sendfox_status]" <?php if(isset($settings['sendfox_status']) and $settings['sendfox_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable Sendfox Integration', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div id="mec_sendfox_status_container_toggle" class="<?php if((isset($settings['sendfox_status']) and !$settings['sendfox_status']) or !isset($settings['sendfox_status'])) echo 'mec-util-hidden'; ?>">

                                    <?php if(function_exists('gb_sf4wp_get_lists')): $sendfox_lists = gb_sf4wp_get_lists(); ?>
                                    <div class="mec-form-row">
                                        <label class="mec-col-3" for="mec_settings_sendfox_list_id"><?php esc_html_e('List ID', 'modern-events-calendar-lite'); ?></label>
                                        <div class="mec-col-9">
                                            <select name="mec[settings][sendfox_list_id]" id="mec_settings_sendfox_list_id">
                                                <?php foreach($sendfox_lists['result']['data'] as $sendfox_list): ?>
                                                <option value="<?php echo esc_attr($sendfox_list['id']); ?>" <?php echo ((isset($settings['sendfox_list_id']) and trim($settings['sendfox_list_id']) == $sendfox_list['id']) ? 'selected="selected"' : ''); ?>><?php echo esc_html($sendfox_list['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <p class="description"><?php echo sprintf(esc_html__("%s plugin should be installed and connected to your Sendfox account.", 'modern-events-calendar-lite'), '<a href="https://wordpress.org/plugins/wp-sendfox/" target="_blank">WP Sendfox</a>'); ?></p>
                                </div>
                            </div>

                        <?php endif; ?>

                        <?php do_action('mec-settings-page-before-form-end', $settings) ?>

                        <div class="mec-options-fields">
                            <?php wp_nonce_field('mec_options_form'); ?>
                            <button style="display: none;" id="mec_settings_form_button" class="button button-primary mec-button-primary" type="submit"><?php esc_html_e('Save Changes', 'modern-events-calendar-lite'); ?></button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <div id="wns-be-footer">
        <a id="" class="dpr-btn dpr-save-btn"><?php esc_html_e('Save Changes', 'modern-events-calendar-lite'); ?></a>
    </div>

</div>

<?php $this->factory->params('footer', '<script>
jQuery(document).ready(function()
{
    jQuery(".dpr-save-btn").on("click", function(event)
    {
        event.preventDefault();
        jQuery("#mec_settings_form_button").trigger("click");
    });
    
    let $database_setup_button = jQuery("#database_setup_button");
    let $database_setup_message = jQuery("#database_setup_message");
    $database_setup_button.on("click", function(event)
    {
        event.preventDefault();
        
        $database_setup_button.data("text", $database_setup_button.text());
        $database_setup_button.text("...");
        
        $database_setup_message.html("");
        $database_setup_button.attr("disabled", true);
        
        jQuery.ajax(
        {
            type: "POST",
            url: ajaxurl,
            data: "action=mec_maintenance_reinstall",
            success: function(response)
            {
                $database_setup_button.attr("disabled", false);
                $database_setup_button.text($database_setup_button.data("text"));
                
                $database_setup_message.html(`<div class="mec-success">${response.message}</div>`);
            },
            error: function()
            {
                $database_setup_button.attr("disabled", false);
                $database_setup_button.text($database_setup_button.data("text"));
            }
        });
    });
});

var archive_value = jQuery("#mec_settings_default_skin_archive").val();
function mec_archive_skin_style_changed(archive_value)
{
    jQuery(".mec-archive-skins").hide();
    jQuery(".mec-archive-skins.mec-archive-"+archive_value+"-skins").show();
    if(archive_value === "custom") jQuery(".mec-archive-events-method-row").hide();
    else jQuery(".mec-archive-events-method-row").show();
}
mec_archive_skin_style_changed(archive_value);

var category_value = jQuery("#mec_settings_default_skin_category").val();
function mec_category_skin_style_changed(category_value)
{
    jQuery(".mec-category-skins").hide();
    jQuery(".mec-category-skins.mec-category-"+category_value+"-skins").show();
}
mec_category_skin_style_changed(category_value);

jQuery("#mec_settings_form").on("submit", function(event)
{
    event.preventDefault();

    // Add loading Class to the button
    jQuery(".dpr-save-btn").addClass("loading").text("'.esc_js(esc_attr__('Saved', 'modern-events-calendar-lite')).'");
    jQuery("<div class=\"wns-saved-settings\">'.esc_js(esc_attr__('Settings Saved!', 'modern-events-calendar-lite')).'</div>").insertBefore("#wns-be-content");

    if(jQuery(".mec-purchase-verify").text() != "'.esc_js(esc_attr__('Verified', 'modern-events-calendar-lite')).'")
    {
        jQuery(".mec-purchase-verify").text("'.esc_js(esc_attr__('Checking ...', 'modern-events-calendar-lite')).'");
    }

    var settings = jQuery("#mec_settings_form").serialize();
    if(jQuery.isArray(jQuery("#mec_settings_form #invoice_attendees_custom_fields").val()) && jQuery("#mec_settings_form #invoice_attendees_custom_fields").val().length==0){
      settings += "&mec[settings][attendees_custom_fields][]=";
    }

    jQuery.ajax(
    {
        type: "POST",
        url: ajaxurl,
        data: "action=mec_save_settings&"+settings,
        beforeSend: function () {
            jQuery(".wns-be-main").append("<div class=\"mec-loarder-wrap mec-settings-loader\"><div class=\"mec-loarder\"><div></div><div></div><div></div></div></div>");
        },
        success: function(data)
        {
            // Remove the loading Class to the button
            setTimeout(function()
            {
                jQuery(".dpr-save-btn").removeClass("loading").text("'.esc_js(esc_attr__('Save Changes', 'modern-events-calendar-lite')).'");
                jQuery(".wns-saved-settings").remove();
                jQuery(".mec-loarder-wrap").remove();
                if(jQuery(".mec-purchase-verify").text() != "'.esc_js(esc_attr__('Verified', 'modern-events-calendar-lite')).'")
                {
                    jQuery(".mec-purchase-verify").text("'.esc_js(esc_attr__('Please Refresh Page', 'modern-events-calendar-lite')).'");
                }
            }, 1000);
        },
        error: function(jqXHR, textStatus, errorThrown)
        {
            // Remove the loading Class to the button
            setTimeout(function()
            {
                jQuery(".dpr-save-btn").removeClass("loading").text("'.esc_js(esc_attr__('Save Changes', 'modern-events-calendar-lite')).'");
                jQuery(".wns-saved-settings").remove();
                jQuery(".mec-loarder-wrap").remove();
            }, 1000);
        }
    });
});
</script>');

<?php
    $nonce = wp_create_nonce('mec_options_wizard');
?>
<div id="mec_popup_settings" class="mec-setup-wizard-wrap lity-hide">
    <div class="mec-wizard-content wns-be-group-tab">
        <div class="mec-wizard-loading"><div class="mec-loader"></div></div>
        <div class="mec-steps-container">
            <img src="<?php echo plugin_dir_url(__FILE__ ) . '../../../assets/img/mec-logo-icon.svg'; ?>"  style="width: 50px" />
            <ul>
                <li class="mec-step mec-step-1 mec-step-passed"><span>1</span></li>
                <li class="mec-step mec-step-2"><span>2</span></li>
                <li class="mec-step mec-step-3"><span>3</span></li>
                <li class="mec-step mec-step-4"><span>4</span></li>
                <?php if($this->getPRO()) : ?>
                <li class="mec-step mec-step-5"><span>5</span></li>
                <li class="mec-step mec-step-6"><span>6</span></li>
                <?php else : ?>
                <li class="mec-step mec-step-5"><span>5</span></li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="mec-steps-panel">
            <div id="mec_popup_settings_form">
                <div class="mec-steps-header">
                    <div class="mec-steps-header-userinfo">
                        <?php $user = wp_get_current_user(); ?>
                        <?php if(get_option('show_avatars')): ?>
                        <span class="mec-steps-header-img"><img src="<?php echo esc_url(get_avatar_url($user->ID)); ?>" /></span>
                        <?php endif; ?>
                        <span class="mec-steps-header-name"><?php echo esc_html($user->display_name); ?></span>
                    </div>
                    <div class="mec-steps-header-dashboard">
                        <a href="<?php echo admin_url('admin.php?page=mec-intro'); ?>"><i class="mec-sl-pie-chart"></i><?php esc_html_e('Dashboard', 'modern-events-calendar-lite'); ?></a>
                    </div>
                    <div class="mec-steps-header-settings">
                        <a href="<?php echo admin_url('admin.php?page=MEC-settings'); ?>"><i class="mec-sl-settings"></i><?php esc_html_e('Settings', 'modern-events-calendar-lite'); ?></a>
                    </div>
                </div>
                <div class="mec-step-wizard-content mec-active-step" data-step="1">
                    <form id="mec_save_weekdays_form">
                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                        <?php $weekdays = $this->main->get_weekday_i18n_labels(); ?>
                        <div class="mec-form-row"  id="mec_settings_weekdays">
                            <label class="mec-col-12" for="mec_settings_weekdays"><?php esc_html_e('Weekdays', 'modern-events-calendar-lite'); ?></label>
                            <div class="mec-col-6">
                                <div class="mec-box">
                                    <?php $mec_weekdays = $this->main->get_weekdays(); foreach($weekdays as $weekday): ?>
                                    <label for="mec_settings_weekdays_<?php echo esc_attr($weekday[0]); ?>">
                                        <input type="checkbox" id="mec_settings_weekdays_<?php echo esc_attr($weekday[0]); ?>" name="mec[settings][weekdays][]" value="<?php echo esc_attr($weekday[0]); ?>" <?php echo (in_array($weekday[0], $mec_weekdays) ? 'checked="checked"' : ''); ?> />
                                        <?php echo esc_html($weekday[1]); ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="mec-form-row" id="mec_settings_weekends">
                            <label class="mec-col-12" for="mec_settings_weekends"><?php esc_html_e('Weekends', 'modern-events-calendar-lite'); ?></label>
                            <div class="mec-col-6">
                                <div class="mec-box">
                                    <?php $mec_weekends = $this->main->get_weekends(); foreach($weekdays as $weekday): ?>
                                    <label for="mec_settings_weekends_<?php echo esc_attr($weekday[0]); ?>">
                                        <input type="checkbox" id="mec_settings_weekends_<?php echo esc_attr($weekday[0]); ?>" name="mec[settings][weekends][]" value="<?php echo esc_attr($weekday[0]); ?>" <?php echo (in_array($weekday[0], $mec_weekends) ? 'checked="checked"' : ''); ?> />
                                        <?php echo esc_html($weekday[1]); ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="mec-step-wizard-content" data-step="2">
                    <form id="mec_save_slug_form">
                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                        <div class="mec-form-row">
                            <label class="mec-col-2" for="mec_settings_archive_title"><?php esc_html_e('Archive Page Title', 'modern-events-calendar-lite'); ?></label>
                            <div class="mec-col-4">
                                <input type="text" id="mec_settings_archive_title" name="mec[settings][archive_title]" value="<?php echo ((isset($settings['archive_title']) and trim($settings['archive_title']) != '') ? $settings['archive_title'] : 'Events'); ?>" />
                                <span class="mec-tooltip">
                                    <div class="box left">
                                        <h5 class="title"><?php esc_html_e('Archive Page Title', 'modern-events-calendar-lite'); ?></h5>
                                        <div class="content"><p><?php esc_attr_e("Default value is Events - It's title of the page", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/archive-pages/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                    </div>
                                    <i title="" class="dashicons-before dashicons-editor-help"></i>
                                </span>
                            </div>
                        </div>

                        <div class="mec-form-row">
                            <label class="mec-col-2" for="mec_settings_default_skin_archive"><?php esc_html_e('Archive Page Skin', 'modern-events-calendar-lite'); ?></label>
                            <div class="mec-col-4 tooltip-move-up">
                                <select id="mec_settings_default_skin_archive" name="mec[settings][default_skin_archive]" onchange="mec_archive_skin_style_changed(this.value);" style="margin-bottom: 8px;">
                                    <?php foreach($archive_skins as $archive_skin): ?>
                                        <option value="<?php echo esc_attr($archive_skin['skin']); ?>" <?php if(isset($settings['default_skin_archive']) and $archive_skin['skin'] == $settings['default_skin_archive']) echo 'selected="selected"'; ?>><?php echo esc_html($archive_skin['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
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
                                <span class="mec-archive-skins mec-archive-monthly_view-skins" style="display: inline-block;">
                                    <select id="mec_settings_monthly_view_skin_archive" name="mec[settings][monthly_view_archive_skin]" style="    min-width: 225px;">
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
                                        <option value="accordion" <?php if(isset($settings['list_archive_skin']) &&  $settings['list_archive_skin'] == 'accordion') echo 'selected="selected"'; ?>><?php echo esc_html__('Accordion' , 'modern-events-calendar-lite'); ?></option>
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
                        <div class="mec-form-row">
                            <label class="mec-col-2" for="mec_settings_slug"><?php esc_html_e('Main Slug', 'modern-events-calendar-lite'); ?></label>
                            <div class="mec-col-4">
                                <input type="text" id="mec_settings_slug" name="mec[settings][slug]" value="<?php echo ((isset($settings['slug']) and trim($settings['slug']) != '') ? $settings['slug'] : 'events'); ?>" />
                                <p><?php esc_attr_e("Valid characters are lowercase a-z, - character and numbers.", 'modern-events-calendar-lite'); ?></p>
                            </div>
                        </div>
                        <div class="mec-form-row">
                            <label class="mec-col-2" for="mec_settings_single_event_single_style"><?php esc_html_e('Single Event Style', 'modern-events-calendar-lite'); ?></label>
                            <div class="mec-col-4">
                                <select id="mec_settings_single_event_single_style" name="mec[settings][single_single_style]">
                                    <option value="default" <?php echo (isset($settings['single_single_style']) and $settings['single_single_style'] == 'default') ? 'selected="selected"' : ''; ?>><?php esc_html_e('Default Style', 'modern-events-calendar-lite'); ?></option>
                                    <option value="modern" <?php echo (isset($settings['single_single_style']) and $settings['single_single_style'] == 'modern') ? 'selected="selected"' : ''; ?>><?php esc_html_e('Modern Style', 'modern-events-calendar-lite'); ?></option>
                                    <?php do_action('mec_single_style', $settings); ?>
                                    <?php if(is_plugin_active('mec-single-builder/mec-single-builder.php')): ?>
                                    <option value="builder" <?php echo (isset($settings['single_single_style']) and $settings['single_single_style'] == 'builder') ? 'selected="selected"' : ''; ?>><?php esc_html_e('Elementor Single Builder', 'modern-events-calendar-lite'); ?></option>
                                    <?php endif; ?>
                                    <?php if(is_plugin_active('mec-gutenberg-single-builder/mec-gutenberg-single-builder.php')): ?>
                                    <option value="gsb-builder" <?php echo (isset($settings['single_single_style']) and $settings['single_single_style'] == 'gsb-builder') ? 'selected="selected"' : ''; ?>><?php esc_html_e('Gutenberg Single Builder', 'modern-events-calendar-lite'); ?></option>
                                    <?php endif; ?>
                                </select>
                                <span class="mec-tooltip">
                                    <div class="box left">
                                        <h5 class="title"><?php esc_html_e('Single Event Style', 'modern-events-calendar-lite'); ?></h5>
                                        <div class="content"><p><?php esc_attr_e("Choose your single event style.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-detailssingle-event-page/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                    </div>
                                    <i title="" class="dashicons-before dashicons-editor-help"></i>
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="mec-step-wizard-content" data-step="3">
                    <form id="mec_save_module_form">
                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                        <div class="mec-form-row">
                            <label style="display: block;">
                                <input type="hidden" name="mec[settings][countdown_status]" value="0" />
                                <input onchange="jQuery('#mec_count_down_container_toggle').toggle();" value="1" type="checkbox" name="mec[settings][countdown_status]" <?php if(isset($settings['countdown_status']) and $settings['countdown_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Show countdown module on event page', 'modern-events-calendar-lite'); ?>
                            </label>
                            <div id="mec_count_down_container_toggle" class="mec-col-6 <?php if((isset($settings['countdown_status']) and !$settings['countdown_status']) or !isset($settings['countdown_status'])) echo 'mec-util-hidden'; ?>">
                                <div class="mec-form-row">
                                    <label class="mec-col-4" for="mec_settings_countdown_list"><?php esc_html_e('Countdown Style', 'modern-events-calendar-lite'); ?></label>
                                    <div class="mec-col-4">
                                        <select id="mec_settings_countdown_list" name="mec[settings][countdown_list]">
                                            <option value="default" <?php echo ((isset($settings['countdown_list']) and $settings['countdown_list'] == "default") ? 'selected="selected"' : ''); ?> ><?php esc_html_e('Plain Style', 'modern-events-calendar-lite'); ?></option>
                                            <option value="flip" <?php echo ((isset($settings['countdown_list']) and $settings['countdown_list'] == "flip") ? 'selected="selected"' : ''); ?> ><?php esc_html_e('Flip Style', 'modern-events-calendar-lite'); ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mec-form-row">
                            <label style="margin-top: 10px;">
                                <input type="hidden" name="mec[settings][related_events]" value="0" />
                                <input onchange="jQuery('#mec_related_events_container_toggle').toggle();" value="1" type="checkbox" name="mec[settings][related_events]" <?php if(isset($settings['related_events']) and $settings['related_events']) echo 'checked="checked"'; ?> /><?php esc_html_e('Display related events based on taxonomy in single event page.', 'modern-events-calendar-lite'); ?>
                            </label>
                            <div id="mec_related_events_container_toggle" class="mec-col-8 <?php if((isset($settings['related_events']) and !$settings['related_events']) or !isset($settings['related_events'])) echo 'mec-util-hidden'; ?>">
                                <div class="mec-form-row">
                                    <label for="mec_settings_countdown_list"><?php esc_html_e('Select Taxonomies:', 'modern-events-calendar-lite'); ?></label>
                                    <label>
                                        <input type="hidden" name="mec[settings][related_events_basedon_category]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][related_events_basedon_category]" <?php if(isset($settings['related_events_basedon_category']) and $settings['related_events_basedon_category']) echo 'checked="checked"'; ?> /><?php esc_html_e('Category', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <?php if(!isset($settings['organizers_status']) || $settings['organizers_status']): ?>
                                    <label>
                                        <input type="hidden" name="mec[settings][related_events_basedon_organizer]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][related_events_basedon_organizer]" <?php if(isset($settings['related_events_basedon_organizer']) and $settings['related_events_basedon_organizer']) echo 'checked="checked"'; ?> /><?php esc_html_e('Organizer', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <?php endif; ?>
                                    <label>
                                        <input type="hidden" name="mec[settings][related_events_basedon_location]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][related_events_basedon_location]" <?php if(isset($settings['related_events_basedon_location']) and $settings['related_events_basedon_location']) echo 'checked="checked"'; ?> /><?php esc_html_e('Location', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <?php if(isset($settings['speakers_status']) and $settings['speakers_status']) : ?>
                                    <label>
                                        <input type="hidden" name="mec[settings][related_events_basedon_speaker]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][related_events_basedon_speaker]" <?php if(isset($settings['related_events_basedon_speaker']) and $settings['related_events_basedon_speaker']) echo 'checked="checked"'; ?> /><?php esc_html_e('Speaker', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <?php endif; ?>
                                    <label>
                                        <input type="hidden" name="mec[settings][related_events_basedon_label]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][related_events_basedon_label]" <?php if(isset($settings['related_events_basedon_label']) and $settings['related_events_basedon_label']) echo 'checked="checked"'; ?> /><?php esc_html_e('Label', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <label>
                                        <input type="hidden" name="mec[settings][related_events_basedon_tag]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][related_events_basedon_tag]" <?php if(isset($settings['related_events_basedon_tag']) and $settings['related_events_basedon_tag']) echo 'checked="checked"'; ?> /><?php esc_html_e('Tag', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mec-form-row">
                            <label style="margin-top: 10px;">
                                <input type="hidden" name="mec[settings][next_previous_events]" value="0" />
                                <input onchange="jQuery('#mec_next_previous_events_container_toggle').toggle();" value="1" type="checkbox" name="mec[settings][next_previous_events]" <?php if(isset($settings['next_previous_events']) and $settings['next_previous_events']) echo 'checked="checked"'; ?> /><?php esc_html_e('Display next / previous events based on taxonomy in single event page.', 'modern-events-calendar-lite'); ?>
                            </label>
                            <div id="mec_next_previous_events_container_toggle" class="mec-col-8 <?php if((isset($settings['next_previous_events']) and !$settings['next_previous_events']) or !isset($settings['next_previous_events'])) echo 'mec-util-hidden'; ?>">

                                <div class="mec-form-row">
                                    <label for="mec_settings_countdown_list"><?php esc_html_e('Select Taxonomies:', 'modern-events-calendar-lite'); ?></label>
                                    <label>
                                        <input type="hidden" name="mec[settings][next_previous_events_category]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][next_previous_events_category]" <?php if(isset($settings['next_previous_events_category']) and $settings['next_previous_events_category']) echo 'checked="checked"'; ?> /><?php esc_html_e('Category', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <label>
                                        <input type="hidden" name="mec[settings][next_previous_events_organizer]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][next_previous_events_organizer]" <?php if(isset($settings['next_previous_events_organizer']) and $settings['next_previous_events_organizer']) echo 'checked="checked"'; ?> /><?php esc_html_e('Organizer', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <label>
                                        <input type="hidden" name="mec[settings][next_previous_events_location]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][next_previous_events_location]" <?php if(isset($settings['next_previous_events_location']) and $settings['next_previous_events_location']) echo 'checked="checked"'; ?> /><?php esc_html_e('Location', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <?php if(isset($settings['speakers_status']) and $settings['speakers_status']) : ?>
                                    <label>
                                        <input type="hidden" name="mec[settings][next_previous_events_speaker]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][next_previous_events_speaker]" <?php if(isset($settings['next_previous_events_speaker']) and $settings['next_previous_events_speaker']) echo 'checked="checked"'; ?> /><?php esc_html_e('Speaker', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <?php endif; ?>
                                    <label>
                                        <input type="hidden" name="mec[settings][next_previous_events_label]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][next_previous_events_label]" <?php if(isset($settings['next_previous_events_label']) and $settings['next_previous_events_label']) echo 'checked="checked"'; ?> /><?php esc_html_e('Label', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <label>
                                        <input type="hidden" name="mec[settings][next_previous_events_tag]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][next_previous_events_tag]" <?php if(isset($settings['next_previous_events_tag']) and $settings['next_previous_events_tag']) echo 'checked="checked"'; ?> /><?php esc_html_e('Tag', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="mec-step-wizard-content" data-step="4">
                    <form id="mec_save_single_form">
                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                        <div class="mec-form-row">
                            <div class="mec-col-12">
                                <label for="mec_settings_speakers_status">
                                    <input type="hidden" name="mec[settings][speakers_status]" value="0" />
                                    <input type="checkbox" name="mec[settings][speakers_status]" id="mec_settings_speakers_status" <?php echo ((isset($settings['speakers_status']) and $settings['speakers_status']) ? 'checked="checked"' : ''); ?> value="1" />
                                    <?php esc_html_e('Enable speakers feature', 'modern-events-calendar-lite'); ?>
                                    <span class="mec-tooltip">
                                        <div class="box">
                                            <h5 class="title"><?php esc_html_e('Speakers', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("Enable this option to have speaker in Hourly Schedule in Single. Refresh after enabling it to see the Speakers menu under MEC dashboard.", 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/speaker/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="mec-form-row">
                            <label class="mec-col-8">
                                <input type="hidden" name="mec[settings][export_module_status]" value="0" />
                                <input onchange="jQuery('#mec_export_module_options_container_toggle').toggle();" value="1" type="checkbox" name="mec[settings][export_module_status]" <?php if(isset($settings['export_module_status']) and $settings['export_module_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Show export module (iCal export and add to Google calendars) on event page', 'modern-events-calendar-lite'); ?>
                            </label>
                        </div>
                        <div class="mec-form-row">
                            <div id="mec_export_module_options_container_toggle" class="<?php if((isset($settings['export_module_status']) and !$settings['export_module_status']) or !isset($settings['export_module_status'])) echo 'mec-util-hidden'; ?>">
                                <div class="mec-form-row">
                                    <ul id="mec_export_module_options" class="mec-form-row">
                                        <?php
                                        $event_options = array('googlecal'=>__('Google Calendar', 'modern-events-calendar-lite'), 'ical'=>__('iCal', 'modern-events-calendar-lite'));
                                        foreach($event_options as $event_key=>$event_option): ?>
                                        <li id="mec_sn_<?php echo esc_attr($event_key); ?>" data-id="<?php echo esc_attr($event_key); ?>" class="mec-form-row mec-switcher <?php echo ((isset($settings['sn'][$event_key]) and $settings['sn'][$event_key]) ? 'mec-enabled' : 'mec-disabled'); ?>">
                                            <label class="mec-col-3"><?php echo esc_html($event_option); ?></label>
                                            <div class="mec-col-9">
                                                <input class="mec-status" type="hidden" name="mec[settings][sn][<?php echo esc_attr($event_key); ?>]" value="<?php echo (isset($settings['sn'][$event_key]) ? esc_attr($settings['sn'][$event_key]) : '1'); ?>" />
                                                <label for="mec[settings][sn][<?php echo esc_attr($event_key); ?>]"></label>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <?php if($this->getPRO()) : ?>
                <div class="mec-step-wizard-content" data-step="5">
                    <form id="mec_save_booking_form">
                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                        <div class="mec-form-row">
                            <label>
                                <input type="hidden" name="mec[settings][booking_status]" value="0" />
                                <input onchange="jQuery('#mec_booking_container_toggle').toggle();" value="1" type="checkbox" name="mec[settings][booking_status]" <?php if(isset($settings['booking_status']) and $settings['booking_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable booking module', 'modern-events-calendar-lite'); ?>
                            </label>
                        </div>
                        <div id="mec_booking_container_toggle" class="<?php if((isset($settings['booking_status']) and !$settings['booking_status']) or !isset($settings['booking_status'])) echo 'mec-util-hidden'; ?>">
                            <div class="mec-form-row">
                                <label class="mec-col-2" for="mec_settings_booking_date_selection"><?php esc_html_e('Date Selection', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-4">
                                    <select id="mec_settings_booking_date_selection" name="mec[settings][booking_date_selection]">
                                        <option value="dropdown" <?php echo ((!isset($settings['booking_date_selection']) || $settings['booking_date_selection'] == 'dropdown') ? 'selected="selected"' : ''); ?>><?php esc_html_e('Dropdown', 'modern-events-calendar-lite'); ?></option>
                                        <option value="calendar" <?php echo ((isset($settings['booking_date_selection']) && $settings['booking_date_selection'] == 'calendar') ? 'selected="selected"' : ''); ?>><?php esc_html_e('Calendar', 'modern-events-calendar-lite'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <label class="mec-col-2" for="mec_settings_booking_registration"><?php esc_html_e('Registration', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-4">
                                    <select id="mec_settings_booking_registration" name="mec[settings][booking_registration]">
                                        <option <?php echo isset($settings['booking_registration']) && $settings['booking_registration'] == '1' ? 'selected="selected"' : ''; ?> value="1"><?php echo esc_html__('Enabled (Main Attendee)', 'modern-events-calendar-lite'); ?></option>
                                        <option <?php echo isset($settings['booking_registration']) && $settings['booking_registration'] == '2' ? 'selected="selected"' : ''; ?> value="2"><?php echo esc_html__('Enabled (All Attendees)', 'modern-events-calendar-lite'); ?></option>
                                        <option <?php echo isset($settings['booking_registration']) && $settings['booking_registration'] == '0' ? 'selected="selected"' : ''; ?> value="0"><?php echo esc_html__('Disabled', 'modern-events-calendar-lite'); ?></option>
                                    </select>
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Registration', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e("If enabled MEC would create a WordPress User for main attendees. It's recommended to keep it enabled.", 'modern-events-calendar-lite'); ?></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label for="mec_settings_booking_auto_verify_free">
                                        <input type="hidden" name="mec[settings][booking_auto_verify_free]" value="0" />
                                        <input type="checkbox" name="mec[settings][booking_auto_verify_free]" id="mec_settings_booking_auto_verify_free" <?php echo ((isset($settings['booking_auto_verify_free']) and $settings['booking_auto_verify_free'] == '1') ? 'checked="checked"' : ''); ?> value="1" />
                                        <?php esc_html_e('Auto verification for free bookings', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label for="mec_settings_booking_auto_verify_paid">
                                        <input type="hidden" name="mec[settings][booking_auto_verify_paid]" value="0" />
                                        <input type="checkbox" name="mec[settings][booking_auto_verify_paid]" id="mec_settings_booking_auto_verify_paid" <?php echo ((isset($settings['booking_auto_verify_paid']) and $settings['booking_auto_verify_paid'] == '1') ? 'checked="checked"' : ''); ?> value="1" />
                                        <?php esc_html_e('Auto verification for paid bookings', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label for="mec_settings_booking_auto_confirm_free">
                                        <input type="hidden" name="mec[settings][booking_auto_confirm_free]" value="0" />
                                        <input type="checkbox" name="mec[settings][booking_auto_confirm_free]" id="mec_settings_booking_auto_confirm_free" <?php echo ((isset($settings['booking_auto_confirm_free']) and $settings['booking_auto_confirm_free'] == '1') ? 'checked="checked"' : ''); ?> value="1" />
                                        <?php esc_html_e('Auto confirmation for free bookings', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label for="mec_settings_booking_auto_confirm_paid">
                                        <input type="hidden" name="mec[settings][booking_auto_confirm_paid]" value="0" />
                                        <input type="checkbox" name="mec[settings][booking_auto_confirm_paid]" id="mec_settings_booking_auto_confirm_paid" <?php echo ((isset($settings['booking_auto_confirm_paid']) and $settings['booking_auto_confirm_paid'] == '1') ? 'checked="checked"' : ''); ?> value="1" />
                                        <?php esc_html_e('Auto confirmation for paid bookings', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="mec-step-wizard-content" data-step="6">
                    <form id="mec_save_styling_form">
                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                        <div class="mec-form-row">
                            <div class="mec-col-3">
                                <label><?php esc_html_e('Custom Color Skin', 'modern-events-calendar-lite'); ?></label>
                            </div>
                            <div class="mec-col-6">
                                <input type="text" class="wp-color-picker-field" id="mec_settings_color" name="mec[styling][color]" value="<?php echo (isset($styling['color']) ? esc_attr($styling['color']) : ''); ?>" data-default-color="" />
                            </div>
                            <div class="mec-col-12">
                                <p><?php esc_attr_e("If you want to select a predefined color skin, you must clear the color of this item", 'modern-events-calendar-lite'); ?></p>
                            </div>
                        </div>
                        <div class="mec-form-row">
                            <div class="mec-col-12">
                                <label><?php esc_html_e('Predefined Color Skin', 'modern-events-calendar-lite'); ?></label>
                            </div>
                            <div class="mec-col-6">
                                <ul class="mec-image-select-wrap">
                                    <?php
                                        $colorskins = array(
                                            '#40d9f1'=>'mec-colorskin-1',
                                            '#0093d0'=>'mec-colorskin-2',
                                            '#e53f51'=>'mec-colorskin-3',
                                            '#f1c40f'=>'mec-colorskin-4',
                                            '#e64883'=>'mec-colorskin-5',
                                            '#45ab48'=>'mec-colorskin-6',
                                            '#9661ab'=>'mec-colorskin-7',
                                            '#0aad80'=>'mec-colorskin-8',
                                            '#0ab1f0'=>'mec-colorskin-9',
                                            '#ff5a00'=>'mec-colorskin-10',
                                            '#c3512f'=>'mec-colorskin-11',
                                            '#55606e'=>'mec-colorskin-12',
                                            '#fe8178'=>'mec-colorskin-13',
                                            '#7c6853'=>'mec-colorskin-14',
                                            '#bed431'=>'mec-colorskin-15',
                                            '#2d5c88'=>'mec-colorskin-16',
                                            '#77da55'=>'mec-colorskin-17',
                                            '#2997ab'=>'mec-colorskin-18',
                                            '#734854'=>'mec-colorskin-19',
                                            '#a81010'=>'mec-colorskin-20',
                                            '#4ccfad'=>'mec-colorskin-21',
                                            '#3a609f'=>'mec-colorskin-22',
                                            '#333333'=>'mec-colorskin-23',
                                            '#D2D2D2'=>'mec-colorskin-24',
                                            '#636363'=>'mec-colorskin-25',
                                        );

                                        foreach($colorskins as $colorskin=>$values): ?>
                                        <li class="mec-image-select">
                                            <label for="<?php echo esc_attr($values); ?>">
                                                <input type="radio" id="<?php echo esc_attr($values); ?>" name="mec[styling][mec_colorskin]" value="<?php echo esc_attr($colorskin); ?>" <?php if(isset($styling['mec_colorskin']) && ($styling['mec_colorskin'] == $colorskin)) echo 'checked="checked"'; ?>>
                                                <span class="<?php echo esc_attr($values); ?>"></span>
                                            </label>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="mec-step-wizard-content" data-step="5">
                    <form id="mec_save_styling_form">
                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                        <div class="mec-form-row">
                            <div class="mec-col-3">
                                <span><?php esc_html_e('Custom Color Skin', 'modern-events-calendar-lite'); ?></span>
                            </div>
                            <div class="mec-col-6">
                                <input type="text" class="wp-color-picker-field" id="mec_settings_color" name="mec[styling][color]" value="<?php echo (isset($styling['color']) ? esc_attr($styling['color']) : ''); ?>" data-default-color="" />
                            </div>
                            <div class="mec-col-6">
                                <p><?php esc_attr_e("If you want to select a predefined color skin, you must clear the color of this item", 'modern-events-calendar-lite'); ?></p>
                            </div>
                        </div>
                        <div class="mec-form-row">
                            <div class="mec-col-3">
                                <span><?php esc_html_e('Predefined Color Skin', 'modern-events-calendar-lite'); ?></span>
                            </div>
                            <div class="mec-col-6">
                                <ul class="mec-image-select-wrap">
                                    <?php
                                    $colorskins = array(
                                        '#40d9f1'=>'mec-colorskin-1',
                                        '#0093d0'=>'mec-colorskin-2',
                                        '#e53f51'=>'mec-colorskin-3',
                                        '#f1c40f'=>'mec-colorskin-4',
                                        '#e64883'=>'mec-colorskin-5',
                                        '#45ab48'=>'mec-colorskin-6',
                                        '#9661ab'=>'mec-colorskin-7',
                                        '#0aad80'=>'mec-colorskin-8',
                                        '#0ab1f0'=>'mec-colorskin-9',
                                        '#ff5a00'=>'mec-colorskin-10',
                                        '#c3512f'=>'mec-colorskin-11',
                                        '#55606e'=>'mec-colorskin-12',
                                        '#fe8178'=>'mec-colorskin-13',
                                        '#7c6853'=>'mec-colorskin-14',
                                        '#bed431'=>'mec-colorskin-15',
                                        '#2d5c88'=>'mec-colorskin-16',
                                        '#77da55'=>'mec-colorskin-17',
                                        '#2997ab'=>'mec-colorskin-18',
                                        '#734854'=>'mec-colorskin-19',
                                        '#a81010'=>'mec-colorskin-20',
                                        '#4ccfad'=>'mec-colorskin-21',
                                        '#3a609f'=>'mec-colorskin-22',
                                        '#333333'=>'mec-colorskin-23',
                                        '#D2D2D2'=>'mec-colorskin-24',
                                        '#636363'=>'mec-colorskin-25',
                                    );

                                    foreach($colorskins as $colorskin=>$values): ?>
                                    <li class="mec-image-select">
                                        <label for="<?php echo esc_attr($values); ?>">
                                            <input type="radio" id="<?php echo esc_attr($values); ?>" name="mec[styling][mec_colorskin]" value="<?php echo esc_attr($colorskin); ?>" <?php if(isset($styling['mec_colorskin']) && ($styling['mec_colorskin'] == $colorskin)) echo 'checked="checked"'; ?>>
                                            <span class="<?php echo esc_attr($values); ?>"></span>
                                        </label>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <div class="mec-next-previous-buttons">
                <button class="mec-button-prev mec-hide-button"><?php esc_html_e('Prev', 'modern-events-calendar-lite'); ?><img src="<?php echo plugin_dir_url(__FILE__ ) . '../../../assets/img/popup/popup-prev-icon.svg'; ?>" /></button>
                <a class="mec-button-dashboard mec-hide-button" href="<?php echo admin_url('/admin.php?page=mec-intro'); ?>"><?php esc_html_e('Go to Dashboard', 'modern-events-calendar-lite'); ?><img src="<?php echo plugin_dir_url(__FILE__ ) . '../../../assets/img/popup/popup-next-icon.svg'; ?>" /></a>
                <button class="mec-button-skip"><?php esc_html_e('Skip', 'modern-events-calendar-lite'); ?><img src="<?php echo plugin_dir_url(__FILE__ ) . '../../../assets/img/popup/popup-next-icon.svg'; ?>" /></button>
                <button class="mec-button-next"><?php esc_html_e('Save & Next', 'modern-events-calendar-lite'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php
$this->getFactory()->params('footer', function()
{
?>
    <script>
    jQuery(document).ready(function($)
    {
        function import_loading($value)
        {
            if ($value) {
                jQuery('.mec-wizard-wrap .mec-wizard-loading').show()
            } else {
                jQuery('.mec-wizard-wrap .mec-wizard-loading').hide()
            }
        }

        function save_loading($value)
        {
            if ($value) {
                jQuery('.mec-setup-wizard-wrap .mec-wizard-loading').show()
            } else {
                jQuery('.mec-setup-wizard-wrap .mec-wizard-loading').hide()
            }
        }

        var $setting_wrap = jQuery(".mec-setup-wizard-wrap");
        var $se_prev = $setting_wrap.find('.mec-button-prev');
        var $se_next = $setting_wrap.find('.mec-button-next');

        $se_next.on("click", function(e)
        {
            e.preventDefault();
            var active_step = jQuery(".mec-step-wizard-content.mec-active-step").attr("data-step")
            var next_step = Number(active_step) + 1;

            <?php if($this->getPRO()) : ?>
            if ( active_step !== '6' ) {
                $se_prev.removeClass("mec-hide-button");
                jQuery(".mec-step-wizard-content").removeClass("mec-active-step");
                jQuery(".mec-step-wizard-content[data-step=" + next_step + "]").addClass("mec-active-step");
                jQuery(".mec-step-" + next_step ).addClass("mec-step-passed");
            }
            if ( next_step == 6 ) {
                jQuery(".mec-button-skip").addClass("mec-hide-button");
            }
            // if ( next_step == 6 ) {
            //     jQuery(".mec-button-dashboard").removeClass("mec-hide-button");
            // }
            <?php else: ?>
            if ( active_step !== '5' ) {
                $se_prev.removeClass("mec-hide-button");
                jQuery(".mec-step-wizard-content").removeClass("mec-active-step");
                jQuery(".mec-step-wizard-content[data-step=" + next_step + "]").addClass("mec-active-step");
                jQuery(".mec-step-" + next_step ).addClass("mec-step-passed");
            }
            if ( next_step == 5 ) {
                jQuery(".mec-button-skip").addClass("mec-hide-button");
            }
            // if ( next_step == 5 ) {
            //     jQuery(".mec-button-dashboard").removeClass("mec-hide-button");
            // }
            <?php endif; ?>

            if ( active_step === '1' ) {
                save_step_1();
            }
            if ( active_step === '2' ) {
                save_step_2();
            }
            if ( active_step === '3' ) {
                save_step_3();
            }
            if ( active_step === '4' ) {
                save_step_4();
            }
            if ( active_step === '5' ) {
                save_step_5();
            }
            if ( active_step === '6' ) {
                save_step_6();
            }
        });

        jQuery(".mec-button-skip").on("click", function(e)
        {
            e.preventDefault();
            var active_step = jQuery(".mec-step-wizard-content.mec-active-step").attr("data-step")
            var next_step = Number(active_step) + 1;
            $se_prev.removeClass("mec-hide-button");
            jQuery(".mec-step-wizard-content").removeClass("mec-active-step");
            jQuery(".mec-step-wizard-content[data-step=" + next_step + "]").addClass("mec-active-step");
            jQuery(".mec-step-" + next_step ).addClass("mec-step-passed");

            <?php if($this->getPRO()) : ?>
            if ( next_step == 6 ) {
                jQuery(".mec-button-skip").addClass("mec-hide-button");
            }
            // if ( next_step == 6 ) {
            //     jQuery(".mec-button-dashboard").removeClass("mec-hide-button");
            // }
            <?php else: ?>
            if ( next_step == 5 ) {
                jQuery(".mec-button-skip").addClass("mec-hide-button");
            }
            // if ( next_step == 5 ) {
            //     jQuery(".mec-button-dashboard").removeClass("mec-hide-button");
            // }
            <?php endif; ?>
        });

        $se_prev.on("click", function(e)
        {
            e.preventDefault();
            var active_step = jQuery(".mec-step-wizard-content.mec-active-step").attr("data-step")
            var next_step = Number(active_step) - 1;
            jQuery(".mec-step-wizard-content").removeClass("mec-active-step");
            jQuery(".mec-step-wizard-content[data-step=" + next_step + "]").addClass("mec-active-step");
            jQuery(".mec-step-" + active_step ).removeClass("mec-step-passed");

            <?php if($this->getPRO()) : ?>
            if ( next_step != 6 ) {
                $se_next.removeClass("mec-hide-button");
                jQuery(".mec-button-skip").removeClass("mec-hide-button");
                //jQuery(".mec-button-dashboard").addClass("mec-hide-button");
            }
            <?php else: ?>
            if ( next_step != 5 ) {
                $se_next.removeClass("mec-hide-button");
                jQuery(".mec-button-skip").removeClass("mec-hide-button");
                //jQuery(".mec-button-dashboard").addClass("mec-hide-button");
            }
            <?php endif; ?>

            if ( next_step == 1 ) {
                $se_prev.addClass("mec-hide-button");
            }
        });

        jQuery(".mec-button-import-events").click(function()
        {
            if(confirm("Are you sure you want to import events?")){
                jQuery.ajax(
                {
                    type: "POST",
                    url: ajaxurl,
                    data: "action=wizard_import_dummy_events",
                    beforeSend: function () {
                        import_loading(true)
                    },
                    success: function(data)
                    {
                        import_loading(false)
                    },
                    error: function(jqXHR, textStatus, errorThrown)
                    {
                        console.log('error');
                    }
                });
            }
            else{
                return false;
            }
        });

        jQuery(".mec-button-import-shortcodes").click(function()
        {
            if(confirm("Are you sure you want to import shortcodes?"))
            {
                jQuery.ajax(
                {
                    type: "POST",
                    url: ajaxurl,
                    data: "action=wizard_import_dummy_shortcodes",
                    beforeSend: function () {
                        import_loading(true)
                    },
                    success: function(data)
                    {

                        import_loading(false)
                    },
                    error: function(jqXHR, textStatus, errorThrown)
                    {
                        console.log('error');
                    }
                });
            }
            else
            {
                return false;
            }
        });

        var archive_value = jQuery('#mec_settings_default_skin_archive').val();
        function mec_archive_skin_style_changed(archive_value)
        {
            jQuery('.mec-archive-skins').hide();
            jQuery('.mec-archive-skins.mec-archive-'+archive_value+'-skins').show();
        }
        mec_archive_skin_style_changed(archive_value);

        jQuery(document).ready(function()
        {
            //Initiate Color Picker
            jQuery('.wp-color-picker-field').wpColorPicker();
        });

        function save_step_1()
        {
            var settings = jQuery("#mec_save_weekdays_form").serialize();
            jQuery.ajax(
            {
                type: "POST",
                url: ajaxurl,
                data: "action=wizard_save_weekdays&"+settings,
                beforeSend: function () {
                    save_loading(true)
                },
                success: function(data)
                {
                    save_loading(false)
                },
                error: function(jqXHR, textStatus, errorThrown)
                {
                    console.log("error");
                }
            });
        }

        function save_step_2()
        {
            var settings = jQuery("#mec_save_slug_form").serialize();
            jQuery.ajax(
            {
                type: "POST",
                url: ajaxurl,
                data: "action=wizard_save_slug&"+settings,
                beforeSend: function () {
                    save_loading(true)
                },
                success: function(data)
                {
                    save_loading(false)
                },
                error: function(jqXHR, textStatus, errorThrown)
                {
                    console.log("error");
                }
            });
        }

        function save_step_3()
        {
            var settings = jQuery("#mec_save_module_form").serialize();
            jQuery.ajax(
            {
                type: "POST",
                url: ajaxurl,
                data: "action=wizard_save_module&"+settings,
                beforeSend: function () {
                    save_loading(true)
                },
                success: function(data)
                {
                    save_loading(false)
                },
                error: function(jqXHR, textStatus, errorThrown)
                {
                    console.log("error");
                }
            });
        }

        function save_step_4()
        {
            var settings = jQuery("#mec_save_single_form").serialize();
            jQuery.ajax(
            {
                type: "POST",
                url: ajaxurl,
                data: "action=wizard_save_single&"+settings,
                beforeSend: function () {
                    save_loading(true)
                },
                success: function(data)
                {
                    save_loading(false)
                },
                error: function(jqXHR, textStatus, errorThrown)
                {
                    console.log("error");
                }
            });
        }

        function save_step_5()
        {
            var settings = jQuery("#mec_save_booking_form").serialize();
            jQuery.ajax(
            {
                type: "POST",
                url: ajaxurl,
                data: "action=wizard_save_booking&"+settings,
                beforeSend: function () {
                    save_loading(true)
                },
                success: function(data)
                {
                    save_loading(false)
                },
                error: function(jqXHR, textStatus, errorThrown)
                {
                    console.log("error");
                }
            });
        }

        function save_step_6()
        {
            var settings = jQuery("#mec_save_styling_form").serialize();
            jQuery.ajax(
            {
                type: "POST",
                url: ajaxurl,
                data: "action=wizard_save_styling&"+settings,
                beforeSend: function () {
                    save_loading(true)
                },
                success: function(data)
                {
                    window.location.replace('<?php echo admin_url('/admin.php?page=mec-intro'); ?>')
                },
                error: function(jqXHR, textStatus, errorThrown)
                {
                    console.log("error");
                }
            });
        }

        jQuery('.mec-wizard-open-popup.mec-settings').on('click', function(e)
        {
            e.preventDefault();

            if(jQuery(".mec-wizard-open-popup.mec-settings").length > 0 )
            {
                jQuery(".mec-wizard-open-popup.mec-settings").addClass("active")
                jQuery(".mec-wizard-open-popup.add-event").removeClass("active")
                jQuery(".mec-wizard-open-popup.add-shortcode").removeClass("active")
                jQuery(".mec-wizard-starter-video a").removeClass("active")
            }

            // Open Lightbox
            lity('.mec-setup-wizard-wrap');
        });

        jQuery(document).on('lity:open', function(event, instance)
        {
            if ( jQuery(".mec-wizard-open-popup.mec-settings").hasClass("active") ) {
                jQuery('.lity').addClass('mec-settings');
            }

            if ( jQuery(".mec-wizard-starter-video a").hasClass("active") ) {
                jQuery('.lity').addClass('wizard-video');
            }
        });

        jQuery('.mec-wizard-starter-video a').on('click', function(e)
        {
            e.preventDefault();

            if(jQuery(".mec-wizard-starter-video a").length > 0 )
            {
                jQuery(".mec-wizard-starter-video a").addClass("active")
                jQuery(".mec-wizard-open-popup.mec-settings").removeClass("active")
                jQuery(".mec-wizard-open-popup.add-event").removeClass("active")
                jQuery(".mec-wizard-open-popup.add-shortcode").removeClass("active")
            }

            // Open Lightbox
            lity('https://www.youtube.com/embed/FV_X341oyiw');
        });
    });
    </script>
    <?php
});

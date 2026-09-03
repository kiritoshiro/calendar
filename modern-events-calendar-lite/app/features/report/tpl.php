<?php
/** no direct access **/
defined('MECEXEC') or die();

/** @var MEC_feature_report $this */

$tab = $_GET['tab'] ?? 'selective';
$current_event_id = $_GET['event_id'] ?? 0;

$events = $this->main->get_events(-1, ['pending', 'draft', 'future', 'publish']);
$date_format = get_option('date_format');

$styling = $this->main->get_styling();
$dark_mode = $styling['dark_mode'] ?? '';

$logo = plugin_dir_url(__FILE__ ) . '../../../assets/img/mec-logo-w.png';
if($dark_mode == 1) $logo = plugin_dir_url(__FILE__ ) . '../../../assets/img/mec-logo-w2.png';
?>
<div class="wrap" id="mec-wrap">
    <h1><?php echo esc_html__('Booking Report', 'modern-events-calendar-lite'); ?></h1>
    <div class="welcome-content w-clearfix extra">
        <div class="mec-report-wrap">
            <div class="nav-tab-wrapper">
                <a href="<?php echo esc_url($this->main->add_qs_var('tab', 'selective')); ?>" class="nav-tab <?php echo $tab === 'selective' ? 'nav-tab-active mec-tab-active' : ''; ?>"><?php esc_html_e('Selective Email', 'modern-events-calendar-lite'); ?></a>
                <a href="<?php echo esc_url($this->main->add_qs_var('tab', 'mass')); ?>" class="nav-tab <?php echo $tab === 'mass' ? 'nav-tab-active mec-tab-active' : ''; ?>"><?php esc_html_e('Mass Email', 'modern-events-calendar-lite'); ?></a>
                <a href="<?php echo esc_url($this->main->add_qs_var('tab', 'export_purge')); ?>" class="nav-tab <?php echo $tab === 'export_purge' ? 'nav-tab-active mec-tab-active' : ''; ?>"><?php esc_html_e('Export & Purge', 'modern-events-calendar-lite'); ?></a>
            </div>

            <div class="mec-container booking-report-container">
                <?php if($tab === 'mass'): ?>
                <h3><?php esc_html_e('Mass Email', 'modern-events-calendar-lite'); ?></h3>
                <p><?php echo esc_html__('Using this section, you can select all the attendees by event and offer them a new event.', 'modern-events-calendar-lite'); ?></p>
                <div class="mec-report-select-event-wrap">
                    <div class="w-row">
                        <div class="w-col-sm-12">
                            <?php if(count($events)): ?>
                            <form method="post" id="mec_report_mass_action_form">
                                <ul>
                                    <?php
                                        foreach($events as $event)
                                        {
                                            $id = $event->ID;
                                            if($this->main->get_original_event($id) !== $id) $id = $this->main->get_original_event($id);

                                            $sold_tickets = $this->getBook()->get_all_sold_tickets($id);
                                            echo '<li class="mec-form-row"><label><input type="checkbox" name="events[]" value="'.esc_attr($id).'" class="mec-report-events">' . sprintf(esc_html__('%s (%s sold tickets)', 'modern-events-calendar-lite'), $event->post_title, $sold_tickets) . '</label></li>';
                                        }
                                    ?>
                                </ul>
                                <hr>
                                <div>
                                    <div class="mec-form-row">
                                        <label>
                                            <input  type="radio" name="task" value="suggest" onchange="jQuery('#mec_report_suggest_new_event_options').toggleClass('w-hidden')">
                                            <span><?php esc_html_e('Suggest another event', 'modern-events-calendar-lite'); ?></span>
                                        </label>
                                    </div>
                                    <div id="mec_report_suggest_new_event_options" class="w-hidden" style="margin-top: 20px;">
                                        <div class="mec-form-row">
                                            <div class="mec-col-2">
                                                <label for="mec_new_event"><?php esc_html_e('New Event', 'modern-events-calendar-lite'); ?></label>
                                            </div>
                                            <div class="mec-col-10">
                                                <select style="margin-top: 0;" name="new_event" id="mec_new_event">
                                                    <option value="">-----</option>
                                                    <?php foreach($events as $event): ?>
                                                        <option value="<?php echo esc_attr($event->ID); ?>"><?php echo $event->post_title; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <?php wp_nonce_field('mec_report_mass'); ?>
                                    <input type="hidden" name="action" value="mec_report_mass">
                                    <button class="button mec-button-primary" type="submit"><?php esc_html_e('Send', 'modern-events-calendar-lite'); ?></button>
                                </div>
                                <div id="mec_report_mass_message"></div>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php elseif($tab === 'selective'): ?>
                <h3><?php esc_html_e('Selective Email', 'modern-events-calendar-lite'); ?></h3>
                <p><?php echo esc_html__('Using this section, you can see the list of participant attendees by the order of date.', 'modern-events-calendar-lite'); ?></p>
                <div class="mec-report-select-event-wrap">
                    <div class="w-row">
                        <div class="w-col-sm-12">
                            <select name="mec-report-event-id" class="mec-reports-selectbox mec-reports-selectbox-event">
                                <option value="none"><?php echo esc_html__( 'Select event' , 'modern-events-calendar-lite'); ?></option>
                                <?php 
                                    if(count($events))
                                    {
                                        foreach($events as $event)
                                        {
                                            $id = $event->ID;
                                            if($this->main->get_original_event($id) !== $id) $id = $this->main->get_original_event($id);

                                            $start_date = get_post_meta($id, 'mec_start_date', true);

                                            echo '<option value="'.esc_attr($id).'" '.(($current_event_id == $id) ? 'selected' : '').'>' . sprintf(esc_html__('%s (from %s)', 'modern-events-calendar-lite'), $event->post_title, date($date_format, strtotime($start_date))) . '</option>';
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mec-report-sendmail-wrap"><div class="w-row"><div class="w-col-sm-12"></div></div></div>
                <div class="mec-report-backtoselect-wrap"><div class="w-row"><div class="w-col-sm-12"><button><?php echo esc_html__('Back to list', 'modern-events-calendar-lite'); ?></button></div></div></div>
                <div class="mec-report-selected-event-attendees-wrap"><div class="w-row"><div class="w-col-sm-12"></div></div></div>
                <div class="mec-report-sendmail-form-wrap">
                    <div class="w-row">
                        <div class="w-col-sm-12">
                            <?php $send_email_label = esc_html__('Send Email', 'modern-events-calendar-lite'); ?>
                            <div class="mec-send-email-form-wrap">
                                <h2><?php echo esc_html__('Bulk Email', 'modern-events-calendar-lite'); ?></h2>
                                <h4 class="mec-send-email-count"><?php echo sprintf(esc_html__('You are sending email to %s attendees', 'modern-events-calendar-lite'), '<span>0</span>'); ?></h4>
                                <input type="text" class="widefat" id="mec-send-email-subject" placeholder="<?php echo esc_html__('Email Subject', 'modern-events-calendar-lite'); ?>"/><br><br>
                                <div id="mec-send-email-editor-wrap"></div>
                                <br>
                                <label><input type="checkbox" id="mec-send-admin-copy" value="1"><?php echo esc_html__('Send a copy to admin', 'modern-events-calendar-lite'); ?></label>
                                <br><br><p class="description"><?php echo esc_html__('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                <ul>
                                    <li><span>%%name%%</span>: <?php echo esc_html__('Attendee Name', 'modern-events-calendar-lite'); ?></li>
                                </ul>
                                <div id="mec-send-email-message" class="mec-util-hidden mec-error"></div>
                                <input type="hidden" id="mec-send-email-label" value="<?php echo esc_attr($send_email_label); ?>" />
                                <input type="hidden" id="mec-send-email-label-loading" value="<?php echo esc_attr__('Loading...', 'modern-events-calendar-lite'); ?>" />
                                <input type="hidden" id="mec-send-email-success" value="<?php echo esc_attr__('Emails successfully sent', 'modern-events-calendar-lite'); ?>" />
                                <input type="hidden" id="mec-send-email-no-user-selected" value="<?php echo esc_attr__('No user selected!', 'modern-events-calendar-lite'); ?>" />
                                <input type="hidden" id="mec-send-email-empty-subject" value="<?php echo esc_attr__('Email subject cannot be empty!', 'modern-events-calendar-lite'); ?>" />
                                <input type="hidden" id="mec-send-email-empty-content" value="<?php echo esc_attr__('Email content cannot be empty!', 'modern-events-calendar-lite'); ?>" />
                                <input type="hidden" id="mec-send-email-error" value="<?php echo esc_attr__('An error occurred. Please try again.', 'modern-events-calendar-lite'); ?>" />
                                <span class="mec-send-email-button"><?php echo esc_html($send_email_label); ?></span>
                            </div>
                            <?php wp_enqueue_editor(); ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <h3><?php esc_html_e('Export & Purge', 'modern-events-calendar-lite'); ?></h3>
                <?php if(isset($_GET['mec_export_purge_done'])): ?>
                    <?php
                        $cnt = isset($_GET['mec_export_purge_count']) ? intval($_GET['mec_export_purge_count']) : 0;
                        $url = isset($_GET['mec_export_purge_url']) ? esc_url_raw($_GET['mec_export_purge_url']) : '';
                        $url = $url ? esc_url_raw(urldecode($url)) : '';
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p>
                            <?php echo sprintf(esc_html__('%d rows exported. File saved and purge completed.', 'modern-events-calendar-lite'), $cnt); ?>
                            <?php if($url): ?>
                                <br><?php echo sprintf(esc_html__('Download: %s', 'modern-events-calendar-lite'), '<a href="'.esc_url($url).'" target="_blank">'.esc_html($url).'</a>'); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
                <p>
                    <?php echo esc_html__( 'Export all bookings before the cutoff date to a CSV file, email it to the recipients, and then delete those outdated booking entries.', 'modern-events-calendar-lite'); ?>
                    </br>
                    <?php echo esc_html__( 'WooCommerce orders will remain untouched.', 'modern-events-calendar-lite'); ?>
                </p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('mec_export_purge'); ?>
                    <input type="hidden" name="action" value="mec_export_purge" />
                    <div id="mec-export-purge-form" class="mec-export-purge-form">
                        <div class="mec-form-row">
                            <label for="mec_export_purge_cutoff" class="mec-col-3"><?php esc_html_e('Cutoff Date', 'modern-events-calendar-lite'); ?></label>
                            <div class="mec-col-9">
                                <input type="date" id="mec_export_purge_cutoff" class="mec-col-5" name="mec_export_purge_cutoff" required />
                                <p class="description"><?php esc_html_e('Bookings with start date before this date are included.', 'modern-events-calendar-lite'); ?></p>
                            </div>
                        </div>
                        <div class="mec-form-row">
                            <label for="mec_export_purge_emails" class="mec-col-3"><?php esc_html_e('Email Recipients', 'modern-events-calendar-lite'); ?></label>
                            <div class="mec-col-9">
                                <input type="text" id="mec_export_purge_emails" name="mec_export_purge_emails" class="regular-text mec-col-5" placeholder="admin@example.com, ops@example.com" />
                                <p class="description"><?php esc_html_e('Comma or semicolon separated email addresses to receive the CSV.', 'modern-events-calendar-lite'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="button mec-button-primary"><?php esc_html_e('Export and Purge', 'modern-events-calendar-lite'); ?></button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

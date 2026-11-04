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
    <h1><?php echo esc_html__('Booking Report', 'renginiu-kalendorius'); ?></h1>
    <div class="welcome-content w-clearfix extra">
        <div class="mec-report-wrap">
            <div class="nav-tab-wrapper">
                <a href="<?php echo esc_url($this->main->add_qs_var('tab', 'selective')); ?>" class="nav-tab <?php echo $tab === 'selective' ? 'nav-tab-active mec-tab-active' : ''; ?>"><?php esc_html_e('Selective Email', 'renginiu-kalendorius'); ?></a>
                <a href="<?php echo esc_url($this->main->add_qs_var('tab', 'mass')); ?>" class="nav-tab <?php echo $tab === 'mass' ? 'nav-tab-active mec-tab-active' : ''; ?>"><?php esc_html_e('Mass Email', 'renginiu-kalendorius'); ?></a>
            </div>

            <div class="mec-container booking-report-container">
                <?php if($tab === 'mass'): ?>
                <h3><?php esc_html_e('Mass Email', 'renginiu-kalendorius'); ?></h3>
                <p><?php echo esc_html__('Using this section, you can select all the attendees by event and offer them a new event.', 'renginiu-kalendorius'); ?></p>
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
                                            echo '<li class="mec-form-row"><label><input type="checkbox" name="events[]" value="'.esc_attr($id).'" class="mec-report-events">' . sprintf(esc_html__('%s (%s sold tickets)', 'renginiu-kalendorius'), $event->post_title, $sold_tickets) . '</label></li>';
                                        }
                                    ?>
                                </ul>
                                <hr>
                                <div>
                                    <div class="mec-form-row">
                                        <label>
                                            <input  type="radio" name="task" value="suggest" onchange="jQuery('#mec_report_suggest_new_event_options').toggleClass('w-hidden')">
                                            <span><?php esc_html_e('Suggest another event', 'renginiu-kalendorius'); ?></span>
                                        </label>
                                    </div>
                                    <div id="mec_report_suggest_new_event_options" class="w-hidden" style="margin-top: 20px;">
                                        <div class="mec-form-row">
                                            <div class="mec-col-2">
                                                <label for="mec_new_event"><?php esc_html_e('New Event', 'renginiu-kalendorius'); ?></label>
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
                                    <button class="button mec-button-primary" type="submit"><?php esc_html_e('Send', 'renginiu-kalendorius'); ?></button>
                                </div>
                                <div id="mec_report_mass_message"></div>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php elseif($tab === 'selective'): ?>
                <h3><?php esc_html_e('Selective Email', 'renginiu-kalendorius'); ?></h3>
                <p><?php echo esc_html__('Using this section, you can see the list of participant attendees by the order of date.', 'renginiu-kalendorius'); ?></p>
                <div class="mec-report-select-event-wrap">
                    <div class="w-row">
                        <div class="w-col-sm-12">
                            <select name="mec-report-event-id" class="mec-reports-selectbox mec-reports-selectbox-event">
                                <option value="none"><?php echo esc_html__( 'Select event' , 'renginiu-kalendorius'); ?></option>
                                <?php 
                                    if(count($events))
                                    {
                                        foreach($events as $event)
                                        {
                                            $id = $event->ID;
                                            if($this->main->get_original_event($id) !== $id) $id = $this->main->get_original_event($id);

                                            $start_date = get_post_meta($id, 'mec_start_date', true);

                                            echo '<option value="'.esc_attr($id).'" '.(($current_event_id == $id) ? 'selected' : '').'>' . sprintf(esc_html__('%s (from %s)', 'renginiu-kalendorius'), $event->post_title, date($date_format, strtotime($start_date))) . '</option>';
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mec-report-sendmail-wrap"><div class="w-row"><div class="w-col-sm-12"></div></div></div>
                <div class="mec-report-backtoselect-wrap"><div class="w-row"><div class="w-col-sm-12"><button><?php echo esc_html__('Back to list', 'renginiu-kalendorius'); ?></button></div></div></div>
                <div class="mec-report-selected-event-attendees-wrap"><div class="w-row"><div class="w-col-sm-12"></div></div></div>
                <div class="mec-report-sendmail-form-wrap">
                    <div class="w-row">
                        <div class="w-col-sm-12">
                            <?php $send_email_label = esc_html__('Send Email', 'renginiu-kalendorius'); ?>
                            <div class="mec-send-email-form-wrap">
                                <h2><?php echo esc_html__('Bulk Email', 'renginiu-kalendorius'); ?></h2>
                                <h4 class="mec-send-email-count"><?php echo sprintf(esc_html__('You are sending email to %s attendees', 'renginiu-kalendorius'), '<span>0</span>'); ?></h4>
                                <input type="text" class="widefat" id="mec-send-email-subject" placeholder="<?php echo esc_html__('Email Subject', 'renginiu-kalendorius'); ?>"/><br><br>
                                <div id="mec-send-email-editor-wrap"></div>
                                <br>
                                <label><input type="checkbox" id="mec-send-admin-copy" value="1"><?php echo esc_html__('Send a copy to admin', 'renginiu-kalendorius'); ?></label>
                                <br><br><p class="description"><?php echo esc_html__('You can use the following placeholders', 'renginiu-kalendorius'); ?></p>
                                <ul>
                                    <li><span>%%name%%</span>: <?php echo esc_html__('Attendee Name', 'renginiu-kalendorius'); ?></li>
                                </ul>
                                <div id="mec-send-email-message" class="mec-util-hidden mec-error"></div>
                                <input type="hidden" id="mec-send-email-label" value="<?php echo esc_attr($send_email_label); ?>" />
                                <input type="hidden" id="mec-send-email-label-loading" value="<?php echo esc_attr__('Loading...', 'renginiu-kalendorius'); ?>" />
                                <input type="hidden" id="mec-send-email-success" value="<?php echo esc_attr__('Emails successfully sent', 'renginiu-kalendorius'); ?>" />
                                <input type="hidden" id="mec-send-email-no-user-selected" value="<?php echo esc_attr__('No user selected!', 'renginiu-kalendorius'); ?>" />
                                <input type="hidden" id="mec-send-email-empty-subject" value="<?php echo esc_attr__('Email subject cannot be empty!', 'renginiu-kalendorius'); ?>" />
                                <input type="hidden" id="mec-send-email-empty-content" value="<?php echo esc_attr__('Email content cannot be empty!', 'renginiu-kalendorius'); ?>" />
                                <input type="hidden" id="mec-send-email-error" value="<?php echo esc_attr__('An error occurred. Please try again.', 'renginiu-kalendorius'); ?>" />
                                <span class="mec-send-email-button"><?php echo esc_html($send_email_label); ?></span>
                            </div>
                            <?php wp_enqueue_editor(); ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <?php /* adventistai.lt: Export & Purge is not part of the calendar-only build. */ ?>
                <div class="notice notice-info"><p><?php esc_html_e('Export & Purge is not available in this calendar-only build.', 'renginiu-kalendorius'); ?></p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

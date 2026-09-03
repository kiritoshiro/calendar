<?php
/** no direct access **/
defined('MECEXEC') or die();

/** @var MEC_feature_mec $this */

$multilingual = $this->main->is_multilingual();
$locale = $this->main->get_backend_active_locale();

$notifications = $this->main->get_notifications(($multilingual ? $locale : NULL));
$settings = $this->main->get_settings();

// Fix Notices
if(!isset($notifications['event_finished'])) $notifications['event_finished'] = [];

// Additional Organizers
$additional_organizers = (isset($settings['additional_organizers']) and $settings['additional_organizers']);
?>
<div class="wns-be-container wns-be-container-sticky">
    <div id="wns-be-infobar">
        <div class="mec-search-settings-wrap">
            <i class="mec-sl-magnifier"></i>
            <input id="mec-search-settings" type="text" placeholder="<?php esc_html_e('Search...' , 'modern-events-calendar-lite'); ?>" title="">
        </div>
        <a href="" id="" class="dpr-btn dpr-save-btn"><?php esc_html_e('Save Changes', 'modern-events-calendar-lite'); ?></a>
    </div>

    <div class="wns-be-sidebar">
        <?php $this->main->get_sidebar_menu('notifications'); ?>
    </div>

    <div class="wns-be-main notifications-settings">
        <div id="wns-be-notification"></div>
        <div id="wns-be-content">
            <div class="wns-be-group-tab">
                <div class="mec-container">

                    <form id="mec_notifications_form">

                        <div id="notification_options" class="mec-options-fields active">
                            <h4 class="mec-form-subtitle"><?php esc_html_e('Notification Options', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_booking_sender_name"><?php esc_html_e('Sender Name', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <input type="text" id="mec_settings_booking_sender_name" name="mec[settings][booking_sender_name]"
                                           value="<?php echo (isset($settings['booking_sender_name']) and trim($settings['booking_sender_name'])) ? esc_attr(stripslashes($settings['booking_sender_name'])) : ''; ?>" placeholder="<?php esc_html_e('e.g. Webnus', 'modern-events-calendar-lite'); ?>"/>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_booking_sender_email"><?php esc_html_e('Sender Email', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <input type="text" id="mec_settings_booking_sender_email" name="mec[settings][booking_sender_email]"
                                           value="<?php echo (isset($settings['booking_sender_email']) and trim($settings['booking_sender_email'])) ? esc_attr($settings['booking_sender_email']) : ''; ?>" placeholder="<?php esc_html_e('e.g. info@webnus.net', 'modern-events-calendar-lite'); ?>"/>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_booking_recipients_method"><?php esc_html_e('Recipients Method', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select id="mec_settings_booking_recipients_method" name="mec[settings][booking_recipients_method]">
                                        <option value="BCC" <?php echo ((isset($settings['booking_recipients_method']) and trim($settings['booking_recipients_method']) == 'BCC') ? 'selected="selected"' : ''); ?>><?php esc_html_e('BCC (Invisible)', 'modern-events-calendar-lite'); ?></option>
                                        <option value="CC" <?php echo ((isset($settings['booking_recipients_method']) and trim($settings['booking_recipients_method']) == 'CC') ? 'selected="selected"' : ''); ?>><?php esc_html_e('CC (Visible)', 'modern-events-calendar-lite'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_notification_gdpr"><?php esc_html_e('GDPR Compliance', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select id="mec_settings_notification_gdpr" name="mec[settings][notification_gdpr]">
                                        <option value="0" <?php echo ((isset($settings['notification_gdpr']) and trim($settings['notification_gdpr']) == '0') ? 'selected="selected"' : ''); ?>><?php esc_html_e('No', 'modern-events-calendar-lite'); ?></option>
                                        <option value="1" <?php echo ((isset($settings['notification_gdpr']) and trim($settings['notification_gdpr']) == '1') ? 'selected="selected"' : ''); ?>><?php esc_html_e('Yes', 'modern-events-calendar-lite'); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e('Send booker emails only after email verification', 'modern-events-calendar-lite'); ?></p>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <label class="mec-col-3" for="mec_settings_attach_default_invoice_confirmation"><?php esc_html_e('Attach default invoice to confirmation email', 'modern-events-calendar-lite'); ?></label>
                                <div class="mec-col-9">
                                    <select id="mec_settings_attach_default_invoice_confirmation" name="mec[settings][attach_default_invoice_confirmation]">
                                        <option value="1" <?php echo (!isset($settings['attach_default_invoice_confirmation']) || (string)$settings['attach_default_invoice_confirmation'] === '1') ? 'selected="selected"' : ''; ?>><?php esc_html_e('Yes', 'modern-events-calendar-lite'); ?></option>
                                        <option value="0" <?php echo (isset($settings['attach_default_invoice_confirmation']) && (string)$settings['attach_default_invoice_confirmation'] === '0') ? 'selected="selected"' : ''; ?>><?php esc_html_e('No', 'modern-events-calendar-lite'); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e('If enabled, MEC attaches the default invoice PDF to booking confirmation emails. Disable to prevent duplicate invoices when using the Ticket & Invoice add-on.', 'modern-events-calendar-lite'); ?></p>
                                </div>
                            </div>

                            <?php if($this->main->getPRO()): ?>
                            <h5 class="mec-form-subtitle"><?php esc_html_e('Notifications Per Event', 'modern-events-calendar-lite'); ?></h5>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label>
                                        <input type="hidden" name="mec[settings][notif_per_event]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][notif_per_event]" <?php if(isset($settings['notif_per_event']) and $settings['notif_per_event']) echo 'checked="checked"'; ?> /><?php esc_html_e('Edit Notifications Per Event', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                            </div>

                            <h5 class="mec-form-subtitle"><?php esc_html_e('Notification Template', 'modern-events-calendar-lite'); ?></h5>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label>
                                        <input type="hidden" name="mec[settings][notif_template_disable]" value="0" />
                                        <input value="1" type="checkbox" name="mec[settings][notif_template_disable]" <?php if(isset($settings['notif_template_disable']) and $settings['notif_template_disable']) echo 'checked="checked"'; ?> /><?php esc_html_e('Disable MEC Notification Template', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if($this->main->getPRO() and isset($this->settings['booking_status']) and $this->settings['booking_status']): ?>
                        <?php do_action('mec_notification_menu_start', $this->main, $notifications); ?>

                        <div id="booking_notification_section" class="mec-options-fields">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('Booking', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label>
                                        <input type="hidden" name="mec[notifications][booking_notification][status]" value="0" />
                                        <input onchange="jQuery('#mec_notification_booking_notification_container_toggle').toggle();" value="1" type="checkbox" name="mec[notifications][booking_notification][status]" <?php if(!isset($notifications['booking_notification']['status']) or $notifications['booking_notification']['status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable booking notification', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <p class="mec-col-12 description"><?php esc_html_e('Sent to attendee after booking to notify them.', 'modern-events-calendar-lite'); ?></p>
                            </div>
                            <div id="mec_notification_booking_notification_container_toggle" class="<?php if(isset($notifications['booking_notification']) and isset($notifications['booking_notification']['status']) and !$notifications['booking_notification']['status']) echo 'mec-util-hidden'; ?>">
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_notification_subject"><?php esc_html_e('Email Subject', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][booking_notification][subject]" id="mec_notifications_booking_notification_subject" value="<?php echo (isset($notifications['booking_notification']['subject']) ? esc_attr(stripslashes($notifications['booking_notification']['subject'])) : ''); ?>" />
                                    </div>
                                </div>

                               <!-- Start Receiver Users -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_notification_receiver_users"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                            $users = $notifications['booking_notification']['receiver_users'] ?? [];
                                            echo MEC_kses::form($this->main->get_users_dropdown($users));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select users to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Users -->

                                <!-- Start Receiver Roles -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_notification_receiver_roles"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                            $roles = $notifications['booking_notification']['receiver_roles'] ?? [];
                                            echo MEC_kses::form($this->main->get_roles_dropdown($roles));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select a user role to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Roles -->

                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_notification_recipients"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][booking_notification][recipients]" id="mec_notifications_booking_notification_recipients" value="<?php echo (isset($notifications['booking_notification']['recipients']) ? esc_attr($notifications['booking_notification']['recipients']) : ''); ?>" />
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Insert the comma separated email addresses for multiple recipients.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_booking_notification_send_to_organizer">
                                            <input type="checkbox" name="mec[notifications][booking_notification][send_to_organizer]" value="1" id="mec_notifications_booking_notification_send_to_organizer" <?php echo ((isset($notifications['booking_notification']['send_to_organizer']) and $notifications['booking_notification']['send_to_organizer'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to event organizer', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>

                                <?php if($additional_organizers): ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_booking_notification_send_to_additional_organizers">
                                            <input type="checkbox" name="mec[notifications][booking_notification][send_to_additional_organizers]" value="1" id="mec_notifications_booking_notification_send_to_additional_organizers" <?php echo ((isset($notifications['booking_notification']['send_to_additional_organizers']) and $notifications['booking_notification']['send_to_additional_organizers'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to additional organizers', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_booking_notification_send_single_one_email">
                                            <input type="checkbox" name="mec[notifications][booking_notification][send_single_one_email]" value="1" id="mec_notifications_booking_notification_send_single_one_email" <?php echo ((isset($notifications['booking_notification']['send_single_one_email']) and $notifications['booking_notification']['send_single_one_email'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send one single email only to first attendee', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>

                                <div class="mec-form-row">
                                    <label for="mec_notifications_booking_notification_content"><?php esc_html_e('Email Content', 'modern-events-calendar-lite'); ?></label>
                                    <?php wp_editor((isset($notifications['booking_notification']) ? stripslashes($notifications['booking_notification']['content']) : ''), 'mec_notifications_booking_notification_content', array('textarea_name'=>'mec[notifications][booking_notification][content]')); ?>
                                </div>

                                <?php
                                    $section = 'booking_notification';
                                    do_action('mec_display_notification_settings',$notifications,$section);
                                ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <p class="description"><?php esc_html_e('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                        <ul>
                                            <li><span>%%name%%</span>: <?php esc_html_e('Full name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%first_name%%</span>: <?php esc_html_e('First name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%last_name%%</span>: <?php esc_html_e('Last name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%user_email%%</span>: <?php esc_html_e('Email of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date%%</span>: <?php esc_html_e('Booked date of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_time%%</span>: <?php esc_html_e('Booked time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime%%</span>: <?php esc_html_e('Booked date and time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_other_datetimes%%</span>: <?php esc_html_e('Other date and times of booking for multiple date booking system', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date_next_occurrences%%</span>: <?php esc_html_e('Date of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime_next_occurrences%%</span>: <?php esc_html_e('Date and Time of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_price%%</span>: <?php esc_html_e('Booking Price', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_payable%%</span>: <?php esc_html_e('Booking Payable', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_order_time%%</span>: <?php esc_html_e('Date and time of booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_name%%</span>: <?php esc_html_e('Your website title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_url%%</span>: <?php esc_html_e('Your website URL', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_description%%</span>: <?php esc_html_e('Your website description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_title%%</span>: <?php esc_html_e('Event title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_description%%</span>: <?php esc_html_e('Event Description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_tags%%</span>: <?php esc_html_e('Event Tags', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_labels%%</span>: <?php esc_html_e('Event Labels', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_categories%%</span>: <?php esc_html_e('Event Categories', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_cost%%</span>: <?php esc_html_e('Event Cost', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_link%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_date%%</span>: <?php esc_html_e('Event Start Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_date%%</span>: <?php esc_html_e('Event End Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_time%%</span>: <?php esc_html_e('Event Start Time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_time%%</span>: <?php esc_html_e('Event End Time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_timezone%%</span>: <?php esc_html_e('Event Timezone', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_date_local%%</span>: <?php esc_html_e('Event Local Start Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_date_local%%</span>: <?php esc_html_e('Event Local End Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_time_local%%</span>: <?php esc_html_e('Event Local Start Time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_time_local%%</span>: <?php esc_html_e('Event Local End Time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_speaker_name%%</span>: <?php esc_html_e('Speaker name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_name%%</span>: <?php esc_html_e('Organizer name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_tel%%</span>: <?php esc_html_e('Organizer tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_email%%</span>: <?php esc_html_e('Organizer email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_url%%</span>: <?php esc_html_e('Organizer url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_name%%</span>: <?php esc_html_e('Additional organizers name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_tel%%</span>: <?php esc_html_e('Additional organizers tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_email%%</span>: <?php esc_html_e('Additional organizers email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_url%%</span>: <?php esc_html_e('Additional organizers url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_name%%</span>: <?php esc_html_e('Location name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_address%%</span>: <?php esc_html_e('Location address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_name%%</span>: <?php esc_html_e('Additional locations name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_address%%</span>: <?php esc_html_e('Additional locations address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_featured_image%%</span>: <?php esc_html_e('Featured image of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_more_info%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_info%%</span>: <?php esc_html_e('Event more info link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%online_link%%</span>: <?php esc_html_e('Event online link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%attendees_full_info%%</span>: <?php esc_html_e('Full Attendee info such as booking form data, name, email etc.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%all_bfixed_fields%%</span>: <?php esc_html_e('All booking fixed fields data.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_id%%</span>: <?php esc_html_e('Booking ID', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_transaction_id%%</span>: <?php esc_html_e('Transaction ID of Booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%invoice_link%%</span>: <?php esc_html_e('Invoice Link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%total_attendees%%</span>: <?php esc_html_e('Total attendees of current booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%amount_tickets%%</span>: <?php esc_html_e('Amount of Booked Tickets (Total attendees of all bookings)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name%%</span>: <?php esc_html_e('Ticket name', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_time%%</span>: <?php esc_html_e('Ticket time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name_time%%</span>: <?php esc_html_e('Ticket name & time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_private_description%%</span>: <?php esc_html_e('Ticket private description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_variations%%</span>: <?php esc_html_e('Ticket Variations', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%payment_gateway%%</span>: <?php esc_html_e('Payment Gateway', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%dl_file%%</span>: <?php esc_html_e('Link to the downloadable file', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ics_link%%</span>: <?php esc_html_e('Download ICS file', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ics_link_all_occurrences%%</span>: <?php esc_html_e('Download ICS file for all occurrences', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%google_calendar_link%%</span>: <?php esc_html_e('Add to Google Calendar', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%google_calendar_link_next_occurrences%%</span>: <?php esc_html_e('Add to Google Calendar Links for next 20 occurrences', 'modern-events-calendar-lite'); ?></li>
                                            <?php do_action('mec_extra_field_notifications', $section ); ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="booking_verification" class="mec-options-fields">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('Booking Verification', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <p class="mec-col-12 description"><?php esc_html_e('It sends to attendee email for verifying their booking/email.', 'modern-events-calendar-lite'); ?></p>
                            </div>

                            <div class="mec-form-row">
                                <div class="mec-col-3">
                                    <label for="mec_notifications_email_verification_subject"><?php esc_html_e('Email Subject', 'modern-events-calendar-lite'); ?></label>
                                </div>
                                <div class="mec-col-9">
                                    <input type="text" name="mec[notifications][email_verification][subject]" id="mec_notifications_email_verification_subject" value="<?php echo (isset($notifications['email_verification']['subject']) ? esc_attr(stripslashes($notifications['email_verification']['subject'])) : ''); ?>" />
                                </div>
                            </div>

                            <!-- Start Receiver Users -->
                            <div class="mec-form-row">
                                <div class="mec-col-3">
                                    <label for="mec_notifications_email_verification_receiver_users"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></label>
                                </div>
                                <div class="mec-col-9">
                                    <?php
                                        $users = $notifications['email_verification']['receiver_users'] ?? [];
                                        echo MEC_kses::form($this->main->get_users_dropdown($users, 'email_verification'));
                                    ?>
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e('Select users to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>
                            </div>
                            <!-- End Receiver Users -->

                            <!-- Start Receiver Roles -->
                            <div class="mec-form-row">
                                <div class="mec-col-3">
                                    <label for="mec_notifications_email_verification_receiver_roles"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></label>
                                </div>
                                <div class="mec-col-9">
                                    <?php
                                        $roles = $notifications['email_verification']['receiver_roles'] ?? [];
                                        echo MEC_kses::form($this->main->get_roles_dropdown($roles, 'email_verification'));
                                    ?>
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e('Select a user role to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>
                            </div>
                            <!-- End Receiver Roles -->

                            <div class="mec-form-row">
                                <div class="mec-col-3">
                                    <label for="mec_notifications_email_verification_recipients"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></label>
                                </div>
                                <div class="mec-col-9">
                                <input type="text" name="mec[notifications][email_verification][recipients]" id="mec_notifications_email_verification_recipients" value="<?php echo (isset($notifications['email_verification']['recipients']) ? esc_attr($notifications['email_verification']['recipients']) : ''); ?>" />
                                    <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e('Insert the comma separated email addresses for multiple recipients.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label for="mec_notifications_email_verification_send_single_one_email">
                                        <input type="checkbox" name="mec[notifications][email_verification][send_single_one_email]" value="1" id="mec_notifications_email_verification_send_single_one_email" <?php echo ((isset($notifications['email_verification']['send_single_one_email']) and $notifications['email_verification']['send_single_one_email'] == 1) ? 'checked="checked"' : ''); ?> />
                                        <?php esc_html_e('Send one single email only to first attendee', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <label for="mec_notifications_email_verification_content"><?php esc_html_e('Email Content', 'modern-events-calendar-lite'); ?></label>
                                <?php wp_editor((isset($notifications['email_verification']) ? stripslashes($notifications['email_verification']['content']) : ''), 'mec_notifications_email_verification_content', array('textarea_name'=>'mec[notifications][email_verification][content]')); ?>
                            </div>

                            <?php
                                $section = 'email_verification';
                                do_action('mec_display_notification_settings',$notifications,$section);
                            ?>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <p class="description"><?php esc_html_e('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                    <ul>
                                        <li><span>%%name%%</span>: <?php esc_html_e('Full name of attendee', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%first_name%%</span>: <?php esc_html_e('First name of attendee', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%last_name%%</span>: <?php esc_html_e('Last name of attendee', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%user_email%%</span>: <?php esc_html_e('Email of attendee', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_date%%</span>: <?php esc_html_e('Booked date of event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_time%%</span>: <?php esc_html_e('Booked time of event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_datetime%%</span>: <?php esc_html_e('Booked date and time of event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_other_datetimes%%</span>: <?php esc_html_e('Other date and times of booking for multiple date booking system', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_date_next_occurrences%%</span>: <?php esc_html_e('Date of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_datetime_next_occurrences%%</span>: <?php esc_html_e('Date and Time of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_price%%</span>: <?php esc_html_e('Booking Price', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_payable%%</span>: <?php esc_html_e('Booking Payable', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_order_time%%</span>: <?php esc_html_e('Date and time of booking', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%blog_name%%</span>: <?php esc_html_e('Your website title', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%blog_url%%</span>: <?php esc_html_e('Your website URL', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%blog_description%%</span>: <?php esc_html_e('Your website description', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_title%%</span>: <?php esc_html_e('Event title', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_description%%</span>: <?php esc_html_e('Event Description', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_tags%%</span>: <?php esc_html_e('Event Tags', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_labels%%</span>: <?php esc_html_e('Event Labels', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_categories%%</span>: <?php esc_html_e('Event Categories', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_cost%%</span>: <?php esc_html_e('Event Cost', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_link%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_start_date%%</span>: <?php esc_html_e('Event Start Date', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_end_date%%</span>: <?php esc_html_e('Event End Date', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_start_time%%</span>: <?php esc_html_e('Event Start Time', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_end_time%%</span>: <?php esc_html_e('Event End Time', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_timezone%%</span>: <?php esc_html_e('Event Timezone', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_start_date_local%%</span>: <?php esc_html_e('Event Local Start Date', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_end_date_local%%</span>: <?php esc_html_e('Event Local End Date', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_start_time_local%%</span>: <?php esc_html_e('Event Local Start Time', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_end_time_local%%</span>: <?php esc_html_e('Event Local End Time', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_speaker_name%%</span>: <?php esc_html_e('Speaker name of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_organizer_name%%</span>: <?php esc_html_e('Organizer name of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_organizer_tel%%</span>: <?php esc_html_e('Organizer tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_organizer_email%%</span>: <?php esc_html_e('Organizer email of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_organizer_url%%</span>: <?php esc_html_e('Organizer url of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_other_organizers_name%%</span>: <?php esc_html_e('Additional organizers name of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_other_organizers_tel%%</span>: <?php esc_html_e('Additional organizers tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_other_organizers_email%%</span>: <?php esc_html_e('Additional organizers email of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_other_organizers_url%%</span>: <?php esc_html_e('Additional organizers url of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_location_name%%</span>: <?php esc_html_e('Location name of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_location_address%%</span>: <?php esc_html_e('Location address of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_other_locations_name%%</span>: <?php esc_html_e('Additional locations name of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_other_locations_address%%</span>: <?php esc_html_e('Additional locations address of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_featured_image%%</span>: <?php esc_html_e('Featured image of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_more_info%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_other_info%%</span>: <?php esc_html_e('Event more info link', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%online_link%%</span>: <?php esc_html_e('Event online link', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%attendees_full_info%%</span>: <?php esc_html_e('Full Attendee info such as booking form data, name, email etc.', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%all_bfixed_fields%%</span>: <?php esc_html_e('All booking fixed fields data.', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%booking_id%%</span>: <?php esc_html_e('Booking ID', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%booking_transaction_id%%</span>: <?php esc_html_e('Transaction ID of Booking', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%verification_link%%</span>: <?php esc_html_e('Email/Booking verification link.', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%total_attendees%%</span>: <?php esc_html_e('Total attendees of current booking', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%amount_tickets%%</span>: <?php esc_html_e('Amount of Booked Tickets (Total attendees of all bookings)', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%ticket_name%%</span>: <?php esc_html_e('Ticket name', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%ticket_time%%</span>: <?php esc_html_e('Ticket time', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%ticket_name_time%%</span>: <?php esc_html_e('Ticket name & time', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%ticket_private_description%%</span>: <?php esc_html_e('Ticket private description', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%ticket_variations%%</span>: <?php esc_html_e('Ticket Variations', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%payment_gateway%%</span>: <?php esc_html_e('Payment Gateway', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%dl_file%%</span>: <?php esc_html_e('Link to the downloadable file', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%ics_link%%</span>: <?php esc_html_e('Download ICS file', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%ics_link_all_occurrences%%</span>: <?php esc_html_e('Download ICS file for all occurrences', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%google_calendar_link%%</span>: <?php esc_html_e('Add to Google Calendar', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%google_calendar_link_next_occurrences%%</span>: <?php esc_html_e('Add to Google Calendar Links for next 20 occurrences', 'modern-events-calendar-lite'); ?></li>
                                        <?php do_action('mec_extra_field_notifications', $section); ?>
                                    </ul>
                                </div>
                            </div>

                        </div>

                        <div id="booking_confirmation" class="mec-options-fields">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('Booking Confirmation', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label>
                                        <input type="hidden" name="mec[notifications][booking_confirmation][status]" value="0" />
                                        <input onchange="jQuery('#mec_notification_booking_confirmation_container_toggle').toggle();" value="1" type="checkbox" name="mec[notifications][booking_confirmation][status]" <?php if(!isset($notifications['booking_confirmation']['status']) || $notifications['booking_confirmation']['status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable booking confirmation', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <p class="mec-col-12 description"><?php esc_html_e('Sent to attendee after confirming the booking by admin.', 'modern-events-calendar-lite'); ?></p>
                            </div>
                            <div id="mec_notification_booking_confirmation_container_toggle" class="<?php if(isset($notifications['booking_confirmation']) and isset($notifications['booking_confirmation']['status']) and !$notifications['booking_confirmation']['status']) echo 'mec-util-hidden'; ?>">

                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_confirmation_subject"><?php esc_html_e('Email Subject', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][booking_confirmation][subject]" id="mec_notifications_booking_confirmation_subject" value="<?php echo (isset($notifications['booking_confirmation']['subject']) ? esc_attr(stripslashes($notifications['booking_confirmation']['subject'])) : ''); ?>" />
                                    </div>
                                </div>

                                <!-- Start Receiver Users -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_confirmation_receiver_users"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                        $users = $notifications['booking_confirmation']['receiver_users'] ?? [];
                                        echo MEC_kses::form($this->main->get_users_dropdown($users, 'booking_confirmation'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select users to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Users -->

                                <!-- Start Receiver Roles -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_confirmation_receiver_roles"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                        $roles = $notifications['booking_confirmation']['receiver_roles'] ?? [];
                                        echo MEC_kses::form($this->main->get_roles_dropdown($roles, 'booking_confirmation'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select a user role to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Roles -->

                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_confirmation_recipients"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][booking_confirmation][recipients]" id="mec_notifications_booking_confirmation_recipients" value="<?php echo (isset($notifications['booking_confirmation']['recipients']) ? esc_attr($notifications['booking_confirmation']['recipients']) : ''); ?>" />
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Insert the comma separated email addresses for multiple recipients.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_booking_confirmation_send_single_one_email">
                                            <input type="checkbox" name="mec[notifications][booking_confirmation][send_single_one_email]" value="1" id="mec_notifications_booking_confirmation_send_single_one_email" <?php echo ((isset($notifications['booking_confirmation']['send_single_one_email']) and $notifications['booking_confirmation']['send_single_one_email'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send one single email only to first attendee', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <label for="mec_notifications_booking_confirmation_content"><?php esc_html_e('Email Content', 'modern-events-calendar-lite'); ?></label>
                                    <?php wp_editor((isset($notifications['booking_confirmation']) ? stripslashes($notifications['booking_confirmation']['content']) : ''), 'mec_notifications_booking_confirmation_content', array('textarea_name'=>'mec[notifications][booking_confirmation][content]')); ?>
                                </div>

                                <?php
                                    $section = 'booking_confirmation';
                                    do_action('mec_display_notification_settings',$notifications,$section);
                                ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <p class="description"><?php esc_html_e('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                        <ul>
                                            <li><span>%%name%%</span>: <?php esc_html_e('Full name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%first_name%%</span>: <?php esc_html_e('First name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%last_name%%</span>: <?php esc_html_e('Last name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%user_email%%</span>: <?php esc_html_e('Email of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date%%</span>: <?php esc_html_e('Booked date of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_time%%</span>: <?php esc_html_e('Booked time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime%%</span>: <?php esc_html_e('Booked date and time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_other_datetimes%%</span>: <?php esc_html_e('Other date and times of booking for multiple date booking system', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date_next_occurrences%%</span>: <?php esc_html_e('Date of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime_next_occurrences%%</span>: <?php esc_html_e('Date and Time of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_price%%</span>: <?php esc_html_e('Booking Price', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_payable%%</span>: <?php esc_html_e('Booking Payable', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%attendee_price%%</span>: <?php esc_html_e('Attendee Price', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_order_time%%</span>: <?php esc_html_e('Date and time of booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_name%%</span>: <?php esc_html_e('Your website title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_url%%</span>: <?php esc_html_e('Your website URL', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_description%%</span>: <?php esc_html_e('Your website description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_title%%</span>: <?php esc_html_e('Event title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_description%%</span>: <?php esc_html_e('Event Description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_tags%%</span>: <?php esc_html_e('Event Tags', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_labels%%</span>: <?php esc_html_e('Event Labels', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_categories%%</span>: <?php esc_html_e('Event Categories', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_cost%%</span>: <?php esc_html_e('Event Cost', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_link%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_date%%</span>: <?php esc_html_e('Event Start Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_date%%</span>: <?php esc_html_e('Event End Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_time%%</span>: <?php esc_html_e('Event Start Time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_time%%</span>: <?php esc_html_e('Event End Time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_timezone%%</span>: <?php esc_html_e('Event Timezone', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_date_local%%</span>: <?php esc_html_e('Event Local Start Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_date_local%%</span>: <?php esc_html_e('Event Local End Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_time_local%%</span>: <?php esc_html_e('Event Local Start Time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_time_local%%</span>: <?php esc_html_e('Event Local End Time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_speaker_name%%</span>: <?php esc_html_e('Speaker name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_name%%</span>: <?php esc_html_e('Organizer name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_tel%%</span>: <?php esc_html_e('Organizer tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_email%%</span>: <?php esc_html_e('Organizer email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_url%%</span>: <?php esc_html_e('Organizer url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_name%%</span>: <?php esc_html_e('Additional organizers name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_tel%%</span>: <?php esc_html_e('Additional organizers tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_email%%</span>: <?php esc_html_e('Additional organizers email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_url%%</span>: <?php esc_html_e('Additional organizers url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_name%%</span>: <?php esc_html_e('Location name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_address%%</span>: <?php esc_html_e('Location address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_name%%</span>: <?php esc_html_e('Additional locations name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_address%%</span>: <?php esc_html_e('Additional locations address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_featured_image%%</span>: <?php esc_html_e('Featured image of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_more_info%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_info%%</span>: <?php esc_html_e('Event more info link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%online_link%%</span>: <?php esc_html_e('Event online link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%attendees_full_info%%</span>: <?php esc_html_e('Full Attendee info such as booking form data, name, email etc.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%all_bfixed_fields%%</span>: <?php esc_html_e('All booking fixed fields data.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_id%%</span>: <?php esc_html_e('Booking ID', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_transaction_id%%</span>: <?php esc_html_e('Transaction ID of Booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%cancellation_link%%</span>: <?php esc_html_e('Booking cancellation link.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%invoice_link%%</span>: <?php esc_html_e('Invoice Link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%total_attendees%%</span>: <?php esc_html_e('Total attendees of current booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%amount_tickets%%</span>: <?php esc_html_e('Amount of Booked Tickets (Total attendees of all bookings)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name%%</span>: <?php esc_html_e('Ticket name', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_time%%</span>: <?php esc_html_e('Ticket time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name_time%%</span>: <?php esc_html_e('Ticket name & time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_private_description%%</span>: <?php esc_html_e('Ticket private description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_variations%%</span>: <?php esc_html_e('Ticket Variations', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%payment_gateway%%</span>: <?php esc_html_e('Payment Gateway', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%dl_file%%</span>: <?php esc_html_e('Link to the downloadable file', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ics_link%%</span>: <?php esc_html_e('Download ICS file', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ics_link_all_occurrences%%</span>: <?php esc_html_e('Download ICS file for all occurrences', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%google_calendar_link%%</span>: <?php esc_html_e('Add to Google Calendar', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%google_calendar_link_next_occurrences%%</span>: <?php esc_html_e('Add to Google Calendar Links for next 20 occurrences', 'modern-events-calendar-lite'); ?></li>
                                            <?php do_action('mec_extra_field_notifications', $section); ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div id="booking_rejection" class="mec-options-fields">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('Booking Rejection', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label>
                                        <input type="hidden" name="mec[notifications][booking_rejection][status]" value="0" />
                                        <input onchange="jQuery('#mec_notification_booking_rejection_container_toggle').toggle();" value="1" type="checkbox" name="mec[notifications][booking_rejection][status]" <?php if((isset($notifications['booking_rejection']) and isset($notifications['booking_rejection']['status']) and $notifications['booking_rejection']['status'])) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable booking rejection', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <p class="mec-col-12 description"><?php esc_html_e('Sent to attendee after booking rejection by admin.', 'modern-events-calendar-lite'); ?></p>
                            </div>
                            <div id="mec_notification_booking_rejection_container_toggle" class="<?php if(!isset($notifications['booking_rejection']) || !$notifications['booking_rejection']['status']) echo 'mec-util-hidden'; ?>">

                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_rejection_subject"><?php esc_html_e('Email Subject', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][booking_rejection][subject]" id="mec_notifications_booking_rejection_subject" value="<?php echo (isset($notifications['booking_rejection']['subject']) ? esc_attr(stripslashes($notifications['booking_rejection']['subject'])) : ''); ?>" />
                                    </div>
                                </div>

                                <!-- Start Receiver Users -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_rejection_receiver_users"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                        $users = $notifications['booking_rejection']['receiver_users'] ?? [];
                                        echo MEC_kses::form($this->main->get_users_dropdown($users, 'booking_rejection'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select users to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Users -->

                                <!-- Start Receiver Roles -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_rejection_receiver_roles"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                        $roles = $notifications['booking_rejection']['receiver_roles'] ?? [];
                                        echo MEC_kses::form($this->main->get_roles_dropdown($roles, 'booking_rejection'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select a user role to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Roles -->

                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_rejection_recipients"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][booking_rejection][recipients]" id="mec_notifications_booking_rejection_recipients" value="<?php echo (isset($notifications['booking_rejection']['recipients']) ? esc_attr($notifications['booking_rejection']['recipients']) : ''); ?>" />
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Insert the comma separated email addresses for multiple recipients.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_booking_rejection_send_to_admin">
                                            <input type="checkbox" name="mec[notifications][booking_rejection][send_to_admin]" value="1" id="mec_notifications_booking_rejection_send_to_admin" <?php echo ((!isset($notifications['booking_rejection']['send_to_admin']) or $notifications['booking_rejection']['send_to_admin'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to admin', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_booking_rejection_send_to_organizer">
                                            <input type="checkbox" name="mec[notifications][booking_rejection][send_to_organizer]" value="1" id="mec_notifications_booking_rejection_send_to_organizer" <?php echo ((isset($notifications['booking_rejection']['send_to_organizer']) and $notifications['booking_rejection']['send_to_organizer'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to event organizer', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>

                                <?php if($additional_organizers): ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_booking_rejection_send_to_additional_organizers">
                                            <input type="checkbox" name="mec[notifications][booking_rejection][send_to_additional_organizers]" value="1" id="mec_notifications_booking_rejection_send_to_additional_organizers" <?php echo ((isset($notifications['booking_rejection']['send_to_additional_organizers']) and $notifications['booking_rejection']['send_to_additional_organizers'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to additional organizers', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_booking_rejection_send_to_user">
                                            <input type="checkbox" name="mec[notifications][booking_rejection][send_to_user]" value="1" id="mec_notifications_booking_rejection_send_to_user" <?php echo ((isset($notifications['booking_rejection']['send_to_user']) and $notifications['booking_rejection']['send_to_user'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to the booked user', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>

                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_booking_rejection_send_single_one_email">
                                            <input type="checkbox" name="mec[notifications][booking_rejection][send_single_one_email]" value="1" id="mec_notifications_booking_rejection_send_single_one_email" <?php echo ((isset($notifications['booking_rejection']['send_single_one_email']) and $notifications['booking_rejection']['send_single_one_email'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send one single email only to first attendee', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>

                                <div class="mec-form-row">
                                    <label for="mec_notifications_booking_rejection_content"><?php esc_html_e('Email Content', 'modern-events-calendar-lite'); ?></label>
                                    <?php wp_editor((isset($notifications['booking_rejection']) ? stripslashes($notifications['booking_rejection']['content']) : ''), 'mec_notifications_booking_rejection_content', array('textarea_name'=>'mec[notifications][booking_rejection][content]')); ?>
                                </div>

                                <?php
                                    $section = 'booking_rejection';
                                    do_action('mec_display_notification_settings',$notifications,$section);
                                ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <p class="description"><?php esc_html_e('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                        <ul>
                                            <li><span>%%name%%</span>: <?php esc_html_e('Full name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%first_name%%</span>: <?php esc_html_e('First name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%last_name%%</span>: <?php esc_html_e('Last name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%user_email%%</span>: <?php esc_html_e('Email of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date%%</span>: <?php esc_html_e('Booked date of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_time%%</span>: <?php esc_html_e('Booked time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime%%</span>: <?php esc_html_e('Booked date and time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_other_datetimes%%</span>: <?php esc_html_e('Other date and times of booking for multiple date booking system', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date_next_occurrences%%</span>: <?php esc_html_e('Date of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime_next_occurrences%%</span>: <?php esc_html_e('Date and Time of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_price%%</span>: <?php esc_html_e('Booking Price', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_payable%%</span>: <?php esc_html_e('Booking Payable', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%attendee_price%%</span>: <?php esc_html_e('Attendee Price', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_order_time%%</span>: <?php esc_html_e('Date and time of booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_name%%</span>: <?php esc_html_e('Your website title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_url%%</span>: <?php esc_html_e('Your website URL', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_description%%</span>: <?php esc_html_e('Your website description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_title%%</span>: <?php esc_html_e('Event title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_description%%</span>: <?php esc_html_e('Event Description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_tags%%</span>: <?php esc_html_e('Event Tags', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_labels%%</span>: <?php esc_html_e('Event Labels', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_categories%%</span>: <?php esc_html_e('Event Categories', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_cost%%</span>: <?php esc_html_e('Event Cost', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_link%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_date%%</span>: <?php esc_html_e('Event Start Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_date%%</span>: <?php esc_html_e('Event End Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_time%%</span>: <?php esc_html_e('Event Start Time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_time%%</span>: <?php esc_html_e('Event End Time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_timezone%%</span>: <?php esc_html_e('Event Timezone', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_date_local%%</span>: <?php esc_html_e('Event Local Start Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_date_local%%</span>: <?php esc_html_e('Event Local End Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_time_local%%</span>: <?php esc_html_e('Event Local Start Time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_time_local%%</span>: <?php esc_html_e('Event Local End Time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_speaker_name%%</span>: <?php esc_html_e('Speaker name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_name%%</span>: <?php esc_html_e('Organizer name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_tel%%</span>: <?php esc_html_e('Organizer tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_email%%</span>: <?php esc_html_e('Organizer email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_url%%</span>: <?php esc_html_e('Organizer url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_name%%</span>: <?php esc_html_e('Additional organizers name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_tel%%</span>: <?php esc_html_e('Additional organizers tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_email%%</span>: <?php esc_html_e('Additional organizers email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_url%%</span>: <?php esc_html_e('Additional organizers url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_name%%</span>: <?php esc_html_e('Location name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_address%%</span>: <?php esc_html_e('Location address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_name%%</span>: <?php esc_html_e('Additional locations name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_address%%</span>: <?php esc_html_e('Additional locations address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_featured_image%%</span>: <?php esc_html_e('Featured image of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_more_info%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_info%%</span>: <?php esc_html_e('Event more info link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%online_link%%</span>: <?php esc_html_e('Event online link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%attendees_full_info%%</span>: <?php esc_html_e('Full Attendee info such as booking form data, name, email etc.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%all_bfixed_fields%%</span>: <?php esc_html_e('All booking fixed fields data.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_id%%</span>: <?php esc_html_e('Booking ID', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_transaction_id%%</span>: <?php esc_html_e('Transaction ID of Booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%cancellation_link%%</span>: <?php esc_html_e('Booking cancellation link.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%invoice_link%%</span>: <?php esc_html_e('Invoice Link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%total_attendees%%</span>: <?php esc_html_e('Total attendees of current booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%amount_tickets%%</span>: <?php esc_html_e('Amount of Booked Tickets (Total attendees of all bookings)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name%%</span>: <?php esc_html_e('Ticket name', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_time%%</span>: <?php esc_html_e('Ticket time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name_time%%</span>: <?php esc_html_e('Ticket name & time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_private_description%%</span>: <?php esc_html_e('Ticket private description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_variations%%</span>: <?php esc_html_e('Ticket Variations', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%payment_gateway%%</span>: <?php esc_html_e('Payment Gateway', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%dl_file%%</span>: <?php esc_html_e('Link to the downloadable file', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ics_link%%</span>: <?php esc_html_e('Download ICS file', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ics_link_all_occurrences%%</span>: <?php esc_html_e('Download ICS file for all occurrences', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%google_calendar_link%%</span>: <?php esc_html_e('Add to Google Calendar', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%google_calendar_link_next_occurrences%%</span>: <?php esc_html_e('Add to Google Calendar Links for next 20 occurrences', 'modern-events-calendar-lite'); ?></li>
                                            <?php do_action('mec_extra_field_notifications', $section); ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div id="cancellation_notification" class="mec-options-fields">
                            <h4 class="mec-form-subtitle"><?php esc_html_e('Booking Cancellation', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label>
                                        <input type="hidden" name="mec[notifications][cancellation_notification][status]" value="0" />
                                        <input onchange="jQuery('#mec_notification_cancellation_notification_container_toggle').toggle();" value="1" type="checkbox" name="mec[notifications][cancellation_notification][status]" <?php if((isset($notifications['cancellation_notification']['status']) and $notifications['cancellation_notification']['status'])) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable cancellation notification', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <p class="mec-col-12 description"><?php esc_html_e('Sent to selected recipients after booking cancellation to notify them.', 'modern-events-calendar-lite'); ?></p>
                                </div>
                            </div>
                            <div id="mec_notification_cancellation_notification_container_toggle" class="<?php if((isset($notifications['cancellation_notification']) and !$notifications['cancellation_notification']['status']) or !isset($notifications['cancellation_notification'])) echo 'mec-util-hidden'; ?>">
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_cancellation_notification_subject"><?php esc_html_e('Email Subject', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][cancellation_notification][subject]" id="mec_notifications_cancellation_notification_subject" value="<?php echo (isset($notifications['cancellation_notification']['subject']) ? esc_attr(stripslashes($notifications['cancellation_notification']['subject'])) : 'Your booking is canceled.'); ?>" />
                                    </div>
                                </div>

                                <!-- Start Receiver Users -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_cancellation_notification_receiver_users"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                            $users = $notifications['cancellation_notification']['receiver_users'] ?? [];
                                            echo MEC_kses::form($this->main->get_users_dropdown($users, 'cancellation_notification'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select users to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Users -->

                                <!-- Start Receiver Roles -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_cancellation_notification_receiver_roles"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                            $roles = $notifications['cancellation_notification']['receiver_roles'] ?? [];
                                            echo MEC_kses::form($this->main->get_roles_dropdown($roles, 'cancellation_notification'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select a user role to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Roles -->

                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_cancellation_notification_recipients"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][cancellation_notification][recipients]" id="mec_notifications_cancellation_notification_recipients" value="<?php echo (isset($notifications['cancellation_notification']['recipients']) ? esc_attr($notifications['cancellation_notification']['recipients']) : ''); ?>" />
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Insert the comma separated email addresses for multiple recipients.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_cancellation_notification_send_to_admin">
                                            <input type="hidden" name="mec[notifications][cancellation_notification][send_to_admin]" value="0" />
                                            <input type="checkbox" name="mec[notifications][cancellation_notification][send_to_admin]" value="1" id="mec_notifications_cancellation_notification_send_to_admin" <?php echo ((!isset($notifications['cancellation_notification']['send_to_admin']) or $notifications['cancellation_notification']['send_to_admin'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to admin', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_cancellation_notification_send_to_organizer">
                                            <input type="checkbox" name="mec[notifications][cancellation_notification][send_to_organizer]" value="1" id="mec_notifications_cancellation_notification_send_to_organizer" <?php echo ((isset($notifications['cancellation_notification']['send_to_organizer']) and $notifications['cancellation_notification']['send_to_organizer'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to event organizer', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>

                                <?php if($additional_organizers): ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_cancellation_notification_send_to_additional_organizers">
                                            <input type="checkbox" name="mec[notifications][cancellation_notification][send_to_additional_organizers]" value="1" id="mec_notifications_cancellation_notification_send_to_additional_organizers" <?php echo ((isset($notifications['cancellation_notification']['send_to_additional_organizers']) and $notifications['cancellation_notification']['send_to_additional_organizers'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to additional organizers', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_cancellation_notification_send_to_user">
                                            <input type="checkbox" name="mec[notifications][cancellation_notification][send_to_user]" value="1" id="mec_notifications_cancellation_notification_send_to_user" <?php echo ((isset($notifications['cancellation_notification']['send_to_user']) and $notifications['cancellation_notification']['send_to_user'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to the booked user', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_cancellation_notification_send_single_one_email">
                                            <input type="checkbox" name="mec[notifications][cancellation_notification][send_single_one_email]" value="1" id="mec_notifications_cancellation_notification_send_single_one_email" <?php echo ((isset($notifications['cancellation_notification']['send_single_one_email']) and $notifications['cancellation_notification']['send_single_one_email'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send one single email only to first attendee', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <label for="mec_notifications_cancellation_notification_content"><?php esc_html_e('Email Content', 'modern-events-calendar-lite'); ?></label>
                                    <?php wp_editor((isset($notifications['cancellation_notification']) ? stripslashes($notifications['cancellation_notification']['content']) : ''), 'mec_notifications_cancellation_notification_content', array('textarea_name'=>'mec[notifications][cancellation_notification][content]')); ?>
                                </div>

                                <?php
                                    $section = 'cancellation_notification';
                                    do_action('mec_display_notification_settings',$notifications,$section);
                                ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <p class="description"><?php esc_html_e('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                        <ul>
                                            <li><span>%%name%%</span>: <?php esc_html_e('Full name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%first_name%%</span>: <?php esc_html_e('First name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%last_name%%</span>: <?php esc_html_e('Last name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%user_email%%</span>: <?php esc_html_e('Email of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date%%</span>: <?php esc_html_e('Booked date of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_time%%</span>: <?php esc_html_e('Booked time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime%%</span>: <?php esc_html_e('Booked date and time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_other_datetimes%%</span>: <?php esc_html_e('Other date and times of booking for multiple date booking system', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date_next_occurrences%%</span>: <?php esc_html_e('Date of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime_next_occurrences%%</span>: <?php esc_html_e('Date and Time of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_price%%</span>: <?php esc_html_e('Booking Price', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_payable%%</span>: <?php esc_html_e('Booking Payable', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_order_time%%</span>: <?php esc_html_e('Date and time of booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_name%%</span>: <?php esc_html_e('Your website title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_url%%</span>: <?php esc_html_e('Your website URL', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_description%%</span>: <?php esc_html_e('Your website description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_title%%</span>: <?php esc_html_e('Event title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_description%%</span>: <?php esc_html_e('Event Description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_tags%%</span>: <?php esc_html_e('Event Tags', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_labels%%</span>: <?php esc_html_e('Event Labels', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_categories%%</span>: <?php esc_html_e('Event Categories', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_cost%%</span>: <?php esc_html_e('Event Cost', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_link%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_speaker_name%%</span>: <?php esc_html_e('Speaker name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_name%%</span>: <?php esc_html_e('Organizer name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_tel%%</span>: <?php esc_html_e('Organizer tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_email%%</span>: <?php esc_html_e('Organizer email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_url%%</span>: <?php esc_html_e('Organizer url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_name%%</span>: <?php esc_html_e('Additional organizers name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_tel%%</span>: <?php esc_html_e('Additional organizers tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_email%%</span>: <?php esc_html_e('Additional organizers email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_url%%</span>: <?php esc_html_e('Additional organizers url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_name%%</span>: <?php esc_html_e('Location name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_address%%</span>: <?php esc_html_e('Location address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_name%%</span>: <?php esc_html_e('Additional locations name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_address%%</span>: <?php esc_html_e('Additional locations address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_featured_image%%</span>: <?php esc_html_e('Featured image of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%attendees_full_info%%</span>: <?php esc_html_e('Full Attendee info such as booking form data, name, email etc.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%all_bfixed_fields%%</span>: <?php esc_html_e('All booking fixed fields data.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_id%%</span>: <?php esc_html_e('Booking ID', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_transaction_id%%</span>: <?php esc_html_e('Transaction ID of Booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%admin_link%%</span>: <?php esc_html_e('Admin booking management link.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%total_attendees%%</span>: <?php esc_html_e('Total attendees of current booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%amount_tickets%%</span>: <?php esc_html_e('Amount of Booked Tickets (Total attendees of all bookings)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name%%</span>: <?php esc_html_e('Ticket name', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_time%%</span>: <?php esc_html_e('Ticket time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name_time%%</span>: <?php esc_html_e('Ticket name & time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_private_description%%</span>: <?php esc_html_e('Ticket private description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_variations%%</span>: <?php esc_html_e('Ticket Variations', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%payment_gateway%%</span>: <?php esc_html_e('Payment Gateway', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%dl_file%%</span>: <?php esc_html_e('Link to the downloadable file', 'modern-events-calendar-lite'); ?></li>
                                            <?php do_action('mec_extra_field_notifications', $section); ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="admin_notification" class="mec-options-fields">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('Admin', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label>
                                        <input type="hidden" name="mec[notifications][admin_notification][status]" value="0" />
                                        <input onchange="jQuery('#mec_notification_admin_notification_container_toggle').toggle();" value="1" type="checkbox" name="mec[notifications][admin_notification][status]" <?php if(!isset($notifications['admin_notification']['status']) || $notifications['admin_notification']['status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable admin notification', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <p class="mec-col-12 description"><?php esc_html_e('Sent to admin to notify them that a new booking has been received.', 'modern-events-calendar-lite'); ?></p>
                            </div>
                            <div id="mec_notification_admin_notification_container_toggle" class="<?php if(isset($notifications['admin_notification']) and isset($notifications['admin_notification']['status']) and !$notifications['admin_notification']['status']) echo 'mec-util-hidden'; ?>">
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_admin_notification_subject"><?php esc_html_e('Email Subject', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][admin_notification][subject]" id="mec_notifications_admin_notification_subject" value="<?php echo (isset($notifications['admin_notification']['subject']) ? esc_attr(stripslashes($notifications['admin_notification']['subject'])) : ''); ?>" />
                                    </div>
                                </div>

                                <!-- Start Receiver Users -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_admin_notification_receiver_users"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                            $users = $notifications['admin_notification']['receiver_users'] ?? [];
                                            echo MEC_kses::form($this->main->get_users_dropdown($users, 'admin_notification'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select users to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Users -->

                                <!-- Start Receiver Roles -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_admin_notification_receiver_roles"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                            $roles = $notifications['admin_notification']['receiver_roles'] ?? [];
                                            echo MEC_kses::form($this->main->get_roles_dropdown($roles, 'admin_notification'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select a user role to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Roles -->

                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_admin_notification_recipients"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][admin_notification][recipients]" id="mec_notifications_admin_notification_recipients" value="<?php echo (isset($notifications['admin_notification']['recipients']) ? esc_attr($notifications['admin_notification']['recipients']) : ''); ?>" />
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Insert the comma separated email addresses for multiple recipients.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_admin_notification_send_to_admin">
                                            <input type="hidden" name="mec[notifications][admin_notification][send_to_admin]" value="0" />
                                            <input type="checkbox" name="mec[notifications][admin_notification][send_to_admin]" value="1" id="mec_notifications_admin_notification_send_to_admin" <?php echo ((!isset($notifications['admin_notification']['send_to_admin']) || $notifications['admin_notification']['send_to_admin'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to admin', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_admin_notification_send_to_organizer">
                                            <input type="checkbox" name="mec[notifications][admin_notification][send_to_organizer]" value="1" id="mec_notifications_admin_notification_send_to_organizer" <?php echo ((isset($notifications['admin_notification']['send_to_organizer']) and $notifications['admin_notification']['send_to_organizer'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to event organizer', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>

                                <?php if($additional_organizers): ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_admin_notification_send_to_additional_organizers">
                                            <input type="checkbox" name="mec[notifications][admin_notification][send_to_additional_organizers]" value="1" id="mec_notifications_admin_notification_send_to_additional_organizers" <?php echo ((isset($notifications['admin_notification']['send_to_additional_organizers']) and $notifications['admin_notification']['send_to_additional_organizers'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to additional organizers', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="mec-form-row">
                                    <label for="mec_notifications_admin_notification_content"><?php esc_html_e('Email Content', 'modern-events-calendar-lite'); ?></label>
                                    <?php wp_editor((isset($notifications['admin_notification']) ? stripslashes($notifications['admin_notification']['content']) : ''), 'mec_notifications_admin_notification_content', array('textarea_name'=>'mec[notifications][admin_notification][content]')); ?>
                                </div>

                                <?php
                                    $section = 'admin_notification';
                                    do_action('mec_display_notification_settings', $notifications,$section);
                                ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <p class="description"><?php esc_html_e('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                        <ul>
                                            <li><span>%%name%%</span>: <?php esc_html_e('Full name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%first_name%%</span>: <?php esc_html_e('First name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%last_name%%</span>: <?php esc_html_e('Last name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%user_email%%</span>: <?php esc_html_e('Email of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date%%</span>: <?php esc_html_e('Booked date of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_time%%</span>: <?php esc_html_e('Booked time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime%%</span>: <?php esc_html_e('Booked date and time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_other_datetimes%%</span>: <?php esc_html_e('Other date and times of booking for multiple date booking system', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date_next_occurrences%%</span>: <?php esc_html_e('Date of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime_next_occurrences%%</span>: <?php esc_html_e('Date and Time of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_price%%</span>: <?php esc_html_e('Booking Price', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_payable%%</span>: <?php esc_html_e('Booking Payable', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_order_time%%</span>: <?php esc_html_e('Date and time of booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_name%%</span>: <?php esc_html_e('Your website title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_url%%</span>: <?php esc_html_e('Your website URL', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_description%%</span>: <?php esc_html_e('Your website description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_title%%</span>: <?php esc_html_e('Event title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_description%%</span>: <?php esc_html_e('Event Description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_tags%%</span>: <?php esc_html_e('Event Tags', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_labels%%</span>: <?php esc_html_e('Event Labels', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_categories%%</span>: <?php esc_html_e('Event Categories', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_cost%%</span>: <?php esc_html_e('Event Cost', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_link%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_speaker_name%%</span>: <?php esc_html_e('Speaker name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_name%%</span>: <?php esc_html_e('Organizer name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_tel%%</span>: <?php esc_html_e('Organizer tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_email%%</span>: <?php esc_html_e('Organizer email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_url%%</span>: <?php esc_html_e('Organizer url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_name%%</span>: <?php esc_html_e('Additional organizers name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_tel%%</span>: <?php esc_html_e('Additional organizers tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_email%%</span>: <?php esc_html_e('Additional organizers email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_url%%</span>: <?php esc_html_e('Additional organizers url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_name%%</span>: <?php esc_html_e('Location name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_address%%</span>: <?php esc_html_e('Location address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_name%%</span>: <?php esc_html_e('Additional locations name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_address%%</span>: <?php esc_html_e('Additional locations address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_featured_image%%</span>: <?php esc_html_e('Featured image of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_more_info%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_info%%</span>: <?php esc_html_e('Event more info link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%online_link%%</span>: <?php esc_html_e('Event online link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%attendees_full_info%%</span>: <?php esc_html_e('Full Attendee info such as booking form data, name, email etc.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%all_bfixed_fields%%</span>: <?php esc_html_e('All booking fixed fields data.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_id%%</span>: <?php esc_html_e('Booking ID', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_transaction_id%%</span>: <?php esc_html_e('Transaction ID of Booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%admin_link%%</span>: <?php esc_html_e('Admin booking management link.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%total_attendees%%</span>: <?php esc_html_e('Total attendees of current booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%amount_tickets%%</span>: <?php esc_html_e('Amount of Booked Tickets (Total attendees of all bookings)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name%%</span>: <?php esc_html_e('Ticket name', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_time%%</span>: <?php esc_html_e('Ticket time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name_time%%</span>: <?php esc_html_e('Ticket name & time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_private_description%%</span>: <?php esc_html_e('Ticket private description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%payment_gateway%%</span>: <?php esc_html_e('Payment Gateway', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%dl_file%%</span>: <?php esc_html_e('Link to the downloadable file', 'modern-events-calendar-lite'); ?></li>
                                            <?php do_action('mec_extra_field_notifications', $section); ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="event_soldout" class="mec-options-fields">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('Event Soldout', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label>
                                        <input type="hidden" name="mec[notifications][event_soldout][status]" value="0" />
                                        <input onchange="jQuery('#mec_notification_event_soldout_container_toggle').toggle();" value="1" type="checkbox" name="mec[notifications][event_soldout][status]" <?php if(!isset($notifications['event_soldout']['status']) || $notifications['event_soldout']['status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable event soldout notification', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <p class="mec-col-12 description"><?php esc_html_e('Sent to admin and / or event organizer to notify them that an event is soldout.', 'modern-events-calendar-lite'); ?></p>
                                </div>
                            </div>
                            <div id="mec_notification_event_soldout_container_toggle" class="<?php if(isset($notifications['event_soldout']) and isset($notifications['event_soldout']['status']) and !$notifications['event_soldout']['status']) echo 'mec-util-hidden'; ?>">
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_event_soldout_subject"><?php esc_html_e('Email Subject', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][event_soldout][subject]" id="mec_notifications_event_soldout_subject" value="<?php echo (isset($notifications['event_soldout']['subject']) ? esc_attr(stripslashes($notifications['event_soldout']['subject'])) : ''); ?>" />
                                    </div>
                                </div>

                                <!-- Start Receiver Users -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_event_soldout_receiver_users"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                        $users = $notifications['event_soldout']['receiver_users'] ?? [];
                                        echo MEC_kses::form($this->main->get_users_dropdown($users, 'event_soldout'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select users to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Users -->

                                <!-- Start Receiver Roles -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_event_soldout_receiver_roles"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                        $roles = $notifications['event_soldout']['receiver_roles'] ?? [];
                                        echo MEC_kses::form($this->main->get_roles_dropdown($roles, 'event_soldout'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select a user role to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Roles -->

                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_event_soldout_recipients"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][event_soldout][recipients]" id="mec_notifications_event_soldout_recipients" value="<?php echo (isset($notifications['event_soldout']['recipients']) ? esc_attr($notifications['event_soldout']['recipients']) : ''); ?>" />
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Insert the comma separated email addresses for multiple recipients.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_event_soldout_send_to_admin">
                                            <input type="hidden" name="mec[notifications][event_soldout][send_to_admin]" value="0" />
                                            <input type="checkbox" name="mec[notifications][event_soldout][send_to_admin]" value="1" id="mec_notifications_event_soldout_send_to_admin" <?php echo ((!isset($notifications['event_soldout']['send_to_admin']) || $notifications['event_soldout']['send_to_admin'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to admin', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_event_soldout_send_to_organizer">
                                            <input type="checkbox" name="mec[notifications][event_soldout][send_to_organizer]" value="1" id="mec_notifications_event_soldout_send_to_organizer" <?php echo ((isset($notifications['event_soldout']['send_to_organizer']) and $notifications['event_soldout']['send_to_organizer'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to event organizer', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>

                                <?php if($additional_organizers): ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_event_soldout_send_to_additional_organizers">
                                            <input type="checkbox" name="mec[notifications][event_soldout][send_to_additional_organizers]" value="1" id="mec_notifications_event_soldout_send_to_additional_organizers" <?php echo ((isset($notifications['event_soldout']['send_to_additional_organizers']) and $notifications['event_soldout']['send_to_additional_organizers'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send the email to additional organizers', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="mec-form-row">
                                    <label for="mec_notifications_event_soldout_content"><?php esc_html_e('Email Content', 'modern-events-calendar-lite'); ?></label>
                                    <?php wp_editor((isset($notifications['event_soldout']) ? stripslashes($notifications['event_soldout']['content']) : ''), 'mec_notifications_event_soldout_content', array('textarea_name'=>'mec[notifications][event_soldout][content]')); ?>
                                </div>

                                <?php
                                    $section = 'event_soldout';
                                    do_action('mec_display_notification_settings',$notifications,$section);
                                ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <p class="description"><?php esc_html_e('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                        <ul>
                                            <li><span>%%name%%</span>: <?php esc_html_e('Full name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%first_name%%</span>: <?php esc_html_e('First name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%last_name%%</span>: <?php esc_html_e('Last name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%user_email%%</span>: <?php esc_html_e('Email of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date%%</span>: <?php esc_html_e('Booked date of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_time%%</span>: <?php esc_html_e('Booked time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime%%</span>: <?php esc_html_e('Booked date and time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_other_datetimes%%</span>: <?php esc_html_e('Other date and times of booking for multiple date booking system', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_name%%</span>: <?php esc_html_e('Your website title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_url%%</span>: <?php esc_html_e('Your website URL', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_description%%</span>: <?php esc_html_e('Your website description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_title%%</span>: <?php esc_html_e('Event title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_description%%</span>: <?php esc_html_e('Event Description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_tags%%</span>: <?php esc_html_e('Event Tags', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_labels%%</span>: <?php esc_html_e('Event Labels', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_categories%%</span>: <?php esc_html_e('Event Categories', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_cost%%</span>: <?php esc_html_e('Event Cost', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_link%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_speaker_name%%</span>: <?php esc_html_e('Speaker name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_name%%</span>: <?php esc_html_e('Organizer name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_tel%%</span>: <?php esc_html_e('Organizer tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_email%%</span>: <?php esc_html_e('Organizer email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_url%%</span>: <?php esc_html_e('Organizer url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_name%%</span>: <?php esc_html_e('Additional organizers name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_tel%%</span>: <?php esc_html_e('Additional organizers tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_email%%</span>: <?php esc_html_e('Additional organizers email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_url%%</span>: <?php esc_html_e('Additional organizers url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_name%%</span>: <?php esc_html_e('Location name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_address%%</span>: <?php esc_html_e('Location address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_name%%</span>: <?php esc_html_e('Additional locations name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_address%%</span>: <?php esc_html_e('Additional locations address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_featured_image%%</span>: <?php esc_html_e('Featured image of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_more_info%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_info%%</span>: <?php esc_html_e('Event more info link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%online_link%%</span>: <?php esc_html_e('Event online link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%admin_link%%</span>: <?php esc_html_e('Admin booking management link.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%total_attendees%%</span>: <?php esc_html_e('Total attendees of current booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%amount_tickets%%</span>: <?php esc_html_e('Amount of Booked Tickets (Total attendees of all bookings)', 'modern-events-calendar-lite'); ?></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="booking_reminder" class="mec-options-fields">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('Booking Reminder', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label>
                                        <input type="hidden" name="mec[notifications][booking_reminder][status]" value="0" />
                                        <input onchange="jQuery('#mec_notification_booking_reminder_container_toggle').toggle();" value="1" type="checkbox" name="mec[notifications][booking_reminder][status]" <?php if(isset($notifications['booking_reminder']) and $notifications['booking_reminder']['status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable booking reminder notification', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                            </div>
                            <div id="mec_notification_booking_reminder_container_toggle" class="<?php if((isset($notifications['booking_reminder']) and !$notifications['booking_reminder']['status']) or !isset($notifications['booking_reminder'])) echo 'mec-util-hidden'; ?>">
                                <div class="mec-form-row">
                                    <?php $cron = MEC_ABSPATH.'app'.DS.'crons'.DS.'booking-reminder.php'; ?>
                                    <p class="mec-col-12 description"><strong><?php esc_html_e('Important Note', 'modern-events-calendar-lite'); ?>: </strong><?php echo sprintf(esc_html__("Set a cronjob to call %s file once per hour otherwise it won't send the reminders. Please note that you should call this file %s otherwise it may send the reminders multiple times.", 'modern-events-calendar-lite'), '<code>'.esc_html($cron).'</code>', '<strong>'.esc_html__('only once per hour', 'modern-events-calendar-lite').'</strong>'); ?></p>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_reminder_subject"><?php esc_html_e('Email Subject', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][booking_reminder][subject]" id="mec_notifications_booking_reminder_subject" value="<?php echo ((isset($notifications['booking_reminder']) and isset($notifications['booking_reminder']['subject'])) ? stripslashes($notifications['booking_reminder']['subject']) : ''); ?>" />
                                    </div>
                                </div>

                                <!-- Start Receiver Users -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_reminder_receiver_users"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                            $users = $notifications['booking_reminder']['receiver_users'] ?? [];
                                            echo MEC_kses::form($this->main->get_users_dropdown($users, 'booking_reminder'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select users to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Users -->

                                <!-- Start Receiver Roles -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_reminder_receiver_roles"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                            $roles = $notifications['booking_reminder']['receiver_roles'] ?? [];
                                            echo MEC_kses::form($this->main->get_roles_dropdown($roles, 'booking_reminder'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select a user role to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Roles -->

                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_reminder_recipients"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][booking_reminder][recipients]" id="mec_notifications_booking_reminder_recipients" value="<?php echo ((isset($notifications['booking_reminder']) and isset($notifications['booking_reminder']['recipients'])) ? $notifications['booking_reminder']['recipients'] : ''); ?>" />
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Insert the comma separated email addresses for multiple recipients.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_reminder_hours"><?php esc_html_e('Hours', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][booking_reminder][hours]" id="mec_notifications_booking_reminder_hours" value="<?php echo ((isset($notifications['booking_reminder']) and isset($notifications['booking_reminder']['hours'])) ? $notifications['booking_reminder']['hours'] : '24,72,168'); ?>" />
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Reminder hours', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Insert the comma separated hours number to trigger the cron job', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_booking_reminder_send_single_one_email">
                                            <input type="checkbox" name="mec[notifications][booking_reminder][send_single_one_email]" value="1" id="mec_notifications_booking_reminder_send_single_one_email" <?php echo ((isset($notifications['booking_reminder']['send_single_one_email']) and $notifications['booking_reminder']['send_single_one_email'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send one single email only to first attendee', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <label for="mec_notifications_booking_reminder_content"><?php esc_html_e('Email Content', 'modern-events-calendar-lite'); ?></label>
                                    <?php wp_editor((isset($notifications['booking_reminder']) ? stripslashes($notifications['booking_reminder']['content']) : ''), 'mec_notifications_booking_reminder_content', array('textarea_name'=>'mec[notifications][booking_reminder][content]')); ?>
                                </div>

                                <?php
                                    $section = 'booking_reminder';
                                    do_action('mec_display_notification_settings',$notifications,$section);
                                ?>

                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <p class="description"><?php esc_html_e('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                        <ul>
                                            <li><span>%%name%%</span>: <?php esc_html_e('Full name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%first_name%%</span>: <?php esc_html_e('First name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%last_name%%</span>: <?php esc_html_e('Last name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%user_email%%</span>: <?php esc_html_e('Email of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date%%</span>: <?php esc_html_e('Booked date of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_time%%</span>: <?php esc_html_e('Booked time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime%%</span>: <?php esc_html_e('Booked date and time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_other_datetimes%%</span>: <?php esc_html_e('Other date and times of booking for multiple date booking system', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date_next_occurrences%%</span>: <?php esc_html_e('Date of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime_next_occurrences%%</span>: <?php esc_html_e('Date and Time of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_price%%</span>: <?php esc_html_e('Booking Price', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_payable%%</span>: <?php esc_html_e('Booking Payable', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_order_time%%</span>: <?php esc_html_e('Date and time of booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_name%%</span>: <?php esc_html_e('Your website title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_url%%</span>: <?php esc_html_e('Your website URL', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_description%%</span>: <?php esc_html_e('Your website description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_title%%</span>: <?php esc_html_e('Event title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_description%%</span>: <?php esc_html_e('Event Description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_tags%%</span>: <?php esc_html_e('Event Tags', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_labels%%</span>: <?php esc_html_e('Event Labels', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_categories%%</span>: <?php esc_html_e('Event Categories', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_cost%%</span>: <?php esc_html_e('Event Cost', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_link%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_speaker_name%%</span>: <?php esc_html_e('Speaker name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_name%%</span>: <?php esc_html_e('Organizer name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_tel%%</span>: <?php esc_html_e('Organizer tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_email%%</span>: <?php esc_html_e('Organizer email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_url%%</span>: <?php esc_html_e('Organizer url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_name%%</span>: <?php esc_html_e('Additional organizers name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_tel%%</span>: <?php esc_html_e('Additional organizers tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_email%%</span>: <?php esc_html_e('Additional organizers email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_url%%</span>: <?php esc_html_e('Additional organizers url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_name%%</span>: <?php esc_html_e('Location name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_address%%</span>: <?php esc_html_e('Location address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_name%%</span>: <?php esc_html_e('Additional locations name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_address%%</span>: <?php esc_html_e('Additional locations address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_featured_image%%</span>: <?php esc_html_e('Featured image of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_more_info%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_info%%</span>: <?php esc_html_e('Event more info link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%online_link%%</span>: <?php esc_html_e('Event online link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%attendees_full_info%%</span>: <?php esc_html_e('Full Attendee info such as booking form data, name, email etc.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%all_bfixed_fields%%</span>: <?php esc_html_e('All booking fixed fields data.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_id%%</span>: <?php esc_html_e('Booking ID', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_transaction_id%%</span>: <?php esc_html_e('Transaction ID of Booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%cancellation_link%%</span>: <?php esc_html_e('Booking cancellation link.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%invoice_link%%</span>: <?php esc_html_e('Invoice Link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%total_attendees%%</span>: <?php esc_html_e('Total attendees of current booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%amount_tickets%%</span>: <?php esc_html_e('Amount of Booked Tickets (Total attendees of all bookings)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name%%</span>: <?php esc_html_e('Ticket name', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_time%%</span>: <?php esc_html_e('Ticket time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name_time%%</span>: <?php esc_html_e('Ticket name & time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_private_description%%</span>: <?php esc_html_e('Ticket private description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_variations%%</span>: <?php esc_html_e('Ticket Variations', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%payment_gateway%%</span>: <?php esc_html_e('Payment Gateway', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%dl_file%%</span>: <?php esc_html_e('Link to the downloadable file', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ics_link%%</span>: <?php esc_html_e('Download ICS file', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ics_link_all_occurrences%%</span>: <?php esc_html_e('Download ICS file for all occurrences', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%google_calendar_link%%</span>: <?php esc_html_e('Add to Google Calendar', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%google_calendar_link_next_occurrences%%</span>: <?php esc_html_e('Add to Google Calendar Links for next 20 occurrences', 'modern-events-calendar-lite'); ?></li>
                                            <?php do_action('mec_extra_field_notifications', $section); ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="attendee_report" class="mec-options-fields">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('Attendee Report', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label>
                                        <input type="hidden" name="mec[notifications][attendee_report][status]" value="0" />
                                        <input onchange="jQuery('#mec_notification_attendee_report_container_toggle').toggle();" value="1" type="checkbox" name="mec[notifications][attendee_report][status]" <?php if(isset($notifications['attendee_report']) and $notifications['attendee_report']['status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable attendee report notification', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                            </div>
                            <div id="mec_notification_attendee_report_container_toggle" class="<?php if((isset($notifications['attendee_report']) and !$notifications['attendee_report']['status']) or !isset($notifications['attendee_report'])) echo 'mec-util-hidden'; ?>">
                                <div class="mec-form-row">
                                    <?php $cron = MEC_ABSPATH.'app'.DS.'crons'.DS.'attendee-report.php'; ?>
                                    <p class="mec-col-12 description"><strong><?php esc_html_e('Important Note', 'modern-events-calendar-lite'); ?>: </strong><?php echo sprintf(esc_html__("Set a cronjob to call %s file once per hour otherwise it won't send the reports. Please note that you should call this file %s otherwise it may send the reports multiple times.", 'modern-events-calendar-lite'), '<code>'.esc_html($cron).'</code>', '<strong>'.esc_html__('only once per hour', 'modern-events-calendar-lite').'</strong>'); ?></p>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_attendee_report_subject"><?php esc_html_e('Email Subject', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][attendee_report][subject]" id="mec_notifications_attendee_report_subject" value="<?php echo ((isset($notifications['attendee_report']) and isset($notifications['attendee_report']['subject'])) ? stripslashes($notifications['attendee_report']['subject']) : ''); ?>" />
                                    </div>
                                </div>

                                <!-- Start Receiver Users -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_attendee_report_receiver_users"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                            $users = $notifications['attendee_report']['receiver_users'] ?? [];
                                            echo MEC_kses::form($this->main->get_users_dropdown($users, 'attendee_report'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select users to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Users -->

                                <!-- Start Receiver Roles -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_attendee_report_receiver_roles"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                            $roles = $notifications['attendee_report']['receiver_roles'] ?? [];
                                            echo MEC_kses::form($this->main->get_roles_dropdown($roles, 'attendee_report'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select a user role to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Roles -->

                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_attendee_report_recipients"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][attendee_report][recipients]" id="mec_notifications_attendee_report_recipients" value="<?php echo ((isset($notifications['attendee_report']) and isset($notifications['attendee_report']['recipients'])) ? $notifications['attendee_report']['recipients'] : ''); ?>" />
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Insert the comma separated email addresses for multiple recipients.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_attendee_report_hours"><?php esc_html_e('Hours', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][attendee_report][hours]" id="mec_notifications_attendee_report_hours" value="<?php echo ((isset($notifications['attendee_report']) and isset($notifications['attendee_report']['hours'])) ? $notifications['attendee_report']['hours'] : '24'); ?>" />
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Report hours', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Insert the comma separated hours number to trigger the cron job', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <label for="mec_notifications_attendee_report_content"><?php esc_html_e('Email Content', 'modern-events-calendar-lite'); ?></label>
                                    <?php wp_editor((isset($notifications['attendee_report']) ? stripslashes($notifications['attendee_report']['content']) : ''), 'mec_notifications_attendee_report_content', array('textarea_name'=>'mec[notifications][attendee_report][content]')); ?>
                                </div>

                                <?php
                                    $section = 'attendee_report';
                                    do_action('mec_display_notification_settings',$notifications,$section);
                                ?>

                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <p class="description"><?php esc_html_e('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                        <ul>
                                            <li><span>%%event_title%%</span>: <?php esc_html_e('Event title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_datetime%%</span>: <?php esc_html_e('Event Start Date & Time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_datetime%%</span>: <?php esc_html_e('Event End Date & Time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%total_attendees%%</span>: <?php esc_html_e('Total attendees of current booking', 'modern-events-calendar-lite'); ?></li>
                                            <?php do_action('mec_extra_field_notifications', $section); ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="booking_moved" class="mec-options-fields">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('Booking Reschedule', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label>
                                        <input type="hidden" name="mec[notifications][booking_moved][status]" value="0" />
                                        <input onchange="jQuery('#mec_notification_booking_moved_container_toggle').toggle();" value="1" type="checkbox" name="mec[notifications][booking_moved][status]" <?php if(isset($notifications['booking_moved']) and $notifications['booking_moved']['status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable booking reschedule notification', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                            </div>
                            <div id="mec_notification_booking_moved_container_toggle" class="<?php if((isset($notifications['booking_moved']) and !$notifications['booking_moved']['status']) or !isset($notifications['booking_moved'])) echo 'mec-util-hidden'; ?>">
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_moved_subject"><?php esc_html_e('Email Subject', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][booking_moved][subject]" id="mec_notifications_booking_moved_subject" value="<?php echo ((isset($notifications['booking_moved']) and isset($notifications['booking_moved']['subject'])) ? stripslashes($notifications['booking_moved']['subject']) : ''); ?>" />
                                    </div>
                                </div>

                                <!-- Start Receiver Users -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_moved_receiver_users"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                        $users = $notifications['booking_moved']['receiver_users'] ?? [];
                                        echo MEC_kses::form($this->main->get_users_dropdown($users, 'booking_moved'));
                                        ?>
                                        <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e('Select users to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                    </div>
                                </div>
                                <!-- End Receiver Users -->

                                <!-- Start Receiver Roles -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_moved_receiver_roles"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                        $roles = $notifications['booking_moved']['receiver_roles'] ?? [];
                                        echo MEC_kses::form($this->main->get_roles_dropdown($roles, 'booking_moved'));
                                        ?>
                                        <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e('Select a user role to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                    </div>
                                </div>
                                <!-- End Receiver Roles -->

                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_booking_moved_recipients"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][booking_moved][recipients]" id="mec_notifications_booking_moved_recipients" value="<?php echo ((isset($notifications['booking_moved']) and isset($notifications['booking_moved']['recipients'])) ? $notifications['booking_moved']['recipients'] : ''); ?>" />
                                        <span class="mec-tooltip">
                                        <div class="box left">
                                            <h5 class="title"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e('Insert the comma separated email addresses for multiple recipients.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label for="mec_notifications_booking_moved_send_single_one_email">
                                            <input type="checkbox" name="mec[notifications][booking_moved][send_single_one_email]" value="1" id="mec_notifications_booking_moved_send_single_one_email" <?php echo ((isset($notifications['booking_moved']['send_single_one_email']) and $notifications['booking_moved']['send_single_one_email'] == 1) ? 'checked="checked"' : ''); ?> />
                                            <?php esc_html_e('Send one single email only to first attendee', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <label for="mec_notifications_booking_moved_content"><?php esc_html_e('Email Content', 'modern-events-calendar-lite'); ?></label>
                                    <?php wp_editor((isset($notifications['booking_moved']) ? stripslashes($notifications['booking_moved']['content']) : ''), 'mec_notifications_booking_moved_content', array('textarea_name'=>'mec[notifications][booking_moved][content]')); ?>
                                </div>

                                <?php
                                $section = 'booking_moved';
                                do_action('mec_display_notification_settings',$notifications,$section);
                                ?>

                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <p class="description"><?php esc_html_e('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                        <ul>
                                            <li><span>%%name%%</span>: <?php esc_html_e('Full name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%first_name%%</span>: <?php esc_html_e('First name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%last_name%%</span>: <?php esc_html_e('Last name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%user_email%%</span>: <?php esc_html_e('Email of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date%%</span>: <?php esc_html_e('Booked date of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_time%%</span>: <?php esc_html_e('Booked time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime_prev%%</span>: <?php esc_html_e('Previous booked date and time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime%%</span>: <?php esc_html_e('Booked date and time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_other_datetimes%%</span>: <?php esc_html_e('Other date and times of booking for multiple date booking system', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date_next_occurrences%%</span>: <?php esc_html_e('Date of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime_next_occurrences%%</span>: <?php esc_html_e('Date and Time of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_price%%</span>: <?php esc_html_e('Booking Price', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_payable%%</span>: <?php esc_html_e('Booking Payable', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_order_time%%</span>: <?php esc_html_e('Date and time of booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_name%%</span>: <?php esc_html_e('Your website title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_url%%</span>: <?php esc_html_e('Your website URL', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_description%%</span>: <?php esc_html_e('Your website description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_title%%</span>: <?php esc_html_e('Event title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_description%%</span>: <?php esc_html_e('Event Description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_tags%%</span>: <?php esc_html_e('Event Tags', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_labels%%</span>: <?php esc_html_e('Event Labels', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_categories%%</span>: <?php esc_html_e('Event Categories', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_cost%%</span>: <?php esc_html_e('Event Cost', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_link%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_speaker_name%%</span>: <?php esc_html_e('Speaker name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_name%%</span>: <?php esc_html_e('Organizer name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_tel%%</span>: <?php esc_html_e('Organizer tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_email%%</span>: <?php esc_html_e('Organizer email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_url%%</span>: <?php esc_html_e('Organizer url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_name%%</span>: <?php esc_html_e('Additional organizers name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_tel%%</span>: <?php esc_html_e('Additional organizers tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_email%%</span>: <?php esc_html_e('Additional organizers email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_url%%</span>: <?php esc_html_e('Additional organizers url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_name%%</span>: <?php esc_html_e('Location name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_address%%</span>: <?php esc_html_e('Location address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_name%%</span>: <?php esc_html_e('Additional locations name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_address%%</span>: <?php esc_html_e('Additional locations address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_featured_image%%</span>: <?php esc_html_e('Featured image of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_more_info%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_info%%</span>: <?php esc_html_e('Event more info link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%online_link%%</span>: <?php esc_html_e('Event online link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%attendees_full_info%%</span>: <?php esc_html_e('Full Attendee info such as booking form data, name, email etc.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%all_bfixed_fields%%</span>: <?php esc_html_e('All booking fixed fields data.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_id%%</span>: <?php esc_html_e('Booking ID', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_transaction_id%%</span>: <?php esc_html_e('Transaction ID of Booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%cancellation_link%%</span>: <?php esc_html_e('Booking cancellation link.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%invoice_link%%</span>: <?php esc_html_e('Invoice Link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%total_attendees%%</span>: <?php esc_html_e('Total attendees of current booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%amount_tickets%%</span>: <?php esc_html_e('Amount of Booked Tickets (Total attendees of all bookings)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name%%</span>: <?php esc_html_e('Ticket name', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_time%%</span>: <?php esc_html_e('Ticket time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name_time%%</span>: <?php esc_html_e('Ticket name & time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_private_description%%</span>: <?php esc_html_e('Ticket private description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_variations%%</span>: <?php esc_html_e('Ticket Variations', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%payment_gateway%%</span>: <?php esc_html_e('Payment Gateway', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%dl_file%%</span>: <?php esc_html_e('Link to the downloadable file', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ics_link%%</span>: <?php esc_html_e('Download ICS file', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ics_link_all_occurrences%%</span>: <?php esc_html_e('Download ICS file for all occurrences', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%google_calendar_link%%</span>: <?php esc_html_e('Add to Google Calendar', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%google_calendar_link_next_occurrences%%</span>: <?php esc_html_e('Add to Google Calendar Links for next 20 occurrences', 'modern-events-calendar-lite'); ?></li>
                                            <?php do_action('mec_extra_field_notifications', $section); ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Certificate Send -->
                        <div id="certificate_send" class="mec-options-fields">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('Send Certificate', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <div class="mec-col-3">
                                    <label for="mec_notifications_certificate_send_subject"><?php esc_html_e('Email Subject', 'modern-events-calendar-lite'); ?></label>
                                </div>
                                <div class="mec-col-9">
                                    <input type="text" name="mec[notifications][certificate_send][subject]" id="mec_notifications_certificate_send_subject" value="<?php echo (isset($notifications['certificate_send']['subject']) ? esc_attr(stripslashes($notifications['certificate_send']['subject'])) : ''); ?>" />
                                </div>
                            </div>

                            <!-- Start Receiver Users -->
                            <div class="mec-form-row">
                                <div class="mec-col-3">
                                    <label for="mec_notifications_certificate_send_receiver_users"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></label>
                                </div>
                                <div class="mec-col-9">
                                    <?php
                                    $users = $notifications['certificate_send']['receiver_users'] ?? [];
                                    echo MEC_kses::form($this->main->get_users_dropdown($users, 'certificate_send'));
                                    ?>
                                    <span class="mec-tooltip">
                                    <div class="box left">
                                        <h5 class="title"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></h5>
                                        <div class="content"><p><?php esc_attr_e('Select users to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                    </div>
                                    <i title="" class="dashicons-before dashicons-editor-help"></i>
                                </span>
                                </div>
                            </div>
                            <!-- End Receiver Users -->

                            <!-- Start Receiver Roles -->
                            <div class="mec-form-row">
                                <div class="mec-col-3">
                                    <label for="mec_notifications_certificate_send_receiver_roles"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></label>
                                </div>
                                <div class="mec-col-9">
                                    <?php
                                    $roles = $notifications['certificate_send']['receiver_roles'] ?? [];
                                    echo MEC_kses::form($this->main->get_roles_dropdown($roles, 'certificate_send'));
                                    ?>
                                    <span class="mec-tooltip">
                                    <div class="box left">
                                        <h5 class="title"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></h5>
                                        <div class="content"><p><?php esc_attr_e('Select a user role to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                    </div>
                                    <i title="" class="dashicons-before dashicons-editor-help"></i>
                                </span>
                                </div>
                            </div>
                            <!-- End Receiver Roles -->

                            <div class="mec-form-row">
                                <div class="mec-col-3">
                                    <label for="mec_notifications_certificate_send_recipients"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></label>
                                </div>
                                <div class="mec-col-9">
                                    <input type="text" name="mec[notifications][certificate_send][recipients]" id="mec_notifications_certificate_send_recipients" value="<?php echo (isset($notifications['certificate_send']['recipients']) ? esc_attr($notifications['certificate_send']['recipients']) : ''); ?>" />
                                    <span class="mec-tooltip">
                                    <div class="box left">
                                        <h5 class="title"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></h5>
                                        <div class="content"><p><?php esc_attr_e('Insert the comma separated email addresses for multiple recipients.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                    </div>
                                    <i title="" class="dashicons-before dashicons-editor-help"></i>
                                </span>
                                </div>
                            </div>
                            <div class="mec-form-row">
                                <label for="mec_notifications_certificate_send_content"><?php esc_html_e('Email Content', 'modern-events-calendar-lite'); ?></label>
                                <?php wp_editor(((isset($notifications['certificate_send']) and isset($notifications['certificate_send']['content'])) ? stripslashes($notifications['certificate_send']['content']) : ''), 'mec_notifications_certificate_send_content', array('textarea_name'=>'mec[notifications][certificate_send][content]')); ?>
                            </div>
                            <?php
                            $section = 'certificate_send';
                            do_action('mec_display_notification_settings', $notifications, $section);
                            ?>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <p class="description"><?php esc_html_e('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                    <ul>
                                        <li><span>%%name%%</span>: <?php esc_html_e('Full name of attendee', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%first_name%%</span>: <?php esc_html_e('First name of attendee', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%last_name%%</span>: <?php esc_html_e('Last name of attendee', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%user_email%%</span>: <?php esc_html_e('Email of attendee', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_date%%</span>: <?php esc_html_e('Booked date of event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_time%%</span>: <?php esc_html_e('Booked time of event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_datetime%%</span>: <?php esc_html_e('Booked date and time of event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_other_datetimes%%</span>: <?php esc_html_e('Other date and times of booking for multiple date booking system', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_date_next_occurrences%%</span>: <?php esc_html_e('Date of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_datetime_next_occurrences%%</span>: <?php esc_html_e('Date and Time of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_price%%</span>: <?php esc_html_e('Booking Price', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_payable%%</span>: <?php esc_html_e('Booking Payable', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%book_order_time%%</span>: <?php esc_html_e('Date and time of booking', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%blog_name%%</span>: <?php esc_html_e('Your website title', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%blog_url%%</span>: <?php esc_html_e('Your website URL', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%blog_description%%</span>: <?php esc_html_e('Your website description', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_title%%</span>: <?php esc_html_e('Event title', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_description%%</span>: <?php esc_html_e('Event Description', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_tags%%</span>: <?php esc_html_e('Event Tags', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_labels%%</span>: <?php esc_html_e('Event Labels', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_categories%%</span>: <?php esc_html_e('Event Categories', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_cost%%</span>: <?php esc_html_e('Event Cost', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_link%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_speaker_name%%</span>: <?php esc_html_e('Speaker name of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_organizer_name%%</span>: <?php esc_html_e('Organizer name of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_organizer_tel%%</span>: <?php esc_html_e('Organizer tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_organizer_email%%</span>: <?php esc_html_e('Organizer email of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_organizer_url%%</span>: <?php esc_html_e('Organizer url of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_other_organizers_name%%</span>: <?php esc_html_e('Additional organizers name of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_other_organizers_tel%%</span>: <?php esc_html_e('Additional organizers tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_other_organizers_email%%</span>: <?php esc_html_e('Additional organizers email of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_other_organizers_url%%</span>: <?php esc_html_e('Additional organizers url of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_location_name%%</span>: <?php esc_html_e('Location name of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_location_address%%</span>: <?php esc_html_e('Location address of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_other_locations_name%%</span>: <?php esc_html_e('Additional locations name of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_other_locations_address%%</span>: <?php esc_html_e('Additional locations address of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_featured_image%%</span>: <?php esc_html_e('Featured image of booked event', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_more_info%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%event_other_info%%</span>: <?php esc_html_e('Event more info link', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%certificate_link%%</span>: <?php esc_html_e('Certificate download / print link', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%online_link%%</span>: <?php esc_html_e('Event online link', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%attendees_full_info%%</span>: <?php esc_html_e('Full Attendee info such as booking form data, name, email etc.', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%all_bfixed_fields%%</span>: <?php esc_html_e('All booking fixed fields data.', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%booking_id%%</span>: <?php esc_html_e('Booking ID', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%booking_transaction_id%%</span>: <?php esc_html_e('Transaction ID of Booking', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%cancellation_link%%</span>: <?php esc_html_e('Booking cancellation link.', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%invoice_link%%</span>: <?php esc_html_e('Invoice Link', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%total_attendees%%</span>: <?php esc_html_e('Total attendees of current booking', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%amount_tickets%%</span>: <?php esc_html_e('Amount of Booked Tickets (Total attendees of all bookings)', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%ticket_name%%</span>: <?php esc_html_e('Ticket name', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%ticket_time%%</span>: <?php esc_html_e('Ticket time', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%ticket_name_time%%</span>: <?php esc_html_e('Ticket name & time', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%ticket_private_description%%</span>: <?php esc_html_e('Ticket private description', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%ticket_variations%%</span>: <?php esc_html_e('Ticket Variations', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%payment_gateway%%</span>: <?php esc_html_e('Payment Gateway', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%dl_file%%</span>: <?php esc_html_e('Link to the downloadable file', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%ics_link%%</span>: <?php esc_html_e('Download ICS file', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%ics_link_all_occurrences%%</span>: <?php esc_html_e('Download ICS file for all occurrences', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%google_calendar_link%%</span>: <?php esc_html_e('Add to Google Calendar', 'modern-events-calendar-lite'); ?></li>
                                        <li><span>%%google_calendar_link_next_occurrences%%</span>: <?php esc_html_e('Add to Google Calendar Links for next 20 occurrences', 'modern-events-calendar-lite'); ?></li>
                                        <?php do_action('mec_extra_field_notifications', $section); ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <?php endif; ?>

                        <?php do_action('mec_notifications_tabs_content',$notifications); ?>

                        <div id="new_event" class="mec-options-fields  <?php if(isset($this->settings['booking_status']) and $this->settings['booking_status'] == 0) echo 'active'; ?>">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('New Event', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label>
                                        <input type="hidden" name="mec[notifications][new_event][status]" value="0" />
                                        <input onchange="jQuery('#mec_notification_new_event_container_toggle').toggle();" value="1" type="checkbox" name="mec[notifications][new_event][status]" <?php if(isset($notifications['new_event']['status']) and $notifications['new_event']['status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable new event notification', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                            </div>
                            <div id="mec_notification_new_event_container_toggle" class="<?php if((isset($notifications['new_event']) and !$notifications['new_event']['status']) or !isset($notifications['new_event'])) echo 'mec-util-hidden'; ?>">
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label>
                                            <input type="hidden" name="mec[notifications][new_event][send_to_admin]" value="0" />
                                            <input value="1" type="checkbox" name="mec[notifications][new_event][send_to_admin]" <?php if((!isset($notifications['new_event']['send_to_admin'])) || $notifications['new_event']['send_to_admin']) echo 'checked="checked"'; ?> /><?php esc_html_e('Send the email to admin', 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                    <p class="mec-col-12 description"><?php esc_html_e('Sent after adding a new event from frontend event submission or from website backend.', 'modern-events-calendar-lite'); ?></p>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <label>
                                            <input type="hidden" name="mec[notifications][new_event][disable_send_notification_if_current_user_or_author_is_admin]" value="0" />
                                            <input value="1" type="checkbox" name="mec[notifications][new_event][disable_send_notification_if_current_user_or_author_is_admin]" <?php if( isset($notifications['new_event']['disable_send_notification_if_current_user_or_author_is_admin']) and $notifications['new_event']['disable_send_notification_if_current_user_or_author_is_admin']) echo 'checked="checked"'; ?> /><?php esc_html_e("Don't notify admins if Super Admin created", 'modern-events-calendar-lite'); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php
                                    $delivery_method = $notifications['new_event']['delivery_method'] ?? 'instant';
                                    $daily_time = $notifications['new_event']['daily_time'] ?? '18:00';
                                ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_new_event_delivery_method"><?php esc_html_e('Delivery Method', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <select id="mec_notifications_new_event_delivery_method" name="mec[notifications][new_event][delivery_method]" onchange="mecToggleNewEventDeliveryMethod(this.value);">
                                            <option value="instant" <?php selected($delivery_method, 'instant'); ?>><?php esc_html_e('Send immediately after event creation', 'modern-events-calendar-lite'); ?></option>
                                            <option value="daily" <?php selected($delivery_method, 'daily'); ?>><?php esc_html_e('Once per day', 'modern-events-calendar-lite'); ?></option>
                                        </select>
                                        <p class="description"><?php esc_html_e('Choose when the New Event notification should be delivered.', 'modern-events-calendar-lite'); ?></p>
                                    </div>
                                </div>
                                <div class="mec-form-row mec-new-event-daily-field <?php echo ($delivery_method === 'daily') ? '' : 'mec-util-hidden'; ?>">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_new_event_daily_time"><?php esc_html_e('Daily send time', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="time" id="mec_notifications_new_event_daily_time" name="mec[notifications][new_event][daily_time]" value="<?php echo esc_attr($daily_time); ?>" />
                                        <p class="description"><?php esc_html_e('Notifications will be sent once per day at this time (site timezone).', 'modern-events-calendar-lite'); ?></p>
                                    </div>
                                </div>
                                <?php $cron = MEC_ABSPATH . 'app' . DS . 'crons' . DS . 'new-event-digest.php'; ?>
                                <div class="mec-form-row mec-new-event-daily-field <?php echo ($delivery_method === 'daily') ? '' : 'mec-util-hidden'; ?>">
                                    <p class="mec-col-12 description"><strong><?php esc_html_e('Important Note', 'modern-events-calendar-lite'); ?>:</strong> <?php echo sprintf(esc_html__('Set a cronjob to call %1$s once per day at or after the selected send time. Running this cron more than once per day may cause duplicate checks but emails will only send once per day.', 'modern-events-calendar-lite'), '<code>' . esc_html($cron) . '</code>'); ?></p>
                                </div>
                                <script type="text/javascript">
                                if (typeof window.mecToggleNewEventDeliveryMethod === 'undefined') {
                                    window.mecToggleNewEventDeliveryMethod = function(value) {
                                        var fields = document.querySelectorAll('.mec-new-event-daily-field');
                                        fields.forEach(function(field) {
                                            if (value === 'daily') field.classList.remove('mec-util-hidden');
                                            else field.classList.add('mec-util-hidden');
                                        });
                                    };
                                    document.addEventListener('DOMContentLoaded', function() {
                                        var selector = document.getElementById('mec_notifications_new_event_delivery_method');
                                        if (selector) window.mecToggleNewEventDeliveryMethod(selector.value);
                                    });
                                }
                                </script>
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_new_event_subject"><?php esc_html_e('Email Subject', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][new_event][subject]" id="mec_notifications_new_event_subject" value="<?php echo (isset($notifications['new_event']['subject']) ? esc_attr(stripslashes($notifications['new_event']['subject'])) : ''); ?>" />
                                    </div>
                                </div>

                                <!-- Start Receiver Users -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_new_event_receiver_users"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                            $users = $notifications['new_event']['receiver_users'] ?? [];
                                            echo MEC_kses::form($this->main->get_users_dropdown($users, 'new_event'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select users to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Users -->

                                <!-- Start Receiver Roles -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_new_event_receiver_roles"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                            $roles = $notifications['new_event']['receiver_roles'] ?? [];
                                            echo MEC_kses::form($this->main->get_roles_dropdown($roles, 'new_event'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select a user role to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Roles -->

                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_new_event_recipients"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][new_event][recipients]" id="mec_notifications_new_event_recipients" value="<?php echo (isset($notifications['new_event']['recipients']) ? esc_attr($notifications['new_event']['recipients']) : ''); ?>" />
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Insert the comma separated email addresses for multiple recipients.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <label for="mec_notifications_new_event_content"><?php esc_html_e('Email Content', 'modern-events-calendar-lite'); ?></label>
                                    <?php wp_editor((isset($notifications['new_event']) ? stripslashes($notifications['new_event']['content']) : ''), 'mec_notifications_new_event_content', array('textarea_name'=>'mec[notifications][new_event][content]')); ?>
                                </div>

                                <?php
                                    $section = 'new_event';
                                    do_action('mec_display_notification_settings',$notifications,$section);
                                ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <p class="description"><?php esc_html_e('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                        <ul>
                                            <li><span>%%event_title%%</span>: <?php esc_html_e('Title of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_description%%</span>: <?php esc_html_e('Event Description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_tags%%</span>: <?php esc_html_e('Event Tags', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_labels%%</span>: <?php esc_html_e('Event Labels', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_categories%%</span>: <?php esc_html_e('Event Categories', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_cost%%</span>: <?php esc_html_e('Event Cost', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_link%%</span>: <?php esc_html_e('Link of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_date%%</span>: <?php esc_html_e('Event Start Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_date%%</span>: <?php esc_html_e('Event End Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_timezone%%</span>: <?php esc_html_e('Event Timezone', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_status%%</span>: <?php esc_html_e('Status of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_note%%</span>: <?php esc_html_e('Event Note', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_name%%</span>: <?php esc_html_e('Your website title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_url%%</span>: <?php esc_html_e('Your website URL', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_description%%</span>: <?php esc_html_e('Your website description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%admin_link%%</span>: <?php esc_html_e('Admin events management link.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%all_events_info%%</span>: <?php esc_html_e('List of all events in the daily digest (available with Once per day delivery).', 'modern-events-calendar-lite'); ?></li>
                                        <?php do_action('mec_extra_field_notifications', $section); ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        </div>

                        <!-- MEC Event Published -->
                        <div id="user_event_publishing" class="mec-options-fields  <?php if(isset($this->settings['booking_status']) and $this->settings['booking_status'] == 0) echo 'active'; ?>">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('User Event Publishing', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label>
                                        <input type="hidden" name="mec[notifications][user_event_publishing][status]" value="0" />
                                        <input onchange="jQuery('#mec_notification_user_event_publishing_container_toggle').toggle();" value="1" type="checkbox" name="mec[notifications][user_event_publishing][status]" <?php if(isset($notifications['user_event_publishing']['status']) and $notifications['user_event_publishing']['status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable user event publishing notification', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <p class="mec-col-12 description"><?php esc_html_e('Sent after publishing a new event from frontend event submission or from website backend.', 'modern-events-calendar-lite'); ?></p>
                                </div>
                            </div>
                            <div id="mec_notification_user_event_publishing_container_toggle" class="<?php if((isset($notifications['user_event_publishing']) and !$notifications['user_event_publishing']['status']) or !isset($notifications['user_event_publishing'])) echo 'mec-util-hidden'; ?>">
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_user_event_publishing_subject"><?php esc_html_e('Email Subject', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][user_event_publishing][subject]" id="mec_notifications_user_event_publishing_subject" value="<?php echo (isset($notifications['user_event_publishing']['subject']) ? esc_attr(stripslashes($notifications['user_event_publishing']['subject'])) : ''); ?>" />
                                    </div>
                                </div>

                                <!-- Start Receiver Users -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_user_event_publishing_receiver_users"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                            $users = $notifications['user_event_publishing']['receiver_users'] ?? [];
                                            echo MEC_kses::form($this->main->get_users_dropdown($users, 'user_event_publishing'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select users to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Users -->

                                <!-- Start Receiver Roles -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_user_event_publishing_receiver_roles"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                            $roles = $notifications['user_event_publishing']['receiver_roles'] ?? [];
                                            echo MEC_kses::form($this->main->get_roles_dropdown($roles, 'user_event_publishing'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select a user role to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Roles -->

                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_user_event_publishing_recipients"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][user_event_publishing][recipients]" id="mec_notifications_user_event_publishing_recipients" value="<?php echo (isset($notifications['user_event_publishing']['recipients']) ? esc_attr($notifications['user_event_publishing']['recipients']) : ''); ?>" />
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Insert the comma separated email addresses for multiple recipients.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <label for="mec_notifications_user_event_publishing_content"><?php esc_html_e('Email Content', 'modern-events-calendar-lite'); ?></label>
                                    <?php wp_editor((isset($notifications['user_event_publishing']) ? stripslashes($notifications['user_event_publishing']['content']) : ''), 'mec_notifications_user_event_publishing_content', array('textarea_name'=>'mec[notifications][user_event_publishing][content]')); ?>
                                </div>
                                <?php
                                    $section = 'user_event_publishing';
                                    do_action('mec_display_notification_settings',$notifications,$section);
                                ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <p class="description"><?php esc_html_e('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                        <ul>
                                            <li><span>%%name%%</span>: <?php esc_html_e('Event sender name', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_title%%</span>: <?php esc_html_e('Title of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_description%%</span>: <?php esc_html_e('Event Description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_tags%%</span>: <?php esc_html_e('Event Tags', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_labels%%</span>: <?php esc_html_e('Event Labels', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_categories%%</span>: <?php esc_html_e('Event Categories', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_cost%%</span>: <?php esc_html_e('Event Cost', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_link%%</span>: <?php esc_html_e('Link of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_date%%</span>: <?php esc_html_e('Event Start Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_date%%</span>: <?php esc_html_e('Event End Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_timezone%%</span>: <?php esc_html_e('Event Timezone', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_status%%</span>: <?php esc_html_e('Status of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_note%%</span>: <?php esc_html_e('Event Note', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_name%%</span>: <?php esc_html_e('Your website title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_url%%</span>: <?php esc_html_e('Your website URL', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_description%%</span>: <?php esc_html_e('Your website description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%admin_link%%</span>: <?php esc_html_e('Admin events management link.', 'modern-events-calendar-lite'); ?></li>
                                            <?php do_action('mec_extra_field_notifications', $section); ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Event Finished -->
                        <div id="event_finished" class="mec-options-fields <?php if(isset($this->settings['booking_status']) and $this->settings['booking_status'] == 0) echo 'active'; ?>">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('Event Finished', 'modern-events-calendar-lite'); ?></h4>
                            <div class="mec-form-row">
                                <div class="mec-col-12">
                                    <label>
                                        <input type="hidden" name="mec[notifications][event_finished][status]" value="0" />
                                        <input onchange="jQuery('#mec_notification_event_finished_container_toggle').toggle();" value="1" type="checkbox" name="mec[notifications][event_finished][status]" <?php if(isset($notifications['event_finished']['status']) and $notifications['event_finished']['status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable event finished notification', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <p class="mec-col-12 description"><?php esc_html_e('It sends after an event finish. You can use it to say thank you to the attendees.', 'modern-events-calendar-lite'); ?></p>
                                </div>
                            </div>
                            <div id="mec_notification_event_finished_container_toggle" class="<?php if((isset($notifications['event_finished']) and isset($notifications['event_finished']['status']) and !$notifications['event_finished']['status']) or !isset($notifications['event_finished'])) echo 'mec-util-hidden'; ?>">

                                <div class="mec-form-row">
                                    <?php $cron = MEC_ABSPATH.'app'.DS.'crons'.DS.'event-finished.php'; ?>
                                    <p class="mec-col-12 description"><strong><?php esc_html_e('Important Note', 'modern-events-calendar-lite'); ?>: </strong><?php echo sprintf(esc_html__("Set a cronjob to call %s file once per hour otherwise it won't send the notifications. Please note that you should call this file %s otherwise it may send the notifications multiple times.", 'modern-events-calendar-lite'), '<code>'.esc_html($cron).'</code>', '<strong>'.esc_html__('only once per hour', 'modern-events-calendar-lite').'</strong>'); ?></p>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_event_finished_subject"><?php esc_html_e('Email Subject', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][event_finished][subject]" id="mec_notifications_event_finished_subject" value="<?php echo (isset($notifications['event_finished']['subject']) ? esc_attr(stripslashes($notifications['event_finished']['subject'])) : ''); ?>" />
                                    </div>
                                </div>

                                <!-- Start Receiver Users -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_event_finished_receiver_users"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                        $users = $notifications['event_finished']['receiver_users'] ?? [];
                                        echo MEC_kses::form($this->main->get_users_dropdown($users, 'event_finished'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select users to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Users -->

                                <!-- Start Receiver Roles -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_event_finished_receiver_roles"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                        $roles = $notifications['event_finished']['receiver_roles'] ?? [];
                                        echo MEC_kses::form($this->main->get_roles_dropdown($roles, 'event_finished'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select a user role to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Roles -->

                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_event_finished_recipients"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][event_finished][recipients]" id="mec_notifications_event_finished_recipients" value="<?php echo (isset($notifications['event_finished']['recipients']) ? esc_attr($notifications['event_finished']['recipients']) : ''); ?>" />
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Insert the comma separated email addresses for multiple recipients.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_event_finished_hour"><?php esc_html_e('Hour', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="number" name="mec[notifications][event_finished][hour]" id="mec_notifications_event_finished_hour" value="<?php echo ((isset($notifications['event_finished']) and isset($notifications['event_finished']['hour'])) ? $notifications['event_finished']['hour'] : '2'); ?>" />
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Send After x Hour', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('It specify the interval between event finish and sending the notification in hour.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <label for="mec_notifications_user_event_publishing_content"><?php esc_html_e('Email Content', 'modern-events-calendar-lite'); ?></label>
                                    <?php wp_editor(((isset($notifications['event_finished']) and isset($notifications['event_finished']['content'])) ? stripslashes($notifications['event_finished']['content']) : ''), 'mec_notifications_event_finished_content', array('textarea_name'=>'mec[notifications][event_finished][content]')); ?>
                                </div>
                                <?php
                                    $section = 'event_finished';
                                    do_action('mec_display_notification_settings', $notifications, $section);
                                ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <p class="description"><?php esc_html_e('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                        <ul>
                                            <li><span>%%name%%</span>: <?php esc_html_e('Full name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%first_name%%</span>: <?php esc_html_e('First name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%last_name%%</span>: <?php esc_html_e('Last name of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%user_email%%</span>: <?php esc_html_e('Email of attendee', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date%%</span>: <?php esc_html_e('Booked date of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_time%%</span>: <?php esc_html_e('Booked time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime%%</span>: <?php esc_html_e('Booked date and time of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_other_datetimes%%</span>: <?php esc_html_e('Other date and times of booking for multiple date booking system', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_date_next_occurrences%%</span>: <?php esc_html_e('Date of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_datetime_next_occurrences%%</span>: <?php esc_html_e('Date and Time of next 20 occurrences of booked event (including the booked date)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_price%%</span>: <?php esc_html_e('Booking Price', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_payable%%</span>: <?php esc_html_e('Booking Payable', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%book_order_time%%</span>: <?php esc_html_e('Date and time of booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_name%%</span>: <?php esc_html_e('Your website title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_url%%</span>: <?php esc_html_e('Your website URL', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_description%%</span>: <?php esc_html_e('Your website description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_title%%</span>: <?php esc_html_e('Event title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_description%%</span>: <?php esc_html_e('Event Description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_tags%%</span>: <?php esc_html_e('Event Tags', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_labels%%</span>: <?php esc_html_e('Event Labels', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_categories%%</span>: <?php esc_html_e('Event Categories', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_cost%%</span>: <?php esc_html_e('Event Cost', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_link%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_speaker_name%%</span>: <?php esc_html_e('Speaker name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_name%%</span>: <?php esc_html_e('Organizer name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_tel%%</span>: <?php esc_html_e('Organizer tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_email%%</span>: <?php esc_html_e('Organizer email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_organizer_url%%</span>: <?php esc_html_e('Organizer url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_name%%</span>: <?php esc_html_e('Additional organizers name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_tel%%</span>: <?php esc_html_e('Additional organizers tel of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_email%%</span>: <?php esc_html_e('Additional organizers email of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_organizers_url%%</span>: <?php esc_html_e('Additional organizers url of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_name%%</span>: <?php esc_html_e('Location name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_location_address%%</span>: <?php esc_html_e('Location address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_name%%</span>: <?php esc_html_e('Additional locations name of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_locations_address%%</span>: <?php esc_html_e('Additional locations address of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_featured_image%%</span>: <?php esc_html_e('Featured image of booked event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_more_info%%</span>: <?php esc_html_e('Event link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_other_info%%</span>: <?php esc_html_e('Event more info link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%online_link%%</span>: <?php esc_html_e('Event online link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%attendees_full_info%%</span>: <?php esc_html_e('Full Attendee info such as booking form data, name, email etc.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%all_bfixed_fields%%</span>: <?php esc_html_e('All booking fixed fields data.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_id%%</span>: <?php esc_html_e('Booking ID', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%booking_transaction_id%%</span>: <?php esc_html_e('Transaction ID of Booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%cancellation_link%%</span>: <?php esc_html_e('Booking cancellation link.', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%invoice_link%%</span>: <?php esc_html_e('Invoice Link', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%total_attendees%%</span>: <?php esc_html_e('Total attendees of current booking', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%amount_tickets%%</span>: <?php esc_html_e('Amount of Booked Tickets (Total attendees of all bookings)', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name%%</span>: <?php esc_html_e('Ticket name', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_time%%</span>: <?php esc_html_e('Ticket time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_name_time%%</span>: <?php esc_html_e('Ticket name & time', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_private_description%%</span>: <?php esc_html_e('Ticket private description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ticket_variations%%</span>: <?php esc_html_e('Ticket Variations', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%payment_gateway%%</span>: <?php esc_html_e('Payment Gateway', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%dl_file%%</span>: <?php esc_html_e('Link to the downloadable file', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ics_link%%</span>: <?php esc_html_e('Download ICS file', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%ics_link_all_occurrences%%</span>: <?php esc_html_e('Download ICS file for all occurrences', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%google_calendar_link%%</span>: <?php esc_html_e('Add to Google Calendar', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%google_calendar_link_next_occurrences%%</span>: <?php esc_html_e('Add to Google Calendar Links for next 20 occurrences', 'modern-events-calendar-lite'); ?></li>
                                            <?php do_action('mec_extra_field_notifications', $section); ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div id="suggest_event" class="mec-options-fields  <?php if(isset($this->settings['booking_status']) and $this->settings['booking_status'] == 0) echo 'active'; ?>">

                            <h4 class="mec-form-subtitle"><?php esc_html_e('Suggest Event', 'modern-events-calendar-lite'); ?></h4>
                            <div>
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_suggest_event_subject"><?php esc_html_e('Email Subject', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][suggest_event][subject]" id="mec_notifications_suggest_event_subject" value="<?php echo (isset($notifications['suggest_event']['subject']) ? esc_attr(stripslashes($notifications['suggest_event']['subject'])) : ''); ?>" />
                                    </div>
                                </div>

                                <!-- Start Receiver Users -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_suggest_event_receiver_users"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                        $users = $notifications['suggest_event']['receiver_users'] ?? [];
                                        echo MEC_kses::form($this->main->get_users_dropdown($users, 'suggest_event'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Users', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select users to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Users -->

                                <!-- Start Receiver Roles -->
                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_suggest_event_receiver_roles"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <?php
                                        $roles = $notifications['suggest_event']['receiver_roles'] ?? [];
                                        echo MEC_kses::form($this->main->get_roles_dropdown($roles, 'suggest_event'));
                                        ?>
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Receiver Roles', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Select a user role to send a copy of this email to them.', 'modern-events-calendar-lite'); ?></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <!-- End Receiver Roles -->

                                <div class="mec-form-row">
                                    <div class="mec-col-3">
                                        <label for="mec_notifications_suggest_event_recipients"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></label>
                                    </div>
                                    <div class="mec-col-9">
                                        <input type="text" name="mec[notifications][suggest_event][recipients]" id="mec_notifications_suggest_event_recipients" value="<?php echo (isset($notifications['suggest_event']['recipients']) ? esc_attr($notifications['suggest_event']['recipients']) : ''); ?>" />
                                        <span class="mec-tooltip">
                                            <div class="box left">
                                                <h5 class="title"><?php esc_html_e('Custom Recipients', 'modern-events-calendar-lite'); ?></h5>
                                                <div class="content"><p><?php esc_attr_e('Insert the comma separated email addresses for multiple recipients.', 'modern-events-calendar-lite'); ?><a href="https://webnus.net/dox/modern-events-calendar/event-notifications/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                            </div>
                                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mec-form-row">
                                    <label for="mec_notifications_suggest_event_content"><?php esc_html_e('Email Content', 'modern-events-calendar-lite'); ?></label>
                                    <?php wp_editor((isset($notifications['suggest_event']) ? stripslashes($notifications['suggest_event']['content']) : ''), 'mec_notifications_suggest_event_content', array('textarea_name'=>'mec[notifications][suggest_event][content]')); ?>
                                </div>

                                <?php
                                $section = 'suggest_event';
                                do_action('mec_display_notification_settings', $notifications, $section);
                                ?>
                                <div class="mec-form-row">
                                    <div class="mec-col-12">
                                        <p class="description"><?php esc_html_e('You can use the following placeholders', 'modern-events-calendar-lite'); ?></p>
                                        <ul>
                                            <li><span>%%event_title%%</span>: <?php esc_html_e('Title of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_description%%</span>: <?php esc_html_e('Event Description', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_tags%%</span>: <?php esc_html_e('Event Tags', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_labels%%</span>: <?php esc_html_e('Event Labels', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_categories%%</span>: <?php esc_html_e('Event Categories', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_cost%%</span>: <?php esc_html_e('Event Cost', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_link%%</span>: <?php esc_html_e('Link of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_start_date%%</span>: <?php esc_html_e('Event Start Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_end_date%%</span>: <?php esc_html_e('Event End Date', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_timezone%%</span>: <?php esc_html_e('Event Timezone', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_status%%</span>: <?php esc_html_e('Status of event', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%event_note%%</span>: <?php esc_html_e('Event Note', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_name%%</span>: <?php esc_html_e('Your website title', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_url%%</span>: <?php esc_html_e('Your website URL', 'modern-events-calendar-lite'); ?></li>
                                            <li><span>%%blog_description%%</span>: <?php esc_html_e('Your website description', 'modern-events-calendar-lite'); ?></li>
                                            <?php do_action('mec_extra_field_notifications', $section); ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <?php if($this->getPRO()): ?>
                            <div id="auto_emails_option" class="mec-options-fields">
                                <h4 class="mec-form-subtitle"><?php esc_html_e('Auto Emails', 'modern-events-calendar-lite'); ?></h4>
                                <div class="mec-form-row">
                                    <label>
                                        <input type="hidden" name="mec[settings][auto_emails_module_status]" value="0" />
                                        <input onchange="jQuery('#mec_auto_emails_module_container_toggle').toggle();" value="1" type="checkbox" name="mec[settings][auto_emails_module_status]" <?php if(isset($settings['auto_emails_module_status']) and $settings['auto_emails_module_status']) echo 'checked="checked"'; ?> /><?php esc_html_e('Enable Auto Emails', 'modern-events-calendar-lite'); ?>
                                    </label>
                                    <p><?php esc_attr_e("After enabling and saving the settings, you should reload the page to see a new menu on the Dashboard > MEC", 'modern-events-calendar-lite'); ?></p>
                                </div>
                                <div id="mec_auto_emails_module_container_toggle" class="<?php if((isset($settings['auto_emails_module_status']) and !$settings['auto_emails_module_status']) or !isset($settings['auto_emails_module_status'])) echo 'mec-util-hidden'; ?>">
                                    <?php $cron = MEC_ABSPATH.'app'.DS.'crons'.DS.'auto-emails.php'; ?>
                                    <p id="mec_auto_emails_cron" class="mec-col-12"><strong><?php esc_html_e('Important Note', 'modern-events-calendar-lite'); ?>: </strong><?php echo sprintf(esc_html__("Set a cronjob to call %s file by php once per minute otherwise it won't send the emails.", 'modern-events-calendar-lite'), '<code>'.esc_html($cron).'</code>'); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mec-options-fields">
                            <?php wp_nonce_field('mec_options_form'); ?>
                            <?php if($multilingual): ?>
                            <input name="mec_locale" type="hidden" value="<?php echo esc_attr($locale); ?>" />
                            <?php endif; ?>
                            <button style="display: none;" id="mec_notifications_form_button" class="button button-primary mec-button-primary" type="submit"><?php esc_html_e('Save Changes', 'modern-events-calendar-lite'); ?></button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="wns-be-footer">
        <a href="" id="" class="dpr-btn dpr-save-btn"><?php esc_html_e('Save Changes', 'modern-events-calendar-lite'); ?></a>
    </div>

</div>

<?php
$this->getFactory()->params('footer', function()
{
    ?>
    <script>
    jQuery(document).ready(function()
    {
        jQuery(".dpr-save-btn").on('click', function(event)
        {
            event.preventDefault();
            jQuery("#mec_notifications_form_button").trigger('click');
        });
    });

    jQuery("#mec_notifications_form").on('submit', function(event)
    {
        event.preventDefault();

        <?php
            $notifications = [
                "booking_notification",
                "email_verification",
                "booking_confirmation",
                "cancellation_notification",
                "booking_rejection",
                "admin_notification",
                "booking_reminder",
                "attendee_report",
                "booking_moved",
                "event_finished",
                "new_event",
                "suggest_event",
                "user_event_publishing",
                "event_soldout",
                "certificate_send",
            ];

            $content_type = apply_filters('mec_settings_notifications_js_content_types', [""]);
            $notifications = apply_filters('mec_settings_notifications_js_notifications', $notifications);
        ?>
        var notifications = <?php echo json_encode($notifications); ?>;
        var content_types = <?php echo json_encode($content_type); ?>;

        jQuery.each(notifications,function(i,notification_type)
        {
            jQuery.each(content_types,function(j,type)
            {
                jQuery("#mec_notifications_"+notification_type+type+"_content-html").click();
                jQuery("#mec_notifications_"+notification_type+type+"_content-tmce").click();
            });
        });

        <?php do_action( 'mec_notification_menu_js' ); ?>
    });
    </script>

    <script>
    jQuery("#mec_notifications_form").on('submit', function(event)
    {
        event.preventDefault();

        // Add loading Class to the button
        jQuery(".dpr-save-btn").addClass('loading').text("<?php echo esc_js(esc_attr__('Saved', 'modern-events-calendar-lite')); ?>");
        jQuery('<div class="wns-saved-settings"><?php echo esc_js(esc_attr__('Settings Saved!', 'modern-events-calendar-lite')); ?></div>').insertBefore('#wns-be-content');

        if(jQuery(".mec-purchase-verify").text() != '<?php echo esc_js(esc_attr__('Verified', 'modern-events-calendar-lite')); ?>')
        {
            jQuery(".mec-purchase-verify").text("<?php echo esc_js(esc_attr__('Checking ...', 'modern-events-calendar-lite')); ?>");
        }

        var settings = jQuery("#mec_notifications_form").serialize();
        jQuery.ajax(
        {
            type: "POST",
            url: ajaxurl,
            data: "action=mec_save_notifications&"+settings,
            beforeSend: function()
            {
                jQuery('.wns-be-main').append('<div class="mec-loarder-wrap mec-settings-loader"><div class="mec-loarder"><div></div><div></div><div></div></div></div>');
            },
            success: function()
            {
                // Remove the loading Class to the button
                setTimeout(function()
                {
                    jQuery(".dpr-save-btn").removeClass('loading').text("<?php echo esc_js(esc_attr__('Save Changes', 'modern-events-calendar-lite')); ?>");
                    jQuery('.wns-saved-settings').remove();
                    jQuery('.mec-loarder-wrap').remove();
                    if(jQuery(".mec-purchase-verify").text() != '<?php echo esc_js(esc_attr__('Verified', 'modern-events-calendar-lite')); ?>')
                    {
                        jQuery(".mec-purchase-verify").text("<?php echo esc_js(esc_attr__('Please Refresh Page', 'modern-events-calendar-lite')); ?>");
                    }
                }, 1000);
            },
            error: function(jqXHR, textStatus, errorThrown)
            {
                // Remove the loading Class to the button
                setTimeout(function()
                {
                    jQuery(".dpr-save-btn").removeClass('loading').text("<?php echo esc_js(esc_attr__('Save Changes', 'modern-events-calendar-lite')); ?>");
                    jQuery('.wns-saved-settings').remove();
                    jQuery('.mec-loarder-wrap').remove();
                }, 1000);
            }
        });
    });
    </script>
    <?php
});

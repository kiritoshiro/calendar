<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC Notifications Per Event class.
 * @author Webnus <info@webnus.net>
 */
class MEC_feature_notifications extends MEC_base
{
    public $factory;
    public $main;
    public $settings;
    public $notif_settings;

    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct()
    {
        // Import MEC Factory
        $this->factory = $this->getFactory();

        // Import MEC Main
        $this->main = $this->getMain();

        // MEC Settings
        $this->settings = $this->main->get_settings();

        // MEC Notification Settings
        $this->notif_settings = $this->main->get_notifications();
    }

    /**
     * Initialize notifications feature
     * @author Webnus <info@webnus.net>
     */
    public function init()
    {
        // Module is disabled
        if(!isset($this->settings['notif_per_event']) || !$this->settings['notif_per_event']) return;

        $this->factory->action('mec_metabox_details', [$this, 'meta_box_notifications'], 30);
    }

    /**
     * Show notification meta box
     * @author Webnus <info@webnus.net>
     * @param $post
     */
    public function meta_box_notifications($post)
    {
        $values = get_post_meta($post->ID, 'mec_notifications', true);
        if(!is_array($values)) $values = [];

        $notifications = $this->get_notifications();
        $hours_notifications = ['booking_reminder', 'attendee_report'];
        $hours_defaults = [
            'booking_reminder' => '24,72,168',
            'attendee_report' => '24',
        ];
    ?>
        <div class="mec-meta-box-fields mec-event-tab-content" id="mec-notifications">
            <?php foreach($notifications as $key => $notification): if(isset($this->notif_settings[$key]) and isset($this->notif_settings[$key]['status']) and !$this->notif_settings[$key]['status']) continue; ?>
			<div>
                <h4><?php echo esc_html($notification['label']); ?></h4>
                <div class="mec-form-row">
                    <label>
                        <input type="hidden" name="mec[notifications][<?php echo esc_attr($key); ?>][status]" value="0" />
                        <input onchange="jQuery('#mec_notification_<?php echo esc_attr($key); ?>_container_toggle').toggle();" value="1" type="checkbox" name="mec[notifications][<?php echo esc_attr($key); ?>][status]" <?php if(isset($values[$key]) and isset($values[$key]['status']) and $values[$key]['status']) echo 'checked="checked"'; ?> /> <?php echo esc_html__("Modify", 'renginiu-kalendorius'); ?>
                    </label>
                </div>
                <div id="mec_notification_<?php echo esc_attr($key); ?>_container_toggle" class="<?php if(!isset($values[$key]) || !$values[$key]['status']) echo 'mec-util-hidden'; ?>">
                    <div class="mec-form-row">
                        <div class="mec-col-2">
                            <label for="mec_notifications_<?php echo esc_attr($key); ?>_subject"><?php esc_html_e('Email Subject', 'renginiu-kalendorius'); ?></label>
                        </div>
                        <div class="mec-col-10">
                            <input id="mec_notifications_<?php echo esc_attr($key); ?>_subject" type="text" name="mec[notifications][<?php echo esc_attr($key); ?>][subject]" value="<?php echo ((isset($values[$key]) and isset($values[$key]['subject']) and trim($values[$key]['subject'])) ? $values[$key]['subject'] : ((isset($this->notif_settings[$key]) and isset($this->notif_settings[$key]['subject']) and trim($this->notif_settings[$key]['subject'])) ? $this->notif_settings[$key]['subject'] : '')); ?>">
                        </div>
                    </div>
                    <div class="mec-form-row">
                        <div class="mec-col-2">
                            <label for="mec_notifications_<?php echo esc_attr($key); ?>_content"><?php esc_html_e('Email Content', 'renginiu-kalendorius'); ?></label>
                        </div>
                        <div class="mec-col-10">
                            <?php wp_editor(((isset($values[$key]) and isset($values[$key]['content']) and trim($values[$key]['content'])) ? stripslashes($values[$key]['content']) : ((isset($this->notif_settings[$key]) and isset($this->notif_settings[$key]['content']) and trim($this->notif_settings[$key]['content'])) ? stripslashes($this->notif_settings[$key]['content']) : '')), 'mec_notifications_'.esc_attr($key).'_content', array('textarea_name'=>'mec[notifications]['.$key.'][content]')); ?>
                        </div>
                    </div>
                    <?php if(in_array($key, $hours_notifications, true)): ?>
                    <?php
                        $event_hours = isset($values[$key]['hours']) ? trim((string) $values[$key]['hours']) : '';
                        $global_hours = isset($this->notif_settings[$key]['hours']) ? trim((string) $this->notif_settings[$key]['hours']) : '';
                        $hours_value = $event_hours !== '' ? $event_hours : ($global_hours !== '' ? $global_hours : $hours_defaults[$key]);
                    ?>
                    <div class="mec-form-row">
                        <div class="mec-col-2">
                            <label for="mec_notifications_<?php echo esc_attr($key); ?>_hours"><?php esc_html_e('Hours', 'renginiu-kalendorius'); ?></label>
                        </div>
                        <div class="mec-col-10">
                            <input id="mec_notifications_<?php echo esc_attr($key); ?>_hours" type="text" name="mec[notifications][<?php echo esc_attr($key); ?>][hours]" value="<?php echo esc_attr($hours_value); ?>">
                            <p class="description"><?php esc_html_e('Comma separated positive hours (for example: 72,48,24,1).', 'renginiu-kalendorius'); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php
                        do_action('mec_display_notification_settings_for_event', $values, $key);
                    ?>
                </div>
			</div>
            <?php endforeach; ?>
            <h4><?php echo esc_html__('Placeholders', 'renginiu-kalendorius'); ?></h4>
            <?php $this->display_placeholders(); ?>
		</div>
    <?php
    }

    public function get_notifications()
    {
        $notifications = [
            'booking_notification' => [
                'label' => esc_html__('Booking Notification', 'renginiu-kalendorius')
            ],
            'booking_confirmation' => [
                'label' => esc_html__('Booking Confirmation', 'renginiu-kalendorius')
            ],
            'booking_rejection' => [
                'label' => esc_html__('Booking Rejection', 'renginiu-kalendorius')
            ],
            'email_verification' => [
                'label' => esc_html__('Email Verification', 'renginiu-kalendorius')
            ],
            'cancellation_notification' => [
                'label' => esc_html__('Booking Cancellation', 'renginiu-kalendorius')
            ],
            'booking_reminder' => [
                'label' => esc_html__('Booking Reminder', 'renginiu-kalendorius')
            ],
            'attendee_report' => [
                'label' => esc_html__('Attendee Report', 'renginiu-kalendorius')
            ],
            'event_finished' => [
                'label' => esc_html__('Event Finished', 'renginiu-kalendorius')
            ],
            'event_soldout' => [
                'label' => esc_html__('Event Soldout', 'renginiu-kalendorius')
            ],
            'admin_notification' => [
                'label' => esc_html__('Admin Notification', 'renginiu-kalendorius')
            ],
            'certificate_send' => [
                'label' => esc_html__('Send Certificate', 'renginiu-kalendorius')
            ],
        ];

        return apply_filters('mec_event_notifications', $notifications);
    }

    public static function display_placeholders()
    {
        ?>
        <ul>
            <li><span>%%name%%</span>: <?php esc_html_e('Full name of attendee', 'renginiu-kalendorius'); ?></li>
            <li><span>%%first_name%%</span>: <?php esc_html_e('First name of attendee', 'renginiu-kalendorius'); ?></li>
            <li><span>%%last_name%%</span>: <?php esc_html_e('Last name of attendee', 'renginiu-kalendorius'); ?></li>
            <li><span>%%user_email%%</span>: <?php esc_html_e('Email of attendee', 'renginiu-kalendorius'); ?></li>
            <li><span>%%book_date%%</span>: <?php esc_html_e('Booked date of event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%book_time%%</span>: <?php esc_html_e('Booked time of event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%book_datetime%%</span>: <?php esc_html_e('Booked date and time of event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%book_other_datetimes%%</span>: <?php esc_html_e('Other date and times of booking for multiple date booking system', 'renginiu-kalendorius'); ?></li>
            <li><span>%%book_date_next_occurrences%%</span>: <?php esc_html_e('Date of next 20 occurrences of booked event (including the booked date)', 'renginiu-kalendorius'); ?></li>
            <li><span>%%book_datetime_next_occurrences%%</span>: <?php esc_html_e('Date and Time of next 20 occurrences of booked event (including the booked date)', 'renginiu-kalendorius'); ?></li>
            <li><span>%%book_price%%</span>: <?php esc_html_e('Booking Price', 'renginiu-kalendorius'); ?></li>
            <li><span>%%book_payable%%</span>: <?php esc_html_e('Booking Payable', 'renginiu-kalendorius'); ?></li>
            <li><span>%%attendee_price%%</span>: <?php esc_html_e('Attendee Price (for booking confirmation notification)', 'renginiu-kalendorius'); ?></li>
            <li><span>%%book_order_time%%</span>: <?php esc_html_e('Date and time of booking', 'renginiu-kalendorius'); ?></li>
            <li><span>%%blog_name%%</span>: <?php esc_html_e('Your website title', 'renginiu-kalendorius'); ?></li>
            <li><span>%%blog_url%%</span>: <?php esc_html_e('Your website URL', 'renginiu-kalendorius'); ?></li>
            <li><span>%%blog_description%%</span>: <?php esc_html_e('Your website description', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_title%%</span>: <?php esc_html_e('Event title', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_description%%</span>: <?php esc_html_e('Event Description', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_tags%%</span>: <?php esc_html_e('Event Tags', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_labels%%</span>: <?php esc_html_e('Event Labels', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_categories%%</span>: <?php esc_html_e('Event Categories', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_cost%%</span>: <?php esc_html_e('Event Cost', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_link%%</span>: <?php esc_html_e('Event link', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_speaker_name%%</span>: <?php esc_html_e('Speaker name of booked event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_organizer_name%%</span>: <?php esc_html_e('Organizer name of booked event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_organizer_tel%%</span>: <?php esc_html_e('Organizer tel of booked event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_organizer_email%%</span>: <?php esc_html_e('Organizer email of booked event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_organizer_url%%</span>: <?php esc_html_e('Organizer url of booked event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_other_organizers_name%%</span>: <?php esc_html_e('Additional organizers name of booked event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_other_organizers_tel%%</span>: <?php esc_html_e('Additional organizers tel of booked event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_other_organizers_email%%</span>: <?php esc_html_e('Additional organizers email of booked event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_location_name%%</span>: <?php esc_html_e('Location name of booked event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_location_address%%</span>: <?php esc_html_e('Location address of booked event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_other_locations_name%%</span>: <?php esc_html_e('Additional locations name of booked event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_other_locations_address%%</span>: <?php esc_html_e('Additional locations address of booked event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_featured_image%%</span>: <?php esc_html_e('Featured image of booked event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_more_info%%</span>: <?php esc_html_e('Event link', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_other_info%%</span>: <?php esc_html_e('Event more info link', 'renginiu-kalendorius'); ?></li>
            <li><span>%%online_link%%</span>: <?php esc_html_e('Event online link', 'renginiu-kalendorius'); ?></li>
            <li><span>%%attendees_full_info%%</span>: <?php esc_html_e('Full Attendees info such as booking form data, name, email etc.', 'renginiu-kalendorius'); ?></li>
            <li><span>%%all_bfixed_fields%%</span>: <?php esc_html_e('All booking fixed fields data.', 'renginiu-kalendorius'); ?></li>
            <li><span>%%booking_id%%</span>: <?php esc_html_e('Booking ID', 'renginiu-kalendorius'); ?></li>
            <li><span>%%booking_transaction_id%%</span>: <?php esc_html_e('Transaction ID of Booking', 'renginiu-kalendorius'); ?></li>
            <li><span>%%admin_link%%</span>: <?php esc_html_e('Admin booking management link.', 'renginiu-kalendorius'); ?></li>
            <li><span>%%total_attendees%%</span>: <?php esc_html_e('Total attendees of current booking', 'renginiu-kalendorius'); ?></li>
            <li><span>%%amount_tickets%%</span>: <?php esc_html_e('Amount of Booked Tickets (Total attendees of all bookings)', 'renginiu-kalendorius'); ?></li>
            <li><span>%%ticket_name%%</span>: <?php esc_html_e('Ticket name', 'renginiu-kalendorius'); ?></li>
            <li><span>%%ticket_time%%</span>: <?php esc_html_e('Ticket time', 'renginiu-kalendorius'); ?></li>
            <li><span>%%ticket_name_time%%</span>: <?php esc_html_e('Ticket name & time', 'renginiu-kalendorius'); ?></li>
            <li><span>%%ticket_private_description%%</span>: <?php esc_html_e('Ticket private description', 'renginiu-kalendorius'); ?></li>
            <li><span>%%ticket_variations%%</span>: <?php esc_html_e('Ticket Variations', 'renginiu-kalendorius'); ?></li>
            <li><span>%%payment_gateway%%</span>: <?php esc_html_e('Payment Gateway', 'renginiu-kalendorius'); ?></li>
            <li><span>%%dl_file%%</span>: <?php esc_html_e('Link to the downloadable file', 'renginiu-kalendorius'); ?></li>
            <li><span>%%google_calendar_link%%</span>: <?php esc_html_e('Add to Google Calendar', 'renginiu-kalendorius'); ?></li>
            <li><span>%%google_calendar_link_next_occurrences%%</span>: <?php esc_html_e('Add to Google Calendar Links for next 20 occurrences', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_start_date%%</span>: <?php esc_html_e('Event Start Date', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_end_date%%</span>: <?php esc_html_e('Event End Date', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_start_time%%</span>: <?php esc_html_e('Event Start Time', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_end_time%%</span>: <?php esc_html_e('Event End Time', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_timezone%%</span>: <?php esc_html_e('Event Timezone', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_start_date_local%%</span>: <?php esc_html_e('Event Local Start Date', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_end_date_local%%</span>: <?php esc_html_e('Event Local End Date', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_start_time_local%%</span>: <?php esc_html_e('Event Local Start Time', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_end_time_local%%</span>: <?php esc_html_e('Event Local End Time', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_status%%</span>: <?php esc_html_e('Status of event', 'renginiu-kalendorius'); ?></li>
            <li><span>%%event_note%%</span>: <?php esc_html_e('Event Note', 'renginiu-kalendorius'); ?></li>
            <?php do_action('mec_extra_field_notifications'); ?>
        </ul>
        <?php
    }
}

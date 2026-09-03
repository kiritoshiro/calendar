<?php
/** no direct access **/
defined('MECEXEC') or die();

/** @var StdClass $event */
/** @var boolean $display_progress_bar */

$event_id = $event->ID;

/** @var MEC_main $main */
$main = $this instanceof MEC_main ? $this : MEC::getInstance('app.libraries.main');

// Transaction ID
$transaction_id = $_REQUEST['mec_stripe_redirect_transaction_id'] ?? '';
if(!trim($transaction_id)) $transaction_id = $_REQUEST['mec_stripe_connect_redirect_transaction_id'] ?? '';

$had_payment = false;
if(trim($transaction_id))
{
    $book = $main->getBook();
    $transaction = $book->get_transaction($transaction_id);

    // Had Payment
    if(isset($transaction['total'])) $had_payment = (bool) $transaction['total'];
}
?>
<div id="mec_booking_thankyou_<?php echo esc_attr($event_id); ?>">
    <?php
    $__spb_active = function_exists('mec_seat_builder_uses_booking_flow') ? mec_seat_builder_uses_booking_flow($event_id) : (class_exists('\MEC\SeatBuilder\Booking\SeatPlanBookingHelper') && \MEC\SeatBuilder\Booking\SeatPlanBookingHelper::uses_seat_builder_booking_flow($event_id));
    ?>
    <?php if($display_progress_bar): ?>
    <ul class="mec-booking-progress-bar">
        <li class="mec-booking-progress-bar-date-and-ticket mec-active"><span class="progress-index"><?php esc_html_e('1', 'modern-events-calendar-lite'); ?></span><?php esc_html_e('Select Ticket', 'modern-events-calendar-lite'); ?></li>
        <li class="mec-booking-progress-bar-attendee-info mec-active"><span class="progress-index"><?php esc_html_e('2', 'modern-events-calendar-lite'); ?></span><?php esc_html_e('Attendees', 'modern-events-calendar-lite'); ?></li>
        <?php if($__spb_active): ?>
        <li class="mec-booking-progress-bar-seat-selection mec-active"><span class="progress-index"><?php esc_html_e('3', 'modern-events-calendar-lite'); ?></span><?php esc_html_e('Seat Selection', 'modern-events-calendar-lite'); ?></li>
        <?php endif; ?>
        <?php if($had_payment): ?>
        <li class="mec-booking-progress-bar-payment mec-active"><span class="progress-index"><?php echo $__spb_active ? '4' : '3'; ?></span><?php esc_html_e('Payment', 'modern-events-calendar-lite'); ?></li>
        <?php endif; ?>
        <li class="mec-booking-progress-bar-complete mec-active"><span class="progress-index"><?php echo $__spb_active ? ($had_payment ? '5' : '4') : ($had_payment ? '4' : '3'); ?></span><?php esc_html_e('Confirmation', 'modern-events-calendar-lite'); ?></li>
    </ul>
    <?php endif; ?>
    <?php if(!$had_payment): ?>
    <div class="warning-msg"><?php esc_html_e("For free bookings, there is no payment step.", 'modern-events-calendar-lite'); ?></div>
    <?php endif; ?>
    <?php if(isset($message)): ?>
    <div class="mec-event-book-message mec-gateway-message mec-success">
        <div class="<?php echo (isset($message_class) ? esc_attr($message_class) : ''); ?>">
            <?php echo MEC_kses::element(stripslashes($message)); ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if(trim($transaction_id)): ?>
    <a href="<?php echo $main->get_event_date_permalink(get_permalink($event_id)); ?>"><?php esc_html_e('New Booking', 'modern-events-calendar-lite'); ?></a>
    <?php endif; ?>
</div>
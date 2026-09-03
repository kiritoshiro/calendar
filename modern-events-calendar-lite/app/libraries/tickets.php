<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC Tickets class.
 * @author Webnus <info@webnus.net>
 */
class MEC_tickets extends MEC_base
{
    /**
     * @var MEC_main
     */
    public $main;
    public $settings;

    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct()
    {
        // Import MEC Main
        $this->main = $this->getMain();

        // MEC Settings
        $this->settings = $this->main->get_settings();
    }

    public function builder($args)
    {
        $object_id = $args['object_id'] ?? null;
        $tickets = $args['tickets'] ?? [];
        $name_prefix = $args['name_prefix'] ?? 'mec[tickets]';
        $basic_class = $args['basic_class'] ?? 'mec-basvanced-basic';
        $advanced_class = $args['advanced_class'] ?? 'mec-basvanced-advanced w-hidden';
        $price_per_date_display = $args['perice_per_date_display'] ?? true;
        $display_global_tickets = $args['display_global_tickets'] ?? true;

        // MEC Main
        $main = $this->getMain();

        // Settings
        $settings = $main->get_settings();

        // This date format used for datepicker
        $datepicker_format = (isset($settings['datepicker_format']) and trim($settings['datepicker_format'])) ? $settings['datepicker_format'] : 'Y-m-d';

        // Private Description
        $private_description_status = (!isset($settings['booking_private_description']) || $settings['booking_private_description']);
        if(is_admin()) $private_description_status = true;

        // Variations Per Ticket
        $variations_per_ticket_status = isset($settings['ticket_variations_per_ticket']) && $settings['ticket_variations_per_ticket'];
        if(isset($settings['ticket_variations_status']) and !$settings['ticket_variations_status']) $variations_per_ticket_status = false;

        // Ticket Availability Date
        $availability_dates_status = isset($settings['booking_ticket_availability_dates']) && $settings['booking_ticket_availability_dates'];

        // Ticket Times Status
        $ticket_times_status = !((isset($settings['disable_ticket_times']) and $settings['disable_ticket_times']));

        // Family Tickets Status
        $family_ticket_status = isset($settings['booking_family_ticket']) && $settings['booking_family_ticket'];
        $global_tickets_status = isset($settings['default_tickets_status']) && $settings['default_tickets_status'];

        $available_tickets_placeholder = __('Available Tickets', 'modern-events-calendar-lite');
        if ($family_ticket_status) $available_tickets_placeholder = __('Available Seats', 'modern-events-calendar-lite');

        $global_tickets = isset($settings['tickets']) && is_array($settings['tickets']) ? $settings['tickets'] : [];
        if (isset($global_tickets[':i:'])) unset($global_tickets[':i:']);

        if (isset($_REQUEST['mec_add_global_tickets']) && $_REQUEST['mec_add_global_tickets'])
        {
            $tickets_updated = false;
            foreach ($global_tickets as $global_ticket_key => $global_ticket)
            {
                if (!count($tickets)) {
                    $tickets[1] = $global_ticket;
                    // Copy category_ids from global ticket if exists
                    if (isset($global_ticket['category_ids'])) {
                        $tickets[1]['category_ids'] = $global_ticket['category_ids'];
                    }
                    $tickets_updated = true;
                } else {
                    $tickets[] = $global_ticket;
                    // Copy category_ids from global ticket if exists
                    $last_key = array_key_last($tickets);
                    if (isset($global_ticket['category_ids'])) {
                        $tickets[$last_key]['category_ids'] = $global_ticket['category_ids'];
                    }
                    $tickets_updated = true;
                }
            }
            
            // Save the updated tickets with category_ids to the database
            if ($tickets_updated && $object_id) {
                update_post_meta($object_id, 'mec_tickets', $tickets);
            }

            echo '<script>
                const url = new URL(window.location.href);
                url.searchParams.delete("mec_add_global_tickets");
                window.history.replaceState({}, "", url);
                
                jQuery(document).ready(function()
                {
                    jQuery(".mec-add-booking-tabs-wrap a[data-href=mec-tickets]").trigger("click");
                    setTimeout(() => jQuery("#mec-tickets")[0].scrollIntoView({behavior: "smooth", block: "start"}), 300);
                });
            </script>';
        }
        ?>
        <div id="mec_meta_box_tickets_form">
            <div class="mec-form-row">
                <button class="button" type="button" id="mec_add_ticket_button"><?php esc_html_e('Add Ticket', 'modern-events-calendar-lite'); ?></button>
                <?php if ($display_global_tickets && $global_tickets_status && count($global_tickets) && !count($tickets)): ?>
                <a class="button" href="<?php echo $main->add_qs_var('mec_add_global_tickets', 1); ?>" id="mec_add_global_tickets_button"><?php esc_html_e('Add Global Tickets', 'modern-events-calendar-lite'); ?></a>
                <?php endif; ?>
            </div>
            <?php
            // Detect whether this event has global tickets applied
            $mec_global_tickets_applied = 0;
            if ($object_id) {
                $mec_global_tickets_applied = (int) get_post_meta($object_id, 'mec_global_tickets_applied', true);
            }
            // Build quick heuristics by comparing event ticket names with global ticket names
            $current_ticket_names = [];
            foreach($tickets as $tk => $tv){ if(is_numeric($tk) && !empty($tv['name'])) $current_ticket_names[] = strtolower(trim($tv['name'])); }
            $global_ticket_names = [];
            foreach($global_tickets as $gk => $gv){ if(is_numeric($gk) && !empty($gv['name'])) $global_ticket_names[] = strtolower(trim($gv['name'])); }
            $intersect_names = array_values(array_unique(array_intersect($current_ticket_names, $global_ticket_names)));
            $classification = 'custom';
            if(count($current_ticket_names) && count($global_ticket_names)){
                if(count($intersect_names) === count($current_ticket_names) && count($current_ticket_names) <= count($global_ticket_names)){
                    $classification = 'global_like';
                } elseif(count($intersect_names) > 0){
                    $classification = 'mixed';
                }
            }
            ?>
            <script>
            (function(){
                try{
                    var eventId  = <?php echo (int) ($object_id ?: 0); ?>;
                    var metaApplied = <?php echo $mec_global_tickets_applied ? 'true' : 'false'; ?>;
                    var classification = <?php echo json_encode($classification); ?>;
                    var names = {
                        current: <?php echo json_encode($current_ticket_names); ?>,
                        global: <?php echo json_encode($global_ticket_names); ?>,
                        intersect: <?php echo json_encode($intersect_names); ?>
                    };
                    console.log('[MEC] Tickets source =>', (classification==='global_like'?'global':classification), { eventId: eventId, metaApplied: metaApplied, names: names });
                }catch(e){}
            })();
            </script>
            <div id="mec_tickets">
                <?php
                $i = 0;
                $tvi = 100;
                foreach($tickets as $key => $ticket)
                {
                    if(!is_numeric($key)) continue;
                    $i = max($i, $key);
                    ?>
                    <div class="mec-box mec_ticket_row mec-form-row" id="mec_ticket_row<?php echo esc_attr($key); ?>">
                        <span class="mec_field_sort button"><?php esc_html_e('Sort', 'modern-events-calendar-lite'); ?></span>
                        <button class="button remove mec_ticket_remove_button mec-dash-remove-btn" type="button" onclick="mec_ticket_remove(<?php echo esc_attr($key); ?>);"><?php esc_html_e("Remove", 'modern-events-calendar-lite'); ?></button>
                        <div class="mec-ticket-id mec-label" title="<?php esc_attr_e('Ticket ID', 'modern-events-calendar-lite'); ?>"><span class="mec-ticket-id-title"><?php esc_attr_e('ID', 'modern-events-calendar-lite'); ?>: </span><?php echo esc_attr($key); ?></div>
                        <div class="mec-form-row <?php echo $basic_class; ?>">
                            <input type="text" class="mec-col-12" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][name]"
                                   placeholder="<?php esc_attr_e('Ticket Name', 'modern-events-calendar-lite'); ?>"
                                   value="<?php echo(isset($ticket['name']) ? esc_attr($ticket['name']) : ''); ?>"/>
                        </div>

                        <div class="<?php echo $advanced_class; ?> w-hidden">
                            <?php do_action('mec_ticket_properties', $key, $ticket, $object_id); ?>
                        </div>

                        <?php if($ticket_times_status): ?>
                            <div class="mec-form-row wn-ticket-time <?php echo $advanced_class; ?>">
                                <div class="mec-ticket-start-time mec-col-12">
                                    <span class="mec-ticket-time mec-label"><?php esc_html_e('Start Time', 'modern-events-calendar-lite'); ?></span>
                                    <?php $main->timepicker(array(
                                        'method' => ($settings['time_format'] ?? 12),
                                        'time_hour' => ($ticket['ticket_start_time_hour'] ?? 8),
                                        'time_minutes' => ($ticket['ticket_start_time_minute'] ?? 0),
                                        'time_ampm' => ($ticket['ticket_start_time_ampm'] ?? 'AM'),
                                        'name' => $name_prefix.'['.esc_attr($key).']',
                                        'hour_key' => 'ticket_start_time_hour',
                                        'minutes_key' => 'ticket_start_time_minute',
                                        'ampm_key' => 'ticket_start_time_ampm'
                                    )); ?>
                                </div>
                                <div class="mec-ticket-end-time mec-ticket-start-time mec-col-12">
                                    <span class="mec-ticket-time mec-label"><?php esc_html_e('End Time', 'modern-events-calendar-lite'); ?></span>
                                    <?php $main->timepicker(array(
                                        'method' => ($settings['time_format'] ?? 12),
                                        'time_hour' => ($ticket['ticket_end_time_hour'] ?? 6),
                                        'time_minutes' => ($ticket['ticket_end_time_minute'] ?? 0),
                                        'time_ampm' => ($ticket['ticket_end_time_ampm'] ?? 'PM'),
                                        'name' => $name_prefix.'['.esc_attr($key).']',
                                        'hour_key' => 'ticket_end_time_hour',
                                        'minutes_key' => 'ticket_end_time_minute',
                                        'ampm_key' => 'ticket_end_time_ampm',
                                    )); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mec-form-row <?php echo $basic_class; ?>">
                                <textarea type="text" class="mec-col-11"
                                          name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][description]"
                                          placeholder="<?php esc_attr_e('Description', 'modern-events-calendar-lite'); ?>"><?php echo(isset($ticket['description']) ? esc_textarea($ticket['description']) : ''); ?></textarea>
                        </div>
                        <?php if($private_description_status): ?>
                            <div class="mec-form-row <?php echo $advanced_class; ?>">
                                <textarea type="text" class="mec-col-11"
                                          name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][private_description]"
                                          placeholder="<?php esc_attr_e('Private Description', 'modern-events-calendar-lite'); ?>"><?php echo(isset($ticket['private_description']) ? esc_textarea($ticket['private_description']) : ''); ?></textarea>
                                <span class="mec-tooltip">
                                    <div class="box top">
                                        <h5 class="title"><?php esc_html_e('Private Description', 'modern-events-calendar-lite'); ?></h5>
                                        <div class="content"><p><?php esc_attr_e("The value can be displayed on the email notifications by placing the %%ticket_private_description%% placeholder into the email content.", 'modern-events-calendar-lite'); ?></p></div>
                                    </div>
                                    <i title="" class="dashicons-before dashicons-editor-help"></i>
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="mec-form-row <?php echo $basic_class; ?>">
                                <span class="mec-col-4">
                                    <input type="number" min="0" step="0.01" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][price]"
                                           placeholder="<?php esc_attr_e('Price', 'modern-events-calendar-lite'); ?>"
                                           value="<?php echo ((isset($ticket['price']) and trim($ticket['price'])) ? esc_attr($ticket['price']) : 0); ?>"/>
                                    <span class="mec-tooltip">
                                        <div class="box top">
                                            <h5 class="title"><?php esc_html_e('Price', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e('Only numbers are allowed; Enter only the price without any symbols or characters. Enter 0 for free tickets.', 'modern-events-calendar-lite'); ?>
                                                    <a href="https://webnus.net/dox/modern-events-calendar/add-a-booking-system/"
                                                       target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </span>
                            <span class="mec-col-8">
                                    <input type="text" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][price_label]"
                                           placeholder="<?php esc_attr_e('Price Label', 'modern-events-calendar-lite'); ?>"
                                           value="<?php echo(isset($ticket['price_label']) ? esc_attr($ticket['price_label']) : ''); ?>"
                                           class="mec-col-12"/>
                                    <span class="mec-tooltip">
                                        <div class="box top">
                                            <h5 class="title"><?php esc_html_e('Price Label', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content"><p><?php esc_attr_e('How shoould the price  be displayed in the booking module? Here you can insert the price with a currency symbol. e.g. $16', 'modern-events-calendar-lite'); ?>
                                                    <a href="https://webnus.net/dox/modern-events-calendar/add-a-booking-system/"
                                                       target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                </span>
                        </div>
                        <div class="mec-form-row <?php echo $basic_class; ?>">
                            <div class="mec-col-10">
                                <input class="mec-col-4 mec-available-tickets" type="text" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][limit]"
                                       placeholder="<?php echo esc_attr($available_tickets_placeholder); ?>"
                                       value="<?php echo (isset($ticket['limit']) ? esc_attr($ticket['limit']) : '100'); ?>"/>
                                <label class="mec-col-3 label-checkbox" for="mec_tickets_unlimited_<?php echo esc_attr($key); ?>"
                                       id="mec_bookings_limit_unlimited_label<?php echo esc_attr($key); ?>">
                                    <input type="hidden" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][unlimited]" value="0"/>
                                    <input id="mec_tickets_unlimited_<?php echo esc_attr($key); ?>" type="checkbox" value="1"
                                           name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][unlimited]"
                                        <?php
                                        if (isset($ticket['unlimited']) and $ticket['unlimited']) {
                                            echo 'checked="checked"';
                                        }
                                        ?>
                                    />
                                    <?php esc_html_e('Unlimited', 'modern-events-calendar-lite'); ?>
                                </label>
                                <?php if($family_ticket_status): ?>
                                    <input class="mec-col-4 mec-ticket-number-of-seats" type="number" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][seats]"
                                           placeholder="<?php esc_attr_e('Number of Seats', 'modern-events-calendar-lite'); ?>"
                                           value="<?php echo (isset($ticket['seats']) ? (int) esc_attr($ticket['seats']) : '1'); ?>" min="0" step="1"/>
                                    <span class="mec-tooltip">
                                        <div class="box top">
                                            <h5 class="title"><?php esc_html_e('Number of Seats', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content">
                                                <p><?php esc_attr_e('The number of seats that take off from the total availability when booked. If you are creating a family ticket for 4 people then you can set it to 4.', 'modern-events-calendar-lite'); ?></p>
                                            </div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                                <?php else: ?>
                                    <input type="hidden" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][seats]" value="1"/>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if(apply_filters('mec_fes_ticket_field_visibility', true, 'minimum_ticket')): ?>
                        <div class="mec-form-row <?php echo $advanced_class; ?>">
                            <div class="mec-col-4">
                                <input type="number" min="0" step="1" id="mec_minimum_ticket_<?php echo esc_attr($key); ?>" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][minimum_ticket]" value="<?php echo (isset($ticket['minimum_ticket']) ? esc_attr($ticket['minimum_ticket']) : '0'); ?>" placeholder="<?php esc_html_e('Minimum Ticket e.g. 3', 'modern-events-calendar-lite'); ?>">
                                <span class="mec-tooltip">
                                        <div class="box top">
                                            <h5 class="title"><?php esc_html_e('Minimum Ticket', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content">
                                                <p><?php esc_attr_e('The minimum number of tickets a user needs to book.', 'modern-events-calendar-lite'); ?></p>
                                            </div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                            </div>
                        <?php endif; ?>
                        <?php if(apply_filters('mec_fes_ticket_field_visibility', true, 'maximum_ticket')): ?>
                            <div class="mec-col-4">
                                <input type="number" min="0" step="1" id="mec_maximum_ticket_<?php echo esc_attr($key); ?>" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][maximum_ticket]" value="<?php echo (isset($ticket['maximum_ticket']) ? esc_attr($ticket['maximum_ticket']) : ''); ?>" placeholder="<?php esc_html_e('Maximum Ticket e.g. 1', 'modern-events-calendar-lite'); ?>">
                                <span class="mec-tooltip">
                                        <div class="box top">
                                            <h5 class="title"><?php esc_html_e('Maximum Ticket', 'modern-events-calendar-lite'); ?></h5>
                                            <div class="content">
                                                <p><?php esc_attr_e('The maximum number of tickets a user can book.', 'modern-events-calendar-lite'); ?></p>
                                            </div>
                                        </div>
                                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                                    </span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if(apply_filters('mec_fes_ticket_field_visibility', true, 'stop_selling')): ?>
                        <div class="mec-form-row <?php echo $advanced_class; ?>">
                            <div class="mec-col-12">
                                <label for="mec_stop_selling_value_<?php echo esc_attr($key); ?>"><?php esc_html_e('Stop selling ticket', 'modern-events-calendar-lite'); ?></label>
                            </div>
                            <div class="mec-col-4">
                                <input type="number" class="mec-stop-selling-tickets" id="mec_stop_selling_value_<?php echo esc_attr($key); ?>" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][stop_selling_value]" value="<?php echo((isset($ticket['stop_selling_value']) and trim($ticket['stop_selling_value'])) ? esc_attr($ticket['stop_selling_value']) : '0'); ?>" placeholder="<?php esc_html_e('e.g. 0', 'modern-events-calendar-lite'); ?>">
                                <span class="mec-tooltip">
                                    <div class="box top">
                                        <h5 class="title"><?php esc_html_e('Stop Selling Value', 'modern-events-calendar-lite'); ?></h5>
                                        <div class="content"><p><?php esc_attr_e('The number of days or hours before the event starts to stop selling this ticket. Set 0 to sell until the event starts.', 'modern-events-calendar-lite'); ?></p></div>
                                    </div>
                                    <i title="" class="dashicons-before dashicons-editor-help"></i>
                                </span>
                            </div>
                            <div class="mec-col-4">
                                <select id="mec_stop_selling_type_<?php echo esc_attr($key); ?>" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][stop_selling_type]">
                                    <option value="day" <?php echo(isset($ticket['stop_selling_type']) and trim($ticket['stop_selling_type']) == 'day') ? 'selected="selected"' : ''; ?>><?php esc_html_e("Day", "mec"); ?></option>
                                    <option value="hour" <?php echo(isset($ticket['stop_selling_type']) and trim($ticket['stop_selling_type']) == 'hour') ? 'selected="selected"' : ''; ?>><?php esc_html_e("Hour", "mec"); ?></option>
                                </select>
                                <span class="mec-tooltip">
                                    <div class="box top">
                                        <h5 class="title"><?php esc_html_e('Stop Selling Period', 'modern-events-calendar-lite'); ?></h5>
                                        <div class="content"><p><?php esc_attr_e('Choose between Day and Hour for the stop selling cutoff time.', 'modern-events-calendar-lite'); ?></p></div>
                                    </div>
                                    <i title="" class="dashicons-before dashicons-editor-help"></i>
                                </span>
                            </div>
                            <div class="mec-col-4">
                                <span class="mec-label"><?php esc_html_e('before event start.', 'modern-events-calendar-lite'); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="<?php echo $advanced_class; ?>">
                            <?php do_action('custom_field_ticket', $ticket, $key); ?>
                        </div>
                        <?php if($price_per_date_display): ?>
                        <div id="mec_price_per_dates_container" class="<?php echo $advanced_class; ?>">
                            <div class="mec-form-row">
                                <h5><?php esc_html_e('Price per Date', 'modern-events-calendar-lite'); ?></h5>
                                <button class="button mec_add_price_date_button" type="button"
                                        data-key="<?php echo esc_attr($key); ?>"><?php esc_html_e('Add', 'modern-events-calendar-lite'); ?></button>
                            </div>
                            <div id="mec-ticket-price-dates-<?php echo esc_attr($key); ?>">
                                <?php $j = 0; if(isset($ticket['dates']) and count($ticket['dates'])) : ?>
                                    <?php
                                    foreach ($ticket['dates'] as $p => $price_date) :
                                        if (!is_numeric($p)) {
                                            continue;
                                        }
                                        $j = max($j, $p);
                                        ?>
                                        <div id="mec_ticket_price_raw_<?php echo esc_attr($key); ?>_<?php echo esc_attr($p); ?>">
                                            <div class="mec-form-row">
                                                <input class="mec-col-2 mec_date_picker_dynamic_format" type="text"
                                                       name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][dates][<?php echo esc_attr($p); ?>][start]"
                                                       value="<?php echo isset($price_date['start']) ? esc_attr(\MEC\Base::get_main()->standardize_format($price_date['start'], $datepicker_format)) : esc_attr(\MEC\Base::get_main()->standardize_format(date('Y-m-d'), $datepicker_format)); ?>"
                                                       placeholder="<?php esc_attr_e('Start', 'modern-events-calendar-lite'); ?>"/>
                                                <input class="mec-col-2 mec_date_picker_dynamic_format" type="text"
                                                       name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][dates][<?php echo esc_attr($p); ?>][end]"
                                                       value="<?php echo isset($price_date['end']) ? esc_attr(\MEC\Base::get_main()->standardize_format($price_date['end'], $datepicker_format)) : esc_attr(\MEC\Base::get_main()->standardize_format(date('Y-m-d', strtotime( '+10 days')), $datepicker_format)); ?>"
                                                       placeholder="<?php esc_attr_e('End', 'modern-events-calendar-lite'); ?>"/>
                                                <input class="mec-col-3" type="number"
                                                       name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][dates][<?php echo esc_attr($p); ?>][price]"
                                                       value="<?php echo isset($price_date['price']) ? esc_attr($price_date['price']) : ''; ?>"
                                                       placeholder="<?php esc_attr_e('Price', 'modern-events-calendar-lite'); ?>" min="0" step="0.01"/>
                                                <input class="mec-col-3" type="text"
                                                       name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][dates][<?php echo esc_attr($p); ?>][label]"
                                                       value="<?php echo isset($price_date['label']) ? esc_attr($price_date['label']) : ''; ?>"
                                                       placeholder="<?php esc_attr_e('Label', 'modern-events-calendar-lite'); ?>"/>
                                                <button class="button mec_ticket_price_remove_button mec-dash-remove-btn" type="button"
                                                        onclick="mec_ticket_price_remove(<?php echo esc_attr($key); ?>, <?php echo esc_attr($p); ?>)"><?php esc_html_e('Remove', 'modern-events-calendar-lite'); ?></button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" id="mec_new_ticket_price_key_<?php echo esc_attr($key); ?>"
                                   value="<?php echo ($j + 1); ?>"/>
                            <div class="mec-util-hidden mec_new_ticket_price_raw" id="mec_new_ticket_price_raw_<?php echo esc_attr($key); ?>">
                                <div id="mec_ticket_price_raw_<?php echo esc_attr($key); ?>_:j:">
                                    <div class="mec-form-row">
                                        <input class="mec-col-2 new_added" type="text"
                                               name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][dates][:j:][start]"
                                               value="<?php echo esc_attr(\MEC\Base::get_main()->standardize_format( date( 'Y-m-d' ), $datepicker_format )); ?>"
                                               placeholder="<?php esc_attr_e('Start', 'modern-events-calendar-lite'); ?>"/>
                                        <input class="mec-col-2 new_added" type="text"
                                               name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][dates][:j:][end]"
                                               value="<?php echo esc_attr(\MEC\Base::get_main()->standardize_format( date( 'Y-m-d', strtotime( '+10 days' ) ), $datepicker_format )); ?>"
                                               placeholder="<?php esc_attr_e('End', 'modern-events-calendar-lite'); ?>"/>
                                        <input class="mec-col-3" type="number"
                                               name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][dates][:j:][price]"
                                               placeholder="<?php esc_attr_e('Price', 'modern-events-calendar-lite'); ?>" min="0" step="0.01"/>
                                        <input class="mec-col-3" type="text"
                                               name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][dates][:j:][label]"
                                               placeholder="<?php esc_attr_e('Label', 'modern-events-calendar-lite'); ?>"/>
                                        <button class="button mec_ticket_price_remove_button mec-dash-remove-btn" type="button"
                                                onclick="mec_ticket_price_remove(<?php echo esc_attr($key); ?>, :j:)"><?php esc_html_e('Remove', 'modern-events-calendar-lite'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if($variations_per_ticket_status): ?>
                            <?php
                            $event_inheritance = $ticket['variations_event_inheritance'] ?? 1;
                            if(trim($event_inheritance) == '') $event_inheritance = 1;

                            // Ticket Variations Object
                            $TicketVariations = $main->getTicketVariations();
                            ?>
                            <div id="mec_variations_per_ticket_container" class="<?php echo $advanced_class; ?>">
                                <div class="mec-form-row">
                                    <h4><?php esc_html_e('Variations Per Ticket', 'modern-events-calendar-lite'); ?></h4>
                                    <div id="mec_variations_per_ticket_form<?php echo esc_attr($key); ?>">
                                        <div class="mec-form-row">
                                            <label class="label-checkbox">
                                                <input type="hidden" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][variations_event_inheritance]" value="0"/>
                                                <input onchange="jQuery('#mec_variations_per_ticket_container_toggle<?php echo esc_attr($key); ?>').toggle();" value="1" type="checkbox" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][variations_event_inheritance]" <?php echo ($event_inheritance ? 'checked="checked"' : ''); ?>> <?php esc_html_e('Inherit from event options', 'modern-events-calendar-lite'); ?>
                                            </label>
                                        </div>
                                        <div id="mec_variations_per_ticket_container_toggle<?php echo esc_attr($key); ?>" class="<?php echo ($event_inheritance ? 'mec-util-hidden' : ''); ?>">
                                            <div class="mec-form-row">
                                                <button class="button mec_add_variation_per_ticket_button" type="button" id="mec_add_variation_per_ticket_button<?php echo esc_attr($key); ?>" onclick="add_variation_per_ticket(<?php echo esc_attr($key); ?>);"><?php esc_html_e('Add', 'modern-events-calendar-lite'); ?></button>
                                            </div>
                                            <div id="mec_ticket_variations_list<?php echo esc_attr($key); ?>">
                                                <?php
                                                $ticket_variations = ((isset($ticket['variations']) and is_array($ticket['variations'])) ? $ticket['variations'] : array());
                                                foreach($ticket_variations as $tvk => $ticket_variation)
                                                {
                                                    if(!is_numeric($tvk)) continue;

                                                    $variation_key = (int) $tvk;
                                                    $tvi = max($tvi, $variation_key);
                                                    $TicketVariations->item(array(
                                                        'name_prefix' => $name_prefix.'['.esc_attr($key).'][variations]',
                                                        'id_prefix' => 'variation_per_ticket'.esc_attr($key),
                                                        'i' => $variation_key,
                                                        'value' => $ticket_variation,
                                                    ));
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mec-util-hidden" id="mec_new_variation_per_ticket_raw<?php echo esc_attr($key); ?>">
                                        <?php
                                        $TicketVariations->item(array(
                                            'name_prefix' => $name_prefix.'['.esc_attr($key).'][variations]',
                                            'id_prefix' => 'variation_per_ticket'.esc_attr($key),
                                            'i' => ':v:',
                                            'value' => array(),
                                        ));
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if($availability_dates_status): ?>
                            <?php
                                $availability_start = $ticket['availability_start'] ?? '';
                                $availability_end = $ticket['availability_end'] ?? '';

                                if(trim($availability_start) && !trim($availability_end)) $availability_start = '';
                                if(trim($availability_end) && !trim($availability_start)) $availability_end = '';
                            ?>
                        <div id="mec_ticket_availability_dates_container" class="<?php echo $advanced_class; ?>">
                            <div class="mec-form-row">
                                <h5><?php esc_html_e('Availability Date', 'modern-events-calendar-lite'); ?></h5>
                                <p class="description"><?php esc_html_e('If you leave the following fields empty then the ticket will be available at any time.'); ?></p><br />
                                <p class="description" style="color:red"><?php esc_html_e('Please note that this is just a timeframe, and if your events or occurrences fall within this period, the ticket will be displayed.'); ?></p>
                                <div>
                                    <input title="<?php esc_attr_e('Availability Start', 'modern-events-calendar-lite'); ?>" placeholder="<?php esc_attr_e('Availability Start', 'modern-events-calendar-lite'); ?>" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][availability_start]" value="<?php echo $availability_start; ?>" type="text" class="mec-date-picker-start">
                                    <input title="<?php esc_attr_e('Availability End', 'modern-events-calendar-lite'); ?>" placeholder="<?php esc_attr_e('Availability End', 'modern-events-calendar-lite'); ?>" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($key); ?>][availability_end]" value="<?php echo $availability_end; ?>" type="text" class="mec-date-picker-end">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php do_action('mec_ticket_extra_options', $key, $ticket, $args); ?>
                    </div>
                    <?php
                }
                ?>
            </div>
            <input type="hidden" id="mec_new_variation_per_ticket_key" value="<?php echo ($tvi + 1); ?>"/>
        </div>
        <input type="hidden" id="mec_new_ticket_key" value="<?php echo ($i + 1); ?>"/>
        <div class="mec-util-hidden" id="mec_new_ticket_raw">
            <div class="mec-box mec_ticket_row mec-form-row" id="mec_ticket_row:i:">
                <span class="mec_field_sort button"><?php esc_html_e('Sort', 'modern-events-calendar-lite'); ?></span>
                <button class="button remove mec_ticket_remove_button mec-dash-remove-btn" type="button" onclick="mec_ticket_remove(:i:);"><?php esc_html_e("Remove", 'modern-events-calendar-lite'); ?></button>
                <div class="mec-ticket-id" title="<?php esc_attr_e('Ticket ID', 'modern-events-calendar-lite'); ?>"><span class="mec-ticket-id-title"><?php esc_attr_e('ID', 'modern-events-calendar-lite'); ?>: </span>:i:</div>
                <div class="mec-form-row <?php echo $basic_class; ?>">
                    <input class="mec-col-12" type="text" name="<?php echo $name_prefix; ?>[:i:][name]"
                           placeholder="<?php esc_attr_e('Ticket Name', 'modern-events-calendar-lite'); ?>"/>
                </div>
                <div class="<?php echo $advanced_class; ?>">
                    <?php do_action('mec_ticket_properties', ':i:', [], $object_id); ?>
                </div>
                <?php if($ticket_times_status): ?>
                    <div class="mec-form-row wn-ticket-time <?php echo $advanced_class; ?>">
                        <div class="mec-ticket-start-time mec-col-12">
                            <span class="mec-ticket-time mec-label"><?php esc_html_e('Start Time', 'modern-events-calendar-lite'); ?></span>
                            <?php $main->timepicker(array(
                                'method' => ($settings['time_format'] ?? 12),
                                'time_hour' => 8,
                                'time_minutes' => 0,
                                'time_ampm' => 'AM',
                                'name' => $name_prefix.'[:i:]',
                                'hour_key' => 'ticket_start_time_hour',
                                'minutes_key' => 'ticket_start_time_minute',
                                'ampm_key' => 'ticket_start_time_ampm',
                            )); ?>
                        </div>
                        <div class="mec-ticket-end-time mec-ticket-start-time mec-col-12">
                            <span class="mec-ticket-time mec-label"><?php esc_html_e('End Time', 'modern-events-calendar-lite'); ?></span>
                            <?php $main->timepicker(array(
                                'method' => ($settings['time_format'] ?? 12),
                                'time_hour' => 6,
                                'time_minutes' => 0,
                                'time_ampm' => 'PM',
                                'name' => $name_prefix.'[:i:]',
                                'hour_key' => 'ticket_end_time_hour',
                                'minutes_key' => 'ticket_end_time_minute',
                                'ampm_key' => 'ticket_end_time_ampm',
                            )); ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="mec-form-row <?php echo $basic_class; ?>">
                        <textarea class="mec-col-11" type="text" name="<?php echo $name_prefix; ?>[:i:][description]"
                                  placeholder="<?php esc_attr_e('Description', 'modern-events-calendar-lite'); ?>"></textarea>
                </div>
                <?php if($private_description_status): ?>
                    <div class="mec-form-row <?php echo $advanced_class; ?>">
                        <textarea type="text" class="mec-col-11" name="<?php echo $name_prefix; ?>[:i:][private_description]"
                                  placeholder="<?php esc_attr_e('Private Description', 'modern-events-calendar-lite'); ?>"></textarea>
                        <span class="mec-tooltip">
                            <div class="box top">
                                <h5 class="title"><?php esc_html_e('Private Description', 'modern-events-calendar-lite'); ?></h5>
                                <div class="content"><p><?php esc_attr_e("The value can be displayed on the email notifications by placing the %%ticket_private_description%% placeholder into the email content.", 'modern-events-calendar-lite'); ?></p></div>
                            </div>
                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                        </span>
                    </div>
                <?php endif; ?>
                <div class="mec-form-row <?php echo $basic_class; ?>">
						<span class="mec-col-4">
							<input type="number" min="0" step="0.01" name="<?php echo $name_prefix; ?>[:i:][price]" placeholder="<?php esc_attr_e('Price', 'modern-events-calendar-lite'); ?>" value="0">
							<span class="mec-tooltip">
								<div class="box top">
									<h5 class="title"><?php esc_html_e('Price', 'modern-events-calendar-lite'); ?></h5>
									<div class="content"><p><?php esc_attr_e('Only numbers are allowed; Enter only the price without any symbols or characters. Enter 0 for free tickets.', 'modern-events-calendar-lite'); ?>
                                            <a href="https://webnus.net/dox/modern-events-calendar/add-a-booking-system/"
                                               target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
								</div>
								<i title="" class="dashicons-before dashicons-editor-help"></i>
							</span>
						</span>
                    <span class="mec-col-8">
							<input type="text" name="<?php echo $name_prefix; ?>[:i:][price_label]" placeholder="<?php esc_attr_e('Price Label', 'modern-events-calendar-lite'); ?>" class="mec-col-12">
							<span class="mec-tooltip">
								<div class="box top">
									<h5 class="title"><?php esc_html_e('Price Label', 'modern-events-calendar-lite'); ?></h5>
									<div class="content"><p><?php esc_attr_e('How should the price  be displayed in the booking module? Here you can insert the price with a currency symbol. e.g. $16', 'modern-events-calendar-lite'); ?>
                                            <a href="https://webnus.net/dox/modern-events-calendar/add-a-booking-system/"
                                               target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a></p></div>
								</div>
								<i title="" class="dashicons-before dashicons-editor-help"></i>
							</span>
						</span>
                </div>
                <div class="mec-form-row <?php echo $basic_class; ?>">
                    <div class="mec-col-10">
                        <input class="mec-col-4 mec-available-tickets" type="text" name="<?php echo $name_prefix; ?>[:i:][limit]"
                               placeholder="<?php echo esc_attr($available_tickets_placeholder); ?>"/>
                        <label class="mec-col-3 label-checkbox" for="mec_tickets_unlimited_:i:"
                               id="mec_bookings_limit_unlimited_label">
                            <input type="hidden" name="<?php echo $name_prefix; ?>[:i:][unlimited]" value="0"/>
                            <input id="mec_tickets_unlimited_:i:" type="checkbox" value="1"
                                   name="<?php echo $name_prefix; ?>[:i:][unlimited]"/>
                            <?php esc_html_e('Unlimited', 'modern-events-calendar-lite'); ?>
                        </label>
                        <?php if($family_ticket_status): ?>
                            <input class="mec-col-4 mec-ticket-number-of-seats" type="number" name="<?php echo $name_prefix; ?>[:i:][seats]"
                                   placeholder="<?php esc_attr_e('Number of Seats', 'modern-events-calendar-lite'); ?>"
                                   value="1" min="0" step="1"/>
                            <span class="mec-tooltip">
                                <div class="box top">
                                    <h5 class="title"><?php esc_html_e('Number of Seats', 'modern-events-calendar-lite'); ?></h5>
                                    <div class="content">
                                        <p><?php esc_attr_e('The number of seats that take off from the total availability when booked. If you are creating a family ticket for 4 people then you can set it to 4.', 'modern-events-calendar-lite'); ?></p>
                                    </div>
                                </div>
                                <i title="" class="dashicons-before dashicons-editor-help"></i>
                            </span>
                        <?php else: ?>
                            <input type="hidden" name="<?php echo $name_prefix; ?>[:i:][seats]" value="1"/>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if(apply_filters('mec_fes_ticket_field_visibility', true, 'minimum_ticket')): ?>
                <div class="mec-form-row <?php echo $advanced_class; ?>">
                    <div class="mec-col-4">
                        <input type="number" min="0" step="1" id="mec_minimum_ticket_:i:" name="<?php echo $name_prefix; ?>[:i:][minimum_ticket]" value="1" placeholder="<?php esc_html_e('Minimum Ticket e.g. 3', 'modern-events-calendar-lite'); ?>">
                        <span class="mec-tooltip">
                                <div class="box top">
                                    <h5 class="title"><?php esc_html_e('Minimum Ticket', 'modern-events-calendar-lite'); ?></h5>
                                    <div class="content">
                                        <p><?php esc_attr_e('The minimum number of tickets  a user needs to book.', 'modern-events-calendar-lite'); ?></p>
                                    </div>
                                </div>
                                <i title="" class="dashicons-before dashicons-editor-help"></i>
                            </span>
                    </div>
                <?php endif; ?>
                <?php if(apply_filters('mec_fes_ticket_field_visibility', true, 'maximum_ticket')): ?>
                    <div class="mec-col-4">
                        <input type="number" min="0" step="1" id="mec_maximum_ticket_:i:" name="<?php echo $name_prefix; ?>[:i:][maximum_ticket]" value="" placeholder="<?php esc_html_e('Maximum Ticket e.g. 1', 'modern-events-calendar-lite'); ?>">
                        <span class="mec-tooltip">
                                <div class="box top">
                                    <h5 class="title"><?php esc_html_e('Maximum Ticket', 'modern-events-calendar-lite'); ?></h5>
                                    <div class="content">
                                        <p><?php esc_attr_e('The maximum number of tickets a user can book.', 'modern-events-calendar-lite'); ?></p>
                                    </div>
                                </div>
                                <i title="" class="dashicons-before dashicons-editor-help"></i>
                            </span>
                    </div>
                </div>
                <?php endif; ?>
                <?php if(apply_filters('mec_fes_ticket_field_visibility', true, 'stop_selling')): ?>
                <div class="mec-form-row <?php echo $advanced_class; ?>">
                    <div class="mec-col-12">
                        <label for="mec_stop_selling_value_:i:"><?php esc_html_e('Stop selling ticket', 'modern-events-calendar-lite'); ?></label>
                    </div>
                    <div class="mec-col-4">
                        <input type="number" class="mec-stop-selling-tickets" id="mec_stop_selling_value_:i:" name="<?php echo $name_prefix; ?>[:i:][stop_selling_value]" value="0" placeholder="<?php esc_html_e('e.g. 0', 'modern-events-calendar-lite'); ?>">
                        <span class="mec-tooltip">
                            <div class="box top">
                                <h5 class="title"><?php esc_html_e('Stop Selling Value', 'modern-events-calendar-lite'); ?></h5>
                                <div class="content"><p><?php esc_attr_e('The number of days or hours before the event starts to stop selling this ticket. Set 0 to sell until the event starts.', 'modern-events-calendar-lite'); ?></p></div>
                            </div>
                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                        </span>
                    </div>
                    <div class="mec-col-4">
                        <select id="mec_stop_selling_type_:i:" name="<?php echo $name_prefix; ?>[:i:][stop_selling_type]">
                            <option value="day"><?php esc_html_e("Day", "mec"); ?></option>
                            <option value="hour"><?php esc_html_e("Hour", "mec"); ?></option>
                        </select>
                        <span class="mec-tooltip">
                            <div class="box top">
                                <h5 class="title"><?php esc_html_e('Stop Selling Period', 'modern-events-calendar-lite'); ?></h5>
                                <div class="content"><p><?php esc_attr_e('Choose between Day and Hour for the stop selling cutoff time.', 'modern-events-calendar-lite'); ?></p></div>
                            </div>
                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                        </span>
                    </div>
                    <div class="mec-col-4">
                        <span class="mec-label"><?php esc_html_e('before event start.', 'modern-events-calendar-lite'); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <div class="<?php echo $advanced_class; ?>">
                    <?php do_action('custom_field_dynamic_ticket'); ?>
                </div>
                <?php if($price_per_date_display): ?>
                <div id="mec_price_per_dates_container_:i:" class="<?php echo $advanced_class; ?>">
                    <div class="mec-form-row">
                        <h5><?php esc_html_e('Price per Date', 'modern-events-calendar-lite'); ?></h5>
                        <button class="button mec_add_price_date_button" type="button"
                                data-key=":i:"><?php esc_html_e('Add', 'modern-events-calendar-lite'); ?></button>
                    </div>
                    <div id="mec-ticket-price-dates-:i:">
                    </div>
                    <input type="hidden" id="mec_new_ticket_price_key_:i:" value="1"/>
                    <div class="mec-util-hidden" id="mec_new_ticket_price_raw_:i:">
                        <div id="mec_ticket_price_raw_:i:_:j:">
                            <div class="mec-form-row">
                                <input class="mec-col-2 new_added" type="text"
                                       name="<?php echo $name_prefix; ?>[:i:][dates][:j:][start]"
                                       value="<?php echo esc_attr($main->standardize_format(date('Y-m-d'), $datepicker_format)); ?>"
                                       placeholder="<?php esc_attr_e('Start', 'modern-events-calendar-lite'); ?>"/>
                                <input class="mec-col-2 new_added" type="text"
                                       name="<?php echo $name_prefix; ?>[:i:][dates][:j:][end]"
                                       value="<?php echo esc_attr($main->standardize_format(date('Y-m-d', strtotime('+10 days')), $datepicker_format)); ?>"
                                       placeholder="<?php esc_attr_e('End', 'modern-events-calendar-lite'); ?>"/>
                                <input class="mec-col-3" type="number" name="<?php echo $name_prefix; ?>[:i:][dates][:j:][price]"
                                       placeholder="<?php esc_attr_e('Price', 'modern-events-calendar-lite'); ?>" min="0" step="0.01"/>
                                <input class="mec-col-3" type="text" name="<?php echo $name_prefix; ?>[:i:][dates][:j:][label]"
                                       placeholder="<?php esc_attr_e('Label', 'modern-events-calendar-lite'); ?>"/>
                                <button class="button mec_ticket_price_remove_button mec-dash-remove-btn" type="button"
                                        onclick="mec_ticket_price_remove(:i:, :j:)"><?php esc_html_e('Remove', 'modern-events-calendar-lite'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($variations_per_ticket_status): ?>
                    <?php
                    // Ticket Variations Object
                    $TicketVariations = $main->getTicketVariations();
                    ?>
                    <div id="mec_variations_per_ticket_container" class="<?php echo $advanced_class; ?>">
                        <div class="mec-form-row">
                            <h4><?php esc_html_e('Variations Per Ticket', 'modern-events-calendar-lite'); ?></h4>
                            <div id="mec_variations_per_ticket_form:i:">
                                <div class="mec-form-row">
                                    <label class="label-checkbox">
                                        <input type="hidden" name="<?php echo $name_prefix; ?>[:i:][variations_event_inheritance]" value="0"/>
                                        <input onchange="jQuery('#mec_variations_per_ticket_container_toggle:i:').toggle();" value="1" type="checkbox" name="<?php echo $name_prefix; ?>[:i:][variations_event_inheritance]" checked="checked"> <?php esc_html_e('Inherit from event options', 'modern-events-calendar-lite'); ?>
                                    </label>
                                </div>
                                <div id="mec_variations_per_ticket_container_toggle:i:" class="mec-util-hidden">
                                    <div class="mec-form-row">
                                        <button class="button mec_add_variation_per_ticket_button" type="button" id="mec_add_variation_per_ticket_button:i:" onclick="add_variation_per_ticket(:i:);"><?php esc_html_e('Add', 'modern-events-calendar-lite'); ?></button>
                                    </div>
                                    <div id="mec_ticket_variations_list:i:"></div>
                                </div>
                            </div>
                            <input type="hidden" id="mec_new_variation_per_ticket_key:i:" value="1"/>
                            <div class="mec-util-hidden" id="mec_new_variation_per_ticket_raw:i:">
                                <?php
                                $TicketVariations->item(array(
                                    'name_prefix' => $name_prefix.'[:i:][variations]',
                                    'id_prefix' => 'variation_per_ticket:i:',
                                    'i' => ':v:',
                                    'value' => array(),
                                ));
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if($availability_dates_status): ?>
                    <div id="mec_ticket_availability_dates_container" class="<?php echo $advanced_class; ?>">
                        <div class="mec-form-row">
                            <h5><?php esc_html_e('Availability Date', 'modern-events-calendar-lite'); ?></h5>
                            <p class="description"><?php esc_html_e('If you leave the following fields empty then the ticket will be available at any time.'); ?></p><br />
                            <p class="description" style="color:red"><?php esc_html_e('Please note that this is just a timeframe, and if your events or occurrences fall within this period, the ticket will be displayed.'); ?></p>
                            <div>
                                <input title="<?php esc_attr_e('Availability Start', 'modern-events-calendar-lite'); ?>" placeholder="<?php esc_attr_e('Availability Start', 'modern-events-calendar-lite'); ?>" name="<?php echo $name_prefix; ?>[:i:][availability_start]" value="" type="text" class="mec-date-picker-start">
                                <input title="<?php esc_attr_e('Availability End', 'modern-events-calendar-lite'); ?>" placeholder="<?php esc_attr_e('Availability End', 'modern-events-calendar-lite'); ?>" name="<?php echo $name_prefix; ?>[:i:][availability_end]" value="" type="text" class="mec-date-picker-end">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php do_action('mec_ticket_extra_options', ':i:', [], $args); ?>
            </div>
        </div>
        <?php
    }
}

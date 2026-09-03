<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC Paid Membership Pro addon class
 * @author Webnus <info@webnus.net>
 */
class MEC_addon_PMP extends MEC_base
{
    /**
     * @var MEC_factory
     */
    public $factory;

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
        // MEC Factory class
        $this->factory = $this->getFactory();
        
        // MEC Main class
        $this->main = $this->getMain();

        // MEC Settings
        $this->settings = $this->main->get_settings();
    }
    
    /**
     * Initialize the PMP addon
     * @author Webnus <info@webnus.net>
     * @return boolean
     */
    public function init()
    {
        $event_restriction = isset($this->settings['pmp_status']) && $this->settings['pmp_status'];
        $booking_restriction = isset($this->settings['pmp_booking_restriction']) && $this->settings['pmp_booking_restriction'];
        $ticket_restriction = isset($this->settings['pmp_ticket_restrictions']) && $this->settings['pmp_ticket_restrictions'];

        // Module is not enabled
        if(!$event_restriction && !$booking_restriction && !$ticket_restriction) return false;

        // Event Restriction
        if($event_restriction)
        {
            // Metabox
            add_action('admin_menu', [$this, 'metabox']);

            // Display Access Error
            add_filter('mec_show_event_details_page', [$this, 'check'], 10, 2);
        }

        // Booking Restriction
        if($booking_restriction)
        {
            add_filter('mec_booking_module_abort', [$this, 'booking_abort'], 10, 2);
        }

        // Ticket Restrictions
        if($ticket_restriction)
        {
            add_action('mec_ticket_extra_options', [$this, 'ticket_plans_fields'], 10, 3);
            add_filter('mec_get_tickets_availability', [$this, 'ticket_availability'], 10, 5);
        }

        return true;
    }

    public function metabox()
    {
        if(!defined('PMPRO_VERSION')) return;

        // Register
        add_meta_box('pmpro_page_meta', esc_html__('Require Membership', 'modern-events-calendar-lite'), 'pmpro_page_meta', $this->main->get_main_post_type(), 'side', 'high');
    }

    public function check($status, $event_id)
    {
        if(!defined('PMPRO_VERSION')) return $status;

        // Has Access
        if(function_exists('pmpro_has_membership_access'))
        {
            $response = pmpro_has_membership_access($event_id, NULL, true);
            $available = $response[0] ?? true;

            if(!$available)
            {
                $post_membership_levels_ids = $response[1];
                $post_membership_levels_names = $response[2];

                $content = pmpro_get_no_access_message('', $post_membership_levels_ids, $post_membership_levels_names);
                $status = '<div class="mec-wrap mec-no-access-error"><h1>'.get_the_title($event_id).'</h1>'.MEC_kses::page($content).'</div>';
            }
        }

        return $status;
    }

    public function booking_abort($abort, $event)
    {
        if(!function_exists('pmpro_hasMembershipLevel')) return $abort;

        // Event ID
        $event_id = $event->ID;
        if(!$event_id) return $abort;

        // Event Categories
        $categories = (isset($event->data) and isset($event->data->categories) and is_array($event->data->categories)) ? $event->data->categories : [];

        // Event has no category
        if(!count($categories)) return $abort;

        // User ID
        $user_id = get_current_user_id();

        // Booking Restriction Options
        $options = isset($this->settings['pmp_booking']) && is_array($this->settings['pmp_booking']) ? $this->settings['pmp_booking'] : [];

        $needed_levels = [];
        foreach($options as $level_id => $cats)
        {
            foreach($categories as $category)
            {
                if(in_array($category['id'], $cats)) $needed_levels[] = $level_id;
            }
        }

        $needed_levels = array_unique($needed_levels);
        if($needed_levels and !pmpro_hasMembershipLevel($needed_levels, $user_id))
        {
            return pmpro_get_no_access_message('', $needed_levels);
        }

        return $abort;
    }

    public function ticket_plans_fields($ticket_id, $data, $args = [])
    {
        if(!function_exists('pmpro_getAllLevels')) return;

        $name_prefix = $args['name_prefix'] ?? 'mec[tickets]';
        $advanced_class = $args['advanced_class'] ?? 'mec-basvanced-advanced w-hidden';

        // Levels
        $levels = pmpro_getAllLevels();
        ?>
        <div id="mec_ticket_pmp_plans_container" class="<?php echo $advanced_class; ?>">
            <div class="mec-form-row">
                <h5><?php esc_html_e('Membership Levels', 'modern-events-calendar-lite'); ?></h5>
                <p class="description"><?php esc_html_e('If you leave the following fields empty then the ticket will be available to all plans.'); ?></p><br />
                <ul>
                    <?php foreach($levels as $level): ?>
                    <li>
                        <label>
                            <input value="<?php echo esc_attr($level->id); ?>" type="checkbox" name="<?php echo $name_prefix; ?>[<?php echo esc_attr($ticket_id); ?>][pmp][]" <?php if(in_array($level->id, $data['pmp'] ?? [])) echo 'checked="checked"'; ?>>
                            <span><?php echo esc_html($level->name); ?></span>
                        </label>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
    }

    public function ticket_availability($availability, $event_id, $timestamp, $mode, $tickets)
    {
        if(!function_exists('pmpro_hasMembershipLevel') || !$event_id) return $availability;

        // User Id
        $user_id = get_current_user_id();

        foreach ($tickets as $ticket)
        {
            // Required Level for Ticket
            $levels = $ticket['pmp'] ?? [];

            // Available to All
            if(!count($levels)) continue;

            if(!pmpro_hasMembershipLevel($levels, $user_id))
            {
                $availability[$ticket['id']] = 0;
                $availability['stop_selling_'.$ticket['id']] = true;
                $availability['stop_selling_'.$ticket['id'].'_message'] = esc_html__("You do not have access to book the %s ticket.", 'modern-events-calendar-lite');
            }
        }

        return $availability;
    }
}

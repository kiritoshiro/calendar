<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC schema class.
 * @author Webnus <info@webnus.net>
 */
class MEC_feature_schema extends MEC_base
{
    public $factory;
    public $main;
    public $settings;

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
    }

    /**
     * Initialize locations feature
     * @author Webnus <info@webnus.net>
     */
    public function init()
    {
        // Schema Meta Box
        $this->factory->action('mec_metabox_details', [$this, 'meta_box_schema'], 60);
        if (!isset($this->settings['fes_section_schema']) or (isset($this->settings['fes_section_schema']) and $this->settings['fes_section_schema'])) $this->factory->action('mec_fes_metabox_details', [$this, 'meta_box_schema'], 60);

        // Save Schema Data
        $this->factory->action('save_post', [$this, 'save_event']);

        // Print Schema
        if (isset($this->settings['schema']) and $this->settings['schema'] != 0)
        {
            $this->factory->action('mec_schema', [$this, 'schema'], 10);
            $this->factory->filter('mec_schema_text', [$this, 'schema_text'], 10, 2);
        }
    }

    /**
     * Show location meta box
     * @param object $post
     * @author Webnus <info@webnus.net>
     */
    public function meta_box_schema($post)
    {
        $event_status = get_post_meta($post->ID, 'mec_event_status', true);
        if (!trim($event_status)) $event_status = 'EventScheduled';

        $moved_online_link = get_post_meta($post->ID, 'mec_moved_online_link', true);
        $cancelled_reason = get_post_meta($post->ID, 'mec_cancelled_reason', true);
        $display_cancellation_reason_in_single_page = get_post_meta($post->ID, 'mec_display_cancellation_reason_in_single_page', true);
        ?>
        <div class="mec-meta-box-fields mec-event-tab-content" id="mec-schema">
            <h4><?php echo esc_html__('SEO Schema', 'modern-events-calendar-lite'); ?></h4>
            <p class="description"><?php esc_html_e("Following statuses are for informing search engines (Google, bing, etc) about your events so they can manage your events better. Therefore you can use these statuses to be more Search Engine Friendly.", 'modern-events-calendar-lite'); ?></p>

            <div class="mec-form-row">
                <label>
                    <input class="mec-schema-event-status" type="radio" name="mec[event_status]"
                           value="EventScheduled" <?php echo($event_status == 'EventScheduled' ? 'checked' : ''); ?>>
                    <?php esc_html_e('Scheduled', 'modern-events-calendar-lite'); ?>
                </label>
                <p class="description"><?php esc_html_e('For active events!', 'modern-events-calendar-lite'); ?></p>
            </div>
            <div class="mec-form-row">
                <label>
                    <input class="mec-schema-event-status" type="radio" name="mec[event_status]"
                           value="EventRescheduled" <?php echo($event_status == 'EventRescheduled' ? 'checked' : ''); ?>>
                    <?php esc_html_e('Rescheduled', 'modern-events-calendar-lite'); ?>
                </label>
                <p class="description"><?php esc_html_e('For rescheduled events!', 'modern-events-calendar-lite'); ?></p>
            </div>
            <div class="mec-form-row">
                <label>
                    <input class="mec-schema-event-status" type="radio" name="mec[event_status]"
                           value="EventPostponed" <?php echo($event_status == 'EventPostponed' ? 'checked' : ''); ?>>
                    <?php esc_html_e('Postponed', 'modern-events-calendar-lite'); ?>
                </label>
                <p class="description"><?php esc_html_e('If you postponed an event then you can use this status!', 'modern-events-calendar-lite'); ?></p>
            </div>
            <div class="mec-form-row">
                <label>
                    <input class="mec-schema-event-status" type="radio" name="mec[event_status]"
                           value="EventCancelled" <?php echo($event_status == 'EventCancelled' ? 'checked' : ''); ?>>
                    <?php esc_html_e('Cancelled', 'modern-events-calendar-lite'); ?>
                </label>
                <p class="description"><?php esc_html_e('If you cancelled an event then you should select this status!', 'modern-events-calendar-lite'); ?></p>
            </div>
            <div id="mec_cancelled_reason_wrapper"
                 class="event-status-schema" <?php echo($event_status == 'EventCancelled' ? '' : 'style="display: none;"'); ?>>
                <div class="mec-form-row">
                    <label class="mec-col-2"
                           for="mec_cancelled_reason"><?php esc_html_e('Reason for Cancellation', 'modern-events-calendar-lite'); ?></label>
                    <input class="mec-col-9" type="text" id="mec_cancelled_reason" name="mec[cancelled_reason]"
                           value="<?php echo esc_attr($cancelled_reason); ?>"
                           placeholder="<?php esc_html_e('Please write your reasons here', 'modern-events-calendar-lite'); ?>">
                </div>
                <div>
                    <p class="description"><?php esc_html_e('This will be displayed in Single Event and Shortcode/Calendar Pages', 'modern-events-calendar-lite'); ?></p>
                </div>
                <div class="mec-form-row">
                    <label for="mec_display_cancellation_reason_in_single_page">
                        <input
                            <?php
                            if (isset($display_cancellation_reason_in_single_page) and $display_cancellation_reason_in_single_page)
                            {
                                echo 'checked="checked"';
                            }
                            ?>
                            type="checkbox" name="mec[display_cancellation_reason_in_single_page]"
                            id="mec_display_cancellation_reason_in_single_page" value="1"/>
                        <?php esc_html_e('Display in single event page', 'modern-events-calendar-lite'); ?>
                    </label>
                </div>
            </div>
            <div class="mec-form-row">
                <label>
                    <input class="mec-schema-event-status" type="radio" name="mec[event_status]"
                           value="EventMovedOnline" <?php echo($event_status == 'EventMovedOnline' ? 'checked' : ''); ?>>
                    <?php esc_html_e('Moved Online', 'modern-events-calendar-lite'); ?>
                </label>
                <p class="description"><?php esc_html_e('For the events that moved online!', 'modern-events-calendar-lite'); ?></p>
            </div>
            <div id="mec_moved_online_link_wrapper"
                 class="event-status-schema" <?php echo($event_status == 'EventMovedOnline' ? '' : 'style="display: none;"'); ?>>
                <div class="mec-form-row">
                    <label class="mec-col-2"
                           for="mec_moved_online_link"><?php esc_html_e('Online Link', 'modern-events-calendar-lite'); ?></label>
                    <input class="mec-col-9" type="url" id="mec_moved_online_link" name="mec[moved_online_link]"
                           value="<?php echo esc_url($moved_online_link); ?>"
                           placeholder="https://online-platform.com/event-id">
                </div>
                <div>
                    <p class="description"><?php esc_html_e('Link to join online event. If you leave it empty event link will be used.', 'modern-events-calendar-lite'); ?></p>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function ()
            {
                jQuery('input.mec-schema-event-status').on('change', function ()
                {
                    var value = jQuery(this).val();
                    if (value === 'EventMovedOnline')
                    {
                        jQuery('#mec_moved_online_link_wrapper').show();
                        jQuery('#mec_cancelled_reason_wrapper').hide();
                    } else if (value === 'EventCancelled')
                    {
                        jQuery('#mec_moved_online_link_wrapper').hide();
                        jQuery('#mec_cancelled_reason_wrapper').show();
                    } else
                    {
                        jQuery('#mec_moved_online_link_wrapper').hide();
                        jQuery('#mec_cancelled_reason_wrapper').hide();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Save event schema data
     * @param int $post_id
     * @return boolean
     * @author Webnus <info@webnus.net>
     */
    public function save_event($post_id)
    {
        // Check if our nonce is set.
        if (!isset($_POST['mec_event_nonce'])) return false;

        // Verify that the nonce is valid.
        if (!wp_verify_nonce(sanitize_text_field($_POST['mec_event_nonce']), 'mec_event_data')) return false;

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') and DOING_AUTOSAVE) return false;

        // Get Modern Events Calendar Data
        $_mec = isset($_POST['mec']) ? $this->main->sanitize_deep_array($_POST['mec']) : [];

        $event_status = isset($_mec['event_status']) ? sanitize_text_field($_mec['event_status']) : 'EventScheduled';
        if (!in_array($event_status, ['EventScheduled', 'EventRescheduled', 'EventPostponed', 'EventCancelled', 'EventMovedOnline'])) $event_status = 'EventScheduled';

        update_post_meta($post_id, 'mec_event_status', $event_status);

        $moved_online_link = (isset($_mec['moved_online_link']) and filter_var($_mec['moved_online_link'], FILTER_VALIDATE_URL)) ? sanitize_url($_mec['moved_online_link']) : '';
        update_post_meta($post_id, 'mec_moved_online_link', $moved_online_link);

        $cancelled_reason = (isset($_mec['cancelled_reason']) and !empty($_mec['cancelled_reason'])) ? sanitize_text_field($_mec['cancelled_reason']) : '';
        update_post_meta($post_id, 'mec_cancelled_reason', $cancelled_reason);

        $display_cancellation_reason_in_single_page = (isset($_mec['display_cancellation_reason_in_single_page']) and !empty($_mec['display_cancellation_reason_in_single_page']));
        update_post_meta($post_id, 'mec_display_cancellation_reason_in_single_page', $display_cancellation_reason_in_single_page);

        return true;
    }

    public function schema($event)
    {
        $status = $this->settings['schema'] ?? 0;
        if (!$status) return;

        $speakers = [];
        if (isset($event->data->speakers) and is_array($event->data->speakers) and count($event->data->speakers))
        {
            foreach ($event->data->speakers as $value)
            {
                $speakers[] = [
                    "@type" => isset($value['type']) && $value['type'] == 'group' ? "PerformingGroup" : "Person",
                    "name" => $value['name'],
                    "image" => $value['thumbnail'],
                    "sameAs" => $value['facebook'],
                ];
            }
        }

        $start_timestamp = ($event->data->time['start_timestamp'] ?? ($event->date['start']['timestamp'] ?? strtotime($event->date['start']['date'])));

        // All Params
        $params = MEC_feature_occurrences::param($event->ID, $start_timestamp, '*');

        $event_status = (isset($event->data->meta['mec_event_status']) and trim($event->data->meta['mec_event_status'])) ? $event->data->meta['mec_event_status'] : 'EventScheduled';
        $event_status = (isset($params['event_status']) and trim($params['event_status']) != '') ? $params['event_status'] : $event_status;

        if (!in_array($event_status, ['EventScheduled', 'EventRescheduled', 'EventPostponed', 'EventCancelled', 'EventMovedOnline'])) $event_status = 'EventScheduled';

        $cost = $this->main->get_event_cost($event, false);

        $location_id = $this->main->get_master_location_id($event);
        $location = ($location_id ? $this->main->get_location_data($location_id) : []);

        $start_date = (isset($event->date) and isset($event->date['start']) and isset($event->date['start']['date'])) ? $event->date['start']['date'] : null;
        $end_date = (isset($event->date) and isset($event->date['end']) and isset($event->date['end']['date'])) ? $event->date['end']['date'] : null;

        $allday = isset($event->data->meta['mec_allday']) ? (int) $event->data->meta['mec_allday'] : 0;
        $hide_time = isset($event->data->meta['mec_hide_time']) ? (int) $event->data->meta['mec_hide_time'] : 0;
        $hide_end_time = $this->main->hide_end_time_status($event->ID);

        $start_ts = isset($event->date['start']['timestamp']) ? (int) $event->date['start']['timestamp'] : (isset($start_date) ? strtotime($start_date) : 0);
        $end_ts = isset($event->date['end']['timestamp']) ? (int) $event->date['end']['timestamp'] : (isset($end_date) ? strtotime($end_date) : 0);

        $start_schema = $start_date;
        $end_schema = $end_date;

        if (!$allday && !$hide_time && $start_ts)
        {
            try
            {
                $tz = new \DateTimeZone($this->main->get_timezone($event->ID));
                $dtS = new \DateTime('@' . $start_ts);
                $dtS->setTimezone($tz);
                $start_schema = $dtS->format('Y-m-d\TH:i:sP');
            }
            catch (\Exception $e)
            {
                $start_schema = $start_date;
            }
        }

        if (!$allday && !$hide_time && !$hide_end_time && $end_ts)
        {
            try
            {
                $tz = new \DateTimeZone($this->main->get_timezone($event->ID));
                $dtE = new \DateTime('@' . $end_ts);
                $dtE->setTimezone($tz);
                $end_schema = $dtE->format('Y-m-d\TH:i:sP');
            }
            catch (\Exception $e)
            {
                $end_schema = $end_date;
            }
        }

        $event_link = $this->main->get_event_date_permalink($event, $start_date);
        $soldout = $this->main->is_soldout($event, $event->date);

        $organizer_id = $this->main->get_master_organizer_id($event);
        $organizer = $organizer_id ? $this->main->get_organizer_data($organizer_id) : [];

        $moved_online_link = (isset($event->data->meta['mec_moved_online_link']) and trim($event->data->meta['mec_moved_online_link'])) ? $event->data->meta['mec_moved_online_link'] : '';
        $moved_online_link = (isset($params['moved_online_link']) and trim($params['moved_online_link']) != '') ? $params['moved_online_link'] : $moved_online_link;

        $content = strip_tags($event->data->post->post_content);

        $this->factory->printOnAjaxOrFooter(function () use ($event, $event_status, $start_date, $end_date, $start_schema, $end_schema, $moved_online_link, $event_link, $location, $organizer, $cost, $soldout, $content, $speakers)
        {
            ?>
            <script type="application/ld+json">
            {
                "@context": "http://schema.org",
                "@type": "Event",
                "eventStatus": "https://schema.org/<?php echo $this->escape($event_status); ?>",
                "startDate": "<?php echo $this->escape($start_schema); ?>",
                "endDate": "<?php echo $this->escape($end_schema); ?>",
                "eventAttendanceMode": "<?php echo($event_status === 'EventMovedOnline' ? "https://schema.org/OnlineEventAttendanceMode" : "https://schema.org/OfflineEventAttendanceMode"); ?>",
                "location":
                {
                    "@type": "<?php echo(($event_status === 'EventMovedOnline') ? 'VirtualLocation' : 'Place'); ?>",
                    <?php if ($event_status === 'EventMovedOnline'): ?>
                    "url": "<?php echo(trim($moved_online_link) ? esc_url($moved_online_link) : esc_url($event_link)); ?>"
                    <?php else: ?>
                    "name": "<?php echo(isset($location['name']) ? $this->escape($location['name']) : ''); ?>",
                    "image": "<?php echo(isset($location['thumbnail']) ? esc_url($location['thumbnail']) : ''); ?>",
                    "address": "<?php echo(isset($location['address']) ? $this->escape($location['address']) : ''); ?>"
                    <?php endif; ?>
                },
                <?php if (!isset($this->settings['organizers_status']) || $this->settings['organizers_status']): ?>
                "organizer":
                {
                    "@type": "Person",
                    "name": "<?php echo(isset($organizer['name']) ? $this->escape($organizer['name']) : ''); ?>",
                    "url": "<?php echo(isset($organizer['url']) ? esc_url($organizer['url']) : ''); ?>"
                },
                <?php endif; ?>
                "offers":
                {
                    "url": "<?php echo esc_url($event->data->permalink); ?>",
                    "price": "<?php echo trim($cost) != '' ? $this->escape($cost) : 0; ?>",
                    "priceCurrency": "<?php echo $this->escape($this->main->get_currency_code($event)); ?>",
                    "availability": "<?php echo($soldout ? "https://schema.org/SoldOut" : "https://schema.org/InStock"); ?>",
                    "validFrom": "<?php echo date('Y-m-d\TH:i', strtotime($event->date['start']['date'])); ?>"
                },
                "performer": <?php echo(count($speakers) ? json_encode($speakers) : '""'); ?>,
                "description": "<?php echo $this->escape(preg_replace('/\s/u', ' ', $content)); ?>",
                "image": "<?php echo(!empty($event->data->featured_image['full']) ? $event->data->featured_image['full'] : ''); ?>",
                "name": "<?php esc_html_e($event->data->title); ?>",
                "url": "<?php echo $this->escape(esc_url($event_link)); ?>"
            }

            </script>
            <?php
        });
    }

    public function schema_text($text, $event)
    {
        ob_start();
        do_action('mec_schema', $event);
        return ob_get_clean();
    }

    public function escape($text)
    {
        if ($text === null) return '';
        if (!is_scalar($text)) return '';

        $encoded = wp_json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false || $encoded === null) return '';

        // Remove the wrapping quotes added by wp_json_encode().
        if (strlen($encoded) >= 2 && $encoded[0] === '"' && substr($encoded, -1) === '"')
        {
            return substr($encoded, 1, -1);
        }

        return $encoded;
    }
}

<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC Ticket Variations class.
 * @author Webnus <info@webnus.net>
 */
class MEC_ticketVariations extends MEC_base
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

    public function item($args)
    {
        $name_prefix = $args['name_prefix'] ?? 'mec[ticket_variations]';
        $id_prefix = $args['id_prefix'] ?? 'ticket_variation';
        $ticket_variation = $args['value'] ?? [];
        $i = $args['i'] ?? ':i:';
        ?>
        <div class="mec-box mec_ticket_variation_row mec-form-row" id="mec_<?php echo esc_attr($id_prefix); ?>_row<?php echo esc_attr($i); ?>">
            <div class="mec-form-row">
                <span class="mec_field_sort button"><?php esc_html_e('Sort', 'modern-events-calendar-lite'); ?></span>
                <button class="button mec_remove_ticket_variation_button mec-dash-remove-btn" type="button" id="mec_remove_<?php echo esc_attr($id_prefix); ?>_button<?php echo esc_attr($i); ?>" onclick="mec_remove_ticket_variation(<?php echo esc_attr($i); ?>, '<?php echo esc_attr($id_prefix); ?>');"><?php esc_html_e('Remove', 'modern-events-calendar-lite'); ?></button>
                <input class="mec-col-8" type="text" name="<?php echo esc_attr($name_prefix); ?>[<?php echo esc_attr($i); ?>][title]" placeholder="<?php esc_attr_e('Title', 'modern-events-calendar-lite'); ?>" value="<?php echo(isset($ticket_variation['title']) ? esc_attr($ticket_variation['title']) : ''); ?>"/>
            </div>
            <div class="mec-form-row">
                <span class="mec-col-4">
                    <input type="text" name="<?php echo esc_attr($name_prefix); ?>[<?php echo esc_attr($i); ?>][price]" placeholder="<?php esc_attr_e('Price', 'modern-events-calendar-lite'); ?>" value="<?php echo(isset($ticket_variation['price']) ? esc_attr($ticket_variation['price']) : ''); ?>"/>
                    <span class="mec-tooltip">
                        <div class="box top">
                            <h5 class="title"><?php esc_html_e('Price', 'modern-events-calendar-lite'); ?></h5>
                            <div class="content">
                                <p>
                                    <?php esc_attr_e('Option Price', 'modern-events-calendar-lite'); ?>
                                    <a href="https://webnus.net/dox/modern-events-calendar/ticket-variations/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a>
                                </p>
                            </div>
                        </div>
                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                    </span>
                </span>
                <span class="mec-col-4">
                    <input type="number" min="0" name="<?php echo esc_attr($name_prefix); ?>[<?php echo esc_attr($i); ?>][max]" placeholder="<?php esc_attr_e('Maximum Per Ticket', 'modern-events-calendar-lite'); ?>" value="<?php echo $ticket_variation['max'] ?? ''; ?>">
                    <span class="mec-tooltip">
                        <div class="box top">
                            <h5 class="title"><?php esc_html_e('Maximum Per Ticket', 'modern-events-calendar-lite'); ?></h5>
                            <div class="content">
                                <p>
                                    <?php esc_attr_e('Maximum Per Ticket. Leave blank for unlimited.', 'modern-events-calendar-lite'); ?>
                                    <a href="https://webnus.net/dox/modern-events-calendar/ticket-variations/" target="_blank"><?php esc_html_e('Read More', 'modern-events-calendar-lite'); ?></a>
                                </p>
                            </div>
                        </div>
                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                    </span>
                </span>
            </div>
            <div class="mec-form-row">
                <div class="mec-col-4"><strong><?php esc_html_e('Notification Placeholders:', 'modern-events-calendar-lite'); ?></strong></div>
                <div class="mec-col-8">
                    <ul style="margin: 0;">
                        <li>%%ticket_variations_<?php echo esc_attr($i); ?>_title%%</li>
                        <li>%%ticket_variations_<?php echo esc_attr($i); ?>_count%%</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
}
<?php
/** no direct access **/
defined('MECEXEC') or die();

/** @var MEC_feature_fes $this */

// Page Size
$limit = 20;

// Current Page
$paged = get_query_var('paged') ? absint(get_query_var('paged')) : 1;

// Search
$s = $_GET['mec_s'] ?? '';

// Taxonomy Search
$tax = [];

// Category
$cat = $_GET['mec_cat'] ?? '';

// Expired filter
$expired_filter = $_GET['mec-expired'] ?? 'active';
$expired_filter = ($expired_filter === 'expired') ? 'expired' : 'active';

if ($cat)
{
    $tax[] = [
        'taxonomy' => 'mec_category',
        'field' => 'term_id',
        'terms' => sanitize_text_field($cat)
    ];
}

// Query Args
$args = [
    'post_type' => $this->PT,
    'posts_per_page' => $limit,
    'paged' => $paged,
    's' => sanitize_text_field($s),
    'post_status' => ['pending', 'draft', 'future', 'publish'],
    'tax_query' => $tax
];

// Apply Author Query
if (!current_user_can('edit_others_posts')) $args['author'] = get_current_user_id();

// Expired/Active filter handling
$expired_ids = [];
$events_feature = MEC::getInstance('app.features.events', 'MEC_feature_events');
if ($events_feature && method_exists($events_feature, 'get_expired_event_ids')) $expired_ids = $events_feature->get_expired_event_ids();

if ($expired_filter === 'expired')
{
    $args['post__in'] = count($expired_ids) ? $expired_ids : [0];
}
else if (!empty($expired_ids))
{
    $args['post__not_in'] = $expired_ids;
}

// The Query
$query = new WP_Query($args);

// Date Format
$date_format = get_option('date_format');

// Display Date
$display_date = isset($this->settings['fes_display_date_in_list']) && $this->settings['fes_display_date_in_list'];

// Categories
$categories = get_terms([
    'taxonomy' => 'mec_category',
    'hide_empty' => 0,
]);

// Generating javascript code of countdown module
$javascript = '<script>
jQuery(document).ready(function()
{
    jQuery(".mec-fes-event-remove").on("click", function(event)
    {
        var id = jQuery(this).data("id");
        var confirmed = jQuery(this).data("confirmed");

        if(confirmed == "0")
        {
            jQuery(this).data("confirmed", "1");
            jQuery(this).addClass("mec-fes-waiting");
            jQuery(this).text("'.esc_attr__('Click again to remove!', 'modern-events-calendar-lite').'");

            return false;
        }

        // Add loading class to the row
        jQuery("#mec_fes_event_"+id).addClass("loading");

        jQuery.ajax(
        {
            type: "POST",
            url: "'.admin_url('admin-ajax.php', NULL).'",
            data: "action=mec_fes_remove&_wpnonce='.wp_create_nonce('mec_fes_remove').'&post_id="+id,
            dataType: "JSON",
            success: function(response)
            {
                if(response.success == "1")
                {
                    // Remove the row
                    jQuery("#mec_fes_event_"+id).remove();
                }
                else
                {
                    // Remove the loading class from the row
                    jQuery("#mec_fes_event_"+id).removeClass("loading");
                }
            },
            error: function(jqXHR, textStatus, errorThrown)
            {
                // Remove the loading class from the row
                jQuery("#mec_fes_event_"+id).removeClass("loading");
            }
        });
    });
});
</script>';

// Include javascript code into the footer
$this->factory->params('footer', $javascript);
?>
<div class="mec-fes-list">
    <div class="mec-fes-list-top-actions">
        <a href="<?php echo esc_url($this->link_add_event()); ?>"><?php echo esc_html__('Add new', 'modern-events-calendar-lite'); ?></a>
        <form method="get" class="mec-form-row">
            <input class="mec-col-4" title="<?php esc_attr_e('Search Query', 'modern-events-calendar-lite'); ?>" type="search" name="mec_s" value="<?php echo esc_attr($s); ?>" placeholder="<?php esc_attr_e('Search Query', 'modern-events-calendar-lite'); ?>">
            <select class="mec-col-3" name="mec_cat" title="<?php esc_attr_e('Event Category', 'modern-events-calendar-lite'); ?>">
                <option value=""><?php esc_html_e('All Categories', 'modern-events-calendar-lite'); ?></option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category->term_id; ?>" <?php echo $category->term_id == $cat ? 'selected' : ''; ?>><?php echo $category->name; ?></option>
                <?php endforeach; ?>
            </select>
            <select class="mec-col-3" name="mec-expired" title="<?php esc_attr_e('Event Status', 'modern-events-calendar-lite'); ?>">
                <option value="active" <?php selected('active', $expired_filter); ?>><?php esc_html_e('Active', 'modern-events-calendar-lite'); ?></option>
                <option value="expired" <?php selected('expired', $expired_filter); ?>><?php esc_html_e('Expired', 'modern-events-calendar-lite'); ?></option>
            </select>
            <button class="mec-col-2 button" type="submit"><?php esc_html_e('Filter', 'modern-events-calendar-lite'); ?></button>
        </form>
    </div>
    <?php if($query->have_posts()): ?>
        <?php do_action('mec_fes_list'); ?>
        <ul>
            <?php
                while($query->have_posts()): $query->the_post();
                // Show Post Status
                global $post;
                $status = $this->main->get_event_label_status(trim($post->post_status));
            ?>
            <li id="mec_fes_event_<?php echo get_the_ID(); ?>">
                <span class="mec-event-title">
                    <a href="<?php echo esc_url($this->link_edit_event(get_the_ID())); ?>"><?php the_title(); ?></a>
                    <?php if($display_date): ?>
                    <span>(<?php echo MEC_kses::element($this->main->date_label(array(
                        'date' => get_post_meta(get_the_ID(), 'mec_start_date', true)
                    ), array(
                        'date' => get_post_meta(get_the_ID(), 'mec_end_date', true)
                    ), $date_format)); ?>)</span>
                    <?php endif; ?>
                </span>
                <?php
                    $event_status = get_post_status(get_the_ID());
                    if(isset($event_status) && strtolower($event_status) === 'publish' && isset($this->settings['booking_status']) && $this->settings['booking_status']):
                ?>
                <span class="mec-fes-event-export"><a href="#mec-fes-export-wrapper-<?php echo get_the_ID(); ?>" data-lity><div class="wn-p-t-right"><div class="wn-p-t-text-content"><?php echo esc_html__('Download Attendees', 'modern-events-calendar-lite'); ?></div><i></i></div></a></span>
                <?php endif; ?>

                <span class="mec-fes-event-view"><a href="<?php the_permalink(); ?>"><div class="wn-p-t-right"><div class="wn-p-t-text-content"><?php echo esc_html__('View Event', 'modern-events-calendar-lite'); ?></div><i></i></div></a></span>
                <span class="mec-fes-event-edit"><a href="<?php echo esc_url($this->link_edit_event(get_the_ID())); ?>"><div class="wn-p-t-right"><div class="wn-p-t-text-content"><?php echo esc_html__('Edit Event', 'modern-events-calendar-lite'); ?></div><i></i></div></a></span>
                <?php if(current_user_can('delete_post', get_the_ID())): ?>
                <span class="mec-fes-event-remove" data-confirmed="0" data-id="<?php echo get_the_ID(); ?>"><div class="wn-p-t-right"><div class="wn-p-t-text-content"><?php echo esc_html__('Remove Event', 'modern-events-calendar-lite'); ?></div><i></i></div></span>
                <?php endif; ?>
                <span class="mec-fes-event-view mec-event-status <?php echo esc_attr($status['status_class']); ?>"><?php echo esc_html($status['label']); ?></span>
                <div class="mec-fes-export-wrapper mec-modal-wrap lity-hide" id="mec-fes-export-wrapper-<?php echo get_the_ID(); ?>" data-event-id="<?php echo get_the_ID(); ?>">
                    <div class="mec-fes-btn-date">
                        <?php $mec_repeat_info = get_post_meta(get_the_ID(), 'mec_repeat', true); if(isset($mec_repeat_info['status']) and $mec_repeat_info['status']): ?>
                            <ul class="mec-export-list-wrapper">
                                <?php
                                $past_week = strtotime('-7 days', current_time('timestamp', 0));

                                $render = $this->getRender();
                                $dates = $render->dates(get_the_ID(), NULL, 15, date('Y-m-d', $past_week));

                                $book = $this->getBook();
                                foreach($dates as $date)
                                {
                                    if(isset($date['start']['date']) and isset($date['end']['date']))
                                    {
                                        $attendees_count = 0;

                                        $bookings = $this->main->get_bookings(get_the_ID(), $date['start']['timestamp']);
                                        foreach($bookings as $booking)
                                        {
                                            $attendees_count += $book->get_total_attendees($booking->ID);
                                        }
                                ?>
                                    <li class="mec-export-list-item" data-time="<?php echo esc_attr($date['start']['timestamp']); ?>"><?php echo MEC_kses::element($this->main->date_label($date['start'], $date['end'], $date_format)); ?> <span class="mec-export-badge"><?php echo esc_html($attendees_count); ?></span></li>
                                <?php
                                    }
                                }
                                ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="mec-fes-btn-export">
                        <span class="mec-event-export-csv"><?php esc_html_e('CSV', 'modern-events-calendar-lite'); ?></span>
                        <span class="mec-event-export-excel"><?php esc_html_e('MS EXCEL', 'modern-events-calendar-lite'); ?></span>
                    </div>
                </div>
            </li>
            <?php endwhile; wp_reset_postdata(); // Restore original Post Data ?>
        </ul>
        <div class="pagination mec-pagination">
            <?php $big = 999999999; echo paginate_links(array(
                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format' => 'paged=%#%',
                'current' => max(1, get_query_var('paged')),
                'total' => $query->max_num_pages,
                'type' => 'list',
                'prev_next' => true,
            )); ?>
        </div>
    <?php else: ?>
    <p><?php echo esc_html__('No events found!', 'modern-events-calendar-lite'); ?></p>
    <?php endif; ?>
</div>

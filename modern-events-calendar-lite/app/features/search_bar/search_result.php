<?php
/** no direct access **/
defined('MECEXEC') or die();

$settings = $this->main->get_settings();
$event_id = get_the_ID();

$today = date('Y-m-d', current_time('timestamp'));
$next_occurrences = $this->getRender()->dates($event_id, null, 1, $today);
$next_occurrence_start = $next_occurrences[0]['start'] ?? [];
$occurrence_timestamp = $next_occurrence_start['timestamp'] ?? null;
$occurrence_date = $next_occurrence_start['date'] ?? null;

if (!$occurrence_timestamp && !empty($next_occurrence_start['date'])) $occurrence_timestamp = strtotime($next_occurrence_start['date']);
if (!$occurrence_timestamp)
{
    $fallback_date = get_post_meta($event_id, 'mec_start_date', true);
    $occurrence_timestamp = $fallback_date ? strtotime($fallback_date) : null;
    if (!$occurrence_date && $fallback_date) $occurrence_date = $fallback_date;
}
if (!$occurrence_date && $occurrence_timestamp) $occurrence_date = date('Y-m-d', $occurrence_timestamp);

$event_link = get_permalink($event_id);
if ($occurrence_date)
{
    $event_data = $this->getRender()->data($event_id);
    $event_link = $this->main->get_event_date_permalink((object) ['data' => $event_data], $occurrence_date);
}

$allday = (get_post_meta($event_id, 'mec_allday', true) == '1');
if ($allday)
{
    $time = $this->main->m('all_day', esc_html__('All Day', 'modern-events-calendar-lite'));
}
else
{
    $time_format = get_option('time_format');
    $start_ts = $next_occurrence_start['timestamp'] ?? $occurrence_timestamp;
    $next_occurrence_end = $next_occurrences[0]['end'] ?? [];
    $end_ts = $next_occurrence_end['timestamp'] ?? null;

    $start_str = $start_ts ? $this->main->date_i18n($time_format, $start_ts) : '';
    $end_str = $end_ts ? $this->main->date_i18n($time_format, $end_ts) : '';

    $time = $start_str . ($end_str ? ' - ' . $end_str : '');
}
?>
<article class="mec-search-bar-result">
    <div class="mec-event-list-search-bar-date mec-color">
        <span class="mec-date-day">
            <?php if ($occurrence_timestamp) echo esc_html($this->main->date_i18n('d', $occurrence_timestamp)); ?>
        </span>
        <?php if ($occurrence_timestamp) echo esc_html($this->main->date_i18n('F', $occurrence_timestamp)); ?>
    </div>
    <div class="mec-event-image">
        <a href="<?php echo esc_url($event_link); ?>" target="_blank"><?php the_post_thumbnail('thumbnail'); ?></a>
    </div>
    <div class="mec-event-time mec-color">
        <i class="mec-sl-clock-o"></i><?php echo esc_html($time); ?>
    </div>
    <h4 class="mec-event-title">
        <a class="mec-color-hover" href="<?php echo esc_url($event_link); ?>" target="_blank"><?php the_title(); ?></a>
    </h4>
    <div class="mec-event-detail">
        <?php
            $id = get_post_meta($event_id, 'mec_location_id', true);
			$term = get_term($id, 'mec_location');
			echo esc_html($term->name);
        ?>
    </div>
</article>

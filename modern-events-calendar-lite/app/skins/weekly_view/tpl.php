<?php
/** no direct access **/
defined('MECEXEC') or die();

/** @var MEC_skin_weekly_view $this */

// Get layout path
$render_path = $this->get_render_path();

// before/after Month
$_1month_before = strtotime('-1 Month', strtotime($this->start_date));
$_1month_after = strtotime('+1 Month', strtotime($this->start_date));

// Current month time
$current_month_time = strtotime($this->start_date);

// Weeks
$weeks = '';
foreach($this->weeks as $week_number=>$week)
{
    $first_week_day = $week[0];

    $i = 1;
    while(strtotime($first_week_day) < $current_month_time)
    {
        $first_week_day = $week[$i];
        $i++;
    }

    $weeks .= '<dl class="mec-weekly-view-week" id="mec_weekly_view_week_'.esc_attr($this->id.'_'.date('Ym', strtotime($first_week_day)).$week_number).'" data-week-id="'.esc_attr(date('Ym', strtotime($first_week_day)).$week_number).'" data-week-number="' . esc_attr($week_number) . '" data-max-weeks="'.count($this->weeks).'">';
    foreach($week as $day)
    {
        $time = strtotime($day);
        $count = isset($this->events[$day]) ? count($this->events[$day]) : 0;
        $weeks .= '<dt data-events-count="'.esc_attr($count).'" class="'.(!$count ? 'mec-weekly-disabled mec-table-nullday' : '').'">'
            .'<span class="mec-weekly-view-weekday">'.esc_html($this->main->date_i18n('D', $time)).'</span> '
            .'<span class="mec-weekly-view-monthday">'.esc_html($this->main->date_i18n('j', $time)).'</span> '
        .'</dt>';
    }

    $weeks .= '</dl>';
}

// Generate Events
ob_start();
include $render_path;
$date_events = ob_get_clean();

$navigator_html = '';

// Generate Month Navigator
if($this->next_previous_button)
{
    // Show previous month handler if showing past events allowed
    if(!isset($this->atts['show_past_events']) or
       (isset($this->atts['show_past_events']) and $this->atts['show_past_events']) or
       (isset($this->atts['show_past_events']) and !$this->atts['show_past_events'] and strtotime(date('Y-m-t', $_1month_before)) >= time())
    )
    {
        $navigator_html .= '<div class="mec-previous-month mec-load-month mec-color" data-mec-year="'.date('Y', $_1month_before).'" data-mec-month="'.date('m', $_1month_before).'"><a href="#" class="mec-load-month-link"><i class="mec-sl-angle-left"></i></a></div>';
    }

    $navigator_html .= '<h4 class="mec-month-label">'.esc_html($this->main->date_i18n('Y F', $current_month_time)).'</h4>';

    // Show next month handler if needed
    if(!$this->show_only_expired_events || strtotime(date('Y-m-01', $_1month_after)) <= time())
    {
        $navigator_html .= '<div class="mec-next-month mec-load-month mec-color" data-mec-year="'.date('Y', $_1month_after).'" data-mec-month="'.date('m', $_1month_after).'"><a href="#" class="mec-load-month-link"><i class="mec-sl-angle-right"></i></a></div>';
    }
}

$week_html = '<div class="mec-calendar-d-top"><div class="mec-previous-month mec-load-week mec-color" href="#"><i class="mec-sl-angle-left"></i></div><div class="mec-next-month mec-load-week mec-color" href="#"><i class="mec-sl-angle-right"></i></div><h3 class="mec-current-week">'.sprintf(esc_html__('Week %s', 'modern-events-calendar-lite'), '<span>'.(isset($this->week_of_days[$this->today]) ? $this->week_of_days[$this->today] : 1).'</span>').'</h3></div>';

$month_html = '<div class="mec-weeks-container mec-calendar-d-table">'.MEC_kses::element($weeks).'</div>
<div class="mec-week-events-container">'.MEC_kses::full($date_events).'</div>';

// Return the data if called by AJAX
if(isset($this->atts['return_items']) and $this->atts['return_items'])
{
    echo json_encode(array(
        'month' => $week_html.$month_html,
        'navigator' => $navigator_html,
        'week_id' => date('Ym', $current_month_time).$this->week_of_days[$this->today],
        'previous_month' => array('label' => $this->main->date_i18n('Y F', $_1month_before), 'id' => date('Ym', $_1month_before), 'year' => date('Y', $_1month_before), 'month' => date('m', $_1month_before)),
        'current_month' => array('label' => $this->main->date_i18n('Y F', $current_month_time), 'id' => date('Ym', $current_month_time), 'year' => date('Y', $current_month_time), 'month' => date('m', $current_month_time)),
        'next_month' => array('label' => $this->main->date_i18n('Y F', $_1month_after), 'id' => date('Ym', $_1month_after), 'year' => date('Y', $_1month_after), 'month' => date('m', $_1month_after)),
    ));
    exit;
}

$sed_method = $this->sed_method;
if($sed_method == 'new') $sed_method = '0';

$styling = $this->main->get_styling();
$event_colorskin = (isset($styling['mec_colorskin'] ) || isset($styling['color'])) ? 'colorskin-custom' : '';

$dark_mode = $styling['dark_mode'] ?? '';
if($dark_mode == 1) $set_dark = 'mec-dark-mode';
else $set_dark = '';

do_action('mec_start_skin', $this->id);
do_action('mec_weekly_skin_head');
?>
<div id="mec_skin_<?php echo esc_attr($this->id); ?>" class="mec-wrap <?php echo esc_attr($event_colorskin . ' ' . $this->html_class . ' ' . $set_dark); ?>">

    <?php if($this->sf_status) echo MEC_kses::full($this->sf_search_form()); ?>

    <?php echo MEC_kses::full($this->magenda_render_shell($date_events)); ?>

    <?php echo $this->subscribe_to_calendar(); ?>
    <?php echo $this->display_credit_url(); ?>

</div>

<?php
defined('MECEXEC') or die();
/** @var MEC_main $this */
$events = wp_count_posts($this->get_main_post_type());
$calendars = wp_count_posts('mec_calendars');
$locations = wp_count_terms('mec_location', array('hide_empty' => false, 'parent' => 0));
$organizers = wp_count_terms('mec_organizer', array('hide_empty' => false, 'parent' => 0));
$locations = is_wp_error($locations) ? 0 : $locations;
$organizers = is_wp_error($organizers) ? 0 : $organizers;
?>
<div id="webnus-dashboard" class="wrap about-wrap">
 <div class="welcome-head w-clearfix"><div class="w-row"><div class="w-col-sm-9"><h1><?php esc_html_e('Calendar', 'modern-events-calendar-lite'); ?></h1><div class="w-welcome"><?php esc_html_e('Manage events and calendar views.', 'modern-events-calendar-lite'); ?></div></div><div class="w-col-sm-3"><span class="w-theme-version"><?php esc_html_e('Version', 'modern-events-calendar-lite'); ?> <?php echo esc_html(MEC_VERSION); ?></span></div></div></div>
 <div class="w-row">
  <div class="w-col-sm-12"><div class="w-box mec-intro-section"><div class="w-box-content mec-intro-section-links wp-core-ui">
   <a class="mec-intro-section-link-tag button button-primary button-hero" href="<?php echo esc_url(admin_url('post-new.php?post_type=mec-events')); ?>"><?php esc_html_e('Add New Event', 'modern-events-calendar-lite'); ?></a>
   <a class="mec-intro-section-link-tag button button-secondary button-hero" href="<?php echo esc_url(admin_url('edit.php?post_type=mec-events')); ?>"><?php esc_html_e('All Events', 'modern-events-calendar-lite'); ?></a>
   <a class="mec-intro-section-link-tag button button-secondary button-hero" href="<?php echo esc_url(admin_url('admin.php?page=MEC-settings')); ?>"><?php esc_html_e('Settings', 'modern-events-calendar-lite'); ?></a>
  </div></div></div>
  <div class="w-col-sm-3"><div class="w-box doc"><div class="w-box-child mec-count-child"><p class="mec_dash_count"><?php echo esc_html($events->publish ?? 0); ?></p><?php esc_html_e('Events', 'modern-events-calendar-lite'); ?></div></div></div>
  <div class="w-col-sm-3"><div class="w-box doc"><div class="w-box-child mec-count-child"><p class="mec_dash_count"><?php echo esc_html($calendars->publish ?? 0); ?></p><?php esc_html_e('Shortcodes', 'modern-events-calendar-lite'); ?></div></div></div>
  <div class="w-col-sm-3"><div class="w-box doc"><div class="w-box-child mec-count-child"><p class="mec_dash_count"><?php echo esc_html($locations); ?></p><?php esc_html_e('Locations', 'modern-events-calendar-lite'); ?></div></div></div>
  <div class="w-col-sm-3"><div class="w-box doc"><div class="w-box-child mec-count-child"><p class="mec_dash_count"><?php echo esc_html($organizers); ?></p><?php esc_html_e('Organizers', 'modern-events-calendar-lite'); ?></div></div></div>
 </div>
 <div class="w-row"><div class="w-col-sm-12"><div class="w-box upcoming-events"><div class="w-box-head"><?php esc_html_e('Upcoming Events', 'modern-events-calendar-lite'); ?></div><div class="w-box-content"><?php
 $render = $this->getRender();
 echo MEC_kses::full($render->skin('list', array('sk-options' => array('list' => array('style' => 'minimal','start_date_type' => 'today','pagination_method' => '0','limit' => '6','month_divider' => '0','load_more_button' => false,'ignore_js' => true)))));
 ?></div></div></div></div>
</div>

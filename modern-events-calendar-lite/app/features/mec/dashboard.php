<?php
defined('MECEXEC') or die();
/** @var MEC_main $this */
global $wp_version;
$events = wp_count_posts($this->get_main_post_type());
$calendars = wp_count_posts('mec_calendars');
$locations = wp_count_terms('mec_location', array('hide_empty' => false, 'parent' => 0));
$organizers = wp_count_terms('mec_organizer', array('hide_empty' => false, 'parent' => 0));
$locations = is_wp_error($locations) ? 0 : $locations;
$organizers = is_wp_error($organizers) ? 0 : $organizers;
$can_manage = current_user_can('manage_options');
$log_file = WP_CONTENT_DIR . '/debug.log';
if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG) && WP_DEBUG_LOG !== '') $log_file = WP_DEBUG_LOG;
$log_available = $can_manage && defined('WP_DEBUG') && WP_DEBUG && is_readable($log_file);
$log_size = $log_available ? filesize($log_file) : 0;
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
 <div class="w-row">
  <div class="w-col-sm-<?php echo $can_manage ? 6 : 12; ?>"><div class="w-box support-page"><div class="w-box-head"><?php esc_html_e('System Information', 'modern-events-calendar-lite'); ?></div><div class="w-box-content"><ul class="system-information">
   <li><div class="mec-si-label"><?php esc_html_e('Home URL', 'modern-events-calendar-lite'); ?></div><div class="mec-si-value"><?php echo esc_url(get_home_url()); ?></div></li>
   <li><div class="mec-si-label"><?php esc_html_e('Site URL', 'modern-events-calendar-lite'); ?></div><div class="mec-si-value"><?php echo esc_url(get_site_url()); ?></div></li>
   <li><div class="mec-si-label"><?php esc_html_e('Locale', 'modern-events-calendar-lite'); ?></div><div class="mec-si-value"><?php echo esc_html(get_locale()); ?></div></li>
   <li><div class="mec-si-label"><?php esc_html_e('MEC Version', 'modern-events-calendar-lite'); ?></div><div class="mec-si-value"><?php echo esc_html(MEC_VERSION); ?></div></li>
   <li><div class="mec-si-label"><?php esc_html_e('WordPress Version', 'modern-events-calendar-lite'); ?></div><div class="mec-si-value"><?php echo esc_html($wp_version); ?></div></li>
   <li><div class="mec-si-label"><?php esc_html_e('PHP Version', 'modern-events-calendar-lite'); ?></div><div class="mec-si-value"><?php echo esc_html(PHP_VERSION); ?></div></li>
   <li><div class="mec-si-label"><?php esc_html_e('PHP Curl', 'modern-events-calendar-lite'); ?></div><div class="mec-si-value"><?php echo function_exists('curl_version') ? esc_html__('Yes', 'modern-events-calendar-lite') : esc_html__('No', 'modern-events-calendar-lite'); ?></div></li>
  </ul></div></div></div>
  <?php if ($can_manage): ?><div class="w-col-sm-6"><div class="w-box support-page"><div class="w-box-head"><?php esc_html_e('Debug Log', 'modern-events-calendar-lite'); ?></div><div class="w-box-content">
   <?php if (!defined('WP_DEBUG') || !WP_DEBUG): ?><p class="info-msg"><?php esc_html_e('WordPress debug is not enabled on this website.', 'modern-events-calendar-lite'); ?></p>
   <?php elseif (!$log_available || !$log_size): ?><p class="info-msg"><?php esc_html_e('Debug mode is enabled, but there is no readable log file to download yet.', 'modern-events-calendar-lite'); ?></p>
   <?php else: ?><p><?php echo wp_kses_post(sprintf(esc_html__('Log file size: %s. %s', 'modern-events-calendar-lite'), esc_html(size_format($log_size)), '<a href="' . esc_url($this->URL('admin') . '?mec-download-log-file=1') . '">' . esc_html__('Download', 'modern-events-calendar-lite') . '</a>')); ?></p><?php endif; ?>
  </div></div></div><?php endif; ?>
 </div>
</div>

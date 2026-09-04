<?php
defined('MECEXEC') or die();
/** @var MEC_feature_mec $this */
global $wp_version;
$can_manage = current_user_can('manage_options');
$log_file = WP_CONTENT_DIR . '/debug.log';
if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG) && WP_DEBUG_LOG !== '') $log_file = WP_DEBUG_LOG;
$log_available = $can_manage && defined('WP_DEBUG') && WP_DEBUG && is_readable($log_file);
$log_size = $log_available ? filesize($log_file) : 0;
?>
<div id="webnus-dashboard" class="wrap about-wrap">
 <div class="welcome-head w-clearfix"><div class="w-row"><div class="w-col-sm-12"><h1><?php esc_html_e('Support', 'modern-events-calendar-lite'); ?></h1><div class="w-welcome"><?php esc_html_e('System information and debug tools for this calendar installation.', 'modern-events-calendar-lite'); ?></div></div></div></div>
 <div class="welcome-content w-clearfix extra"><div class="w-row">
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
   <?php else: ?><p><?php echo wp_kses_post(sprintf(esc_html__('Log file size: %s. %s', 'modern-events-calendar-lite'), esc_html(size_format($log_size)), '<a href="' . esc_url($this->main->URL('admin') . '?mec-download-log-file=1') . '">' . esc_html__('Download', 'modern-events-calendar-lite') . '</a>')); ?></p><?php endif; ?>
  </div></div></div><?php endif; ?>
 </div></div>
</div>

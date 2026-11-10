<?php
defined('MECEXEC') or die();
$ix = $this->main->get_ix_options();
?>
<div class="wrap" id="mec-wrap">
<h1><?php esc_html_e('Google Calendar Synchronization', 'renginiu-kalendorius'); ?></h1>
<h2 class="nav-tab-wrapper">
<a href="<?php echo esc_url($this->main->remove_qs_var('tab')); ?>" class="nav-tab"><?php esc_html_e('Google Cal. Import', 'renginiu-kalendorius'); ?></a>
<a href="<?php echo esc_url($this->main->add_qs_var('tab', 'MEC-g-calendar-export')); ?>" class="nav-tab"><?php esc_html_e('Google Cal. Export', 'renginiu-kalendorius'); ?></a>
<a href="<?php echo esc_url($this->main->add_qs_var('tab', 'MEC-sync')); ?>" class="nav-tab nav-tab-active"><?php esc_html_e('Synchronization', 'renginiu-kalendorius'); ?></a>
</h2>
<div class="mec-container"><div class="sync-content w-clearfix extra">
<?php if (isset($this->response['success']) and !$this->response['success']): ?><div class="mec-error"><?php echo esc_html($this->response['error']); ?></div><?php endif; ?>
<form id="mec_ix_sync_form" action="<?php echo esc_url($this->main->get_full_url()); ?>" method="POST">
<div class="mec-form-row"><input type="hidden" name="ix[sync_g_import]" value="0"><label><input type="checkbox" name="ix[sync_g_import]" value="1" <?php checked(($ix['sync_g_import'] ?? 0), 1); ?>> <?php esc_html_e('Google Calendar import synchronization', 'renginiu-kalendorius'); ?></label></div>
<div class="mec-form-row"><input type="hidden" name="ix[sync_g_export]" value="0"><label><input type="checkbox" name="ix[sync_g_export]" value="1" <?php checked(($ix['sync_g_export'] ?? 0), 1); ?>> <?php esc_html_e('Google Calendar export synchronization', 'renginiu-kalendorius'); ?></label></div>
<div class="mec-options-fields"><input type="hidden" name="mec-ix-action" value="save-sync-options"><?php wp_nonce_field('mec_ix_sync', 'mec_ix_nonce', false); ?><button class="button button-primary mec-button-primary" type="submit"><?php esc_html_e('Save', 'renginiu-kalendorius'); ?></button></div>
</form></div></div></div>

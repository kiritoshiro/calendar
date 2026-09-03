<?php
/** no direct access **/
defined('MECEXEC') or die();

/** @var WP_Post $post */
/** @var MEC_feature_mec $this */

$icons = get_post_meta($post->ID, 'mec_icons', true);
if(!is_array($icons)) $icons = [];
?>
<div class="mec-calendar-metabox">
    <div class="mec-form-row">
        <p class="description"><?php esc_html_e('You can change the default icons using following options.', 'modern-events-calendar-lite'); ?></p>
    </div>
    <?php $this->main->icons()->form(
        'shortcode',
        'mec',
        $icons
    ); ?>
</div>
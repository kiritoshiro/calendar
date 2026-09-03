<?php

/**
 *   Plugin Name: Modern Events Calendar Lite
 *   Plugin URI: https://github.com/kiritoshiro/calendar
 *   Description: The adventistai.lt maintained build of Modern Events Calendar Lite.
 *   Author: adventistai.lt
 *   Author URI: https://adventistai.lt
 *   Developer: adventistai.lt
 *   Developer URI: https://adventistai.lt
 *   Version: 7.35.1.2
 *   Update URI: https://github.com/kiritoshiro/calendar
 *   Text Domain: modern-events-calendar-lite
 *   Domain Path: /languages
 **/

if (!defined('MECEXEC')) {
    /** MEC Execution **/
    define('MECEXEC', 1);

    /** Directory Separator **/
    if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

    /** MEC Absolute Path **/
    define('MEC_ABSPATH', dirname(__FILE__) . DS);

    /** Plugin Directory Name **/
    define('MEC_DIRNAME', basename(MEC_ABSPATH));

    /** Plugin File Name **/
    define('MEC_FILENAME', basename(__FILE__));

    /** Plugin Base Name **/
    define('MEC_BASENAME', plugin_basename(__FILE__)); // modern-events-calendar/mec.php

    /** Plugin Version **/
    define('MEC_VERSION', '7.35.1.2');

    /** GitHub updater for the adventistai.lt maintained fork **/
    require_once MEC_ABSPATH . 'app/libraries/github-updater.php';
    MEC_Adventistai_GitHub_Updater::instance()->register();

    /** Load custom calendar styling after MEC's main frontend stylesheet. **/
    add_action('wp_enqueue_scripts', function ()
    {
        wp_enqueue_style(
            'mec-adventistai-calendar',
            plugin_dir_url(__FILE__) . 'assets/css/adventistai-calendar.css',
            array(),
            MEC_VERSION
        );
    }, 100);

    add_action('admin_enqueue_scripts', function ()
    {
        wp_enqueue_style(
            'mec-adventistai-admin',
            plugin_dir_url(__FILE__) . 'assets/css/adventistai-admin.css',
            array(),
            MEC_VERSION
        );
    }, 100);

    /** Include Webnus MEC class if not included before **/
    if (!class_exists('MEC')) require_once MEC_ABSPATH . 'mec-init.php';

    add_action('before_woocommerce_init', function ()
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil'))
        {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    });

    /** Initialize Webnus MEC Plugin **/
    $MEC = MEC::instance();
    $MEC->init();

    require_once MEC_ABSPATH . 'app/core/mec.php';
    do_action('mec_init');
}

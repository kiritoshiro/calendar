<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC Divi addon class
 * @author Webnus <info@webnus.net>
 */
class MEC_addon_divi extends MEC_base
{
    /**
     * @var MEC_factory
     */
    public $factory;

    /**
     * @var MEC_main
     */
    public $main;

    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct()
    {
        // MEC Factory class
        $this->factory = $this->getFactory();

        // MEC Main class
        $this->main = $this->getMain();
    }

    /**
     * Initialize the Elementor addon
     * @author Webnus <info@webnus.net>
     */
    public function init()
    {
        if (did_action('after_setup_theme'))
        {
            return $this->register_divi_hooks();
        }

        add_action('after_setup_theme', [$this, 'register_divi_hooks']);

        return true;
    }

    /**
     * Register Divi-specific hooks after themes are initialized.
     *
     * @return bool
     */
    public function register_divi_hooks()
    {
        // Divi is not installed
        $theme = wp_get_theme(); // gets the current theme

        if ('Divi' != $theme->get_template()) return false;

        if (!has_action('divi_extensions_init', [$this, 'mecdivi_initialize_extension']))
        {
            add_action('divi_extensions_init', [$this, 'mecdivi_initialize_extension']);
        }

        if (!has_filter('et_builder_load_actions', [$this, 'add_ajax_actions']))
        {
            add_filter('et_builder_load_actions', [$this, 'add_ajax_actions']);
        }

        return true;
    }

    /**
     * Creates the extension's main class instance.
     *
     * @since 1.0.0
     */
    public function mecdivi_initialize_extension()
    {
        require_once plugin_dir_path(__FILE__) . 'divi/includes/Divi.php';
        require_once plugin_dir_path(__FILE__) . 'divi/includes/MECShortcodesForDivi.php';
    }

    public function add_ajax_actions($actions)
    {
        $actions[] = 'mec_load_single_page';
        return $actions;
    }
}

<?php
/**
 * Signatures for optional third-party plugins that Modern Events Calendar
 * integrates with but never requires. PHPStan only scans this file for symbol
 * names, so unknown-symbol reports stay meaningful for genuine typos.
 *
 * This file is never loaded at runtime and is not part of the plugin package.
 */

namespace Elementor {
    class Plugin
    {
        /** @var self */
        public static $instance;
    }
}

namespace {
    // BuddyPress
    function bp_activity_set_action($component_id, $type, $description, $format_callback = false, $label = false, $context = array()) {}
    function bp_core_get_userlink($user_id, $no_anchor = false, $just_link = false) {}
    function bp_core_new_nav_item($args = array()) {}
    function bp_loggedin_user_domain() {}
    function bp_core_load_template($templates) {}

    // Polylang
    function pll_get_post_translations($post_id) {}
    function pll_switch_language($locale) {}
}

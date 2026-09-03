<?php

/**
 * WordPress updater for the adventistai.lt MEC fork.
 *
 * The repository is private and the plugin lives in a subdirectory of the
 * repository, so this class handles both authenticated GitHub requests and
 * selecting the nested plugin directory after WordPress unpacks the archive.
 */

defined('MECEXEC') or die();

final class MEC_Adventistai_GitHub_Updater
{
    const REPOSITORY = 'kiritoshiro/calendar';
    const REPOSITORY_URL = 'https://github.com/kiritoshiro/calendar';
    const PLUGIN_DIRECTORY = 'modern-events-calendar-lite';
    const REMOTE_PLUGIN_FILE = 'modern-events-calendar-lite/modern-events-calendar-lite.php';
    const CACHE_KEY = 'mec_adventistai_github_update';

    /** @var self|null */
    private static $instance = null;

    /** @var bool */
    private $registered = false;

    /** @return self */
    public static function instance()
    {
        if(self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    /** Register all updater hooks once. */
    public function register()
    {
        if($this->registered) return;
        $this->registered = true;

        add_filter('pre_set_site_transient_update_plugins', array($this, 'inject_update'));
        add_filter('plugins_api', array($this, 'plugin_information'), 20, 3);
        add_filter('http_request_args', array($this, 'authorize_github_request'), 20, 2);
        add_filter('upgrader_source_selection', array($this, 'select_plugin_source'), 20, 4);
        add_action('delete_site_transient_update_plugins', array($this, 'clear_cache'));
        add_action('admin_notices', array($this, 'token_notice'));
    }

    /**
     * Add this fork to the standard WordPress Plugins and Updates screens.
     *
     * @param object $transient
     * @return object
     */
    public function inject_update($transient)
    {
        if(!is_object($transient)) $transient = new stdClass();
        if(empty($transient->checked) || !isset($transient->checked[MEC_BASENAME])) return $transient;

        $remote = $this->get_remote_version();
        if(is_wp_error($remote)) return $transient;

        $item = (object) array(
            'id' => self::REPOSITORY_URL,
            'slug' => self::PLUGIN_DIRECTORY,
            'plugin' => MEC_BASENAME,
            'new_version' => $remote['version'],
            'url' => self::REPOSITORY_URL,
            'package' => $remote['package'],
            'icons' => array(),
            'banners' => array(),
            'tested' => '7.0.2',
            'requires_php' => '7.4',
        );

        if(version_compare($remote['version'], MEC_VERSION, '>'))
        {
            if(!isset($transient->response) || !is_array($transient->response)) $transient->response = array();
            $transient->response[MEC_BASENAME] = $item;
        }
        else
        {
            if(!isset($transient->no_update) || !is_array($transient->no_update)) $transient->no_update = array();
            $transient->no_update[MEC_BASENAME] = $item;
        }

        return $transient;
    }

    /**
     * Supply the modal shown by “View details” on the Plugins screen.
     *
     * @param mixed  $result
     * @param string $action
     * @param object $args
     * @return mixed
     */
    public function plugin_information($result, $action, $args)
    {
        if($action !== 'plugin_information' || !is_object($args) || empty($args->slug) || $args->slug !== self::PLUGIN_DIRECTORY) return $result;

        $remote = $this->get_remote_version();
        if(is_wp_error($remote)) return $result;

        return (object) array(
            'name' => 'Modern Events Calendar Lite',
            'slug' => self::PLUGIN_DIRECTORY,
            'version' => $remote['version'],
            'author' => '<a href="https://adventistai.lt">adventistai.lt</a>',
            'homepage' => self::REPOSITORY_URL,
            'requires' => '5.8',
            'tested' => '7.0.2',
            'requires_php' => '7.4',
            'last_updated' => $remote['updated_at'],
            'download_link' => $remote['package'],
            'sections' => array(
                'description' => esc_html__('The adventistai.lt maintained build of Modern Events Calendar Lite.', 'modern-events-calendar-lite'),
                'changelog' => sprintf(
                    '<p>%s <a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
                    esc_html__('Release notes and source history are available in GitHub.', 'modern-events-calendar-lite'),
                    esc_url(self::REPOSITORY_URL . '/commits/' . rawurlencode($remote['branch'])),
                    esc_html__('Open repository history', 'modern-events-calendar-lite')
                ),
            ),
        );
    }

    /**
     * Attach the token only to API calls for this exact repository.
     * Tokens are never put in URLs, transients, notices, or logs.
     *
     * @param array  $args
     * @param string $url
     * @return array
     */
    public function authorize_github_request($args, $url)
    {
        $repository_api = 'https://api.github.com/repos/' . self::REPOSITORY;
        if($url !== $repository_api && strpos($url, $repository_api . '/') !== 0) return $args;

        $token = $this->get_token();
        if($token === '') return $args;

        if(!isset($args['headers']) || !is_array($args['headers'])) $args['headers'] = array();
        $args['headers']['Authorization'] = 'Bearer ' . $token;
        $args['headers']['Accept'] = 'application/vnd.github+json';
        $args['headers']['X-GitHub-Api-Version'] = '2022-11-28';
        $args['headers']['User-Agent'] = 'adventistai.lt-calendar-updater';

        return $args;
    }

    /**
     * GitHub archives contain repository-root/plugin-directory/. Tell the
     * upgrader to install only the nested plugin directory.
     *
     * @param string|WP_Error $source
     * @param string          $remote_source
     * @param WP_Upgrader     $upgrader
     * @param array           $hook_extra
     * @return string|WP_Error
     */
    public function select_plugin_source($source, $remote_source, $upgrader, $hook_extra)
    {
        if(is_wp_error($source)) return $source;
        if(empty($hook_extra['plugin']) || $hook_extra['plugin'] !== MEC_BASENAME) return $source;

        $source = trailingslashit($source);
        if(basename(untrailingslashit($source)) === self::PLUGIN_DIRECTORY) return $source;

        $nested_source = $source . self::PLUGIN_DIRECTORY;
        if(is_dir($nested_source)) return trailingslashit($nested_source);

        return new WP_Error(
            'mec_adventistai_invalid_update_package',
            esc_html__('The GitHub update archive does not contain the expected plugin directory.', 'modern-events-calendar-lite')
        );
    }

    /** Clear our metadata whenever WordPress performs a fresh plugin check. */
    public function clear_cache()
    {
        delete_site_transient(self::CACHE_KEY);
    }

    /** Explain the only required setup without exposing token values. */
    public function token_notice()
    {
        if($this->get_token() !== '' || !current_user_can('update_plugins') || !function_exists('get_current_screen')) return;

        $screen = get_current_screen();
        if(!$screen || !in_array($screen->id, array('plugins', 'update-core'), true)) return;

        echo '<div class="notice notice-warning"><p>';
        echo '<strong>' . esc_html__('Modern Events Calendar (adventistai.lt):', 'modern-events-calendar-lite') . '</strong> ';
        echo esc_html__('Private GitHub update checks are disabled until ADVENTISTAI_CALENDAR_GITHUB_TOKEN (or ADVENTISTAI_GITHUB_TOKEN) is defined in wp-config.php.', 'modern-events-calendar-lite');
        echo '</p></div>';
    }

    /** @return string */
    private function get_token()
    {
        $token = '';

        if(defined('ADVENTISTAI_CALENDAR_GITHUB_TOKEN')) $token = (string) ADVENTISTAI_CALENDAR_GITHUB_TOKEN;
        elseif(defined('ADVENTISTAI_GITHUB_TOKEN')) $token = (string) ADVENTISTAI_GITHUB_TOKEN;

        return trim((string) apply_filters('mec_adventistai_github_token', $token));
    }

    /**
     * Read the plugin version directly from the repository default branch.
     * This intentionally follows the default branch instead of Webnus or the
     * WordPress.org channel. Future updates only need a version bump and push.
     *
     * @return array|WP_Error
     */
    private function get_remote_version()
    {
        $cached = get_site_transient(self::CACHE_KEY);
        if(is_array($cached) && !empty($cached['version'])) return $cached;

        $token = $this->get_token();
        if($token === '') return new WP_Error('mec_adventistai_missing_github_token');

        $repository = $this->request_json('https://api.github.com/repos/' . self::REPOSITORY);
        if(is_wp_error($repository)) return $repository;

        $branch = isset($repository['default_branch']) ? sanitize_text_field($repository['default_branch']) : '';
        if($branch === '') return new WP_Error('mec_adventistai_missing_default_branch');

        $contents_url = 'https://api.github.com/repos/' . self::REPOSITORY . '/contents/' . self::REMOTE_PLUGIN_FILE . '?ref=' . rawurlencode($branch);
        $file = $this->request_json($contents_url);
        if(is_wp_error($file)) return $file;

        if(empty($file['content']) || (isset($file['encoding']) && $file['encoding'] !== 'base64')) return new WP_Error('mec_adventistai_invalid_plugin_file');

        $plugin_source = base64_decode(str_replace(array("\r", "\n"), '', $file['content']), true);
        if(!is_string($plugin_source) || !preg_match('/^[ \t\/*#@]*Version:\s*(.+)$/mi', $plugin_source, $matches)) return new WP_Error('mec_adventistai_missing_remote_version');

        $version = trim(preg_replace('/\s*(?:\*\/)?\s*$/', '', $matches[1]));
        if(!preg_match('/^\d+(?:\.\d+){1,3}(?:[-+][0-9A-Za-z.-]+)?$/', $version)) return new WP_Error('mec_adventistai_invalid_remote_version');

        $remote = array(
            'version' => $version,
            'branch' => $branch,
            'updated_at' => isset($repository['updated_at']) ? sanitize_text_field($repository['updated_at']) : '',
            'package' => 'https://api.github.com/repos/' . self::REPOSITORY . '/zipball/' . rawurlencode($branch),
        );

        set_site_transient(self::CACHE_KEY, $remote, 6 * HOUR_IN_SECONDS);
        return $remote;
    }

    /** @return array|WP_Error */
    private function request_json($url)
    {
        $args = $this->authorize_github_request(array(
            'timeout' => 15,
            'redirection' => 3,
        ), $url);

        $response = wp_remote_get($url, $args);
        if(is_wp_error($response)) return $response;

        $status = (int) wp_remote_retrieve_response_code($response);
        if($status !== 200) return new WP_Error('mec_adventistai_github_http_error', sprintf('GitHub API returned HTTP %d.', $status));

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if(!is_array($body)) return new WP_Error('mec_adventistai_github_json_error');

        return $body;
    }
}

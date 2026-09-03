<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC RESTful class.
 * @author Webnus <info@webnus.net>
 */
class MEC_restful extends MEC_base
{
    /**
     * @var MEC_main
     */
    private $main;

    /**
     * @var MEC_db
     */
    private $db;

    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct()
    {
        // Main
        $this->main = $this->getMain();

        // Database
        $this->db = $this->getDB();
    }

    public function get_endpoint_url()
    {
        return get_rest_url(null, $this->get_namespace());
    }

    public function get_namespace()
    {
        return 'mec/v1.0';
    }

    public function get_api_version()
    {
        return '1';
    }

    public function permission(WP_REST_Request $request)
    {
        // Validate API Token
        if (!$this->is_api_token_valid($request, $request->get_header('mec-token')))
        {
            return new WP_Error('invalid_api_token', esc_html__('Invalid API Token!', 'modern-events-calendar-lite'), ['status' => 401]);
        }

        // Validate User Token
        if (!$this->is_user_token_valid($request, $request->get_header('mec-user')))
        {
            return new WP_Error('invalid_user_token', esc_html__('Invalid User Token!', 'modern-events-calendar-lite'), ['status' => 401]);
        }

        return true;
    }

    public function guest(WP_REST_Request $request)
    {
        // Validate API Token
        if (!$this->is_api_token_valid($request, $request->get_header('mec-token'))) return new WP_Error('invalid_api_token', esc_html__('Invalid API Token!', 'modern-events-calendar-lite'));

        // Set Current User if Token Provided
        $this->is_user_token_valid($request, $request->get_header('mec-user'));

        return true;
    }

    public function response(array $response): WP_REST_Response
    {
        $data = $response['data'] ?? [];
        $status = $response['status'] ?? 200;

        $wp = new WP_REST_Response($data);
        $wp->set_status($status);

        return $wp;
    }

    public function is_api_token_valid(WP_REST_Request $request, $token = '')
    {
        $token = $this->normalize_token($token);

        // Check Token
        if ($token)
        {
            $settings = $this->main->get_settings();
            $api_keys = (isset($settings['api_keys']) && is_array($settings['api_keys'])) ? $settings['api_keys'] : [];

            $tokens = [];
            foreach ($api_keys as $k => $t)
            {
                if (!is_numeric($k)) continue;

                $key = $this->normalize_token($t['key'] ?? '');
                if ($key === '') continue;

                $tokens[] = $key;
            }

            if (in_array($token, $tokens, true)) return true;
        }

        return false;
    }

    public function is_user_token_valid(WP_REST_Request $request, $token = '')
    {
        $token = $this->normalize_token($token);

        // Check User
        if ($token)
        {
            $users = get_users([
                'meta_key' => 'mec_token',
                'meta_value' => $token,
                'number' => 1,
                'fields' => 'ids',
            ]);

            $user_id = isset($users[0]) ? (int) $users[0] : 0;
            if (!$user_id) return false;

            // Set Current User
            wp_set_current_user($user_id);
            return true;
        }

        return false;
    }

    public function get_user_token($user_id): string
    {
        $token = $this->main->str_random(40);
        update_user_meta($user_id, 'mec_token', $token);

        return $token;
    }

    private function normalize_token($token): string
    {
        if (!is_scalar($token)) return '';

        return trim((string) $token);
    }
}

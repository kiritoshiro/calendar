<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC Cart class.
 * @author Webnus <info@webnus.net>
 */
class MEC_cart extends MEC_base
{
    /**
     * @var MEC_main
     */
    private $main;
    private $settings;
    private $ticket_names = [];
    private $last_event_id = 0;

    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct()
    {
        // Main
        $this->main = $this->getMain();

        // MEC Settings
        $this->settings = $this->main->get_settings();
    }

    public function add($transaction_id)
    {
        $cart_id = $this->get_cart_id();

        $cart = $this->get_cart($cart_id);
        $cart[] = $transaction_id;

        $this->update_cart($cart_id, $cart);

        // Add to Ticket Names
        $this->ticket_names = array_merge($this->ticket_names, $this->get_ticket_names($transaction_id));

        // Store the event language for localization purposes
        $book = $this->getBook();
        if ($book)
        {
            $TO = $book->get_TO($transaction_id);
            if ($TO and method_exists($TO, 'get_event_id'))
            {
                $event_id = (int) $TO->get_event_id();
                if ($event_id) $this->last_event_id = $event_id;
            }
        }

        return $this;
    }

    public function remove($transaction_id)
    {
        $cart_id = $this->get_cart_id();

        $cart = $this->get_cart($cart_id);
        if (!in_array($transaction_id, $cart)) return false;

        $key = array_search($transaction_id, $cart);
        if ($key !== false) unset($cart[$key]);

        $this->update_cart($cart_id, $cart);

        return true;
    }

    public function next()
    {
        $ticket_names = implode(', ', $this->ticket_names);
        if (trim($ticket_names) === '') $ticket_names = esc_html__('Ticket', 'modern-events-calendar-lite');

        $cart_id = $this->get_cart_id();
        $cart = $this->get_cart($cart_id);
        $event_id = $this->last_event_id ?: $this->get_first_event_id($cart);

        // Checkout URL
        if (isset($this->settings['cart_after_add']) and $this->settings['cart_after_add'] == 'checkout') return ['type' => 'url', 'url' => $this->get_checkout_url($event_id)];
        // Optional Checkout URL
        if (isset($this->settings['cart_after_add']) and $this->settings['cart_after_add'] == 'optional_cart') return ['type' => 'message', 'message' => '<div class="woocommerce-notices-wrapper"><div class="woocommerce-message" role="alert"><a href="' . esc_url($this->get_cart_url($event_id)) . '" tabindex="1" class="button wc-forward" target="_parent">' . esc_html__('View cart', 'modern-events-calendar-lite') . '</a> ' . esc_html(sprintf(_n('“%s” has been added to your cart.', '“%s” have been added to your cart.', count($this->ticket_names), 'modern-events-calendar-lite'), $ticket_names)) . '</div></div>'];
        // Optional Cart URL
        if (isset($this->settings['cart_after_add']) and $this->settings['cart_after_add'] == 'optional_chckout') return ['type' => 'message', 'message' => '<div class="woocommerce-notices-wrapper"><div class="woocommerce-message" role="alert"><a href="' . esc_url($this->get_checkout_url($event_id)) . '" tabindex="1" class="button wc-forward" target="_parent">' . esc_html__('Checkout', 'modern-events-calendar-lite') . '</a> ' . esc_html(sprintf(_n('“%s” has been added to your cart.', '“%s” have been added to your cart.', count($this->ticket_names), 'modern-events-calendar-lite'), $ticket_names)) . '</div></div>'];
        // Cart URL
        else return ['type' => 'url', 'url' => $this->get_cart_url($event_id)];
    }

    public function get_cart($cart_id)
    {
        $cart = get_option('mec_cart_' . $cart_id, null);
        if (is_null($cart))
        {
            $cart = [];
            update_option('mec_cart_' . $cart_id, $cart, 'no');
        }

        if (!is_array($cart)) $cart = [];
        return $cart;
    }

    public function update_cart($cart_id, $value)
    {
        return update_option('mec_cart_' . $cart_id, $value, 'no');
    }

    public function archive_cart($cart_id)
    {
        $value = $this->get_cart($cart_id);
        return update_option('mec_cart_' . $cart_id . '_archived', $value, 'no');
    }

    public function get_archived_cart($cart_id)
    {
        $cart = get_option('mec_cart_' . $cart_id . '_archived', null);

        if (!is_array($cart)) $cart = [];
        return $cart;
    }

    public function get_cart_id()
    {
        $cart_id = (isset($_COOKIE['mec_cart']) && trim($_COOKIE['mec_cart'])) ? sanitize_text_field(wp_unslash($_COOKIE['mec_cart'])) : null;
        if ($cart_id && !$this->is_valid_cart_id($cart_id)) $cart_id = null;

        if (!$cart_id && isset($_REQUEST['cart_id']) && trim((string) $_REQUEST['cart_id']))
        {
            $request_cart_id = sanitize_text_field(wp_unslash($_REQUEST['cart_id']));
            $request_cart_key = isset($_REQUEST['mec_cart_key']) ? sanitize_text_field(wp_unslash($_REQUEST['mec_cart_key'])) : '';

            if ($this->is_valid_cart_id($request_cart_id) && hash_equals($this->get_cart_key($request_cart_id), $request_cart_key))
            {
                $cart_id = $request_cart_id;
                $this->set_cart_cookie($cart_id);
            }
        }

        if (!$cart_id)
        {
            $cart_id = (string) mt_rand(100000000, 999999999);
            $this->set_cart_cookie($cart_id);
        }

        return $cart_id;
    }

    public function get_fresh_cart_id()
    {
        $cart_id = mt_rand(100000000, 999999999);
        $this->set_cart_cookie((string) $cart_id);

        return $cart_id;
    }

    public function get_checkout_url($event_id = null)
    {
        $page_id = (isset($this->settings['checkout_page']) and trim($this->settings['checkout_page'])) ? $this->settings['checkout_page'] : null;
        $localized_page_id = $this->get_localized_page_id($page_id, $event_id);

        return ($localized_page_id ? get_permalink($localized_page_id) : ($page_id ? get_permalink($page_id) : home_url()));
    }

    public function get_cart_url($event_id = null)
    {
        $page_id = (isset($this->settings['cart_page']) and trim($this->settings['cart_page'])) ? $this->settings['cart_page'] : null;
        $localized_page_id = $this->get_localized_page_id($page_id, $event_id);

        if ($localized_page_id) $page_id = $localized_page_id;

        $language_codes_array = null;
        $language_current_code = null;
        if (class_exists('TRP_Translate_Press'))
        {
            $trp = TRP_Translate_Press::get_trp_instance();
            $trp_settings = $trp->get_component('settings');
            $language_codes_array = $trp_settings->get_settings()['publish-languages'];
            $language_current_code = $_REQUEST['trp-form-language'] ?? '';
        }

        $url = ($page_id ? get_permalink($page_id) : home_url());

        if (!empty($language_codes_array) and !empty($language_current_code))
        {
            $url = home_url() . '/' . $language_current_code . str_replace(home_url(), '', $url);
        }

        // Ensure first-time users without cookie can carry the cart id to the cart page
        $has_cookie = (isset($_COOKIE['mec_cart']) && trim($_COOKIE['mec_cart']));
        if (!$has_cookie)
        {
            $cart_id = $this->get_cart_id();
            $url = $this->main->add_qs_var('cart_id', $cart_id, $url);
            $url = $this->main->add_qs_var('mec_cart_key', $this->get_cart_key($cart_id), $url);
        }

        return $url;
    }

    protected function get_cart_key($cart_id)
    {
        return hash_hmac('sha256', (string) $cart_id, wp_salt('auth'));
    }

    protected function is_valid_cart_id($cart_id)
    {
        return (bool) preg_match('/^[A-Za-z0-9_-]{6,64}$/', (string) $cart_id);
    }

    protected function set_cart_cookie($cart_id)
    {
        if (!headers_sent()) setcookie('mec_cart', $cart_id, (time() + (30 * 86400)), '/');
        $_COOKIE['mec_cart'] = $cart_id;
    }

    private function get_localized_page_id($page_id, $event_id = null)
    {
        if (!$page_id) return 0;

        $language = $this->determine_language($event_id);
        $default_language = '';

        // WPML
        if (class_exists('SitePress'))
        {
            if (!$language) $language = apply_filters('wpml_current_language', null);
            $default_language = apply_filters('wpml_default_language', null);

            if ($language)
            {
                $translated_id = apply_filters('wpml_object_id', $page_id, 'page', false, $language);
                if ($translated_id) return (int) $translated_id;
            }

            if ($default_language)
            {
                $fallback_id = apply_filters('wpml_object_id', $page_id, 'page', false, $default_language);
                if ($fallback_id) return (int) $fallback_id;
            }

            return (int) $page_id;
        }

        // Polylang
        if (function_exists('pll_get_post'))
        {
            if (!$language) $language = function_exists('pll_current_language') ? pll_current_language() : '';
            $default_language = function_exists('pll_default_language') ? pll_default_language() : '';

            if ($language)
            {
                $translated_id = pll_get_post($page_id, $language);
                if ($translated_id) return (int) $translated_id;
            }

            if ($default_language)
            {
                $fallback_id = pll_get_post($page_id, $default_language);
                if ($fallback_id) return (int) $fallback_id;
            }

            return (int) $page_id;
        }

        return (int) $page_id;
    }

    private function determine_language($event_id = null)
    {
        if ($event_id)
        {
            if (class_exists('SitePress'))
            {
                $details = apply_filters('wpml_post_language_details', null, $event_id);
                if (is_array($details) and !empty($details['language_code'])) return $details['language_code'];
            }

            if (function_exists('pll_get_post_language'))
            {
                $language = pll_get_post_language($event_id);
                if ($language) return $language;
            }
        }

        if (class_exists('SitePress'))
        {
            $current_language = apply_filters('wpml_current_language', null);
            if ($current_language) return $current_language;
        }

        if (function_exists('pll_current_language'))
        {
            $current_language = pll_current_language();
            if ($current_language) return $current_language;
        }

        return '';
    }

    public function get_ticket_names($transaction_id)
    {
        $book = $this->getBook();
        $transaction = $book->get_transaction($transaction_id);

        $event_id = ((isset($transaction['event_id']) and $transaction['event_id']) ? $transaction['event_id'] : 0);
        $tickets = ((isset($transaction['tickets']) and is_array($transaction['tickets'])) ? $transaction['tickets'] : []);

        $event_tickets = get_post_meta($event_id, 'mec_tickets', true);
        if (!is_array($event_tickets)) $event_tickets = [];

        $names = [];
        foreach ($tickets as $key => $ticket)
        {
            if (!is_numeric($key)) continue;

            $ticket_id = (isset($ticket['id']) and $ticket['id']) ? $ticket['id'] : 0;
            if (!$ticket_id) continue;

            $ticket = $event_tickets[$ticket_id] ?? [];
            $ticket_name = ($ticket['name'] ?? '');

            if (trim($ticket_name)) $names[] = $ticket_name;
        }

        return array_unique($names);
    }

    public function get_payable($cart = null)
    {
        if (is_null($cart))
        {
            $cart_id = $this->get_cart_id();
            $cart = $this->get_cart($cart_id);
        }

        // Booking Library
        $book = $this->getBook();

        $payable = 0;
        foreach ($cart as $transaction_id)
        {
            $TO = $book->get_TO($transaction_id);

            $payable += $TO->get_payable();
        }

        return $payable;
    }

    public function is_free($cart = null)
    {
        $payable = $this->get_payable($cart);
        return !($payable > 0);
    }

    public function clear($cart_id)
    {
        // Save it for future usage
        $this->archive_cart($cart_id);

        // Make it empty
        $this->update_cart($cart_id, []);

        // New Cart ID
        $this->get_fresh_cart_id();
    }

    public function get_first_event_id($cart = null)
    {
        if (is_null($cart))
        {
            $cart_id = $this->get_cart_id();
            $cart = $this->get_cart($cart_id);
        }

        // Booking Library
        $book = $this->getBook();

        $event_id = null;
        foreach ($cart as $transaction_id)
        {
            $TO = $book->get_TO($transaction_id);

            $event_id = $TO->get_event_id();
            break;
        }

        return $event_id;
    }

    public function get_main_attendee_email($cart = null)
    {
        if (is_null($cart))
        {
            $cart_id = $this->get_cart_id();
            $cart = $this->get_cart($cart_id);
        }

        // Booking Library
        $book = $this->getBook();

        $main_attendee_email = null;
        foreach ($cart as $transaction_id)
        {
            $TO = $book->get_TO($transaction_id);

            $main_attendee_email = $TO->get_main_attendee_email();
            break;
        }

        return $main_attendee_email;
    }

    public function get_invoice_link($cart_id)
    {
        if (isset($this->settings['mec_cart_invoice']) and !$this->settings['mec_cart_invoice']) return '';

        $url = $this->main->URL();
        $url = $this->main->add_qs_var('method', 'mec-cart-invoice', $url);

        // Invoice Key
        $url = $this->main->add_qs_var('mec-key', $cart_id, $url);

        return apply_filters('mec_cart_invoice_url', $url, $cart_id);
    }

    public function is_done($cart_id)
    {
        return (bool) $this->get_archived_cart($cart_id);
    }
}

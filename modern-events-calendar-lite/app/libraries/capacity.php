<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Pre-payment capacity holds shared by MEC gateways and WooCommerce integration.
 *
 * Serializes concurrent seat reservations with MySQL GET_LOCK and a holds table so
 * only one shopper can claim the last seat before payment is captured.
 *
 * @author Webnus <info@webnus.net>
 */
class MEC_capacity extends MEC_base
{
    const DB_VERSION = '1.0.0';
    const HOLD_TTL = 300; // 5 minutes 

    /** @var string|null */
    private static $exclude_transaction = null;

    /** @var string|null */
    private static $exclude_session = null;

    /** @var bool */
    private static $bypass_filter = false;

    /** @var bool */
    private static $hooks_ready = false;

    public function __construct()
    {
        $this->maybe_create_table();
        $this->register_hooks();
    }

    /**
     * @return void
     */
    public function register_hooks()
    {
        if (self::$hooks_ready) {
            return;
        }

        self::$hooks_ready = true;

        // IMPORTANT: Do NOT filter mec_get_tickets_availability globally.
        // Subtracting unpaid holds from public availability can mark the event
        // sold-out for everyone (including the holder) and breaks the booking UI.
        // Capacity is enforced in place_hold_for_transaction() + assert_can_complete().

        add_action('mec_capacity_cleanup_holds', [$this, 'cleanup_expired']);

        if (!wp_next_scheduled('mec_capacity_cleanup_holds')) {
            wp_schedule_event(time(), 'hourly', 'mec_capacity_cleanup_holds');
        }

        // One-shot cleanup of any leftover holds after deploy / race tests.
        $this->cleanup_expired();

        // Wipe stale active holds once — race tests left unpaid holds that
        // blocked the event at capacity 1 even before anyone paid.
        if (get_option('mec_capacity_holds_purged_v1', '') !== 'yes') {
            global $wpdb;
            $wpdb->query('DELETE FROM ' . $this->table_name());
            update_option('mec_capacity_holds_purged_v1', 'yes', false);
        }
    }

    /**
     * @return string
     */
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'mec_capacity_holds';
    }

    /**
     * @return void
     */
    public function maybe_create_table()
    {
        $installed = get_option('mec_capacity_holds_db_version', '');
        if ($installed === self::DB_VERSION) {
            return;
        }

        global $wpdb;
        $table = $this->table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL DEFAULT 0,
            event_timestamp bigint(20) NOT NULL DEFAULT 0,
            ticket_id varchar(50) NOT NULL DEFAULT '0',
            qty smallint(5) unsigned NOT NULL DEFAULT 1,
            hold_type varchar(20) NOT NULL DEFAULT 'gateway',
            session_key varchar(64) NOT NULL DEFAULT '',
            order_id bigint(20) unsigned NOT NULL DEFAULT 0,
            transaction_id varchar(191) NOT NULL DEFAULT '',
            product_id bigint(20) unsigned NOT NULL DEFAULT 0,
            expires_at datetime NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event_occurrence (event_id, event_timestamp),
            KEY session_key (session_key),
            KEY order_id (order_id),
            KEY transaction_id (transaction_id),
            KEY product_id (product_id),
            KEY expires_at (expires_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('mec_capacity_holds_db_version', self::DB_VERSION);
    }

    /**
     * Must match mec-woocommerce PaymentHelper::normalize_timestamp().
     *
     * @param mixed $timestamp
     * @return int
     */
    public function normalize_timestamp($timestamp)
    {
        if (is_numeric($timestamp)) {
            return absint($timestamp);
        }

        $str = (string) $timestamp;

        if (strpos($str, ':') !== false) {
            $parts = explode(':', $str);
            if (is_numeric($parts[0])) {
                return absint($parts[0]);
            }
        }

        $parsed = strtotime($str);

        return $parsed ? absint($parsed) : 0;
    }

    /**
     * Shared with mec-woocommerce so both serialize the same seat pool.
     *
     * @param int   $event_id
     * @param mixed $event_timestamp
     * @return string
     */
    public function lock_name($event_id, $event_timestamp)
    {
        $event_timestamp = $this->normalize_timestamp($event_timestamp);

        return 'mec_cap_' . md5(absint($event_id) . '_' . $event_timestamp);
    }

    /**
     * @param int   $event_id
     * @param mixed $event_timestamp
     * @param int   $timeout
     * @return bool
     */
    public function acquire_lock($event_id, $event_timestamp, $timeout = 15)
    {
        global $wpdb;

        $got = $wpdb->get_var($wpdb->prepare(
            'SELECT GET_LOCK(%s, %d)',
            $this->lock_name($event_id, $event_timestamp),
            absint($timeout)
        ));

        return ('1' === (string) $got);
    }

    /**
     * @param int   $event_id
     * @param mixed $event_timestamp
     * @return void
     */
    public function release_lock($event_id, $event_timestamp)
    {
        global $wpdb;

        $wpdb->query($wpdb->prepare(
            'SELECT RELEASE_LOCK(%s)',
            $this->lock_name($event_id, $event_timestamp)
        ));
    }

    /**
     * @param array $transaction
     * @return array{total:int,tickets:array<string,int>}
     */
    public function count_transaction_tickets($transaction)
    {
        $result = [
            'total'   => 0,
            'tickets' => [],
        ];

        if (empty($transaction['tickets']) || !is_array($transaction['tickets'])) {
            return $result;
        }

        foreach ($transaction['tickets'] as $key => $ticket_data) {
            if (!is_numeric($key) || !is_array($ticket_data)) {
                continue;
            }

            $ticket_id = isset($ticket_data['id']) ? (string) $ticket_data['id'] : '';
            if ($ticket_id === '') {
                continue;
            }

            $count = isset($ticket_data['count']) ? absint($ticket_data['count']) : 1;
            if ($count < 1) {
                $count = 1;
            }

            if (!isset($result['tickets'][$ticket_id])) {
                $result['tickets'][$ticket_id] = 0;
            }

            $result['tickets'][$ticket_id] += $count;
            $result['total'] += $count;
        }

        return $result;
    }

    /**
     * @param string|null $exclude_transaction
     * @param string|null $exclude_session
     * @return void
     */
    public function begin_exclude($exclude_transaction = null, $exclude_session = null)
    {
        self::$exclude_transaction = $exclude_transaction ? (string) $exclude_transaction : null;
        self::$exclude_session = $exclude_session ? (string) $exclude_session : null;
    }

    /**
     * @param string|null $exclude_transaction
     * @return void
     */
    public function begin_exclude_transaction($exclude_transaction = null)
    {
        $this->begin_exclude($exclude_transaction, self::$exclude_session);
    }

    /**
     * @return void
     */
    public function end_exclude_transaction()
    {
        self::$exclude_transaction = null;
        self::$exclude_session = null;
    }

    /**
     * Alias for WooCommerce bridge.
     *
     * @return void
     */
    public function end_exclude()
    {
        $this->end_exclude_transaction();
    }

    /**
     * @param int   $event_id
     * @param mixed $event_timestamp
     * @return array{total:int,tickets:array<string,int>}
     */
    public function get_held_quantities($event_id, $event_timestamp)
    {
        $this->maybe_create_table();

        global $wpdb;
        $table = $this->table_name();
        $now = gmdate('Y-m-d H:i:s');
        $ts = $this->normalize_timestamp($event_timestamp);

        $sql = "SELECT ticket_id, SUM(qty) AS held_qty
                FROM {$table}
                WHERE event_id = %d
                AND event_timestamp = %d
                AND expires_at > %s";
        $params = [absint($event_id), $ts, $now];

        if (self::$exclude_transaction) {
            $sql .= ' AND transaction_id != %s';
            $params[] = self::$exclude_transaction;
        }

        if (self::$exclude_session) {
            $sql .= ' AND session_key != %s';
            $params[] = self::$exclude_session;
        }

        $sql .= ' GROUP BY ticket_id';

        $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        $result = [
            'total'   => 0,
            'tickets' => [],
        ];

        if (empty($rows)) {
            return $result;
        }

        foreach ($rows as $row) {
            $qty = absint($row['held_qty']);
            $ticket_id = (string) $row['ticket_id'];
            $result['tickets'][$ticket_id] = $qty;
            $result['total'] += $qty;
        }

        return $result;
    }

    /**
     * Raw MEC availability without subtracting unpaid holds.
     *
     * @param int   $event_id
     * @param mixed $event_timestamp
     * @return array
     */
    public function get_availability_without_holds($event_id, $event_timestamp)
    {
        self::$bypass_filter = true;
        try {
            $book = $this->getBook();

            return $book->get_tickets_availability($event_id, $event_timestamp);
        } finally {
            self::$bypass_filter = false;
        }
    }

    /**
     * @param array  $availability
     * @param int    $event_id
     * @param mixed  $timestamp
     * @param string $mode
     * @param array  $tickets
     * @return array
     */
    public function filter_availability($availability, $event_id, $timestamp, $mode, $tickets)
    {
        if (self::$bypass_filter) {
            return $availability;
        }

        if ($mode !== 'availability' || !is_array($availability)) {
            return $availability;
        }

        $held = $this->get_held_quantities($event_id, $timestamp);

        if (isset($availability['total']) && $availability['total'] != -1) {
            $availability['total'] = max(0, (int) $availability['total'] - $held['total']);
        }

        foreach ($held['tickets'] as $ticket_id => $qty) {
            if (isset($availability[$ticket_id]) && $availability[$ticket_id] != -1) {
                $availability[$ticket_id] = max(0, (int) $availability[$ticket_id] - $qty);
            }
        }

        return $availability;
    }

    /**
     * @param array $availability
     * @param array $demand
     * @return bool
     */
    public function demand_fits_availability($availability, $demand)
    {
        $total_available = isset($availability['total']) ? (int) $availability['total'] : -1;
        if ($total_available != -1 && $demand['total'] > $total_available) {
            return false;
        }

        foreach ($demand['tickets'] as $ticket_id => $qty) {
            $ticket_available = isset($availability[$ticket_id]) ? (int) $availability[$ticket_id] : -1;
            if ($ticket_available != -1 && $qty > $ticket_available) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reserve seats for a MEC gateway transaction before payment.
     *
     * @param string $transaction_id
     * @param string $hold_type
     * @return bool
     */
    public function place_hold_for_transaction($transaction_id, $hold_type = 'gateway')
    {
        $this->maybe_create_table();

        $book = $this->getBook();
        $transaction = $book->get_transaction($transaction_id);

        if (!is_array($transaction) || empty($transaction['event_id']) || empty($transaction['date'])) {
            return false;
        }

        $event_id = absint($transaction['event_id']);
        $event_timestamp = $this->normalize_timestamp($transaction['date']);
        $demand = $this->count_transaction_tickets($transaction);

        if ($demand['total'] < 1) {
            return false;
        }

        if (!$this->acquire_lock($event_id, $event_timestamp)) {
            return false;
        }

        try {
            global $wpdb;
            $table = $this->table_name();

            // Replace previous holds for this transaction.
            $wpdb->delete($table, ['transaction_id' => (string) $transaction_id], ['%s']);

            $this->begin_exclude_transaction($transaction_id);
            $availability = $this->get_availability_without_holds($event_id, $event_timestamp);
            $held = $this->get_held_quantities($event_id, $event_timestamp);
            $this->end_exclude_transaction();

            $effective = $availability;
            if (isset($effective['total']) && $effective['total'] != -1) {
                $effective['total'] = max(0, (int) $effective['total'] - $held['total']);
            }
            foreach ($held['tickets'] as $ticket_id => $qty) {
                if (isset($effective[$ticket_id]) && $effective[$ticket_id] != -1) {
                    $effective[$ticket_id] = max(0, (int) $effective[$ticket_id] - $qty);
                }
            }

            if (!$this->demand_fits_availability($effective, $demand)) {
                return false;
            }

            $expires = gmdate('Y-m-d H:i:s', time() + self::HOLD_TTL);

            foreach ($demand['tickets'] as $ticket_id => $qty) {
                $inserted = $wpdb->insert(
                    $table,
                    [
                        'event_id'        => $event_id,
                        'event_timestamp' => $event_timestamp,
                        'ticket_id'       => (string) $ticket_id,
                        'qty'             => absint($qty),
                        'hold_type'       => sanitize_key($hold_type),
                        'session_key'     => '',
                        'order_id'        => 0,
                        'transaction_id'  => (string) $transaction_id,
                        'product_id'      => 0,
                        'expires_at'      => $expires,
                    ],
                    ['%d', '%d', '%s', '%d', '%s', '%s', '%d', '%s', '%d', '%s']
                );

                if (!$inserted) {
                    $wpdb->delete($table, ['transaction_id' => (string) $transaction_id], ['%s']);
                    return false;
                }
            }

            return true;
        } finally {
            $this->end_exclude_transaction();
            $this->release_lock($event_id, $event_timestamp);
        }
    }

    /**
     * Final post-payment check under lock. Caller must still create the booking
     * while holding the lock, then release_lock + release_by_transaction.
     *
     * @param string $transaction_id
     * @param array  $transaction
     * @return bool True when booking may proceed.
     */
    public function assert_can_complete($transaction_id, $transaction)
    {
        $event_id = isset($transaction['event_id']) ? absint($transaction['event_id']) : 0;
        $event_timestamp = isset($transaction['date']) ? $transaction['date'] : '';
        $demand = $this->count_transaction_tickets($transaction);

        if (!$event_id || !$event_timestamp || $demand['total'] < 1) {
            return true; // nothing to enforce
        }

        // Ignore this transaction's unpaid hold — only real bookings can block us.
        $availability = $this->get_availability_without_holds($event_id, $event_timestamp);

        $fits = $this->demand_fits_availability($availability, $demand);

        return $fits;
    }

    /**
     * @param string $transaction_id
     * @return void
     */
    public function release_by_transaction($transaction_id)
    {
        $this->maybe_create_table();

        global $wpdb;
        $wpdb->delete($this->table_name(), ['transaction_id' => (string) $transaction_id], ['%s']);
    }

    /**
     * @return void
     */
    public function cleanup_expired()
    {
        $this->maybe_create_table();

        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                'DELETE FROM ' . $this->table_name() . ' WHERE expires_at <= %s',
                gmdate('Y-m-d H:i:s')
            )
        );
    }
}

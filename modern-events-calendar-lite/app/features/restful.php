<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC RESTful class.
 * @author Webnus <info@webnus.net>
 */
class MEC_feature_restful extends MEC_base
{
    /**
     * @var MEC_factory
     */
    public $factory;

    /**
     * @var MEC_restful
     */
    public $restful;

    private $settings;

    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct()
    {
        // Import MEC Factory
        $this->factory = $this->getFactory();

        // Import MEC RESTful
        $this->restful = $this->getRestful();

        // MEC Settings
        $this->settings = $this->getMain()->get_settings();
    }

    /**
     * Initialize
     * @author Webnus <info@webnus.net>
     */
    public function init()
    {
        // Disabled
        if (!isset($this->settings['restful_api_status']) || !$this->settings['restful_api_status']) return;

        $this->factory->action('rest_api_init', [$this, 'register']);
    }

    public function register()
    {
        // Get Events
        register_rest_route($this->restful->get_namespace(), 'events', [
            'methods' => 'GET',
            'callback' => [$this, 'events'],
            'permission_callback' => [$this->restful, 'guest'],
        ]);

        // Get Event
        register_rest_route($this->restful->get_namespace(), 'events/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_event'],
            'permission_callback' => [$this->restful, 'guest'],
            'args' => [
                'id' => [
                    'validate_callback' => function ($param)
                    {
                        return is_numeric($param);
                    },
                ],
            ],
        ]);

        // Login Controller
        register_rest_route($this->restful->get_namespace(), 'login', [
            'methods' => 'POST',
            'callback' => [$this, 'login'],
            'permission_callback' => [$this->restful, 'guest'],
        ]);

        // Upload Image
        register_rest_route($this->restful->get_namespace(), 'images', [
            'methods' => 'POST',
            'callback' => [$this, 'upload_image'],
            'permission_callback' => [$this->restful, 'permission'],
        ]);

        // Upload File
        register_rest_route($this->restful->get_namespace(), 'files', [
            'methods' => 'POST',
            'callback' => [$this, 'upload_file'],
            'permission_callback' => [$this->restful, 'permission'],
        ]);

        // Create Event
        register_rest_route($this->restful->get_namespace(), 'events', [
            'methods' => 'POST',
            'callback' => [$this, 'create_event'],
            'permission_callback' => [$this->restful, 'permission'],
        ]);

        // Taxonomy Entities
        register_rest_route($this->restful->get_namespace(), 'taxonomies/(?P<entity>categories|tags|labels|speakers|sponsors)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'list_taxonomy_entities'],
                'permission_callback' => [$this->restful, 'permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_taxonomy_entity'],
                'permission_callback' => [$this->restful, 'permission'],
            ],
        ]);

        register_rest_route($this->restful->get_namespace(), 'taxonomies/(?P<entity>categories|tags|labels|speakers|sponsors)/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_taxonomy_entity'],
                'permission_callback' => [$this->restful, 'permission'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_taxonomy_entity'],
                'permission_callback' => [$this->restful, 'permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_taxonomy_entity'],
                'permission_callback' => [$this->restful, 'permission'],
            ],
        ]);

        // My Events
        register_rest_route($this->restful->get_namespace(), 'my-events', [
            'methods' => 'GET',
            'callback' => [$this, 'my'],
            'permission_callback' => [$this->restful, 'permission'],
        ]);

        // Edit Event
        register_rest_route($this->restful->get_namespace(), 'events/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'edit_event'],
            'permission_callback' => [$this->restful, 'permission'],
        ]);

        // Trash Event
        register_rest_route($this->restful->get_namespace(), 'events/(?P<id>\d+)/trash', [
            'methods' => 'DELETE',
            'callback' => [$this, 'trash'],
            'permission_callback' => [$this->restful, 'permission'],
        ]);

        // Delete Event
        register_rest_route($this->restful->get_namespace(), 'events/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete'],
            'permission_callback' => [$this->restful, 'permission'],
        ]);

        // Weather
        register_rest_route($this->restful->get_namespace(), 'events/(?P<id>\d+)/weather', [
            'methods' => 'GET',
            'callback' => [$this, 'weather'],
            'permission_callback' => [$this->restful, 'guest'],
        ]);

        // Related Events
        register_rest_route($this->restful->get_namespace(), 'events/(?P<id>\d+)/related-events', [
            'methods' => 'GET',
            'callback' => [$this, 'related_events'],
            'permission_callback' => [$this->restful, 'guest'],
        ]);

        // Next / Previous Events
        register_rest_route($this->restful->get_namespace(), 'events/(?P<id>\d+)/next-previous-events', [
            'methods' => 'GET',
            'callback' => [$this, 'next_previous_events'],
            'permission_callback' => [$this->restful, 'guest'],
        ]);

        // Next Occurrences
        register_rest_route($this->restful->get_namespace(), 'events/(?P<id>\d+)/next-occurrences', [
            'methods' => 'GET',
            'callback' => [$this, 'next_occurrences'],
            'permission_callback' => [$this->restful, 'guest'],
        ]);

        // Tickets
        register_rest_route($this->restful->get_namespace(), 'events/(?P<id>\d+)/tickets', [
            'methods' => 'GET',
            'callback' => [$this, 'tickets'],
            'permission_callback' => [$this->restful, 'guest'],
        ]);

        // Tax / Fees
        register_rest_route($this->restful->get_namespace(), 'events/(?P<id>\d+)/fees', [
            'methods' => 'GET',
            'callback' => [$this, 'fees'],
            'permission_callback' => [$this->restful, 'guest'],
        ]);

        // Custom Fields
        register_rest_route($this->restful->get_namespace(), 'config/custom-fields', [
            'methods' => 'GET',
            'callback' => [$this, 'custom_fields'],
            'permission_callback' => [$this->restful, 'guest'],
        ]);

        // Attendee Fields
        register_rest_route($this->restful->get_namespace(), 'config/attendee-fields', [
            'methods' => 'GET',
            'callback' => [$this, 'attendee_fields'],
            'permission_callback' => [$this->restful, 'guest'],
        ]);

        // Fixed Fields
        register_rest_route($this->restful->get_namespace(), 'config/fixed-fields', [
            'methods' => 'GET',
            'callback' => [$this, 'fixed_fields'],
            'permission_callback' => [$this->restful, 'guest'],
        ]);

        // Ticket Variation
        register_rest_route($this->restful->get_namespace(), 'config/ticket-variations', [
            'methods' => 'GET',
            'callback' => [$this, 'ticket_variations'],
            'permission_callback' => [$this->restful, 'guest'],
        ]);

        // Icons
        register_rest_route($this->restful->get_namespace(), 'config/icons', [
            'methods' => 'GET',
            'callback' => [$this, 'icons'],
            'permission_callback' => [$this->restful, 'guest'],
        ]);
    }

    private function add_event_labels($event)
    {
        if (!is_object($event) || !isset($event->data) || !is_object($event->data)) return $event;

        $event_id = $event->data->ID ?? ($event->ID ?? null);
        if (!$event_id) return $event;

        if (isset($event->data->labels) && is_array($event->data->labels) && count($event->data->labels)) return $event;

        $terms = wp_get_post_terms($event_id, 'mec_label', ['fields' => 'all']);
        if (is_wp_error($terms) || !is_array($terms) || !count($terms)) return $event;

        $labels = [];
        foreach ($terms as $term)
        {
            if (!isset($term->term_id)) continue;

            $labels[$term->term_id] = [
                'id' => (int) $term->term_id,
                'name' => $term->name,
                'color' => get_metadata('term', $term->term_id, 'color', true),
                'style' => get_metadata('term', $term->term_id, 'style', true),
            ];
        }

        if (count($labels)) $event->data->labels = $labels;

        return $event;
    }

    private function add_labels_in_events($events)
    {
        if (!is_array($events)) return $events;

        foreach ($events as $date => $day_events)
        {
            if (!is_array($day_events)) continue;

            foreach ($day_events as $index => $event)
            {
                $events[$date][$index] = $this->add_event_labels($event);
            }
        }

        return $events;
    }

    private function wp_error_status(WP_Error $error, $default = 500): int
    {
        $data = $error->get_error_data();

        if (is_array($data) && isset($data['status']) && is_numeric($data['status']))
        {
            return (int) $data['status'];
        }

        return (int) $default;
    }

    private function error_response(WP_Error $error, $default = 500): WP_REST_Response
    {
        return $this->restful->response([
            'data' => $error,
            'status' => $this->wp_error_status($error, $default),
        ]);
    }

    private function get_taxonomy_payload(array $vars, array $tax, string $key): array
    {
        if (isset($tax[$key]) && is_array($tax[$key])) return $tax[$key];
        if (isset($vars[$key]) && is_array($vars[$key])) return $vars[$key];

        $payload = [];
        $taxonomy_prefix = 'taxonomies[' . $key . ']';
        foreach ($vars as $param => $value)
        {
            if (strpos($param, $key . '[') === 0)
            {
                $sub_key = trim(substr($param, strlen($key)), '[]');
            }
            elseif (strpos($param, $taxonomy_prefix . '[') === 0)
            {
                $sub_key = trim(substr($param, strlen($taxonomy_prefix)), '[]');
            }
            else continue;

            if ($sub_key === '') continue;

            $payload[$sub_key] = $value;
        }

        return $payload;
    }

    private function get_taxonomy_id(array $vars, array $payload, string $key): int
    {
        $id_key = $key . '_id';
        if (isset($vars[$id_key]) && is_numeric($vars[$id_key])) return (int) $vars[$id_key];
        if (isset($payload['id']) && is_numeric($payload['id'])) return (int) $payload['id'];
        if (isset($payload[$id_key]) && is_numeric($payload[$id_key])) return (int) $payload[$id_key];

        return 0;
    }

    private function get_taxonomy_entities(): array
    {
        return [
            'categories' => [
                'single' => 'category',
                'taxonomy' => 'mec_category',
                'save_method' => 'save_category',
            ],
            'tags' => [
                'single' => 'tag',
                'taxonomy' => apply_filters('mec_taxonomy_tag', ''),
                'save_method' => 'save_tag',
            ],
            'labels' => [
                'single' => 'label',
                'taxonomy' => 'mec_label',
                'save_method' => 'save_label',
            ],
            'speakers' => [
                'single' => 'speaker',
                'taxonomy' => 'mec_speaker',
                'save_method' => 'save_speaker',
            ],
            'sponsors' => [
                'single' => 'sponsor',
                'taxonomy' => 'mec_sponsor',
                'save_method' => 'save_sponsor',
            ],
        ];
    }

    private function get_taxonomy_entity_config(string $entity): ?array
    {
        $entities = $this->get_taxonomy_entities();
        $config = $entities[$entity] ?? null;
        if (!$config) return null;

        if ($entity === 'tags' && !trim((string) $config['taxonomy'])) return null;
        if ($entity === 'speakers' && (!isset($this->settings['speakers_status']) || !$this->settings['speakers_status'])) return null;
        if ($entity === 'sponsors' && (!$this->getPRO() || !isset($this->settings['sponsors_status']) || !$this->settings['sponsors_status'])) return null;

        return $config;
    }

    private function get_taxonomy_entity_items(array $vars, array $tax, string $entity): array
    {
        $items = [];

        if (isset($tax[$entity]) && is_array($tax[$entity])) $items = $tax[$entity];
        elseif (isset($vars[$entity]) && is_array($vars[$entity])) $items = $vars[$entity];

        $ids_key = $this->get_taxonomy_entities()[$entity]['single'] . '_ids';
        if (isset($vars[$ids_key]) && is_array($vars[$ids_key])) $items = array_merge($items, $vars[$ids_key]);

        return $items;
    }

    private function sanitize_taxonomy_entity_value($value, string $entity, string $field = '')
    {
        if (is_array($value)) return '';

        switch ($entity)
        {
            case 'categories':
                if ($field === 'mec_cat_color') return sanitize_text_field($value);
                if ($field === 'mec_cat_fallback_image') return sanitize_text_field($value);
                return sanitize_text_field($value);

            case 'labels':
                return sanitize_text_field($value);

            case 'speakers':
                if (in_array($field, ['facebook', 'twitter', 'instagram', 'linkedin', 'website'], true)) return trim((string) $value) ? esc_url_raw($value) : '';
                if ($field === 'email') return sanitize_email($value);
                if ($field === 'mec_index') return is_numeric($value) ? (string) $value : '99';
                if ($field === 'type') return in_array($value, ['person', 'group'], true) ? $value : 'person';
                return sanitize_text_field($value);

            case 'sponsors':
                if (in_array($field, ['link', 'logo'], true)) return trim((string) $value) ? esc_url_raw($value) : '';
                return sanitize_text_field($value);

            case 'tags':
            default:
                return sanitize_text_field($value);
        }
    }

    private function get_taxonomy_entity_meta_input($payload, string $entity): array
    {
        if (!is_array($payload)) return [];

        switch ($entity)
        {
            case 'categories':
                return [
                    'mec_cat_icon' => $this->sanitize_taxonomy_entity_value($payload['icon'] ?? ($payload['mec_cat_icon'] ?? ''), $entity, 'mec_cat_icon'),
                    'mec_cat_color' => $this->sanitize_taxonomy_entity_value($payload['color'] ?? ($payload['mec_cat_color'] ?? ''), $entity, 'mec_cat_color'),
                    'mec_cat_fallback_image' => $this->sanitize_taxonomy_entity_value($payload['fallback_image'] ?? ($payload['mec_cat_fallback_image'] ?? ''), $entity, 'mec_cat_fallback_image'),
                ];

            case 'labels':
                return [
                    'color' => $this->sanitize_taxonomy_entity_value($payload['color'] ?? '', $entity, 'color'),
                    'style' => $this->sanitize_taxonomy_entity_value($payload['style'] ?? '', $entity, 'style'),
                ];

            case 'speakers':
                return [
                    'type' => $this->sanitize_taxonomy_entity_value($payload['type'] ?? 'person', $entity, 'type'),
                    'job_title' => $this->sanitize_taxonomy_entity_value($payload['job_title'] ?? '', $entity, 'job_title'),
                    'tel' => $this->sanitize_taxonomy_entity_value($payload['tel'] ?? '', $entity, 'tel'),
                    'email' => $this->sanitize_taxonomy_entity_value($payload['email'] ?? '', $entity, 'email'),
                    'website' => $this->sanitize_taxonomy_entity_value($payload['website'] ?? '', $entity, 'website'),
                    'mec_index' => $this->sanitize_taxonomy_entity_value($payload['mec_index'] ?? 99, $entity, 'mec_index'),
                    'facebook' => $this->sanitize_taxonomy_entity_value($payload['facebook'] ?? '', $entity, 'facebook'),
                    'twitter' => $this->sanitize_taxonomy_entity_value($payload['twitter'] ?? '', $entity, 'twitter'),
                    'instagram' => $this->sanitize_taxonomy_entity_value($payload['instagram'] ?? '', $entity, 'instagram'),
                    'linkedin' => $this->sanitize_taxonomy_entity_value($payload['linkedin'] ?? '', $entity, 'linkedin'),
                    'thumbnail' => $this->sanitize_taxonomy_entity_value($payload['thumbnail'] ?? '', $entity, 'thumbnail'),
                ];

            case 'sponsors':
                return [
                    'link' => $this->sanitize_taxonomy_entity_value($payload['link'] ?? '', $entity, 'link'),
                    'logo' => $this->sanitize_taxonomy_entity_value($payload['logo'] ?? '', $entity, 'logo'),
                ];

            case 'tags':
            default:
                return [];
        }
    }

    private function filter_taxonomy_entity_meta(array $meta): array
    {
        foreach ($meta as $key => $value)
        {
            if ($value === '' || $value === null) unset($meta[$key]);
        }

        return $meta;
    }

    private function format_taxonomy_entity($term, string $entity): array
    {
        $term = get_term($term);
        if (!$term || is_wp_error($term)) return [];

        $data = [
            'id' => (int) $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => $term->description,
            'count' => (int) $term->count,
            'taxonomy' => $term->taxonomy,
        ];

        if ($entity === 'categories')
        {
            $data['icon'] = get_term_meta($term->term_id, 'mec_cat_icon', true);
            $data['color'] = get_term_meta($term->term_id, 'mec_cat_color', true);
            $data['fallback_image'] = get_term_meta($term->term_id, 'mec_cat_fallback_image', true);
            $data['parent'] = (int) $term->parent;
        }
        elseif ($entity === 'labels')
        {
            $data['color'] = get_term_meta($term->term_id, 'color', true);
            $data['style'] = get_term_meta($term->term_id, 'style', true);
        }
        elseif ($entity === 'speakers')
        {
            $data['type'] = get_term_meta($term->term_id, 'type', true);
            $data['job_title'] = get_term_meta($term->term_id, 'job_title', true);
            $data['tel'] = get_term_meta($term->term_id, 'tel', true);
            $data['email'] = get_term_meta($term->term_id, 'email', true);
            $data['website'] = get_term_meta($term->term_id, 'website', true);
            $data['mec_index'] = get_term_meta($term->term_id, 'mec_index', true);
            $data['facebook'] = get_term_meta($term->term_id, 'facebook', true);
            $data['twitter'] = get_term_meta($term->term_id, 'twitter', true);
            $data['instagram'] = get_term_meta($term->term_id, 'instagram', true);
            $data['linkedin'] = get_term_meta($term->term_id, 'linkedin', true);
            $data['thumbnail'] = get_term_meta($term->term_id, 'thumbnail', true);
        }
        elseif ($entity === 'sponsors')
        {
            $data['link'] = get_term_meta($term->term_id, 'link', true);
            $data['logo'] = get_term_meta($term->term_id, 'logo', true);
        }

        return $data;
    }

    private function current_user_can_manage_taxonomy(string $taxonomy, string $action = 'assign_terms'): bool
    {
        $taxonomy_object = get_taxonomy($taxonomy);
        if (!$taxonomy_object || !isset($taxonomy_object->cap)) return false;

        $cap = $taxonomy_object->cap->{$action} ?? '';
        if (!trim($cap)) return false;

        return current_user_can($cap);
    }

    private function taxonomy_entity_capability_error(string $action = 'manage'): WP_REST_Response
    {
        return $this->restful->response([
            'data' => new WP_Error('401', esc_html__("You're not authorized to manage this taxonomy!", 'modern-events-calendar-lite')),
            'status' => 401,
        ]);
    }

    private function taxonomy_entity_not_found_response(): WP_REST_Response
    {
        return $this->restful->response([
            'data' => new WP_Error('404', esc_html__('Taxonomy entity not found!', 'modern-events-calendar-lite')),
            'status' => 404,
        ]);
    }

    private function get_term_id_from_event_item($item, string $entity)
    {
        $config = $this->get_taxonomy_entity_config($entity);
        if (!$config) return 0;

        $taxonomy = $config['taxonomy'];
        if (!$taxonomy || !taxonomy_exists($taxonomy)) return 0;

        if (is_numeric($item))
        {
            $term = get_term((int) $item, $taxonomy);
            return ($term && !is_wp_error($term)) ? (int) $term->term_id : 0;
        }

        if (is_string($item))
        {
            $name = trim($item);
            if ($name === '') return 0;

            return (int) $this->getMain()->{$config['save_method']}([
                'name' => $name,
            ]);
        }

        if (!is_array($item)) return 0;

        if (isset($item['id']) && is_numeric($item['id']))
        {
            $term = get_term((int) $item['id'], $taxonomy);
            return ($term && !is_wp_error($term)) ? (int) $term->term_id : 0;
        }

        $payload = ['name' => trim((string) ($item['name'] ?? ''))];
        if ($payload['name'] === '') return 0;

        $existing = get_term_by('name', $payload['name'], $taxonomy);
        if ($existing && !is_wp_error($existing) && isset($existing->term_id)) return (int) $existing->term_id;

        if ($entity === 'labels') $payload['color'] = $item['color'] ?? '';
        elseif ($entity === 'speakers')
        {
            $payload['job_title'] = $item['job_title'] ?? '';
            $payload['tel'] = $item['tel'] ?? '';
            $payload['email'] = $item['email'] ?? '';
            $payload['facebook'] = $item['facebook'] ?? '';
            $payload['twitter'] = $item['twitter'] ?? '';
            $payload['instagram'] = $item['instagram'] ?? '';
            $payload['linkedin'] = $item['linkedin'] ?? '';
            $payload['website'] = $item['website'] ?? '';
            $payload['thumbnail'] = $item['thumbnail'] ?? '';
        }
        elseif ($entity === 'sponsors')
        {
            $payload['link'] = $item['link'] ?? '';
            $payload['logo'] = $item['logo'] ?? '';
        }

        $term_id = (int) $this->getMain()->{$config['save_method']}($payload);
        if ($term_id) $this->save_taxonomy_entity_meta($term_id, $entity, $item);

        return $term_id;
    }

    private function get_term_ids_from_event_items(array $vars, array $tax, string $entity): array
    {
        $items = $this->get_taxonomy_entity_items($vars, $tax, $entity);
        $ids = [];

        foreach ($items as $item)
        {
            $id = $this->get_term_id_from_event_item($item, $entity);
            if ($id) $ids[] = $id;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function insert_taxonomy_entity_term(array $config, array $params)
    {
        $taxonomy = $config['taxonomy'];

        $args = [];
        if (isset($params['slug']) && trim($params['slug'])) $args['slug'] = sanitize_title($params['slug']);
        if (isset($params['description'])) $args['description'] = sanitize_textarea_field($params['description']);
        if ($taxonomy === 'mec_category' && isset($params['parent']) && is_numeric($params['parent'])) $args['parent'] = (int) $params['parent'];

        return wp_insert_term(sanitize_text_field($params['name']), $taxonomy, $args);
    }

    private function save_taxonomy_entity_meta(int $term_id, string $entity, array $payload): void
    {
        $meta = $this->filter_taxonomy_entity_meta($this->get_taxonomy_entity_meta_input($payload, $entity));

        foreach ($meta as $key => $value)
        {
            update_term_meta($term_id, $key, $value);
        }
    }

    public function events(WP_REST_Request $request)
    {
        $limit = $request->get_param('limit');
        if (!$limit) $limit = 12;

        if (!is_numeric($limit))
        {
            return $this->restful->response([
                'data' => new WP_Error(400, esc_html__('Limit parameter must be numeric!', 'modern-events-calendar-lite')),
                'status' => 400,
            ]);
        }

        $order = $request->get_param('order');
        if (!$order) $order = 'ASC';

        if (!in_array($order, ['ASC', 'DESC']))
        {
            return $this->restful->response([
                'data' => new WP_Error(400, esc_html__('Order parameter is invalid!', 'modern-events-calendar-lite')),
                'status' => 400,
            ]);
        }

        $start_date = $request->get_param('start_date');

        $start_date_type = $request->get_param('start_date_type');
        if (!$start_date_type)
        {
            $start_date_type = $start_date ? 'date' : 'today';
        }

        if ($start_date_type === 'date' && !$start_date)
        {
            return $this->restful->response([
                'data' => new WP_Error(400, esc_html__('When the start_date_type parameter is set to date, then start_date parameter is required.', 'modern-events-calendar-lite')),
                'status' => 400,
            ]);
        }

        $end_date_type = $request->get_param('end_date_type');
        if (!$end_date_type) $end_date_type = 'date';

        $end_date = $request->get_param('end_date');

        $show_only_past_events = (int) $request->get_param('show_only_past_events');
        $include_past_events = (int) $request->get_param('include_past_events');

        $show_only_ongoing_events = (int) $request->get_param('show_only_ongoing_events');
        $include_ongoing_events = (int) $request->get_param('include_ongoing_events');

        $args = [
            'sk-options' => [
                'list' => [
                    'limit' => $limit,
                    'order_method' => $order,
                    'start_date_type' => $start_date_type,
                    'start_date' => $start_date,
                    'end_date_type' => $end_date_type,
                    'maximum_date_range' => $end_date,
                ],
            ],
            'show_only_past_events' => $show_only_past_events,
            'show_past_events' => $include_past_events,
            'show_only_ongoing_events' => $show_only_ongoing_events,
            'show_ongoing_events' => $include_ongoing_events,
            's' => (string) $request->get_param('keyword'),
            'label' => (string) $request->get_param('labels'),
            'ex_label' => (string) $request->get_param('ex_labels'),
            'category' => (string) $request->get_param('categories'),
            'ex_category' => (string) $request->get_param('ex_categories'),
            'location' => (string) $request->get_param('locations'),
            'ex_location' => (string) $request->get_param('ex_locations'),
            'address' => (string) $request->get_param('address'),
            'organizer' => (string) $request->get_param('organizers'),
            'ex_organizer' => (string) $request->get_param('ex_organizers'),
            'sponsor' => (string) $request->get_param('sponsors'),
            'speaker' => (string) $request->get_param('speakers'),
            'ex_speaker' => (string) $request->get_param('ex_speakers'),
            'tag' => (string) $request->get_param('tags'),
            'ex_tag' => (string) $request->get_param('ex_tags'),
        ];

        // Events Object
        $EO = new MEC_skin_list();
        $EO->initialize($args);

        // Set Offset
        $EO->offset = (int) $request->get_param('offset');

        // Events
        $events = $EO->fetch();
        $events = $this->add_labels_in_events($events);

        // Response
        return $this->restful->response([
            'data' => [
                'events' => $events,
                'pagination' => [
                    'next_date' => $EO->end_date,
                    'next_offset' => $EO->next_offset,
                    'has_more_events' => $EO->has_more_events,
                    'found' => $EO->found,
                ],
            ],
        ]);
    }

    public function get_event(WP_REST_Request $request)
    {
        // Event ID
        $id = $request->get_param('id');

        // Invalid Event ID
        if (!is_numeric($id))
        {
            return $this->restful->response([
                'data' => new WP_Error(400, esc_html__('Event id must be numeric!', 'modern-events-calendar-lite')),
                'status' => 400,
            ]);
        }

        // Event Post
        $post = get_post($id);

        // Not Event Post or Not Published Event
        if (
            !$post
            || $post->post_type !== $this->getMain()->get_main_post_type()
            || $post->post_status !== 'publish'
            || $post->post_password !== ''
        )
        {
            return $this->restful->response([
                'data' => new WP_Error(404, esc_html__('Event not found!', 'modern-events-calendar-lite')),
                'status' => 404,
            ]);
        }

        // Render Event Data
        $single = new MEC_skin_single();
        $events = $single->get_event_mec($id);
        $event = (isset($events[0]) && is_object($events[0])) ? $this->add_event_labels($events[0]) : null;

        // Response
        return $this->restful->response([
            'data' => $event ?: new stdClass(),
        ]);
    }

    public function login(WP_REST_Request $request)
    {
        $vars = $request->get_params();

        $username = $vars['username'] ?? '';
        $password = $vars['password'] ?? '';

        // Login
        $response = wp_signon([
            'user_login' => $username,
            'user_password' => $password,
            'remember' => false,
        ], is_ssl());

        // Invalid Credentials
        if (is_wp_error($response)) return $response;

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
                'id' => $response->ID,
                'token' => $this->restful->get_user_token($response->ID),
            ],
            'status' => 200,
        ]);
    }

    public function upload_image(WP_REST_Request $request)
    {
        if (!current_user_can('upload_files')) return $this->restful->response([
            'data' => new WP_Error(401, esc_html__("You're not authorized to upload images!", 'modern-events-calendar-lite')),
            'status' => 401,
        ]);

        // Media Libraries
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $vars = $request->get_file_params();

        $image = is_array($vars) && isset($vars['image']) ? $vars['image'] : [];
        $tmp = $image['tmp_name'] ?? null;

        // Image Not Found
        if (!$tmp) return $this->restful->response([
            'data' => new WP_Error(400, esc_html__('Image is required!', 'modern-events-calendar-lite')),
            'status' => 400,
        ]);

        $ex = explode('.', $image['name']);
        $extension = end($ex);

        // Invalid Extension
        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'gif'])) return $this->restful->response([
            'data' => new WP_Error(400, esc_html__('Invalid image extension! PNG, JPG and GIF images are allowed.', 'modern-events-calendar-lite')),
            'status' => 400,
        ]);

        // Upload File
        $uploaded = wp_handle_upload($image, ['test_form' => false]);

        // Upload Failed
        if (isset($uploaded['error'])) return $this->restful->response([
            'data' => new WP_Error(400, $uploaded['error']),
            'status' => 400,
        ]);

        $name = $image['name'];
        $file = $uploaded['file'];

        $attachment = [
            'post_mime_type' => $uploaded['type'],
            'post_title' => sanitize_file_name($name),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        // Add as Attachment
        $id = wp_insert_attachment($attachment, $file);

        // Update Metadata
        wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $file));

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
                'image_id' => $id,
            ],
            'status' => 200,
        ]);
    }

    public function upload_file(WP_REST_Request $request)
    {
        if (!current_user_can('upload_files')) return $this->restful->response([
            'data' => new WP_Error(401, esc_html__("You're not authorized to upload files!", 'modern-events-calendar-lite')),
            'status' => 401,
        ]);

        // Media Libraries
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $vars = $request->get_file_params();

        $file = is_array($vars) && isset($vars['file']) ? $vars['file'] : [];
        $tmp = $file['tmp_name'] ?? null;

        // Image Not Found
        if (!$tmp) return $this->restful->response([
            'data' => new WP_Error(400, esc_html__('File is required!', 'modern-events-calendar-lite')),
            'status' => 400,
        ]);

        $ex = explode('.', $file['name']);
        $extension = end($ex);

        // Invalid Extension
        if (!in_array($extension, ['docx', 'jpeg', 'jpg', 'png', 'pdf', 'zip'])) return $this->restful->response([
            'data' => new WP_Error(400, esc_html__('Invalid file extension! Docx, PNG, JPG, PDF and zip files are allowed.', 'modern-events-calendar-lite')),
            'status' => 400,
        ]);

        // Upload File
        $uploaded = wp_handle_upload($file, ['test_form' => false]);

        // Upload Failed
        if (isset($uploaded['error'])) return $this->restful->response([
            'data' => new WP_Error(400, $uploaded['error']),
            'status' => 400,
        ]);

        $name = $file['name'];
        $wp_upload_dir = wp_upload_dir();

        $attachment = [
            'guid' => $wp_upload_dir['baseurl'] . _wp_relative_upload_path($uploaded['file']),
            'post_mime_type' => $uploaded['type'],
            'post_title' => sanitize_file_name($name),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        // Add as Attachment
        $id = wp_insert_attachment($attachment, $uploaded['file']);

        // Update Metadata
        wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $uploaded['file']));

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
                'file_id' => $id,
            ],
            'status' => 200,
        ]);
    }

    public function create_event(WP_REST_Request $request, $event_id = null): WP_REST_Response
    {
        $vars = $request->get_params();

        $tax = isset($vars['taxonomies']) && is_array($vars['taxonomies']) ? $vars['taxonomies'] : [];
        $post_title = isset($vars['title']) ? sanitize_text_field($vars['title']) : '';
        $post_content = $vars['content'] ?? '';

        // Event Title is Required
        if (!trim($post_title)) return $this->restful->response([
            'data' => new WP_Error('400', esc_html__("Event title field is required!", 'modern-events-calendar-lite')),
            'status' => 400,
        ]);

        $main = $this->getMain();

        // Post Status
        $status = 'pending';
        if (current_user_can('publish_posts')) $status = 'publish';

        // Event location
        $location = $this->get_taxonomy_payload($vars, $tax, 'location');
        $location_id = $this->get_taxonomy_id($vars, $location, 'location');
        if (!$location_id && isset($location['name']) && trim($location['name']))
        {
            $location_id = $main->save_location([
                'name' => trim($location['name']),
                'address' => $location['address'] ?? '',
                'latitude' => $location['latitude'] ?? 0,
                'longitude' => $location['longitude'] ?? 0,
                'thumbnail' => $location['thumbnail'] ?? '',
            ]);
        }

        if (!$location_id) $location_id = 1;

        // Event Organizer
        $organizer = $this->get_taxonomy_payload($vars, $tax, 'organizer');
        $organizer_id = $this->get_taxonomy_id($vars, $organizer, 'organizer');
        if (!$organizer_id && isset($organizer['name']) && trim($organizer['name']))
        {
            $organizer_id = $main->save_organizer([
                'name' => trim($organizer['name']),
                'email' => $organizer['email'] ?? '',
                'tel' => $organizer['tel'] ?? '',
                'url' => $organizer['url'] ?? '',
                'thumbnail' => $organizer['thumbnail'] ?? '',
            ]);
        }

        if (!$organizer_id) $organizer_id = 1;

        // Event Categories, Tags, Labels, Speakers and Sponsors
        $category_ids = $this->get_term_ids_from_event_items($vars, $tax, 'categories');
        $tag_ids = $this->get_term_ids_from_event_items($vars, $tax, 'tags');
        $label_ids = $this->get_term_ids_from_event_items($vars, $tax, 'labels');
        $speaker_ids = $this->get_term_ids_from_event_items($vars, $tax, 'speakers');
        $sponsor_ids = $this->get_term_ids_from_event_items($vars, $tax, 'sponsors');

        // Start
        $start_date = $vars['start_date'] ?? current_time('Y-m-d');
        $start_hour = $vars['start_hour'] ?? 8;
        $start_minutes = $vars['start_minutes'] ?? 0;
        $start_ampm = $vars['start_ampm'] ?? 'AM';

        // End
        $end_date = $vars['end_date'] ?? current_time('Y-m-d');
        $end_hour = $vars['end_hour'] ?? 6;
        $end_minutes = $vars['end_minutes'] ?? 0;
        $end_ampm = $vars['end_ampm'] ?? 'PM';

        // Time Options
        $allday = $vars['allday'] ?? 0;
        $time_comment = $vars['time_comment'] ?? '';
        $hide_time = $vars['hide_time'] ?? 0;
        $hide_end_time = $vars['hide_end_time'] ?? 0;

        // Repeat Options
        $repeat_status = $vars['repeat_status'] ?? 0;
        $repeat_type = $vars['repeat_type'] ?? '';
        $repeat_interval = $vars['repeat_interval'] ?? 1;
        $finish = $vars['finish'] ?? '';
        $year = $vars['year'] ?? '';
        $month = $vars['month'] ?? '';
        $day = $vars['day'] ?? '';
        $week = $vars['week'] ?? '';
        $weekday = $vars['weekday'] ?? '';
        $weekdays = $vars['weekdays'] ?? '';
        $days = $vars['days'] ?? '';
        $not_in_days = $vars['not_in_days'] ?? '';
        $event_color = trim(sanitize_text_field($vars['color'] ?? ($vars['event_color'] ?? '')), '# ');

        $additional_organizer_ids = [];
        if (isset($vars['additional_organizer_ids']) && is_array($vars['additional_organizer_ids']))
        {
            $additional_organizer_ids[] = $vars['additional_organizer_ids'];
        }

        $hourly_schedules = [];
        if (isset($vars['hourly_schedules']) && is_array($vars['hourly_schedules']))
        {
            $hourly_schedules[] = $vars['hourly_schedules'];
        }

        $tickets = [];
        if (isset($vars['tickets']) && is_array($vars['tickets']))
        {
            $tickets[] = $vars['tickets'];
        }

        $fees = [];
        if (isset($vars['fees']) && is_array($vars['fees']))
        {
            $fees[] = $vars['fees'];
        }

        $advanced_days = [];
        if (isset($vars['advanced_days']) && is_array($vars['advanced_days']))
        {
            $advanced_days[] = $vars['advanced_days'];
        }

        $args = [
            'title' => $post_title,
            'content' => $post_content,
            'status' => $status,
            'location_id' => $location_id,
            'organizer_id' => $organizer_id,
            'date' => [
                'start' => [
                    'date' => $start_date,
                    'hour' => $start_hour,
                    'minutes' => $start_minutes,
                    'ampm' => $start_ampm,
                ],
                'end' => [
                    'date' => $end_date,
                    'hour' => $end_hour,
                    'minutes' => $end_minutes,
                    'ampm' => $end_ampm,
                ],
                'repeat' => [],
                'allday' => $allday,
                'comment' => $time_comment,
                'hide_time' => $hide_time,
                'hide_end_time' => $hide_end_time,
            ],
            'start' => $start_date,
            'start_time_hour' => $start_hour,
            'start_time_minutes' => $start_minutes,
            'start_time_ampm' => $start_ampm,
            'end' => $end_date,
            'end_time_hour' => $end_hour,
            'end_time_minutes' => $end_minutes,
            'end_time_ampm' => $end_ampm,
            'repeat_status' => $repeat_status,
            'repeat_type' => $repeat_type,
            'interval' => $repeat_interval,
            'finish' => $finish,
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'week' => $week,
            'weekday' => $weekday,
            'weekdays' => $weekdays,
            'days' => $days,
            'not_in_days' => $not_in_days,
            'meta' => [
                'mec_source' => 'mec-calendar',
                'mec_dont_show_map' => $vars['dont_show_map'] ?? '',
                'mec_color' => $event_color,
                'mec_read_more' => $vars['read_more'] ?? '',
                'mec_more_info' => $vars['more_info'] ?? '',
                'mec_more_info_title' => $vars['more_info_title'] ?? '',
                'mec_more_info_target' => $vars['more_info_target'] ?? '',
                'mec_cost' => $vars['cost'] ?? '',
                'mec_additional_organizer_ids' => $additional_organizer_ids,
                'mec_repeat' => [
                    'status' => $repeat_status,
                    'type' => $repeat_type,
                    'interval' => $repeat_interval,
                    'end' => $vars['end'] ?? '',
                    'end_at_date' => $vars['end_at_date'] ?? '',
                    'end_at_occurrences' => $vars['end_at_occurrences'] ?? '',
                ],
                'mec_allday' => $allday,
                'mec_hide_time' => $hide_time,
                'mec_hide_end_time' => $hide_end_time,
                'mec_comment' => $time_comment,
                'mec_repeat_end' => $vars['repeat_end'] ?? '',
                'mec_repeat_end_at_occurrences' => $vars['repeat_end_at_occurrences'] ?? '',
                'mec_repeat_end_at_date' => $vars['repeat_end_at_date'] ?? '',
                'mec_in_days' => $vars['in_days'] ?? '',
                'mec_not_in_days' => $vars['not_in_days'] ?? '',
                'mec_hourly_schedules' => $hourly_schedules,
                'mec_booking' => [
                    'bookings_limit_unlimited' => $vars['bookings_limit_unlimited'] ?? '',
                    'bookings_limit' => $vars['bookings_limit'] ?? '',
                ],
                'mec_tickets' => $tickets,
                'mec_fees_global_inheritance' => $vars['fees_global_inheritance'] ?? 1,
                'mec_fees' => $fees,
                'mec_reg_fields_global_inheritance' => 1,
                'mec_reg_fields' => [],
                'mec_advanced_days' => $advanced_days,
                'mec_fields' => [],
            ],
        ];

        // Insert the event into MEC
        $post_id = $main->save_event($args, $event_id);
        if (is_wp_error($post_id)) return $this->error_response($post_id);
        if (trim($event_color)) $main->add_to_available_colors($event_color);

        // Set location to the post
        if ($location_id)
        {
            $result = wp_set_object_terms($post_id, (int) $location_id, 'mec_location');
            if (is_wp_error($result)) return $this->error_response($result);
        }

        // Set organizer to the post
        if ($organizer_id)
        {
            $result = wp_set_object_terms($post_id, (int) $organizer_id, 'mec_organizer');
            if (is_wp_error($result)) return $this->error_response($result);
        }

        // Set categories to the post
        if (count($category_ids))
        {
            foreach ($category_ids as $category_id)
            {
                $result = wp_set_object_terms($post_id, (int) $category_id, 'mec_category', true);
                if (is_wp_error($result)) return $this->error_response($result);
            }
        }

        // Set tags to the post
        if (count($tag_ids))
        {
            foreach ($tag_ids as $tag_id)
            {
                $result = wp_set_object_terms($post_id, (int) $tag_id, apply_filters('mec_taxonomy_tag', ''), true);
                if (is_wp_error($result)) return $this->error_response($result);
            }
        }

        // Set labels to the post
        if (count($label_ids))
        {
            foreach ($label_ids as $label_id)
            {
                $result = wp_set_object_terms($post_id, (int) $label_id, 'mec_label', true);
                if (is_wp_error($result)) return $this->error_response($result);
            }
        }

        // Set speakers to the post
        if (count($speaker_ids))
        {
            foreach ($speaker_ids as $speaker_id)
            {
                $result = wp_set_object_terms($post_id, (int) $speaker_id, 'mec_speaker', true);
                if (is_wp_error($result)) return $this->error_response($result);
            }
        }

        // Set sponsors to the post
        if (count($sponsor_ids))
        {
            foreach ($sponsor_ids as $sponsor_id)
            {
                $result = wp_set_object_terms($post_id, (int) $sponsor_id, 'mec_sponsor', true);
                if (is_wp_error($result)) return $this->error_response($result);
            }
        }

        // Featured Image
        if (isset($vars['thumbnail']) && $vars['thumbnail']) set_post_thumbnail($post_id, (int) $vars['thumbnail']);

        // Publish Event
        if ($status === 'publish' && get_post_status($post_id) !== 'publish')
        {
            wp_publish_post($post_id);

            if (get_post_status($post_id) !== 'publish')
            {
                return $this->error_response(new WP_Error('mec_event_publish_failed', esc_html__('The event could not be published.', 'modern-events-calendar-lite'), ['status' => 500]));
            }
        }

        if ($status === 'publish') $message = esc_html__('The event is published.', 'modern-events-calendar-lite');
        else $message = esc_html__('The event is submitted. It will publish as soon as possible.', 'modern-events-calendar-lite');

        // Trigger Action
        do_action('mec_api_event_created', $post_id, $request);

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
                'message' => $message,
                'event_id' => $post_id,
            ],
            'status' => 200,
        ]);
    }

    public function edit_event(WP_REST_Request $request): WP_REST_Response
    {
        $id = $request->get_param('id');

        // Current User is not Authorized to Edit this Event
        if (!current_user_can('edit_post', $id)) return $this->restful->response([
            'data' => new WP_Error('401', esc_html__("You're not authorized to edit this event!", 'modern-events-calendar-lite')),
            'status' => 401,
        ]);

        return $this->create_event($request, $id);
    }

    public function my(WP_REST_Request $request)
    {
        $limit = $request->get_param('limit');
        if (!$limit) $limit = 12;

        if (!is_numeric($limit))
        {
            return $this->restful->response([
                'data' => new WP_Error(400, esc_html__('Limit parameter must be numeric!', 'modern-events-calendar-lite')),
                'status' => 400,
            ]);
        }

        // Get Current User
        $user = wp_get_current_user();

        // Invalid User
        if (is_wp_error($user)) return $user;

        // Page
        $paged = $request->get_param('paged');
        if (!$paged) $paged = 1;

        // The Query
        $query = new WP_Query([
            'post_type' => $this->getMain()->get_main_post_type(),
            'posts_per_page' => $limit,
            'paged' => $paged,
            'post_status' => ['pending', 'draft', 'future', 'publish'],
            'author' => get_current_user_id(),
        ]);

        $events = [];
        while ($query->have_posts())
        {
            $query->the_post();

            $events[] = [
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'url' => get_the_permalink(),
                'status' => get_post_status(),
            ];
        }

        wp_reset_postdata();

        // Response
        return $this->restful->response([
            'data' => [
                'events' => $events,
                'pagination' => [
                    'current_page' => $paged,
                    'total_pages' => $query->max_num_pages,
                ],
            ],
            'status' => 200,
        ]);
    }

    public function list_taxonomy_entities(WP_REST_Request $request): WP_REST_Response
    {
        $entity = $request->get_param('entity');
        $config = $this->get_taxonomy_entity_config($entity);
        if (!$config) return $this->taxonomy_entity_not_found_response();

        if (!$this->current_user_can_manage_taxonomy($config['taxonomy'], 'assign_terms')) return $this->taxonomy_entity_capability_error('read');

        $limit = (int) $request->get_param('limit');
        if ($limit < 1) $limit = 20;

        $paged = (int) $request->get_param('paged');
        if ($paged < 1) $paged = 1;

        $orderby = sanitize_text_field($request->get_param('orderby') ?: 'name');
        if (!in_array($orderby, ['name', 'slug', 'count', 'term_id'], true)) $orderby = 'name';

        $order = strtoupper(sanitize_text_field($request->get_param('order') ?: 'ASC'));
        if (!in_array($order, ['ASC', 'DESC'], true)) $order = 'ASC';

        $terms = get_terms([
            'taxonomy' => $config['taxonomy'],
            'hide_empty' => (bool) $request->get_param('hide_empty'),
            'number' => $limit,
            'offset' => ($paged - 1) * $limit,
            'search' => sanitize_text_field((string) $request->get_param('search')),
            'orderby' => $orderby,
            'order' => $order,
        ]);

        if (is_wp_error($terms)) return $this->error_response($terms, 400);

        $total = wp_count_terms([
            'taxonomy' => $config['taxonomy'],
            'hide_empty' => (bool) $request->get_param('hide_empty'),
            'search' => sanitize_text_field((string) $request->get_param('search')),
        ]);

        $items = [];
        foreach ($terms as $term)
        {
            $items[] = $this->format_taxonomy_entity($term, $entity);
        }

        return $this->restful->response([
            'data' => [
                $entity => $items,
                'pagination' => [
                    'current_page' => $paged,
                    'per_page' => $limit,
                    'total' => (int) $total,
                    'total_pages' => $limit ? (int) ceil(((int) $total) / $limit) : 1,
                ],
            ],
            'status' => 200,
        ]);
    }

    public function get_taxonomy_entity(WP_REST_Request $request): WP_REST_Response
    {
        $entity = $request->get_param('entity');
        $config = $this->get_taxonomy_entity_config($entity);
        if (!$config) return $this->taxonomy_entity_not_found_response();

        if (!$this->current_user_can_manage_taxonomy($config['taxonomy'], 'assign_terms')) return $this->taxonomy_entity_capability_error('read');

        $term = get_term((int) $request->get_param('id'), $config['taxonomy']);
        if (!$term || is_wp_error($term)) return $this->taxonomy_entity_not_found_response();

        return $this->restful->response([
            'data' => [
                $config['single'] => $this->format_taxonomy_entity($term, $entity),
            ],
            'status' => 200,
        ]);
    }

    public function create_taxonomy_entity(WP_REST_Request $request): WP_REST_Response
    {
        $entity = $request->get_param('entity');
        $config = $this->get_taxonomy_entity_config($entity);
        if (!$config) return $this->taxonomy_entity_not_found_response();

        if (!$this->current_user_can_manage_taxonomy($config['taxonomy'], 'edit_terms')) return $this->taxonomy_entity_capability_error();

        $params = $request->get_params();
        $name = isset($params['name']) ? sanitize_text_field($params['name']) : '';
        if (!trim($name))
        {
            return $this->restful->response([
                'data' => new WP_Error('400', esc_html__('Name field is required!', 'modern-events-calendar-lite')),
                'status' => 400,
            ]);
        }

        $params['name'] = $name;
        $result = $this->insert_taxonomy_entity_term($config, $params);
        if (is_wp_error($result)) return $this->error_response($result, 400);

        $term_id = isset($result['term_id']) ? (int) $result['term_id'] : 0;
        if (!$term_id) return $this->taxonomy_entity_not_found_response();

        $this->save_taxonomy_entity_meta($term_id, $entity, $params);

        $term = get_term($term_id, $config['taxonomy']);
        if (!$term || is_wp_error($term)) return $this->taxonomy_entity_not_found_response();

        return $this->restful->response([
            'data' => [
                'success' => 1,
                $config['single'] => $this->format_taxonomy_entity($term, $entity),
            ],
            'status' => 200,
        ]);
    }

    public function update_taxonomy_entity(WP_REST_Request $request): WP_REST_Response
    {
        $entity = $request->get_param('entity');
        $config = $this->get_taxonomy_entity_config($entity);
        if (!$config) return $this->taxonomy_entity_not_found_response();

        if (!$this->current_user_can_manage_taxonomy($config['taxonomy'], 'edit_terms')) return $this->taxonomy_entity_capability_error();

        $term_id = (int) $request->get_param('id');
        $term = get_term($term_id, $config['taxonomy']);
        if (!$term || is_wp_error($term)) return $this->taxonomy_entity_not_found_response();

        $params = $request->get_params();
        $args = [];

        if (isset($params['name']) && trim((string) $params['name'])) $args['name'] = sanitize_text_field($params['name']);
        if (isset($params['slug'])) $args['slug'] = sanitize_title($params['slug']);
        if (isset($params['description'])) $args['description'] = sanitize_textarea_field($params['description']);
        if ($config['taxonomy'] === 'mec_category' && isset($params['parent']) && is_numeric($params['parent'])) $args['parent'] = (int) $params['parent'];

        if (count($args))
        {
            $result = wp_update_term($term_id, $config['taxonomy'], $args);
            if (is_wp_error($result)) return $this->error_response($result, 400);
        }

        $this->save_taxonomy_entity_meta($term_id, $entity, $params);

        $term = get_term($term_id, $config['taxonomy']);
        if (!$term || is_wp_error($term)) return $this->taxonomy_entity_not_found_response();

        return $this->restful->response([
            'data' => [
                'success' => 1,
                $config['single'] => $this->format_taxonomy_entity($term, $entity),
            ],
            'status' => 200,
        ]);
    }

    public function delete_taxonomy_entity(WP_REST_Request $request): WP_REST_Response
    {
        $entity = $request->get_param('entity');
        $config = $this->get_taxonomy_entity_config($entity);
        if (!$config) return $this->taxonomy_entity_not_found_response();

        if (!$this->current_user_can_manage_taxonomy($config['taxonomy'], 'delete_terms')) return $this->taxonomy_entity_capability_error();

        $term_id = (int) $request->get_param('id');
        $term = get_term($term_id, $config['taxonomy']);
        if (!$term || is_wp_error($term)) return $this->taxonomy_entity_not_found_response();

        $result = wp_delete_term($term_id, $config['taxonomy']);
        if (is_wp_error($result)) return $this->error_response($result, 400);
        if (!$result) return $this->taxonomy_entity_not_found_response();

        return $this->restful->response([
            'data' => [
                'success' => 1,
            ],
            'status' => 200,
        ]);
    }

    public function trash(WP_REST_Request $request): WP_REST_Response
    {
        $id = $request->get_param('id');

        // Current User is not Authorized to Delete this Event
        if (!current_user_can('delete_post', $id)) return $this->restful->response([
            'data' => new WP_Error('401', esc_html__("You're not authorized to trash this event!", 'modern-events-calendar-lite')),
            'status' => 401,
        ]);

        // Event
        $event = get_post($id);

        // Not Found!
        if (!$event || (isset($event->post_type) && $event->post_type !== $this->getMain()->get_main_post_type())) return $this->restful->response([
            'data' => new WP_Error('404', esc_html__('Event not found!', 'modern-events-calendar-lite')),
            'status' => 404,
        ]);

        // Trash
        wp_trash_post($id);

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
            ],
            'status' => 200,
        ]);
    }

    public function delete(WP_REST_Request $request): WP_REST_Response
    {
        $id = $request->get_param('id');

        // Current User is not Authorized to Delete this Event
        if (!current_user_can('delete_post', $id)) return $this->restful->response([
            'data' => new WP_Error('401', esc_html__("You're not authorized to delete this event!", 'modern-events-calendar-lite')),
            'status' => 401,
        ]);

        // Event
        $event = get_post($id);

        // Not Found!
        if (!$event || (isset($event->post_type) && $event->post_type !== $this->getMain()->get_main_post_type())) return $this->restful->response([
            'data' => new WP_Error('404', esc_html__('Event not found!', 'modern-events-calendar-lite')),
            'status' => 404,
        ]);

        // Delete
        wp_delete_post($id, true);

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
            ],
            'status' => 200,
        ]);
    }

    public function custom_fields(): WP_REST_Response
    {
        $fields = $this->getMain()->get_event_fields();

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
                'fields' => $fields,
            ],
            'status' => 200,
        ]);
    }

    public function attendee_fields(): WP_REST_Response
    {
        $fields = $this->getMain()->get_reg_fields();

        if (isset($fields[':i:'])) unset($fields[':i:']);
        if (isset($fields[':fi:'])) unset($fields[':fi:']);

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
                'fields' => $fields,
            ],
            'status' => 200,
        ]);
    }

    public function fixed_fields(): WP_REST_Response
    {
        $fields = $this->getMain()->get_bfixed_fields();

        if (isset($fields[':i:'])) unset($fields[':i:']);
        if (isset($fields[':fi:'])) unset($fields[':fi:']);

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
                'fields' => $fields,
            ],
            'status' => 200,
        ]);
    }

    public function ticket_variations(): WP_REST_Response
    {
        $ticket_variations = $this->getMain()->ticket_variations();

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
                'ticket_variations' => $ticket_variations,
            ],
            'status' => 200,
        ]);
    }

    public function icons(): WP_REST_Response
    {
        $icons = $this->getMain()->icons(
            (isset($this->settings['icons']) && is_array($this->settings['icons']) ? $this->settings['icons'] : [])
        );

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
                'icons' => $icons->list(),
            ],
            'status' => 200,
        ]);
    }

    public function tickets(WP_REST_Request $request): WP_REST_Response
    {
        // Event ID
        $id = $request->get_param('id');

        $today = $request->get_param('occurrence');
        if (!$today) $today = current_time('Y-m-d H:i:s');

        // Tickets
        $tickets = $this->getMain()->get_full_tickets($id);

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
                'tickets' => $tickets,
                'availability' => $this->getBook()->get_tickets_availability($id, strtotime($today)),
            ],
            'status' => 200,
        ]);
    }

    public function fees(WP_REST_Request $request): WP_REST_Response
    {
        // Event ID
        $id = $request->get_param('id');

        // Fees
        $fees = $this->getBook()->get_fees($id);

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
                'fees' => $fees,
            ],
            'status' => 200,
        ]);
    }

    public function weather(WP_REST_Request $request): WP_REST_Response
    {
        // The module is disabled
        if (!isset($this->settings['weather_module_status']) || !$this->settings['weather_module_status'])
        {
            return $this->restful->response([
                'data' => [
                    'success' => 0,
                    'message' => esc_html__('Module is disabled.', 'modern-events-calendar-lite'),
                ],
                'status' => 503,
            ]);
        }

        $visual_crossing = isset($this->settings['weather_module_vs_api_key']) && trim($this->settings['weather_module_vs_api_key']) ? $this->settings['weather_module_vs_api_key'] : '';
        $weather_api = isset($this->settings['weather_module_wa_api_key']) && trim($this->settings['weather_module_wa_api_key']) ? $this->settings['weather_module_wa_api_key'] : '';

        // Main
        $main = $this->getMain();

        // Event ID
        $id = $request->get_param('id');

        // Location ID
        $location_id = $main->get_master_location_id($id);

        // Location
        $location = $main->get_location_data($location_id);

        $lat = $location['latitude'] ?? 0;
        $lng = $location['longitude'] ?? 0;

        // Cannot find the geo point
        if (!$location_id || !$lat || !$lng)
        {
            return $this->restful->response([
                'data' => [
                    'success' => 0,
                    'message' => esc_html__('No location found for this event.', 'modern-events-calendar-lite'),
                ],
                'status' => 404,
            ]);
        }

        $today = $request->get_param('date');
        if (!$today) $today = current_time('Y-m-d H:i:s');

        $weather = [];
        if ($weather_api)
        {
            $response = $main->get_weather_wa($weather_api, $lat, $lng, $today);
            $weather = [
                'icon' => $response['condition']['icon'] ?? '',
                'condition' => $response['condition']['text'] ?? '',
                'temp_c' => $response['temp_c'] ?? '',
                'temp_f' => $response['temp_f'] ?? '',
                'wind_kph' => $response['wind_kph'] ?? '',
                'wind_mph' => $response['wind_mph'] ?? '',
                'humidity' => $response['humidity'] ?? '',
                'feelslike_c' => $response['feelslike_c'] ?? '',
                'feelslike_f' => $response['feelslike_f'] ?? '',
            ];
        }
        else if ($visual_crossing)
        {
            $response = $main->get_weather_visualcrossing($visual_crossing, $lat, $lng, $today);
            $weather = [
                'icon' => $response['icon'] ?? '',
                'condition' => $response['conditions'] ?? '',
                'temp_c' => $response['temp'] ?? '',
                'temp_f' => $main->weather_unit_convert($response['temp'] ?? '', 'C_TO_F'),
                'wind_kph' => $response['windspeed'] ?? '',
                'wind_mph' => $main->weather_unit_convert($response['windspeed'] ?? '', 'KM_TO_M'),
                'humidity' => $response['humidity'] ?? '',
                'visibility_km' => $response['visibility'] ?? '',
                'visibility_m' => $main->weather_unit_convert($response['visibility'] ?? '', 'KM_TO_M'),
            ];
        }

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
                'weather' => $weather,
            ],
            'status' => 200,
        ]);
    }

    public function related_events(WP_REST_Request $request): WP_REST_Response
    {
        // Module Disabled
        if (!isset($this->settings['related_events']) || $this->settings['related_events'] != '1')
        {
            return $this->restful->response([
                'data' => [
                    'success' => 0,
                    'message' => esc_html__('Module is disabled.', 'modern-events-calendar-lite'),
                ],
                'status' => 503,
            ]);
        }

        // Libraries
        $main = $this->getMain();
        $render = $this->getRender();
        $single = new MEC_skin_single();

        // Event ID
        $id = $request->get_param('id');

        $limit = isset($this->settings['related_events_limit']) && trim($this->settings['related_events_limit']) ? $this->settings['related_events_limit'] : 30;

        // Display Expired Events
        $display_expired_events = isset($this->settings['related_events_display_expireds']) && $this->settings['related_events_display_expireds'];

        $now = current_time('timestamp');
        $printed = 0;

        // Events
        $events = [];

        // Query
        $query = $single->get_related_events_query($id);

        if ($query->have_posts())
        {
            while ($query->have_posts())
            {
                if ($printed >= min($limit, 4)) break;
                $query->the_post();

                // Event Repeat Type
                $repeat_type = get_post_meta(get_the_ID(), 'mec_repeat_type', true);

                $occurrence = date('Y-m-d');
                if (!in_array($repeat_type, ['certain_weekdays', 'custom_days', 'weekday', 'weekend', 'advanced']))
                {
                    $new_occurrence = date('Y-m-d', strtotime('-1 day', strtotime($occurrence)));
                    if ($repeat_type === 'monthly' && date('m', strtotime($new_occurrence)) != date('m', strtotime($occurrence))) $new_occurrence = date('Y-m-d', strtotime($occurrence));

                    $occurrence = $new_occurrence;
                }

                $dates = $render->dates(get_the_ID(), null, 5, $occurrence);

                $t = 0;
                do
                {
                    $d = $dates[$t] ?? [];

                    $timestamp = $d['start']['timestamp'] ?? 0;
                    $t++;
                } while (isset($dates[$t]) && $t <= 5 && $timestamp < $now);

                // Don't show Expired Events
                if ($display_expired_events || ($timestamp && $timestamp > $now))
                {
                    $printed += 1;
                    $mec_date = $d['start']['date'] ?? get_post_meta(get_the_ID(), 'mec_start_date', true);
                    $date = $main->date_i18n(get_option('date_format'), strtotime($mec_date));

                    $event_link = $main->get_event_date_permalink(get_the_permalink(), $mec_date);

                    // Custom Link
                    $read_more = get_post_meta(get_the_ID(), 'mec_read_more', true);
                    $read_more_occ_url = MEC_feature_occurrences::param(get_the_ID(), $timestamp, 'read_more', $read_more);

                    if ($read_more_occ_url && filter_var($read_more_occ_url, FILTER_VALIDATE_URL)) $event_link = $read_more_occ_url;

                    $events[] = [
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'url' => $event_link,
                        'timestamp' => $timestamp,
                        'date' => $date,
                    ];
                }
            }
        }

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
                'related_events' => $events,
            ],
            'status' => 200,
        ]);
    }

    public function next_previous_events(WP_REST_Request $request): WP_REST_Response
    {
        // Module Disabled
        if (!isset($this->settings['next_previous_events']) || $this->settings['next_previous_events'] != '1')
        {
            return $this->restful->response([
                'data' => [
                    'success' => 0,
                    'message' => esc_html__('Module is disabled.', 'modern-events-calendar-lite'),
                ],
                'status' => 503,
            ]);
        }

        // Event ID
        $id = $request->get_param('id');

        // Libraries
        $main = $this->getMain();
        $single = new MEC_skin_single();

        list($p, $n) = $single->get_next_prev_query($id);

        $next = [];
        $previous = [];

        // Previous
        if (is_array($p))
        {
            $p_url = $main->get_event_date_permalink(get_permalink($p['post_id']), date('Y-m-d', $p['tstart']));
            $previous = [
                'id' => $p['post_id'],
                'title' => get_the_title($p['post_id']),
                'url' => $p_url,
            ];
        }

        // Next
        if (is_array($n))
        {
            $n_url = $main->get_event_date_permalink(get_permalink($n['post_id']), date('Y-m-d', $n['tstart']));
            $next = [
                'id' => $n['post_id'],
                'title' => get_the_title($n['post_id']),
                'url' => $n_url,
            ];
        }

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
                'next' => $next,
                'previous' => $previous,
            ],
            'status' => 200,
        ]);
    }

    public function next_occurrences(WP_REST_Request $request): WP_REST_Response
    {
        // Libraries
        $single = new MEC_skin_single();

        // Event ID
        $id = $request->get_param('id');

        $events = $single->get_event_mec($id);
        $event = $events[0] ?? null;

        // Response
        return $this->restful->response([
            'data' => [
                'success' => 1,
                'occurrences' => $event->dates ?? [],
            ],
            'status' => 200,
        ]);
    }
}

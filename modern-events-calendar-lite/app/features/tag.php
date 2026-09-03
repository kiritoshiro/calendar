<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * @author Webnus <info@webnus.net>
 */
class MEC_feature_tag extends MEC_base
{
    public $factory;
    public $main;
    public $settings;
    public $PT;
    public $taxonomy;

    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct()
    {
        // Import MEC Factory
        $this->factory = $this->getFactory();

        // Main Library
        $this->main = $this->getMain();

        // Settings
        $this->settings = $this->main->get_settings();

        // Post Type
        $this->PT = $this->main->get_main_post_type();

        // Taxonomy
        $this->taxonomy = $this->settings['tag_method'] ?? 'post_tag';
    }

    /**
     * Initialize search feature
     * @author Webnus <info@webnus.net>
     */
    public function init()
    {
        $this->factory->action('init', [$this, 'register_taxonomy'], 100);
        $this->factory->filter('mec_taxonomy_tag', [$this, 'taxonomy']);

        // Toggle Tag Method
        $this->factory->action('mec_tag_method_changed', [$this, 'toggle'], 10, 2);
    }

    public function register_taxonomy($taxonomy = null)
    {
        if (!$taxonomy) $taxonomy = $this->taxonomy;

        if ($taxonomy === 'post_tag') register_taxonomy_for_object_type('post_tag', $this->PT);
        else
        {
            $singular_label = $this->main->m('taxonomy_tag', esc_html__('Tag', 'modern-events-calendar-lite'));
            $plural_label = $this->main->m('taxonomy_tags', esc_html__('Tags', 'modern-events-calendar-lite'));

            $tag_args = apply_filters(
                'mec_register_taxonomy_args',
                [
                    'label' => $plural_label,
                    'labels' => [
                        'name' => $plural_label,
                        'singular_name' => $singular_label,
                        'all_items' => sprintf(esc_html__('All %s', 'modern-events-calendar-lite'), $plural_label),
                        'edit_item' => sprintf(esc_html__('Edit %s', 'modern-events-calendar-lite'), $singular_label),
                        'view_item' => sprintf(esc_html__('View %s', 'modern-events-calendar-lite'), $singular_label),
                        'update_item' => sprintf(esc_html__('Update %s', 'modern-events-calendar-lite'), $singular_label),
                        'add_new_item' => sprintf(esc_html__('Add New %s', 'modern-events-calendar-lite'), $singular_label),
                        'new_item_name' => sprintf(esc_html__('New %s Name', 'modern-events-calendar-lite'), $singular_label),
                        'popular_items' => sprintf(esc_html__('Popular %s', 'modern-events-calendar-lite'), $plural_label),
                        'search_items' => sprintf(esc_html__('Search %s', 'modern-events-calendar-lite'), $plural_label),
                        'back_to_items' => sprintf(esc_html__('← Back to %s', 'modern-events-calendar-lite'), $plural_label),
                        'not_found' => sprintf(esc_html__('no %s found.', 'modern-events-calendar-lite'), strtolower($plural_label)),
                    ],
                    'rewrite' => ['slug' => 'events-tag'],
                    'public' => true,
                    'show_ui' => true,
                    'show_in_rest' => true,
                    'hierarchical' => false,
                ],
                'mec_tag'
            );
            register_taxonomy(
                'mec_tag',
                $this->PT,
                $tag_args
            );

            register_taxonomy_for_object_type('mec_tag', $this->PT);
        }
    }

    public function taxonomy($taxonomy)
    {
        return $this->taxonomy;
    }

    public function toggle($new_method, $old_method)
    {
        // Register New Taxonomy
        $this->register_taxonomy($new_method);

        $events = get_posts([
            'post_type' => $this->PT,
            'post_status' => ['publish', 'pending', 'draft', 'future', 'private', 'trash'],
            'numberposts' => -1,
        ]);

        foreach ($events as $event)
        {
            $old_terms = get_the_terms($event, $old_method);
            if (!is_array($old_terms) || !count($old_terms)) continue;

            $new_term_ids = [];
            foreach ($old_terms as $old_term)
            {
                $term = wp_create_term($old_term->name, $new_method);
                $new_term_id = (int) (is_array($term) ? $term['term_id'] : (is_numeric($term) ? $term : false));

                if (!$new_term_id) continue;
                $new_term_ids[] = $new_term_id;
            }

            if (count($new_term_ids)) wp_set_object_terms($event->ID, $new_term_ids, $new_method);
            wp_delete_object_term_relationships($event->ID, $old_method);
        }
    }
}

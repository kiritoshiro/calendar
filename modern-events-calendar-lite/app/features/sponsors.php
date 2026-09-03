<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC sponsors class.
 * @author Webnus <info@webnus.net>
 */
class MEC_feature_sponsors extends MEC_base
{
    public $factory;
    public $main;
    public $settings;

    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct()
    {
        // Import MEC Factory
        $this->factory = $this->getFactory();

        // Import MEC Main
        $this->main = $this->getMain();

        // MEC Settings
        $this->settings = $this->main->get_settings();
    }

    /**
     * Initialize Sponsors feature
     * @author Webnus <info@webnus.net>
     */
    public function init()
    {
        // Feature is not included in PRO
        if (!$this->getPRO()) return;

        // Sponsors Feature is Disabled
        if (!isset($this->settings['sponsors_status']) or (isset($this->settings['sponsors_status']) and !$this->settings['sponsors_status'])) return;

        $this->factory->action('init', [$this, 'register_taxonomy'], 25);
        $this->factory->action('mec_sponsor_edit_form_fields', [$this, 'edit_form']);
        $this->factory->action('mec_sponsor_add_form_fields', [$this, 'add_form']);
        $this->factory->action('edited_mec_sponsor', [$this, 'save_metadata']);
        $this->factory->action('created_mec_sponsor', [$this, 'save_metadata']);

        $this->factory->filter('post_edit_category_parent_dropdown_args', [$this, 'hide_parent_dropdown']);

        $this->factory->action('wp_ajax_mec_sponsor_adding', [$this, 'fes_sponsor_adding']);
        $this->factory->action('wp_ajax_nopriv_mec_sponsor_adding', [$this, 'fes_sponsor_adding']);
    }

    /**
     * Registers Sponsors taxonomy
     * @author Webnus <info@webnus.net>
     */
    public function register_taxonomy()
    {
        $singular_label = $this->main->m('taxonomy_sponsor', esc_html__('Sponsor', 'modern-events-calendar-lite'));
        $plural_label = $this->main->m('taxonomy_sponsors', esc_html__('Sponsors', 'modern-events-calendar-lite'));

        $sponsor_args = apply_filters(
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
                'rewrite' => ['slug' => 'events-sponsor'],
                'public' => false,
                'show_ui' => true,
                'show_in_rest' => true,
                'hierarchical' => false,
                'meta_box_cb' => function_exists('wp_doing_ajax') && wp_doing_ajax() ? '' : 'post_categories_meta_box',
            ],
            'mec_sponsor'
        );
        register_taxonomy(
            'mec_sponsor',
            $this->main->get_main_post_type(),
            $sponsor_args
        );

        register_taxonomy_for_object_type('mec_sponsor', $this->main->get_main_post_type());
    }

    /**
     * Show edit form of Sponsors taxonomy
     * @param object $term
     * @author Webnus <info@webnus.net>
     */
    public function edit_form($term)
    {
        $link = get_metadata('term', $term->term_id, 'link', true);
        $logo = get_metadata('term', $term->term_id, 'logo', true);
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="mec_link"><?php esc_html_e('Link', 'modern-events-calendar-lite'); ?></label>
            </th>
            <td>
                <input type="url" placeholder="<?php esc_attr_e('Insert URL of Sponsor', 'modern-events-calendar-lite'); ?>" name="link"
                       id="mec_link" value="<?php echo esc_attr($link); ?>"/>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="mec_thumbnail_button"><?php esc_html_e('Logo', 'modern-events-calendar-lite'); ?></label>
            </th>
            <td>
                <div
                    id="mec_thumbnail_img"><?php if (trim($logo) != '') echo '<img src="' . esc_url($logo) . '" />'; ?></div>
                <input type="hidden" name="logo" id="mec_thumbnail" value="<?php echo esc_attr($logo); ?>"/>
                <button type="button" class="mec_upload_image_button button"
                        id="mec_thumbnail_button"><?php echo esc_html__('Upload/Add image', 'modern-events-calendar-lite'); ?></button>
                <button type="button"
                        class="mec_remove_image_button button <?php echo(!trim($logo) ? 'mec-util-hidden' : ''); ?>"><?php echo esc_html__('Remove', 'modern-events-calendar-lite'); ?></button>
            </td>
        </tr>
        <?php do_action('mec_edit_sponsor_extra_fields', $term); ?>
        <?php
    }

    /**
     * Show add form of Sponsors taxonomy
     * @author Webnus <info@webnus.net>
     */
    public function add_form()
    {
        ?>
        <div class="form-field">
            <label for="mec_link"><?php esc_html_e('Link', 'modern-events-calendar-lite'); ?></label>
            <input type="url" name="link" placeholder="<?php esc_attr_e('Insert URL of Sponsor.', 'modern-events-calendar-lite'); ?>"
                   id="mec_link" value=""/>
        </div>
        <div class="form-field">
            <label for="mec_thumbnail_button"><?php esc_html_e('Logo', 'modern-events-calendar-lite'); ?></label>
            <div id="mec_thumbnail_img"></div>
            <input type="hidden" name="logo" id="mec_thumbnail" value=""/>
            <button type="button" class="mec_upload_image_button button"
                    id="mec_thumbnail_button"><?php echo esc_html__('Upload/Add image', 'modern-events-calendar-lite'); ?></button>
            <button type="button"
                    class="mec_remove_image_button button mec-util-hidden"><?php echo esc_html__('Remove', 'modern-events-calendar-lite'); ?></button>
        </div>
        <?php do_action('mec_add_sponsor_extra_fields'); ?>
        <?php
    }

    /**
     * Save meta data of Sponsors taxonomy
     * @param int $term_id
     * @author Webnus <info@webnus.net>
     */
    public function save_metadata($term_id)
    {
        // Quick Edit
        if (!isset($_POST['link'])) return;

        $link = trim($_POST['link']) ? esc_url($_POST['link']) : '';
        $logo = isset($_POST['logo']) && trim($_POST['logo']) ? esc_url($_POST['logo']) : '';

        update_term_meta($term_id, 'link', $link);
        update_term_meta($term_id, 'logo', $logo);

        do_action('mec_save_sponsor_extra_fields', $term_id);
    }

    public function hide_parent_dropdown($args)
    {
        if ('mec_sponsor' == $args['taxonomy']) $args['echo'] = false;
        return $args;
    }

    /**
     * Adding new sponsor
     * @return void
     * @author Webnus <info@webnus.net>
     */
    public function fes_sponsor_adding()
    {
        if (!class_exists('MEC_feature_fes')) MEC::import('app.features.fes');
        $fes = class_exists('MEC_feature_fes') ? new MEC_feature_fes() : null;

        if (!$fes || !$fes->validate_fes_term_creation_request())
        {
            wp_die(esc_html__('Sorry! You do not have permission to add sponsors.', 'modern-events-calendar-lite'), 403);
        }

        $key = isset($_REQUEST['key']) ? sanitize_text_field($_REQUEST['key']) : null;
        $key = intval($key);

        if (isset($_REQUEST['content']))
        {
            $content = sanitize_text_field($_REQUEST['content']);
            $content = wp_strip_all_tags($content);
            $content = sanitize_text_field($content);

            if (!trim($content))
            {
                echo '<p class="mec-error" id="mec-sponsor-error-' . esc_attr($key) . '">' . sprintf(esc_html__('Sorry, You must insert %s name!', 'modern-events-calendar-lite'), strtolower(\MEC\Base::get_main()->m('taxonomy_sponsor', esc_html__('sponsor', 'modern-events-calendar-lite')))) . '</p>';
                exit;
            }

            if (term_exists($content, 'mec_sponsor'))
            {
                echo '<p class="mec-error" id="mec-sponsor-error-' . esc_attr($key) . '">' . esc_html__("Sorry, $content already exists!", 'modern-events-calendar-lite') . '</p>';
                exit;
            }

            wp_insert_term(trim($content), 'mec_sponsor');
        }
        else if (isset($_REQUEST['name']))
        {
            $name = sanitize_text_field($_REQUEST['name']);
            $url = isset($_REQUEST['url']) ? esc_url($_REQUEST['url']) : '';
            $image = isset($_REQUEST['image']) ? esc_url($_REQUEST['image']) : '';

            if (!trim($name))
            {
                echo '<p class="mec-error" id="mec-sponsor-error-' . esc_attr($key) . '">' . sprintf(esc_html__('Sorry, You must insert %s name!', 'modern-events-calendar-lite'), strtolower(\MEC\Base::get_main()->m('taxonomy_sponsor', esc_html__('sponsor', 'modern-events-calendar-lite')))) . '</p>';
                exit;
            }

            if (term_exists($name, 'mec_sponsor'))
            {
                echo '<p class="mec-error" id="mec-sponsor-error-' . esc_attr($key) . '">' . esc_html__("Sorry, $name already exists!", 'modern-events-calendar-lite') . '</p>';
                exit;
            }

            $sponsor = wp_insert_term(trim($name), 'mec_sponsor');
            if (is_array($sponsor))
            {
                $sponsor_id = $sponsor['term_id'];

                update_term_meta($sponsor_id, 'link', $url);
                update_term_meta($sponsor_id, 'logo', $image);
            }
        }

        $sponsors = '';
        $sponsor_terms = get_terms(['taxonomy' => 'mec_sponsor', 'hide_empty' => false]);
        foreach ($sponsor_terms as $sponsor_term)
        {
            $sponsors .= '<label for="mec_fes_sponsors' . esc_attr($sponsor_term->term_id) . '">
                <input type="checkbox" name="mec[sponsors][' . esc_attr($sponsor_term->term_id) . ']" id="mec_fes_sponsors' . esc_attr($sponsor_term->term_id) . '" value="1">
                ' . esc_html($sponsor_term->name) . '
            </label>';
        }

        echo MEC_kses::form($sponsors);
        exit;
    }
}

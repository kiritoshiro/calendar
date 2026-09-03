<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC speakers class.
 * @author Webnus <info@webnus.net>
 */
class MEC_feature_speakers extends MEC_base
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
     * Initialize speakers feature
     * @author Webnus <info@webnus.net>
     */
    public function init()
    {
        // Speakers Feature is Disabled
        if (!isset($this->settings['speakers_status']) || !$this->settings['speakers_status']) return;

        $this->factory->action('init', [$this, 'register_taxonomy'], 25);
        $this->factory->action('mec_speaker_edit_form_fields', [$this, 'edit_form']);
        $this->factory->action('mec_speaker_add_form_fields', [$this, 'add_form']);
        $this->factory->action('edited_mec_speaker', [$this, 'save_metadata']);
        $this->factory->action('created_mec_speaker', [$this, 'save_metadata']);

        $this->factory->action('wp_ajax_mec_speaker_adding', [$this, 'fes_speaker_adding']);
        $this->factory->action('wp_ajax_nopriv_mec_speaker_adding', [$this, 'fes_speaker_adding']);
        $this->factory->action('current_screen', [$this, 'show_notices']);

        $this->factory->filter('manage_edit-mec_speaker_columns', [$this, 'filter_columns']);
        $this->factory->filter('manage_mec_speaker_custom_column', [$this, 'filter_columns_content'], 10, 3);

        $this->factory->action('current_screen', [$this, 'update_speakers_list_admin']);
        $this->factory->action('mec_fes_form_footer', [$this, 'update_speakers_list']);

        $this->factory->action('wp_ajax_update_speakers_list', [$this, 'get_speakers']);
        $this->factory->action('wp_ajax_nopriv_update_speakers_list', [$this, 'get_speakers']);

        $this->factory->filter('post_edit_category_parent_dropdown_args', [$this, 'hide_parent_dropdown']);
    }

    /**
     * Registers speaker taxonomy
     * @author Webnus <info@webnus.net>
     */
    public function register_taxonomy()
    {
        $singular_label = $this->main->m('taxonomy_speaker', esc_html__('Speaker', 'modern-events-calendar-lite'));
        $plural_label = $this->main->m('taxonomy_speakers', esc_html__('Speakers', 'modern-events-calendar-lite'));
        $speaker_args = apply_filters(
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
                'rewrite' => ['slug' => 'events-speaker'],
                'public' => false,
                'show_ui' => true,
                'show_in_rest' => true,
                'hierarchical' => false,
                'meta_box_cb' => (isset($_POST['_inline_edit']) ? '' : 'post_categories_meta_box'),
            ],
            'mec_speaker'
        );
        register_taxonomy(
            'mec_speaker',
            $this->main->get_main_post_type(),
            $speaker_args
        );

        register_taxonomy_for_object_type('mec_speaker', $this->main->get_main_post_type());
    }

    /**
     * Show edit form of speaker taxonomy
     * @param object $term
     * @author Webnus <info@webnus.net>
     */
    public function edit_form($term)
    {
        $job_title = get_metadata('term', $term->term_id, 'job_title', true);
        $tel = get_metadata('term', $term->term_id, 'tel', true);
        $email = get_metadata('term', $term->term_id, 'email', true);
        $website = get_metadata('term', $term->term_id, 'website', true);
        $index = get_metadata('term', $term->term_id, 'mec_index', true);
        $facebook = get_metadata('term', $term->term_id, 'facebook', true);
        $type = get_metadata('term', $term->term_id, 'type', true);
        $instagram = get_metadata('term', $term->term_id, 'instagram', true);
        $linkedin = get_metadata('term', $term->term_id, 'linkedin', true);
        $twitter = get_metadata('term', $term->term_id, 'twitter', true);
        $thumbnail = get_metadata('term', $term->term_id, 'thumbnail', true);
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="mec_type"><?php esc_html_e('Type', 'modern-events-calendar-lite'); ?></label>
            </th>
            <td>
                <select name="type" id="mec_type">
                    <option
                        value="person" <?php echo $type === 'person' ? 'selected' : ''; ?>><?php esc_html_e('Person', 'modern-events-calendar-lite'); ?></option>
                    <option
                        value="group" <?php echo $type === 'group' ? 'selected' : ''; ?>><?php esc_html_e('Group', 'modern-events-calendar-lite'); ?></option>
                </select>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="mec_job_title"><?php esc_html_e('Job Title', 'modern-events-calendar-lite'); ?></label>
            </th>
            <td>
                <input type="text" placeholder="<?php esc_attr_e('Insert speaker job title.', 'modern-events-calendar-lite'); ?>"
                       name="job_title" id="mec_job_title" value="<?php echo esc_attr($job_title); ?>"/>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="mec_tel"><?php esc_html_e('Tel', 'modern-events-calendar-lite'); ?></label>
            </th>
            <td>
                <input type="text" placeholder="<?php esc_attr_e('Insert speaker phone number.', 'modern-events-calendar-lite'); ?>" name="tel"
                       id="mec_tel" value="<?php echo esc_attr($tel); ?>"/>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="mec_email"><?php esc_html_e('Email', 'modern-events-calendar-lite'); ?></label>
            </th>
            <td>
                <input type="text" placeholder="<?php esc_attr_e('Insert speaker email address.', 'modern-events-calendar-lite'); ?>"
                       name="email" id="mec_email" value="<?php echo esc_attr($email); ?>"/>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="mec_website"><?php esc_html_e('Website', 'modern-events-calendar-lite'); ?></label>
            </th>
            <td>
                <input type="text" placeholder="<?php esc_attr_e('Insert URL of Website', 'modern-events-calendar-lite'); ?>" name="website"
                       id="mec_website" value="<?php echo esc_attr($website); ?>"/>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="mec_index"><?php esc_html_e('Index', 'modern-events-calendar-lite'); ?></label>
            </th>
            <td>
                <input type="number" placeholder="<?php esc_attr_e('Index. Used for sorting.', 'modern-events-calendar-lite'); ?>"
                       name="mec_index" id="mec_index" value="<?php echo esc_attr($index); ?>" min="0" step="0.01"/>
                <p class="description"><?php esc_html_e('Lower numbers appears first.'); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="mec_facebook"><?php esc_html_e('Facebook Page', 'modern-events-calendar-lite'); ?></label>
            </th>
            <td>
                <input type="text" placeholder="<?php esc_attr_e('Insert URL of Facebook Page', 'modern-events-calendar-lite'); ?>"
                       name="facebook" id="mec_facebook" value="<?php echo esc_attr($facebook); ?>"/>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="mec_instagram"><?php esc_html_e('Instagram', 'modern-events-calendar-lite'); ?></label>
            </th>
            <td>
                <input type="text" placeholder="<?php esc_attr_e('Insert URL of Instagram', 'modern-events-calendar-lite'); ?>" name="instagram"
                       id="mec_instagram" value="<?php echo esc_attr($instagram); ?>"/>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="mec_linkedin"><?php esc_html_e('LinkedIn', 'modern-events-calendar-lite'); ?></label>
            </th>
            <td>
                <input type="text" placeholder="<?php esc_attr_e('Insert URL of LinkedIn', 'modern-events-calendar-lite'); ?>" name="linkedin"
                       id="mec_linkedin" value="<?php echo esc_attr($linkedin); ?>"/>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="mec_twitter"><?php esc_html_e('Twitter Page', 'modern-events-calendar-lite'); ?></label>
            </th>
            <td>
                <input type="text" placeholder="<?php esc_attr_e('Insert URL of Twitter Page', 'modern-events-calendar-lite'); ?>"
                       name="twitter" id="mec_twitter" value="<?php echo esc_attr($twitter); ?>"/>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="mec_thumbnail_button"><?php esc_html_e('Thumbnail', 'modern-events-calendar-lite'); ?></label>
            </th>
            <td>
                <div
                    id="mec_thumbnail_img"><?php if (trim($thumbnail) != '') echo '<img src="' . esc_url($thumbnail) . '" />'; ?></div>
                <input type="hidden" name="thumbnail" id="mec_thumbnail" value="<?php echo esc_attr($thumbnail); ?>"/>
                <button type="button" class="mec_upload_image_button button"
                        id="mec_thumbnail_button"><?php echo esc_html__('Upload/Add image', 'modern-events-calendar-lite'); ?></button>
                <button type="button"
                        class="mec_remove_image_button button <?php echo(!trim($thumbnail) ? 'mec-util-hidden' : ''); ?>"><?php echo esc_html__('Remove', 'modern-events-calendar-lite'); ?></button>
            </td>
        </tr>
        <?php do_action('mec_edit_speaker_extra_fields', $term); ?>
        <?php
    }

    /**
     * Show add form of speaker taxonomy
     * @author Webnus <info@webnus.net>
     */
    public function add_form()
    {
        ?>
        <div class="form-field">
            <label for="mec_type"><?php esc_html_e('Type', 'modern-events-calendar-lite'); ?></label>
            <select name="type" id="mec_type">
                <option value="person"><?php esc_html_e('Person', 'modern-events-calendar-lite'); ?></option>
                <option value="group"><?php esc_html_e('Group', 'modern-events-calendar-lite'); ?></option>
            </select>
        </div>
        <div class="form-field">
            <label for="mec_job_title"><?php esc_html_e('Job Title', 'modern-events-calendar-lite'); ?></label>
            <input type="text" name="job_title" placeholder="<?php esc_attr_e('Insert speaker job title.', 'modern-events-calendar-lite'); ?>"
                   id="mec_job_title" value=""/>
        </div>
        <div class="form-field">
            <label for="mec_tel"><?php esc_html_e('Tel', 'modern-events-calendar-lite'); ?></label>
            <input type="text" name="tel" placeholder="<?php esc_attr_e('Insert speaker phone number.', 'modern-events-calendar-lite'); ?>"
                   id="mec_tel" value=""/>
        </div>
        <div class="form-field">
            <label for="mec_email"><?php esc_html_e('Email', 'modern-events-calendar-lite'); ?></label>
            <input type="text" name="email" placeholder="<?php esc_attr_e('Insert speaker email address.', 'modern-events-calendar-lite'); ?>"
                   id="mec_email" value=""/>
        </div>
        <div class="form-field">
            <label for="mec_website"><?php esc_html_e('Website', 'modern-events-calendar-lite'); ?></label>
            <input type="text" name="website" placeholder="<?php esc_attr_e('Insert URL of Website', 'modern-events-calendar-lite'); ?>"
                   id="mec_website" value=""/>
        </div>
        <div class="form-field">
            <label for="mec_index"><?php esc_html_e('Index', 'modern-events-calendar-lite'); ?></label>
            <input type="number" name="mec_index" placeholder="<?php esc_attr_e('Index. Used for sorting.', 'modern-events-calendar-lite'); ?>"
                   id="mec_index" value="99" min="1" step="0.01"/>
            <p class="description"><?php esc_html_e('Lower numbers appears first.'); ?></p>
        </div>
        <div class="form-field">
            <label for="mec_facebook"><?php esc_html_e('Facebook Page', 'modern-events-calendar-lite'); ?></label>
            <input type="text" name="facebook" placeholder="<?php esc_attr_e('Insert URL of Facebook Page', 'modern-events-calendar-lite'); ?>"
                   id="mec_facebook" value=""/>
        </div>
        <div class="form-field">
            <label for="mec_instagram"><?php esc_html_e('Instagram', 'modern-events-calendar-lite'); ?></label>
            <input type="text" name="instagram" placeholder="<?php esc_attr_e('Insert URL of Instagram', 'modern-events-calendar-lite'); ?>"
                   id="mec_instagram" value=""/>
        </div>
        <div class="form-field">
            <label for="mec_linkedin"><?php esc_html_e('LinkedIn', 'modern-events-calendar-lite'); ?></label>
            <input type="text" name="linkedin" placeholder="<?php esc_attr_e('Insert URL of linkedin', 'modern-events-calendar-lite'); ?>"
                   id="mec_linkedin" value=""/>
        </div>
        <div class="form-field">
            <label for="mec_twitter"><?php esc_html_e('Twitter Page', 'modern-events-calendar-lite'); ?></label>
            <input type="text" name="twitter" placeholder="<?php esc_attr_e('Insert URL of Twitter Page', 'modern-events-calendar-lite'); ?>"
                   id="mec_twitter" value=""/>
        </div>
        <div class="form-field">
            <label for="mec_thumbnail_button"><?php esc_html_e('Thumbnail', 'modern-events-calendar-lite'); ?></label>
            <div id="mec_thumbnail_img"></div>
            <input type="hidden" name="thumbnail" id="mec_thumbnail" value=""/>
            <button type="button" class="mec_upload_image_button button"
                    id="mec_thumbnail_button"><?php echo esc_html__('Upload/Add image', 'modern-events-calendar-lite'); ?></button>
            <button type="button"
                    class="mec_remove_image_button button mec-util-hidden"><?php echo esc_html__('Remove', 'modern-events-calendar-lite'); ?></button>
        </div>
        <?php do_action('mec_add_speaker_extra_fields'); ?>
        <?php
    }

    /**
     * Save meta data of speaker taxonomy
     * @param int $term_id
     * @author Webnus <info@webnus.net>
     */
    public function save_metadata($term_id)
    {
        // Quick Edit
        if (!isset($_POST['job_title'])) return;

        $job_title = sanitize_text_field($_POST['job_title']);
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'person';
        $tel = isset($_POST['tel']) ? sanitize_text_field($_POST['tel']) : '';
        $email = isset($_POST['email']) ? sanitize_text_field($_POST['email']) : '';
        $website = (isset($_POST['website']) and trim($_POST['website'])) ? esc_url($_POST['website']) : '';
        $index = (isset($_POST['mec_index']) and trim($_POST['mec_index'])) ? sanitize_text_field($_POST['mec_index']) : 99;
        $facebook = (isset($_POST['facebook']) and trim($_POST['facebook'])) ? esc_url($_POST['facebook']) : '';
        $twitter = (isset($_POST['twitter']) and trim($_POST['twitter'])) ? esc_url($_POST['twitter']) : '';
        $instagram = (isset($_POST['instagram']) and trim($_POST['instagram'])) ? esc_url($_POST['instagram']) : '';
        $linkedin = (isset($_POST['linkedin']) and trim($_POST['linkedin'])) ? esc_url($_POST['linkedin']) : '';
        $thumbnail = isset($_POST['thumbnail']) ? sanitize_text_field($_POST['thumbnail']) : '';

        update_term_meta($term_id, 'type', $type);
        update_term_meta($term_id, 'job_title', $job_title);
        update_term_meta($term_id, 'tel', $tel);
        update_term_meta($term_id, 'email', $email);
        update_term_meta($term_id, 'website', $website);
        update_term_meta($term_id, 'mec_index', $index);
        update_term_meta($term_id, 'facebook', $facebook);
        update_term_meta($term_id, 'twitter', $twitter);
        update_term_meta($term_id, 'instagram', $instagram);
        update_term_meta($term_id, 'linkedin', $linkedin);
        update_term_meta($term_id, 'thumbnail', $thumbnail);

        do_action('mec_save_speaker_extra_fields', $term_id);
    }

    /**
     * Filter columns of speaker taxonomy
     * @param array $columns
     * @return array
     * @author Webnus <info@webnus.net>
     */
    public function filter_columns($columns)
    {
        unset($columns['name']);
        unset($columns['slug']);
        unset($columns['description']);
        unset($columns['posts']);

        $columns['id'] = esc_html__('ID', 'modern-events-calendar-lite');
        $columns['name'] = $this->main->m('taxonomy_speaker', esc_html__('Speaker', 'modern-events-calendar-lite'));
        $columns['job_title'] = esc_html__('Job Title', 'modern-events-calendar-lite');
        $columns['tel'] = esc_html__('Tel', 'modern-events-calendar-lite');
        $columns['posts'] = esc_html__('Count', 'modern-events-calendar-lite');

        return apply_filters('speaker_filter_column', $columns);
    }

    /**
     * Filter content of speaker taxonomy columns
     * @param string $content
     * @param string $column_name
     * @param int $term_id
     * @return string
     * @author Webnus <info@webnus.net>
     */
    public function filter_columns_content($content, $column_name, $term_id)
    {
        switch ($column_name)
        {
            case 'id':

                $content = $term_id;
                break;

            case 'tel':

                $content = get_metadata('term', $term_id, 'tel', true);

                break;

            case 'job_title':

                $content = get_metadata('term', $term_id, 'job_title', true);

                break;

            default:
                break;
        }

        return apply_filters('speaker_filter_column_content', $content, $column_name, $term_id);
    }

    /**
     * Adding new speaker
     * @return void
     * @author Webnus <info@webnus.net>
     */
    public function fes_speaker_adding()
    {
        if (!class_exists('MEC_feature_fes')) MEC::import('app.features.fes');
        $fes = class_exists('MEC_feature_fes') ? new MEC_feature_fes() : null;

        if (!$fes || !$fes->validate_fes_term_creation_request())
        {
            wp_die(esc_html__('Sorry! You do not have permission to add speakers.', 'modern-events-calendar-lite'), 403);
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
                echo '<p class="mec-error" id="mec-speaker-error-' . esc_attr($key) . '">' . sprintf(esc_html__('Sorry, You must insert %s name!', 'modern-events-calendar-lite'), strtolower(\MEC\Base::get_main()->m('taxonomy_speaker', esc_html__('speaker', 'modern-events-calendar-lite')))) . '</p>';
                exit;
            }

            if (term_exists($content, 'mec_speaker'))
            {
                echo '<p class="mec-error" id="mec-speaker-error-' . esc_attr($key) . '">' . esc_html__("Sorry, $content already exists!", 'modern-events-calendar-lite') . '</p>';
                exit;
            }

            wp_insert_term(trim($content), 'mec_speaker');
        }
        else if (isset($_REQUEST['name']))
        {
            $name = sanitize_text_field($_REQUEST['name']);
            $type = isset($_REQUEST['type']) ? sanitize_text_field($_REQUEST['type']) : 'person';
            $job_title = isset($_REQUEST['job_title']) ? sanitize_text_field($_REQUEST['job_title']) : '';
            $tel = isset($_REQUEST['tel']) ? sanitize_text_field($_REQUEST['tel']) : '';
            $email = isset($_REQUEST['email']) ? sanitize_text_field($_REQUEST['email']) : '';
            $website = isset($_REQUEST['website']) ? esc_url($_REQUEST['website']) : '';
            $facebook = isset($_REQUEST['facebook']) ? esc_url($_REQUEST['facebook']) : '';
            $instagram = isset($_REQUEST['instagram']) ? esc_url($_REQUEST['instagram']) : '';
            $linkedin = isset($_REQUEST['linkedin']) ? esc_url($_REQUEST['linkedin']) : '';
            $twitter = isset($_REQUEST['twitter']) ? esc_url($_REQUEST['twitter']) : '';
            $image = isset($_REQUEST['image']) ? esc_url($_REQUEST['image']) : '';

            if (!trim($name))
            {
                echo '<p class="mec-error" id="mec-speaker-error-' . esc_attr($key) . '">' . sprintf(esc_html__('Sorry, You must insert %s name!', 'modern-events-calendar-lite'), strtolower(\MEC\Base::get_main()->m('taxonomy_speaker', esc_html__('speaker', 'modern-events-calendar-lite')))) . '</p>';
                exit;
            }

            if (term_exists($name, 'mec_speaker'))
            {
                echo '<p class="mec-error" id="mec-speaker-error-' . esc_attr($key) . '">' . esc_html__("Sorry, $name already exists!", 'modern-events-calendar-lite') . '</p>';
                exit;
            }

            $speaker = wp_insert_term(trim($name), 'mec_speaker');
            if (is_array($speaker))
            {
                $speaker_id = $speaker['term_id'];

                update_term_meta($speaker_id, 'type', $type);
                update_term_meta($speaker_id, 'job_title', $job_title);
                update_term_meta($speaker_id, 'tel', $tel);
                update_term_meta($speaker_id, 'email', $email);
                update_term_meta($speaker_id, 'website', $website);
                update_term_meta($speaker_id, 'mec_index', 99);
                update_term_meta($speaker_id, 'facebook', $facebook);
                update_term_meta($speaker_id, 'twitter', $twitter);
                update_term_meta($speaker_id, 'instagram', $instagram);
                update_term_meta($speaker_id, 'linkedin', $linkedin);
                update_term_meta($speaker_id, 'thumbnail', $image);
            }
        }

        $speakers = '';
        $speaker_terms = get_terms(['taxonomy' => 'mec_speaker', 'hide_empty' => false]);
        foreach ($speaker_terms as $speaker_term)
        {
            $speakers .= '<label for="mec_fes_speakers' . esc_attr($speaker_term->term_id) . '">
                <input type="checkbox" name="mec[speakers][' . esc_attr($speaker_term->term_id) . ']" id="mec_fes_speakers' . esc_attr($speaker_term->term_id) . '" value="1">
                ' . esc_html($speaker_term->name) . '
            </label>';
        }

        echo MEC_kses::form($speakers);
        exit;
    }

    private function authorize_speakers_list_request(): bool
    {
        $nonce = isset($_REQUEST['mec_speakers_nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['mec_speakers_nonce'])) : '';
        if ($nonce && is_user_logged_in() && current_user_can('edit_posts') && wp_verify_nonce($nonce, 'mec_speakers_list')) return true;

        if (!class_exists('MEC_feature_fes')) MEC::import('app.features.fes');
        $fes = class_exists('MEC_feature_fes') ? new MEC_feature_fes() : null;

        return $fes && $fes->validate_fes_term_creation_request();
    }

    public function show_notices($screen)
    {
        if (isset($screen->id) and $screen->id == 'edit-mec_speaker')
        {
            add_action('admin_footer', function ()
            {
                echo "<script>
                var xhrObject = window.XMLHttpRequest;
                function ajaxXHR()
                {
                    var xmlHttp = new xhrObject();
                    xmlHttp.addEventListener('readystatechange', function(xhr)
                    {
                        if(xmlHttp.readyState == 4 && xmlHttp.status == 200)
                        {
                            if(xhr.currentTarget.responseText.indexOf('tr') != -1)
                            {
                                jQuery('.form-wrap').find('.warning-msg').remove();
                                jQuery('.form-wrap').append('<div class=\"warning-msg\"><p>" . esc_html__('Note: You can use the speakers in your event edit/add page > hourly schedule section and speaker widget section!', 'modern-events-calendar-lite') . "</p></div>');
                            }
                        }
                    });

                    return xmlHttp;
                }
                window.XMLHttpRequest = ajaxXHR;
                </script>";
            });
        }
    }

    public function update_speakers_list_admin($screen)
    {
        if (isset($screen->id) and $screen->id == 'mec-events' and isset($screen->base) and $screen->base == 'post')
        {
            add_action('admin_footer', [$this, 'update_speakers_list']);
        }
    }

    public function update_speakers_list()
    {
        $speakers_nonce = wp_create_nonce('mec_speakers_list');
        echo "<script>
        jQuery('body').on('DOMSubtreeModified', 'ul.tagchecklist, #mec-fes-speakers-list', function()
        {
            jQuery.ajax(
            {
                url: '" . admin_url('admin-ajax.php', null) . "',
                type: 'POST',
                data:
                {
                    action: 'update_speakers_list',
                    fes_nonce: (typeof mecdata !== 'undefined' ? mecdata.fes_nonce : ''),
                    mec_speakers_nonce: '" . esc_js($speakers_nonce) . "'
                },
                dataType: 'json'
            })
            .done(function(response)
            {
                for(var i = 0; i < response.speakers.length; i++)
                {
                    var speaker = response.speakers[i];
                    jQuery('.mec-hourly-schedule-form-speakers').each(function(index)
                    {
                        var d = jQuery(this).data('d');
                        var key = jQuery(this).data('key');
                        var name_prefix = jQuery(this).data('name-prefix');

                        var name = name_prefix + '[hourly_schedules]['+d+'][schedules]['+key+'][speakers][]';

                        // Add
                        if(!jQuery(this).find('input[value=\"'+speaker[0]+'\"]').length)
                        {
                            jQuery(this).append('<label><input type=\"checkbox\" name=\"'+name+'\" value=\"'+speaker[0]+'\">'+speaker[1]+'</label>');
                        }
                    });
                }
            });
        });
        </script>";
    }

    public function get_speakers()
    {
        if (!$this->authorize_speakers_list_request())
        {
            wp_send_json_error(['message' => esc_html__('Sorry! You do not have permission to view speakers.', 'modern-events-calendar-lite')], 403);
        }

        $speakers = get_terms('mec_speaker', [
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => '0',
        ]);

        $sp = [];
        foreach ($speakers as $speaker)
        {
            $sp[] = [$speaker->term_id, $speaker->name];
        }

        wp_send_json(['speakers' => $sp]);
    }

    public function hide_parent_dropdown($args)
    {
        if ('mec_speaker' == $args['taxonomy']) $args['echo'] = false;
        return $args;
    }
}

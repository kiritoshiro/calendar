<?php

class MECDIVI_MECShortcodes extends ET_Builder_Module
{
    public $slug = 'mecdivi_MECShortcodes';
    public $vb_support = 'on';

    public function init()
    {
        $this->name = esc_html__('MEC Shortcodes', 'modern-events-calendar-lite');
    }

    public function get_fields(): array
    {
        $calendar_posts = get_posts(['post_type' => 'mec_calendars', 'posts_per_page' => '-1']);
        $calendars = [];
        foreach ($calendar_posts as $calendar_post) $calendars[$calendar_post->ID] = $calendar_post->post_title;

        return [
            'shortcode_id' => [
                'label' => esc_html__('MEC Shortcodes', 'mecdivi-divi'),
                'type' => 'select',
                'options' => $calendars,
                'description' => esc_html__('Enter the shortcode_id of your choosing here.', 'mecdivi-divi'),
            ],
        ];
    }

    public function render($attrs, $content = null, $render_slug = null)
    {
        return do_shortcode('[MEC id="' . $this->props['shortcode_id'] . '"]');
    }
}

new MECDIVI_MECShortcodes;

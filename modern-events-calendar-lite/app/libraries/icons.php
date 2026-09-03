<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC Icons class.
 * @author Webnus <info@webnus.net>
 */
class MEC_icons
{
    protected $icons = [];

    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct($icons = [])
    {
        $this->icons = $icons;
    }

    public function has($key)
    {
        return isset($this->icons[$key]) && trim($this->icons[$key]);
    }

    public function display($key)
    {
        $default = $this->default($key);
        $custom = isset($this->icons[$key]) && trim($this->icons[$key]) ? '<img class="mec-custom-image-icon" src="'.esc_url_raw($this->icons[$key]).'" alt="'.esc_attr($key).'">' : '';

        return trim($custom) ? $custom : $default;
    }

    public function default($key)
    {
        $all = $this->all();
        return isset($all[$key]['default']) && trim($all[$key]['default']) ? $all[$key]['default'] : '';
    }

    public function list(): array
    {
        $all = $this->all();

        $icons = [];
        foreach ($all as $key => $icon)
        {
            $icon['value'] = $this->display($key);
            $icons[] = $icon;
        }

        return $icons;
    }

    public function all()
    {
        return apply_filters('mec_icons', [
            'location-pin' => [
                'name' => __('Location Marker Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-location-pin"></i>',
                'modules' => ['single', 'shortcode'],
            ],
            'map-marker' => [
                'name' => __('Map Marker Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-map-marker"></i>',
                'modules' => ['shortcode'],
            ],
            'info' => [
                'name' => __('Info Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-info"></i>',
                'modules' => ['single'],
            ],
            'bookmark' => [
                'name' => __('Bookmark Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-fa-bookmark-o"></i>',
                'modules' => ['single'],
            ],
            'folder' => [
                'name' => __('Folder Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-folder"></i>',
                'modules' => ['single', 'shortcode'],
            ],
            'home' => [
                'name' => __('Home Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-home"></i>',
                'modules' => ['single'],
            ],
            'people' => [
                'name' => __('People Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-people"></i>',
                'modules' => ['single'],
            ],
            'phone' => [
                'name' => __('Phone Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-phone"></i>',
                'modules' => ['single'],
            ],
            'envelope' => [
                'name' => __('Envelope Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-envelope"></i>',
                'modules' => ['single'],
            ],
            'calendar' => [
                'name' => __('Calendar Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-calendar"></i>',
                'modules' => ['single', 'shortcode'],
            ],
            'clock' => [
                'name' => __('Clock Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-clock"></i>',
                'modules' => ['single', 'shortcode'],
            ],
            'clock-o' => [
                'name' => __('Clock 2 Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-clock-o"></i>',
                'modules' => ['shortcode'],
            ],
            'wallet' => [
                'name' => __('Wallet Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-wallet"></i>',
                'modules' => ['single', 'shortcode'],
            ],
            'user' => [
                'name' => __('User Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-user"></i>',
                'modules' => ['shortcode'],
            ],
            'magnifier' => [
                'name' => __('Magnifire Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-magnifier"></i>',
                'modules' => ['shortcode'],
            ],
            'credit-card' => [
                'name' => __('Credit Card Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-credit-card"></i>',
                'modules' => ['shortcode'],
            ],
            'map' => [
                'name' => __('Map Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-map"></i>',
                'modules' => ['shortcode'],
            ],
            'pin' => [
                'name' => __('Pin Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-pin"></i>',
                'modules' => ['shortcode'],
            ],
            'tag' => [
                'name' => __('Tag Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-tag"></i>',
                'modules' => ['shortcode'],
            ],
            'microphone' => [
                'name' => __('Microphone Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-microphone"></i>',
                'modules' => ['shortcode'],
            ],
            'sitemap' => [
                'name' => __('Website Icon', 'modern-events-calendar-lite'),
                'default' => '<i class="mec-sl-sitemap"></i>',
                'modules' => ['single'],
            ]
        ]);
    }

    public function form($section = 'single', $prefix = 'mec[settings]', $values = [])
    {
        $all = $this->all();
        ?>
        <div class="mec-form-row mec-icons-form">
            <div class="mec-col-12 mec-image-picker-page">
                <?php foreach($all as $key => $icon): if(!in_array($section, $icon['modules'])) continue; $current = isset($values[$key]) && trim($values[$key]) ? $values[$key] : ''; ?>
                <div class="mec-icon mec-image-picker-wrapper" id="mec-icons-<?php echo esc_attr($key); ?>">
                    <div class="mec-icon-default">
                        <span class="default"><?php echo $icon['default']; ?></span>
                        <label for="mec_icons_<?php echo esc_attr($key); ?>"><?php echo esc_html($icon['name']); ?></label>
                    </div>
                    <div class="mec-icon-uploader">
                        <button type="button" class="button button-secondary mec-image-picker-upload <?php echo $current ? 'w-hidden' : ''; ?>" id="mec_icons_<?php echo esc_attr($key); ?>"><?php esc_html_e('Upload', 'modern-events-calendar-lite'); ?></button>
                        <button class="button button-secondary mec-image-picker-remove <?php echo $current ? '' : 'w-hidden'; ?>"><?php esc_html_e('Remove', 'modern-events-calendar-lite'); ?></button>
                        <input class="mec-image-picker-input" type="hidden" name="<?php echo esc_attr($prefix); ?>[icons][<?php echo esc_attr($key); ?>]" value="<?php echo $current; ?>" />
                    </div>
                    <div class="mec-icon-preview-remove mec-image-picker-preview-wrapper">
                        <div class="mec-image-picker-preview <?php echo $current ? '' : 'w-hidden'; ?>"><img src="<?php echo esc_url_raw($current); ?>" alt="<?php echo esc_attr($icon['name']); ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}

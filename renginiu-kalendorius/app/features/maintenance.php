<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * @author Webnus <info@webnus.net>
 */
class MEC_feature_maintenance extends MEC_base
{
    public $factory;
    public $main;

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
    }

    /**
     * Initialize maintenance feature
     *
     * @author Webnus <info@webnus.net>
     */
    public function init()
    {
        $this->factory->action('mec_maintenance', [$this, 'maintenance']);
    }

    /**
     * MEC Maintenance Jobs
     *
     * @return void
     */
    public function maintenance()
    {
        // Settings
        $settings = $this->main->get_settings();

        // Trash Interval
        $trash_interval = isset($settings['events_trash_interval']) ? (int) $settings['events_trash_interval'] : 0;

        // Do Events Trash
        if ($trash_interval) $this->events('trash', $trash_interval);

        // Purge Interval
        $purge_interval = isset($settings['events_purge_interval']) ? (int) $settings['events_purge_interval'] : 0;

        // Do Events Purge
        if ($purge_interval) $this->events('purge', $purge_interval);

        // QR Code Remove
        $this->qrcode_images();
    }

    public function events($type, $interval)
    {
        // Date
        $date = date('Y-m-d', strtotime('-' . $interval . ' Days'));

        // DB
        $db = $this->getDB();

        // Events
        $event_ids = $db->select("SELECT post_id FROM `#__mec_dates` WHERE `dend` < '" . $date . "' GROUP BY post_id ORDER BY dend DESC", 'loadColumn');

        // Upcoming Events
        $upcoming_events = $this->main->get_upcoming_event_ids();

        // Trash / Purge
        foreach ($event_ids as $event_id)
        {
            // Event is still ongoing
            if (in_array($event_id, $upcoming_events)) continue;

            if ($type === 'trash') wp_trash_post($event_id);
            else if ($type === 'purge') wp_delete_post($event_id, true);
        }
    }

    public function qrcode_images()
    {
        $upload_dir = wp_upload_dir();
        $dir_path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'mec' . DIRECTORY_SEPARATOR;

        if (is_dir($dir_path))
        {
            $files = glob($dir_path . '*.png');

            $now = time();
            $seven_days = 7 * DAY_IN_SECONDS;

            foreach ($files as $file)
            {
                if (is_file($file))
                {
                    $file_mtime = filemtime($file);

                    if ($file_mtime && ($now - $file_mtime) >= $seven_days)
                    {
                        unlink($file);
                    }
                }
            }
        }
    }
}

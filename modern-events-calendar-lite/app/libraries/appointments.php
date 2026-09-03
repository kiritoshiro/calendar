<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC Appointments class.
 * @author Webnus <info@webnus.net>
 */
class MEC_appointments extends MEC_base
{
    /**
     * @var MEC_main
     */
    public $main;

    /**
     * @var array
     */
    public $settings;

    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct()
    {
        // MEC Main library
        $this->main = $this->getMain();

        // MEC settings
        $this->settings = $this->main->get_settings();
    }

    public function get_entity_type(int $event_id): string
    {
        $entity_type = get_post_meta($event_id, 'mec_entity_type', true);
        if (!in_array($entity_type, ['event', 'appointment'])) $entity_type = 'event';

        return $entity_type;
    }

    public function save($event_id, $data)
    {
        // Entity Type
        $entity_type = isset($data['entity_type']) && in_array($data['entity_type'], ['event', 'appointment']) ? $data['entity_type'] : 'event';

        if ($entity_type === 'event')
        {
            delete_post_meta($event_id, 'mec_appointments');
            return;
        }

        // Appointment Config
        $config = isset($data['appointments']) && is_array($data['appointments']) ? $data['appointments'] : [];

        // Remove Temp Keys
        if (isset($config['availability']) && is_array($config['availability']))
        {
            foreach ($config['availability'] as &$day)
            {
                if (isset($day[':t:'])) unset($day[':t:']);
            }

            unset($day); // break reference
        }

        $availability_repeat_type = $config['availability_repeat_type'] ?? 'weekly';

        $adjusted_map = [];
        if (isset($config['adjusted_availability']) && is_array($config['adjusted_availability']))
        {
            foreach ($config['adjusted_availability'] as $k => &$day)
            {
                if (isset($day[':t:'])) unset($day[':t:']);

                if (empty($day['date']))
                {
                    unset($config['adjusted_availability'][$k]);
                    continue;
                }

                $day['date'] = sanitize_text_field($day['date']);

                $periods = [];
                foreach ($day as $i => $slot)
                {
                    if ($i === 'date' || !is_array($slot)) continue;
                    $periods[] = $slot;
                }

                $adjusted_map[$day['date']] = $periods;
            }

            unset($day);
        }

        $availability = $config['availability'] ?? [];
        $duration = isset($config['duration']) && is_numeric($config['duration']) ? (int) $config['duration'] : 10;
        $buffer = isset($config['buffer']) && is_numeric($config['buffer']) ? (int) $config['buffer'] : 0;
        $max_bookings_per_day = isset($config['max_bookings_per_day']) && is_numeric($config['max_bookings_per_day']) ? (int) $config['max_bookings_per_day'] : '';
        $repeat_start_date = isset($config['start_date']) ? sanitize_text_field($config['start_date']) : '';

        $config['duration'] = $duration;
        $config['buffer'] = $buffer;
        $config['max_bookings_per_day'] = $max_bookings_per_day;
        $config['start_date'] = $repeat_start_date;

        $start_date = null;
        $start_hour = null;
        $start_minutes = null;
        $start_ampm = null;
        $end_date = null;
        $end_hour = null;
        $end_minutes = null;
        $end_ampm = null;

        $in_days = [];

        if ($availability_repeat_type === 'no_repeat')
        {
            $dates = array_keys($adjusted_map);
            sort($dates);

            $start_ts = null;
            $end_ts = null;

            foreach ($dates as $date)
            {
                $periods = $adjusted_map[$date];
                $g = $this->generate_slots([0 => $periods], $duration, $buffer);
                $day_slots = $g[0] ?? [];

                foreach ($day_slots as $day_slot)
                {
                    $st = strtotime($date .' '.$day_slot['start']);
                    $et = strtotime($date .' '.$day_slot['end']);

                    if (is_null($start_ts)) $start_ts = $st;
                    if (is_null($end_ts)) $end_ts = $et;

                    $start_time = date('h-i-A', $st);
                    $end_time = date('h-i-A', $et);
                    $in_days[] = "$date:$date:$start_time:$end_time";
                }
            }

            if (!is_null($start_ts))
            {
                $start_date = date('Y-m-d', $start_ts);
                $start_hour = date('h', $start_ts);
                $start_minutes = date('i', $start_ts);
                $start_ampm = date('A', $start_ts);
            }

            if (!is_null($end_ts))
            {
                $end_date = date('Y-m-d', $end_ts);
                $end_hour = date('h', $end_ts);
                $end_minutes = date('i', $end_ts);
                $end_ampm = date('A', $end_ts);
            }
        }
        else
        {
            $slots = $this->generate_slots($availability, $duration, $buffer);
            $adjusted_slots = [];
            foreach ($adjusted_map as $date => $periods)
            {
                $g = $this->generate_slots([0 => $periods], $duration, $buffer);
                $adjusted_slots[$date] = $g[0] ?? [];
            }

            $timezone = wp_timezone();
            $today_dt = new DateTime('now', $timezone);
            $today_dt->setTime(0, 0);

            $start_ts = $today_dt->getTimestamp();
            if(!empty($repeat_start_date))
            {
                $tmp_start_dt = DateTime::createFromFormat('Y-m-d', $repeat_start_date, $timezone);
                if($tmp_start_dt)
                {
                    $tmp_start_dt->setTime(0, 0);
                    $tmp_start_ts = $tmp_start_dt->getTimestamp();
                    if($tmp_start_ts > $start_ts) $start_ts = $tmp_start_ts;
                }
            }

            $start_slot_ts = null;
            $end_slot_ts = null;

            for ($i = 0; $i < 180; $i++)
            {
                $date_ts = $start_ts + (DAY_IN_SECONDS * $i);
                $date = wp_date('Y-m-d', $date_ts);

                if (isset($adjusted_slots[$date]))
                {
                    $day_slots = $adjusted_slots[$date];
                    if (!count($day_slots)) continue;
                }
                else
                {
                    // Get PHP weekday index (0 = Monday, 6 = Sunday)
                    $weekday = (int) wp_date('N', $date_ts) - 1;
                    if (empty($slots[$weekday])) continue;

                    $day_slots = $slots[$weekday];
                }

                // Day Slots
                foreach ($day_slots as $day_slot)
                {
                    $start_dt = DateTime::createFromFormat('Y-m-d H:i', $date.' '.$day_slot['start'], $timezone);
                    $end_dt = DateTime::createFromFormat('Y-m-d H:i', $date.' '.$day_slot['end'], $timezone);
                    if(!$start_dt || !$end_dt) continue;

                    $st = $start_dt->getTimestamp();
                    $et = $end_dt->getTimestamp();

                    if (is_null($start_date)) $start_date = $date;
                    if (is_null($start_hour)) $start_hour = wp_date('h', $st);
                    if (is_null($start_minutes)) $start_minutes = wp_date('i', $st);
                    if (is_null($start_ampm)) $start_ampm = wp_date('A', $st);
                    if (is_null($end_date)) $end_date = $date;
                    if (is_null($end_hour)) $end_hour = wp_date('h', $et);
                    if (is_null($end_minutes)) $end_minutes = wp_date('i', $et);
                    if (is_null($end_ampm)) $end_ampm = wp_date('A', $et);
                    if (is_null($start_slot_ts)) $start_slot_ts = $st;
                    if (is_null($end_slot_ts)) $end_slot_ts = $et;

                    $start_time = wp_date('h-i-A', $st);
                    $end_time = wp_date('h-i-A', $et);

                    $in_days[] = "$date:$date:$start_time:$end_time";
                }
            }
        }

        // To avoid first date duplication
        array_shift($in_days);

        $in_days_str = '';
        foreach ($in_days as $key => $in_day)
        {
            if (is_numeric($key)) $in_days_str .= $in_day . ',';
        }

        $in_days_str = trim($in_days_str, ', ');
        $repeat_type = 'custom_days';

        if (isset($this->settings['time_format']) and $this->settings['time_format'] == 24)
        {
            $day_start_seconds = $this->main->time_to_seconds($this->main->to_24hours($start_hour, null, 'start'), $start_minutes);
            $day_end_seconds = $this->main->time_to_seconds($this->main->to_24hours($end_hour, null), $end_minutes);
        }
        else
        {
            $day_start_seconds = $this->main->time_to_seconds($this->main->to_24hours($start_hour, $start_ampm, 'start'), $start_minutes);
            $day_end_seconds = $this->main->time_to_seconds($this->main->to_24hours($end_hour, $end_ampm), $end_minutes);
        }

        if ($end_date === $start_date && $day_end_seconds < $day_start_seconds)
        {
            $day_end_seconds = $day_start_seconds;

            $end_hour = $start_hour;
            $end_minutes = $start_minutes;
            $end_ampm = $start_ampm;
        }

        $start_datetime = $start_date.' '.$start_hour.':'.$start_minutes.' '.$start_ampm;
        $end_datetime = $end_date.' '.$end_hour.':'.$end_minutes.' '.$end_ampm;

        update_post_meta($event_id, 'mec_date', [
            'start' => [
                'date' => $start_date,
                'hour' => wp_date('g', $start_slot_ts),
                'minutes' => $start_minutes,
                'ampm' => $start_ampm,
            ],
            'end' => [
                'date' => $end_date,
                'hour' => wp_date('g', $end_slot_ts),
                'minutes' => $end_minutes,
                'ampm' => $end_ampm,
            ],
            'comment' => '',
            'repeat' => [
                'status' => 1,
                'type' => $repeat_type,
                'interval' => '',
                'advanced' => '',
                'end' => 'never',
                'end_at_date' => '',
                'end_at_occurrences' => 10,
            ],
        ]);

        update_post_meta($event_id, 'mec_start_date', $start_date);
        update_post_meta($event_id, 'mec_start_time_hour', $start_hour);
        update_post_meta($event_id, 'mec_start_time_minutes', $start_minutes);
        update_post_meta($event_id, 'mec_start_time_ampm', $start_ampm);
        update_post_meta($event_id, 'mec_start_day_seconds', $day_start_seconds);
        update_post_meta($event_id, 'mec_start_datetime', $start_datetime);

        update_post_meta($event_id, 'mec_end_date', $end_date);
        update_post_meta($event_id, 'mec_end_time_hour', $end_hour);
        update_post_meta($event_id, 'mec_end_time_minutes', $end_minutes);
        update_post_meta($event_id, 'mec_end_time_ampm', $end_ampm);
        update_post_meta($event_id, 'mec_end_day_seconds', $day_end_seconds);
        update_post_meta($event_id, 'mec_end_datetime', $end_datetime);
        update_post_meta($event_id, 'mec_repeat_status', 1);
        update_post_meta($event_id, 'mec_repeat_type', $repeat_type);
        update_post_meta($event_id, 'mec_repeat_interval', null);
        $display_in_shortcodes = isset($this->settings['appointments_display_in_shortcodes']) && (int) $this->settings['appointments_display_in_shortcodes'] === 1;
        update_post_meta($event_id, 'mec_public', $display_in_shortcodes ? 1 : 0);
        update_post_meta($event_id, 'mec_in_days', $in_days_str);

        $db = $this->getDB();
        $db->q("UPDATE `#__mec_events` SET `repeat`=1, `rinterval`=null, `days`='".esc_sql($in_days_str)."' WHERE `post_id`='".esc_sql($event_id)."'");

        update_post_meta($event_id, 'mec_appointments', $config);

        // Update Schedule
        $schedule = $this->getSchedule();
        $schedule->reschedule($event_id, 500);
    }

    public function generate_slots(array $availability, int $duration = 10, int $buffer = 0): array
    {
        $result = [];

        if (!count($availability)) return $result;
        if ($duration <= 0) return $result;

        foreach ($availability as $d => $slots)
        {
            $result[$d] = [];
            if (!is_array($slots)) continue;

            foreach ($slots as $slot)
            {
                if (
                    !isset($slot['start']['hour'], $slot['start']['minutes']) ||
                    !isset($slot['end']['hour'], $slot['end']['minutes'])
                )
                {
                    continue;
                }

                // Convert start and end time to timestamps
                if (isset($this->settings['time_format']) and $this->settings['time_format'] == 24)
                {
                    $start = mktime($this->main->to_24hours($slot['start']['hour'], null, 'start'), $slot['start']['minutes'], 0, 1, 1, 2000);
                    $end = mktime($this->main->to_24hours($slot['end']['hour'], null), $slot['end']['minutes'], 0, 1, 1, 2000);
                }
                else
                {
                    $start = mktime($this->main->to_24hours($slot['start']['hour'], $slot['start']['ampm'], 'start'), $slot['start']['minutes'], 0, 1, 1, 2000);
                    $end = mktime($this->main->to_24hours($slot['end']['hour'], $slot['end']['ampm']), $slot['end']['minutes'], 0, 1, 1, 2000);
                }

                $appointments = [];
                while ($start + ($duration * 60) <= $end)
                {
                    $appointments[] = [
                        'start' => date('H:i', $start),
                        'end' => date('H:i', $start + ($duration * 60)),
                    ];

                    // Move start time forward by duration + buffer
                    $start += ($duration + $buffer) * 60;
                }

                $result[$d] = array_merge($result[$d], $appointments);
            }
        }

        return $result;
    }
}

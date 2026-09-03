<?php
/** no direct access **/
defined('MECEXEC') or die();

/**
 * Webnus MEC Appointments class.
 * @author Webnus <info@webnus.net>
 */
class MEC_feature_appointments extends MEC_base
{
    public $factory;
    public $main;
    public $appointment;
    public $settings;

    /**
     * Constructor method
     * @author Webnus <info@webnus.net>
     */
    public function __construct()
    {
        // Import MEC Factory
        $this->factory = $this->getFactory();

        // Import Appointment Library
        $this->appointment = $this->getAppointments();

        // Import MEC Main
        $this->main = $this->getMain();

        // MEC Settings
        $this->settings = $this->main->get_settings();
    }

    /**
     * Initialize Appointments feature
     * @author Webnus <info@webnus.net>
     */
    public function init()
    {
        // PRO Version is required
        if (!$this->getPRO()) return false;

        // Show Appointments feature only if the module and bookings are enabled
        if (!isset($this->settings['booking_status']) || !$this->settings['booking_status']) return false;
        if (!isset($this->settings['appointments_status']) || !$this->settings['appointments_status']) return false;

        $this->factory->action('mec_editor_before_date_time', [$this, 'form']);

        return true;
    }

    public function form($event_id)
    {
        $entity_type = $this->appointment->get_entity_type($event_id);
        $days = $this->main->get_weekday_abbr_labels();

        $config = get_post_meta($event_id, 'mec_appointments', true);
        if (!is_array($config)) $config = [];

        $duration = isset($config['duration']) && $config['duration'] ? $config['duration'] : 60;
        $buffer   = isset($config['buffer']) ? (int) $config['buffer'] : 0;
        $max_bookings_per_day = $config['max_bookings_per_day'] ?? '';
        $start_date = $config['start_date'] ?? '';
        $adjusted = isset($config['adjusted_availability']) && is_array($config['adjusted_availability']) ? $config['adjusted_availability'] : [];
        $availability_repeat_type = $config['availability_repeat_type'] ?? 'weekly';
        ?>
        <div class="mec-event-appointment-type-wrap">&mdash;
            <label for="mec_entity_type_select" class="screen-reader-text"><?php esc_html_e('Event Type', 'modern-events-calendar-lite'); ?></label>
            <select id="mec_entity_type_select" name="mec[entity_type]">
                <option value="event" <?php selected($entity_type, 'event'); ?>><?php esc_html_e('Event', 'modern-events-calendar-lite'); ?></option>
                <option value="appointment" <?php selected($entity_type, 'appointment'); ?>><?php esc_html_e('Appointment', 'modern-events-calendar-lite'); ?></option>
            </select>
        </div>
        <div class="mec-appointment-form-wrap">
            <input type="hidden" name="mec[appointments][saved]" value="1">
            <h4><?php esc_html_e('Appointment duration', 'modern-events-calendar-lite'); ?></h4>
            <div class="mec-form-row">
                <select id="mec_appointments_duration" name="mec[appointments][duration]">
                    <option value="10" <?php echo $duration == '10' ? 'selected' : ''; ?>><?php esc_html_e('10 minutes', 'modern-events-calendar-lite'); ?></option>
                    <option value="15" <?php echo $duration == '15' ? 'selected' : ''; ?>><?php esc_html_e('15 minutes', 'modern-events-calendar-lite'); ?></option>
                    <option value="20" <?php echo $duration == '20' ? 'selected' : ''; ?>><?php esc_html_e('20 minutes', 'modern-events-calendar-lite'); ?></option>
                    <option value="30" <?php echo $duration == '30' ? 'selected' : ''; ?>><?php esc_html_e('30 minutes', 'modern-events-calendar-lite'); ?></option>
                    <option value="45" <?php echo $duration == '45' ? 'selected' : ''; ?>><?php esc_html_e('45 minutes', 'modern-events-calendar-lite'); ?></option>
                    <option value="60" <?php echo $duration == '60' ? 'selected' : ''; ?>><?php esc_html_e('1 hour', 'modern-events-calendar-lite'); ?></option>
                    <option value="90" <?php echo $duration == '90' ? 'selected' : ''; ?>><?php esc_html_e('1.5 hours', 'modern-events-calendar-lite'); ?></option>
                    <option value="120" <?php echo $duration == '120' ? 'selected' : ''; ?>><?php esc_html_e('2 hours', 'modern-events-calendar-lite'); ?></option>
                    <option value="240" <?php echo $duration == '240' ? 'selected' : ''; ?>><?php esc_html_e('4 hours', 'modern-events-calendar-lite'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('How long should each appointment last?', 'modern-events-calendar-lite'); ?></p>
            </div>
            <div class="mec-form-row">
                <input type="number" id="mec_appointments_buffer" name="mec[appointments][buffer]" min="0" max="240" step="1" value="<?php echo esc_attr($buffer); ?>">
                <p class="description"><?php esc_html_e('Buffer time between appointments (minutes)', 'modern-events-calendar-lite'); ?></p>
            </div>
            <div class="mec-form-row">
                <input type="number" id="mec_appointments_max_per_day" name="mec[appointments][max_bookings_per_day]" min="0" step="1" value="<?php echo esc_attr($max_bookings_per_day); ?>">
                <p class="description"><?php esc_html_e('Maximum bookings per day', 'modern-events-calendar-lite'); ?></p>
            </div>
            <h4><?php esc_html_e('Availability', 'modern-events-calendar-lite'); ?></h4>
            <div class="mec-form-row">
                <select id="mec_appointments_availability_repeat_type" name="mec[appointments][availability_repeat_type]">
                    <option value="weekly" <?php echo ($availability_repeat_type == 'weekly' ? 'selected' : ''); ?>><?php esc_html_e('Repeat Weekly', 'modern-events-calendar-lite'); ?></option>
                    <option value="no_repeat" <?php echo ($availability_repeat_type == 'no_repeat' ? 'selected' : ''); ?>><?php esc_html_e('Does Not Repeat', 'modern-events-calendar-lite'); ?></option>
                </select>
                <p class="description"><?php esc_html_e("Set when you're available for appointments.", 'modern-events-calendar-lite'); ?></p>
            </div>
            <div class="mec-form-row lsd-apt-start-date-wrapper <?php echo ($availability_repeat_type === 'no_repeat' ? 'mec-util-hidden' : ''); ?>">
                <input type="text" id="mec_appointments_start_date" class="mec-apt-date-picker" name="mec[appointments][start_date]" value="<?php echo esc_attr($start_date); ?>">
                <p class="description"><?php esc_html_e('Start date', 'modern-events-calendar-lite'); ?></p>
            </div>
            <div class="lsd-apt-days-wrapper <?php echo ($availability_repeat_type === 'no_repeat' ? 'mec-util-hidden' : ''); ?>">
                <?php foreach($days as $key => $day): ?>
                    <?php
                        $day_availability = isset($config['availability'][$key]) && is_array($config['availability'][$key]) ? $config['availability'][$key] : [];
                        $t = 0;
                    ?>
                    <div class="lsd-apt-day-wrapper mec-form-row" data-key="<?php echo esc_attr($key); ?>">
                        <div class="lsd-apt-day-label mec-col-2"><?php echo esc_html($day); ?></div>
                        <div class="lsd-apt-day-timeslots mec-col-9">
                            <div class="lsd-apt-day-timeslots-unavailable <?php echo isset($config['saved']) && !count($day_availability) ? '' : 'mec-util-hidden'; ?>"><?php esc_html_e('Unavailable', 'modern-events-calendar-lite'); ?></div>
                            <div class="lsd-apt-day-timeslots-wrapper">
                                <?php if (count($day_availability) || isset($config['saved'])): ?>
                                <?php foreach($day_availability as $a => $availability): if (!is_numeric($a)) continue; $t = max($t, $a); ?>
                                    <div class="lsd-apt-day-timeslot-wrapper">
                                        <div>
                                            <?php $this->main->timepicker([
                                                'method' => $this->settings['time_format'] ?? 12,
                                                'time_hour' => $availability['start']['hour'] ?? 8,
                                                'time_minutes' => $availability['start']['minutes'] ?? 0,
                                                'time_ampm' => $availability['start']['ampm'] ?? '',
                                                'name' => 'mec[appointments][availability]['.$key.']['.$a.'][start]',
                                                'id_key' => 'mec_appointments_availability_'.$key.'_'.$a.'_start_',
                                            ]); ?>
                                            <span class="lsd-apt-to"> - </span>
                                            <?php $this->main->timepicker([
                                                'method' => $this->settings['time_format'] ?? 12,
                                                'time_hour' => $availability['end']['hour'] ?? 6,
                                                'time_minutes' => $availability['end']['minutes'] ?? 0,
                                                'time_ampm' => $availability['end']['ampm'] ?? '',
                                                'name' => 'mec[appointments][availability]['.$key.']['.$a.'][end]',
                                                'id_key' => 'mec_appointments_availability_'.$key.'_'.$a.'_end_',
                                            ]); ?>
                                        </div>
                                        <span class="button mec-dash-remove-btn lsd-apt-day-icon-remove">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M170.5 51.6L151.5 80l145 0-19-28.4c-1.5-2.2-4-3.6-6.7-3.6l-93.7 0c-2.7 0-5.2 1.3-6.7 3.6zm147-26.6L354.2 80 368 80l48 0 8 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-8 0 0 304c0 44.2-35.8 80-80 80l-224 0c-44.2 0-80-35.8-80-80l0-304-8 0c-13.3 0-24-10.7-24-24S10.7 80 24 80l8 0 48 0 13.8 0 36.7-55.1C140.9 9.4 158.4 0 177.1 0l93.7 0c18.7 0 36.2 9.4 46.6 24.9zM80 128l0 304c0 17.7 14.3 32 32 32l224 0c17.7 0 32-14.3 32-32l0-304L80 128zm80 64l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16z"/></svg>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="lsd-apt-day-timeslot-wrapper">
                                        <div>
                                            <?php $this->main->timepicker([
                                                'method' => $this->settings['time_format'] ?? 12,
                                                'time_hour' => 8,
                                                'time_minutes' => 0,
                                                'time_ampm' => 'AM',
                                                'name' => 'mec[appointments][availability]['.$key.'][0][start]',
                                                'id_key' => 'mec_appointments_availability_'.$key.'_0_start_',
                                            ]); ?>
                                            <span class="lsd-apt-to"> - </span>
                                            <?php $this->main->timepicker([
                                                'method' => $this->settings['time_format'] ?? 12,
                                                'time_hour' => 6,
                                                'time_minutes' => 0,
                                                'time_ampm' => 'PM',
                                                'name' => 'mec[appointments][availability]['.$key.'][0][end]',
                                                'id_key' => 'mec_appointments_availability_'.$key.'_0_end_',
                                            ]); ?>
                                        </div>
                                        <span class="button mec-dash-remove-btn lsd-apt-day-icon-remove">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M170.5 51.6L151.5 80l145 0-19-28.4c-1.5-2.2-4-3.6-6.7-3.6l-93.7 0c-2.7 0-5.2 1.3-6.7 3.6zm147-26.6L354.2 80 368 80l48 0 8 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-8 0 0 304c0 44.2-35.8 80-80 80l-224 0c-44.2 0-80-35.8-80-80l0-304-8 0c-13.3 0-24-10.7-24-24S10.7 80 24 80l8 0 48 0 13.8 0 36.7-55.1C140.9 9.4 158.4 0 177.1 0l93.7 0c18.7 0 36.2 9.4 46.6 24.9zM80 128l0 304c0 17.7 14.3 32 32 32l224 0c17.7 0 32-14.3 32-32l0-304L80 128zm80 64l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16z"/></svg>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="lsd-apt-day-icons mec-col-1">
                            <span class="button lsd-apt-day-icon-plus" data-key="<?php echo esc_attr($t); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 144L48 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l144 0 0 144c0 17.7 14.3 32 32 32s32-14.3 32-32l0-144 144 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-144 0 0-144z"/></svg>
                            </span>
                            <span class="button lsd-apt-day-icon-copy">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l140.1 0L400 115.9 400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-204.1c0-12.7-5.1-24.9-14.1-33.9L366.1 14.1c-9-9-21.2-14.1-33.9-14.1L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-32-48 0 0 32c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l32 0 0-48-32 0z"/></svg>
                            </span>
                        </div>
                        <div class="mec-util-hidden mec-col-12">
                            <div id="lsd-apt-day-templates-<?php echo esc_attr($key); ?>-timeslot">
                                <div class="lsd-apt-day-timeslot-wrapper">
                                    <div>
                                        <?php $this->main->timepicker([
                                            'method' => $this->settings['time_format'] ?? 12,
                                            'time_hour' => 8,
                                            'time_minutes' => 0,
                                            'time_ampm' => 'AM',
                                            'name' => 'mec[appointments][availability]['.$key.'][:t:][start]',
                                            'id_key' => 'mec_appointments_availability_'.$key.'_:t:_start_',
                                        ]); ?>
                                        <span class="lsd-apt-to"> - </span>
                                        <?php $this->main->timepicker([
                                            'method' => $this->settings['time_format'] ?? 12,
                                            'time_hour' => 6,
                                            'time_minutes' => 0,
                                            'time_ampm' => 'PM',
                                            'name' => 'mec[appointments][availability]['.$key.'][:t:][end]',
                                            'id_key' => 'mec_appointments_availability_'.$key.'_:t:_end_',
                                        ]); ?>
                                    </div>
                                    <span class="button mec-dash-remove-btn lsd-apt-day-icon-remove">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M170.5 51.6L151.5 80l145 0-19-28.4c-1.5-2.2-4-3.6-6.7-3.6l-93.7 0c-2.7 0-5.2 1.3-6.7 3.6zm147-26.6L354.2 80 368 80l48 0 8 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-8 0 0 304c0 44.2-35.8 80-80 80l-224 0c-44.2 0-80-35.8-80-80l0-304-8 0c-13.3 0-24-10.7-24-24S10.7 80 24 80l8 0 48 0 13.8 0 36.7-55.1C140.9 9.4 158.4 0 177.1 0l93.7 0c18.7 0 36.2 9.4 46.6 24.9zM80 128l0 304c0 17.7 14.3 32 32 32l224 0c17.7 0 32-14.3 32-32l0-304L80 128zm80 64l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16z"/></svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <h4 class="lsd-apt-adjusted-title <?php echo ($availability_repeat_type === 'no_repeat' ? 'mec-util-hidden' : ''); ?>"><?php esc_html_e('Adjusted Availability', 'modern-events-calendar-lite'); ?></h4>
            <?php $last_adjusted_key = count($adjusted) ? max(array_map('intval', array_keys($adjusted))) : 0; ?>
            <div class="lsd-apt-adjusted-days-wrapper" data-key="<?php echo esc_attr($last_adjusted_key); ?>">
                <?php if (count($adjusted)): ?>
                    <?php foreach ($adjusted as $i => $day_adjusted): $periods = $day_adjusted; $date = $day_adjusted['date'] ?? ''; unset($periods['date']); $t = 0; ?>
                    <div class="lsd-apt-day-wrapper mec-form-row" data-day="<?php echo esc_attr($i); ?>">
                        <div class="lsd-apt-day-label mec-col-2"><input type="text" class="mec-apt-date-picker" name="mec[appointments][adjusted_availability][<?php echo esc_attr($i); ?>][date]" value="<?php echo esc_attr($date); ?>"></div>
                        <div class="lsd-apt-day-timeslots mec-col-9">
                            <div class="lsd-apt-day-timeslots-unavailable <?php echo count($periods) ? 'mec-util-hidden' : ''; ?>"><?php esc_html_e('Unavailable', 'modern-events-calendar-lite'); ?></div>
                            <div class="lsd-apt-day-timeslots-wrapper">
                                <?php if(count($periods)): foreach($periods as $a => $p): if(!is_numeric($a)) continue; $t = max($t, $a); ?>
                                <div class="lsd-apt-day-timeslot-wrapper">
                                    <div>
                                        <?php $this->main->timepicker([
                                            'method' => $this->settings['time_format'] ?? 12,
                                            'time_hour' => $p['start']['hour'] ?? 8,
                                            'time_minutes' => $p['start']['minutes'] ?? 0,
                                            'time_ampm' => $p['start']['ampm'] ?? '',
                                            'name' => 'mec[appointments][adjusted_availability]['.$i.']['.$a.'][start]',
                                            'id_key' => 'mec_appointments_adjusted_'.$i.'_'.$a.'_start_',
                                        ]); ?>
                                        <span class="lsd-apt-to"> - </span>
                                        <?php $this->main->timepicker([
                                            'method' => $this->settings['time_format'] ?? 12,
                                            'time_hour' => $p['end']['hour'] ?? 6,
                                            'time_minutes' => $p['end']['minutes'] ?? 0,
                                            'time_ampm' => $p['end']['ampm'] ?? '',
                                            'name' => 'mec[appointments][adjusted_availability]['.$i.']['.$a.'][end]',
                                            'id_key' => 'mec_appointments_adjusted_'.$i.'_'.$a.'_end_',
                                        ]); ?>
                                    </div>
                                    <span class="button mec-dash-remove-btn lsd-apt-day-icon-remove">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M170.5 51.6L151.5 80l145 0-19-28.4c-1.5-2.2-4-3.6-6.7-3.6l-93.7 0c-2.7 0-5.2 1.3-6.7 3.6zm147-26.6L354.2 80 368 80l48 0 8 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-8 0 0 304c0 44.2-35.8 80-80 80l-224 0c-44.2 0-80-35.8-80-80l0-304-8 0c-13.3 0-24-10.7-24-24S10.7 80 24 80l80 0 13.8 0 36.7-55.1C140.9 9.4 158.4 0 177.1 0l93.7 0c18.7 0 36.2 9.4 46.6 24.9zM80 128l0 304c0 17.7 14.3 32 32 32l224 0c17.7 0 32-14.3 32-32l0-304L80 128zm80 64l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16z"/></svg>
                                    </span>
                                </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>
                        <div class="lsd-apt-day-icons mec-col-1">
                            <span class="button lsd-apt-adj-day-icon-plus" data-key="<?php echo esc_attr($t); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 144L48 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l144 0 0 144c0 17.7 14.3 32 32 32s32-14.3 32-32l0-144 144 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-144 0 0-144z"/></svg>
                            </span>
                            <span class="button mec-dash-remove-btn lsd-apt-adjusted-day-remove">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M170.5 51.6L151.5 80l145 0-19-28.4c-1.5-2.2-4-3.6-6.7-3.6l-93.7 0c-2.7 0-5.2 1.3-6.7 3.6zm147-26.6L354.2 80 368 80l48 0 8 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-8 0 0 304c0 44.2-35.8 80-80 80l-224 0c-44.2 0-80-35.8-80-80l0-304-8 0c-13.3 0-24-10.7-24-24S10.7 80 24 80l80 0 13.8 0 36.7-55.1C140.9 9.4 158.4 0 177.1 0l93.7 0c18.7 0 36.2 9.4 46.6 24.9zM80 128l0 304c0 17.7 14.3 32 32 32l224 0c17.7 0 32-14.3 32-32l0-304L80 128zm80 64l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16z"/></svg>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="mec-form-row"><span class="button lsd-apt-adjusted-day-add"><?php esc_html_e('Add a date', 'modern-events-calendar-lite'); ?></span></div>
            <div class="mec-util-hidden">
                <div id="lsd-apt-adjusted-template-day">
                    <div class="lsd-apt-day-wrapper mec-form-row" data-day=":i:">
                        <div class="lsd-apt-day-label mec-col-2"><input type="text" class="mec-apt-date-picker" name="mec[appointments][adjusted_availability][:i:][date]" value=""></div>
                        <div class="lsd-apt-day-timeslots mec-col-9">
                            <div class="lsd-apt-day-timeslots-unavailable mec-util-hidden"><?php esc_html_e('Unavailable', 'modern-events-calendar-lite'); ?></div>
                            <div class="lsd-apt-day-timeslots-wrapper">
                                <div class="lsd-apt-day-timeslot-wrapper">
                                    <div>
                                        <?php $this->main->timepicker([
                                            'method' => $this->settings['time_format'] ?? 12,
                                            'time_hour' => 8,
                                            'time_minutes' => 0,
                                            'time_ampm' => 'AM',
                                            'name' => 'mec[appointments][adjusted_availability][:i:][0][start]',
                                            'id_key' => 'mec_appointments_adjusted_:i:_0_start_',
                                        ]); ?>
                                        <span class="lsd-apt-to"> - </span>
                                        <?php $this->main->timepicker([
                                            'method' => $this->settings['time_format'] ?? 12,
                                            'time_hour' => 6,
                                            'time_minutes' => 0,
                                            'time_ampm' => 'PM',
                                            'name' => 'mec[appointments][adjusted_availability][:i:][0][end]',
                                            'id_key' => 'mec_appointments_adjusted_:i:_0_end_',
                                        ]); ?>
                                    </div>
                                    <span class="button mec-dash-remove-btn lsd-apt-day-icon-remove">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M170.5 51.6L151.5 80l145 0-19-28.4c-1.5-2.2-4-3.6-6.7-3.6l-93.7 0c-2.7 0-5.2 1.3-6.7 3.6zm147-26.6L354.2 80 368 80l48 0 8 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-8 0 0 304c0 44.2-35.8 80-80 80l-224 0c-44.2 0-80-35.8-80-80l0-304-8 0c-13.3 0-24-10.7-24-24S10.7 80 24 80l80 0 13.8 0 36.7-55.1C140.9 9.4 158.4 0 177.1 0l93.7 0c18.7 0 36.2 9.4 46.6 24.9zM80 128l0 304c0 17.7 14.3 32 32 32l224 0c17.7 0 32-14.3 32-32l0-304L80 128zm80 64l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16z"/></svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="lsd-apt-day-icons mec-col-1">
                            <span class="button lsd-apt-adj-day-icon-plus" data-key="0">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 144L48 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l144 0 0 144c0 17.7 14.3 32 32 32s32-14.3 32-32l0-144 144 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-144 0 0-144z"/></svg>
                            </span>
                            <span class="button mec-dash-remove-btn lsd-apt-adjusted-day-remove">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M170.5 51.6L151.5 80l145 0-19-28.4c-1.5-2.2-4-3.6-6.7-3.6l-93.7 0c-2.7 0-5.2 1.3-6.7 3.6zm147-26.6L354.2 80 368 80l48 0 8 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-8 0 0 304c0 44.2-35.8 80-80 80l-224 0c-44.2 0-80-35.8-80-80l0-304-8 0c-13.3 0-24-10.7-24-24S10.7 80 24 80l80 0 13.8 0 36.7-55.1C140.9 9.4 158.4 0 177.1 0l93.7 0c18.7 0 36.2 9.4 46.6 24.9zM80 128l0 304c0 17.7 14.3 32 32 32l224 0c17.7 0 32-14.3 32-32l0-304L80 128zm80 64l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16z"/></svg>
                            </span>
                        </div>
                    </div>
                </div>
                <div id="lsd-apt-adjusted-template-timeslot">
                    <div class="lsd-apt-day-timeslot-wrapper">
                        <div>
                            <?php $this->main->timepicker([
                                'method' => $this->settings['time_format'] ?? 12,
                                'time_hour' => 8,
                                'time_minutes' => 0,
                                'time_ampm' => 'AM',
                                'name' => 'mec[appointments][adjusted_availability][:i:][:t:][start]',
                                'id_key' => 'mec_appointments_adjusted_:i:_:t:_start_',
                            ]); ?>
                            <span class="lsd-apt-to"> - </span>
                            <?php $this->main->timepicker([
                                'method' => $this->settings['time_format'] ?? 12,
                                'time_hour' => 6,
                                'time_minutes' => 0,
                                'time_ampm' => 'PM',
                                'name' => 'mec[appointments][adjusted_availability][:i:][:t:][end]',
                                'id_key' => 'mec_appointments_adjusted_:i:_:t:_end_',
                            ]); ?>
                        </div>
                        <span class="button mec-dash-remove-btn lsd-apt-day-icon-remove">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M170.5 51.6L151.5 80l145 0-19-28.4c-1.5-2.2-4-3.6-6.7-3.6l-93.7 0c-2.7 0-5.2 1.3-6.7 3.6zm147-26.6L354.2 80 368 80l48 0 8 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-8 0 0 304c0 44.2-35.8 80-80 80l-224 0c-44.2 0-80-35.8-80-80l0-304-8 0c-13.3 0-24-10.7-24-24S10.7 80 24 80l80 0 13.8 0 36.7-55.1C140.9 9.4 158.4 0 177.1 0l93.7 0c18.7 0 36.2 9.4 46.6 24.9zM80 128l0 304c0 17.7 14.3 32 32 32l224 0c17.7 0 32-14.3 32-32l0-304L80 128zm80 64l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0l0 208c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-208c0-8.8 7.2-16 16-16s16 7.2 16 16z"/></svg>
                        </span>
                    </div>
                </div>
            </div>
            <div class="mec-apt-scheduling-window-wrapper">
                <h4><?php esc_html_e('Scheduling Window', 'modern-events-calendar-lite'); ?></h4>
                <div class="mec-form-row">
                    <p class="description"><?php esc_html_e('Limit the time range that appointments can be booked.', 'modern-events-calendar-lite'); ?></p>
                    <span class="mec-tooltip">
                        <div class="box top">
                            <h5 class="title"><?php esc_html_e('Scheduling Window', 'modern-events-calendar-lite'); ?></h5>
                            <div class="content"><p><?php esc_attr_e('This is the maximum time into the future that someone can schedule an appointment with you. Uncheck this option to allow people to book indefinitely into the future.', 'modern-events-calendar-lite'); ?></p></div>
                        </div>
                        <i title="" class="dashicons-before dashicons-editor-help"></i>
                    </span>
                </div>

                <div>
                    <p class="description"><?php esc_html_e('Maximum time in advance that an appointment can be booked', 'modern-events-calendar-lite'); ?></p>
                </div>
                <div class="mec-form-row sw-apt">
                    <span>
                        <input type="hidden" name="mec[appointments][scheduling_advance_status]" value="0">
                        <input title="" type="checkbox" name="mec[appointments][scheduling_advance_status]" value="1" <?php echo !isset($config['scheduling_advance_status']) || $config['scheduling_advance_status'] ? 'checked' : ''; ?> onchange="jQuery('#mec_appointments_scheduling_advance').prop('readonly', !jQuery(this).is(':checked'));">
                    </span>
                    <input title="" class="mec-col-2" id="mec_appointments_scheduling_advance" <?php echo isset($config['scheduling_advance_status']) && !$config['scheduling_advance_status'] ? 'readonly' : ''; ?> type="number" name="mec[appointments][scheduling_advance]" value="<?php echo isset($config['scheduling_advance']) && $config['scheduling_advance'] ? $config['scheduling_advance'] : 60; ?>">
                    <span><?php esc_html_e('days', 'modern-events-calendar-lite'); ?></span>
                </div>

                <div>
                    <p class="description"><?php esc_html_e('Minimum time before the appointment start that it can be booked.', 'modern-events-calendar-lite'); ?></p>
                </div>
                <div class="mec-form-row sw-apt">
                    <span>
                        <input type="hidden" name="mec[appointments][scheduling_before_status]" value="0">
                        <input title="" type="checkbox" name="mec[appointments][scheduling_before_status]" value="1" <?php echo !isset($config['scheduling_before_status']) || $config['scheduling_before_status'] ? 'checked' : ''; ?> onchange="jQuery('#mec_appointments_scheduling_before').prop('readonly', !jQuery(this).is(':checked'));">
                    </span>
                    <input title="" class="mec-col-2" id="mec_appointments_scheduling_before" <?php echo isset($config['scheduling_before_status']) && !$config['scheduling_before_status'] ? 'readonly' : ''; ?> type="number" name="mec[appointments][scheduling_before]" value="<?php echo isset($config['scheduling_before']) && $config['scheduling_before'] ? $config['scheduling_before'] : 4; ?>">
                    <span><?php esc_html_e('hours', 'modern-events-calendar-lite'); ?></span>
                </div>
            </div>
        </div>
        <?php
    }
}

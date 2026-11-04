// Set datepicker default value.
var datepicker_format = 'yy-mm-dd';

function mecEnhanceDatepickerAccessibility($inputs)
{
    if(!$inputs || !$inputs.length) return;

    $inputs.attr('aria-haspopup', 'dialog');
    $inputs.off('focus.mecDatepickerA11y').on('focus.mecDatepickerA11y', function()
    {
        window.setTimeout(function()
        {
            var $datepicker = jQuery('#ui-datepicker-div');
            if(!$datepicker.length) return;

            $datepicker.attr({
                role: 'dialog',
                'aria-live': 'polite',
                'aria-label': (typeof mecdata !== 'undefined' && mecdata.a11y_calendar_dialog) ? mecdata.a11y_calendar_dialog : 'Calendar date picker'
            });
        }, 0);
    });
}

jQuery(document).ready(function($)
{
    // Image picker on terms menu
    $('.mec_upload_image_button').click(function(event)
    {
        event.preventDefault();

        var real_ajax_url = wp.ajax.settings.url;
        wp.ajax.settings.url = real_ajax_url + '?mec_fes=1';

        var post_id = $(this).data('post-id');
        if(post_id && post_id !== -1) wp.media.model.settings.post.id = post_id;
        if(post_id === -1) wp.media.model.settings.post.id = null;

        var preview_id = 'mec_thumbnail_img';
        var input_id = 'mec_thumbnail';

        if($(this).data('preview-id')) preview_id = $(this).data('preview-id');
        if($(this).data('input-id')) input_id = $(this).data('input-id');

        var frame;
        if(frame)
        {
            frame.open();
            return;
        }

        frame = wp.media();
        frame.on('select', function()
        {
            // Grab the selected attachment.
            var attachment = frame.state().get('selection').first();

            $('#'+preview_id).html('<img src="'+attachment.attributes.url+'" alt="Featured image preview" style="max-width: 100%;" />');
            $('#'+input_id).val(attachment.attributes.url);

            $('.mec_remove_image_button').toggleClass('mec-util-hidden');

            frame.close();
        });

        frame.open();
    });

    // Image remover on terms menu
    $('.mec_remove_image_button').click(function(event)
    {
        event.preventDefault();

        var preview_id = 'mec_thumbnail_img';
        var input_id = 'mec_thumbnail';

        if($(this).data('preview-id')) preview_id = $(this).data('preview-id');
        if($(this).data('input-id')) input_id = $(this).data('input-id');

        $('#'+preview_id).html('');
        $('#'+input_id).val('');

        $('.mec_remove_image_button').toggleClass('mec-util-hidden');
    });

    // Image picker on add event menu for location
    $('.mec_location_upload_image_button').click(function(event)
    {
        event.preventDefault();

        var frame;
        if(frame)
        {
            frame.open();
            return;
        }

        frame = wp.media();
        frame.on('select', function()
        {
            // Grab the selected attachment.
            var attachment = frame.state().get('selection').first();

            $('#mec_location_thumbnail_img').html('<img src="'+attachment.attributes.url+'" />');
            $('#mec_location_thumbnail').val(attachment.attributes.url);

            $('.mec_location_remove_image_button').toggleClass('mec-util-hidden');

            frame.close();
        });

        frame.open();
    });

    // Image remover on add event menu for location
    $('.mec_location_remove_image_button').click(function(event)
    {
        event.preventDefault();

        $('#mec_location_thumbnail_img').html('');
        $('#mec_location_thumbnail').val('');

        $('.mec_location_remove_image_button').toggleClass('mec-util-hidden');
    });

    // Image picker on add event menu for organizer
    $('.mec_organizer_upload_image_button').click(function(event)
    {
        event.preventDefault();

        var frame;
        if(frame)
        {
            frame.open();
            return;
        }

        frame = wp.media();
        frame.on('select', function()
        {
            // Grab the selected attachment.
            var attachment = frame.state().get('selection').first();

            $('#mec_organizer_thumbnail_img').html('<img src="'+attachment.attributes.url+'" />');
            $('#mec_organizer_thumbnail').val(attachment.attributes.url);

            $('.mec_organizer_remove_image_button').toggleClass('mec-util-hidden');

            frame.close();
        });

        frame.open();
    });

    // Image remover on add event menu for organizer
    $('.mec_organizer_remove_image_button').click(function(event)
    {
        event.preventDefault();

        $('#mec_organizer_thumbnail_img').html('');
        $('#mec_organizer_thumbnail').val('');

        $('.mec_organizer_remove_image_button').toggleClass('mec-util-hidden');
    });

    // Image remover on frontend event submission menu
    $('#mec_fes_remove_image_button').click(function(event)
    {
        event.preventDefault();

        $('#mec_fes_thumbnail_img').html('');
        $('#mec_fes_thumbnail').val('');
        $('#mec_featured_image_file').val('');

        $('#mec_fes_remove_image_button').addClass('mec-util-hidden');
    });

    // Location Image remover on frontend event submission menu
    $('#mec_fes_location_remove_image_button').click(function(event)
    {
        event.preventDefault();

        $('#mec_fes_location_thumbnail_img').html('');
        $('#mec_fes_location_thumbnail').val('');
        $('#mec_fes_location_thumbnail_file').val('');

        $('#mec_fes_location_remove_image_button').addClass('mec-util-hidden');
    });

    // Organizer Image remover on frontend event submission menu
    $('#mec_fes_organizer_remove_image_button').click(function(event)
    {
        event.preventDefault();

        $('#mec_fes_organizer_thumbnail_img').html('');
        $('#mec_fes_organizer_thumbnail').val('');
        $('#mec_fes_organizer_thumbnail_file').val('');

        $('#mec_fes_organizer_remove_image_button').addClass('mec-util-hidden');
    });

    // Sponsor Image remover on frontend event submission menu
    $('#mec_fes_sponsor_remove_image_button').click(function(event)
    {
        event.preventDefault();

        $('#mec_fes_sponsor_thumbnail_img').html('');
        $('#mec_fes_sponsor_thumbnail').val('');
        $('#mec_fes_sponsor_thumbnail_file').val('');

        $('#mec_fes_sponsor_remove_image_button').addClass('mec-util-hidden');
    });

    // Speaker Image remover on frontend event submission menu
    $('#mec_fes_speaker_remove_image_button').click(function(event)
    {
        event.preventDefault();

        $('#mec_fes_speaker_thumbnail_img').html('');
        $('#mec_fes_speaker_thumbnail').val('');
        $('#mec_fes_speaker_thumbnail_file').val('');

        $('#mec_fes_speaker_remove_image_button').addClass('mec-util-hidden');
    });

    var date_splite;
    if(typeof mec_admin_localize !== 'undefined')
    {
        date_splite = mec_admin_localize.datepicker_format.split('&');
        if(date_splite[0] !== undefined && date_splite.length == 2) datepicker_format = date_splite[0];
    }
    else if(typeof mecdata !== 'undefined')
    {
        date_splite = mecdata.datepicker_format.split( '&' );
        if(date_splite[0] !== undefined && date_splite.length == 2) datepicker_format = date_splite[0];
    }

    $('[id^="mec-book-form-btn-step"]').on('click',function()
    {
        setTimeout(function()
        {
            if($.fn.datepicker)
            {
                if('date' !== $('.mec-date-picker').prop('type'))
                {
                    $('.mec-date-picker').datepicker(
                    {
                        changeYear: true,
                        changeMonth: true,
                        dateFormat: datepicker_format,
                        gotoCurrent: true,
                        yearRange: 'c-3:c+5',
                    });
                }

            }
        }, 1000);
    });

    if($.fn.datepicker)
    {
        mecEnhanceDatepickerAccessibility($('.mec-date-picker, .mec_date_picker_dynamic_format, .mec_date_picker, .mec_date_picker_dynamic_format_start, .mec_date_picker_dynamic_format_end, #mec_start_date, #mec_end_date, #mec_date_repeat_end_at_date'));

        $('.mec-date-picker').datepicker(
        {
            changeYear: true,
            changeMonth: true,
            dateFormat: datepicker_format,
            gotoCurrent: true,
            yearRange: 'c-3:c+5',
        });

        $('#mec_start_date').datepicker(
        {
            changeYear: true,
            changeMonth: true,
            dateFormat: datepicker_format,
            gotoCurrent: true,
            yearRange: 'c-3:c+5',
            onSelect: function(value, i)
            {
                const end = $("#mec_end_date");
                if(value !== i.lastVal)
                {
                    end.datepicker("option", "minDate", value);
                }

                if(end.val() === '')
                {
                    end.val(value);
                }
            }
        });

        $('#mec_end_date').datepicker(
        {
            changeYear: true,
            changeMonth: true,
            dateFormat: datepicker_format,
            gotoCurrent: true,
            yearRange: 'c-3:c+5',
            onSelect: function(value, i)
            {
                const start = $("#mec_start_date");
                if(value !== i.lastVal)
                {
                    start.datepicker("option", "maxDate", value);
                }

                if(start.val() === '')
                {
                    start.val(value);
                }
            }
        });

        $('#mec_date_repeat_end_at_date').datepicker(
        {
            changeYear: true,
            changeMonth: true,
            dateFormat: datepicker_format,
            gotoCurrent: true,
            yearRange: 'c-3:c+5',
        });

        $('.mec_date_picker_dynamic_format').datepicker(
        {
            changeYear: true,
            changeMonth: true,
            dateFormat: datepicker_format,
            gotoCurrent: true,
            yearRange: 'c-3:c+5',
        });

        $('.mec_date_picker').datepicker(
        {
            changeYear: true,
            changeMonth: true,
            dateFormat: 'yy-mm-dd',
            gotoCurrent: true,
            yearRange: 'c-3:c+5',
        });

        $('.mec_date_picker_dynamic_format_start').datepicker(
        {
            changeYear: true,
            changeMonth: true,
            dateFormat: datepicker_format,
            gotoCurrent: true,
            yearRange: 'c-1:c+5',
            onSelect: function(date)
            {
                var selectedDate;

                try
                {
                    selectedDate = $.datepicker.parseDate(datepicker_format, date);
                }
                catch(e)
                {
                    selectedDate = new Date(date);
                }

                if(!(selectedDate instanceof Date) || isNaN(selectedDate.getTime())) return;

                var endDate = new Date(selectedDate.getTime());

                var $end_picker = $(this).next();
                $end_picker.datepicker("option", "minDate", endDate);
                $end_picker.datepicker("option", "maxDate", '+5y');

                $(this).trigger('change');
            }
        });

        $('.mec_date_picker_dynamic_format_end').datepicker(
        {
            changeYear: true,
            changeMonth: true,
            dateFormat: datepicker_format,
            gotoCurrent: true,
            yearRange: 'c-1:c+5',
        });

        trigger_period_picker();
    }

    // Initialize WP Color Picker
    if($.fn.wpColorPicker) jQuery('.mec-color-picker').wpColorPicker();

    $('#mec_location_id').on('change', function()
    {
        mec_location_toggle();
    });

    $('#mec_organizer_id').on('change', function()
    {
        mec_organizer_toggle();
        var mec_organizer_val = parseInt($(this).val());
        var mec_additional_organizer = $(this).parent().parent().find('#mec-additional-organizer-wrap');

        if(mec_organizer_val != 1) mec_additional_organizer.show();
        else mec_additional_organizer.hide();
    });

    mec_location_toggle();
    mec_organizer_toggle();

    $('#mec_repeat').on('change', function()
    {
        mec_repeat_toggle();
    });

    mec_repeat_toggle();

    $('#mec_repeat_type').on('change', function()
    {
        mec_repeat_type_toggle();
    });

    mec_repeat_type_toggle();

    $('#mec_bookings_limit_unlimited').on('change', function()
    {
        mec_bookings_unlimited_toggle();
    });

    $('#mec_add_in_days').on('click', function()
    {
        var allday = $(this).data('allday');

        var start = $('#mec_exceptions_in_days_start_date').val();
        if(start === '') return false;

        var end = $('#mec_exceptions_in_days_end_date').val();
        if(end === '') return false;

        var start_hour = $('#mec_exceptions_in_days_start_hour').val();
        if(start_hour.length === 1) start_hour = '0'+start_hour;

        var start_minutes = $('#mec_exceptions_in_days_start_minutes').val();
        if(start_minutes.length === 1) start_minutes = '0'+start_minutes;

        var start_ampm = $('#mec_exceptions_in_days_start_ampm').val();
        if(typeof start_ampm === 'undefined') start_ampm = '';

        var end_hour = $('#mec_exceptions_in_days_end_hour').val();
        if(end_hour.length === 1) end_hour = '0'+end_hour;

        var end_minutes = $('#mec_exceptions_in_days_end_minutes').val();
        if(end_minutes.length === 1) end_minutes = '0'+end_minutes;

        var end_ampm = $('#mec_exceptions_in_days_end_ampm').val();
        if(typeof end_ampm === 'undefined') end_ampm = '';

        var value = start + ':' + end + ':' + start_hour + '-' + start_minutes + '-' + start_ampm + ':' + end_hour + '-' + end_minutes + '-' + end_ampm;
        var label = start + ' <span class="mec-time-picker-label '+(allday ? 'mec-util-hidden' : '')+'">' + start_hour + ':' + start_minutes + ' ' + start_ampm + '</span> - ' + end + ' <span class="mec-time-picker-label '+(allday ? 'mec-util-hidden' : '')+'">' + end_hour + ':' + end_minutes + ' ' + end_ampm + '</span>';

        // Don't add exactly same occurrences
        if($('#mec_in_days input[value="'+value+'"]').length > 0) return false;

        var $key = $('#mec_new_in_days_key');

        var key = $key.val();
        var html = $('#mec_new_in_days_raw').html().replace(/:i:/g, key).replace(/:val:/g, value).replace(/:label:/g, label);

        $('#mec_in_days').append(html);
        $key.val(parseInt(key)+1);
    });

    $('#mec_edit_in_days').on('click', function()
    {
        // Form
        const $form = $('#mec-in-days-form');

        const modify_id = $form.data('modify');
        const $row = $('#mec_in_days_row'+modify_id);

        var allday = $(this).data('allday');

        var start = $('#mec_exceptions_in_days_start_date').val();
        if(start === '') return false;

        var end = $('#mec_exceptions_in_days_end_date').val();
        if(end === '') return false;

        var start_hour = $('#mec_exceptions_in_days_start_hour').val();
        if(start_hour.length === 1) start_hour = '0'+start_hour;

        var start_minutes = $('#mec_exceptions_in_days_start_minutes').val();
        if(start_minutes.length === 1) start_minutes = '0'+start_minutes;

        var start_ampm = $('#mec_exceptions_in_days_start_ampm').val();
        if(typeof start_ampm === 'undefined') start_ampm = '';

        var end_hour = $('#mec_exceptions_in_days_end_hour').val();
        if(end_hour.length === 1) end_hour = '0'+end_hour;

        var end_minutes = $('#mec_exceptions_in_days_end_minutes').val();
        if(end_minutes.length === 1) end_minutes = '0'+end_minutes;

        var end_ampm = $('#mec_exceptions_in_days_end_ampm').val();
        if(typeof end_ampm === 'undefined') end_ampm = '';

        var value = start + ':' + end + ':' + start_hour + '-' + start_minutes + '-' + start_ampm + ':' + end_hour + '-' + end_minutes + '-' + end_ampm;
        var label = start + ' <span class="mec-time-picker-label '+(allday ? 'mec-util-hidden' : '')+'">' + start_hour + ':' + start_minutes + ' ' + start_ampm + '</span> - ' + end + ' <span class="mec-time-picker-label '+(allday ? 'mec-util-hidden' : '')+'">' + end_hour + ':' + end_minutes + ' ' + end_ampm + '</span>';

        $row.find($('input[type=hidden]')).val(value);
        $row.find($('.mec-in-days-day')).html(label);

        // Reset Dates
        $form.parent().find($('input[type=text]')).val('');

        // Modification Mode
        $form.removeClass('mec-in-days-edit-mode').addClass('mec-in-days-add-mode').removeData('modify');
    });

    $('#mec_cancel_in_days').on('click', function()
    {
        // Form
        let $form = $('#mec-in-days-form');

        // Reset Dates
        $form.parent().find($('input[type=text]')).val('');

        // Modification Mode
        $form.removeClass('mec-in-days-edit-mode').addClass('mec-in-days-add-mode').removeData('modify');
    });

    $('#mec_add_not_in_days').on('click', function()
    {
        let date = $('#mec_exceptions_not_in_days_date').val();
        if(date === '') return false;

        let d = date.replaceAll('-', '');
        d = d.replaceAll('/', '');
        d = d.replaceAll('.', '');


        let $wrapper = $('#mec_not_in_days');
        let $key = $('#mec_new_not_in_days_key');

        let c = 'mec-date-'+d;
        if($wrapper.find($('.'+c)).length) return;

        let key = $key.val();
        let html = $('#mec_new_not_in_days_raw').html().replace(/:i:/g, key).replace(/:d:/g, d).replace(/:val:/g, date);

        $wrapper.append(html);
        $key.val(parseInt(key)+1);
    });

    $('#mec_add_ticket_button').on('click', function()
    {
        let $key = $('#mec_new_ticket_key');
        let key = $key.val();
        let html = $('#mec_new_ticket_raw').html().replace(/:i:/g, key);

        $('#mec_tickets').append(html);
        $key.val(parseInt(key)+1);

        mec_init_sortable_sections();

        $('.mec_add_price_date_button').off('click').on('click', function()
        {
            mec_handle_add_price_date_button(this);
        });

        $.each($(".mec-select2"), function(i,v)
        {
            if($(v).attr('name').search(":i:") > 0)
            {
                return;
            }

            if(typeof $(v).data('select2-id') == 'undefined')
            {
                $(v).select2();
            }
        });

        trigger_period_picker();
    });

    $('.mec_add_price_date_button').off('click').on('click', function()
    {
        mec_handle_add_price_date_button(this);
    });

    mec_hourly_schedule_add_day_listener();
    mec_init_sortable_sections();

    $('#mec_add_fee_button').on('click', function()
    {
        var key = $('#mec_new_fee_key').val();
        var html = $('#mec_new_fee_raw').html().replace(/:i:/g, key);

        $('#mec_fees_list').append(html);
        $('#mec_new_fee_key').val(parseInt(key)+1);

        mec_init_sortable_sections();
    });

    $('#mec_add_ticket_variation_button').on('click', function()
    {
        var key = $('#mec_new_ticket_variation_key').val();
        var html = $('#mec_new_ticket_variation_raw').html().replace(/:i:/g, key);

        $('#mec_ticket_variations_list').append(html);
        $('#mec_new_ticket_variation_key').val(parseInt(key)+1);

        mec_init_sortable_sections();
    });

    $('.mec-form-row.mec-available-color-row span').on('click', function()
    {
        $('.mec-form-row.mec-available-color-row span').removeClass('color-selected');
        $(this).addClass('color-selected');
    });

    $('#mec_reg_form_field_types button').on('click', function()
    {
        var type = $(this).data('type');

        if (type == 'mec_email') {
            if ($('#mec_reg_form_fields').find('input[value="mec_email"][type="hidden"]').length) {
                return false;
            }
        }

        if (type == 'name') {
            if ($('#mec_reg_form_fields').find('input[value="name"][type="hidden"]').length) {
                return false;
            }
        }

        var key  = $('#mec_new_reg_field_key').val();
        var html = $('#mec_reg_field_'+type).html().replace(/:i:/g, key);

        $('#mec_reg_form_fields').append(html);
        $('#mec_new_reg_field_key').val(parseInt(key)+1);

        // Set onclick listener for add option fields
        mec_reg_fields_option_listeners();
        mec_refresh_booking_condition_editors('reg');
    });

    // Set onclick listener for add option fields
    mec_reg_fields_option_listeners();
    mec_refresh_booking_condition_editors('reg');

    // Advanced Repeating
    $('#mec-advanced-wraper ul > ul > li').click(function()
    {
        if($(this).attr('class') == '') $(this).attr('class', 'mec-active');
        else $(this).attr('class', '');

        $('#mec_date_repeat_advanced').val($('#mec-advanced-wraper div:first-child > ul').find('.mec-active').find('span').text().slice(0, -1));
    });

    $('#mec_event_form_field_types button').on('click', function()
    {
        var type = $(this).data('type');

        var key  = $('#mec_new_event_field_key').val();
        var html = $('#mec_event_field_'+type).html().replace(/:i:/g, key);

        $('#mec_event_form_fields').append(html);
        $('#mec_new_event_field_key').val(parseInt(key)+1);

        // Set onclick listener for add option fields
        mec_event_fields_option_listeners();
    });

    // Set onclick listener for add option fields
    mec_event_fields_option_listeners();

    $('#mec_bfixed_form_field_types button').on('click', function()
    {
        var type = $(this).data('type');

        var key  = $('#mec_new_bfixed_field_key').val();
        var html = $('#mec_bfixed_field_'+type).html().replace(/:i:/g, key);

        $('#mec_bfixed_form_fields').append(html);
        $('#mec_new_bfixed_field_key').val(parseInt(key)+1);

        // Set onclick listener for add option fields
        mec_bfixed_fields_option_listeners();
        mec_refresh_booking_condition_editors('bfixed');
    });

    // Set onclick listener for add option fields
    mec_bfixed_fields_option_listeners();
    mec_refresh_booking_condition_editors('bfixed');

    jQuery(document).on('change', '.mec-booking-condition-enabled, .mec-booking-condition-source, .mec-booking-condition-option', function()
    {
        const $trigger = jQuery(this);
        const $conditionBox = jQuery(this).closest('.mec-booking-condition-box');
        const prefix = String($conditionBox.data('conditionPrefix') || '');

        if($trigger.hasClass('mec-booking-condition-source'))
        {
            $conditionBox.data('currentSourceFieldId', String($trigger.val() || ''));
            $conditionBox.data('currentOptionKey', '');
            $conditionBox.find('.mec-booking-condition-option').val('');
        }
        else if($trigger.hasClass('mec-booking-condition-option'))
        {
            $conditionBox.data('currentOptionKey', String($trigger.val() || ''));
        }

        mec_refresh_booking_condition_editor($conditionBox);
        if(prefix) mec_refresh_booking_condition_editors(prefix);
    });

    jQuery(document).on('input change', '#mec_reg_form_fields input[name*="[label]"], #mec_bfixed_form_fields input[name*="[label]"]', function()
    {
        const $field = jQuery(this).closest('li[id^="mec_reg_fields_"], li[id^="mec_bfixed_fields_"]');
        if($field.attr('id') && $field.attr('id').indexOf('mec_reg_fields_') === 0) mec_refresh_booking_condition_editors('reg');
        if($field.attr('id') && $field.attr('id').indexOf('mec_bfixed_fields_') === 0) mec_refresh_booking_condition_editors('bfixed');
    });

    // Additional Organizers
    mec_additional_organizers_listeners();

    // Show / Hide Password
    $('.mec-show-hide-password').on('click', function()
    {
        var $input = $(this).siblings("input");
        var current = $input.attr('type');

        if(current === 'password') $input.attr('type', 'text');
        else $input.attr('type', 'password');
    });

    // FAQ
    $('#mec_add_faq_button').on('click', function()
    {
        const $key = $('#mec_new_faq_key');
        const key = $key.val();
        const html = $('#mec_new_faq_raw').html().replace(/:i:/g, key);

        $('#mec_faq_list').append(html);
        $key.val(parseInt(key)+1);

        mec_init_sortable_sections();
    });

    // Appointments
    // Move the selector to the metabox header
    var $header = $('#mec_metabox_details .hndle');
    // If .hndle is not found, try .postbox-header (newer WP versions)
    if ($header.length === 0) {
        $header = $('#mec_metabox_details .postbox-header');
    }
    $('.mec-event-appointment-type-wrap').appendTo($header).removeClass('mec-util-hidden').on('click', function(e) {
        e.stopPropagation();
    });

    $('#mec_entity_type_select').on('change', function()
    {
        const entity_type = $(this).val();
        const $fes_form = $('#mec_fes_form');
        const $metabox_details = $('#mec_metabox_details');
        const $appointment_form_wrapper = $('.mec-appointment-form-wrap');

        if (entity_type === 'appointment')
        {
            $fes_form.removeClass('mec-entity-type-event').addClass('mec-entity-type-appointment');
            $metabox_details.removeClass('mec-entity-type-event').addClass('mec-entity-type-appointment');

            $appointment_form_wrapper.removeClass('mec-util-hidden');
        }
        else
        {
            $fes_form.removeClass('mec-entity-type-appointment').addClass('mec-entity-type-event');
            $metabox_details.removeClass('mec-entity-type-appointment').addClass('mec-entity-type-event');

            $appointment_form_wrapper.addClass('mec-util-hidden');
        }
    });

    $('#mec_entity_type_select').trigger('change');

    const $repeatType = $('#mec_appointments_availability_repeat_type');
    if($.fn.datepicker)
    {
        $('#mec_appointments_start_date').datepicker({
            changeYear: true,
            changeMonth: true,
            dateFormat: datepicker_format,
            gotoCurrent: true,
            yearRange: 'c-3:c+5',
        });
    }
    function mec_toggle_repeat_type()
    {
        const val = $repeatType.val();
        if (val === 'no_repeat')
        {
            $('.lsd-apt-days-wrapper').addClass('mec-util-hidden');
            $('.lsd-apt-adjusted-title').addClass('mec-util-hidden');
            $('.lsd-apt-start-date-wrapper').addClass('mec-util-hidden');
        }
        else
        {
            $('.lsd-apt-days-wrapper').removeClass('mec-util-hidden');
            $('.lsd-apt-adjusted-title').removeClass('mec-util-hidden');
            $('.lsd-apt-start-date-wrapper').removeClass('mec-util-hidden');
        }
    }
    $repeatType.on('change', mec_toggle_repeat_type);
    mec_toggle_repeat_type();

    $(document).on('click', '.lsd-apt-day-icon-remove', function()
    {
        const $button = $(this);
        const $timeslot = $button.parent();
        const $timeslots = $button.parents('.lsd-apt-day-timeslots');

        $timeslot.remove();
        if ($timeslots.find('.lsd-apt-day-timeslot-wrapper').length === 0)
        {
            $timeslots.find('.lsd-apt-day-timeslots-unavailable').removeClass('mec-util-hidden');
        }
    });

    $(document).on('click', '.lsd-apt-day-icon-plus', function()
    {
        const $button = $(this);
        let key = $button.data('key');

        const $day = $button.parents('.lsd-apt-day-wrapper');
        const day = $day.data('key');

        const $timeslots = $day.find('.lsd-apt-day-timeslots-wrapper');
        const $template = $('#lsd-apt-day-templates-'+day+'-timeslot');

        key = key + 1;
        const html = $template.html().replace(/:t:/g, key);
        $timeslots.append(html);

        $button.data('key', key);
        $day.find('.lsd-apt-day-timeslots-unavailable').addClass('mec-util-hidden');
    });

    $(document).on('click', '.lsd-apt-day-icon-copy', function ()
    {
        const $button = $(this);
        const $sourceDay = $button.closest('.lsd-apt-day-wrapper');
        const sourceDayKey = $sourceDay.data('key');

        const $weekWrapper = $sourceDay.parent();
        const $sourceTimeslots = $sourceDay.find('.lsd-apt-day-timeslots-wrapper .lsd-apt-day-timeslot-wrapper');

        $weekWrapper.find('.lsd-apt-day-wrapper').each(function ()
        {
            const $targetDay = $(this);

            const targetDayKey = $targetDay.data('key');
            if (targetDayKey === sourceDayKey) return;

            const isActive = $targetDay.find('.lsd-apt-day-timeslots-wrapper .lsd-apt-day-timeslot-wrapper').length > 0;
            if (!isActive) return;

            const $targetWrapper = $targetDay.find('.lsd-apt-day-timeslots-wrapper');
            $targetWrapper.empty();

            $sourceTimeslots.each(function ()
            {
                const $sourceSlot = $(this);
                const $clone = $sourceSlot.clone(true);

                $sourceSlot.find('select').each(function (i) {
                    const value = $(this).val();
                    $clone.find('select').eq(i).val(value);
                });

                $sourceSlot.find('input').each(function (i) {
                    const value = $(this).val();
                    $clone.find('input').eq(i).val(value);
                });

                $clone.find('[name], [id], [for]').each(function ()
                {
                    $.each(this.attributes, function () {
                        if (this.name === 'name' || this.name === 'id' || this.name === 'for') {
                            this.value = this.value
                            .replace(`[availability][${sourceDayKey}]`, `[availability][${targetDayKey}]`)
                            .replace(`availability_${sourceDayKey}_`, `availability_${targetDayKey}_`);
                        }
                    });
                });

                $targetWrapper.append($clone);
            });
        });
    });

    $(document).on('click', '.lsd-apt-adjusted-day-add', function()
    {
        const $wrapper = $('.lsd-apt-adjusted-days-wrapper');
        let key = $wrapper.data('key') || 0;

        key = key + 1;
        let html = $('#lsd-apt-adjusted-template-day').html();
        html = html.replace(/:i:/g, key);

        $wrapper.append(html);
        $wrapper.data('key', key);

        if($.fn.datepicker)
        {
            $wrapper.find('.mec-apt-date-picker').last().datepicker({
                changeYear: true,
                changeMonth: true,
                dateFormat: datepicker_format,
                gotoCurrent: true,
                yearRange: 'c-3:c+5',
            });
        }
    });

    $(document).on('click', '.lsd-apt-adjusted-day-remove', function()
    {
        $(this).closest('.lsd-apt-day-wrapper').remove();
    });

    $(document).on('click', '.lsd-apt-adj-day-icon-plus', function()
    {
        const $button = $(this);
        let key = $button.data('key');
        const $day = $button.closest('.lsd-apt-day-wrapper');
        const day = $day.data('day');
        const $timeslots = $day.find('.lsd-apt-day-timeslots-wrapper');

        key = key + 1;
        let html = $('#lsd-apt-adjusted-template-timeslot').html();

        html = html.replace(/:i:/g, day).replace(/:t:/g, key);
        $timeslots.append(html);

        $button.data('key', key);
        $day.find('.lsd-apt-day-timeslots-unavailable').addClass('mec-util-hidden');
    });
});

function trigger_period_picker()
{
    jQuery('.mec-date-picker-start').datepicker(
    {
        changeYear: true,
        changeMonth: true,
        dateFormat: 'yy-mm-dd',
        gotoCurrent: true,
        yearRange: 'c-1:c+5',
        onSelect: function(date)
        {
            const selectedDate = new Date(date);
            const endDate = new Date(selectedDate.getTime());

            const $end_picker = jQuery(this).next();
            $end_picker.datepicker("option", "minDate", endDate);
            $end_picker.datepicker("option", "maxDate", '+5y');
            jQuery(this).trigger('change');
        }
    });

    jQuery('.mec-date-picker-end').datepicker(
    {
        changeYear: true,
        changeMonth: true,
        dateFormat: 'yy-mm-dd',
        gotoCurrent: true,
        yearRange: 'c-1:c+5',
    });
}

function mec_location_toggle()
{
    if(jQuery('#mec_location_id').val() != '0') jQuery('#mec_location_new_container').hide();
    else jQuery('#mec_location_new_container').show();
}

function mec_organizer_toggle()
{
    if(jQuery('#mec_organizer_id').val() != '0') jQuery('#mec_organizer_new_container').hide();
    else jQuery('#mec_organizer_new_container').show();
}

function mec_repeat_toggle()
{
    if(jQuery('#mec_repeat').is(':checked')) jQuery('.mec-form-repeating-event-row').show();
    else jQuery('.mec-form-repeating-event-row').hide();
}

function mec_repeat_type_toggle()
{
    var repeat_type = jQuery('#mec_repeat_type').val();

    if(repeat_type == 'certain_weekdays')
    {
        jQuery('#mec_repeat_interval_container').hide();
        jQuery('#mec_repeat_certain_weekdays_container').show();
        jQuery('#mec_exceptions_in_days_container').hide();
        jQuery('#mec_end_wrapper').show();
        jQuery('#mec-advanced-wraper').hide();
    }
    else if(repeat_type == 'custom_days')
    {
        jQuery('#mec_repeat_interval_container').hide();
        jQuery('#mec_repeat_certain_weekdays_container').hide();
        jQuery('#mec_exceptions_in_days_container').show();
        jQuery('#mec_end_wrapper').hide();
        jQuery('#mec-advanced-wraper').hide();
    }
    else if(repeat_type == 'advanced')
    {
        jQuery('#mec_repeat_interval_container').hide();
        jQuery('#mec_repeat_certain_weekdays_container').hide();
        jQuery('#mec_exceptions_in_days_container').hide();
        jQuery('#mec_end_wrapper').show();
        jQuery('#mec-advanced-wraper').show();
    }
    else if(repeat_type != 'daily' && repeat_type != 'weekly' && repeat_type != 'monthly')
    {
        jQuery('#mec_repeat_interval_container').hide();
        jQuery('#mec_repeat_certain_weekdays_container').hide();
        jQuery('#mec_exceptions_in_days_container').hide();
        jQuery('#mec_end_wrapper').show();
        jQuery('#mec-advanced-wraper').hide();
    }
    else
    {
        jQuery('#mec_repeat_interval_container').show();
        jQuery('#mec_repeat_certain_weekdays_container').hide();
        jQuery('#mec_exceptions_in_days_container').hide();
        jQuery('#mec_end_wrapper').show();
        jQuery('#mec-advanced-wraper').hide();
    }
}

function mec_in_days_remove(i)
{
    jQuery('#mec_in_days_row'+i).remove();
}

function mec_in_days_edit(i)
{
    // Date
    let $row = jQuery('#mec_in_days_row'+i);
    let value = $row.find(jQuery('input[type=hidden]')).val();

    const values = value.split(':');
    const start_times = values[2].split('-')
    const end_times = values[3].split('-')

    // Form
    let $form = jQuery('#mec-in-days-form');

    // Set Dates
    jQuery('#mec_exceptions_in_days_start_date').val(values[0]);
    jQuery('#mec_exceptions_in_days_end_date').val(values[1]);

    // Set Times
    jQuery('#mec_exceptions_in_days_start_hour').val(parseInt(start_times[0]));
    jQuery('#mec_exceptions_in_days_start_minutes').val(parseInt(start_times[1]));
    jQuery('#mec_exceptions_in_days_start_ampm').val(start_times[2]);

    jQuery('#mec_exceptions_in_days_end_hour').val(parseInt(end_times[0]));
    jQuery('#mec_exceptions_in_days_end_minutes').val(parseInt(end_times[1]));
    jQuery('#mec_exceptions_in_days_end_ampm').val(end_times[2]);

    // Modification Mode
    $form.removeClass('mec-in-days-add-mode').addClass('mec-in-days-edit-mode').data('modify', i);
}

function mec_not_in_days_remove(i)
{
    jQuery('#mec_not_in_days_row'+i).remove();
}

function mec_bookings_unlimited_toggle()
{
    jQuery('#mec_bookings_limit').toggleClass('mec-util-hidden');
}

function mec_hourly_schedule_add_day_listener()
{
    jQuery('.mec-add-hourly-schedule-day-button').each(function()
    {
        jQuery(this).off('click').on('click', function()
        {
            var k = jQuery(this).data('key');
            var raw = jQuery(this).data('raw');
            var append = jQuery(this).data('append');

            var key = jQuery(k).val();
            var html = jQuery(raw).html().replace(/:d:/g, key).replace(/:dd:/g, parseInt(key)+1);

            jQuery(append).append(html);
            jQuery(k).val(parseInt(key)+1);

            mec_hourly_schedule_listeners();
        });

        mec_hourly_schedule_listeners();
    });
}

function mec_bookings_after_occurrence_cancel_listener()
{
    jQuery('.mec-occurrences-bookings-after-occurrences-cancel').off('change').on('change', function()
    {
        const $dropdown = jQuery(this);
        const value = $dropdown.val();
        const $moveWrapper = $dropdown.next();

        if(value === 'move' || value === 'move_notify')
        {
            $moveWrapper.removeClass('w-hidden');
        }
        else
        {
            $moveWrapper.addClass('w-hidden');
        }
    });
}

function mec_hourly_schedule_listeners()
{
    jQuery('.mec-add-hourly-schedule-button').off('click').on('click', function()
    {
        var prefix = jQuery(this).data('prefix');
        var day = jQuery(this).data('day');
        var $key = jQuery('#'+prefix+'mec_new_hourly_schedule_key'+day);

        var key = $key.val();
        var html = jQuery('#'+prefix+'mec_new_hourly_schedule_raw'+day).html().replace(/:i:/g, key).replace(/:d:/g, day).replace();
        var g_field_id = prefix+"-hourly_schedules-"+day+"-schedules-:i:-description";
        var field_id = prefix+"-hourly_schedules-"+day+"-schedules-"+key+"-description";
        html = html.replace(g_field_id,field_id);
        html = html.replace(':k:',key);
        jQuery('#'+prefix+'mec_hourly_schedules'+day).append(html);
        $key.val(parseInt(key)+1);

        wp.editor.initialize("mec"+field_id,{
            tinymce: {
                wpautop: true,
                plugins : 'charmap colorpicker compat3x directionality fullscreen hr image lists media paste tabfocus textcolor wordpress wpautoresize wpdialogs wpeditimage wpemoji wpgallery wplink wptextpattern wpview',
                toolbar1: 'bold italic underline strikethrough | bullist numlist | blockquote hr wp_more | alignleft aligncenter alignright | link unlink | fullscreen | wp_adv',
                toolbar2: 'formatselect alignjustify forecolor | pastetext removeformat charmap | outdent indent | undo redo | wp_help'
            },
            quicktags: true,
            mediaButtons: false,
        });
    });

    jQuery(".mec-hourly-schedule-schedules").sortable({
        handle: '.mec_field_sort'
    });
}

function mec_hourly_schedule_remove(day, i, prefix)
{
    jQuery("#"+prefix+"mec_hourly_schedule_row"+day+'_'+i).remove();
}

function mec_hourly_schedule_day_remove(day, prefix)
{
    jQuery("#"+prefix+"mec_meta_box_hourly_schedule_day_"+day).remove();
}

function mec_ticket_remove(i)
{
    jQuery("#mec_ticket_row"+i).remove();
}

function mec_set_event_color(color)
{
    try
    {
        jQuery("#mec_event_color").wpColorPicker('color', '#'+color);
    }
    catch(e)
    {
        jQuery("#mec_event_color").val(color);
    }
}

function mec_remove_fee(key)
{
    jQuery("#mec_fee_row"+key).remove();
}

function mec_remove_ticket_variation(key, id_prefix)
{
    jQuery("#mec_"+id_prefix+"_row"+key).remove();
}

function add_variation_per_ticket(ticket_id)
{
    var $input = jQuery('#mec_new_variation_per_ticket_key');

    var key = $input.val();
    var html = jQuery('#mec_new_variation_per_ticket_raw'+ticket_id).html().replace(/:v:/g, key);

    jQuery('#mec_ticket_variations_list'+ticket_id).append(html);
    $input.val(parseInt(key)+1);

    mec_init_sortable_sections();
}

function mec_init_sortable_instance(selector, options)
{
    jQuery(selector).each(function()
    {
        var $element = jQuery(this);

        if(!$element.length) return;

        if(typeof $element.data('ui-sortable') === 'undefined')
        {
            $element.sortable(options);
        }
        else
        {
            $element.sortable('refresh');
        }
    });
}

function mec_init_sortable_sections()
{
    if(typeof jQuery.fn.sortable === 'undefined') return;

    mec_init_sortable_instance('#mec_tickets', { handle: '.mec_field_sort', items: '> .mec_ticket_row' });
    mec_init_sortable_instance('#mec_fees_list', { handle: '.mec_field_sort', items: '> .mec-box' });
    mec_init_sortable_instance('[id^="mec_ticket_variations_list"]', { handle: '.mec_field_sort', items: '> .mec_ticket_variation_row' });
    mec_init_sortable_instance('#mec_faq_list', { handle: '.mec_field_sort', items: '> .mec_faq_row' });
}

function mec_get_booking_condition_form_fields(prefix)
{
    return jQuery('#mec_' + prefix + '_form_fields > li');
}

function mec_get_booking_condition_field_id($field, prefix)
{
    return String(($field.attr('id') || '').replace('mec_' + prefix + '_fields_', ''));
}

function mec_get_booking_condition_field_type($field, prefix)
{
    const fieldId = mec_get_booking_condition_field_id($field, prefix);
    return String($field.find('input[name="mec[' + prefix + '_fields][' + fieldId + '][type]"]').val() || '');
}

function mec_get_booking_condition_field_label($field, prefix)
{
    const fieldId = mec_get_booking_condition_field_id($field, prefix);
    const $labelInput = $field.find('input[name="mec[' + prefix + '_fields][' + fieldId + '][label]"]').first();
    const fallback = jQuery.trim($field.find('.mec_' + prefix + '_field_type').first().text() || '');

    return jQuery.trim(($labelInput.val() || fallback || ''));
}

function mec_get_booking_condition_field_option_list($field, prefix)
{
    const fieldId = mec_get_booking_condition_field_id($field, prefix);
    const options = [];

    $field.find('input[name^="mec[' + prefix + '_fields][' + fieldId + '][options]"][name$="[label]"]').each(function()
    {
        const name = jQuery(this).attr('name') || '';
        const match = name.match(/\[options\]\[(.*?)\]\[label\]$/);
        if(!match) return;

        options.push({
            key: String(match[1]),
            label: jQuery.trim(jQuery(this).val() || '')
        });
    });

    return options;
}

function mec_booking_condition_field_has_duplicate_options($field, prefix)
{
    const seen = {};
    const options = mec_get_booking_condition_field_option_list($field, prefix);

    for(let i = 0; i < options.length; i++)
    {
        const label = options[i].label;
        if(!label) continue;
        if(seen[label]) return true;

        seen[label] = true;
    }

    return false;
}

function mec_refresh_booking_condition_editor($conditionBox)
{
    if(!$conditionBox || !$conditionBox.length) return;

    const prefix = String($conditionBox.data('conditionPrefix') || '');
    const currentFieldId = String($conditionBox.data('conditionFieldId') || '');
    const $enabled = $conditionBox.find('.mec-booking-condition-enabled');
    const $controls = $conditionBox.find('.mec-booking-condition-controls');
    const $source = $conditionBox.find('.mec-booking-condition-source');
    const $option = $conditionBox.find('.mec-booking-condition-option');
    const $matchType = $conditionBox.find('.mec-booking-condition-match-type');
    const $message = $conditionBox.find('.mec-booking-condition-message');
    const enabled = $enabled.is(':checked');
    const strings = (typeof mec_admin_localize !== 'undefined' && mec_admin_localize.booking_conditions) ? mec_admin_localize.booking_conditions : {};

    $controls.toggleClass('mec-util-hidden', !enabled);
    if(!enabled)
    {
        $message.text('');
        return;
    }

    const currentSourceId = String($source.val() || $conditionBox.data('currentSourceFieldId') || '');
    const currentOptionKey = String($option.val() || $conditionBox.data('currentOptionKey') || '');
    const sourceFields = [];
    const sourceMap = {};

    mec_get_booking_condition_form_fields(prefix).each(function()
    {
        const $field = jQuery(this);
        const fieldId = mec_get_booking_condition_field_id($field, prefix);
        const fieldType = mec_get_booking_condition_field_type($field, prefix);

        if(!fieldId) return;
        if(fieldId === currentFieldId) return false;
        if(jQuery.inArray(fieldType, ['checkbox', 'radio', 'select', 'agreement']) === -1) return;
        if(jQuery.inArray(fieldType, ['checkbox', 'radio', 'select']) !== -1 && mec_booking_condition_field_has_duplicate_options($field, prefix)) return;

        const sourceField = {
            id: fieldId,
            type: fieldType,
            label: mec_get_booking_condition_field_label($field, prefix)
        };

        sourceFields.push(sourceField);
        sourceMap[fieldId] = sourceField;
    });

    $source.empty().append('<option value="">' + (strings.select_field || 'Select a field') + '</option>');
    jQuery.each(sourceFields, function(index, sourceField)
    {
        $source.append('<option value="' + sourceField.id + '">' + sourceField.label + '</option>');
    });

    if(currentSourceId && sourceMap[currentSourceId]) $source.val(currentSourceId);

    const selectedSourceId = String($source.val() || '');
    const selectedSource = sourceMap[selectedSourceId] || null;

    $option.empty();

    if(!selectedSource)
    {
        $matchType.val('');
        $option.prop('disabled', true).append('<option value="">' + (strings.select_option || 'Select an option') + '</option>');
        $message.text(sourceFields.length ? (strings.select_controller || 'Choose a controller field.') : (strings.no_sources || 'No eligible controller fields are available.'));
        $conditionBox.data('currentSourceFieldId', '');
        $conditionBox.data('currentOptionKey', '');
        return;
    }

    if(selectedSource.type === 'agreement')
    {
        $matchType.val('checked');
        $option.prop('disabled', true).append('<option value="">' + (strings.checked_state || 'Checked') + '</option>');
        $message.text((strings.checked_message || 'This field will show when "%s" is checked.').replace('%s', selectedSource.label));
        $conditionBox.data('currentSourceFieldId', selectedSourceId);
        $conditionBox.data('currentOptionKey', '');
        return;
    }

    const $sourceField = jQuery('#mec_' + prefix + '_fields_' + selectedSource.id);
    const options = mec_get_booking_condition_field_option_list($sourceField, prefix);
    const matchType = selectedSource.type === 'checkbox' ? 'contains_option' : 'equals_option';

    $matchType.val(matchType);
    $option.prop('disabled', false).append('<option value="">' + (strings.select_option || 'Select an option') + '</option>');

    jQuery.each(options, function(index, option)
    {
        $option.append('<option value="' + option.key + '">' + option.label + '</option>');
    });

    if(currentOptionKey) $option.val(currentOptionKey);

    if(!$option.val())
    {
        $message.text((strings.select_value || 'Choose the value that should reveal this field.').replace('%s', selectedSource.label));
    }
    else
    {
        $message.text('');
    }

    $conditionBox.data('currentSourceFieldId', selectedSourceId);
    $conditionBox.data('currentOptionKey', String($option.val() || ''));
}

function mec_refresh_booking_condition_editors(prefix)
{
    jQuery('.mec-booking-condition-box[data-condition-prefix="' + prefix + '"]').each(function()
    {
        mec_refresh_booking_condition_editor(jQuery(this));
    });
}

function mec_reg_fields_option_listeners()
{
    jQuery('button.mec-reg-field-add-option').off('click').on('click', function()
    {
        var field_id = jQuery(this).data('field-id');
        var key = jQuery('#mec_new_reg_field_option_key_'+field_id).val();
        var html = jQuery('#mec_reg_field_option').html().replace(/:i:/g, key).replace(/:fi:/g, field_id);

        jQuery('#mec_reg_fields_'+field_id+'_options_container').append(html);
        jQuery('#mec_new_reg_field_option_key_'+field_id).val(parseInt(key)+1);
        mec_refresh_booking_condition_editors('reg');
    });

    if(typeof jQuery.fn.sortable !== 'undefined')
    {
        jQuery("#mec_reg_form_fields").sortable(
        {
            handle: '.mec_reg_field_sort',
            stop: function()
            {
                mec_refresh_booking_condition_editors('reg');
            }
        });

        jQuery(".mec_reg_fields_options_container").sortable(
        {
            handle: '.mec_reg_field_option_sort'
        });

        jQuery(".mec-hourly-schedule-days").sortable(
        {
            handle: 'h4'
        });

        jQuery(".mec-hourly-schedule-schedules").sortable({
            handle: '.mec_field_sort'
        });
    }
}

function mec_reg_fields_option_remove(field_key, key)
{
    jQuery("#mec_reg_fields_option_"+field_key+"_"+key).remove();
    mec_refresh_booking_condition_editors('reg');
}

function mec_reg_fields_remove(key)
{
    jQuery("#mec_reg_fields_"+key).remove();
    mec_refresh_booking_condition_editors('reg');
}

function mec_handle_add_price_date_button(e)
{
    var key = jQuery(e).data('key');
    var p = jQuery('#mec_new_ticket_price_key_'+key).val();
    var html = jQuery('#mec_new_ticket_price_raw_'+key).html().replace(/:i:/g, key).replace(/:j:/g, p);

    jQuery('#mec-ticket-price-dates-'+key).append(html);
    jQuery('#mec_new_ticket_price_key_'+key).val(parseInt(p)+1);
    jQuery('#mec-ticket-price-dates-'+key+' .new_added').datepicker(
    {
        changeYear: true,
        changeMonth: true,
        dateFormat: datepicker_format,
        gotoCurrent: true,
        yearRange: 'c-3:c+5',
    });
}

function mec_ticket_price_remove(ticket_key, price_key)
{
    jQuery("#mec_ticket_price_raw_"+ticket_key+"_"+price_key).remove();
}

function mec_event_fields_option_listeners()
{
    jQuery('button.mec-event-field-add-option').off('click').on('click', function()
    {
        var field_id = jQuery(this).data('field-id');
        var key = jQuery('#mec_new_event_field_option_key_'+field_id).val();
        var html = jQuery('#mec_event_field_option').html().replace(/:i:/g, key).replace(/:fi:/g, field_id);

        jQuery('#mec_event_fields_'+field_id+'_options_container').append(html);
        jQuery('#mec_new_event_field_option_key_'+field_id).val(parseInt(key)+1);
    });

    if(typeof jQuery.fn.sortable !== 'undefined')
    {
        jQuery("#mec_event_form_fields").sortable(
        {
            handle: '.mec_event_field_sort'
        });

        jQuery(".mec_event_fields_options_container").sortable(
        {
            handle: '.mec_event_field_option_sort'
        });
    }
}

function mec_event_fields_option_remove(field_key, key)
{
    jQuery("#mec_event_fields_option_"+field_key+"_"+key).remove();
}

function mec_event_fields_remove(key)
{
    jQuery("#mec_event_fields_"+key).remove();
}

function mec_bfixed_fields_option_listeners()
{
    jQuery('button.mec-bfixed-field-add-option').off('click').on('click', function()
    {
        var field_id = jQuery(this).data('field-id');
        var key = jQuery('#mec_new_bfixed_field_option_key_'+field_id).val();
        var html = jQuery('#mec_bfixed_field_option').html().replace(/:i:/g, key).replace(/:fi:/g, field_id);

        jQuery('#mec_bfixed_fields_'+field_id+'_options_container').append(html);
        jQuery('#mec_new_bfixed_field_option_key_'+field_id).val(parseInt(key)+1);
        mec_refresh_booking_condition_editors('bfixed');
    });

    if(typeof jQuery.fn.sortable !== 'undefined')
    {
        jQuery("#mec_bfixed_form_fields").sortable(
        {
            handle: '.mec_bfixed_field_sort',
            stop: function()
            {
                mec_refresh_booking_condition_editors('bfixed');
            }
        });

        jQuery(".mec_bfixed_fields_options_container").sortable(
        {
            handle: '.mec_bfixed_field_option_sort'
        });
    }
}

function mec_bfixed_fields_option_remove(field_key, key)
{
    jQuery("#mec_bfixed_fields_option_"+field_key+"_"+key).remove();
    mec_refresh_booking_condition_editors('bfixed');
}

function mec_bfixed_fields_remove(key)
{
    jQuery("#mec_bfixed_fields_"+key).remove();
    mec_refresh_booking_condition_editors('bfixed');
}

function mec_additional_organizers_listeners()
{
    jQuery('#mec_additional_organizers_add').off('click').on('click', function()
    {
        var value = jQuery('.mec-additional-organizers select').val();
        console.log(value);
        if (!value) return;

        var text = jQuery('.mec-additional-organizers select option:selected').text();

        var sortLabel = jQuery(this).data('sort-label');
        var removeLabel = jQuery(this).data('remove-label');

        jQuery('.mec-additional-organizers-list').append('<li><span class="mec-additional-organizer-sort">'+sortLabel+'</span> <span onclick="mec_additional_organizers_remove(this);" class="mec-additional-organizer-remove">'+removeLabel+'</span><input type="hidden" name="mec[additional_organizer_ids][]" value="'+value+'"><span class="mec_orgz_item_name">'+text+'</span></li>');

        mec_additional_organizers_listeners();
    });

    if(typeof jQuery.fn.sortable !== 'undefined')
    {
        jQuery(".mec-additional-organizers-list").sortable(
        {
            handle: '.mec-additional-organizer-sort'
        });
    }
}

function mec_additional_organizers_remove(element)
{
    jQuery(element).parent().remove();
}

function mec_faq_remove(key)
{
    jQuery("#mec_faq_row"+key).remove();
}

jQuery(document).on('focus', '.mec_date_picker', function ()
{
    if (!jQuery(this).hasClass('hasDatepicker'))
    {
        jQuery(this).datepicker({
            changeYear: true,
            changeMonth: true,
            dateFormat: 'yy-mm-dd',
            gotoCurrent: true,
            yearRange: 'c-3:c+5',
        });
    }
});

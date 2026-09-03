function mec_fes_upload_featured_image() {
    var fd = new FormData();
    fd.append("action", "mec_fes_upload_featured_image");
    fd.append("_wpnonce", mecdata.fes_upload_nonce);
    fd.append("file", jQuery("#mec_featured_image_file").prop("files")[0]);

    jQuery("#mec_fes_thumbnail_error").html("").addClass("mec-util-hidden");

    // Submit Button
    const $button = jQuery('.mec-fes-sub-button');

    // Disable Button
    $button.prop('disabled', true);

    jQuery.ajax({
        url: mecdata.ajax_url,
        type: "POST",
        data: fd,
        dataType: "json",
        processData: false,
        contentType: false
    })
    .done(function (data) {
        // Enable Button
        $button.prop('disabled', false);

        if (data.success) {
            jQuery("#mec_fes_thumbnail").val(data.data.url);
            jQuery("#mec_featured_image_file").val("");
            jQuery("#mec_fes_thumbnail_img").html("<img src=\"" + data.data.url + "\" />");
            jQuery("#mec_fes_remove_image_button").removeClass("mec-util-hidden");
        } else {
            jQuery("#mec_fes_thumbnail_error").html(data.message).removeClass("mec-util-hidden");
        }
    });

    return false;
}

function mec_fes_upload_location_thumbnail() {
    var fd = new FormData();

    fd.append("action", "mec_fes_upload_featured_image");
    fd.append("_wpnonce", mecdata.fes_upload_nonce);
    fd.append("file", jQuery("#mec_fes_location_thumbnail_file").prop("files")[0]);

    // Submit Button
    const $button = jQuery('.mec-fes-sub-button');

    // Disable Button
    $button.prop('disabled', true);

    jQuery.ajax({
        url: mecdata.ajax_url,
        type: "POST",
        data: fd,
        dataType: "json",
        processData: false,
        contentType: false
    })
    .done(function (data) {
        // Enable Button
        $button.prop('disabled', false);

        jQuery("#mec_fes_location_thumbnail").val(data.data.url);
        jQuery("#mec_fes_location_thumbnail_file").val("");
        jQuery("#mec_fes_location_thumbnail_img").html("<img src=\"" + data.data.url + "\" />");
        jQuery("#mec_fes_location_remove_image_button").removeClass("mec-util-hidden");
    });

    return false;
}

function mec_fes_upload_organizer_thumbnail() {
    var fd = new FormData();

    fd.append("action", "mec_fes_upload_featured_image");
    fd.append("_wpnonce", mecdata.fes_upload_nonce);
    fd.append("file", jQuery("#mec_fes_organizer_thumbnail_file").prop("files")[0]);

    // Submit Button
    const $button = jQuery('.mec-fes-sub-button');

    // Disable Button
    $button.prop('disabled', true);

    jQuery.ajax({
        url: mecdata.ajax_url,
        type: "POST",
        data: fd,
        dataType: "json",
        processData: false,
        contentType: false
    })
    .done(function (data) {
        // Enable Button
        $button.prop('disabled', false);

        jQuery("#mec_fes_organizer_thumbnail").val(data.data.url);
        jQuery("#mec_fes_organizer_thumbnail_file").val("");
        jQuery("#mec_fes_organizer_thumbnail_img").html("<img src=\"" + data.data.url + "\" />");
        jQuery("#mec_fes_organizer_remove_image_button").removeClass("mec-util-hidden");
    });

    return false;
}

function mec_fes_upload_sponsor_thumbnail() {
    const fd = new FormData();

    fd.append("action", "mec_fes_upload_featured_image");
    fd.append("_wpnonce", mecdata.fes_upload_nonce);
    fd.append("file", jQuery("#mec_fes_sponsor_thumbnail_file").prop("files")[0]);

    // Submit Button
    const $button = jQuery('.mec-fes-sub-button');

    // Disable Button
    $button.prop('disabled', true);

    jQuery.ajax({
        url: mecdata.ajax_url,
        type: "POST",
        data: fd,
        dataType: "json",
        processData: false,
        contentType: false
    })
    .done(function (data) {
        // Enable Button
        $button.prop('disabled', false);

        jQuery("#mec_fes_sponsor_thumbnail").val(data.data.url);
        jQuery("#mec_fes_sponsor_thumbnail_file").val("");
        jQuery("#mec_fes_sponsor_thumbnail_img").html("<img src=\"" + data.data.url + "\" />");
        jQuery("#mec_fes_sponsor_remove_image_button").removeClass("mec-util-hidden");
    });

    return false;
}

function mec_fes_upload_speaker_thumbnail() {
    const fd = new FormData();

    fd.append("action", "mec_fes_upload_featured_image");
    fd.append("_wpnonce", mecdata.fes_upload_nonce);
    fd.append("file", jQuery("#mec_fes_speaker_thumbnail_file").prop("files")[0]);

    // Submit Button
    const $button = jQuery('.mec-fes-sub-button');

    // Disable Button
    $button.prop('disabled', true);

    jQuery.ajax({
        url: mecdata.ajax_url,
        type: "POST",
        data: fd,
        dataType: "json",
        processData: false,
        contentType: false
    })
    .done(function (data) {
        // Enable Button
        $button.prop('disabled', false);

        jQuery("#mec_fes_speaker_thumbnail").val(data.data.url);
        jQuery("#mec_fes_speaker_thumbnail_file").val("");
        jQuery("#mec_fes_speaker_thumbnail_img").html("<img src=\"" + data.data.url + "\" />");
        jQuery("#mec_fes_speaker_remove_image_button").removeClass("mec-util-hidden");
    });

    return false;
}

jQuery(document).ready(function ($) {
    $("#mec_fes_form").on("submit", function (event) {
        event.preventDefault();

        mec_fes_form_submit();
    });

    // Location select2
    jQuery(".mec-additional-locations select").select2();
    jQuery("#mec_location_id").select2();

    // Organizer Select2
    jQuery(".mec-additional-organizers select").select2();
    jQuery("#mec_organizer_id").select2();
});

function mec_recaptcha_v3_submit()
{
    mec_fes_form_submit();
}

var mec_fes_form_ajax = false;
function mec_fes_form_submit()
{
    const $ = jQuery;

    var $form = $("#mec_fes_form");

    // Hide the message
    $("#mec_fes_form_message").removeClass("mec-error").removeClass("mec-success").html("").hide();

    // Add loading Class to the form
    $form.addClass("mec-fes-loading");
    $(".mec-fes-form-cntt").hide();
    $(".mec-fes-form-sdbr").hide();
    $(".mec-fes-submit-wide").hide();

    // Fix WordPress editor issue
    $("#mec_fes_content-html").click();
    $("#mec_fes_content-tmce").click();

    // Abort previous request
    if (mec_fes_form_ajax) mec_fes_form_ajax.abort();

    var data = $form.serialize();
    mec_fes_form_ajax = $.ajax({
        type: "POST",
        url: mecdata.ajax_url,
        data: data,
        dataType: "JSON",
        success: function (response) {
            // Remove the loading Class from the form
            $("#mec_fes_form").removeClass("mec-fes-loading");
            $(".mec-fes-form-cntt").show();
            $(".mec-fes-form-sdbr").show();
            $(".mec-fes-submit-wide").show();

            if (response.success == "1") {
                // Show the message
                $("#mec_fes_form_message").removeClass("mec-error").removeClass("mec-success").addClass("mec-success").html(response.message).css("display", "inline-block");

                // Set the event id
                $(".mec-fes-post-id").val(response.data.post_id);

                // Redirect Currnet Page
                if (response.data.redirect_to !== "") {
                    // Seat layout booking uses the same FES submit flow, but we don't want to redirect
                    // to transaction-pay endpoints after selecting seats.
                    const redirectTo = response.data.redirect_to;
                    const hasSeatLayout =
                        document.querySelector('#mec-seat-layouts-wrap') ||
                        document.querySelector('#mec-seat-open-seats') ||
                        document.querySelector('#mec-seat-save-seats');

                    const isSeatTransactionPayRedirect =
                        typeof redirectTo === 'string' && redirectTo.indexOf('mec_do_transaction_pay_locally') !== -1;

                    if (!hasSeatLayout && !isSeatTransactionPayRedirect) {
                        setTimeout(function () {
                            window.location.href = redirectTo;
                        }, mecdata.fes_thankyou_page_time );
                    }
                }
            } else {
                // Refresh reCaptcha
                if (response.code === "CAPTCHA_IS_INVALID" && typeof grecaptcha !== "undefined") {
                    grecaptcha.reset();
                }

                // Show the message
                $("#mec_fes_form_message").removeClass("mec-error").addClass("mec-error").html(response.message).css("display", "inline-block");
            }
            $("#mec_fes_form_message").get(0).scrollIntoView({behavior: 'smooth', block: 'end'});
        },
        error: function (jqXHR, textStatus, errorThrown) {
            // Remove the loading Class from the form
            $("#mec_fes_form").removeClass("loading");
        }
    });
}

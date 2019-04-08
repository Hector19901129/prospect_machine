/**
 *
 *
 */
jQuery(".on-off-img").click(function () {
    var cur_id = jQuery(this).attr("id");
    var box = jQuery(this).closest("box");
    //Get the id of this clicked item

    jQuery.ajax({
        url: my_ajax_object.ajaxurl,
        type: 'post',
        data: {
            action: 'my_ajax_callback_function',
            security: my_ajax_object.nextNonce,
            campaignid: cur_id,
            do_run: 0
        },
        success: function (response) {
            jQuery('div.textwidget.custom-html-widget').html(response);
            jQuery(box).find("ribbon").toggle("hide");
        }
    });
});

/**
 *
 *
 */
function do_run_turn_off_campaign(_cid) {
    jQuery.ajax({
        url: my_ajax_object.ajaxurl,
        type: 'post',
        data: {
            action: 'my_ajax_callback_function',
            security: my_ajax_object.nextNonce,
            campaignid: _cid,
            do_run: 1
        },
        success: function (response) {
            jQuery('div.textwidget.custom-html-widget').html(response);
        }
    });

}

/**
 *
 *
 */
function do_run_turn_on_campaign(_cid) {
    jQuery.ajax({
        url: my_ajax_object.ajaxurl,
        type: 'post',
        data: {
            action: 'my_ajax_callback_function',
            security: my_ajax_object.nextNonce,
            campaignid: _cid,
            do_run: 1
        },
        success: function (response) {
            jQuery('div.textwidget.custom-html-widget').html(response);
        }
    });

}

// ----------------------------------------------------------------------
// CREATE NEW CAMPAIGN

/**
 *
 *
 */
jQuery("#creat_new_campaign_trigger").click(function () {
    jQuery.ajax({
        url: my_ajax_object.ajaxurl,
        type: 'post',
        data: {
            action: 'create_new_campaign_function',
            security: my_ajax_object.nextNonce,
            to_execute: 'show_form'
        },
        success: function (response) {
            jQuery('div.textwidget.custom-html-widget').html(response);
        }
    });

});

/**
 *
 *
 */
function create_new_campaign(form_id) {
    let form_data = {};
    let flag = false;
    jQuery(form_id).find('[name]').each(function (i, el) {
        if (el.value === ''
            || (jQuery(el).attr('validate')
                && !window[my_ajax_object.validation_function[jQuery(el).attr('validate')]](el.value))) {
            jQuery(el).addClass('has-error');
            flag = true;
        } else {
            jQuery(el).removeClass('has-error');
        }
        form_data[el.name] = el.value;
    });
    if (flag) {
        return false;
    }
    let data = JSON.stringify(form_data);
    jQuery.ajax({
        url: my_ajax_object.ajaxurl,
        type: 'post',
        data: {
            action: 'create_new_campaign_function',
            security: my_ajax_object.nextNonce,
            to_execute: 'create_now',
            form_data: data
        },
        success: function (response) {
            jQuery('div.textwidget.custom-html-widget').html(response);
            setTimeout(function () {
                location.reload();
            }, 2000);
        }
    });

}

/**
 * Helping functions
 *
 */

function isInteger(value) {
    return parseInt(value) == value;
}

function isNumber(value) {
    return !isNaN(value);
}

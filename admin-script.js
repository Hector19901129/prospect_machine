/**
 *  Admin script
 *
 */
function save_rec_ajax(_id, _field) {

	if (!_id) {
		_id = 0;
		if (!jQuery('#prospmac_weekly_budget_0').val()) {
			console.log('Empty data');
			return;
		}

		var data = {
			'action': 'save_rec_ajax',
			'platform': jQuery('#prospmac_platform_' + _id).val(),
			'id': _id,
			'status': jQuery('#prospmac_status_' + _id).val(),
			'weekly_budget': jQuery('#prospmac_weekly_budget_' + _id).val(),
			'no_of_weeks': jQuery('#prospmac_no_of_weeks_' + _id).val(),
			'budget_used': jQuery('#prospmac_budget_used_' + _id).val(),
			'no_of_views': jQuery('#prospmac_no_of_views_' + _id).val(),
			'no_of_clicks': jQuery('#prospmac_no_of_clicks_' + _id).val(),
			'no_of_leads': jQuery('#prospmac_no_of_leads_' + _id).val(),
			'no_of_sales': jQuery('#prospmac_no_of_sales_' + _id).val(),
			'user_id': jQuery('select#prospmac_user_id_' + _id + ' option').filter(":selected").val(),
			'hide': jQuery('#prospmac_hide_' + _id).val(),
			'req_turn_on': jQuery('#prospmac_req_turn_on_' + _id).val(),
			'req_turn_off': jQuery('#prospmac_req_turn_off_' + _id).val(),
			'req_change': jQuery('#prospmac_req_change_' + _id).val()
		};
	} else {
		if (_field) {
			switch (_field) {
				case 'change_desc':
					var data = {
						'action': 'save_rec_ajax',
						'change_desc': (jQuery('#prospmac_' + _field + '_' + _id).val()),
						'id': _id
					};
					break;
				case 'no_of_sales':
					var data = {
						'action': 'save_rec_ajax',
						'no_of_sales': (jQuery('#prospmac_' + _field + '_' + _id).val()),
						'id': _id
					};
					break;
				case 'no_of_views':
					var data = {
						'action': 'save_rec_ajax',
						'no_of_views': (jQuery('#prospmac_' + _field + '_' + _id).val()),
						'id': _id
					};
					break;
				case 'no_of_clicks':
					var data = {
						'action': 'save_rec_ajax',
						'no_of_clicks': (jQuery('#prospmac_' + _field + '_' + _id).val()),
						'id': _id
					};
					break;
				case 'no_of_leads':
					var data = {
						'action': 'save_rec_ajax',
						'no_of_leads': (jQuery('#prospmac_' + _field + '_' + _id).val()),
						'id': _id
					};
					break;
				case 'budget_used':
					var data = {
						'action': 'save_rec_ajax',
						'budget_used': (jQuery('#prospmac_' + _field + '_' + _id).val()),
						'id': _id
					};
					break;
				case 'no_of_weeks':
					var data = {
						'action': 'save_rec_ajax',
						'no_of_weeks': (jQuery('#prospmac_' + _field + '_' + _id).val()),
						'id': _id
					};
					break;
				case 'weekly_budget':
					var data = {
						'action': 'save_rec_ajax',
						'weekly_budget': (jQuery('#prospmac_' + _field + '_' + _id).val()),
						'id': _id
					};
					break;
				case 'platform':
					var data = {
						'action': 'save_rec_ajax',
						'platform': (jQuery('#prospmac_' + _field + '_' + _id + ' :selected').val()),
						'id': _id
					};
					break;
				case 'user_id':
					var data = {
						'action': 'save_rec_ajax',
						'user_id': (jQuery('#prospmac_' + _field + '_' + _id + ' :selected').val()),
						'id': _id
					};
					break;
				case 'req_turn_on':
					var data = {
						'action': 'save_rec_ajax',
						'req_turn_on': (jQuery('#prospmac_' + _field + '_' + _id).val() == 1 ? 0 : 1),
						'id': _id
					};
					break;
				case 'req_turn_off':
					var data = {
						'action': 'save_rec_ajax',
						'req_turn_off': (jQuery('#prospmac_' + _field + '_' + _id).val() == 1 ? 0 : 1),
						'id': _id
					};
					break;
				case 'status':
					var data = {
						'action': 'save_rec_ajax',
						'status': (jQuery('#prospmac_' + _field + '_' + _id).val() == 1 ? 0 : 1),
						'id': _id
					};
					break;
				case 'hide':
					var data = {
						'action': 'save_rec_ajax',
						'hide': (jQuery('#prospmac_' + _field + '_' + _id).val() == 1 ? 0 : 1),
						'id': _id
					};
					break;
				case 'req_change':
					var data = {
						'action': 'save_rec_ajax',
						'req_change': (jQuery('#prospmac_' + _field + '_' + _id).val() == 1 ? 0 : 1),
						'id': _id
					};
					break;
				/*
				case '':
					break;
				*/
			}
		} else {
			console.log('Field name was empty');
			return;
		}
	}

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery.post(my_ajax_object.ajaxurl, data, function (response) {
		console.log(response);

		if (response) window.location.reload();
		else alert('No response from server. Check with developers.');
	});
}
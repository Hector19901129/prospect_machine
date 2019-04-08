<?php
/*
Plugin Name: Prospect Machine
Plugin URI: https://www.prospect-machine.co.uk
Description: a plugin to create awesomeness and spread joy
Version: 1.0
Author: Mr. T Jacobi
Author URI: http://mrtotallyawesome.com
License: GPL2
*/
# Important elements to remember
defined('ABSPATH') or die('No script kiddies please!');
if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * plugin_dir_path()
 * plugins_url()
 */

# Default Constants
global $myvar;
define('PM_SHORTNAME', 'prospmac'); // Used to reference namespace functions.
define('PM_SLUG', 'prospect-machine/prospect-machine.php'); // Used for settings link.
define('PM_TEXTDOMAIN', ''); // Your textdomain
define('PM_PLUGIN_NAME', 'Prospect Machine'); // Plugin Name shows up on the admin settings screen.
define('PM_VERSION', '1.0'); // Plugin Version Number. Recommend you use Semantic Versioning http://semver.org/
define('PM_PLUGIN_PATH', plugin_dir_path(__FILE__)); // Example output: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/PM/
define('PM_PLUGIN_URL', plugin_dir_url(__FILE__)); // Example output: http://localhost:8888/wordpress/wp-content/plugins/PM/
define('PM_TABLENAME', 'prospmac_subscribers');

# ----------------------------------------------------------------------
# ADMIN
# ----------------------------------------------------------------------

# Upon activation of plugin
function prospmac_activate()
{
	global $myvar, $wpdb;

	# Protection
	if (!current_user_can('activate_plugins')) return;
	$plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
	check_admin_referer("activate-plugin_{$plugin}");
	#
	$table_name = $wpdb->prefix . "prospmac";
	$charset_collate = $wpdb->get_charset_collate();
	# ------------------------------
	# v1 - first installation

	/*
	$sql = "CREATE TABLE $table_name (
	  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	  `platform` VARCHAR(20) NOT NULL,
	  `status` TINYINT(1) DEFAULT 0 NOT NULL,
	  `weekly_budget` DECIMAL(5,2),
	  `no_of_weeks` INT(3) DEFAULT 0 NOT NULL,
	  `budget_used` DECIMAL(5,2),
	  `no_of_leads` INT(5) DEFAULT 0 NOT NULL,
	  `no_of_sales` INT(5) DEFAULT 0 NOT NULL,
	  `user_id` BIGINT(20) DEFAULT 0 NOT NULL,
	  `dt_created` DATETIME NOT NULL DEFAULT NOW(),
	  `dt_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	  `hide` TINYINT(1) DEFAULT 0 NOT NULL,
	  `req_turn_on` TINYINT(1) DEFAULT 0 NOT NULL,
	  `req_turn_off` TINYINT(1) DEFAULT 0 NOT NULL,
	  `req_change` TINYINT(1) DEFAULT 0 NOT NULL,
	  `change_desc` VARCHAR(225) NULL,
	  PRIMARY KEY  (id)
	) $charset_collate;";

	#echo __LINE__.'<br>';

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	#echo $sql.'<br>';


	$wpdb->insert(
		$table_name,
		array(
			'platform' => 'Facebook',
			'status' => 0,
			'weekly_budget' => 100,
			'no_of_weeks' => 3,
			'budget_used' => 43.86,
			'no_of_leads' => 2,
			'user_id' => 12,
			'hide' => 0
			)
		);

	$wpdb->insert(
		$table_name,
		array(
			'platform' => 'LinkedIn',
			'status' => 0,
			'weekly_budget' => 100,
			'no_of_weeks' => 3,
			'budget_used' => 43.86,
			'no_of_leads' => 2,
			'user_id' => 12,
			'hide' => 0
			)
		);
	*/
	# ------------------------------
}

register_activation_hook(__FILE__, 'prospmac_activate');

#

function prospmac_deactivate()
{
	global $wpdb;

	if (!current_user_can('activate_plugins')) return;
	$plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
	check_admin_referer("deactivate-plugin_{$plugin}");
	#exit( var_dump( $_GET ) );
	#$table_name = $wpdb->prefix . "prospmac";
	#$sql = "DROP TABLE IF EXISTS $table_name";
	#$wpdb->query($sql);
	#delete_option("my_plugin_db_version");
}

register_deactivation_hook(__FILE__, 'prospmac_deactivate');

#

function prospmac_uninstall()
{

}

register_uninstall_hook(__FILE__, 'prospmac_uninstall');

# Admin menu

function prospmac_menu_page()
{
	global $wpdb;

	$table_name = $wpdb->prefix . "prospmac";
	$status_select = '<select id="prospmac_status_%ln_id%" onchange="save_rec_ajax(%ln_id%,\'status\');"><option value="1" %select_1%>On</option><option value="0" %select_0%>Off</option></select>';
	$hide_select = '<select id="prospmac_hide_%ln_id%" onchange="save_rec_ajax(%ln_id%,\'hide\');"><option value="1" %select_1%>Hide</option><option value="0" %select_0%>Show</option></select>';
	$turn_on_req_select = '<select id="prospmac_req_turn_on_%ln_id%" onchange="save_rec_ajax(%ln_id%,\'req_turn_on\');"><option value="1" %select_1%>Turn On</option><option value="0" %select_0%>--</option></select>';
	$turn_off_req_select = '<select id="prospmac_req_turn_off_%ln_id%" onchange="save_rec_ajax(%ln_id%,\'req_turn_off\');"><option value="1" %select_1%>Turn Off</option><option value="0" %select_0%>--</option></select>';
	$change_req_select = '<select id="prospmac_req_change_%ln_id%" onchange="save_rec_ajax(%ln_id%,\'req_change\');"><option value="1" %select_1%>Change</option><option value="0" %select_0%>--</option></select>';
	$change_platform_select = '<select id="prospmac_platform_%ln_id%" onchange="save_rec_ajax(%ln_id%,\'platform\');"><option value="Facebook" %select_Facebook%>Facebook</option><option value="LinkedIn" %select_LinkedIn%>LinkedIn</option><option value="Instagram" %select_Instagram%>Instagram</option></select>';
	$platform_select = '<select id="prospmac_platform_%ln_id%"><option value="Facebook" %select_Facebook%>Facebook</option><option value="LinkedIn" %select_LinkedIn%>LinkedIn</option><option value="Instagram" %select_Instagram%>Instagram</option></select>';

	$users_select = '<select id="prospmac_user_id_%ln_id%">%user_options%</select>';
	$change_users_select = '<select id="prospmac_user_id_%ln_id%" onchange="save_rec_ajax(%ln_id%,\'user_id\');">%user_options%</select>';
	$wp_users = get_users(array('fields' => array('ID')));
	$user_options = '';
	foreach ($wp_users as $user_id) {
		$tmp_user = get_user_meta($user_id->ID);
		$user_options .= '<option value="' . $user_id->ID . '" %select_' . $user_id->ID . '%>' . $tmp_user['nickname'][0] . ' (' . $user_id->ID . ')</option>';
		$users_arr[$user_id->ID] = $tmp_user['nickname'][0] . ' (' . $user_id->ID . ')';
	}
	$users_select = str_replace('%user_options%', $user_options, $users_select);
	$change_users_select = str_replace('%user_options%', $user_options, $change_users_select);

	echo '
	<div id="message" class="updated fade"></div>
	<div class="wrap">
		<h2>Prospect Machine</h2>
		<table class="widefat">
		<tr><th>Id</th><th>Platform</th><th>Status</th><th>Weekly Budget</th><th>No of Weeks</th><th>Budget Used</th><th>No of Leads</th><th>No of Sales</th><th>User</th><th>Date Created</th><th>Date Updated</th><th>Hide</th><th>Trun On Req</th><th>Trun Off Req</th><th>Change Req</th><th>Change Desc</th><th>No of Views</th><th>No of Clicks</th></tr>';

	$r = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY `id` DESC", ''));

	foreach ($r as $result) {
		echo '<tr>';
		foreach ($result as $k => $v) {
			if ($k == 'change_desc') {
				echo '<td><input type="text" class="w-75" onblur="save_rec_ajax(' . $result->id . ',\'' . $k . '\');" value="' . $v . '" id="prospmac_' . $k . '_' . $result->id . '"></td>';
			} elseif ($k == 'no_of_sales') {
				echo '<td><input type="text" class="w-75" onblur="save_rec_ajax(' . $result->id . ',\'' . $k . '\');" value="' . $v . '" id="prospmac_' . $k . '_' . $result->id . '"></td>';
			} elseif ($k == 'no_of_leads') {
				echo '<td><input type="text" class="w-75" onblur="save_rec_ajax(' . $result->id . ',\'' . $k . '\');" value="' . $v . '" id="prospmac_' . $k . '_' . $result->id . '"></td>';
			} elseif ($k == 'budget_used') {
				echo '<td><input type="text" class="w-75" onblur="save_rec_ajax(' . $result->id . ',\'' . $k . '\');" value="' . $v . '" id="prospmac_' . $k . '_' . $result->id . '"></td>';
			} elseif ($k == 'no_of_weeks') {
				echo '<td><input type="text" class="w-75" onblur="save_rec_ajax(' . $result->id . ',\'' . $k . '\');" value="' . $v . '" id="prospmac_' . $k . '_' . $result->id . '"></td>';
			} elseif ($k == 'weekly_budget') {
				echo '<td><input type="text" class="w-75" onblur="save_rec_ajax(' . $result->id . ',\'' . $k . '\');" value="' . $v . '" id="prospmac_' . $k . '_' . $result->id . '"></td>';
			} elseif ($k == 'no_of_views') {
				echo '<td><input type="text" class="w-75" onblur="save_rec_ajax(' . $result->id . ',\'' . $k . '\');" value="' . $v . '" id="prospmac_' . $k . '_' . $result->id . '"></td>';
			} elseif ($k == 'no_of_clicks') {
				echo '<td><input type="text" class="w-75" onblur="save_rec_ajax(' . $result->id . ',\'' . $k . '\');" value="' . $v . '" id="prospmac_' . $k . '_' . $result->id . '"></td>';
			} elseif ($k == 'platform') {
				echo '<td>' . cleanup_fields(array("%select_{$v}%"), array('selected="selected"'), $change_platform_select, $result->id, false) . '</td>';
			} elseif ($k == 'status') {
				echo '<td>' . cleanup_fields(array("%select_{$v}%"), array('selected="selected"'), $status_select, $result->id) . '</td>';
			} elseif ($k == 'hide')
				echo '<td>' . cleanup_fields(array("%select_{$v}%"), array('selected="selected"'), $hide_select, $result->id) . '</td>';
			elseif ($k == 'req_turn_on')
				echo '<td>' . cleanup_fields(array("%select_{$v}%"), array('selected="selected"'), $turn_on_req_select, $result->id) . '</td>';
			elseif ($k == 'req_turn_off')
				echo '<td>' . cleanup_fields(array("%select_{$v}%"), array('selected="selected"'), $turn_off_req_select, $result->id) . '</td>';
			elseif ($k == 'req_change')
				echo '<td>' . cleanup_fields(array("%select_{$v}%"), array('selected="selected"'), $change_req_select, $result->id) . '</td>';
			elseif ($k == 'user_id')
				echo '<td>' . cleanup_fields(array("%select_{$v}%"), array('selected="selected"'), $change_users_select, $result->id, false) . '</td>';
			else
				echo "<td>{$v}</td>";

		}
		echo '</tr>';
	}
	echo '<tr><td></td>
			<td>' . cleanup_fields('', '', $platform_select, 0, false) . '</td>
			<td>' . cleanup_fields('', '', $status_select, 0, false) . '</td>
			<td><input type="text" class="w-75" value="" id="prospmac_weekly_budget_0"></td>
			<td><input type="text" class="w-75" value="" id="prospmac_no_of_weeks_0"></td>
			<td><input type="text" class="w-75" value="" id="prospmac_budget_used_0"></td>
			<td><input type="text" class="w-75" value="" id="prospmac_no_of_leads_0"></td>
			<td><input type="text" class="w-75" value="" id="prospmac_no_of_sales_0"></td>
			<td>' . cleanup_fields('', '', $users_select, 0, false) . '</td>
			<td>' . cleanup_fields('%select_0%', 'selected="selected"', $hide_select, 0, false) . '</td>
			<td><input type="button" value="Create" id="prospmac_create_form" onclick="save_rec_ajax();return false;"></td>
			<td><input type="text" class="w-75" value="" id="prospmac_no_of_views_0"></td>
			<td><input type="text" class="w-75" value="" id="prospmac_budget_clicks_0"></td>
			</tr>';
	echo '</table>';
	echo '</div>';
}

function prospmac_menu()
{
	add_menu_page('Prospect Machine', 'Prospect Machine', 'manage_options', 'prospect-machine-admin', 'prospmac_menu_page');
}

add_action('admin_menu', 'prospmac_menu');

# Save record

function prospmac_admin_script()
{
	wp_register_script('prospmac_admin_script', plugins_url('admin-script.js', __FILE__), array('jquery'), '1.0.' . rand(0, 10), true);
	wp_localize_script('prospmac_admin_script', 'my_ajax_object', array(
			'ajaxurl' => admin_url('admin-ajax.php')
		, 'nextNonce' => wp_create_nonce('myajax-next-nonce')
		)
	);
	wp_enqueue_script('prospmac_admin_script');
	wp_enqueue_style('prospmac_admin_style', plugins_url('admin-style.css?v=1.0.' . rand(0, 10), __FILE__));
}

add_action('admin_enqueue_scripts', 'prospmac_admin_script');

function save_rec_ajax()
{
	global $wpdb; // this is how you get access to the database

	if (!$_POST['id']) {
		$r = $wpdb->insert(
			$wpdb->prefix . "prospmac",
			array(
				'platform' => $_POST['platform'],
				'status' => $_POST['status'],
				'weekly_budget' => $_POST['weekly_budget'],
				'no_of_weeks' => $_POST['no_of_weeks'],
				'budget_used' => $_POST['budget_used'],
				'no_of_leads' => $_POST['no_of_leads'],
				'no_of_sales' => $_POST['no_of_sales'],
				'user_id' => $_POST['user_id'],
				'hide' => $_POST['hide']
			)
		);
	} else {
		foreach ($_POST as $k => $V) {
			if ($k != 'id' && $k != 'action') $array_u[$k] = $_POST[$k];
		}
		#echo "<pre>".print_r($array_u,true)."</pre>";#wp_die();
		$r = $wpdb->update(
			$wpdb->prefix . "prospmac",
			$array_u/*
			array(
				'platform' => $_POST['platform'],
				'status' => $_POST['status'],
				'weekly_budget' => $_POST['weekly_budget'],
				'no_of_weeks' => $_POST['no_of_weeks'],
				'budget_used' => $_POST['budget_used'],
				'no_of_leads' => $_POST['no_of_leads'],
				'no_of_sales' => $_POST['no_of_sales'],
				'user_id' => $_POST['user_id'],
				'hide' => $_POST['hide'],
				'req_turn_on' => $_POST['req_turn_on'],
				'req_turn_off' => $_POST['req_turn_off'],
				'req_change' => $_POST['req_change']
				)*/,
			array('id' => $_POST['id'])
		);

	}
	if ($r) echo $r;
	else echo 'Error';

	wp_die(); // this is required to terminate immediately and return a proper response
}

add_action('wp_ajax_save_rec_ajax', 'save_rec_ajax');

# ----------------------------------------------------------------------
# PUBLIC
# ----------------------------------------------------------------------

# Shortcode
/**
* put your comment there...
* 
*/
function prospmac_register_shortcodes()
{
	/**
	* put your comment there...
	* 
	*/
	function prospmac_load_plugin_css()
	{
		$plugin_url = plugin_dir_url(__FILE__);
		wp_enqueue_style('prospmac_shortcode_style', $plugin_url . 'style.css?v=1.0.' . rand(0, 10));
	}

	add_action('wp_enqueue_scripts', 'prospmac_load_plugin_css');

	/**
	* put your comment there...
	* 
	*/
	function prospmac_shortcode_callback()
	{
		global $wpdb;

		$table_name = '`custom-uc67`.'.$wpdb->prefix . "prospmac";
		#echo "SELECT * FROM $table_name WHERE `user_id`=" . get_current_user_id() . " ORDER BY `id` DESC<br>";
		#echo "<pre>".print_r($replace,true)."</pre>";

		$user = wp_get_current_user();
		$user_id = $_GET['user_id'] ? $_GET['user_id'] : get_current_user_id();

		if (in_array("administrator", $user->roles) && !$_GET['user_id']) {
			# FOR ADMINISTRATORS ONLY
			$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$wp_users = get_users(array('fields' => array('ID', 'display_name')));

			echo '<div id="wp-custom-prospmac-users" marginwidth="0" marginheight="0">
					<h2>User\'s pages</h2>
					<ul>';
			foreach ($wp_users as $wp_user) {
				echo '<li>
						<a href="' . $actual_link . '?user_id=' . $wp_user->ID . '" target="_blank">' . $wp_user->display_name . '</a>
					  </li>';
			}
			echo '
					</ul>
				</div>
				<br>';
		}

		if ($_GET['user_id']) {
			$user_data = get_userdata($user_id);
			echo '<div id="wp-custom-prospmac-users" marginwidth="0" marginheight="0">
					<h2>User\'s page for ' . $user_data->display_name . '</h2>
				</div>
				<br>';
		}

		#echo "SELECT * FROM $table_name WHERE `user_id`=" . $user_id . " ORDER BY `id` DESC";
		$r = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE `user_id`=" . $user_id . " ORDER BY `id` DESC", ''));
		if (!$r) $r = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE `user_id`=12 ORDER BY `id` DESC", ''));

		foreach ($r as $result) {
			echo '<div id="wp-custom-prospmac" marginwidth="0" marginheight="0">
					<div class="box">
						<div class="wp-embed">
							' . /* DEMO CAMPAIGNS */ ($result->user_id == 12 ? '<p>Demo campaign</p>' : '') . '
							' . /* RIBBON FOR CHANGE CAMPAIGNS */ ($result->req_turn_on || $result->req_turn_off || $result->req_change ?
								'<p class="wp-embed-heading">
									'.$result->platform.' <a href="#" class="mojosp-toggle-right"><img id="campaignid_'.$result->id.'" src="/wp-content/uploads/'.($result->status?'status-on.jpg':'status-off.jpg').'" class="on-off-img"></a>
								</p>
								<div class="ribbon ribbon-top-right">
									<span>change</span>
								</div>' :
								/* NO CHANGE REQUESTED */
								'<p class="wp-embed-heading">
									'.$result->platform.' <a href="#" class="mojosp-toggle-right"><img id="campaignid_'.$result->id.'" src="/wp-content/uploads/'.($result->status?'status-on.jpg':'status-off.jpg').'" class="on-off-img"></a>
								</p>
								<div class="ribbon ribbon-top-right hide" id="change_ribbon_' . $result->user_id . '">
									<span>change</span>
								</div>') .
								/* BODY OF THE CAMPAIGN */
							'<div class="wp-embed-excerpt">
								<p>
									Budget: &pound;' . ($result->weekly_budget * $result->no_of_weeks) . '<br>
									Consumed: &pound;' . $result->budget_used . '<br>
									Number of Views: ' . $result->no_of_views. '<br>
									Number of Clicks: ' . $result->no_of_clicks. '<br>
									Number of Prospects: ' . $result->no_of_leads . '<br>
									Number of Sales Converted: ' . $result->no_of_sales . '
								</p>
							</div>
						</div>
					</div>
				</div><br>';
		}

		# CREATE A NEW CAMPAIGN
		echo '<div id="wp-custom-prospmac" marginwidth="0" marginheight="0">
				<div class="box mojosp-toggle-right" style="cursor:pointer;" id="creat_new_campaign_trigger">
					<div class="wp-embed">
						<p class="wp-embed-heading">
							Create a new campaign
						</p>
					</div>
				</div>
			</div><br>';
	}

	add_shortcode('the-prospect-machine', 'prospmac_shortcode_callback');
}

add_action('init', 'prospmac_register_shortcodes');

#

function sd_add_scripts()
{
	wp_register_script('sd_my_cool_script', plugins_url('script.js', __FILE__), array('jquery'), '1.0.' . rand(0, 10), true);
	wp_localize_script('sd_my_cool_script', 'my_ajax_object', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'validation_function' => [
				'number' => 'isNumber',
				'integer' => 'isInteger'
			],
			'nextNonce' => wp_create_nonce('myajax-next-nonce')
		)
	);
	wp_enqueue_script('sd_my_cool_script');
}

add_action('wp_enqueue_scripts', 'sd_add_scripts');

#
/**
 * Change to campaigns
 *
 */
function my_ajax_callback_function()
{
	global $wpdb;

	list($literal, $cid) = explode('_', $_POST['campaignid']);

	$table_name = $wpdb->prefix . "prospmac";
	$r = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE `id`=$cid", ''));
	foreach ($r as $result) {
		#echo "<pre>".print_r($result,true)."</pre>";
		if ($result->req_turn_on) {
			echo "This campaign is requested to turn on. Please wait until this request is processed before changing anything else.";
			exit;
		}

		if ($result->req_turn_off) {
			echo "This campaign is requested to turn off. Please wait until this request is processed before changing anything else.";
			exit;
		}

		# Turn on
		if (!$result->status) {
			if ($_POST['do_run']) {
				$wpdb->update($table_name, array('req_turn_on' => 1), array('id' => $cid));
				echo "Request to turn on the campaign was made successfuly. Please wait for our internal review before the ads are made live again. <button id='' value='' onclick='window.location.reload();'>Reload this page now</button>";
			} else {
				echo "Please confirm that you would like to turn this campaign on: <button id='turn-on-confirm' value='1' onclick='do_run_turn_on_campaign(\"{$_POST['campaignid']}\");'>Confirm, Turn it On</button>";
			}
		}

		# Turn off
		if ($result->status) {
			if ($_POST['do_run']) {
				$wpdb->update($table_name, array('req_turn_off' => 1), array('id' => $cid));
				echo "Request to turn off the campaign was made successfuly. Please wait for our internal review before the ads are turned off completely for the selected campaign. <button id='' value='' onclick='window.location.reload();'>Reload this page now</button>";
			} else {
				echo "Please confirm that you would like to turn this campaign off: <button id='turn-off-confirm' value='1' onclick='do_run_turn_off_campaign(\"{$_POST['campaignid']}\");'>Confirm, Turn it Off</button>";
			}
		}
	}
	exit();
}

add_action('wp_ajax_my_ajax_callback_function', 'my_ajax_callback_function');
add_action('wp_ajax_nopriv_my_ajax_callback_function', 'my_ajax_callback_function');

/**
 * Create new to campaign
 *
 */
function my_create_new_campaign_function()
{
	global $wpdb;

	$table_name = $wpdb->prefix . "prospmac";

	$r = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE `user_id`=" . get_current_user_id() . " ORDER BY `id` DESC", ''));
	if (!$r) $new_customer = true;
	else $new_customer = false;

	switch ($_POST['to_execute']) {
		case 'show_form':
			echo '<div id="create_new_campaign_form">
					<p>
					Request here to create a new campaign for you.' . ($new_customer ? '<br>This will be your first campaign. When you create it, you will not see the demo campaigns anymore.' : '') . '
					</p>
					<div class="input-group mb-3">
						<select class="selectpicker form-contro" name="create_new_campaign_platform">
						  <option value="Facebook">Facebook</option>
						  <option value="LinkedIn">LinkedIn</option>
						  <option value="Instagram">Instagram</option>
						</select>
					</div>
					<div class="input-group mb-3">
					  <input type="text" validate="number" class="form-control" placeholder="Weekly budget" aria-label="Weekly budget" name="create_new_campaign_weekly_budget" required>
					</div>
					<div class="input-group mb-3">
					  <input type="text" validate="integer" class="form-control" placeholder="Number of weeks" aria-label="Number of weeks" name="create_new_campaign_no_of_weeks" required>
					</div>
					<div class="input-group mb-3">
					  <button id="" value="" onclick="create_new_campaign(\'#create_new_campaign_form\');">Request now</button>
					</div>
				</div>';
			break;

		case 'create_now':
			$user_id = get_current_user_id();
			$data = json_decode(stripcslashes($_POST['form_data']), true);

			$r = $wpdb->insert(
				$wpdb->prefix . "prospmac",
				array(
					'platform' => $data['create_new_campaign_platform'] ? $data['create_new_campaign_platform'] : '0',
					'req_turn_on' => '1',
					'status' => '0',
					'weekly_budget' => $data['create_new_campaign_weekly_budget'] ? $data['create_new_campaign_weekly_budget'] : '0',
					'no_of_weeks' => $data['create_new_campaign_no_of_weeks'] ? $data['create_new_campaign_no_of_weeks'] : '0',
					'budget_used' => '0',
					'no_of_leads' => '0',
					'no_of_sales' => '0',
					'user_id' => $user_id,
					'hide' => '0'
				)
			);
			if ($r) {
				echo 'New campaign was created!';
			} else {
				echo 'New campaign wasn\'t created!';
			}
			break;
	}
	exit();
}

add_action('wp_ajax_create_new_campaign_function', 'my_create_new_campaign_function');
add_action('wp_ajax_nopriv_create_new_campaign_function', 'my_create_new_campaign_function');

// If called from admin panel

function ajax_login()
{
	//nonce-field is created on page
	check_ajax_referer('myajax-next-nonce', 'security');
	die();
}

add_action('wp_ajax_nopriv_ajaxlogin', 'ajax_login');

# -------------------------------------------------------------------
# Helping functions
# -------------------------------------------------------------------

function cleanup_fields($search, $replace, $subject, $ln_id = 0, $create_duplicate = true)
{
	$subject = str_replace('%ln_id%', $ln_id, $subject);
	#echo "<pre>".print_r($subject,true)."</pre>";
	if ($search) $subject = str_replace($search, $replace, $subject);
	#echo "<pre>".print_r($subject,true)."</pre>";
	#echo "<pre>".print_r($search,true)."</pre>";
	#echo "<pre>".print_r($replace,true)."</pre>";

	$subject = preg_replace('/\%select_[^%]*\%/', '', $subject);

	if ($create_duplicate) {
		echo "<pre style='display:none;'>" . print_r($subject, true) . "</pre>";
	}

	return $subject;

}



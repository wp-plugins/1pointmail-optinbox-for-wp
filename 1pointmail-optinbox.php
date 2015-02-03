<?php
/**
* Plugin Name: 1PointMail Subscription Box for Wordpress
* Plugin URI: http://www.1pointinteractive.com/1point-for-wordpress
* Description: Add a 1PointMail subscription box to your wordpress site.
* Version: 1.2
* Author: 1PointInteractive, LLC.
* Author URI: http://www.1pointinteractive.com
* Tags: email, subscription, form, 1point, mail, interactive, 1pointinteractive, 1pointmail, marketing, newsletter
* Usage: Simply configure your 1PointMail username, password and list ID and you are ready to use the widget!
* Requires at least: 3.0
* Tested up to: 4.0.1
License: GPL v3

1PointMail Subscription Box for Wordpress
Copyright (C) 2014, 1Point Interactive LLC, email_ops@1pointinteractive.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
**/

global $wp_version;
if (!version_compare($wp_version,"3.0",">=")) {
	die("Wordpress 3.0 or higher is required to use this plugin.");
} // EO If

/**
==================================================
========== Activation
**/
function opmwp_activate() {
	global $wpdb;

	$table_name = $wpdb->prefix . "onepointmail";

	// Check to see if the tbl exists, return NULL if there is not a table
	if($wpdb->get_var('SHOW TABLES LIKE "' . $table_name . '"') != $table_name){
		$sql ='CREATE TABLE ' . $table_name . ' (
			id INTEGER(10) UNSIGNED AUTO_INCREMENT,
			username VARCHAR(50), 
			password VARCHAR(100), 
			endpoint VARCHAR(200), 
			listid INT(10), 
			listname VARCHAR(200),
			recip_total INT(10), 
			recip_active INT(10), 
			recip_unsub INT(10), 
			last_update DATETIME,
			PRIMARY KEY (id) )';

		// Create database table
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		// Set option in main WP so we know what version of our table schema
		// We can use this later for upgrades (check this version vs the updated one, etc.)
		add_option('onepointmail_database_version','1.0');

		// Insert the 1st row so we can just do updates
		$wpdb->insert($table_name,array('endpoint'=>''),array('%s'));
	} // EO If
} // EO Function
register_activation_hook(__FILE__,'opmwp_activate');

/**
==================================================
========== Admin Menu
**/

// Admin function
function opmwp_option_page(){

	global $wpdb;
	$table_name = $wpdb->prefix . "onepointmail";

	// Check to see that the nonce has been created and passed etc.
	if(isset($_POST['opmwp_settings_updated']) && wp_verify_nonce($_POST['opmwp_settings_updated'],'opmwp_settings')) {
		// In here we need to update the settings passed by the user

		$endpoint       = strip_tags($_POST['opmwp-endpoint']);
		$username       = strip_tags($_POST['opmwp-username']);
		$password       = strip_tags($_POST['opmwp-password']);
		$listid         = strip_tags($_POST['opmwp-listid']);

		//$sql = $wpdb->prepare('UPDATE ' . $table_name . ' SET endpoint = %1s', $endpoint);
		//$wpdb->query($sql);
		$wpdb->update($table_name,
			array(
				'endpoint' => $endpoint,
				'username' => $username,
				'password' => $password,
				'listid'   => $listid,
			),
			array(
				'ID' => '1'
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		//exit( var_dump( $wpdb->last_query ) );
		//opmwp_runsync();

		// Get List Name
        	if (($endpoint!=null)&&($username!=null)&&($password!=null)&&($listid!=null)) {
                	$url  = $endpoint . "/GetListByListID?UserId=" . $username . "&Password=" . $password . "&ListID=" . $listid;

			$data = @file_get_contents( $url ); // @ will surpress errors

			// Regex to get the message response from the API
			$regex = '%ListName>(.*?)</ListName>%';
			preg_match( $regex, $data, $matches );

			// Set the string value of the message to $ret
			$ret = false;
			if( $matches && $matches[1] ) $ret = $matches[1];

			if ($ret != null) {
				$tDate = date("Y-m-d H:i:s");

				$wpdb->update($table_name,
					array(
						'listname'      => $ret,
						'last_update'   => $tDate,
					),
					array(
						'ID' => '1'
					),
					array(
						'%s',
						'%s',
					)
				);
			} // EO if
		} // EO If


		?>
		<div id="message" class="updated">Settings Updated</div>
		<?php
	} // EO if

	// Get data from the DB if there is any to show the user
	//$endpoint = $wpdb->get_var($wpdb->prepare('SELECT endpoint FROM ' . $table_name . ' WHERE ID = %d'),'1');
	$id = '1';
	$sql = $wpdb->prepare("SELECT endpoint FROM " . $table_name . " WHERE ID = %d",$id);
	$endpoint = $wpdb->get_var($sql);

	$sql = $wpdb->prepare("SELECT username FROM " . $table_name . " WHERE ID = %d",$id);
	$username = $wpdb->get_var($sql);

	$sql = $wpdb->prepare("SELECT password FROM " . $table_name . " WHERE ID = %d",$id);
        $password = $wpdb->get_var($sql);

	$sql = $wpdb->prepare("SELECT listid FROM " . $table_name . " WHERE ID = %d",$id);
        $listid = $wpdb->get_var($sql);
	?>

	<div class="wrap">
	<h2><img src="<? echo path_join(WP_PLUGIN_URL, basename(dirname(__FILE__))."/assets/img/opmwp-logo.png"); ?>" alt="1PointMail Logo" style="border: #000 1px solid;"><div style="display: inline-block; padding: 0px 0px 35px 0px; vertical-align: middle;">&nbsp;&nbsp;Options Page</div></h2>
	<p>Please Note: All information is required.</p>

	<form action="" method="POST" id="opmwp-admin-options-form">
		<?php wp_nonce_field('opmwp_settings','opmwp_settings_updated'); ?>
		<h2 class="title">API Information<hr></h2>
		<table class="form-table">
		<tr>
		 <th scope="row"><label for="opmwp-endpoint">API Endpoint: </label></th>
		 <td><input type="text" id="opmwp-endpoint" name="opmwp-endpoint" size="45" value="<?php echo esc_attr($endpoint);?>" />
		 <p class="description">The URL to the 1PointMail API Endpoint<br>(ex: http://api.1pointinteractive.com/OnePointAPIService.asmx)</p></td>
		</tr>
		<tr>
		 <th scope="row"><label for="opmwp-username">API Username: </label></th>
		 <td><input type="text" id="opmwp-username" name="opmwp-username" value="<?php echo esc_attr($username);?>" /></td>
		</tr>
                <tr>
		 <th scope="row"><label for="opmwp-password">API Password: </label></th>
                 <td><input type="password" id="opmwp-password" name="opmwp-password" value="<?php echo esc_attr($password);?>" /></td>
                </tr>
                <tr>
                 <th scope="row"><label for="opmwp-password">&nbsp;</label></th>
                 <td>
		 <div id="checkInfo"><p class="description">&nbsp;</p></div>
		 <div id="checkMyLogin" class="button action">Test Login</div>
		 </td>
                </tr>
		</table>

		<h2 class="title">List Information<hr></h2>
		<table class="form-table">
                <tr>
		 <th scope="row"><label for="opmwp-listid">1PointMail List ID: </label></th>
                 <td><input type="text" id="opmwp-listid" name="opmwp-listid" size="5" value="<?php echo esc_attr($listid);?>" />
		 <p class="description">The ListID of the list you wish to add subscribers to. It can be found under <b>Lists -> Lists</b>, and is next to the name of the list in the table.</p>
		 </td>
                </tr>
		<tr>
		<td>&nbsp;</td>
		<td><input type="submit" name="submit" class="button button-primary button-large" value="Update Settings" /></td>
		</tr>
		</table>
	</form>
	</div>
	<?php
} // EO Function

// Admin Screen Function
function opmwp_plugin_menu() {
	add_menu_page('1PointMail Settings', '1PointMail', 'manage_options', 'opmwp-optin-plugin', 'opmwp_option_page', 
					'dashicons-email',71);
} // EO Function
// Register the Admin menu
add_action('admin_menu','opmwp_plugin_menu');


/**
==================================================
========== Dashboard Widget
**/

function opmwp_dashboard_widget() {
	global $wpdb;

	$table_name = $wpdb->prefix . "onepointmail";
        $id = '1';

        $sql = $wpdb->prepare("SELECT listname FROM " . $table_name . " WHERE ID = %d",$id);
        $listname = $wpdb->get_var($sql);

        $sql = $wpdb->prepare("SELECT listid FROM " . $table_name . " WHERE ID = %d",$id);
        $listid = $wpdb->get_var($sql);

	$sql = $wpdb->prepare("SELECT last_update FROM " . $table_name . " WHERE ID = %d",$id);
        $lastupdate = $wpdb->get_var($sql);

	?>
	<p><strong>List ID:</strong> <?php echo $listid; ?><br>
	<strong>List Name:</strong> <?php echo $listname; ?></p>
	<p align="right"><i>Last Updated: <?php echo date('F j, Y, g:i a', strtotime($lastupdate)); ?><br>(Server Time)</i></p>
	<p><div id="syncButton" class="button action">Sync Now</div><div id='syncInfo'></div></p>
	<p align="right"><img src="<? echo path_join(WP_PLUGIN_URL, basename(dirname(__FILE__))."/assets/img/opmwp-logo.png"); ?>" alt="1PointMail Logo"></p>
	<?php
} // EO Function

function opmwp_register_dashboard_widget() {
	wp_add_dashboard_widget('opmwp-dashboard-widget','1PointMail Statistics','opmwp_dashboard_widget');
} // EO Function
add_action('wp_dashboard_setup','opmwp_register_dashboard_widget');


/**
==================================================
========== Main Stuff
**/

// Add the ajax stuff from the admin section to the main page so we can use it.
add_action('wp_head','opmwp_ajaxurl');
function opmwp_ajaxurl() {
	?>
		<script type="text/javascript">
			var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
		</script>
	<?php
} // EO Function


// Function called by ajax via the 'Test Login' button on the admin page.
function opmwp_login_check() {

	$endpoint 	= isset($_POST['opmwp-endpoint']) ? $_POST['opmwp-endpoint'] : null;
	$uid 		= isset($_POST['opmwp-username']) ? $_POST['opmwp-username'] : null;
	$pwd		= isset($_POST['opmwp-password']) ? $_POST['opmwp-password'] : null;

	// Get login data from the database to test API login
	$msg = "Invalid";

	// Do Check
	if (($endpoint != null)&&($uid != null)&&($pwd != null)) {
		// Build the URL to call
		$url = $endpoint . "/Login?UserId=" . $uid . "&Password=" . $pwd;

		// Init curl
		$r = curl_init($url);
		curl_setopt($r, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($r, CURLOPT_CUSTOMREQUEST, "GET");

		// Call curl
		$ret = curl_exec($r);

		// Test
		if (strpos($ret, 'you have logged in Successfully') !== false) {
			$msg = "valid";
		};
	} // EO if

	echo $msg;
	die();
	
} // EO Function

// Include JS for the admin section
add_action('wp_ajax_opmwp_login_check','opmwp_login_check');
add_action('admin_print_scripts','opmwp_login_check_script');

function opmwp_login_check_script() {
	wp_enqueue_script("opmwp-admin",path_join(WP_PLUGIN_URL, basename(dirname(__FILE__))."/assets/js/jq_opmwp_admin.js"), array('jquery'));
} // EO Function

// Function called by the basic subscribe box
function opmwp_boptin_subscribe() {
        global $wpdb;

        $table_name = $wpdb->prefix . "onepointmail";
        $id = '1';

        $sql = $wpdb->prepare("SELECT endpoint FROM " . $table_name . " WHERE ID = %d",$id);
        $endpoint = $wpdb->get_var($sql);

        $sql = $wpdb->prepare("SELECT username FROM " . $table_name . " WHERE ID = %d",$id);
        $username = $wpdb->get_var($sql);

        $sql = $wpdb->prepare("SELECT password FROM " . $table_name . " WHERE ID = %d",$id);
        $password = $wpdb->get_var($sql);

        $sql = $wpdb->prepare("SELECT listid FROM " . $table_name . " WHERE ID = %d",$id);
        $listid = $wpdb->get_var($sql);

        $email  = isset($_POST['boptin_email']) ? $_POST['boptin_email'] : null;
	$fname	= isset($_POST['boptin_fname']) ? $_POST['boptin_fname'] : null;
	$lname	= isset($_POST['boptin_lname']) ? $_POST['boptin_lname'] : null;

	$msg = "Error";

        if (($endpoint!=null)&&($username!=null)&&($password!=null)&&($listid!=null)&&($email!=null)) {
                $url  = $endpoint . "/AddRecipient?UserId=" . $username . "&Password=" . $password;
		$url .= "&EmailAddress=" . $email . "&ListIds=" . $listid . "&FirstName=" . $fname . "&LastName=" . $lname;

                $data = @file_get_contents( $url ); // @ will surpress errors

		// Regex to get the message response from the API
		$regex = '%Message>(.*?)</Message>%';
		preg_match( $regex, $data, $matches );

		// Set the string value of the message to $ret
		$ret = false;
		if( $matches && $matches[1] ) $ret = $matches[1];

		// 1st use wordpress is_email to test email syntax
		if (is_email($email)) {
			// Valid email - do nothing and continue
		} else {
			// No use in calling the API for the fun of it
			// if the email is not valid...
			// pass back invalid for the JS and die early...
			$msg = "invalid";
			echo $msg;
			die();
		} // EO if

		// Test our cases...
		if (strpos($ret, 'Email Already Exist') !== false) {
			$msg = "exists";
		} elseif (strpos($ret, 'Please Enter Valid Email Format') !== false) {
			$msg = "invalid";
		} elseif (strpos($ret, 'Recipient Added Successfully') !== false) {
			$msg = "ok";
		} // EO if
        } // EO If

	echo $msg;
	die();
} // EO Function

// Register the action
add_action('wp_ajax_opmwp_boptin_subscribe','opmwp_boptin_subscribe');
// Register the action for NON AUTHENTICATED users
add_action('wp_ajax_nopriv_opmwp_boptin_subscribe','opmwp_boptin_subscribe');


// Function called by the unsubscribe box
function opmwp_boptin_unsubscribe() {
        global $wpdb;

        $table_name = $wpdb->prefix . "onepointmail";
        $id = '1';

        $sql = $wpdb->prepare("SELECT endpoint FROM " . $table_name . " WHERE ID = %d",$id);
        $endpoint = $wpdb->get_var($sql);

        $sql = $wpdb->prepare("SELECT username FROM " . $table_name . " WHERE ID = %d",$id);
        $username = $wpdb->get_var($sql);

        $sql = $wpdb->prepare("SELECT password FROM " . $table_name . " WHERE ID = %d",$id);
        $password = $wpdb->get_var($sql);

        $email  = isset($_POST['boptinu_email']) ? $_POST['boptinu_email'] : null;

        $msg = "Error";

        if (($endpoint!=null)&&($username!=null)&&($password!=null)&&($email!=null)) {
                $url  = $endpoint . "/UnsubscribeContactByEmailAddress?UserId=" . $username . "&Password=" . $password;
                $url .= "&EmailAddress=" . $email;

                #$data = @file_get_contents( $url ); // @ will surpress errors

		if (is_email($email)) {
		// If the email is valid via Wordpress, then continue
			$url  = $endpoint . "/UnsubscribeContactByEmailAddress";
			$data = wp_remote_post($url, array(
						'method' => 'POST',
						'timeout' => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking' => true,
						'headers' => array(),
						'body' => array('UserId' => $username, 'Password' => $password, 'EmailAddress' => $email),
						'cookies' => array()
					)
			); // EO wp_remote_post

                	// test for wp errors
	                if( is_wp_error( $data) ) {
	                        // show error message to admins
	                        $this->show_error( "HTTP Error: " . $data->get_error_message() );
	                        return false;
	                }
		
			// Regex to get the message response from the API
			$regex = '%Message>(.*?)</Message>%';
			preg_match( $regex, $data['body'], $matches );
	
			// Set the string value of the message to $ret
			$ret = false;
			if( $matches && $matches[1] ) $ret = $matches[1];
	
			// Test our cases...
			if (strpos($ret, 'exist in Database.') !== false) {
				$msg = "exists";
			} elseif (strpos($ret, 'cannot be unsubscribed again') !== false) {
				$msg = "already";
			} elseif (strpos($ret, 'unsubscribed successfully.') !== false) {
				$msg = "ok";
			} // EO if
	
		} else {
		// Wordpress says no-go for the email format, pass back invalid for the JS and die...
			$msg = "invalid";
			echo $msg;
			die();
		} // EO if is_email

#echo "E: $endpoint <br>\n";
#echo "U: $username <br>\n";
#echo "P: $password <br>\n";
#echo "E: $email <br>\n";

#                // Regex to get the message response from the API
#                $regex = '%Message>(.*?)</Message>%';
#                preg_match( $regex, $data, $matches );
#
#                // Set the string value of the message to $ret
#                $ret = false;
#                if( $matches && $matches[1] ) $ret = $matches[1];
#
#                // 1st use wordpress is_email to test email syntax
#                if (is_email($email)) {
#                        // Valid email - do nothing and continue
#                } else {
#                        // No use in calling the API for the fun of it
#                        // if the email is not valid...
#                        // pass back invalid for the JS and die early...
#                        $msg = "invalid";
#                        echo $msg;
#                        die();
#                } // EO if
#
#                // Test our cases...
#                if (strpos($ret, 'exist in Database.') !== false) {
#                        $msg = "exists";
#		} elseif (strpos($ret, 'cannot be unsubscribed again') !== false) {
#			$msg = "already";
#                } elseif (strpos($ret, 'unsubscribed successfully.') !== false) {
#                        $msg = "ok";
#                } // EO if
#echo "R: $ret<br>\n";
#echo "M:$msg<br>\n";
        } // EO If

        echo $msg;
        die();
} // EO Function

// Register the action
add_action('wp_ajax_opmwp_boptin_unsubscribe','opmwp_boptin_unsubscribe');
// Register the action for NON AUTHENTICATED users
add_action('wp_ajax_nopriv_opmwp_boptin_unsubscribe','opmwp_boptin_unsubscribe');

// Dashboard - Run Sync
function opmwp_runsync() {
        global $wpdb;

        $table_name = $wpdb->prefix . "onepointmail";
        $id = '1';

        $sql = $wpdb->prepare("SELECT endpoint FROM " . $table_name . " WHERE ID = %d",$id);
        $endpoint = $wpdb->get_var($sql);

        $sql = $wpdb->prepare("SELECT username FROM " . $table_name . " WHERE ID = %d",$id);
        $username = $wpdb->get_var($sql);

        $sql = $wpdb->prepare("SELECT password FROM " . $table_name . " WHERE ID = %d",$id);
        $password = $wpdb->get_var($sql);

        $sql = $wpdb->prepare("SELECT listid FROM " . $table_name . " WHERE ID = %d",$id);
        $listid = $wpdb->get_var($sql);

        $msg = "Error";

	// Get List Name
        if (($endpoint!=null)&&($username!=null)&&($password!=null)&&($listid!=null)) {
                $url  = $endpoint . "/GetListByListID?UserId=" . $username . "&Password=" . $password . "&ListID=" . $listid;

                $data = @file_get_contents( $url ); // @ will surpress errors

                // Regex to get the message response from the API
                $regex = '%ListName>(.*?)</ListName>%';
                preg_match( $regex, $data, $matches );

                // Set the string value of the message to $ret
                $ret = false;
                if( $matches && $matches[1] ) $ret = $matches[1];

		if ($ret != null) {
			$tDate = date("Y-m-d H:i:s");

                	$wpdb->update($table_name,
                        	array(
                                	'listname'	=> $ret,
					'last_update'	=> $tDate,
                        	),
                        	array(
                                	'ID' => '1'
                        	),
                        	array(
                                	'%s',
					'%s',
                        	)
                	);
			$msg = "ok";
		} // EO if
        } // EO If

	echo $msg;
        die();
} // EO Function
// Register the action
add_action('wp_ajax_opmwp_runsync','opmwp_runsync');


// Include JS for the front end
add_action('wp_print_scripts','opmwp_boptin_script');

function opmwp_boptin_script() {
        wp_enqueue_script("opmwp",path_join(WP_PLUGIN_URL, basename(dirname(__FILE__))."/assets/js/jq_opmwp.js"), array('jquery'));
} // EO Function



/**
==================================================
========== Widget(s)
**/
class opwmpUnsub extends WP_Widget {
	function opwmpUnsub() {
		$widget_options = array (
					'classname'	=> 'widget-opwmpUnsub',
					'description'	=> '1PointMail Unsubscribe Box',
				);
		parent::WP_Widget('opmwp_widget_unsub','1PointMail Unsubscribe Box',$widget_options);
	} // EO Function

	function widget($args,$instance) {
		extract($args, EXTR_SKIP);
		
		$title		= ($instance['title']) ? $instance['title'] : 'Unsubscribe';
		$submit_val     = ($instance['submit_val']) ? $instance['submit_val'] : 'Unsubscribe';
		$pb             = ($instance['cb_pb']) ? 'true' : 'false';

		// Crate the Widget display
		$body = "";
		$body .= "<input id='boptinu_email' name='boptinu_email' placeholder='Email Address'><br>";
                if('on' == $instance['cb_pb'] ) {
                        $body .= "Powered By <a href='http://www.1pointinteractive.com' target='_blank'>1PointMail</a>!";
                }; // EO If

                $body .= "<div id='boptin_unsubscribe'>";
                $body .= "<input type='submit' id='submit' class='button button-primary' value='" . $submit_val . "'></div>";
                $body .= "<div id='boptinInfoUnSub'><p class='description'>&nbsp;</p></div>";
                ?>
                <?php echo $before_widget; ?>
                <?php echo $before_title . $title . $after_title ?>
                <p><?php echo $body ?></p>
                <?php
	} // EO Function

	function form($instance) {
                ?>
                <p>
                <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
                <input id="<?php echo $this->get_field_id('title'); ?>"
                        name="<?php echo $this->get_field_name('title'); ?>"
                        value="<?php echo esc_attr( $instance['title'] ); ?>"
                        type="text" class="widefat" />
                </p>
                <p>
                <label for="<?php echo $this->get_field_id('submit_val'); ?>">UnSubscribe Button Text:</label>
                <input id="<?php echo $this->get_field_id('submit_val'); ?>"
                        name="<?php echo $this->get_field_name('submit_val'); ?>"
                        value="<?php echo esc_attr( $instance['submit_val'] ); ?>"
                        type="text" class="" />
                </p>
                <hr>
                <p>
                Display Powered By 1PointMail?
                &nbsp;&nbsp;&nbsp;
                <input class="checkbox" type="checkbox" <?php checked($instance['cb_pb'], 'on'); ?>
                        id="<?php echo $this->get_field_id('cb_pb'); ?>"
                        name="<?php echo $this->get_field_name('cb_pb'); ?>" />
                </p>
                <hr>
                <?php
	} // EO Function
} // EO Class

class opmwpBasicOptin extends WP_Widget {
	function opmwpBasicOptin() {
		$widget_options = array (
					'classname'	=> 'widget-opmwpBasicOptin',
					'description'	=> '1PointMail Basic Opt-In Subscription Box',
				);
		parent::WP_Widget('opmwp_widget_basicoptin','1PointMail Basic OptIn Box',$widget_options);
	} // EO Function

	function widget($args,$instance) {
		extract($args, EXTR_SKIP);

                $title		= ($instance['title']) ? $instance['title'] : 'Subscribe!';
		$submit_val	= ($instance['submit_val']) ? $instance['submit_val'] : 'Subscribe';
                $fn		= ($instance['cb_fn']) ? 'true' : 'false';
                $ln		= ($instance['cb_ln']) ? 'true' : 'false';
		$lbl		= ($instance['cb_lbl']) ? 'true' : 'false';
		$pb		= ($instance['cb_pb']) ? 'true' : 'false';

                //$body   = ($instance['body']) ? $instance['body'] : $body_default; #'Subscribe Box Goes here...';
	
		// Create the use display of the widget
		$body  = "";	

		// show firstname / lastname based on if the option is selected
 		if('on' == $instance['cb_fn'] ) {
			if('on' == $instance['cb_lbl'] ) {
				$body .= "First Name: <input id='boptin_fname' name='boptin_fnanme' placeholder='First Name'><br><br>";
			} else {
				$body .= "<input id='boptin_fname' name='boptin_fnanme' placeholder='First Name'><br><br>";
			} // EO if
		} // EO if
		if('on' == $instance['cb_ln'] ) {
			if('on' == $instance['cb_lbl'] ) {
				$body .= "Last Name: <input id='boptin_lname' name='boptin_lname' placeholder='Last Name'><br><br>";
			} else {
				$body .= "<input id='boptin_lname' name='boptin_lname' placeholder='Last Name'><br><br>";
			} // EO if
		} // EO if

		if('on' == $instance['cb_lbl'] ) {
			$body .= "Email: <input id='boptin_email' name='boptin_email' placeholder='Email Address'><br>";
		} else {
			$body .= "<input id='boptin_email' name='boptin_email' placeholder='Email Address'><br>";
		} // EO if

                if('on' == $instance['cb_pb'] ) {
                        $body .= "Powered By <a href='http://www.1pointinteractive.com' target='_blank'>1PointMail</a>!";
                }; // EO If

		$body .= "<div id='boptin_subscribe'>";
		$body .= "<input type='submit' id='submit' class='button button-primary' value='" . $submit_val . "'></div>";
		$body .= "<div id='boptinInfo'><p class='description'>&nbsp;</p></div>";

		?>
		<?php echo $before_widget; ?>
		<?php echo $before_title . $title . $after_title ?>
		<p><?php echo $body ?></p>
		<?php
	} // EO Function

	function form($instance) {
		?>
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
		<input id="<?php echo $this->get_field_id('title'); ?>"
			name="<?php echo $this->get_field_name('title'); ?>"
			value="<?php echo esc_attr( $instance['title'] ); ?>"
			type="text" class="widefat" />
		</p>
                <p>
                <label for="<?php echo $this->get_field_id('submit_val'); ?>">Subscribe Button Text:</label>
                <input id="<?php echo $this->get_field_id('submit_val'); ?>"
                        name="<?php echo $this->get_field_name('submit_val'); ?>"
                        value="<?php echo esc_attr( $instance['submit_val'] ); ?>"
                        type="text" class="" />
                </p>
		<hr>
		<p>
		Display input boxes for:<br>
		&nbsp;&nbsp;&nbsp;<label for="<?php echo $this->get_field_id('cb_fn'); ?>">First Name: </label>&nbsp;
		<input class="checkbox" type="checkbox" <?php checked($instance['cb_fn'], 'on'); ?> 
			id="<?php echo $this->get_field_id('cb_fn'); ?>" 
			name="<?php echo $this->get_field_name('cb_fn'); ?>" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<br>
		&nbsp;&nbsp;&nbsp;<label for="<?php echo $this->get_field_id('cb_ln'); ?>">Last Name: </label>&nbsp;
                <input class="checkbox" type="checkbox" <?php checked($instance['cb_ln'], 'on'); ?>
                        id="<?php echo $this->get_field_id('cb_ln'); ?>"
                        name="<?php echo $this->get_field_name('cb_ln'); ?>" />

		</p>
                <p>
                Display input box labels?
                &nbsp;&nbsp;&nbsp;
                <input class="checkbox" type="checkbox" <?php checked($instance['cb_lbl'], 'on'); ?>
                        id="<?php echo $this->get_field_id('cb_lbl'); ?>"
                        name="<?php echo $this->get_field_name('cb_lbl'); ?>" />
		</p>
		<hr>
                <p>
                Display Powered By 1PointMail?
                &nbsp;&nbsp;&nbsp;
                <input class="checkbox" type="checkbox" <?php checked($instance['cb_pb'], 'on'); ?>
                        id="<?php echo $this->get_field_id('cb_pb'); ?>"
                        name="<?php echo $this->get_field_name('cb_pb'); ?>" />
                </p>
		<hr>
		<?php
	} // EO Function
} // EO Class

function opmwp_basicoptin_init() {
	register_widget("opmwpBasicOptin");
} // EO Function
add_action('widgets_init','opmwp_basicoptin_init');

function opmwp_unsub_init() {
	register_widget("opwmpUnsub");
} // EO Function
add_action('widgets_init','opmwp_unsub_init');


/**
==================================================
========== Rate
**/

function rate_opmwp($links, $file) {
        if ($file == plugin_basename(__FILE__)) {
                $rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . basename(dirname(__FILE__)) . '?rate=5#postform';
                $links[] = '<a href="' . $rate_url . '" target="_blank" title="Click here to rate and review this plugin on WordPress.org">Rate this plugin</a>';
        }
        return $links;
}
add_filter('plugin_row_meta', 'rate_opmwp', 10, 2);
?>

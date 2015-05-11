<?php
/*
Plugin Name: Errnio
Plugin URI: http://errnio.com
Description: errnio adds gesture based monetization tools to the mobile version of the site. It enhances mobile engagement if users with additional mobile functionality, to generate added monetization without banners, without ads.
Version: 1.2
Author: Errnio
Author URI: http://errnio.com
*/

define('ERRNIO_INSTALLER_NAME', 'wordpress_gesture_monetization');

// Utils
function do_post_request($url, $data) {
    $data = json_encode( $data );
    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/json',
            'content' => $data
        )
    );
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    return json_decode($response);
}

function errnio_createTagId() {
	$urlpre = 'http://customer.errnio.com';

 	$createTagUrl = $urlpre.'/createTag';
 	$params = array('installerName' => ERRNIO_INSTALLER_NAME);
 	$response = do_post_request($createTagUrl, $params);

	if (!$response || !$response->success) {
		return NULL;
	} else {
		$tagId = $response->tagId;
		if (get_option(ERRNIO_OPTION_NAME_TAGID) == '') {
			update_option(ERRNIO_OPTION_NAME_TAGID, $tagId);
		} else {
			add_option(ERRNIO_OPTION_NAME_TAGID, $tagId);
		}
	 	add_option(ERRNIO_OPTION_NAME_TAGTYPE, ERRNIO_TAGTYPE_TEMP);
	}

	return $tagId;
}

function errnio_sendEvent($eventType) {
	$tagId = get_option(ERRNIO_OPTION_NAME_TAGID);
	if ($tagId) {
		$urlpre = 'http://customer.errnio.com';
	 	$createTagUrl = $urlpre.'/sendEvent';

	 	$params = array('tagId' => $tagId, 'eventName' => $eventType);
	 	$response = do_post_request($createTagUrl, $params);
	}
	// No tagId - no point sending an event
}

function checkShouldRegister() {
	$tagtype = get_option(ERRNIO_OPTION_NAME_TAGTYPE);
	$tagId = get_option(ERRNIO_OPTION_NAME_TAGID);
	$shouldregister = true;

	if (!$tagtype) {
		// No tag type - means legacy user
		if (!$tagId || empty($tagId)) {
			// No tag type + no tag id - means legacy user never registered - needs temp tag id
			$shouldregister = true;
			$tagId = errnio_createTagId();
		} else {
			// No tag type + has tag id - means legacy user who registered - need to set tag type to perm
			$shouldregister = false;
			add_option(ERRNIO_OPTION_NAME_TAGTYPE, ERRNIO_TAGTYPE_PERM);
		}
	} else {
		// Have tag type - means new user
		if ($tagtype == ERRNIO_TAGTYPE_TEMP) {
			// tag type temp - means we also have tag id
			$shouldregister = true;
		} else {
			// tag type perm - ideal! - new registered user
			$shouldregister = false;
		}
	}

	return $shouldregister;
}

// Option names
define('ERRNIO_OPTION_NAME_TAGID', 'errnio_api');
define('ERRNIO_OPTION_NAME_REGISTERED', 'errnio_registered');
define('ERRNIO_OPTION_NAME_TAGTYPE', 'errnio_api_type');
define('ERRNIO_OPTION_NAME_PLUGINNAME', 'errnio_plugin_name');
// Constants
define('ERRNIO_EVENT_NAME_ACTIVATE', 'wordpress_activated');
define('ERRNIO_EVENT_NAME_DEACTIVATE', 'wordpress_deactivated');
define('ERRNIO_EVENT_NAME_UNINSTALL', 'wordpress_uninstalled');
define('ERRNIO_TAGTYPE_TEMP', 'temporary');
define('ERRNIO_TAGTYPE_PERM', 'permanent');

// Activation / Deactivation / Uninstall hooks

function errnio_activate() {
	if ( ! current_user_can( 'activate_plugins' ) )
	        return;

	$tagId = get_option(ERRNIO_OPTION_NAME_TAGID);

 	// If tagId option exists - reset it, then create new temp tag - use will procede to errnio settings
	if ($tagId != NULL && !empty($tagId)) {
		// TagId exists - but no type flag - means legacy user - means tagId is permanent
		if (!get_option(ERRNIO_OPTION_NAME_TAGTYPE)) {
			add_option(ERRNIO_OPTION_NAME_TAGTYPE, ERRNIO_TAGTYPE_PERM);
		}
	} else {
		errnio_createTagId();
	}

	add_option(ERRNIO_OPTION_NAME_PLUGINNAME, ERRNIO_INSTALLER_NAME);

	// Send event - activated
	errnio_sendEvent(ERRNIO_EVENT_NAME_ACTIVATE);
}

function errnio_deactivate() {
	if ( ! current_user_can( 'activate_plugins' ) )
	        return;

	// Send event - deactivated
	errnio_sendEvent(ERRNIO_EVENT_NAME_DEACTIVATE);
}

function errnio_uninstall() {
	if ( ! current_user_can( 'activate_plugins' ) )
	        return;

	// Send event - uninstall
	errnio_sendEvent(ERRNIO_EVENT_NAME_UNINSTALL);
}

register_activation_hook( __FILE__, 'errnio_activate' );
register_deactivation_hook( __FILE__, 'errnio_deactivate' );
register_uninstall_hook( __FILE__, 'errnio_uninstall' );

// Options / Settings section

function errnio_register_callback() {
	$type = $_POST['type'];
	$tagId = $_POST['tag_id'];

	if ($type == 'switchTag') {
		update_option(ERRNIO_OPTION_NAME_TAGID, $tagId);
	}

	update_option(ERRNIO_OPTION_NAME_TAGTYPE, ERRNIO_TAGTYPE_PERM);

	wp_die(); // this is required to terminate immediately and return a proper response
}

function errnio_admin_page() {
	$stylehandle = 'errnio-style';
	$jshandle = 'errnio-js';
	wp_register_style($stylehandle, plugins_url('assets/css/errnio.css', __FILE__));
	wp_enqueue_style($stylehandle);
	wp_register_script($jshandle, plugins_url('assets/js/errnio.js', __FILE__), array('jquery'));
	wp_enqueue_script($jshandle);
	wp_localize_script($jshandle, 'errniowp', array('ajax_url' => admin_url( 'admin-ajax.php' )));
    ?>
    <div class="wrap">
		<?php
		$shouldregister = checkShouldRegister();
		$tagId = get_option(ERRNIO_OPTION_NAME_TAGID);

		echo '<h2>Errnio Options</h2>';

		if (!$shouldregister) {
			echo '<p>Thank you for joining errnio. Your new plugin is up and running.<br/>For any issues please contact us at <a href="mailto:info@errnio.com">info@errnio.com</a>.</p>';
		} else {
			$urlpre = 'http://errnio.com';

			if ($tagId) {
				$url = $urlpre.'/register/iframe.html?tagId='.$tagId.'&installerName='.ERRNIO_INSTALLER_NAME;
				echo '<iframe src="'.$url.'" id="errnio-iframe" height="100%" width="100%" frameborder="0"></iframe>';
			} else {
				echo '<p>There was an error :( Contact <a href="mailto:info@errnio.com">info@errnio.com</a> for help.</p>';
			}
		};

		?>
    </div>
    <?php
}

function errnio_add_menu() {
   // Consider changing icon to 'none' and style that div
    add_menu_page (
        'Errnio Settings',
        'Errnio',
        'manage_options',
        'errnio-settings',
        'errnio_admin_page',
		'dashicons-smartphone'
    );
}

function wptuts_admin_notices() {
	$shouldregister = checkShouldRegister();
	$settingsurl = get_bloginfo('wpurl').'/wp-admin/admin.php?page=errnio-settings';

	if($shouldregister){
		echo( '<div class="error" style="font-weight:bold;font-size=22px;color:red;"> <p> Errnio needs to be configured, please register <a href="'.$settingsurl.'">here</a> </p> </div>');
	}
}

function errnio_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=errnio-settings">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}

add_action('admin_menu', 'errnio_add_menu');
add_action('admin_notices', 'wptuts_admin_notices');
add_filter('plugin_action_links', 'errnio_plugin_action_links', 10, 2);
add_action('wp_ajax_errnio_register', 'errnio_register_callback');

// Hook into pages for loading script - Display script on site

function errnio_load_script() {
	$list = 'enqueued';
	$handle = 'errnio_script';

	// Script already running on this page
	if (wp_script_is($handle, $list)) {
		return;
	}

	$tagId = get_option(ERRNIO_OPTION_NAME_TAGID);

	if (!$tagId || empty($tagId)) {
		$tagId = errnio_createTagId();
	}

	if ($tagId) {
		$script_url = "//service.errnio.com/loader?tagid=".$tagId;
		wp_register_script($handle, $script_url);
		wp_enqueue_script($handle );
	}
}

function add_async_attr( $url ) {
	if(FALSE === strpos( $url, 'service.errnio.com')){
		return $url;
	}

	return "$url' async='async";
}

add_filter('clean_url', 'add_async_attr', 11, 1);
add_action('wp_enqueue_scripts', 'errnio_load_script', 99999 );
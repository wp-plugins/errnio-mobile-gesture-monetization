<?php
/*
Plugin Name: Errnio
Plugin URI: http://errnio.com
Description: errnio adds gesture based monetization tools to the mobile version of the site. It enhances mobile engagement if users with additional mobile functionality, to generate added monetization without banners, without ads.
Version: 1.1
Author: Errnio
Author URI: http://errnio.com
*/

// Errnio page - options
function errnio_plugin_menu() {
	add_options_page('Errnio Options', 'Errnio Options', 'manage_options', 'errnio.php', 'errnio_options');
}
add_action('admin_menu', 'errnio_plugin_menu');


// Errnio page - register
if( !get_option("slider_option_width") ) {
	add_option("errnio_api", '', '', 'yes');
}

// Errnio page - function
function errnio_options() {

	wp_register_style( 'errnio-style', plugins_url( 'css/style.css', __FILE__ ) );
	wp_enqueue_style( 'errnio-style' );

?>
	<form method="post" action="options.php">
		<div class="wrap">
			<h2>Errnio Options</h2>
			<div class="wrap-options">


					<?php wp_nonce_field('update-options'); ?>

					<div class="wrap-options-left">
						<div class="element">
							<label>Errnio Site ID:</label>
							<input name="errnio_api" type="text" id="errnio_api" value="<?php echo get_option('errnio_api'); ?>" />
						</div>
					</div>
					</br>
					<div class="wrap-options-separator"></div>

					<div class="wrap-options-right">
						<div class="element">
							Don't have an errnio site ID? <a class="publisher_button" href="http://errnio.com/#contact" target="_blank">Become a Publisher</a> </div></br>
							<div class="description_small">Grab our unique code for your site and go live today.</div>
						</div>
					</div>

					<div class="clear"></div>

					<input type="hidden" name="page_options" value="errnio_api" />
					<input type="hidden" name="action" value="update" />


			</div>
		</div>

		<input class="button button-primary wrap-options-submit" type="submit" value="Save Changes" />

	</form>
<?php
}

//Display script on site
function errnio_load_script()
{
	if(get_option('errnio_api')){
		$script_url = "//service.errnio.com/loader?tagid=".get_option('errnio_api');

		wp_register_script( 'errnio_script', $script_url, array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'errnio_script');
	}
	else{
		$script_url = "//service.errnio.com/loader?tagid=54982f10154585edd75615e0";

		wp_register_script( 'errnio_script', $script_url, array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'errnio_script');
	}
}

function add_async_attr( $url )
{
	if(FALSE === strpos( $url, 'service.errnio.com')){
		return $url;
	}

	return "$url' async='async";
}

add_filter( 'clean_url', 'add_async_attr', 11, 1);

add_action( 'wp_enqueue_scripts', 'errnio_load_script', 99999 );


/* Display a notice if plugin is not configured */
add_action( 'admin_notices', 'wptuts_admin_notices' );
function wptuts_admin_notices() {
	if(!get_option('errnio_api')){
		echo( '<div class="error" style="font-weight:bold;font-size=22px;color:red;"> <p> Errnio needs to be configured, enter your code <a href="'.admin_url( 'options-general.php?page=errnio.php' ).'">here</a> </p> </div>');
	}
}

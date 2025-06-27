<?php
/**
 *
 * @link              https://fronseye.com
 * @since             1.0.0
 * @package           mass guna
 *
 * @wordpress-plugin
 * Plugin Name:       EPP - Dashboard
 * Plugin URI:        https://fronseye.com
 * Description:       Passage Customization
 * Version:           1.0.0
 * Author:            Developer
 * Author URI:        https://fronseye.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       EPP - Dashboard
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PASSAGE_CUSTOMIZATION_VERSION', '1.0.1' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-passage-customization-activator.php
 */
function activate_passage_customization() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-passage-customization-activator.php';
	Passage_Customization_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-passage-customization-deactivator.php
 */
function deactivate_passage_customization() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-passage-customization-deactivator.php';
	Passage_Customization_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_passage_customization' );
register_deactivation_hook( __FILE__, 'deactivate_passage_customization' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-passage-customization.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

new Passage_Customization();

function disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' ); 
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' ); 
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	//add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
	//add_filter( 'wp_resource_hints', 'disable_emojis_remove_dns_prefetch', 10, 2 );
}
add_action( 'init', 'disable_emojis' );

















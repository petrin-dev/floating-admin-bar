<?php
/**
 * Plugin Name:       Floating Admin Bar
 * Description:       A compact, draggable, grid-based replacement for the WordPress admin bar.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Petrin.dev
 * Author URI:        https://petrin.dev
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       floating-admin-bar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FAB_VERSION', '1.0.0' );
define( 'FAB_PLUGIN_FILE', __FILE__ );
define( 'FAB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FAB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once FAB_PLUGIN_DIR . 'includes/class-fab-loader.php';
require_once FAB_PLUGIN_DIR . 'includes/class-fab-settings.php';

/**
 * Kick things off once all plugins (and their admin_bar_menu callbacks) are registered.
 */
function fab_init() {
	FAB_Settings::instance();
	FAB_Loader::instance();
}
add_action( 'plugins_loaded', 'fab_init' );

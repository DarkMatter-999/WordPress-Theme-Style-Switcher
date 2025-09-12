<?php
/**
 * Plugin Name:       Theme Style Switcher
 * Plugin URI:        https://github.com/DarkMatter-999/WordPress-Theme-Style-Switcher
 * Description:       Custom block plugin to change theme styles on the go.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            DarkMatter-999
 * Author URI:        https://github.com/DarkMatter-999
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       theme-style-switcher
 * Domain Path:       /languages
 *
 * @category Plugin
 * @package  DM_Theme_Style_Switcher
 * @author   DarkMatter-999 <darkmatter999official@gmail.com>
 * @license  GPL v2 or later <https://www.gnu.org/licenses/gpl-2.0.html>
 * @link     https://github.com/DarkMatter-999/WordPress-Theme-Style-Switcher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Plugin base path and URL.
 */
define( 'TSS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'TSS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once TSS_PLUGIN_PATH . 'include/helpers/autoloader.php';

use DM_Theme_Style_Switcher\Plugin;

Plugin::get_instance();

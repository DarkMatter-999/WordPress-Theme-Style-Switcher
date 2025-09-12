<?php
/**
 * Main Assets Class File
 *
 * Main Theme Asset class file for the Plugin. This class enqueues the necessary scripts and styles.
 *
 * @package DM_Theme_Style_Switcher
 **/

namespace DM_Theme_Style_Switcher;

use DM_Theme_Style_Switcher\Traits\Singleton;

/**
 * Main Assets Class File
 *
 * Main Theme Asset class file for the Plugin. This class enqueues the necessary scripts and styles.
 *
 * @since 1.0.0
 **/
class Assets {

	use Singleton;

	/**
	 * Constructor for the Assets class.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_assets' ) );
	}

	/**
	 * Enqueues styles and scripts for the theme.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$style_asset = include TSS_PLUGIN_PATH . 'assets/build/css/main.asset.php';
		wp_enqueue_style(
			'main-css',
			TSS_PLUGIN_URL . 'assets/build/css/main.css',
			$style_asset['dependencies'],
			$style_asset['version']
		);

		$script_asset = include TSS_PLUGIN_PATH . 'assets/build/js/main.asset.php';

		wp_enqueue_script(
			'main-js',
			TSS_PLUGIN_URL . 'assets/build/js/main.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}

	/**
	 * Enqueues styles and scripts for the frontend.
	 *
	 * @return void
	 */
	public function enqueue_block_assets() {
		$style_asset = include TSS_PLUGIN_PATH . 'assets/build/css/screen.asset.php';
		wp_enqueue_style(
			'block-css',
			TSS_PLUGIN_URL . 'assets/build/css/screen.css',
			$style_asset['dependencies'],
			$style_asset['version']
		);

		$script_asset = include TSS_PLUGIN_PATH . 'assets/build/js/screen.asset.php';

		wp_enqueue_script(
			'block-js',
			TSS_PLUGIN_URL . 'assets/build/js/screen.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}
}

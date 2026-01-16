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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin styles for plugin settings page.
	 *
	 * @param string $hook The current admin page.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on Theme Style Switcher settings page under Appearance.
		if ( 'appearance_page_dm-theme-style-switcher' !== $hook ) {
			return;
		}

		$style_asset = include TSS_PLUGIN_PATH . 'assets/build/css/admin.asset.php';
		wp_enqueue_style(
			'tss-admin-css',
			TSS_PLUGIN_URL . 'assets/build/css/admin.css',
			$style_asset['dependencies'],
			$style_asset['version']
		);
	}
}

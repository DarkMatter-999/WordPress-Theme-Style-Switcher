<?php
/**
 * Main Plugin File for Plugin.
 *
 * @package DM_Theme_Style_Switcher
 */

namespace DM_Theme_Style_Switcher;

use DM_Theme_Style_Switcher\Traits\Singleton;

/**
 * Main Plugin File for the Plugin.
 */
class Plugin {


	use Singleton;

	/**
	 * Constructor for the Plugin.
	 *
	 * @return void
	 */
	public function __construct() {
		Assets::get_instance();
		Blocks::get_instance();
		Theme_Data::get_instance();
		Settings::get_instance();
	}
}

<?php
/**
 * Settings Page for Theme Style Switcher.
 *
 * @package DM_Theme_Style_Switcher
 */

namespace DM_Theme_Style_Switcher;

use DM_Theme_Style_Switcher\Traits\Singleton;

/**
 * Settings Page for Theme Style Switcher.
 */
class Settings {

	use Singleton;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings page to admin menu.
	 */
	public function add_settings_page() {
		add_theme_page(
			'Theme Style Switcher Settings',
			'Theme Style Switcher',
			'edit_theme_options',
			'dm-theme-style-switcher',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting( 'dm_tss_settings_group', 'dm_tss_override_layout' );
		register_setting( 'dm_tss_settings_group', 'dm_tss_override_colors' );
	}



	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Theme Style Switcher Settings', 'dm-tss' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'dm_tss_settings_group' ); ?>
				<?php do_settings_sections( 'dm_tss_settings_group' ); ?>

				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Don\'t Override Layout', 'dm-tss' ); ?></th>
						<td>
							<label class="tss-toggle-switch">
								<input type="checkbox" name="dm_tss_override_layout" value="1" <?php checked( get_option( 'dm_tss_override_layout' ), 1 ); ?> />
								<span class="tss-slider"></span>
							</label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Don\'t Override Colors', 'dm-tss' ); ?></th>
						<td>
							<label class="tss-toggle-switch">
								<input type="checkbox" name="dm_tss_override_colors" value="1" <?php checked( get_option( 'dm_tss_override_colors' ), 1 ); ?> />
								<span class="tss-slider"></span>
							</label>
						</td>
					</tr>
				</table>

				<?php submit_button( 'Save Settings' ); ?>
			</form>
		</div>
		<?php
	}
}

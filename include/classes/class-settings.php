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
		register_setting(
			'dm_tss_settings_group',
			'dm_tss_override_layout',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);

		register_setting(
			'dm_tss_settings_group',
			'dm_tss_override_colors',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);

		register_setting(
			'dm_tss_settings_group',
			'dm_tss_use_mapped_names',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);

		register_setting(
			'dm_tss_settings_group',
			'dm_tss_name_mappings',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_name_mappings' ),
				'default'           => '',
			)
		);
	}

	/**
	 * Sanitize name mappings input.
	 *
	 * Accepts:
	 * - An associative array provided by WP.
	 *
	 * Returns a sanitized associative array.
	 *
	 * @param mixed $input Raw input.
	 * @return array Sanitized associative array of mappings.
	 */
	public function sanitize_name_mappings( $input ) {
		$sanitized = array();

		if ( is_array( $input ) ) {
			foreach ( $input as $k => $v ) {
				$k = is_string( $k ) ? trim( $k ) : '';
				if ( '' === $k ) {
					continue;
				}
				$sanitized[ sanitize_text_field( $k ) ] = sanitize_text_field( $v );
			}
		}

		return $sanitized;
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

					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Use Custom Name Mappings', 'dm-tss' ); ?></th>
						<td>
							<label class="tss-toggle-switch">
								<input type="checkbox" name="dm_tss_use_mapped_names" value="1" <?php checked( get_option( 'dm_tss_use_mapped_names' ), 1 ); ?> />
								<span class="tss-slider"></span>
							</label>
							<p class="description"><?php esc_html_e( 'When enabled, names shown on the frontend will use the mappings defined below instead of default names.', 'dm-tss' ); ?></p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Name Mappings', 'dm-tss' ); ?></th>
						<td>
							<?php
							$styles = Theme_Data::get_instance()->tss_get_theme_variations();

							$saved_mappings = get_option( 'dm_tss_name_mappings', array() );

							if ( empty( $styles ) ) {
								echo '<p>' . esc_html__( 'No theme styles found in the theme\'s styles/ directory.', 'dm-tss' ) . '</p>';
							} else {
								// Build a table of inputs: include a row for the site default, then one input per discovered style.
								echo '<table class="form-table"><tbody>';

								foreach ( $styles as $style ) {
									$slug  = isset( $style['slug'] ) ? $style['slug'] : '';
									$title = isset( $style['title'] ) ? $style['title'] : $slug;

									$value = isset( $saved_mappings[ $slug ] ) ? $saved_mappings[ $slug ] : '';
									?>
									<tr>
										<th scope="row"><?php echo esc_html( $title ); ?></th>
										<td>
											<input type="text" name="dm_tss_name_mappings[<?php echo esc_attr( $slug ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="<?php echo esc_attr( $title ); ?>" />
											<p class="description"><?php esc_html_e( 'Custom display name for this style. Leave blank to use the default label.', 'dm-tss' ); ?></p>
										</td>
									</tr>
									<?php
								}
								echo '</tbody></table>';
							}
							?>
							<p class="description"><?php esc_html_e( 'Set a custom display name per style. These inputs are saved as an associative mapping (option `dm_tss_name_mappings`). The plugin will use these names when "Use Custom Name Mappings" is enabled.', 'dm-tss' ); ?></p>
						</td>
					</tr>

				</table>

				<?php submit_button( 'Save Settings' ); ?>
			</form>
		</div>
		<?php
	}
}

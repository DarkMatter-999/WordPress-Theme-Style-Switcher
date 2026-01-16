<?php
/**
 * Main File for all theme related operations.
 *
 * @package DM_Theme_Style_Switcher
 */

namespace DM_Theme_Style_Switcher;

use DM_Theme_Style_Switcher\Traits\Singleton;

/**
 * Main class for all theme related operations.
 */
class Theme_Data {

	use Singleton;

	/**
	 * Constructor for the ThemeData.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'tss_enqueue_scoped_variation_styles' ) );
		add_action( 'wp_head', array( $this, 'tss_output_variation_fonts' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'tss_localize_script' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'tss_localize_script' ) );
	}

	/**
	 * Scopes the provided CSS string by prefixing selectors with a given class.
	 *
	 * This method parses a CSS string, identifies selectors and media queries,
	 * and modifies them to ensure they apply only within the context of the
	 * specified `$scoped_class`. Specifically, `:root` selectors are converted
	 * to `:root.$scoped_class`, and other selectors are prefixed with
	 * `.$scoped_class ` (e.g., `.wp-block-group` becomes `.$scoped_class .wp-block-group`).
	 * Media queries have their inner CSS recursively scoped.
	 *
	 * @param string $css          The raw CSS string to be scoped.
	 * @param string $scoped_class The class name to use for scoping (e.g., 'is-style-dark').
	 * @return string The scoped CSS string.
	 */
	public function scope_theme_json_css( string $css, string $scoped_class ): string {
		$scoped_css = '';
		$lines      = preg_split( '/(?<=\})\s*/', $css );

		foreach ( $lines as $line ) {
				$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}

			// Handle @media queries.
			if ( str_starts_with( $line, '@media' ) ) {
				// Extract media query and inner CSS.
				if ( preg_match( '/@media\s+([^{]+)\{(.*)\}\s*$/s', $line, $matches ) ) {
					$media_query = trim( $matches[1] );
					$inner_css   = trim( $matches[2] );

					// Recursively scope inner CSS.
					$scoped_inner = $this->scope_theme_json_css( $inner_css, $scoped_class );

					$scoped_css .= "@media $media_query {\n" . $scoped_inner . "\n}\n";
				} else {
							$scoped_css .= $line . "\n";
				}
			} elseif ( preg_match( '/^([^{]+)\{(.*)\}$/s', $line, $matches ) ) {
				// Handle normal CSS blocks (:root, .wp-block-*, etc.).
				$selectors = trim( $matches[1] );
				$rules     = trim( $matches[2] );

				// Prefix each selector with the scoped class.
				$prefixed_selectors = implode(
					', ',
					array_map(
						function ( $selector ) use ( $scoped_class ) {
							$selector = trim( $selector );

							// Special case for :root — we scope as :root.scoped_class.
							if ( ':root' === $selector ) {
								return ":root.$scoped_class";
							}

							// Otherwise, normal scoping via class prefix.
							return ".$scoped_class $selector";
						},
						explode( ',', $selectors )
					)
				);

				$scoped_css .= $prefixed_selectors . " {\n" . $rules . "\n}\n";
			} else {
				// Unknown block format, just pass through.
				$scoped_css .= $line . "\n";
			}
		}

		return $scoped_css;
	}


	/**
	 * Scopes the CSS variables defined within a `:root` block to a specific class.
	 *
	 * This method takes a CSS string and, if it contains a `:root` selector with
	 * CSS variables, it rewrites that selector to `:root.$scoped_class`. This ensures
	 * that the variables defined within that block only apply when the specified
	 * `$scoped_class` is present on the root element (e.g., `<html>`).
	 *
	 * If no `:root` block is found, it wraps the entire provided CSS string
	 * within a `:root.$scoped_class` block as a fallback.
	 *
	 * @param string $css          The CSS string, potentially containing a :root block.
	 * @param string $scoped_class The class name to apply to the :root selector.
	 * @return string The CSS string with :root variables scoped to the provided class.
	 */
	public function scope_root_css_variables( $css, $scoped_class ) {
		// Match the contents inside :root { ... }.
		if ( preg_match( '/:root\s*{([^}]*)}/s', $css, $matches ) ) {
			$inner_css = trim( $matches[1] );
			return ":root.$scoped_class {\n" . $inner_css . "\n}";
		} else {
			// No :root found, fallback.
			return ":root.$scoped_class {\n" . $css . "\n}";
		}
	}

	/**
	 * Recursively cleans an array by removing null values and empty arrays.
	 *
	 * This method traverses an array and its nested arrays. It removes any element
	 * whose value is `null`. After processing nested arrays, if a nested array
	 * becomes empty, it is also removed.
	 *
	 * @param array $input_array The array to be cleaned.
	 * @return array The cleaned array.
	 */
	public function tss_deep_clean_array( $input_array ) {
		foreach ( $input_array as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = $this->tss_deep_clean_array( $value );
			}

			if ( is_null( $value ) ) {
				unset( $input_array[ $key ] );
			}

			if ( is_array( $value ) && empty( $value ) ) {
				unset( $input_array[ $key ] );
			}
		}
		return $input_array;
	}

	/**
	 * Removes invalid presets from the provided settings array.
	 *
	 * This method iterates through predefined paths within the `$settings` array
	 * (e.g., color palettes, font families, font sizes, spacing sizes). For each
	 * path, it filters the presets, keeping only those that are arrays and have
	 * a 'slug' key defined. This helps in cleaning up theme.json settings that
	 * might contain malformed or incomplete preset definitions.
	 *
	 * @param array $settings The theme settings array, passed by reference, to be cleaned.
	 * @return array The cleaned theme settings array.
	 */
	public function tss_remove_invalid_presets( &$settings ) {
		$preset_paths = array(
			array( 'color', 'palette' ),
			array( 'color', 'duotone' ),
			array( 'color', 'gradients' ),
			array( 'typography', 'fontFamilies' ),
			array( 'typography', 'fontSizes' ),
			array( 'spacing', 'spacingSizes' ),
			array( 'spacing', 'customSpacingSizes' ),
		);

		foreach ( $preset_paths as $path ) {
			$current = &$settings;

			foreach ( $path as $step ) {
				if ( ! isset( $current[ $step ] ) ) {
					continue 2;
				}

				$current = &$current[ $step ];
			}

			if ( is_array( $current ) ) {
				$current = array_values(
					array_filter(
						$current,
						function ( $preset ) {
							return is_array( $preset ) && isset( $preset['slug'] );
						}
					)
				);
			}
		}

		return $settings;
	}

	/**
	 * Retrieves the rendered CSS for the base theme.json and all style variations.
	 *
	 * This method fetches the base theme's stylesheet generated from its theme.json.
	 * It then iterates through all style variation JSON files located in the 'styles'
	 * directory of the theme, merges each variation's data with the base theme.json,
	 * cleans up the merged data (removing nulls, empty arrays, and invalid presets),
	 * and finally generates the CSS for each merged variation.
	 *
	 * @return array An associative array where keys are 'base' for the main theme CSS
	 *               and variation slugs (eg: 'dark', 'light') for variation CSS,
	 *               and values are the corresponding CSS strings.
	 */
	public function get_rendered_theme_json_css_for_all_variations() {
		$theme           = wp_get_theme();
		$styles_dir      = get_theme_file_path( 'styles' );
		$variation_files = glob( $styles_dir . '/*.json' );

		$all_css = array();

		// Base theme.json.
		$theme_json      = \WP_Theme_JSON_Resolver::get_theme_data();
		$all_css['base'] = $theme_json->get_stylesheet();

		// Variations.
		foreach ( $variation_files as $file_path ) {
			$slug         = basename( $file_path, '.json' );
			$json_data    = file_get_contents( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$decoded_data = json_decode( $json_data, true );

			if ( ! is_array( $decoded_data ) ) {
				continue;
			}

			$merged = \WP_Theme_JSON_Resolver::get_theme_data()->get_raw_data();
			$merged = array_replace_recursive( $merged, $decoded_data );

			$cleaned = $this->tss_deep_clean_array( $merged );

			if ( isset( $cleaned['settings'] ) ) {
				$cleaned['settings'] = $this->tss_remove_invalid_presets( $cleaned['settings'] );
			}

			$theme_json_obj = new \WP_Theme_JSON( $cleaned );
			$css            = $theme_json_obj->get_stylesheet();

			// If override settings are enabled, remove individual declarations that reference
			// 'size' (layout) or 'color' (colors) from the generated stylesheet. This prevents
			// theme JSON generated declarations from overriding plugin-controlled variables.
			$layout_override = get_option( 'dm_tss_override_layout' );
			$color_override  = get_option( 'dm_tss_override_colors' );

			if ( $layout_override || $color_override ) {
				$filter_css = function ( $css_string ) use ( &$filter_css, $layout_override, $color_override ) {
					$filtered_css = '';
					$lines        = preg_split( '/(?<=\})\s*/', $css_string );

					foreach ( $lines as $line ) {
						$line = trim( $line );
						if ( empty( $line ) ) {
							continue;
						}

						// Handle @media queries by filtering their inner CSS recursively.
						if ( str_starts_with( $line, '@media' ) ) {
							if ( preg_match( '/@media\s+([^{]+)\{(.*)\}\s*$/s', $line, $matches ) ) {
								$media_query = trim( $matches[1] );
								$inner_css   = trim( $matches[2] );

								$filtered_inner = $filter_css( $inner_css );

								// Only include the media block if there's remaining inner CSS.
								if ( '' !== trim( $filtered_inner ) ) {
									$filtered_css .= "@media $media_query {\n" . $filtered_inner . "\n}\n";
								}
							} else {
								$filtered_css .= $line . "\n";
							}
							continue;
						}

						// Match CSS blocks like "selector { rules }".
						if ( preg_match( '/^([^{]+)\{(.*)\}$/s', $line, $matches ) ) {
							$selectors = trim( $matches[1] );
							$rules     = trim( $matches[2] );

							// Break declarations apart by semicolon (simple split).
							// Note: this is a heuristic and assumes declarations are not containing unmatched semicolons.
							$decls = preg_split( '/;(?![^(]*\))/', $rules );

							$kept_decls = array();
							foreach ( $decls as $decl ) {
								$decl = trim( $decl );
								if ( '' === $decl ) {
									continue;
								}

								$lower_decl = strtolower( $decl );
								$remove     = false;

								// Remove declaration if it mentions 'size' and layout override is enabled.
								if ( $layout_override && strpos( $lower_decl, 'size' ) !== false ) {
									$remove = true;
								}

								// Remove declaration if it mentions 'color' and color override is enabled.
								if ( $color_override && strpos( $lower_decl, 'color' ) !== false ) {
									$remove = true;
								}

								if ( ! $remove ) {
									$kept_decls[] = $decl;
								}
							}

							// If any declarations remain, reassemble and keep the rule.
							if ( ! empty( $kept_decls ) ) {
								$filtered_rules = implode( ";\n", $kept_decls ) . ';';
								$filtered_css  .= $selectors . " {\n" . $filtered_rules . "\n}\n";
							} else { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElse -- Intentional empty block.
								// No declarations left after filtering — omit the rule entirely.
							}

							continue;
						}

						// Unknown block format: attempt a lightweight removal of matching declarations.
						$lower_line           = strtolower( $line );
						$should_remove_entire = false;
						if ( $layout_override && strpos( $lower_line, 'size' ) !== false ) {
							// Try to remove declarations containing 'size' inside the block portion of the line.
							$line = preg_replace(
								'/[^{}]*\{([^}]*)\}/s',
								function ( $m ) use ( $layout_override, $color_override ) {
									$inner = $m[1];
									$decls = preg_split( '/;(?![^(]*\))/', $inner );
									$kept  = array();
									foreach ( $decls as $d ) {
										$d = trim( $d );
										if ( '' === $d ) {
											continue;
										}
										if ( strpos( strtolower( $d ), 'size' ) !== false ) {
											continue;
										}
										if ( $color_override && strpos( strtolower( $d ), 'color' ) !== false ) {
											continue;
										}
										$kept[] = $d;
									}
									if ( empty( $kept ) ) {
										return '';
									}
									return '{' . implode( ';', $kept ) . ';}';
								},
								$line
							);
						} elseif ( $color_override && strpos( $lower_line, 'color' ) !== false ) {
							$line = preg_replace(
								'/[^{}]*\{([^}]*)\}/s',
								function ( $m ) use ( $layout_override, $color_override ) {
									$inner = $m[1];
									$decls = preg_split( '/;(?![^(]*\))/', $inner );
									$kept  = array();
									foreach ( $decls as $d ) {
										$d = trim( $d );
										if ( '' === $d ) {
											continue;
										}
										if ( $layout_override && strpos( strtolower( $d ), 'size' ) !== false ) {
											continue;
										}
										if ( strpos( strtolower( $d ), 'color' ) !== false ) {
											continue;
										}
										$kept[] = $d;
									}
									if ( empty( $kept ) ) {
										return '';
									}
									return '{' . implode( ';', $kept ) . ';}';
								},
								$line
							);
						}

						// If line became empty after replacements, skip it.
						if ( trim( $line ) === '' ) {
							continue;
						}

						$filtered_css .= $line . "\n";
					}

					return $filtered_css;
				};

				$css = $filter_css( $css );
			}

			$all_css[ $slug ] = $css;
		}

		return $all_css;
	}


	/**
	 * Detects and retrieves a list of available theme style variations.
	 *
	 * This method scans the 'styles' directory within the theme for JSON files,
	 * each representing a style variation. It parses the JSON to extract
	 * the variation's slug and title. If a title is not explicitly defined
	 * in the JSON, it defaults to a capitalized version of the slug.
	 *
	 * @return array An array of associative arrays, where each inner array represents
	 *               a style variation and contains 'slug' (string) and 'title' (string) keys.
	 *               Returns an empty array if no variations are found or the 'styles' directory
	 *               does not exist.
	 */
	public function tss_get_theme_variations() {
		$theme_dir  = get_theme_file_path( 'styles' );
		$variations = array(
			array(
				'slug'  => '00-default',
				'title' => __( 'Default', 'dm-tss' ),
			),
		);

		if ( file_exists( $theme_dir ) ) {
			foreach ( glob( $theme_dir . '/*.json' ) as $file ) {
				$slug         = basename( $file, '.json' );
				$json         = json_decode( file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$title        = $json['title'] ?? ucfirst( $slug );
				$variations[] = array(
					'slug'  => $slug,
					'title' => $title,
				);
			}
		}

		return $variations;
	}

	/**
	 * Enqueues scoped CSS styles for the base theme and all style variations.
	 *
	 * This method retrieves the rendered CSS for the base theme and each style variation.
	 * For each variation (including the base, treated as 'base' slug), it scopes the
	 * generated CSS by prefixing selectors with a unique class (e.g., 'is-style-dark').
	 * The scoped CSS is then registered and enqueued as an inline style for each variation.
	 *
	 * @return void
	 */
	public function tss_enqueue_scoped_variation_styles() {
		$all_css = $this->get_rendered_theme_json_css_for_all_variations();

		foreach ( $all_css as $slug => $css ) {
			$scoped_class = 'is-style-' . sanitize_title( $slug );
			$final_css    = $this->scope_theme_json_css( $css, $scoped_class );

			wp_register_style( "tss-style-$slug", false, array(), wp_get_theme()->get( 'Version' ) );
			wp_enqueue_style( "tss-style-$slug" );
			wp_add_inline_style( "tss-style-$slug", $final_css );
		}
	}

	/**
	 * Outputs `@font-face` declarations and font `preload` links for all theme style variations.
	 *
	 * This method scans all theme style variation JSON files for font family definitions
	 * that specify local font files (starting with 'file:'). For each such font,
	 * it generates a `<link rel="preload">` tag to prefetch the font and an
	 * `@font-face` CSS rule. Both are then output directly into the HTML head:
	 * preloads as `<link>` tags and `@font-face` rules within an inline `<style>` block.
	 *
	 * @action wp_head
	 * @return void
	 */
	public function tss_output_variation_fonts() {
		$styles_dir      = get_theme_file_path( 'styles' );
		$variation_files = glob( $styles_dir . '/*.json' );

		$font_faces = array();
		$preloads   = array();

		foreach ( $variation_files as $file_path ) {
			$json = json_decode( file_get_contents( $file_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			if ( ! isset( $json['settings']['typography']['fontFamilies'] ) ) {
						continue;
			}

			foreach ( $json['settings']['typography']['fontFamilies'] as $font ) {
				if ( empty( $font['fontFace'] ) ) {
					continue;
				}

				foreach ( $font['fontFace'] as $font_face ) {
					if ( ! isset( $font_face['src'] ) || ! is_array( $font_face['src'] ) ) {
									continue;
					}

					foreach ( $font_face['src'] as $src ) {
						if ( str_starts_with( $src, 'file:' ) ) {
							$relative_path = str_replace( 'file:', '', $src );
							$font_url      = esc_url( get_theme_file_uri( $relative_path ) );

							$preloads[] = $font_url;

							// Build @font-face rule.
							$font_face_css  = "@font-face {\n";
							$font_face_css .= "  font-family: '{$font_face['fontFamily']}';\n";
							$font_face_css .= "  src: url('{$font_url}') format('woff2');\n";

							if ( isset( $font_face['fontWeight'] ) ) {
								$font_face_css .= "  font-weight: {$font_face['fontWeight']};\n";
							}

							if ( isset( $font_face['fontStyle'] ) ) {
								$font_face_css .= "  font-style: {$font_face['fontStyle']};\n";
							}

							if ( isset( $font_face['fontDisplay'] ) ) {
								$font_face_css .= "  font-display: {$font_face['fontDisplay']};\n";
							} else {
								$font_face_css .= "  font-display: swap;\n";
							}

							$font_face_css .= "}\n";

							$font_faces[] = $font_face_css;
						}
					}
				}
			}
		}

		// Add <link rel="preload"> tags in <head>.
		foreach ( array_unique( $preloads ) as $url ) {
			echo '<link rel="preload" as="font" type="font/woff2" href="' . esc_url( $url ) . '" crossorigin>' . "\n";
		}

		// Add @font-face declarations inline.
		if ( ! empty( $font_faces ) ) {
			echo "<style id=\"tss-font-faces\">\n" . implode( "\n", array_unique( $font_faces ) ) . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}


	/**
	 * Enqueues the style switcher script and localizes it with theme variation data.
	 *
	 * This method registers and enqueues the JavaScript file responsible for
	 * handling style switching. It then localizes the script by passing an object
	 * containing all available theme style variations, which the JavaScript can use
	 * to populate a UI or apply styles dynamically.
	 *
	 * @action wp_enqueue_scripts
	 * @return void
	 */
	public function tss_localize_script() {
		$raw_variations = $this->tss_get_theme_variations();

		$saved_mappings = get_option( 'dm_tss_name_mappings', array() );
		if ( is_string( $saved_mappings ) && '' !== $saved_mappings ) {
			$decoded_saved = json_decode( $saved_mappings, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_saved ) ) {
				$saved_mappings = $decoded_saved;
			} else {
				$saved_mappings = array();
			}
		} elseif ( ! is_array( $saved_mappings ) ) {
			$saved_mappings = array();
		}

		$use_mapped = (bool) get_option( 'dm_tss_use_mapped_names', 0 );

		$variations = array();

		foreach ( $raw_variations as $v ) {
			$slug  = $v['slug'] ?? '';
			$title = $v['title'] ?? ucfirst( $slug );

			if ( $use_mapped && '' !== $slug && isset( $saved_mappings[ $slug ] ) && '' !== $saved_mappings[ $slug ] ) {
				$title = $saved_mappings[ $slug ];
			}

			$variations[] = array(
				'slug'  => $slug,
				'title' => $title,
			);
		}

		wp_register_script(
			'tss-main',
			'',
			array(),
			'v1.0',
			true
		);

		wp_enqueue_script( 'tss-main' );

		wp_add_inline_script(
			'tss-main',
			'window.tss_data = ' . wp_json_encode(
				array(
					'variations'     => $variations,
					'useMappedNames' => $use_mapped ? 1 : 0,
					'isBlockTheme'   => wp_is_block_theme(),
				)
			) . ';',
			'before'
		);
	}
}

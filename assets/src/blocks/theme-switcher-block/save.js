/**
 * Save component for the Theme Style Switcher block.
 *
 * This returns static markup — front-end behavior is provided by the plugin's
 * view scripts (e.g. viewScript / screen.js).
 */
import { useBlockProps } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
	const { display = 'buttons' } = attributes ?? {};

	return (
		<div
			{ ...useBlockProps.save() }
			className="theme-style-switcher-block wp-block-buttons"
			data-tss-block
			data-display={ display }
		></div>
	);
}

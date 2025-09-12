import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';
import { __ } from '@wordpress/i18n';

registerBlockType( 'dm-tss/theme-style-switcher', {
	title: __( 'Theme Style Switcher', 'dm-tss' ),
	icon: 'art',
	category: 'theme-switcher',
	edit: Edit,
	save: Save,
} );

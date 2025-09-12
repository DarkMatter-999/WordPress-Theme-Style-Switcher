import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function Edit() {
	const [ variations, setVariations ] = useState( [] );

	useEffect( () => {
		if ( typeof window !== 'undefined' && window.tss_data?.variations ) {
			setVariations( window.tss_data.variations );
		}
	}, [] );

	return (
		<div className="theme-style-switcher-block">
			<p>
				<strong>
					{ __( 'Theme Style Switcher (Editor Preview)', 'dm-tss' ) }
				</strong>
			</p>

			{ variations.length === 0 && (
				<p>{ __( 'No variations found.', 'dm-tss' ) }</p>
			) }

			{ variations.map( ( v ) => (
				<button
					key={ v.slug }
					onClick={ () => {
						if ( typeof window.setThemeStyle === 'function' ) {
							window.setThemeStyle( v.slug );
						}
					} }
					style={ { marginRight: '10px' } }
				>
					{ v.title }
				</button>
			) ) }
		</div>
	);
}

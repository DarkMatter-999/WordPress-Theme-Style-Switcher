import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export default function Edit() {
	const [ variations, setVariations ] = useState( [] );

	useEffect( () => {
		if ( typeof window !== 'undefined' && window.tss_data?.variations ) {
			setVariations( window.tss_data.variations );
		}
	}, [] );

	return (
		<div
			{ ...useBlockProps( { className: 'theme-style-switcher-block' } ) }
		>
			<div className="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex">
				<div className="wp-block-button" aria-hidden="true">
					<span
						className="wp-block-button__link wp-element-button"
						aria-hidden="true"
						tabIndex={ -1 }
					>
						{ __( 'Default', 'dm-tss' ) }
					</span>
				</div>

				{ variations.map( ( v ) => (
					<div
						className="wp-block-button"
						key={ v.slug }
						aria-hidden="true"
					>
						<span
							className="wp-block-button__link wp-element-button"
							aria-hidden="true"
							tabIndex={ -1 }
						>
							{ v.title }
						</span>
					</div>
				) ) }
			</div>
		</div>
	);
}

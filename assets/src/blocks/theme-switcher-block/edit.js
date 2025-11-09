import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { display = 'buttons' } = attributes ?? {};
	const [ variations, setVariations ] = useState( [] );

	useEffect( () => {
		if ( typeof window !== 'undefined' && window.tss_data?.variations ) {
			setVariations( window.tss_data.variations );
		}
	}, [] );

	const blockProps = useBlockProps( {
		className: 'theme-style-switcher-block',
		'data-display': display,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Display', 'dm-tss' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Style Switcher Type', 'dm-tss' ) }
						value={ display }
						options={ [
							{
								label: __( 'Buttons', 'dm-tss' ),
								value: 'buttons',
							},
							{
								label: __( 'Dropdown', 'dm-tss' ),
								value: 'dropdown',
							},
							{
								label: __( 'Invisible', 'dm-tss' ),
								value: 'invisible',
							},
						] }
						onChange={ ( val ) =>
							setAttributes( { display: val } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ display === 'buttons' && (
					<div
						className="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex"
						style={ { display: 'flex', gap: '10px' } }
					>
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
				) }

				{ display === 'dropdown' && (
					<div
						className="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex"
						aria-hidden="true"
					>
						<select
							disabled
							aria-disabled="true"
							className="wp-block-button wp-block-button__link wp-element-button"
						>
							<option value="default">
								{ __( 'Default', 'dm-tss' ) }
							</option>
							{ variations.map( ( v ) => (
								<option key={ v.slug } value={ v.slug }>
									{ v.title }
								</option>
							) ) }
						</select>
					</div>
				) }

				{ display === 'invisible' && (
					<div
						className="tss-invisible-preview"
						style={ { fontStyle: 'italic' } }
					>
						{ __(
							'Theme switcher invisible on frontend',
							'dm-tss'
						) }
					</div>
				) }
			</div>
		</>
	);
}

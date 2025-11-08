/* global localStorage */

( function () {
	const CLASS_PREFIX = 'is-style-';
	const __ =
		window.wp && window.wp.i18n && window.wp.i18n.__
			? window.wp.i18n.__
			: ( s ) => s;

	const switchStyle = ( slug ) => {
		const root = document.documentElement;
		const all = Array.from( root.classList ).filter( ( cls ) =>
			cls.startsWith( CLASS_PREFIX )
		);

		// Remove all scoped style variation classes
		all.forEach( ( cls ) => root.classList.remove( cls ) );

		if ( slug && slug !== 'default' ) {
			root.classList.add( `${ CLASS_PREFIX }${ slug }` );
			localStorage.setItem( 'theme-style-slug', slug );
		} else {
			localStorage.removeItem( 'theme-style-slug' );
		}
	};

	window.setThemeStyle = switchStyle;

	document.addEventListener( 'DOMContentLoaded', () => {
		const saved = localStorage.getItem( 'theme-style-slug' );
		if ( saved ) {
			switchStyle( saved );
		}

		const container = document.querySelector(
			'.theme-style-switcher-block'
		);
		if ( container && window.tss_data?.variations ) {
			const buttonsWrapper = document.createElement( 'div' );
			buttonsWrapper.classList.add(
				'wp-block-buttons',
				'is-layout-flex',
				'wp-block-buttons-is-layout-flex'
			);
			container.appendChild( buttonsWrapper );

			// Helper function to create a button with the new structure
			const createStyledButton = ( text, slug ) => {
				const buttonBlock = document.createElement( 'div' );
				buttonBlock.classList.add( 'wp-block-button' );

				const buttonLink = document.createElement( 'a' );
				buttonLink.classList.add(
					'wp-block-button__link',
					'wp-element-button'
				);
				buttonLink.innerText = text;
				buttonLink.onclick = () => switchStyle( slug );

				buttonBlock.appendChild( buttonLink );
				return buttonBlock;
			};

			// Default button
			buttonsWrapper.appendChild(
				createStyledButton( __( 'Default', 'dm-tss' ), 'default' )
			);

			// Variation buttons
			window.tss_data.variations.forEach( ( v ) => {
				buttonsWrapper.appendChild(
					createStyledButton( v.title, v.slug )
				);
			} );
		}
	} );
} )();

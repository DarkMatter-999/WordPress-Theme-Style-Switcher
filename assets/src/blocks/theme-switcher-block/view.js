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

		const containers = document.querySelectorAll(
			'.theme-style-switcher-block'
		);

		if ( containers.length && window.tss_data?.variations ) {
			containers.forEach( ( container ) => {
				const display = container.dataset.display || 'buttons';

				if ( display === 'dropdown' ) {
					const select = document.createElement( 'select' );
					select.classList.add(
						'tss-style-select',
						'wp-block-button',
						'wp-block-button__link',
						'wp-element-button'
					);
					select.name = 'theme-style-switcher';

					const defaultOpt = document.createElement( 'option' );
					defaultOpt.value = 'default';
					defaultOpt.innerText = __( 'Default', 'dm-tss' );
					select.appendChild( defaultOpt );

					window.tss_data.variations.forEach( ( v ) => {
						const opt = document.createElement( 'option' );
						opt.value = v.slug;
						opt.innerText = v.title;
						select.appendChild( opt );
					} );

					if ( saved ) {
						select.value = saved;
					}

					select.addEventListener( 'change', ( e ) => {
						switchStyle( e.target.value );
					} );

					container.appendChild( select );
					return;
				}

				const buttonsWrapper = document.createElement( 'div' );
				buttonsWrapper.classList.add(
					'wp-block-buttons',
					'is-layout-flex',
					'wp-block-buttons-is-layout-flex'
				);
				container.appendChild( buttonsWrapper );

				const createStyledButton = ( text, slug ) => {
					const buttonBlock = document.createElement( 'div' );
					buttonBlock.classList.add( 'wp-block-button' );

					const buttonLink = document.createElement( 'a' );
					buttonLink.classList.add(
						'wp-block-button__link',
						'wp-element-button'
					);
					buttonLink.href = '#';
					buttonLink.innerText = text;

					buttonLink.addEventListener( 'click', ( e ) => {
						e.preventDefault();
						switchStyle( slug );
					} );

					if ( saved && saved === slug ) {
						buttonLink.classList.add( 'is-active' );
					}

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
			} );
		}
	} );
} )();

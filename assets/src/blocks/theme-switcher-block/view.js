import { __ } from '@wordpress/i18n';

/* global localStorage */

( function () {
	const CLASS_PREFIX = 'is-style-';

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
		if (
			'undefined' !== typeof window &&
			! window?.tss_data?.isBlockTheme
		) {
			return;
		}

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

				if ( display === 'invisible' ) {
					return;
				}

				if ( display === 'dropdown' ) {
					const selectId = 'theme-style-select-' + Date.now();

					const label = document.createElement( 'label' );
					label.setAttribute( 'for', selectId );
					label.textContent = __( 'Select Theme Style', 'dm-tss' );
					label.classList.add( 'screen-reader-text' );

					const select = document.createElement( 'select' );
					select.id = selectId;
					select.classList.add(
						'tss-style-select',
						'wp-block-button',
						'wp-block-button__link',
						'wp-element-button'
					);
					select.name = 'theme-style-switcher';

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

					container.appendChild( label );
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

				window.tss_data.variations.forEach( ( v ) => {
					buttonsWrapper.appendChild(
						createStyledButton( v.title, v.slug )
					);
				} );
			} );
		}
	} );
} )();

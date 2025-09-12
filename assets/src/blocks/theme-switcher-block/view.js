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
		const saved = localStorage.getItem( 'theme-style-slug' );
		if ( saved ) {
			switchStyle( saved );
		}

		const container = document.querySelector(
			'.theme-style-switcher-block'
		);
		if ( container && window.tss_data?.variations ) {
			// Default button
			const defaultBtn = document.createElement( 'button' );
			defaultBtn.innerText = 'Default';
			defaultBtn.style.marginRight = '10px';
			defaultBtn.onclick = () => switchStyle( 'default' );
			container.appendChild( defaultBtn );

			// Variation buttons
			window.tss_data.variations.forEach( ( v ) => {
				const btn = document.createElement( 'button' );
				btn.innerText = v.title;
				btn.style.marginRight = '10px';
				btn.onclick = () => switchStyle( v.slug );
				container.appendChild( btn );
			} );
		}
	} );
} )();

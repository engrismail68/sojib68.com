/* Gallery on a জমি ও প্রপার্টি advertisement: a click on a thumbnail shows that photo in the big frame. */
( function () {
	'use strict';
	document.querySelectorAll( '[data-prop-gallery]' ).forEach( function ( gallery ) {
		var stage = gallery.querySelector( '[data-prop-stage]' );
		if ( ! stage ) { return; }
		gallery.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '[data-prop-thumb]' );
			if ( ! btn ) { return; }
			stage.removeAttribute( 'srcset' );
			stage.removeAttribute( 'sizes' );
			stage.src = btn.getAttribute( 'data-prop-thumb' );
			gallery.querySelectorAll( '[data-prop-thumb]' ).forEach( function ( b ) { b.classList.toggle( 'is-on', b === btn ); } );
		} );
	} );
} )();

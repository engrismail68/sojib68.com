( function () {
	'use strict';

	var list = document.querySelector( '[data-ck-list]' );
	var sheet = document.querySelector( '[data-ck-sheet]' );
	if ( ! list || ! sheet ) {
		return;
	}

	var boxes = Array.prototype.slice.call( list.querySelectorAll( '[data-ck-item]' ) );
	var countEl = document.querySelector( '[data-ck-count]' );
	var msg = document.querySelector( '[data-ck-msg]' );
	var nameIn = document.querySelector( '[data-ck-name]' );
	var mobileIn = document.querySelector( '[data-ck-mobile]' );
	var noteIn = document.querySelector( '[data-ck-note]' );

	var BN = '০১২৩৪৫৬৭৮৯';
	function bn( n ) {
		return String( n ).replace( /[0-9]/g, function ( d ) { return BN.charAt( +d ); } );
	}

	function selected() {
		return boxes.filter( function ( b ) { return b.checked; } );
	}

	function setText( sel, text ) {
		var el = sheet.querySelector( sel );
		if ( el ) { el.textContent = text; }
	}

	function toggleRow( sel, show ) {
		var el = sheet.querySelector( sel );
		if ( el ) { el.hidden = ! show; }
	}

	function todayBn() {
		var d = new Date();
		try {
			return d.toLocaleDateString( 'bn-BD', { day: 'numeric', month: 'long', year: 'numeric' } );
		} catch ( e ) {
			return bn( d.getDate() + '/' + ( d.getMonth() + 1 ) + '/' + d.getFullYear() );
		}
	}

	// Rebuild the printed sheet from what is ticked.
	function update() {
		var chosen = selected();
		if ( countEl ) {
			countEl.textContent = bn( chosen.length ) + ' / ' + bn( boxes.length ) + ' টি বাছাই করা';
		}
		if ( chosen.length && msg ) {
			msg.hidden = true;
		}

		var ol = sheet.querySelector( '[data-sheet-list]' );
		ol.innerHTML = '';
		var group = null;
		chosen.forEach( function ( b ) {
			var g = b.getAttribute( 'data-group' ) || '';
			if ( g !== group ) {
				group = g;
				if ( g ) {
					var h = document.createElement( 'li' );
					h.className = 'ck-sheet__group';
					h.textContent = g;
					ol.appendChild( h );
				}
			}
			var li = document.createElement( 'li' );
			li.className = 'ck-sheet__item';
			var box = document.createElement( 'span' );
			box.className = 'ck-sheet__box';
			box.setAttribute( 'aria-hidden', 'true' );
			var text = document.createElement( 'span' );
			text.className = 'ck-sheet__text';
			text.textContent = b.getAttribute( 'data-text' );
			li.appendChild( box );
			li.appendChild( text );
			var note = b.getAttribute( 'data-note' );
			if ( note ) {
				var small = document.createElement( 'small' );
				small.textContent = note;
				text.appendChild( small );
			}
			ol.appendChild( li );
		} );

		setText( '[data-sheet-date]', todayBn() );
		var name = nameIn ? nameIn.value.trim() : '';
		var mobile = mobileIn ? mobileIn.value.trim() : '';
		var note = noteIn ? noteIn.value.trim() : '';
		setText( '[data-sheet-name]', name );
		setText( '[data-sheet-mobile]', mobile );
		setText( '[data-sheet-note]', note );
		toggleRow( '[data-sheet-name-row]', !! name );
		toggleRow( '[data-sheet-mobile-row]', !! mobile );
		toggleRow( '[data-sheet-note-row]', !! note );
	}

	list.addEventListener( 'change', update );
	[ nameIn, mobileIn, noteIn ].forEach( function ( el ) {
		if ( el ) { el.addEventListener( 'input', update ); }
	} );

	document.querySelector( '[data-ck-all]' ).addEventListener( 'click', function () {
		boxes.forEach( function ( b ) { b.checked = true; } );
		update();
	} );
	document.querySelector( '[data-ck-none]' ).addEventListener( 'click', function () {
		boxes.forEach( function ( b ) { b.checked = false; } );
		update();
	} );

	document.querySelector( '[data-ck-print]' ).addEventListener( 'click', function () {
		update();
		if ( ! selected().length ) {
			if ( msg ) {
				msg.textContent = 'প্রিন্টের আগে অন্তত একটি কাগজ বাছাই করুন।';
				msg.hidden = false;
			}
			return;
		}
		window.print();
	} );

	// Browser menu / Ctrl+P also prints a fresh sheet.
	window.addEventListener( 'beforeprint', update );

	update();
} )();

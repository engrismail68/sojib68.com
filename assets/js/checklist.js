( function () {
	'use strict';

	var modal = document.querySelector( '[data-ck-modal]' );
	var dataEl = document.getElementById( 'ck-data' );
	if ( ! modal || ! dataEl ) {
		return;
	}
	var DATA;
	try {
		DATA = JSON.parse( dataEl.textContent );
	} catch ( e ) {
		return;
	}

	var dialog = modal.querySelector( '.ck-dialog' );
	var list = modal.querySelector( '[data-ck-list]' );
	var sheet = modal.querySelector( '[data-ck-sheet]' );
	var titleEl = modal.querySelector( '[data-ck-title]' );
	var countEl = modal.querySelector( '[data-ck-count]' );
	var msg = modal.querySelector( '[data-ck-msg]' );
	var nameIn = modal.querySelector( '[data-ck-name]' );
	var mobileIn = modal.querySelector( '[data-ck-mobile]' );
	var noteIn = modal.querySelector( '[data-ck-note]' );
	var boxes = [];
	var typesEl = modal.querySelector( '[data-ck-types]' );
	var checked = {}; // item index => ticked, kept while the deed type changes
	var picked = {}; // question => the answer picked
	var lastOpener = null;

	var BN = '০১২৩৪৫৬৭৮৯';
	function bn( n ) {
		return String( n ).replace( /[0-9]/g, function ( d ) { return BN.charAt( +d ); } );
	}

	function selected() {
		return boxes.filter( function ( b ) { return b.checked; } );
	}

	// Items belonging to one section heading.
	function groupBoxes( name ) {
		return boxes.filter( function ( b ) { return ( b.getAttribute( 'data-group' ) || '' ) === name; } );
	}

	function updateGroupButtons() {
		list.querySelectorAll( '[data-ck-group]' ).forEach( function ( li ) {
			var btn = li.querySelector( '[data-ck-group-toggle]' );
			var inGroup = groupBoxes( li.getAttribute( 'data-ck-group' ) );
			var all = inGroup.length > 0 && inGroup.every( function ( b ) { return b.checked; } );
			if ( btn ) { btn.textContent = all ? 'এই অংশের সব বাদ' : 'এই অংশের সব বাছাই'; }
		} );
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
		boxes.forEach( function ( b ) {
			var idx = b.getAttribute( 'data-idx' );
			if ( b.checked ) { checked[ idx ] = 1; } else { delete checked[ idx ]; }
		} );
		var chosen = selected();
		if ( countEl ) {
			countEl.textContent = bn( chosen.length ) + ' / ' + bn( boxes.length ) + ' টি বাছাই করা';
		}
		if ( chosen.length && msg ) {
			msg.hidden = true;
		}
		updateGroupButtons();

		sheet.classList.toggle( 'ck-sheet--dense', chosen.length > 12 ); // long lists print a little smaller to stay within the half sheet
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

	list.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '[data-ck-group-toggle]' );
		if ( ! btn ) {
			return;
		}
		var inGroup = groupBoxes( btn.closest( '[data-ck-group]' ).getAttribute( 'data-ck-group' ) );
		var all = inGroup.every( function ( b ) { return b.checked; } );
		inGroup.forEach( function ( b ) { b.checked = ! all; } );
		update();
	} );
	[ nameIn, mobileIn, noteIn ].forEach( function ( el ) {
		if ( el ) { el.addEventListener( 'input', update ); }
	} );

	modal.querySelector( '[data-ck-all]' ).addEventListener( 'click', function () {
		boxes.forEach( function ( b ) { b.checked = true; } );
		update();
	} );
	modal.querySelector( '[data-ck-none]' ).addEventListener( 'click', function () {
		boxes.forEach( function ( b ) { b.checked = false; } );
		update();
	} );

	modal.querySelector( '[data-ck-print]' ).addEventListener( 'click', function () {
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

	// ----- popup -----

	var current = '';

	function buildList( key ) {
		var items = DATA[ key ].items;
		list.innerHTML = '';
		boxes = [];
		var group = null;
		items.forEach( function ( item, i ) {
			if ( item.type && picked[ item.kind || 'দলিলের ধরন' ] !== item.type ) {
				return; // belongs to an answer that is not picked
			}
			var g = item.group || item.type || '';
			if ( g !== group ) {
				group = g;
				if ( g ) {
					var gh = document.createElement( 'li' );
					gh.className = 'ck-group';
					gh.setAttribute( 'data-ck-group', g );
					var span = document.createElement( 'span' );
					span.textContent = g;
					var tg = document.createElement( 'button' );
					tg.type = 'button';
					tg.className = 'ck-link';
					tg.setAttribute( 'data-ck-group-toggle', '' );
					tg.textContent = 'এই অংশের সব বাছাই';
					gh.appendChild( span );
					gh.appendChild( tg );
					list.appendChild( gh );
				}
			}
			var li = document.createElement( 'li' );
			li.className = 'ck-item';
			var label = document.createElement( 'label' );
			var input = document.createElement( 'input' );
			input.type = 'checkbox';
			input.id = 'ck-item-' + i;
			input.setAttribute( 'data-idx', i );
			input.checked = !! checked[ i ];
			input.setAttribute( 'data-ck-item', '' );
			input.setAttribute( 'data-text', item.text );
			input.setAttribute( 'data-note', item.note || '' );
			input.setAttribute( 'data-group', g );
			var box = document.createElement( 'span' );
			box.className = 'ck-item__box';
			box.setAttribute( 'aria-hidden', 'true' );
			var text = document.createElement( 'span' );
			text.className = 'ck-item__text';
			text.appendChild( document.createTextNode( item.text ) );
			if ( item.note ) {
				var small = document.createElement( 'small' );
				small.textContent = item.note;
				text.appendChild( small );
			}
			label.appendChild( input );
			label.appendChild( box );
			label.appendChild( text );
			li.appendChild( label );
			list.appendChild( li );
			boxes.push( input );
		} );
	}

	// Questions ("kinds") in the order they first appear, each with its answers.
	function kindsOf( key ) {
		var kinds = [];
		DATA[ key ].items.forEach( function ( item ) {
			if ( ! item.type ) { return; }
			var kind = item.kind || 'দলিলের ধরন';
			var k = kinds.filter( function ( x ) { return x.name === kind; } )[ 0 ];
			if ( ! k ) {
				k = { name: kind, options: [] };
				kinds.push( k );
			}
			if ( k.options.indexOf( item.type ) < 0 ) { k.options.push( item.type ); }
		} );
		return kinds;
	}

	function buildTypes( key ) {
		var kinds = kindsOf( key );
		typesEl.innerHTML = '';
		typesEl.hidden = ! kinds.length;
		if ( ! kinds.length ) { return; }
		kinds.forEach( function ( k ) {
			var wrap = document.createElement( 'div' );
			wrap.className = 'ck-types__group';
			var lab = document.createElement( 'p' );
			lab.className = 'ck-types__label';
			lab.textContent = k.name + ' বাছাই করুন';
			wrap.appendChild( lab );
			var row = document.createElement( 'div' );
			row.className = 'ck-types__row';
			k.options.forEach( function ( name ) {
				var b = document.createElement( 'button' );
				b.type = 'button';
				b.className = 'ck-type';
				b.setAttribute( 'data-ck-kind', k.name );
				b.setAttribute( 'data-ck-type', name );
				b.setAttribute( 'aria-pressed', 'false' );
				b.textContent = name;
				row.appendChild( b );
			} );
			wrap.appendChild( row );
			typesEl.appendChild( wrap );
		} );
	}

	function refreshTypes() {
		var names = [];
		typesEl.querySelectorAll( '[data-ck-type]' ).forEach( function ( b ) {
			var on = picked[ b.getAttribute( 'data-ck-kind' ) ] === b.getAttribute( 'data-ck-type' );
			b.classList.toggle( 'is-on', on );
			b.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			if ( on ) { names.push( b.getAttribute( 'data-ck-type' ) ); }
		} );
		setText( '[data-sheet-title]', DATA[ current ].title + ( names.length ? ' (' + names.join( ' · ' ) + ')' : '' ) );
	}

	typesEl.addEventListener( 'click', function ( e ) {
		var b = e.target.closest( '[data-ck-type]' );
		if ( ! b ) { return; }
		update(); // keep what is ticked so far
		var kind = b.getAttribute( 'data-ck-kind' );
		var name = b.getAttribute( 'data-ck-type' );
		if ( picked[ kind ] === name ) { delete picked[ kind ]; } else { picked[ kind ] = name; }
		buildList( current );
		refreshTypes();
		update();
	} );

	function openModal( key, opener ) {
		if ( ! DATA[ key ] ) {
			return;
		}
		current = key;
		lastOpener = opener || null;
		titleEl.textContent = DATA[ key ].title;
		checked = {};
		picked = {};
		buildTypes( key );
		buildList( key );
		refreshTypes();
		nameIn.value = '';
		mobileIn.value = '';
		noteIn.value = '';
		if ( msg ) { msg.hidden = true; }
		modal.hidden = false;
		document.body.classList.add( 'ck-open' );
		list.parentNode.scrollTop = 0;
		update();
		dialog.focus();
	}

	function closeModal() {
		modal.hidden = true;
		document.body.classList.remove( 'ck-open' );
		if ( lastOpener && lastOpener.focus ) { lastOpener.focus(); }
	}

	document.addEventListener( 'click', function ( e ) {
		var opener = e.target.closest( '[data-ck-open]' );
		if ( opener ) {
			e.preventDefault();
			openModal( opener.getAttribute( 'data-ck-open' ), opener );
			return;
		}
		if ( ! modal.hidden && e.target.closest( '[data-ck-close]' ) ) {
			closeModal();
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( ! modal.hidden && ( e.key === 'Escape' || e.key === 'Esc' ) ) {
			closeModal();
		}
	} );
} )();

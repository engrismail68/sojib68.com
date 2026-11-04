( function () {
	'use strict';

	var modal = document.querySelector( '[data-lc-modal]' );
	var dataEl = document.getElementById( 'lc-data' );
	if ( ! modal || ! dataEl ) {
		return;
	}
	var UNITS;
	try {
		UNITS = JSON.parse( dataEl.textContent );
	} catch ( e ) {
		return;
	}

	var dialog = modal.querySelector( '.lc-dialog' );
	var input = modal.querySelector( '[data-lc-value]' );
	var select = modal.querySelector( '[data-lc-unit]' );
	var results = modal.querySelector( '[data-lc-results]' );
	var msg = modal.querySelector( '[data-lc-msg]' );
	var lastOpener = null;
	var keys = Object.keys( UNITS );

	var BN = '০১২৩৪৫৬৭৮৯';

	// accept Bangla digits and commas as typed
	function parseNumber( raw ) {
		var s = String( raw ).replace( /[০-৯]/g, function ( d ) { return BN.indexOf( d ); } )
			.replace( /[,\s]/g, '' ).replace( /[।]/g, '.' );
		if ( s === '' ) { return null; }
		if ( ! /^\d*\.?\d+$|^\d+\.$/.test( s ) ) { return NaN; }
		return parseFloat( s );
	}

	function format( n ) {
		if ( n >= 1000000 ) { return n.toLocaleString( 'en-US', { maximumFractionDigits: 0 } ); }
		var s = n.toLocaleString( 'en-US', { maximumFractionDigits: 4 } );
		return s === '0' && n > 0 ? '< 0.0001' : s;
	}

	keys.forEach( function ( k ) {
		var opt = document.createElement( 'option' );
		opt.value = k;
		opt.textContent = UNITS[ k ].label;
		select.appendChild( opt );
	} );
	select.value = keys[ 0 ];

	function renderList( ul, base, fromKey ) {
		ul.innerHTML = '';
		keys.forEach( function ( k ) {
			var li = document.createElement( 'li' );
			li.className = 'lc-result' + ( k === fromKey ? ' is-from' : '' );
			var name = document.createElement( 'span' );
			name.className = 'lc-result__name';
			name.textContent = UNITS[ k ].label;
			var val = document.createElement( 'strong' );
			val.className = 'lc-result__val';
			val.textContent = format( base / UNITS[ k ].sqft );
			li.appendChild( name );
			li.appendChild( val );
			ul.appendChild( li );
		} );
	}

	function render() {
		var v = parseNumber( input.value );
		results.innerHTML = '';
		if ( v === null ) {
			msg.hidden = true;
			return;
		}
		if ( isNaN( v ) ) {
			msg.textContent = 'শুধু সংখ্যা লিখুন, যেমন 5 বা 5.5';
			msg.hidden = false;
			return;
		}
		msg.hidden = true;
		renderList( results, v * UNITS[ select.value ].sqft, select.value );
	}

	// ----- area from the sides -----

	var SQFT_PER_SQM = 10.7639104;
	var SHAPES = {
		tri: {
			label: 'ত্রিভুজ (৩ বাহু)',
			sides: [ 'AB', 'BC', 'CA' ],
			svg: '<svg viewBox="0 0 160 120"><polygon points="20,100 140,100 70,18" fill="#E8F3EA" stroke="#198030" stroke-width="2"/><text x="8" y="112">A</text><text x="144" y="112">B</text><text x="66" y="12">C</text></svg>'
		},
		quad: {
			label: 'চতুর্ভুজ (৪ বাহু + ১ কর্ণ)',
			sides: [ 'AB', 'BC', 'CD', 'DA', 'AC (কর্ণ)' ],
			svg: '<svg viewBox="0 0 160 120"><polygon points="18,98 142,92 126,20 30,30" fill="#E8F3EA" stroke="#198030" stroke-width="2"/><line x1="18" y1="98" x2="126" y2="20" stroke="#198030" stroke-width="1.6" stroke-dasharray="5 4"/><text x="6" y="110">A</text><text x="144" y="104">B</text><text x="128" y="16">C</text><text x="18" y="24">D</text></svg>'
		}
	};

	var shapesEl = modal.querySelector( '[data-lc-shapes]' );
	var sidesEl = modal.querySelector( '[data-lc-sides]' );
	var diagramEl = modal.querySelector( '[data-lc-diagram]' );
	var lunit = modal.querySelector( '[data-lc-lunit]' );
	var amsg = modal.querySelector( '[data-lc-amsg]' );
	var aresults = modal.querySelector( '[data-lc-aresults]' );
	var quadNote = modal.querySelector( '[data-lc-quadnote]' );
	var shape = 'tri';

	Object.keys( SHAPES ).forEach( function ( k ) {
		var b = document.createElement( 'button' );
		b.type = 'button';
		b.className = 'lc-chip';
		b.setAttribute( 'data-lc-shape', k );
		b.textContent = SHAPES[ k ].label;
		shapesEl.appendChild( b );
	} );

	function buildSides() {
		var def = SHAPES[ shape ];
		sidesEl.innerHTML = '';
		def.sides.forEach( function ( name, i ) {
			var wrap = document.createElement( 'div' );
			wrap.className = 'lc-field lc-field--side';
			var label = document.createElement( 'label' );
			label.setAttribute( 'for', 'lc-side-' + i );
			label.textContent = name;
			var inp = document.createElement( 'input' );
			inp.type = 'text';
			inp.id = 'lc-side-' + i;
			inp.setAttribute( 'inputmode', 'decimal' );
			inp.setAttribute( 'autocomplete', 'off' );
			inp.setAttribute( 'data-lc-side', '' );
			wrap.appendChild( label );
			wrap.appendChild( inp );
			sidesEl.appendChild( wrap );
		} );
		diagramEl.innerHTML = def.svg;
		quadNote.hidden = shape !== 'quad';
		shapesEl.querySelectorAll( '[data-lc-shape]' ).forEach( function ( b ) {
			var on = b.getAttribute( 'data-lc-shape' ) === shape;
			b.classList.toggle( 'is-on', on );
			b.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		} );
		renderArea();
	}

	// Heron's formula; null when the three lengths cannot make a triangle.
	function triArea( a, b, c ) {
		if ( a + b <= c || a + c <= b || b + c <= a ) { return null; }
		var s = ( a + b + c ) / 2;
		return Math.sqrt( s * ( s - a ) * ( s - b ) * ( s - c ) );
	}

	function renderArea() {
		var fields = Array.prototype.slice.call( sidesEl.querySelectorAll( '[data-lc-side]' ) );
		aresults.innerHTML = '';
		var vals = fields.map( function ( f ) { return parseNumber( f.value ); } );
		if ( vals.some( function ( v ) { return v === null; } ) ) {
			amsg.hidden = true; // still being filled in
			return;
		}
		if ( vals.some( function ( v ) { return isNaN( v ) || v <= 0; } ) ) {
			amsg.textContent = 'প্রতিটি ঘরে শূন্যের চেয়ে বড় একটি সংখ্যা লিখুন, যেমন 40 বা 32.5';
			amsg.hidden = false;
			return;
		}
		var area;
		if ( shape === 'tri' ) {
			area = triArea( vals[ 0 ], vals[ 1 ], vals[ 2 ] );
			if ( area === null ) {
				amsg.textContent = 'এই তিন মাপে ত্রিভুজ হয় না। যেকোনো দুই বাহুর যোগফল তৃতীয় বাহুর চেয়ে বড় হতে হবে। মাপগুলো আবার দেখুন।';
				amsg.hidden = false;
				return;
			}
		} else {
			// AB, BC, CD, DA and the diagonal AC: split into ABC and ACD
			var t1 = triArea( vals[ 0 ], vals[ 1 ], vals[ 4 ] );
			var t2 = triArea( vals[ 2 ], vals[ 3 ], vals[ 4 ] );
			if ( t1 === null || t2 === null ) {
				amsg.textContent = ( t1 === null ? 'AB, BC ও কর্ণ AC' : 'CD, DA ও কর্ণ AC' ) + ' মিলে ত্রিভুজ হয় না। কর্ণ এত ছোট বা বড় হতে পারে না; মাপগুলো আবার দেখুন।';
				amsg.hidden = false;
				return;
			}
			area = t1 + t2;
		}
		amsg.hidden = true;
		var sqft = lunit.value === 'm' ? area * SQFT_PER_SQM : area;
		renderList( aresults, sqft, 'sqft' );
	}

	// ----- tabs -----

	function showTab( name ) {
		modal.querySelectorAll( '[data-lc-tab]' ).forEach( function ( b ) {
			var on = b.getAttribute( 'data-lc-tab' ) === name;
			b.classList.toggle( 'is-on', on );
			b.setAttribute( 'aria-selected', on ? 'true' : 'false' );
		} );
		modal.querySelectorAll( '[data-lc-panel]' ).forEach( function ( p ) {
			p.hidden = p.getAttribute( 'data-lc-panel' ) !== name;
		} );
	}

	function open( opener ) {
		lastOpener = opener || null;
		modal.hidden = false;
		document.body.classList.add( 'lc-open' );
		showTab( 'convert' );
		render();
		input.focus();
	}

	function close() {
		modal.hidden = true;
		document.body.classList.remove( 'lc-open' );
		if ( lastOpener && lastOpener.focus ) { lastOpener.focus(); }
	}

	input.addEventListener( 'input', render );
	select.addEventListener( 'change', render );
	sidesEl.addEventListener( 'input', renderArea );
	lunit.addEventListener( 'change', renderArea );
	buildSides();

	document.addEventListener( 'click', function ( e ) {
		var opener = e.target.closest( '[data-lc-open]' );
		if ( opener ) {
			e.preventDefault();
			open( opener );
			return;
		}
		var tab = e.target.closest( '[data-lc-tab]' );
		if ( tab ) {
			showTab( tab.getAttribute( 'data-lc-tab' ) );
			return;
		}
		var sh = e.target.closest( '[data-lc-shape]' );
		if ( sh ) {
			shape = sh.getAttribute( 'data-lc-shape' );
			buildSides();
			return;
		}
		if ( ! modal.hidden && e.target.closest( '[data-lc-close]' ) ) {
			close();
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( ! modal.hidden && ( e.key === 'Escape' || e.key === 'Esc' ) ) {
			close();
		}
	} );
} )();

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
	var printBtn = modal.querySelector( '[data-lc-print]' );
	var sheet = modal.querySelector( '[data-lc-sheet]' );
	var calc = { convert: null, area: null }; // the last good result of each tab
	var tab = 'convert';
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

	function updatePrint() {
		if ( printBtn ) { printBtn.disabled = ! calc[ tab ]; }
	}

	function render() {
		var v = parseNumber( input.value );
		results.innerHTML = '';
		calc.convert = null;
		updatePrint();
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
		calc.convert = { value: v, unit: select.value };
		updatePrint();
	}

	// ----- area from the sides -----

	var SQFT_PER_SQM = 10.7639104;
	var SHAPES = {
		rect: {
			label: 'আয়তক্ষেত্র',
			hint: 'সোজা আয়তাকার জমি, চার কোণাই সমকোণ।',
			sides: [
				{ label: 'দৈর্ঘ্য', hint: 'লম্বা দিকের মাপ' },
				{ label: 'প্রস্থ', hint: 'ছোট দিকের মাপ' }
			],
			svg: '<svg viewBox="0 0 160 120"><rect x="10" y="26" width="140" height="68" fill="#E8F3EA" stroke="#198030" stroke-width="2"/><text x="52" y="18">দৈর্ঘ্য (লম্বা)</text><text x="52" y="64">জমি</text></svg>'
		},
		sq: {
			label: 'বর্গক্ষেত্র',
			hint: 'চার দিকের মাপ সমান, চার কোণাই সমকোণ।',
			sides: [
				{ label: 'এক দিকের মাপ', hint: 'চার দিকের যেকোনো একটি' }
			],
			svg: '<svg viewBox="0 0 160 120"><rect x="36" y="14" width="88" height="88" fill="#E8F3EA" stroke="#198030" stroke-width="2"/><text x="64" y="64">জমি</text></svg>'
		},
		tri: {
			label: 'ত্রিভুজ (৩ কোণা)',
			hint: 'ছবির ক, খ, গ কোণা। এক কোণা থেকে পরের কোণা পর্যন্ত মাপ দিন।',
			sides: [
				{ label: 'ক – খ', hint: 'ক কোণা থেকে খ কোণা পর্যন্ত' },
				{ label: 'খ – গ', hint: 'খ কোণা থেকে গ কোণা পর্যন্ত' },
				{ label: 'গ – ক', hint: 'গ কোণা থেকে ক কোণা পর্যন্ত' }
			],
			svg: '<svg viewBox="0 0 160 120"><polygon points="24,98 138,98 76,18" fill="#E8F3EA" stroke="#198030" stroke-width="2"/><text x="8" y="110">ক</text><text x="142" y="110">খ</text><text x="72" y="12">গ</text></svg>'
		},
		quad: {
			label: 'চতুর্ভুজ (৪ কোণা)',
			hint: 'ছবির ক, খ, গ, ঘ কোণা। এক কোণা থেকে পরের কোণা পর্যন্ত মাপ দিন, আর ক থেকে গ পর্যন্ত কোণাকুণি মাপটিও (বিন্দু-বিন্দু দাগ) দিন।',
			sides: [
				{ label: 'ক – খ', hint: 'ক কোণা থেকে খ কোণা পর্যন্ত' },
				{ label: 'খ – গ', hint: 'খ কোণা থেকে গ কোণা পর্যন্ত' },
				{ label: 'গ – ঘ', hint: 'গ কোণা থেকে ঘ কোণা পর্যন্ত' },
				{ label: 'ঘ – ক', hint: 'ঘ কোণা থেকে ক কোণা পর্যন্ত' },
				{ label: 'কোণাকুণি মাপ (ক – গ)', hint: 'ক থেকে গ-এ জমির ভেতর দিয়ে সোজা মাপ' }
			],
			svg: '<svg viewBox="0 0 160 120"><polygon points="22,98 138,92 124,20 34,30" fill="#E8F3EA" stroke="#198030" stroke-width="2"/><line x1="22" y1="98" x2="124" y2="20" stroke="#198030" stroke-width="1.6" stroke-dasharray="5 4"/><text x="8" y="110">ক</text><text x="142" y="104">খ</text><text x="126" y="16">গ</text><text x="20" y="26">ঘ</text></svg>'
		}
	};

	var shapesEl = modal.querySelector( '[data-lc-shapes]' );
	var sidesEl = modal.querySelector( '[data-lc-sides]' );
	var diagramEl = modal.querySelector( '[data-lc-diagram]' );
	var lunit = modal.querySelector( '[data-lc-lunit]' );
	var amsg = modal.querySelector( '[data-lc-amsg]' );
	var aresults = modal.querySelector( '[data-lc-aresults]' );
	var quadNote = modal.querySelector( '[data-lc-quadnote]' );
	var shapeHint = modal.querySelector( '[data-lc-shapehint]' );
	var shape = 'rect';

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
		def.sides.forEach( function ( side, i ) {
			var wrap = document.createElement( 'div' );
			wrap.className = 'lc-field lc-field--side';
			var label = document.createElement( 'label' );
			label.setAttribute( 'for', 'lc-side-' + i );
			label.textContent = side.label;
			var hint = document.createElement( 'small' );
			hint.textContent = side.hint;
			var inp = document.createElement( 'input' );
			inp.type = 'text';
			inp.id = 'lc-side-' + i;
			inp.setAttribute( 'inputmode', 'decimal' );
			inp.setAttribute( 'autocomplete', 'off' );
			inp.setAttribute( 'data-lc-side', '' );
			wrap.appendChild( label );
			wrap.appendChild( hint );
			wrap.appendChild( inp );
			sidesEl.appendChild( wrap );
		} );
		diagramEl.innerHTML = def.svg;
		shapeHint.textContent = def.hint;
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
		calc.area = null;
		updatePrint();
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
		if ( shape === 'rect' ) {
			area = vals[ 0 ] * vals[ 1 ];
		} else if ( shape === 'sq' ) {
			area = vals[ 0 ] * vals[ 0 ];
		} else if ( shape === 'tri' ) {
			area = triArea( vals[ 0 ], vals[ 1 ], vals[ 2 ] );
			if ( area === null ) {
				amsg.textContent = 'এই তিন মাপে ত্রিভুজ হয় না। যেকোনো দুই দিকের মাপের যোগফল তৃতীয় দিকের চেয়ে বড় হতে হবে। মাপগুলো আবার দেখুন।';
				amsg.hidden = false;
				return;
			}
		} else {
			// AB, BC, CD, DA and the diagonal AC: split into ABC and ACD
			var t1 = triArea( vals[ 0 ], vals[ 1 ], vals[ 4 ] );
			var t2 = triArea( vals[ 2 ], vals[ 3 ], vals[ 4 ] );
			if ( t1 === null || t2 === null ) {
				amsg.textContent = ( t1 === null ? 'ক–খ, খ–গ ও কোণাকুণি মাপ' : 'গ–ঘ, ঘ–ক ও কোণাকুণি মাপ' ) + ' মিলে হয় না। কোণাকুণি মাপ এত ছোট বা বড় হতে পারে না; মাপগুলো আবার দেখুন।';
				amsg.hidden = false;
				return;
			}
			area = t1 + t2;
		}
		amsg.hidden = true;
		var sqft = lunit.value === 'm' ? area * SQFT_PER_SQM : area;
		renderList( aresults, sqft, 'sqft' );
		calc.area = {
			shape: SHAPES[ shape ].label,
			unit: lunit.value === 'm' ? 'মিটার' : 'ফুট',
			sides: SHAPES[ shape ].sides.map( function ( sd, i ) { return { label: sd.label, value: vals[ i ] }; } ),
			sqft: sqft,
			svg: SHAPES[ shape ].svg
		};
		updatePrint();
	}

	// ----- printed slip -----

	function todayBn() {
		var d = new Date();
		try {
			return d.toLocaleDateString( 'bn-BD', { day: 'numeric', month: 'long', year: 'numeric' } );
		} catch ( e ) {
			return d.getDate() + '/' + ( d.getMonth() + 1 ) + '/' + d.getFullYear();
		}
	}

	function setSheetText( sel, text ) {
		var el = sheet.querySelector( sel );
		if ( el ) { el.textContent = text; }
	}

	function addLi( ul, label, value ) {
		var li = document.createElement( 'li' );
		var b = document.createElement( 'b' );
		b.textContent = label + ': ';
		li.appendChild( b );
		li.appendChild( document.createTextNode( value ) );
		ul.appendChild( li );
	}

	function buildSheet() {
		var data = calc[ tab ];
		if ( ! data ) { return false; }
		var inputs = sheet.querySelector( '[data-sheet-inputs]' );
		var rows = sheet.querySelector( '[data-sheet-results]' );
		var diagram = sheet.querySelector( '[data-sheet-diagram]' );
		inputs.innerHTML = '';
		rows.innerHTML = '';
		var base;
		var fromKey;
		if ( tab === 'convert' ) {
			setSheetText( '[data-sheet-title]', 'জমির এককের হিসাব' );
			addLi( inputs, 'দেওয়া পরিমাণ', format( data.value ) + ' ' + UNITS[ data.unit ].label );
			diagram.innerHTML = '';
			base = data.value * UNITS[ data.unit ].sqft;
			fromKey = data.unit;
		} else {
			setSheetText( '[data-sheet-title]', 'জমির ক্ষেত্রফলের হিসাব' );
			addLi( inputs, 'জমির আকৃতি', data.shape );
			data.sides.forEach( function ( sd ) {
				addLi( inputs, sd.label, format( sd.value ) + ' ' + data.unit );
			} );
			diagram.innerHTML = data.svg;
			base = data.sqft;
			fromKey = 'sqft';
		}
		setSheetText( '[data-sheet-date]', todayBn() );
		keys.forEach( function ( k ) {
			var tr = document.createElement( 'tr' );
			if ( k === fromKey ) { tr.className = 'is-from'; }
			var th = document.createElement( 'th' );
			th.textContent = UNITS[ k ].label;
			var td = document.createElement( 'td' );
			td.textContent = format( base / UNITS[ k ].sqft );
			tr.appendChild( th );
			tr.appendChild( td );
			rows.appendChild( tr );
		} );
		return true;
	}

	if ( printBtn ) {
		printBtn.addEventListener( 'click', function () {
			if ( buildSheet() ) { window.print(); }
		} );
	}
	window.addEventListener( 'beforeprint', function () { if ( ! modal.hidden ) { buildSheet(); } } );

	// ----- tabs -----

	function showTab( name ) {
		tab = name;
		updatePrint();
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

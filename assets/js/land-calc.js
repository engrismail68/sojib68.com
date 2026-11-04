( function () {
	'use strict';

	var modal = document.querySelector( '[data-lc-modal]' );
	var dataEl = document.getElementById( 'lc-data' );
	if ( ! modal || ! dataEl ) {
		return;
	}
	var UNITS;
	var OLD;
	try {
		UNITS = JSON.parse( dataEl.textContent );
		OLD = JSON.parse( document.getElementById( 'lc-old' ).textContent );
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
	var calc = { convert: null, area: null, old: null }; // the last good result of each tab
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

	function renderList( ul, base, fromKey, extra ) {
		ul.innerHTML = '';
		if ( extra ) {
			extra.forEach( function ( row ) {
				var li = document.createElement( 'li' );
				li.className = 'lc-result is-from';
				var n = document.createElement( 'span' );
				n.className = 'lc-result__name';
				n.textContent = row[ 0 ];
				var v = document.createElement( 'strong' );
				v.className = 'lc-result__val';
				v.textContent = row[ 1 ];
				li.appendChild( n );
				li.appendChild( v );
				ul.appendChild( li );
			} );
		}
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

	// ----- old measures (আনা / গণ্ডা / কড়া / ক্রান্তি) -----

	var OKEYS = Object.keys( OLD ); // anna, ganda, kora, kranti — largest first
	var SQFT_PER_SHOTANGSHO = UNITS.shotangsho ? UNITS.shotangsho.sqft : 435.6;
	var OMODES = {
		fixed: {
			label: 'আনা-গণ্ডা → শতাংশ',
			hint: 'যত আনা, গণ্ডা, কড়া, ক্রান্তি আছে লিখুন (যেটা নেই সেটা ফাঁকা রাখুন)। আপনার এলাকার মাপে শতাংশ হবে।',
			fields: OKEYS.map( function ( k ) { return { key: k, label: OLD[ k ].label, hint: '' }; } )
		},
		share: {
			label: 'মোট জমির অংশ → শতাংশ',
			hint: 'মোট জমি কত শতাংশ এবং তার কত অংশ (১৬ আনায় পুরো জমি) লিখুন।',
			fields: [ { key: 'total', label: 'মোট জমি (শতাংশ)', hint: 'পুরো জমির পরিমাণ' } ].concat(
				OKEYS.map( function ( k ) { return { key: k, label: OLD[ k ].label, hint: 'অংশ' }; } )
			)
		},
		back: {
			label: 'শতাংশ → আনা-গণ্ডা',
			hint: 'শতাংশে জমির পরিমাণ লিখুন, আনা-গণ্ডা-কড়া-ক্রান্তিতে কত হয় দেখুন।',
			fields: [ { key: 'sh', label: 'জমি (শতাংশ)', hint: '' } ]
		}
	};
	var omode = 'fixed';
	var omodesEl = modal.querySelector( '[data-lc-omodes]' );
	var omodeHint = modal.querySelector( '[data-lc-omodehint]' );
	var ofields = modal.querySelector( '[data-lc-ofields]' );
	var omsg = modal.querySelector( '[data-lc-omsg]' );
	var oresults = modal.querySelector( '[data-lc-oresults]' );
	var otable = modal.querySelector( '[data-lc-otable]' );

	otable.textContent = 'আপনার এলাকার মাপ: ' + OKEYS.map( function ( k ) {
		return '১ ' + OLD[ k ].label + ' = ' + format( OLD[ k ].shatangsho ) + ' শতাংশ';
	} ).join( '; ' ) + '। ১ আনা = ২০ গণ্ডা, ১ গণ্ডা = ৪ কড়া, ১ কড়া = ৩ ক্রান্তি; ১৬ আনায় পুরো।';

	Object.keys( OMODES ).forEach( function ( k ) {
		var b = document.createElement( 'button' );
		b.type = 'button';
		b.className = 'lc-chip';
		b.setAttribute( 'data-lc-omode', k );
		b.textContent = OMODES[ k ].label;
		omodesEl.appendChild( b );
	} );

	function buildOld() {
		var def = OMODES[ omode ];
		ofields.innerHTML = '';
		def.fields.forEach( function ( f, i ) {
			var wrap = document.createElement( 'div' );
			wrap.className = 'lc-field lc-field--side';
			var label = document.createElement( 'label' );
			label.setAttribute( 'for', 'lc-o-' + i );
			label.textContent = f.label;
			wrap.appendChild( label );
			if ( f.hint ) {
				var hint = document.createElement( 'small' );
				hint.textContent = f.hint;
				wrap.appendChild( hint );
			}
			var inp = document.createElement( 'input' );
			inp.type = 'text';
			inp.id = 'lc-o-' + i;
			inp.setAttribute( 'inputmode', 'decimal' );
			inp.setAttribute( 'autocomplete', 'off' );
			inp.setAttribute( 'data-lc-ofield', f.key );
			wrap.appendChild( inp );
			ofields.appendChild( wrap );
		} );
		omodeHint.textContent = def.hint;
		omodesEl.querySelectorAll( '[data-lc-omode]' ).forEach( function ( b ) {
			var on = b.getAttribute( 'data-lc-omode' ) === omode;
			b.classList.toggle( 'is-on', on );
			b.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		} );
		renderOld();
	}

	function renderOld() {
		oresults.innerHTML = '';
		calc.old = null;
		updatePrint();
		var vals = {};
		var any = false;
		var bad = false;
		var empties = 0;
		ofields.querySelectorAll( '[data-lc-ofield]' ).forEach( function ( f ) {
			var v = parseNumber( f.value );
			if ( v === null ) {
				empties++;
				vals[ f.getAttribute( 'data-lc-ofield' ) ] = 0;
				return;
			}
			if ( isNaN( v ) || v < 0 ) { bad = true; return; }
			any = any || v > 0;
			vals[ f.getAttribute( 'data-lc-ofield' ) ] = v;
		} );
		if ( bad ) {
			omsg.textContent = 'শুধু সংখ্যা লিখুন, যেমন 2 বা 1.5';
			omsg.hidden = false;
			return;
		}
		if ( ! any ) {
			omsg.hidden = true;
			return;
		}

		var sh; // the land in শতাংশ
		var inputs = [];
		var extra = null;
		if ( omode === 'fixed' ) {
			sh = 0;
			OKEYS.forEach( function ( k ) {
				sh += vals[ k ] * OLD[ k ].shatangsho;
				if ( vals[ k ] > 0 ) { inputs.push( [ OLD[ k ].label, format( vals[ k ] ) ] ); }
			} );
		} else if ( omode === 'share' ) {
			if ( ! ( vals.total > 0 ) ) {
				omsg.textContent = 'আগে মোট জমি (শতাংশ) লিখুন।';
				omsg.hidden = false;
				return;
			}
			var part = 0;
			OKEYS.forEach( function ( k ) {
				part += vals[ k ] * OLD[ k ].shatangsho;
				if ( vals[ k ] > 0 ) { inputs.push( [ OLD[ k ].label + ' (অংশ)', format( vals[ k ] ) ] ); }
			} );
			var whole = 16 * OLD[ OKEYS[ 0 ] ].shatangsho; // 16 আনা
			if ( part > whole ) {
				omsg.textContent = 'অংশ ১৬ আনার বেশি হতে পারে না। মাপগুলো আবার দেখুন।';
				omsg.hidden = false;
				return;
			}
			inputs.unshift( [ 'মোট জমি', format( vals.total ) + ' শতাংশ' ] );
			sh = vals.total * part / whole;
		} else {
			sh = vals.sh;
			inputs.push( [ 'জমি', format( sh ) + ' শতাংশ' ] );
			var rest = sh;
			var parts = [];
			OKEYS.forEach( function ( k, i ) {
				var u = OLD[ k ].shatangsho;
				var n = i === OKEYS.length - 1 ? Math.round( ( rest / u ) * 100 ) / 100 : Math.floor( rest / u + 1e-9 );
				rest -= n * u;
				if ( n > 0 ) { parts.push( format( n ) + ' ' + OLD[ k ].label ); }
			} );
			extra = [ [ 'আনা-গণ্ডা-কড়া-ক্রান্তিতে', parts.length ? parts.join( ', ' ) : '০' ] ];
		}
		omsg.hidden = true;
		var sqft = sh * SQFT_PER_SHOTANGSHO;
		renderList( oresults, sqft, 'shotangsho', extra );
		calc.old = { mode: OMODES[ omode ].label, inputs: inputs, sqft: sqft, extra: extra };
		updatePrint();
	}

	omodesEl.addEventListener( 'click', function ( e ) {
		var b = e.target.closest( '[data-lc-omode]' );
		if ( b ) {
			omode = b.getAttribute( 'data-lc-omode' );
			buildOld();
		}
	} );
	ofields.addEventListener( 'input', renderOld );

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
		var extraRows = [];
		if ( tab === 'old' ) {
			setSheetText( '[data-sheet-title]', 'পুরোনো মাপের হিসাব' );
			addLi( inputs, 'হিসাবের ধরন', data.mode );
			data.inputs.forEach( function ( r ) { addLi( inputs, r[ 0 ], r[ 1 ] ); } );
			diagram.innerHTML = '';
			base = data.sqft;
			fromKey = 'shotangsho';
			extraRows = data.extra || [];
		} else if ( tab === 'convert' ) {
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
		extraRows.forEach( function ( r ) {
			var tr = document.createElement( 'tr' );
			tr.className = 'is-from';
			var th = document.createElement( 'th' );
			th.textContent = r[ 0 ];
			var td = document.createElement( 'td' );
			td.textContent = r[ 1 ];
			tr.appendChild( th );
			tr.appendChild( td );
			rows.appendChild( tr );
		} );
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
	buildOld();

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

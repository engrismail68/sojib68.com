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
		var base = v * UNITS[ select.value ].sqft;
		keys.forEach( function ( k ) {
			var li = document.createElement( 'li' );
			li.className = 'lc-result' + ( k === select.value ? ' is-from' : '' );
			var name = document.createElement( 'span' );
			name.className = 'lc-result__name';
			name.textContent = UNITS[ k ].label;
			var val = document.createElement( 'strong' );
			val.className = 'lc-result__val';
			val.textContent = format( base / UNITS[ k ].sqft );
			li.appendChild( name );
			li.appendChild( val );
			results.appendChild( li );
		} );
	}

	function open( opener ) {
		lastOpener = opener || null;
		modal.hidden = false;
		document.body.classList.add( 'lc-open' );
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

	document.addEventListener( 'click', function ( e ) {
		var opener = e.target.closest( '[data-lc-open]' );
		if ( opener ) {
			e.preventDefault();
			open( opener );
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

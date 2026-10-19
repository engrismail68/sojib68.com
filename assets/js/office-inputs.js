/**
 * Office inputs: whatever the keyboard produces (Bangla or English digits),
 * numeric fields are converted to clean English digits as you type, so
 * "১২,৫০০" and "12500" always end up the same and a wrong character can't slip in.
 */
( function () {
	'use strict';

	var BN = '০১২৩৪৫৬৭৮৯';

	function toEnglish( str ) {
		return str.replace( /[০-৯]/g, function ( d ) {
			return String( BN.indexOf( d ) );
		} );
	}

	// money: digits and one decimal point; int: digits only; tel: digits and a leading +; text: digits converted only.
	function kindOf( el ) {
		if ( el.tagName !== 'INPUT' || ( el.type && el.type !== 'text' && el.type !== 'tel' ) ) {
			return '';
		}
		var name = el.getAttribute( 'name' ) || '';
		var mode = el.getAttribute( 'inputmode' );
		if ( 'decimal' === mode ) { return 'money'; }
		if ( 'numeric' === mode ) { return 'int'; }
		if ( 'tel' === mode ) { return 'tel'; }
		if ( /(^|\[)(deed_no|khatian_no)(\]|$)/.test( name ) ) { return 'text'; }
		return '';
	}

	function clean( kind, value ) {
		var v = toEnglish( value );
		switch ( kind ) {
			case 'money':
				v = v.replace( /[,،]/g, '' ).replace( /[^0-9.]/g, '' );
				var dot = v.indexOf( '.' );
				if ( dot !== -1 ) {
					v = v.slice( 0, dot + 1 ) + v.slice( dot + 1 ).replace( /\./g, '' ).slice( 0, 2 );
				}
				return v;
			case 'int':
				return v.replace( /[^0-9]/g, '' );
			case 'tel':
				return v.replace( /(?!^\+)[^0-9]/g, '' );
			default:
				return v;
		}
	}

	function currencyField( el ) {
		return el.hasAttribute( 'data-money' ) || /^(f-total-due|f-deed-value)$/.test( el.id ) || /^(value_min|value_max)$/.test( el.name );
	}

	function showHint( el ) {
		if ( ! currencyField( el ) ) { return; }
		var hint = el.nextElementSibling;
		if ( ! hint || ! hint.classList.contains( 'money-hint' ) ) {
			hint = document.createElement( 'small' );
			hint.className = 'money-hint';
			el.parentNode.insertBefore( hint, el.nextSibling );
		}
		var n = parseFloat( el.value );
		hint.textContent = isNaN( n ) || 0 === n ? '' : '= ৳ ' + n.toLocaleString( 'en-IN', { maximumFractionDigits: 2 } );
	}

	document.addEventListener( 'input', function ( e ) {
		var el = e.target;
		var kind = kindOf( el );
		if ( ! kind ) { return; }

		var before = el.value;
		var after = clean( kind, before );
		if ( after !== before ) {
			var pos = el.selectionStart;
			el.value = after;
			try {
				var diff = before.length - after.length;
				el.setSelectionRange( Math.max( 0, pos - diff ), Math.max( 0, pos - diff ) );
			} catch ( err ) { /* some input types don't support selection */ }
		}
		showHint( el );
	} );

	// Values filled in by the page (edit form, browser autofill) get the same treatment.
	function init() {
		document.querySelectorAll( 'input' ).forEach( function ( el ) {
			var kind = kindOf( el );
			if ( ! kind ) { return; }
			if ( 'money' === kind || 'int' === kind || 'tel' === kind ) {
				el.value = clean( kind, el.value );
			}
			if ( el.value ) { showHint( el ); }
		} );
	}
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();

( function () {
	'use strict';

	var cfg = window.sojib68Dues;
	if ( ! cfg ) {
		return;
	}

	var FLASH_KEY = 'sojib68_flash';

	function post( data ) {
		var fd = new FormData();
		Object.keys( data ).forEach( function ( k ) { fd.append( k, data[ k ] ); } );
		fd.append( 'nonce', cfg.nonce );
		return fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd } ).then( function ( r ) { return r.json(); } );
	}

	function flash( message ) {
		try { sessionStorage.setItem( FLASH_KEY, message ); } catch ( e ) { /* ignore */ }
	}

	// Show a message saved before the last reload.
	var box = document.getElementById( 'dues-flash' );
	try {
		var saved = sessionStorage.getItem( FLASH_KEY );
		if ( saved && box ) {
			box.textContent = '✓ ' + saved;
			box.hidden = false;
			sessionStorage.removeItem( FLASH_KEY );
		}
	} catch ( e ) { /* ignore */ }

	document.addEventListener( 'click', function ( e ) {
		var toggle = e.target.closest( '[data-toggle-collect]' );
		if ( toggle ) {
			var panel = toggle.closest( '.due-card' ).querySelector( '.due-card__collect' );
			panel.hidden = ! panel.hidden;
			if ( ! panel.hidden ) {
				var amount = panel.querySelector( '[data-collect-field="amount"]' );
				if ( amount ) { amount.focus(); }
			}
			return;
		}

		var fill = e.target.closest( '[data-fill]' );
		if ( fill ) {
			var field = fill.closest( '.collect' ).querySelector( '[data-collect-field="amount"]' );
			field.value = fill.getAttribute( 'data-fill' );
			field.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			return;
		}

		var save = e.target.closest( '[data-collect-save]' );
		if ( save ) {
			submitCollect( save.closest( '.collect' ), save );
			return;
		}

		var del = e.target.closest( '[data-del-payment]' );
		if ( del ) {
			if ( ! window.confirm( 'এই আদায়ের এন্ট্রিটি মুছে ফেলতে চান? বাকি টাকা আবার বেড়ে যাবে।' ) ) {
				return;
			}
			del.disabled = true;
			post( { action: 'sojib68_delete_payment', payment_id: del.getAttribute( 'data-del-payment' ) } )
				.then( function ( res ) {
					if ( res.success ) {
						flash( res.data.message );
						window.location.reload();
					} else {
						del.disabled = false;
						window.alert( res.data && res.data.message ? res.data.message : 'মুছা যায়নি।' );
					}
				} )
				.catch( function () { del.disabled = false; window.alert( 'নেটওয়ার্ক সমস্যা হয়েছে।' ); } );
		}
	} );

	// Enter inside the collect panel saves it (and never submits the surrounding entry form).
	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Enter' !== e.key ) { return; }
		var wrap = e.target.closest && e.target.closest( '.collect' );
		if ( wrap && 'INPUT' === e.target.tagName ) {
			e.preventDefault();
			submitCollect( wrap, wrap.querySelector( '[data-collect-save]' ) );
		}
	} );

	function submitCollect( wrap, btn ) {
		var msg = wrap.querySelector( '.collect__msg' );
		var get = function ( n ) { return wrap.querySelector( '[data-collect-field="' + n + '"]' ).value; };

		function say( text, ok ) {
			msg.hidden = false;
			msg.textContent = text;
			msg.className = 'collect__msg ' + ( ok ? 'is-ok' : 'is-err' );
		}

		var amount = parseFloat( get( 'amount' ) );
		if ( isNaN( amount ) || amount <= 0 ) {
			say( 'আগে কত টাকা আদায় হলো তা লিখুন।', false );
			return;
		}

		btn.disabled = true;
		post( {
			action: 'sojib68_add_payment',
			deed_id: wrap.getAttribute( 'data-collect' ),
			amount: get( 'amount' ),
			received_date: get( 'received_date' ),
			method: get( 'method' ),
			note: get( 'note' ),
		} )
			.then( function ( res ) {
				if ( res.success ) {
					flash( res.data.message );
					window.location.reload();
				} else {
					btn.disabled = false;
					say( res.data && res.data.message ? res.data.message : 'সংরক্ষণ করা যায়নি।', false );
				}
			} )
			.catch( function () {
				btn.disabled = false;
				say( 'নেটওয়ার্ক সমস্যা হয়েছে। আবার চেষ্টা করুন।', false );
			} );
	}
} )();

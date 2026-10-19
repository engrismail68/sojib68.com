/**
 * Delete / restore / permanently-erase a deed — used on the all-deeds
 * list, the recycle bin page, and the entry/edit form's "মুছুন" link.
 * Soft delete and restore reload the page on success so counts and
 * the serial numbers (which shift once a deed leaves/re-enters the
 * active list) are always shown fresh from the server.
 */
( function () {
	'use strict';

	var cfg = window.sojib68DeedActions;
	if ( ! cfg ) {
		return;
	}

	function post( data ) {
		var fd = new FormData();
		Object.keys( data ).forEach( function ( k ) { fd.append( k, data[ k ] ); } );
		fd.append( 'nonce', cfg.nonce );
		return fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd } ).then( function ( r ) { return r.json(); } );
	}

	function flash( message ) {
		try { sessionStorage.setItem( 'sojib68_flash', message ); } catch ( e ) { /* ignore */ }
	}

	// Show a message saved just before the last reload (delete/restore/purge all navigate away).
	var box = document.getElementById( 'office-flash' );
	try {
		var saved = sessionStorage.getItem( 'sojib68_flash' );
		if ( saved && box ) {
			box.textContent = '✓ ' + saved;
			box.hidden = false;
			sessionStorage.removeItem( 'sojib68_flash' );
		}
	} catch ( e ) { /* ignore */ }

	document.addEventListener( 'click', function ( e ) {
		var del = e.target.closest( '[data-delete-deed]' );
		if ( del ) {
			if ( ! window.confirm( 'দলিলটি রিসাইকেল বিনে পাঠাতে চান? ৭ দিনের মধ্যে ফিরিয়ে আনা যাবে, এরপর স্থায়ীভাবে মুছে যাবে।' ) ) {
				return;
			}
			del.disabled = true;
			post( { action: 'sojib68_delete_deed', deed_id: del.getAttribute( 'data-delete-deed' ) } )
				.then( function ( res ) {
					if ( res.success ) {
						flash( res.data.message );
						window.location = del.getAttribute( 'data-after' ) || window.location.href;
					} else {
						del.disabled = false;
						window.alert( res.data && res.data.message ? res.data.message : 'মুছা যায়নি।' );
					}
				} )
				.catch( function () { del.disabled = false; window.alert( 'নেটওয়ার্ক সমস্যা হয়েছে।' ); } );
			return;
		}

		var restore = e.target.closest( '[data-restore-deed]' );
		if ( restore ) {
			restore.disabled = true;
			post( { action: 'sojib68_restore_deed', deed_id: restore.getAttribute( 'data-restore-deed' ) } )
				.then( function ( res ) {
					if ( res.success ) {
						flash( res.data.message );
						window.location.reload();
					} else {
						restore.disabled = false;
						window.alert( res.data && res.data.message ? res.data.message : 'ফিরিয়ে আনা যায়নি।' );
					}
				} )
				.catch( function () { restore.disabled = false; window.alert( 'নেটওয়ার্ক সমস্যা হয়েছে।' ); } );
			return;
		}

		var purge = e.target.closest( '[data-purge-deed]' );
		if ( purge ) {
			if ( ! window.confirm( 'এখনই স্থায়ীভাবে মুছে ফেলতে চান? এই কাজ আর ফেরানো যাবে না।' ) ) {
				return;
			}
			purge.disabled = true;
			post( { action: 'sojib68_purge_deed', deed_id: purge.getAttribute( 'data-purge-deed' ) } )
				.then( function ( res ) {
					if ( res.success ) {
						flash( res.data.message );
						window.location.reload();
					} else {
						purge.disabled = false;
						window.alert( res.data && res.data.message ? res.data.message : 'মুছা যায়নি।' );
					}
				} )
				.catch( function () { purge.disabled = false; window.alert( 'নেটওয়ার্ক সমস্যা হয়েছে।' ); } );
		}
	} );
} )();

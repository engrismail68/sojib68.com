/* মৌজা তালিকা (sidebar): add / edit / delete a mouza profile. */
( function () {
	'use strict';
	var M = window.sojib68DwM, dlg = document.getElementById( 'dw-mouza-dlg' );
	if ( ! M || ! dlg ) { return; }
	var form = document.getElementById( 'dw-mouza-form' ), delBtn = document.getElementById( 'dw-mouza-del' );
	var FIELDS = [ 'name', 'no', 'union', 'upazila', 'district', 'jl_no', 'village', 'land_office', 'sub_office', 'intro', 'id' ];

	function open( id ) {
		var m = id && M.mouzas[ id ] ? M.mouzas[ id ] : { upazila: 'রামগঞ্জ', district: 'লক্ষ্মীপুর', sub_office: 'রামগঞ্জ সাব রেজিস্ট্রি অফিস।', land_office: 'সহকারী কমিশনার ভূমি অফিস, রামগঞ্জ।' };
		FIELDS.forEach( function ( k ) { form.elements[ k ].value = m[ k ] || ''; } );
		document.getElementById( 'dw-mouza-title' ).textContent = id ? 'মৌজার তথ্য বদলান' : 'নতুন মৌজা';
		delBtn.hidden = ! id;
		if ( dlg.showModal ) { dlg.showModal(); } else { dlg.setAttribute( 'open', '' ); }
	}
	function close() { if ( dlg.close ) { dlg.close(); } else { dlg.removeAttribute( 'open' ); } }
	function post( action, extra ) {
		var fd = new FormData(); fd.append( 'action', action ); fd.append( 'nonce', M.nonce );
		Object.keys( extra ).forEach( function ( k ) { fd.append( k, extra[ k ] ); } );
		return fetch( M.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } );
	}

	document.addEventListener( 'click', function ( e ) {
		var t = e.target.closest && e.target.closest( 'button' );
		if ( ! t ) { return; }
		if ( t.id === 'dw-mouza-add' ) { open( '' ); }
		else if ( t.hasAttribute( 'data-mouza-edit' ) ) { open( t.getAttribute( 'data-mouza-edit' ) ); }
		else if ( t.id === 'dw-mouza-cancel' ) { close(); }
		else if ( t.id === 'dw-mouza-del' ) {
			if ( window.confirm( 'এই মৌজা তালিকা থেকে মুছবেন? (আগের দলিলগুলো মুছবে না)' ) ) {
				post( 'sojib68_dw_mouza_delete', { id: form.elements.id.value } ).then( function () { location.reload(); } );
			}
		}
	} );

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		var data = {};
		FIELDS.forEach( function ( k ) { data[ k ] = form.elements[ k ].value; } );
		post( 'sojib68_dw_mouza_save', { mouza: JSON.stringify( data ) } ).then( function ( res ) {
			if ( res && res.success ) { location.reload(); } else { window.alert( ( res && res.data && res.data.message ) || 'সেভ হয়নি' ); }
		} );
	} );

	window.sojib68DwMouza = { post: post, M: M };
}() );

( function () {
	var q = document.getElementById( 'dw-mz-search' );
	if ( ! q ) { return; }
	q.addEventListener( 'input', function () {
		var t = q.value.trim().toLowerCase();
		[].forEach.call( document.querySelectorAll( '.dw-mouzas li' ), function ( li ) {
			var all = li.querySelector( '.dw-mz-name' );
			if ( ! all ) { return; }   // "সব দলিল" stays
			li.style.display = ! t || all.textContent.toLowerCase().indexOf( t ) >= 0 ? '' : 'none';
		} );
	} );
}() );

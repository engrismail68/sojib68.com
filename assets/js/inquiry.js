( function () {
	'use strict';

	var modal = document.querySelector( '[data-inq-modal]' );
	if ( ! modal || typeof window.sojib68Inquiry === 'undefined' ) {
		return;
	}

	var cfg = window.sojib68Inquiry;
	var dialog = modal.querySelector( '.inq-dialog' );
	var form = modal.querySelector( '[data-inq-form]' );
	var done = modal.querySelector( '[data-inq-done]' );
	var statusEl = modal.querySelector( '[data-inq-status]' );
	var submitBtn = modal.querySelector( '[data-inq-submit]' );
	var lastOpener = null;
	var openedAt = 0;
	var busy = false;

	function clearErrors() {
		form.querySelectorAll( '[data-inq-error]' ).forEach( function ( el ) { el.textContent = ''; } );
		form.querySelectorAll( '.has-error' ).forEach( function ( el ) { el.classList.remove( 'has-error' ); } );
		statusEl.textContent = '';
		statusEl.className = 'inq-status';
	}

	function showErrors( errors ) {
		Object.keys( errors || {} ).forEach( function ( key ) {
			var slot = form.querySelector( '[data-inq-error="' + key + '"]' );
			var field = form.elements[ key ];
			if ( slot ) { slot.textContent = errors[ key ]; }
			if ( field ) { field.classList.add( 'has-error' ); }
		} );
	}

	function open( opener ) {
		lastOpener = opener || null;
		openedAt = Date.now();
		form.hidden = false;
		done.hidden = true;
		clearErrors();
		modal.hidden = false;
		document.body.classList.add( 'inq-open' );
		var first = form.elements.name;
		if ( first ) { first.focus(); }
	}

	function close() {
		modal.hidden = true;
		document.body.classList.remove( 'inq-open' );
		if ( lastOpener && lastOpener.focus ) { lastOpener.focus(); }
	}

	// an error goes away as soon as that box is edited
	form.addEventListener( 'input', function ( e ) {
		var el = e.target;
		if ( el && el.name ) {
			el.classList.remove( 'has-error' );
			var slot = form.querySelector( '[data-inq-error="' + el.name + '"]' );
			if ( slot ) { slot.textContent = ''; }
		}
	} );

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		if ( busy ) { return; }
		clearErrors();

		// quick local check; the server checks again
		var local = {};
		if ( ! form.elements.name.value.trim() ) { local.name = 'নাম লিখুন।'; }
		if ( ! form.elements.phone.value.trim() ) { local.phone = 'মোবাইল নম্বর লিখুন।'; }
		if ( ! form.elements.message.value.trim() ) { local.message = 'সমস্যার বিবরণ লিখুন।'; }
		if ( Object.keys( local ).length ) {
			showErrors( local );
			return;
		}

		var elapsed = Date.now() - openedAt;
		if ( elapsed < cfg.minMs ) {
			// a person who filled this faster than the minimum: just wait it out
			busy = true;
			submitBtn.disabled = true;
			setTimeout( function () { busy = false; submitBtn.disabled = false; form.requestSubmit(); }, cfg.minMs - elapsed + 100 );
			return;
		}

		busy = true;
		submitBtn.disabled = true;
		statusEl.textContent = 'পাঠানো হচ্ছে…';

		var body = new URLSearchParams();
		body.append( 'action', 'sojib68_inquiry' );
		body.append( 't', String( elapsed ) );
		[ 'name', 'phone', 'address', 'message', 'website' ].forEach( function ( k ) {
			body.append( k, form.elements[ k ].value );
		} );

		fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} )
			.then( function ( res ) { return res.json().catch( function () { return null; } ); } )
			.then( function ( json ) {
				busy = false;
				submitBtn.disabled = false;
				if ( json && json.success ) {
					statusEl.textContent = '';
					form.reset();
					form.hidden = true;
					done.hidden = false;
					if ( window.sojib68Track ) { window.sojib68Track( 'inquiry_form' ); }
					return;
				}
				var data = json && json.data ? json.data : {};
				showErrors( data.errors );
				statusEl.textContent = data.message || 'জমা দেওয়া যায়নি। একটু পরে আবার চেষ্টা করুন।';
				statusEl.className = 'inq-status is-error';
			} )
			.catch( function () {
				busy = false;
				submitBtn.disabled = false;
				statusEl.textContent = 'সংযোগে সমস্যা হয়েছে। আবার চেষ্টা করুন।';
				statusEl.className = 'inq-status is-error';
			} );
	} );

	document.addEventListener( 'click', function ( e ) {
		var opener = e.target.closest( '[data-inq-open]' );
		if ( opener ) {
			e.preventDefault();
			// close the mobile menu if it is open
			var nav = document.getElementById( 'primary-navigation' );
			if ( nav && nav.classList ) { nav.classList.remove( 'is-open' ); }
			open( opener );
			return;
		}
		if ( ! modal.hidden && e.target.closest( '[data-inq-close]' ) ) {
			close();
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( ! modal.hidden && ( e.key === 'Escape' || e.key === 'Esc' ) ) {
			close();
		}
	} );
} )();

/* Gallery on a জমি ও প্রপার্টি advertisement: a click on a thumbnail shows that photo in the big frame. */
( function () {
	'use strict';
	document.querySelectorAll( '[data-prop-gallery]' ).forEach( function ( gallery ) {
		var stage = gallery.querySelector( '[data-prop-stage]' );
		if ( ! stage ) { return; }
		gallery.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '[data-prop-thumb]' );
			if ( ! btn ) { return; }
			stage.removeAttribute( 'srcset' );
			stage.removeAttribute( 'sizes' );
			stage.src = btn.getAttribute( 'data-prop-thumb' );
			gallery.querySelectorAll( '[data-prop-thumb]' ).forEach( function ( b ) { b.classList.toggle( 'is-on', b === btn ); } );
		} );
	} );
} )();


/*
 * "নিজের নাম ও নম্বর রেখে যান": the buyer's name and mobile are sent to the server and become a lead
 * (CRM + wp-admin). The seller's number is never in the page or in the answer.
 */
( function () {
	'use strict';

	var box = document.querySelector( '[data-prop-contact]' );
	var form = box ? box.querySelector( '[data-prop-gate]' ) : null;
	if ( ! box || ! form || typeof window.sojib68Property === 'undefined' ) {
		return;
	}

	var openedAt = Date.now();
	var thanks = box.querySelector( '[data-prop-thanks]' );
	var statusEl = form.querySelector( '[data-prop-gate-status]' );
	var submit = form.querySelector( '[data-prop-gate-submit]' );
	var busy = false;

	function say( text, isError ) {
		statusEl.textContent = text || '';
		statusEl.className = 'prop-gate__status' + ( isError ? ' is-error' : '' );
	}

	function errors( map ) {
		form.querySelectorAll( '[data-error-for]' ).forEach( function ( el ) { el.textContent = ''; } );
		Object.keys( map || {} ).forEach( function ( k ) {
			var slot = form.querySelector( '[data-error-for="' + k + '"]' );
			if ( slot ) { slot.textContent = map[ k ]; }
		} );
	}

	form.addEventListener( 'input', function ( e ) {
		var slot = form.querySelector( '[data-error-for="' + e.target.name + '"]' );
		if ( slot ) { slot.textContent = ''; }
	} );

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		if ( busy ) { return; }
		errors( {} );
		say( '' );

		var name = form.elements.name.value.trim();
		var phone = form.elements.phone.value.trim();
		var local = {};
		if ( ! name ) { local.name = 'আপনার নাম লিখুন।'; }
		if ( ! phone ) { local.phone = 'আপনার মোবাইল নম্বর লিখুন।'; }
		if ( Object.keys( local ).length ) { errors( local ); return; }

		var elapsed = Date.now() - openedAt;
		if ( elapsed < 1700 ) {
			busy = true;
			submit.disabled = true;
			setTimeout( function () { busy = false; submit.disabled = false; form.requestSubmit(); }, 1800 - elapsed );
			return;
		}

		busy = true;
		submit.disabled = true;
		say( 'একটু অপেক্ষা করুন…' );

		var body = new URLSearchParams();
		body.append( 'action', 'sojib68_property_lead' );
		body.append( 'id', box.getAttribute( 'data-prop-id' ) );
		body.append( 't', String( elapsed ) );
		body.append( 'name', name );
		body.append( 'phone', phone );
		body.append( 'website', form.elements.website.value );

		fetch( window.sojib68Property.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} )
			.then( function ( res ) { return res.json().catch( function () { return null; } ); } )
			.then( function ( json ) {
				busy = false;
				submit.disabled = false;
				if ( json && json.success ) {
					say( '' );
					if ( window.sojib68Track ) { window.sojib68Track( 'property_lead' ); }
					form.hidden = true;
					thanks.textContent = ( json.data && json.data.message ) || 'ধন্যবাদ! শীঘ্রই আপনার সঙ্গে যোগাযোগ করা হবে।';
					thanks.hidden = false;
					return;
				}
				var d = json && json.data ? json.data : {};
				errors( d.errors );
				say( d.message || 'এই মুহূর্তে হলো না। একটু পরে আবার চেষ্টা করুন।', true );
			} )
			.catch( function () {
				busy = false;
				submit.disabled = false;
				say( 'সংযোগে সমস্যা হয়েছে। আবার চেষ্টা করুন।', true );
			} );
	} );
} )();

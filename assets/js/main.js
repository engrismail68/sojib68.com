( function () {
	'use strict';

	// Mobile menu toggle.
	var toggle = document.getElementById( 'menu-toggle' );
	var nav = document.getElementById( 'primary-navigation' );

	if ( toggle && nav ) {
		toggle.addEventListener( 'click', function () {
			var isOpen = nav.classList.toggle( 'is-open' );
			toggle.classList.toggle( 'is-open', isOpen );
			toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		} );

		nav.querySelectorAll( 'a' ).forEach( function ( link ) {
			link.addEventListener( 'click', function () {
				nav.classList.remove( 'is-open' );
				toggle.classList.remove( 'is-open' );
				toggle.setAttribute( 'aria-expanded', 'false' );
			} );
		} );
	}

	// Pre-select a service when a service card is clicked.
	document.querySelectorAll( '.service-card[data-service]' ).forEach( function ( card ) {
		card.addEventListener( 'click', function () {
			var select = document.getElementById( 'field-service' );
			if ( select ) {
				select.value = card.getAttribute( 'data-service' );
			}
		} );
	} );

	// Contact form submission.
	var form = document.getElementById( 'contact-form' );
	if ( ! form || typeof window.sojib68Contact === 'undefined' ) {
		return;
	}

	var statusEl = form.querySelector( '.contact__status' );
	var submitBtn = form.querySelector( '.contact__submit' );
	var submitLabel = submitBtn ? submitBtn.querySelector( '.btn-label' ) : null;
	var originalLabel = submitLabel ? submitLabel.textContent : '';

	function clearErrors() {
		form.querySelectorAll( '.field-error' ).forEach( function ( el ) {
			el.textContent = '';
		} );
		form.querySelectorAll( '.has-error' ).forEach( function ( el ) {
			el.classList.remove( 'has-error' );
		} );
	}

	function showErrors( errors ) {
		Object.keys( errors || {} ).forEach( function ( field ) {
			var errorEl = form.querySelector( '[data-error-for="' + field + '"]' );
			var input = form.querySelector( '[name="' + field + '"]' );
			if ( errorEl ) {
				errorEl.textContent = errors[ field ];
			}
			if ( input ) {
				input.classList.add( 'has-error' );
			}
		} );
	}

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		clearErrors();

		if ( statusEl ) {
			statusEl.textContent = '';
			statusEl.classList.remove( 'is-success', 'is-error' );
		}

		var formData = new FormData( form );
		formData.append( 'action', 'sojib68_submit_contact' );
		formData.append( 'nonce', window.sojib68Contact.nonce );

		if ( submitBtn ) {
			submitBtn.disabled = true;
		}
		if ( submitLabel ) {
			submitLabel.textContent = '...';
		}

		fetch( window.sojib68Contact.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function ( response ) {
				return response.json().then( function ( data ) {
					return { ok: response.ok, data: data };
				} );
			} )
			.then( function ( result ) {
				if ( result.data && result.data.success ) {
					if ( statusEl ) {
						statusEl.textContent = result.data.data.message;
						statusEl.classList.add( 'is-success' );
					}
					form.reset();
				} else {
					var payload = result.data && result.data.data ? result.data.data : {};
					if ( statusEl ) {
						statusEl.textContent = payload.message || 'কিছু একটা সমস্যা হয়েছে। আবার চেষ্টা করুন।';
						statusEl.classList.add( 'is-error' );
					}
					showErrors( payload.errors );
				}
			} )
			.catch( function () {
				if ( statusEl ) {
					statusEl.textContent = 'নেটওয়ার্ক সমস্যা হয়েছে। আবার চেষ্টা করুন।';
					statusEl.classList.add( 'is-error' );
				}
			} )
			.finally( function () {
				if ( submitBtn ) {
					submitBtn.disabled = false;
				}
				if ( submitLabel ) {
					submitLabel.textContent = originalLabel;
				}
			} );
	} );
} )();

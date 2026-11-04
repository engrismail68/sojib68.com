/*
 * Lead tracking hook. A click on anything marked data-lead="<type>" (WhatsApp, call,
 * social, email …) is reported as a lead to whatever is installed on the page:
 *  - dataLayer (Google Tag Manager): event "lead_action" with lead_type
 *  - Meta Pixel (fbq): standard event "Contact"
 *  - Google tag (gtag): event "generate_lead"
 * Nothing is sent when none of them is present, and no personal data is involved.
 */
( function () {
	'use strict';

	function track( type ) {
		try {
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push( { event: 'lead_action', lead_type: type } );
			if ( typeof window.fbq === 'function' ) {
				window.fbq( 'track', 'Contact', { lead_type: type } );
			}
			if ( typeof window.gtag === 'function' ) {
				window.gtag( 'event', 'generate_lead', { lead_type: type } );
			}
		} catch ( e ) { /* tracking must never break the click */ }
	}

	window.sojib68Track = track;

	document.addEventListener( 'click', function ( e ) {
		var el = e.target.closest( '[data-lead]' );
		if ( el ) {
			track( el.getAttribute( 'data-lead' ) );
		}
	} );
} )();

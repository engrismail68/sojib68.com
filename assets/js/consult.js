( function () {
	'use strict';

	var modal = document.querySelector( '[data-cs-modal]' );
	var dataEl = document.getElementById( 'cs-data' );
	if ( ! modal || ! dataEl ) {
		return;
	}
	var DATA;
	try {
		DATA = JSON.parse( dataEl.textContent );
	} catch ( e ) {
		return;
	}

	var dialog = modal.querySelector( '.lc-dialog' );
	var topicsBox = modal.querySelector( '[data-cs-topics]' );
	var topicList = modal.querySelector( '[data-cs-topiclist]' );
	var nameIn = modal.querySelector( '[data-cs-name]' );
	var placeIn = modal.querySelector( '[data-cs-place]' );
	var msgIn = modal.querySelector( '[data-cs-msg]' );
	var err = modal.querySelector( '[data-cs-err]' );
	var topic = '';
	var lastOpener = null;

	if ( DATA.topics && DATA.topics.length ) {
		topicsBox.hidden = false;
		DATA.topics.forEach( function ( t ) {
			var b = document.createElement( 'button' );
			b.type = 'button';
			b.className = 'lc-chip';
			b.setAttribute( 'data-cs-topic', t );
			b.setAttribute( 'aria-pressed', 'false' );
			b.textContent = t;
			topicList.appendChild( b );
		} );
	}

	function refreshTopics() {
		topicList.querySelectorAll( '[data-cs-topic]' ).forEach( function ( b ) {
			var on = b.getAttribute( 'data-cs-topic' ) === topic;
			b.classList.toggle( 'is-on', on );
			b.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		} );
	}

	function open( opener ) {
		lastOpener = opener || null;
		modal.hidden = false;
		document.body.classList.add( 'lc-open' );
		err.hidden = true;
		dialog.focus();
	}

	function close() {
		modal.hidden = true;
		document.body.classList.remove( 'lc-open' );
		if ( lastOpener && lastOpener.focus ) { lastOpener.focus(); }
	}

	function send() {
		var name = nameIn.value.trim();
		var place = placeIn.value.trim();
		var msg = msgIn.value.trim();
		if ( ! topic && ! msg ) {
			err.textContent = 'বিষয় বাছুন অথবা সংক্ষেপে কী জানতে চান লিখুন।';
			err.hidden = false;
			return;
		}
		err.hidden = true;
		var lines = [ 'আসসালামু আলাইকুম,', '"' + DATA.title + '" বিষয়ে জানতে চাই।' ];
		if ( topic ) { lines.push( 'বিষয়: ' + topic ); }
		if ( name ) { lines.push( 'নাম: ' + name ); }
		if ( place ) { lines.push( 'জমির এলাকা: ' + place ); }
		if ( msg ) { lines.push( 'সংক্ষেপে: ' + msg ); }
		var url = 'https://wa.me/' + DATA.wa + '?text=' + encodeURIComponent( lines.join( '\n' ) );
		if ( window.sojib68Track ) { window.sojib68Track( 'whatsapp_form' ); }
		window.open( url, '_blank', 'noopener' );
	}

	document.addEventListener( 'click', function ( e ) {
		var opener = e.target.closest( '[data-cs-open]' );
		if ( opener ) {
			e.preventDefault();
			open( opener );
			return;
		}
		if ( modal.hidden ) { return; }
		var t = e.target.closest( '[data-cs-topic]' );
		if ( t ) {
			var name = t.getAttribute( 'data-cs-topic' );
			topic = topic === name ? '' : name;
			refreshTopics();
			err.hidden = true;
			return;
		}
		if ( e.target.closest( '[data-cs-send]' ) ) {
			send();
			return;
		}
		if ( e.target.closest( '[data-cs-close]' ) ) {
			close();
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( ! modal.hidden && ( e.key === 'Escape' || e.key === 'Esc' ) ) {
			close();
		}
	} );
} )();

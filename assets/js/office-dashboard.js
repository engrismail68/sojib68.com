/**
 * Dashboard report: monthly/yearly tab toggle (no reload — both panels
 * are rendered server-side) and the hover/focus tooltip shared by both
 * chart types. A bar's own data-* attributes are the only source for
 * the tooltip text, inserted with textContent only.
 */
( function () {
	'use strict';

	var section = document.querySelector( '.report-section' );
	if ( ! section ) {
		return;
	}

	/* ---------------- Tabs ---------------- */

	section.querySelectorAll( '[data-report-tab]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var view = btn.getAttribute( 'data-report-tab' );
			section.querySelectorAll( '[data-report-tab]' ).forEach( function ( b ) {
				b.classList.toggle( 'is-active', b === btn );
			} );
			section.querySelectorAll( '[data-report-panel]' ).forEach( function ( panel ) {
				panel.hidden = panel.getAttribute( 'data-report-panel' ) !== view;
			} );
		} );
	} );

	/* ---------------- Tooltip ---------------- */

	var tip = document.getElementById( 'chart-tooltip' );
	if ( ! tip ) {
		return;
	}

	function fmt( n ) {
		return '৳' + Number( n ).toLocaleString( 'en-IN' );
	}

	function buildRows( g ) {
		var rows = [];
		if ( g.hasAttribute( 'data-collected' ) ) {
			rows.push( [ 'আদায়', fmt( g.getAttribute( 'data-collected' ) ), '#2a78d6' ] );
			rows.push( [ 'খরচ', fmt( g.getAttribute( 'data-expenses' ) ), '#eb6834' ] );
		}
		if ( g.hasAttribute( 'data-profit' ) ) {
			var neg = 'neg' === g.getAttribute( 'data-sign' );
			rows.push( [ neg ? 'ক্ষতি' : 'লাভ', fmt( g.getAttribute( 'data-profit' ) ), neg ? '#b3261e' : '#198030' ] );
		}
		return rows;
	}

	function showTip( g, x, y ) {
		tip.innerHTML = '';

		var title = document.createElement( 'div' );
		title.className = 'chart-tooltip__title';
		title.textContent = g.getAttribute( 'data-label' ) || '';
		tip.appendChild( title );

		buildRows( g ).forEach( function ( row ) {
			var line = document.createElement( 'div' );
			line.className = 'chart-tooltip__row';

			var key = document.createElement( 'span' );
			key.className = 'chart-tooltip__key';
			key.style.background = row[ 2 ];

			var label = document.createElement( 'span' );
			label.className = 'chart-tooltip__label';
			label.textContent = row[ 0 ];

			var val = document.createElement( 'strong' );
			val.textContent = row[ 1 ];

			line.appendChild( key );
			line.appendChild( label );
			line.appendChild( val );
			tip.appendChild( line );
		} );

		tip.hidden = false;
		var card = g.closest( '.chart-card' );
		var cardRect = card.getBoundingClientRect();
		var tipRect = tip.getBoundingClientRect();
		var left = x - cardRect.left - tipRect.width / 2;
		left = Math.max( 4, Math.min( left, cardRect.width - tipRect.width - 4 ) );
		card.style.position = 'relative';
		tip.style.left = left + 'px';
		tip.style.top = ( y - cardRect.top - tipRect.height - 14 ) + 'px';
		card.appendChild( tip );
	}

	function hideTip() {
		tip.hidden = true;
	}

	section.querySelectorAll( '.chart-group' ).forEach( function ( g ) {
		g.addEventListener( 'pointerenter', function ( e ) {
			g.classList.add( 'is-active' );
			showTip( g, e.clientX, e.clientY );
		} );
		g.addEventListener( 'pointermove', function ( e ) {
			showTip( g, e.clientX, e.clientY );
		} );
		g.addEventListener( 'pointerleave', function () {
			g.classList.remove( 'is-active' );
			hideTip();
		} );
		g.addEventListener( 'focus', function () {
			g.classList.add( 'is-active' );
			var rect = g.querySelector( '.chart-bar' ).getBoundingClientRect();
			showTip( g, rect.left + rect.width / 2, rect.top );
		} );
		g.addEventListener( 'blur', function () {
			g.classList.remove( 'is-active' );
			hideTip();
		} );
	} );
} )();

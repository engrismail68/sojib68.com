/**
 * "নকশা থেকে মাপ": load a map image / PDF, set the scale from one known distance, click the corners of the
 * land, and read the area. Everything happens in the browser — the file is never uploaded.
 */
( function () {
	'use strict';
	var modal = document.querySelector( '[data-lc-modal]' );
	var panel = modal && modal.querySelector( '[data-lc-panel="map"]' );
	var LC = window.sojib68Lc;
	if ( ! panel || ! LC ) { return; }

	var $ = function ( s ) { return panel.querySelector( s ); };
	var fileEl = $( '[data-lm-file]' ), stage = $( '[data-lm-stage]' ), canvas = $( '[data-lm-canvas]' ), tools = $( '[data-lm-tools]' );
	var stepEl = $( '[data-lm-step]' ), msg = $( '[data-lm-msg]' ), results = $( '[data-lm-results]' ), scaleBox = $( '[data-lm-scalebox]' );
	var distEl = $( '[data-lm-dist]' ), dunitEl = $( '[data-lm-dunit]' ), pdfBar = $( '[data-lm-pdfbar]' ), pageEl = $( '[data-lm-page]' ), pagesEl = $( '[data-lm-pages]' );
	var ctx = canvas.getContext( '2d' );

	var bg = null, iw = 0, ih = 0;                 // background (image or rendered PDF page)
	var view = { s: 1, ox: 0, oy: 0 };
	var pts = [], closed = false, scalePts = [], ppf = null;   // ppf = image pixels per foot
	var mode = 'scale', drag = null, pdfDoc = null, cw = 0, ch = 0, dpr = 1;
	var FT_PER_M = 3.28084;

	/* ---------------------------------------------------------------- helpers */
	function say( t, bad ) { msg.textContent = t || ''; msg.hidden = ! t; msg.classList.toggle( 'is-bad', !! bad ); }
	function toScreen( p ) { return { x: p.x * view.s + view.ox, y: p.y * view.s + view.oy }; }
	function toImage( x, y ) { return { x: ( x - view.ox ) / view.s, y: ( y - view.oy ) / view.s }; }
	function dist( a, b ) { return Math.hypot( a.x - b.x, a.y - b.y ); }
	function ft( px ) { return px / ppf; }

	function layout() {
		var w = stage.clientWidth || 480;
		dpr = window.devicePixelRatio || 1;
		cw = w; ch = Math.max( 280, Math.min( 460, Math.round( w * 0.75 ) ) );
		canvas.style.width = cw + 'px'; canvas.style.height = ch + 'px';
		canvas.width = Math.round( cw * dpr ); canvas.height = Math.round( ch * dpr );
		if ( bg ) { fit(); }
		draw();
	}
	function fit() {
		if ( ! bg ) { return; }
		view.s = Math.min( cw / iw, ch / ih ) * 0.98;
		view.ox = ( cw - iw * view.s ) / 2; view.oy = ( ch - ih * view.s ) / 2;
	}
	function zoomAt( cx, cy, f ) {
		var before = toImage( cx, cy ), s = Math.max( 0.05, Math.min( 40, view.s * f ) );
		view.s = s; view.ox = cx - before.x * s; view.oy = cy - before.y * s; draw();
	}

	/* ---------------------------------------------------------------- drawing */
	function polyPoints() { return pts; }
	function drawOverlay( c, tx, withUi ) {
		// tx: image point -> canvas point
		var list = polyPoints(), i, a, b;
		if ( list.length >= 3 && closed ) {
			c.beginPath(); list.forEach( function ( p, k ) { var q = tx( p ); k ? c.lineTo( q.x, q.y ) : c.moveTo( q.x, q.y ); } ); c.closePath();
			c.fillStyle = 'rgba(25,128,48,.22)'; c.fill();
		}
		c.lineWidth = 2; c.strokeStyle = '#0f6023'; c.lineJoin = 'round';
		c.beginPath();
		list.forEach( function ( p, k ) { var q = tx( p ); k ? c.lineTo( q.x, q.y ) : c.moveTo( q.x, q.y ); } );
		if ( closed && list.length > 2 ) { c.closePath(); }
		c.stroke();
		// side lengths
		if ( ppf ) {
			c.font = '600 ' + ( withUi ? 12 : 22 ) + 'px sans-serif'; c.textAlign = 'center'; c.textBaseline = 'middle';
			var n = closed ? list.length : list.length - 1;
			for ( i = 0; i < n; i++ ) {
				a = list[ i ]; b = list[ ( i + 1 ) % list.length ];
				var qa = tx( a ), qb = tx( b ), t = LC.format( Math.round( ft( dist( a, b ) ) * 10 ) / 10 ) + ' ফুট', mx = ( qa.x + qb.x ) / 2, my = ( qa.y + qb.y ) / 2, w = c.measureText( t ).width + 8;
				c.fillStyle = 'rgba(255,255,255,.88)'; c.fillRect( mx - w / 2, my - 9, w, 18 );
				c.fillStyle = '#0f6023'; c.fillText( t, mx, my );
			}
		}
		if ( withUi ) {
			list.forEach( function ( p, k ) {
				var q = tx( p ); c.beginPath(); c.arc( q.x, q.y, k === 0 && ! closed ? 7 : 5, 0, Math.PI * 2 );
				c.fillStyle = k === 0 ? '#c0392b' : '#198030'; c.fill(); c.strokeStyle = '#fff'; c.lineWidth = 2; c.stroke();
			} );
		}
	}
	function draw() {
		ctx.setTransform( dpr, 0, 0, dpr, 0, 0 );
		ctx.fillStyle = '#e9ece8'; ctx.fillRect( 0, 0, cw, ch );
		if ( ! bg ) { return; }
		ctx.imageSmoothingQuality = 'high';
		ctx.drawImage( bg, view.ox, view.oy, iw * view.s, ih * view.s );
		drawOverlay( ctx, toScreen, true );
		// scale line
		if ( scalePts.length ) {
			ctx.strokeStyle = '#d97706'; ctx.fillStyle = '#d97706'; ctx.lineWidth = 3;
			var q = scalePts.map( toScreen );
			ctx.beginPath(); q.forEach( function ( p, k ) { k ? ctx.lineTo( p.x, p.y ) : ctx.moveTo( p.x, p.y ); } ); ctx.stroke();
			q.forEach( function ( p ) { ctx.beginPath(); ctx.arc( p.x, p.y, 5, 0, Math.PI * 2 ); ctx.fill(); } );
		}
	}

	/* ---------------------------------------------------------------- result */
	function segInter( p1, p2, p3, p4 ) {
		var d = ( p2.x - p1.x ) * ( p4.y - p3.y ) - ( p2.y - p1.y ) * ( p4.x - p3.x );
		if ( Math.abs( d ) < 1e-9 ) { return false; }
		var t = ( ( p3.x - p1.x ) * ( p4.y - p3.y ) - ( p3.y - p1.y ) * ( p4.x - p3.x ) ) / d, u = ( ( p3.x - p1.x ) * ( p2.y - p1.y ) - ( p3.y - p1.y ) * ( p2.x - p1.x ) ) / d;
		return t > 0.001 && t < 0.999 && u > 0.001 && u < 0.999;
	}
	function selfCross() {
		var n = pts.length, i, j;
		for ( i = 0; i < n; i++ ) { for ( j = i + 1; j < n; j++ ) {
			if ( Math.abs( i - j ) === 1 || ( i === 0 && j === n - 1 ) ) { continue; }
			if ( segInter( pts[ i ], pts[ ( i + 1 ) % n ], pts[ j ], pts[ ( j + 1 ) % n ] ) ) { return true; }
		} }
		return false;
	}
	function snapshot() {
		var max = 1100, k = Math.min( 1, max / iw ), c = document.createElement( 'canvas' );
		c.width = Math.round( iw * k ); c.height = Math.round( ih * k );
		var x = c.getContext( '2d' ); x.drawImage( bg, 0, 0, c.width, c.height );
		drawOverlay( x, function ( p ) { return { x: p.x * k, y: p.y * k }; }, false );
		return c.toDataURL( 'image/jpeg', 0.82 );
	}
	function steps() {
		if ( ! bg ) { stepEl.textContent = ''; return; }
		if ( mode === 'scale' ) { stepEl.textContent = ppf ? '✓ স্কেল ঠিক আছে। চাইলে নতুন দুই বিন্দু দিয়ে আবার ঠিক করুন, নইলে "২. জমি আঁকুন" চাপুন।' : 'ধাপ ১: নকশায় যে দুই বিন্দুর আসল দূরত্ব জানেন (যেমন একটি দাগের দৈর্ঘ্য বা রাস্তার প্রস্থ) তার দুই মাথায় ক্লিক করুন।'; }
		else if ( mode === 'draw' ) { stepEl.textContent = ppf ? ( closed ? '✓ জমি বন্ধ হয়েছে। বিন্দু টেনে সরিয়ে ঠিক করতে পারেন।' : 'ধাপ ২: জমির সীমানার প্রতিটি কোণায় ক্লিক করুন। শেষে লাল বিন্দুতে ক্লিক করুন বা "জমি বন্ধ করুন" চাপুন।' ) : 'আগে "১. স্কেল ঠিক করুন" ধাপটি করুন।'; }
		else { stepEl.textContent = 'নকশাটি আঙুল/মাউস দিয়ে টেনে সরান; মাউসের চাকা দিয়ে বড়-ছোট করুন।'; }
	}
	function update() {
		results.innerHTML = ''; LC.setMapCalc( null ); say( '' );
		steps();
		if ( ! ppf || pts.length < 3 || ! closed ) { return; }
		if ( selfCross() ) { say( 'রেখাগুলো একে অপরকে কেটেছে — বিন্দুগুলো ক্রম অনুযায়ী (ঘুরে ঘুরে) বসান বা টেনে ঠিক করুন।', true ); return; }
		var a = 0, per = 0, i, p, q;
		for ( i = 0; i < pts.length; i++ ) { p = pts[ i ]; q = pts[ ( i + 1 ) % pts.length ]; a += p.x * q.y - q.x * p.y; per += dist( p, q ); }
		var sqft = Math.abs( a ) / 2 / ( ppf * ppf ), perFt = per / ppf;
		var extra = [ [ 'জমির আকৃতি', pts.length + ' কোণা', false ], [ 'পরিসীমা (চারপাশের মাপ)', LC.format( Math.round( perFt * 10 ) / 10 ) + ' ফুট ≈ ' + LC.format( Math.round( perFt / FT_PER_M * 10 ) / 10 ) + ' মিটার', false ] ];
		var sides = pts.map( function ( p0, k ) { return [ 'বাহু ' + ( k + 1 ), LC.format( Math.round( ft( dist( p0, pts[ ( k + 1 ) % pts.length ] ) ) * 10 ) / 10 ) + ' ফুট', false ]; } );
		LC.renderList( results, sqft, 'sqft', extra.concat( [ [ 'বাহুর মাপ', '', 'head' ] ], sides, [ [ 'ক্ষেত্রফল', '', 'head' ] ] ) );
		LC.setMapCalc( {
			sqft: sqft, img: snapshot(),
			inputs: [ [ 'পদ্ধতি', 'নকশার ছবিতে স্কেল ঠিক করে বিন্দু বসিয়ে মাপা' ], [ 'কোণার সংখ্যা', String( pts.length ) ], [ 'পরিসীমা', LC.format( Math.round( perFt * 10 ) / 10 ) + ' ফুট' ] ],
			extra: sides.slice( 0, 12 )
		} );
	}

	/* ---------------------------------------------------------------- loading */
	function setBg( src, w, h ) { bg = src; iw = w; ih = h; pts = []; closed = false; scalePts = []; ppf = null; scaleBox.hidden = true; stage.hidden = false; tools.hidden = false; setMode( 'scale' ); layout(); update(); }
	function loadImage( file ) {
		var url = URL.createObjectURL( file ), im = new Image();
		im.onload = function () { setBg( im, im.naturalWidth, im.naturalHeight ); URL.revokeObjectURL( url ); };
		im.onerror = function () { say( 'ছবিটি পড়া যায়নি। JPG বা PNG ফাইল দিন।', true ); };
		im.src = url;
	}
	function loadScript( src ) { return new Promise( function ( ok, no ) { var s = document.createElement( 'script' ); s.src = src; s.onload = ok; s.onerror = no; document.head.appendChild( s ); } ); }
	function renderPdfPage( n ) {
		return pdfDoc.getPage( n ).then( function ( page ) {
			var v1 = page.getViewport( { scale: 1 } ), k = Math.min( 2.5, 2400 / Math.max( v1.width, v1.height ) ), vp = page.getViewport( { scale: k } );
			var c = document.createElement( 'canvas' ); c.width = Math.round( vp.width ); c.height = Math.round( vp.height );
			return page.render( { canvasContext: c.getContext( '2d' ), viewport: vp } ).promise.then( function () { setBg( c, c.width, c.height ); } );
		} );
	}
	function loadPdf( file ) {
		say( 'PDF পড়া হচ্ছে…' );
		var base = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/';
		( window.pdfjsLib ? Promise.resolve() : loadScript( base + 'pdf.min.js' ) ).then( function () {
			window.pdfjsLib.GlobalWorkerOptions.workerSrc = base + 'pdf.worker.min.js';
			return file.arrayBuffer();
		} ).then( function ( buf ) { return window.pdfjsLib.getDocument( { data: buf } ).promise; } ).then( function ( doc ) {
			pdfDoc = doc; pageEl.max = doc.numPages; pageEl.value = 1; pagesEl.textContent = '/ ' + doc.numPages; pdfBar.hidden = false;
			return renderPdfPage( 1 );
		} ).then( function () { say( '' ); } ).catch( function () { say( 'PDF পড়া যায়নি। নকশার একটি ছবি (স্ক্রিনশট/JPG) তুলে সেটি দিন।', true ); } );
	}
	fileEl.addEventListener( 'change', function () {
		var f = fileEl.files && fileEl.files[ 0 ]; if ( ! f ) { return; }
		pdfBar.hidden = true; pdfDoc = null; say( '' );
		if ( /pdf$/i.test( f.type ) || /\.pdf$/i.test( f.name ) ) { loadPdf( f ); } else { loadImage( f ); }
	} );
	pageEl.addEventListener( 'change', function () { var n = Math.max( 1, Math.min( pdfDoc ? pdfDoc.numPages : 1, parseInt( pageEl.value, 10 ) || 1 ) ); pageEl.value = n; if ( pdfDoc ) { renderPdfPage( n ); } } );

	/* ---------------------------------------------------------------- modes & buttons */
	function lockUi() {
		var b = panel.querySelector( '[data-lm-mode="draw"]' ); if ( ! b ) { return; }
		b.classList.toggle( 'is-locked', ! ppf ); b.setAttribute( 'aria-disabled', ppf ? 'false' : 'true' );
		b.title = ppf ? '' : 'আগে স্কেল ঠিক করুন';
	}
	function setMode( m ) {
		if ( m === 'draw' && ! ppf ) { m = 'scale'; say( 'আগে স্কেল ঠিক করতে হবে — নকশার জানা দূরত্বের দুই মাথায় ক্লিক করে দূরত্ব লিখুন। স্কেল ছাড়া জমি আঁকা যাবে না।', true ); }
		mode = m;
		lockUi();
		panel.querySelectorAll( '[data-lm-mode]' ).forEach( function ( b ) { b.classList.toggle( 'is-on', b.getAttribute( 'data-lm-mode' ) === m ); } );
		canvas.style.cursor = m === 'pan' ? 'grab' : 'crosshair';
		steps();
	}
	panel.addEventListener( 'click', function ( e ) {
		var b = e.target.closest( 'button' ); if ( ! b ) { return; }
		var m = b.getAttribute( 'data-lm-mode' ), z = b.getAttribute( 'data-lm-zoom' );
		if ( m ) { setMode( m ); }
		else if ( z ) { if ( z === 'fit' ) { fit(); draw(); } else { zoomAt( cw / 2, ch / 2, z === 'in' ? 1.4 : 1 / 1.4 ); } }
		else if ( b.hasAttribute( 'data-lm-undo' ) ) { if ( closed ) { closed = false; } else { pts.pop(); } draw(); update(); }
		else if ( b.hasAttribute( 'data-lm-close' ) ) { if ( ! ppf ) { say( 'আগে স্কেল ঠিক করুন।', true ); return; } if ( pts.length >= 3 ) { closed = true; draw(); update(); } else { say( 'জমি বন্ধ করতে অন্তত ৩টি কোণা লাগে।', true ); } }
		else if ( b.hasAttribute( 'data-lm-clear' ) ) { pts = []; closed = false; draw(); update(); }
		else if ( b.hasAttribute( 'data-lm-setscale' ) ) {
			var d = LC.parseNumber( distEl.value );
			if ( scalePts.length < 2 ) { say( 'আগে নকশায় দুটি বিন্দুতে ক্লিক করুন।', true ); return; }
			if ( d === null || isNaN( d ) || d <= 0 ) { say( 'দূরত্ব সংখ্যায় লিখুন, যেমন 66', true ); return; }
			ppf = dist( scalePts[ 0 ], scalePts[ 1 ] ) / ( d * ( dunitEl.value === 'm' ? FT_PER_M : 1 ) );
			scaleBox.hidden = true; scalePts = []; say( '' ); setMode( 'draw' ); draw(); update();
		}
	} );

	/* ---------------------------------------------------------------- pointer */
	function pos( e ) { var r = canvas.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; }
	function hit( x, y ) { for ( var i = 0; i < pts.length; i++ ) { var q = toScreen( pts[ i ] ); if ( Math.hypot( q.x - x, q.y - y ) <= 11 ) { return i; } } return -1; }
	canvas.addEventListener( 'pointerdown', function ( e ) {
		if ( ! bg ) { return; }
		var p = pos( e ); canvas.setPointerCapture( e.pointerId );
		if ( mode === 'pan' || e.button === 1 ) { drag = { kind: 'pan', x: p.x, y: p.y, ox: view.ox, oy: view.oy }; canvas.style.cursor = 'grabbing'; return; }
		if ( mode === 'scale' ) {
			if ( scalePts.length >= 2 ) { scalePts = []; }
			scalePts.push( toImage( p.x, p.y ) ); scaleBox.hidden = scalePts.length < 2; if ( scalePts.length === 2 ) { distEl.focus(); } draw(); return;
		}
		if ( ! ppf ) { setMode( 'scale' ); draw(); return; }
		var h = hit( p.x, p.y );
		if ( h === 0 && ! closed && pts.length >= 3 ) { closed = true; draw(); update(); return; }
		if ( h >= 0 ) { drag = { kind: 'pt', i: h }; return; }
		if ( closed ) { return; }
		pts.push( toImage( p.x, p.y ) ); draw(); update();
	} );
	canvas.addEventListener( 'pointermove', function ( e ) {
		if ( ! drag ) { return; }
		var p = pos( e );
		if ( drag.kind === 'pan' ) { view.ox = drag.ox + p.x - drag.x; view.oy = drag.oy + p.y - drag.y; draw(); }
		else { pts[ drag.i ] = toImage( p.x, p.y ); draw(); }
	} );
	function endDrag() { if ( drag && drag.kind === 'pt' ) { update(); } drag = null; canvas.style.cursor = mode === 'pan' ? 'grab' : 'crosshair'; }
	canvas.addEventListener( 'pointerup', endDrag );
	canvas.addEventListener( 'pointercancel', endDrag );
	canvas.addEventListener( 'wheel', function ( e ) { if ( ! bg ) { return; } e.preventDefault(); var p = pos( e ); zoomAt( p.x, p.y, e.deltaY < 0 ? 1.15 : 1 / 1.15 ); }, { passive: false } );
	canvas.style.touchAction = 'none';

	window.addEventListener( 'resize', function () { if ( ! panel.hidden ) { layout(); } } );
	document.addEventListener( 'click', function ( e ) { if ( e.target.closest && e.target.closest( '[data-lc-tab="map"]' ) ) { setTimeout( layout, 30 ); } } );
	window.sojib68LandMap = { debug: function () { return { pts: pts, ppf: ppf, closed: closed, view: view }; } };
}() );

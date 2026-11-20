/**
 * "নকশা থেকে মাপ": load a map image / PDF, calibrate the scale from one known distance, then trace one or
 * more dags (corner by corner) and read the area, sides and perimeter. Works on a big screen (full-screen
 * workspace, magnifier, snapping, keyboard shortcuts). Everything happens in the browser — nothing is uploaded.
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
	var summaryEl = $( '[data-lm-summary]' ), labelsEl = $( '[data-lm-labels]' ), dagsEl = $( '[data-lm-dags]' ), actionsEl = $( '[data-lm-actions]' ), statusEl = $( '[data-lm-status]' ), snapEl = $( '[data-lm-snap]' ), fullBtn = $( '[data-lm-full]' );
	var ctx = canvas.getContext( '2d' );

	var bg = null, iw = 0, ih = 0;                       // background (image or rendered PDF page)
	var view = { s: 1, ox: 0, oy: 0 };
	var dags = [ { name: 'দাগ 1', pts: [], closed: false } ], cur = 0;
	var scalePts = [], ppf = null;                       // ppf = image pixels per foot
	var dists = [], pendingDist = null;                   // free distance measurements
	var mode = 'scale', drag = null, pdfDoc = null, cw = 0, ch = 0, dpr = 1, mouse = null, space = false, full = false, holder = null;
	var FT_PER_M = 3.28084;

	/* ---------------------------------------------------------------- helpers */
	var P = function () { return dags[ cur ]; };
	function say( t, bad ) { msg.textContent = t || ''; msg.hidden = ! t; msg.classList.toggle( 'is-bad', !! bad ); }
	function toScreen( p ) { return { x: p.x * view.s + view.ox, y: p.y * view.s + view.oy }; }
	function toImage( x, y ) { return { x: ( x - view.ox ) / view.s, y: ( y - view.oy ) / view.s }; }
	function dist( a, b ) { return Math.hypot( a.x - b.x, a.y - b.y ); }
	function feet( px ) { return px / ppf; }
	function ftIn( f ) { var t = Math.round( f * 12 ), a = Math.floor( t / 12 ), i = t % 12; return a + "' " + i + '"'; }
	function n1( v ) { return LC.format( Math.round( v * 10 ) / 10 ); }
	function shat( sqft ) { return Math.round( sqft / 435.6 * 1000 ) / 1000; }
	function areaOf( pts ) { var a = 0, i, p, q; for ( i = 0; i < pts.length; i++ ) { p = pts[ i ]; q = pts[ ( i + 1 ) % pts.length ]; a += p.x * q.y - q.x * p.y; } return Math.abs( a ) / 2 / ( ppf * ppf ); }
	function perimOf( pts, closedFlag ) { var s = 0, n = closedFlag ? pts.length : pts.length - 1, i; for ( i = 0; i < n; i++ ) { s += dist( pts[ i ], pts[ ( i + 1 ) % pts.length ] ); } return s / ppf; }

	function layout() {
		var w = stage.clientWidth || 480;
		dpr = window.devicePixelRatio || 1;
		cw = w;
		ch = full ? Math.max( 320, stage.clientHeight || ( window.innerHeight - 260 ) ) : Math.max( 280, Math.min( 460, Math.round( w * 0.75 ) ) );
		canvas.style.width = cw + 'px'; canvas.style.height = ch + 'px';
		canvas.width = Math.round( cw * dpr ); canvas.height = Math.round( ch * dpr );
		if ( bg ) { fit(); }
		draw();
	}
	function fit() { if ( ! bg ) { return; } view.s = Math.min( cw / iw, ch / ih ) * 0.98; view.ox = ( cw - iw * view.s ) / 2; view.oy = ( ch - ih * view.s ) / 2; }
	function zoomAt( cx, cy, f ) { var b = toImage( cx, cy ), s = Math.max( 0.05, Math.min( 60, view.s * f ) ); view.s = s; view.ox = cx - b.x * s; view.oy = cy - b.y * s; draw(); }

	/* ---------------------------------------------------------------- drawing */
	function polyPath( c, list, tx, closedFlag ) { c.beginPath(); list.forEach( function ( p, k ) { var q = tx( p ); k ? c.lineTo( q.x, q.y ) : c.moveTo( q.x, q.y ); } ); if ( closedFlag && list.length > 2 ) { c.closePath(); } }
	function label( c, text, x, y, size, color ) {
		c.font = '600 ' + size + 'px sans-serif'; c.textAlign = 'center'; c.textBaseline = 'middle';
		var w = c.measureText( text ).width + 8; c.fillStyle = 'rgba(255,255,255,.9)'; c.fillRect( x - w / 2, y - size * 0.7, w, size * 1.4 ); c.fillStyle = color; c.fillText( text, x, y );
	}
	function drawDags( c, tx, ui, size ) {
		dags.forEach( function ( d, di ) {
			var list = d.pts, on = di === cur, i;
			if ( ! list.length ) { return; }
			if ( list.length >= 3 && d.closed ) { polyPath( c, list, tx, true ); c.fillStyle = on ? 'rgba(25,128,48,.22)' : 'rgba(25,128,48,.12)'; c.fill(); }
			c.lineWidth = on ? 2.2 : 1.6; c.strokeStyle = on ? '#0f6023' : '#4b8a5a'; c.lineJoin = 'round'; polyPath( c, list, tx, d.closed ); c.stroke();
			if ( ui ) {
				list.forEach( function ( p, k ) { var q = tx( p ); c.beginPath(); c.arc( q.x, q.y, k === 0 && ! d.closed && on ? 7 : 4.5, 0, Math.PI * 2 ); c.fillStyle = k === 0 && on ? '#c0392b' : '#198030'; c.fill(); c.strokeStyle = '#fff'; c.lineWidth = 2; c.stroke(); } );
			}
		} );
		drawLabels( c, tx, size );
		dists.forEach( function ( d ) { drawDist( c, tx, d[ 0 ], d[ 1 ], size ); } );
		if ( pendingDist ) { drawDist( c, tx, pendingDist, mouse ? toImage( mouse.x, mouse.y ) : pendingDist, size ); }
	}
	/* side lengths: only the long sides by default, pushed outside the shape, never on top of each other */
	function drawLabels( c, tx, size ) {
		var rects = [], modeL = labelsEl ? labelsEl.value : 'long';
		function free( x, y, w, h ) { for ( var i = 0; i < rects.length; i++ ) { var r = rects[ i ]; if ( x < r[ 0 ] + r[ 2 ] && x + w > r[ 0 ] && y < r[ 1 ] + r[ 3 ] && y + h > r[ 1 ] ) { return false; } } return true; }
		function put( text, x, y, color, force ) {
			c.font = '600 ' + size + 'px sans-serif'; var w = c.measureText( text ).width + 8, h = size * 1.4;
			if ( ! force && ! free( x - w / 2, y - h / 2, w, h ) ) { return false; }
			rects.push( [ x - w / 2, y - h / 2, w, h ] ); label( c, text, x, y, size, color ); return true;
		}
		dags.forEach( function ( d ) {
			var list = d.pts; if ( ! ppf || list.length < 2 ) { return; }
			var cx = 0, cy = 0; list.forEach( function ( p ) { var q = tx( p ); cx += q.x; cy += q.y; } ); cx /= list.length; cy /= list.length;
			var n = d.closed ? list.length : list.length - 1, i;
			for ( i = 0; i < n && modeL !== 'none'; i++ ) {
				var a = tx( list[ i ] ), b = tx( list[ ( i + 1 ) % list.length ] ), len = Math.hypot( b.x - a.x, b.y - a.y );
				if ( modeL === 'long' && len < size * 5 ) { continue; }
				var mx = ( a.x + b.x ) / 2, my = ( a.y + b.y ) / 2, nx = -( b.y - a.y ) / ( len || 1 ), ny = ( b.x - a.x ) / ( len || 1 );
				if ( ( mx - cx ) * nx + ( my - cy ) * ny < 0 ) { nx = -nx; ny = -ny; }
				put( ftIn( feet( dist( list[ i ], list[ ( i + 1 ) % list.length ] ) ) ), mx + nx * size * 1.2, my + ny * size * 1.2, '#0f6023', false );
			}
		} );
		dags.forEach( function ( d ) {
			if ( d.closed && d.pts.length >= 3 && ppf ) {
				var cx = 0, cy = 0; d.pts.forEach( function ( p ) { cx += p.x; cy += p.y; } ); var q = tx( { x: cx / d.pts.length, y: cy / d.pts.length } );
				put( d.name + ' · ' + shat( areaOf( d.pts ) ) + ' শতাংশ', q.x, q.y, '#14532d', true );
			}
		} );
	}
	function drawDist( c, tx, a, b, size ) {
		var qa = tx( a ), qb = tx( b ); c.strokeStyle = '#2563eb'; c.lineWidth = 2; c.setLineDash( [ 6, 4 ] ); c.beginPath(); c.moveTo( qa.x, qa.y ); c.lineTo( qb.x, qb.y ); c.stroke(); c.setLineDash( [] );
		if ( ppf ) { label( c, ftIn( feet( dist( a, b ) ) ), ( qa.x + qb.x ) / 2, ( qa.y + qb.y ) / 2 - size, size, '#1d4ed8' ); }
	}
	function drawLoupe() {
		if ( ! mouse || ! bg || mode === 'pan' || space || drag ) { return; }
		var R = 62, Z = 3, lx = mouse.x + R + 26, ly = mouse.y - R - 18;
		if ( lx + R > cw ) { lx = mouse.x - R - 26; } if ( ly - R < 0 ) { ly = mouse.y + R + 18; }
		lx = Math.max( R + 2, Math.min( cw - R - 2, lx ) ); ly = Math.max( R + 2, Math.min( ch - R - 2, ly ) );
		var ip = toImage( mouse.x, mouse.y ), S = view.s * Z, ox = lx - ip.x * S, oy = ly - ip.y * S;
		ctx.save(); ctx.beginPath(); ctx.arc( lx, ly, R, 0, Math.PI * 2 ); ctx.clip(); ctx.fillStyle = '#fff'; ctx.fillRect( lx - R, ly - R, R * 2, R * 2 );
		ctx.imageSmoothingEnabled = false; ctx.drawImage( bg, ox, oy, iw * S, ih * S );
		drawDags( ctx, function ( p ) { return { x: p.x * S + ox, y: p.y * S + oy }; }, false, 11 );
		ctx.restore();
		ctx.beginPath(); ctx.arc( lx, ly, R, 0, Math.PI * 2 ); ctx.lineWidth = 3; ctx.strokeStyle = '#0f6023'; ctx.stroke();
		ctx.strokeStyle = 'rgba(192,57,43,.95)'; ctx.lineWidth = 1.2; ctx.beginPath(); ctx.moveTo( lx - R, ly ); ctx.lineTo( lx + R, ly ); ctx.moveTo( lx, ly - R ); ctx.lineTo( lx, ly + R ); ctx.stroke();
	}
	function draw() {
		ctx.setTransform( dpr, 0, 0, dpr, 0, 0 );
		ctx.fillStyle = '#e9ece8'; ctx.fillRect( 0, 0, cw, ch );
		if ( ! bg ) { return; }
		ctx.imageSmoothingEnabled = true; ctx.imageSmoothingQuality = 'high';
		ctx.drawImage( bg, view.ox, view.oy, iw * view.s, ih * view.s );
		drawDags( ctx, toScreen, true, 12 );
		if ( scalePts.length ) {
			ctx.strokeStyle = '#d97706'; ctx.fillStyle = '#d97706'; ctx.lineWidth = 3; var q = scalePts.map( toScreen );
			ctx.beginPath(); q.forEach( function ( p, k ) { k ? ctx.lineTo( p.x, p.y ) : ctx.moveTo( p.x, p.y ); } ); ctx.stroke();
			q.forEach( function ( p ) { ctx.beginPath(); ctx.arc( p.x, p.y, 5, 0, Math.PI * 2 ); ctx.fill(); } );
		}
		drawLoupe();
	}

	/* ---------------------------------------------------------------- geometry checks */
	function segInter( p1, p2, p3, p4 ) {
		var d = ( p2.x - p1.x ) * ( p4.y - p3.y ) - ( p2.y - p1.y ) * ( p4.x - p3.x ); if ( Math.abs( d ) < 1e-9 ) { return false; }
		var t = ( ( p3.x - p1.x ) * ( p4.y - p3.y ) - ( p3.y - p1.y ) * ( p4.x - p3.x ) ) / d, u = ( ( p3.x - p1.x ) * ( p2.y - p1.y ) - ( p3.y - p1.y ) * ( p2.x - p1.x ) ) / d;
		return t > 0.001 && t < 0.999 && u > 0.001 && u < 0.999;
	}
	function selfCross( pts ) {
		var n = pts.length, i, j;
		for ( i = 0; i < n; i++ ) { for ( j = i + 1; j < n; j++ ) { if ( Math.abs( i - j ) === 1 || ( i === 0 && j === n - 1 ) ) { continue; } if ( segInter( pts[ i ], pts[ ( i + 1 ) % n ], pts[ j ], pts[ ( j + 1 ) % n ] ) ) { return true; } } }
		return false;
	}
	function snapshot() {
		var k = Math.min( 1, 1200 / iw ), c = document.createElement( 'canvas' ); c.width = Math.round( iw * k ); c.height = Math.round( ih * k );
		var x = c.getContext( '2d' ); x.drawImage( bg, 0, 0, c.width, c.height ); drawDags( x, function ( p ) { return { x: p.x * k, y: p.y * k }; }, false, 22 ); return c.toDataURL( 'image/jpeg', 0.82 );
	}

	/* ---------------------------------------------------------------- panels: steps, dag chips, status, result */
	function steps() {
		if ( ! bg ) { stepEl.textContent = ''; return; }
		var t = '';
		if ( mode === 'scale' ) { t = ppf ? '✓ স্কেল ঠিক আছে। নতুন দুই বিন্দু দিয়ে আবার ঠিক করা যায়, নইলে "দাগ আঁকুন" চাপুন।' : 'ধাপ ১: নকশায় যে দুই বিন্দুর আসল দূরত্ব জানেন (যেমন একটি দাগের দৈর্ঘ্য বা রাস্তার প্রস্থ) তার দুই মাথায় ক্লিক করুন।'; }
		else if ( mode === 'draw' ) { t = P().closed ? '✓ "' + P().name + '" বন্ধ হয়েছে। বিন্দু টেনে সরিয়ে ঠিক করুন, বা "+ নতুন দাগ" চাপুন।' : 'ধাপ ২: "' + P().name + '"-এর সীমানার প্রতিটি কোণায় ক্লিক করুন। শেষে লাল বিন্দুতে ক্লিক করুন বা Enter চাপুন।'; }
		else if ( mode === 'dist' ) { t = ppf ? 'দুই বিন্দুতে ক্লিক করলে মাঝের দূরত্ব দেখাবে (জমির ক্ষেত্রফলে যোগ হয় না)।' : 'আগে স্কেল ঠিক করুন।'; }
		else { t = 'নকশা টেনে সরান; মাউসের চাকা দিয়ে বড়-ছোট করুন (Space চেপে ধরেও সরানো যায়)।'; }
		stepEl.textContent = t;
	}
	function lockUi() {
		panel.querySelectorAll( '[data-lm-mode="draw"], [data-lm-mode="dist"]' ).forEach( function ( b ) { b.classList.toggle( 'is-locked', ! ppf ); b.setAttribute( 'aria-disabled', ppf ? 'false' : 'true' ); b.title = ppf ? '' : 'আগে স্কেল ঠিক করুন'; } );
	}
	function chips() {
		if ( ! bg ) { dagsEl.hidden = true; return; }
		dagsEl.hidden = false;
		dagsEl.innerHTML = dags.map( function ( d, i ) {
			var a = ppf && d.closed && d.pts.length >= 3 ? ' · ' + shat( areaOf( d.pts ) ) + ' শতাংশ' : '';
			return '<span class="lm-chip' + ( i === cur ? ' is-on' : '' ) + '" data-lm-dag="' + i + '">' + d.name.replace( /</g, '&lt;' ) + a + ' <button type="button" data-lm-dagdel="' + i + '" aria-label="মুছুন">×</button></span>';
		} ).join( '' ) + '<span class="lm-chip-hint">দাগের নাম বদলাতে দুবার ক্লিক করুন</span>';
	}
	function statusBar() {
		if ( ! bg ) { statusEl.hidden = true; return; }
		statusEl.hidden = false;
		var d = P(), t = d.name + ': ' + d.pts.length + ' কোণ';
		if ( ppf && d.pts.length >= 2 ) { t += ' · পরিসীমা ' + ftIn( perimOf( d.pts, d.closed ) ); }
		if ( ppf && d.closed && d.pts.length >= 3 ) { var s = areaOf( d.pts ); t += ' · ' + shat( s ) + ' শতাংশ · ' + n1( s ) + ' বর্গফুট'; }
		var closedAll = dags.filter( function ( x ) { return x.closed && x.pts.length >= 3; } );
		if ( ppf && closedAll.length > 1 ) { var tot = closedAll.reduce( function ( a, x ) { return a + areaOf( x.pts ); }, 0 ); t += '  |  মোট ' + closedAll.length + 'টি দাগ: ' + shat( tot ) + ' শতাংশ (' + n1( tot ) + ' বর্গফুট)'; }
		statusEl.textContent = t;
		actionsEl.hidden = ! ppf;
	}
	function update() {
		results.innerHTML = ''; summaryEl.innerHTML = ''; LC.setMapCalc( null ); say( '' );
		steps(); lockUi(); chips(); statusBar();
		var done = dags.filter( function ( d ) { return d.closed && d.pts.length >= 3; } );
		if ( ! ppf || ! done.length ) { return; }
		for ( var i = 0; i < done.length; i++ ) { if ( selfCross( done[ i ].pts ) ) { say( '"' + done[ i ].name + '"-এর রেখাগুলো একে অপরকে কেটেছে — বিন্দুগুলো ক্রম অনুযায়ী (ঘুরে ঘুরে) বসান বা টেনে ঠিক করুন।', true ); return; } }
		var total = 0, extra = [], inputs = [ [ 'পদ্ধতি', 'নকশার ছবিতে স্কেল ঠিক করে বিন্দু বসিয়ে মাপা' ], [ 'দাগের সংখ্যা', String( done.length ) ] ], html = '';
		function cardHtml( title, sqft, pts ) {
			var per = perimOf( pts, true ), sides = pts.map( function ( p, k ) { var l = feet( dist( p, pts[ ( k + 1 ) % pts.length ] ) ); return [ k + 1, l ]; } );
			var rows = sides.map( function ( r ) { return '<tr><td>' + r[ 0 ] + '</td><td>' + ftIn( r[ 1 ] ) + '</td><td>' + n1( r[ 1 ] ) + '</td><td>' + n1( r[ 1 ] / FT_PER_M ) + '</td></tr>'; } ).join( '' );
			return '<div class="lm-card"><div class="lm-card__t">' + title.replace( /</g, '&lt;' ) + '</div><div class="lm-big">' + shat( sqft ) + ' <small>শতাংশ</small></div>' +
				'<div class="lm-sub">' + n1( sqft ) + ' বর্গফুট · ' + LC.format( sqft / 720 ) + ' কাঠা · ' + LC.format( sqft / 14400 ) + ' বিঘা · ' + LC.format( sqft / 43560 ) + ' একর</div>' +
				'<div class="lm-sub2">' + pts.length + ' কোণা · পরিসীমা ' + ftIn( per ) + ' (' + n1( per ) + ' ফুট ≈ ' + n1( per / FT_PER_M ) + ' মিটার)</div>' +
				'<details class="lm-det"><summary>প্রতিটি বাহুর মাপ দেখুন (' + pts.length + 'টি)</summary><table class="lm-tab"><thead><tr><th>বাহু</th><th>ফুট-ইঞ্চি</th><th>ফুট</th><th>মিটার</th></tr></thead><tbody>' + rows + '</tbody></table></details></div>';
		}
		done.forEach( function ( d ) {
			var a = areaOf( d.pts ); total += a; html += cardHtml( d.name, a, d.pts );
			extra.push( [ d.name, shat( a ) + ' শতাংশ · ' + n1( a ) + ' বর্গফুট', false ] );
			extra.push( [ d.name + ' — পরিসীমা', ftIn( perimOf( d.pts, true ) ), false ] );
			if ( done.length === 1 ) { d.pts.slice( 0, 12 ).forEach( function ( p, k ) { var l = feet( dist( p, d.pts[ ( k + 1 ) % d.pts.length ] ) ); extra.push( [ 'বাহু ' + ( k + 1 ), ftIn( l ) + '  (' + n1( l ) + ' ফুট)', false ] ); } ); }
		} );
		if ( done.length > 1 ) { html = '<div class="lm-card lm-card--total"><div class="lm-card__t">মোট ' + done.length + 'টি দাগ</div><div class="lm-big">' + shat( total ) + ' <small>শতাংশ</small></div><div class="lm-sub">' + n1( total ) + ' বর্গফুট</div></div>' + html; }
		summaryEl.innerHTML = html;
		extra.push( [ 'সব এককে ' + ( done.length > 1 ? 'মোট ' : '' ) + 'ক্ষেত্রফল', '', 'head' ] );
		LC.renderList( results, total, 'sqft', [ [ 'সব এককে ' + ( done.length > 1 ? 'মোট ' : '' ) + 'ক্ষেত্রফল', '', 'head' ] ] );
		LC.setMapCalc( { sqft: total, img: snapshot(), inputs: inputs, extra: extra.filter( function ( r ) { return r[ 2 ] !== 'head'; } ).slice( 0, 16 ) } );
	}

	/* ---------------------------------------------------------------- loading */
	function setBg( src, w, h ) {
		bg = src; iw = w; ih = h; dags = [ { name: 'দাগ 1', pts: [], closed: false } ]; cur = 0; scalePts = []; ppf = null; dists = []; pendingDist = null;
		scaleBox.hidden = true; stage.hidden = false; tools.hidden = false; setMode( 'scale' ); layout(); update();
	}
	function loadImage( file ) { var url = URL.createObjectURL( file ), im = new Image(); im.onload = function () { setBg( im, im.naturalWidth, im.naturalHeight ); URL.revokeObjectURL( url ); }; im.onerror = function () { say( 'ছবিটি পড়া যায়নি। JPG বা PNG ফাইল দিন।', true ); }; im.src = url; }
	function loadScript( src ) { return new Promise( function ( ok, no ) { var s = document.createElement( 'script' ); s.src = src; s.onload = ok; s.onerror = no; document.head.appendChild( s ); } ); }
	function renderPdfPage( n ) {
		return pdfDoc.getPage( n ).then( function ( page ) {
			var v1 = page.getViewport( { scale: 1 } ), k = Math.min( 3, 3200 / Math.max( v1.width, v1.height ) ), vp = page.getViewport( { scale: k } );
			var c = document.createElement( 'canvas' ); c.width = Math.round( vp.width ); c.height = Math.round( vp.height );
			return page.render( { canvasContext: c.getContext( '2d' ), viewport: vp } ).promise.then( function () { setBg( c, c.width, c.height ); } );
		} );
	}
	function loadPdf( file ) {
		say( 'PDF পড়া হচ্ছে…' ); var base = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/';
		( window.pdfjsLib ? Promise.resolve() : loadScript( base + 'pdf.min.js' ) ).then( function () { window.pdfjsLib.GlobalWorkerOptions.workerSrc = base + 'pdf.worker.min.js'; return file.arrayBuffer(); } )
			.then( function ( buf ) { return window.pdfjsLib.getDocument( { data: buf } ).promise; } )
			.then( function ( doc ) { pdfDoc = doc; pageEl.max = doc.numPages; pageEl.value = 1; pagesEl.textContent = '/ ' + doc.numPages; pdfBar.hidden = false; return renderPdfPage( 1 ); } )
			.then( function () { say( '' ); } ).catch( function () { say( 'PDF পড়া যায়নি। নকশার একটি ছবি (স্ক্রিনশট/JPG) তুলে সেটি দিন।', true ); } );
	}
	fileEl.addEventListener( 'change', function () { var f = fileEl.files && fileEl.files[ 0 ]; if ( ! f ) { return; } pdfBar.hidden = true; pdfDoc = null; say( '' ); if ( /pdf$/i.test( f.type ) || /\.pdf$/i.test( f.name ) ) { loadPdf( f ); } else { loadImage( f ); } } );
	pageEl.addEventListener( 'change', function () { var n = Math.max( 1, Math.min( pdfDoc ? pdfDoc.numPages : 1, parseInt( pageEl.value, 10 ) || 1 ) ); pageEl.value = n; if ( pdfDoc ) { renderPdfPage( n ); } } );

	/* ---------------------------------------------------------------- modes, full screen, buttons */
	function setMode( m ) {
		if ( ( m === 'draw' || m === 'dist' ) && ! ppf ) { m = 'scale'; say( 'আগে স্কেল ঠিক করতে হবে — নকশার জানা দূরত্বের দুই মাথায় ক্লিক করে দূরত্ব লিখুন। স্কেল ছাড়া মাপা যাবে না।', true ); }
		if ( m !== 'dist' ) { dists = []; }
		mode = m; pendingDist = null;
		panel.querySelectorAll( '[data-lm-mode]' ).forEach( function ( b ) { b.classList.toggle( 'is-on', b.getAttribute( 'data-lm-mode' ) === m ); } );
		canvas.style.cursor = m === 'pan' ? 'grab' : 'crosshair'; steps(); lockUi(); draw();
	}
	function setFull( on ) {
		if ( on === full ) { return; }
		full = on;
		if ( on ) { holder = document.createComment( 'lm' ); panel.parentNode.insertBefore( holder, panel ); document.body.appendChild( panel ); panel.classList.add( 'lm-full' ); panel.hidden = false; document.body.classList.add( 'lm-noscroll' ); fullBtn.textContent = '✕ বড় পর্দা বন্ধ'; }
		else { if ( holder && holder.parentNode ) { holder.parentNode.insertBefore( panel, holder ); holder.remove(); } panel.classList.remove( 'lm-full' ); document.body.classList.remove( 'lm-noscroll' ); fullBtn.textContent = '⛶ বড় পর্দা'; }
		setTimeout( layout, 30 );
	}
	function closeDag() { var d = P(); if ( d.pts.length < 3 ) { say( 'জমি বন্ধ করতে অন্তত ৩টি কোণা লাগে।', true ); return; } d.closed = true; draw(); update(); }
	function undo() { var d = P(); if ( d.closed ) { d.closed = false; } else { d.pts.pop(); } draw(); update(); }

	panel.addEventListener( 'click', function ( e ) {
		var chip = e.target.closest( '[data-lm-dag]' ), del = e.target.closest( '[data-lm-dagdel]' );
		if ( del ) { var di = +del.getAttribute( 'data-lm-dagdel' ); if ( dags.length > 1 ) { dags.splice( di, 1 ); cur = Math.min( cur, dags.length - 1 ); } else { dags = [ { name: 'দাগ 1', pts: [], closed: false } ]; cur = 0; } draw(); update(); return; }
		if ( chip ) { cur = +chip.getAttribute( 'data-lm-dag' ); setMode( 'draw' ); draw(); update(); return; }
		var b = e.target.closest( 'button' ); if ( ! b ) { return; }
		var m = b.getAttribute( 'data-lm-mode' ), z = b.getAttribute( 'data-lm-zoom' );
		if ( m ) { setMode( m ); }
		else if ( z ) { if ( z === 'fit' ) { fit(); draw(); } else { zoomAt( cw / 2, ch / 2, z === 'in' ? 1.4 : 1 / 1.4 ); } }
		else if ( b.hasAttribute( 'data-lm-full' ) ) { setFull( ! full ); }
		else if ( b.hasAttribute( 'data-lm-undo' ) ) { undo(); }
		else if ( b.hasAttribute( 'data-lm-close' ) ) { closeDag(); }
		else if ( b.hasAttribute( 'data-lm-newdag' ) ) { if ( ! P().pts.length ) { say( 'এই দাগেই আগে বিন্দু বসান।', true ); return; } if ( ! P().closed ) { closeDag(); if ( ! P().closed ) { return; } } dags.push( { name: 'দাগ ' + ( dags.length + 1 ), pts: [], closed: false } ); cur = dags.length - 1; setMode( 'draw' ); update(); draw(); }
		else if ( b.hasAttribute( 'data-lm-clear' ) ) { P().pts = []; P().closed = false; draw(); update(); }
		else if ( b.hasAttribute( 'data-lm-setscale' ) ) {
			var d = LC.parseNumber( distEl.value );
			if ( scalePts.length < 2 ) { say( 'আগে নকশায় দুটি বিন্দুতে ক্লিক করুন।', true ); return; }
			if ( d === null || isNaN( d ) || d <= 0 ) { say( 'দূরত্ব সংখ্যায় লিখুন, যেমন 66', true ); return; }
			var old = ppf; ppf = dist( scalePts[ 0 ], scalePts[ 1 ] ) / ( d * ( dunitEl.value === 'm' ? FT_PER_M : 1 ) );
			scaleBox.hidden = true; scalePts = []; say( '' ); setMode( 'draw' ); draw(); update(); if ( old ) { say( 'স্কেল বদলানো হয়েছে — আগের সব মাপ নতুন স্কেলে হিসাব হয়েছে।' ); }
		}
	} );
	if ( labelsEl ) { labelsEl.addEventListener( 'change', function () { draw(); } ); }
	dagsEl.addEventListener( 'dblclick', function ( e ) { var chip = e.target.closest( '[data-lm-dag]' ); if ( ! chip ) { return; } var i = +chip.getAttribute( 'data-lm-dag' ), n = window.prompt( 'দাগের নাম বা নম্বর (যেমন: ৩৭৫ নং দাগ):', dags[ i ].name ); if ( n && n.trim() ) { dags[ i ].name = n.trim().slice( 0, 40 ); draw(); update(); } } );

	/* ---------------------------------------------------------------- pointer */
	function pos( e ) { var r = canvas.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; }
	function hitVertex( x, y ) { var d = P(), i; for ( i = 0; i < d.pts.length; i++ ) { var q = toScreen( d.pts[ i ] ); if ( Math.hypot( q.x - x, q.y - y ) <= 11 ) { return i; } } return -1; }
	function snapPoint( x, y, skip ) {
		var p = toImage( x, y ); if ( ! snapEl.checked ) { return p; }
		var best = null, bd = 11, di, k;
		for ( di = 0; di < dags.length; di++ ) { for ( k = 0; k < dags[ di ].pts.length; k++ ) { if ( skip && skip.d === di && skip.k === k ) { continue; } var q = toScreen( dags[ di ].pts[ k ] ), dd = Math.hypot( q.x - x, q.y - y ); if ( dd < bd ) { bd = dd; best = dags[ di ].pts[ k ]; } } }
		return best ? { x: best.x, y: best.y } : p;
	}
	/* a click places a point; dragging on empty map moves the map (so the map can always be moved with the mouse) */
	function clickAction( p ) {
		if ( mode === 'scale' ) { if ( scalePts.length >= 2 ) { scalePts = []; } scalePts.push( toImage( p.x, p.y ) ); scaleBox.hidden = scalePts.length < 2; if ( scalePts.length === 2 ) { distEl.focus(); } draw(); return; }
		if ( ! ppf ) { setMode( 'scale' ); return; }
		if ( mode === 'dist' ) { var sp = snapPoint( p.x, p.y ); if ( ! pendingDist ) { pendingDist = sp; } else { dists = [ [ pendingDist, sp ] ]; say( 'দূরত্ব: ' + ftIn( feet( dist( pendingDist, sp ) ) ) + ' (' + n1( feet( dist( pendingDist, sp ) ) ) + ' ফুট ≈ ' + n1( feet( dist( pendingDist, sp ) ) / FT_PER_M ) + ' মিটার)' ); pendingDist = null; } draw(); return; }
		var d = P(), h = hitVertex( p.x, p.y );
		if ( h === 0 && ! d.closed && d.pts.length >= 3 ) { closeDag(); return; }
		if ( d.closed ) { return; }
		d.pts.push( snapPoint( p.x, p.y ) ); draw(); update();
	}
	canvas.addEventListener( 'pointerdown', function ( e ) {
		if ( ! bg ) { return; }
		var p = pos( e ); canvas.setPointerCapture( e.pointerId ); mouse = p;
		if ( mode === 'pan' || e.button === 1 || e.button === 2 || space ) { drag = { kind: 'pan', x: p.x, y: p.y, ox: view.ox, oy: view.oy }; canvas.style.cursor = 'grabbing'; return; }
		if ( mode === 'draw' && ppf ) { var h = hitVertex( p.x, p.y ); if ( h > 0 || ( h === 0 && ( P().closed || P().pts.length < 3 ) ) ) { drag = { kind: 'pt', i: h }; return; } }
		drag = { kind: 'maybe', x: p.x, y: p.y, ox: view.ox, oy: view.oy };
	} );
	canvas.addEventListener( 'pointermove', function ( e ) {
		var p = pos( e ); mouse = p;
		if ( drag ) {
			if ( drag.kind === 'maybe' && Math.hypot( p.x - drag.x, p.y - drag.y ) > 5 ) { drag.kind = 'pan'; canvas.style.cursor = 'grabbing'; }
			if ( drag.kind === 'pan' ) { view.ox = drag.ox + p.x - drag.x; view.oy = drag.oy + p.y - drag.y; }
			else if ( drag.kind === 'pt' ) { P().pts[ drag.i ] = snapPoint( p.x, p.y, { d: cur, k: drag.i } ); }
		}
		draw();
	} );
	canvas.addEventListener( 'pointerleave', function () { if ( ! drag ) { mouse = null; draw(); } } );
	function endDrag( e ) {
		var d = drag; drag = null;
		if ( d && d.kind === 'pt' ) { update(); }
		if ( d && d.kind === 'maybe' && e && e.type === 'pointerup' ) { clickAction( pos( e ) ); }
		canvas.style.cursor = mode === 'pan' ? 'grab' : 'crosshair'; draw();
	}
	canvas.addEventListener( 'pointerup', endDrag ); canvas.addEventListener( 'pointercancel', endDrag );
	canvas.addEventListener( 'contextmenu', function ( e ) { e.preventDefault(); } );
	canvas.addEventListener( 'wheel', function ( e ) { if ( ! bg ) { return; } e.preventDefault(); var p = pos( e ); zoomAt( p.x, p.y, e.deltaY < 0 ? 1.15 : 1 / 1.15 ); }, { passive: false } );
	canvas.style.touchAction = 'none';

	/* ---------------------------------------------------------------- keyboard (only while the map tab is in use) */
	function active() { return full || ( ! modal.hidden && ! panel.hidden ); }
	window.addEventListener( 'keydown', function ( e ) {
		if ( ! active() ) { return; }
		var typing = /^(INPUT|SELECT|TEXTAREA)$/.test( ( e.target.tagName || '' ) ) && e.target !== snapEl;
		if ( e.key === 'Escape' && full ) { setFull( false ); e.stopImmediatePropagation(); e.preventDefault(); return; }
		if ( typing ) { return; }
		if ( e.code === 'Space' ) { space = true; canvas.style.cursor = 'grab'; e.preventDefault(); return; }
		if ( ! bg ) { return; }
		if ( e.key === 'Enter' && mode === 'draw' ) { closeDag(); e.preventDefault(); }
		else if ( ( e.key === 'z' || e.key === 'Z' ) && ( e.ctrlKey || e.metaKey ) ) { undo(); e.preventDefault(); }
		else if ( e.key === 'Backspace' && mode === 'draw' ) { undo(); e.preventDefault(); }
		else if ( e.key === '+' || e.key === '=' ) { zoomAt( cw / 2, ch / 2, 1.3 ); }
		else if ( e.key === '-' ) { zoomAt( cw / 2, ch / 2, 1 / 1.3 ); }
		else if ( ( e.key === 's' || e.key === 'S' ) && ! e.ctrlKey ) { setMode( 'scale' ); }
		else if ( ( e.key === 'd' || e.key === 'D' ) && ! e.ctrlKey ) { setMode( 'draw' ); }
		else if ( ( e.key === 'm' || e.key === 'M' ) && ! e.ctrlKey ) { setMode( 'dist' ); }
	}, true );
	window.addEventListener( 'keyup', function ( e ) { if ( e.code === 'Space' ) { space = false; canvas.style.cursor = mode === 'pan' ? 'grab' : 'crosshair'; } } );

	window.addEventListener( 'resize', function () { if ( active() ) { layout(); } } );
	document.addEventListener( 'click', function ( e ) { var t = e.target.closest && e.target.closest( '[data-lc-tab]' ); if ( t ) { if ( full && t.getAttribute( 'data-lc-tab' ) !== 'map' ) { setFull( false ); } setTimeout( layout, 30 ); } } );
	window.sojib68LandMap = { debug: function () { return { dags: dags, ppf: ppf, view: view, cur: cur, full: full }; } };
}() );

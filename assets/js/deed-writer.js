/* দলিল লিখুন — office deed form: instant autosave, NID/name lookup, Bangla number words & dates,
 * then Word download (server builds the Bijoy .docx from data.out + parties). */
( function () {
	'use strict';

	var BOOT = window.sojib68Dw;
	var app = document.getElementById( 'dw-app' );
	if ( ! BOOT || ! app ) { return; }

	/* ------------------------------------------------------------ Bangla helpers */
	var BN = '০১২৩৪৫৬৭৮৯';
	function bn( s ) { return String( s ).replace( /[0-9]/g, function ( d ) { return BN[ +d ]; } ); }
	function en( s ) { return String( s ).replace( /[০-৯]/g, function ( d ) { return String( BN.indexOf( d ) ); } ); }
	function esc( s ) { return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ]; } ); }

	var W99 = [ 'শূন্য', 'এক', 'দুই', 'তিন', 'চার', 'পাঁচ', 'ছয়', 'সাত', 'আট', 'নয়', 'দশ', 'এগারো', 'বারো', 'তেরো', 'চৌদ্দ', 'পনেরো', 'ষোলো', 'সতেরো', 'আঠারো', 'ঊনিশ',
		'বিশ', 'একুশ', 'বাইশ', 'তেইশ', 'চব্বিশ', 'পঁচিশ', 'ছাব্বিশ', 'সাতাশ', 'আটাশ', 'ঊনত্রিশ', 'ত্রিশ', 'একত্রিশ', 'বত্রিশ', 'তেত্রিশ', 'চৌত্রিশ', 'পঁয়ত্রিশ', 'ছত্রিশ', 'সাঁইত্রিশ', 'আটত্রিশ', 'ঊনচল্লিশ',
		'চল্লিশ', 'একচল্লিশ', 'বিয়াল্লিশ', 'তেতাল্লিশ', 'চুয়াল্লিশ', 'পঁয়তাল্লিশ', 'ছেচল্লিশ', 'সাতচল্লিশ', 'আটচল্লিশ', 'ঊনপঞ্চাশ',
		'পঞ্চাশ', 'একান্ন', 'বায়ান্ন', 'তিপ্পান্ন', 'চুয়ান্ন', 'পঞ্চান্ন', 'ছাপ্পান্ন', 'সাতান্ন', 'আটান্ন', 'ঊনষাট',
		'ষাট', 'একষট্টি', 'বাষট্টি', 'তেষট্টি', 'চৌষট্টি', 'পঁয়ষট্টি', 'ছেষট্টি', 'সাতষট্টি', 'আটষট্টি', 'ঊনসত্তর',
		'সত্তর', 'একাত্তর', 'বাহাত্তর', 'তিয়াত্তর', 'চুয়াত্তর', 'পঁচাত্তর', 'ছিয়াত্তর', 'সাতাত্তর', 'আটাত্তর', 'ঊনআশি',
		'আশি', 'একাশি', 'বিরাশি', 'তিরাশি', 'চুরাশি', 'পঁচাশি', 'ছিয়াশি', 'সাতাশি', 'আটাশি', 'ঊননব্বই',
		'নব্বই', 'একানব্বই', 'বিরানব্বই', 'তিরানব্বই', 'চুরানব্বই', 'পঁচানব্বই', 'ছিয়ানব্বই', 'সাতানব্বই', 'আটানব্বই', 'নিরানব্বই' ];

	/** Integer → Bangla words (কোটি, লক্ষ, হাজার, শত). */
	function words( n ) {
		n = Math.floor( n );
		if ( n === 0 ) { return W99[ 0 ]; }
		var out = [], units = [ [ 10000000, 'কোটি' ], [ 100000, 'লক্ষ' ], [ 1000, 'হাজার' ], [ 100, 'শত' ] ];
		units.forEach( function ( u ) {
			var q = Math.floor( n / u[ 0 ] );
			if ( q > 0 ) { out.push( ( u[ 0 ] === 10000000 && q > 99 ? words( q ) : W99[ q ] ) + ( u[ 1 ] === 'শত' ? '' : ' ' ) + u[ 1 ] ); n %= u[ 0 ]; }
		} );
		if ( n > 0 ) { out.push( W99[ n ] ); }
		return out.join( ' ' ).replace( /\s+/g, ' ' ).replace( /শত /g, 'শত ' ).trim();
	}

	/** 500000 → ৫,০০,০০০ (lakh grouping). */
	function lakh( n ) {
		var s = String( Math.floor( n ) ), last = s.slice( -3 ), rest = s.slice( 0, -3 );
		if ( rest ) { rest = rest.replace( /\B(?=(\d{2})+(?!\d))/g, ',' ); return bn( rest + ',' + last ); }
		return bn( last );
	}

	function num( v ) { var x = parseFloat( en( String( v ).replace( /,/g, '' ) ) ); return isFinite( x ) ? x : NaN; }

	function areaText( v ) {
		var x = num( v );
		if ( ! ( x > 0 ) ) { return ''; }
		var s = String( x ), parts = s.split( '.' ), ip = parts[ 0 ], fp = parts[ 1 ];
		var shown = ( ip.length < 2 ? '0' + ip : ip ) + ( fp ? '.' + fp : '' );
		var w = words( +ip ) + ( fp ? ' দশমিক ' + fp.split( '' ).map( function ( d ) { return W99[ +d ]; } ).join( ' ' ) : '' );
		return 'মোঃ ' + bn( shown ) + ' (' + w + ') শতাংশ';
	}

	function priceText( v ) {
		var x = num( v );
		if ( ! ( x > 0 ) ) { return ''; }
		return 'মং ' + lakh( x ) + '/- (' + words( x ) + ') টাকা';
	}

	/* Bangladesh revised Bangla calendar (Boishakh 1 = 14 April). */
	var BMONTHS = [ 'বৈশাখ', 'জ্যৈষ্ঠ', 'আষাঢ়', 'শ্রাবণ', 'ভাদ্র', 'আশ্বিন', 'কার্তিক', 'অগ্রহায়ণ', 'পৌষ', 'মাঘ', 'ফাল্গুন', 'চৈত্র' ];
	function isLeap( y ) { return ( y % 4 === 0 && y % 100 !== 0 ) || y % 400 === 0; }
	function banglaDate( iso ) {
		var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec( iso || '' );
		if ( ! m ) { return ''; }
		var y = +m[ 1 ], mo = +m[ 2 ], d = +m[ 3 ];
		var onOrAfter = mo > 4 || ( mo === 4 && d >= 14 );
		var startY = onOrAfter ? y : y - 1;
		var bYear = startY - 593;
		var diff = Math.round( ( Date.UTC( y, mo - 1, d ) - Date.UTC( startY, 3, 14 ) ) / 86400000 );
		var lens = [ 31, 31, 31, 31, 31, 31, 30, 30, 30, 30, isLeap( startY + 1 ) ? 30 : 29, 30 ];
		var i = 0;
		while ( i < 11 && diff >= lens[ i ] ) { diff -= lens[ i ]; i++; }
		var day = diff + 1, suf = day === 1 ? 'লা' : ( day === 2 || day === 3 ) ? 'রা' : day === 4 ? 'ঠা' : day <= 18 ? 'ই' : 'শে';
		return bn( day ) + suf + ' ' + BMONTHS[ i ] + ' ' + bn( bYear ) + ' বাংলাসন মোতাবেক ' + bn( m[ 3 ] + '/' + m[ 2 ] + '/' + m[ 1 ] ) + ' ইং।';
	}

	function fmtNid( v ) {
		var d = en( v ).replace( /\D/g, '' );
		if ( d.length === 10 ) { return d.slice( 0, 3 ) + ' ' + d.slice( 3, 6 ) + ' ' + d.slice( 6 ); }
		return d;
	}
	function nidKey( v ) { return en( v ).replace( /\D/g, '' ); }

	/* ------------------------------------------------------------ state */
	var DEF = BOOT.defaults || {};
	function emptyParty() { return { nid: '', name: '', spouse: '', father: '', mother: '', dob: '', religion: DEF.religion || 'ইসলাম', profession: '', nationality: DEF.nationality || 'বাংলাদেশী', perm: '', present: '', share: '', o: {} }; }
	function person( k ) { var d = ( DEF[ k ] || {} ); return { name: d.name || '', father: d.father || '', mother: d.mother || '', village: d.village || '', post: d.post || '', upazila: d.upazila || 'রামগঞ্জ', district: d.district || 'লক্ষ্মীপুর', sanad: d.sanad || '' }; }

	var saved = BOOT.data && BOOT.data.f ? BOOT.data : null;
	var S = {
		id: BOOT.id || 0,
		f: saved ? saved.f : {
			sub_office: DEF.sub_office || 'রামগঞ্জ সাব রেজিস্ট্রি অফিস।', deed_type: 'সাফ কবলা', district: DEF.district || 'লক্ষ্মীপুর', upazila: DEF.upazila || 'রামগঞ্জ',
			union: '', mouza_no: '', mouza_name: '', land_class: 'নাল', area: '', price: '', pages: '', proxy_text: 'প্রযোজ্য নহে।', poa_text: 'প্রযোজ্য নহে।', ownership: '',
			exec_greg: new Date().toISOString().slice( 0, 10 ), village: '', land_office: DEF.land_office || 'সহকারী কমিশনার ভূমি অফিস, রামগঞ্জ।', jl_no: '',
			schedule: [ { khatian: '', rs_dag: '', cs_dag: '', class: 'নাল', total: '', transferred: '' } ],
			north: '', south: '', east: '', west: '', n_ft: '', s_ft: '', e_ft: '', w_ft: '',
			w1: person( 'w1' ), w2: person( 'w2' ), id: person( 'id' ), wr: person( 'wr' ), o: {}
		},
		donees: saved ? saved.donees : [ emptyParty() ],
		donors: saved ? saved.donors : [ emptyParty() ]
	};
	if ( ! S.f.o ) { S.f.o = {}; }
	/* ---- mouza library: fixed details of a mouza fill in automatically ---- */
	var MZ = BOOT.mouzas || [];
	var MAP = [ [ 'mouza_name', 'name' ], [ 'mouza_no', 'no' ], [ 'union', 'union' ], [ 'upazila', 'upazila' ], [ 'district', 'district' ], [ 'jl_no', 'jl_no' ], [ 'village', 'village' ], [ 'land_office', 'land_office' ], [ 'sub_office', 'sub_office' ], [ 'land_class', 'land_class' ], [ 'ownership', 'intro' ] ];

	function findMouza() {
		var f = S.f, i, hit = null, name = ( f.mouza_name || '' ).trim(), no = en( f.mouza_no || '' ).trim();
		if ( f.mouza_id ) { for ( i = 0; i < MZ.length; i++ ) { if ( MZ[ i ].id === f.mouza_id ) { return MZ[ i ]; } } }
		if ( ! name ) { return null; }
		var same = MZ.filter( function ( m ) { return ( m.name || '' ).trim() === name; } );
		if ( same.length === 1 ) { return same[ 0 ]; }
		same.forEach( function ( m ) { if ( en( m.no || '' ).trim() === no ) { hit = m; } } );
		return hit;
	}

	/** overwrite=true: set every mouza field (user chose the mouza); false: only fill what is still empty. Returns true if anything changed. */
	function applyMouza( m, overwrite ) {
		var changed = false;
		MAP.forEach( function ( p ) {
			var v = m[ p[ 1 ] ];
			if ( ! v ) { return; }
			if ( p[ 0 ] === 'ownership' && S.f.ownership && S.f.ownership.trim() && S.f.ownership !== v ) { if ( ! overwrite || ! window.confirm( 'মালিকানার বিবরণে আগে থেকে লেখা আছে। এই মৌজার শুরুর অংশ দিয়ে বদলে দেবেন?' ) ) { return; } }
			if ( overwrite || ! S.f[ p[ 0 ] ] || ! String( S.f[ p[ 0 ] ] ).trim() ) { if ( S.f[ p[ 0 ] ] !== v ) { S.f[ p[ 0 ] ] = v; changed = true; } }
		} );
		if ( m.id && S.f.mouza_id !== m.id ) { S.f.mouza_id = m.id; changed = true; }
		if ( m.land_class && S.f.schedule && S.f.schedule[ 0 ] && ! S.f.schedule[ 0 ][ 'class' ] ) { S.f.schedule[ 0 ][ 'class' ] = m.land_class; }
		return changed;
	}

	if ( ! S.f.o ) { S.f.o = {}; }
	if ( ! saved && BOOT.newMouza ) { applyMouza( BOOT.newMouza, true ); }
	var loadedFilled = false;
	if ( saved ) { var m0 = findMouza(); if ( m0 ) { loadedFilled = applyMouza( m0, false ); } }
	S.donees.forEach( function ( p ) { if ( ! p.o ) { p.o = {}; } } );
	S.donors.forEach( function ( p ) { if ( ! p.o ) { p.o = {}; } } );

	/* ------------------------------------------------------------ compute */
	function compute() {
		var f = S.f, o = f.o || {};
		var auto = {};
		auto.mouza_short = ( f.mouza_no ? bn( String( f.mouza_no ).replace( /\s*নং\s*$/, '' ) ) + 'নং ' : '' ) + ( f.mouza_name || '' );
		auto.mouza_head = 'মৌজাঃ ' + ( f.mouza_name || '' );
		auto.area_text = areaText( f.area );
		auto.price_text = priceText( f.price );
		auto.payment_text = auto.price_text ? auto.price_text + ' নগদ পরিশোধিত।' : '';
		var a = num( f.area );
		auto.sqft_text = a > 0 ? lakh( Math.round( a * 435.6 ) ) + ' বর্গফুট।' : '';
		auto.exec_date = banglaDate( f.exec_greg );
		auto.schedule_head = 'জেলা ঃ ' + ( f.district || '' ) + ' মৌজাঃ ' + ( f.mouza_name || '' ) + ' উপজেলা ঃ ' + ( f.upazila || '' ) + ', জে. এল নং ঃ ' + ( f.jl_no || '' ) + ', ইউনিয়নঃ ' + ( f.union || '' ) + ' গ্রাম ঃ ' + ( f.village || '' ) + ', ভূমি অফিসের নাম ঃ ' + ( f.land_office || '' );
		auto.total_text = 'মোট জমির পরিমান ঃ ' + ( o.area_text || auto.area_text );
		var ft = function ( v ) { return v ? bn( v ) + ' ফুট' : '                '; };
		auto.boundaries = 'উত্তরে: ' + ( f.north || '' ) + ', দক্ষিণে: ' + ( f.south || '' ) + ', পূর্বে: ' + ( f.east || '' ) + ', পশ্চিমে: ' + ( f.west || '' ) +
			'। উক্ত চৌহুদ্দির মধ্যে উত্তরে: ' + ft( f.n_ft ) + ', দক্ষিণে: ' + ft( f.s_ft ) + ', পূর্বে: ' + ft( f.e_ft ) + ', পশ্চিমে: ' + ft( f.w_ft ) + '। অত্র চৌহুদ্দির মধ্যে বিক্রিত ভূমি বটে।';
		S.auto = auto;

		var out = {};
		[ 'mouza_short', 'mouza_head', 'area_text', 'price_text', 'payment_text', 'sqft_text', 'exec_date', 'schedule_head', 'total_text', 'boundaries' ].forEach( function ( k ) {
			out[ k ] = o[ k ] != null && o[ k ] !== '' ? o[ k ] : auto[ k ];
		} );
		[ 'sub_office', 'deed_type', 'union', 'upazila', 'district', 'land_class', 'proxy_text', 'poa_text', 'ownership', 'pages' ].forEach( function ( k ) { out[ k ] = f[ k ] || ''; } );
		out.w1 = f.w1; out.w2 = f.w2;
		[ 'name', 'father', 'mother', 'village', 'post', 'upazila', 'district' ].forEach( function ( k ) { out[ 'id_' + k ] = f.id[ k ] || ''; out[ 'wr_' + k ] = f.wr[ k ] || ''; } );
		out.wr_sanad = f.wr.sanad || '';
		out.schedule_rows = ( f.schedule || [] ).map( function ( r ) { return { khatian: r.khatian || '', rs_dag: r.rs_dag || '', cs_dag: r.cs_dag || '', 'class': r[ 'class' ] || '', total: r.total || '', transferred: r.transferred || '' }; } );

		function shareRows( list, single ) {
			return list.map( function ( p ) {
				var st = p.o && p.o.share_text ? p.o.share_text : areaText( p.share );
				if ( ! st && list.length === 1 ) { st = out.area_text; }
				p.share_text_auto = areaText( p.share ) || ( list.length === 1 ? out.area_text : '' );
				return { name: p.name || '', share: st };
			} );
		}
		out.donee_rows = shareRows( S.donees );
		out.donor_rows = shareRows( S.donors );
		auto.affidavit_head = S.donors.map( function ( p ) {
			return 'হলফকারীগনের নাম, পিতার নাম, ঠিকানা ও বয়সঃ নাম ঃ ' + ( p.name || '' ) + ' পিতার নামঃ ' + ( p.father || '' ) + ' মাতার নাম ঃ ' + ( p.mother || '' ) +
				' জন্ম তারিখ ঃ ' + ( p.dob || '' ) + ' ইং ' + ( p.perm || '' );
		} ).join( '\n' );
		out.affidavit_head = o.affidavit_head != null && o.affidavit_head !== '' ? o.affidavit_head : auto.affidavit_head;
		S.out = out;
		return out;
	}

	/* ------------------------------------------------------------ path helpers */
	function getp( path ) {
		var o = S, parts = path.split( '.' );
		for ( var i = 0; i < parts.length; i++ ) { if ( o == null ) { return ''; } o = o[ parts[ i ] ]; }
		return o == null ? '' : o;
	}
	function setp( path, val ) {
		var o = S, parts = path.split( '.' );
		for ( var i = 0; i < parts.length - 1; i++ ) { if ( o[ parts[ i ] ] == null ) { o[ parts[ i ] ] = {}; } o = o[ parts[ i ] ]; }
		o[ parts[ parts.length - 1 ] ] = val;
	}

	/* ------------------------------------------------------------ render */
	/* ---- document-style form: same layout as the preview, with the values typed in place ---- */
	function inp( path, o ) {
		o = o || {};
		var v = getp( path ), ph = o.ph ? ' placeholder="' + esc( o.ph ) + '"' : '';
		if ( o.area ) { return '<textarea data-p="' + esc( path ) + '" rows="' + ( o.rows || 2 ) + '"' + ph + '>' + esc( v ) + '</textarea>'; }
		return '<input type="' + ( o.type || 'text' ) + '" data-p="' + esc( path ) + '" value="' + esc( v ) + '"' + ph + ( o.nid ? ' data-nid="1" inputmode="numeric"' : '' ) + ( o.list ? ' list="' + o.list + '"' : '' ) + ' autocomplete="off">';
	}
	function ainp( key, scope, rows ) {
		var holder = scope || 'f.o';
		return '<textarea data-auto="' + holder + '.' + key + '" rows="' + ( rows || 1 ) + '"></textarea>';
	}
	function reset( key, scope ) { return '<button type="button" class="dw-reset" data-reset="' + ( scope || 'f.o' ) + '.' + key + '" title="স্বয়ংক্রিয় মান ফেরান">↺</button>'; }
	/** "label : [input]" line like the document */
	function drow( label, control, note ) { return '<div class="df-row"><span class="df-l">' + label + '</span><span class="df-c">:</span><span class="df-v">' + control + ( note || '' ) + '</span></div>'; }
	function dauto( label, key, scope, rows ) { return drow( label + ' ' + reset( key, scope ), ainp( key, scope, rows ) ); }
	function th( t ) { return '<th>' + t + '</th>'; }
	function dsec( id, num, title, body ) { return '<section class="df-sec" id="' + id + '"><h3 class="df-h"><b>' + num + '।</b> ' + title + '</h3>' + body + '</section>'; }

	function partyCard( group, i ) {
		var base = group + '.' + i + '.', n = S[ group ].length, nm = n > 1 ? bn( i + 1 ) + '। ' : '';
		return '<div class="df-party" data-group="' + group + '" data-i="' + i + '">' +
			'<div class="df-party-h"><b>' + nm + ( group === 'donees' ? 'গ্রহীতা' : 'দাতা' ) + '</b>' + ( n > 1 ? '<button type="button" class="dw-x" data-del-party="' + group + ':' + i + '">মুছুন ×</button>' : '' ) + '</div>' +
			drow( 'পরিচিতি নং (NID)', inp( base + 'nid', { nid: true, ph: 'যেমন 823 541 7337' } ), '<small class="dw-nid-st"></small>' ) +
			drow( 'নাম', inp( base + 'name' ) ) +
			drow( 'স্বামীর নাম <em>(বিবাহিতা মহিলা হলে)</em>', inp( base + 'spouse' ) ) +
			drow( 'পিতার নাম', inp( base + 'father' ) ) + drow( 'মাতার নাম', inp( base + 'mother' ) ) +
			drow( 'জন্ম তারিখ', inp( base + 'dob', { ph: 'যেমন ১০/০৩/১৯৮৭' } ) ) +
			drow( 'ধর্ম', inp( base + 'religion' ) ) + drow( 'পেশা', inp( base + 'profession' ) ) + drow( 'জাতীয়তা', inp( base + 'nationality' ) ) +
			'<table class="df-t df-addr"><tr>' + th( 'স্থায়ী ঠিকানা' ) + th( 'বর্তমান ঠিকানা <button type="button" class="dw-link" data-copy-addr="' + group + ':' + i + '">স্থায়ীর মতোই</button>' ) + '</tr><tr><td>' +
			inp( base + 'perm', { area: true, rows: 3, ph: 'বাসা/হোল্ডিং: …, গ্রাম/রাস্তা: …, ডাকঘর: …, উপজেলা: …, জেলা: …' } ) + '</td><td>' + inp( base + 'present', { area: true, rows: 3 } ) + '</td></tr></table>' +
			( n > 1 ? drow( 'এই ব্যক্তির অংশ (শতাংশ)', inp( base + 'share', { ph: 'যেমন 3' } ) ) + drow( 'অংশের লেখা ' + reset( 'share_text', group + '.' + i + '.o' ), ainp( 'share_text', group + '.' + i + '.o' ) ) : '' ) +
			'</div>';
	}

	function peopleRow( label, key, extra ) {
		var b = 'f.' + key + '.';
		return '<tr><th>' + label + '</th><td>' + inp( b + 'name' ) + '</td><td>' + inp( b + 'father' ) + '</td><td>' + inp( b + 'mother' ) + '</td><td>' + inp( b + 'village' ) + '</td><td>' + inp( b + 'post' ) + '</td><td>' + inp( b + 'upazila' ) + '</td><td>' + inp( b + 'district' ) + '</td></tr>';
	}
	var PEOPLE_HEAD = '<tr><th></th>' + [ 'নাম', 'পিতার নাম', 'মাতার নাম', 'গ্রাম/রোড', 'ডাকঘর', 'উপজেলা', 'জেলা' ].map( th ).join( '' ) + '</tr>';

	function render() {
		var f = S.f, h = '';
		h += '<div class="dw-top"><a class="btn btn-outline btn-sm" href="' + esc( BOOT.listUrl ) + '">← সব দলিল</a>' +
			'<span class="dw-status" id="dw-status">নতুন — লিখতে শুরু করুন, নিজে নিজে সেভ হবে</span>' +
			'<button type="button" class="btn btn-outline btn-sm" id="dw-pv-full">পুরো পাতায় দেখুন</button>' +
			'<button type="button" class="btn btn-outline btn-sm dw-pv-show" id="dw-pv-show">প্রিভিউ দেখুন</button>' +
			'<a class="btn btn-primary btn-sm" id="dw-download" href="#">Word ডাউনলোড</a>' +
			'<nav class="dw-jump" aria-label="দলিলের অংশ"><a href="#s-1">১–২</a><a href="#s-3">৩ গ্রহীতা</a><a href="#s-4">৪ দাতা</a><a href="#s-5">৫–৭ মালিকানা</a><a href="#s-10">১০ তারিখ</a><a href="#s-11">১১ তফসিল</a><a href="#s-12">১২–১৫</a><a href="#s-18">১৮–২০ সাক্ষী</a><a href="#s-21">২১–২২</a></nav></div>' +
			'<div class="dw-warn" id="dw-warn" hidden></div><div class="df-paper">';

		// 1–2
		h += dsec( 's-1', '১–২', 'রেজিস্ট্রি অফিস ও দলিলের সার সংক্ষেপ',
			mouzaPicker() + drow( '১। রেজিস্ট্রী অফিসের নাম', inp( 'f.sub_office' ) ) +
			'<p class="df-cap">২। দলিলের সার সংক্ষেপ</p>' +
			'<table class="df-t"><tr>' + [ 'দলিলের প্রকৃতি', 'মৌজা নম্বর', 'মৌজার নাম', 'ইউনিয়ন/ওয়ার্ড', 'থানা/উপজেলা', 'জেলা' ].map( th ).join( '' ) + '</tr><tr>' +
			'<td>' + inp( 'f.deed_type', { list: 'dw-types' } ) + '</td><td>' + inp( 'f.mouza_no', { ph: '99' } ) + '</td><td>' + inp( 'f.mouza_name', { ph: 'তিয়রী' } ) + '</td><td>' + inp( 'f.union', { ph: '৯ নং ভোলাকোট' } ) + '</td><td>' + inp( 'f.upazila' ) + '</td><td>' + inp( 'f.district' ) + '</td></tr></table>' +
			'<table class="df-t df-sum"><tr>' + [ 'হস্তান্তরিত সম্পত্তির পরিমান', 'শ্রেণী', 'মূল্য (অংকে ও কথায়)' ].map( th ).join( '' ) + '</tr><tr>' +
			'<td><small class="df-mini">পরিমাণ লিখুন (শতাংশ, শুধু সংখ্যা)</small>' + inp( 'f.area', { ph: 'যেমন 5 বা 5.25' } ) + '<small class="df-mini">কথায় লেখা (নিজে নিজে হয়, চাইলে বদলান)</small>' + ainp( 'area_text', 'f.o', 2 ) + reset( 'area_text' ) + '</td>' +
			'<td>' + inp( 'f.land_class', { ph: 'যেমন নাল' } ) + '</td>' +
			'<td><small class="df-mini">মূল্য লিখুন (টাকা, শুধু সংখ্যা)</small>' + inp( 'f.price', { ph: 'যেমন 500000' } ) + '<small class="df-mini">কথায় লেখা (নিজে নিজে হয়, চাইলে বদলান)</small>' + ainp( 'price_text', 'f.o', 2 ) + reset( 'price_text' ) + '</td></tr></table>' +
			'<div class="df-row df-btnrow"><button type="button" class="btn btn-outline btn-sm" id="dw-save-mouza">এই মৌজার তথ্য মৌজা তালিকায় সেভ করুন</button> <small id="dw-mouza-st"></small></div>' );

		// 3, 4
		h += dsec( 's-3', '৩', 'দলিল গ্রহীতা/গ্রহীতাগণের নাম ও ঠিকানা (ক্রেতা)', searchBox( 'donees' ) + S.donees.map( function ( p, i ) { return partyCard( 'donees', i ); } ).join( '' ) + '<button type="button" class="btn btn-outline btn-sm" data-add-party="donees">+ আরেকজন গ্রহীতা</button>' );
		h += dsec( 's-4', '৪', 'দলিল দাতা/দাতাগণের নাম ও ঠিকানা (বিক্রেতা)', searchBox( 'donors' ) + S.donors.map( function ( p, i ) { return partyCard( 'donors', i ); } ).join( '' ) + '<button type="button" class="btn btn-outline btn-sm" data-add-party="donors">+ আরেকজন দাতা</button>' );

		// 5–7
		h += dsec( 's-5', '৫–৭', 'আমমোক্তার, আমমোক্তারনামা ও মালিকানার বিবরণ',
			drow( '৫। আমমোক্তার/প্রতিনিধি/অভিভাবক', inp( 'f.proxy_text' ) ) + drow( '৬। আমমোক্তারনামার বিবরণ', inp( 'f.poa_text' ) ) +
			'<p class="df-cap">৭। হস্তান্তরাধীন জমির নূন্য পক্ষে ২৫ বছরের মালিকানার ধারাবাহিক বিবরণ</p><div class="df-box">' + inp( 'f.ownership', { area: true, rows: 10, ph: 'জেলা-…, উপজেলা ও সাব-রেজিস্ট্রি অফিস … এর পুরো বিবরণ এখানে লিখুন বা পেস্ট করুন' } ) + '</div>' );

		// 10
		h += dsec( 's-10', '১০', 'সম্পাদনের তারিখ (বাংলায় ও ইংরেজী)', drow( 'ইংরেজি তারিখ বেছে নিন', inp( 'f.exec_greg', { type: 'date' } ) ) + dauto( 'বাংলা ও ইংরেজি তারিখ', 'exec_date', 'f.o', 1 ) );

		// 11
		var srows = ( S.f.schedule || [] ).map( function ( r, i ) {
			var b = 'f.schedule.' + i + '.';
			return '<tr><td>' + inp( b + 'khatian', { area: true, rows: 2, ph: 'সি.এস ০৮নং খং … আগত আর.এস ৩৮ নং খং …' } ) + '</td><td>' + inp( b + 'rs_dag' ) + '</td><td>' + inp( b + 'cs_dag', { ph: '১৭১৩ (এক হাজার সাতশত তেরো) দাগ' } ) + '</td><td>' + inp( b + 'class' ) + '</td><td>' + inp( b + 'total', { ph: 'মোঃ ১৮ শতাংশ হইতে' } ) + '</td><td>' + inp( b + 'transferred', { ph: 'মোঃ ০৫ শতাংশ' } ) + '</td><td class="df-x">' + ( S.f.schedule.length > 1 ? '<button type="button" class="dw-x" data-del-sched="' + i + '" title="এই দাগ মুছুন">×</button>' : '' ) + '</td></tr>';
		} ).join( '' );
		h += dsec( 's-11', '১১', 'সম্পত্তির তফসিল',
			drow( 'গ্রাম', inp( 'f.village' ) ) + drow( 'জে.এল. নং', inp( 'f.jl_no', { ph: 'সি.এস ১৪১ আর.এস ৯৯ নং' } ) ) + drow( 'ভূমি অফিসের নাম', inp( 'f.land_office' ) ) +
			dauto( 'তফসিলের শুরুর লাইন', 'schedule_head', 'f.o', 2 ) +
			'<table class="df-t"><tr>' + [ 'খতিয়ান নং (সি.এস / আর.এস / বর্তমান)', 'আর.এস দাগ', 'সি.এস দাগ', 'শ্রেণি', 'দাগে মোট জমি', 'হস্তান্তরিত জমির পরিমান', '' ].map( th ).join( '' ) + '</tr>' + srows + '</table>' +
			'<button type="button" class="btn btn-outline btn-sm" data-add-sched="1">+ আরেকটি দাগ</button>' + dauto( 'মোট জমির পরিমান', 'total_text', 'f.o', 1 ) );

		// 12–15
		h += dsec( 's-12', '১২–১৫', 'চৌহদ্দি, পরিমাণ, মূল্য ও হাত নকশা',
			'<p class="df-cap">১২। সম্পত্তির চৌহদ্দির বিবরণ</p><table class="df-t df-bound"><tr>' + th( 'দিক' ) + th( 'চৌহদ্দিতে যা আছে' ) + th( 'মাপ (ফুট)' ) + '</tr>' +
			[ [ 'উত্তরে', 'north', 'n_ft' ], [ 'দক্ষিণে', 'south', 's_ft' ], [ 'পূর্বে', 'east', 'e_ft' ], [ 'পশ্চিমে', 'west', 'w_ft' ] ].map( function ( d ) { return '<tr><th>' + d[ 0 ] + '</th><td>' + inp( 'f.' + d[ 1 ] ) + '</td><td>' + inp( 'f.' + d[ 2 ], { ph: '38' } ) + '</td></tr>'; } ).join( '' ) + '</table>' +
			dauto( 'চৌহদ্দির পুরো লেখা', 'boundaries', 'f.o', 3 ) +
			dauto( '১৩। হস্তান্তরিত সম্পত্তির পরিমাণ', 'area_text', 'f.o', 1 ) + dauto( '১৪। মূল্য পরিশোধের বিবরণ', 'payment_text', 'f.o', 1 ) + dauto( '১৫। হাত নকশা ও পরিমাপ', 'sqft_text', 'f.o', 1 ) +
			'<p class="df-note">১৬। কৈফিয়ত ও ১৭। স্বাক্ষর অংশ আপনার ছাঁচ অনুযায়ী ফাঁকা থাকবে।</p>' );

		// 18–20
		h += dsec( 's-18', '১৮–২০', 'স্বাক্ষী, সনাক্তকারী ও দলিল লেখক',
			'<table class="df-t df-people">' + PEOPLE_HEAD + peopleRow( '১৮। (ক) স্বাক্ষী', 'w1' ) + peopleRow( '১৮। (খ) স্বাক্ষী', 'w2' ) + peopleRow( '১৯। সনাক্তকারী', 'id' ) + peopleRow( '২০। দলিল লেখক', 'wr' ) + '</table>' +
			drow( 'দলিল লেখকের সনদ নং', inp( 'f.wr.sanad' ) ) + drow( '২০। দলিলটি কয় ফর্দে লিখিত', inp( 'f.pages', { ph: 'যেমন 7' } ) ) + '<p class="df-note">সাক্ষী ছাড়া অন্যগুলো পরের দলিলে নিজে নিজে ভরা থাকবে।</p>' );

		// 21–22
		h += dsec( 's-21', '২১–২২', 'দলিল দাতার হলফনামা ও সাব-রেজিষ্ট্রারের স্বাক্ষর',
			'<p class="df-note">হলফনামার স্থায়ী ঘোষণাগুলো ছাঁচ থেকে আসে। শুধু হলফকারীর পরিচয়ের লাইনটি দাতার তথ্য থেকে ভরে; চাইলে বদলান।</p>' +
			'<p class="df-cap">২১। হলফকারীগনের নাম, পিতার নাম, ঠিকানা ও বয়স ' + reset( 'affidavit_head' ) + '</p><div class="df-box">' + ainp( 'affidavit_head', 'f.o', 4 ) + '</div>' +
			'<p class="df-note">২২। সাব-রেজিষ্ট্রারের নাম, পদবী, স্বাক্ষর ও তারিখ: রেজিস্ট্রি অফিসে তিনি নিজে পূরণ করেন, এখানে কিছু লেখার নেই।</p>' );

		h += '</div><section class="dw-end"><button type="button" class="btn btn-primary" id="dw-download2">Word ডাউনলোড (বিজয় / SutonnyMJ)</button>' +
			'<p class="dw-hint">ডাউনলোড করা ফাইলে ডান ক্লিক → Properties → Unblock টিক দিয়ে খুলুন। Word-এ SutonnyMJ ফন্ট ইনস্টল থাকতে হবে।</p></section>';
		h += '<datalist id="dw-types"><option value="সাফ কবলা"><option value="হেবা"><option value="বণ্টননামা"><option value="বায়না"><option value="দান"></datalist>';
		app.innerHTML = '<div class="dw-edit' + ( previewHidden() ? ' is-nopreview' : '' ) + '"><div class="dw-form">' + h + '</div><aside class="dw-preview" id="dw-preview" aria-label="দলিলের প্রিভিউ"></aside></div>';
		refreshAuto();
	}

	function mouzaPicker() {
		if ( ! MZ.length ) { return '<p class="df-note">মৌজা তালিকায় মৌজা যোগ করলে এখানে বেছে নেওয়া যাবে, আর তার তথ্য নিজে নিজে ভরবে।</p>'; }
		var cur = ( findMouza() || {} ).id || '';
		return '<div class="df-row df-mpick"><span class="df-l">মৌজা বেছে নিন</span><span class="df-c">:</span><span class="df-v"><select id="dw-mouza-pick"><option value="">— মৌজা বেছে নিন —</option>' +
			MZ.map( function ( m ) { return '<option value="' + esc( m.id ) + '"' + ( m.id === cur ? ' selected' : '' ) + '>' + esc( ( m.no ? String( m.no ).replace( /\s*নং\s*$/, '' ) + 'নং ' : '' ) + m.name ) + '</option>'; } ).join( '' ) + '</select><small>ইউনিয়ন, জে.এল. নং, গ্রাম, ভূমি অফিস ও মালিকানার শুরুর অংশ নিজে নিজে ভরবে</small></span></div>';
	}

	function searchBox( group ) {
		return '<div class="dw-search" data-search="' + group + '"><input type="search" placeholder="আগে সেভ করা ব্যক্তি খুঁজুন (নাম বা NID লিখুন)…" autocomplete="off"><div class="dw-results" hidden></div></div>';
	}

	/** Fill the computed-text boxes (without stealing focus from the one being typed in). */
	function refreshAuto() {
		compute();
		[].forEach.call( app.querySelectorAll( '[data-auto]' ), function ( el ) {
			var path = el.getAttribute( 'data-auto' ), parts = path.split( '.' ), key = parts.pop(), ov = getp( parts.join( '.' ) ) || {};
			var autoVal;
			if ( /^(donees|donors)\./.test( path ) ) {
				var m = /^(donees|donors)\.(\d+)\./.exec( path ); autoVal = ( S[ m[ 1 ] ][ +m[ 2 ] ].share_text_auto ) || '';
			} else { autoVal = S.auto[ key ] || ''; }
			el.placeholder = autoVal;
			if ( document.activeElement !== el ) { el.value = ov[ key ] != null && ov[ key ] !== '' ? ov[ key ] : autoVal; }
			el.classList.toggle( 'is-manual', ov[ key ] != null && ov[ key ] !== '' );
		} );
		warnLatin();
		renderPreview();
	}

	function warnLatin() {
		var bad = [];
		function scan( label, v ) { if ( typeof v === 'string' && /[A-Za-z]/.test( v ) ) { bad.push( label ); } }
		S.donees.concat( S.donors ).forEach( function ( p, i ) { [ 'name', 'father', 'mother', 'perm', 'present', 'profession' ].forEach( function ( k ) { scan( ( p.name || 'ব্যক্তি' ) + ' (' + k + ')', p[ k ] ); } ); } );
		[ 'ownership', 'mouza_name', 'union', 'village' ].forEach( function ( k ) { scan( k, S.f[ k ] ); } );
		var el = document.getElementById( 'dw-warn' );
		if ( ! el ) { return; }
		el.hidden = ! bad.length;
		if ( bad.length ) { el.textContent = '⚠ কিছু ঘরে ইংরেজি অক্ষর আছে। বিজয় ফন্টে ইংরেজি অক্ষর ঠিক দেখাবে না, বাংলা অক্ষরে লিখুন।'; }
	}

	/* ------------------------------------------------------------ live preview */
	function previewHidden() { try { return window.localStorage.getItem( 'sojib68DwPreviewHidden' ) === '1'; } catch ( e ) { return false; } }
	function nl( v ) { return esc( v ).replace( /\n/g, '<br>' ); }
	function pv( v ) { return v && String( v ).trim() ? nl( v ) : '<span class="dw-blank">………………</span>'; }
	function row( label, v ) { return '<div class="pv-row"><span class="pv-l">' + label + '</span><span class="pv-c">:</span><span class="pv-v">' + pv( v ) + '</span></div>'; }
	function fixedNote( t ) { return '<p class="pv-fixed">' + t + '</p>'; }
	function pvParty( p, i, n ) {
		var h = '<div class="pv-party"><strong>' + ( n > 1 ? bn( i + 1 ) + '। ' : '' ) + 'নাম</strong> : ' + pv( p.name );
		if ( p.spouse ) { h += row( 'স্বামীর নাম', p.spouse ); }
		h += row( 'পিতার নাম', p.father ) + row( 'মাতার নাম', p.mother ) + row( 'জন্ম তারিখ', p.dob ? p.dob + ' ইং' : '' ) + row( 'ধর্ম', p.religion ) + row( 'পেশা', p.profession ) + row( 'জাতীয়তা', p.nationality ) + row( 'পরিচিতি নং', p.nid );
		h += '<div class="pv-addr"><div><b>স্থায়ী ঠিকানা</b><br>' + pv( p.perm ) + '</div><div><b>বর্তমান ঠিকানা</b><br>' + pv( p.present ) + '</div></div></div>';
		return h;
	}
	function pvPerson( tag, p ) {
		return '<div class="pv-person"><b>' + tag + '</b> ' + pv( p.name ) + '<br>পিতা: ' + pv( p.father ) + ' · মাতা: ' + pv( p.mother ) + '<br>গ্রাম/রোড: ' + pv( p.village ) + ' · ডাকঘর: ' + pv( p.post ) + ' · উপজেলা: ' + pv( p.upazila ) + ' · জেলা: ' + pv( p.district ) + '</div>';
	}
	function paperHtml() {
		var o = S.out || compute(), f = S.f, h = '';
		h += '<div class="pv-paper">';
		h += '<p class="pv-h">১। রেজিস্ট্রী অফিসের নাম : <span>' + pv( o.sub_office ) + '</span></p>';
		h += '<p class="pv-h">২। দলিলের সার সংক্ষেপ</p>';
		h += '<table class="pv-t"><tr><th>দলিলের প্রকৃতি</th><th>মৌজার নাম</th><th>ইউনিয়ন/ওয়ার্ড</th><th>থানা/উপজেলা</th><th>জেলা</th></tr><tr><td>' + pv( o.deed_type ) + '</td><td>' + pv( o.mouza_short ) + '</td><td>' + pv( o.union ) + '</td><td>' + pv( o.upazila ) + '</td><td>' + pv( o.district ) + '</td></tr></table>';
		h += '<table class="pv-t"><tr><th>হস্তান্তরিত সম্পত্তির পরিমান</th><th>শ্রেণী</th><th>মূল্য (অংকে ও কথায়)</th></tr><tr><td>' + pv( o.area_text ) + '</td><td>' + pv( o.land_class ) + '</td><td>' + pv( o.price_text ) + '</td></tr></table>';
		h += '<p class="pv-h">৩। দলিল গ্রহীতা/গ্রহীতাগণের নাম ও ঠিকানা</p>' + S.donees.map( function ( p, i ) { return pvParty( p, i, S.donees.length ); } ).join( '' );
		h += '<p class="pv-h">৪। দলিল দাতা/দাতাগণের নাম ও ঠিকানা</p>' + S.donors.map( function ( p, i ) { return pvParty( p, i, S.donors.length ); } ).join( '' );
		h += row( '৫। আমমোক্তার/প্রতিনিধি/অভিভাবক', o.proxy_text ) + row( '৬। আমমোক্তারনামার বিবরণ', o.poa_text );
		h += '<p class="pv-h">৭। মালিকানার ধারাবাহিক বিবরণ</p><p class="pv-body">' + pv( o.ownership ) + '</p>' + fixedNote( '[ছাঁচের স্থায়ী লেখা: “হস্তান্তরিত ভূমির উপর আমি দাতার … ” ও “প্রকাশ থাকে যে, …” অনুচ্ছেদ Word ফাইলে আসবে]' );
		h += '<p class="pv-h">৮। একাধিক গ্রহীতার হারাহারি মালিকানা</p><table class="pv-t"><tr><th>ক্রেতা/গ্রহীতার নাম</th><th>মালিকানার পরিমান</th></tr>' + ( o.donee_rows || [] ).map( function ( r ) { return '<tr><td>' + pv( r.name ) + '</td><td>' + pv( r.share ) + '</td></tr>'; } ).join( '' ) + '</table>';
		h += '<p class="pv-h">৯। একাধিক দাতার হারাহারি মালিকানা</p><table class="pv-t"><tr><th>বিক্রেতা/দাতার নাম</th><th>মালিকানার পরিমান</th></tr>' + ( o.donor_rows || [] ).map( function ( r ) { return '<tr><td>' + pv( r.name ) + '</td><td>' + pv( r.share ) + '</td></tr>'; } ).join( '' ) + '</table>';
		h += row( '১০। সম্পাদনের তারিখ', o.exec_date );
		h += '<p class="pv-h">১১। সম্পত্তির তফসিল</p><p class="pv-body">' + pv( o.schedule_head ) + '</p><p class="pv-sm">' + pv( o.mouza_head ) + '</p>';
		h += '<table class="pv-t"><tr><th>খতিয়ান নং</th><th>আর.এস দাগ</th><th>সি.এস দাগ</th><th>শ্রেণি</th><th>দাগে মোট জমি</th><th>হস্তান্তরিত জমি</th></tr>' + ( o.schedule_rows || [] ).map( function ( r ) { return '<tr><td>' + pv( r.khatian ) + '</td><td>' + pv( r.rs_dag ) + '</td><td>' + pv( r.cs_dag ) + '</td><td>' + pv( r[ 'class' ] ) + '</td><td>' + pv( r.total ) + '</td><td>' + pv( r.transferred ) + '</td></tr>'; } ).join( '' ) + '</table><p class="pv-sm">' + pv( o.total_text ) + '</p>';
		h += row( '১২। চৌহদ্দির বিবরণ', o.boundaries ) + row( '১৩। সম্পত্তির পরিমাণ', o.area_text ) + row( '১৪। মূল্য পরিশোধের বিবরণ', o.payment_text ) + row( '১৫। হাত নকশা ও পরিমাপ', o.sqft_text );
		h += '<p class="pv-h">১৬। কৈফিয়ত যদি থাকে :</p>' + fixedNote( '[১৭। স্বাক্ষর অংশ ছাঁচ অনুযায়ী ফাঁকা থাকবে]' );
		h += '<p class="pv-h">১৮। স্বাক্ষীগণের নাম, ঠিকানা ও স্বাক্ষর</p>' + pvPerson( '(ক)', f.w1 ) + pvPerson( '(খ)', f.w2 );
		h += '<p class="pv-h">১৯। সনাক্তকারী</p>' + pvPerson( '', f.id );
		h += '<p class="pv-h">২০। দলিল লেখক (ফর্দ: ' + pv( o.pages ) + ')</p>' + pvPerson( '', f.wr ) + row( 'সনদ নং', f.wr.sanad );
		h += '<p class="pv-h">২১। দলিল দাতার হলফনামা</p><p class="pv-body">' + pv( o.affidavit_head ) + '</p>' + fixedNote( '[হলফনামার ঘোষণা (ক–চ) ছাঁচ থেকে আসবে]' );
		h += '<p class="pv-h">২২। সাব-রেজিষ্ট্রারের নাম ও পদবীসহ স্বাক্ষর ও তারিখ : ………</p>';
		h += '</div>';
		return h;
	}
	var PV_NOTE = '<p class="pv-note">এটি দ্রুত দেখার জন্য প্রিভিউ। বড় স্থায়ী অনুচ্ছেদগুলো সংক্ষেপে দেখানো; চূড়ান্ত ফাইল Word ডাউনলোডে (বিজয় ফন্টে) পাবেন।</p>';
	function renderPreview() {
		var box = document.getElementById( 'dw-preview' );
		if ( box ) {
			box.innerHTML = '<div class="pv-bar"><strong>প্রিভিউ</strong> <small>যা লিখছেন সঙ্গে সঙ্গে দেখাচ্ছে</small> <button type="button" class="dw-link" id="dw-pv-hide">লুকান</button></div><div class="pv-desk">' + paperHtml() + '</div>' + PV_NOTE;
		}
		var big = document.getElementById( 'dw-pv-body' );
		if ( big ) { big.innerHTML = paperHtml(); }
	}
	function openFullPreview() {
		var dlg = document.getElementById( 'dw-pv-dlg' );
		if ( ! dlg ) {
			dlg = document.createElement( 'dialog' );
			dlg.id = 'dw-pv-dlg'; dlg.className = 'dw-pv-dlg';
			dlg.innerHTML = '<div class="dw-pv-dlg-bar"><strong>দলিলের প্রিভিউ</strong><button type="button" class="btn btn-outline btn-sm" id="dw-pv-close">বন্ধ করুন</button></div><div class="pv-desk pv-desk--big" id="dw-pv-body"></div>' + PV_NOTE;
			document.body.appendChild( dlg );
			dlg.addEventListener( 'click', function ( e ) { if ( e.target.id === 'dw-pv-close' || e.target === dlg ) { dlg.close(); } } );
		}
		renderPreview();
		if ( dlg.showModal ) { dlg.showModal(); } else { dlg.setAttribute( 'open', '' ); }
	}

	/* ------------------------------------------------------------ autosave */
	var timer = null, inflight = false, dirty = false, lastSaved = '';
	function statusText( t, cls ) { var el = document.getElementById( 'dw-status' ); if ( el ) { el.textContent = t; el.className = 'dw-status ' + ( cls || '' ); } }

	function payload() {
		compute();
		var f = S.f;
		var defaults = { sub_office: f.sub_office, district: f.district, upazila: f.upazila, land_office: f.land_office, id: f.id, wr: f.wr, w1: { upazila: f.w1.upazila, district: f.w1.district }, w2: { upazila: f.w2.upazila, district: f.w2.district } };
		var title = ( ( S.donors[ 0 ] && S.donors[ 0 ].name ) || '' ) + ( S.donees[ 0 ] && S.donees[ 0 ].name ? ' → ' + S.donees[ 0 ].name : '' );
		title = ( title ? title + ' — ' : '' ) + ( f.deed_type || '' ) + ( f.mouza_name ? ' (' + f.mouza_name + ')' : '' );
		return { title: title, data: { f: S.f, donees: S.donees, donors: S.donors, out: S.out, defaults: defaults } };
	}

	function send( beacon ) {
		var p = payload(), json = JSON.stringify( p.data );
		if ( json === lastSaved && S.id ) { return Promise.resolve(); }
		var fd = new FormData();
		fd.append( 'action', 'sojib68_dw_save' ); fd.append( 'nonce', BOOT.nonce ); fd.append( 'id', S.id || 0 ); fd.append( 'title', p.title ); fd.append( 'data', json );
		if ( beacon && navigator.sendBeacon ) { navigator.sendBeacon( BOOT.ajaxUrl, fd ); return Promise.resolve(); }
		inflight = true; statusText( 'সেভ হচ্ছে…', 'is-saving' );
		return fetch( BOOT.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } ).then( function ( res ) {
			inflight = false;
			if ( ! res || ! res.success ) { throw new Error( ( res && res.data && res.data.message ) || 'সেভ হয়নি' ); }
			lastSaved = json; dirty = false;
			if ( ! S.id ) { S.id = res.data.id; try { history.replaceState( null, '', BOOT.listUrl + '?draft=' + S.id ); } catch ( e ) {} }
			statusText( '✓ সেভ হয়েছে — ' + new Date().toLocaleTimeString( 'en-GB' ), 'is-ok' );
			if ( dirty ) { schedule(); }
		} ).catch( function ( e ) {
			inflight = false; dirty = true;
			statusText( '⚠ সেভ হয়নি: ' + e.message + ' (আবার চেষ্টা হবে)', 'is-err' );
			clearTimeout( timer ); timer = setTimeout( flush, 4000 );
		} );
	}
	function flush() { clearTimeout( timer ); timer = null; if ( inflight ) { schedule(); return Promise.resolve(); } return send( false ); }
	function schedule() { dirty = true; statusText( 'লেখা হচ্ছে…', '' ); clearTimeout( timer ); timer = setTimeout( flush, 700 ); }
	window.addEventListener( 'beforeunload', function () { if ( dirty ) { send( true ); } } );
	document.addEventListener( 'visibilitychange', function () { if ( document.hidden && dirty ) { flush(); } } );

	/* ------------------------------------------------------------ events */
	var nidTimers = {};
	app.addEventListener( 'input', function ( e ) {
		var el = e.target;
		if ( el.matches( '.dw-search input' ) ) { return searchInput( el ); }
		var p = el.getAttribute && el.getAttribute( 'data-p' ), a = el.getAttribute && el.getAttribute( 'data-auto' );
		if ( p ) {
			setp( p, el.value );
			if ( el.hasAttribute( 'data-nid' ) ) { nidChanged( el, p ); }
		} else if ( a ) {
			var parts = a.split( '.' ), key = parts.pop(), holder = getp( parts.join( '.' ) );
			if ( ! holder ) { setp( parts.join( '.' ), {} ); holder = getp( parts.join( '.' ) ); }
			var autoVal = el.placeholder;
			if ( el.value === autoVal || el.value === '' ) { delete holder[ key ]; } else { holder[ key ] = el.value; }
			el.classList.toggle( 'is-manual', holder[ key ] != null );
		} else { return; }
		refreshAuto();
		schedule();
	} );

	app.addEventListener( 'change', function ( e ) {
		if ( e.target.id !== 'dw-mouza-pick' || ! e.target.value ) { return; }
		var m = MZ.filter( function ( x ) { return x.id === e.target.value; } )[ 0 ];
		if ( m ) { applyMouza( m, true ); render(); schedule(); }
	} );

	app.addEventListener( 'blur', function ( e ) {
		var el = e.target;
		var bp = el.getAttribute && el.getAttribute( 'data-p' );
		if ( bp === 'f.mouza_name' || bp === 'f.mouza_no' ) {
			var mm = findMouza();
			if ( mm && applyMouza( mm, false ) ) { render(); schedule(); }
		}
		if ( el.hasAttribute && el.hasAttribute( 'data-nid' ) ) { var p = el.getAttribute( 'data-p' ); el.value = fmtNid( el.value ) || el.value; setp( p, el.value ); lookupNid( el, p, true ); schedule(); }
	}, true );

	app.addEventListener( 'click', function ( e ) {
		var t = e.target.closest ? e.target.closest( 'button,a' ) : null;
		if ( ! t ) { return; }
		var v;
		if ( ( v = t.getAttribute( 'data-add-party' ) ) ) { S[ v ].push( emptyParty() ); render(); schedule(); }
		else if ( ( v = t.getAttribute( 'data-del-party' ) ) ) { var g = v.split( ':' ); if ( window.confirm( 'এই ব্যক্তিকে সরাবেন?' ) ) { S[ g[ 0 ] ].splice( +g[ 1 ], 1 ); render(); schedule(); } }
		else if ( ( v = t.getAttribute( 'data-copy-addr' ) ) ) { var c = v.split( ':' ), pp = S[ c[ 0 ] ][ +c[ 1 ] ]; pp.present = pp.perm; render(); schedule(); }
		else if ( t.hasAttribute( 'data-add-sched' ) ) { S.f.schedule.push( { khatian: '', rs_dag: '', cs_dag: '', 'class': S.f.land_class || '', total: '', transferred: '' } ); render(); schedule(); }
		else if ( ( v = t.getAttribute( 'data-del-sched' ) ) !== null ) { S.f.schedule.splice( +v, 1 ); render(); schedule(); }
		else if ( ( v = t.getAttribute( 'data-reset' ) ) ) { var rp = v.split( '.' ), rk = rp.pop(), holder = getp( rp.join( '.' ) ); if ( holder ) { delete holder[ rk ]; } refreshAuto(); var tx = app.querySelector( '[data-auto="' + v + '"]' ); if ( tx ) { tx.value = tx.placeholder; tx.classList.remove( 'is-manual' ); } schedule(); }
		else if ( ( v = t.getAttribute( 'data-pick' ) ) ) { pickResult( t ); }
		else if ( ( v = t.getAttribute( 'data-use-saved' ) ) ) { useSaved( t ); }
		else if ( t.id === 'dw-pv-hide' || t.id === 'dw-pv-show' ) { var hide = t.id === 'dw-pv-hide'; try { window.localStorage.setItem( 'sojib68DwPreviewHidden', hide ? '1' : '0' ); } catch ( err ) {} app.querySelector( '.dw-edit' ).classList.toggle( 'is-nopreview', hide ); }
		else if ( t.id === 'dw-pv-full' ) { openFullPreview(); }
		else if ( t.id === 'dw-save-mouza' ) { saveMouza(); }
		else if ( t.id === 'dw-download' || t.id === 'dw-download2' ) { e.preventDefault(); download(); }
	} );

	function saveMouza() {
		var f = S.f, st = document.getElementById( 'dw-mouza-st' );
		if ( ! f.mouza_name ) { st.textContent = 'আগে মৌজার নাম লিখুন'; return; }
		var m = { id: f.mouza_id || '', name: f.mouza_name, no: f.mouza_no, union: f.union, upazila: f.upazila, district: f.district, jl_no: f.jl_no, village: f.village, land_office: f.land_office, sub_office: f.sub_office, land_class: f.land_class, intro: f.ownership };
		post( 'sojib68_dw_mouza_save', { mouza: JSON.stringify( m ) } ).then( function ( res ) {
			if ( res && res.success ) { f.mouza_id = res.data.id; st.textContent = '✓ মৌজা তালিকায় সেভ হয়েছে (পেজ রিফ্রেশ করলে বাঁ পাশে দেখাবে)'; schedule(); }
			else { st.textContent = '⚠ ' + ( ( res && res.data && res.data.message ) || 'সেভ হয়নি' ); }
		} );
	}

	function download() {
		statusText( 'সেভ করে ফাইল তৈরি হচ্ছে…', 'is-saving' );
		flush().then( function () {
			if ( ! S.id ) { statusText( '⚠ আগে কিছু লিখুন', 'is-err' ); return; }
			window.location.href = BOOT.docxUrl + S.id;
		} );
	}

	/* ------------------------------------------------------------ NID / name lookup */
	function post( action, extra ) {
		var fd = new FormData(); fd.append( 'action', action ); fd.append( 'nonce', BOOT.nonce );
		Object.keys( extra ).forEach( function ( k ) { fd.append( k, extra[ k ] ); } );
		return fetch( BOOT.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } );
	}
	var FILL = [ 'name', 'spouse', 'father', 'mother', 'dob', 'religion', 'profession', 'nationality', 'perm', 'present' ];

	function nidChanged( el, path ) {
		clearTimeout( nidTimers[ path ] );
		nidTimers[ path ] = setTimeout( function () { lookupNid( el, path, false ); }, 600 );
	}

	function lookupNid( el, path, onBlur ) {
		var key = nidKey( el.value ), card = el.closest( '.df-party' ), st = card && card.querySelector( '.dw-nid-st' );
		if ( key.length < 8 || ! st ) { if ( st ) { st.textContent = ''; } return; }
		post( 'sojib68_dw_party_get', { nid: key } ).then( function ( res ) {
			var party = res && res.success && res.data.party;
			if ( ! party ) { st.textContent = key.length >= 10 ? 'নতুন ব্যক্তি — তথ্য লিখলে পরে নিজে নিজে মনে থাকবে' : ''; return; }
			var g = card.getAttribute( 'data-group' ), i = +card.getAttribute( 'data-i' ), cur = S[ g ][ i ];
			if ( ! cur.name ) {
				FILL.forEach( function ( k ) { if ( party[ k ] ) { cur[ k ] = party[ k ]; } } );
				render(); schedule();
				var again = app.querySelector( '.df-party[data-group="' + g + '"][data-i="' + i + '"] .dw-nid-st' );
				if ( again ) { again.textContent = '✓ সেভ করা তথ্য থেকে ভরা হয়েছে'; }
			} else {
				st.innerHTML = 'সেভ করা তথ্য আছে: <b>' + esc( party.name ) + '</b> <button type="button" class="dw-link" data-use-saved="' + esc( JSON.stringify( party ) ) + '">এটি ব্যবহার করুন</button>';
			}
		} );
	}

	function useSaved( btn ) {
		var card = btn.closest( '.df-party' ), g = card.getAttribute( 'data-group' ), i = +card.getAttribute( 'data-i' ), party = {};
		try { party = JSON.parse( btn.getAttribute( 'data-use-saved' ) ); } catch ( e ) { return; }
		FILL.forEach( function ( k ) { if ( party[ k ] != null ) { S[ g ][ i ][ k ] = party[ k ]; } } );
		render(); schedule();
	}

	var searchTimer = null;
	function searchInput( el ) {
		var box = el.closest( '.dw-search' ), res = box.querySelector( '.dw-results' ), q = el.value.trim();
		clearTimeout( searchTimer );
		if ( q.length < 2 ) { res.hidden = true; return; }
		searchTimer = setTimeout( function () {
			post( 'sojib68_dw_party_search', { q: q } ).then( function ( r ) {
				var list = ( r && r.success && r.data.results ) || [];
				res.hidden = false;
				res.innerHTML = list.length ? list.map( function ( p ) {
					return '<button type="button" class="dw-res" data-pick="' + esc( JSON.stringify( p ) ) + '"><b>' + esc( p.name ) + '</b> <small>' + esc( p.father ? 'পিতা: ' + p.father : '' ) + ' · NID ' + esc( p.nid_display || p.nid ) + '</small></button>';
				} ).join( '' ) : '<div class="dw-res-empty">কিছু পাওয়া যায়নি</div>';
			} );
		}, 300 );
	}

	function pickResult( btn ) {
		var box = btn.closest( '.dw-search' ), g = box.getAttribute( 'data-search' ), party = {};
		try { party = JSON.parse( btn.getAttribute( 'data-pick' ) ); } catch ( e ) { return; }
		var list = S[ g ], target = list[ list.length - 1 ];
		if ( target.name || target.nid ) { target = emptyParty(); list.push( target ); }
		FILL.forEach( function ( k ) { if ( party[ k ] != null ) { target[ k ] = party[ k ]; } } );
		target.nid = fmtNid( party.nid || '' ) || party.nid || '';
		render(); schedule();
	}

	document.addEventListener( 'click', function ( e ) { if ( ! e.target.closest( '.dw-search' ) ) { [].forEach.call( app.querySelectorAll( '.dw-results' ), function ( r ) { r.hidden = true; } ); } } );

	/* test hook */
	window.sojib68DwDebug = { words: words, lakh: lakh, areaText: areaText, priceText: priceText, banglaDate: banglaDate, fmtNid: fmtNid, state: S, compute: compute };

	render();
	if ( S.id ) { statusText( 'খোলা হয়েছে — যা বদলাবেন নিজে নিজে সেভ হবে', 'is-ok' ); lastSaved = JSON.stringify( payload().data ); }
	if ( loadedFilled ) { lastSaved = ''; schedule(); }
}() );

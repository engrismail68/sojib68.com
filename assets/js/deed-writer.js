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
	// New deed started from a saved mouza: its fixed details are filled in already.
	if ( ! saved && BOOT.newMouza ) {
		var nm = BOOT.newMouza;
		S.f.mouza_id = nm.id || '';
		[ [ 'mouza_name', 'name' ], [ 'mouza_no', 'no' ], [ 'union', 'union' ], [ 'upazila', 'upazila' ], [ 'district', 'district' ], [ 'jl_no', 'jl_no' ], [ 'village', 'village' ], [ 'land_office', 'land_office' ], [ 'sub_office', 'sub_office' ], [ 'land_class', 'land_class' ], [ 'ownership', 'intro' ] ].forEach( function ( p ) {
			if ( nm[ p[ 1 ] ] ) { S.f[ p[ 0 ] ] = nm[ p[ 1 ] ]; }
		} );
		if ( S.f.schedule && S.f.schedule[ 0 ] && nm.land_class ) { S.f.schedule[ 0 ][ 'class' ] = nm.land_class; }
	}
	S.donees.forEach( function ( p ) { if ( ! p.o ) { p.o = {}; } } );
	S.donors.forEach( function ( p ) { if ( ! p.o ) { p.o = {}; } } );

	/* ------------------------------------------------------------ compute */
	function compute() {
		var f = S.f, o = f.o || {};
		var auto = {};
		auto.mouza_short = ( f.mouza_no ? bn( f.mouza_no ) + 'নং ' : '' ) + ( f.mouza_name || '' );
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
	function field( label, path, opts ) {
		opts = opts || {};
		var cls = 'dw-f' + ( opts.wide ? ' dw-wide' : '' ) + ( opts.full ? ' dw-full' : '' );
		var v = getp( path );
		var input = opts.area
			? '<textarea data-p="' + esc( path ) + '" rows="' + ( opts.rows || 3 ) + '"' + ( opts.ph ? ' placeholder="' + esc( opts.ph ) + '"' : '' ) + '>' + esc( v ) + '</textarea>'
			: '<input type="' + ( opts.type || 'text' ) + '" data-p="' + esc( path ) + '" value="' + esc( v ) + '"' + ( opts.ph ? ' placeholder="' + esc( opts.ph ) + '"' : '' ) + ( opts.nid ? ' data-nid="1" inputmode="numeric"' : '' ) + ( opts.list ? ' list="' + opts.list + '"' : '' ) + ' autocomplete="off">';
		return '<label class="' + cls + '"><span>' + label + '</span>' + input + ( opts.note ? '<small>' + opts.note + '</small>' : '' ) + '</label>';
	}

	/** Computed text the user may overwrite; ↺ restores the automatic value. */
	function autoField( label, key, scope, rows ) {
		var holder = scope || 'f.o';
		return '<label class="dw-f dw-full dw-autof"><span>' + label + ' <em>(নিজে নিজে হয়, চাইলে বদলান)</em> <button type="button" class="dw-reset" data-reset="' + holder + '.' + key + '" title="স্বয়ংক্রিয় মান ফেরান">↺</button></span>' +
			'<textarea rows="' + ( rows || 1 ) + '" data-auto="' + holder + '.' + key + '"></textarea></label>';
	}

	function partyCard( group, i ) {
		var p = S[ group ][ i ], base = group + '.' + i + '.';
		var title = group === 'donees' ? 'গ্রহীতা' : 'দাতা';
		var n = S[ group ].length;
		return '<div class="dw-party" data-group="' + group + '" data-i="' + i + '">' +
			'<div class="dw-party-h"><strong>' + title + ' ' + ( i + 1 ) + '</strong>' + ( n > 1 ? '<button type="button" class="dw-x" data-del-party="' + group + ':' + i + '">মুছুন ×</button>' : '' ) + '</div>' +
			'<div class="dw-grid">' +
			field( 'জাতীয় পরিচয়পত্র (NID) নম্বর', base + 'nid', { nid: true, ph: 'যেমন 823 541 7337', note: '<span class="dw-nid-st"></span>' } ) +
			field( 'নাম', base + 'name', { wide: true } ) +
			field( 'স্বামীর নাম (বিবাহিতা মহিলা হলে)', base + 'spouse' ) +
			field( 'পিতার নাম', base + 'father' ) + field( 'মাতার নাম', base + 'mother' ) +
			field( 'জন্ম তারিখ', base + 'dob', { ph: 'যেমন ১০/০৩/১৯৮৭' } ) +
			field( 'ধর্ম', base + 'religion' ) + field( 'পেশা', base + 'profession' ) + field( 'জাতীয়তা', base + 'nationality' ) +
			field( 'স্থায়ী ঠিকানা', base + 'perm', { area: true, full: true, rows: 2, ph: 'বাসা/হোল্ডিং: …, গ্রাম/রাস্তা: …, ডাকঘর: …, উপজেলা: …, জেলা: …' } ) +
			field( 'বর্তমান ঠিকানা', base + 'present', { area: true, full: true, rows: 2, note: '<button type="button" class="dw-link" data-copy-addr="' + group + ':' + i + '">স্থায়ীর মতোই</button>' } ) +
			( n > 1 ? field( 'এই ব্যক্তির অংশ (শতাংশ)', base + 'share', { type: 'text', ph: 'যেমন 3' } ) + autoField( 'অংশের লেখা', 'share_text', group + '.' + i + '.o', 1 ) : '' ) +
			'</div></div>';
	}

	function sched( i ) {
		var b = 'f.schedule.' + i + '.';
		return '<div class="dw-sched"><div class="dw-party-h"><strong>দাগ ' + ( i + 1 ) + '</strong>' + ( S.f.schedule.length > 1 ? '<button type="button" class="dw-x" data-del-sched="' + i + '">মুছুন ×</button>' : '' ) + '</div><div class="dw-grid">' +
			field( 'খতিয়ান (সি.এস/আর.এস/বর্তমান)', b + 'khatian', { area: true, full: true, rows: 2, ph: 'যেমন সি.এস ০৮নং খং … আগত আর.এস ৩৮ নং খং …' } ) +
			field( 'আর.এস দাগ', b + 'rs_dag' ) + field( 'সি.এস দাগ', b + 'cs_dag', { ph: 'যেমন ১৭১৩ (এক হাজার সাতশত তেরো) দাগ' } ) + field( 'শ্রেণি', b + 'class' ) +
			field( 'দাগে মোট জমি', b + 'total', { ph: 'যেমন মোঃ ১৮ শতাংশ হইতে' } ) + field( 'হস্তান্তরিত জমি', b + 'transferred', { ph: 'যেমন মোঃ ০৫ শতাংশ' } ) +
			'</div></div>';
	}

	function personBlock( title, key, extra ) {
		var b = 'f.' + key + '.';
		return '<div class="dw-sub"><h4>' + title + '</h4><div class="dw-grid">' + field( 'নাম', b + 'name', { wide: true } ) + field( 'পিতার নাম', b + 'father' ) + field( 'মাতার নাম', b + 'mother' ) +
			field( 'গ্রাম/রোড', b + 'village' ) + field( 'ডাকঘর', b + 'post' ) + field( 'উপজেলা', b + 'upazila' ) + field( 'জেলা', b + 'district' ) + ( extra || '' ) + '</div></div>';
	}

	function render() {
		var h = '';
		h += '<div class="dw-top"><a class="btn btn-outline btn-sm" href="' + esc( BOOT.listUrl ) + '">← সব দলিল</a>' +
			'<span class="dw-status" id="dw-status">নতুন — লিখতে শুরু করুন, নিজে নিজে সেভ হবে</span>' +
			'<a class="btn btn-primary btn-sm" id="dw-download" href="#">Word ডাউনলোড</a></div>' +
			'<div class="dw-warn" id="dw-warn" hidden></div>';

		h += '<section class="dw-sec"><h3>১–২ নম্বর: মূল তথ্য</h3><div class="dw-grid">' +
			field( 'রেজিস্ট্রি অফিসের নাম', 'f.sub_office', { wide: true } ) + field( 'দলিলের প্রকৃতি', 'f.deed_type', { list: 'dw-types' } ) +
			field( 'জেলা', 'f.district' ) + field( 'উপজেলা/থানা', 'f.upazila' ) + field( 'ইউনিয়ন/ওয়ার্ড', 'f.union', { ph: 'যেমন ৯ নং ভোলাকোট' } ) +
			field( 'মৌজা নম্বর', 'f.mouza_no', { ph: 'যেমন 99' } ) + field( 'মৌজার নাম', 'f.mouza_name', { ph: 'যেমন তিয়রী' } ) + field( 'শ্রেণি', 'f.land_class' ) +
			field( 'জমির পরিমাণ (শতাংশ)', 'f.area', { ph: 'যেমন 5 বা 5.25' } ) + field( 'মূল্য (টাকা)', 'f.price', { ph: 'যেমন 500000' } ) + field( 'দলিলের ফর্দ সংখ্যা', 'f.pages', { ph: 'যেমন 7' } ) +
			'<div class="dw-f dw-full"><button type="button" class="btn btn-outline btn-sm" id="dw-save-mouza">এই মৌজার তথ্য মৌজা তালিকায় সেভ করুন</button> <small id="dw-mouza-st"></small></div>' +
			autoField( 'পরিমাণ (অংকে ও কথায়)', 'area_text' ) + autoField( 'মূল্য (অংকে ও কথায়)', 'price_text' ) + autoField( 'মূল্য পরিশোধের বিবরণ', 'payment_text' ) + autoField( 'হাত নকশা ও পরিমাপ', 'sqft_text' ) +
			'</div></section>';

		h += '<section class="dw-sec"><h3>৩ নম্বর: গ্রহীতা (ক্রেতা)</h3>' + searchBox( 'donees' ) + S.donees.map( function ( p, i ) { return partyCard( 'donees', i ); } ).join( '' ) +
			'<button type="button" class="btn btn-outline btn-sm" data-add-party="donees">+ আরেকজন গ্রহীতা</button></section>';
		h += '<section class="dw-sec"><h3>৪ নম্বর: দাতা (বিক্রেতা)</h3>' + searchBox( 'donors' ) + S.donors.map( function ( p, i ) { return partyCard( 'donors', i ); } ).join( '' ) +
			'<button type="button" class="btn btn-outline btn-sm" data-add-party="donors">+ আরেকজন দাতা</button></section>';

		h += '<section class="dw-sec"><h3>৫–৭ নম্বর: প্রতিনিধি ও মালিকানার বিবরণ</h3><div class="dw-grid">' +
			field( '৫। আমমোক্তার/প্রতিনিধি/অভিভাবক', 'f.proxy_text', { full: true } ) + field( '৬। আমমোক্তারনামার বিবরণ', 'f.poa_text', { full: true } ) +
			field( '৭। মালিকানার ধারাবাহিক বিবরণ', 'f.ownership', { area: true, full: true, rows: 10, ph: 'জেলা-…, উপজেলা ও সাব-রেজিস্ট্রি অফিস … এর পুরো বিবরণ এখানে লিখুন বা পেস্ট করুন' } ) +
			'</div></section>';

		h += '<section class="dw-sec"><h3>১০ নম্বর: সম্পাদনের তারিখ</h3><div class="dw-grid">' + field( 'ইংরেজি তারিখ', 'f.exec_greg', { type: 'date' } ) + autoField( 'বাংলা ও ইংরেজি তারিখ', 'exec_date' ) + '</div></section>';

		h += '<section class="dw-sec"><h3>১১ নম্বর: সম্পত্তির তফসিল</h3><div class="dw-grid">' + field( 'গ্রাম', 'f.village' ) + field( 'জে.এল. নং', 'f.jl_no', { ph: 'যেমন সি.এস ১৪১ আর.এস ৯৯ নং' } ) + field( 'ভূমি অফিসের নাম', 'f.land_office', { wide: true } ) +
			autoField( 'তফসিলের শুরুর লাইন', 'schedule_head', 'f.o', 2 ) + '</div>' +
			S.f.schedule.map( function ( r, i ) { return sched( i ); } ).join( '' ) + '<button type="button" class="btn btn-outline btn-sm" data-add-sched="1">+ আরেকটি দাগ</button>' +
			'<div class="dw-grid">' + autoField( 'মোট জমির পরিমাণ (তফসিলের নিচে)', 'total_text' ) + '</div></section>';

		h += '<section class="dw-sec"><h3>১২ নম্বর: চৌহদ্দি</h3><div class="dw-grid">' +
			field( 'উত্তরে', 'f.north' ) + field( 'উত্তরে মাপ (ফুট)', 'f.n_ft', { ph: 'যেমন 38' } ) + field( 'দক্ষিণে', 'f.south' ) + field( 'দক্ষিণে মাপ (ফুট)', 'f.s_ft' ) +
			field( 'পূর্বে', 'f.east' ) + field( 'পূর্বে মাপ (ফুট)', 'f.e_ft' ) + field( 'পশ্চিমে', 'f.west' ) + field( 'পশ্চিমে মাপ (ফুট)', 'f.w_ft' ) +
			autoField( 'চৌহদ্দির পুরো লেখা', 'boundaries', 'f.o', 3 ) + '</div></section>';

		h += '<section class="dw-sec"><h3>১৮–২০ নম্বর: সাক্ষী, সনাক্তকারী, দলিল লেখক</h3>' +
			personBlock( '(ক) সাক্ষী', 'w1' ) + personBlock( '(খ) সাক্ষী', 'w2' ) +
			personBlock( '১৯। সনাক্তকারী', 'id' ) + personBlock( '২০। দলিল লেখক', 'wr', field( 'সনদ নং', 'f.wr.sanad' ) ) +
			'<p class="dw-hint">সাক্ষী ছাড়া অন্যগুলো পরের দলিলে নিজে নিজে ভরা থাকবে।</p></section>';

		h += '<section class="dw-sec"><h3>২১ নম্বর: দলিল দাতার হলফনামা</h3><p class="dw-hint">হলফনামার স্থায়ী ঘোষণাগুলো (ক–চ ও “আমি/আমরা আরও ঘোষনা করিতেছি”) আপনার ছাঁচ থেকে নিজে নিজে আসে। শুধু হলফকারীর পরিচয়ের লাইনটি দাতার তথ্য থেকে ভরা হয়; চাইলে এখানে বদলান।</p><div class="dw-grid">' +
			autoField( 'হলফকারীগনের নাম, পিতার নাম, ঠিকানা ও বয়স', 'affidavit_head', 'f.o', 4 ) + '</div></section>';
		h += '<section class="dw-sec"><h3>২২ নম্বর: সাব-রেজিষ্ট্রারের নাম ও পদবীসহ স্বাক্ষর ও তারিখ</h3><p class="dw-hint">এটি রেজিস্ট্রি অফিসে সাব-রেজিস্ট্রার নিজে পূরণ করেন, তাই Word ফাইলে শিরোনামটি ফাঁকা জায়গাসহ থাকে। এখানে কিছু লেখার নেই।</p></section>';
		h += '<section class="dw-sec dw-end"><button type="button" class="btn btn-primary" id="dw-download2">Word ডাউনলোড (বিজয় / SutonnyMJ)</button>' +
			'<p class="dw-hint">ডাউনলোড করা ফাইলে ডান ক্লিক → Properties → Unblock টিক দিয়ে খুলুন। ফাইলটি Word-এ খুলে একবার দেখে নিন; SutonnyMJ ফন্ট ইনস্টল থাকতে হবে।</p></section>';
		h += '<datalist id="dw-types"><option value="সাফ কবলা"><option value="হেবা"><option value="বণ্টননামা"><option value="বায়না"><option value="দান"></datalist>';
		app.innerHTML = h;
		refreshAuto();
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

	app.addEventListener( 'blur', function ( e ) {
		var el = e.target;
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
		var key = nidKey( el.value ), card = el.closest( '.dw-party' ), st = card && card.querySelector( '.dw-nid-st' );
		if ( key.length < 8 || ! st ) { if ( st ) { st.textContent = ''; } return; }
		post( 'sojib68_dw_party_get', { nid: key } ).then( function ( res ) {
			var party = res && res.success && res.data.party;
			if ( ! party ) { st.textContent = key.length >= 10 ? 'নতুন ব্যক্তি — তথ্য লিখলে পরে নিজে নিজে মনে থাকবে' : ''; return; }
			var g = card.getAttribute( 'data-group' ), i = +card.getAttribute( 'data-i' ), cur = S[ g ][ i ];
			if ( ! cur.name ) {
				FILL.forEach( function ( k ) { if ( party[ k ] ) { cur[ k ] = party[ k ]; } } );
				render(); schedule();
				var again = app.querySelector( '.dw-party[data-group="' + g + '"][data-i="' + i + '"] .dw-nid-st' );
				if ( again ) { again.textContent = '✓ সেভ করা তথ্য থেকে ভরা হয়েছে'; }
			} else {
				st.innerHTML = 'সেভ করা তথ্য আছে: <b>' + esc( party.name ) + '</b> <button type="button" class="dw-link" data-use-saved="' + esc( JSON.stringify( party ) ) + '">এটি ব্যবহার করুন</button>';
			}
		} );
	}

	function useSaved( btn ) {
		var card = btn.closest( '.dw-party' ), g = card.getAttribute( 'data-group' ), i = +card.getAttribute( 'data-i' ), party = {};
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
}() );

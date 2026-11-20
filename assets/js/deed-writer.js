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
	/** দাগে মোট জমি / হস্তান্তরিত জমি: 18 → ১৮ শতাংশ হইতে / ১০ শতাংশ ; anything that is not a plain number stays as typed. */
	function shotText( v, suffix ) {
		var t = en( String( v == null ? '' : v ) ).trim();
		if ( /^\d+(\.\d+)?$/.test( t ) && parseFloat( t ) > 0 ) { return bn( t ) + ' শতাংশ' + ( suffix || '' ); }
		return String( v == null ? '' : v ).trim();
	}
	/** আর.এস দাগ: 1713 → ১৭১৩ (এক হাজার সাতশত তেরো) দাগ ; anything that is not a plain number stays as typed. */
	function dagText( v ) {
		var t = en( String( v == null ? '' : v ) ).trim();
		if ( /^\d+$/.test( t ) && +t > 0 ) { return bn( t ) + ' (' + words( +t ) + ') দাগ'; }
		return String( v == null ? '' : v ).trim();
	}
	/** ১ম, ২য়, ৩য়, ৪র্থ, ৫ম, ৬ষ্ঠ, ৭ম … */
	function ord( n ) { return bn( n ) + ( n === 1 ? 'ম' : n === 2 || n === 3 ? 'য়' : n === 4 ? 'র্থ' : n === 6 ? 'ষ্ঠ' : 'ম' ); }
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
	function emptyParty() { return { nid: '', name: '', gender: '', spouse: '', father: '', mother: '', dob: '', religion: DEF.religion || 'ইসলাম', profession: '', nationality: DEF.nationality || 'বাংলাদেশী', perm: '', present: '', share: '', o: {} }; }
	function person( k ) { var d = ( DEF[ k ] || {} ); return { name: d.name || '', father: d.father || '', mother: d.mother || '', village: d.village || '', post: d.post || '', upazila: d.upazila || 'রামগঞ্জ', district: d.district || 'লক্ষ্মীপুর', sanad: d.sanad || '' }; }

	var saved = BOOT.data && BOOT.data.f ? BOOT.data : null;
	var S = {
		id: BOOT.id || 0,
		f: saved ? saved.f : {
			sub_office: DEF.sub_office || 'রামগঞ্জ সাব রেজিস্ট্রি অফিস।', deed_type: 'সাফ কবলা', district: DEF.district || 'লক্ষ্মীপুর', upazila: DEF.upazila || 'রামগঞ্জ',
			union: '', mouza_no: '', mouza_name: '', land_class: 'নাল', area: '', price: '', pages: '', ownership: '',
			exec_greg: new Date().toISOString().slice( 0, 10 ), village: '', land_office: DEF.land_office || 'সহকারী কমিশনার ভূমি অফিস, রামগঞ্জ।', jl_no: '',
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
		// the mouza's usual শ্রেণী goes into the first land line
		if ( m.land_class && S.f.lands && S.f.lands[ 0 ] && ( overwrite || ! S.f.lands[ 0 ].cls ) && S.f.lands[ 0 ].cls !== m.land_class ) { S.f.lands[ 0 ].cls = m.land_class; changed = true; }
		if ( m.id && S.f.mouza_id !== m.id ) { S.f.mouza_id = m.id; changed = true; }
		if ( m.land_class && S.f.khatians && S.f.khatians[ 0 ] && S.f.khatians[ 0 ].dags[ 0 ] && ! S.f.khatians[ 0 ].dags[ 0 ].cls ) { S.f.khatians[ 0 ].dags[ 0 ].cls = m.land_class; }
		return changed;
	}

	if ( ! S.f.o ) { S.f.o = {}; }
	if ( ! saved && BOOT.newMouza ) { applyMouza( BOOT.newMouza, true ); }
	// Several land lines (ভিটি, পুকুর, বাড়ী …), each with its own area and price; older drafts have a single area/price.
	if ( ! S.f.lands || ! S.f.lands.length ) { S.f.lands = [ { cls: S.f.land_class || 'নাল', area: S.f.area || '', price: S.f.price || '', o: {} } ]; }
	S.f.lands.forEach( function ( l ) { if ( ! l.o ) { l.o = {}; } } );
	// ৫ আমমোক্তার/প্রতিনিধি/অভিভাবক and ৬ আমমোক্তারনামা: pick who (donor / donee / someone else) instead of typing the text.
	if ( ! S.f.rep ) { S.f.rep = { mode: 'none', idx: 0, role: 'অভিভাবক' }; }
	[ 'nid', 'name', 'spouse', 'father', 'mother', 'dob', 'profession', 'perm', 'present' ].forEach( function ( k ) { if ( S.f.rep[ k ] == null ) { S.f.rep[ k ] = ''; } } );
	if ( S.f.rep.addr && ! S.f.rep.perm ) { S.f.rep.perm = S.f.rep.addr; }
	if ( S.f.rep.religion == null ) { S.f.rep.religion = DEF.religion || 'ইসলাম'; }
	if ( S.f.rep.nationality == null ) { S.f.rep.nationality = DEF.nationality || 'বাংলাদেশী'; }
	if ( ! S.f.poa ) { S.f.poa = { mode: 'none', no: '', date: '', office: '' }; }
	if ( S.f.proxy_text && S.f.proxy_text.trim() && S.f.proxy_text.trim() !== 'প্রযোজ্য নহে।' && S.f.rep.mode === 'none' && ! S.f.o.proxy_text ) { S.f.o.proxy_text = S.f.proxy_text; }
	if ( S.f.poa_text && S.f.poa_text.trim() && S.f.poa_text.trim() !== 'প্রযোজ্য নহে।' && S.f.poa.mode === 'none' && ! S.f.o.poa_text ) { S.f.o.poa_text = S.f.poa_text; }
	delete S.f.proxy_text; delete S.f.poa_text;
	// তফসিল: one or more khatians, each with one or more dags (several dags can share a khatian).
	function newDag( cls ) { return { rs_dag: '', cs_dag: '', cls: cls || '', total: '', transferred: '' }; }
	if ( ! S.f.khatians || ! S.f.khatians.length ) {
		S.f.khatians = ( S.f.schedule && S.f.schedule.length ? S.f.schedule : [ { khatian: '', class: S.f.land_class || 'নাল' } ] ).map( function ( r ) {
			return { khatian: r.khatian || '', dags: [ { rs_dag: r.rs_dag || '', cs_dag: r.cs_dag || '', cls: r[ 'class' ] || '', total: r.total || '', transferred: r.transferred || '' } ] };
		} );
	}
	delete S.f.schedule;
	// চৌহদ্দি: one set (উত্তর/দক্ষিণ/পূর্ব/পশ্চিম) or several when the land has separate boundaries.
	if ( ! S.f.bounds || ! S.f.bounds.length ) { S.f.bounds = [ { title: '', north: S.f.north || '', south: S.f.south || '', east: S.f.east || '', west: S.f.west || '' } ]; }
	[ 'north', 'south', 'east', 'west', 'n_ft', 's_ft', 'e_ft', 'w_ft' ].forEach( function ( k ) { delete S.f[ k ]; } );
	if ( S.f.sketch_lines == null ) { S.f.sketch_lines = 10; }
	// Singular / plural words used in item 7 (আমি/আমরা, আপনি/আপনারা …): [একবচন, বহুবচন]. d_* follow the donors, g_* the donees.
	var VB_DEFAULTS = {
		d_ami: [ 'আমি', 'আমরা' ], d_amar: [ 'আমার', 'আমাদের' ], d_datar: [ 'দাতার', 'দাতাগণের', 'দাত্রীর', 'দাত্রীগণের' ],
		g_apni: [ 'আপনি', 'আপনারা' ], g_grohitar: [ 'গ্রহিতার', 'গ্রহিতার', 'গ্রহিত্রীর', 'গ্রহিত্রীর' ], g_grohita: [ 'গ্রহিতা', 'গ্রহিতাগণ', 'গ্রহিত্রী', 'গ্রহিত্রীগণ' ], g_apnar: [ 'আপনার', 'আপনাদের' ]
	};
	if ( ! S.f.vb ) { S.f.vb = JSON.parse( JSON.stringify( DEF.vb || VB_DEFAULTS ) ); }
	Object.keys( VB_DEFAULTS ).forEach( function ( k ) { if ( ! S.f.vb[ k ] ) { S.f.vb[ k ] = VB_DEFAULTS[ k ].slice(); } else { for ( var vi = S.f.vb[ k ].length; vi < VB_DEFAULTS[ k ].length; vi++ ) { S.f.vb[ k ][ vi ] = VB_DEFAULTS[ k ][ vi ]; } } } );
	var loadedFilled = false;
	if ( saved ) { var m0 = findMouza(); if ( m0 ) { loadedFilled = applyMouza( m0, false ); } }
	S.donees.forEach( function ( p ) { if ( ! p.o ) { p.o = {}; } } );
	S.donors.forEach( function ( p ) { if ( ! p.o ) { p.o = {}; } } );

	/** The representative / guardian as a full person record (same shape as a donor), or null. */
	function repParty() {
		var rp = S.f.rep || {};
		if ( rp.mode === 'donor' || rp.mode === 'donee' ) {
			var p = ( S[ rp.mode === 'donor' ? 'donors' : 'donees' ] || [] )[ +rp.idx || 0 ];
			return p ? { nid: p.nid, name: p.name, spouse: p.spouse, father: p.father, mother: p.mother, dob: p.dob, religion: p.religion, profession: p.profession, nationality: p.nationality, perm: p.perm, present: p.present } : null;
		}
		if ( rp.mode === 'other' ) { return { nid: rp.nid, name: rp.name, spouse: rp.spouse, father: rp.father, mother: rp.mother, dob: rp.dob, religion: rp.religion, profession: rp.profession, nationality: rp.nationality, perm: rp.perm, present: rp.present }; }
		return null;
	}

	/* ------------------------------------------------------------ compute */
	function compute() {
		var f = S.f, o = f.o || {};
		var auto = {};
		auto.mouza_short = ( f.mouza_no ? bn( String( f.mouza_no ).replace( /\s*নং\s*$/, '' ) ) + 'নং ' : '' ) + ( f.mouza_name || '' );
		auto.mouza_head = 'মৌজাঃ ' + ( f.mouza_name || '' );
		var totArea = 0, totPrice = 0, anyArea = false, anyPrice = false;
		( f.lands || [] ).forEach( function ( l ) {
			var a = num( l.area ), p = num( l.price );
			if ( a > 0 ) { totArea += a; anyArea = true; }
			if ( p > 0 ) { totPrice += p; anyPrice = true; }
		} );
		totArea = Math.round( totArea * 10000 ) / 10000; totPrice = Math.round( totPrice * 100 ) / 100;
		auto.area_text = anyArea ? areaText( totArea ) : '';
		auto.price_text = anyPrice ? priceText( totPrice ) : '';
		auto.payment_text = auto.price_text ? auto.price_text + ' নগদ পরিশোধিত।' : '';
		auto.sqft_text = totArea > 0 ? lakh( Math.round( totArea * 435.6 ) ) + ' বর্গফুট।' : '';
		S.totals = { area: totArea, price: totPrice };
		f.land_class = ( f.lands && f.lands[ 0 ] && f.lands[ 0 ].cls ) || f.land_class || '';
		f.area = anyArea ? String( totArea ) : '';
		f.price = anyPrice ? String( totPrice ) : '';
		auto.exec_date = banglaDate( f.exec_greg );
		auto.schedule_head = 'জেলা ঃ ' + ( f.district || '' ) + ' মৌজাঃ ' + ( f.mouza_name || '' ) + ' উপজেলা ঃ ' + ( f.upazila || '' ) + ', জে. এল নং ঃ ' + ( f.jl_no || '' ) + ', ইউনিয়নঃ ' + ( f.union || '' ) + ' গ্রাম ঃ ' + ( f.village || '' ) + ', ভূমি অফিসের নাম ঃ ' + ( f.land_office || '' );
		var rp = f.rep || {};
		auto.proxy_text = rp.mode && rp.mode !== 'none' ? ( rp.role || 'অভিভাবক' ) + ' (বিবরণ নিচে)' : 'প্রযোজ্য নহে।';
		var pa = f.poa || {};
		auto.poa_text = pa.mode === 'yes' ? [ pa.no ? 'দলিল নং ' + pa.no : '', pa.date ? 'তারিখ: ' + pa.date : '', pa.office ? 'রেজিস্ট্রি অফিস: ' + pa.office : '' ].filter( Boolean ).join( ', ' ) + '।' : 'প্রযোজ্য নহে।';
		auto.total_text = 'মোট জমির পরিমান ঃ ' + ( o.area_text || auto.area_text );
		var bset = function ( b ) { return 'উত্তরে: ' + ( b.north || '' ) + ', দক্ষিণে: ' + ( b.south || '' ) + ', পূর্বে: ' + ( b.east || '' ) + ', পশ্চিমে: ' + ( b.west || '' ) + '।'; };
		var bl = f.bounds || [];
		auto.boundaries = ( bl.length > 1 ? bl.map( function ( b, i ) { return ( b.title && b.title.trim() ? b.title.trim() : ord( i + 1 ) + ' চৌহদ্দি' ) + ': ' + bset( b ); } ).join( '\n' ) : ( bl[ 0 ] ? bset( bl[ 0 ] ) : '' ) ) + ' অত্র চৌহুদ্দির মধ্যে বিক্রিত ভূমি বটে।';
		S.auto = auto;

		var out = {};
		[ 'mouza_short', 'mouza_head', 'area_text', 'price_text', 'payment_text', 'sqft_text', 'exec_date', 'schedule_head', 'total_text', 'boundaries', 'proxy_text', 'poa_text' ].forEach( function ( k ) {
			out[ k ] = o[ k ] != null && o[ k ] !== '' ? o[ k ] : auto[ k ];
		} );
		[ 'sub_office', 'deed_type', 'union', 'upazila', 'district', 'ownership', 'pages' ].forEach( function ( k ) { out[ k ] = f[ k ] || ''; } );
		out.w1 = f.w1; out.w2 = f.w2;
		[ 'name', 'father', 'mother', 'village', 'post', 'upazila', 'district' ].forEach( function ( k ) { out[ 'id_' + k ] = f.id[ k ] || ''; out[ 'wr_' + k ] = f.wr[ k ] || ''; } );
		out.wr_sanad = f.wr.sanad || '';
		out.vb = {};
		var allFemale = function ( list ) { return list.length > 0 && list.every( function ( p ) { return p.gender === 'মহিলা' || ( ! p.gender && ( p.spouse || '' ).trim() ); } ); };
		Object.keys( VB_DEFAULTS ).forEach( function ( k ) {
			var list = k.indexOf( 'd_' ) === 0 ? S.donors : S.donees, plural = list.length > 1 ? 1 : 0;
			var idx = ( VB_DEFAULTS[ k ].length > 2 && allFemale( list ) ) ? plural + 2 : plural;
			var cur = f.vb && f.vb[ k ] ? f.vb[ k ] : [];
			out.vb[ k ] = cur[ idx ] || VB_DEFAULTS[ k ][ idx ] || cur[ plural ] || VB_DEFAULTS[ k ][ plural ];
		} );
		out.sketch_lines = parseInt( f.sketch_lines, 10 ); if ( ! isFinite( out.sketch_lines ) ) { out.sketch_lines = 10; }
		out.land_class = ( f.lands || [] ).map( function ( l ) { return l.cls || ''; } ).filter( Boolean ).join( ', ' );
		out.land_rows = ( f.lands || [] ).map( function ( l ) {
			var lo = l.o || {};
			l.area_auto = areaText( l.area ); l.price_auto = priceText( l.price );
			return { area: lo.area_text || l.area_auto, 'class': l.cls || '', price: lo.price_text || l.price_auto };
		} );
		out.schedule_rows = [];
		( f.khatians || [] ).forEach( function ( k ) {
			( k.dags || [] ).forEach( function ( d, j ) {
				if ( ! d.o ) { d.o = {}; }
				d.rs_auto = dagText( d.rs_dag ); d.total_auto = shotText( d.total, ' হইতে' ); d.transferred_auto = shotText( d.transferred, '' );
				out.schedule_rows.push( { khatian: j === 0 ? ( k.khatian || '' ) : '', cs_dag: d.cs_dag || '', rs_dag: d.o.rs_text || d.rs_auto, 'class': d.cls || '', total: d.o.total_text || d.total_auto, transferred: d.o.transferred_text || d.transferred_auto, vm: ( k.dags.length > 1 ? ( j === 0 ? 'restart' : 'cont' ) : '' ), span: j === 0 ? k.dags.length : 0 } );
			} );
		} );

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
		var base = group + '.' + i + '.', p = S[ group ][ i ], n = S[ group ].length, nm = n > 1 ? bn( i + 1 ) + '। ' : '';
		return '<div class="df-party" data-group="' + group + '" data-i="' + i + '">' +
			'<div class="df-party-h"><b>' + nm + ( group === 'donees' ? 'গ্রহীতা' : 'দাতা' ) + '</b>' + ( n > 1 ? '<button type="button" class="dw-x" data-del-party="' + group + ':' + i + '">মুছুন ×</button>' : '' ) + '</div>' +
			drow( 'পরিচিতি নং (NID)', inp( base + 'nid', { nid: true, ph: 'যেমন 823 541 7337' } ), '<small class="dw-nid-st"></small>' ) +
			drow( 'নাম', inp( base + 'name' ) ) +
			drow( 'লিঙ্গ <em>(মহিলা হলে দলিলে গ্রহিত্রী/দাত্রী আসবে)</em>', '<select data-p="' + base + 'gender"><option value=""' + ( ! p.gender ? ' selected' : '' ) + '>— বেছে নিন —</option><option' + ( p.gender === 'পুরুষ' ? ' selected' : '' ) + '>পুরুষ</option><option' + ( p.gender === 'মহিলা' ? ' selected' : '' ) + '>মহিলা</option></select>' ) +
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

	function radios( name, path, opts ) {
		var cur = getp( path );
		return '<div class="df-radios">' + opts.map( function ( o ) { return '<label><input type="radio" name="' + name + '" data-rep="' + esc( path ) + '" value="' + esc( o[ 0 ] ) + '"' + ( cur === o[ 0 ] ? ' checked' : '' ) + '> ' + o[ 1 ] + '</label>'; } ).join( '' ) + '</div>';
	}
	function personSelect( group ) {
		var list = S[ group ], cur = +S.f.rep.idx || 0;
		return '<select data-p="f.rep.idx">' + list.map( function ( p, i ) { return '<option value="' + i + '"' + ( i === cur ? ' selected' : '' ) + '>' + esc( p.name || ( ( group === 'donors' ? 'দাতা ' : 'গ্রহীতা ' ) + ( i + 1 ) ) ) + '</option>'; } ).join( '' ) + '</select>';
	}
	function repBlock() {
		var m = S.f.rep.mode, h = radios( 'rep-mode', 'f.rep.mode', [ [ 'none', 'প্রযোজ্য নহে' ], [ 'donor', 'দাতা নিজেই (দাতার তথ্য থেকে)' ], [ 'donee', 'গ্রহীতা (গ্রহীতার তথ্য থেকে)' ], [ 'other', 'অন্য কেউ' ] ] );
		if ( m !== 'none' ) {
			h += drow( 'পরিচয়', '<select data-p="f.rep.role">' + [ 'অভিভাবক', 'প্রতিনিধি', 'আমমোক্তার' ].map( function ( r ) { return '<option' + ( S.f.rep.role === r ? ' selected' : '' ) + '>' + r + '</option>'; } ).join( '' ) + '</select>' );
			if ( m === 'donor' || m === 'donee' ) { h += drow( 'কে', personSelect( m === 'donor' ? 'donors' : 'donees' ) ); }
			if ( m === 'other' ) {
				h += drow( 'পরিচিতি নং (NID)', inp( 'f.rep.nid', { nid: true, ph: 'যেমন 823 541 7337' } ) ) + drow( 'নাম', inp( 'f.rep.name' ) ) + drow( 'স্বামীর নাম <em>(বিবাহিতা মহিলা হলে)</em>', inp( 'f.rep.spouse' ) ) +
					drow( 'পিতার নাম', inp( 'f.rep.father' ) ) + drow( 'মাতার নাম', inp( 'f.rep.mother' ) ) + drow( 'জন্ম তারিখ', inp( 'f.rep.dob', { ph: 'যেমন ১০/০৩/১৯৮৭' } ) ) +
					drow( 'ধর্ম', inp( 'f.rep.religion' ) ) + drow( 'পেশা', inp( 'f.rep.profession' ) ) + drow( 'জাতীয়তা', inp( 'f.rep.nationality' ) ) +
					'<table class="df-t df-addr"><tr>' + th( 'স্থায়ী ঠিকানা' ) + th( 'বর্তমান ঠিকানা' ) + '</tr><tr><td>' + inp( 'f.rep.perm', { area: true, rows: 3 } ) + '</td><td>' + inp( 'f.rep.present', { area: true, rows: 3 } ) + '</td></tr></table>';
			} else { h += '<p class="df-note">দলিলে এই ব্যক্তির পুরো বিবরণ (নাম, পিতা, মাতা, জন্ম তারিখ, NID, ঠিকানা) দাতা/গ্রহীতার তথ্য থেকে নিজে নিজে বসবে, ছবির জায়গাসহ ঠিক ৩–৪ নম্বরের মতো।</p>'; }
		}
		return h + dauto( 'দলিলে যা লেখা হবে', 'proxy_text', 'f.o', 2 );
	}
	function poaBlock() {
		var h = radios( 'poa-mode', 'f.poa.mode', [ [ 'none', 'প্রযোজ্য নহে' ], [ 'yes', 'আমমোক্তারনামা আছে' ] ] );
		if ( S.f.poa.mode === 'yes' ) { h += drow( 'আমমোক্তারনামা দলিল নং', inp( 'f.poa.no' ) ) + drow( 'তারিখ', inp( 'f.poa.date', { ph: 'যেমন ১০/০৩/২০২৫' } ) ) + drow( 'রেজিস্ট্রি অফিস', inp( 'f.poa.office' ) ); }
		return h + dauto( 'দলিলে যা লেখা হবে', 'poa_text', 'f.o', 2 );
	}

	/** words box with the ↺ button inside it */
	function wbox( key, scope ) { return '<div class="df-wd">' + ainp( key, scope, 1 ) + reset( key, scope ) + '</div>'; }
	function boundSets() {
		var many = ( S.f.bounds || [] ).length > 1;
		return ( S.f.bounds || [] ).map( function ( b, i ) {
			var p = 'f.bounds.' + i + '.';
			return '<div class="df-bset">' + ( many ? '<div class="df-bset-h">' + drow( 'কোন অংশের চৌহদ্দি <em>(না লিখলে ' + ord( i + 1 ) + ' চৌহদ্দি)</em>', inp( p + 'title', { ph: 'যেমন ১১২১ নং দাগের চৌহদ্দি' } ), '<button type="button" class="dw-x" data-del-bound="' + i + '">এই চৌহদ্দি মুছুন ×</button>' ) + '</div>' : '' ) +
				'<table class="df-t df-bound"><tr>' + th( 'দিক' ) + th( 'চৌহদ্দিতে যা আছে' ) + '</tr>' +
				[ [ 'উত্তরে', 'north' ], [ 'দক্ষিণে', 'south' ], [ 'পূর্বে', 'east' ], [ 'পশ্চিমে', 'west' ] ].map( function ( d ) { return '<tr><th>' + d[ 0 ] + '</th><td>' + inp( p + d[ 1 ] ) + '</td></tr>'; } ).join( '' ) + '</table></div>';
		} ).join( '' );
	}
	function vbBlock() {
		var dn = S.donors.length, gn = S.donees.length;
		var rows = [ [ 'd_ami', 'আমি / আমরা', 'দাতা' ], [ 'd_amar', 'আমার / আমাদের', 'দাতা' ], [ 'd_datar', 'দাতার / দাতাগণের', 'দাতা' ], [ 'g_apni', 'আপনি / আপনারা', 'গ্রহীতা' ], [ 'g_grohitar', 'গ্রহিতার', 'গ্রহীতা' ], [ 'g_grohita', 'গ্রহিতা / গ্রহিতাগণ', 'গ্রহীতা' ], [ 'g_apnar', 'আপনার / আপনাদের', 'গ্রহীতা' ] ];
		return '<details class="df-vb"><summary>৭ নম্বরের নিচের দুই অনুচ্ছেদে এক বচন/বহুবচন (আমি-আমরা, আপনি-আপনারা): এই দলিলে <b>দাতা ' + ( dn > 1 ? 'বহুবচন' : 'একবচন' ) + '</b>, <b>গ্রহীতা ' + ( gn > 1 ? 'বহুবচন' : 'একবচন' ) + '</b> (শব্দ দেখুন/বদলান)</summary>' +
			'<p class="df-note">দাতা বা গ্রহীতা একাধিক হলে নিজে নিজে বহুবচনের শব্দ বসে। নিচের শব্দগুলো আপনার মতো ঠিক করে নিন; বদলালে পরের দলিলেও এভাবেই থাকবে।</p>' +
			'<table class="df-t df-vbt"><tr>' + th( 'কার সংখ্যা অনুযায়ী' ) + th( 'শব্দ' ) + th( 'একবচন' ) + th( 'বহুবচন' ) + th( 'মহিলা (এক)' ) + th( 'মহিলা (বহু)' ) + '</tr>' +
			rows.map( function ( r ) { var fem = VB_DEFAULTS[ r[ 0 ] ].length > 2; return '<tr><td>' + r[ 2 ] + '</td><td>' + r[ 1 ] + '</td><td>' + inp( 'f.vb.' + r[ 0 ] + '.0' ) + '</td><td>' + inp( 'f.vb.' + r[ 0 ] + '.1' ) + '</td><td>' + ( fem ? inp( 'f.vb.' + r[ 0 ] + '.2' ) : '' ) + '</td><td>' + ( fem ? inp( 'f.vb.' + r[ 0 ] + '.3' ) : '' ) + '</td></tr>'; } ).join( '' ) + '</table></details>';
	}

	function landRows() {
		var many = ( S.f.lands || [] ).length > 1;
		return ( S.f.lands || [] ).map( function ( l, i ) {
			var b = 'f.lands.' + i + '.', ho = 'f.lands.' + i + '.o';
			return '<tr><td>' + inp( b + 'area', { ph: 'শতাংশ (শুধু সংখ্যা), যেমন 5 বা 5.25' } ) + wbox( 'area_text', ho ) + '</td>' +
				'<td class="df-mid">' + inp( b + 'cls', { ph: 'শ্রেণী, যেমন নাল, ভিটি, পুকুর', list: 'dw-classes' } ) + '</td>' +
				'<td>' + inp( b + 'price', { ph: 'টাকা (শুধু সংখ্যা), যেমন 500000' } ) + wbox( 'price_text', ho ) + '</td>' +
				'<td class="df-x">' + ( many ? '<button type="button" class="dw-x" data-del-land="' + i + '" title="এই সারি মুছুন">×</button>' : '' ) + '</td></tr>';
		} ).join( '' );
	}

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
			mouzaPicker() + '<div class="df-row df-btnrow"><button type="button" class="btn btn-outline btn-sm" id="dw-save-mouza">এই মৌজার তথ্য মৌজা তালিকায় সেভ করুন</button> <small id="dw-mouza-st"></small></div>' + drow( '১। রেজিস্ট্রী অফিসের নাম', inp( 'f.sub_office' ) ) +
			'<p class="df-cap">২। দলিলের সার সংক্ষেপ</p>' +
			'<table class="df-t"><tr>' + [ 'দলিলের প্রকৃতি', 'মৌজা নম্বর', 'মৌজার নাম', 'ইউনিয়ন/ওয়ার্ড', 'থানা/উপজেলা', 'জেলা' ].map( th ).join( '' ) + '</tr><tr>' +
			'<td>' + inp( 'f.deed_type', { list: 'dw-types' } ) + '</td><td>' + inp( 'f.mouza_no', { ph: '99' } ) + '</td><td>' + inp( 'f.mouza_name', { ph: 'তিয়রী' } ) + '</td><td>' + inp( 'f.union', { ph: '৯ নং ভোলাকোট' } ) + '</td><td>' + inp( 'f.upazila' ) + '</td><td>' + inp( 'f.district' ) + '</td></tr></table>' +
			'<table class="df-t df-sum"><colgroup><col style="width:38%"><col style="width:24%"><col style="width:34%"><col style="width:4%"></colgroup><tr>' + [ 'হস্তান্তরিত সম্পত্তির পরিমান', 'শ্রেণী', 'মূল্য (অংকে ও কথায়)', '' ].map( th ).join( '' ) + '</tr>' + landRows() +
			'<tr class="df-total"><td>' + wbox( 'area_text', 'f.o' ) + '<small class="df-mini">মোট জমি (নিজে নিজে যোগ হয়)</small></td><td class="df-mid df-tl">মোট</td><td>' + wbox( 'price_text', 'f.o' ) + '<small class="df-mini">মোট মূল্য (নিজে নিজে যোগ হয়)</small></td><td class="df-x"></td></tr></table>' +
			'<button type="button" class="btn btn-outline btn-sm" data-add-land="1">+ আরেক শ্রেণীর জমি (ভিটি, পুকুর, বাড়ী …)</button>' );

		// 3, 4
		h += dsec( 's-3', '৩', 'দলিল গ্রহীতা/গ্রহীতাগণের নাম ও ঠিকানা (ক্রেতা)', searchBox( 'donees' ) + S.donees.map( function ( p, i ) { return partyCard( 'donees', i ); } ).join( '' ) + '<button type="button" class="btn btn-outline btn-sm" data-add-party="donees">+ আরেকজন গ্রহীতা</button>' );
		h += dsec( 's-4', '৪', 'দলিল দাতা/দাতাগণের নাম ও ঠিকানা (বিক্রেতা)', searchBox( 'donors' ) + S.donors.map( function ( p, i ) { return partyCard( 'donors', i ); } ).join( '' ) + '<button type="button" class="btn btn-outline btn-sm" data-add-party="donors">+ আরেকজন দাতা</button>' );

		// 5–7
		h += dsec( 's-5', '৫–৭', 'আমমোক্তার, আমমোক্তারনামা ও মালিকানার বিবরণ',
			'<p class="df-cap">৫। আমমোক্তার/প্রতিনিধি/অভিভাবকের মাধ্যমে দলিল সম্পাদিত হইয়া থাকিলে তাহাদের নাম, ঠিকানা ও বিবরণ</p>' + repBlock() +
			'<p class="df-cap">৬। আমমোক্তারনামার বিবরণ</p>' + poaBlock() +
			'<p class="df-cap">৭। হস্তান্তরাধীন জমির নূন্য পক্ষে ২৫ বছরের মালিকানার ধারাবাহিক বিবরণ</p><div class="df-box">' + inp( 'f.ownership', { area: true, rows: 10, ph: 'জেলা-…, উপজেলা ও সাব-রেজিস্ট্রি অফিস … এর পুরো বিবরণ এখানে লিখুন বা পেস্ট করুন' } ) + '</div>' + vbBlock() );

		// 10
		h += dsec( 's-10', '১০', 'সম্পাদনের তারিখ (বাংলায় ও ইংরেজী)', drow( 'ইংরেজি তারিখ বেছে নিন', inp( 'f.exec_greg', { type: 'date' } ) ) + dauto( 'বাংলা ও ইংরেজি তারিখ', 'exec_date', 'f.o', 1 ) );

		// 11
		var srows = ( S.f.khatians || [] ).map( function ( k, ki ) {
			var kb = 'f.khatians.' + ki + '.', n = k.dags.length;
			return k.dags.map( function ( d, di ) {
				var b = kb + 'dags.' + di + '.';
				return '<tr>' + ( di === 0 ? '<td class="df-kh" rowspan="' + n + '"><small class="df-mini">খতিয়ান ' + bn( ki + 1 ) + '</small>' + inp( kb + 'khatian', { area: true, rows: 3, ph: 'সি.এস ০৮নং খং … আগত আর.এস ৩৮ নং খং …' } ) +
					'<button type="button" class="dw-link" data-add-dag="' + ki + '">+ এই খতিয়ানে আরেকটি দাগ</button>' + ( S.f.khatians.length > 1 ? '<button type="button" class="dw-x" data-del-kh="' + ki + '">এই খতিয়ান মুছুন ×</button>' : '' ) + '</td>' : '' ) +
					'<td>' + inp( b + 'cs_dag', { ph: 'সি.এস দাগ নং' } ) + '</td><td>' + inp( b + 'rs_dag', { ph: 'আর.এস দাগ নং (সংখ্যা)' } ) + wbox( 'rs_text', kb + 'dags.' + di + '.o' ) + '</td><td>' + inp( b + 'cls', { list: 'dw-classes' } ) + '</td><td>' + inp( b + 'total', { ph: 'শতাংশ (সংখ্যা), যেমন 18' } ) + wbox( 'total_text', kb + 'dags.' + di + '.o' ) + '</td><td>' + inp( b + 'transferred', { ph: 'শতাংশ (সংখ্যা), যেমন 10' } ) + wbox( 'transferred_text', kb + 'dags.' + di + '.o' ) + '</td>' +
					'<td class="df-x">' + ( n > 1 ? '<button type="button" class="dw-x" data-del-dag="' + ki + ':' + di + '" title="এই দাগ মুছুন">×</button>' : '' ) + '</td></tr>';
			} ).join( '' );
		} ).join( '' );
		h += dsec( 's-11', '১১', 'সম্পত্তির তফসিল',
			drow( 'গ্রাম', inp( 'f.village' ) ) + drow( 'জে.এল. নং', inp( 'f.jl_no', { ph: 'সি.এস ১৪১ আর.এস ৯৯ নং' } ) ) + drow( 'ভূমি অফিসের নাম', inp( 'f.land_office' ) ) +
			dauto( 'তফসিলের শুরুর লাইন', 'schedule_head', 'f.o', 2 ) +
			'<table class="df-t df-sched"><colgroup><col style="width:20%"><col style="width:9%"><col style="width:19%"><col style="width:9%"><col style="width:19%"><col style="width:19%"><col style="width:5%"></colgroup><tr>' + [ 'খতিয়ান নং (সি.এস / আর.এস / বর্তমান)', 'সি.এস দাগ', 'আর.এস দাগ (কথায় নিজে নিজে হয়)', 'শ্রেণি', 'দাগে মোট জমি', 'হস্তান্তরিত জমির পরিমান', '' ].map( th ).join( '' ) + '</tr>' + srows + '</table>' +
			'<button type="button" class="btn btn-outline btn-sm" data-add-kh="1">+ আলাদা খতিয়ান (নতুন খতিয়ান ও দাগ)</button>' + dauto( 'মোট জমির পরিমান', 'total_text', 'f.o', 1 ) );

		// 12–15
		h += dsec( 's-12', '১২–১৫', 'চৌহদ্দি, পরিমাণ, মূল্য ও হাত নকশা',
			'<p class="df-cap">১২। সম্পত্তির চৌহদ্দির বিবরণ</p>' + boundSets() +
			'<button type="button" class="btn btn-outline btn-sm" data-add-bound="1">+ আরেকটি চৌহদ্দি (আলাদা চৌহদ্দি হলে)</button>' +
			dauto( 'চৌহদ্দির পুরো লেখা', 'boundaries', 'f.o', 3 ) +
			dauto( '১৩। হস্তান্তরিত সম্পত্তির পরিমাণ', 'area_text', 'f.o', 1 ) + dauto( '১৪। মূল্য পরিশোধের বিবরণ', 'payment_text', 'f.o', 1 ) + dauto( '১৫। হাত নকশা ও পরিমাপ', 'sqft_text', 'f.o', 1 ) +
			drow( 'হাত নকশার জন্য খালি জায়গা', '<select data-p="f.sketch_lines">' + [ [ 4, 'অল্প (৪ লাইন)' ], [ 8, 'কিছুটা (৮ লাইন)' ], [ 10, 'মাঝারি (১০ লাইন)' ], [ 14, 'বেশি (১৪ লাইন)' ], [ 20, 'অনেক (২০ লাইন)' ], [ 30, 'পুরো পাতার মতো (৩০ লাইন)' ] ].map( function ( o ) { return '<option value="' + o[ 0 ] + '"' + ( +S.f.sketch_lines === o[ 0 ] ? ' selected' : '' ) + '>' + o[ 1 ] + '</option>'; } ).join( '' ) + '</select>', '<small>১৫ নম্বরের পরে এই পরিমাণ ফাঁকা জায়গা থাকবে, হাতে নকশা আঁকার জন্য</small>' ) +
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
		h += '<datalist id="dw-classes"><option value="নাল"><option value="ভিটি"><option value="ভিটা"><option value="পুকুর"><option value="বাড়ী (স্থাপনা বিহীন)"><option value="বাগান"><option value="ডোবা"><option value="আবাদী"><option value="চালা"><option value="জঙ্গল"></datalist>' + '<datalist id="dw-types"><option value="সাফ কবলা"><option value="হেবা"><option value="বণ্টননামা"><option value="বায়না"><option value="দান"></datalist>';
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
			var dm = /^f\.khatians\.(\d+)\.dags\.(\d+)\.o\./.exec( path );
			var lm = /^f\.lands\.(\d+)\.o\./.exec( path );
			if ( dm ) { var dd = S.f.khatians[ +dm[ 1 ] ].dags[ +dm[ 2 ] ]; autoVal = ( key === 'rs_text' ? dd.rs_auto : key === 'total_text' ? dd.total_auto : dd.transferred_auto ) || ''; }
			else
			if ( lm ) { autoVal = ( key === 'area_text' ? S.f.lands[ +lm[ 1 ] ].area_auto : S.f.lands[ +lm[ 1 ] ].price_auto ) || ''; }
			else if ( /^(donees|donors)\./.test( path ) ) {
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
		h += '<table class="pv-t"><tr><th>হস্তান্তরিত সম্পত্তির পরিমান</th><th>শ্রেণী</th><th>মূল্য (অংকে ও কথায়)</th></tr>' + ( o.land_rows || [] ).map( function ( r ) { return '<tr><td>' + pv( r.area ) + '</td><td>' + pv( r[ 'class' ] ) + '</td><td>' + pv( r.price ) + '</td></tr>'; } ).join( '' ) + ( ( o.land_rows || [] ).length > 1 ? '<tr class="pv-tot"><td>' + pv( o.area_text ) + '</td><td><b>মোট</b></td><td>' + pv( o.price_text ) + '</td></tr>' : '' ) + '</table>';
		h += '<p class="pv-h">৩। দলিল গ্রহীতা/গ্রহীতাগণের নাম ও ঠিকানা</p>' + S.donees.map( function ( p, i ) { return pvParty( p, i, S.donees.length ); } ).join( '' );
		h += '<p class="pv-h">৪। দলিল দাতা/দাতাগণের নাম ও ঠিকানা</p>' + S.donors.map( function ( p, i ) { return pvParty( p, i, S.donors.length ); } ).join( '' );
		h += row( '৫। আমমোক্তার/প্রতিনিধি/অভিভাবক', o.proxy_text ) + ( repParty() ? pvParty( repParty(), 0, 1 ) : '' ) + row( '৬। আমমোক্তারনামার বিবরণ', o.poa_text );
		h += '<p class="pv-h">৭। মালিকানার ধারাবাহিক বিবরণ</p><p class="pv-body">' + pv( o.ownership ) + '</p>' + fixedNote( '[ছাঁচের স্থায়ী লেখা: “হস্তান্তরিত ভূমির উপর আমি দাতার … ” ও “প্রকাশ থাকে যে, …” অনুচ্ছেদ Word ফাইলে আসবে]' );
		h += '<p class="pv-h">৮। একাধিক গ্রহীতার হারাহারি মালিকানা</p><table class="pv-t"><tr><th>ক্রেতা/গ্রহীতার নাম</th><th>মালিকানার পরিমান</th></tr>' + ( o.donee_rows || [] ).map( function ( r ) { return '<tr><td>' + pv( r.name ) + '</td><td>' + pv( r.share ) + '</td></tr>'; } ).join( '' ) + '</table>';
		h += '<p class="pv-h">৯। একাধিক দাতার হারাহারি মালিকানা</p><table class="pv-t"><tr><th>বিক্রেতা/দাতার নাম</th><th>মালিকানার পরিমান</th></tr>' + ( o.donor_rows || [] ).map( function ( r ) { return '<tr><td>' + pv( r.name ) + '</td><td>' + pv( r.share ) + '</td></tr>'; } ).join( '' ) + '</table>';
		h += row( '১০। সম্পাদনের তারিখ', o.exec_date );
		h += '<p class="pv-h">১১। সম্পত্তির তফসিল</p><p class="pv-body">' + pv( o.schedule_head ) + '</p><p class="pv-sm">' + pv( o.mouza_head ) + '</p>';
		h += '<table class="pv-t"><tr><th>খতিয়ান নং</th><th>সি.এস দাগ</th><th>আর.এস দাগ</th><th>শ্রেণি</th><th>দাগে মোট জমি</th><th>হস্তান্তরিত জমি</th></tr>' + ( o.schedule_rows || [] ).map( function ( r ) {
			var kc = r.vm === 'cont' ? '' : '<td' + ( r.span > 1 ? ' rowspan="' + r.span + '"' : '' ) + '>' + pv( r.khatian ) + '</td>';
			return '<tr>' + kc + '<td>' + pv( r.cs_dag ) + '</td><td>' + pv( r.rs_dag ) + '</td><td>' + pv( r[ 'class' ] ) + '</td><td>' + pv( r.total ) + '</td><td>' + pv( r.transferred ) + '</td></tr>';
		} ).join( '' ) + '</table><p class="pv-sm">' + pv( o.total_text ) + '</p>';
		h += row( '১২। চৌহদ্দির বিবরণ', o.boundaries ) + row( '১৩। সম্পত্তির পরিমাণ', o.area_text ) + row( '১৪। মূল্য পরিশোধের বিবরণ', o.payment_text ) + row( '১৫। হাত নকশা ও পরিমাপ', o.sqft_text ) + '<div class="pv-sketch" style="height:' + Math.max( 10, ( o.sketch_lines || 0 ) * 6 ) + 'px">হাত নকশার জন্য খালি জায়গা</div>';
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
		var defaults = { vb: f.vb, sub_office: f.sub_office, district: f.district, upazila: f.upazila, land_office: f.land_office, id: f.id, wr: f.wr, w1: { upazila: f.w1.upazila, district: f.w1.district }, w2: { upazila: f.w2.upazila, district: f.w2.district } };
		var title = ( ( S.donors[ 0 ] && S.donors[ 0 ].name ) || '' ) + ( S.donees[ 0 ] && S.donees[ 0 ].name ? ' → ' + S.donees[ 0 ].name : '' );
		title = ( title ? title + ' — ' : '' ) + ( f.deed_type || '' ) + ( f.mouza_name ? ' (' + f.mouza_name + ')' : '' );
		var rep = repParty();
		return { title: title, data: { f: S.f, donees: S.donees, donors: S.donors, rep: rep ? [ rep ] : [], out: S.out, defaults: defaults } };
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
		if ( e.target.matches && e.target.matches( 'input[data-rep]' ) ) { setp( e.target.getAttribute( 'data-rep' ), e.target.value ); if ( e.target.getAttribute( 'data-rep' ) === 'f.rep.mode' ) { S.f.rep.idx = 0; } render(); schedule(); return; }
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
		else if ( t.hasAttribute( 'data-add-land' ) ) { S.f.lands.push( { cls: '', area: '', price: '', o: {} } ); render(); schedule(); }
		else if ( ( v = t.getAttribute( 'data-del-land' ) ) !== null ) { S.f.lands.splice( +v, 1 ); render(); schedule(); }
		else if ( t.hasAttribute( 'data-add-bound' ) ) { S.f.bounds.push( { title: '', north: '', south: '', east: '', west: '' } ); render(); schedule(); }
		else if ( ( v = t.getAttribute( 'data-del-bound' ) ) !== null ) { if ( window.confirm( 'এই চৌহদ্দিটি মুছবেন?' ) ) { S.f.bounds.splice( +v, 1 ); render(); schedule(); } }
		else if ( t.hasAttribute( 'data-add-kh' ) ) { var lastK = S.f.khatians[ S.f.khatians.length - 1 ], lc = lastK && lastK.dags.length ? lastK.dags[ lastK.dags.length - 1 ].cls : ''; S.f.khatians.push( { khatian: '', dags: [ newDag( lc ) ] } ); render(); schedule(); }
		else if ( ( v = t.getAttribute( 'data-del-kh' ) ) !== null ) { if ( window.confirm( 'এই খতিয়ান ও তার সব দাগ মুছবেন?' ) ) { S.f.khatians.splice( +v, 1 ); render(); schedule(); } }
		else if ( ( v = t.getAttribute( 'data-add-dag' ) ) !== null ) { var kk = S.f.khatians[ +v ], ld = kk.dags[ kk.dags.length - 1 ]; kk.dags.push( newDag( ld ? ld.cls : '' ) ); render(); schedule(); }
		else if ( ( v = t.getAttribute( 'data-del-dag' ) ) !== null ) { var dp = v.split( ':' ); S.f.khatians[ +dp[ 0 ] ].dags.splice( +dp[ 1 ], 1 ); render(); schedule(); }
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
	var FILL = [ 'name', 'gender', 'spouse', 'father', 'mother', 'dob', 'religion', 'profession', 'nationality', 'perm', 'present' ];

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

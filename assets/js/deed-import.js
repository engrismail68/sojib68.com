/**
 * "পুরোনো দলিল থেকে তথ্য তুলুন" — runs entirely in the browser: the chosen .doc/.docx files are read
 * on this computer (never uploaded); only the people the user confirms are sent to be saved.
 *
 * Bijoy → Unicode mapping data adapted from the MIT-licensed "bijoy2unicode" package (Rajib Biswas),
 * plus corrections found on a real SutonnyMJ deed.
 */
( function () {
	'use strict';
	var CFG = window.sojib68DwM || {};

	/* ---------------------------------------------------------------- Bijoy -> Unicode */
	var MAP = [["Av","আ"],["„„","ৃ"],["A","অ"],["B","ই"],["C","ঈ"],["D","উ"],["E","ঊ"],["F","ঋ"],["G","এ"],["H","ঐ"],["I","ও"],["J","ঔ"],["K","ক"],["L","খ"],["M","গ"],["N","ঘ"],["O","ঙ"],["P","চ"],["Q","ছ"],["R","জ"],["S","ঝ"],["T","ঞ"],["U","ট"],["V","ঠ"],["W","ড"],["X","ঢ"],["Y","ণ"],["Z","ত"],["_","থ"],["`","দ"],["a","ধ"],["b","ন"],["c","প"],["d","ফ"],["e","ব"],["f","ভ"],["g","ম"],["h","য"],["i","র"],["j","ল"],["k","শ"],["l","ষ"],["m","স"],["n","হ"],["o","ড়"],["p","ঢ়"],["q","য়"],["r","ৎ"],["s","ং"],["t","ঃ"],["u","ঁ"],["0","০"],["1","১"],["2","২"],["3","৩"],["4","৪"],["5","৫"],["6","৬"],["7","৭"],["8","৮"],["9","৯"],["•","ঙ্"],["v","া"],["w","ি"],["x","ী"],["y","ু"],["z","ু"],["“","ু"],["–","ু"],["~","ূ"],["ƒ","ূ"],["‚","ূ"],["„","ৃ"],["…","ৃ"],["†","ে"],["‡","ে"],["ˆ","ৈ"],["‰","ৈ"],["Š","ৗ"],["|","।"],["&","্‌"],["^","্ব"],["‘","্তু"],["’","্থ"],["‹","্ক"],["Œ","্ক্র"],["”","চ্"],["—","্ত"],["˜","দ্"],["™","দ্"],["š","ন্"],["›","ন্"],["œ","্ন"],["Ÿ","্ব"],["¡","্ব"],["¢","্ভ"],["£","্ভ্র"],["¤","ম্"],["¥","্ম"],["¦","্ব"],["§","্ম"],["¨","্য"],["©",""],["ª","্র"],["«","্র"],["¬","্ল"],["­","্ল"],["®","ষ্"],["¯","স্"],["°","ক্ক"],["±","ক্ট"],["²","ক্ষ্ম"],["³","ক্ত"],["´","ক্ম"],["µ","ক্র"],["¶","ক্ষ"],["·","ক্স"],["¸","গু"],["¹","জ্ঞ"],["º","গ্দ"],["»","গ্ধ"],["¼","ঙ্ক"],["½","ঙ্গ"],["¾","জ্জ"],["¿","্ত্র"],["À","জ্ঝ"],["Á","জ্ঞ"],["Â","ঞ্চ"],["Ã","ঞ্ছ"],["Ä","ঞ্জ"],["Å","ঞ্ঝ"],["Æ","ট্ট"],["Ç","ড্ড"],["È","ণ্ট"],["É","ণ্ঠ"],["Ê","ণ্ড"],["Ë","ত্ত"],["Ì","ত্থ"],["Í","্ত"],["Î","ত্র"],["Ï","দ্দ"],["Ð","-"],["Ñ","-"],["Ò","\""],["Ó","\""],["Ô","'"],["Õ","'"],["Ö","্র"],["×","দ্ধ"],["Ø","দ্ব"],["Ù","দ্ম"],["Ú","ন্ঠ"],["Û","ন্ড"],["Ü","ন্ধ"],["Ý","ন্স"],["Þ","প্ট"],["ß","প্ত"],["à","প্প"],["á","প্স"],["â","ব্জ"],["ã","ব্দ"],["ä","ব্ধ"],["å","ভ্র"],["æ","ু"],["ç","ম্ফ"],["è","্ন"],["é","ল্ক"],["ê","ল্গ"],["ë","ল্ট"],["ì","ল্ড"],["í","ল্প"],["î","ল্ফ"],["ï","শু"],["ð","শ্চ"],["ñ","শ্ছ"],["ò","ষ্ণ"],["ó","ষ্ট"],["ô","ষ্ঠ"],["õ","ষ্ফ"],["ö","স্খ"],["÷","স্ট"],["ø","্ল"],["ù","স্ফ"],["ú","্প"],["û","হু"],["ü","হৃ"],["ý","হ্ন"],["þ","হ্ম"],["ÿ","ক্ষ"]];
	var BY_FIRST = {};
	MAP.forEach( function ( kv ) { ( BY_FIRST[ kv[ 0 ].charAt( 0 ) ] = BY_FIRST[ kv[ 0 ].charAt( 0 ) ] || [] ).push( kv ); } );
	var PRE = 'িেৈ', CONS = 'কখগঘঙচছজঝঞটঠডঢণতথদধনপফবভমযরলশষসহড়ঢ়য়ৎ', POST = 'াোৌৗুূীৃ';
	var isCons = function ( c ) { return c && CONS.indexOf( c ) >= 0; };

	function bijoyToUnicode( s ) {
		s = s.replace( / +/g, ' ' ).replace( /yy/g, 'y' ).replace( /vv/g, 'v' ).replace( /‡u/g, 'u‡' ).replace( /wu/g, 'uw' );
		var out = '', i = 0, n = s.length, c, list, k, hit;
		while ( i < n ) {
			c = s.charAt( i ); list = BY_FIRST[ c ]; hit = false;
			if ( list ) {
				for ( k = 0; k < list.length; k++ ) { if ( s.substr( i, list[ k ][ 0 ].length ) === list[ k ][ 0 ] ) { out += list[ k ][ 1 ]; i += list[ k ][ 0 ].length; hit = true; break; } }
			}
			if ( ! hit ) { out += c; i++; }
		}
		var r = out.split( '' ), j;
		i = 0;
		while ( i < r.length ) {
			if ( PRE.indexOf( r[ i ] ) >= 0 ) {
				j = i + 1;
				while ( j < r.length && isCons( r[ j ] ) ) {
					if ( j + 1 < r.length && r[ j + 1 ] === '্' ) { j += 2; } else { j += 1; break; }
				}
				var kar = r[ i ];
				if ( kar === 'ে' && j < r.length && r[ j ] === 'া' ) { kar = 'ো'; r.splice( j, 1 ); }
				else if ( kar === 'ে' && j < r.length && r[ j ] === 'ৗ' ) { kar = 'ৌ'; r.splice( j, 1 ); }
				var cluster = r.slice( i + 1, j );
				r.splice.apply( r, [ i, j - i ].concat( cluster, [ kar ] ) );
				i = j;
				continue;
			}
			i++;
		}
		var t = r.join( '' ).replace( /অা/g, 'আ' );
		r = t.split( '' );
		var q = 0, p;
		while ( q < r.length ) {
			if ( r[ q ] === '' ) {
				p = q - 1;
				while ( p >= 0 && ( POST.indexOf( r[ p ] ) >= 0 || PRE.indexOf( r[ p ] ) >= 0 ) ) { p--; }
				while ( p >= 0 && isCons( r[ p ] ) ) { p--; if ( p >= 0 && r[ p ] === '্' ) { p--; } else { break; } }
				var seg = r.slice( p + 1, q );
				r.splice.apply( r, [ p + 1, q - p ].concat( [ 'র', '্' ], seg ) );
			}
			q++;
		}
		return r.join( '' ).replace( /্্/g, '্' ).replace( /ঁ([ািীুূৃোৌ]+)/g, '$1ঁ' );
	}

	/* ---------------------------------------------------------------- .docx (zip + xml) */
	function u32( dv, o ) { return dv.getUint32( o, true ); }
	function u16( dv, o ) { return dv.getUint16( o, true ); }

	function inflateRaw( bytes ) {
		var ds = new DecompressionStream( 'deflate-raw' );
		var stream = new Blob( [ bytes ] ).stream().pipeThrough( ds );
		return new Response( stream ).arrayBuffer();
	}

	function readDocx( buf ) {
		var dv = new DataView( buf ), u8 = new Uint8Array( buf ), i, eocd = -1;
		for ( i = buf.byteLength - 22; i >= 0 && i > buf.byteLength - 70000; i-- ) { if ( u32( dv, i ) === 0x06054b50 ) { eocd = i; break; } }
		if ( eocd < 0 ) { return Promise.reject( new Error( 'Word ফাইলটি পড়া যায়নি' ) ); }
		var count = u16( dv, eocd + 10 ), p = u32( dv, eocd + 16 ), found = null;
		for ( i = 0; i < count; i++ ) {
			var method = u16( dv, p + 10 ), csize = u32( dv, p + 20 ), nlen = u16( dv, p + 28 ), elen = u16( dv, p + 30 ), clen = u16( dv, p + 32 ), loff = u32( dv, p + 42 );
			var name = new TextDecoder().decode( u8.subarray( p + 46, p + 46 + nlen ) );
			if ( name === 'word/document.xml' ) { found = { method: method, csize: csize, loff: loff }; break; }
			p += 46 + nlen + elen + clen;
		}
		if ( ! found ) { return Promise.reject( new Error( 'Word ফাইলে লেখা পাওয়া যায়নি' ) ); }
		var ds = found.loff + 30 + u16( dv, found.loff + 26 ) + u16( dv, found.loff + 28 ), data = u8.subarray( ds, ds + found.csize );
		var done = found.method === 0 ? Promise.resolve( data.buffer.slice( data.byteOffset, data.byteOffset + data.byteLength ) ) : inflateRaw( data );
		return done.then( function ( ab ) {
			var xml = new DOMParser().parseFromString( new TextDecoder( 'utf-8' ).decode( ab ), 'application/xml' );
			var W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main', lines = [];
			[].forEach.call( xml.getElementsByTagNameNS( W, 'p' ), function ( para ) {
				var t = '';
				( function walk( node ) {
					[].forEach.call( node.childNodes, function ( c ) {
						if ( c.nodeType !== 1 ) { return; }
						if ( c.localName === 't' ) { t += c.textContent; }
						else if ( c.localName === 'tab' ) { t += '\t'; }
						else if ( c.localName === 'br' ) { t += '\n'; }
						else { walk( c ); }
					} );
				}( para ) );
				lines.push( t );
			} );
			return lines.join( '\n' );
		} );
	}

	/* ---------------------------------------------------------------- .doc (old binary Word) */
	function readDoc( buf ) {
		var dv = new DataView( buf ), u8 = new Uint8Array( buf );
		if ( u32( dv, 0 ) !== 0xE011CFD0 ) { throw new Error( 'এটি Word ফাইল নয়' ); }
		var sec = 1 << u16( dv, 30 ), mini = 1 << u16( dv, 32 );
		var dirStart = u32( dv, 48 ), miniCut = u32( dv, 56 ), miniFatStart = u32( dv, 60 ), nMiniFat = u32( dv, 64 ), difat = u32( dv, 68 ), nDifat = u32( dv, 72 );
		var off = function ( s ) { return ( s + 1 ) * sec; }, END = 0xFFFFFFFA, i, fatSecs = [];
		for ( i = 0; i < 109; i++ ) { var v = u32( dv, 76 + i * 4 ); if ( v < END ) { fatSecs.push( v ); } }
		for ( var d = 0; d < nDifat && difat < END; d++ ) {
			for ( i = 0; i < sec / 4 - 1; i++ ) { var w = u32( dv, off( difat ) + i * 4 ); if ( w < END ) { fatSecs.push( w ); } }
			difat = u32( dv, off( difat ) + sec - 4 );
		}
		var fat = [];
		fatSecs.forEach( function ( s ) { for ( var k = 0; k < sec / 4; k++ ) { fat.push( u32( dv, off( s ) + k * 4 ) ); } } );
		function chain( table, start ) { var o = [], s = start; while ( s < END && o.length <= table.length ) { o.push( s ); s = table[ s ]; } return o; }
		function big( start, size ) {
			var ch = chain( fat, start ), out = new Uint8Array( ch.length * sec );
			ch.forEach( function ( s, k ) { out.set( u8.subarray( off( s ), off( s ) + sec ), k * sec ); } );
			return size == null ? out : out.subarray( 0, size );
		}
		var dir = big( dirStart ), ddv = new DataView( dir.buffer, dir.byteOffset, dir.byteLength ), entries = {};
		for ( i = 0; i + 128 <= dir.length; i += 128 ) {
			var nl = ddv.getUint16( i + 64, true ), nm = '';
			for ( var c = 0; c < nl / 2 - 1; c++ ) { nm += String.fromCharCode( ddv.getUint16( i + c * 2, true ) ); }
			if ( nm ) { entries[ nm ] = { type: dir[ i + 66 ], start: ddv.getUint32( i + 116, true ), size: ddv.getUint32( i + 120, true ) }; }
		}
		var root = entries[ 'Root Entry' ], miniStream = root ? big( root.start, root.size ) : new Uint8Array( 0 ), miniFat = [];
		if ( nMiniFat && miniFatStart < END ) { var mf = big( miniFatStart, nMiniFat * sec ), mdv = new DataView( mf.buffer, mf.byteOffset, mf.byteLength ); for ( i = 0; i + 4 <= mf.length; i += 4 ) { miniFat.push( mdv.getUint32( i, true ) ); } }
		function stream( e ) {
			if ( ! e ) { return null; }
			if ( e.size >= miniCut ) { return big( e.start, e.size ); }
			var ch = chain( miniFat, e.start ), out = new Uint8Array( ch.length * mini );
			ch.forEach( function ( s, k ) { out.set( miniStream.subarray( s * mini, s * mini + mini ), k * mini ); } );
			return out.subarray( 0, e.size );
		}
		var wd = stream( entries.WordDocument );
		if ( ! wd ) { throw new Error( 'Word ফাইলে লেখা পাওয়া যায়নি' ); }
		var wdv = new DataView( wd.buffer, wd.byteOffset, wd.byteLength );
		if ( wdv.getUint16( 0, true ) !== 0xA5EC ) { throw new Error( 'এই Word ফাইলের ধরন পড়া যায়নি' ); }
		var table = stream( entries[ ( wdv.getUint16( 10, true ) & 0x0200 ) ? '1Table' : '0Table' ] );
		var csw = wdv.getUint16( 32, true ), cslw = wdv.getUint16( 34 + csw * 2, true ), lwBase = 36 + csw * 2;
		var ccpText = wdv.getInt32( lwBase + 12, true );
		var blob = lwBase + cslw * 4 + 2, fcClx = wdv.getUint32( blob + 33 * 8, true ), lcbClx = wdv.getUint32( blob + 33 * 8 + 4, true );
		var tdv = new DataView( table.buffer, table.byteOffset, table.byteLength ), pos = fcClx, end = fcClx + lcbClx, text = '';
		while ( pos < end ) {
			var tag = table[ pos ];
			if ( tag === 1 ) { pos += 3 + tdv.getUint16( pos + 1, true ); continue; }
			if ( tag !== 2 ) { break; }
			var lcb = tdv.getUint32( pos + 1, true ), base = pos + 5, n = ( lcb - 4 ) / 12, pieceText = '';
			for ( i = 0; i < n; i++ ) {
				var cpS = tdv.getUint32( base + i * 4, true ), cpE = tdv.getUint32( base + ( i + 1 ) * 4, true );
				if ( cpS >= ccpText ) { break; }
				cpE = Math.min( cpE, ccpText );
				var fc = tdv.getUint32( base + ( n + 1 ) * 4 + i * 8 + 2, true ), len = cpE - cpS;
				if ( fc & 0x40000000 ) {
					var o1 = ( fc & 0x3FFFFFFF ) / 2;
					pieceText += new TextDecoder( 'windows-1252' ).decode( wd.subarray( o1, o1 + len ) );
				} else {
					var o2 = fc & 0x3FFFFFFF;
					pieceText += new TextDecoder( 'utf-16le' ).decode( wd.subarray( o2, o2 + len * 2 ) );
				}
			}
			text = pieceText; break;
		}
		return text.replace( /\r\n?/g, '\n' ).replace( /\x07/g, '\t' ).replace( /[\x0B]/g, '\n' ).replace( /[\x00-\x06\x08\x0C\x0E-\x1F\x13\x14\x15]/g, '' );
	}

	/* ---------------------------------------------------------------- file -> unicode text */
	function readFile( file ) {
		return file.arrayBuffer().then( function ( buf ) {
			var u8 = new Uint8Array( buf, 0, 4 ), isZip = u8[ 0 ] === 0x50 && u8[ 1 ] === 0x4B;
			return isZip ? readDocx( buf ) : Promise.resolve( readDoc( buf ) );
		} ).then( function ( raw ) {
			return norm( /[ঀ-৿]/.test( raw ) ? raw : bijoyToUnicode( raw ) );
		} );
	}

	/* ---------------------------------------------------------------- text -> people */
	var DIG = '০-৯0-9';
	/* য় ড় ঢ় may be one character or two (letter + nukta): make both spellings the same */
	function norm( t ) { return String( t ).replace( /য\u09BC/g, '\u09DF' ).replace( /ড\u09BC/g, '\u09DC' ).replace( /ঢ\u09BC/g, '\u09DD' ); }
	function clean( v ) { return String( v == null ? '' : v ).replace( /[ \t]+/g, ' ' ).replace( /^[\s:ঃ\-]+/, '' ).replace( /[\s:ঃ]+$/, '' ).trim(); }

	function field( block, label ) {
		var re = new RegExp( '(?:^|\\n)[ \\t]*(?:[' + DIG + ']+[ \\t]*[।.][ \\t]*)?' + norm( label ) + '[ \\t]*[ঃ:][ \\t\\-]*([^\\n]*)' );
		var m = re.exec( block );
		return m ? clean( m[ 1 ] ) : '';
	}

	function parsePerson( block ) {
		var p = { nid: '', name: '', gender: '', spouse: '', father: '', mother: '', dob: '', religion: '', profession: '', nationality: '', perm: '', present: '' };
		var b = block.replace( /\t+/g, ' ' ).replace( / {2,}/g, ' ' );
		p.name = field( b, 'নাম' ); p.spouse = field( b, '(?:স্বামীর|স্ত্রীর) নাম' ); p.father = field( b, 'পিতার নাম' ); p.mother = field( b, 'মাতার নাম' );
		p.dob = field( b, 'জন্ম তারিখ' ).replace( /\s*ইং\.?$/, '' ).trim();
		p.religion = field( b, 'ধর্ম' ); p.profession = field( b, 'পেশা' ); p.nationality = field( b, 'জাতীয়তা' );
		var n = new RegExp( '(?:পরিচিতি|পরিচয়(?:পত্র)?|এন\\s*আই\\s*ডি|NID)[^\\n:ঃ\\d০-৯]*[-ঃ:]*[ \\t]*([' + DIG + '][' + DIG + ' ]*)', 'i' ).exec( b );
		if ( n ) { p.nid = n[ 1 ].replace( /\s+$/, '' ); }
		if ( /স্বামীর নাম/.test( b ) && p.spouse ) { p.gender = 'মহিলা'; }
		else if ( /স্ত্রীর নাম/.test( b ) && p.spouse ) { p.gender = 'পুরুষ'; }
		// addresses: either "স্থায়ী ঠিকানা ঃ ..." or a header line followed by one or two address lines
		var lines = b.split( '\n' ), hi = -1, k;
		for ( k = 0; k < lines.length; k++ ) { if ( /স্থায়ী ঠিকানা/.test( lines[ k ] ) ) { hi = k; break; } }
		if ( hi >= 0 ) {
			var head = lines[ hi ], inline = /স্থায়ী ঠিকানা\s*[ঃ:]*\s*-?\s*(.*?)(?:বর্তমান ঠিকানা\s*[ঃ:]*\s*-?\s*(.*))?$/.exec( head );
			var strip = function ( a ) { return clean( String( a ).replace( /^\s*ঠিকানা\s*[ঃ:]/, '' ) ); };
			var perm = inline ? strip( inline[ 1 ] ) : '', pres = inline && inline[ 2 ] ? strip( inline[ 2 ] ) : '';
			if ( ! perm ) {
				var addr = [];
				for ( k = hi + 1; k < lines.length && addr.length < 2; k++ ) { var ln = clean( lines[ k ] ); if ( ! ln ) { if ( addr.length ) { break; } continue; } addr.push( ln ); }
				perm = strip( addr[ 0 ] || '' ); pres = strip( addr[ 1 ] || '' );
			}
			p.perm = perm; p.present = pres || ( /বর্তমান ঠিকানা/.test( head ) ? perm : '' );
		}
		return p;
	}

	function parsePeople( text ) {
		var lines = text.replace( /\r/g, '' ).split( '\n' ), i, di = -1, oi = -1, ei = lines.length;
		for ( i = 0; i < lines.length; i++ ) {
			if ( di < 0 && /গ্রহ|গ্রহী/.test( lines[ i ] ) && /নাম ও ঠিকানা/.test( lines[ i ] ) ) { di = i; }
			else if ( oi < 0 && /দাতা/.test( lines[ i ] ) && /নাম ও ঠিকানা/.test( lines[ i ] ) ) { oi = i; }
			else if ( oi >= 0 && i > oi && /^\s*[৫5]\s*[।.]/.test( lines[ i ] ) ) { ei = i; break; }
		}
		function section( from, to, role ) {
			if ( from < 0 ) { return []; }
			var blocks = [], cur = null;
			lines.slice( from + 1, to ).forEach( function ( l ) {
				if ( new RegExp( '^\\s*(?:[' + DIG + ']+\\s*[।.]\\s*)?নাম\\s*[ঃ:]' ).test( l ) ) { cur = [ l ]; blocks.push( cur ); }
				else if ( cur ) { cur.push( l ); }
			} );
			return blocks.map( function ( bl ) { var p = parsePerson( bl.join( '\n' ) ); p.role = role; return p; } ).filter( function ( p ) { return p.name || p.nid; } );
		}
		var first = ( di >= 0 && oi >= 0 ) ? Math.min( di, oi ) : Math.max( di, oi );
		var donees = section( di, oi > di ? oi : ei, 'গ্রহীতা' ), donors = section( oi, oi > di ? ei : ( di > oi ? di : ei ), 'দাতা' );
		return first < 0 ? [] : donees.concat( donors );
	}

	window.sojib68Import = { bijoyToUnicode: bijoyToUnicode, readFile: readFile, parsePeople: parsePeople };

	/* ---------------------------------------------------------------- dialog */
	var FIELDS = [ [ 'nid', 'NID' ], [ 'name', 'নাম' ], [ 'spouse', 'স্বামী/স্ত্রী' ], [ 'father', 'পিতা' ], [ 'mother', 'মাতা' ], [ 'dob', 'জন্ম তারিখ' ], [ 'religion', 'ধর্ম' ], [ 'profession', 'পেশা' ], [ 'nationality', 'জাতীয়তা' ], [ 'perm', 'স্থায়ী ঠিকানা' ], [ 'present', 'বর্তমান ঠিকানা' ] ];
	function esc( s ) { return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ]; } ); }
	var en = function ( s ) { return String( s ).replace( /[০-৯]/g, function ( d ) { return '০১২৩৪৫৬৭৮৯'.indexOf( d ); } ).replace( /\D/g, '' ); };

	function open() {
		var old = document.getElementById( 'dw-imp-dlg' ); if ( old ) { old.remove(); }
		var d = document.createElement( 'div' ); d.id = 'dw-imp-dlg'; d.className = 'dw-tpl-dlg';
		d.innerHTML = '<div class="dw-tpl-box dw-imp-box"><h3>পুরোনো দলিল থেকে ব্যক্তির তথ্য তুলুন</h3>' +
			'<p class="dw-hint">আপনার কম্পিউটার থেকে পুরোনো Word ফাইল (.doc বা .docx) বাছুন — একসঙ্গে অনেকগুলো বাছা যায়। ফাইলগুলো ওয়েবসাইটে আপলোড হয় না, শুধু আপনার ব্রাউজারে পড়া হয়। নিচে যা দেখাবে তা মিলিয়ে নিয়ে যাদের রাখতে চান তাদের সেভ করুন। শুধু NID ও নাম দুটোই আছে এমন ব্যক্তিই সেভ হবে; আগে থেকে সেভ করা ব্যক্তির তথ্য বদলাবে না।</p>' +
			'<input type="file" id="dw-imp-file" multiple accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">' +
			'<div id="dw-imp-list"></div>' +
			'<div class="dw-tpl-act"><button type="button" class="btn btn-primary btn-sm" id="dw-imp-save" disabled>✓ নির্বাচিতগুলো সেভ করুন</button> <button type="button" class="btn btn-outline btn-sm" id="dw-imp-close">বন্ধ করুন</button> <span class="dw-hint" id="dw-imp-msg"></span></div></div>';
		document.body.appendChild( d );
		var list = d.querySelector( '#dw-imp-list' ), msg = d.querySelector( '#dw-imp-msg' ), saveBtn = d.querySelector( '#dw-imp-save' ), seen = {}, count = 0;

		function card( p, fname ) {
			var id = ++count, key = en( p.nid ), ok = key.length >= 8 && p.name, dup = ok && seen[ key ];
			if ( ok ) { seen[ key ] = true; }
			var h = '<div class="dw-imp-p' + ( ok && ! dup ? '' : ' is-off' ) + '" data-imp="' + id + '"><label class="dw-imp-h"><input type="checkbox" data-imp-on' + ( ok && ! dup ? ' checked' : '' ) + '> <b>' + esc( p.role ) + '</b> · ' + esc( p.name || '(নাম পাওয়া যায়নি)' ) + ' <small>— ' + esc( fname ) + '</small>' + ( ! ok ? ' <em class="dw-imp-warn">NID বা নাম নেই — সেভ হবে না (নিচে লিখে দিলে হবে)</em>' : '' ) + ( dup ? ' <em class="dw-imp-warn">আগের ফাইলে একই NID আছে</em>' : '' ) + '</label><div class="dw-imp-g">';
			FIELDS.forEach( function ( f ) { h += '<label>' + f[ 1 ] + '<input type="text" data-k="' + f[ 0 ] + '" value="' + esc( p[ f[ 0 ] ] ) + '"></label>'; } );
			h += '<label>লিঙ্গ<select data-k="gender"><option value="">—</option><option' + ( p.gender === 'পুরুষ' ? ' selected' : '' ) + '>পুরুষ</option><option' + ( p.gender === 'মহিলা' ? ' selected' : '' ) + '>মহিলা</option></select></label>';
			return h + '</div></div>';
		}
		function refresh() { var n = list.querySelectorAll( '[data-imp-on]:checked' ).length; saveBtn.disabled = ! n; saveBtn.textContent = '✓ নির্বাচিত ' + n + ' জনকে সেভ করুন'; }

		d.querySelector( '#dw-imp-file' ).addEventListener( 'change', function ( e ) {
			var files = [].slice.call( e.target.files ); list.innerHTML = ''; seen = {}; count = 0; msg.textContent = 'পড়া হচ্ছে…';
			var chain = Promise.resolve();
			files.forEach( function ( f ) {
				chain = chain.then( function () { return readFile( f ); } ).then( function ( text ) {
					var people = parsePeople( text );
					list.insertAdjacentHTML( 'beforeend', '<h4 class="dw-imp-f">' + esc( f.name ) + ' — ' + ( people.length ? people.length + ' জনের তথ্য পাওয়া গেছে' : 'কোনো ব্যক্তির তথ্য পাওয়া যায়নি (দলিলের ৩/৪ নম্বর অংশের ধরন মেলেনি)' ) + '</h4>' );
					people.forEach( function ( p ) { list.insertAdjacentHTML( 'beforeend', card( p, f.name ) ); } );
				}, function ( err ) { list.insertAdjacentHTML( 'beforeend', '<h4 class="dw-imp-f">' + esc( f.name ) + ' — ⚠ ' + esc( err.message || 'পড়া যায়নি' ) + '</h4>' ); } );
			} );
			chain.then( function () { msg.textContent = ''; refresh(); } );
		} );
		d.addEventListener( 'change', function ( e ) { var c = e.target.closest && e.target.closest( '.dw-imp-p' ); if ( c ) { c.classList.toggle( 'is-off', ! c.querySelector( '[data-imp-on]' ).checked ); refresh(); } } );
		d.querySelector( '#dw-imp-close' ).addEventListener( 'click', function () { d.remove(); } );
		saveBtn.addEventListener( 'click', function () {
			var people = [];
			[].forEach.call( list.querySelectorAll( '.dw-imp-p' ), function ( c ) {
				if ( ! c.querySelector( '[data-imp-on]' ).checked ) { return; }
				var p = {}; [].forEach.call( c.querySelectorAll( '[data-k]' ), function ( i ) { p[ i.getAttribute( 'data-k' ) ] = i.value.trim(); } );
				if ( en( p.nid ).length >= 8 && p.name ) { p.nid_display = p.nid; people.push( p ); }
			} );
			if ( ! people.length ) { msg.textContent = '⚠ NID ও নাম আছে এমন কাউকে বাছুন'; return; }
			var fd = new FormData(); fd.append( 'action', 'sojib68_dw_party_import' ); fd.append( 'nonce', CFG.nonce ); fd.append( 'people', JSON.stringify( people ) );
			saveBtn.disabled = true; msg.textContent = 'সেভ হচ্ছে…';
			fetch( CFG.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } ).then( function ( res ) {
				if ( res && res.success ) { msg.textContent = '✓ নতুন ' + res.data.added + ' জন সেভ হয়েছে' + ( res.data.skipped ? '; ' + res.data.skipped + ' জন আগে থেকেই ছিল (বদলানো হয়নি)' : '' ); }
				else { msg.textContent = '⚠ ' + ( ( res && res.data && res.data.message ) || 'সেভ হয়নি' ); }
				refresh();
			}, function () { msg.textContent = '⚠ সংযোগ সমস্যা'; refresh(); } );
		} );
		refresh();
	}

	document.addEventListener( 'click', function ( e ) { if ( e.target.closest && e.target.closest( '#dw-import-open' ) ) { e.preventDefault(); open(); } } );
}() );

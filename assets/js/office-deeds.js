( function () {
	'use strict';

	var form = document.getElementById( 'deed-form' );
	if ( ! form ) {
		return;
	}

	var baseUrl = form.getAttribute( 'data-base-url' ) || '/';

	/* ---------------- Repeatable rows (cloned from <template>s) ---------------- */

	var BN = '০১২৩৪৫৬৭৮৯';

	function toNumber( str ) {
		var clean = String( str || '' )
			.replace( /[০-৯]/g, function ( d ) { return String( BN.indexOf( d ) ); } )
			.replace( /[^0-9.]/g, '' );
		var n = parseFloat( clean );
		return isNaN( n ) ? null : n;
	}

	function fmt( n ) {
		return '৳ ' + ( Math.round( n * 100 ) / 100 ).toLocaleString( 'en-IN', { maximumFractionDigits: 2 } );
	}

	function syncEmpty( group ) {
		var container = form.querySelector( '[data-repeat="' + group + '"]' );
		var note = form.querySelector( '[data-empty-for="' + group + '"]' );
		if ( container && note ) {
			note.hidden = container.children.length > 0;
		}
	}

	form.querySelectorAll( '[data-add-row]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var group = btn.getAttribute( 'data-add-row' );
			var container = form.querySelector( '[data-repeat="' + group + '"]' );
			var tpl = document.getElementById( 'tpl-' + group );
			if ( ! container || ! tpl ) {
				return;
			}
			var index = parseInt( container.getAttribute( 'data-next' ), 10 ) || 0;
			container.setAttribute( 'data-next', index + 1 );
			container.insertAdjacentHTML( 'beforeend', tpl.innerHTML.replace( /__INDEX__/g, index ) );
			var preset = btn.getAttribute( 'data-preset' );
			if ( preset ) {
				var cat = container.lastElementChild.querySelector( '[name$="[category]"]' );
				if ( cat ) { cat.value = preset; }
			}
			syncEmpty( group );
			updateSummary();
			var first = container.lastElementChild && container.lastElementChild.querySelector( 'input,select' );
			if ( first ) { first.focus(); }
		} );
	} );

	// Groups that always keep one visible row (clear it instead of removing the last one).
	var keepOne = { donors: 1, grantees: 1, lands: 1 };

	form.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.deed-row__remove' );
		if ( ! btn ) {
			return;
		}
		var row = btn.closest( '.deed-row' );
		var container = row && row.parentNode;
		if ( ! container ) {
			return;
		}
		var group = container.getAttribute( 'data-repeat' );
		if ( keepOne[ group ] && container.children.length === 1 ) {
			row.querySelectorAll( 'input,select' ).forEach( function ( el ) { el.value = ''; } );
		} else {
			row.remove();
		}
		syncEmpty( group );
		updateSummary();
	} );

	/* ---------------- Live summary ---------------- */

	function setSum( key, text ) {
		form.querySelectorAll( '[data-sum="' + key + '"]' ).forEach( function ( el ) { el.textContent = text; } );
	}

	function updateSummary() {
		var value = toNumber( ( document.getElementById( 'f-deed-value' ) || {} ).value );
		var totalDue = toNumber( ( document.getElementById( 'f-total-due' ) || {} ).value );

		var received = 0;
		form.querySelectorAll( '[data-money="receipt"]' ).forEach( function ( el ) {
			received += toNumber( el.value ) || 0;
		} );

		var expenses = 0;
		form.querySelectorAll( '[data-repeat="expenses"] .deed-row' ).forEach( function ( row ) {
			var amt = toNumber( ( row.querySelector( '[data-money="expense"]' ) || {} ).value ) || 0;
			expenses += amt;
		} );

		setSum( 'value', null === value ? '—' : fmt( value ) );
		setSum( 'total_due', null === totalDue ? '—' : fmt( totalDue ) );
		setSum( 'received', fmt( received ) );
		setSum( 'expenses', fmt( expenses ) );
		setSum( 'profit', null === totalDue ? '—' : fmt( totalDue - expenses ) );

		var chip = form.querySelector( '[data-sum="status"]' );
		var cls = 'deed-chip deed-chip--unknown';
		var chipText = 'প্রাপ্য অজানা';
		var dueText = '—';
		var dueLabel = 'বাকি';
		if ( null !== totalDue ) {
			var due = Math.round( ( totalDue - received ) * 100 ) / 100;
			if ( due > 0 ) {
				cls = 'deed-chip deed-chip--due'; chipText = 'বকেয়া আছে'; dueText = fmt( due );
			} else if ( due < 0 ) {
				cls = 'deed-chip deed-chip--advance'; chipText = 'অগ্রিম জমা'; dueText = fmt( -due ); dueLabel = 'অগ্রিম';
			} else {
				cls = 'deed-chip deed-chip--settled'; chipText = 'পরিশোধিত'; dueText = fmt( 0 );
			}
		}
		setSum( 'due', dueText );
		setSum( 'due_label', dueLabel );
		if ( chip ) { chip.className = cls; chip.textContent = chipText; }
	}

	form.addEventListener( 'input', updateSummary );
	form.addEventListener( 'change', updateSummary );
	updateSummary();

	/* ---------------- Add custom office / deed type (AJAX, form data preserved) ---------------- */

	form.querySelectorAll( '[data-add]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var kind = btn.getAttribute( 'data-add' ); // 'office' or 'deed_type'
			var label = 'office' === kind ? 'নতুন অফিসের নাম লিখুন' : 'নতুন দলিলের ধরনের নাম লিখুন';
			var name = window.prompt( label, '' );
			if ( ! name || ! name.trim() ) {
				return;
			}

			var action = 'office' === kind ? 'sojib68_add_office' : 'sojib68_add_deed_type';
			var selectId = 'office' === kind ? 'f-office' : 'f-deed-type';
			var select = document.getElementById( selectId );

			var fd = new FormData();
			fd.append( 'action', action );
			fd.append( 'nonce', form.querySelector( '[name="nonce"]' ).value );
			fd.append( 'name', name.trim() );

			fetch( window.sojib68DeedAjax.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					if ( res.success && select ) {
						var opt = document.createElement( 'option' );
						opt.value = res.data.name;
						opt.textContent = res.data.name;
						opt.selected = true;
						select.appendChild( opt );
					} else if ( ! res.success ) {
						window.alert( res.data && res.data.message ? res.data.message : 'যোগ করা যায়নি।' );
					}
				} )
				.catch( function () {
					window.alert( 'নেটওয়ার্ক সমস্যা হয়েছে।' );
				} );
		} );
	} );

	/* ---------------- Delete a mistakenly-added office / deed type ---------------- */

	form.querySelectorAll( '[data-delete]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var kind = btn.getAttribute( 'data-delete' ); // 'office' or 'deed_type'
			var select = document.getElementById( btn.getAttribute( 'data-select' ) );
			if ( ! select || ! select.value ) {
				window.alert( 'আগে dropdown থেকে যেটা মুছতে চান সেটা নির্বাচন করুন।' );
				return;
			}
			var name = select.value;
			if ( ! window.confirm( '"' + name + '" তালিকা থেকে মুছে ফেলতে চান? আগের দলিলে এর প্রভাব পড়বে না।' ) ) {
				return;
			}

			var action = 'office' === kind ? 'sojib68_delete_office' : 'sojib68_delete_deed_type';
			var fd = new FormData();
			fd.append( 'action', action );
			fd.append( 'nonce', form.querySelector( '[name="nonce"]' ).value );
			fd.append( 'name', name );

			fetch( window.sojib68DeedAjax.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					if ( res.success ) {
						var opt = select.querySelector( 'option[value="' + CSS.escape( name ) + '"]' );
						if ( opt ) { opt.remove(); }
						select.value = '';
					} else {
						window.alert( res.data && res.data.message ? res.data.message : 'মুছা যায়নি।' );
					}
				} )
				.catch( function () {
					window.alert( 'নেটওয়ার্ক সমস্যা হয়েছে।' );
				} );
		} );
	} );

	/* ---------------- Date / year mismatch warning (non-blocking) ---------------- */

	var dateField = document.getElementById( 'f-reg-date' );
	var yearField = document.getElementById( 'f-reg-year' );
	var warningEl = document.getElementById( 'date-year-warning' );

	function checkDateYear() {
		if ( ! dateField || ! yearField || ! warningEl ) {
			return;
		}
		var dateVal = dateField.value;
		var yearVal = yearField.value.trim();
		if ( ! dateVal || ! yearVal ) {
			warningEl.hidden = true;
			return;
		}
		var dateYear = dateVal.split( '-' )[0];
		var bnDigits = '০১২৩৪৫৬৭৮৯';
		var normalizedYear = yearVal.replace( /[০-৯]/g, function ( d ) {
			return String( bnDigits.indexOf( d ) );
		} );
		warningEl.hidden = ( dateYear === normalizedYear );
	}
	if ( dateField ) { dateField.addEventListener( 'change', checkDateYear ); }
	if ( yearField ) { yearField.addEventListener( 'change', checkDateYear ); }

	/* ---------------- Submit (AJAX) ---------------- */

	var noticeArea = form.querySelector( '.deed-notice-area' );
	var formActions = form.querySelector( '.deed-form-actions' );
	var successActions = form.querySelector( '.deed-success-actions' );
	var submitButtons = form.querySelectorAll( 'button[type="submit"]' );
	var lastClickedAction = 'submit';

	submitButtons.forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			lastClickedAction = btn.getAttribute( 'data-action' ) || 'submit';
		} );
	} );

	function showNotice( type, message ) {
		if ( ! noticeArea ) {
			return;
		}
		var div = document.createElement( 'div' );
		div.className = 'office-notice office-notice--' + type;
		div.textContent = message;
		noticeArea.appendChild( div );
	}

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();

		if ( noticeArea ) {
			noticeArea.innerHTML = '';
		}
		submitButtons.forEach( function ( b ) { b.disabled = true; } );

		var fd = new FormData( form );
		fd.append( 'action', 'sojib68_save_deed' );

		fetch( window.sojib68DeedAjax.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				if ( res.success ) {
					showNotice( 'success', res.data.message );
					if ( res.data.duplicate_warning ) {
						showNotice( 'warning', res.data.duplicate_warning );
					}

					if ( 'submit-and-new' === lastClickedAction ) {
						window.location = baseUrl + 'entry-new';
						return;
					}

					if ( formActions ) { formActions.hidden = true; }
					if ( successActions ) { successActions.hidden = false; }
					var deedIdField = form.querySelector( '[name="deed_id"]' );
					if ( deedIdField ) { deedIdField.value = res.data.deed_id; }
				} else {
					showNotice( 'error', res.data && res.data.message ? res.data.message : 'সংরক্ষণ করা যায়নি।' );
					submitButtons.forEach( function ( b ) { b.disabled = false; } );
				}
			} )
			.catch( function () {
				showNotice( 'error', 'নেটওয়ার্ক সমস্যা হয়েছে। আবার চেষ্টা করুন।' );
				submitButtons.forEach( function ( b ) { b.disabled = false; } );
			} );
	} );
} )();

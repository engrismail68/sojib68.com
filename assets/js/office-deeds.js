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

	/* ---------------- পুরাতন হিসাব toggle ---------------- */

	var legacyToggle = form.querySelector( '[data-legacy-toggle]' );
	var totalInput = document.getElementById( 'f-total-due' );
	var legacyInput = document.getElementById( 'f-legacy-due' );
	var collectedInput = document.getElementById( 'f-collected' );

	function isLegacy() {
		return !! ( legacyToggle && legacyToggle.checked );
	}

	// Only the visible amount box is enabled (and so submitted); the typed
	// amount is carried across so switching never loses what was written.
	function applyLegacy( carry ) {
		var on = isLegacy();
		if ( carry && totalInput && legacyInput ) {
			if ( on ) { legacyInput.value = totalInput.value; } else { totalInput.value = legacyInput.value; }
		}
		if ( totalInput ) { totalInput.disabled = on; }
		if ( legacyInput ) { legacyInput.disabled = ! on; }
		// New deed: an old-notebook entry has no আদায় to record here.
		if ( collectedInput && ! collectedInput.readOnly ) { collectedInput.disabled = on; }
		form.querySelectorAll( '[data-legacy-show]' ).forEach( function ( el ) {
			el.hidden = el.getAttribute( 'data-legacy-show' ) !== ( on ? '1' : '0' );
		} );
		setSum( 'total_label', on ? 'পুরাতন বকেয়া' : 'মোট টাকা' );
	}

	if ( legacyToggle ) {
		legacyToggle.addEventListener( 'change', function () {
			applyLegacy( true );
			updateSummary();
			if ( isLegacy() && legacyInput ) { legacyInput.focus(); }
		} );
		applyLegacy( false );
	}

	function updateSummary() {
		var value = toNumber( ( document.getElementById( 'f-deed-value' ) || {} ).value );
		var totalDue = toNumber( ( ( isLegacy() ? legacyInput : totalInput ) || {} ).value );

		var received = 0;
		form.querySelectorAll( '[data-money="receipt"]' ).forEach( function ( el ) {
			if ( ! el.disabled ) {
				received += toNumber( el.value ) || 0;
			}
		} );

		var expenses = 0;
		form.querySelectorAll( '[data-money="expense"]' ).forEach( function ( el ) {
			expenses += toNumber( el.value ) || 0;
		} );

		setSum( 'value', null === value ? '—' : fmt( value ) );
		setSum( 'total_due', null === totalDue ? '—' : fmt( totalDue ) );
		setSum( 'received', fmt( received ) );
		setSum( 'expenses', fmt( expenses ) );
		setSum( 'profit', fmt( received - expenses ) );

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

	// Browsers can refill fields after a reload/back or by autofill without
	// firing any event, at an unpredictable moment. So re-check whenever the
	// page settles, and keep watching the money fields: if any value differs
	// from what the summary last saw, bring everything back in sync.
	var lastSeen = '';
	function snapshot() {
		var parts = [ legacyToggle && legacyToggle.checked ? 1 : 0 ];
		form.querySelectorAll( '#f-deed-value, #f-total-due, #f-legacy-due, [data-money]' ).forEach( function ( el ) {
			parts.push( el.disabled ? '-' : el.value );
		} );
		return parts.join( '|' );
	}
	function resync() {
		var now = snapshot();
		if ( now === lastSeen ) {
			return;
		}
		if ( legacyToggle ) { applyLegacy( false ); }
		updateSummary();
		lastSeen = snapshot();
	}
	window.addEventListener( 'pageshow', function () { lastSeen = ''; resync(); } );
	window.addEventListener( 'load', function () { lastSeen = ''; resync(); } );
	setTimeout( resync, 300 );
	setInterval( resync, 700 );

	/* ---------------- Add custom office / deed type / writer (AJAX, form data preserved) ---------------- */

	var LISTS = {
		office: { add: 'sojib68_add_office', del: 'sojib68_delete_office', select: 'f-office', prompt: 'নতুন অফিসের নাম লিখুন' },
		deed_type: { add: 'sojib68_add_deed_type', del: 'sojib68_delete_deed_type', select: 'f-deed-type', prompt: 'নতুন দলিলের ধরনের নাম লিখুন' },
		writer: { add: 'sojib68_add_writer', del: 'sojib68_delete_writer', select: 'f-writer', prompt: 'নতুন লেখকের নাম লিখুন' }
	};

	form.querySelectorAll( '[data-add]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var list = LISTS[ btn.getAttribute( 'data-add' ) ];
			if ( ! list ) {
				return;
			}
			var name = window.prompt( list.prompt, '' );
			if ( ! name || ! name.trim() ) {
				return;
			}

			var action = list.add;
			var select = document.getElementById( list.select );

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
			var list = LISTS[ btn.getAttribute( 'data-delete' ) ];
			var select = document.getElementById( btn.getAttribute( 'data-select' ) );
			if ( ! select || ! select.value ) {
				window.alert( 'আগে dropdown থেকে যেটা মুছতে চান সেটা নির্বাচন করুন।' );
				return;
			}
			var name = select.value;
			if ( ! window.confirm( '"' + name + '" তালিকা থেকে মুছে ফেলতে চান? আগের দলিলে এর প্রভাব পড়বে না।' ) ) {
				return;
			}

			var action = list ? list.del : '';
			if ( ! action ) {
				return;
			}
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

	/* ---------------- Original deed status (shows only the relevant fields) ---------------- */

	var statusGroup = form.querySelector( '[data-original-status-group]' );
	if ( statusGroup ) {
		var updateStatusFields = function () {
			var checked = statusGroup.querySelector( '[data-original-status-radio]:checked' );
			var current = checked ? checked.value : '';
			statusGroup.querySelectorAll( '[data-original-status-field]' ).forEach( function ( field ) {
				field.hidden = field.getAttribute( 'data-original-status-field' ) !== current;
			} );
		};
		statusGroup.querySelectorAll( '[data-original-status-radio]' ).forEach( function ( r ) {
			r.addEventListener( 'change', updateStatusFields );
		} );
		updateStatusFields();
	}

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

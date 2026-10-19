( function () {
	'use strict';

	var form = document.getElementById( 'deed-form' );
	if ( ! form ) {
		return;
	}

	var baseUrl = form.getAttribute( 'data-base-url' ) || '/';

	/* ---------------- Repeatable row groups ---------------- */

	var rowTemplates = {
		donors: function ( i ) {
			return personRowHtml( 'donors', i, 'দাতার নাম' );
		},
		grantees: function ( i ) {
			return personRowHtml( 'grantees', i, 'গ্রহীতার নাম' );
		},
		lands: function ( i ) {
			return landRowHtml( i );
		},
		receipts: function ( i ) {
			return receiptRowHtml( i );
		},
		expenses: function ( i ) {
			return expenseRowHtml( i );
		},
	};

	function fieldHtml( label, inputHtml ) {
		return '<div class="deed-field"><label>' + label + '</label>' + inputHtml + '</div>';
	}

	function personRowHtml( group, i, nameLabel ) {
		return (
			'<div class="deed-repeat-row"><div class="deed-grid-3">' +
			fieldHtml( nameLabel, '<input type="text" name="' + group + '[' + i + '][name]">' ) +
			fieldHtml( 'ঠিকানা', '<input type="text" name="' + group + '[' + i + '][address]">' ) +
			fieldHtml( 'মোবাইল নং', '<input type="text" name="' + group + '[' + i + '][mobile]">' ) +
			'</div><button type="button" class="deed-remove-row" aria-label="সরান">&times;</button></div>'
		);
	}

	function selectHtml( name, options, blank ) {
		var html = '<select name="' + name + '"><option value="">' + ( blank || '— নির্বাচন করুন —' ) + '</option>';
		options.forEach( function ( opt ) {
			html += '<option value="' + opt[0] + '">' + opt[1] + '</option>';
		} );
		return html + '</select>';
	}

	var landUnits = [ [ 'শতক', 'শতক/ডেসিমেল' ], [ 'একর', 'একর' ], [ 'বর্গফুট', 'বর্গফুট' ] ];
	var paymentMethods = [ [ 'cash', 'নগদ' ], [ 'bank', 'ব্যাংক' ], [ 'mobile_banking', 'মোবাইল ব্যাংকিং' ], [ 'other', 'অন্যান্য' ] ];
	var expenseCategories = [ [ 'registration_fee', 'রেজিস্ট্রেশন ফি' ], [ 'other_govt_fee_tax', 'অন্যান্য সরকারি ফি ও কর' ], [ 'other', 'অন্যান্য' ] ];
	var expenseStatuses = [ [ 'paid', 'পরিশোধিত' ], [ 'unpaid', 'অপরিশোধিত' ], [ 'unknown', 'অজানা' ] ];
	var expensePaidBy = [ [ 'me', 'আমার মাধ্যমে' ], [ 'client_direct', 'গ্রাহক সরাসরি' ], [ 'other', 'অন্যান্য' ], [ 'unknown', 'অজানা' ] ];

	function landRowHtml( i ) {
		return (
			'<div class="deed-repeat-row"><div class="deed-grid-4">' +
			fieldHtml( 'মৌজা', '<input type="text" name="lands[' + i + '][mouza]">' ) +
			fieldHtml( 'খতিয়ান নং', '<input type="text" name="lands[' + i + '][khatian_no]">' ) +
			fieldHtml( 'জমির পরিমাণ', '<input type="text" inputmode="decimal" name="lands[' + i + '][quantity]">' ) +
			fieldHtml( 'একক', selectHtml( 'lands[' + i + '][unit]', landUnits ) ) +
			'</div><button type="button" class="deed-remove-row" aria-label="সরান">&times;</button></div>'
		);
	}

	function receiptRowHtml( i ) {
		return (
			'<div class="deed-repeat-row"><div class="deed-grid-4">' +
			fieldHtml( 'টাকা গ্রহণের তারিখ', '<input type="date" name="receipts[' + i + '][received_date]">' ) +
			fieldHtml( 'জমার পরিমাণ', '<input type="text" inputmode="decimal" name="receipts[' + i + '][amount]">' ) +
			fieldHtml( 'মাধ্যম', selectHtml( 'receipts[' + i + '][method]', paymentMethods ) ) +
			fieldHtml( 'মন্তব্য', '<input type="text" name="receipts[' + i + '][note]">' ) +
			'</div><button type="button" class="deed-remove-row" aria-label="সরান">&times;</button></div>'
		);
	}

	function expenseRowHtml( i ) {
		return (
			'<div class="deed-repeat-row"><div class="deed-grid-3">' +
			fieldHtml( 'খরচের খাত', selectHtml( 'expenses[' + i + '][category]', expenseCategories ) ) +
			fieldHtml( 'খরচের বিবরণ', '<input type="text" name="expenses[' + i + '][description]">' ) +
			fieldHtml( 'টাকার পরিমাণ', '<input type="text" inputmode="decimal" name="expenses[' + i + '][amount]">' ) +
			fieldHtml( 'পরিশোধের তারিখ', '<input type="date" name="expenses[' + i + '][paid_date]">' ) +
			fieldHtml( 'পরিশোধের অবস্থা', selectHtml( 'expenses[' + i + '][paid_status]', expenseStatuses ) ) +
			fieldHtml( 'কে পরিশোধ করেছেন', selectHtml( 'expenses[' + i + '][paid_by]', expensePaidBy ) ) +
			'</div><button type="button" class="deed-remove-row" aria-label="সরান">&times;</button></div>'
		);
	}

	document.querySelectorAll( '[data-add-row]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var group     = btn.getAttribute( 'data-add-row' );
			var container = form.querySelector( '[data-repeat="' + group + '"]' );
			if ( ! container || ! rowTemplates[ group ] ) {
				return;
			}
			var nextIndex = container.children.length;
			container.insertAdjacentHTML( 'beforeend', rowTemplates[ group ]( nextIndex ) );
		} );
	} );

	form.addEventListener( 'click', function ( e ) {
		if ( e.target.classList.contains( 'deed-remove-row' ) ) {
			var row = e.target.closest( '.deed-repeat-row' );
			if ( row ) {
				row.remove();
			}
		}
	} );

	/* ---------------- Add custom office / deed type (AJAX, form data preserved) ---------------- */

	document.querySelectorAll( '[data-add]' ).forEach( function ( btn ) {
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

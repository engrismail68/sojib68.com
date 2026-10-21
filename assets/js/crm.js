(function () {
	'use strict';

	if (typeof sojib68Crm === 'undefined') { return; }

	function flash(message, isError) {
		var el = document.getElementById('crm-flash');
		if (!el) { return; }
		el.textContent = message;
		el.hidden = false;
		el.className = 'crm-flash ' + (isError ? 'crm-flash--error' : 'crm-flash--ok');
		window.setTimeout(function () { el.hidden = true; }, 4000);
	}

	function post(action, extra) {
		var body = new URLSearchParams(Object.assign({ action: action, nonce: sojib68Crm.nonce }, extra));
		return fetch(sojib68Crm.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		}).then(function (r) { return r.json(); });
	}

	document.querySelectorAll('.crm-status-select').forEach(function (select) {
		select.addEventListener('change', function () {
			var id = select.getAttribute('data-lead-id');
			post('sojib68_crm_update_status', { id: id, status: select.value }).then(function (res) {
				if (res && res.success) {
					flash('অবস্থা আপডেট হয়েছে।', false);
				} else {
					flash('আপডেট ব্যর্থ হয়েছে।', true);
				}
			});
		});
	});

	document.querySelectorAll('.crm-delete-lead').forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (!window.confirm('এই লিডটি মুছে ফেলতে চান?')) { return; }
			var id = btn.getAttribute('data-lead-id');
			post('sojib68_crm_delete_lead', { id: id }).then(function (res) {
				if (res && res.success) {
					var row = btn.closest('tr');
					if (row) { row.remove(); }
				} else {
					flash('মুছে ফেলা যায়নি।', true);
				}
			});
		});
	});

	document.querySelectorAll('[data-add-followup]').forEach(function (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var leadId = form.getAttribute('data-lead-id');
			var due = form.querySelector('[name="due_at"]').value;
			var note = form.querySelector('[name="note"]').value;
			if (!due) { return; }
			post('sojib68_crm_add_followup', { lead_id: leadId, due_at: due, note: note }).then(function (res) {
				if (res && res.success) {
					window.location.reload();
				} else {
					flash((res && res.data && res.data.message) || 'ফলোআপ যোগ করা যায়নি।', true);
				}
			});
		});
	});

	document.querySelectorAll('[data-complete-followup]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var id = btn.getAttribute('data-complete-followup');
			post('sojib68_crm_complete_followup', { id: id }).then(function (res) {
				if (res && res.success) {
					window.location.reload();
				} else {
					flash('আপডেট ব্যর্থ হয়েছে।', true);
				}
			});
		});
	});

	document.querySelectorAll('[data-add-note]').forEach(function (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var leadId = form.getAttribute('data-lead-id');
			var note = form.querySelector('[name="note"]').value;
			if (!note.trim()) { return; }
			post('sojib68_crm_add_note', { lead_id: leadId, note: note }).then(function (res) {
				if (res && res.success) {
					window.location.reload();
				} else {
					flash((res && res.data && res.data.message) || 'নোট যোগ করা যায়নি।', true);
				}
			});
		});
	});

	document.querySelectorAll('[data-create-work]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (btn.disabled) { return; }
			btn.disabled = true;
			var leadId = btn.getAttribute('data-create-work');
			post('sojib68_crm_create_work', { lead_id: leadId }).then(function (res) {
				if (res && res.success) {
					window.location.reload();
				} else {
					btn.disabled = false;
					flash((res && res.data && res.data.message) || 'কাজের ফাইল তৈরি করা যায়নি।', true);
				}
			});
		});
	});

	document.querySelectorAll('.crm-delete-cash').forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (!window.confirm('এই এন্ট্রিটি মুছে ফেলতে চান?')) { return; }
			var id = btn.getAttribute('data-cash-id');
			post('sojib68_crm_delete_cash', { id: id }).then(function (res) {
				if (res && res.success) {
					window.location.reload();
				} else {
					flash('মুছে ফেলা যায়নি।', true);
				}
			});
		});
	});

	document.querySelectorAll('[data-stage-select]').forEach(function (select) {
		var box = document.querySelector('[data-cancel-reason]');
		if (!box) { return; }
		var field = box.querySelector('textarea');
		function sync() {
			var cancelled = select.value === select.getAttribute('data-cancel-stage');
			box.hidden = !cancelled;
			field.required = cancelled;
		}
		select.addEventListener('change', sync);
		sync();
	});

	// Money inputs: accept Bangla or English digits and group as you type
	// (Bangladeshi style 12,34,567.50), keeping whichever digits were typed.
	var BN = '০১২৩৪৫৬৭৮৯';

	function toEn(s) {
		return s.replace(/[০-৯]/g, function (d) { return String(BN.indexOf(d)); });
	}
	function toBn(s) {
		return s.replace(/[0-9]/g, function (d) { return BN.charAt(+d); });
	}
	function groupBd(intPart) {
		if (intPart.length <= 3) { return intPart; }
		var last3 = intPart.slice(-3);
		var rest = intPart.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',');
		return rest + ',' + last3;
	}
	function formatMoney(raw) {
		var bangla = /[০-৯]/.test(raw);
		var s = toEn(raw).replace(/[^0-9.]/g, '');
		var dot = s.indexOf('.');
		var intPart = dot === -1 ? s : s.slice(0, dot);
		var frac = dot === -1 ? null : s.slice(dot + 1).replace(/\./g, '').slice(0, 2);
		intPart = intPart.replace(/^0+(?=\d)/, '');
		var out = groupBd(intPart) + (frac === null ? '' : '.' + frac);
		return bangla ? toBn(out) : out;
	}
	function digitsBefore(str, pos) {
		return toEn(str.slice(0, pos)).replace(/[^0-9.]/g, '').length;
	}

	// Live বাকী / প্রফিট on the entry form: existing totals (from the ledger)
	// plus whatever new আদায় / খরচ is being typed right now.
	function moneyValue(str) {
		var n = parseFloat(toEn(str || '').replace(/[^0-9.]/g, ''));
		return isNaN(n) ? null : n;
	}
	function taka(n) {
		var neg = n < 0;
		var parts = Math.abs(n).toFixed(2).split('.');
		return (neg ? '-' : '') + '৳' + groupBd(parts[0]) + '.' + parts[1];
	}
	document.querySelectorAll('[data-acc-form]').forEach(function (form) {
		var baseCollected = (parseInt(form.getAttribute('data-collected'), 10) || 0) / 100;
		var baseSpent = (parseInt(form.getAttribute('data-spent'), 10) || 0) / 100;
		var agreedIn = form.querySelector('[data-acc="agreed"]');
		var collectedIn = form.querySelector('[data-acc="collected"]');
		var spentIn = form.querySelector('[data-acc="spent"]');
		var dueOut = form.querySelector('[data-out="due"]');
		var profitOut = form.querySelector('[data-out="profit"]');
		if (!agreedIn || !dueOut || !profitOut) { return; }

		function recalc() {
			var agreed = moneyValue(agreedIn.value);
			var collected = baseCollected + (moneyValue(collectedIn.value) || 0);
			var spent = baseSpent + (moneyValue(spentIn.value) || 0);
			dueOut.classList.remove('is-due', 'is-extra', 'is-loss');
			profitOut.classList.remove('is-loss');
			if (agreed === null) {
				dueOut.textContent = 'চুক্তির টাকা দিন';
				profitOut.textContent = 'চুক্তির টাকা দিন';
				return;
			}
			var due = agreed - collected;
			if (due < 0) {
				dueOut.textContent = 'অতিরিক্ত জমা ' + taka(-due);
				dueOut.classList.add('is-extra');
			} else {
				dueOut.textContent = taka(due);
				if (due > 0) { dueOut.classList.add('is-due'); }
			}
			var profit = agreed - spent;
			profitOut.textContent = taka(profit);
			if (profit < 0) { profitOut.classList.add('is-loss'); }
		}
		[agreedIn, collectedIn, spentIn].forEach(function (el) { if (el) { el.addEventListener('input', recalc); } });
		recalc();
	});

	document.querySelectorAll('[data-money]').forEach(function (input) {
		function apply() {
			var old = input.value;
			var caret = input.selectionStart === null ? old.length : input.selectionStart;
			var keep = digitsBefore(old, caret);
			var next = formatMoney(old);
			if (next === old) { return; }
			input.value = next;
			// Put the caret back after the same number of digits it was after before formatting.
			var pos = 0;
			var seen = 0;
			while (pos < next.length && seen < keep) {
				if (/[0-9০-৯.]/.test(next.charAt(pos))) { seen++; }
				pos++;
			}
			if (document.activeElement === input) {
				input.setSelectionRange(pos, pos);
			}
		}
		input.addEventListener('input', apply);
		apply();
	});

	var menuToggle = document.getElementById('crm-menu-toggle');
	var sidebar = document.getElementById('crm-sidebar');
	if (menuToggle && sidebar) {
		menuToggle.addEventListener('click', function () {
			var isOpen = sidebar.classList.toggle('is-open');
			menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		});
	}
})();

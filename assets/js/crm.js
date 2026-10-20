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

	var menuToggle = document.getElementById('crm-menu-toggle');
	var sidebar = document.getElementById('crm-sidebar');
	if (menuToggle && sidebar) {
		menuToggle.addEventListener('click', function () {
			var isOpen = sidebar.classList.toggle('is-open');
			menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		});
	}
})();

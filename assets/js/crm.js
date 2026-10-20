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

	var menuToggle = document.getElementById('crm-menu-toggle');
	var sidebar = document.getElementById('crm-sidebar');
	if (menuToggle && sidebar) {
		menuToggle.addEventListener('click', function () {
			var isOpen = sidebar.classList.toggle('is-open');
			menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		});
	}
})();

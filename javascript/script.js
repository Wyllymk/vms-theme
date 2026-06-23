/**
 * VMS Theme — JS bundle entry point.
 *
 * Wires up vendor libraries (Alpine, Chart.js, flatpickr) as globals so the
 * existing IIFE-wrapped component code in ./main.js can consume them without
 * modification, then boots Alpine.
 *
 * Build:   npm run build:js   → assets/js/app.js (+ app.min.js)
 * Watch:   npm run watch:js
 */

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import Chart from 'chart.js/auto';
import flatpickr from 'flatpickr';

// Expose vendors on window — main.js reads them as globals.
window.Alpine    = Alpine;
window.Chart     = Chart;
window.flatpickr = flatpickr;

// Alpine plugins.
Alpine.plugin(collapse);
Alpine.plugin(focus);

// Load component registrations. This file attaches a listener to
// `alpine:init` and registers every Alpine.store / Alpine.data factory,
// so it MUST be imported before Alpine.start() fires the event.
// import './main.js';

// Boot Alpine once the DOM is ready. WordPress loads this bundle in the
// footer so the DOM is usually complete, but guard anyway so inline
// template <script> blocks (e.g. loginPage() in front-page.php) have
// definitely executed before Alpine walks the tree.
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => Alpine.start());
} else {
	Alpine.start();
}
/**
 * VMS Theme — Main JavaScript
 *
 * Alpine.js-powered frontend for the Visitor Management System WordPress theme.
 * Handles theme switching, toast notifications, SPA routing, dashboard stats,
 * guest/visit CRUD, sign-in desk, supplier/accommodation/reciprocation modules,
 * reports, member profiles, authentication, and dynamic module building.
 *
 * @package VMS_Theme
 * @since   2.0.0
 */

/* global vmsTheme, Alpine, Chart, flatpickr */

(function () {
	'use strict';

	// ---------------------------------------------------------------------------
	// Globals — safe references to the localized config object
	// ---------------------------------------------------------------------------

	const CONFIG  = window.vmsTheme ?? {};
	const AJAX    = CONFIG.ajaxUrl ?? '/wp-admin/admin-ajax.php';
	const NONCES  = CONFIG.nonces ?? {};
	const I18N    = CONFIG.i18n ?? {};
	const MODULES = CONFIG.modules ?? {};

	// ---------------------------------------------------------------------------
	// Helper utilities
	// ---------------------------------------------------------------------------

	/**
	 * Wrapper around fetch() that POSTs FormData to the WP AJAX endpoint.
	 *
	 * @param {string} action   WordPress AJAX action name.
	 * @param {Object} data     Key/value payload.
	 * @param {string} nonceKey Which nonce to include ('guest'|'settings'|'audit').
	 * @returns {Promise<Object>} Parsed JSON response.
	 */
	function vmsFetch(action, data = {}, nonceKey = 'guest') {
		const fd = new FormData();
		fd.append('action', action);
		fd.append('nonce', NONCES[nonceKey] ?? '');

		Object.entries(data).forEach(function ([key, value]) {
			if (value !== null && value !== undefined) {
				fd.append(key, value);
			}
		});

		return fetch(AJAX, { method: 'POST', credentials: 'same-origin', body: fd })
		.then(function (res) {
			if (!res.ok) {
				throw new Error('Network response ' + res.status);
			}
			return res.json();
		})
		.then(function (json) {
			if (json && json.success === false) {
				var msg = (json.data && json.data.message) ? json.data.message : (I18N.error || 'An error occurred.');
				throw new Error(msg);
			}
			return json;
		});
	}

	/**
	 * Format an ISO/MySQL date string into a locale-friendly representation.
	 *
	 * @param {string|null} dateStr Date string.
	 * @param {Object}      opts    Intl.DateTimeFormat options.
	 * @returns {string} Formatted date or empty string.
	 */
	function formatDate(dateStr, opts) {
		if (!dateStr) return '';
		try {
			var d = new Date(dateStr);
			if (isNaN(d.getTime())) return dateStr;
			var defaults = { year: 'numeric', month: 'short', day: 'numeric' };
			return d.toLocaleDateString(undefined, opts || defaults);
		} catch (_e) {
			return dateStr;
		}
	}

	/**
	 * Format a date-time string including time portion.
	 *
	 * @param {string|null} dateStr Date-time string.
	 * @returns {string} Formatted date-time or empty string.
	 */
	function formatDateTime(dateStr) {
		return formatDate(dateStr, {
			year: 'numeric', month: 'short', day: 'numeric',
			hour: '2-digit', minute: '2-digit'
		});
	}

	/**
	 * Debounce a function so it fires only after a pause in calls.
	 *
	 * @param {Function} fn    Function to debounce.
	 * @param {number}   delay Milliseconds to wait.
	 * @returns {Function} Debounced wrapper.
	 */
	function debounce(fn, delay) {
		var timer = null;
		return function () {
			var ctx = this;
			var args = arguments;
			if (timer) clearTimeout(timer);
			timer = setTimeout(function () {
				fn.apply(ctx, args);
			}, delay);
		};
	}

	/**
	 * Escape a string for safe insertion into HTML.
	 *
	 * @param {string} str Raw string.
	 * @returns {string} HTML-safe string.
	 */
	function escapeHtml(str) {
		if (!str) return '';
		var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
		return String(str).replace(/[&<>"']/g, function (c) { return map[c]; });
	}

	/**
	 * Generate a simple unique identifier for internal tracking (toasts, etc.).
	 *
	 * @returns {string}
	 */
	function uid() {
		return '_' + Math.random().toString(36).slice(2, 10) + Date.now().toString(36);
	}

	// ---------------------------------------------------------------------------
	// Expose helpers on window for optional external use
	// ---------------------------------------------------------------------------

	window.vmsFetch    = vmsFetch;
	window.formatDate  = formatDate;
	window.formatDateTime = formatDateTime;
	window.debounce    = debounce;
	window.escapeHtml  = escapeHtml;

	/**
	 * Global toast notification helper.
	 * Can be called from any page template as: window.vmsToast(message, type)
	 *
	 * @param {string} message  Text to display.
	 * @param {string} type     'success'|'error'|'warning'|'info'.
	 * @param {number} duration Auto-dismiss ms (default 4000).
	 */
	window.vmsToast = function (message, type, duration) {
		type = type || 'success';
		duration = duration !== undefined ? duration : 4000;

		// Dispatch to Alpine toast container (template-tags.php)
		window.dispatchEvent(new CustomEvent('toast', {
			detail: { message: message, type: type, title: '', visible: true, id: uid() }
		}));

		// Also try the Alpine store method
		try {
			if (window.Alpine && Alpine.store('toast')) {
				Alpine.store('toast').show(message, type, duration);
			}
		} catch (_e) { /* Alpine may not be ready yet */ }
	};

	/**
	 * Global PDF export helper.
	 * Handles the response from VMS export AJAX endpoints.
	 *
	 * @param {string} action   AJAX action name.
	 * @param {Object} data     Additional payload data.
	 * @param {string} nonceKey Nonce key (default 'guest').
	 */
	window.vmsExportPdf = function (action, data, nonceKey) {
		nonceKey = nonceKey || 'guest';
		vmsFetch(action, data || {}, nonceKey)
		.then(function (json) {
			var d = (json && json.data) ? json.data : {};
			if (d.method === 'print' && d.html) {
				// Open print-friendly HTML in new window
				var win = window.open('', '_blank', 'width=1200,height=800');
				if (win) {
					win.document.write(d.html);
					win.document.close();
				} else {
					window.vmsToast('Pop-up blocked. Please allow pop-ups for PDF export.', 'warning');
				}
			} else {
				window.vmsToast('Export generated successfully.', 'success');
			}
		})
		.catch(function (err) {
			window.vmsToast(err.message || 'Export failed.', 'error');
		});
	};

	// ---------------------------------------------------------------------------
	// Wait for Alpine before registering anything
	// ---------------------------------------------------------------------------

	document.addEventListener('alpine:init', function () {

		// =========================================================================
		// 1. Toast Manager — Alpine.store('toast')
		// =========================================================================

		Alpine.store('toast', {
			items: [],
			_counter: 0,

			/**
			 * Show a toast notification.
			 *
			 * @param {string} message  Text to display.
			 * @param {string} type     One of 'success','error','warning','info'.
			 * @param {number} duration Auto-dismiss milliseconds (0 = manual).
			 */
			show: function (message, type, duration) {
				type = type || 'success';
				duration = duration !== undefined ? duration : 3000;

				var id = ++this._counter;

				// Keep a maximum of 5 visible toasts.
				if (this.items.length >= 5) {
					this.items.shift();
				}

				this.items.push({ id: id, message: message, type: type });

				if (duration > 0) {
					var self = this;
					setTimeout(function () { self.dismiss(id); }, duration);
				}
			},

			/**
			 * Dismiss a specific toast by its id.
			 *
			 * @param {number} id Toast identifier.
			 */
			dismiss: function (id) {
				this.items = this.items.filter(function (t) { return t.id !== id; });
			}
		});

		// =========================================================================
		// 1b. Toast Manager Component — toastManager()
		//
		// Used by template-tags.php vms_render_toast() container.
		// Receives dispatched CustomEvents from window.vmsToast().
		// =========================================================================

		Alpine.data('toastManager', function () {
			return {
				toasts: [],
				_counter: 0,

				addToast: function (detail) {
					var id = detail.id || (++this._counter + '_' + Date.now());
					var toast = {
						id: id,
						message: detail.message || '',
						title: detail.title || '',
						type: detail.type || 'success',
						visible: true
					};

					if (this.toasts.length >= 5) {
						this.toasts.shift();
					}

					this.toasts.push(toast);

					var self = this;
					setTimeout(function () {
						self.removeToast(id);
					}, 4000);
				},

				removeToast: function (id) {
					var idx = this.toasts.findIndex(function (t) { return t.id === id; });
					if (idx !== -1) {
						this.toasts[idx].visible = false;
						var self = this;
						setTimeout(function () {
							self.toasts = self.toasts.filter(function (t) { return t.id !== id; });
						}, 300);
					}
				}
			};
		});

		// =========================================================================
		// 1c. Modal Manager Component — modalManager()
		//
		// Used by template-tags.php vms_render_modal_container().
		// =========================================================================

		Alpine.data('modalManager', function () {
			return {
				isOpen: false,
				title: '',
				body: '',
				modalSize: 'md',
				showFooter: false,
				confirmLabel: 'Confirm',
				confirmClass: '',
				_onConfirm: null,

				open: function (detail) {
					this.title = detail.title || '';
					this.body = detail.body || '';
					this.modalSize = detail.size || 'md';
					this.showFooter = !!detail.onConfirm;
					this.confirmLabel = detail.confirmLabel || 'Confirm';
					this.confirmClass = detail.confirmClass || '';
					this._onConfirm = detail.onConfirm || null;
					this.isOpen = true;
				},

				close: function () {
					this.isOpen = false;
					this._onConfirm = null;
				},

				confirm: function () {
					if (typeof this._onConfirm === 'function') {
						this._onConfirm();
					}
					this.close();
				}
			};
		});

		// =========================================================================
		// 2. Theme Manager — themeManager()
		// =========================================================================

		Alpine.data('themeManager', function () {
			return {
				// Reactive state — read directly by x-bind:class on <html> so Alpine
				// handles the .dark class declaratively. `darkMode` is the user's
				// explicit preference; `prefersDark` mirrors the OS-level media query.
				darkMode: 'system', // 'light' | 'dark' | 'system'
				prefersDark: false,
				_mediaQuery: null,

				// Back-compat: some older page templates reference `mode` directly,
				// so expose it as a computed alias.
				get mode() { return this.darkMode; },

					init: function () {
						var self = this;

						// Hook up the OS-level preference listener first so `prefersDark`
						// is correct before we evaluate the x-bind expression.
						this._mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
						this.prefersDark = this._mediaQuery.matches;
						this._mediaQuery.addEventListener('change', function (e) {
							self.prefersDark = e.matches;
						});

						// Restore the persisted preference. Unknown/absent → 'system'.
						var saved = localStorage.getItem('vms-theme');
						if (saved === 'light' || saved === 'dark' || saved === 'system') {
							this.darkMode = saved;
						}

						// Enable the scoped colour transitions (see theme.css §21) on the
						// very next tick so the initial paint isn't animated — only
						// user-triggered toggles get the 400ms cross-fade.
						requestAnimationFrame(function () {
							document.documentElement.classList.add('transitioning');
						});
					},

					/**
					 * Set the theme mode and persist to localStorage.
					 * The .dark class is applied reactively by x-bind on <html>, so
					 * we only need to mutate state here.
					 *
					 * @param {string} newMode 'light' | 'dark' | 'system'
					 */
					setDarkMode: function (newMode) {
						this.darkMode = newMode;
						try {
							localStorage.setItem('vms-theme', newMode);
						} catch (e) {
							// Storage may be unavailable (private browsing, quota) — fail
							// silently and keep the in-memory preference for this session.
						}
					},

					// Back-compat alias for templates still calling setTheme().
					setTheme: function (newMode) { this.setDarkMode(newMode); }
			};
		});

		// =========================================================================
		// 3. SPA Router — spaRouter()
		// =========================================================================

		Alpine.data('spaRouter', function () {
			return {
				loading: false,
				currentUrl: window.location.href,

				init: function () {
					var self = this;

					// Intercept clicks on SPA links.
					document.addEventListener('click', function (e) {
						var link = e.target.closest('[data-spa-link]');
						if (!link) return;
						e.preventDefault();
						var href = link.getAttribute('href') || link.dataset.spaLink;
						if (href && href !== self.currentUrl) {
							self.navigate(href);
						}
					});

					// Handle browser back/forward.
					window.addEventListener('popstate', function () {
						self._loadContent(window.location.href, false);
					});
				},

				/**
				 * Navigate to a URL by fetching its content and swapping the main area.
				 *
				 * @param {string} url Target URL.
				 */
				navigate: function (url) {
					history.pushState(null, '', url);
					this._loadContent(url, true);
				},

				/**
				 * Fetch a page and replace the main content area.
				 *
				 * @param {string}  url     URL to fetch.
				 * @param {boolean} pushed  Whether we already pushed state.
				 */
				_loadContent: function (url, pushed) {
					var self = this;
					self.loading = true;
					self.currentUrl = url;

					fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
					.then(function (res) { return res.text(); })
					.then(function (html) {
						var parser = new DOMParser();
						var doc = parser.parseFromString(html, 'text/html');

						// Try to extract the main content container.
						var newContent = doc.querySelector('#vms-main-content') ||
						doc.querySelector('main') ||
						doc.querySelector('.vms-content') ||
						doc.body;

						var target = document.querySelector('#vms-main-content') ||
						document.querySelector('main') ||
						document.querySelector('.vms-content');

						if (target && newContent) {
							target.innerHTML = newContent.innerHTML;
							// Re-initialize Alpine on the new content.
							Alpine.initTree(target);
						}

						// Update the page title.
						var titleEl = doc.querySelector('title');
						if (titleEl) {
							document.title = titleEl.textContent;
						}

						// Scroll to top.
						window.scrollTo({ top: 0, behavior: 'smooth' });
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message || I18N.error, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				}
			};
		});

		// =========================================================================
		// 4. Dashboard App — dashboardApp()
		// =========================================================================

		Alpine.data('dashboardApp', function () {
			return {
				loading: true,
				todaysVisits: 0,
				signedIn: 0,
				totalGuests: 0,
				monthlyVisits: 0,
				visits: [],
				chart: null,
				_refreshTimer: null,

				init: function () {
					this.fetchStats();
					var self = this;
					this._refreshTimer = setInterval(function () { self.fetchStats(); }, 30000);
				},

				destroy: function () {
					if (this._refreshTimer) clearInterval(this._refreshTimer);
					if (this.chart) { this.chart.destroy(); this.chart = null; }
				},

				/** Fetch today's visit statistics from the server. */
				fetchStats: function () {
					var self = this;
					self.loading = true;

					vmsFetch('vms_get_visits', {}, 'guest')
					.then(function (json) {
						var data = (json && json.data) ? json.data : {};
						var visits = data.visits || [];
						self.visits = visits;
						self.todaysVisits = visits.length;
						self.signedIn = visits.filter(function (v) {
							return v.sign_in_time && !v.sign_out_time;
						}).length;

						// totalGuests: unique guest IDs in today's visits
						var guestIds = {};
						visits.forEach(function (v) { guestIds[v.guest_id] = true; });
						self.totalGuests = Object.keys(guestIds).length;

						// monthlyVisits is not in today's endpoint — keep previous value
						// unless server sends it explicitly.
						if (data.monthlyVisits !== undefined) {
							self.monthlyVisits = data.monthlyVisits;
						}

						self._renderChart(visits);
					})
					.catch(function () {
						// Silently continue; dashboard still shows last-known data.
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Render a Chart.js bar chart of visit trends if Chart is available.
				 *
				 * @param {Array} visits Today's visits array.
				 */
				_renderChart: function (visits) {
					if (typeof Chart === 'undefined') return;

					var canvas = document.getElementById('vms-dashboard-chart');
					if (!canvas) return;

					// Build hourly distribution of sign-ins for today.
					var hours = {};
					for (var h = 6; h <= 22; h++) { hours[h] = 0; }
					(visits || []).forEach(function (v) {
						if (v.sign_in_time) {
							var hour = new Date(v.sign_in_time).getHours();
							if (hours[hour] !== undefined) {
								hours[hour]++;
							}
						}
					});

					var labels = Object.keys(hours).map(function (h) { return h + ':00'; });
					var counts = Object.values(hours);

					var ctx = canvas.getContext('2d');

					if (this.chart) {
						this.chart.data.labels = labels;
						this.chart.data.datasets[0].data = counts;
						this.chart.update();
						return;
					}

					var primary = (CONFIG.branding && CONFIG.branding.primary_color) ? CONFIG.branding.primary_color : '#0ea5e9';

					this.chart = new Chart(ctx, {
						type: 'bar',
						data: {
							labels: labels,
							datasets: [{
								label: 'Sign-ins',
								data: counts,
								backgroundColor: primary + '99',
								borderColor: primary,
								borderWidth: 1
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							scales: {
								y: { beginAtZero: true, ticks: { stepSize: 1 } }
							},
							plugins: {
								legend: { display: false }
							}
						}
					});
				}
			};
		});

		// =========================================================================
		// 5. Guest Manager — guestManager()
		// =========================================================================

		Alpine.data('guestManager', function () {
			return {
				guests: [],
				searchTerm: '',
				loading: false,
				selectedGuest: null,
				showModal: false,
				sortBy: 'first_name',
				sortDir: 'asc',
				currentPage: 1,
				perPage: 20,
				totalGuests: 0,
				editingId: null,
				editData: {},
				formData: {
					first_name: '', last_name: '', email: '', phone_number: '',
					id_number: '', receive_emails: 0, receive_messages: 0, notes: ''
				},

				init: function () {
					this.loadGuests();
				},

				/** Fetch the guest list from the server. */
				loadGuests: function () {
					var self = this;
					self.loading = true;

					vmsFetch('vms_search_guests', { term: self.searchTerm || '' }, 'guest')
					.then(function (json) {
						var data = (json && json.data) ? json.data : {};
						self.guests = data.results || [];
						self.totalGuests = self.guests.length;
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/** Search guests with debounced input. */
				searchGuests: debounce(function () {
					this.currentPage = 1;
					this.loadGuests();
				}, 350),

				/**
				 * Register a new guest.
				 *
				 * @param {Object} fd Form data payload.
				 */
				registerGuest: function (fd) {
					var self = this;
					var payload = fd || this.formData;
					self.loading = true;

					vmsFetch('vms_register_guest', payload, 'guest')
					.then(function (json) {
						var data = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(data.message || I18N.saved, 'success');
						self.showModal = false;
						self._resetForm();
						self.loadGuests();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Update an existing guest record.
				 *
				 * @param {number} id   Guest ID.
				 * @param {Object} data Updated fields.
				 */
				updateGuest: function (id, data) {
					var self = this;
					var payload = Object.assign({ guest_id: id }, data || this.editData);
					self.loading = true;

					vmsFetch('vms_update_guest', payload, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || I18N.saved, 'success');
						self.editingId = null;
						self.editData = {};
						self.loadGuests();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Delete a guest after confirmation.
				 *
				 * @param {number} id Guest ID.
				 */
				deleteGuest: function (id) {
					if (!confirm(I18N.confirm || 'Are you sure?')) return;
					var self = this;
					self.loading = true;

					vmsFetch('vms_delete_guest', { guest_id: id }, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Guest deleted.', 'success');
						self.selectedGuest = null;
						self.loadGuests();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Set a guest's status (active/suspended/banned).
				 *
				 * @param {number} id     Guest ID.
				 * @param {string} status New status.
				 */
				setStatus: function (id, status) {
					var self = this;
					self.loading = true;

					vmsFetch('vms_set_guest_status', { guest_id: id, status: status }, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Status updated.', 'success');
						self.loadGuests();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * View full guest details in the modal.
				 *
				 * @param {number} id Guest ID.
				 */
				viewGuest: function (id) {
					var self = this;
					self.loading = true;

					vmsFetch('vms_get_guest', { guest_id: id }, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						self.selectedGuest = d.guest || null;
						self.showModal = true;
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Start inline editing a guest row.
				 *
				 * @param {Object} guest Guest object.
				 */
				startEdit: function (guest) {
					this.editingId = guest.id;
					this.editData = {
						first_name: guest.first_name || '',
						last_name: guest.last_name || '',
						email: guest.email || '',
						phone_number: guest.phone_number || '',
						id_number: guest.id_number || '',
						notes: guest.notes || ''
					};
				},

				/** Cancel inline editing. */
				cancelEdit: function () {
					this.editingId = null;
					this.editData = {};
				},

				/**
				 * Sort the guest list by a given column.
				 *
				 * @param {string} column Column key.
				 */
				sort: function (column) {
					if (this.sortBy === column) {
						this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
					} else {
						this.sortBy = column;
						this.sortDir = 'asc';
					}
				},

				/** Get guests sorted and paginated for the current view. */
				get sortedGuests() {
					var self = this;
					var sorted = [].concat(this.guests);
					sorted.sort(function (a, b) {
						var aVal = (a[self.sortBy] || '').toString().toLowerCase();
						var bVal = (b[self.sortBy] || '').toString().toLowerCase();
						if (aVal < bVal) return self.sortDir === 'asc' ? -1 : 1;
						if (aVal > bVal) return self.sortDir === 'asc' ? 1 : -1;
						return 0;
					});
					var start = (this.currentPage - 1) * this.perPage;
					return sorted.slice(start, start + this.perPage);
				},

				/** Total number of pages for the current result set. */
				get totalPages() {
					return Math.max(1, Math.ceil(this.totalGuests / this.perPage));
				},

				/**
				 * Go to a specific page.
				 *
				 * @param {number} page Page number.
				 */
				goToPage: function (page) {
					if (page >= 1 && page <= this.totalPages) {
						this.currentPage = page;
					}
				},

				/** Reset the registration form to defaults. */
				_resetForm: function () {
					this.formData = {
						first_name: '', last_name: '', email: '', phone_number: '',
						id_number: '', receive_emails: 0, receive_messages: 0, notes: ''
					};
				}
			};
		});

		// =========================================================================
		// 6. Visit Manager — visitManager()
		// =========================================================================

		Alpine.data('visitManager', function () {
			return {
				visits: [],
				loading: false,
				filters: {
					status: '',
					date: ''
				},
				formData: {
					guest_id: '', visit_date: '', host_id: '', courtesy: ''
				},
				showRegisterForm: false,

				init: function () {
					this.loadVisits();
				},

				/** Fetch today's visits, optionally filtered by status. */
				loadVisits: function () {
					var self = this;
					self.loading = true;

					vmsFetch('vms_get_visits', { status: self.filters.status || '' }, 'guest')
					.then(function (json) {
						var data = (json && json.data) ? json.data : {};
						self.visits = data.visits || [];
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Register a new visit.
				 *
				 * @param {Object} data Visit form data.
				 */
				registerVisit: function (data) {
					var self = this;
					var payload = data || this.formData;
					self.loading = true;

					vmsFetch('vms_register_visit', payload, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Visit registered.', 'success');
						self.showRegisterForm = false;
						self.loadVisits();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Cancel a visit.
				 *
				 * @param {number} id Visit ID.
				 */
				cancelVisit: function (id) {
					if (!confirm(I18N.confirm || 'Are you sure?')) return;
					var self = this;
					self.loading = true;

					vmsFetch('vms_cancel_visit', { visit_id: id }, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Visit cancelled.', 'success');
						self.loadVisits();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Sign a guest in for a visit (requires ID verification).
				 *
				 * @param {number} id       Visit ID.
				 * @param {string} idNumber Guest ID number for verification.
				 */
				signIn: function (id, idNumber) {
					var self = this;
					self.loading = true;

					vmsFetch('vms_signin_guest', { visit_id: id, id_number: idNumber || '' }, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Guest signed in.', 'success');
						self.loadVisits();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Sign a guest out for a visit.
				 *
				 * @param {number} id Visit ID.
				 */
				signOut: function (id) {
					var self = this;
					self.loading = true;

					vmsFetch('vms_signout_guest', { visit_id: id }, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Guest signed out.', 'success');
						self.loadVisits();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/** Helper to get a human-readable visit status label. */
				statusLabel: function (status) {
					var labels = {
						approved: 'Approved',
			  unapproved: 'Pending Approval',
			  cancelled: 'Cancelled',
			  completed: 'Completed'
					};
					return labels[status] || status || 'Unknown';
				},

				/** Helper to get a CSS class for a visit status badge. */
				statusClass: function (status) {
					var classes = {
						approved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
			  unapproved: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
			  cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
			  completed: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
					};
					return classes[status] || 'bg-gray-100 text-gray-800';
				}
			};
		});

		// =========================================================================
		// 7. Sign-In Desk — signInDesk()
		// =========================================================================

		Alpine.data('signInDesk', function () {
			return {
				searchTerm: '',
				searchResults: [],
				todaysVisits: [],
				loading: false,
				searching: false,
				idVerification: '',
				selectedVisit: null,
				_refreshTimer: null,

				init: function () {
					this.loadTodaysVisits();
					var self = this;
					this._refreshTimer = setInterval(function () {
						self.loadTodaysVisits();
					}, 15000);
				},

				destroy: function () {
					if (this._refreshTimer) clearInterval(this._refreshTimer);
				},

				/** Load all of today's approved or signed-in visits. */
				loadTodaysVisits: function () {
					var self = this;

					vmsFetch('vms_get_visits', { status: '' }, 'guest')
					.then(function (json) {
						var data = (json && json.data) ? json.data : {};
						self.todaysVisits = (data.visits || []).filter(function (v) {
							return v.status !== 'cancelled';
						});
					})
					.catch(function () {
						// Silent refresh failure.
					});
				},

				/** Search for guests by name, phone, or ID number. */
				searchGuest: debounce(function () {
					var self = this;
					if (!self.searchTerm || self.searchTerm.length < 2) {
						self.searchResults = [];
						return;
					}
					self.searching = true;

					vmsFetch('vms_search_guests', { term: self.searchTerm }, 'guest')
					.then(function (json) {
						var data = (json && json.data) ? json.data : {};
						self.searchResults = data.results || [];
					})
					.catch(function () {
						self.searchResults = [];
					})
					.finally(function () {
						self.searching = false;
					});
				}, 300),

				/**
				 * Select a visit for sign-in, prompting for ID verification.
				 *
				 * @param {Object} visit Visit object.
				 */
				selectForSignIn: function (visit) {
					this.selectedVisit = visit;
					this.idVerification = '';
				},

				/** Confirm sign-in with ID verification for the selected visit. */
				confirmSignIn: function () {
					if (!this.selectedVisit) return;
					var self = this;
					self.loading = true;

					vmsFetch('vms_signin_guest', {
						visit_id: self.selectedVisit.id,
						id_number: self.idVerification
					}, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Guest signed in.', 'success');
						self.selectedVisit = null;
						self.idVerification = '';
						self.loadTodaysVisits();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Sign a guest out.
				 *
				 * @param {number} visitId Visit ID.
				 */
				signOutGuest: function (visitId) {
					var self = this;
					self.loading = true;

					vmsFetch('vms_signout_guest', { visit_id: visitId }, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Guest signed out.', 'success');
						self.loadTodaysVisits();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/** Filter today's visits to only those that are approved and not yet signed in. */
				get pendingVisits() {
					return this.todaysVisits.filter(function (v) {
						return v.status === 'approved' && !v.sign_in_time;
					});
				},

				/** Filter today's visits to those currently signed in. */
				get currentlySignedIn() {
					return this.todaysVisits.filter(function (v) {
						return v.sign_in_time && !v.sign_out_time;
					});
				},

				/** Filter today's visits to those that have completed (signed out). */
				get completedVisits() {
					return this.todaysVisits.filter(function (v) {
						return v.sign_out_time;
					});
				}
			};
		});

		// =========================================================================
		// 8. Supplier Manager — supplierManager()
		// =========================================================================

		Alpine.data('supplierManager', function () {
			return {
				suppliers: [],
				searchTerm: '',
				loading: false,
				selectedSupplier: null,
				showModal: false,
				sortBy: 'company_name',
				sortDir: 'asc',
				currentPage: 1,
				perPage: 20,
				formData: {
					company_name: '', contact_person: '', phone_number: '',
					email: '', id_number: '', notes: ''
				},

				init: function () {
					this.loadSuppliers();
				},

				/** Fetch supplier list from the server. */
				loadSuppliers: function () {
					var self = this;
					self.loading = true;

					vmsFetch('vms_search_suppliers', { term: self.searchTerm || '' }, 'guest')
					.then(function (json) {
						var data = (json && json.data) ? json.data : {};
						self.suppliers = data.results || [];
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/** Search suppliers with debounced input. */
				searchSuppliers: debounce(function () {
					this.currentPage = 1;
					this.loadSuppliers();
				}, 350),

				/**
				 * Register a new supplier.
				 *
				 * @param {Object} fd Form data payload.
				 */
				registerSupplier: function (fd) {
					var self = this;
					var payload = fd || this.formData;
					self.loading = true;

					vmsFetch('vms_register_supplier', payload, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || I18N.saved, 'success');
						self.showModal = false;
						self._resetForm();
						self.loadSuppliers();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Update an existing supplier.
				 *
				 * @param {number} id   Supplier ID.
				 * @param {Object} data Updated fields.
				 */
				updateSupplier: function (id, data) {
					var self = this;
					self.loading = true;

					vmsFetch('vms_update_supplier', Object.assign({ supplier_id: id }, data), 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || I18N.saved, 'success');
						self.loadSuppliers();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Delete a supplier after confirmation.
				 *
				 * @param {number} id Supplier ID.
				 */
				deleteSupplier: function (id) {
					if (!confirm(I18N.confirm || 'Are you sure?')) return;
					var self = this;
					self.loading = true;

					vmsFetch('vms_delete_supplier', { supplier_id: id }, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Supplier deleted.', 'success');
						self.selectedSupplier = null;
						self.loadSuppliers();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Register a visit for a supplier.
				 *
				 * @param {Object} data Visit data including supplier_id and visit_date.
				 */
				registerVisit: function (data) {
					var self = this;
					self.loading = true;

					vmsFetch('vms_register_supplier_visit', data, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Visit registered.', 'success');
						self.loadSuppliers();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Sign in a supplier.
				 *
				 * @param {number} visitId    Visit ID.
				 * @param {string} idNumber   ID number for verification.
				 */
				signIn: function (visitId, idNumber) {
					var self = this;
					self.loading = true;

					vmsFetch('vms_signin_supplier', { visit_id: visitId, id_number: idNumber || '' }, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Supplier signed in.', 'success');
						self.loadSuppliers();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Sign out a supplier.
				 *
				 * @param {number} visitId Visit ID.
				 */
				signOut: function (visitId) {
					var self = this;
					self.loading = true;

					vmsFetch('vms_signout_supplier', { visit_id: visitId }, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Supplier signed out.', 'success');
						self.loadSuppliers();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/** Paginated and sorted supplier list. */
				get sortedSuppliers() {
					var self = this;
					var sorted = [].concat(this.suppliers);
					sorted.sort(function (a, b) {
						var aVal = (a[self.sortBy] || '').toString().toLowerCase();
						var bVal = (b[self.sortBy] || '').toString().toLowerCase();
						if (aVal < bVal) return self.sortDir === 'asc' ? -1 : 1;
						if (aVal > bVal) return self.sortDir === 'asc' ? 1 : -1;
						return 0;
					});
					var start = (this.currentPage - 1) * this.perPage;
					return sorted.slice(start, start + this.perPage);
				},

				get totalPages() {
					return Math.max(1, Math.ceil(this.suppliers.length / this.perPage));
				},

				/** Reset the registration form to defaults. */
				_resetForm: function () {
					this.formData = {
						company_name: '', contact_person: '', phone_number: '',
						email: '', id_number: '', notes: ''
					};
				}
			};
		});

		// =========================================================================
		// 9. Accommodation Manager — accommodationManager()
		// =========================================================================

		Alpine.data('accommodationManager', function () {
			return {
				bookings: [],
				rooms: [],
				loading: false,
				showModal: false,
				selectedBooking: null,
				formData: {
					guest_id: '', room_id: '', check_in_date: '', check_out_date: '', notes: ''
				},

				init: function () {
					this.loadBookings();
					this.loadRooms();
				},

				/** Fetch current accommodation bookings. */
				loadBookings: function () {
					var self = this;
					self.loading = true;

					vmsFetch('vms_get_accommodation_bookings', {}, 'guest')
					.then(function (json) {
						var data = (json && json.data) ? json.data : {};
						self.bookings = data.bookings || [];
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/** Fetch available rooms. */
				loadRooms: function () {
					var self = this;

					vmsFetch('vms_get_rooms', {}, 'guest')
					.then(function (json) {
						var data = (json && json.data) ? json.data : {};
						self.rooms = data.rooms || [];
					})
					.catch(function () {
						// Non-critical; rooms may not be configured.
					});
				},

				/**
				 * Check a guest into accommodation.
				 *
				 * @param {Object} data Booking data.
				 */
				checkIn: function (data) {
					var self = this;
					var payload = data || this.formData;
					self.loading = true;

					vmsFetch('vms_accommodation_checkin', payload, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Checked in.', 'success');
						self.showModal = false;
						self.loadBookings();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Check a guest out of accommodation.
				 *
				 * @param {number} bookingId Booking ID.
				 */
				checkOut: function (bookingId) {
					var self = this;
					self.loading = true;

					vmsFetch('vms_accommodation_checkout', { booking_id: bookingId }, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Checked out.', 'success');
						self.loadBookings();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Assign a room to a booking.
				 *
				 * @param {number} bookingId Booking ID.
				 * @param {number} roomId    Room ID.
				 */
				assignRoom: function (bookingId, roomId) {
					var self = this;
					self.loading = true;

					vmsFetch('vms_assign_room', { booking_id: bookingId, room_id: roomId }, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Room assigned.', 'success');
						self.loadBookings();
						self.loadRooms();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/** Get bookings with guests currently checked in. */
				get activeBookings() {
					return this.bookings.filter(function (b) {
						return b.status === 'checked_in';
					});
				},

				/** Get available rooms that are not currently occupied. */
				get availableRooms() {
					var occupiedIds = {};
					this.activeBookings.forEach(function (b) {
						if (b.room_id) occupiedIds[b.room_id] = true;
					});
						return this.rooms.filter(function (r) {
							return !occupiedIds[r.id];
						});
				}
			};
		});

		// =========================================================================
		// 10. Reciprocation Manager — reciprocationManager()
		// =========================================================================

		Alpine.data('reciprocationManager', function () {
			return {
				clubs: [],
				members: [],
				loading: false,
				showClubModal: false,
				showMemberModal: false,
				selectedClub: null,
				clubForm: {
					club_name: '', contact_person: '', phone: '', email: '', address: '', notes: ''
				},
				memberForm: {
					club_id: '', member_name: '', membership_number: '', phone: '', email: ''
				},

				init: function () {
					this.loadClubs();
				},

				/** Fetch reciprocating clubs. */
				loadClubs: function () {
					var self = this;
					self.loading = true;

					vmsFetch('vms_get_reciprocating_clubs', {}, 'guest')
					.then(function (json) {
						var data = (json && json.data) ? json.data : {};
						self.clubs = data.clubs || [];
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Load members for a specific club.
				 *
				 * @param {number} clubId Club ID.
				 */
				loadMembers: function (clubId) {
					var self = this;
					self.loading = true;
					self.selectedClub = clubId;

					vmsFetch('vms_get_club_members', { club_id: clubId }, 'guest')
					.then(function (json) {
						var data = (json && json.data) ? json.data : {};
						self.members = data.members || [];
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Create or update a reciprocating club.
				 *
				 * @param {Object} data Club data.
				 */
				saveClub: function (data) {
					var self = this;
					var payload = data || this.clubForm;
					var action = payload.id ? 'vms_update_reciprocating_club' : 'vms_register_reciprocating_club';
					self.loading = true;

					vmsFetch(action, payload, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || I18N.saved, 'success');
						self.showClubModal = false;
						self.clubForm = {
							club_name: '', contact_person: '', phone: '', email: '', address: '', notes: ''
						};
						self.loadClubs();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Delete a reciprocating club.
				 *
				 * @param {number} clubId Club ID.
				 */
				deleteClub: function (clubId) {
					if (!confirm(I18N.confirm || 'Are you sure?')) return;
					var self = this;
					self.loading = true;

					vmsFetch('vms_delete_reciprocating_club', { club_id: clubId }, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Club deleted.', 'success');
						self.loadClubs();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Add or update a member of a reciprocating club.
				 *
				 * @param {Object} data Member data.
				 */
				saveMember: function (data) {
					var self = this;
					var payload = data || this.memberForm;
					var action = payload.id ? 'vms_update_club_member' : 'vms_register_club_member';
					self.loading = true;

					vmsFetch(action, payload, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || I18N.saved, 'success');
						self.showMemberModal = false;
						self.memberForm = {
							club_id: self.selectedClub || '', member_name: '',
							membership_number: '', phone: '', email: ''
						};
						if (self.selectedClub) self.loadMembers(self.selectedClub);
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Delete a club member.
				 *
				 * @param {number} memberId Member ID.
				 */
				deleteMember: function (memberId) {
					if (!confirm(I18N.confirm || 'Are you sure?')) return;
					var self = this;
					self.loading = true;

					vmsFetch('vms_delete_club_member', { member_id: memberId }, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Member removed.', 'success');
						if (self.selectedClub) self.loadMembers(self.selectedClub);
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Register a visit for a reciprocating club member.
				 *
				 * @param {Object} data Visit data including member_id and visit_date.
				 */
				registerVisit: function (data) {
					var self = this;
					self.loading = true;

					vmsFetch('vms_register_reciprocating_visit', data, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Visit registered.', 'success');
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				}
			};
		});

		// =========================================================================
		// 11. Reports App — reportsApp()
		// =========================================================================

		Alpine.data('reportsApp', function () {
			return {
				loading: false,
				dateFrom: '',
				dateTo: '',
				reportData: null,
				activeTab: 'visits',
				charts: {},
				_flatpickrInstances: [],

				init: function () {
					// Set default date range: last 30 days.
					var today = new Date();
					var thirtyAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
					this.dateTo = today.toISOString().slice(0, 10);
					this.dateFrom = thirtyAgo.toISOString().slice(0, 10);

					this._initDatePickers();
					this.loadReport();
				},

				destroy: function () {
					// Clean up flatpickr instances.
					this._flatpickrInstances.forEach(function (fp) {
						if (fp && fp.destroy) fp.destroy();
					});
						this._flatpickrInstances = [];

						// Clean up Chart.js instances.
						Object.values(this.charts).forEach(function (c) {
							if (c && c.destroy) c.destroy();
						});
							this.charts = {};
				},

				/** Initialize flatpickr on date inputs if available. */
				_initDatePickers: function () {
					if (typeof flatpickr === 'undefined') return;
					var self = this;

					var fromEl = document.getElementById('vms-report-date-from');
					var toEl   = document.getElementById('vms-report-date-to');

					if (fromEl) {
						var fpFrom = flatpickr(fromEl, {
							dateFormat: 'Y-m-d',
							defaultDate: self.dateFrom,
								onChange: function (selectedDates, dateStr) { self.dateFrom = dateStr; }
						});
						this._flatpickrInstances.push(fpFrom);
					}

					if (toEl) {
						var fpTo = flatpickr(toEl, {
							dateFormat: 'Y-m-d',
							defaultDate: self.dateTo,
								onChange: function (selectedDates, dateStr) { self.dateTo = dateStr; }
						});
						this._flatpickrInstances.push(fpTo);
					}
				},

				/** Fetch report data from the server for the selected date range. */
				loadReport: function () {
					var self = this;
					self.loading = true;

					vmsFetch('vms_get_report_data', {
						date_from: self.dateFrom,
						date_to: self.dateTo
					}, 'audit')
					.then(function (json) {
						var data = (json && json.data) ? json.data : {};
						self.reportData = data;
						self._renderCharts(data);
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Render Chart.js charts from report data.
				 *
				 * @param {Object} data Report data from server.
				 */
				_renderCharts: function (data) {
					if (typeof Chart === 'undefined' || !data) return;

					var primary   = (CONFIG.branding && CONFIG.branding.primary_color) || '#0ea5e9';
					var secondary = (CONFIG.branding && CONFIG.branding.secondary_color) || '#8b5cf6';

					// --- Visits by Day Chart ---
					this._renderLineChart(
						'vms-chart-visits-by-day',
						'visitsByDay',
						data.visits_by_day || {},
						'Daily Visits',
						primary
					);

					// --- Guests by Status Chart ---
					this._renderDoughnutChart(
						'vms-chart-guests-status',
						'guestsByStatus',
						data.guests_by_status || {},
						[primary, secondary, '#ef4444', '#f59e0b']
					);

					// --- Monthly Trends Chart ---
					this._renderBarChart(
						'vms-chart-monthly-trends',
						'monthlyTrends',
						data.monthly_trends || {},
						'Monthly Visits',
						primary
					);
				},

				/**
				 * Render a line chart for daily visit data.
				 *
				 * @param {string} canvasId  DOM ID of the canvas element.
				 * @param {string} chartKey  Key to store the chart instance.
				 * @param {Object} dataset   Label-value pairs (date -> count).
				 * @param {string} label     Dataset label.
				 * @param {string} color     Line/fill color.
				 */
				_renderLineChart: function (canvasId, chartKey, dataset, label, color) {
					var canvas = document.getElementById(canvasId);
					if (!canvas) return;
					var ctx = canvas.getContext('2d');

					var labels = Object.keys(dataset);
					var values = Object.values(dataset);

					if (this.charts[chartKey]) {
						this.charts[chartKey].data.labels = labels;
						this.charts[chartKey].data.datasets[0].data = values;
						this.charts[chartKey].update();
						return;
					}

					this.charts[chartKey] = new Chart(ctx, {
						type: 'line',
						data: {
							labels: labels,
							datasets: [{
								label: label,
								data: values,
								borderColor: color,
								backgroundColor: color + '33',
								fill: true,
								tension: 0.3
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
							plugins: { legend: { display: true, position: 'top' } }
						}
					});
				},

				/**
				 * Render a doughnut chart for guest status distribution.
				 *
				 * @param {string}   canvasId DOM ID of the canvas.
				 * @param {string}   chartKey Key for chart storage.
				 * @param {Object}   dataset  Label-value pairs.
				 * @param {string[]} colors   Color palette.
				 */
				_renderDoughnutChart: function (canvasId, chartKey, dataset, colors) {
					var canvas = document.getElementById(canvasId);
					if (!canvas) return;
					var ctx = canvas.getContext('2d');

					var labels = Object.keys(dataset);
					var values = Object.values(dataset);

					if (this.charts[chartKey]) {
						this.charts[chartKey].data.labels = labels;
						this.charts[chartKey].data.datasets[0].data = values;
						this.charts[chartKey].update();
						return;
					}

					this.charts[chartKey] = new Chart(ctx, {
						type: 'doughnut',
						data: {
							labels: labels,
							datasets: [{
								data: values,
								backgroundColor: colors.slice(0, labels.length)
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							plugins: { legend: { display: true, position: 'bottom' } }
						}
					});
				},

				/**
				 * Render a bar chart for monthly trend data.
				 *
				 * @param {string} canvasId DOM ID.
				 * @param {string} chartKey Storage key.
				 * @param {Object} dataset  Label-value pairs.
				 * @param {string} label    Dataset label.
				 * @param {string} color    Bar color.
				 */
				_renderBarChart: function (canvasId, chartKey, dataset, label, color) {
					var canvas = document.getElementById(canvasId);
					if (!canvas) return;
					var ctx = canvas.getContext('2d');

					var labels = Object.keys(dataset);
					var values = Object.values(dataset);

					if (this.charts[chartKey]) {
						this.charts[chartKey].data.labels = labels;
						this.charts[chartKey].data.datasets[0].data = values;
						this.charts[chartKey].update();
						return;
					}

					this.charts[chartKey] = new Chart(ctx, {
						type: 'bar',
						data: {
							labels: labels,
							datasets: [{
								label: label,
								data: values,
								backgroundColor: color + '99',
								borderColor: color,
								borderWidth: 1
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
							plugins: { legend: { display: false } }
						}
					});
				},

				/**
				 * Export the current report data as a CSV file.
				 */
				exportCsv: function () {
					if (!this.reportData) {
						Alpine.store('toast').show('No data to export.', 'warning');
						return;
					}

					var rows = this.reportData.rows || this.reportData.visits || [];
					if (!rows.length) {
						Alpine.store('toast').show(I18N.noResults || 'No results found.', 'warning');
						return;
					}

					// Build CSV from the first row's keys as headers.
					var headers = Object.keys(rows[0]);
					var csvLines = [headers.join(',')];

					rows.forEach(function (row) {
						var line = headers.map(function (h) {
							var val = row[h];
							if (val === null || val === undefined) return '';
							var str = String(val).replace(/"/g, '""');
							return '"' + str + '"';
						});
						csvLines.push(line.join(','));
					});

					var csv = csvLines.join('\n');
					var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
					var url  = URL.createObjectURL(blob);
					var a    = document.createElement('a');
					a.href     = url;
					a.download = 'vms-report-' + this.dateFrom + '_' + this.dateTo + '.csv';
					document.body.appendChild(a);
					a.click();
					document.body.removeChild(a);
					URL.revokeObjectURL(url);
				}
				 };
			});

		// =========================================================================
		// 12. Member Profile App — memberProfileApp()
		// =========================================================================

		Alpine.data('memberProfileApp', function () {
			return {
				loading: false,
				saving: false,
				profile: {
					display_name: (CONFIG.currentUser && CONFIG.currentUser.displayName) || '',
					phone: '',
					email_preferences: 1
				},
				passwordForm: {
					current_password: '',
					new_password: '',
					confirm_password: ''
				},
				guestHistory: [],
				showPasswordForm: false,
				passwordError: '',

				init: function () {
					this.loadProfile();
					this.loadGuestHistory();
				},

				/** Load the current user's profile details. */
				loadProfile: function () {
					var self = this;
					self.loading = true;

					vmsFetch('vms_get_member_profile', {}, 'guest')
					.then(function (json) {
						var data = (json && json.data) ? json.data : {};
						if (data.profile) {
							self.profile = Object.assign(self.profile, data.profile);
						}
					})
					.catch(function () {
						// Use defaults if profile endpoint is unavailable.
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/** Save profile changes. */
				saveProfile: function () {
					var self = this;
					self.saving = true;

					vmsFetch('vms_update_member_profile', self.profile, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || I18N.saved, 'success');
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.saving = false;
					});
				},

				/** Change the user's password via WordPress AJAX. */
				changePassword: function () {
					var self = this;
					self.passwordError = '';

					if (!self.passwordForm.current_password) {
						self.passwordError = 'Current password is required.';
						return;
					}
					if (!self.passwordForm.new_password) {
						self.passwordError = 'New password is required.';
						return;
					}
					if (self.passwordForm.new_password !== self.passwordForm.confirm_password) {
						self.passwordError = 'New passwords do not match.';
						return;
					}
					if (self.passwordForm.new_password.length < 8) {
						self.passwordError = 'Password must be at least 8 characters.';
						return;
					}

					self.saving = true;

					vmsFetch('vms_change_password', {
						current_password: self.passwordForm.current_password,
						new_password: self.passwordForm.new_password
					}, 'guest')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Password changed.', 'success');
						self.passwordForm = { current_password: '', new_password: '', confirm_password: '' };
						self.showPasswordForm = false;
					})
					.catch(function (err) {
						self.passwordError = err.message;
					})
					.finally(function () {
						self.saving = false;
					});
				},

				/** Load the user's own guest registration history. */
				loadGuestHistory: function () {
					var self = this;

					vmsFetch('vms_get_member_guest_history', {}, 'guest')
					.then(function (json) {
						var data = (json && json.data) ? json.data : {};
						self.guestHistory = data.history || [];
					})
					.catch(function () {
						// Non-critical.
					});
				}
			};
		});

		// =========================================================================
		// 13. Login App — loginApp()
		// =========================================================================

		Alpine.data('loginApp', function () {
			return {
				view: 'login', // 'login' | 'forgot' | 'reset'
				loading: false,
				error: '',
				success: '',
				showPassword: false,

				loginForm: {
					username: '',
					password: ''
				},
				forgotForm: {
					email: ''
				},
				resetForm: {
					password: '',
					confirmPassword: ''
				},

				// For password reset links: ?key=...&login=...
				resetKey: '',
				resetLogin: '',

				init: function () {
					// Check if we are on a password reset URL.
					var params = new URLSearchParams(window.location.search);
					var key   = params.get('key');
					var login = params.get('login');

					if (key && login) {
						this.view = 'reset';
						this.resetKey = key;
						this.resetLogin = login;
					}
				},

				/** Submit the login form via AJAX. */
				submitLogin: function () {
					var self = this;
					self.error = '';
					self.success = '';

					if (!self.loginForm.username || !self.loginForm.password) {
						self.error = 'Please enter your username and password.';
						return;
					}

					self.loading = true;

					var fd = new FormData();
					fd.append('action', 'vms_ajax_login');
					fd.append('username', self.loginForm.username);
					fd.append('password', self.loginForm.password);
					fd.append('nonce', NONCES.guest || '');

					fetch(AJAX, { method: 'POST', credentials: 'same-origin', body: fd })
					.then(function (res) { return res.json(); })
					.then(function (json) {
						if (json && json.success) {
							self.success = 'Login successful. Redirecting...';
							var redirect = (json.data && json.data.redirect) ? json.data.redirect : (CONFIG.dashboardUrl || '/');
							setTimeout(function () {
								window.location.href = redirect;
							}, 500);
						} else {
							self.error = (json.data && json.data.message) ? json.data.message : 'Invalid credentials.';
						}
					})
					.catch(function (err) {
						self.error = err.message || I18N.error;
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/** Send a password reset email. */
				submitForgotPassword: function () {
					var self = this;
					self.error = '';
					self.success = '';

					if (!self.forgotForm.email) {
						self.error = 'Please enter your email address.';
						return;
					}

					self.loading = true;

					var fd = new FormData();
					fd.append('action', 'vms_request_password_reset');
					fd.append('email', self.forgotForm.email);

					fetch(AJAX, { method: 'POST', credentials: 'same-origin', body: fd })
					.then(function (res) { return res.json(); })
					.then(function (json) {
						if (json && json.success) {
							self.success = (json.data && json.data.message) || 'If that email exists, a reset link has been sent.';
							self.forgotForm.email = '';
						} else {
							// Always show a generic message to prevent user enumeration.
							self.success = 'If that email exists, a reset link has been sent.';
						}
					})
					.catch(function () {
						self.success = 'If that email exists, a reset link has been sent.';
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/** Submit the password reset form with key and login from URL. */
				submitResetPassword: function () {
					var self = this;
					self.error = '';
					self.success = '';

					if (!self.resetForm.password) {
						self.error = 'Please enter a new password.';
						return;
					}
					if (self.resetForm.password.length < 8) {
						self.error = 'Password must be at least 8 characters.';
						return;
					}
					if (self.resetForm.password !== self.resetForm.confirmPassword) {
						self.error = 'Passwords do not match.';
						return;
					}

					self.loading = true;

					var fd = new FormData();
					fd.append('action', 'vms_reset_password');
					fd.append('key', self.resetKey);
					fd.append('login', self.resetLogin);
					fd.append('password', self.resetForm.password);

					fetch(AJAX, { method: 'POST', credentials: 'same-origin', body: fd })
					.then(function (res) { return res.json(); })
					.then(function (json) {
						if (json && json.success) {
							self.success = (json.data && json.data.message) || 'Password has been reset. You can now log in.';
							self.resetForm = { password: '', confirmPassword: '' };
							// Switch to login view after a short delay.
							setTimeout(function () { self.view = 'login'; }, 2000);
						} else {
							self.error = (json.data && json.data.message) || 'Reset failed. The link may have expired.';
						}
					})
					.catch(function (err) {
						self.error = err.message || I18N.error;
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/** Toggle password visibility. */
				togglePassword: function () {
					this.showPassword = !this.showPassword;
				},

				/**
				 * Switch the current view (login, forgot, reset).
				 *
				 * @param {string} newView Target view.
				 */
				switchView: function (newView) {
					this.view = newView;
					this.error = '';
					this.success = '';
				}
			};
		});

		// =========================================================================
		// 14. Module Builder — moduleBuilder()
		// =========================================================================

		Alpine.data('moduleBuilder', function () {
			return {
				loading: false,
				modules: [],
				selectedModule: null,
				showModuleModal: false,
				showFieldModal: false,
				showPreview: false,
				moduleForm: {
					name: '',
					slug: '',
					description: ''
				},
				fieldForm: {
					label: '',
					name: '',
					type: 'text',
					required: false,
					options: '',      // For select type: comma-separated values.
					placeholder: '',
					default_value: ''
				},
				fieldTypes: [
					{ value: 'text', label: 'Text' },
					{ value: 'number', label: 'Number' },
					{ value: 'date', label: 'Date' },
					{ value: 'select', label: 'Dropdown Select' },
					{ value: 'checkbox', label: 'Checkbox' },
					{ value: 'textarea', label: 'Text Area' }
				],
				previewData: {},

				init: function () {
					this.loadModules();
				},

				/** Load all custom module configurations from the server. */
				loadModules: function () {
					var self = this;
					self.loading = true;

					vmsFetch('vms_get_custom_modules', {}, 'settings')
					.then(function (json) {
						var data = (json && json.data) ? json.data : {};
						self.modules = data.modules || [];
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/** Save a module configuration (create or update). */
				saveModule: function () {
					var self = this;

					if (!self.moduleForm.name) {
						Alpine.store('toast').show('Module name is required.', 'warning');
						return;
					}

					// Auto-generate slug from name if not provided.
					if (!self.moduleForm.slug) {
						self.moduleForm.slug = self.moduleForm.name
						.toLowerCase()
						.replace(/[^a-z0-9]+/g, '_')
						.replace(/^_|_$/g, '');
					}

					self.loading = true;

					var payload = Object.assign({}, self.moduleForm);
					if (self.selectedModule && self.selectedModule.id) {
						payload.module_id = self.selectedModule.id;
					}
					// Include existing fields if editing.
					if (self.selectedModule && self.selectedModule.fields) {
						payload.fields = JSON.stringify(self.selectedModule.fields);
					}

					vmsFetch('vms_save_custom_module', payload, 'settings')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || I18N.saved, 'success');
						self.showModuleModal = false;
						self._resetModuleForm();
						self.loadModules();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Delete a custom module after confirmation.
				 *
				 * @param {number} moduleId Module ID.
				 */
				deleteModule: function (moduleId) {
					if (!confirm(I18N.confirm || 'Are you sure?')) return;
					var self = this;
					self.loading = true;

					vmsFetch('vms_delete_custom_module', { module_id: moduleId }, 'settings')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || 'Module deleted.', 'success');
						self.selectedModule = null;
						self.loadModules();
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/**
				 * Select a module for editing.
				 *
				 * @param {Object} mod Module object.
				 */
				editModule: function (mod) {
					this.selectedModule = JSON.parse(JSON.stringify(mod)); // deep clone
					this.moduleForm = {
						name: mod.name || '',
						slug: mod.slug || '',
						description: mod.description || ''
					};
					this.showModuleModal = true;
				},

				/** Add a field to the currently selected module. */
				addField: function () {
					if (!this.selectedModule) return;

					if (!this.fieldForm.label) {
						Alpine.store('toast').show('Field label is required.', 'warning');
						return;
					}

					// Auto-generate field name from label if not provided.
					if (!this.fieldForm.name) {
						this.fieldForm.name = this.fieldForm.label
						.toLowerCase()
						.replace(/[^a-z0-9]+/g, '_')
						.replace(/^_|_$/g, '');
					}

					if (!this.selectedModule.fields) {
						this.selectedModule.fields = [];
					}

					// Parse options for select type.
					var field = Object.assign({}, this.fieldForm);
					if (field.type === 'select' && field.options) {
						field.options = field.options.split(',').map(function (o) { return o.trim(); }).filter(Boolean);
					} else if (field.type !== 'select') {
						delete field.options;
					}

					this.selectedModule.fields.push(field);
					this.showFieldModal = false;
					this._resetFieldForm();
				},

				/**
				 * Remove a field from the selected module by index.
				 *
				 * @param {number} index Field index.
				 */
				removeField: function (index) {
					if (!this.selectedModule || !this.selectedModule.fields) return;
					this.selectedModule.fields.splice(index, 1);
				},

				/**
				 * Move a field up or down in the list.
				 *
				 * @param {number} index     Current field index.
				 * @param {number} direction -1 for up, +1 for down.
				 */
				moveField: function (index, direction) {
					if (!this.selectedModule || !this.selectedModule.fields) return;
					var fields = this.selectedModule.fields;
					var newIndex = index + direction;
					if (newIndex < 0 || newIndex >= fields.length) return;
					var temp = fields[index];
					fields[index] = fields[newIndex];
					fields[newIndex] = temp;
					// Trigger Alpine reactivity.
					this.selectedModule.fields = [].concat(fields);
				},

				/** Open the form preview for the selected module. */
				openPreview: function () {
					if (!this.selectedModule || !this.selectedModule.fields) return;
					this.previewData = {};
					var self = this;
					this.selectedModule.fields.forEach(function (f) {
						self.previewData[f.name] = f.default_value || '';
					});
					this.showPreview = true;
				},

				/**
				 * Submit the preview form data to the server.
				 */
				submitPreviewForm: function () {
					if (!this.selectedModule) return;
					var self = this;
					self.loading = true;

					var payload = Object.assign({
						module_slug: self.selectedModule.slug
					}, self.previewData);

					vmsFetch('vms_submit_custom_module_entry', payload, 'settings')
					.then(function (json) {
						var d = (json && json.data) ? json.data : {};
						Alpine.store('toast').show(d.message || I18N.saved, 'success');
						self.showPreview = false;
					})
					.catch(function (err) {
						Alpine.store('toast').show(err.message, 'error');
					})
					.finally(function () {
						self.loading = false;
					});
				},

				/** Reset module form to defaults. */
				_resetModuleForm: function () {
					this.moduleForm = { name: '', slug: '', description: '' };
					this.selectedModule = null;
				},

				/** Reset field form to defaults. */
				_resetFieldForm: function () {
					this.fieldForm = {
						label: '', name: '', type: 'text', required: false,
						options: '', placeholder: '', default_value: ''
					};
				}
			};
		});

		}); // end alpine:init

	})();

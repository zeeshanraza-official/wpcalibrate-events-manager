/**
 * WPCalibrate Events Manager - Frontend Calendar Script
 * Asynchronous calendar navigation, accessible focus states, and view switching.
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		initCalendars();
	});

	function initCalendars() {
		const calendarApps = document.querySelectorAll('.wpcal-main-calendar-app');
		calendarApps.forEach(function (app) {
			bindCalendarEvents(app);
		});
	}

	function bindCalendarEvents(app) {
		const stage = app.querySelector('.wpcal-calendar-view-stage');
		if (!stage) return;

		// Delegate navigation and view buttons
		app.addEventListener('click', function (e) {
			// 1. Navigation Buttons (Prev, Next, Today)
			const navBtn = e.target.closest('.wpcal-btn-prev, .wpcal-btn-next, .wpcal-btn-today');
			if (navBtn) {
				e.preventDefault();
				const currentView = app.getAttribute('data-current-view') || 'month';
				const params = {
					view: currentView
				};

				if (navBtn.dataset.year) params.year = navBtn.dataset.year;
				if (navBtn.dataset.month) params.month = navBtn.dataset.month;
				if (navBtn.dataset.date) params.date = navBtn.dataset.date;
				if (navBtn.dataset.page) params.page = navBtn.dataset.page;

				loadCalendarView(app, stage, params);
				return;
			}

			// 2. View Switcher Buttons
			const viewBtn = e.target.closest('.wpcal-view-btn');
			if (viewBtn) {
				e.preventDefault();
				const newView = viewBtn.dataset.view;
				if (!newView) return;

				app.querySelectorAll('.wpcal-view-btn').forEach(b => {
					b.classList.remove('active');
					b.setAttribute('aria-checked', 'false');
				});
				viewBtn.classList.add('active');
				viewBtn.setAttribute('aria-checked', 'true');
				app.setAttribute('data-current-view', newView);

				loadCalendarView(app, stage, { view: newView });
				return;
			}
		});

		// Delegate Search Form in List view
		app.addEventListener('submit', function (e) {
			const searchForm = e.target.closest('.wpcal-filter-form');
			if (searchForm) {
				e.preventDefault();
				const input = searchForm.querySelector('#wpcal-search-input');
				const query = input ? input.value : '';
				loadCalendarView(app, stage, {
					view: 'list',
					search: query,
					page: 1
				});
			}
		});
	}

	function loadCalendarView(app, stage, params) {
		stage.setAttribute('aria-busy', 'true');
		stage.style.opacity = '0.5';

		const restUrl = (window.wpcalData && window.wpcalData.restUrl)
			? window.wpcalData.restUrl + 'calendar/view'
			: '/wp-json/wpcalibrate-events/v1/calendar/view';

		const url = new URL(restUrl, window.location.origin);
		Object.keys(params).forEach(key => {
			if (params[key] !== undefined && params[key] !== null) {
				url.searchParams.set(key, params[key]);
			}
		});

		fetch(url.toString(), {
			headers: {
				'Accept': 'application/json'
			}
		})
		.then(function (response) {
			if (!response.ok) throw new Error('Network error loading calendar');
			return response.json();
		})
		.then(function (data) {
			if (data && data.html) {
				stage.innerHTML = data.html;
				// Move focus to the new title for screen-reader awareness
				const heading = stage.querySelector('h2');
				if (heading) {
					heading.setAttribute('tabindex', '-1');
					heading.focus();
				}
			}
		})
		.catch(function (err) {
			console.error('WPCalibrate Calendar load error:', err);
		})
		.finally(function () {
			stage.removeAttribute('aria-busy');
			stage.style.opacity = '1';
		});
	}
})();

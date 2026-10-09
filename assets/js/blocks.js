(function (wp) {
	if (!wp || !wp.blocks || !wp.element) return;

	const { registerBlockType } = wp.blocks;
	const { createElement: el } = wp.element;
	const { InspectorControls } = wp.blockEditor || wp.editor;
	const { PanelBody, SelectControl, RangeControl } = wp.components;

	// Register Calendar Block
	registerBlockType('wpcalibrate/calendar', {
		edit: function (props) {
			const { attributes, setAttributes } = props;
			const { view } = attributes;

			return el(
				'div',
				{ className: 'wpcal-block-preview' },
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: 'Calendar Settings', initialOpen: true },
						el(SelectControl, {
							label: 'Default View',
							value: view,
							options: [
								{ label: 'Month View', value: 'month' },
								{ label: 'Week View', value: 'week' },
								{ label: 'Day View', value: 'day' },
								{ label: 'List View', value: 'list' }
							],
							onChange: function (val) {
								setAttributes({ view: val });
							}
						})
					)
				),
				el(
					'div',
					{ style: { border: '2px dashed #2271b1', padding: '24px', textAlign: 'center', background: '#f0f6fc', borderRadius: '6px' } },
					el('span', { className: 'dashicons dashicons-calendar-alt', style: { fontSize: '36px', width: '36px', height: '36px', color: '#2271b1' } }),
					el('h3', { style: { margin: '10px 0 5px' } }, 'WPCalibrate Calendar'),
					el('p', { style: { margin: 0, color: '#646970' } }, 'Interactive event calendar (Default: ' + view + ' view). Previews fully on frontend.')
				)
			);
		},
		save: function () {
			return null; // Server rendered via render_callback
		}
	});

	// Register Upcoming Events Block
	registerBlockType('wpcalibrate/upcoming-events', {
		edit: function (props) {
			const { attributes, setAttributes } = props;
			const { limit } = attributes;

			return el(
				'div',
				{ className: 'wpcal-block-preview' },
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: 'Upcoming Events Settings', initialOpen: true },
						el(RangeControl, {
							label: 'Number of Events',
							value: limit,
							min: 1,
							max: 20,
							onChange: function (val) {
								setAttributes({ limit: val });
							}
						})
					)
				),
				el(
					'div',
					{ style: { border: '2px dashed #2271b1', padding: '24px', textAlign: 'center', background: '#f0f6fc', borderRadius: '6px' } },
					el('span', { className: 'dashicons dashicons-clock', style: { fontSize: '36px', width: '36px', height: '36px', color: '#2271b1' } }),
					el('h3', { style: { margin: '10px 0 5px' } }, 'WPCalibrate Upcoming Events'),
					el('p', { style: { margin: 0, color: '#646970' } }, 'Displaying up to ' + limit + ' upcoming events.')
				)
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp);

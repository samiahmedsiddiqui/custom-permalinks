(function () {
	const inputSelector = 'input.post-settings-input';
	const firstInput = document.querySelector(inputSelector);
	const tagButtonSelector = '.avaliable-tag button';

	/**
	 * Highlight buttons whose tags are used in the currently selected input field.
	 *
	 * @param {*} input
	 */
	function updateButtonStatesForInput(input) {
		const value = input.value;
		document.querySelectorAll(tagButtonSelector).forEach(function (button) {
			let tag = button.getAttribute('data-name');
			if (value.includes(tag)) {
				button.classList.add('active');
			} else if (tag.startsWith('%ctax_')) {
				tag = tag.replace('TAXONOMY_NAME%', '');
				if (value.includes(tag)) {
					button.classList.add('active');
				} else {
					button.classList.remove('active');
				}
			} else {
				button.classList.remove('active');
			}
		});
	}

	/**
	 * Initial state update for first input.
	 */
	if (firstInput) {
		updateButtonStatesForInput(firstInput);
	}

	/**
	 * Toggle tag in input and active class on button.
	 */
	document.querySelectorAll(tagButtonSelector).forEach(function (button) {
		button.addEventListener('click', function () {
			const activeInput = document.querySelector(
				'.active-row ' + inputSelector
			);

			if (!activeInput) {
				return;
			}

			const tag = this.getAttribute('data-name');
			let value = activeInput.value;

			// Normalize slashes before we begin.
			value = value.replace(/\/+/g, '/');

			if (value.includes(tag)) {
				// Remove tag and surrounding slashes.
				value = value.replace(new RegExp(`/?${tag}/?`), '/');
				this.classList.remove('active');
			} else {
				// Ensure it ends with a single slash.
				if (!value.endsWith('/')) {
					value += '/';
				}
				value += tag + '/';
				this.classList.add('active');
			}

			// Clean up double slashes and trim leading/trailing.
			value = value.replace(/\/+/g, '/').replace(/^\/|\/$/g, '');
			if (value === '') {
				activeInput.value = '';
			} else {
				activeInput.value = value + '/';
			}
		});
	});

	/**
	 * Move tag row below the active row.
	 */
	document.querySelectorAll(inputSelector).forEach(function (input) {
		input.addEventListener('focus', function () {
			const thisRow = this.closest('tr');
			const nextRow = thisRow.nextElementSibling;
			const tagRow = document.querySelector('.permalink-tags');

			document.querySelectorAll('tr').forEach(function (tr) {
				tr.classList.remove('active-row');
			});

			thisRow.classList.add('active-row');
			if (tagRow && tagRow !== nextRow) {
				thisRow.parentNode.insertBefore(tagRow, thisRow.nextSibling);
			}

			updateButtonStatesForInput(this);
		});
	});
})();

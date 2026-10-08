(function () {
	const inputSelector = 'input.post-settings-input';
	const firstInput = document.querySelector(inputSelector);
	const tagButtonSelector = '.avaliable-tag button';

	/**
	 * Highlight buttons whose tags are used in the currently selected input field.
	 *
	 * @param {HTMLInputElement} input Selected settings input field.
	 */
	function updateButtonStatesForInput(input) {
		const value = input.value;
		const usedCtax = {
			ctax: {
				slug: false,
				name: false,
			},
			ctax_parent: {
				slug: false,
				name: false,
			},
			ctax_parents: {
				slug: false,
				name: false,
			},
		};

		value.replace(
			/%(ctax(?:_parent|_parents)?)_([^%]+?)(?:(_name))?%/g,
			function (_, type, taxonomy, namePart) {
				if (namePart) {
					usedCtax[type].name = true;
				} else {
					usedCtax[type].slug = true;
				}
			}
		);

		document.querySelectorAll(tagButtonSelector).forEach(function (button) {
			const tag = button.getAttribute('data-name');
			button.classList.remove('active');

			if (!tag) {
				return;
			}

			if (value.includes(tag)) {
				button.classList.add('active');
				return;
			}

			const match = tag.match(
				/^%(ctax(?:_parent|_parents)?)_TAXONOMY_NAME(?:(_name))?%$/
			);
			if (match) {
				const type = match[1];
				const isNameButton = !!match[2];

				if (
					(isNameButton && usedCtax[type].name) ||
					(!isNameButton && usedCtax[type].slug)
				) {
					button.classList.add('active');
				}
			}

			if (tag.startsWith('%custom_permalinks_')) {
				const customRegex = /%custom_permalinks_[^%]+%/;
				if (customRegex.test(value)) {
					button.classList.add('active');
				}
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

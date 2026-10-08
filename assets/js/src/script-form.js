(function () {
	'use strict';

	let editPost = '';
	let isSaving = '';
	let lastIsSaving = false;

	/**
	 * Change color of edit box on focus.
	 *
	 * @param {Object} event Event Listener Object.
	 */
	function focusPermalinkField(event) {
		if (event.target) {
			event.target.style.color = '#000';
		}
	}

	/**
	 * Change color of edit box on blur.
	 *
	 * @param {Object} event Event Listener Object.
	 */
	function blurPermalinkField(event) {
		if (!event.target) {
			return;
		}

		const originalPermalink = document.getElementById('original-permalink');

		document.getElementById('custom_permalink').value = event.target.value;
		if (
			event.target.value === '' ||
			event.target.value === originalPermalink.value
		) {
			event.target.value = originalPermalink.value;
			event.target.style.color = '#ddd';
		}
	}

	/**
	 * Build an absolute http(s) URL from the home URL and a permalink.
	 *
	 * @param {string} homeURL   Site home URL.
	 * @param {string} permalink Permalink path.
	 *
	 * @return {string} Absolute URL, or an empty string if it isn't an http(s)
	 *                  URL on the home URL's site.
	 */
	function buildViewURL(homeURL, permalink) {
		let home;
		let url;
		try {
			home = new URL(homeURL);
			url = new URL(homeURL + permalink);
		} catch {
			return '';
		}

		if (
			(url.protocol !== 'http:' && url.protocol !== 'https:') ||
			url.origin !== home.origin
		) {
			return '';
		}

		return url.href;
	}

	/**
	 * Update Permalink Value in View Button and hidden fields.
	 *
	 * @param {Object} setPermlinks
	 */
	function updateFetchedPermalink(setPermlinks) {
		const getHomeURL = document.getElementById(
			'custom_permalinks_home_url'
		);
		const permalinkAdd = document.getElementById('custom-permalinks-add');
		let replaceOldPermalink = '';

		document.getElementById('custom_permalink').value =
			setPermlinks.custom_permalink;
		if (setPermlinks.custom_permalink === '') {
			setPermlinks.custom_permalink = setPermlinks.original_permalink;
		}

		const viewPermalink = buildViewURL(
			getHomeURL.value,
			setPermlinks.preview_permalink || setPermlinks.custom_permalink
		);

		document.getElementById('custom-permalinks-post-slug').value =
			setPermlinks.custom_permalink;
		document.getElementById('original-permalink').value =
			setPermlinks.original_permalink;

		// Only update links with a valid http(s) URL.
		if (viewPermalink !== '') {
			if (document.querySelector('#view-post-btn a')) {
				replaceOldPermalink =
					document.querySelector('#view-post-btn a').href;

				// Cannot be removed as replaceOldPermalink can be empty.
				document.querySelector('#view-post-btn a').href = viewPermalink;
			}

			if (document.querySelector('a.editor-post-preview')) {
				// Cannot be removed as replaceOldPermalink can be empty.
				document.querySelector('a.editor-post-preview').href =
					viewPermalink;
			}

			// Only works when replaceOldPermalink is not empty.
			if (replaceOldPermalink !== '') {
				// Plain text replace, so characters like `.` or `?` in the URL aren't regex syntax.
				document.querySelectorAll('body a').forEach(function (link) {
					if (link.href && link.href.includes(replaceOldPermalink)) {
						link.href = link.href
							.split(replaceOldPermalink)
							.join(viewPermalink);
					}
				});
			}
		}

		if (permalinkAdd && permalinkAdd.value === 'add') {
			document.getElementById(
				'custom-permalinks-edit-box'
			).style.display = '';
		}

		if (document.querySelector('.components-notice__content a')) {
			document.querySelector('.components-notice__content a').href =
				'/' + setPermlinks.custom_permalink;
		}
	}

	/**
	 * Fetch updated permalink via REST API.
	 */
	function fetchUpdates() {
		if (!editPost || !wpApiSettings || !wpApiSettings.nonce) {
			return;
		}

		const defaultPerm = document.getElementsByClassName(
			'edit-post-post-link__preview-label'
		);
		const geBaseURL = document.getElementById('custom_permalinks_base_url');
		let postId = '';
		let xhttp = '';

		if (defaultPerm && defaultPerm[0]) {
			defaultPerm[0].parentNode.classList.add('cp-permalink-hidden');
		}

		isSaving = editPost.isSavingMetaBoxes();
		if (isSaving !== lastIsSaving && !isSaving && geBaseURL) {
			postId = wp.data.select('core/editor').getEditedPostAttribute('id');
			xhttp = new XMLHttpRequest();

			lastIsSaving = isSaving;
			xhttp.onreadystatechange = function () {
				const xhttpReadyState = 4;
				const xhttpStatus = 200;

				if (
					xhttp.readyState === xhttpReadyState &&
					xhttp.status === xhttpStatus
				) {
					updateFetchedPermalink(JSON.parse(xhttp.responseText));
				}
			};

			xhttp.open(
				'GET',
				geBaseURL.value +
					'wp-json/custom-permalinks/v1/get-permalink/' +
					postId,
				true
			);
			xhttp.setRequestHeader(
				'Cache-Control',
				'private, max-age=0, no-cache'
			);
			xhttp.setRequestHeader('X-WP-NONCE', wpApiSettings.nonce);

			xhttp.send();
		}

		lastIsSaving = isSaving;
	}

	/**
	 * Hide default Permalink metabox
	 */
	function hideDefaultPermalink() {
		const defaultPerm = document.getElementsByClassName(
			'edit-post-post-link__preview-label'
		);

		if (defaultPerm && defaultPerm[0]) {
			defaultPerm[0].parentNode.classList.add('cp-permalink-hidden');
		}
	}

	function permalinkContentLoaded() {
		const defaultPerm = document.getElementsByClassName(
			'edit-post-post-link__preview-label'
		);
		const incrementNumber = 1;
		let loopInit = 0;
		let permalinkAdd = '';
		const permalinkEdit = document.getElementById(
			'custom-permalinks-edit-box'
		);
		const postSlug = document.getElementById('custom-permalinks-post-slug');
		let sidebar = '';
		let totalTabs = 0;

		if (postSlug) {
			postSlug.addEventListener('focus', focusPermalinkField);
			postSlug.addEventListener('blur', blurPermalinkField);
		}

		if (permalinkEdit) {
			if (
				document
					.querySelector('#custom-permalinks-edit-box .inside')
					.innerHTML.trim() === ''
			) {
				permalinkEdit.style.display = 'none';
			}
		}

		if (wp.data) {
			permalinkAdd = document.getElementById('custom-permalinks-add');
			sidebar = document.querySelectorAll(
				'.edit-post-sidebar .components-panel__header'
			);

			if (sidebar && sidebar.length) {
				totalTabs = sidebar.length;
			}

			if (permalinkAdd && permalinkAdd.value === 'add') {
				permalinkEdit.style.display = 'none';
			}

			editPost = wp.data.select('core/edit-post');
			wp.data.subscribe(fetchUpdates);

			if (defaultPerm && defaultPerm[0]) {
				defaultPerm[0].parentNode.classList.add('cp-permalink-hidden');
			}

			if (permalinkEdit.classList.contains('closed')) {
				permalinkEdit.classList.remove('closed');
			}

			while (loopInit < totalTabs) {
				sidebar[loopInit].addEventListener(
					'click',
					hideDefaultPermalink
				);
				loopInit += incrementNumber;
			}
		}
	}

	document.addEventListener('DOMContentLoaded', permalinkContentLoaded);
})();

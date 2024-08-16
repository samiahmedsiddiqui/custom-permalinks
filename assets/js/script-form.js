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
   * Update Permalink Value in View Button and hidden fields.
   *
   * @param {Object} setPermlinks
   */
  function updateFetchedPermalink(setPermlinks) {
    const getHomeURL = document.getElementById(
      'custom_permalinks_home_url'
    );
    const permalinkAdd = document.getElementById('custom-permalinks-add');
    let viewPermalink = '';
    let replaceOldPermalink = '';

    document.getElementById('custom_permalink').value =
      setPermlinks.custom_permalink;
    if (setPermlinks.custom_permalink === '') {
      // eslint-disable-next-line camelcase
      setPermlinks.custom_permalink = setPermlinks.original_permalink;
    }

    if (setPermlinks.preview_permalink) {
      viewPermalink = getHomeURL.value + setPermlinks.preview_permalink;
    } else {
      viewPermalink = getHomeURL.value + setPermlinks.custom_permalink;
    }

    document.getElementById('custom-permalinks-post-slug').value =
      setPermlinks.custom_permalink;
    document.getElementById('original-permalink').value =
      setPermlinks.original_permalink;

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
      replaceOldPermalink = replaceOldPermalink.replace(/\//g, '/');
      let loopInit = 0;

      const incrementNumber = 1;
      const oldPermalinks = document.querySelectorAll('body a');
      const replaceRegex = new RegExp(replaceOldPermalink, 'g');
      const totalOldLinks = oldPermalinks.length;

      while (loopInit < totalOldLinks) {
        if (oldPermalinks[loopInit] && oldPermalinks[loopInit].href) {
          oldPermalinks[loopInit].href = oldPermalinks[
            loopInit
          ].href.replace(replaceRegex, viewPermalink);
        }

        loopInit += incrementNumber;
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

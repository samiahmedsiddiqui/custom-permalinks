var getHomeURL = document.getElementById("custom_permalinks_home_url");
var getPermalink = document.getElementById("custom_permalink");
var editPost = "";
var isSaving = "";
var lastIsSaving = false;

/**
 * Change color of edit box on focus.
 */
function focusPermalinkField() {
  "use strict";

  var newPostSlug = document.getElementById("custom-permalinks-post-slug");
  if (newPostSlug) {
    newPostSlug.style.color = "#000";
  }
}

/**
 * Change color of edit box on blur.
 */
function blurPermalinkField() {
  "use strict";

  var newPostSlug = document.getElementById("custom-permalinks-post-slug");
  var originalPermalink = document.getElementById("original_permalink");
  if (!newPostSlug) {
    return;
  }
  getPermalink.value = newPostSlug.value;
  if (newPostSlug.value === "" || newPostSlug.value === originalPermalink.value) {
    newPostSlug.value = originalPermalink.value;
    newPostSlug.style.color = "#ddd";
  }
}

/**
 * Update Permalink Value in View Button
 */
function updateMetaBox() {
  "use strict";

  if (!editPost) {
    return;
  }

  var defaultPerm = document.getElementsByClassName("edit-post-post-link__preview-label");
  if (defaultPerm && defaultPerm[0]) {
    defaultPerm[0].parentNode.classList.add("cp-permalink-hidden");
  }
  isSaving = editPost.isSavingMetaBoxes();

  if (isSaving !== lastIsSaving && !isSaving) {
    lastIsSaving = isSaving;
    var postId = wp.data.select("core/editor").getEditedPostAttribute("id");
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
      if (this.readyState == 4 && this.status == 200) {
        var setPermlinks = JSON.parse(this.responseText);
        var permalinkAdd = document.getElementById("custom-permalinks-add");
        getPermalink.value = setPermlinks.permalink_customizer;
        document.getElementById("custom-permalinks-post-slug").value = setPermlinks.permalink_customizer;
        document.getElementById("original_permalink").value = setPermlinks.original_permalink;
        document.querySelector("#view-post-btn a").href = getHomeURL.value + "/" + setPermlinks.permalink_customizer;
        if (permalinkAdd && permalinkAdd.value === "add") {
          document.getElementById("custom-permalinks-edit-box").style.display = "";
        }
        if (document.querySelector(".components-notice__content a")) {
          document.querySelector(".components-notice__content a").href = "/" + setPermlinks.permalink_customizer;
        }
      }
    };
    xhttp.open("GET", getHomeURL.value + "/wp-json/custom-permalinks/v1/get-permalink/" + postId, true);
    xhttp.setRequestHeader("Cache-Control", "private, max-age=0, no-cache");
    xhttp.send();
  }

  lastIsSaving = isSaving;
}

/**
 * Hide default Permalink metabox
 */
function hideDefaultPermalink() {
  "use strict";

  var defaultPerm = document.getElementsByClassName("edit-post-post-link__preview-label");
  if (defaultPerm && defaultPerm[0]) {
    defaultPerm[0].parentNode.classList.add("cp-permalink-hidden");
  }
}

function permalinkContentLoaded() {
  "use strict";

  var permalinkEdit = document.getElementById("custom-permalinks-edit-box");
  var defaultPerm = document.getElementsByClassName("edit-post-post-link__preview-label");
  var postSlug = document.getElementById("custom-permalinks-post-slug");

  if (postSlug) {
    postSlug.addEventListener("focus", focusPermalinkField);
    postSlug.addEventListener("blur", blurPermalinkField);
  }

  if (document.querySelector("#custom-permalinks-edit-box .inside").innerHTML.trim() === "") {
    permalinkEdit.style.display = "none";
  }
  if (wp.data) {
    var permalinkAdd = document.getElementById("custom-permalinks-add");
    var sidebar = document.querySelectorAll(".edit-post-sidebar .components-panel__header");
    var i = 0;
    var totalTabs = sidebar.length;
    if (permalinkAdd && permalinkAdd.value === "add") {
      permalinkEdit.style.display = "none";
    }
    editPost = wp.data.select("core/edit-post");
    wp.data.subscribe(updateMetaBox);
    if (defaultPerm && defaultPerm[0]) {
      defaultPerm[0].parentNode.classList.add("cp-permalink-hidden");
    }
    if (permalinkEdit.classList.contains("closed")) {
      permalinkEdit.classList.remove("closed");
    }
    if (sidebar && totalTabs > 0) {
      while (i < totalTabs) {
        sidebar[i].addEventListener("click", hideDefaultPermalink);
        i += 1;
      }
    }
  }
}
document.addEventListener("DOMContentLoaded", permalinkContentLoaded);

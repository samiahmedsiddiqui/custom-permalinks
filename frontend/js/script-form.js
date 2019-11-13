var getHomeURL = document.getElementById("custom_permalinks_home_url");
var getPermalink = document.getElementById("custom_permalink");
var checkYoastSEO = document.getElementById("wpseo_meta");

function changeSEOLinkOnBlur() {
  "use strict";

  var snippetCiteBase = document.getElementById("snippet_citeBase");
  var funcAllowed = true;
  if (!snippetCiteBase) {
    funcAllowed = false;
  } else if (!getHomeURL || getHomeURL.value === "") {
    funcAllowed = false;
  } else if (!getPermalink || !getPermalink.value) {
    funcAllowed = false;
  }

  if (funcAllowed) {
    return;
  }

  var i = 0;
  var urlChanged = setInterval(function () {
    i += 1;
    snippetCiteBase.innerHTML = getHomeURL.value + "/" + getPermalink.value;
    if (i === 5) {
      clearInterval(urlChanged);
    }
  }, 1000);
}

function changeSEOLink() {
  "use strict";

  var snippetCiteBase = document.getElementById("snippet_citeBase");
  var funcAllowed = true;
  if (!snippetCiteBase) {
    funcAllowed = false;
  } else if (!getHomeURL || getHomeURL.value === "") {
    funcAllowed = false;
  } else if (!getPermalink || !getPermalink.value) {
    funcAllowed = false;
  }

  if (funcAllowed) {
    return;
  }

  var i = 0;
  var urlChanged = setInterval(function () {
    i += 1;
    snippetCiteBase.innerHTML = getHomeURL.value + "/" + getPermalink.value;
    if (i === 5) {
      clearInterval(urlChanged);
    }
  }, 1000);
  var snippetEditorTitle = document.getElementById("snippet-editor-title");
  var snippetEditorSlug = document.getElementById("snippet-editor-slug");
  var snippetEditorDesc = document.getElementById("snippet-editor-meta-description");
  var snippetCite = document.getElementById("snippet_cite");
  if (snippetEditorTitle) {
    snippetEditorTitle.addEventListener("blur", changeSEOLinkOnBlur);
  }
  if (snippetEditorSlug) {
    snippetEditorSlug.addEventListener("blur", changeSEOLinkOnBlur);
  }
  if (snippetEditorDesc) {
    snippetEditorDesc.addEventListener("blur", changeSEOLinkOnBlur);
  }
  if (snippetCite) {
    snippetCite.style.display = "none";
  }
}

if (checkYoastSEO) {
  window.addEventListener("load", changeSEOLink);
}
if (document.querySelector("#custom-permalinks-edit-box .inside").innerHTML.trim() === "") {
  document.getElementById("custom-permalinks-edit-box").style.display = "none";
}

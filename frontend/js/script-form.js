var getHomeURL = document.getElementById("custom_permalinks_home_url");
var getPermalink = document.getElementById("custom_permalink");
var checkYoastSEO = document.getElementById("wpseo_meta");

function changeSEOLinkOnBlur() {
    var snippetCiteBase = document.getElementById("snippet_citeBase");
    if (snippetCiteBase && getHomeURL && getHomeURL.value !== "" && getPermalink && getPermalink.value) {
        var i = 0;
        var urlChanged = setInterval( function() {
            i++;
            snippetCiteBase.innerHTML = getHomeURL.value + "/" + getPermalink.value;
            if (i === 5) {
                clearInterval(urlChanged);                
            }
        }, 1000);
    }
}

function changeSEOLink() {
    var snippetCiteBase = document.getElementById("snippet_citeBase");
    if (snippetCiteBase && getHomeURL && getHomeURL.value !== "" && getPermalink && getPermalink.value) {
        var i = 0;
        var urlChanged = setInterval( function() {
            i++;
            snippetCiteBase.innerHTML = getHomeURL.value + "/" + getPermalink.value;
            if (i === 5) {
                clearInterval(urlChanged);
            }
        }, 1000);
        var snippetEditorTitle = document.getElementById("snippet-editor-title");
        var snippetEditorSlug = document.getElementById("snippet-editor-slug");
        var snippetEditorDesc = document.getElementById("snippet-editor-meta-description");
        var snippetCite = document.getElementById("snippet_cite");
        if ( snippetEditorTitle ) {
            snippetEditorTitle.addEventListener("blur", changeSEOLinkOnBlur, false);
        }
        if ( snippetEditorSlug ) {
            snippetEditorSlug.addEventListener("blur", changeSEOLinkOnBlur, false);
        }
        if ( snippetEditorDesc ) {
            snippetEditorDesc.addEventListener("blur", changeSEOLinkOnBlur, false);
        }
        if (snippetCite) {
            snippetCite.style.display = "none";
        }
    }
}

if (checkYoastSEO) {
    window.addEventListener("load", changeSEOLink, false);
}
if (document.querySelector("#custom-permalinks-edit-box .inside").innerHTML.trim() === "") {
    document.getElementById("custom-permalinks-edit-box").style.display = "none";
}

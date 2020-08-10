const { src, dest, parallel } = require('gulp');
const cleanCSS = require('gulp-clean-css');
const del = require('del');
const package = require("./package.json");
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');

// Get Plugin Version from `package.json`
const pluginVersion = package.customPermalinks.pluginVersion;

async function deleteMinFiles() {
  await del(['admin/**/*.min.css', 'frontend/**/*.min.js']);
}

function minifyCss() {
  return src(['admin/**/*.css', '!admin/**/*/*.min.css'])
    .pipe(cleanCSS({compatibility: 'ie8'}))
    .pipe(rename(function(path) {
        path.extname = "-" + pluginVersion + ".min" + path.extname;
    }))
    .pipe(dest('admin/'));
}

function minifyJs() {
  return src(['frontend/**/*.js', '!frontend/**/*/*.min.js'])
    .pipe(uglify())
    .pipe(rename(function(path) {
        console.log(pluginVersion)
        path.extname = "-" + pluginVersion + ".min" + path.extname;
    }))
    .pipe(dest('frontend/'));
}

exports.build = parallel(deleteMinFiles, minifyCss, minifyJs);

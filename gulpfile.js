const { src, dest, parallel } = require('gulp');
const cleanCSS = require('gulp-clean-css');
const del = require('del');
const package = require("./package.json");
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');

// Get Plugin Version from `package.json`
const pluginVersion = package.customPermalinks.pluginVersion;

async function deleteMinFiles() {
  await del(['assets/**/*.min.*']);
}

function minifyCss() {
  return src(['assets/**/*.css', '!assets/**/*/*.min.css'])
    .pipe(cleanCSS({compatibility: 'ie8'}))
    .pipe(rename(function(path) {
        path.extname = '-' + pluginVersion + '.min.css';
    }))
    .pipe(dest('./assets'));
}

function minifyJs() {
  return src(['assets/**/*.js', '!assets/**/*/*.min.js'])
    .pipe(uglify())
    .pipe(rename(function(path) {
        path.extname = '-' + pluginVersion + '.min.js';
    }))
    .pipe(dest('./assets'));
}

exports.build = parallel(deleteMinFiles, minifyCss, minifyJs);

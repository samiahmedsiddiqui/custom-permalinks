const { src, dest, parallel } = require('gulp');
const cleanCSS = require('gulp-clean-css');
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');


function minifyCss() {
  return src(['admin/**/*.css', '!admin/**/*/.min.css'])
    .pipe(cleanCSS({compatibility: 'ie8'}))
    .pipe(rename({ extname: '.min.css' }))
    .pipe(dest('admin/'));
}

function minifyJs() {
  return src(['frontend/**/*.js', '!frontend/**/*/.min.js'])
    .pipe(uglify())
    .pipe(rename({ extname: '.min.js' }))
    .pipe(dest('frontend/'));
}

exports.build = parallel(minifyCss, minifyJs);

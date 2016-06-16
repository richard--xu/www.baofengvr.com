// Gulp Task
// Author: Zhixiang Wang
'use strict';

var gulp = require('gulp');

var $ = require('gulp-load-plugins')();

var vinylPaths = require('vinyl-paths');

var prefixerConfig = [
	'ie >= 10',
	'ie_mob >= 10',
	'ff >= 30',
	'chrome >= 34',
	'safari >= 7',
	'opera >= 23',
	'ios >= 7',
	'android >= 4.4',
	'bb >= 10'
];

var sftpConfig = {
	'host': '10.21.3.33',
	'port': '22',
	'user': 'root',
	'pass': 'YB-testforzz',
	'remotePath': '/usr/local/bbs_yaf/public/front/admin/css'
}

// file path
var path = {
	'scripts': 'js/**/*.js',
	'scss': './admin/scss/**/*.scss',
	'css': 'statisticsmanage/css/*.css',
	'images': ''
};

// jshint
gulp.task('jshint', function () {
	return gulp.src(fpath['scripts'])
		.pipe($.jshint('.jshintrc'))
		.pipe($.jshint.reporter());
});

// scsslint
gulp.task('scsslint', function () {
	return gulp.src(fpath['scss'])
		.pipe($.csslint())
		.pipe($.csslint.reporter());
});

// styles
gulp.task('styles', function (e) {
	return gulp.src('admin/scss/base.scss')
		.pipe($.sourcemaps.init())
		.pipe($.sass({precision: 10}).on('error', $.sass.logError))
		.pipe($.autoprefixer(prefixerConfig))
		.pipe(gulp.dest('./admin/.tmp/css'))
		.pipe($.if('*.css', $.minifyCss()))
		.pipe($.sourcemaps.write())
		.pipe(gulp.dest('./admin/css'))
		.pipe($.size({title: 'styles'}))
		.pipe($.sftp(sftpConfig));
});

// uglify javascripts
gulp.task('uglify', function () {
	return gulp.src(fpath['scripts'])
		.pipe($.uglify({preserveComments: 'some'}))
		.pipe(gulp.dest('.tmp/js'))
		.pipe($.size({title: 'scripts'}));
});

// delete files
gulp.task('clean', function () {
	return gulp.src('.tmp/*')
		.pipe(vinylPaths($.del));
});

// watch styles
gulp.task('watch', function () {
	gulp.watch(path.scss, ['styles']);
});
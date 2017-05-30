/**
 * gulpfile.js for php-library developement
 *
 * 1. install node.js & npm
 * 2. $ npm install
 * 3. $ gulp server
 * 4. open http://localhost:9000/ (livereload enabled)
 * 5. coding on src/*.php and tests/*.php
 *
 * enjoy!
 *
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0-1.0 (No Rights Reserved.)
 * @link https://github.com/spindle/spindle-lib-template
 */
var gulp = require('gulp');
var exec = require('child_process').exec;
var connect = require('gulp-connect');

gulp.task('default', ['test', 'inspect']);

gulp.task('help', function(){
    console.log('gulp test\t... kick vendor/bin/phpunit command');
    console.log('gulp inspect\t... kick vendor/bin/apigen and vendor/bin/pdepend');
    console.log('gulp server\t... start static web server on http://localhost:9000/');
    console.log('\tcoverage report... http://localhost:9000/coverage/');
    console.log('\tApiGen document... http://localhost:9000/api/');
});

gulp.task('test', function(done){
    exec('vendor/bin/phpunit --colors=always', function(err, stdout, stderr){
        console.log(stdout);
        console.error(stderr);
        done();
    });
});

gulp.task('inspect', function(done){
    var i = 0, count = function(){ if (++i > 1) done() };
    exec([
        'vendor/bin/pdepend',
        '--jdepend-chart=builds/pdepend.svg',
        '--overview-pyramid=builds/pyramid.svg',
        '--summary-xml=builds/summary.xml',
        'src/'].join(' '), count);
    exec('vendor/bin/apigen.php', count);
});

gulp.task('connect', ['default'], function(){
    connect.server({
        root: [__dirname + '/builds/'],
        port: 9000,
        livereload: true
    });
});

gulp.task('reload', ['test'], function(){
    return gulp.src('builds/coverage/**')
        .pipe(connect.reload());
});

gulp.task('server', ['connect'], function(){
    gulp.watch(['src/**', 'tests/**'], ['reload']);
});


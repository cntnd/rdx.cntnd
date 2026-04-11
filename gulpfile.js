const gulp = require('gulp');
const del = require('del');

gulp.task('clean', function () {
    return del(['addon/**']);
});

gulp.task('addon', function () {
    return gulp.src(['assets/*', 'data/*', 'fragments/*', 'lang/*', 'lib/*', 'pages/*', 'plugins/*', 'vendor/*', '*.php', '*.sql', 'README.md', 'LICENSE', 'package.yml'], {"base" : ".", "allowEmpty": true})
        .pipe(gulp.dest('addon'));
});

gulp.task('default', gulp.series('clean', 'addon'));

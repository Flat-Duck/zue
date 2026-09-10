import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel([
            'resources/sass/app.scss',
            'resources/sass/app-rtl.scss',
            'resources/js/app.js',
            'resources/js/editor.js',
            'resources/js/appraisal-period-form.js',
            'resources/sass/manifest.scss',
            'resources/sass/print/timesheet-print.scss',
            'resources/sass/print/timesheet-approve.scss',
            'resources/sass/print/injury-report.scss',
            'resources/sass/print/appraisal-official.scss',
            'resources/sass/print/appraisal-review.scss',
            'resources/sass/print/report-balance.scss',
            'resources/sass/print/report-printable.scss',
            'resources/sass/print/report-timesheets.scss',
            'resources/sass/print/report-attendance.scss',
            'resources/sass/print/report-run.scss',
        ]),
    ],
});

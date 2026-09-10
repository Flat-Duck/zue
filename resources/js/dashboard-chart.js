/**
 * The timesheet coverage chart on the dashboard.
 *
 * ApexCharts is around half a megabyte and this is the only place that uses it, so
 * it is fetched only once the chart's container is actually on the page rather than
 * being bundled into every screen in the application.
 */

const CONTAINER_ID = 'chart-timesheet-coverage';

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const AXIS_LABEL = { style: { colors: '#616876' } };

function optionsFor(series) {
    return {
        chart: {
            type: 'bar',
            height: 300,
            stacked: true,
            toolbar: { show: false },
            animations: { enabled: true },
        },
        plotOptions: {
            bar: { columnWidth: '50%', borderRadius: 4 },
        },
        dataLabels: { enabled: false },
        // Tabler blue and soft grey.
        colors: ['#206bc4', '#dce1e7'],
        series,
        xaxis: { categories: MONTHS, labels: AXIS_LABEL },
        yaxis: { labels: AXIS_LABEL },
        legend: { position: 'top', horizontalAlign: 'right' },
        tooltip: {
            theme: 'light',
            y: { formatter: (value) => `${value} employees` },
        },
    };
}

function seriesFrom(data) {
    return [
        { name: 'Filled Timesheets', data: data.filled },
        { name: 'Pending Timesheets', data: data.pending },
    ];
}

async function renderChart(container) {
    const { default: ApexCharts } = await import('apexcharts');

    let data;

    try {
        data = JSON.parse(container.dataset.coverage ?? '{}');
    } catch (error) {
        console.error('DashboardChart: could not read the coverage data', error);

        return;
    }

    if (!Array.isArray(data.filled) || !Array.isArray(data.pending)) {
        console.error('DashboardChart: coverage data is missing its series', data);

        return;
    }

    const chart = new ApexCharts(container, optionsFor(seriesFrom(data)));
    chart.render();

    // Livewire v3 hands the payload through as an array of arguments.
    Livewire.on('updateChart', (payload) => {
        const updated = Array.isArray(payload) ? payload[0] : payload;

        if (!updated?.filled || !updated?.pending) {
            console.error('DashboardChart: invalid data received', updated);

            return;
        }

        chart.updateSeries(seriesFrom(updated));
    });
}

document.addEventListener('livewire:init', () => {
    const container = document.getElementById(CONTAINER_ID);

    if (container) {
        renderChart(container);
    }
});

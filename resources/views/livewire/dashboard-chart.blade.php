<div class="card">
    {{-- <div class="card-header">
        <div>
            <h3 class="card-title">Timesheet Coverage Summary</h3>
            <p class="card-subtitle">Monthly distribution of filled vs pending timesheets</p>
        </div>
        <div class="card-actions">

        </div>
    </div> --}}
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="subheader">Timesheet Coverage Summary</div>
            <div class="ms-auto lh-1">
                <div class="dropdown">
                    <select wire:model.live="selectedYear" class="form-select form-select-sm">
                        @foreach($availableYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div wire:ignore id="chart-timesheet-coverage" style="min-height: 300px;"></div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts" defer></script>
    <script defer>
        document.addEventListener('livewire:init', () => {
            const chartOptions = {
                chart: {
                    type: 'bar',
                    height: 300,
                    stacked: true,
                    toolbar: { show: false },
                    animations: { enabled: true }
                },
                plotOptions: {
                    bar: {
                        columnWidth: '50%',
                        borderRadius: 4
                    }
                },
                dataLabels: { enabled: false },
                colors: ['#206bc4', '#dce1e7'], // Tabler Blue and Soft Grey
                series: [
                    { name: 'Filled Timesheets', data: @json($initialData['filled']) },
                    { name: 'Pending Timesheets', data: @json($initialData['pending']) }
                ],
                xaxis: {
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    labels: {
                        style: { colors: '#616876' }
                    }
                },
                yaxis: {
                    labels: {
                        style: { colors: '#616876' }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right'
                },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function (val) {
                            return val + " employees"
                        }
                    }
                }
            };

            const chart = new ApexCharts(document.querySelector("#chart-timesheet-coverage"), chartOptions);
            chart.render();

            Livewire.on('updateChart', (payload) => {
                // In Livewire v3, the payload is often passed as an array of arguments
                const data = Array.isArray(payload) ? payload[0] : payload;

                if (chart && data && data.filled && data.pending) {
                    chart.updateSeries([
                        { name: 'Filled Timesheets', data: data.filled },
                        { name: 'Pending Timesheets', data: data.pending }
                    ]);
                } else {
                    console.error('DashboardChart: Invalid data received', data);
                }
            });
        });
    </script>
@endpush
<?php
// Fetch latest estimated_departure_date from DB
$result = $conn->query("SELECT MAX(estimated_departure_date) AS latest FROM pdc_projects");
$latestDate = $result->fetch_assoc()['latest'] ?? date('Y-m-d');

// Default start = this month
$defaultStart = date('Y-m'); // e.g., 2025-08
$defaultEnd = date('Y-m', strtotime($latestDate)); // e.g., 2026-12

// Generate months array
$months = [];
$startDate = new DateTime($defaultStart . '-01');
$endDate = new DateTime($defaultEnd . '-01');
while ($startDate <= $endDate) {
    $months[] = $startDate->format('Y-m');
    $startDate->modify('+1 month');
}

// Determine default quarters
function monthToQuarter($month)
{
    return 'Q' . ceil($month / 3);
}
$startQuarter = date('Y', strtotime($defaultStart . '-01')) . '-' . monthToQuarter(date('n'));
$endQuarter = date('Y', strtotime($defaultEnd . '-01')) . '-' . monthToQuarter(date('n', strtotime($defaultEnd . '-01')));

// Determine default years
$startYear = date('Y'); // current year
$endYear = date('Y', strtotime($defaultEnd . '-01')); // latest year in DB
?>

<!DOCTYPE html>
<html>

<head>
    <style>
        .canvasjs-chart-credit {
            display: none !important;
        }

        .canvasjs-chart-canvas {
            border-radius: 12px;
        }
    </style>
</head>

<body>

    <div id="filterProjectDashboard" style="display:none">
        <div class="d-flex justify-content-center align-items-center mb-3 background-color shadow-lg py-4 rounded-3">
            <div class="row col-8">
                <div class="col-10 mb-3">
                    <label for="filterType" class="fw-bold">Filter By:</label>
                    <select id="filterType" class="form-select">
                        <option value="month" selected>Month</option>
                        <option value="quarter">Quarter</option>
                        <option value="year">Year</option>
                    </select>
                </div>
                <div class="col-2 mb-3">
                    <label for="poFilter" class="fw-bold">PO:</label>
                    <select id="poFilter" class="form-select">
                        <option value="">Any</option>
                        <option value="yes">Yes</option>
                        <option value="no">No</option>
                    </select>
                </div>

                <div id="monthFilter" class="d-flex gap-3">
                    <div class="flex-grow-1">
                        <label for="startMonth" class="fw-bold">Start Month:</label>
                        <input type="month" id="startMonth" name="startMonth" class="form-control"
                            value="<?php echo $months[0] ?? ''; ?>" />
                    </div>
                    <div class="flex-grow-1">
                        <label for="endMonth" class="fw-bold">End Month:</label>
                        <input type="month" id="endMonth" name="endMonth" class="form-control"
                            value="<?php echo end($months) ?? ''; ?>" />
                    </div>
                </div>

                <div id="quarterFilter" class="d-none gap-3">
                    <div class="flex-grow-1">
                        <label for="startQuarter" class="fw-bold">Start Quarter:</label>
                        <select id="startQuarter" name="startQuarter" class="form-select"></select>
                    </div>
                    <div class="flex-grow-1 mt-3">
                        <label for="endQuarter" class="fw-bold">End Quarter:</label>
                        <select id="endQuarter" name="endQuarter" class="form-select"></select>
                    </div>
                </div>

                <div id="yearFilter" class="d-none gap-3">
                    <div class="flex-grow-1">
                        <label for="startYear" class="fw-bold">Start Year:</label>
                        <select id="startYear" name="startYear" class="form-select"></select>
                    </div>
                    <div class="flex-grow-1 mt-3">
                        <label for="endYear" class="fw-bold">End Year:</label>
                        <select id="endYear" name="endYear" class="form-select"></select>
                    </div>
                </div>


                <div class="d-flex justify-content-center mt-3 col-12 gap-2">
                    <button class="btn btn-danger fw-bold" id="resetFilter">Reset</button>
                    <button class="btn btn-secondary fw-bold" id="hideFilterProjectDashboard">Cancel</button>
                    <button id="filterButton" class="btn btn-dark fw-bold">Filter</button>
                </div>
            </div>
        </div>
    </div>

    <div
        class="d-flex flex-column flex-md-row align-items-center justify-content-between p-3 signature-bg-color text-white rounded-top-3">
        <div class="d-flex flex-column flex-md-row align-items-center my-2 my-md-0">
            <span class="fw-bold">Timeframe: <span id="timeframeDisplay"></span></span>
        </div>
        <div class="d-flex flex-column flex-md-row align-items-center my-2 my-md-0">
            <button class="btn btn-dark btn-sm fw-bold ms-2" id="showFiltererdPDCTable">Show Table <i
                    class="fa-solid fa-table"></i></button>
            <button class="btn btn-light btn-sm fw-bold ms-2" id="showFilterProjectDashboard">Edit<i
                    class="fa-regular fa-pen-to-square ms-1"></i></button>
            <button class="btn btn-success btn-sm fw-bold ms-2" id="refreshButton">Refresh<i
                    class="fa-solid fa-arrows-rotate ms-1"></i></button>
        </div>
    </div>

    <canvas class="p-3 shadow-lg rounded-3 mb-3" id="combinedChart" width="1000" height="300"></canvas>

    <table id="fbnTable" style="display: none" class="table table-bordered table-striped text-center table-hover">
        <thead>
            <tr>
                <th>Date</th>
                <th>Total PDC (Estimated Departure Date)</th>
                <th>Total PDC (rosdCorrect)</th>
            </tr>
        </thead> 
        <tbody></tbody>
    </table>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const filterTypeSelect = document.getElementById('filterType');
        const monthFilter = document.getElementById('monthFilter');
        const quarterFilter = document.getElementById('quarterFilter');
        const yearFilter = document.getElementById('yearFilter');
        const filterButton = document.getElementById('filterButton');
        const resetButton = document.getElementById('resetFilter');
        const refreshButton = document.getElementById('refreshButton');
        const startMonthInput = document.getElementById('startMonth');
        const endMonthInput = document.getElementById('endMonth');
        const startQuarterSelect = document.getElementById('startQuarter');
        const endQuarterSelect = document.getElementById('endQuarter');
        const startYearSelect = document.getElementById('startYear');
        const endYearSelect = document.getElementById('endYear');
        const ctx = document.getElementById('combinedChart').getContext('2d');
        const timeframeDisplay = document.getElementById('timeframeDisplay');
        const fbnTableBody = document.querySelector('#fbnTable tbody');
        const showTableBtn = document.getElementById('showFiltererdPDCTable');
        const filterProjectDashboard = document.getElementById('filterProjectDashboard');
        const showFilterProjectDashboard = document.getElementById('showFilterProjectDashboard');
        const hideFilterProjectDashboard = document.getElementById('hideFilterProjectDashboard');
        const fbnTable = document.getElementById('fbnTable');

        let combinedChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [], datasets: [
                    { label: 'Total PDC (Estimated Departure Date)', data: [], backgroundColor: 'rgba(54, 162, 235, 0.2)', borderColor: 'rgba(54, 162, 235, 1)', borderWidth: 2 },
                    { label: 'Total PDC (rosdCorrect)', data: [], backgroundColor: 'rgba(255, 159, 64, 0.5)', borderColor: 'rgba(255, 159, 64, 1)', borderWidth: 2 }
                ]
            },
            options: {
                responsive: true, plugins: {
                    legend: { position: 'top' }, tooltip: {
                        callbacks: {
                            afterBody: function (context) {
                                const index = context[0].dataIndex;
                                let fbnsList = '';
                                if (context[0].dataset.label.includes('Estimated Departure')) fbnsList = window.fbnsPDC[index] || 'No FBNs';
                                else fbnsList = window.fbnsRosd[index] || 'No FBNs';
                                return 'FBNs: ' + fbnsList;
                            }
                        }
                    }
                }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        window.fbnsPDC = [];
        window.fbnsRosd = [];

        function populateQuarterSelect(select) {
            select.innerHTML = '';
            const currentYear = new Date().getFullYear(); // current year
            const startYear = currentYear - 20;
            const endYear = currentYear + 20;

            for (let y = startYear; y <= endYear; y++) {
                for (let q = 1; q <= 4; q++) {
                    const opt = document.createElement('option');
                    opt.value = `${y}-Q${q}`;
                    opt.textContent = `${y} Q${q}`;
                    select.appendChild(opt);
                }
            }
        }

        function populateYearSelects() {
            startYearSelect.innerHTML = '';
            endYearSelect.innerHTML = '';
            const currentYear = new Date().getFullYear(); // current year
            const startYear = currentYear - 20;
            const endYear = currentYear + 20;

            for (let y = startYear; y <= endYear; y++) {
                const opt1 = document.createElement('option');
                opt1.value = y;
                opt1.textContent = y;
                startYearSelect.appendChild(opt1);

                const opt2 = document.createElement('option');
                opt2.value = y;
                opt2.textContent = y;
                endYearSelect.appendChild(opt2);
            }

            startYearSelect.value = currentYear;
            endYearSelect.value = currentYear; // default to current year
        }


        // call this on load
        populateYearSelects();


        function setDefaultValues() {
            startMonthInput.value = '<?php echo $defaultStart; ?>';
            endMonthInput.value = '<?php echo $defaultEnd; ?>';

            startQuarterSelect.value = '<?php echo $startQuarter; ?>';
            endQuarterSelect.value = '<?php echo $endQuarter; ?>';

            startYearSelect.value = '<?php echo $startYear; ?>';
            endYearSelect.value = '<?php echo $endYear; ?>';
        }

        function updateTimeframeDisplay(type, params) {
            if (type === 'month') timeframeDisplay.textContent = formatMonthYear(params.startMonth) + ' to ' + formatMonthYear(params.endMonth);
            else if (type === 'quarter') timeframeDisplay.textContent = params.startQuarter + ' to ' + params.endQuarter;
            else if (type === 'year') timeframeDisplay.textContent = params.startYear + ' to ' + params.endYear;
        }

        function formatMonthYear(monthYear) {
            const [year, month] = monthYear.split('-');
            const date = new Date(year, parseInt(month) - 1);
            return date.toLocaleString('en-US', { year: 'numeric', month: 'long' });
        }

        function updateFbnTable(labels, totalsEstimated, totalsRosd) {
            fbnTableBody.innerHTML = '';
            let sumEstimated = 0, sumRosd = 0;

            const serverAddress = '<?php echo $serverAddress; ?>';
            const projectName = '<?php echo $projectName; ?>';

            labels.forEach((label, i) => {
                const estValue = totalsEstimated[i] ?? 0;
                const rosdValue = totalsRosd[i] ?? 0;
                sumEstimated += estValue; sumRosd += rosdValue;

                // Main row
                const tr = document.createElement('tr');
                tr.innerHTML = `
            <td>
                <button class="btn btn-sm btn-link p-0 toggleFbn" data-index="${i}">
                    <i class="fa-solid fa-chevron-right fa-xs"></i> ${label}
                </button>
            </td>
            <td>${estValue}</td>
            <td>${rosdValue}</td>
        `;
                fbnTableBody.appendChild(tr);

                // Helper function to create FBN hyperlinks
                function createFbnLinks(fbns) {
                    if (!fbns) return 'No FBNs';
                    return fbns.split(',').map(fbn => {
                        fbn = fbn.trim();
                        return `<a href="http://${serverAddress}/${projectName}/Pages/pdc-table.php?search=${encodeURIComponent(fbn)}" target="_blank">${fbn}</a>`;
                    }).join(', ');
                }

                // FBN row (hidden by default)
                const trFbn = document.createElement('tr');
                trFbn.classList.add('fbnRow');
                trFbn.style.display = 'none';
                trFbn.innerHTML = `
            <td colspan="3" class="text-start ps-3">
                <strong>Estimated PDC:</strong> ${createFbnLinks(window.fbnsPDC[i])}<br>
                <strong>rosd(Correct):</strong> ${createFbnLinks(window.fbnsRosd[i])}
            </td>
        `;
                fbnTableBody.appendChild(trFbn);
            });

            // Total row
            const totalRow = document.createElement('tr');
            totalRow.style.fontWeight = 'bold';
            totalRow.innerHTML = `<td>Total</td><td>${sumEstimated}</td><td>${sumRosd}</td>`;
            fbnTableBody.appendChild(totalRow);

            // Add toggle functionality
            document.querySelectorAll('.toggleFbn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const index = btn.dataset.index;
                    const fbnRow = fbnTableBody.querySelectorAll('.fbnRow')[index];
                    const icon = btn.querySelector('i');
                    if (fbnRow.style.display === 'none') {
                        fbnRow.style.display = '';
                        icon.classList.replace('fa-chevron-right', 'fa-chevron-down');
                    } else {
                        fbnRow.style.display = 'none';
                        icon.classList.replace('fa-chevron-down', 'fa-chevron-right');
                    }
                });
            });
        }

        function fetchData() {
            const filterType = filterTypeSelect.value;
            const poFilter = document.getElementById('poFilter').value;
            let params = new URLSearchParams();
            params.append('filterType', filterType);
            params.append('poFilter', poFilter);

            if (filterType === 'month') {
                const startMonth = startMonthInput.value, endMonth = endMonthInput.value;
                if (!startMonth || !endMonth || startMonth > endMonth) { alert('Please select valid start and end months.'); return; }
                params.append('startMonth', startMonth); params.append('endMonth', endMonth);
                updateTimeframeDisplay(filterType, { startMonth, endMonth });
            } else if (filterType === 'quarter') {
                const startQ = startQuarterSelect.value, endQ = endQuarterSelect.value;
                if (!startQ || !endQ) { alert('Please select valid start and end quarters.'); return; }
                params.append('startQuarter', startQ); params.append('endQuarter', endQ);
                updateTimeframeDisplay(filterType, { startQuarter: startQ, endQuarter: endQ });
            } else if (filterType === 'year') {
                const startYear = startYearSelect.value;
                const endYear = endYearSelect.value;
                if (!startYear || !endYear || startYear > endYear) {
                    alert('Please select a valid year range.');
                    return;
                }
                params.append('startYear', startYear);
                params.append('endYear', endYear);
                updateTimeframeDisplay(filterType, { startYear, endYear });
            }


            fetch('../AJAXphp/get_pdc_project_report_data.php?' + params.toString())
                .then(res => res.json())
                .then(data => {
                    if (data.error) { alert('Error: ' + data.error); return; }
                    combinedChart.data.labels = data.labels;
                    combinedChart.data.datasets[0].data = data.totalPDCProjects;
                    combinedChart.data.datasets[1].data = data.totalQtyRosd;
                    window.fbnsPDC = data.fbnsPDC;
                    window.fbnsRosd = data.fbnsRosd;
                    combinedChart.update();
                    updateFbnTable(data.labels, data.totalPDCProjects, data.totalQtyRosd);
                })
                .catch(err => alert('Error: ' + err.message));
        }

        // Event listeners
        showTableBtn.addEventListener('click', () => { fbnTable.style.display = (fbnTable.style.display === 'none' || fbnTable.style.display === '') ? 'table' : 'none'; showTableBtn.textContent = (fbnTable.style.display === 'none') ? 'Show Table ' : 'Hide Table '; showTableBtn.innerHTML += '<i class="fa-solid fa-table"></i>'; });
        showFilterProjectDashboard.addEventListener('click', () => { filterProjectDashboard.style.display = 'block'; showFilterProjectDashboard.style.display = 'none'; });
        hideFilterProjectDashboard.addEventListener('click', () => { filterProjectDashboard.style.display = 'none'; showFilterProjectDashboard.style.display = 'block'; });
        filterTypeSelect.addEventListener('change', () => { const type = filterTypeSelect.value; monthFilter.classList.toggle('d-none', type !== 'month'); quarterFilter.classList.toggle('d-none', type !== 'quarter'); yearFilter.classList.toggle('d-none', type !== 'year'); });
        filterButton.addEventListener('click', fetchData); refreshButton.addEventListener('click', fetchData);
        resetButton.addEventListener('click', () => {
            filterTypeSelect.value = 'month'; filterTypeSelect.dispatchEvent(new Event('change'));
            startMonthInput.value = '<?php echo $months[0] ?? date("Y-m"); ?>'; // current month
            endMonthInput.value = '<?php echo end($months) ?? date("Y-m"); ?>'; // latest month from DB

            setDefaultValues();
            fetchData();
        });

        populateQuarterSelect(startQuarterSelect);
        populateQuarterSelect(endQuarterSelect);
        populateYearSelects();
        setDefaultValues();
        window.addEventListener('load', fetchData);
    </script>
</body>

</html>
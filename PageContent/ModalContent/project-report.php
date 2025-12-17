<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require("../db_connect.php");
require_once("../status_check.php");

date_default_timezone_set('Australia/Sydney');

$config = include('./../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

?>

<!DOCTYPE HTML>
<html>

<head>
    <link rel="stylesheet" type="text/css" href="./../style.css">

    <style>
        /* Table Row Hover Effect */
        .custom-table tr:hover {
            background-color: #f1f1f1;
            /* Light gray background on hover */
        }
    </style>
</head>

<body>
    <div class="d-flex justify-content-center align-items-center d-none mb-5 background-color shadow-lg py-4 rounded-3"
        id="filterProjectReportForm">
        <div class="row col-8">
            <div class="col-6">
                <label for="startDate" class="fw-bold">Start Date</label>
                <input type="date" id="startDate" class="form-control" placeholder="Start Date">
            </div>
            <div class="col-6">
                <label for="endDate" class="fw-bold">End Date</label>
                <input type="date" id="endDate" class="form-control" placeholder="End Date">
            </div>
            <div class="col-6 mt-3">
                <label for="projectTypeSelection" class="fw-bold">Project Type</label>
                <select name="projectType" id="projectTypeSelection" class="form-select" required="">
                    <option value="" selected>Any</option>
                    <option value="Local">Local</option>
                    <option value="Sitework">Sitework</option>
                    <option value="IOR & Commissioning">IOR & Commissioning</option>
                    <option value="Export">Export</option>
                    <option value="R&D">R&D</option>
                    <option value="Service">Service</option>
                    <option value="PDC - International">PDC - International</option>
                    <option value="PDC - Local">PDC - Local</option>
                </select>
            </div>
            <div class="col-6 mt-3">
                <label for="paymentTermsSelection" class="fw-bold">Payment Terms</label>
                <select name="paymentTerms" id="paymentTermsSelection" class="form-select" required>
                    <option value="" selected>Any</option>
                    <option value="COD">COD</option>
                    <option value="0 Days">0 Days</option>
                    <option value="30 Days">30 Days</option>
                    <option value="60 Days">60 Days</option>
                </select>
            </div>
            <div class="d-flex justify-content-center mt-3">
                <button class="btn btn-danger fw-bold me-1" id="resetFilterBtn">Reset</button>
                <button class="btn btn-secondary fw-bold me-1" id="hideFilterProjectReportForm">Cancel</button>
                <button id="filterButton" class="btn btn-dark fw-bold">Filter</button>
            </div>
        </div>
    </div>
    <div
        class="d-flex flex-column flex-md-row align-items-center justify-content-between p-3 signature-bg-color text-white rounded-3">
        <div class="d-flex flex-column flex-md-row align-items-center">
            <span class="fw-bold">Timeframe: </span>
            <h6 id="startDateText" class="ms-1 fw-bold mb-0 pb-0"></h6>
            <span class="mx-2">-</span> <!-- Add space using margin-x -->
            <h6 id="endDateText" class="fw-bold mb-0 pb-0"></h6>
        </div>

        <div class="d-flex flex-column flex-md-row align-items-center my-2 my-md-0">
            <span class="fw-bold">Project Type: </span>
            <h6 id="projectTypeFilterSelection" class="ms-1 fw-bold mb-0 pb-0"></h6>
        </div>

        <div class="d-flex flex-column flex-md-row align-items-center my-2 my-md-0">
            <span class="fw-bold">Payment Terms: </span>
            <h6 id="paymentTermsFilterSelection" class="ms-1 fw-bold mb-0 pb-0"></h6>
        </div>

        <div class="d-flex flex-column flex-md-row align-items-center mt-2 mt-md-0">
            <button class="btn btn-light btn-sm fw-bold ms-2" id="showFilterProjectReportForm">Edit<i
                    class="fa-regular fa-pen-to-square ms-1"></i></button>
            <button class="btn btn-success btn-sm fw-bold ms-2" id="refreshButton">Refresh <i
                    class="fa-solid fa-arrows-rotate ms-1"></i></button>
            <button class="btn btn-primary btn-sm fw-bold ms-2" id="exportToExcelBtn">Export to Excel</button>
        </div>
    </div>

    <!-- Chart Container -->
    <div class="row d-flex justify-content-between">
        <div class="col-md-8">
            <div class="border border-1 p-3 my-3 rounded-3">
                <div id="chartContainer5" style="height: 420px; width: 100%;"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border border-1 p-3 my-3 rounded-3 text-end">
                <div id="chartContainer6" style="height: 263px; width: 100%;"></div>
                <table class="table mt-4">
                    <tr>
                        <td class="fw-bold">Total Invoiced</td>
                        <td class="fw-bold" id="totalInvoiced"> </td> <!-- Placeholder for total invoiced -->
                    </tr>
                    <tr>
                        <td class="fw-bold">Total Non-Invoiced</td>
                        <td class="fw-bold" id="totalNonInvoiced"> </td> <!-- Placeholder for total non-invoiced -->
                    </tr>
                    <tr>
                        <td class="fw-bold" style="color:#043f9d">Grand Total</td>
                        <td class="fw-bold" id="grandTotal"> </td> <!-- Placeholder for grand total -->
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="row d-flex justify-content-between">
        <div class="col-12 col-md-4 d-flex align-items-center">
            <div class="input-group mb-4 w-100">
                <span class="input-group-text">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <small class="fw-bold ms-1">Search</small>
                </span>
                <input type="search" class="form-control" id="searchProjectDocuments" name="search"
                    placeholder="Search Projects" oninput="filterProjects()">
            </div>
        </div>
        <div class="col-12 col-md-3 mb-4 mb-md-0">
            <div class="d-flex align-items-center justify-content-center">
                <label for="invoiceStatus" class="form-label fw-bold text-nowrap mb-0 pb-0 me-2">Invoice Status</label>
                <select id="invoiceStatus" class="form-select w-100" onchange="filterProjects()">
                    <option value="Any">Any</option>
                    <option value="Invoiced">Invoiced</option>
                    <option value="NonInvoiced">Non-Invoiced</option>
                </select>
            </div>
        </div>
    </div>

    <div class="table-responsive rounded-3 mb-0">
        <table class="table table-bordered custom-table" id="projectDetailsTableTimeframe">

        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Set default start and end dates for the last 12 months
        document.addEventListener("DOMContentLoaded", function () {
            // Function to get date in 'YYYY-MM-DD' format for Sydney timezone
            function getSydneyDate(date) {
                // Convert the date to Sydney timezone
                const options = { timeZone: 'Australia/Sydney', year: 'numeric', month: '2-digit', day: '2-digit' };
                const sydneyDate = new Intl.DateTimeFormat('en-CA', options).format(date);

                // Format the result as 'YYYY-MM-DD'
                return sydneyDate.replace(/(\d{4})-(\d{2})-(\d{2})/, '$1-$2-$3');
            }

            // Get today's date in Sydney timezone
            const today = new Date();
            const sydneyToday = getSydneyDate(today);

            // Calculate last year's date
            const lastYear = new Date(today);
            lastYear.setFullYear(today.getFullYear() - 1); // Subtract 1 year
            const sydneyLastYear = getSydneyDate(lastYear);

            // Set the start and end dates
            document.getElementById("startDate").value = sydneyLastYear;
            document.getElementById("endDate").value = sydneyToday;
        });


        document.getElementById("filterButton").addEventListener("click", function () {
            var startDate = document.getElementById("startDate").value;
            var endDate = document.getElementById("endDate").value;
            var projectType = document.getElementById("projectTypeSelection").value;
            var paymentTerms = document.getElementById("paymentTermsSelection").value;
            var projectTypeText = document.getElementById("projectTypeSelection").options[document.getElementById("projectTypeSelection").selectedIndex].text;
            var paymentTermsText = document.getElementById("paymentTermsSelection").options[document.getElementById("paymentTermsSelection").selectedIndex].text;

            if (startDate && endDate) {
                filterData(startDate, endDate, projectType, paymentTerms);

                function formatDate(dateString) {
                    const date = new Date(dateString);
                    const options = { day: '2-digit', month: 'long', year: 'numeric' };
                    return date.toLocaleDateString('en-GB', options);
                }

                document.getElementById("startDateText").innerText = formatDate(startDate);
                document.getElementById("endDateText").innerText = formatDate(endDate);
                document.getElementById("projectTypeFilterSelection").innerText = projectTypeText
                document.getElementById("paymentTermsFilterSelection").innerText = paymentTermsText
            }
        });

        document.getElementById("refreshButton").addEventListener("click", function () {
            var startDate = document.getElementById("startDate").value;
            var endDate = document.getElementById("endDate").value;
            var projectType = document.getElementById("projectTypeSelection").value;
            var paymentTerms = document.getElementById("paymentTermsSelection").value;
            var projectTypeText = document.getElementById("projectTypeSelection").options[document.getElementById("projectTypeSelection").selectedIndex].text;
            var paymentTermsText = document.getElementById("paymentTermsSelection").options[document.getElementById("paymentTerms").selectedIndex].text;

            if (startDate && endDate) {
                filterData(startDate, endDate, projectType, paymentTerms);

                function formatDate(dateString) {
                    const date = new Date(dateString);
                    const options = { day: '2-digit', month: 'long', year: 'numeric' };
                    return date.toLocaleDateString('en-GB', options);
                }

                document.getElementById("startDateText").innerText = formatDate(startDate);
                document.getElementById("endDateText").innerText = formatDate(endDate);
                document.getElementById("projectTypeFilterSelection").innerText = projectTypeText
                document.getElementById("paymentTermsFilterSelection").innerText = paymentTermsText
            }
        });

        function filterData(startDate, endDate, projectType, paymentTerms) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../AJAXphp/get_project_report_data.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function () {
                if (xhr.status == 200) {
                    var response = JSON.parse(xhr.responseText);
                    updateCharts(response);
                }
            };

            // Encode the projectType to prevent issues with special characters
            var encodedProjectType = encodeURIComponent(projectType);

            xhr.send("startDate=" + startDate + "&endDate=" + endDate + "&projectType=" + encodedProjectType + "&paymentTerms=" + paymentTerms);
        }

        function updateCharts(response) {
            // Log the data to check the values
            console.log(response.dataPoints8);
            console.log(response.dataPoints9);

            // Calculate the total invoiced and non-invoiced
            var totalInvoiced = response.totalInvoiced;
            var totalNonInvoiced = response.totalNonInvoiced;
            var grandTotal = totalInvoiced + totalNonInvoiced;

            // Update the table with the values
            document.getElementById("totalInvoiced").innerText = "$" + totalInvoiced.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById("totalNonInvoiced").innerText = "$" + totalNonInvoiced.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById("grandTotal").innerText = "$" + grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Clear existing table content before adding new data
            var table = document.getElementById("projectDetailsTableTimeframe");
            table.innerHTML = ""; // Clear any previous data
            table.classList.add("table", "table-bordered", "table-hover");
            table.style.borderRadius = "10px";
            table.style.overflow = "hidden"; // Ensure the rounded corners work correctly

            // Create table headers
            // Create table headers
            var headers = document.createElement("tr");

            headers.innerHTML = `
    <th class="fw-bold py-2 text-center sortable" data-sort="project_no" style="background-color: #043f9d; color: white; min-width: 200px; cursor: pointer;">
        Project No. <i class="bg-transparent text-white fa-solid fa-sort fa-sm ms-1"></i>
    </th>
    <th class="fw-bold py-2 text-center sortable" data-sort="project_name" style="background-color: #043f9d; color: white; min-width: 400px; cursor: pointer;">
        Project Name <i class="bg-transparent text-white fa-solid fa-sort fa-sm ms-1"></i>
    </th>
    <th class="fw-bold py-2 text-center sortable" data-sort="payment_terms" style="background-color: #043f9d; color: white; min-width: 200px; cursor: pointer;">
        Payment Terms <i class="bg-transparent text-white fa-solid fa-sort fa-sm ms-1"></i>
    </th>
    <th class="fw-bold py-2 text-center sortable" data-sort="project_type" style="background-color: #043f9d; color: white; min-width: 300px; cursor: pointer;">
        Project Type <i class="bg-transparent text-white fa-solid fa-sort fa-sm ms-1"></i>
    </th>
    <th class="fw-bold py-2 text-center sortable" data-sort="engineers" style="background-color: #043f9d; color: white; min-width: 300px; cursor: pointer;">
        Project Engineer <i class="bg-transparent text-white fa-solid fa-sort fa-sm ms-1"></i>
    </th>
    <th class="fw-bold py-2 text-center sortable" data-sort="description" style="background-color: #043f9d; color: white; cursor: pointer;">
        Description <i class="bg-transparent text-white fa-solid fa-sort fa-sm ms-1"></i>
    </th>
    <th class="fw-bold py-2 text-center sortable" data-sort="delivery_date" style="background-color: #043f9d; color: white; min-width: 200px; cursor: pointer;">
        Delivery Date <i class="bg-transparent text-white fa-solid fa-sort fa-sm ms-1"></i>
    </th>
    <th class="fw-bold py-2 text-center sortable" data-sort="unit_price" style="background-color: #043f9d; color: white; cursor: pointer;">
        Unit Price <i class="bg-transparent text-white fa-solid fa-sort fa-sm ms-1"></i>
    </th>
    <th class="fw-bold py-2 text-center sortable" data-sort="quantity" style="background-color: #043f9d; color: white; min-width: 100px; cursor: pointer;">
        Qty <i class="bg-transparent text-white fa-solid fa-sort fa-sm ms-1"></i>
    </th>
    <th class="fw-bold py-2 text-center sortable" data-sort="sub_total" style="background-color: #043f9d; color: white; cursor: pointer;">
        Sub Total <i class="bg-transparent text-white fa-solid fa-sort fa-sm ms-1"></i>
    </th>
    <th class="fw-bold py-2 text-center sortable" data-sort="invoiced" style="background-color: #043f9d; color: white; min-width: 100px; cursor: pointer;">
        Invoiced <i class="bg-transparent text-white fa-solid fa-sort fa-sm ms-1"></i>
    </th>
    <th class="fw-bold py-2 text-center sortable" data-sort="approved_by" style="background-color: #043f9d; color: white; min-width: 200px; cursor: pointer;">
        Approved By <i class="bg-transparent text-white fa-solid fa-sort fa-sm ms-1"></i>
    </th>
`;
            table.appendChild(headers);

            // Populate the table with project details
            response.projectDetails.forEach(function (project) {
                var invoicedText = project.invoiced == 1 ? "Yes" : "No";
                var invoicedClass = project.invoiced == 1 ? "bg-success bg-opacity-25" : "bg-danger bg-opacity-25";

                // Determine which date to show (Revised takes priority over Estimated)
                let deliveryDate = "N/A";
                let deliveryDateRaw = null;
                let dateType = "";

                if (project.revised_delivery_date) {
                    deliveryDate = new Date(project.revised_delivery_date).toLocaleDateString('en-AU', { day: '2-digit', month: 'long', year: 'numeric' });
                    deliveryDateRaw = project.revised_delivery_date;
                    dateType = "revised";
                } else if (project.date) {
                    deliveryDate = new Date(project.date).toLocaleDateString('en-AU', { day: '2-digit', month: 'long', year: 'numeric' });
                    deliveryDateRaw = project.date;
                    dateType = "estimated";
                }

                var row = document.createElement("tr");
                row.innerHTML = `
        <td class="py-2 text-center">
            <a style="background-color: transparent" href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/project-table.php?search=${project.project_no}" target="_blank">${project.project_no}</a>
        </td>
        <td class="py-2 text-center">${project.project_name}</td>
        <td class="py-2 text-center">${project.payment_terms}</td>
        <td class="py-2 text-center">${project.project_type}</td>
        <td class="py-2 text-center">
            ${project.engineers ? project.engineers : 'N/A'}
        </td>
        <td class="py-2 text-center">${project.description}</td>
        <td class="py-2 text-center delivery-date-cell" 
            data-original-estimated="${project.date || ''}" 
            data-original-revised="${project.revised_delivery_date || ''}"
            data-date-type="${dateType}">
            ${deliveryDate}
            ${dateType === 'revised' ? '<br><small class="text-muted">(Revised)</small>' : ''}
        </td>
        <td class="py-2 text-center">$${parseFloat(project.unit_price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        <td class="py-2 text-center">${project.quantity}</td>
        <td class="py-2 text-center">$${parseFloat(project.sub_total).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        <td class="py-2 text-center ${invoicedClass}">${invoicedText}</td>
        <td class="py-2 text-center">
            ${project.approved_first_name && project.approved_last_name ? project.approved_first_name + ' ' + project.approved_last_name : 'N/A'}
        </td>
    `;
                table.appendChild(row);
            });

            // Sort dataPoints8 and dataPoints9 by date (using label as month-year)
            function parseMonthYear(label) {
                const [monthName, year] = label.split(" ");
                const monthIndex = new Date(`${monthName} 1, 2000`).getMonth(); // Get month index from name
                return new Date(parseInt(year), monthIndex);
            }

            function sortByDate(a, b) {
                const dateA = parseMonthYear(a.label);
                const dateB = parseMonthYear(b.label);
                return dateA - dateB;
            }

            const sortedDataPoints8 = response.dataPoints8.sort(sortByDate).map(point => ({
                label: point.label,
                x: point.x, // If x is necessary, keep it; otherwise, CanvasJS can use label for X.
                y: parseFloat(point.y) // Ensure 'y' is correctly parsed as a number.
            }));

            const sortedDataPoints9 = response.dataPoints9.sort(sortByDate).map(point => ({
                label: point.label,
                x: point.x,  // Same as above, x is kept if required.
                y: parseFloat(point.y)  // Ensure 'y' is correctly parsed as a number.
            }));

            // Debugging: Check the values in the console for correct summing
            console.log("Sorted Data for Invoiced (dataPoints8):", sortedDataPoints8);
            console.log("Sorted Data for Non-Invoiced (dataPoints9):", sortedDataPoints9);

            // Now, use the sorted dataPoints8 and dataPoints9 for the chart.
            var chart5 = new CanvasJS.Chart("chartContainer5", {
                animationEnabled: true,
                theme: "light2",
                axisY: {
                    includeZero: true,
                    prefix: "$",
                },
                legend: {
                    cursor: "pointer",
                    itemclick: toggleDataSeries
                },
                toolTip: {
                    enabled: true,
                    shared: true,
                    content: function (e) {
                        var total = 0;

                        // Calculate the total value for tooltip
                        e.entries.forEach(function (entry) {
                            total += entry.dataPoint.y;
                        });

                        // Start building the tooltip content
                        var content = `<div style="font-weight: bold; margin-bottom: 5px;">${e.entries[0].dataPoint.label}</div>`;

                        // Add each data series with color indicators
                        e.entries.forEach(function (entry) {
                            content += `
                        <div style="display: flex; align-items: center; margin-bottom: 3px;">
                            <span style="display: inline-block; width: 10px; height: 10px; background-color: ${entry.dataSeries.color}; margin-right: 8px; border-radius: 50%;"></span>
                            <span>${entry.dataSeries.name}: </span>
                            <span style="margin-left: auto;">$${entry.dataPoint.y.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                        </div>`;
                        });

                        // Add the total value at the bottom
                        content += `
                    <hr style="margin: 5px 0;">
                    <div style="font-weight: bold; text-align: right;">Total: $${total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>`;

                        return content;
                    }
                },

                data: [
                    {
                        type: "stackedColumn",
                        name: "Invoiced",
                        showInLegend: true,
                        color: "#2980b9",
                        dataPoints: sortedDataPoints8
                    },
                    {
                        type: "stackedColumn",
                        name: "Non-Invoiced",
                        showInLegend: true,
                        color: "#5bc0de",
                        dataPoints: sortedDataPoints9
                    }
                ],
            });

            function showOnlyDataset(datasetNameToShow) {
                chart5.options.data.forEach(ds => {
                    ds.visible = (ds.name === datasetNameToShow);
                });
                chart5.render();
            }

            document.getElementById("invoiceStatus").addEventListener("change", function () {
                const selectedValue = this.value;

                if (selectedValue === "Invoiced") {
                    showOnlyDataset("Invoiced");
                } else if (selectedValue === "NonInvoiced") {
                    showOnlyDataset("Non-Invoiced");
                } else {
                    // Show both if 'Any'
                    chart5.options.data.forEach(ds => ds.visible = true);
                    chart5.render();
                }
            });

            var chart6 = new CanvasJS.Chart("chartContainer6", {
                animationEnabled: true,
                theme: "light2",
                data: [{
                    type: "pie",
                    startAngle: 240,
                    yValueFormatString: "$#,##0.00",
                    indexLabelFontWeight: "bolder",
                    indexLabel: "{y}",
                    showInLegend: true,
                    legendText: "{label}",
                    dataPoints: [
                        { y: response.totalInvoiced, label: "Invoiced", color: "#2980b9" },
                        { y: response.totalNonInvoiced, label: "Non-Invoiced", color: "#5bc0de" }
                    ],
                }]
            });

            chart5.render();
            chart6.render();
        }

        function toggleDataSeries(e) {
            if (typeof (e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
                e.dataSeries.visible = false;
            } else {
                e.dataSeries.visible = true;
            }
            e.chart.render();
        }

        // Trigger the chart and filter function only when the modal is shown
        $('#projectReportModal').on('shown.bs.modal', function () {
            var startDate = document.getElementById("startDate").value;
            var endDate = document.getElementById("endDate").value;
            var projectType = document.getElementById("projectTypeSelection").value;
            var paymentTerms = document.getElementById("paymentTermsSelection").value;
            var projectTypeText = document.getElementById("projectTypeSelection").options[document.getElementById("projectTypeSelection").selectedIndex].text;
            var paymentTermsText = document.getElementById("paymentTermsSelection").options[document.getElementById("paymentTermsSelection").selectedIndex].text;

            if (startDate && endDate) {
                filterData(startDate, endDate, projectType, paymentTerms);

                function formatDate(dateString) {
                    const date = new Date(dateString);
                    const options = { day: '2-digit', month: 'long', year: 'numeric' };
                    return date.toLocaleDateString('en-GB', options);
                }

                document.getElementById("startDateText").innerText = formatDate(startDate);
                document.getElementById("endDateText").innerText = formatDate(endDate);
                document.getElementById("projectTypeFilterSelection").innerText = projectTypeText
                document.getElementById("paymentTermsFilterSelection").innerText = paymentTermsText
            }

        });

        document.addEventListener('DOMContentLoaded', function () {
            const filterProjectReportForm = document.getElementById("filterProjectReportForm");
            const filterButton = document.getElementById("filterButton");
            const showFilterProjectReportForm = document.getElementById("showFilterProjectReportForm");
            const hideFilterProjectReportForm = document.getElementById("hideFilterProjectReportForm");

            showFilterProjectReportForm.addEventListener("click", function () {
                filterProjectReportForm.classList.remove("d-none");
                showFilterProjectReportForm.classList.add("d-none");
            });

            hideFilterProjectReportForm.addEventListener("click", function () {
                filterProjectReportForm.classList.add("d-none");
                showFilterProjectReportForm.classList.remove("d-none");
            });

            filterButton.addEventListener("click", function () {
                filterProjectReportForm.classList.add("d-none");
                showFilterProjectReportForm.classList.remove("d-none");
            });
        });

        updateSortIcons();
    </script>
    <script>
        // Filter function for search and invoice status
        function filterProjects() {
            const query = document.getElementById('searchProjectDocuments').value.toLowerCase();
            const status = document.getElementById('invoiceStatus').value;

            // Get all rows in the table
            const rows = document.querySelectorAll('#projectDetailsTableTimeframe tr');

            // Loop through each row
            rows.forEach(function (row) {
                // Skip the header row (if needed)
                if (row.rowIndex === 0) return;

                // Get all the cells in the row
                const cells = row.getElementsByTagName('td');

                // Get the invoiced status from the 10th column (index 9)
                const rowStatus = cells[10]?.textContent.trim().toLowerCase(); // Invoiced status is in the 9th column (index 8)

                // Check if the row matches the search query and invoice status filter
                let match = false;
                let statusMatch = false;

                // Check invoice status condition
                if (status === 'Any') {
                    statusMatch = true;
                } else if (status === 'Invoiced' && rowStatus === 'yes') {
                    statusMatch = true;
                } else if (status === 'NonInvoiced' && rowStatus === 'no') {
                    statusMatch = true;
                }

                // Check if any cell matches the search query
                for (let i = 0; i < cells.length; i++) {
                    const cellText = cells[i].textContent || cells[i].innerText;
                    if (cellText.toLowerCase().includes(query)) {
                        match = true;
                        break; // Exit loop if any cell matches
                    }
                }

                // Show or hide row based on both search and status filter
                if (match && statusMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Get the reset button
            const resetFilterBtn = document.getElementById('resetFilterBtn');

            // Function to reset the filters and set the default date range
            resetFilterBtn.addEventListener("click", function () {
                // Reset search input
                document.getElementById('searchDocuments').value = '';

                // Reset the invoice status select dropdown to "Any"
                document.getElementById('invoiceStatus').value = 'Any';

                // Reset the project type and payment terms dropdowns to "Any"
                document.getElementById('projectTypeSelection').value = '';
                document.getElementById('paymentTermsSelection').value = '';

                // Reset the table rows to show all (in case any were hidden due to filters)
                const rows = document.querySelectorAll('#projectDetailsTableTimeframe tr');
                rows.forEach(function (row) {
                    if (row.rowIndex > 0) { // Skip header row
                        row.style.display = '';
                    }
                });

                // Set default start and end dates for the last 12 months
                function getSydneyDate(date) {
                    const options = { timeZone: 'Australia/Sydney', year: 'numeric', month: '2-digit', day: '2-digit' };
                    const sydneyDate = new Intl.DateTimeFormat('en-CA', options).format(date);
                    return sydneyDate.replace(/(\d{4})-(\d{2})-(\d{2})/, '$1-$2-$3');
                }

                const today = new Date();
                const sydneyToday = getSydneyDate(today);

                const lastYear = new Date(today);
                lastYear.setFullYear(today.getFullYear() - 1); // Subtract 1 year
                const sydneyLastYear = getSydneyDate(lastYear);

                // Set the start and end dates in the date inputs
                document.getElementById("startDate").value = sydneyLastYear;
                document.getElementById("endDate").value = sydneyToday;

            });
        });
    </script>
    <script>
        document.getElementById('exportToExcelBtn').addEventListener('click', exportToExcel);

        function exportToExcel() {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
            script.onload = function () {
                const wb = XLSX.utils.book_new();

                // Summary data
                const totalInvoiced = document.getElementById('totalInvoiced').innerText;
                const totalNonInvoiced = document.getElementById('totalNonInvoiced').innerText;
                const grandTotal = document.getElementById('grandTotal').innerText;

                const timeframe = document.getElementById('startDateText').innerText + ' to ' + document.getElementById('endDateText').innerText;
                const projectType = document.getElementById('projectTypeFilterSelection').innerText;
                const paymentTerms = document.getElementById('paymentTermsFilterSelection').innerText;

                const summaryData = [
                    ["Project Report Summary"],
                    [""],
                    ["Timeframe:", timeframe],
                    ["Project Type:", projectType],
                    ["Payment Terms:", paymentTerms],
                    [""],
                    ["Total Invoiced:", totalInvoiced],
                    ["Total Non-Invoiced:", totalNonInvoiced],
                    ["Grand Total:", grandTotal],
                    [""],
                ];

                const summaryWs = XLSX.utils.aoa_to_sheet(summaryData);
                XLSX.utils.book_append_sheet(wb, summaryWs, "Summary");

                // Project table
                const table = document.getElementById('projectDetailsTableTimeframe');
                // In the exportToExcel function, update the table data processing:
                const tableData = [];

                // Headers (with combined Delivery Date column)
                const headers = [
                    'Project No.', 'Project Name', 'Payment Terms', 'Project Type',
                    'Project Engineer', 'Description', 'Delivery Date',
                    'Unit Price', 'Qty', 'Sub Total', 'Invoiced', 'Approved By'
                ];
                tableData.push(headers);

                // Rows
                for (let i = 1; i < table.rows.length; i++) {
                    const row = table.rows[i];
                    if (row.style.display === 'none') continue;

                    const rowData = [];
                    const cells = row.cells;

                    for (let j = 0; j < cells.length; j++) {
                        let cellValue;

                        if (j === 0) {
                            // Project No with link
                            const link = cells[j].querySelector('a');
                            cellValue = link ? link.innerText : cells[j].innerText;
                        } else if (j === 6) {
                            // Combined Delivery Date column
                            // Get the raw date values from data attributes for proper sorting/formatting
                            const deliveryCell = cells[j];
                            const dateType = deliveryCell.getAttribute('data-date-type');
                            let rawDate = '';

                            if (dateType === 'revised') {
                                rawDate = deliveryCell.getAttribute('data-original-revised');
                            } else if (dateType === 'estimated') {
                                rawDate = deliveryCell.getAttribute('data-original-estimated');
                            }

                            // Use the formatted display text
                            cellValue = deliveryCell.textContent.trim().replace('(Revised)', '').trim();
                        } else {
                            cellValue = cells[j].innerText.trim();
                        }

                        // Format detection logic remains the same...
                        if (/^\$[\d,]+(\.\d{2})?$/.test(cellValue)) {
                            const numValue = parseFloat(cellValue.replace(/[$,]/g, ""));
                            rowData.push({ v: numValue, t: 'n', z: '$#,##0.00' });
                        }
                        // Handle date format
                        else if (/^\d{1,2} [A-Za-z]+ \d{4}$/.test(cellValue)) {
                            const dateObj = new Date(cellValue);
                            if (!isNaN(dateObj.getTime())) {
                                rowData.push({ v: dateObj, t: 'd', z: 'dd mmmm yyyy' });
                            } else {
                                rowData.push(cellValue);
                            }
                        }
                        else {
                            rowData.push(cellValue);
                        }
                    }

                    tableData.push(rowData);
                }

                // Create sheet with formatting preserved
                const projectWs = XLSX.utils.aoa_to_sheet(tableData);
                XLSX.utils.book_append_sheet(wb, projectWs, "Projects");

                // Save file
                const date = new Date();
                const dateString = date.toISOString().split('T')[0];
                XLSX.writeFile(wb, `Project_Report_${dateString}.xlsx`);
            };
            document.head.appendChild(script);
        }
    </script>

    <script>
        // Sorting functionality
        let currentSort = {
            column: null,
            direction: 'asc' // 'asc' or 'desc'
        };

        function sortTable(column) {
            const table = document.getElementById('projectDetailsTableTimeframe');
            const tbody = table.querySelector('tbody') || table;
            const rows = Array.from(tbody.rows).slice(1); // Skip header row

            // Update sort direction
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }

            // Sort the rows
            rows.sort((a, b) => {
                const columnIndex = getColumnIndex(column);
                let aValue = a.cells[columnIndex].textContent.trim();
                let bValue = b.cells[columnIndex].textContent.trim();

                // Handle numeric values (currency, quantities)
                if (['unit_price', 'quantity', 'sub_total'].includes(column)) {
                    aValue = parseFloat(aValue.replace(/[$,]/g, '')) || 0;
                    bValue = parseFloat(bValue.replace(/[$,]/g, '')) || 0;
                }

                // Handle the new combined delivery_date column
                else if (column === 'delivery_date') {
                    // Get the raw date values from data attributes for proper sorting
                    const aCell = a.cells[columnIndex];
                    const bCell = b.cells[columnIndex];

                    const aDateType = aCell.getAttribute('data-date-type');
                    const bDateType = bCell.getAttribute('data-date-type');

                    let aRawDate = '';
                    let bRawDate = '';

                    // Get the actual date values from data attributes
                    if (aDateType === 'revised') {
                        aRawDate = aCell.getAttribute('data-original-revised');
                    } else if (aDateType === 'estimated') {
                        aRawDate = aCell.getAttribute('data-original-estimated');
                    }

                    if (bDateType === 'revised') {
                        bRawDate = bCell.getAttribute('data-original-revised');
                    } else if (bDateType === 'estimated') {
                        bRawDate = bCell.getAttribute('data-original-estimated');
                    }

                    // Convert to Date objects for comparison
                    aValue = aRawDate ? new Date(aRawDate) : new Date(0); // Use epoch for N/A values
                    bValue = bRawDate ? new Date(bRawDate) : new Date(0); // Use epoch for N/A values
                }

                // Handle boolean values for invoiced
                else if (column === 'invoiced') {
                    aValue = aValue.toLowerCase() === 'yes';
                    bValue = bValue.toLowerCase() === 'yes';
                }

                // Handle other text values
                else {
                    // For text columns, convert to lowercase for case-insensitive sorting
                    aValue = aValue.toLowerCase();
                    bValue = bValue.toLowerCase();
                }

                // Compare values
                let comparison = 0;
                if (aValue < bValue) {
                    comparison = -1;
                } else if (aValue > bValue) {
                    comparison = 1;
                }

                return currentSort.direction === 'desc' ? comparison * -1 : comparison;
            });

            // Reorder the table rows
            rows.forEach(row => tbody.appendChild(row));

            // Update sort icons
            updateSortIcons();
        }

        function getColumnIndex(columnName) {
            const columnMap = {
                'project_no': 0,
                'project_name': 1,
                'payment_terms': 2,
                'project_type': 3,
                'engineers': 4,
                'description': 5,
                'delivery_date': 6,  // Combined column
                'unit_price': 7,
                'quantity': 8,
                'sub_total': 9,
                'invoiced': 10,
                'approved_by': 11
            };
            return columnMap[columnName] || 0;
        }

        function updateSortIcons() {
            // Reset all icons to default sort
            document.querySelectorAll('.sortable i').forEach(icon => {
                icon.className = 'bg-transparent text-white fa-solid fa-sort fa-sm ms-1';
            });

            // Update the current sorted column icon
            if (currentSort.column) {
                const currentHeader = document.querySelector(`.sortable[data-sort="${currentSort.column}"]`);
                if (currentHeader) {
                    const icon = currentHeader.querySelector('i');
                    if (icon) {
                        icon.className = currentSort.direction === 'asc'
                            ? 'bg-transparent text-white fa-solid fa-sort-up fa-sm ms-1'
                            : 'bg-transparent text-white fa-solid fa-sort-down fa-sm ms-1';
                    }
                }
            }
        }

        // Add event listeners for sortable headers
        document.addEventListener('click', function (e) {
            if (e.target.closest('.sortable')) {
                const header = e.target.closest('.sortable');
                const column = header.getAttribute('data-sort');
                sortTable(column);
            }
        });
    </script>
</body>

</html>
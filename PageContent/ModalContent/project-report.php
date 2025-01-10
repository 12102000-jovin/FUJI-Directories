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
                <label for="projectType" class="fw-bold">Project Type</label>
                <select name="projectType" id="projectType" class="form-select" required="">
                    <option value="" selected>Any</option>
                    <option value="Local">Local</option>
                    <option value="Sitework">Sitework</option>
                    <option value="IOR & Commissioning">IOR & Commissioning</option>
                    <option value="Export">Export</option>
                    <option value="R&D">R&D</option>
                    <option value="Service">Service</option>
                </select>
            </div>
            <div class="col-6 mt-3">
                <label for="paymentTerms" class="fw-bold">Payment Terms</label>
                <select name="paymentTerms" id="paymentTerms" class="form-select" required>
                    <option value="" selected>Any</option>
                    <option value="COD">COD</option>
                    <option value="0 Days">0 Days</option>
                    <option value="30 Days">30 Days</option>
                    <option value="60 Days">60 Days</option>
                </select>
            </div>
            <div class="d-flex justify-content-center mt-3">
                <button class="btn btn-secondary me-1" id="hideFilterProjectReportForm">Cancel</button>
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
            var projectType = document.getElementById("projectType").value;
            var paymentTerms = document.getElementById("paymentTerms").value;
            var projectTypeText = document.getElementById("projectType").options[document.getElementById("projectType").selectedIndex].text;
            var paymentTermsText = document.getElementById("paymentTerms").options[document.getElementById("paymentTerms").selectedIndex].text;

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
            var projectType = document.getElementById("projectType").value;
            var paymentTerms = document.getElementById("paymentTerms").value;
            var projectTypeText = document.getElementById("projectType").options[document.getElementById("projectType").selectedIndex].text;
            var paymentTermsText = document.getElementById("paymentTerms").options[document.getElementById("paymentTerms").selectedIndex].text;

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

            // Apply styles for rounded corners and add the .table-hover class for row hover effect
            table.classList.add("table", "table-bordered", "table-hover");
            // Apply styles for rounded corners
            table.style.borderRadius = "10px";
            table.style.overflow = "hidden"; // Ensure the rounded corners work correctly

            // Create table headers with background color and rounded corners
            var headers = document.createElement("tr");
            headers.innerHTML = `
    <th class="fw-bold py-2 text-center" style="background-color: #043f9d; color: white;">Project No.</th>
    <th class="fw-bold py-2 text-center" style="background-color: #043f9d; color: white;">Payment Terms</th>
    <th class="fw-bold py-2 text-center" style="background-color: #043f9d; color: white;">Project Type</th>
    <th class="fw-bold py-2 text-center" style="background-color: #043f9d; color: white;">Description</th>
    <th class="fw-bold py-2 text-center" style="background-color: #043f9d; color: white;">Estimated Delivery Date</th>
    <th class="fw-bold py-2 text-center" style="background-color: #043f9d; color: white;">Unit Price</th>
    <th class="fw-bold py-2 text-center" style="background-color: #043f9d; color: white;">Qty</th>
    <th class="fw-bold py-2 text-center" style="background-color: #043f9d; color: white;">Sub Total</th>
    <th class="fw-bold py-2 text-center" style="background-color: #043f9d; color: white;">Invoiced</th>
`;
            table.appendChild(headers);


            // Populate the table with project details
            response.projectDetails.forEach(function (project) {
                var invoicedText = project.invoiced == 1 ? "Yes" : "No";
                var invoicedClass = project.invoiced == 1 ? "bg-success bg-opacity-25" : "bg-danger bg-opacity-25";

                var row = document.createElement("tr");
                row.innerHTML = `
                <td class="py-2 text-center">
   <a  style="background-color: transparent" href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/project-table.php?search=${project.project_no}" target="_blank">${project.project_no}</a>
</td>

                <td class="py-2 text-center">${project.payment_terms}</td>
                <td class="py-2 text-center">${project.project_type}</td>
        <td class="py-2 text-center">${project.description}</td>
        <td class="py-2 text-center">${new Date(project.date).toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' })}</td>
        <td class="py-2 text-center">$${parseFloat(project.unit_price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        <td class="py-2 text-center">${project.quantity}</td>
        <td class="py-2 text-center">$${parseFloat(project.sub_total).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        <td class="py-2 text-center ${invoicedClass}">${invoicedText}</td>
    `;
                table.appendChild(row);
            });

            const dataPoints8 = response.dataPoints8.map(point => ({
                label: point.label,
                x: point.x,  // If x is necessary, keep it; otherwise, CanvasJS can use label for X.
                y: parseFloat(point.y)  // Convert y to a number using parseFloat().
            }));

            const dataPoints9 = response.dataPoints9.map(point => ({
                label: point.label,
                x: point.x,  // If x is necessary, keep it; otherwise, CanvasJS can use label for X.
                y: parseFloat(point.y)  // Convert y to a number using parseFloat().
            }));

            // Now, use the updated dataPoints8 and dataPoints9 for the chart.
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

                        // Calculate the total value
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
                        dataPoints: dataPoints8
                    },
                    {
                        type: "stackedColumn",
                        name: "Non-Invoiced",
                        showInLegend: true,
                        color: "#5bc0de",
                        dataPoints: dataPoints9
                    }
                ]
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
            var projectType = document.getElementById("projectType").value;
            var paymentTerms = document.getElementById("paymentTerms").value;
            var projectTypeText = document.getElementById("projectType").options[document.getElementById("projectType").selectedIndex].text;
            var paymentTermsText = document.getElementById("paymentTerms").options[document.getElementById("paymentTerms").selectedIndex].text;

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
    </script>
</body>

</html>
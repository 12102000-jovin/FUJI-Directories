<?php
$labels = [];
$startDates = [];
$durations = [];

if (!empty($ganttProjects)) {
    $minDate = null;
    $maxDate = null;

    // Find min and max dates only for "In Progress" projects
    foreach ($ganttProjects as $row) {
        if (
            isset($row["earliest_estimated_date"], $row["latest_estimated_date"], $row["current"]) &&
            $row["current"] === "In Progress"
        ) {
            $start = strtotime($row["earliest_estimated_date"]);
            $end = strtotime($row["latest_estimated_date"]);

            if ($start && $end && $end > $start) {
                if (is_null($minDate) || $start < $minDate) {
                    $minDate = $start;
                }
                if (is_null($maxDate) || $end > $maxDate) {
                    $maxDate = $end;
                }
            }
        }
    }

    // Collect data for only "In Progress" projects
    foreach ($ganttProjects as $row) {
        if (
            isset($row["earliest_estimated_date"], $row["latest_estimated_date"], $row["current"]) &&
            $row["current"] === "In Progress"
        ) {
            $start = strtotime($row["earliest_estimated_date"]);
            $end = strtotime($row["latest_estimated_date"]);

            if ($start && $end && $end > $start) {
                $labels[] = $row["project_name"];
                $startDates[] = date('j M Y', $start);
                $durations[] = round(($end - $start) / (60 * 60 * 24)); // duration in days
            }
        }
    }
}
?>
<canvas id="ganttChart"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const labels = <?= json_encode($labels) ?>;
    const startDates = <?= json_encode($startDates) ?>;
    const durations = <?= json_encode($durations) ?>;

    const minDate = <?= json_encode(date('j M Y', $minDate)) ?>;
    const maxDate = <?= json_encode(date('j M Y', $maxDate)) ?>;

    const ctx = document.getElementById('ganttChart').getContext('2d');

    const dayDiff = (date1, date2) => {
        const d1 = new Date(date1);
        const d2 = new Date(date2);
        return (d1 - d2) / (1000 * 60 * 60 * 24);
    };

    const offsets = startDates.map(d => dayDiff(d, minDate));

    // Format date like "12 Mar 2014"
    function formatDateLabel(value) {
        const date = new Date(minDate);
        date.setDate(date.getDate() + value);
        const options = { day: '2-digit', month: 'short', year: 'numeric' };
        return date.toLocaleDateString('en-GB', options); // en-GB gives day-month-year format
    }

    const ganttChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Offset',
                    data: offsets,
                    backgroundColor: 'rgba(0,0,0,0)', // transparent for offset
                    stack: 'combined'
                },
                {
                    label: 'Duration (Days)',
                    data: durations,
                    backgroundColor: '#007bff',
                    stack: 'combined'
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            if (context.dataset.label === 'Duration (Days)') {
                                const i = context.dataIndex;
                                const startDate = startDates[i];
                                const durationDays = durations[i];
                                const endDateObj = new Date(startDate);
                                endDateObj.setDate(endDateObj.getDate() + durationDays);

                                const options = { day: '2-digit', month: 'short', year: 'numeric' };
                                const endDate = endDateObj.toLocaleDateString('en-GB', options);

                                return `${labels[i]}: ${startDate} --> ${endDate} (${durationDays} days)`;
                            }
                            return null;
                        }
                    }
                },
                legend: { display: false }
            },
            scales: {
                x: {
                    stacked: true,
                    type: 'linear',
                    position: 'bottom',
                    min: 0,
                    max: (new Date(maxDate) - new Date(minDate)) / (1000 * 60 * 60 * 24),
                    title: {
                        display: true,
                        text: 'Date'
                    },
                    ticks: {
                        callback: function (value) {
                            return formatDateLabel(value);
                        }
                    }
                },
                y: {
                    stacked: true,
                    ticks: {
                        autoSkip: false, // Prevent skipping labels
                        font: {
                            size: 12 // You can adjust to make them smaller if needed
                        }
                    }
                }
            }
        }
    });
</script>
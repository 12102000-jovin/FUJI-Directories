<input type="hidden" id="assetNo">

<div class="rounded-3 mb-0" style="overflow-y: hidden;">
    <div class="m-1 rounded-3 bg-info bg-opacity-10 p-3">
        <h4 class="fw-bold">Cable</h4>
        <table class="table table-bordered table-hover mb-0 pb-0">
            <thead class="print-table-head">
                <tr>
                    <th class="py-2 align-middle text-center">Cable No.</th>
                    <th class="py-2 align-middle text-center">Description</th>
                    <th class="py-2 align-middle text-center">Location</th>
                    <th class="py-2 align-middle text-center">Test Frequency</th>
                </tr>
            </thead>
            <tbody id="assetDetailsTbody">
            </tbody>
        </table>
    </div>

    <div class="m-1 rounded-3 bg-info bg-opacity-10 p-3 mt-2">
        <h4 class="fw-bold ">Maintenance</h4>
    </div>

    <div class="m-1 rounded-3 bg-info bg-opacity-10 p-3 mt-2">
        <h4 class="fw-bold ">Service</h4>
    </div>

    <div class="m-1 rounded-3 bg-info bg-opacity-10 p-3 mt-2">
        <h4 class="fw-bold ">Calibration</h4>
    </div>

    <div class="m-1 rounded-3 bg-info bg-opacity-10 p-3 mt-2">
        <h4 class="fw-bold ">Repairs</h4>
    </div>

    <div class="m-1 rounded-3 bg-info bg-opacity-10 p-3 mt-2">
        <h4 class="fw-bold "> Warranty</h4>
    </div>

</div>

<?php require_once("../logout.php") ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    // This is where you define the function to fetch asset details via AJAX
    function fetchAssetDetails(assetNo) {
        $.ajax({
            url: '../AJAXphp/fetch_asset_details.php', // PHP file to fetch asset details
            method: 'POST',
            data: {
                asset_no: assetNo // Send the asset number to the server
            },
            success: function (response) {
                // Update the asset details table with the new data
                $('#assetDetailsTbody').html(response);
            },
            error: function () {
                alert("An error occurred while fetching asset details.");
            }
        });
    }

    // Listen for the modal being shown
    $('#assetDetailsModal').on('shown.bs.modal', function () {
        var assetNo = $("#assetNo").val(); // Get the asset ID from the input field
        console.log("Asset No: ", assetNo);

        // Check if the assetNo has a value before calling the function
        if (assetNo) {
            fetchAssetDetails(assetNo); // Call the function to fetch and display the asset details
        } else {
            console.log("No asset NO provided in the modal input.");
        }
    });
</script>
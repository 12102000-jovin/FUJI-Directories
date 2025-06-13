<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resizable Bootstrap Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2>Resizable Table Example</h2>
    <table class="table table-bordered" id="myTable">
        <thead class="table-dark">
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Phone</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>John</td>
                <td>Doe</td>
                <td>john@example.com john@example.com john@example.com john@example.com john@example.com john@example.com john@example.com john@example.com john@example.com john@example.com john@example.com</td>
                <td>1234567890</td>
            </tr>
            <tr>
                <td>Jane</td>
                <td>Smith</td>
                <td>jane@example.com</td>
                <td>0987654321</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- colResizable plugin -->
<script src="https://cdn.jsdelivr.net/gh/alvaro-prieto/colResizable/colResizable-1.6.min.js"></script>

<script>
$(function() {
    $("#myTable").colResizable({
        liveDrag: true,
        gripInnerHtml:"<div class='grip'></div>", 
        draggingClass:"dragging",
        resizeMode:'fit' // or 'overflow' if you prefer
    });
});
</script>

<style>
/* Just some style for the grip */
.grip {
    background-color: #000;
    height: 100%;
    cursor: col-resize;
}
.dragging .grip {
    background-color: red;
}
</style>

</body>
</html>

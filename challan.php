<?php
include 'db_connect.php';

$query = "SELECT c.id, v.vehicle_number, c.violation_count, c.count, c.status, c.amount, c.challan_date, c.updated_at
          FROM challans c
          JOIN vehicles v ON c.vehicle_id = v.id
          ORDER BY c.updated_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Challan Records</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2 class="page-title">Challan Records</h2>
    <table class="challan-table">
        <tr>
            <th>ID</th>
            <th>Vehicle Number</th>
            <th>Violation Count</th>
            <th>Repeat Count</th>
            <th>Status</th>
            <th>Amount</th>
            <th>Created At</th>
            <th>Updated At</th>
        </tr>
        <?php while($row = mysqli_fetch_assoc($result)) { ?>
        <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= htmlspecialchars($row['vehicle_number']) ?></td>
            <td><?= htmlspecialchars($row['violation_count']) ?></td>
            <td><?= htmlspecialchars($row['count']) ?></td>
            <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
            <td><?= htmlspecialchars($row['amount']) ?></td>
            <td><?= htmlspecialchars($row['challan_date']) ?></td>
            <td><?= htmlspecialchars($row['updated_at']) ?></td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>

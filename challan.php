<?php
include __DIR__ . '/db_connect.php';

// Handle "Mark as Paid" action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['challan_id'])) {
    $challan_id = intval($_POST['challan_id']);
    $stmt = $conn->prepare("UPDATE challans SET status='paid' WHERE id=?");
    $stmt->bind_param("i", $challan_id);
    $stmt->execute();
}

// Fetch challans using only columns present in the current schema
$query = "SELECT c.id, v.vehicle_number, c.violation_count, c.status, c.amount, c.challan_date
          FROM challans c
          JOIN vehicles v ON c.vehicle_id = v.id
          ORDER BY c.challan_date DESC";
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
            <th>Status</th>
            <th>Amount</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
        <?php while($row = mysqli_fetch_assoc($result)) { ?>
        <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= htmlspecialchars($row['vehicle_number']) ?></td>
            <td><?= htmlspecialchars($row['violation_count']) ?></td>
            <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
            <td><?= htmlspecialchars($row['amount']) ?></td>
            <td><?= htmlspecialchars($row['challan_date']) ?></td>
            <td>
                <?php if ($row['status'] === 'unpaid') { ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="challan_id" value="<?= $row['id'] ?>">
                        <button type="submit" class="btn-paid">Mark as Paid</button>
                    </form>
                <?php } else { ?>
                    <span class="paid-label">Paid</span>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>

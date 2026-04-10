<?php
session_start();
include __DIR__ . '/db_connect.php';

// Check admin
$is_admin = isset($_SESSION['gov_user']) && $_SESSION['gov_user'] === true;

// Get vehicle number from search
$vnum = strtoupper(trim($_GET['vehicle_number'] ?? ''));

// ── QUERY ───────────────────────────────────────
if ($is_admin) {
    // Admin → see all challans
    $stmt = $conn->prepare("
        SELECT c.id, v.vehicle_number, v.owner_name, v.contact_details,
               c.violation_count, c.amount, c.status, c.challan_date
        FROM challans c
        JOIN vehicles v ON v.id = c.vehicle_id
        ORDER BY c.challan_date DESC
    ");
} else {
    // User → search by vehicle number
    if ($vnum != '') {
        $stmt = $conn->prepare("
            SELECT c.id, v.vehicle_number, v.owner_name, v.contact_details,
                   c.violation_count, c.amount, c.status, c.challan_date
            FROM challans c
            JOIN vehicles v ON v.id = c.vehicle_id
            WHERE v.vehicle_number = ?
            ORDER BY c.challan_date DESC
        ");
        $stmt->bind_param("s", $vnum);
    } else {
        // No input → show nothing
        $challans = [];
    }
}

// Execute query if exists
if (isset($stmt)) {
    $stmt->execute();
    $result = $stmt->get_result();
    $challans = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Challan Records</title>

<style>
body { background:#f1f5f9; font-family: Arial; }
.container { max-width: 1000px; margin: 30px auto; }

h2 { color:#1e293b; }

input {
    padding:8px;
    border:1px solid #ccc;
    border-radius:6px;
}
button {
    padding:8px 12px;
    background:#3498db;
    color:white;
    border:none;
    border-radius:6px;
    cursor:pointer;
}

table {
    width:100%;
    border-collapse: collapse;
    margin-top:20px;
    background:white;
}

th, td {
    padding:10px;
    border-bottom:1px solid #eee;
}

th {
    background:#1e293b;
    color:white;
}

.badge {
    padding:3px 8px;
    border-radius:10px;
    font-size:12px;
}
.paid { background:#dcfce7; color:#166534; }
.unpaid { background:#fee2e2; color:#991b1b; }
.pending { background:#fef9c3; color:#854d0e; }

.empty {
    margin-top:20px;
    color:#777;
}
</style>
</head>

<body>

<div class="container">

<h2>💳 Challan Records</h2>

<?php if (!$is_admin): ?>
<!-- SEARCH FORM -->
<form method="GET">
    <input type="text" name="vehicle_number" placeholder="Enter Vehicle Number" required>
    <button type="submit">Search</button>
</form>
<?php endif; ?>

<?php if (empty($challans)): ?>
    <div class="empty">No challans found.</div>
<?php else: ?>

<table>
<thead>
<tr>
<th>#</th>
<th>Vehicle</th>
<th>Owner</th>
<th>Contact</th>
<th>Violations</th>
<th>Amount</th>
<th>Status</th>
<th>Date</th>
</tr>
</thead>

<tbody>
<?php foreach ($challans as $i => $c): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($c['vehicle_number']) ?></td>
<td><?= htmlspecialchars($c['owner_name']) ?></td>
<td><?= htmlspecialchars($c['contact_details']) ?></td>
<td><?= $c['violation_count'] ?></td>
<td>₹<?= $c['amount'] ?></td>
<td>
<span class="badge <?= strtolower($c['status']) ?>">
<?= $c['status'] ?>
</span>
</td>
<td><?= $c['challan_date'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php endif; ?>

</div>

</body>
</html>

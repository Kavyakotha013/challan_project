<?php
// ── If this file is requested directly via HTTP, render the challan view ──────
if (php_sapi_name() !== 'cli' && !defined('CHALLAN_INCLUDED')) {
    define('CHALLAN_INCLUDED', true);
    session_start();

    include __DIR__ . '/db_connect.php';

    $is_admin = isset($_SESSION['gov_user']) && $_SESSION['gov_user'] === true;

    // Build query: admins see all challans; logged-in vehicle owners see only theirs
    if ($is_admin) {
        $stmt = $conn->prepare("
            SELECT c.id, v.vehicle_number, v.owner_name, v.contact_details,
                   c.violation_count, c.amount, c.status, c.challan_date
            FROM challans c
            JOIN vehicles v ON v.id = c.vehicle_id
            ORDER BY c.challan_date DESC
        ");
        $stmt->execute();
    } else {
        // Non-admin: must be logged in with a vehicle_number in session
        if (!isset($_SESSION['vehicle_number'])) {
            header("Location: verify_challan.php");
            exit();
        }
        $vnum = $_SESSION['vehicle_number'];
        $stmt = $conn->prepare("
            SELECT c.id, v.vehicle_number, v.owner_name, v.contact_details,
                   c.violation_count, c.amount, c.status, c.challan_date
            FROM challans c
            JOIN vehicles v ON v.id = c.vehicle_id
            WHERE v.vehicle_number = ?
            ORDER BY c.challan_date DESC
        ");
        $stmt->bind_param("s", $vnum);
        $stmt->execute();
    }

    $result   = $stmt->get_result();
    $challans = $result->fetch_all(MYSQLI_ASSOC);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Challan Records – Pollution Monitoring Portal</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { background: #f1f5f9; }
    .topbar {
      background: #1e293b;
      color: #e2e8f0;
      padding: 12px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .topbar .brand { font-size: 1.1rem; font-weight: 700; color: #38bdf8; }
    .topbar a {
      color: #94a3b8;
      text-decoration: none;
      font-size: 0.88rem;
      margin-left: 16px;
    }
    .topbar a:hover { color: #e2e8f0; }
    .page { max-width: 1100px; margin: 32px auto; padding: 0 20px 48px; }
    h2 { margin-bottom: 20px; color: #1e293b; }
    table { width: 100%; border-collapse: collapse; background: #fff;
            border-radius: 10px; overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
    thead th { background: #1e293b; color: #94a3b8; text-transform: uppercase;
               font-size: 0.72rem; letter-spacing: .06em; padding: 12px 16px;
               text-align: left; }
    tbody td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9;
               color: #334155; font-size: 0.88rem; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover { background: #f8fafc; }
    .badge {
      display: inline-block; padding: 3px 10px; border-radius: 20px;
      font-size: 0.75rem; font-weight: 600;
    }
    .badge.paid    { background: #dcfce7; color: #166534; }
    .badge.unpaid  { background: #fee2e2; color: #991b1b; }
    .badge.pending { background: #fef9c3; color: #854d0e; }
    .empty { text-align: center; padding: 48px; color: #94a3b8; font-size: 0.95rem; }
    .back-link { display: inline-block; margin-bottom: 18px; color: #3498db;
                 text-decoration: none; font-size: 0.88rem; }
    .back-link:hover { text-decoration: underline; }
  </style>
</head>
<body>

<div class="topbar">
  <span class="brand">🌿 Pollution Monitoring Portal</span>
  <div>
    <?php if ($is_admin): ?>
      <a href="dashboard.php">📊 Dashboard</a>
      <a href="register.php">📋 Vehicles</a>
      <a href="logout.php">⏻ Logout</a>
    <?php else: ?>
      <a href="index.html">🏠 Home</a>
      <a href="logout.php">⏻ Logout</a>
    <?php endif; ?>
  </div>
</div>

<div class="page">
  <a href="<?= $is_admin ? 'dashboard.php' : 'index.html' ?>" class="back-link">← Back</a>
  <h2>💳 Challan Records<?php if (!$is_admin && isset($vnum)): ?> — <?= htmlspecialchars($vnum) ?><?php endif; ?></h2>

  <?php if (empty($challans)): ?>
    <div class="empty">✅ No challans found<?php if (!$is_admin && isset($vnum)): ?> for vehicle <strong><?= htmlspecialchars($vnum) ?></strong><?php endif; ?>.</div>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Vehicle No.</th>
          <th>Owner</th>
          <th>Contact</th>
          <th>Violations</th>
          <th>Amount (₹)</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($challans as $i => $c): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= htmlspecialchars($c['vehicle_number']) ?></td>
          <td><?= htmlspecialchars($c['owner_name']) ?></td>
          <td><?= htmlspecialchars($c['contact_details']) ?></td>
          <td><?= (int)$c['violation_count'] ?></td>
          <td><?= number_format((float)$c['amount'], 2) ?></td>
          <td><span class="badge <?= strtolower($c['status']) ?>"><?= htmlspecialchars($c['status']) ?></span></td>
          <td><?= htmlspecialchars($c['challan_date']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

</body>
</html>
<?php
    exit();
}

// ── Library mode: included by violation.php ───────────────────────────────────
if (!isset($conn)) {
    include __DIR__ . '/db_connect.php';
}

function generateChallan($vehicle_id, $sensor_code) {
    global $conn;

    // Step 1: Fetch violation count before reset
    $stmt = $conn->prepare("SELECT violation_count FROM violations WHERE vehicle_id=?");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $violation_count = $row['violation_count'] ?? 0;

    // Step 2: Calculate challan amount (₹250 per violation)
    $amount = $violation_count * 250;

    // Step 3: Insert challan record
    $stmt2 = $conn->prepare("
        INSERT INTO challans (vehicle_id, sensor_code, violation_count, amount, status, challan_date)
        VALUES (?, ?, ?, ?, 'unpaid', NOW())
    ");
    $stmt2->bind_param("isid", $vehicle_id, $sensor_code, $violation_count, $amount);

    if (!$stmt2->execute()) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to create challan: " . $stmt2->error
        ]);
        return;
    }

    // Step 4: Reset violations for this vehicle
    $resetStmt = $conn->prepare("
        UPDATE violations 
        SET violation_count = 0, min_count = 0, violation_date = NOW() 
        WHERE vehicle_id = ?
    ");
    $resetStmt->bind_param("i", $vehicle_id);
    $resetStmt->execute();

    // Step 5: Response
    echo json_encode([
        "status" => "success",
        "message" => "Challan generated",
        "vehicle_id" => $vehicle_id,
        "sensor_code" => $sensor_code,
        "violation_count" => $violation_count,
        "amount" => $amount
    ]);
}
?>

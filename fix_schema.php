<?php
include __DIR__ . '/db_connect.php';

$messages = [];
$errors = [];

// Helper: check if a column exists in a table
function columnExists($conn, $table, $column) {
    $result = $conn->query(
        "SELECT COUNT(*) AS cnt
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = '" . $conn->real_escape_string($table)  . "'
           AND COLUMN_NAME  = '" . $conn->real_escape_string($column) . "'"
    );
    if (!$result) return false;
    $row = $result->fetch_assoc();
    return (int)$row['cnt'] > 0;
}

// Helper: check if a table exists
function tableExists($conn, $table) {
    $result = $conn->query(
        "SELECT COUNT(*) AS cnt
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = '" . $conn->real_escape_string($table) . "'"
    );
    if (!$result) return false;
    $row = $result->fetch_assoc();
    return (int)$row['cnt'] > 0;
}

// Helper: run an ALTER/CREATE and record the outcome
function runSQL($conn, $sql, $successMsg, &$messages, &$errors) {
    if ($conn->query($sql)) {
        $messages[] = "✅ " . $successMsg;
    } else {
        $errors[] = "❌ Failed — " . $successMsg . ": " . $conn->error;
    }
}

// ── 1. challans.violation_count ───────────────────────────────────────────────
if (!columnExists($conn, 'challans', 'violation_count')) {
    runSQL(
        $conn,
        "ALTER TABLE challans ADD COLUMN violation_count INT NOT NULL DEFAULT 0",
        "Added column <code>violation_count INT NOT NULL DEFAULT 0</code> to <code>challans</code>",
        $messages, $errors
    );
} else {
    $messages[] = "ℹ️ Column <code>violation_count</code> already exists in <code>challans</code> — skipped.";
}

// ── 2. challans.count ─────────────────────────────────────────────────────────
if (!columnExists($conn, 'challans', 'count')) {
    runSQL(
        $conn,
        "ALTER TABLE challans ADD COLUMN `count` INT NOT NULL DEFAULT 1",
        "Added column <code>count INT NOT NULL DEFAULT 1</code> to <code>challans</code>",
        $messages, $errors
    );
} else {
    $messages[] = "ℹ️ Column <code>count</code> already exists in <code>challans</code> — skipped.";
}

// ── 3. challans.updated_at ────────────────────────────────────────────────────
if (!columnExists($conn, 'challans', 'updated_at')) {
    runSQL(
        $conn,
        "ALTER TABLE challans ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        "Added column <code>updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP</code> to <code>challans</code>",
        $messages, $errors
    );
} else {
    $messages[] = "ℹ️ Column <code>updated_at</code> already exists in <code>challans</code> — skipped.";
}

// ── 4. alerts table ───────────────────────────────────────────────────────────
if (!tableExists($conn, 'alerts')) {
    runSQL(
        $conn,
        "CREATE TABLE alerts (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            vehicle_id   INT NOT NULL,
            alert_type   ENUM('warning','critical') NOT NULL,
            pollution_value FLOAT NOT NULL,
            alert_date   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status       ENUM('active','resolved') NOT NULL DEFAULT 'active',
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
        )",
        "Created table <code>alerts</code>",
        $messages, $errors
    );
} else {
    $messages[] = "ℹ️ Table <code>alerts</code> already exists — skipped.";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Schema</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
            background: #f4f6f8;
            color: #2c3e50;
        }
        h1 {
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 10px;
        }
        .card {
            background: #fff;
            border-radius: 8px;
            padding: 20px 24px;
            margin-top: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
        .card h2 {
            margin-top: 0;
            font-size: 1.1rem;
        }
        ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        ul li {
            padding: 6px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.95rem;
        }
        ul li:last-child {
            border-bottom: none;
        }
        .success { color: #27ae60; }
        .error   { color: #e74c3c; }
        .info    { color: #2980b9; }
        .summary {
            margin-top: 20px;
            padding: 12px 16px;
            border-radius: 6px;
            font-weight: bold;
        }
        .summary.ok  { background: #eafaf1; color: #27ae60; border: 1px solid #a9dfbf; }
        .summary.err { background: #fdedec; color: #e74c3c; border: 1px solid #f5b7b1; }
    </style>
</head>
<body>
    <h1>🔧 Schema Fix — Challan Project</h1>

    <?php if (!empty($messages)): ?>
    <div class="card">
        <h2>Results</h2>
        <ul>
            <?php foreach ($messages as $msg): ?>
                <li><?= $msg ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="card">
        <h2 class="error">Errors</h2>
        <ul>
            <?php foreach ($errors as $err): ?>
                <li class="error"><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="summary <?= empty($errors) ? 'ok' : 'err' ?>">
        <?= empty($errors)
            ? '✅ Schema check complete — no errors.'
            : '❌ Schema check finished with ' . count($errors) . ' error(s). See above for details.'
        ?>
    </div>
</body>
</html>

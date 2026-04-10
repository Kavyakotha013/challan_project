<?php
/**
 * verify_challan.php
 *
 * Public challan lookup with OTP verification.
 * Flow:
 *   Step 1 – User submits vehicle number + phone number.
 *             System looks up the vehicle, generates a 6-digit OTP,
 *             stores it in the session (with 5-minute expiry), and
 *             sends it to the registered email address.
 *   Step 2 – User enters the OTP.
 *             On success the vehicle_number is stored in the session
 *             and the user is redirected to challan.php.
 */

session_start();
include __DIR__ . '/db_connect.php';

// ── Helpers ───────────────────────────────────────────────────────────────────

function renderPage(string $step, string $error = '', array $data = []): void {
    $vehicle_number = htmlspecialchars($data['vehicle_number'] ?? '');
    $phone_number   = htmlspecialchars($data['phone_number']   ?? '');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Challan – Pollution Monitoring Portal</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.10);
      padding: 36px 32px;
      width: 100%;
      max-width: 420px;
    }
    .card h2 { margin-bottom: 6px; color: #1e293b; font-size: 1.3rem; }
    .card .sub { color: #64748b; font-size: 0.88rem; margin-bottom: 24px; }
    label { display: block; font-size: 0.85rem; color: #475569; margin-bottom: 4px; margin-top: 14px; font-weight: 600; }
    input[type="text"], input[type="tel"], input[type="number"] {
      width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1;
      border-radius: 8px; font-size: 0.92rem; color: #1e293b;
      outline: none; transition: border-color 0.2s; box-sizing: border-box;
    }
    input:focus { border-color: #3498db; }
    .btn {
      display: block; width: 100%; margin-top: 22px;
      background: #3498db; color: #fff; border: none;
      padding: 11px; border-radius: 8px; font-size: 0.95rem;
      cursor: pointer; transition: background 0.2s;
    }
    .btn:hover { background: #2980b9; }
    .error {
      background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;
      border-radius: 8px; padding: 10px 14px; font-size: 0.85rem; margin-bottom: 16px;
    }
    .info {
      background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd;
      border-radius: 8px; padding: 10px 14px; font-size: 0.85rem; margin-bottom: 16px;
    }
    .back { display: block; text-align: center; margin-top: 18px; color: #64748b; font-size: 0.82rem; text-decoration: none; }
    .back:hover { color: #3498db; }
    .otp-hint { font-size: 0.78rem; color: #94a3b8; margin-top: 6px; }
  </style>
</head>
<body>
<div class="card">
  <h2>🔍 Challan Lookup</h2>

  <?php if ($step === 'request'): ?>
    <p class="sub">Enter your vehicle number and registered phone number to receive a verification code.</p>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" action="verify_challan.php">
      <input type="hidden" name="action" value="request_otp">
      <label>Vehicle Number <span style="color:#ef4444;">*</span></label>
      <input type="text" name="vehicle_number" placeholder="e.g. MH12AB1234"
             value="<?= $vehicle_number ?>" required autocomplete="off">
      <label>Phone Number <span style="color:#ef4444;">*</span></label>
      <input type="tel" name="phone_number" placeholder="e.g. 9876543210"
             value="<?= $phone_number ?>" required autocomplete="off">
      <button type="submit" class="btn">Send OTP</button>
    </form>

  <?php elseif ($step === 'verify'): ?>
    <p class="sub">A 6-digit OTP has been sent to the email address registered with your vehicle. It expires in 5 minutes.</p>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="info">OTP sent for vehicle <strong><?= $vehicle_number ?></strong>.</div>
    <form method="POST" action="verify_challan.php">
      <input type="hidden" name="action" value="verify_otp">
      <input type="hidden" name="vehicle_number" value="<?= $vehicle_number ?>">
      <label>Enter OTP <span style="color:#ef4444;">*</span></label>
      <input type="number" name="otp" placeholder="6-digit code" required
             min="100000" max="999999" autocomplete="one-time-code">
      <p class="otp-hint">Didn't receive it? <a href="verify_challan.php">Start over</a>.</p>
      <button type="submit" class="btn">Verify &amp; View Challans</button>
    </form>
  <?php endif; ?>

  <a href="index.html" class="back">← Back to Home</a>
</div>
</body>
</html>
    <?php
}

// ── OTP sender (email via PHP mail()) ─────────────────────────────────────────

function sendOtpEmail(string $to_email, string $otp, string $vehicle_number): bool {
    $subject = "Your Challan Lookup OTP – Pollution Monitoring Portal";
    $body    = "Hello,\n\n"
             . "Your OTP for challan lookup of vehicle {$vehicle_number} is:\n\n"
             . "    {$otp}\n\n"
             . "This code is valid for 5 minutes. Do not share it with anyone.\n\n"
             . "– Pollution Monitoring Portal";
    $headers = "From: noreply@pollution-monitor.local\r\n"
             . "X-Mailer: PHP/" . phpversion();
    return mail($to_email, $subject, $body, $headers);
}

// ── Request handling ──────────────────────────────────────────────────────────

$action = $_POST['action'] ?? '';

// ── Action: request OTP ───────────────────────────────────────────────────────
if ($action === 'request_otp') {
    $vehicle_number = strtoupper(trim($_POST['vehicle_number'] ?? ''));
    $phone_number   = trim($_POST['phone_number'] ?? '');

    if ($vehicle_number === '' || $phone_number === '') {
        renderPage('request', 'Both vehicle number and phone number are required.',
                   compact('vehicle_number', 'phone_number'));
        exit();
    }

    // Look up vehicle by number AND contact_details (phone)
    $stmt = $conn->prepare("
        SELECT id, owner_name, owner_email, contact_details
        FROM vehicles
        WHERE vehicle_number = ? AND contact_details = ?
    ");
    $stmt->bind_param("ss", $vehicle_number, $phone_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        renderPage('request',
                   'No vehicle found matching that number and phone. Please check your details.',
                   compact('vehicle_number', 'phone_number'));
        exit();
    }

    $vehicle = $result->fetch_assoc();

    // Generate 6-digit OTP
    $otp     = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expires = time() + 300; // 5 minutes

    // Store in session
    $_SESSION['otp_data'] = [
        'otp'            => $otp,
        'expires'        => $expires,
        'vehicle_number' => $vehicle_number,
        'phone_number'   => $phone_number,
    ];

    // Send OTP via email
    sendOtpEmail($vehicle['owner_email'], $otp, $vehicle_number);

    renderPage('verify', '', compact('vehicle_number', 'phone_number'));
    exit();
}

// ── Action: verify OTP ────────────────────────────────────────────────────────
if ($action === 'verify_otp') {
    $entered_otp    = trim($_POST['otp'] ?? '');
    $vehicle_number = strtoupper(trim($_POST['vehicle_number'] ?? ''));

    $otp_data = $_SESSION['otp_data'] ?? null;

    if (!$otp_data) {
        renderPage('request', 'Session expired. Please start over.');
        exit();
    }

    // Check expiry
    if (time() > $otp_data['expires']) {
        unset($_SESSION['otp_data']);
        renderPage('request', 'OTP has expired. Please request a new one.');
        exit();
    }

    // Check vehicle number matches
    if ($vehicle_number !== $otp_data['vehicle_number']) {
        renderPage('verify', 'Vehicle number mismatch. Please start over.',
                   ['vehicle_number' => $otp_data['vehicle_number']]);
        exit();
    }

    // Check OTP
    if ($entered_otp !== $otp_data['otp']) {
        renderPage('verify', 'Incorrect OTP. Please try again.',
                   ['vehicle_number' => $vehicle_number]);
        exit();
    }

    // ✅ OTP correct — store vehicle in session and redirect
    unset($_SESSION['otp_data']);
    $_SESSION['vehicle_number'] = $vehicle_number;

    header("Location: challan.php");
    exit();
}

// ── Default: show the request form ───────────────────────────────────────────
renderPage('request');

<?php
session_start();
include __DIR__ . '/db_connect.php';

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ───────────────── EMAIL FUNCTION ─────────────────
function sendOtpEmail($to_email, $otp, $vehicle_number) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your_email@gmail.com';
        $mail->Password   = 'your_app_password';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('your_email@gmail.com', 'Pollution Portal');
        $mail->addAddress($to_email);

        $mail->Subject = 'Your OTP for Challan Lookup';
        $mail->Body    = "OTP for vehicle $vehicle_number is: $otp (Valid 5 mins)";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ───────────────── SMS FUNCTION ─────────────────
function sendOtpSMS($phone, $otp) {
    $apiKey = "YOUR_FAST2SMS_API_KEY";

    $data = [
        "sender_id" => "FSTSMS",
        "message"   => "Your OTP is $otp",
        "language"  => "english",
        "route"     => "q",
        "numbers"   => $phone,
    ];

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://www.fast2sms.com/dev/bulk",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            "authorization: $apiKey",
            "content-type: application/json"
        ],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}

// ───────────────── UI FUNCTION ─────────────────
function renderPage($step, $error = '', $vehicle_number = '') {
?>
<!DOCTYPE html>
<html>
<head>
<title>Verify Challan</title>
</head>
<body>

<h2>Challan Verification</h2>

<?php if ($error): ?>
<p style="color:red;"><?php echo $error; ?></p>
<?php endif; ?>

<?php if ($step == 'request'): ?>
<form method="POST">
    <input type="hidden" name="action" value="request_otp">
    Vehicle Number: <input type="text" name="vehicle_number" required><br><br>
    Phone Number: <input type="text" name="phone_number" required><br><br>
    <button type="submit">Send OTP</button>
</form>

<?php elseif ($step == 'verify'): ?>
<form method="POST">
    <input type="hidden" name="action" value="verify_otp">
    <input type="hidden" name="vehicle_number" value="<?php echo $vehicle_number; ?>">
    Enter OTP: <input type="text" name="otp" required><br><br>
    <button type="submit">Verify</button>
</form>
<?php endif; ?>

</body>
</html>
<?php
}

// ───────────────── LOGIC ─────────────────
$action = $_POST['action'] ?? '';

// STEP 1: REQUEST OTP
if ($action === 'request_otp') {

    $vehicle_number = strtoupper(trim($_POST['vehicle_number']));
    $phone_number   = trim($_POST['phone_number']);

    $stmt = $conn->prepare("
        SELECT owner_email 
        FROM vehicles 
        WHERE vehicle_number=? AND contact_details=?
    ");
    $stmt->bind_param("ss", $vehicle_number, $phone_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        renderPage('request', 'Invalid vehicle or phone');
        exit();
    }

    $row = $result->fetch_assoc();

    $otp = rand(100000, 999999);

    $_SESSION['otp'] = $otp;
    $_SESSION['vehicle_number'] = $vehicle_number;
    $_SESSION['expires'] = time() + 300;

    // Send Email
    $emailStatus = sendOtpEmail($row['owner_email'], $otp, $vehicle_number);

    // Send SMS
    $smsStatus = sendOtpSMS($phone_number, $otp);

    if (!$emailStatus) {
        renderPage('request', 'Email failed to send!');
        exit();
    }

    renderPage('verify', '', $vehicle_number);
    exit();
}

// STEP 2: VERIFY OTP
if ($action === 'verify_otp') {

    $entered_otp = $_POST['otp'];

    if (!isset($_SESSION['otp'])) {
        renderPage('request', 'Session expired');
        exit();
    }

    if (time() > $_SESSION['expires']) {
        renderPage('request', 'OTP expired');
        exit();
    }

    if ($entered_otp != $_SESSION['otp']) {
        renderPage('verify', 'Wrong OTP', $_SESSION['vehicle_number']);
        exit();
    }

    // SUCCESS
    unset($_SESSION['otp']);
    header("Location: challan.php");
    exit();
}

// DEFAULT
renderPage('request');
?>

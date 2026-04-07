<?php
include __DIR__ . '/db_connect.php';

// Fetch challans
$query = "SELECT c.id, v.vehicle_number, c.count, c.status, c.amount, c.challan_date, c.updated_at
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
    <style>
        .challan-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .challan-table th,
        .challan-table td {
            border: 1px solid #ddd;
            padding: 10px 14px;
            text-align: left;
            vertical-align: middle;
        }
        .challan-table th {
            background: #2c3e50;
            color: #fff;
        }
        .challan-table tr:nth-child(even) {
            background: #f4f6f8;
        }
        .page-title {
            text-align: center;
            margin: 24px 0 10px;
            color: #2c3e50;
        }
        .payment-form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .payment-form input[type="number"] {
            width: 110px;
            padding: 6px 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }
        .btn-pay {
            background-color: #3498db;
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .btn-pay:hover {
            background-color: #2980b9;
        }
        .count-badge {
            display: inline-block;
            background: #e74c3c;
            color: #fff;
            border-radius: 12px;
            padding: 2px 10px;
            font-size: 13px;
            font-weight: 600;
        }
        .paid-label {
            color: #27ae60;
            font-weight: bold;
        }
        .hint {
            font-size: 11px;
            color: #888;
            display: block;
            margin-top: 3px;
        }
        #payment-msg {
            max-width: 900px;
            margin: 10px auto 0;
        }
    </style>
</head>
<body>
    <h2 class="page-title">Challan Records</h2>

    <div id="payment-msg"></div>

    <table class="challan-table">
        <tr>
            <th>ID</th>
            <th>Vehicle Number</th>
            <th>Count</th>
            <th>Status</th>
            <th>Amount (₹)</th>
            <th>Created At</th>
            <th>Updated At</th>
            <th>Payment</th>
        </tr>
        <?php while($row = mysqli_fetch_assoc($result)) { ?>
        <tr id="row-<?= intval($row['id']) ?>">
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= htmlspecialchars($row['vehicle_number']) ?></td>
            <td>
                <span class="count-badge" id="count-<?= intval($row['id']) ?>">
                    <?= htmlspecialchars($row['count']) ?>
                </span>
            </td>
            <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
            <td>₹<?= htmlspecialchars(number_format($row['amount'], 2)) ?></td>
            <td><?= htmlspecialchars($row['challan_date']) ?></td>
            <td><?= htmlspecialchars($row['updated_at']) ?></td>
            <td>
                <?php if ($row['status'] === 'unpaid') { ?>
                    <form class="payment-form" onsubmit="processPayment(event, <?= intval($row['id']) ?>)">
                        <div>
                            <input
                                type="number"
                                name="payment_amount"
                                id="amount-<?= intval($row['id']) ?>"
                                min="250"
                                step="250"
                                placeholder="₹250, 500…"
                                required
                            >
                            <span class="hint">₹250 per count</span>
                        </div>
                        <button type="submit" class="btn-pay">Pay</button>
                    </form>
                <?php } else { ?>
                    <span class="paid-label">Paid</span>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
    </table>

    <script>
    function processPayment(event, challanId) {
        event.preventDefault();
        const form = event.target;
        const amountInput = document.getElementById('amount-' + challanId);
        const paymentAmount = amountInput.value;
        const msgBox = document.getElementById('payment-msg');

        const formData = new FormData();
        formData.append('challan_id', challanId);
        formData.append('payment_amount', paymentAmount);

        fetch('process_payment.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                msgBox.innerHTML = '<div class="message success">' + data.message + '</div>';

                if (data.remaining_count !== undefined) {
                    // Update count badge in-place
                    document.getElementById('count-' + challanId).textContent = data.remaining_count;
                    amountInput.value = '';
                } else {
                    // Challan deleted — remove the row
                    const row = document.getElementById('row-' + challanId);
                    if (row) row.remove();
                }
            } else {
                msgBox.innerHTML = '<div class="message error">' + data.message + '</div>';
            }

            // Auto-clear message after 4 seconds
            setTimeout(() => { msgBox.innerHTML = ''; }, 4000);
        })
        .catch(() => {
            msgBox.innerHTML = '<div class="message error">An unexpected error occurred. Please try again.</div>';
        });
    }
    </script>
</body>
</html>

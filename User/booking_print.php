<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/user_auth.php';

$user_id = $_SESSION['uid'];

$bid = trim($_GET['bid'] ?? '');

if ($bid === '' || !ctype_digit($bid)) {
    die("Invalid Booking ID");
}

$stmt = $conn->prepare("
    SELECT 
        b.bid,
        b.uid,
        b.date_booked,
        b.time_start,
        b.time_end,
        b.status,
        b.payment_status,
        b.transaction_ref,
        b.purpose,
        b.approve_date,
        b.created_at,

        u.username,
        u.email,
        u.phone_num,

        v.vname,
        v.max_cap,
        v.deposit,
        vc.category,

        a.admin_name,

        i.ins_id,
        i.ins_status,
        i.damage_desc,
        i.damage_cost,
        i.penalty,
        i.inspected_at,

        s.staff_name AS inspector_name,

        r.rid,
        r.final_deduct,
        r.refund_status,
        r.penalty_status,
        r.created_at AS report_created_at

    FROM booking b

    JOIN user u
        ON b.uid = u.uid

    JOIN venue v
        ON b.vid = v.vid 

    JOIN vcategory vc
        ON v.vcid = vc.vcid 

    LEFT JOIN admin a
        ON b.aid = a.aid 

    LEFT JOIN inspection i
        ON b.bid = i.bid

    LEFT JOIN staff s
        ON i.sid = s.sid

    LEFT JOIN report r
        ON i.ins_id = r.ins_id

    WHERE b.bid = ?
      AND b.uid = ?

    ORDER BY i.ins_id DESC, r.rid DESC
    LIMIT 1
");

$stmt->bind_param("is", $bid, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$record = $result->fetch_assoc();

if (!$record) {
    die("Booking not found or access denied.");
}

function safeText($value) {
    if ($value === null || $value === '') {
        return '-';
    }

    return htmlspecialchars((string)$value);
}

function safeDate($value, $format = "d M Y") {
    if (empty($value) || $value === "0000-00-00" || $value === "0000-00-00 00:00:00") {
        return "-";
    }

    $timestamp = strtotime($value);
    return $timestamp ? date($format, $timestamp) : htmlspecialchars($value);
}

function safeTime($value) {
    if (empty($value)) {
        return "-";
    }

    $timestamp = strtotime($value);
    return $timestamp ? date("h:i A", $timestamp) : htmlspecialchars($value);
}

function money($value) {
    return "RM " . number_format((float)$value, 2);
}

$status = strtolower((string)$record['status']);
$payment_status = strtolower((string)$record['payment_status']);
$inspection_status = strtolower((string)($record['ins_status'] ?? ''));
$refund_status = strtolower((string)($record['refund_status'] ?? ''));
$penalty_status = strtolower((string)($record['penalty_status'] ?? ''));

$document_no = "BR-" . date("Y") . "-" . str_pad((string)$record['bid'], 6, "0", STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Record <?php echo htmlspecialchars($document_no); ?></title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Century Gothic", CenturyGothic, "Century", Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            margin: 0;
            padding: 30px;
        }

        .page {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 34px;
        }

        .top-actions {
            max-width: 850px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            border: 1px solid #cbd5e1;
            color: #334155;
            background: #ffffff;
            cursor: pointer;
        }

        .btn-primary {
            background: #4f46e5;
            color: white;
            border-color: #4f46e5;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 18px;
            margin-bottom: 24px;
        }

        .system-title {
            font-size: 22px;
            font-weight: 800;
            margin: 0;
        }

        .system-subtitle {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }

        .document-title {
            text-align: right;
        }

        .document-title h2 {
            font-size: 18px;
            margin: 0;
            text-transform: uppercase;
        }

        .document-no {
            font-size: 12px;
            color: #64748b;
            margin-top: 6px;
        }

        .status-row {
            display: flex;
            gap: 10px;
            margin-bottom: 22px;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            border: 1px solid #cbd5e1;
            color: #334155;
            background: #f8fafc;
        }

        .section {
            margin-top: 22px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #334155;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 18px;
        }

        .field {
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            border-radius: 8px;
            background: #f8fafc;
        }

        .label {
            font-size: 10px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .value {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .full {
            grid-column: 1 / -1;
        }

        .amount {
            font-weight: 800;
            color: #047857;
        }

        .footer {
            margin-top: 34px;
            border-top: 1px solid #e2e8f0;
            padding-top: 14px;
            font-size: 11px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }

            .top-actions {
                display: none;
            }

            .page {
                max-width: none;
                border: none;
                padding: 0;
                margin: 0;
            }

            @page {
                size: A4;
                margin: 14mm;
            }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/user_css.css?v=2.4">
</head>

<body class="user-light-theme bg-white text-slate-900">

<div class="top-actions">
    <a class="btn" href="booking_details.php?bid=<?php echo urlencode($record['bid']); ?>">
        Back to Booking Details
    </a>

    <a class="btn btn-primary" href="download_booking_pdf.php?bid=<?php echo urlencode($record['bid']); ?>">
        Download Report
    </a>
</div>

<div class="page">

    <div class="header">
        <div>
            <h1 class="system-title">Campus Venue Booking Management System</h1>
            <div class="system-subtitle">
                Booking record generated for user reference.
            </div>
        </div>

        <div class="document-title">
            <h2>Booking Report</h2>
            <div class="document-no">
                Document No: <?php echo htmlspecialchars($document_no); ?>
            </div>
        </div>
    </div>

    <div class="status-row">
        <span class="badge">
            Booking: <?php echo safeText($status); ?>
        </span>

        <span class="badge">
            Payment: <?php echo safeText($payment_status ?: "unpaid"); ?>
        </span>

        <?php if (!empty($inspection_status)): ?>
            <span class="badge">
                Inspection: <?php echo safeText($inspection_status); ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="section">
        <div class="section-title">User Information</div>

        <div class="grid">
            <div class="field">
                <div class="label">Student ID</div>
                <div class="value"><?php echo safeText($record['uid']); ?></div>
            </div>

            <div class="field">
                <div class="label">Name</div>
                <div class="value"><?php echo safeText($record['username']); ?></div>
            </div>

            <div class="field">
                <div class="label">Email</div>
                <div class="value"><?php echo safeText($record['email']); ?></div>
            </div>

            <div class="field">
                <div class="label">Phone Number</div>
                <div class="value"><?php echo safeText($record['phone_num']); ?></div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Booking Information</div>

        <div class="grid">
            <div class="field">
                <div class="label">Booking ID</div>
                <div class="value">#<?php echo safeText($record['bid']); ?></div>
            </div>

            <div class="field">
                <div class="label">Created At</div>
                <div class="value"><?php echo safeDate($record['created_at'], "d M Y, h:i A"); ?></div>
            </div>

            <div class="field">
                <div class="label">Venue</div>
                <div class="value"><?php echo safeText($record['vname']); ?></div>
            </div>

            <div class="field">
                <div class="label">Category</div>
                <div class="value"><?php echo safeText($record['category']); ?></div>
            </div>

            <div class="field">
                <div class="label">Booking Date</div>
                <div class="value"><?php echo safeDate($record['date_booked']); ?></div>
            </div>

            <div class="field">
                <div class="label">Booking Time</div>
                <div class="value">
                    <?php echo safeTime($record['time_start']); ?>
                    -
                    <?php echo safeTime($record['time_end']); ?>
                </div>
            </div>

            <div class="field">
                <div class="label">Maximum Capacity</div>
                <div class="value"><?php echo (int)$record['max_cap']; ?> Pax</div>
            </div>

            <div class="field">
                <div class="label">Deposit</div>
                <div class="value amount"><?php echo money($record['deposit']); ?></div>
            </div>

            <div class="field full">
                <div class="label">Purpose</div>
                <div class="value"><?php echo safeText($record['purpose']); ?></div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Payment Information</div>

        <div class="grid">
            <div class="field">
                <div class="label">Payment Status</div>
                <div class="value"><?php echo safeText($payment_status ?: "unpaid"); ?></div>
            </div>

            <div class="field">
                <div class="label">Transaction Reference</div>
                <div class="value"><?php echo safeText($record['transaction_ref']); ?></div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Admin Review</div>

        <div class="grid">
            <div class="field">
                <div class="label">Reviewed By</div>
                <div class="value"><?php echo safeText($record['admin_name']); ?></div>
            </div>

            <div class="field">
                <div class="label">Review Date</div>
                <div class="value"><?php echo safeDate($record['approve_date'], "d M Y, h:i A"); ?></div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Inspection / Settlement</div>

        <div class="grid">
            <div class="field">
                <div class="label">Inspector</div>
                <div class="value"><?php echo safeText($record['inspector_name']); ?></div>
            </div>

            <div class="field">
                <div class="label">Inspection Status</div>
                <div class="value"><?php echo safeText($inspection_status); ?></div>
            </div>

            <div class="field">
                <div class="label">Inspected At</div>
                <div class="value"><?php echo safeDate($record['inspected_at'], "d M Y, h:i A"); ?></div>
            </div>

            <div class="field">
                <div class="label">Damage Cost</div>
                <div class="value"><?php echo money($record['damage_cost'] ?? 0); ?></div>
            </div>

            <div class="field">
                <div class="label">Penalty</div>
                <div class="value"><?php echo money($record['penalty'] ?? 0); ?></div>
            </div>

            <div class="field">
                <div class="label">Final Deduct</div>
                <div class="value"><?php echo money($record['final_deduct'] ?? 0); ?></div>
            </div>

            <div class="field">
                <div class="label">Refund Status</div>
                <div class="value"><?php echo safeText($refund_status); ?></div>
            </div>

            <div class="field">
                <div class="label">Penalty Status</div>
                <div class="value"><?php echo safeText($penalty_status); ?></div>
            </div>

            <div class="field full">
                <div class="label">Damage Description</div>
                <div class="value"><?php echo safeText($record['damage_desc']); ?></div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div>
            This is a computer-generated booking record. No signature is required.
        </div>

        <div>
            Printed on: <?php echo date("d M Y, h:i A"); ?>
        </div>
    </div>

</div>

</body>
</html>
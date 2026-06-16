<?php
require_once(__DIR__ . '/../config/db.php');
require_once __DIR__ . '/../includes/user_auth.php';

$autoload_path = "../vendor/autoload.php";

if (!file_exists($autoload_path)) {
    die("Dompdf is not installed. Please run: composer require dompdf/dompdf");
}

require_once($autoload_path);

use Dompdf\Dompdf;
use Dompdf\Options;

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
    JOIN user u ON b.uid = u.uid
    JOIN venue v ON b.vid = v.vid 
    JOIN vcategory vc ON v.vcid = vc.vcid 
    LEFT JOIN admin a ON b.aid = a.aid 
    LEFT JOIN inspection i ON b.bid = i.bid
    LEFT JOIN staff s ON i.sid = s.sid
    LEFT JOIN report r ON i.ins_id = r.ins_id

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

function pdfText($value) {
    if ($value === null || $value === '') {
        return '-';
    }

    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function pdfDate($value, $format = "d M Y") {
    if (empty($value) || $value === "0000-00-00" || $value === "0000-00-00 00:00:00") {
        return "-";
    }

    $timestamp = strtotime($value);
    return $timestamp ? date($format, $timestamp) : pdfText($value);
}

function pdfTime($value) {
    if (empty($value)) {
        return "-";
    }

    $timestamp = strtotime($value);
    return $timestamp ? date("h:i A", $timestamp) : pdfText($value);
}

function pdfMoney($value) {
    return "RM " . number_format((float)$value, 2);
}

$status = strtolower((string)$record['status']);
$payment_status = strtolower((string)$record['payment_status']);
$inspection_status = strtolower((string)($record['ins_status'] ?? ''));
$refund_status = strtolower((string)($record['refund_status'] ?? ''));
$penalty_status = strtolower((string)($record['penalty_status'] ?? ''));

$document_no = "BR-" . date("Y") . "-" . str_pad((string)$record['bid'], 6, "0", STR_PAD_LEFT);

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <style>
        body {
            font-family: "Century Gothic", "DejaVu Sans", Arial, sans-serif;
            color: #0f172a;
            font-size: 12px;
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .system-title {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }

        .system-subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }

        .document-title {
            text-align: right;
            margin-top: -42px;
        }

        .document-title h2 {
            font-size: 16px;
            margin: 0;
            text-transform: uppercase;
        }

        .document-no {
            font-size: 11px;
            color: #64748b;
            margin-top: 5px;
        }

        .badge-row {
            margin-bottom: 16px;
        }

        .badge {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 14px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-right: 6px;
        }

        .section {
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #334155;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            width: 50%;
            vertical-align: top;
            padding: 5px;
        }

        .field {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 8px;
            border-radius: 6px;
        }

        .label {
            font-size: 9px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .value {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }

        .amount {
            color: #047857;
        }

        .footer {
            margin-top: 26px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-size: 10px;
            color: #64748b;
        }
    </style>
    <link rel="stylesheet" href="../assets/css/user_css.css?v=2.4">
</head>

<body class="user-light-theme bg-white text-slate-900">

    <div class="header">
        <h1 class="system-title">Campus Venue Booking Management System</h1>
        <div class="system-subtitle">Booking report generated for user reference.</div>

        <div class="document-title">
            <h2>Booking Report</h2>
            <div class="document-no">Document No: ' . pdfText($document_no) . '</div>
        </div>
    </div>

    <div class="badge-row">
        <span class="badge">Booking: ' . pdfText($status) . '</span>
        <span class="badge">Payment: ' . pdfText($payment_status ?: "unpaid") . '</span>
        <span class="badge">Inspection: ' . pdfText($inspection_status ?: "not available") . '</span>
    </div>

    <div class="section">
        <div class="section-title">User Information</div>

        <table>
            <tr>
                <td>
                    <div class="field">
                        <div class="label">Student ID</div>
                        <div class="value">' . pdfText($record['uid']) . '</div>
                    </div>
                </td>

                <td>
                    <div class="field">
                        <div class="label">Name</div>
                        <div class="value">' . pdfText($record['username']) . '</div>
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="field">
                        <div class="label">Email</div>
                        <div class="value">' . pdfText($record['email']) . '</div>
                    </div>
                </td>

                <td>
                    <div class="field">
                        <div class="label">Phone Number</div>
                        <div class="value">' . pdfText($record['phone_num']) . '</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Booking Information</div>

        <table>
            <tr>
                <td>
                    <div class="field">
                        <div class="label">Booking ID</div>
                        <div class="value">#' . pdfText($record['bid']) . '</div>
                    </div>
                </td>

                <td>
                    <div class="field">
                        <div class="label">Created At</div>
                        <div class="value">' . pdfDate($record['created_at'], "d M Y, h:i A") . '</div>
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="field">
                        <div class="label">Venue</div>
                        <div class="value">' . pdfText($record['vname']) . '</div>
                    </div>
                </td>

                <td>
                    <div class="field">
                        <div class="label">Category</div>
                        <div class="value">' . pdfText($record['category']) . '</div>
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="field">
                        <div class="label">Booking Date</div>
                        <div class="value">' . pdfDate($record['date_booked']) . '</div>
                    </div>
                </td>

                <td>
                    <div class="field">
                        <div class="label">Booking Time</div>
                        <div class="value">' . pdfTime($record['time_start']) . ' - ' . pdfTime($record['time_end']) . '</div>
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="field">
                        <div class="label">Maximum Capacity</div>
                        <div class="value">' . (int)$record['max_cap'] . ' Pax</div>
                    </div>
                </td>

                <td>
                    <div class="field">
                        <div class="label">Deposit</div>
                        <div class="value amount">' . pdfMoney($record['deposit']) . '</div>
                    </div>
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <div class="field">
                        <div class="label">Purpose</div>
                        <div class="value">' . pdfText($record['purpose']) . '</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Payment Information</div>

        <table>
            <tr>
                <td>
                    <div class="field">
                        <div class="label">Payment Status</div>
                        <div class="value">' . pdfText($payment_status ?: "unpaid") . '</div>
                    </div>
                </td>

                <td>
                    <div class="field">
                        <div class="label">Transaction Reference</div>
                        <div class="value">' . pdfText($record['transaction_ref']) . '</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Admin Review</div>

        <table>
            <tr>
                <td>
                    <div class="field">
                        <div class="label">Reviewed By</div>
                        <div class="value">' . pdfText($record['admin_name']) . '</div>
                    </div>
                </td>

                <td>
                    <div class="field">
                        <div class="label">Review Date</div>
                        <div class="value">' . pdfDate($record['approve_date'], "d M Y, h:i A") . '</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Inspection / Settlement</div>

        <table>
            <tr>
                <td>
                    <div class="field">
                        <div class="label">Inspector</div>
                        <div class="value">' . pdfText($record['inspector_name']) . '</div>
                    </div>
                </td>

                <td>
                    <div class="field">
                        <div class="label">Inspection Status</div>
                        <div class="value">' . pdfText($inspection_status) . '</div>
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="field">
                        <div class="label">Inspected At</div>
                        <div class="value">' . pdfDate($record['inspected_at'], "d M Y, h:i A") . '</div>
                    </div>
                </td>

                <td>
                    <div class="field">
                        <div class="label">Damage Cost</div>
                        <div class="value">' . pdfMoney($record['damage_cost'] ?? 0) . '</div>
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="field">
                        <div class="label">Penalty</div>
                        <div class="value">' . pdfMoney($record['penalty'] ?? 0) . '</div>
                    </div>
                </td>

                <td>
                    <div class="field">
                        <div class="label">Final Deduct</div>
                        <div class="value">' . pdfMoney($record['final_deduct'] ?? 0) . '</div>
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="field">
                        <div class="label">Refund Status</div>
                        <div class="value">' . pdfText($refund_status) . '</div>
                    </div>
                </td>

                <td>
                    <div class="field">
                        <div class="label">Penalty Status</div>
                        <div class="value">' . pdfText($penalty_status) . '</div>
                    </div>
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <div class="field">
                        <div class="label">Damage Description</div>
                        <div class="value">' . pdfText($record['damage_desc']) . '</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        This is a computer-generated booking report. No signature is required. <br> Generated on: ' . date("d M Y, h:i A") . ' </div>  </body> </html> ';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$file_name = "booking_report_" . $document_no . ".pdf";

$dompdf->stream($file_name, [
    "Attachment" => true
]);

exit;
?>
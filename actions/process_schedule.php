<?php
// File: actions/process_schedule.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    $conn->begin_transaction();

    try {
        if ($action === 'create' || $action === 'update') {
            $vid = trim($_POST['vid'] ?? '');
            $day = $_POST['day_of_week'] ?? '';
            $start = $_POST['start_time'] ?? '';
            $end = $_POST['end_time'] ?? '';
            $subject = trim($_POST['subject'] ?? '');
            $sch_id = intval($_POST['sch_id'] ?? 0);

            if (empty($vid) || empty($start) || empty($end)) {
                throw new Exception("Validation Error: Missing time-space vectors.");
            }
            if (strtotime($end) <= strtotime($start)) {
                throw new Exception("Logical Error: End time must strictly succeed start time.");
            }

            // 💡 排除矩陣碰撞檢查 (Exclusion Collision Check)
            // 檢查該場地在同一天是否有重疊的課
            $sql_check = "SELECT subject_name FROM academic_schedule 
                          WHERE vid = ? AND day_of_week = ? 
                          AND start_time < ? AND end_time > ?";
            
            if ($action === 'update') $sql_check .= " AND sch_id != $sch_id";

            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("ssss", $vid, $day, $end, $start);
            $stmt_check->execute();
            $overlap = $stmt_check->get_result()->fetch_assoc();

            if ($overlap) {
                throw new Exception("Schedule Conflict: This slot overlaps with [{$overlap['subject_name']}].");
            }

            if ($action === 'create') {
                $stmt = $conn->prepare("INSERT INTO academic_schedule (vid, day_of_week, start_time, end_time, subject_name) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $vid, $day, $start, $end, $subject);
            } else {
                $stmt = $conn->prepare("UPDATE academic_schedule SET vid = ?, day_of_week = ?, start_time = ?, end_time = ?, subject_name = ? WHERE sch_id = ?");
                $stmt->bind_param("sssssi", $vid, $day, $start, $end, $subject, $sch_id);
            }
            $stmt->execute();
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Academic slot successfully " . ($action === 'create' ? "locked." : "modified.")];

        } elseif ($action === 'delete') {
            $sch_id = intval($_POST['sch_id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM academic_schedule WHERE sch_id = ?");
            $stmt->bind_param("i", $sch_id);
            $stmt->execute();
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Academic exclusion removed."];
        }

        elseif ($action === 'batch_import') {
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File Transfer Fault: No valid CSV detected.");
            }

            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, "r");
            $csv_data = [];
            $header = fgetcsv($handle); // 跳過標題列

            // 1. 原始數據提取
            while (($row = fgetcsv($handle)) !== FALSE) {
                if (count($row) < 5) continue; 
                $csv_data[] = [
                    'vid' => strtoupper(trim($row[0])),
                    'day' => trim($row[1]),
                    'start' => trim($row[2]),
                    'end' => trim($row[3]),
                    'subject' => trim($row[4])
                ];
            }
            fclose($handle);

            $valid_pool = [];
            $conflict_log = [];

            // 2. 衝突仲裁矩陣 (Conflict Arbitration)
            foreach ($csv_data as $index => $current) {
                $has_conflict = false;
                $reason = "";

                // A. 內部衝突檢查 (Self-Consistency Check within CSV)
                foreach ($csv_data as $sub_index => $compare) {
                    if ($index === $sub_index) continue;
                    if ($current['vid'] === $compare['vid'] && $current['day'] === $compare['day']) {
                        if ($current['start'] < $compare['end'] && $current['end'] > $compare['start']) {
                            $has_conflict = true;
                            $reason = "Internal Overlap with CSV Row " . ($sub_index + 2);
                            break;
                        }
                    }
                }

                // B. 外部衝突檢查 (Database Integrity Check)
                if (!$has_conflict) {
                    $stmt_check = $conn->prepare("SELECT subject_name FROM academic_schedule WHERE vid = ? AND day_of_week = ? AND start_time < ? AND end_time > ? LIMIT 1");
                    $stmt_check->bind_param("ssss", $current['vid'], $current['day'], $current['end'], $current['start']);
                    $stmt_check->execute();
                    $db_conflict = $stmt_check->get_result()->fetch_assoc();
                    if ($db_conflict) {
                        $has_conflict = true;
                        $reason = "Database Collision with existing slot: [{$db_conflict['subject_name']}]";
                    }
                    $stmt_check->close();
                }

                // 3. 分流處理
                if ($has_conflict) {
                    $conflict_log[] = array_merge($current, ['reason' => $reason]);
                } else {
                    $valid_pool[] = $current;
                }
            }

            // 4. 原子級入庫 (Atomic Insertion)
            $success_count = 0;
            foreach ($valid_pool as $entry) {
                $ins = $conn->prepare("INSERT INTO academic_schedule (vid, day_of_week, start_time, end_time, subject_name) VALUES (?, ?, ?, ?, ?)");
                $ins->bind_param("sssss", $entry['vid'], $entry['day'], $entry['start'], $entry['end'], $entry['subject']);
                if ($ins->execute()) $success_count++;
                $ins->close();
            }

            // 5. 反饋封裝
            $_SESSION['batch_results'] = [
                'success' => $success_count,
                'conflicts' => $conflict_log
            ];
            $_SESSION['toast'] = ['type' => 'info', 'msg' => "Batch process finished. $success_count items synced."];
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => $e->getMessage()];
    }

    header("Location: ../admin/academic_schedule.php");
    exit;
}
<?php
// This section checks and processes schedule requests.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $action = $_POST['action'] ?? '';


    $conn->begin_transaction();

    try
    {
        if ($action === 'create' || $action === 'update')
        {

            $sem_id = intval($_POST['sem_id'] ?? 0);
            $vid = trim($_POST['vid'] ?? '');
            $day = $_POST['day_of_week'] ?? '';
            $start = $_POST['start_time'] ?? '';
            $end = $_POST['end_time'] ?? '';
            $subject = trim($_POST['subject'] ?? '');
            $sch_id = intval($_POST['sch_id'] ?? 0);

            if ($sem_id === 0 || empty($vid) || empty($start) || empty($end))
            {
                throw new Exception("Validation Fault: Missing spatiotemporal or semester vectors.");
            }
            if (strtotime($end) <= strtotime($start))
            {
                throw new Exception("Logical Fault: End time must strictly succeed start time.");
            }


            $sql_check = "SELECT subject_name FROM academic_schedule
                          WHERE vid = ? AND sem_id = ? AND day_of_week = ?
                          AND start_time < ? AND end_time > ?";

            if ($action === 'update')
            {
                $sql_check .= " AND sch_id != ?";
            }

            $stmt_check = $conn->prepare($sql_check);
            if ($action === 'update')
            {
                $stmt_check->bind_param("sisssi", $vid, $sem_id, $day, $end, $start, $sch_id);
            } else
            {
                $stmt_check->bind_param("sisss", $vid, $sem_id, $day, $end, $start);
            }
            $stmt_check->execute();
            $overlap = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();

            if ($overlap)
            {
                throw new Exception("Schedule Conflict: This vector overlaps with [{$overlap['subject_name']}] in the designated semester.");
            }


            if ($action === 'create')
            {
                $stmt = $conn->prepare("INSERT INTO academic_schedule (vid, sem_id, day_of_week, start_time, end_time, subject_name) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sissss", $vid, $sem_id, $day, $start, $end, $subject);
            } else
            {
                $stmt = $conn->prepare("UPDATE academic_schedule SET vid = ?, sem_id = ?, day_of_week = ?, start_time = ?, end_time = ?, subject_name = ? WHERE sch_id = ?");
                $stmt->bind_param("sissssi", $vid, $sem_id, $day, $start, $end, $subject, $sch_id);
            }
            $stmt->execute();
            if ($stmt->error) throw new Exception("Database Execution Fault: " . $stmt->error); $stmt->close();  $_SESSION['toast'] = ['type' => 'success', 'msg' => "Academic slot successfully " . ($action === 'create' ? "locked." : "modified.")];  } elseif ($action === 'batch_import')
            {

            $sem_id = intval($_POST['sem_id'] ?? 0);
            if ($sem_id === 0)
            {
                throw new Exception("Batch Fault: No Target Semester Vector Selected.");
            }
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK)
            {
                throw new Exception("File Transfer Fault: Invalid CSV binary payload.");
            }

            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, "r");
            $csv_data = [];
 fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== FALSE)
            {
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


            $v_whitelist = [];
            $stmt_v = $conn->prepare("SELECT vid FROM venue");
            $stmt_v->execute();
            $res_v = $stmt_v->get_result();
            while ($r = $res_v->fetch_assoc())
            {
                $v_whitelist[] = strtoupper($r['vid']);
            }
            $stmt_v->close();


            foreach ($csv_data as $current)
            {

                if (!in_array($current['vid'], $v_whitelist))
                {
                    $conflict_log[] = array_merge($current, ['reason' => "Topology Constraint Fault: Venue ID [{$current['vid']}] ∉ V_{valid}."]);
 continue;
                }


                if (!preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/', $current['start']) || !preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/', $current['end']))
                {
                    $conflict_log[] = array_merge($current, ['reason' => "Temporal Syntax Fault: Malformed HH:MM(:SS) format."]);
                    continue;
                }
                if (strtotime($current['end']) <= strtotime($current['start']))
                {
                    $conflict_log[] = array_merge($current, ['reason' => "Vector Direction Fault: End time ≤ Start time."]);
                    continue;
                }

                $has_conflict = false;
                $reason = "";


                $stmt_check = $conn->prepare("SELECT subject_name FROM academic_schedule WHERE vid = ? AND sem_id = ? AND day_of_week = ? AND start_time < ? AND end_time > ? LIMIT 1");
                $stmt_check->bind_param("sisss", $current['vid'], $sem_id, $current['day'], $current['end'], $current['start']);
                $stmt_check->execute();
                $db_conflict = $stmt_check->get_result()->fetch_assoc();
                $stmt_check->close();

                if ($db_conflict)
                {
                    $has_conflict = true;
                    $reason = "Database Collision: Overlaps with [{$db_conflict['subject_name']}]";
                }


                if (!$has_conflict)
                {
                    foreach ($valid_pool as $accepted)
                    {
                        if ($current['vid'] === $accepted['vid'] && $current['day'] === $accepted['day'])
                        {
                            if ($current['start'] < $accepted['end'] && $current['end'] > $accepted['start'])
                            {
                                $has_conflict = true;
                                $reason = "Internal CSV Collision: Overlaps with [{$accepted['subject']}] earlier in the file";
                                break;
                            }
                        }
                    }
                }

                if ($has_conflict)
                {
                    $conflict_log[] = array_merge($current, ['reason' => $reason]);
                } else
                {
                    $valid_pool[] = $current;
                }
            }


            $success_count = 0;
            foreach ($valid_pool as $entry)
            {
                $ins = $conn->prepare("INSERT INTO academic_schedule (vid, sem_id, day_of_week, start_time, end_time, subject_name) VALUES (?, ?, ?, ?, ?, ?)");
                $ins->bind_param("sissss", $entry['vid'], $sem_id, $entry['day'], $entry['start'], $entry['end'], $entry['subject']);
                if ($ins->execute()) $success_count++; $ins->close(); }  $_SESSION['batch_results'] = ['success' => $success_count, 'conflicts' => $conflict_log]; $_SESSION['toast'] = ['type' => 'success', 'msg' => "Batch sync completed. $success_count vectors injected into Semester ID $sem_id."];  } elseif ($action === 'bulk_delete')
                {
            $ids = $_POST['sch_ids'] ?? [];
            if (!is_array($ids) || empty($ids)) throw new Exception("Execution Fault: Null vector array provided for deletion.");  $success = 0; $stmt = $conn->prepare("DELETE FROM academic_schedule WHERE sch_id = ?"); foreach ($ids as $id)
            {
                $clean_id = intval($id);
                if ($clean_id > 0)
                {
                    $stmt->bind_param("i", $clean_id);
                    if ($stmt->execute()) $success++; } } $stmt->close(); $_SESSION['toast'] = ['type' => 'success', 'msg' => "Remove Process Complete: $success exclusions purged."]; }  $conn->commit(); } catch (Exception $e)
                    {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => $e->getMessage()];
    }

    header("Location: ../admin/academic_schedule.php");
    exit;
} else
{
    header("Location: ../admin/academic_schedule.php");
    exit;
}

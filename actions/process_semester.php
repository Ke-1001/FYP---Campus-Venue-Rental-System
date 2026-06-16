<?php

// This section checks and processes semester requests.
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
            $sem_name = htmlspecialchars(trim($_POST['sem_name'] ?? ''));
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $is_booking_open = isset($_POST['is_booking_open']) ? 1 : 0;

            if ($sem_id < 1000 || $sem_id > 9999)
            {
                throw new Exception("Validation Fault: Semester ID must be a strictly 4-digit integer.");
            }
            if (empty($sem_name) || empty($start_date) || empty($end_date))
            {
                throw new Exception("Validation Fault: Essential temporal vectors cannot be null.");
            }
            if (strtotime($end_date) <= strtotime($start_date))
            {
                throw new Exception("Logical Fault: End date must strictly succeed start date.");
            }

            $sql_overlap = "SELECT sem_name FROM semester_config WHERE start_date <= ? AND end_date >= ?";

            if ($action === 'update')
            {
                $sql_overlap .= " AND sem_id != ?";
                $stmt_overlap = $conn->prepare($sql_overlap);
                $stmt_overlap->bind_param("ssi", $end_date, $start_date, $sem_id);
            }
            else
            {
                $stmt_overlap = $conn->prepare($sql_overlap);
                $stmt_overlap->bind_param("ss", $end_date, $start_date);
            }

            $stmt_overlap->execute();
            $overlap_res = $stmt_overlap->get_result()->fetch_assoc();
            $stmt_overlap->close();

            if ($overlap_res)
            {
                throw new Exception("Temporal Collision: The specified dates overlap with an existing academic term [{$overlap_res['sem_name']}].");
            }

            if ($action === 'update' && $is_active === 0)
            {
                $stmt_check_active = $conn->prepare("SELECT COUNT(*) as active_count FROM semester_config WHERE is_active = 1 AND sem_id != ?");
                $stmt_check_active->bind_param("i", $sem_id);
                $stmt_check_active->execute();
                $active_res = $stmt_check_active->get_result()->fetch_assoc();
                $stmt_check_active->close();

                if ($active_res['active_count'] == 0)
                {
                    throw new Exception("State Collapse Prevented: System requires Σ is_active ≥ 1. You cannot deactivate the only active semester.");
                }
            }

            if ($is_active === 1)
            {
                $conn->query("UPDATE semester_config SET is_active = 0");
            }

            if ($action === 'create')
            {
                $check = $conn->prepare("SELECT sem_id FROM semester_config WHERE sem_id = ?");
                $check->bind_param("i", $sem_id);
                $check->execute();
                if ($check->get_result()->num_rows > 0)
                {
                    throw new Exception("ID Collision: Semester Code [$sem_id] already exists in the system.");
                }
                $check->close();

                $stmt = $conn->prepare("INSERT INTO semester_config (sem_id, sem_name, start_date, end_date, is_active, is_booking_open) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssii", $sem_id, $sem_name, $start_date, $end_date, $is_active, $is_booking_open);
            }
            else
            {
                $stmt = $conn->prepare("UPDATE semester_config SET sem_name = ?, start_date = ?, end_date = ?, is_active = ?, is_booking_open = ? WHERE sem_id = ?");
                $stmt->bind_param("sssiii", $sem_name, $start_date, $end_date, $is_active, $is_booking_open, $sem_id);
            }

            $stmt->execute();
            if ($stmt->error) throw new Exception("Database Fault: " . $stmt->error); $stmt->close();  $_SESSION['toast'] = ['type' => 'success', 'msg' => "Semester configuration [ID: $sem_id] successfully " . ($action === 'create' ? "deployed." : "updated.")];  
        } 
        elseif ($action === 'delete')
        {
            $ids = $_POST['selected_ids'] ?? [];
            if (!is_array($ids) || empty($ids)) throw new Exception("Execution Fault: Null vector array provided for deletion.");

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            $stmt_check_del = $conn->prepare("SELECT COUNT(*) as active_in_del FROM semester_config WHERE is_active = 1 AND sem_id IN ($placeholders)");
            $stmt_check_del->bind_param($types, ...$ids);
            $stmt_check_del->execute();
            $del_res = $stmt_check_del->get_result()->fetch_assoc();
            $stmt_check_del->close();

            if ($del_res['active_in_del'] > 0)
            {
                throw new Exception("State Constraint Fault: Cannot purge an active semester. Deactivate it first.");
            }

            $success = 0;
            $stmt = $conn->prepare("DELETE FROM semester_config WHERE sem_id = ?");
            foreach ($ids as $id)
            {
                $clean_id = intval($id);
                if ($clean_id > 0)
                {
                    $stmt->bind_param("i", $clean_id);
                    if ($stmt->execute()) $success++; } } $stmt->close(); $_SESSION['toast'] = ['type' => 'success', 'msg' => "Eradication Protocol Complete: $success semester(s) purged."]; 
        } 
        else
        {
            throw new Exception("Unknown Execution Protocol.");
        }

        $conn->commit();
    }
    catch (Exception $e)
    {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => $e->getMessage()];
    }

    header("Location: ../admin/semester_management.php");
    exit;
}

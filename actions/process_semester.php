<?php
// File: actions/process_semester.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    $conn->begin_transaction();

    try {
        if ($action === 'create') {
            $sem_name = trim($_POST['sem_name'] ?? '');
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';

            if (empty($sem_name) || empty($start_date) || empty($end_date)) {
                throw new Exception("Validation Fault: Missing temporal vectors.");
            }
            if (strtotime($end_date) <= strtotime($start_date)) {
                throw new Exception("Logical Paradox: End date ($E_{new}$) must strictly succeed start date ($S_{new}$).");
            }

            // 💡 時間互斥檢查 (Temporal Mutex Validation)
            $check_stmt = $conn->prepare("SELECT sem_name FROM semester_config WHERE start_date <= ? AND end_date >= ? LIMIT 1");
            $check_stmt->bind_param("ss", $end_date, $start_date);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();
            if ($check_res->num_rows > 0) {
                $conflict = $check_res->fetch_assoc();
                throw new Exception("Temporal Collision: The specified boundary intersects with an existing node [{$conflict['sem_name']}].");
            }
            $check_stmt->close();

            $stmt = $conn->prepare("INSERT INTO semester_config (sem_name, start_date, end_date, is_active, is_booking_open) VALUES (?, ?, ?, 0, 0)");
            $stmt->bind_param("sss", $sem_name, $start_date, $end_date);
            $stmt->execute();
            $stmt->close();
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Temporal boundary [$sem_name] mapped successfully."];
        } 
        elseif ($action === 'update') {
            $sem_id = intval($_POST['sem_id'] ?? 0);
            $sem_name = trim($_POST['sem_name'] ?? '');
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';

            if ($sem_id === 0 || empty($sem_name) || empty($start_date) || empty($end_date)) {
                throw new Exception("Validation Fault: Missing parameters for update.");
            }
            if (strtotime($end_date) <= strtotime($start_date)) {
                throw new Exception("Logical Paradox: End date ($E_{new}$) must succeed start date ($S_{new}$).");
            }

            // 💡 時間互斥檢查 (排除自身實體)
            $check_stmt = $conn->prepare("SELECT sem_name FROM semester_config WHERE start_date <= ? AND end_date >= ? AND sem_id != ? LIMIT 1");
            $check_stmt->bind_param("ssi", $end_date, $start_date, $sem_id);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();
            if ($check_res->num_rows > 0) {
                $conflict = $check_res->fetch_assoc();
                throw new Exception("Temporal Collision: Modifying this boundary intersects with node [{$conflict['sem_name']}].");
            }
            $check_stmt->close();

            $stmt = $conn->prepare("UPDATE semester_config SET sem_name = ?, start_date = ?, end_date = ? WHERE sem_id = ?");
            $stmt->bind_param("sssi", $sem_name, $start_date, $end_date, $sem_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Semester Node [$sem_name] has been modified."];
        }
        elseif ($action === 'toggle_active') {
            $sem_id = intval($_POST['sem_id'] ?? 0);
            if ($sem_id === 0) throw new Exception("NULL Pointer Reference for Semester Node.");

            $conn->query("UPDATE semester_config SET is_active = 0");
            
            $stmt = $conn->prepare("UPDATE semester_config SET is_active = 1 WHERE sem_id = ?");
            $stmt->bind_param("i", $sem_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Global State Shift: Semester promoted to Active."];
        } 
        elseif ($action === 'toggle_booking') {
            $sem_id = intval($_POST['sem_id'] ?? 0);
            if ($sem_id === 0) throw new Exception("NULL Pointer Reference.");

            $stmt = $conn->prepare("UPDATE semester_config SET is_booking_open = is_booking_open ^ 1 WHERE sem_id = ?");
            $stmt->bind_param("i", $sem_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Booking Gateway State inverted successfully."];
        } 
        elseif ($action === 'delete') {
            $sem_id = intval($_POST['sem_id'] ?? 0);
            if ($sem_id === 0) throw new Exception("NULL Pointer Reference. Entity not found.");

            $stmt = $conn->prepare("DELETE FROM semester_config WHERE sem_id = ?");
            $stmt->bind_param("i", $sem_id);
            
            if ($stmt->execute()) {
                $_SESSION['toast'] = ['type' => 'success', 'msg' => "Temporal boundary purged successfully."];
            } else {
                throw new Exception("SQL Exception: Could not purge entity.");
            }
            $stmt->close();
        } 
        else {
            throw new Exception("Unknown Operation Protocol.");
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => $e->getMessage()];
    }

    header("Location: ../admin/semester_management.php");
    exit;
}
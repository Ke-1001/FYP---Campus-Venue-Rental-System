<?php
// File: actions/process_semester.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    $conn->begin_transaction();

    try {
        if ($action === 'create' || $action === 'update') {
            
            // 💡 嚴格擷取 4 位數業務鍵與時間向量
            $sem_id = intval($_POST['sem_id'] ?? 0);
            $sem_name = htmlspecialchars(trim($_POST['sem_name'] ?? ''));
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // 邊界防呆
            if ($sem_id < 1000 || $sem_id > 9999) {
                throw new Exception("Validation Fault: Semester ID must be a strictly 4-digit integer.");
            }
            if (empty($sem_name) || empty($start_date) || empty($end_date)) {
                throw new Exception("Validation Fault: Essential temporal vectors cannot be null.");
            }
            if (strtotime($end_date) <= strtotime($start_date)) {
                throw new Exception("Logical Fault: End date must strictly succeed start date.");
            }

            // 💡 新增：時間軸重疊防護 (Temporal Overlap Detection)
            // 數學條件: S_ex <= E_new AND E_ex >= S_new
            $sql_overlap = "SELECT sem_name FROM semester_config WHERE start_date <= ? AND end_date >= ?";
            
            if ($action === 'update') {
                $sql_overlap .= " AND sem_id != ?";
                $stmt_overlap = $conn->prepare($sql_overlap);
                $stmt_overlap->bind_param("ssi", $end_date, $start_date, $sem_id);
            } else {
                $stmt_overlap = $conn->prepare($sql_overlap);
                $stmt_overlap->bind_param("ss", $end_date, $start_date);
            }
            
            $stmt_overlap->execute();
            $overlap_res = $stmt_overlap->get_result()->fetch_assoc();
            $stmt_overlap->close();

            if ($overlap_res) {
                throw new Exception("Temporal Collision: The specified dates overlap with an existing academic term [{$overlap_res['sem_name']}].");
            }

            // 若設為 Active，將其他所有學期降階為 Inactive (互斥鎖)
            if ($is_active === 1) {
                $conn->query("UPDATE semester_config SET is_active = 0");
            }

            if ($action === 'create') {
                // 💡 主鍵防護：防止手動輸入的 ID 重複
                $check = $conn->prepare("SELECT sem_id FROM semester_config WHERE sem_id = ?");
                $check->bind_param("i", $sem_id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    throw new Exception("ID Collision: Semester Code [$sem_id] already exists in the system.");
                }
                $check->close();

                $stmt = $conn->prepare("INSERT INTO semester_config (sem_id, sem_name, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isssi", $sem_id, $sem_name, $start_date, $end_date, $is_active);
            } else {
                // 編輯模式：主鍵不變
                $stmt = $conn->prepare("UPDATE semester_config SET sem_name = ?, start_date = ?, end_date = ?, is_active = ? WHERE sem_id = ?");
                $stmt->bind_param("sssii", $sem_name, $start_date, $end_date, $is_active, $sem_id);
            }
            
            $stmt->execute();
            if ($stmt->error) throw new Exception("Database Fault: " . $stmt->error);
            $stmt->close();

            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Semester configuration [ID: $sem_id] successfully " . ($action === 'create' ? "deployed." : "updated.")];

        } elseif ($action === 'delete') {
            $ids = $_POST['selected_ids'] ?? [];
            if (!is_array($ids) || empty($ids)) throw new Exception("Execution Fault: Null vector array provided for deletion.");
            
            $success = 0;
            $stmt = $conn->prepare("DELETE FROM semester_config WHERE sem_id = ?");
            foreach ($ids as $id) {
                $clean_id = intval($id);
                if ($clean_id > 0) {
                    $stmt->bind_param("i", $clean_id);
                    if ($stmt->execute()) $success++;
                }
            }
            $stmt->close();
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Eradication Protocol Complete: $success semester(s) purged."];
        } else {
            throw new Exception("Unknown Execution Protocol.");
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => $e->getMessage()];
    }

    header("Location: ../admin/semester_management.php");
    exit;
}
<?php
// File: actions/process_venue.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
 // 1. Get action command (Action Directive)
    $action = $_POST['action'] ?? '';
    
    // =========================================================================
 // alpha: Bulk delete handler (Bulk Delete Interceptor)
 // This branch must run before normal validation (Short-circuiting)
    // =========================================================================
    if ($action === 'bulk_delete') {
        $vids = $_POST['selected_vids'] ?? [];
        
        if (!empty($vids) && is_array($vids)) {
 // Start a separate transaction for safe bulk delete
            $conn->begin_transaction();
            try {
 // Prepared SQL statement (remove update logic and use strict delete rules)
                $stmt_check = $conn->prepare("SELECT COUNT(*) FROM booking WHERE vid = ?");
                $stmt_del = $conn->prepare("DELETE FROM venue WHERE vid = ?");
                
                $count_purged = 0;

                foreach ($vids as $del_vid) {
 // 1. Check booking history dependency
                    $stmt_check->bind_param("s", $del_vid);
                    $stmt_check->execute();
                    $result = $stmt_check->get_result();
                    $row = $result->fetch_row();
                    $booking_count = intval($row[0]);
                    
                    if ($booking_count > 0) {
 // 2a. Related data exists => stop and roll back all changes (Strict Constraint Violation)
                        throw new Exception("Deletion denied. Node [$del_vid] possesses historical transaction records. Referential integrity protocol forbids erasure.");
                    } else {
 // 2b. No related data => Run hard delete (Physical Purge)
                        $stmt_del->bind_param("s", $del_vid);
                        $stmt_del->execute();
                        $count_purged++;
                    }
                }
                
                $stmt_check->close();
                $stmt_del->close();
                $conn->commit();
                
 // Create final status report (only when all checks pass)
                $op_msg = "Batch Execution Complete. Purged $count_purged node(s) successfully.";
                $_SESSION['toast'] = ['type' => 'success', 'msg' => $op_msg];
            } catch (Exception $e) {
 // Error handler: show red toast and roll back data
                $conn->rollback();
                $_SESSION['toast'] = ['type' => 'error', 'msg' => "Operation Aborted: " . $e->getMessage()];
            }
            
 // Fix: redirect and stop after transaction to avoid falling into branch beta
            header("Location: ../admin/venue_directory.php");
            exit;
            
        } else {
            header("Location: ../admin/venue_directory.php");
            exit; 
 } // Stop the next process
    }

    // =========================================================================
 // beta: Create or update record (Create / Update Pipeline)
    // =========================================================================
    
 // 1. Get and convert values strictly (match rental_venue 4.sql)
    $vid = strtoupper(trim($_POST['vid'] ?? '')); // VARCHAR(10)
    $vname = trim($_POST['vname'] ?? '');
 $vcid = intval($_POST['vcid'] ?? 0); // Foreign key vcid
    $max_cap = intval($_POST['max_cap'] ?? 0);
    $deposit = floatval($_POST['deposit'] ?? 0.00);
    $status = $_POST['status'] ?? 'available';
    $description = trim($_POST['description'] ?? '');

 // Basic validation (Eager Validation)
    if (empty($vid) || empty($vname) || $vcid === 0) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Validation Fault: Essential vectors (ID, Name, Category) cannot be null.'];
        header("Location: ../admin/venue_directory.php");
        exit;
    }

 // Start transaction (Atomic Transaction)
    $conn->begin_transaction();

    try {
        if ($action === 'create') {
 // Check if VID already exists
            $check = $conn->prepare("SELECT vid FROM venue WHERE vid = ?");
            $check->bind_param("s", $vid);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("Entity Collision: Venue Node [$vid] already exists in the system.");
            }
            $check->close();

 // Save to venue table (including description and vcid)
            $sql = "INSERT INTO venue (vid, vname, vcid, max_cap, deposit, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiidss", $vid, $vname, $vcid, $max_cap, $deposit, $status, $description);
            $stmt->execute();
            $stmt->close();
            
            $op_msg = "Asset Creation Success: Venue [$vid] initialized.";

        } elseif ($action === 'update') {
            $sql = "UPDATE venue SET vname = ?, vcid = ?, max_cap = ?, deposit = ?, status = ?, description = ? WHERE vid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siidsss", $vname, $vcid, $max_cap, $deposit, $status, $description, $vid);
            $stmt->execute();
            $stmt->close();
            
            $op_msg = "Vector Update Success: Node [$vid] reconfigured.";
        } else {
            throw new Exception("Unknown Operation Protocol.");
        }

 // 2. Handle multiple image upload (Multipart Asset Pipeline)
        if (isset($_FILES['venue_pics']) && !empty($_FILES['venue_pics']['name'][0])) {
            $upload_dir = '../uploads/venues/';
            
 // Create folder if it does not exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_count = count($_FILES['venue_pics']['name']);
            $pic_success = 0;

            for ($i = 0; $i < $file_count; $i++) {
                $tmp_name = $_FILES['venue_pics']['tmp_name'][$i];
                $error = $_FILES['venue_pics']['error'][$i];
                
                if ($error === UPLOAD_ERR_OK && is_uploaded_file($tmp_name)) {
                    $ext = strtolower(pathinfo($_FILES['venue_pics']['name'][$i], PATHINFO_EXTENSION));
                    
 // Allow safe image formats only
                    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
 // Create a unique file name
                        $new_filename = $vid . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                        $destination = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($tmp_name, $destination)) {
 // Save to vpic table
                            $pic_desc = "Gallery asset for node " . $vid;
                            $stmt_pic = $conn->prepare("INSERT INTO vpic (pic, vid, description) VALUES (?, ?, ?)");
                            $stmt_pic->bind_param("sss", $new_filename, $vid, $pic_desc);
                            $stmt_pic->execute();
                            $stmt_pic->close();
                            $pic_success++;
                        }
                    }
                }
            }
            if ($pic_success > 0) {
                $op_msg .= " ($pic_success visual assets mounted).";
            }
        }

 // Commit transaction
        $conn->commit();
        $_SESSION['toast'] = ['type' => 'success', 'msg' => $op_msg];

    } catch (Exception $e) {
 // Roll back all changes if any error happens (including uploaded database records)
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => "Transaction Aborted: " . $e->getMessage()];
    }
    
 // 3. Common redirect (return to directory)
    header("Location: ../admin/venue_directory.php");
    exit;
} else {
    header("Location: ../admin/venue_directory.php");
    exit;
}
?>
<?php
// File: actions/process_venue.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ∴ 1. 提取操作指令 (Action Directive)
    $action = $_POST['action'] ?? '';
    
    // =========================================================================
    // 🔻 分支 α: 批量删除拦截器 (Bulk Delete Interceptor) 🔻
    // 此分支必须置于标量验证之前，以实现短路求值 (Short-circuiting)
    // =========================================================================
    if ($action === 'bulk_delete') {
        $vids = $_POST['selected_vids'] ?? [];
        
        if (!empty($vids) && is_array($vids)) {
            // 启动独立原子交易以保障批量删除的数据一致性
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("DELETE FROM venue WHERE vid = ?");
                foreach ($vids as $del_vid) {
                    $stmt->bind_param("s", $del_vid);
                    $stmt->execute();
                }
                $stmt->close();
                $conn->commit();
                
                $count = count($vids);
                $_SESSION['toast'] = ['type' => 'success', 'msg' => "Deleted: $count node(s) purged from the system."];
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['toast'] = ['type' => 'error', 'msg' => "Deletion Aborted: " . $e->getMessage()];
            }
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => "Validation Fault: No vectors selected for deletion."];
        }
        
        header("Location: ../admin/venue_directory.php");
        exit; // ⚡ 强制挂起后续管线
    }

    // =========================================================================
    // 🔻 分支 β: 节点创建与参数覆写 (Create / Update Pipeline) 🔻
    // =========================================================================
    
    // 💡 1. 嚴格提取與轉型 (對齊 rental_venue 4.sql)
    $vid = strtoupper(trim($_POST['vid'] ?? '')); // VARCHAR(10)
    $vname = trim($_POST['vname'] ?? '');
    $vcid = intval($_POST['vcid'] ?? 0);          // 外鍵 vcid
    $max_cap = intval($_POST['max_cap'] ?? 0);
    $deposit = floatval($_POST['deposit'] ?? 0.00);
    $status = $_POST['status'] ?? 'available';
    $description = trim($_POST['description'] ?? '');

    // 基础防呆约束 (Eager Validation)
    if (empty($vid) || empty($vname) || $vcid === 0) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Validation Fault: Essential vectors (ID, Name, Category) cannot be null.'];
        header("Location: ../admin/venue_directory.php");
        exit;
    }

    // 啟動原子交易 (Atomic Transaction)
    $conn->begin_transaction();

    try {
        if ($action === 'create') {
            // 檢查 VID 是否發生主鍵衝突
            $check = $conn->prepare("SELECT vid FROM venue WHERE vid = ?");
            $check->bind_param("s", $vid);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("Entity Collision: Venue Node [$vid] already exists in the system.");
            }
            $check->close();

            // 💡 寫入 venue 主表 (包含 description 與 vcid)
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

        // 💡 2. 多重圖片管線處理 (Multipart Asset Pipeline)
        if (isset($_FILES['venue_pics']) && !empty($_FILES['venue_pics']['name'][0])) {
            $upload_dir = '../uploads/venues/';
            
            // 若目錄不存在則自動建立
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
                    
                    // 僅允許安全圖片格式
                    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                        // 產生防碰撞的唯一識別檔名
                        $new_filename = $vid . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                        $destination = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($tmp_name, $destination)) {
                            // 💡 寫入 vpic 關聯表
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

        // 提交交易
        $conn->commit();
        $_SESSION['toast'] = ['type' => 'success', 'msg' => $op_msg];

    } catch (Exception $e) {
        // 發生任何錯誤即全盤回滾 (包含已上傳的資料庫記錄)
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => "Transaction Aborted: " . $e->getMessage()];
    }
    
    // 💡 3. 統一重定向 (無縫返回目錄)
    header("Location: ../admin/venue_directory.php");
    exit;
} else {
    header("Location: ../admin/venue_directory.php");
    exit;
}
?>
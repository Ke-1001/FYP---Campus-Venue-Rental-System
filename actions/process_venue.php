<?php
// This section checks and processes venue requests.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{


    $action = $_POST['action'] ?? '';


    if ($action === 'bulk_delete')
    {
        $vids = $_POST['selected_vids'] ?? [];

        if (!empty($vids) && is_array($vids))
        {

            $conn->begin_transaction();
            try
            {

                $stmt_check = $conn->prepare("SELECT COUNT(*) FROM booking WHERE vid = ?");
                $stmt_del = $conn->prepare("DELETE FROM venue WHERE vid = ?");

                $count_purged = 0;

                foreach ($vids as $del_vid)
                {

                    $stmt_check->bind_param("s", $del_vid);
                    $stmt_check->execute();
                    $result = $stmt_check->get_result();
                    $row = $result->fetch_row();
                    $booking_count = intval($row[0]);

                    if ($booking_count > 0)
                    {

                        throw new Exception("Deletion denied. Node [$del_vid] possesses historical transaction records. Referential integrity protocol forbids erasure.");
                    } else
                    {

                        $stmt_del->bind_param("s", $del_vid);
                        $stmt_del->execute();
                        $count_purged++;
                    }
                }

                $stmt_check->close();
                $stmt_del->close();
                $conn->commit();


                $op_msg = "Batch Execution Complete. Purged $count_purged node(s) successfully.";
                $_SESSION['toast'] = ['type' => 'success', 'msg' => $op_msg];
            } catch (Exception $e)
            {

                $conn->rollback();
                $_SESSION['toast'] = ['type' => 'error', 'msg' => "Operation Aborted: " . $e->getMessage()];
            }


            header("Location: ../admin/venue_directory.php");
            exit;

        } else
        {
            header("Location: ../admin/venue_directory.php");
            exit;
 }
    }


    $vid = strtoupper(trim($_POST['vid'] ?? ''));
    $vname = trim($_POST['vname'] ?? '');
 $vcid = intval($_POST['vcid'] ?? 0);
    $max_cap = intval($_POST['max_cap'] ?? 0);
    $deposit = floatval($_POST['deposit'] ?? 0.00);
    $status = $_POST['status'] ?? 'available';
    $description = trim($_POST['description'] ?? '');


    if (empty($vid) || empty($vname) || $vcid === 0)
    {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Validation Fault: Essential vectors (ID, Name, Category) cannot be null.'];
        header("Location: ../admin/venue_directory.php");
        exit;
    }


    $conn->begin_transaction();

    try
    {
        if ($action === 'create')
        {

            $check = $conn->prepare("SELECT vid FROM venue WHERE vid = ?");
            $check->bind_param("s", $vid);
            $check->execute();
            if ($check->get_result()->num_rows > 0)
            {
                throw new Exception("Entity Collision: Venue Node [$vid] already exists in the system.");
            }
            $check->close();


            $sql = "INSERT INTO venue (vid, vname, vcid, max_cap, deposit, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiidss", $vid, $vname, $vcid, $max_cap, $deposit, $status, $description);
            $stmt->execute();
            $stmt->close();

            $op_msg = "Asset Creation Success: Venue [$vid] initialized.";

        } elseif ($action === 'update')
        {
            $sql = "UPDATE venue SET vname = ?, vcid = ?, max_cap = ?, deposit = ?, status = ?, description = ? WHERE vid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siidsss", $vname, $vcid, $max_cap, $deposit, $status, $description, $vid);
            $stmt->execute();
            $stmt->close();

            $op_msg = "Vector Update Success: Node [$vid] reconfigured.";
        } else
        {
            throw new Exception("Unknown Operation Protocol.");
        }


        if (isset($_FILES['venue_pics']) && !empty($_FILES['venue_pics']['name'][0]))
        {
            $upload_dir = '../uploads/venues/';


            if (!is_dir($upload_dir))
            {
                mkdir($upload_dir, 0777, true);
            }

            $file_count = count($_FILES['venue_pics']['name']);
            $pic_success = 0;

            for ($i = 0; $i < $file_count; $i++)
            {
                $tmp_name = $_FILES['venue_pics']['tmp_name'][$i];
                $error = $_FILES['venue_pics']['error'][$i];

                if ($error === UPLOAD_ERR_OK && is_uploaded_file($tmp_name))
                {
                    $ext = strtolower(pathinfo($_FILES['venue_pics']['name'][$i], PATHINFO_EXTENSION));


                    if (in_array($ext, ['jpg', 'jpeg', 'png']))
                    {

                        $new_filename = $vid . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                        $destination = $upload_dir . $new_filename;

                        if (move_uploaded_file($tmp_name, $destination))
                        {

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
            if ($pic_success > 0)
            {
                $op_msg .= " ($pic_success visual assets mounted).";
            }
        }


        $conn->commit();
        $_SESSION['toast'] = ['type' => 'success', 'msg' => $op_msg];

    } catch (Exception $e)
    {

        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => "Transaction Aborted: " . $e->getMessage()];
    }


    header("Location: ../admin/venue_directory.php");
    exit;
} else
{
    header("Location: ../admin/venue_directory.php");
    exit;
}
?>

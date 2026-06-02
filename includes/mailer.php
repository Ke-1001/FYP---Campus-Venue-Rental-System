<?php
// File: includes/mailer.php

// 載入 Composer 自動加載器
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * 系統全域郵件派發引擎 (Global SMTP Dispatch Engine)
 * * @param string $to_email 目標信箱向量
 * @param string $to_name  目標實體名稱
 * @param string $subject  信件標題
 * @param string $body     純文字信件內容
 * @return bool            傳輸狀態 (True ≡ Success, False ≡ Failure)
 */
function dispatchSystemEmail($to_email, $to_name, $subject, $body) {
    // 實例化並啟用異常捕捉
    $mail = new PHPMailer(true);

    try {
        // 💡 1. 伺服器硬體拓撲設定 (Server Topology Settings)
        $mail->isSMTP();                                            // 強制使用 SMTP 協定
        $mail->Host       = 'smtp.gmail.com';                     // 替換為企業 SMTP 伺服器 (此處以 Gmail 為例)
        $mail->SMTPAuth   = true;                                   // 啟用 SMTP 密碼驗證
        
        // ⚠️ 安全宣告：此處應替換為專用的發信帳戶與 App Password (應用程式密碼)
        $mail->Username   = 'noreply.cvbms@gmail.com';          
        $mail->Password   = 'mtlg hlqw gcrq wsuw';           
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // 採用 TLS 加密通道
        $mail->Port       = 587;  
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            )
        );                                  // STARTTLS 標準連接埠

        // 💡 2. 實體位址定義 (Entity Address Definition)
        $mail->setFrom('noreply.cvbms@gmail.com', 'CVBMS Automated System');
        $mail->addAddress($to_email, $to_name);

        // 💡 3. 封包內容建構 (Payload Construction)
        $mail->isHTML(false);                                       // 為防止 XSS 注入與提高到達率，採用純文字渲染
        $mail->Subject = $subject;
        $mail->Body    = $body;

        // 執行傳輸
        $mail->send();
        return true;

    } catch (Exception $e) {
        // 💡 暫時替換為這三行：強制印出錯誤並中斷程式，防止頁面跳轉
        echo "<h3>SMTP 連線崩潰，底層錯誤日誌如下：</h3>";
        echo "<pre>{$mail->ErrorInfo}</pre>";
        exit; 
    }
}
?>
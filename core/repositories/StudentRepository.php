<?php
// File: core/repositories/StudentRepository.php

namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class StudentRepository {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * 獲取學生目錄矩陣 (Student Directory Matrix)
     */
    public function getAllStudents(FilterBuilder $filterBuilder, string $sortOption) {
        // ∴ 引入 SQL 投影別名 (account_status AS status)，無縫對接 DataGrid 渲染引擎
        $sql = "SELECT uid, username, email, phone_num, account_status AS status, created_at FROM user WHERE 1=1";

        $sql .= $filterBuilder->buildSqlWhere($this->conn);

        switch ($sortOption) {
            case 'oldest':
                $sql .= " ORDER BY created_at ASC";
                break;
            case 'name_asc':
                $sql .= " ORDER BY username ASC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY created_at DESC";
                break;
        }
        
        return $this->conn->query($sql);
    }

    /**
     * 提取單一學生實體拓撲 (Single Entity Extraction)
     * @return array|null 關聯矩陣
     */
    public function getStudentById(string $uid): ?array {
        // ∴ 同步別名映射，確保 edit_student.php 狀態機 (FioriFormBuilder) 正常讀取
        $sql = "SELECT uid, username, email, phone_num, account_status AS status, created_at FROM user WHERE uid = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Statement Preparation Fault in StudentRepository: " . $this->conn->error);
            return null;
        }

        $stmt->bind_param("s", $uid);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $entity = $result->fetch_assoc();
        
        $stmt->close();
        
        return $entity ?: null;
    }
}
?>
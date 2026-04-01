<?php
// ============================================
// FUNGSI PENGURUSAN STAFF (PLAIN TEXT VERSION)
// ============================================

require_once 'database.php';

function addNewStaff($pdo, $nama, $cawangan_id, $peranan, $password) {
    // Check if staff already exists
    $check = $pdo->prepare("SELECT id FROM staf WHERE nama = ?");
    $check->execute([$nama]);
    
    if($check->rowCount() > 0) {
        return ['success' => false, 'message' => "Staf dengan nama '$nama' sudah wujud!"];
    }
    
    // Simpan password dalam plain text (tanpa hash)
    $stmt = $pdo->prepare("INSERT INTO staf (nama, cawangan_id, peranan, password) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$nama, $cawangan_id, $peranan, $password]);
    
    if($result) {
        return ['success' => true, 'message' => "✅ Staf '$nama' berjaya ditambah! Password: $password", 'id' => $pdo->lastInsertId()];
    } else {
        return ['success' => false, 'message' => "❌ Gagal menambah staf!"];
    }
}

function resetStaffPassword($pdo, $staff_id, $new_password) {
    // Check if staff exists
    $check = $pdo->prepare("SELECT id, nama FROM staf WHERE id = ?");
    $check->execute([$staff_id]);
    $staff = $check->fetch(PDO::FETCH_ASSOC);
    
    if(!$staff) {
        return ['success' => false, 'message' => "Staf tidak dijumpai!"];
    }
    
    // Update password terus (plain text)
    $stmt = $pdo->prepare("UPDATE staf SET password = ? WHERE id = ?");
    $result = $stmt->execute([$new_password, $staff_id]);
    
    if($result) {
        return ['success' => true, 'message' => "✅ Password untuk {$staff['nama']} telah direset kepada: $new_password"];
    } else {
        return ['success' => false, 'message' => "❌ Gagal reset password!"];
    }
}

function deleteStaff($pdo, $staff_id) {
    // Check if staff exists
    $check = $pdo->prepare("SELECT nama FROM staf WHERE id = ?");
    $check->execute([$staff_id]);
    $staff = $check->fetch(PDO::FETCH_ASSOC);
    
    if(!$staff) {
        return ['success' => false, 'message' => "Staf tidak dijumpai!"];
    }
    
    // Check if staff has appointments
    $appointments = $pdo->prepare("SELECT COUNT(*) FROM janji WHERE staf_id = ?");
    $appointments->execute([$staff_id]);
    $count = $appointments->fetchColumn();
    
    if($count > 0) {
        return ['success' => false, 'message' => "Staf mempunyai $count janji temu. Tidak boleh dipadam!"];
    }
    
    // Delete staff
    $stmt = $pdo->prepare("DELETE FROM staf WHERE id = ?");
    $result = $stmt->execute([$staff_id]);
    
    if($result) {
        return ['success' => true, 'message' => "✅ Staf {$staff['nama']} telah dipadam!"];
    } else {
        return ['success' => false, 'message' => "❌ Gagal memadam staf!"];
    }
}

function getAllStaff($pdo) {
    $stmt = $pdo->query("
        SELECT s.*, c.nama as cawangan_nama 
        FROM staf s 
        LEFT JOIN cawangan c ON s.cawangan_id = c.id 
        ORDER BY s.id
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStaffById($pdo, $staff_id) {
    $stmt = $pdo->prepare("SELECT * FROM staf WHERE id = ?");
    $stmt->execute([$staff_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
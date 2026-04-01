<?php
// branch-management.php
require_once 'database.php';

function getAllBranches($pdo) {
    $stmt = $pdo->query("SELECT * FROM cawangan ORDER BY id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addBranch($pdo, $nama, $alamat, $telefon) {
    $check = $pdo->prepare("SELECT id FROM cawangan WHERE nama = ?");
    $check->execute([$nama]);
    if ($check->rowCount() > 0) {
        return ['success' => false, 'message' => "Cawangan '$nama' sudah wujud!"];
    }
    $stmt = $pdo->prepare("INSERT INTO cawangan (nama, alamat, telefon) VALUES (?, ?, ?)");
    $result = $stmt->execute([$nama, $alamat, $telefon]);
    if ($result) {
        return ['success' => true, 'message' => "✅ Cawangan '$nama' berjaya ditambah!"];
    } else {
        return ['success' => false, 'message' => "❌ Gagal menambah cawangan!"];
    }
}

function updateBranch($pdo, $id, $nama, $alamat, $telefon) {
    $stmt = $pdo->prepare("UPDATE cawangan SET nama = ?, alamat = ?, telefon = ? WHERE id = ?");
    $result = $stmt->execute([$nama, $alamat, $telefon, $id]);
    return $result ? ['success' => true, 'message' => "✅ Cawangan dikemas kini!"] : ['success' => false, 'message' => "❌ Gagal mengemas kini!"];
}

function deleteBranch($pdo, $id) {
    // Semak jika ada staf atau janji yang menggunakan cawangan ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM staf WHERE cawangan_id = ?");
    $stmt->execute([$id]);
    $staffCount = $stmt->fetchColumn();
    if ($staffCount > 0) {
        return ['success' => false, 'message' => "Cawangan masih digunakan oleh $staffCount staf. Tidak boleh dipadam!"];
    }
    $stmt = $pdo->prepare("DELETE FROM cawangan WHERE id = ?");
    $result = $stmt->execute([$id]);
    return $result ? ['success' => true, 'message' => "✅ Cawangan dipadam!"] : ['success' => false, 'message' => "❌ Gagal memadam!"];
}
?>
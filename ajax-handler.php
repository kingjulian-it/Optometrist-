<?php
// ============================================
// AJAX REQUEST HANDLER
// ============================================

require_once 'database.php';
require_once 'reminder.php';
require_once 'chatbot.php';
require_once 'staff-management.php';

function handleAjaxRequest($pdo) {
    if($_POST['ajax'] == 'whatsapp_webhook') {
        $bot = new WhatsAppBot($pdo);
        $result = $bot->handleMessage($_POST['phone'], $_POST['message']);
        echo json_encode(['success' => true, 'result' => $result]);
        exit;
    }
    
    if($_POST['ajax'] == 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if($username == 'admin' && $password == 'admin123') {
            $_SESSION['admin_logged_in'] = true;
            echo json_encode(['success' => true]);
            exit;
        }
        
        // Staff login - PLAIN TEXT (ID dan password terus)
        $stmt = $pdo->prepare("SELECT * FROM staf WHERE id = ? AND password = ?");
        $stmt->execute([$username, $password]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($staff) {
            $_SESSION['staff_id'] = $staff['id'];
            $_SESSION['staff_name'] = $staff['nama'];
            echo json_encode(['success' => true, 'type' => 'staff']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Kata laluan salah']);
        }
        exit;
    }
    
    if($_POST['ajax'] == 'get_cawangan') {
        $stmt = $pdo->query("SELECT * FROM cawangan ORDER BY id");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
    
    if($_POST['ajax'] == 'get_staf') {
        $cawangan_id = $_POST['cawangan_id'] ?? 0;
        if($cawangan_id) {
            $stmt = $pdo->prepare("SELECT * FROM staf WHERE cawangan_id = ?");
            $stmt->execute([$cawangan_id]);
        } else {
            $stmt = $pdo->query("SELECT s.*, c.nama as cawangan_nama FROM staf s LEFT JOIN cawangan c ON s.cawangan_id = c.id");
        }
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
    
    if($_POST['ajax'] == 'get_available_slots') {
        $staf_id = $_POST['staf_id'];
        $tarikh = $_POST['tarikh'];
        
        $stmt = $pdo->prepare("SELECT masa FROM janji WHERE staf_id = ? AND tarikh = ? AND status != 'cancelled'");
        $stmt->execute([$staf_id, $tarikh]);
        $booked = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode(['booked' => $booked]);
        exit;
    }
    
    if($_POST['ajax'] == 'save_booking') {
        $data = $_POST;
        
        $stmt = $pdo->prepare("
            INSERT INTO janji (pelanggan, telefon, email, ic, cawangan_id, staf_id, tarikh, masa, jenis_layanan, catatan, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')
        ");
        
        $stmt->execute([
            $data['pelanggan'],
            $data['telefon'],
            $data['email'],
            $data['ic'] ?? '',
            $data['cawangan_id'],
            $data['staf_id'],
            $data['tarikh'],
            $data['masa'],
            $data['jenis_layanan'],
            $data['catatan'] ?? ''
        ]);
        
        $bookingId = $pdo->lastInsertId();
        
        echo json_encode(['success' => true, 'booking_id' => $bookingId]);
        exit;
    }
    
    if($_POST['ajax'] == 'get_today_schedule') {
        $staff_id = $_POST['staff_id'];
        $tarikh = $_POST['tarikh'];
        
        $stmt = $pdo->prepare("
            SELECT * FROM janji 
            WHERE staf_id = ? AND tarikh = ? 
            ORDER BY masa ASC
        ");
        $stmt->execute([$staff_id, $tarikh]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
    
    if($_POST['ajax'] == 'update_status') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE janji SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    if($_POST['ajax'] == 'send_reminders') {
        checkAndSendReminders($pdo);
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Reset staff password via AJAX (plain text)
    if($_POST['ajax'] == 'reset_staff_password') {
        if(!isset($_SESSION['admin_logged_in'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $staff_id = $_POST['staff_id'] ?? 0;
        $new_password = $_POST['new_password'] ?? '';
        
        if(!$staff_id || !$new_password) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }
        
        $result = resetStaffPassword($pdo, $staff_id, $new_password);
        echo json_encode($result);
        exit;
    }
    
    // Delete staff via AJAX
    if($_POST['ajax'] == 'delete_staff') {
        if(!isset($_SESSION['admin_logged_in'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $staff_id = $_POST['staff_id'] ?? 0;
        
        if(!$staff_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }
        
        $result = deleteStaff($pdo, $staff_id);
        echo json_encode($result);
        exit;
    }
}
?>
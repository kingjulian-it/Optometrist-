<?php
// ============================================
// FUNGSI REMINDER 24 JAM
// ============================================

require_once 'database.php';
require_once 'functions.php';

function sendWhatsAppMessage($phone, $message) {
    $apiKey = WHATSAPP_API_KEY;
    if($apiKey == 'YOUR_FONNTE_API_KEY') {
        error_log("WA Demo to $phone: $message");
        return true;
    }
    
    $data = [
        'target' => $phone,
        'message' => $message,
        'countryCode' => '60'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, WHATSAPP_API_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $apiKey]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return true;
}

function sendEmailMessage($to, $subject, $message) {
    $headers = "From: Optometris Malaysia <noreply@optometris.my>\r\n";
    $headers .= "Reply-To: support@optometris.my\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

function sendReminderNotifications($pdo, $appointment) {
    $success = false;
    
    $message = "🔔 *Peringatan Temu Janji Optometris* 🔔\n\n";
    $message .= "Assalamualaikum/ Hai *{$appointment['pelanggan']}*,\n\n";
    $message .= "Ini adalah peringatan mesra untuk temu janji anda *ESOK*:\n\n";
    $message .= "📅 *Tarikh:* " . date('d/m/Y', strtotime($appointment['tarikh'])) . "\n";
    $message .= "⏰ *Masa:* {$appointment['masa']}\n";
    $message .= "🏢 *Cawangan:* {$appointment['cawangan_nama']}\n";
    $message .= "👨‍⚕️ *Optometris:* {$appointment['staf_nama']}\n";
    $message .= "🔧 *Layanan:* {$appointment['jenis_layanan']}\n\n";
    $message .= "✨ *Sila datang 10 minit awal*\n";
    $message .= "📞 *Sebarang perubahan:* 03-1234 5678\n\n";
    $message .= "Terima kasih!";
    
    if(!empty($appointment['telefon'])) {
        $wa_sent = sendWhatsAppMessage($appointment['telefon'], $message);
        if($wa_sent) $success = true;
    }
    
    if(!empty($appointment['email'])) {
        $email_sent = sendEmailMessage($appointment['email'], "Peringatan Temu Janji Optometris", $message);
        if($email_sent) $success = true;
    }
    
    return $success;
}

function checkAndSendReminders($pdo) {
    $esok = date('Y-m-d', strtotime('+1 day'));
    
    $stmt = $pdo->prepare("
        SELECT j.*, c.nama as cawangan_nama, s.nama as staf_nama 
        FROM janji j
        LEFT JOIN cawangan c ON j.cawangan_id = c.id
        LEFT JOIN staf s ON j.staf_id = s.id
        WHERE j.tarikh = ? 
        AND j.status = 'confirmed'
        AND (j.reminder_sent IS NULL OR j.reminder_sent = 0)
    ");
    $stmt->execute([$esok]);
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = 0;
    foreach($reminders as $r) {
        $sent = sendReminderNotifications($pdo, $r);
        if($sent) {
            $update = $pdo->prepare("UPDATE janji SET reminder_sent = 1, reminder_time = NOW() WHERE id = ?");
            $update->execute([$r['id']]);
            $count++;
        }
    }
    
    if($count > 0) {
        error_log("✅ $count reminder dihantar untuk tarikh $esok");
    }
}
?>
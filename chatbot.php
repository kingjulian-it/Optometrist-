<?php
// ============================================
// WHATSAPP CHATBOT CLASS
// ============================================

require_once 'database.php';
require_once 'reminder.php';

class WhatsAppBot {
    private $apiKey;
    private $apiUrl;
    private $pdo;
    
    public function __construct($pdo) {
        $this->apiKey = WHATSAPP_API_KEY;
        $this->apiUrl = WHATSAPP_API_URL;
        $this->pdo = $pdo;
    }
    
    public function handleMessage($phone, $message) {
        $message = trim(strtolower($message));
        
        $stmt = $this->pdo->prepare("SELECT * FROM chat_sessions WHERE phone = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([$phone]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            return $this->sendMainMenu($phone);
        }
        
        $sessionData = json_decode($session['session_data'], true) ?? [];
        $state = $sessionData['state'] ?? 'MAIN_MENU';
        
        switch($state) {
            case 'MAIN_MENU':
                return $this->handleMainMenu($phone, $message);
            case 'SELECT_BRANCH':
                return $this->handleBranchSelection($phone, $message);
            case 'SELECT_STAFF':
                return $this->handleStaffSelection($phone, $message);
            case 'SELECT_DATE':
                return $this->handleDateSelection($phone, $message);
            case 'SELECT_TIME':
                return $this->handleTimeSelection($phone, $message);
            case 'GET_DETAILS':
                return $this->handleGetDetails($phone, $message);
            default:
                return $this->sendMainMenu($phone);
        }
    }
    
    private function sendMainMenu($phone) {
        $menu = "👁️ *Optometris Malaysia - Chatbot* 👁️\n\n";
        $menu .= "Selamat datang ke perkhidmatan WhatsApp kami!\n\n";
        $menu .= "Sila pilih:\n\n";
        $menu .= "1️⃣ *Buat Temu Janji Baru*\n";
        $menu .= "2️⃣ *Semak Status Booking*\n";
        $menu .= "3️⃣ *Batalkan Temu Janji*\n";
        $menu .= "4️⃣ *Hubungi Kami*\n";
        $menu .= "5️⃣ *Info Cawangan*\n\n";
        $menu .= "Balas dengan *nombor* pilihan anda (1-5)";
        
        $this->saveSession($phone, ['state' => 'MAIN_MENU', 'data' => []]);
        return $this->sendWhatsApp($phone, $menu);
    }
    
    private function handleMainMenu($phone, $message) {
        switch($message) {
            case '1':
            case '1️⃣':
                return $this->startBooking($phone);
            case '2':
            case '2️⃣':
                return $this->askBookingNumber($phone, 'CHECK');
            case '3':
            case '3️⃣':
                return $this->askBookingNumber($phone, 'CANCEL');
            case '4':
            case '4️⃣':
                return $this->sendContactInfo($phone);
            case '5':
            case '5️⃣':
                return $this->sendBranchInfo($phone);
            default:
                return $this->sendMainMenu($phone);
        }
    }
    
    private function startBooking($phone) {
        $stmt = $this->pdo->query("SELECT * FROM cawangan ORDER BY id");
        $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $branchList = "🏢 *Pilih Cawangan*\n\n";
        foreach($branches as $index => $branch) {
            $branchList .= ($index + 1) . ". {$branch['nama']}\n📞 {$branch['telefon']}\n\n";
        }
        $branchList .= "\nBalas dengan *nombor* cawangan pilihan anda (cth: 1)";
        
        $this->saveSession($phone, [
            'state' => 'SELECT_BRANCH',
            'data' => ['branches' => $branches]
        ]);
        
        return $this->sendWhatsApp($phone, $branchList);
    }
    
    private function handleBranchSelection($phone, $message) {
        $session = $this->getSession($phone);
        $index = intval($message) - 1;
        
        if (!isset($session['data']['branches'][$index])) {
            $this->sendWhatsApp($phone, "❌ Nombor tidak sah. Sila pilih nombor cawangan yang betul.");
            return $this->startBooking($phone);
        }
        
        $selectedBranch = $session['data']['branches'][$index];
        
        $stmt = $this->pdo->prepare("SELECT * FROM staf WHERE cawangan_id = ?");
        $stmt->execute([$selectedBranch['id']]);
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($staff)) {
            $this->sendWhatsApp($phone, "❌ Tiada optometris di cawangan ini buat masa ini.");
            return $this->sendMainMenu($phone);
        }
        
        $staffList = "👨‍⚕️ *Optometris di {$selectedBranch['nama']}*\n\n";
        foreach($staff as $index => $s) {
            $staffList .= ($index + 1) . ". {$s['nama']} - {$s['peranan']}\n";
        }
        $staffList .= "\nBalas dengan *nombor* optometris pilihan anda";
        
        $session['data']['selectedBranch'] = $selectedBranch;
        $session['data']['staff'] = $staff;
        $session['state'] = 'SELECT_STAFF';
        $this->saveSession($phone, $session);
        
        return $this->sendWhatsApp($phone, $staffList);
    }
    
    private function handleStaffSelection($phone, $message) {
        $session = $this->getSession($phone);
        $index = intval($message) - 1;
        
        if (!isset($session['data']['staff'][$index])) {
            $this->sendWhatsApp($phone, "❌ Nombor tidak sah. Sila pilih optometris yang betul.");
            return $this->startBooking($phone);
        }
        
        $selectedStaff = $session['data']['staff'][$index];
        $session['data']['selectedStaff'] = $selectedStaff;
        
        $dates = [];
        for($i = 1; $i <= 7; $i++) {
            $dates[] = date('Y-m-d', strtotime("+$i days"));
        }
        $session['data']['availableDates'] = $dates;
        
        $dateList = "📅 *Pilih Tarikh*\n\n";
        foreach($dates as $index => $date) {
            $dateList .= ($index + 1) . ". " . date('d/m/Y (l)', strtotime($date)) . "\n";
        }
        $dateList .= "\nBalas dengan *nombor* tarikh pilihan anda";
        
        $session['state'] = 'SELECT_DATE';
        $this->saveSession($phone, $session);
        
        return $this->sendWhatsApp($phone, $dateList);
    }
    
    private function handleDateSelection($phone, $message) {
        $session = $this->getSession($phone);
        $index = intval($message) - 1;
        
        if (!isset($session['data']['availableDates'][$index])) {
            $this->sendWhatsApp($phone, "❌ Nombor tidak sah. Sila pilih tarikh yang betul.");
            return $this->handleStaffSelection($phone, $message);
        }
        
        $selectedDate = $session['data']['availableDates'][$index];
        $session['data']['selectedDate'] = $selectedDate;
        
        $stmt = $this->pdo->prepare("SELECT masa FROM janji WHERE staf_id = ? AND tarikh = ? AND status != 'cancelled'");
        $stmt->execute([$session['data']['selectedStaff']['id'], $selectedDate]);
        $booked = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $slots = [];
        for($hour = 9; $hour <= 17; $hour++) {
            for($min = 0; $min <= 30; $min += 30) {
                if($hour == 17 && $min == 30) continue;
                $time = sprintf("%02d:%02d", $hour, $min);
                if(!in_array($time, $booked)) {
                    $slots[] = $time;
                }
            }
        }
        
        if(empty($slots)) {
            $this->sendWhatsApp($phone, "❌ Tiada slot kosong pada tarikh ini. Sila pilih tarikh lain.");
            return $this->handleStaffSelection($phone, $message);
        }
        
        $session['data']['availableSlots'] = $slots;
        
        $slotList = "⏰ *Pilih Masa*\n\n";
        foreach(array_slice($slots, 0, 10) as $index => $slot) {
            $slotList .= ($index + 1) . ". $slot\n";
        }
        if(count($slots) > 10) {
            $slotList .= "\n...dan " . (count($slots) - 10) . " slot lagi. Balas *LAGI* untuk lebih banyak pilihan.\n";
        }
        $slotList .= "\nBalas dengan *nombor* masa pilihan anda";
        
        $session['state'] = 'SELECT_TIME';
        $session['data']['slotPage'] = 0;
        $this->saveSession($phone, $session);
        
        return $this->sendWhatsApp($phone, $slotList);
    }
    
    private function handleTimeSelection($phone, $message) {
        $session = $this->getSession($phone);
        
        if(strtolower($message) == 'lagi') {
            $session['data']['slotPage']++;
            $start = $session['data']['slotPage'] * 10;
            $slots = array_slice($session['data']['availableSlots'], $start, 10);
            
            if(empty($slots)) {
                $this->sendWhatsApp($phone, "❌ Tiada lagi slot.");
                return $this->handleTimeSelection($phone, '1');
            }
            
            $slotList = "⏰ *Lagi Pilihan Masa*\n\n";
            foreach($slots as $index => $slot) {
                $slotList .= ($start + $index + 1) . ". $slot\n";
            }
            if(count($session['data']['availableSlots']) > $start + 10) {
                $slotList .= "\nBalas *LAGI* untuk lebih banyak pilihan.\n";
            }
            $slotList .= "\nBalas dengan *nombor* masa pilihan anda";
            
            $this->saveSession($phone, $session);
            return $this->sendWhatsApp($phone, $slotList);
        }
        
        $index = intval($message) - 1;
        if(!isset($session['data']['availableSlots'][$index])) {
            $this->sendWhatsApp($phone, "❌ Nombor tidak sah. Sila pilih masa yang betul.");
            return $this->handleTimeSelection($phone, '1');
        }
        
        $session['data']['selectedTime'] = $session['data']['availableSlots'][$index];
        
        $details = "📝 *Maklumat Peribadi*\n\n";
        $details .= "Untuk melengkapkan booking, sila berikan dalam format:\n\n";
        $details .= "*Nama, No IC, Jenis Layanan*\n\n";
        $details .= "Contoh: *Ali bin Abu, 900101-01-1234, Pemeriksaan Biasa*\n\n";
        $details .= "Jenis Layanan:\n";
        $details .= "- Pemeriksaan Biasa\n";
        $details .= "- Lensa Kontak\n";
        $details .= "- Konsultasi\n";
        $details .= "- Pengambilan Cermin Mata";
        
        $session['state'] = 'GET_DETAILS';
        $this->saveSession($phone, $session);
        
        return $this->sendWhatsApp($phone, $details);
    }
    
    private function handleGetDetails($phone, $message) {
        $session = $this->getSession($phone);
        $parts = explode(',', $message);
        
        if(count($parts) < 3) {
            $this->sendWhatsApp($phone, "❌ Format tidak lengkap. Sila guna format:\n\nNama, No IC, Jenis Layanan");
            return true;
        }
        
        $nama = trim($parts[0]);
        $ic = trim($parts[1]);
        $layanan = trim($parts[2]);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO janji (pelanggan, telefon, ic, cawangan_id, staf_id, tarikh, masa, jenis_layanan, status, source) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'whatsapp')
        ");
        
        $stmt->execute([
            $nama,
            $phone,
            $ic,
            $session['data']['selectedBranch']['id'],
            $session['data']['selectedStaff']['id'],
            $session['data']['selectedDate'],
            $session['data']['selectedTime'],
            $layanan
        ]);
        
        $bookingId = $this->pdo->lastInsertId();
        
        $confirmation = "✅ *TEMU JANJI BERJAYA!* ✅\n\n";
        $confirmation .= "*Nombor Rujukan:* #$bookingId\n\n";
        $confirmation .= "📋 *Butiran:*\n";
        $confirmation .= "👤 Nama: $nama\n";
        $confirmation .= "🏢 : {$session['data']['selectedBranch']['nama']}\n";
        $confirmation .= "👨‍⚕️ Optometris: {$session['data']['selectedStaff']['nama']}\n";
        $confirmation .= "📅 Tarikh: {$session['data']['selectedDate']}\n";
        $confirmation .= "⏰ Masa: {$session['data']['selectedTime']}\n";
        $confirmation .= "🔧 Layanan: $layanan\n\n";
        $confirmation .= "*Simpan nombor rujukan ini untuk semakan kemudian.*\n\n";
        $confirmation .= "Balas *MENU* untuk kembali ke menu utama.";
        
        $stmt = $this->pdo->prepare("DELETE FROM chat_sessions WHERE phone = ?");
        $stmt->execute([$phone]);
        
        return $this->sendWhatsApp($phone, $confirmation);
    }
    
    private function askBookingNumber($phone, $action) {
        $message = ($action == 'CHECK') 
            ? "🔍 *Semak Status Booking*\n\nSila masukkan nombor rujukan anda:\nContoh: #12345"
            : "❌ *Batalkan Temu Janji*\n\nSila masukkan nombor rujukan yang ingin dibatalkan:\nContoh: #12345";
        
        $this->saveSession($phone, [
            'state' => ($action == 'CHECK') ? 'CHECK_BOOKING' : 'CANCEL_BOOKING',
            'data' => []
        ]);
        
        return $this->sendWhatsApp($phone, $message);
    }
    
    private function sendContactInfo($phone) {
        $contact = "📞 *Hubungi Kami*\n\n";
        $contact .= "📱 Talian Utama: 03-1234 5678\n";
        $contact .= "📧 Email: support@optometris.my\n";
        $contact .= "🌐 Website: www.optometris.my\n\n";
        $contact .= "⏰ Waktu Operasi:\n";
        $contact .= "Isnin - Jumaat: 9:00 pagi - 6:00 petang\n";
        $contact .= "Sabtu: 9:00 pagi - 1:00 petang\n";
        $contact .= "Ahad & Cuti Umum: Tutup\n\n";
        $contact .= "Balas *MENU* untuk kembali.";
        
        $stmt = $this->pdo->prepare("DELETE FROM chat_sessions WHERE phone = ?");
        $stmt->execute([$phone]);
        
        return $this->sendWhatsApp($phone, $contact);
    }
    
    private function sendBranchInfo($phone) {
        $stmt = $this->pdo->query("SELECT * FROM ");
        $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $info = "🏢 *Senarai Cawangan*\n\n";
        foreach($branches as $branch) {
            $info .= "*{$branch['nama']}*\n";
            $info .= "📍 {$branch['alamat']}\n";
            $info .= "📞 {$branch['telefon']}\n\n";
        }
        $info .= "Balas *MENU* untuk kembali.";
        
        $stmt = $this->pdo->prepare("DELETE FROM chat_sessions WHERE phone = ?");
        $stmt->execute([$phone]);
        
        return $this->sendWhatsApp($phone, $info);
    }
    
    private function saveSession($phone, $data) {
        $jsonData = json_encode($data);
        $stmt = $this->pdo->prepare("
            INSERT INTO chat_sessions (phone, session_data, last_message, status) 
            VALUES (?, ?, ?, 'active')
            ON DUPLICATE KEY UPDATE 
            session_data = VALUES(session_data),
            updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$phone, $jsonData, $data['state'] ?? '']);
    }
    
    private function getSession($phone) {
        $stmt = $this->pdo->prepare("SELECT session_data FROM chat_sessions WHERE phone = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([$phone]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? json_decode($result['session_data'], true) : ['state' => 'MAIN_MENU', 'data' => []];
    }
    
    private function sendWhatsApp($to, $message) {
        if(empty($this->apiKey) || $this->apiKey == 'YOUR_FONNTE_API_KEY') {
            error_log("WA to $to: $message");
            return true;
        }
        
        $data = [
            'target' => $to,
            'message' => $message,
            'countryCode' => '60'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $this->apiKey
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
}
?>
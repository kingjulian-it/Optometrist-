<?php
// ============================================
// FUNGSI DATABASE
// ============================================

require_once 'config.php';

function getDB() {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
    try {
        $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

function executeQuery($pdo, $sql, $params = []) {
    $start = microtime(true);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $end = microtime(true);
    
    $time = round(($end - $start) * 1000, 2);
    
    if($time > 200) {
        error_log("⚠️ SLOW QUERY ({$time}ms): $sql");
    }
    
    return $stmt;
}

function initializeDatabase() {
    $pdo = getDB();
    
    // Check if reminder_sent column exists
    try {
        $pdo->query("SELECT reminder_sent FROM janji LIMIT 1");
    } catch(PDOException $e) {
        $pdo->exec("ALTER TABLE janji 
                    ADD COLUMN reminder_sent TINYINT(1) DEFAULT 0,
                    ADD COLUMN reminder_time DATETIME NULL");
    }
    
    // Create tables if not exist
    $sql = "
    CREATE TABLE IF NOT EXISTS cawangan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(100) NOT NULL,
        alamat TEXT,
        telefon VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS staf (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(100) NOT NULL,
        cawangan_id INT,
        peranan VARCHAR(50),
        password VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (_id) REFERENCES cawangan(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS janji (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pelanggan VARCHAR(100) NOT NULL,
        telefon VARCHAR(20),
        email VARCHAR(100),
        ic VARCHAR(20),
        _id INT,
        staf_id INT,
        tarikh DATE,
        masa TIME,
        jenis_layanan VARCHAR(50),
        catatan TEXT,
        status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
        source VARCHAR(20) DEFAULT 'web',
        reminder_sent TINYINT(1) DEFAULT 0,
        reminder_time DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (cawangan_id) REFERENCES cawangan(id) ON DELETE SET NULL,
        FOREIGN KEY (staf_id) REFERENCES staf(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS medical_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pelanggan_id INT,
        r_sph VARCHAR(10),
        r_cyl VARCHAR(10),
        r_axis VARCHAR(10),
        l_sph VARCHAR(10),
        l_cyl VARCHAR(10),
        l_axis VARCHAR(10),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        janji_id INT,
        pelanggan VARCHAR(100),
        staf_id INT,
        cawangan_id INT,
        rating INT CHECK (rating >= 1 AND rating <= 5),
        review TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (janji_id) REFERENCES janji(id) ON DELETE CASCADE,
        FOREIGN KEY (staf_id) REFERENCES staf(id) ON DELETE SET NULL,
        FOREIGN KEY (cawangan_id) REFERENCES cawangan(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS chat_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20),
        session_data TEXT,
        last_message TEXT,
        status VARCHAR(20) DEFAULT 'active',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    try {
        $pdo->exec($sql);
        
        // Insert default data if empty
        $count = $pdo->query("SELECT COUNT(*) FROM cawangan")->fetchColumn();
        if ($count == 0) {
            $pdo->exec("
                INSERT INTO cawangan (nama, alamat, telefon) VALUES
                ('Optometris Wahida', 'Lot 39', 'Plot 21', 'Cmart BDI', '06000 JITRA', '019-4746648'),
                ('Optometris Eyemaster', '1589', 'Wisma DTC', 'Jalan Sultan Badlishah', '05000 Alor Setar', '019-334 7674'),
                ('Optometris Ameera', 'No 17, 'Persiaran Pendang Square 1', '06700 Pendang', '013-948 7848'),
                ('Optometris Shaharudin', 'no. 23C', 'Jalan Kampung Baru', '08000 Sungai Petani', '019-4746648');
            ");
            
            $stmt = $pdo->prepare("INSERT INTO staf (nama, cawangan_id, peranan, password) VALUES (?, ?, ?, ?)");
            
            $staff_data = [
                ['Dr. Aisyah', 1, 'Optometris Kanan', password_hash('aisyah123', PASSWORD_DEFAULT)],
                ['Dr. Ramesh', 1, 'Optometris', password_hash('ramesh123', PASSWORD_DEFAULT)],
                ['Dr. Ling', 2, 'Optometris', password_hash('ling123', PASSWORD_DEFAULT)],
                ['Dr. Fatimah', 3, 'Optometris', password_hash('fatimah123', PASSWORD_DEFAULT)]
            ];
            
            foreach($staff_data as $s) {
                $stmt->execute($s);
            }
        }
        
        return true;
    } catch(PDOException $e) {
        return false;
    }
}
?>
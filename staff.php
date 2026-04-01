<?php
// Halaman Staf - DENGAN BUTTON MATA 100% BERFUNGSI

$esok = date('Y-m-d', strtotime('+1 day'));
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM janji 
    WHERE tarikh = ? AND status = 'confirmed'
");
$stmt->execute([$esok]);
$esokCount = $stmt->fetchColumn();
?>

<div class="login-container" id="staffLoginSection">
    <h2 style="text-align: center;">👨‍⚕️ Log Masuk Staf</h2>
    
    <div class="form-group">
        <label>Pilih Nama:</label>
        <select id="staffSelect" onchange="checkStaffPassword()">
            <option value="">-- Pilih Staf --</option>
            <?php
            $stmt = $pdo->query("SELECT * FROM staf ORDER BY nama");
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<option value='{$row['id']}'>{$row['nama']} ({$row['peranan']})</option>";
            }
            ?>
        </select>
    </div>

    <div id="staffPasswordSection" style="display: none;">
        <div class="form-group">
            <label>Kata Laluan:</label>
            <div class="password-wrapper">
                <input type="password" id="staffPassword" placeholder="Masukkan kata laluan">
                <span class="toggle-password-btn" onclick="togglePassword(this)">👁️</span>
            </div>
        </div>
        <button class="btn btn-primary" onclick="staffLogin()" style="width: 100%;">Log Masuk</button>
        <p id="staffLoginError" class="error-message" style="color: red; margin-top: 10px;"></p>
    </div>
    
    <!-- Info untuk staf baru -->
    <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 10px; font-size: 14px;">
        <p style="margin-bottom: 5px;"><strong>📌 Staf Baru?</strong></p>
        <p>Password default akan diberikan oleh admin. Sila jumpa admin untuk reset password jika lupa.</p>
    </div>
</div>

<div id="staffDashboard" style="display: none;">
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2>Selamat Datang, <span id="staffName"></span></h2>
                <p id="staffCawangan"></p>
            </div>
            <button class="btn btn-outline" onclick="staffLogout()">Log Keluar</button>
        </div>
    </div>

    <?php if($esokCount > 0): ?>
    <div class="reminder-banner">
        <div class="reminder-icon">⏰</div>
        <div class="reminder-content">
            <h4>Peringatan: <?= $esokCount ?> Temu Janji Esok!</h4>
            <p>Jangan lupa prepare untuk pesakit esok. Sila semak jadual di bawah.</p>
        </div>
    </div>

    <script>
    function playReminderSound() {
        var audio = new Audio('https://www.soundjay.com/misc/sounds/bell-ringing-05.mp3');
        audio.play().catch(e => console.log('Sound blocked by browser'));
    }
    
    function showBrowserNotification() {
        if (!("Notification" in window)) return;
        
        if (Notification.permission === "granted") {
            new Notification("⏰ Peringatan Temu Janji Esok!", {
                body: "Ada <?= $esokCount ?> temu janji untuk esok. Sila semak jadual.",
                icon: "https://cdn-icons-png.flaticon.com/512/3075/3075909.png"
            });
        } else if (Notification.permission !== "denied") {
            Notification.requestPermission();
        }
    }
    
    window.onload = function() {
        playReminderSound();
        showBrowserNotification();
    }
    </script>
    <?php endif; ?>

    <div class="card">
        <h3>📅 Jadual Hari Ini - <span id="currentDate"></span></h3>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Masa</th>
                        <th>Pelanggan</th>
                        <th>Jenis</th>
                        <th>Telefon</th>
                        <th>Status</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>
                <tbody id="todaySchedule"></tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h3>📅 Jadual Esok - <?= date('d/m/Y', strtotime('+1 day')) ?></h3>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Masa</th>
                        <th>Pelanggan</th>
                        <th>Jenis</th>
                        <th>Telefon</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT j.* 
                        FROM janji j
                        WHERE j.tarikh = ? 
                        AND j.status = 'confirmed'
                        ORDER BY j.masa ASC
                    ");
                    $stmt->execute([$esok]);

                    if($stmt->rowCount() > 0) {
                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>
                                <td>{$row['masa']}</td>
                                <td>{$row['pelanggan']}</td>
                                <td>{$row['jenis_layanan']}</td>
                                <td>{$row['telefon']}</td>
                                <td><span class='status-badge status-confirmed'>Sah</span></td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align: center;'>Tiada temu janji untuk esok</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let currentStaffId = null;

function checkStaffPassword() {
    const staffId = document.getElementById('staffSelect').value;
    document.getElementById('staffPasswordSection').style.display = staffId ? 'block' : 'none';
}

function staffLogin() {
    const staffId = document.getElementById('staffSelect').value;
    const password = document.getElementById('staffPassword').value;
    
    if(!staffId || !password) {
        document.getElementById('staffLoginError').textContent = 'Sila pilih staf dan masukkan kata laluan';
        return;
    }
    
    fetch('?page=staff', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=login&username=' + encodeURIComponent(staffId) + '&password=' + encodeURIComponent(password)
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            document.getElementById('staffLoginSection').style.display = 'none';
            document.getElementById('staffDashboard').style.display = 'block';
            document.getElementById('staffName').textContent = document.getElementById('staffSelect').selectedOptions[0].text.split(' (')[0];
            
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('currentDate').textContent = new Date().toLocaleDateString('ms-MY', options);
            
            loadTodaySchedule(staffId);
        } else {
            document.getElementById('staffLoginError').textContent = 'Kata laluan salah';
        }
    });
}

function loadTodaySchedule(staffId) {
    const today = new Date().toISOString().split('T')[0];
    
    fetch('?page=staff', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=get_today_schedule&staff_id=' + staffId + '&tarikh=' + today
    })
    .then(res => res.json())
    .then(data => {
        let html = '';
        if(data.length === 0) {
            html = '<tr><td colspan="6" style="text-align: center;">Tiada temu janji hari ini</td></tr>';
        } else {
            data.forEach(j => {
                const statusClass = j.status === 'confirmed' ? 'status-confirmed' : 
                                   j.status === 'pending' ? 'status-pending' :
                                   j.status === 'completed' ? 'status-completed' : 'status-cancelled';
                
                const statusText = j.status === 'confirmed' ? 'Sah' :
                                  j.status === 'pending' ? 'Menunggu' :
                                  j.status === 'completed' ? 'Selesai' : 'Batal';
                
                html += `<tr>
                    <td>${j.masa}</td>
                    <td>${j.pelanggan}</td>
                    <td>${j.jenis_layanan}</td>
                    <td>${j.telefon}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td>
                        ${j.status !== 'completed' ? `
                            <button class="btn btn-success" onclick="updateStatus(${j.id}, 'completed')" style="padding: 4px 8px; font-size: 12px;">Selesai</button>
                        ` : ''}
                        ${j.status !== 'cancelled' ? `
                            <button class="btn btn-danger" onclick="updateStatus(${j.id}, 'cancelled')" style="padding: 4px 8px; font-size: 12px;">Batal</button>
                        ` : ''}
                    </td>
                </tr>`;
            });
        }
        document.getElementById('todaySchedule').innerHTML = html;
    });
}

function updateStatus(janjiId, status) {
    fetch('?page=staff', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=update_status&id=' + janjiId + '&status=' + status
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            location.reload();
        }
    });
}

function staffLogout() {
    location.reload();
}
</script>
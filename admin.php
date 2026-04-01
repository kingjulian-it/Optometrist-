<?php
// Halaman Admin - Dengan fungsi Tambah Staff, Reset Password & Padam Janji

if(!isset($_SESSION['admin_logged_in'])) {
    ?>
    <div class="login-container">
        <h2 style="text-align: center;">👑 Admin Login</h2>
        
        <form onsubmit="return adminLogin(event)">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" id="adminUsername" required>
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <div class="password-wrapper">
                    <input type="password" id="adminPassword" required>
                    <span class="toggle-password-btn" onclick="togglePassword(this)">👁️</span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Log Masuk</button>
            <p id="adminLoginError" style="color: red; margin-top: 10px;"></p>
        </form>
    </div>
    
    <script>
    function adminLogin(e) {
        e.preventDefault();
        
        const username = document.getElementById('adminUsername').value;
        const password = document.getElementById('adminPassword').value;
        
        fetch('?page=admin', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'ajax=login&username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password)
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                location.reload();
            } else {
                document.getElementById('adminLoginError').textContent = 'Invalid credentials';
            }
        });
        
        return false;
    }
    </script>
    <?php
} else {
    // Handle add staff form submission
    $message = '';
    $messageType = '';
    
    // ========== TAMBAHAN: Padam Janji ==========
    if(isset($_GET['delete_appointment'])) {
        $appointment_id = intval($_GET['delete_appointment']);
        if($appointment_id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM janji WHERE id = ?");
                $stmt->execute([$appointment_id]);
                if($stmt->rowCount() > 0) {
                    $message = "✅ Janji temu berjaya dipadamkan.";
                    $messageType = "success";
                } else {
                    $message = "❌ Rekod janji tidak ditemui.";
                    $messageType = "error";
                }
            } catch(Exception $e) {
                $message = "❌ Ralat semasa memadam: " . $e->getMessage();
                $messageType = "error";
            }
        } else {
            $message = "❌ ID janji tidak sah.";
            $messageType = "error";
        }
    }
    // ========== TAMAT TAMBAHAN ==========
    
    if(isset($_POST['add_staff'])) {
        $nama = $_POST['nama'] ?? '';
        $cawangan_id = $_POST['cawangan_id'] ?? 0;
        $peranan = $_POST['peranan'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if($nama && $cawangan_id && $peranan && $password) {
            require_once 'staff-management.php';
            $result = addNewStaff($pdo, $nama, $cawangan_id, $peranan, $password);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
        } else {
            $message = "❌ Sila isi semua maklumat!";
            $messageType = 'error';
        }
    }
    
    // Statistics
    $totalCawangan = $pdo->query("SELECT COUNT(*) FROM cawangan")->fetchColumn();
    $totalStaf = $pdo->query("SELECT COUNT(*) FROM staf")->fetchColumn();
    $totalJanji = $pdo->query("SELECT COUNT(*) FROM janji WHERE tarikh >= CURDATE()")->fetchColumn();
    $totalSelesai = $pdo->query("SELECT COUNT(*) FROM janji WHERE status = 'completed'")->fetchColumn();
    
    $esok = date('Y-m-d', strtotime('+1 day'));
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total, 
               SUM(CASE WHEN reminder_sent = 1 THEN 1 ELSE 0 END) as sent 
        FROM janji 
        WHERE tarikh = ?
    ");
    $stmt->execute([$esok]);
    $reminder_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get all staff
    require_once 'staff-management.php';
    $all_staff = getAllStaff($pdo);
    ?>
    
    <div class="card">
        <h2>👑 Admin Dashboard</h2>
        
        <!-- Message Alert -->
        <?php if($message): ?>
        <div class="alert-<?= $messageType ?>">
            <?= $message ?>
        </div>
        <?php endif; ?>
        
        <!-- Reminder Stats -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="color: white; margin-bottom: 10px;">📊 Status Reminder Esok</h3>
                <p style="opacity: 0.9;">Total Janji Esok: <strong><?= $reminder_stats['total'] ?></strong></p>
                <p style="opacity: 0.9;">Reminder Dihantar: <strong><?= $reminder_stats['sent'] ?></strong></p>
            </div>
            <button class="btn btn-outline" style="background: white; color: #667eea;" onclick="manualSendReminders()">
                📤 Hantar Manual
            </button>
        </div>
        
        <!-- Stats Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px;">
                <h4 style="margin-bottom: 10px;">Jumlah Cawangan</h4>
                <div style="font-size: 36px; font-weight: bold;"><?= $totalCawangan ?></div>
            </div>
            
            <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 20px; border-radius: 10px;">
                <h4 style="margin-bottom: 10px;">Jumlah Staf</h4>
                <div style="font-size: 36px; font-weight: bold;"><?= $totalStaf ?></div>
            </div>
            
            <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 20px; border-radius: 10px;">
                <h4 style="margin-bottom: 10px;">Janji Aktif</h4>
                <div style="font-size: 36px; font-weight: bold;"><?= $totalJanji ?></div>
            </div>
            
            <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 10px;">
                <h4 style="margin-bottom: 10px;">Selesai</h4>
                <div style="font-size: 36px; font-weight: bold;"><?= $totalSelesai ?></div>
            </div>
        </div>
        
        <!-- ADD STAFF FORM -->
        <div style="background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 30px;">
            <h3 style="margin-bottom: 20px;">➕ Tambah Staf Baru</h3>
            
            <form method="POST" action="">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Nama Penuh:</label>
                        <input type="text" name="nama" required style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Cawangan:</label>
                        <select name="cawangan_id" required style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px;">
                            <option value="">-- Pilih Cawangan --</option>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM cawangan ORDER BY nama");
                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$row['id']}'>{$row['nama']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Peranan:</label>
                        <input type="text" name="peranan" required placeholder="Contoh: Optometris" style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Password:</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="newStaffPassword" required placeholder="Min 4 karakter" style="width: 100%; padding: 12px 45px 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px;">
                            <span class="toggle-password-btn" onclick="togglePassword(this)">👁️</span>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="add_staff" class="btn btn-success" style="width: 100%; padding: 15px; font-size: 16px;">
                    ✅ Tambah Staf
                </button>
            </form>
        </div>
        
        <!-- STAFF LIST -->
        <div style="margin-bottom: 30px;">
            <h3 style="margin-bottom: 20px;">📋 Senarai Staf Sedia Ada (<?= count($all_staff) ?>)</h3>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Cawangan</th>
                            <th>Peranan</th>
                            <th>Password (Hash)</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($all_staff as $staff): ?>
                        <tr>
                            <td><?= $staff['id'] ?></td>
                            <td><strong><?= htmlspecialchars($staff['nama']) ?></strong></td>
                            <td><?= htmlspecialchars($staff['cawangan_nama'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($staff['peranan']) ?></td>
                            <td><small><?= substr($staff['password'], 0, 30) ?>...</small></td>
                            <td>
                                <div class="staff-actions">
                                    <button class="btn btn-warning btn-sm" onclick="resetStaffPassword(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['nama']) ?>')">
                                        🔄 Reset
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteStaff(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['nama']) ?>')">
                                        🗑️ Padam
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- ========== PENGURUSAN CAWANGAN ========== -->
        <?php
        // Include branch management functions
        require_once 'branch-management.php';

        // Handle branch form submissions
        if (isset($_POST['add_branch'])) {
            $nama = $_POST['branch_name'] ?? '';
            $alamat = $_POST['branch_address'] ?? '';
            $telefon = $_POST['branch_phone'] ?? '';
            if ($nama && $alamat && $telefon) {
                $result = addBranch($pdo, $nama, $alamat, $telefon);
                echo "<div class='alert-" . ($result['success'] ? 'success' : 'error') . "'>" . $result['message'] . "</div>";
            } else {
                echo "<div class='alert-error'>❌ Sila isi semua maklumat!</div>";
            }
        }

        if (isset($_POST['edit_branch'])) {
            $id = $_POST['branch_id'] ?? 0;
            $nama = $_POST['branch_name'] ?? '';
            $alamat = $_POST['branch_address'] ?? '';
            $telefon = $_POST['branch_phone'] ?? '';
            if ($id && $nama && $alamat && $telefon) {
                $result = updateBranch($pdo, $id, $nama, $alamat, $telefon);
                echo "<div class='alert-" . ($result['success'] ? 'success' : 'error') . "'>" . $result['message'] . "</div>";
            } else {
                echo "<div class='alert-error'>❌ Data tidak lengkap!</div>";
            }
        }

        if (isset($_GET['delete_branch'])) {
            $id = $_GET['delete_branch'];
            $result = deleteBranch($pdo, $id);
            echo "<div class='alert-" . ($result['success'] ? 'success' : 'error') . "'>" . $result['message'] . "</div>";
        }

        $branches = getAllBranches($pdo);
        ?>

        <!-- Form Tambah Cawangan -->
        <div style="background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 30px;">
            <h3 style="margin-bottom: 20px;">🏢 Tambah Cawangan Baru</h3>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Nama Cawangan:</label>
                        <input type="text" name="branch_name" required style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Telefon:</label>
                        <input type="text" name="branch_phone" required style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px;">
                    </div>
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Alamat:</label>
                    <textarea name="branch_address" rows="3" required style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px;"></textarea>
                </div>
                <button type="submit" name="add_branch" class="btn btn-success" style="width: 100%; padding: 15px;">➕ Tambah Cawangan</button>
            </form>
        </div>

        <!-- Senarai Cawangan -->
        <div style="margin-bottom: 30px;">
            <h3 style="margin-bottom: 20px;">📋 Senarai Cawangan (<?= count($branches) ?>)</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Alamat</th>
                            <th>Telefon</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($branches as $b): ?>
                        <tr>
                            <td><?= $b['id'] ?></td>
                            <td><strong><?= htmlspecialchars($b['nama']) ?></strong></td>
                            <td><?= htmlspecialchars($b['alamat']) ?></td>
                            <td><?= htmlspecialchars($b['telefon']) ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="editBranch(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['nama'])) ?>', '<?= htmlspecialchars(addslashes($b['alamat'])) ?>', '<?= htmlspecialchars(addslashes($b['telefon'])) ?>')">✏️ Edit</button>
                                <a href="?page=admin&delete_branch=<?= $b['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Pastikan anda mahu padam cawangan ini?')">🗑️ Padam</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Edit Cawangan -->
        <div id="editBranchModal" class="modal" style="display: none;">
            <div class="modal-content">
                <h3>✏️ Edit Cawangan</h3>
                <form method="POST">
                    <input type="hidden" name="branch_id" id="edit_branch_id">
                    <div class="form-group">
                        <label>Nama Cawangan:</label>
                        <input type="text" name="branch_name" id="edit_branch_name" required>
                    </div>
                    <div class="form-group">
                        <label>Alamat:</label>
                        <textarea name="branch_address" id="edit_branch_address" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Telefon:</label>
                        <input type="text" name="branch_phone" id="edit_branch_phone" required>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="edit_branch" class="btn btn-primary" style="flex: 1;">💾 Simpan</button>
                        <button type="button" class="btn btn-danger" onclick="document.getElementById('editBranchModal').style.display='none'" style="flex: 1;">❌ Batal</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function editBranch(id, name, address, phone) {
            document.getElementById('edit_branch_id').value = id;
            document.getElementById('edit_branch_name').value = name;
            document.getElementById('edit_branch_address').value = address;
            document.getElementById('edit_branch_phone').value = phone;
            document.getElementById('editBranchModal').style.display = 'flex';
        }
        </script>
        
        <!-- ========== JANJI TERKINI DENGAN BUTANG PADAM ========== -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <h3 style="margin-bottom: 15px;">Senarai Cawangan</h3>
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 8px;">ID</th>
                            <th style="text-align: left; padding: 8px;">Nama</th>
                            <th style="text-align: left; padding: 8px;">Telefon</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM cawangan LIMIT 5");
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>
                            <td style='padding: 8px;'>{$row['id']}</td>
                            <td style='padding: 8px;'>{$row['nama']}</td>
                            <td style='padding: 8px;'>{$row['telefon']}</td>
                        </tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <h3 style="margin-bottom: 15px;">📅 Janji Terkini</h3>
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 8px;">Pelanggan</th>
                            <th style="text-align: left; padding: 8px;">Tarikh</th>
                            <th style="text-align: left; padding: 8px;">Status</th>
                            <th style="text-align: center; padding: 8px;">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM janji ORDER BY created_at DESC LIMIT 5");
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $statusClass = $row['status'] == 'confirmed' ? 'status-confirmed' : 
                                       ($row['status'] == 'pending' ? 'status-pending' : 
                                       ($row['status'] == 'completed' ? 'status-completed' : 'status-cancelled'));
                        if ($row['status'] == 'confirmed') {
                            $statusClass = 'status-confirmed';
                        } elseif ($row['status'] == 'pending') {
                            $statusClass = 'status-pending';
                        } elseif ($row['status'] == 'completed') {
                            $statusClass = 'status-completed';
                        } else {
                            $statusClass = 'status-cancelled';
                        }
                        echo "<tr>
                            <td style='padding: 8px;'>{$row['pelanggan']}</td>
                            <td style='padding: 8px;'>{$row['tarikh']}</td>
                            <td style='padding: 8px;'><span class='status-badge {$statusClass}'> </span></td>
                            <td style='padding: 8px; text-align: center;'>
                                <a href='?page=admin&delete_appointment={$row['id']}' 
                                   class='btn btn-danger btn-sm' 
                                   onclick='return confirm(\"⚠️ Padam janji untuk {$row['pelanggan']} pada {$row['tarikh']}? Tindakan ini kekal.\")'
                                   style='background: #dc3545; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 12px;'>
                                    🗑️ Padam
                                </a>
                            </td>
                        </tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div style="margin-top: 30px; text-align: right;">
            <button class="btn btn-danger" onclick="adminLogout()">Log Keluar</button>
        </div>
    </div>
    <?php
}
?>
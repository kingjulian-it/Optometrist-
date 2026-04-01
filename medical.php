<?php
// Halaman Rekod Kesihatan
// Pastikan sudah ada sambungan database $pdo

// ========== PROSES SIMPAN PRESKRIPSI ==========
if (isset($_POST['tambah_preskripsi'])) {
    $pelanggan_nama = trim($_POST['pelanggan_nama']);
    $pelanggan_telefon = trim($_POST['pelanggan_telefon']);
    $pelanggan_email = trim($_POST['pelanggan_email'] ?? '');
    
    // Data mata kanan
    $sph_od = $_POST['sph_od'] ?? null;
    $cyl_od = $_POST['cyl_od'] ?? null;
    $axis_od = $_POST['axis_od'] ?? null;
    $add_od = $_POST['add_od'] ?? null;
    
    // Data mata kiri
    $sph_os = $_POST['sph_os'] ?? null;
    $cyl_os = $_POST['cyl_os'] ?? null;
    $axis_os = $_POST['axis_os'] ?? null;
    $add_os = $_POST['add_os'] ?? null;
    
    $jenis_cermin = $_POST['jenis_cermin'] ?? '';
    $tarikh_preskripsi = $_POST['tarikh_preskripsi'] ?? date('Y-m-d');
    $catatan = $_POST['catatan'] ?? '';
    
    // Dapatkan atau buat pelanggan_id berdasarkan nama & telefon
    // Ini untuk keserasian dengan jadual janji yang mungkin tidak ada jadual pelanggan berasingan
    $stmt = $pdo->prepare("SELECT id FROM pelanggan WHERE nama = ? AND telefon = ? LIMIT 1");
    $stmt->execute([$pelanggan_nama, $pelanggan_telefon]);
    $pelanggan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pelanggan) {
        $pelanggan_id = $pelanggan['id'];
    } else {
        // Jika tiada dalam jadual pelanggan, kita create
        $stmt = $pdo->prepare("INSERT INTO pelanggan (nama, telefon, email, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$pelanggan_nama, $pelanggan_telefon, $pelanggan_email]);
        $pelanggan_id = $pdo->lastInsertId();
    }
    
    // Insert ke jadual preskripsi
    try {
        $stmt = $pdo->prepare("INSERT INTO preskripsi 
            (pelanggan_id, sph_od, cyl_od, axis_od, add_od, sph_os, cyl_os, axis_os, add_os, 
             jenis_cermin, tarikh_preskripsi, catatan, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $pelanggan_id, $sph_od, $cyl_od, $axis_od, $add_od,
            $sph_os, $cyl_os, $axis_os, $add_os,
            $jenis_cermin, $tarikh_preskripsi, $catatan
        ]);
        $message = "✅ Preskripsi berjaya disimpan untuk $pelanggan_nama.";
        $messageType = "success";
    } catch (Exception $e) {
        $message = "❌ Gagal menyimpan: " . $e->getMessage();
        $messageType = "error";
    }
}
?>

<style>
    /* Modal styling */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        backdrop-filter: blur(3px);
    }
    .modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 20px 25px;
        border-radius: 20px;
        width: 90%;
        max-width: 700px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        animation: fadeIn 0.2s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 12px;
        margin-bottom: 20px;
    }
    .modal-header h3 {
        margin: 0;
        color: #1e3a5f;
    }
    .close-modal {
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        color: #aaa;
    }
    .close-modal:hover {
        color: #dc3545;
    }
    .prescription-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    .eye-card {
        background: #f8fafc;
        padding: 15px;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
    }
    .eye-card h4 {
        margin-top: 0;
        margin-bottom: 15px;
        text-align: center;
        color: #2c6e9e;
    }
    .form-group {
        margin-bottom: 12px;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 5px;
        font-size: 0.85rem;
        color: #2d3748;
    }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        font-size: 0.9rem;
    }
    .btn-submit {
        background: #2c6e9e;
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 40px;
        font-weight: bold;
        cursor: pointer;
        width: 100%;
        font-size: 1rem;
        transition: background 0.2s;
    }
    .btn-submit:hover {
        background: #1e4a6e;
    }
    .alert-small {
        margin-top: 10px;
        padding: 8px;
        border-radius: 8px;
        font-size: 0.8rem;
        text-align: center;
    }
    @media (max-width: 550px) {
        .prescription-grid {
            grid-template-columns: 1fr;
        }
    }
    /* Badge status */
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    .status-confirmed { background: #d4edda; color: #155724; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-completed { background: #cce5ff; color: #004085; }
    .status-cancelled { background: #f8d7da; color: #721c24; }
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.8rem;
    }
    .btn-primary {
        background: #2c6e9e;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    .btn-primary:hover {
        background: #1e4a6e;
    }
</style>

<div class="card">
    <h2>📋 Rekod Kesihatan Pesakit</h2>
    
    <!-- Mesej respon -->
    <?php if (isset($message)): ?>
        <div class="alert-<?= $messageType ?>" style="margin-bottom: 20px;">
            <?= $message ?>
        </div>
    <?php endif; ?>
    
    <div class="form-group">
        <input type="text" id="searchPatient" placeholder="Carian nama / telefon / email..." onkeyup="searchPatient()" style="padding: 15px; width: 100%;">
    </div>
    
    <div id="patientList" style="margin-top: 30px;">
        <?php
        // Query mendapatkan senarai pesakit unik dari jadual janji
        $stmt = $pdo->query("
            SELECT DISTINCT pelanggan, telefon, email, 
            MAX(tarikh) as last_visit,
            COUNT(*) as total_visits
            FROM janji 
            GROUP BY pelanggan, telefon, email
            ORDER BY last_visit DESC
            LIMIT 10
        ");
        
        if($stmt->rowCount() > 0) {
            echo "<div class='table-container'>";
            echo "<table style='width:100%; border-collapse:collapse;'>";
            echo "<thead><tr>
                    <th>Nama</th>
                    <th>Telefon</th>
                    <th>Lawatan Terakhir</th>
                    <th>Jumlah</th>
                    <th>Tindakan</th>
                  </tr></thead><tbody>";
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>
                        <td><strong>{$row['pelanggan']}</strong></td>
                        <td>{$row['telefon']}</td>
                        <td>{$row['last_visit']}</td>
                        <td>{$row['total_visits']}</td>
                        <td>
                            <button class='btn btn-primary btn-sm' onclick='openPrescriptionModal(\"{$row['pelanggan']}\", \"{$row['telefon']}\", \"{$row['email']}\")'>👓 Tambah Preskripsi</button>
                        </td>
                      </tr>";
            }
            
            echo "</tbody></table></div>";
        } else {
            echo "<p style='text-align: center; color: #666;'>Tiada rekod pesakit</p>";
        }
        ?>
    </div>
</div>

<!-- Modal untuk Tambah Preskripsi -->
<div id="prescriptionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>👓 Tambah Preskripsi Cermin Mata</h3>
            <span class="close-modal" onclick="closePrescriptionModal()">&times;</span>
        </div>
        <form id="prescriptionForm" method="POST" action="">
            <input type="hidden" name="pelanggan_nama" id="prescription_nama">
            <input type="hidden" name="pelanggan_telefon" id="prescription_telefon">
            <input type="hidden" name="pelanggan_email" id="prescription_email">
            
            <div class="prescription-grid">
                <!-- Mata Kanan -->
                <div class="eye-card">
                    <h4>🔵 Mata Kanan (OD)</h4>
                    <div class="form-group">
                        <label>SPH (kuasa sfera)</label>
                        <input type="text" name="sph_od" placeholder="cth: -2.00" step="0.25">
                    </div>
                    <div class="form-group">
                        <label>CYL (silinder)</label>
                        <input type="text" name="cyl_od" placeholder="cth: -0.75">
                    </div>
                    <div class="form-group">
                        <label>Axis (°)</label>
                        <input type="text" name="axis_od" placeholder="cth: 180">
                    </div>
                    <div class="form-group">
                        <label>ADD (untuk progresif)</label>
                        <input type="text" name="add_od" placeholder="cth: +2.00">
                    </div>
                </div>

                <!-- Mata Kiri -->
                <div class="eye-card">
                    <h4>🟢 Mata Kiri (OS)</h4>
                    <div class="form-group">
                        <label>SPH (kuasa sfera)</label>
                        <input type="text" name="sph_os" placeholder="cth: -1.75">
                    </div>
                    <div class="form-group">
                        <label>CYL (silinder)</label>
                        <input type="text" name="cyl_os" placeholder="cth: -0.50">
                    </div>
                    <div class="form-group">
                        <label>Axis (°)</label>
                        <input type="text" name="axis_os" placeholder="cth: 90">
                    </div>
                    <div class="form-group">
                        <label>ADD (untuk progresif)</label>
                        <input type="text" name="add_os" placeholder="cth: +2.00">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Jenis Cermin Mata</label>
                <select name="jenis_cermin">
                    <option value="Single Vision">Single Vision (Satu Jarak)</option>
                    <option value="Bifocal">Bifocal (Dua Jarak)</option>
                    <option value="Progressive">Progressive (Tanpa Garis)</option>
                    <option value="Reading">Reading (Membaca)</option>
                    <option value="Others">Lain-lain</option>
                </select>
            </div>

            <div class="form-group">
                <label>Tarikh Preskripsi</label>
                <input type="date" name="tarikh_preskripsi" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
                <label>Catatan (pilihan)</label>
                <textarea name="catatan" rows="2" placeholder="Sebarang maklumat tambahan..."></textarea>
            </div>

            <button type="submit" name="tambah_preskripsi" class="btn-submit">💾 Simpan Preskripsi</button>
        </form>
    </div>
</div>

<script>
// Fungsi carian pesakit
function searchPatient() {
    const search = document.getElementById('searchPatient').value.toLowerCase();
    const rows = document.querySelectorAll('#patientList table tbody tr');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
}

// Buka modal preskripsi
function openPrescriptionModal(nama, telefon, email) {
    document.getElementById('prescription_nama').value = nama;
    document.getElementById('prescription_telefon').value = telefon;
    document.getElementById('prescription_email').value = email;
    document.querySelector('#prescriptionModal .modal-header h3').innerHTML = `👓 Tambah Preskripsi untuk ${nama}`;
    document.getElementById('prescriptionModal').style.display = 'block';
    // Reset form (tetapi tidak hapus hidden fields)
    document.getElementById('prescriptionForm').reset();
    // Set tarikh hari ini
    document.querySelector('input[name="tarikh_preskripsi"]').value = new Date().toISOString().slice(0,10);
}

function closePrescriptionModal() {
    document.getElementById('prescriptionModal').style.display = 'none';
}

// Tutup modal jika klik di luar content
window.onclick = function(event) {
    const modal = document.getElementById('prescriptionModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>
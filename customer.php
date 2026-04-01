<?php
// Halaman Pelanggan
?>
<div class="card">
    <h2>📅 Buat Temu Janji Baru</h2>
    
    <form id="bookingForm" onsubmit="return saveBooking(event)">
        <div class="form-group">
            <label>🏢 Pilih Cawangan:</label>
            <select id="cawangan" required onchange="loadStaf()">
                <option value="">-- Sila Pilih Cawangan --</option>
                <?php
                $stmt = $pdo->query("SELECT * FROM cawangan ORDER BY id");
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='{$row['id']}'>{$row['nama']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label>👨‍⚕️ Pilih Optometris:</label>
            <select id="staf" required onchange="loadAvailableSlots()">
                <option value="">-- Sila Pilih Optometris --</option>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>📅 Pilih Tarikh:</label>
                <input type="date" id="tarikh" required onchange="loadAvailableSlots()" min="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label>⏰ Pilih Masa:</label>
                <select id="masa" required>
                    <option value="">-- Pilih Masa --</option>
                </select>
            </div>
        </div>

        <h3 style="margin: 30px 0 20px;">📝 Maklumat Peribadi</h3>

        <div class="form-row">
            <div class="form-group">
                <label>Nama Penuh:</label>
                <input type="text" id="nama" required>
            </div>

            <div class="form-group">
                <label>No IC:</label>
                <input type="text" id="ic" placeholder="900101-01-1234">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>No Telefon:</label>
                <input type="tel" id="telefon" required placeholder="012-3456789">
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" id="email" required>
            </div>
        </div>

        <div class="form-group">
            <label>Jenis Layanan:</label>
            <select id="jenisLayanan" required>
                <option value="Pemeriksaan Biasa">👁️ Pemeriksaan Biasa (30 min)</option>
                <option value="Pemeriksaan Lensa Kontak">💧 Pemeriksaan Lensa Kontak (45 min)</option>
                <option value="Konsultasi">💬 Konsultasi (15 min)</option>
                <option value="Pengambilan Cermin Mata">👓 Pengambilan Cermin Mata (15 min)</option>
            </select>
        </div>

        <div class="form-group">
            <label>Catatan (Optional):</label>
            <textarea id="catatan" rows="3" placeholder="Contoh: Saya ada alergi, minta doctor maklum..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;">📅 Sahkan Temu Janji</button>
    </form>

    <div id="bookingSuccess" style="display: none; margin-top: 30px; padding: 30px; background: #d4edda; border-radius: 10px; text-align: center;">
        <h3 style="color: #155724;">✅ Temu Janji Berjaya!</h3>
        <p style="margin: 20px 0;">Sila semak WhatsApp/Email anda untuk pengesahan.</p>
        <button class="btn btn-primary" onclick="resetBooking()">Buat Temu Janji Baru</button>
    </div>
</div>

<script>
function loadStaf() {
    const cawanganId = document.getElementById('cawangan').value;
    const stafSelect = document.getElementById('staf');
    
    if(!cawanganId) {
        stafSelect.innerHTML = '<option value="">-- Sila Pilih Cawangan Dahulu --</option>';
        return;
    }
    
    fetch('?page=customer', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=get_staf&cawangan_id=' + cawanganId
    })
    .then(res => res.json())
    .then(data => {
        stafSelect.innerHTML = '<option value="">-- Sila Pilih Optometris --</option>';
        data.forEach(s => {
            stafSelect.innerHTML += `<option value="${s.id}">${s.nama} (${s.peranan})</option>`;
        });
    });
}

function loadAvailableSlots() {
    const stafId = document.getElementById('staf').value;
    const tarikh = document.getElementById('tarikh').value;
    const masaSelect = document.getElementById('masa');
    
    if(!stafId || !tarikh) {
        masaSelect.innerHTML = '<option value="">-- Pilih Masa --</option>';
        return;
    }
    
    fetch('?page=customer', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=get_available_slots&staf_id=' + stafId + '&tarikh=' + tarikh
    })
    .then(res => res.json())
    .then(data => {
        const booked = data.booked || [];
        
        let options = '<option value="">-- Pilih Masa --</option>';
        for(let hour = 10; hour <= 18; hour++) {
            for(let min of ['00', '30']) {
                if(hour == 17 && min == '30') continue;
                const time = `${hour.toString().padStart(2,'0')}:${min}`;
                if(!booked.includes(time)) {
                    options += `<option value="${time}">${time}</option>`;
                }
            }
        }
        
        masaSelect.innerHTML = options;
    });
}

function saveBooking(e) {
    e.preventDefault();
    
    const data = {
        ajax: 'save_booking',
        pelanggan: document.getElementById('nama').value,
        telefon: document.getElementById('telefon').value,
        email: document.getElementById('email').value,
        ic: document.getElementById('ic').value,
        cawangan_id: document.getElementById('cawangan').value,
        staf_id: document.getElementById('staf').value,
        tarikh: document.getElementById('tarikh').value,
        masa: document.getElementById('masa').value,
        jenis_layanan: document.getElementById('jenisLayanan').value,
        catatan: document.getElementById('catatan').value
    };
    
    const params = new URLSearchParams();
    for(let key in data) {
        params.append(key, data[key]);
    }
    
    fetch('?page=customer', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params.toString()
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            document.getElementById('bookingForm').style.display = 'none';
            document.getElementById('bookingSuccess').style.display = 'block';
            sendWhatsAppNotification(data.telefon, data.pelanggan, res.booking_id);
        } else {
            alert('Ralat! Sila cuba lagi.');
        }
    });
    
    return false;
}

function sendWhatsAppNotification(phone, name, bookingId) {
    const message = `Terima kasih ${name}! Temu janji anda telah disahkan. Nombor rujukan: #${bookingId}`;
    console.log('Sending WhatsApp to', phone, message);
}

function resetBooking() {
    document.getElementById('bookingForm').style.display = 'block';
    document.getElementById('bookingSuccess').style.display = 'none';
    document.getElementById('bookingForm').reset();
}
</script>
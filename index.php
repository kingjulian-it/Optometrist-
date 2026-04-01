<?php
// ============================================
// SISTEM TEMU JANJI OPTOMETRIS - FAIL UTAMA
// ============================================

session_start();
require_once 'config.php';
require_once 'database.php';
require_once 'functions.php';
require_once 'reminder.php';
require_once 'staff-management.php';
require_once 'ajax-handler.php';

// Handle logout
if(isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?page=home');
    exit;
}

// Initialize database
$pdo = getDB();
initializeDatabase();

// Run reminder (10% chance)
if(rand(1, 10) <= REMINDER_CHANCE) {
    checkAndSendReminders($pdo);
}

// Handle AJAX requests
if(isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    handleAjaxRequest($pdo);
}

// Page routing
$page = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Temu Janji Optometris</title>
    <style>
        /* ========== ALL CSS STYLES ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Modal styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
    z-index: 1000;
}
.modal-content {
    background: white;
    padding: 30px;
    border-radius: 20px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}
/* Alert styles */
.alert-success {
    background: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    border-left: 5px solid #28a745;
}
.alert-error {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    border-left: 5px solid #dc3545;
}

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo h1 {
            color: #2c3e50;
            font-size: 24px;
        }

        .logo p {
            color: #7f8c8d;
            font-size: 14px;
        }

        nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        nav a {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            color: #2c3e50;
            font-weight: 500;
            transition: all 0.3s;
        }

        nav a:hover {
            background: #667eea;
            color: white;
        }

        nav a.active {
            background: #667eea;
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }

        .portal-card {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            cursor: pointer;
        }

        .portal-card:hover {
            transform: translateY(-10px);
        }

        .portal-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .portal-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 24px;
        }

        .portal-card p {
            color: #7f8c8d;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: border 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #667eea;
            outline: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Password field with eye button - CRITICAL */
        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper input {
            width: 100%;
            padding-right: 45px !important;
        }

        .toggle-password-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 22px;
            user-select: none;
            color: #667eea;
            background: transparent;
            border: none;
            padding: 5px;
            z-index: 10;
            line-height: 1;
        }

        .toggle-password-btn:hover {
            color: #764ba2;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
        }

        .btn-outline:hover {
            background: #667eea;
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 5px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #cce5ff;
            color: #004085;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }

        .time-slot {
            padding: 10px;
            text-align: center;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .time-slot:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .time-slot.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .time-slot.disabled {
            background: #e0e0e0;
            color: #999;
            cursor: not-allowed;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .login-container {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .chatbot-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999;
        }

        .chatbot-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(37, 211, 102, 0.3);
            transition: transform 0.3s;
        }

        .chatbot-button:hover {
            transform: scale(1.1);
        }

        .chatbot-button svg {
            width: 35px;
            height: 35px;
            fill: white;
        }

        .reminder-banner {
            background: #fff3cd;
            color: #856404;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid #ffc107;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
            }
        }

        .reminder-icon {
            font-size: 30px;
        }

        .reminder-content h4 {
            margin-bottom: 5px;
            font-size: 18px;
        }

        .reminder-content p {
            opacity: 0.9;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid #dc3545;
        }

        .staff-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }

            nav {
                margin-top: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <h1>👁️ Optometris Malaysia</h1>
            <p>Sistem Temu Janji Optometris</p>
        </div>
        <nav>
            <a href="?page=home" class="<?= $page == 'home' ? 'active' : '' ?>">Utama</a>
            <a href="?page=customer" class="<?= $page == 'customer' ? 'active' : '' ?>">Pelanggan</a>
            <a href="?page=staff" class="<?= $page == 'staff' ? 'active' : '' ?>">Staf</a>
            <a href="?page=admin" class="<?= $page == 'admin' ? 'active' : '' ?>">Admin</a>
            <a href="?page=medical" class="<?= $page == 'medical' ? 'active' : '' ?>">Rekod Kesihatan</a>
        </nav>
    </header>

    <div class="container">
        <?php
        // Load page content
        switch($page) {
            case 'customer':
                include 'pages/customer.php';
                break;
            case 'staff':
                include 'pages/staff.php';
                break;
            case 'admin':
                include 'pages/admin.php';
                break;
            case 'medical':
                include 'pages/medical.php';
                break;
            default:
                include 'pages/home.php';
        }
        ?>
    </div>

    <div class="chatbot-container">
        <div class="chatbot-button" onclick="window.open('https://wa.me/601151399115?text=Hi%2C%20saya%20nak%20buat%20temu%20janji', '_blank')">
            <svg viewBox="0 0 24 24">
                <path d="M19.077 4.928C17.191 3.041 14.683 2 12.006 2 6.798 2 2.548 6.193 2.54 11.393c-.003 1.738.451 3.446 1.311 4.962L2.25 21.75l5.513-1.445c1.469.801 3.12 1.224 4.811 1.225h.004c5.2 0 9.46-4.194 9.468-9.394.004-2.512-.973-4.875-2.859-6.762zM12.021 20.184h-.003c-1.498 0-2.97-.402-4.247-1.16l-.305-.182-3.256.854.872-3.171-.198-.317c-.819-1.308-1.253-2.823-1.252-4.376.008-4.355 3.554-7.897 7.92-7.897 2.116 0 4.105.825 5.602 2.322 1.497 1.497 2.322 3.485 2.32 5.6-.008 4.358-3.554 7.902-7.908 7.902zM16.3 13.045c-.242-.121-1.427-.702-1.648-.782-.221-.08-.382-.121-.543.121-.161.242-.625.782-.767.942-.141.161-.282.181-.524.06-.868-.404-1.536-.724-2.159-1.223-.826-.66-1.384-1.464-1.544-1.714-.161-.25-.017-.385.12-.51.124-.113.282-.303.423-.454.141-.152.188-.253.282-.423.094-.17.047-.318-.023-.445-.071-.127-.535-1.29-.734-1.767-.193-.463-.389-.401-.534-.409-.139-.008-.298-.009-.457-.009-.158 0-.414.059-.632.297-.217.238-.829.81-.829 1.977 0 1.166.85 2.293.969 2.451.119.158 1.638 2.5 3.967 3.405 2.33.905 2.33.604 2.75.566.419-.038 1.352-.552 1.543-1.085.19-.534.19-.99.133-1.085-.058-.096-.213-.153-.455-.274z"/>
            </svg>
        </div>
    </div>

    <script>
    // SIMPLE & RELIABLE - PASSWORD TOGGLE FUNCTION
    // Ini adalah function yang akan dipanggil terus dari onclick button
    function togglePassword(button) {
        // Cari input dalam parent yang sama (password-wrapper)
        var wrapper = button.parentElement;
        var input = wrapper.querySelector('input');
        
        if (!input) {
            console.log('Input not found');
            return;
        }
        
        if (input.type === 'password') {
            input.type = 'text';
            button.innerHTML = '👁️‍🗨️';
        } else {
            input.type = 'password';
            button.innerHTML = '👁️';
        }
    }

    // Function untuk reset password staff (AJAX)
    function resetStaffPassword(staffId, currentName) {
        var newPassword = prompt('Masukkan password baru untuk ' + currentName + ':', '');
        
        if (!newPassword) return;
        
        if (newPassword.length < 4) {
            alert('Password mesti sekurang-kurangnya 4 aksara');
            return;
        }
        
        if (confirm('Reset password untuk ' + currentName + ' kepada: ' + newPassword + '?')) {
            var formData = new FormData();
            formData.append('ajax', 'reset_staff_password');
            formData.append('staff_id', staffId);
            formData.append('new_password', newPassword);
            
            fetch('?page=admin', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                alert('Ralat: ' + error);
            });
        }
    }

    // Function untuk delete staff
    function deleteStaff(staffId, staffName) {
        if (confirm('Pastikan anda mahu padam staf: ' + staffName + '?')) {
            var formData = new FormData();
            formData.append('ajax', 'delete_staff');
            formData.append('staff_id', staffId);
            
            fetch('?page=admin', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                alert('Ralat: ' + error);
            });
        }
    }

    function showAlert(message, type = 'success') {
        alert(message);
    }
    
    function manualSendReminders() {
        if(confirm('Hantar reminder manual untuk semua janji esok?')) {
            var formData = new FormData();
            formData.append('ajax', 'send_reminders');
            
            fetch('?page=admin', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert('✅ Reminder dihantar!');
                location.reload();
            });
        }
    }
    
    function adminLogout() {
        window.location.href = '?page=admin&logout=1';
    }
    </script>
</body>
</html>
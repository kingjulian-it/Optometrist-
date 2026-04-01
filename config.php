<?php
// ============================================
// KONFIGURASI SISTEM
// ============================================

// Database configuration
$DB_HOST = 'localhost';
$DB_NAME = 'optometris_db';
$DB_USER = 'root';
$DB_PASS = '';

// WhatsApp API configuration
define('WHATSAPP_API_KEY', 'YOUR_FONNTE_API_KEY');
define('WHATSAPP_API_URL', 'https://api.fonnte.com/send');

// System configuration
define('CACHE_DIR', __DIR__ . '/cache/');
define('REMINDER_CHANCE', 10); // 10% chance to run reminder

// Timezone
date_default_timezone_set('Asia/Kuala_Lumpur');
?>
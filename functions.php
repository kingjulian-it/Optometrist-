<?php
// ============================================
// FUNGSI-FUNGSI TAMBAHAN
// ============================================

require_once 'config.php';

function getCachedData($pdo, $key, $sql, $ttl = 3600) {
    if (!is_dir(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0777, true);
    }
    
    $cache_file = CACHE_DIR . "$key.cache";
    
    if(file_exists($cache_file) && (time() - filemtime($cache_file)) < $ttl) {
        return unserialize(file_get_contents($cache_file));
    }
    
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    file_put_contents($cache_file, serialize($data));
    
    return $data;
}

function clearCache($key = null) {
    if ($key) {
        $cache_file = CACHE_DIR . "$key.cache";
        if(file_exists($cache_file)) {
            unlink($cache_file);
        }
    } else {
        array_map('unlink', glob(CACHE_DIR . "*.cache"));
    }
}

function getCawanganNama($pdo, $id) {
    static $cache = [];
    if (!isset($cache[$id])) {
        $stmt = $pdo->prepare("SELECT nama FROM cawangan WHERE id = ?");
        $stmt->execute([$id]);
        $cache[$id] = $stmt->fetchColumn() ?: 'N/A';
    }
    return $cache[$id];
}

function getStafNama($pdo, $id) {
    static $cache = [];
    if (!isset($cache[$id])) {
        $stmt = $pdo->prepare("SELECT nama FROM staf WHERE id = ?");
        $stmt->execute([$id]);
        $cache[$id] = $stmt->fetchColumn() ?: 'N/A';
    }
    return $cache[$id];
}

function generateBookingRef($id) {
    return '#' . str_pad($id, 6, '0', STR_PAD_LEFT);
}

function formatDateMalaysia($date) {
    $months = [
        'January' => 'Januari',
        'February' => 'Februari',
        'March' => 'Mac',
        'April' => 'April',
        'May' => 'Mei',
        'June' => 'Jun',
        'July' => 'Julai',
        'August' => 'Ogos',
        'September' => 'September',
        'October' => 'Oktober',
        'November' => 'November',
        'December' => 'Disember'
    ];
    
    $english = date('F j, Y', strtotime($date));
    return str_replace(array_keys($months), array_values($months), $english);
}

// Function untuk toggle password - untuk inline onclick
function getPasswordToggleScript() {
    return "
    function togglePassword(button) {
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
    ";
}
?>
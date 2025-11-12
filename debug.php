<?php
// =================================================================
// SCRIPT DEBUGGING UNTUK MASALAH HTTP 500
// =================================================================

// 1. Aktifkan Laporan Error PHP
// -----------------------------------------------------------------
// Baris ini akan memaksa server untuk menampilkan semua error PHP.
// Ini adalah langkah paling penting untuk mendiagnosis masalah 500.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Sesi Debugging Dimulai</h1>";
echo "<p>Jika Anda melihat halaman ini, artinya file <code>debug.php</code> berhasil dijalankan. Sekarang kita akan memeriksa beberapa konfigurasi umum.</p>";
echo "<hr>";

// 2. Periksa Versi PHP
// -----------------------------------------------------------------
echo "<h2>1. Informasi Versi PHP</h2>";
echo "<p>Versi PHP yang sedang berjalan: <strong>" . phpversion() . "</strong></p>";
if (version_compare(phpversion(), '7.4.0', '<')) {
    echo "<p style='color:red;'><strong>Peringatan:</strong> Versi PHP Anda lebih rendah dari 7.4. Aplikasi ini mungkin memerlukan PHP 7.4 atau lebih tinggi.</p>";
} else {
    echo "<p style='color:green;'>Versi PHP Anda memenuhi persyaratan (7.4+).</p>";
}
echo "<hr>";

// 3. Periksa Path dan Keberadaan File Konfigurasi
// -----------------------------------------------------------------
echo "<h2>2. Pemeriksaan File Konfigurasi</h2>";
$config_path = __DIR__ . '/config/config.php';
echo "<p>Mencoba memuat file konfigurasi dari path: <code>" . $config_path . "</code></p>";

if (file_exists($config_path)) {
    echo "<p style='color:green;'><strong>Berhasil:</strong> File <code>config/config.php</code> ditemukan.</p>";
    
    // 4. Coba muat file config.php dan periksa error fatal
    // -----------------------------------------------------------------
    echo "<p>Sekarang mencoba untuk me-<em>require</em> file tersebut...</p>";
    
    // Menggunakan try-catch untuk menangkap error fatal jika ada
    try {
        require_once($config_path);
        echo "<p style='color:green;'><strong>Berhasil:</strong> File <code>config/config.php</code> berhasil dimuat tanpa error fatal.</p>";
        
        // 5. Uji Koneksi Database setelah config dimuat
        // -----------------------------------------------------------------
        echo "<hr><h2>3. Uji Koneksi Database</h2>";
        echo "<p>Mencoba menghubungkan ke database menggunakan fungsi <code>getDBConnection()</code> dari file konfigurasi...</p>";
        
        // Periksa apakah fungsi getDBConnection ada
        if (function_exists('getDBConnection')) {
            $conn = getDBConnection();
            if ($conn) {
                echo "<p style='color:green;'><strong>Koneksi Database Berhasil!</strong></p>";
                echo "<p>Informasi server: " . $conn->host_info . "</p>";
                $conn->close();
            } else {
                // getDBConnection() mungkin mengembalikan false atau null jika gagal
                 echo "<p style='color:red;'><strong>Gagal:</strong> Fungsi <code>getDBConnection()</code> tidak mengembalikan koneksi yang valid. Periksa kembali kredensial di <code>config.php</code>.</p>";
            }
        } else {
            echo "<p style='color:red;'><strong>Gagal:</strong> Fungsi <code>getDBConnection()</code> tidak ditemukan setelah memuat <code>config.php</code>.</p>";
        }

    } catch (Throwable $e) {
        // Tangkap error apapun (termasuk parse error) saat memuat config.php
        echo "<p style='color:red;'><strong>ERROR FATAL:</strong> Terjadi masalah saat memuat <code>config/config.php</code>.</p>";
        echo "<p>Ini adalah penyebab utama masalah Anda. Errornya adalah:</p>";
        echo "<pre style='background-color:#f5f5f5; border:1px solid #ccc; padding:10px;'>" . htmlspecialchars($e->getMessage()) . "\n\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }

} else {
    echo "<p style='color:red;'><strong>ERROR FATAL:</strong> File <code>config/config.php</code> tidak ditemukan pada path yang diharapkan. Pastikan Anda sudah mengunggah folder <code>config</code> dengan benar.</p>";
    echo "<p>Path absolut yang dicari: <code>" . realpath(__DIR__) . "/config/config.php</code></p>";
}

echo "<hr>";
echo "<h2>4. Pemeriksaan Sesi</h2>";
// Memeriksa apakah sesi sudah bisa dimulai
if (session_status() == PHP_SESSION_NONE) {
    echo "<p>Status sesi: Belum ada sesi yang dimulai. Mencoba memulai sesi...</p>";
    try {
        session_start();
        if (session_status() == PHP_SESSION_ACTIVE) {
            echo "<p style='color:green;'><strong>Berhasil:</strong> Sesi berhasil dimulai.</p>";
        } else {
            echo "<p style='color:red;'><strong>Gagal:</strong> Tidak dapat memulai sesi. Periksa konfigurasi server terkait sesi.</p>";
        }
    } catch (Throwable $e) {
        echo "<p style='color:red;'><strong>ERROR FATAL:</strong> Gagal memulai sesi. Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:green;'>Sesi sudah aktif.</p>";
}


echo "<hr>";
echo "<h1>Sesi Debugging Selesai</h1>";
echo "<p>Silakan salin semua teks di halaman ini dan kirimkan kepada saya untuk dianalisis.</p>";

?>

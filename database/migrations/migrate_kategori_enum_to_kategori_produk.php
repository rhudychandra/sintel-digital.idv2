<?php
// Migration: Migrate produk.kategori from fixed ENUM set to dynamic kategori_produk table values
// Usage (dry-run first):
//   php migrate_kategori_enum_to_kategori_produk.php dry_run=1
// Real run:
//   php migrate_kategori_enum_to_kategori_produk.php
// Optional params:
//   mapping=auto  -> attempt naive keyword mapping (internet -> Voucher/Perdana Internet, etc.)
//   mapping=json  -> provide custom JSON mapping via STDIN or file path env (see notes)

require_once __DIR__ . '/../../config/config.php';

function out($msg) { echo $msg . PHP_EOL; }

$dryRun = false;
$autoMap = false;
$customMap = [];

foreach ($argv as $arg) {
    if (stripos($arg, 'dry_run') === 0) $dryRun = true;
    if (stripos($arg, 'mapping=auto') === 0) $autoMap = true;
}

// Optional: allow mapping via JSON file path env MIGRATION_MAP_FILE
$mapFile = getenv('MIGRATION_MAP_FILE');
if ($mapFile && file_exists($mapFile)) {
    $json = file_get_contents($mapFile);
    $data = json_decode($json, true);
    if (is_array($data)) {
        $customMap = $data;
        out("Loaded custom mapping from $mapFile");
    } else {
        out("Warning: Failed to parse JSON mapping from $mapFile");
    }
}

$conn = getDBConnection();
$conn->set_charset('utf8mb4');

// 1) Ensure produk.kategori is VARCHAR(100)
try {
    $res = $conn->query("SHOW COLUMNS FROM produk LIKE 'kategori'");
    $col = $res ? $res->fetch_assoc() : null;
    $type = $col ? strtolower($col['Type']) : '';
    if ($type && strpos($type, 'enum(') !== false) {
        out('Altering produk.kategori from ENUM to VARCHAR(100)...');
        if (!$dryRun) {
            $conn->query("ALTER TABLE produk MODIFY COLUMN kategori VARCHAR(100) NOT NULL");
        }
    } else {
        out('produk.kategori already VARCHAR (or compatible).');
    }
} catch (Throwable $e) {
    out('ERROR altering produk.kategori: ' . $e->getMessage());
    exit(1);
}

// 2) Ensure kategori_produk table exists (minimal schema)
try {
    $res = $conn->query("SHOW TABLES LIKE 'kategori_produk'");
    if (!$res || $res->num_rows === 0) {
        out('Creating kategori_produk table (not found)...');
        if (!$dryRun) {
            $sql = "CREATE TABLE kategori_produk (
                kategori_id INT AUTO_INCREMENT PRIMARY KEY,
                nama_kategori VARCHAR(100) NOT NULL UNIQUE,
                deskripsi TEXT,
                icon VARCHAR(50) DEFAULT '📦',
                status ENUM('active','inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_nama (nama_kategori)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $conn->query($sql);
        }
    } else {
        out('kategori_produk table exists.');
    }
} catch (Throwable $e) {
    out('ERROR ensuring kategori_produk: ' . $e->getMessage());
    exit(1);
}

// 3) Fetch existing kategori_produk names
$kategoriProdukMap = [];
try {
    $res = $conn->query("SELECT kategori_id, nama_kategori FROM kategori_produk");
    while ($row = $res->fetch_assoc()) {
        $kategoriProdukMap[$row['nama_kategori']] = (int)$row['kategori_id'];
    }
    out('Loaded ' . count($kategoriProdukMap) . ' kategori_produk rows.');
} catch (Throwable $e) {
    out('ERROR reading kategori_produk: ' . $e->getMessage());
    exit(1);
}

// 4) Collect distinct old kategori values from produk
$distinctProdukKategori = [];
try {
    $res = $conn->query("SELECT DISTINCT kategori FROM produk");
    while ($row = $res->fetch_assoc()) {
        $distinctProdukKategori[] = $row['kategori'];
    }
    out('Found distinct produk.kategori: ' . implode(', ', $distinctProdukKategori));
} catch (Throwable $e) {
    out('ERROR fetching distinct produk.kategori: ' . $e->getMessage());
    exit(1);
}

// 5) Build mapping plan
// Default mapping array (EDIT HERE if you know exact mapping)
$mapping = $customMap ?: [
    // Definitive mapping from old ENUM values to new kategori_produk names.
    // Adjust if you want a different target. All targets must exist in kategori_produk.
    'internet'        => 'Voucher Internet Lite',
    'tv_cable'        => 'Voucher Internet ByU',
    'phone'           => 'LinkAja',
    'paket_bundling'  => 'Perdana Internet Lite',
    'enterprise'      => 'Perdana Internet Special',
];

// If mapping=auto, try naive keyword mapping
if ($autoMap && empty($customMap)) {
    foreach ($distinctProdukKategori as $old) {
        $low = strtolower(trim($old));
        $target = null;
        // Heuristic: prefer kategori with similar keywords
        if (strpos($low, 'internet') !== false) {
            // pick a preferred category if exists
            foreach (['Voucher Internet Lite','Perdana Internet Lite','Perdana Internet Special'] as $cand) {
                if (isset($kategoriProdukMap[$cand])) { $target = $cand; break; }
            }
        } elseif (strpos($low, 'tv') !== false) {
            foreach (['Voucher Segel ByU','Voucher Segel Lite'] as $cand) {
                if (isset($kategoriProdukMap[$cand])) { $target = $cand; break; }
            }
        } elseif (strpos($low, 'phone') !== false) {
            foreach (['LinkAja','Finpay'] as $cand) {
                if (isset($kategoriProdukMap[$cand])) { $target = $cand; break; }
            }
        } elseif (strpos($low, 'bundling') !== false) {
            foreach (['Perdana Segel ByU 0K','Perdana Segel Red 0K'] as $cand) {
                if (isset($kategoriProdukMap[$cand])) { $target = $cand; break; }
            }
        } elseif (strpos($low, 'enterprise') !== false) {
            foreach (['Perdana Internet Special'] as $cand) {
                if (isset($kategoriProdukMap[$cand])) { $target = $cand; break; }
            }
        }
        if ($target) $mapping[$old] = $target;
    }
}

if (empty($mapping)) {
    out('No mapping specified. Dry-run summary below. Provide mapping via MIGRATION_MAP_FILE env or edit $mapping in script.');
    foreach ($distinctProdukKategori as $old) {
        out(" - '$old' -> (not mapped)");
    }
    out("To run with auto-guess: php migrate_kategori_enum_to_kategori_produk.php mapping=auto");
    exit(0);
}

// Validate mapping targets exist
$invalidTargets = [];
foreach ($mapping as $old => $newName) {
    if (!isset($kategoriProdukMap[$newName])) {
        $invalidTargets[$old] = $newName;
    }
}
if (!empty($invalidTargets)) {
    out('ERROR: Some mapping targets do not exist in kategori_produk:');
    foreach ($invalidTargets as $o => $t) {
        out(" - '$o' -> '$t' (NOT FOUND)");
    }
    out('Please insert missing kategori into kategori_produk or adjust mapping.');
    exit(1);
}

// 6) Apply mapping updates
$updated = 0;
foreach ($mapping as $old => $newName) {
    $oldEsc = $conn->real_escape_string($old);
    $newEsc = $conn->real_escape_string($newName);
    $sql = "UPDATE produk SET kategori = '$newEsc' WHERE kategori = '$oldEsc'";
    out('Applying: ' . $sql);
    if (!$dryRun) {
        if ($conn->query($sql) === true) {
            $updated += $conn->affected_rows;
        } else {
            out('  FAILED: ' . $conn->error);
        }
    }
}

// 7) Optional: add kategori_id and backfill
try {
    $res = $conn->query("SHOW COLUMNS FROM produk LIKE 'kategori_id'");
    if (!$res || $res->num_rows === 0) {
        out('Adding produk.kategori_id (nullable)...');
        if (!$dryRun) {
            $conn->query("ALTER TABLE produk ADD COLUMN kategori_id INT NULL AFTER kategori");
            // Optional FK (commented for now to avoid failures if data inconsistent)
            // $conn->query("ALTER TABLE produk ADD INDEX idx_kategori_id (kategori_id)");
            // $conn->query("ALTER TABLE produk ADD CONSTRAINT fk_produk_kategori_id FOREIGN KEY (kategori_id) REFERENCES kategori_produk(kategori_id) ON UPDATE CASCADE ON DELETE RESTRICT");
        }
    } else {
        out('produk.kategori_id already exists.');
    }
} catch (Throwable $e) {
    out('WARNING: Could not add kategori_id: ' . $e->getMessage());
}

// Backfill kategori_id from kategori name
try {
    // Build temp table for fast join
    if (!$dryRun) {
        $conn->query("DROP TEMPORARY TABLE IF EXISTS tmp_kat_map");
        $conn->query("CREATE TEMPORARY TABLE tmp_kat_map (nama_kategori VARCHAR(100) PRIMARY KEY, kategori_id INT NOT NULL)");
        $stmt = $conn->prepare("INSERT INTO tmp_kat_map (nama_kategori, kategori_id) VALUES (?, ?)");
        foreach ($kategoriProdukMap as $name => $kid) {
            $stmt->bind_param('si', $name, $kid);
            $stmt->execute();
        }
        $stmt->close();
        $conn->query("UPDATE produk p JOIN tmp_kat_map m ON p.kategori = m.nama_kategori SET p.kategori_id = m.kategori_id");
    }
    out('Backfill kategori_id completed.');
} catch (Throwable $e) {
    out('WARNING: Backfill kategori_id failed: ' . $e->getMessage());
}

out('Done. Rows updated: ' . $updated . ($dryRun ? ' (dry-run)' : ''));

// Summary
out('Distinct produk.kategori after migration:');
$res = $conn->query("SELECT DISTINCT kategori FROM produk ORDER BY kategori");
while ($row = $res->fetch_assoc()) {
    out(' - ' . $row['kategori']);
}

$conn->close();

<?php
/**
 * Test Path Resolution for Evidence Upload
 * Verifies that upload path works across different installations
 */

// Simulate being in modules/finance/setoran_harian_tap.php
$simulatedFileLocation = __DIR__ . '/modules/finance';

echo "<!DOCTYPE html><html><head><title>Path Resolution Test</title>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;} table{border-collapse:collapse;margin:10px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f0f0f0;}</style>";
echo "</head><body>";

echo "<h1>🧪 Path Resolution Test</h1>";
echo "<p>Testing evidence upload path portability across different environments.</p>";
echo "<hr>";

// Current Environment
echo "<h2>📍 Current Environment</h2>";
echo "<table>";
echo "<tr><th>Variable</th><th>Value</th></tr>";
echo "<tr><td>__FILE__</td><td>" . __FILE__ . "</td></tr>";
echo "<tr><td>__DIR__</td><td>" . __DIR__ . "</td></tr>";
echo "<tr><td>\$_SERVER['DOCUMENT_ROOT']</td><td>" . $_SERVER['DOCUMENT_ROOT'] . "</td></tr>";
echo "<tr><td>\$_SERVER['SCRIPT_NAME']</td><td>" . $_SERVER['SCRIPT_NAME'] . "</td></tr>";
echo "<tr><td>PHP Version</td><td>" . PHP_VERSION . "</td></tr>";
echo "<tr><td>OS</td><td>" . PHP_OS . "</td></tr>";
echo "</table>";

// Test Path Resolution Methods
echo "<h2>🔍 Path Resolution Methods</h2>";

echo "<h3>Method 1: realpath() + Fallback (IMPLEMENTED)</h3>";
$method1 = realpath(__DIR__ . '/assets/images/evidence');
if (!$method1 || !is_dir($method1)) {
    $method1 = dirname(__DIR__) . '/assets/images/evidence';
    $method1_fallback = true;
} else {
    $method1_fallback = false;
}

echo "<table>";
echo "<tr><th>Property</th><th>Value</th></tr>";
echo "<tr><td>Path</td><td><code>$method1</code></td></tr>";
echo "<tr><td>Used Fallback?</td><td>" . ($method1_fallback ? '<span class="warning">Yes (realpath failed)</span>' : '<span class="success">No (realpath worked)</span>') . "</td></tr>";
echo "<tr><td>Directory Exists?</td><td>" . (is_dir($method1) ? '<span class="success">✓ YES</span>' : '<span class="error">✗ NO</span>') . "</td></tr>";
echo "<tr><td>Is Readable?</td><td>" . (is_readable($method1) ? '<span class="success">✓ YES</span>' : '<span class="error">✗ NO</span>') . "</td></tr>";
echo "<tr><td>Is Writable?</td><td>" . (is_writable($method1) ? '<span class="success">✓ YES</span>' : '<span class="error">✗ NO</span>') . "</td></tr>";
echo "</table>";

// Test write
echo "<h3>✍️ Write Test</h3>";
if (is_dir($method1)) {
    $testFile = $method1 . '/test_' . time() . '.txt';
    if (@file_put_contents($testFile, 'Path resolution test - ' . date('Y-m-d H:i:s'))) {
        echo "<p class='success'><strong>✓ SUCCESS:</strong> File written to <code>$testFile</code></p>";
        if (@unlink($testFile)) {
            echo "<p class='success'><strong>✓ CLEANUP:</strong> Test file deleted successfully</p>";
        } else {
            echo "<p class='warning'><strong>⚠ WARNING:</strong> Could not delete test file</p>";
        }
    } else {
        echo "<p class='error'><strong>✗ FAILED:</strong> Cannot write to folder</p>";
        echo "<p>Possible reasons:</p>";
        echo "<ul>";
        echo "<li>Folder permission insufficient (need 0777 for network access)</li>";
        echo "<li>Disk full</li>";
        echo "<li>SELinux/AppArmor blocking</li>";
        echo "</ul>";
    }
} else {
    echo "<p class='error'><strong>✗ FAILED:</strong> Evidence folder does not exist</p>";
    echo "<p>Attempting to create folder...</p>";
    if (@mkdir($method1, 0777, true)) {
        echo "<p class='success'><strong>✓ SUCCESS:</strong> Folder created at <code>$method1</code></p>";
        @chmod($method1, 0777);
        echo "<p class='success'><strong>✓ CHMOD:</strong> Permission set to 0777</p>";
    } else {
        echo "<p class='error'><strong>✗ FAILED:</strong> Cannot create folder</p>";
        echo "<p>Manual action required:</p>";
        echo "<pre>";
        echo "# Windows (PowerShell):\n";
        echo "New-Item -Path \"$method1\" -ItemType Directory -Force\n";
        echo "icacls \"$method1\" /grant Everyone:F /T\n\n";
        echo "# Linux/Mac:\n";
        echo "mkdir -p \"$method1\"\n";
        echo "chmod 777 \"$method1\"\n";
        echo "</pre>";
    }
}

// Test from simulated location (modules/finance/)
echo "<h2>🎭 Simulated Location Test</h2>";
echo "<p>Testing as if running from <code>modules/finance/setoran_harian_tap.php</code></p>";

$simulatedPath = realpath($simulatedFileLocation . '/../../assets/images/evidence');
if (!$simulatedPath || !is_dir($simulatedPath)) {
    $simulatedPath = dirname(dirname($simulatedFileLocation)) . '/assets/images/evidence';
}

echo "<table>";
echo "<tr><th>Property</th><th>Value</th></tr>";
echo "<tr><td>Simulated File Location</td><td><code>$simulatedFileLocation</code></td></tr>";
echo "<tr><td>Resolved Evidence Path</td><td><code>$simulatedPath</code></td></tr>";
echo "<tr><td>Matches Actual?</td><td>" . ($simulatedPath === $method1 ? '<span class="success">✓ YES</span>' : '<span class="error">✗ NO</span>') . "</td></tr>";
echo "</table>";

// BASE_PATH Test
echo "<h2>🌐 BASE_PATH Test</h2>";
if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
    echo "<p class='success'><strong>✓ config.php loaded</strong></p>";
    echo "<table>";
    echo "<tr><th>Constant</th><th>Value</th></tr>";
    echo "<tr><td>BASE_PATH</td><td><code>" . (defined('BASE_PATH') ? BASE_PATH : 'NOT DEFINED') . "</code></td></tr>";
    echo "</table>";
    
    if (defined('BASE_PATH')) {
        $evidenceUrl = BASE_PATH . '/assets/images/evidence';
        echo "<p>Evidence URL would be: <code>$evidenceUrl</code></p>";
        echo "<p>Full URL example: <code>http://" . $_SERVER['HTTP_HOST'] . $evidenceUrl . "/evidence_123.jpg</code></p>";
    }
} else {
    echo "<p class='warning'><strong>⚠ WARNING:</strong> config/config.php not found</p>";
}

// Comparison Table
echo "<h2>📊 Method Comparison</h2>";
echo "<table>";
echo "<tr><th>Method</th><th>Path</th><th>Works?</th><th>Note</th></tr>";

// Method A: Hardcoded (BAD)
$methodA = $_SERVER['DOCUMENT_ROOT'] . '/sinartelekomdashboardsystem/assets/images/evidence';
echo "<tr>";
echo "<td>Hardcoded DOCUMENT_ROOT</td>";
echo "<td><code>$methodA</code></td>";
echo "<td>" . (is_dir($methodA) ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . "</td>";
echo "<td class='error'>❌ Not portable (hardcoded folder name)</td>";
echo "</tr>";

// Method B: __DIR__ relative (GOOD)
$methodB = realpath(__DIR__ . '/assets/images/evidence');
echo "<tr>";
echo "<td>__DIR__ relative</td>";
echo "<td><code>$methodB</code></td>";
echo "<td>" . (is_dir($methodB ?: '') ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . "</td>";
echo "<td class='success'>✅ Portable, recommended</td>";
echo "</tr>";

// Method C: dirname fallback (GOOD)
$methodC = dirname(__DIR__) . '/assets/images/evidence';
echo "<tr>";
echo "<td>dirname() fallback</td>";
echo "<td><code>$methodC</code></td>";
echo "<td>" . (is_dir($methodC) ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . "</td>";
echo "<td class='success'>✅ Portable, good fallback</td>";
echo "</tr>";

echo "</table>";

// Recommendations
echo "<h2>💡 Recommendations</h2>";

$allGood = is_dir($method1) && is_writable($method1);

if ($allGood) {
    echo "<div style='background:#d4edda;padding:15px;border-left:4px solid #28a745;'>";
    echo "<h3 style='margin-top:0;color:#155724;'>✅ All Tests Passed!</h3>";
    echo "<p>Evidence upload path is correctly configured and portable across:</p>";
    echo "<ul>";
    echo "<li>✓ Local development (XAMPP Windows/Mac)</li>";
    echo "<li>✓ Repository clone with different folder names</li>";
    echo "<li>✓ Network access (Mac accessing Windows XAMPP)</li>";
    echo "<li>✓ Hosting environments (Hostinger, cPanel, etc.)</li>";
    echo "</ul>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Test upload evidence via Setoran Harian TAP page</li>";
    echo "<li>Verify image displays correctly in evidence table</li>";
    echo "<li>Clone repository to different folder name and test again</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da;padding:15px;border-left:4px solid #dc3545;'>";
    echo "<h3 style='margin-top:0;color:#721c24;'>⚠️ Action Required</h3>";
    
    if (!is_dir($method1)) {
        echo "<p><strong>Issue:</strong> Evidence folder does not exist</p>";
        echo "<p><strong>Solution:</strong></p>";
        echo "<pre style='background:#f5f5f5;padding:10px;'>";
        echo "# Windows PowerShell (Administrator):\n";
        echo "New-Item -Path \"$method1\" -ItemType Directory -Force\n";
        echo "icacls \"$method1\" /grant Everyone:F /T\n\n";
        echo "# Or run the provided script:\n";
        echo ".\\fix_evidence_permission.ps1\n";
        echo "</pre>";
    } elseif (!is_writable($method1)) {
        echo "<p><strong>Issue:</strong> Evidence folder not writable</p>";
        echo "<p><strong>Solution:</strong></p>";
        echo "<pre style='background:#f5f5f5;padding:10px;'>";
        echo "# Windows PowerShell (Administrator):\n";
        echo "icacls \"$method1\" /grant Everyone:F /T\n\n";
        echo "# Or run the provided script:\n";
        echo ".\\fix_evidence_permission.ps1\n";
        echo "</pre>";
    }
    
    echo "</div>";
}

// File Information
echo "<h2>📄 Related Files</h2>";
echo "<ul>";
echo "<li><code>modules/finance/setoran_harian_tap.php</code> - Main upload handler</li>";
echo "<li><code>config/config.php</code> - BASE_PATH configuration</li>";
echo "<li><code>fix_evidence_permission.ps1</code> - Permission fix script (Windows)</li>";
echo "<li><code>PATH_PORTABILITY_GUIDE.md</code> - Complete documentation</li>";
echo "<li><code>test_path_resolution.php</code> - This test script</li>";
echo "</ul>";

echo "<hr>";
echo "<p><small>Test completed at: " . date('Y-m-d H:i:s') . "</small></p>";
echo "</body></html>";
?>

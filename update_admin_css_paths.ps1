# PowerShell script to update CSS paths in all admin files

$adminFiles = @(
    "admin/users.php",
    "admin/produk.php",
    "admin/kategori.php",
    "admin/cabang.php",
    "admin/penjualan.php",
    "admin/inventory.php",
    "admin/stock.php",
    "admin/reseller.php",
    "admin/grafik.php"
)

foreach ($file in $adminFiles) {
    if (Test-Path $file) {
        Write-Host "Updating $file..."
        $content = Get-Content $file -Raw
        
        # Replace old CSS path with new path
        $content = $content -replace 'href="../styles\.css"', 'href="../assets/css/styles.css"'
        $content = $content -replace 'href="admin-styles\.css"', 'href="../assets/css/admin-styles.css"'
        $content = $content -replace 'href="../admin-styles\.css"', 'href="../assets/css/admin-styles.css"'
        
        Set-Content $file -Value $content -NoNewline
        Write-Host "✓ Updated $file"
    } else {
        Write-Host "✗ File not found: $file"
    }
}

Write-Host ""
Write-Host "All admin files updated successfully!"

$files = @(
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

foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "Updating $file..."
        $content = Get-Content $file -Raw
        $content = $content -replace 'href="../styles\.css"', 'href="../assets/css/styles.css"'
        $content = $content -replace 'href="admin-styles\.css"', 'href="../assets/css/admin-styles.css"'
        $content = $content -replace 'href="../admin-styles\.css"', 'href="../assets/css/admin-styles.css"'
        Set-Content $file -Value $content -NoNewline
        Write-Host "Done: $file"
    }
}

Write-Host "Complete!"

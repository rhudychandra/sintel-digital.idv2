# Import database files for Sinar Telekom Dashboard in the correct order
# Run in PowerShell: right-click > Run with PowerShell (or execute in pwsh)
# Requirements: XAMPP MySQL running, mysql.exe typically at C:\xampp\mysql\bin\mysql.exe

param(
    [string]$MySqlExe = "C:\\xampp\\mysql\\bin\\mysql.exe",
    [string]$DbName = "sinar_telkom_dashboard",
    [string]$User = "root",
    [string]$Password = ""  # leave empty for default XAMPP (no password)
)

function Test-MySqlExe {
    param([string]$Path)
    if (-not (Test-Path -LiteralPath $Path)) {
        Write-Error "mysql.exe not found at: $Path. Adjust -MySqlExe parameter."
        return $false
    }
    return $true
}

function Invoke-MySQLImport {
    param(
        [string]$SqlFile
    )
    if (-not (Test-Path -LiteralPath $SqlFile)) {
        Write-Warning "Skip: File not found $SqlFile"
        return
    }
    $cmd = @()
    $cmd += "-u"; $cmd += $User
    if ($Password -ne "") { $cmd += "-p$Password" } else { $cmd += "--password=" }
    $cmd += "--protocol=tcp"
    $cmd += "-h"; $cmd += "127.0.0.1"
    $cmd += "--port=3306"
    $cmd += "--execute=SOURCE `"$SqlFile`";"

    Write-Host "\n>>> Importing: $SqlFile" -ForegroundColor Cyan
    $p = Start-Process -FilePath $MySqlExe -ArgumentList $cmd -NoNewWindow -PassThru -Wait
    if ($p.ExitCode -ne 0) {
        Write-Warning "Import finished with exit code $($p.ExitCode). If it's 'table/column exists', it's safe to continue."
    } else {
        Write-Host "Done: $SqlFile" -ForegroundColor Green
    }
}

function Invoke-MySQLQuery {
    param([string]$Query)
    $cmd = @()
    $cmd += "-u"; $cmd += $User
    if ($Password -ne "") { $cmd += "-p$Password" } else { $cmd += "--password=" }
    $cmd += "--protocol=tcp"
    $cmd += "-h"; $cmd += "127.0.0.1"
    $cmd += "--port=3306"
    $cmd += "--execute=$Query"
    $p = Start-Process -FilePath $MySqlExe -ArgumentList $cmd -NoNewWindow -PassThru -Wait
    return $p.ExitCode
}

# 0) Check mysql.exe
if (-not (Test-MySqlExe -Path $MySqlExe)) { exit 1 }

# 1) Quick connectivity test
Write-Host "Testing MySQL connectivity..." -ForegroundColor Yellow
$exit = Invoke-MySQLQuery -Query "SELECT 1;"
if ($exit -ne 0) {
    Write-Warning "Cannot connect to MySQL. Ensure MySQL is running and credentials are correct."
}

# Base paths
$Root = Split-Path -Parent $MyInvocation.MyCommand.Path | Split-Path -Parent
$DbDir = Join-Path $Root "database"
$MigDir = Join-Path $DbDir "migrations"
$SeedDir = Join-Path $DbDir "seeds"

# Import list (recommended order)
$sqlFiles = @(
    # Step 1: base schema
    (Join-Path $DbDir "database.sql"),

    # Step 2: admin expansion (cabang/reseller + relations)
    (Join-Path $MigDir "database_update_admin.sql"),
    (Join-Path $MigDir "create_views_only.sql"),

    # Step 3: categories
    (Join-Path $MigDir "create_kategori_table.sql"),
    (Join-Path $MigDir "fix_kategori_mapping.sql"),

    # Step 4: inventory approval (choose ONE; we pick add_approval_system.sql)
    (Join-Path $MigDir "add_approval_system.sql"),

    # Step 5: payment enums (choose ONE; we pick the 2025-11-12 enum migration)
    (Join-Path $MigDir "2025-11-12_update_penjualan_payment_enums.sql"),

    # Step 6: finance tables
    (Join-Path $MigDir "create_setoran_harian_table.sql"),
    (Join-Path $MigDir "create_setoran_evidence_table.sql"),
    (Join-Path $MigDir "alter_setoran_evidence_add_nominal_bank_pengirim.sql"),

    # Step 7: optional column completion
    (Join-Path $MigDir "add_cabang_email_and_timestamps.sql"),
    (Join-Path $MigDir "add_reseller_missing_columns.sql"),
    (Join-Path $MigDir "fix_reseller_columns.sql")
)

# Execute imports
foreach ($file in $sqlFiles) { Invoke-MySQLImport -SqlFile $file }

# Step 8/9: Seed admin user (choose file depending on whether cabang_id exists)
# Prefer the WITH cabang variant because database_update_admin.sql adds cabang_id
$seedWithCabang = Join-Path $SeedDir "add_admin_user_with_cabang.sql"
$seedSimple = Join-Path $SeedDir "add_admin_user.sql"

if (Test-Path $seedWithCabang) {
    Invoke-MySQLImport -SqlFile $seedWithCabang
} elseif (Test-Path $seedSimple) {
    Invoke-MySQLImport -SqlFile $seedSimple
}

Write-Host "\nAll imports attempted. Verify in phpMyAdmin." -ForegroundColor Green

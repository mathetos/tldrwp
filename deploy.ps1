# TLDRWP Deployment Script
# This script builds the plugin and creates a deployment ZIP file
# Includes only distribution files (matches GitHub Actions workflow)

Write-Host "Starting TLDRWP deployment..." -ForegroundColor Green

# Step 1: Build the plugin
Write-Host "Building plugin assets..." -ForegroundColor Yellow
npm run build

if ($LASTEXITCODE -ne 0) {
    Write-Host "Build failed!" -ForegroundColor Red
    exit 1
}

# Step 2: Extract version from plugin file
Write-Host "Reading plugin version..." -ForegroundColor Yellow
$pluginContent = Get-Content "tldrwp.php" -Raw
if ($pluginContent -match "Version:\s*([0-9]+\.[0-9]+\.[0-9]+)") {
    $version = $matches[1]
    Write-Host "Version detected: $version" -ForegroundColor Green
} else {
    Write-Host "Could not detect version, using 0.1.0" -ForegroundColor Yellow
    $version = "0.1.0"
}

# Step 3: Create deployment directory
$deployDir = "deploy"
$zipName = "tldrwp-v$version.zip"

if (Test-Path $deployDir) {
    Remove-Item $deployDir -Recurse -Force
}
New-Item -ItemType Directory -Path $deployDir | Out-Null

# Step 4: Copy files to deployment directory (matching .distignore)
Write-Host "Copying plugin files (excluding development files)..." -ForegroundColor Yellow

# Files and directories to include in distribution (matches what 10up action includes)
$filesToCopy = @(
    "admin",
    "blocks",
    "build",      # Built assets from wp-scripts
    "includes",
    "public",
    "LICENSE",
    "readme.txt",
    "tldrwp.php",
    "uninstall.php"
)

$copiedCount = 0
foreach ($item in $filesToCopy) {
    if (Test-Path $item) {
        try {
            if (Test-Path $item -PathType Container) {
                Copy-Item $item -Destination $deployDir -Recurse -Force
                Write-Host "  [OK] Copied directory: $item" -ForegroundColor Green
            } else {
                Copy-Item $item -Destination $deployDir -Force
                Write-Host "  [OK] Copied file: $item" -ForegroundColor Green
            }
            $copiedCount++
        } catch {
            Write-Host "  [ERROR] Error copying $item : $_" -ForegroundColor Red
        }
    } else {
        Write-Host "  [WARN] Missing: $item" -ForegroundColor Yellow
    }
}

Write-Host "  Total items copied: $copiedCount" -ForegroundColor Green

# Step 5: Create ZIP file
Write-Host "Creating ZIP file: $zipName" -ForegroundColor Yellow
Set-Location $deployDir
Compress-Archive -Path * -DestinationPath "../$zipName" -Force
Set-Location ..

# Step 6: Clean up
Remove-Item $deployDir -Recurse -Force

# Step 7: Show results
$zipSize = (Get-Item $zipName).Length
$zipSizeKB = [math]::Round($zipSize / 1KB, 2)
$zipSizeMB = [math]::Round($zipSize / 1MB, 2)

Write-Host ""
Write-Host "Deployment completed successfully!" -ForegroundColor Green
Write-Host "ZIP file: $zipName" -ForegroundColor Cyan
Write-Host "Size: $zipSizeKB KB ($zipSizeMB MB)" -ForegroundColor Cyan
Write-Host "Location: $((Get-Location).Path)\$zipName" -ForegroundColor Cyan
Write-Host "" 
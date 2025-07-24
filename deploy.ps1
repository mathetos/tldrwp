# TLDRWP Deployment Script
# This script builds the plugin and creates a deployment ZIP file

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

# Step 4: Copy files to deployment directory
Write-Host "Copying plugin files..." -ForegroundColor Yellow
$filesToCopy = @(
    "admin",
    "blocks", 
    "includes",
    "public",
    "LICENSE",
    "readme.txt",
    "tldrwp.php",
    "uninstall.php"
)

foreach ($file in $filesToCopy) {
    if (Test-Path $file) {
        if (Test-Path $file -PathType Container) {
            Copy-Item $file -Destination $deployDir -Recurse
        } else {
            Copy-Item $file -Destination $deployDir
        }
        Write-Host "  Copied: $file" -ForegroundColor Green
    } else {
        Write-Host "  Missing: $file" -ForegroundColor Yellow
    }
}

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

Write-Host "Deployment completed successfully!" -ForegroundColor Green
Write-Host "ZIP file: $zipName ($zipSizeKB KB)" -ForegroundColor Cyan
Write-Host "Location: $(Get-Location)\$zipName" -ForegroundColor Cyan 
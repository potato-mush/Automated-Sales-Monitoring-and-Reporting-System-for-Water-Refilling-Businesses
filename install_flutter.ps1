# Flutter Installation Script
Write-Host "=================================================="
Write-Host "  Flutter SDK Installation Script"
Write-Host "=================================================="
Write-Host ""

$FlutterDir = "C:\flutter"
$FlutterZip = "$env:TEMP\flutter_windows.zip"
$FlutterUrl = "https://storage.googleapis.com/flutter_infra_release/releases/stable/windows/flutter_windows_3.24.5-stable.zip"

if (Test-Path "$FlutterDir\bin\flutter.bat") {
    Write-Host "Flutter is already installed at $FlutterDir"
    & "$FlutterDir\bin\flutter.bat" --version
    $continue = Read-Host "`nDo you want to reinstall? (y/n)"
    if ($continue -ne 'y') {
        exit 0
    }
}

Write-Host "Step 1: Downloading Flutter SDK..."
Write-Host "This may take a few minutes (approx 1.2 GB)..."

try {
    Invoke-WebRequest -Uri $FlutterUrl -OutFile $FlutterZip -UseBasicParsing
    Write-Host "Download complete!"
} catch {
    Write-Host "ERROR: Failed to download Flutter: $_"
    exit 1
}

Write-Host "`nStep 2: Extracting Flutter SDK..."

try {
    if (Test-Path $FlutterDir) {
        Remove-Item -Path $FlutterDir -Recurse -Force
    }
    Expand-Archive -Path $FlutterZip -DestinationPath "C:\" -Force
    Write-Host "Extraction complete!"
} catch {
    Write-Host "ERROR: Failed to extract Flutter: $_"
    exit 1
}

Write-Host "`nStep 3: Cleaning up..."
Remove-Item -Path $FlutterZip -Force

Write-Host "`nStep 4: Adding Flutter to PATH..."

try {
    $currentPath = [Environment]::GetEnvironmentVariable("Path", "User")
    $flutterBin = "$FlutterDir\bin"
    
    if ($currentPath -notlike "*$flutterBin*") {
        $newPath = $currentPath + ";" + $flutterBin
        [Environment]::SetEnvironmentVariable("Path", $newPath, "User")
        $env:Path = $env:Path + ";" + $flutterBin
        Write-Host "Flutter added to PATH!"
    } else {
        Write-Host "Flutter already in PATH!"
    }
} catch {
    Write-Host "WARNING: Could not add to PATH: $_"
}

Write-Host "`nStep 5: Verifying installation..."
$env:Path = $env:Path + ";$FlutterDir\bin"

& "$FlutterDir\bin\flutter.bat" --version

Write-Host "`n=================================================="
Write-Host "  Installation Complete!"
Write-Host "=================================================="
Write-Host ""
Write-Host "Next Steps:"
Write-Host "1. Close and reopen PowerShell"
Write-Host "2. Run: flutter doctor"
Write-Host "3. Navigate to mobile_app and run: flutter pub get"
Write-Host ""

$PluginName = "automatic_badges"
$SourceDir  = $PSScriptRoot
$ZipName    = "${PluginName}_release.zip"
$TempParent = "$env:TEMP\moodle_build"
$TempDir    = "$TempParent\$PluginName"

Write-Host "======================================"
Write-Host "  Empaquetando plugin $PluginName"
Write-Host "======================================"

# 1. Limpiar y preparar directorio temporal
if (Test-Path $TempParent) {
    Remove-Item -Recurse -Force $TempParent
}
New-Item -ItemType Directory -Path $TempDir -Force | Out-Null

# 2. Carpetas y archivos a excluir
$ExcludeDirs  = @(".git", ".vscode", ".idea", "node_modules", "tests")
$ExcludeFiles = @("*.zip", "build.ps1", ".gitignore", "debug.log")

Write-Host "Copiando archivos a carpeta temporal..."

# Obtener todos los items del source (recursivo)
$AllItems = Get-ChildItem -Path $SourceDir -Recurse -Force

foreach ($Item in $AllItems) {
    # Calcular ruta relativa respecto al source
    $RelPath = $Item.FullName.Substring($SourceDir.Length).TrimStart('\')

    # Ignorar si el item (o alguno de sus padres) es una carpeta excluida
    $InExcludedDir = $false
    foreach ($ExDir in $ExcludeDirs) {
        if ($RelPath -eq $ExDir -or $RelPath.StartsWith("$ExDir\")) {
            $InExcludedDir = $true
            break
        }
    }
    if ($InExcludedDir) { continue }

    # Ignorar archivos que coincidan con los patrones excluidos
    if (-not $Item.PSIsContainer) {
        $IsExcluded = $false
        foreach ($Pattern in $ExcludeFiles) {
            if ($Item.Name -like $Pattern) {
                $IsExcluded = $true
                break
            }
        }
        if ($IsExcluded) { continue }
    }

    # Construir ruta destino
    $DestPath = Join-Path $TempDir $RelPath

    if ($Item.PSIsContainer) {
        New-Item -ItemType Directory -Path $DestPath -Force | Out-Null
    } else {
        $DestFolder = Split-Path $DestPath -Parent
        if (-not (Test-Path $DestFolder)) {
            New-Item -ItemType Directory -Path $DestFolder -Force | Out-Null
        }
        Copy-Item -Path $Item.FullName -Destination $DestPath -Force
    }
}

# 3. Verificar que version.php existe en la carpeta temporal
$VersionFile = Join-Path $TempDir "version.php"
if (-not (Test-Path $VersionFile)) {
    Write-Host "ERROR: version.php no fue copiado. Abortando." -ForegroundColor Red
    Remove-Item -Recurse -Force $TempParent
    exit 1
}
Write-Host "OK: version.php encontrado." -ForegroundColor Green

# 4. Crear el archivo ZIP (directamente la carpeta del plugin)
$ZipPath = Join-Path -Path $SourceDir -ChildPath $ZipName
if (Test-Path $ZipPath) {
    Remove-Item -Force $ZipPath
}

Write-Host "Comprimiendo carpeta..."
Compress-Archive -Path $TempDir -DestinationPath $ZipPath -Force

# 5. Limpiar temporal
Remove-Item -Recurse -Force $TempParent

Write-Host "======================================"
Write-Host "¡Completado!"
Write-Host "Plugin listo en: $ZipPath"
Write-Host "======================================"

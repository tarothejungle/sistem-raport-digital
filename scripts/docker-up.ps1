$ErrorActionPreference = 'Stop'

$wslDistro = 'FedoraLinux-44'
$projectRoot = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $projectRoot '.env.docker'
$envExample = Join-Path $projectRoot '.env.docker.example'

function ConvertTo-WslPath([string] $WindowsPath) {
    $fullPath = [IO.Path]::GetFullPath($WindowsPath)

    if ($fullPath -notmatch '^([A-Za-z]):\\(.*)$') {
        throw "Path Windows tidak didukung WSL: $fullPath"
    }

    return '/mnt/' + $Matches[1].ToLowerInvariant() + '/' + $Matches[2].Replace('\', '/')
}

if (-not (Get-Command wsl.exe -ErrorAction SilentlyContinue)) {
    throw 'WSL tidak ditemukan pada Windows PATH.'
}

& wsl.exe -d $wslDistro -- sudo -n docker info *> $null
if ($LASTEXITCODE -ne 0) {
    throw "Docker Fedora tidak dapat diakses. Jalankan 'wsl.exe -d $wslDistro -- sudo -v', lalu ulangi command ini."
}

if (-not (Test-Path -LiteralPath $envFile)) {
    Copy-Item -LiteralPath $envExample -Destination $envFile

    Write-Host 'Created .env.docker from .env.docker.example.'
}

$content = [IO.File]::ReadAllText($envFile)
if ($content -match '(?m)^APP_KEY=$') {
    $keyBytes = New-Object byte[] 32
    $random = New-Object System.Security.Cryptography.RNGCryptoServiceProvider
    try {
        $random.GetBytes($keyBytes)
    }
    finally {
        $random.Dispose()
    }
    $appKey = 'base64:' + [Convert]::ToBase64String($keyBytes)
    $content = [Text.RegularExpressions.Regex]::Replace(
        $content,
        '(?m)^APP_KEY=$',
        "APP_KEY=$appKey"
    )
    [IO.File]::WriteAllText($envFile, $content)

    Write-Host 'Generated APP_KEY in .env.docker.'
}

$wslProjectRoot = ConvertTo-WslPath $projectRoot

& wsl.exe -d $wslDistro -- sudo -n docker compose `
    --project-directory $wslProjectRoot `
    --env-file "$wslProjectRoot/.env.docker" `
    up -d --build --force-recreate --remove-orphans
if ($LASTEXITCODE -ne 0) {
    throw 'docker compose up gagal.'
}

& wsl.exe -d $wslDistro -- sudo -n docker compose `
    --project-directory $wslProjectRoot `
    --env-file "$wslProjectRoot/.env.docker" `
    ps
if ($LASTEXITCODE -ne 0) {
    throw 'Container berjalan, tetapi docker compose ps gagal.'
}

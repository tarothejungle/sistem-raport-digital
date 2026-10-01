param(
    [Parameter(Mandatory = $true)]
    [ValidateScript({ Test-Path -LiteralPath $_ -PathType Leaf })]
    [string] $DumpPath
)

$ErrorActionPreference = 'Stop'
$wslDistro = 'FedoraLinux-44'
$projectRoot = Split-Path -Parent $PSScriptRoot
$resolvedDumpPath = (Resolve-Path -LiteralPath $DumpPath).Path

function ConvertTo-WslPath([string] $WindowsPath) {
    $fullPath = [IO.Path]::GetFullPath($WindowsPath)

    if ($fullPath -notmatch '^([A-Za-z]):\\(.*)$') {
        throw "Path Windows tidak didukung WSL: $fullPath"
    }

    return '/mnt/' + $Matches[1].ToLowerInvariant() + '/' + $Matches[2].Replace('\', '/')
}

$wslProjectRoot = ConvertTo-WslPath $projectRoot
$wslDumpPath = ConvertTo-WslPath $resolvedDumpPath

$composeArguments = @(
    '-d', $wslDistro, '--', 'sudo', '-n', 'docker', 'compose',
    '--project-directory', $wslProjectRoot,
    '--env-file', "$wslProjectRoot/.env.docker"
)

$tableCount = & wsl.exe @composeArguments exec -T mysql sh -c 'mysql -N -u root -p"$MYSQL_ROOT_PASSWORD" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = \"$MYSQL_DATABASE\";"'
if ($LASTEXITCODE -ne 0) {
    throw 'Tidak dapat memeriksa database Docker.'
}

if ([int]$tableCount -gt 0) {
    throw "Import dibatalkan: database Docker tidak kosong ($tableCount tabel)."
}

& wsl.exe @composeArguments cp $wslDumpPath mysql:/tmp/raport-import.sql
if ($LASTEXITCODE -ne 0) {
    throw 'Gagal menyalin dump ke container MySQL.'
}

& wsl.exe @composeArguments exec -T mysql sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" < /tmp/raport-import.sql && rm /tmp/raport-import.sql'
if ($LASTEXITCODE -ne 0) {
    throw 'Import database gagal. File dump masih tersedia di /tmp/raport-import.sql dalam container MySQL.'
}

Write-Host 'Database berhasil diimpor. Jalankan scripts/docker-up.ps1 untuk migration dan startup aplikasi.'

# Alias: Install + migrasi PostgreSQL untuk Docker lokal (dev)
#
# Gunakan ini agar alurnya konsisten dengan dashboard-skrining.
# Wrapper ini meneruskan parameter ke deploy/install-migrate-pgsql.ps1.

param(
    [switch]$InitEnv,
    [switch]$SkipBuild,
    [switch]$FreshOnly,
    [switch]$VerifyOnly
)

& (Join-Path $PSScriptRoot "install-migrate-pgsql.ps1") @PSBoundParameters


<#
  Crea la VM "Win10-TP" (Windows 10 Pro 22H2, español Latinoamérica, x64) en VirtualBox.

  Uso: abrí PowerShell NORMAL (no como administrador) y corré
      powershell -ExecutionPolicy Bypass -File .\crear_vm_win10.ps1

  Para leer bcdedit y las características de Windows pide UAC una sola vez,
  solo para leer. No apaga ni cambia nada de Windows.
  No usa instalación desatendida (unattended): Windows se instala a mano.
#>
#Requires -Version 5.1

$ErrorActionPreference = 'Stop'
$ProgressPreference    = 'SilentlyContinue'
[Net.ServicePointManager]::SecurityProtocol = [Net.ServicePointManager]::SecurityProtocol -bor [Net.SecurityProtocolType]::Tls12

$VmNombre   = 'Win10-TP'
$VmBase     = 'C:\VMs'
$VmDir      = Join-Path $VmBase $VmNombre
$VdiRuta    = Join-Path $VmDir "$VmNombre.vdi"
$DiscoMB    = 50 * 1024
$IsoDir     = 'C:\VMs\ISO'
$IsoPatron  = 'Win10_22H2_*Spanish*Mexico*x64*.iso'
$FidoUrl    = 'https://raw.githubusercontent.com/pbatard/Fido/master/Fido.ps1'
$Snapshot   = 'VM recién creada'
$VmCreada   = $false

# ---------------------------------------------------------------- utilidades

function Titulo([string]$t) { Write-Host ''; Write-Host "== $t ==" -ForegroundColor Cyan }
function Ok([string]$t)     { Write-Host "  [OK] $t" -ForegroundColor Green }
function Aviso([string]$t)  { Write-Host "  [!]  $t" -ForegroundColor Yellow }

function Confirmar([string]$pregunta) {
    $r = Read-Host "  $pregunta (s/n)"
    return ($r -match '^\s*(s|si|sí|y|yes)\s*$')
}

# Muestra el error, qué significa en una línea, y corta el script sin intentar otra cosa.
function Fallar([string]$detalle, [string]$significado) {
    Write-Host ''
    Write-Host 'ERROR:' -ForegroundColor Red
    Write-Host $detalle -ForegroundColor Red
    Write-Host "Qué significa: $significado" -ForegroundColor Yellow
    if ($script:VmCreada) {
        Write-Host "La VM quedó a medio crear. Para borrarla: & '$script:VBoxManage' unregistervm $VmNombre --delete" -ForegroundColor Yellow
    }
    Write-Host 'Me freno acá, no intento nada más.'
    exit 1
}

$ExplicacionVBox = [ordered]@{
    'VERR_ALREADY_EXISTS|already exists'                                   = 'Ya hay un archivo o una VM con ese nombre; no lo piso.'
    'VERR_VMX_NO_VMX|VERR_SVM_NO_SVM|VT-x is not available|AMD-V is not available' = 'La virtualización (VT-x/AMD-V) está apagada en la BIOS/UEFI de la PC.'
    'VERR_VMX_IN_VMX_ROOT_MODE|VERR_SVM_IN_USE'                            = 'Otro hipervisor (Hyper-V u otro programa) tiene tomada la virtualización.'
    'VERR_NEM_'                                                             = 'VirtualBox está corriendo arriba de Hyper-V (modo tortuga) y eso falló.'
    'VERR_SUPDRV|VERR_VM_DRIVER|kernel driver'                              = 'El driver de VirtualBox no está cargado; reiniciá la PC o reinstalá VirtualBox.'
    'VERR_ACCESS_DENIED|E_ACCESSDENIED|Access denied'                       = 'Falta permiso sobre esa carpeta o archivo.'
    'VERR_DISK_FULL|not enough space'                                       = 'No queda espacio en el disco.'
    'VERR_FILE_NOT_FOUND|VERR_PATH_NOT_FOUND|Could not find file'           = 'No encuentra el archivo indicado (ISO o disco).'
    'VBOX_E_OBJECT_NOT_FOUND|Could not find a registered machine'           = 'No existe una VM registrada con ese nombre.'
    'VBOX_E_INVALID_OBJECT_STATE|is locked|already locked'                  = 'La VM está abierta o bloqueada por otra ventana de VirtualBox.'
    'Unknown option|Invalid parameter|syntax error'                         = 'Esta versión de VBoxManage no reconoce alguna opción del comando.'
}

function Explicar-ErrorVBox([string]$texto) {
    foreach ($patron in $ExplicacionVBox.Keys) {
        if ($texto -match $patron) { return $ExplicacionVBox[$patron] }
    }
    return 'Error de VirtualBox no previsto; el mensaje de arriba es el original.'
}

# Corre VBoxManage; si falla, muestra el error explicado y corta.
function VBox([string[]]$Argumentos) {
    $eap = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'   # VBoxManage escribe el progreso por stderr
    try {
        $salida = & $script:VBoxManage @Argumentos 2>&1 | ForEach-Object { "$_" }
    } finally {
        $ErrorActionPreference = $eap
    }
    if ($LASTEXITCODE -ne 0) {
        $texto = ($salida -join "`n").Trim()
        Fallar "VBoxManage $($Argumentos -join ' ')`n$texto" (Explicar-ErrorVBox $texto)
    }
    return $salida
}

function Explicar-Curl([int]$codigo) {
    switch ($codigo) {
        6       { 'No resuelve el nombre del servidor (sin Internet o problema de DNS).' }
        7       { 'No pudo conectar con el servidor de Microsoft.' }
        22      { 'Microsoft devolvió un error HTTP (link vencido o bloqueado); volvé a correr el script para pedir otro link.' }
        23      { 'No pudo escribir el archivo (disco lleno o sin permiso en C:\VMs\ISO).' }
        28      { 'Se agotó el tiempo de espera (red muy lenta o caída).' }
        { $_ -in 35, 60 } { 'Falló la conexión segura (TLS); suele ser un antivirus o proxy que intercepta HTTPS.' }
        { $_ -in 18, 56, 92 } { 'Se cortó la conexión a mitad de la descarga; volvé a correr el script y sigue desde donde quedó.' }
        default { 'curl falló; el código de arriba indica el motivo.' }
    }
}

# Lee bcdedit y las características de Windows. Si no somos admin, lo hace
# un PowerShell elevado aparte (UAC) que solo lee y deja el resultado en JSON.
$ChequeoHyperV = {
    $r = @{ bcd = (bcdedit.exe /enum '{current}' 2>&1 | Out-String); features = @{} }
    foreach ($n in $Nombres) {
        try {
            $f = Get-WindowsOptionalFeature -Online -FeatureName $n -ErrorAction Stop
            $r.features[$n] = if ($f) { [string]$f.State } else { 'NoExiste' }
        } catch {
            $r.features[$n] = 'NoExiste'
        }
    }
    $r | ConvertTo-Json | Set-Content -Path $Salida -Encoding UTF8
}

function Leer-EstadoHyperV([string[]]$lista, [bool]$esAdmin) {
    $tmp = Join-Path $env:TEMP "chequeo_hyperv_$PID.json"
    if ($esAdmin) {
        $Nombres = $lista; $Salida = $tmp
        & $script:ChequeoHyperV
    } else {
        $prefijo = "`$Salida = '$($tmp -replace "'", "''")'; `$Nombres = @('$($lista -join "','")')`n"
        $cmd = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($prefijo + $script:ChequeoHyperV.ToString()))
        try {
            Start-Process powershell.exe -Verb RunAs -Wait -WindowStyle Hidden `
                -ArgumentList '-NoProfile', '-ExecutionPolicy', 'Bypass', '-EncodedCommand', $cmd
        } catch {
            return $null   # UAC rechazado
        }
    }
    if (-not (Test-Path $tmp)) { return $null }
    $j = Get-Content $tmp -Raw | ConvertFrom-Json
    Remove-Item $tmp -Force
    return $j
}

# ---------------------------------------------------------------- 1) verificaciones

Titulo '1) Verificaciones previas'

$candidatos = @()
if ($env:VBOX_MSI_INSTALL_PATH) { $candidatos += Join-Path $env:VBOX_MSI_INSTALL_PATH 'VBoxManage.exe' }
$candidatos += 'C:\Program Files\Oracle\VirtualBox\VBoxManage.exe'
$enPath = Get-Command VBoxManage.exe -ErrorAction SilentlyContinue
if ($enPath) { $candidatos += $enPath.Source }
$VBoxManage = $candidatos | Where-Object { $_ -and (Test-Path $_) } | Select-Object -First 1
if (-not $VBoxManage) {
    Fallar 'No encontré VBoxManage.exe.' 'VirtualBox no está instalado, o está en una carpeta que no es la estándar.'
}
$version = (VBox @('--version') | Select-Object -Last 1).Trim()
Ok "VBoxManage: $VBoxManage"
Ok "VirtualBox $version"
if ($version -match '^(\d+)\.' -and [int]$Matches[1] -lt 6) {
    Fallar "VirtualBox $version" 'Es muy viejo: el controlador VBoxSVGA necesita VirtualBox 6.0 o más nuevo.'
}

# RAM instalada (suma de módulos); si no se puede leer, la que ve Windows.
$ramBytes = (Get-CimInstance Win32_PhysicalMemory | Measure-Object Capacity -Sum).Sum
$cs = Get-CimInstance Win32_ComputerSystem
if (-not $ramBytes) { $ramBytes = $cs.TotalPhysicalMemory }
$VmRamMB = if ($ramBytes -le 8GB) { 3072 } else { 4096 }
Ok ("RAM total: {0:N1} GB  ->  la VM va a usar {1} MB" -f ($ramBytes / 1GB), $VmRamMB)

$c = Get-CimInstance Win32_LogicalDisk -Filter "DeviceID='C:'"
$libreGB = $c.FreeSpace / 1GB
Ok ("Disco C: {0:N1} GB libres de {1:N1} GB" -f $libreGB, ($c.Size / 1GB))
if ($libreGB -lt 60) {
    Aviso 'Hacen falta ~60 GB en el peor caso (ISO ~6 GB + disco virtual que puede crecer hasta 50 GB).'
    if (-not (Confirmar '¿Sigo igual?')) { exit 0 }
}

# Hyper-V / Plataforma del hipervisor
$caracteristicas = [ordered]@{
    'Microsoft-Hyper-V-All'         = 'Hyper-V'
    'Microsoft-Hyper-V-Hypervisor'  = 'Hipervisor de Hyper-V'
    'HypervisorPlatform'            = 'Plataforma del hipervisor de Windows'
    'VirtualMachinePlatform'        = 'Plataforma de máquina virtual (WSL2)'
    'Containers-DisposableClientVM' = 'Espacio aislado de Windows (Sandbox)'
}
$esAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole(
    [Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $esAdmin) { Write-Host '  Te va a saltar UAC para leer bcdedit y las características (solo lectura).' }

$activos = @()
$estado = Leer-EstadoHyperV @($caracteristicas.Keys) $esAdmin
if (-not $estado) {
    Aviso 'No pude leer bcdedit ni las características (UAC cancelado). Sigo solo con lo que ve WMI.'
} else {
    if ($estado.bcd -match 'hypervisorlaunchtype\s+(\S+)') {
        $hlt = $Matches[1]
        Write-Host "  bcdedit hypervisorlaunchtype: $hlt"
        if ($hlt -ne 'Off') { $activos += "bcdedit: hypervisorlaunchtype = $hlt" }
    } else {
        Write-Host '  bcdedit hypervisorlaunchtype: no definido (equivale a Off)'
    }
    $traduccion = @{
        Enabled = 'ACTIVADA'; EnablePending = 'ACTIVADA (pendiente de reinicio)'
        Disabled = 'desactivada'; DisablePending = 'desactivada (pendiente de reinicio)'
        DisabledWithPayloadRemoved = 'desactivada'; NoExiste = 'no existe en esta edición'
    }
    foreach ($n in $caracteristicas.Keys) {
        $st = [string]$estado.features.$n
        $texto = if ($traduccion.ContainsKey($st)) { $traduccion[$st] } else { $st }
        Write-Host ("  {0,-40} {1}" -f $caracteristicas[$n], $texto)
        if ($st -in 'Enabled', 'EnablePending') { $activos += "Característica activa: $($caracteristicas[$n]) ($n)" }
    }
}
if ($cs.HypervisorPresent) { $activos += 'Hay un hipervisor corriendo ahora mismo (HypervisorPresent = True)' }
try {
    $dg = Get-CimInstance -Namespace root\Microsoft\Windows\DeviceGuard -ClassName Win32_DeviceGuard -ErrorAction Stop
    if ($dg.VirtualizationBasedSecurityStatus -eq 2) {
        $activos += 'Seguridad basada en virtualización corriendo (Aislamiento del núcleo / Integridad de memoria)'
    }
} catch { }

if ($activos.Count -gt 0) {
    Write-Host ''
    Aviso 'OJO: hay cosas de Hyper-V activas. Con esto VirtualBox anda en "modo tortuga" (lentísimo):'
    $activos | ForEach-Object { Write-Host "       - $_" -ForegroundColor Yellow }
    Write-Host '  No toqué nada. Si querés apagarlo vos (PowerShell como admin y después reiniciar):'
    Write-Host '       bcdedit /set hypervisorlaunchtype off'
    Write-Host '       Disable-WindowsOptionalFeature -Online -FeatureName <la que figure activa>'
    Write-Host '       Seguridad de Windows > Seguridad del dispositivo > Aislamiento del núcleo > Integridad de memoria: Desactivado'
    if (-not (Confirmar '¿Seguimos igual, sabiendo que la VM va a andar lenta?')) {
        Write-Host '  Listo, no hice nada. Apagalo, reiniciá y volvé a correr el script.'
        exit 0
    }
} else {
    Ok 'Hyper-V y la Plataforma del hipervisor están apagados.'
    $cpu = Get-CimInstance Win32_Processor | Select-Object -First 1
    if ($cpu.VirtualizationFirmwareEnabled -eq $false) {
        Aviso 'La virtualización (VT-x/AMD-V) está APAGADA en la BIOS/UEFI: sin eso no arranca una VM de 64 bits.'
        if (-not (Confirmar '¿Creo la VM igual (y la prendés en la BIOS antes de iniciarla)?')) { exit 0 }
    } else {
        Ok 'Virtualización por hardware (VT-x/AMD-V) habilitada en la BIOS.'
    }
}

# Antes de bajar 6 GB, que no exista ya la VM.
$vms = VBox @('list', 'vms')
if ($vms -match "^`"$([regex]::Escape($VmNombre))`" ") {
    Fallar "Ya existe una VM llamada $VmNombre." 'No la piso: si querés recrearla, borrala antes desde VirtualBox.'
}
if (Test-Path $VdiRuta) {
    Fallar "Ya existe $VdiRuta." 'Hay un disco viejo de otra VM con ese nombre; no lo piso.'
}

# ---------------------------------------------------------------- 2) ISO

Titulo '2) ISO oficial de Windows 10 22H2 (Español Latinoamérica, x64)'
New-Item -ItemType Directory -Force -Path $IsoDir | Out-Null

$IsoRuta = $null
$existente = Get-ChildItem -Path $IsoDir -Filter $IsoPatron -ErrorAction SilentlyContinue |
    Where-Object { $_.Extension -eq '.iso' } | Select-Object -First 1
if ($existente -and (Confirmar ("Ya tenés {0} ({1:N2} GB). ¿Uso ese?" -f $existente.Name, ($existente.Length / 1GB)))) {
    $IsoRuta = $existente.FullName
}

if (-not $IsoRuta) {
    $fidoDir = Join-Path $IsoDir 'Fido'
    $fido = Join-Path $fidoDir 'Fido.ps1'
    New-Item -ItemType Directory -Force -Path $fidoDir | Out-Null
    try {
        Invoke-WebRequest -UseBasicParsing -Uri $FidoUrl -OutFile $fido
    } catch {
        Fallar $_.Exception.Message 'No pude bajar Fido de GitHub (sin Internet, proxy o GitHub caído).'
    }
    Ok "Fido bajado de github.com/pbatard/Fido en $fido"

    Write-Host '  Pidiéndole a Microsoft el link directo con Fido (tarda unos segundos)...'
    $eap = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $salidaFido = & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $fido `
            -Win 'Windows 10' -Rel 22H2 -Ed Pro -Lang 'Spanish (Mexico)' -Arch x64 -PlatformArch x64 -GetUrl 2>&1 |
            ForEach-Object { "$_" }
    } finally {
        $ErrorActionPreference = $eap
    }
    $url = $salidaFido | Where-Object { $_ -match '^https://' } | Select-Object -Last 1
    if (-not $url) {
        $texto = ($salidaFido -join "`n").Trim()
        $significado = if ($texto -match '715-123130') {
            'Microsoft bloqueó temporalmente tu IP para descargas (pasa con VPN o muchos intentos); probá más tarde y sin VPN.'
        } else {
            'Fido no pudo conseguir el link de Microsoft (cambió su web o hubo un problema de red).'
        }
        Fallar "Fido no devolvió ningún link.`n$texto" $significado
    }
    $url = $url.Trim()
    $servidor = ([uri]$url).Host
    if ($servidor -notmatch '(^|\.)microsoft\.com$') {
        Fallar "Link inesperado: $servidor" 'El link no es de un servidor de Microsoft; no lo descargo.'
    }

    $archivo = [regex]::Match($url, '/([^/?]+\.iso)').Groups[1].Value
    if (-not $archivo) { $archivo = 'Win10_22H2_Spanish_Mexico_x64.iso' }
    $IsoRuta = Join-Path $IsoDir $archivo

    $tamBytes = $null
    try {
        $cab = Invoke-WebRequest -UseBasicParsing -Method Head -Uri $url
        $tamBytes = [uint64]("$($cab.Headers['Content-Length'])")
    } catch { }
    $tamTexto = if ($tamBytes) { '{0:N2} GB' -f ($tamBytes / 1GB) } else { '~5,8 GB' }

    Ok "Link directo de $servidor"
    if (-not (Confirmar "¿Descargo $archivo ($tamTexto) en $IsoDir?")) {
        Write-Host '  Listo, no descargo nada. Cuando quieras, volvé a correr el script.'
        exit 0
    }

    $parcial = "$IsoRuta.part"
    $curl = Join-Path $env:SystemRoot 'System32\curl.exe'
    if (Test-Path $curl) {
        & $curl -L --fail --retry 3 --retry-delay 5 -C - -o $parcial $url
        if ($LASTEXITCODE -ne 0) { Fallar "curl terminó con código $LASTEXITCODE." (Explicar-Curl $LASTEXITCODE) }
    } else {
        try {
            Start-BitsTransfer -Source $url -Destination $parcial -DisplayName 'ISO Windows 10'
        } catch {
            Fallar $_.Exception.Message 'BITS no pudo bajar el archivo (corte de red o link vencido).'
        }
    }
    $bajado = (Get-Item $parcial).Length
    if ($tamBytes -and $bajado -ne $tamBytes) {
        Fallar "Bajó $bajado bytes de $tamBytes." 'La descarga quedó incompleta; volvé a correr el script y sigue desde donde quedó.'
    }
    Move-Item -Path $parcial -Destination $IsoRuta -Force
}
Ok "ISO: $IsoRuta"

# ---------------------------------------------------------------- 3) VM

Titulo "3) Creando la VM $VmNombre"

VBox @('createvm', '--name', $VmNombre, '--ostype', 'Windows10_64', '--basefolder', $VmBase, '--register') | Out-Null
$VmCreada = $true
Ok "VM registrada en $VmDir"

VBox @('modifyvm', $VmNombre,
    '--memory', $VmRamMB, '--cpus', '2', '--ioapic', 'on', '--longmode', 'on', '--firmware', 'bios',
    '--vram', '128', '--graphicscontroller', 'vboxsvga', '--accelerate3d', 'off',
    '--nic1', 'nat', '--nictype1', '82540EM', '--usbohci', 'on', '--mouse', 'usbtablet',
    '--boot1', 'dvd', '--boot2', 'disk', '--boot3', 'none', '--boot4', 'none') | Out-Null
Ok "RAM $VmRamMB MB, 2 CPUs, vídeo 128 MB VBoxSVGA, 3D desactivado"

VBox @('createmedium', 'disk', '--filename', $VdiRuta, '--size', $DiscoMB, '--format', 'VDI', '--variant', 'Standard') | Out-Null
Ok "Disco dinámico de 50 GB: $VdiRuta"

VBox @('storagectl', $VmNombre, '--name', 'SATA', '--add', 'sata', '--controller', 'IntelAhci', '--portcount', '2', '--bootable', 'on') | Out-Null
VBox @('storageattach', $VmNombre, '--storagectl', 'SATA', '--port', '0', '--device', '0', '--type', 'hdd', '--medium', $VdiRuta) | Out-Null
VBox @('storageattach', $VmNombre, '--storagectl', 'SATA', '--port', '1', '--device', '0', '--type', 'dvddrive', '--medium', $IsoRuta) | Out-Null
Ok 'Controlador SATA: disco en el puerto 0, ISO en el lector de DVD (puerto 1)'

# ---------------------------------------------------------------- 4) snapshot e inicio

Titulo '4) Snapshot e inicio'
VBox @('snapshot', $VmNombre, 'take', $Snapshot, '--description', 'VM vacía, antes de instalar Windows') | Out-Null
Ok "Snapshot `"$Snapshot`" sacado"

VBox @('startvm', $VmNombre, '--type', 'gui') | Out-Null
Ok 'VM iniciada'

Write-Host @"

Qué elegir en el instalador de Windows:
  1. Idioma: Español (México) | Hora y moneda: Español (Argentina) | Teclado: Latinoamericano -> Siguiente -> Instalar ahora
  2. Activar Windows: "No tengo clave de producto"
  3. Sistema operativo: "Windows 10 Pro" (ni Pro N, ni Pro Education, ni Workstations)
  4. Aceptar la licencia
  5. "Personalizada: instalar solo Windows (avanzado)"
  6. "Espacio sin asignar en la unidad 0" (50 GB) -> Siguiente, sin crear particiones
  7. Reinicia solo. Si dice "Presione cualquier tecla para arrancar desde CD", no toques nada.
  8. Región Argentina, teclado Latinoamericano, omitir la segunda distribución
  9. "Configurar para uso personal"
 10. Abajo a la izquierda: "Cuenta sin conexión" -> "Experiencia limitada" -> usuario y contraseña
     Si no aparece: & '$VBoxManage' controlvm $VmNombre setlinkstate1 off
     volvé atrás, elegí "No tengo Internet" -> "Continuar con configuración limitada",
     y al llegar al escritorio: & '$VBoxManage' controlvm $VmNombre setlinkstate1 on
 11. Privacidad: todo en No -> Aceptar. Historial de actividad: No. Cortana: Ahora no.

Después: Dispositivos > Insertar imagen de CD de las Guest Additions, instalarlas y reiniciar.
"@

@echo off
REM Borra las cuentas de EdenAir que nadie usa hace 180 dias, con sus equipos
REM y su historial, y deja solo las ultimas 15 mediciones de cada equipo
REM (ver app/Commands/LimpiarCuentas.php). Pensado para el
REM Programador de tareas de Windows, una vez por semana. Cada cuenta borrada
REM queda anotada en writable\logs\limpieza-cuentas.log.
REM
REM Para ver que borraria SIN borrar nada, desde la carpeta del proyecto:
REM   php spark edenair:limpiar
REM
REM Se le pueden pasar opciones, por ejemplo: limpiar_cuentas.cmd --dias 365

setlocal
cd /d "%~dp0.."

if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" spark edenair:limpiar --confirmar %*
) else (
    php spark edenair:limpiar --confirmar %*
)

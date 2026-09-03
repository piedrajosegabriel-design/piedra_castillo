@echo off
REM Doble clic aca para dejar la placa en cero y poder vincularla de nuevo
REM con el QR. Busca solo un Python que tenga pyserial: primero el de Thonny,
REM que ya lo trae, y si no el del sistema.

setlocal
set SCRIPT=%~dp0resetear_placa.py

for %%P in (
    "%LOCALAPPDATA%\Programs\Thonny\python.exe"
    "%ProgramFiles%\Thonny\python.exe"
    "%ProgramFiles(x86)%\Thonny\python.exe"
) do (
    if exist %%P (
        %%P "%SCRIPT%" %*
        goto :fin
    )
)

python "%SCRIPT%" %*

:fin
echo.
pause

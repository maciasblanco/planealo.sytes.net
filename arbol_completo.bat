@echo off
chcp 65001 >nul
title ARBOL COMPLETO - SISTEMA PLANEALO

echo ===============================================
echo    ARBOL COMPLETO DEL SISTEMA PLANEALO
echo    Mostrando todas las carpetas y archivos
echo ===============================================
echo.

set "REPORTE=arbol_completo_%date:~-4,4%%date:~-10,2%%date:~-7,2%.txt"

echo ARBOL COMPLETO - SISTEMA PLANEALO > "%REPORTE%"
echo Generado: %date% %time% >> "%REPORTE%"
echo ================================= >> "%REPORTE%"
echo. >> "%REPORTE%"

echo [RAIZ] . >> "%REPORTE%"
call :showTree "." "" "%REPORTE%"

echo. >> "%REPORTE%"
echo ================================= >> "%REPORTE%"
echo TOTALES: >> "%REPORTE%"
dir /s /b *.* | find /c /v "" >> "%REPORTE%" 2>nul

echo.
echo Arbol completo guardado en: %REPORTE%
echo.

type "%REPORTE%"

echo.
echo Presiona cualquier tecla para abrir el reporte completo...
pause >nul
start notepad "%REPORTE%"

exit /b

:showTree
set "folder=%~1"
set "prefix=%~2"
set "report=%~3"

setlocal enabledelayedexpansion

:: Mostrar archivos en esta carpeta
for %%f in ("%folder%\*") do (
    if not "%%~xf"=="" (
        if not "%%~nxf"=="%REPORTE%" (
            echo !prefix!├── 📄 %%~nxf >> "%report%"
        )
    )
)

:: Procesar subcarpetas
set /a subfolder_count=0
for /d %%d in ("%folder%\*") do set /a subfolder_count+=1

set /a current=0
for /d %%d in ("%folder%\*") do (
    set /a current+=1
    
    if !current! equ !subfolder_count! (
        echo !prefix!└── 📁 %%~nxd >> "%report%"
        call :showTree "%%d" "!prefix!    " "%report%"
    ) else (
        echo !prefix!├── 📁 %%~nxd >> "%report%"
        call :showTree "%%d" "!prefix!│   " "%report%"
    )
)

endlocal
exit /b
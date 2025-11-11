@echo off
chcp 65001 >nul
title ARBOL ESENCIAL - SISTEMA PLANEALO

echo ===============================================
echo    ARBOL ESENCIAL DEL SISTEMA PLANEALO
echo    Estructura de desarrollo
echo ===============================================
echo.

set "REPORTE=arbol_esencial_%date:~-4,4%%date:~-10,2%%date:~-7,2%.txt"

echo ARBOL ESENCIAL - SISTEMA PLANEALO > "%REPORTE%"
echo Generado: %date% %time% >> "%REPORTE%"
echo ================================= >> "%REPORTE%"
echo. >> "%REPORTE%"

echo 📁 [RAIZ] . >> "%REPORTE%"

:: Directorios esenciales del desarrollo
set "essential_dirs=models controllers components widgets modules views config commands web migrations tests"

for %%d in (%essential_dirs%) do (
    if exist "%%d" (
        echo ├── 📁 %%d >> "%REPORTE%"
        call :showEssentialTree "%%d" "│   " "%REPORTE%"
    )
)

:: Otros directorios
echo ├── 📁 otros directorios >> "%REPORTE%"
for /d %%d in (*) do (
    set "is_essential=0"
    for %%e in (%essential_dirs%) do if "%%d"=="%%e" set "is_essential=1"
    if !is_essential! equ 0 (
        echo │   └── 📁 %%d >> "%REPORTE%"
    )
)

echo. >> "%REPORTE%"
echo ARCHIVOS PRINCIPALES EN RAIZ: >> "%REPORTE%"
for %%f in (*.php *.bat *.txt *.md *.json *.yml) do (
    echo ├── 📄 %%~nxf >> "%REPORTE%"
)

echo.
echo Arbol esencial guardado en: %REPORTE%
echo.

type "%REPORTE%"

echo.
echo Presiona cualquier tecla para abrir el reporte...
pause >nul
start notepad "%REPORTE%"

exit /b

:showEssentialTree
set "folder=%~1"
set "prefix=%~2"
set "report=%~3"

setlocal enabledelayedexpansion

:: Mostrar solo archivos relevantes
for %%f in ("%folder%\*.php" "%folder%\*.js" "%folder%\*.css" "%folder%\*.html" "%folder%\*.twig" "%folder%\*.json" "%folder%\*.yml" "%folder%\*.xml" "%folder%\*.sql" "%folder%\*.txt" "%folder%\*.md" "%folder%\*.bat") do (
    echo !prefix!├── 📄 %%~nxf >> "%report%"
)

:: Procesar subcarpetas
set /a subfolder_count=0
for /d %%d in ("%folder%\*") do set /a subfolder_count+=1

set /a current=0
for /d %%d in ("%folder%\*") do (
    set /a current+=1
    
    if !current! equ !subfolder_count! (
        echo !prefix!└── 📁 %%~nxd >> "%report%"
        call :showEssentialTree "%%d" "!prefix!    " "%report%"
    ) else (
        echo !prefix!├── 📁 %%~nxd >> "%report%"
        call :showEssentialTree "%%d" "!prefix!│   " "%report%"
    )
)

endlocal
exit /b
@echo off
setlocal enabledelayedexpansion

echo Generando estructura completa del sistema...
echo.

> estructura_completa.txt (
    echo ESTRUCTURA DEL SISTEMA PLANEALO
    echo ===============================
    echo Fecha: %date% %time%
    echo.
    
    echo 1. ESTRUCTURA GENERAL
    dir /B
    echo.
    
    echo 2. MODELOS
    dir models\*.php /B
    echo.
    
    echo 3. CONTROLADORES
    dir controllers\*.php /B
    echo.
    
    echo 4. VISTAS PRINCIPALES
    dir views /B
    echo.
    
    echo 5. ESTRUCTURA DE MODULOS
    for /d %%i in (modules\*) do (
        echo [%%i]
        echo   Controladores:
        dir "%%i\controllers\*.php" /B 2>nul || echo     No hay controladores
        echo   Vistas:
        for /d %%j in ("%%i\views\*" 2^>nul) do (
            echo     -- %%~nj
            dir "%%j\*.php" /B 2>nul
        )
        echo.
    )
)

echo ¡Estructura guardada en estructura_completa.txt!
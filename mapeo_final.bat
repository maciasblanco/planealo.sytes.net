@echo off
chcp 65001 >nul
title MAPEO SISTEMA PLANEALO

echo ===============================================
echo    MAPEO COMPLETO DEL SISTEMA PLANEALO
echo ===============================================
echo.

set "REPORTE=mapeo_final_%date:~-4,4%%date:~-10,2%%date:~-7,2%.txt"
echo MAPEO SISTEMA PLANEALO > "%REPORTE%"
echo Fecha: %date% %time% >> "%REPORTE%"
echo ===================== >> "%REPORTE%"
echo.

echo [1/6] Contando modelos...
echo 1. MODELOS ENCONTRADOS >> "%REPORTE%"
echo ---------------------- >> "%REPORTE%"
set /a MODELOS=0
set /a RELACIONES=0
for %%f in (models\*.php) do (
    set /a MODELOS+=1
    echo - %%~nf >> "%REPORTE%"
    
    :: Contar relaciones de forma segura
    set "tempfile=%temp%\temp_%random%.txt"
    type "%%f" > "!tempfile!" 2>nul
    for /f "tokens=1 delims=:" %%c in ('findstr /c:"public function get" "!tempfile!" 2^>nul') do (
        set /a RELACIONES+=1
    )
    del "!tempfile!" >nul 2>&1
)
echo Total modelos: !MODELOS! >> "%REPORTE%"
echo Total relaciones: !RELACIONES! >> "%REPORTE%"
echo. >> "%REPORTE%"

echo [2/6] Contando controladores...
echo 2. CONTROLADORES PRINCIPALES >> "%REPORTE%"
echo ---------------------------- >> "%REPORTE%"
set /a CONTROLADORES=0
set /a ACCIONES=0
for %%f in (controllers\*.php) do (
    set /a CONTROLADORES+=1
    echo - %%~nf >> "%REPORTE%"
    
    :: Contar acciones de forma segura
    set "tempfile=%temp%\temp_%random%.txt"
    type "%%f" > "!tempfile!" 2>nul
    for /f "tokens=1 delims=:" %%c in ('findstr /c:"public function" "!tempfile!" 2^>nul ^| findstr /c:"Action"') do (
        set /a ACCIONES+=1
    )
    del "!tempfile!" >nul 2>&1
)
echo Total controladores: !CONTROLADORES! >> "%REPORTE%"
echo Total acciones: !ACCIONES! >> "%REPORTE%"
echo. >> "%REPORTE%"

echo [3/6] Contando componentes...
echo 3. COMPONENTES >> "%REPORTE%"
echo -------------- >> "%REPORTE%"
set /a COMPONENTES=0
if exist components (
    for %%f in (components\*.php) do (
        set /a COMPONENTES+=1
        echo - %%~nf >> "%REPORTE%"
    )
)
echo Total componentes: !COMPONENTES! >> "%REPORTE%"
echo. >> "%REPORTE%"

echo [4/6] Contando widgets...
echo 4. WIDGETS >> "%REPORTE%"
echo ---------- >> "%REPORTE%"
set /a WIDGETS=0
if exist widgets (
    for %%f in (widgets\*.php) do (
        set /a WIDGETS+=1
        echo - %%~nf >> "%REPORTE%"
    )
)
echo Total widgets: !WIDGETS! >> "%REPORTE%"
echo. >> "%REPORTE%"

echo [5/6] Analizando modulos...
echo 5. MODULOS >> "%REPORTE%"
echo ---------- >> "%REPORTE%"
set /a MODULOS=0
set /a CONTROLADORES_MODULOS=0
set /a VISTAS_MODULOS=0

if exist modules (
    for /d %%m in (modules\*) do (
        set /a MODULOS+=1
        echo MODULO: %%~nxm >> "%REPORTE%"
        
        :: Controladores en modulo
        set /a CTRL_MOD=0
        if exist "%%m\controllers" (
            for %%c in ("%%m\controllers\*.php") do set /a CTRL_MOD+=1
        )
        echo - Controladores: !CTRL_MOD! >> "%REPORTE%"
        set /a CONTROLADORES_MODULOS+=!CTRL_MOD!
        
        :: Vistas en modulo
        set /a VISTAS_MOD=0
        if exist "%%m\views" (
            for /r "%%m\views" %%v in (*.php) do set /a VISTAS_MOD+=1
        )
        echo - Vistas: !VISTAS_MOD! >> "%REPORTE%"
        set /a VISTAS_MODULOS+=!VISTAS_MOD!
        echo. >> "%REPORTE%"
    )
)
echo Total modulos: !MODULOS! >> "%REPORTE%"
echo. >> "%REPORTE%"

echo [6/6] Generando resumen...
echo 6. RESUMEN FINAL >> "%REPORTE%"
echo --------------- >> "%REPORTE%"
set /a TOTAL_CONTROLADORES=!CONTROLADORES!+!CONTROLADORES_MODULOS!
set /a TOTAL_VISTAS=!VISTAS_MODULOS!

echo MODELOS: !MODELOS! >> "%REPORTE%"
echo RELACIONES: !RELACIONES! >> "%REPORTE%"
echo CONTROLADORES: !TOTAL_CONTROLADORES! >> "%REPORTE%"
echo ACCIONES: !ACCIONES! >> "%REPORTE%"
echo COMPONENTES: !COMPONENTES! >> "%REPORTE%"
echo WIDGETS: !WIDGETS! >> "%REPORTE%"
echo MODULOS: !MODULOS! >> "%REPORTE%"
echo VISTAS: !TOTAL_VISTAS! >> "%REPORTE%"

:: Mostrar resultados en pantalla
echo.
echo ========= RESULTADOS =========
echo Modelos: !MODELOS!
echo Relaciones: !RELACIONES!
echo Controladores: !TOTAL_CONTROLADORES!
echo Acciones: !ACCIONES!
echo Componentes: !COMPONENTES!
echo Widgets: !WIDGETS!
echo Modulos: !MODULOS!
echo Vistas: !TOTAL_VISTAS!
echo ==============================
echo.
echo Reporte guardado en: %REPORTE%
echo.

:: Mostrar contenido del reporte
type "%REPORTE%"

echo.
echo Presiona cualquier tecla para abrir el reporte...
pause >nul
start notepad "%REPORTE%"
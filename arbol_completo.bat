@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul
title MAPEO SISTEMA PLANEALO - ANALISIS COMPLETO

:: ===============================================
:: CONFIGURACIÓN INICIAL
:: ===============================================
echo ===============================================
echo    MAPEO COMPLETO DEL SISTEMA PLANEALO
echo ===============================================
echo.

:: Verificar que estamos en directorio válido
if not exist .\ ( 
    echo ERROR: No se puede acceder al directorio actual
    pause
    exit /b 1
)

:: Obtener fecha en formato YYYYMMDD
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value 2^>nul') do set datetime=%%I
if "!datetime!"=="" (
    set "fecha=%date:~-4,4%%date:~-7,2%%date:~-10,2%"
) else (
    set "fecha=!datetime:~0,8!"
)
set "REPORTE=mapeo_final_%fecha%.txt"
set "ERROR_LOG=errores_mapeo_%fecha%.log"

:: Iniciar reporte
echo MAPEO SISTEMA PLANEALO > "%REPORTE%"
echo Fecha: %date% %time% >> "%REPORTE%"
echo Directorio: %cd% >> "%REPORTE%"
echo ========================================== >> "%REPORTE%"
echo. >> "%REPORTE%"

:: ===============================================
:: 1. CONTAR MODELOS
:: ===============================================
echo [1/7] Analizando modelos...
echo 1. MODELOS ENCONTRADOS >> "%REPORTE%"
echo ---------------------- >> "%REPORTE%"
set /a MODELOS=0
set /a RELACIONES=0
set /a MODELOS_VACIOS=0

if exist models (
    for %%f in (models\*.php) do (
        set /a MODELOS+=1
        set "nombre=%%~nf"
        
        :: Verificar si el archivo tiene contenido
        for %%s in ("%%f") do if %%~zs EQU 0 (
            echo [VACIO] !nombre! >> "%REPORTE%"
            set /a MODELOS_VACIOS+=1
        ) else (
            echo - !nombre! >> "%REPORTE%"
        )
        
        :: Contar relaciones (más preciso para Yii2)
        set "tempfile=%temp%\temp_%random%.txt"
        type "%%f" > "!tempfile!" 2>nul
        
        :: Buscar relaciones comunes en Yii2
        for /f %%c in ('findstr /c:"hasOne(" !tempfile! ^| find /c /v ""') do (
            set /a RELACIONES+=%%c
        )
        for /f %%c in ('findstr /c:"hasMany(" !tempfile! ^| find /c /v ""') do (
            set /a RELACIONES+=%%c
        )
        for /f %%c in ('findstr /c:"belongsTo(" !tempfile! ^| find /c /v ""') do (
            set /a RELACIONES+=%%c
        )
        
        del "!tempfile!" >nul 2>&1
    )
) else (
    echo No se encontró directorio 'models' >> "%REPORTE%"
)

echo Total modelos: !MODELOS! >> "%REPORTE%"
echo Modelos vacíos: !MODELOS_VACIOS! >> "%REPORTE%"
echo Total relaciones: !RELACIONES! >> "%REPORTE%"
echo. >> "%REPORTE%"

:: ===============================================
:: 2. CONTAR CONTROLADORES
:: ===============================================
echo [2/7] Analizando controladores...
echo 2. CONTROLADORES PRINCIPALES >> "%REPORTE%"
echo ---------------------------- >> "%REPORTE%"
set /a CONTROLADORES=0
set /a ACCIONES=0
set /a CONTROLADORES_VACIOS=0

if exist controllers (
    for %%f in (controllers\*.php) do (
        set /a CONTROLADORES+=1
        set "nombre=%%~nf"
        
        :: Verificar contenido
        for %%s in ("%%f") do if %%~zs EQU 0 (
            echo [VACIO] !nombre! >> "%REPORTE%"
            set /a CONTROLADORES_VACIOS+=1
        ) else (
            echo - !nombre! >> "%REPORTE%"
        )
        
        :: Contar TODAS las funciones públicas (acciones)
        set "tempfile=%temp%\temp_%random%.txt"
        type "%%f" > "!tempfile!" 2>nul
        
        :: Buscar funciones públicas (mejor para Yii2)
        for /f %%c in ('findstr /r /c:"^[ ]*public function " !tempfile! ^| find /c /v ""') do (
            set /a ACCIONES+=%%c
        )
        
        del "!tempfile!" >nul 2>&1
    )
) else (
    echo No se encontró directorio 'controllers' >> "%REPORTE%"
)

echo Total controladores: !CONTROLADORES! >> "%REPORTE%"
echo Controladores vacíos: !CONTROLADORES_VACIOS! >> "%REPORTE%"
echo Total acciones: !ACCIONES! >> "%REPORTE%"
echo. >> "%REPORTE%"

:: ===============================================
:: 3. CONTAR COMPONENTES
:: ===============================================
echo [3/7] Analizando componentes...
echo 3. COMPONENTES >> "%REPORTE%"
echo -------------- >> "%REPORTE%"
set /a COMPONENTES=0
set /a COMPONENTES_VACIOS=0

if exist components (
    for %%f in (components\*.php) do (
        set /a COMPONENTES+=1
        
        :: Verificar contenido
        for %%s in ("%%f") do if %%~zs EQU 0 (
            echo [VACIO] %%~nf >> "%REPORTE%"
            set /a COMPONENTES_VACIOS+=1
        ) else (
            echo - %%~nf >> "%REPORTE%"
        )
    )
) else (
    echo No se encontró directorio 'components' >> "%REPORTE%"
)

echo Total componentes: !COMPONENTES! >> "%REPORTE%"
echo Componentes vacíos: !COMPONENTES_VACIOS! >> "%REPORTE%"
echo. >> "%REPORTE%"

:: ===============================================
:: 4. CONTAR WIDGETS
:: ===============================================
echo [4/7] Analizando widgets...
echo 4. WIDGETS >> "%REPORTE%"
echo ---------- >> "%REPORTE%"
set /a WIDGETS=0
set /a WIDGETS_VACIOS=0

if exist widgets (
    for %%f in (widgets\*.php) do (
        set /a WIDGETS+=1
        
        :: Verificar contenido
        for %%s in ("%%f") do if %%~zs EQU 0 (
            echo [VACIO] %%~nf >> "%REPORTE%"
            set /a WIDGETS_VACIOS+=1
        ) else (
            echo - %%~nf >> "%REPORTE%"
        )
    )
) else (
    echo No se encontró directorio 'widgets' >> "%REPORTE%"
)

echo Total widgets: !WIDGETS! >> "%REPORTE%"
echo Widgets vacíos: !WIDGETS_VACIOS! >> "%REPORTE%"
echo. >> "%REPORTE%"

:: ===============================================
:: 5. ANALIZAR MÓDULOS
:: ===============================================
echo [5/7] Analizando módulos...
echo 5. MODULOS >> "%REPORTE%"
echo ---------- >> "%REPORTE%"
set /a MODULOS=0
set /a CONTROLADORES_MODULOS=0
set /a VISTAS_MODULOS=0
set /a MODULOS_VACIOS=0

if exist modules (
    for /d %%m in (modules\*) do (
        set /a MODULOS+=1
        echo MODULO: %%~nxm >> "%REPORTE%"
        
        :: Verificar si el módulo tiene contenido
        set "modulo_vacio=1"
        
        :: Controladores en módulo
        set /a CTRL_MOD=0
        if exist "%%m\controllers" (
            for %%c in ("%%m\controllers\*.php") do (
                set /a CTRL_MOD+=1
                set "modulo_vacio=0"
            )
        )
        echo - Controladores: !CTRL_MOD! >> "%REPORTE%"
        set /a CONTROLADORES_MODULOS+=!CTRL_MOD!
        
        :: Vistas en módulo
        set /a VISTAS_MOD=0
        if exist "%%m\views" (
            for /r "%%m\views" %%v in (*.php) do (
                set /a VISTAS_MOD+=1
                set "modulo_vacio=0"
            )
        )
        echo - Vistas: !VISTAS_MOD! >> "%REPORTE%"
        set /a VISTAS_MODULOS+=!VISTAS_MOD!
        
        :: Verificar si el módulo está vacío
        if !modulo_vacio! EQU 1 (
            echo [MODULO VACIO] >> "%REPORTE%"
            set /a MODULOS_VACIOS+=1
        )
        echo. >> "%REPORTE%"
    )
) else (
    echo No se encontró directorio 'modules' >> "%REPORTE%"
)

echo Total módulos: !MODULOS! >> "%REPORTE%"
echo Módulos vacíos: !MODULOS_VACIOS! >> "%REPORTE%"
echo. >> "%REPORTE%"

:: ===============================================
:: 6. CONTAR VISTAS (RAÍZ Y ESPECIALES)
:: ===============================================
echo [6/7] Analizando vistas...
echo 6. VISTAS >> "%REPORTE%"
echo --------- >> "%REPORTE%"

:: Vistas en directorio raíz
set /a VISTAS_RAIZ=0
if exist views (
    for /r "views" %%v in (*.php) do set /a VISTAS_RAIZ+=1
    echo Vistas en directorio raiz: !VISTAS_RAIZ! >> "%REPORTE%"
) else (
    echo No se encontró directorio 'views' >> "%REPORTE%"
)

:: Vistas en widgets (si existen)
set /a VISTAS_WIDGETS=0
if exist widgets (
    for /r "widgets" %%v in (*.php) do set /a VISTAS_WIDGETS+=1
    if !VISTAS_WIDGETS! GTR 0 (
        echo Vistas en widgets: !VISTAS_WIDGETS! >> "%REPORTE%"
    )
)

:: Vistas en componentes (si existen)
set /a VISTAS_COMPONENTES=0
if exist components (
    for /r "components" %%v in (*.php) do set /a VISTAS_COMPONENTES+=1
    if !VISTAS_COMPONENTES! GTR 0 (
        echo Vistas en componentes: !VISTAS_COMPONENTES! >> "%REPORTE%"
    )
)

set /a TOTAL_VISTAS=!VISTAS_MODULOS!+!VISTAS_RAIZ!+!VISTAS_WIDGETS!+!VISTAS_COMPONENTES!
echo Total vistas: !TOTAL_VISTAS! >> "%REPORTE%"
echo. >> "%REPORTE%"

:: ===============================================
:: 7. RESUMEN FINAL
:: ===============================================
echo [7/7] Generando resumen...
echo 7. RESUMEN FINAL >> "%REPORTE%"
echo ================ >> "%REPORTE%"
set /a TOTAL_CONTROLADORES=!CONTROLADORES!+!CONTROLADORES_MODULOS!
set /a TOTAL_VISTAS=!TOTAL_VISTAS!

echo MODELOS:            !MODELOS! (!MODELOS_VACIOS! vacíos) >> "%REPORTE%"
echo RELACIONES:         !RELACIONES! >> "%REPORTE%"
echo CONTROLADORES:      !TOTAL_CONTROLADORES! (!CONTROLADORES_VACIOS! vacíos) >> "%REPORTE%"
echo ACCIONES:           !ACCIONES! >> "%REPORTE%"
echo COMPONENTES:        !COMPONENTES! (!COMPONENTES_VACIOS! vacíos) >> "%REPORTE%"
echo WIDGETS:            !WIDGETS! (!WIDGETS_VACIOS! vacíos) >> "%REPORTE%"
echo MODULOS:            !MODULOS! (!MODULOS_VACIOS! vacíos) >> "%REPORTE%"
echo VISTAS:             !TOTAL_VISTAS! >> "%REPORTE%"
echo. >> "%REPORTE%"

:: Calcular estadísticas
if !MODELOS! GTR 0 (
    set /a RELACIONES_POR_MODELO=!RELACIONES!*100/!MODELOS!
    echo Relaciones por modelo: !RELACIONES_POR_MODELO!%% >> "%REPORTE%"
)

if !TOTAL_CONTROLADORES! GTR 0 (
    set /a ACCIONES_POR_CONTROLADOR=!ACCIONES!*100/!TOTAL_CONTROLADORES!
    echo Acciones por controlador: !ACCIONES_POR_CONTROLADOR!%% >> "%REPORTE%"
)

:: Mostrar resultados en pantalla
echo.
echo ============ RESUMEN DEL SISTEMA ============
echo Modelos:          !MODELOS! (!MODELOS_VACIOS! vacíos)
echo Relaciones:       !RELACIONES!
echo Controladores:    !TOTAL_CONTROLADORES! (!CONTROLADORES_VACIOS! vacíos)
echo Acciones:         !ACCIONES!
echo Componentes:      !COMPONENTES! (!COMPONENTES_VACIOS! vacíos)
echo Widgets:          !WIDGETS! (!WIDGETS_VACIOS! vacíos)
echo Modulos:          !MODULOS! (!MODULOS_VACIOS! vacíos)
echo Vistas:           !TOTAL_VISTAS!
echo ============================================
echo.
echo Reporte guardado en: %REPORTE%

:: Mostrar contenido del reporte
echo.
echo === CONTENIDO DEL REPORTE ===
type "%REPORTE%"
echo ============================
echo.

echo Presiona cualquier tecla para abrir el reporte...
pause >nul
start notepad "%REPORTE%"

:: Opcional: Crear copia en CSV
set "CSV_REPORTE=mapeo_%fecha%.csv"
echo "Categoria","Total","Vacios" > "%CSV_REPORTE%"
echo "Modelos",!MODELOS!,!MODELOS_VACIOS! >> "%CSV_REPORTE%"
echo "Controladores",!TOTAL_CONTROLADORES!,!CONTROLADORES_VACIOS! >> "%CSV_REPORTE%"
echo "Componentes",!COMPONENTES!,!COMPONENTES_VACIOS! >> "%CSV_REPORTE%"
echo "Widgets",!WIDGETS!,!WIDGETS_VACIOS! >> "%CSV_REPORTE%"
echo "Modulos",!MODULOS!,!MODULOS_VACIOS! >> "%CSV_REPORTE%"
echo "Vistas",!TOTAL_VISTAS!,0 >> "%CSV_REPORTE%"
echo "Relaciones",!RELACIONES!,0 >> "%CSV_REPORTE%"
echo "Acciones",!ACCIONES!,0 >> "%CSV_REPORTE%"

echo.
echo Reporte CSV creado: %CSV_REPORTE%

endlocal
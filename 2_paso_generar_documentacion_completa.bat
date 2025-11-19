@echo off
echo === GENERANDO DOCUMENTACIÓN COMPLETA ===
echo.

:: Crear directorios necesarios
if not exist "docs" mkdir docs
if not exist "docs\contexto" mkdir docs\contexto
if not exist "scripts" mkdir scripts

echo 📊 Ejecutando scripts de documentación...
echo.

:: Ejecutar cada script PHP con verificación
echo [1/6] 📁 Estructura general...
php scripts\doc_estructura.php

echo [2/6] 🗂️ Modelos...
php scripts\doc_modelos.php

echo [3/6] 🎮 Controladores...
php scripts\doc_controladores.php

echo [4/6] 🏗️ Módulos...
php scripts\doc_modulos.php

echo [5/6] 📦 Dependencias...
php scripts\doc_dependencias.php

echo [6/6] 📊 Resumen ejecutivo...
php scripts\doc_resumen.php

:: Verificar generación
echo.
echo ✅ DOCUMENTACIÓN GENERADA - VERIFICANDO ARCHIVOS...
dir /B docs\

echo.
echo 📈 ESTADÍSTICAS FINALES:
for /f "tokens=3" %%i in ('dir /A:-D ^| find "archivo(s)"') do set FILES=%%i
echo    - Archivos en docs/: %FILES%
if exist "docs\RESUMEN_EJECUTIVO.md" (
    for /f "tokens=2" %%i in ('find /c /v "" ^< docs\RESUMEN_EJECUTIVO.md') do set LINES=%%i
    echo    - Líneas de documentación: %LINES%
)

echo.
pause
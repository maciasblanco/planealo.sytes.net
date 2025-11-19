@echo off
echo Actualizando contexto del proyecto...

:: Ejecutar generación de documentación
call generar_documentacion.bat

:: Crear snapshot del día sin interferir con Git
set FECHA=%date:~6,4%%date:~3,2%%date:~0,2%
if not exist "docs\contexto" mkdir "docs\contexto"
copy "docs\RESUMEN_EJECUTIVO.md" "docs\contexto\resumen_%FECHA%.md" >nul

:: Actualizar contexto para IA
type "docs\RESUMEN_EJECUTIVO.md" > "CONTEXTO_ACTUAL.md"
echo. >> "CONTEXTO_ACTUAL.md"
echo === ESTRUCTURA COMPLETA === >> "CONTEXTO_ACTUAL.md"
type "docs\estructura_general.md" >> "CONTEXTO_ACTUAL.md"

echo.
echo ✅ Contexto actualizado en CONTEXTO_ACTUAL.md
echo 📊 Resumen guardado en: docs\contexto\resumen_%FECHA%.md

:: NO hacer git add/commit automáticamente para evitar bucles
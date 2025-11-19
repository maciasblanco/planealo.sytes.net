@echo off
echo === VERIFICACIÓN COMPLETA DEL SISTEMA ===
echo.

echo 1. 📁 Archivos esenciales:
if exist "CONTEXTO_ACTUAL.md" (echo ✅ CONTEXTO_ACTUAL.md) else (echo ❌ FALTANTE)
if exist "actualizar_contexto.bat" (echo ✅ actualizar_contexto.bat) else (echo ❌ FALTANTE)
if exist ".git\hooks\post-commit" (echo ✅ Hook post-commit) else (echo ❌ FALTANTE)

echo.
echo 2. 📊 Documentación generada:
dir /B docs\

echo.
echo 3. 🔗 Prueba de hook Git:
echo    El hook se ejecutará automáticamente después de:
echo    git commit -m "prueba"
echo.
echo 4. 💡 Uso inmediato:
echo    Ejecuta: type CONTEXTO_ACTUAL.md ^| clip
echo    Luego pega en tus consultas de IA
echo.
pause
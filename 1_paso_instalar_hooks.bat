@echo off
echo === INSTALANDO HOOKS GIT AUTOMÁTICOS ===
echo.

:: Verificar que estamos en un repositorio Git
if not exist ".git" (
    echo ❌ No es un repositorio Git
    pause
    exit 1
)

:: Crear directorio hooks si no existe
if not exist ".git\hooks" mkdir ".git\hooks"

:: Crear hook post-commit
(
echo #!/bin/sh
echo.
echo echo ""
echo echo "🔄 ACTUALIZANDO DOCUMENTACIÓN AUTOMÁTICA..."
echo echo "=========================================="
echo.
echo "# Navegar al directorio del proyecto"
echo "cd /c/xampp/htdocs/planealo.sytes.net"
echo.
echo "# Ejecutar actualización de contexto"
echo "./actualizar_contexto.bat"
echo.
echo "echo \"✅ Documentación actualizada después del commit\""
echo "echo \"📄 Contexto listo en: CONTEXTO_ACTUAL.md\""
echo "echo \"\""
) > .git/hooks/post-commit

:: Versión alternativa para Windows CMD
(
echo @echo off
echo.
echo echo 🔄 ACTUALIZANDO DOCUMENTACIÓN AUTOMÁTICA...
echo echo ==========================================
echo.
echo cd "C:\xampp\htdocs\planealo.sytes.net"
echo call actualizar_contexto.bat
echo.
echo echo ✅ Documentación actualizada después del commit
echo echo 📄 Contexto listo en: CONTEXTO_ACTUAL.md
echo echo.
) > .git/hooks/post-commit.cmd

echo ✅ Hooks instalados:
echo    - .git/hooks/post-commit (Git Bash)
echo    - .git/hooks/post-commit.cmd (Windows CMD)
echo.
echo 🔧 Si usas Git Bash, da permisos de ejecución:
echo    chmod +x .git/hooks/post-commit
echo.
pause
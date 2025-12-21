@echo off
echo Creando archivos placeholder para resolver errores 404...
echo.

cd /d "C:\xampp\htdocs\planealo_desarrollo"

echo Creando carpetas si no existen...
if not exist "js" mkdir js
if not exist "js\modules" mkdir js\modules

echo 1. Creando horario-selector.js...
echo // HORARIO SELECTOR - PLACEHOLDER > "js\modules\horario-selector.js"
echo console.log('✅ horario-selector.js cargado (placeholder)'); >> "js\modules\horario-selector.js"
echo window.HorarioSelector = { init: function() { return this; } }; >> "js\modules\horario-selector.js"

echo 2. Creando tienda-module.js...
echo // TIENDA MODULE - PLACEHOLDER > "js\modules\tienda-module.js"
echo console.log('✅ tienda-module.js cargado (placeholder)'); >> "js\modules\tienda-module.js"
echo window.TiendaModule = { init: function() { return this; } }; >> "js\modules\tienda-module.js"

echo 3. Creando ged-offcanvas.js...
echo // GED OFFCANVAS - PLACEHOLDER > "js\ged-offcanvas.js"
echo console.log('✅ ged-offcanvas.js cargado (placeholder)'); >> "js\ged-offcanvas.js"
echo window.GEDOffcanvas = { init: function() { return this; } }; >> "js\ged-offcanvas.js"

echo.
echo ✅ Archivos creados exitosamente en:
echo   C:\xampp\htdocs\planealo_desarrollo\js\modules\horario-selector.js
echo   C:\xampp\htdocs\planealo_desarrollo\js\modules\tienda-module.js
echo   C:\xampp\htdocs\planealo_desarrollo\js\ged-offcanvas.js
echo.
echo Ahora limpia la cache del navegador (Ctrl+Shift+Delete) y recarga con Ctrl+F5
pause
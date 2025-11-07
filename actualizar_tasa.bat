@echo off
cd /d "C:\xampp\htdocs\planealo.sytes.net"
echo =========================================== >> cron.log
echo EJECUCION: %date% %time% >> cron.log
echo =========================================== >> cron.log

"C:\xampp\php\php.exe" yii actualizar-tasa >> cron.log 2>&1

echo. >> cron.log
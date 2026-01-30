<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user app\models\User */
/* @var $code string */
/* @var $expiryMinutes int */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación</title>
    <style>
        /* Estilos principales */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        /* Header */
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .header-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
        }
        
        .header-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0 0 10px 0;
        }
        
        .header-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0;
        }
        
        /* Body */
        .email-body {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 1.2rem;
            margin-bottom: 25px;
            color: #2c3e50;
        }
        
        .instruction {
            font-size: 1rem;
            margin-bottom: 30px;
            color: #4a5568;
        }
        
        /* Código de verificación */
        .code-container {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
            border: 2px dashed #dee2e6;
        }
        
        .code-label {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .verification-code {
            font-size: 3.5rem;
            font-weight: 700;
            letter-spacing: 15px;
            color: #667eea;
            font-family: 'Courier New', monospace;
            margin: 20px 0;
            padding: 10px;
            background: white;
            border-radius: 8px;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .code-expiry {
            font-size: 0.95rem;
            color: #6c757d;
            margin-top: 15px;
        }
        
        .expiry-warning {
            color: #e53e3e;
            font-weight: 600;
        }
        
        /* Información del usuario */
        .user-info {
            background: #e8f4fc;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            border-left: 4px solid #0d6efd;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1rem;
            color: #2c3e50;
            font-weight: 600;
        }
        
        /* Instrucciones */
        .instructions-box {
            background: #fff8e6;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            border-left: 4px solid #ffc107;
        }
        
        .instructions-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #856404;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .instructions-list {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }
        
        .instructions-list li {
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
        }
        
        .instructions-list li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
        
        /* Advertencias */
        .warnings-box {
            background: #fdeaea;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            border-left: 4px solid #dc3545;
        }
        
        .warnings-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #c53030;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .warnings-list {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }
        
        .warnings-list li {
            margin-bottom: 8px;
            padding-left: 25px;
            position: relative;
        }
        
        .warnings-list li:before {
            content: '⚠';
            position: absolute;
            left: 0;
            color: #dc3545;
            font-weight: bold;
        }
        
        /* Botón de acción */
        .action-button {
            display: inline-block;
            padding: 14px 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            margin: 20px 0;
            transition: all 0.3s;
            text-align: center;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(50,50,93,.1), 0 3px 6px rgba(0,0,0,.08);
        }
        
        /* Footer */
        .email-footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e3e3e3;
        }
        
        .footer-text {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 10px 0;
            line-height: 1.5;
        }
        
        .footer-logo {
            color: #667eea;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            .email-container {
                border-radius: 0;
            }
            
            .email-header, .email-body, .email-footer {
                padding: 25px 20px;
            }
            
            .verification-code {
                font-size: 2.5rem;
                letter-spacing: 10px;
                padding: 15px;
            }
            
            .header-icon {
                width: 60px;
                height: 60px;
                line-height: 60px;
                font-size: 2rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .action-button {
                display: block;
                margin: 25px 0;
            }
        }
        
        /* Impresión */
        @media print {
            .email-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .action-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="header-icon">🔐</div>
            <h1 class="header-title">Código de Verificación</h1>
            <p class="header-subtitle">Escuela Polideportiva y Cultural San Agustín</p>
        </div>
        
        <!-- Body -->
        <div class="email-body">
            <!-- Saludo -->
            <p class="greeting">
                Hola <strong><?= Html::encode($user->username) ?></strong>,
            </p>
            
            <p class="instruction">
                Has solicitado un código de verificación para acceder al sistema GED. 
                Utiliza el siguiente código para completar tu verificación de seguridad:
            </p>
            
            <!-- Código de verificación -->
            <div class="code-container">
                <div class="code-label">TU CÓDIGO DE VERIFICACIÓN</div>
                <div class="verification-code"><?= Html::encode($code) ?></div>
                <div class="code-expiry">
                    ⏰ <span class="expiry-warning">Válido por <?= $expiryMinutes ?> minutos</span> 
                    (hasta las <?= date('H:i', strtotime("+{$expiryMinutes} minutes")) ?>)
                </div>
            </div>
            
            <!-- Información del usuario -->
            <div class="user-info">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Usuario:</span>
                        <span class="info-value"><?= Html::encode($user->username) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Cédula:</span>
                        <span class="info-value"><?= Html::encode($user->cedula) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Solicitud:</span>
                        <span class="info-value"><?= date('d/m/Y H:i:s') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">IP de acceso:</span>
                        <span class="info-value"><?= Yii::$app->request->getUserIP() ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Instrucciones -->
            <div class="instructions-box">
                <div class="instructions-title">
                    📋 Instrucciones para usar el código
                </div>
                <ul class="instructions-list">
                    <li>Regresa a la ventana de verificación en el sistema</li>
                    <li>Ingresa el código de 6 dígitos exactamente como aparece arriba</li>
                    <li>Haz clic en "Validar Código" para continuar</li>
                    <li>Si el código es correcto, podrás establecer tu nueva contraseña</li>
                </ul>
            </div>
            
            <!-- Advertencias -->
            <div class="warnings-box">
                <div class="warnings-title">
                    ⚠️ Información de seguridad importante
                </div>
                <ul class="warnings-list">
                    <li><strong>No compartas</strong> este código con nadie</li>
                    <li>El código <strong>expirará automáticamente</strong> después de <?= $expiryMinutes ?> minutos</li>
                    <li>Solo tienes <strong>3 intentos</strong> para ingresar el código correctamente</li>
                    <li>Después de 3 intentos fallidos, la sesión será <strong>bloqueada</strong></li>
                    <li>Si no solicitaste este código, puedes ignorar este mensaje</li>
                </ul>
            </div>
            
            <!-- Botón de acción -->
            <div style="text-align: center;">
                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['site/verify-email-first']) ?>" class="action-button">
                    🔗 Volver al sistema para verificar
                </a>
            </div>
            
            <p style="text-align: center; margin-top: 20px; color: #6c757d;">
                ¿Problemas con el código? Puedes solicitar uno nuevo desde el sistema.
            </p>
        </div>
        
        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-logo">
                Escuela Polideportiva y Cultural San Agustín
            </div>
            
            <p class="footer-text">
                © <?= date('Y') ?> Sistema GED - Gestión Escolar Deportiva<br>
                Este es un mensaje automático del sistema de seguridad.
            </p>
            
            <p class="footer-text">
                <strong>No respondas a este correo</strong><br>
                Si necesitas ayuda, contacta al administrador del sistema.
            </p>
            
            <p class="footer-text" style="font-size: 0.8rem; color: #a0aec0;">
                ID de transacción: VER-<?= strtoupper(substr(md5($user->id . time()), 0, 12)) ?><br>
                Fecha de generación: <?= date('Y-m-d H:i:s') ?>
            </p>
        </div>
    </div>
</body>
</html>
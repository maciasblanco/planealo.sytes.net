<?php
/**
 * @var yii\web\View $this
 * @var int $idEscuela
 * @var string $nombreEscuela
 * @var string $navbarVariant - 'default' | 'escuela'
 */

use yii\bootstrap5\Html;
use yii\helpers\Url;

// ✅ DETECCIÓN DE RUTAS PARA EVITAR BUCLE
$currentRoute = Yii::$app->controller->route;
$isIndexRoute = $currentRoute === 'site/index';
$isLoginRoute = $currentRoute === 'site/login';
$isSignupRoute = $currentRoute === 'site/signup';

$showLoginButton = !Yii::$app->user->isGuest ? false : !$isLoginRoute;
$showSignupButton = !Yii::$app->user->isGuest ? false : (!$isSignupRoute && !$isLoginRoute);

// ✅ DETECCIÓN DE DISPOSITIVO MÓVIL
$mobileDetect = Yii::$app->has('mobileDetect') ? Yii::$app->mobileDetect->isMobile() : false;
?>

<!-- ================================================== -->
<!-- NAVBAR UNIFICADO - ANCHO COMPLETO -->
<!-- ================================================== -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-contextual fixed-top" aria-label="Navegación principal">
    <div class="container-fluid p-0 m-0 w-100 vw-100">
        
        <!-- ✅ LOGO - 15% -->
        <div class="navbar-brand-section">
            <a class="navbar-brand" href="<?= Yii::$app->homeUrl ?>" 
               title="Inicio - Sistema GED"
               onclick="return !<?= $isIndexRoute ? 'true' : 'false' ?>;">
                <?= Html::img('@web/img/logos/logoGed.png', [
                    'class' => 'navbar-logo',
                    'alt' => 'GED Logo - Sistema de Gestión Deportiva',
                    'loading' => 'eager',
                    'onerror' => "this.style.display='none'; this.nextElementSibling.style.display='block';"
                ]) ?>
                <div class="logo-fallback">
                    <strong>GED</strong><br>
                    <small>Sistema Deportivo</small>
                </div>
            </a>
        </div>

        <!-- ✅ TOGGLER PARA MÓVIL (USANDO BOOTSTRAP OFFCANVAS) -->
        <button class="navbar-toggler d-lg-none" type="button" 
                data-bs-toggle="offcanvas"
                data-bs-target="#gedMobileMenuContainer"
                aria-controls="gedMobileMenuContainer"
                aria-expanded="false"
                aria-label="Alternar navegación">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- ✅ CONTENIDO DEL NAVBAR (SOLO ESCRITORIO) -->
        <div class="collapse navbar-collapse d-none d-lg-flex" id="navbarContent">
            <div class="navbar-container">
                
                <!-- ✅ MENÚ PRINCIPAL - 50% -->
                <div class="navbar-menu-section">
                    <div class="section-container">
                        <?= \app\components\MenuWidget::widget([
                            'options' => [
                                'class' => 'navbar-nav main-navigation w-100',
                                'mobileMode' => false,  // IMPORTANTE: false para desktop
                                'rootOnly' => false     // IMPORTANTE: false para mostrar todos los niveles
                            ]
                        ]) ?>
                    </div>
                </div>
                
                <!-- ✅ REDES SOCIALES - 15% (SOLO ESCRITORIO) -->
                <div class="navbar-social-section">
                    <div class="section-container">
                        <div class="social-icons-vertical" aria-label="Redes sociales">
                            <a href="#" class="social-icon-circle" title="Facebook" aria-label="Facebook">
                                <i class="bi bi-facebook" aria-hidden="true"></i>
                            </a>
                            <a href="#" class="social-icon-circle" title="Twitter" aria-label="Twitter">
                                <i class="bi bi-twitter" aria-hidden="true"></i>
                            </a>
                            <a href="#" class="social-icon-circle" title="Instagram" aria-label="Instagram">
                                <i class="bi bi-instagram" aria-hidden="true"></i>
                            </a>
                            <a href="#" class="social-icon-circle" title="YouTube" aria-label="YouTube">
                                <i class="bi bi-youtube" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- ✅ CONTROL DE USUARIO Y ESCUELA - 20% -->
                <div class="navbar-control-section">
                    <div class="section-container">
                        
                        <!-- INFORMACIÓN DE ESCUELA (SOLO ESCRITORIO) -->
                        <div class="school-info">
                            <?php if ($idEscuela && $idEscuela > 0): ?>
                                <div class="escuela-activa-indicator">
                                    <small class="text-white d-block">
                                        <i class="bi bi-building me-1" aria-hidden="true"></i> 
                                        <strong id="current-school"><?= Html::encode(mb_strimwidth($nombreEscuela, 0, 25, '...')) ?></strong>
                                    </small>
                                    <small class="text-light opacity-75 d-block" id="current-school-id">
                                        ID: <?= $idEscuela ?>
                                    </small>
                                </div>
                                
                                <!-- SELECTOR DE ESCUELA -->
                                <div class="nav-item dropdown">
                                    <a class="nav-link text-white dropdown-toggle p-1" href="#" 
                                       id="navbarEscuelaDropdown" role="button" data-bs-toggle="dropdown" 
                                       aria-expanded="false" title="Cambiar Escuela" aria-label="Selector de escuela">
                                        <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>Cambiar
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end escuela-selector-dropdown" 
                                         aria-labelledby="navbarEscuelaDropdown">
                                        <div class="px-3 py-2">
                                            <h6 class="dropdown-header">Seleccionar Escuela</h6>
                                            <select id="navbar-escuela-select" class="form-select form-select-sm" 
                                                    aria-label="Seleccionar escuela">
                                                <option value="">Buscar escuela...</option>
                                            </select>
                                            <div class="mt-2 text-center">
                                                <small class="text-muted">Escuela actual: <?= Html::encode(mb_strimwidth($nombreEscuela, 0, 30, '...')) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            <?php else: ?>
                                <!-- SIN ESCUELA SELECCIONADA -->
                                <div class="alert alert-warning py-1 mb-2" role="alert">
                                    <small>
                                        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                                        <strong>Sin escuela seleccionada</strong>
                                    </small>
                                </div>
                                
                                <!-- BOTÓN PARA SELECCIONAR ESCUELA -->
                                <a href="<?= Url::to(['/ged/default/index']) ?>" 
                                   class="btn btn-sm btn-outline-light w-100"
                                   title="Seleccionar escuela"
                                   aria-label="Seleccionar escuela">
                                    <i class="bi bi-building me-1" aria-hidden="true"></i>
                                    <span>Seleccionar</span>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- CONTROL DE SESIÓN -->
                        <div class="session-controls mt-2">
                            <?php if (Yii::$app->user->isGuest): ?>
                                <!-- USUARIO NO AUTENTICADO -->
                                <div class="d-flex flex-column gap-1">
                                    <?php if ($showLoginButton): ?>
                                    <a class="btn btn-sm btn-outline-light" 
                                       href="<?= Yii::$app->urlManager->createUrl(['/site/login']) ?>" 
                                       title="Iniciar sesión"
                                       aria-label="Iniciar sesión">
                                        <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
                                        <span class="d-none d-lg-inline">Login</span>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($showSignupButton): ?>
                                    <a class="btn btn-sm btn-outline-light" 
                                       href="<?= Yii::$app->urlManager->createUrl(['/site/signup']) ?>" 
                                       title="Registrarse"
                                       aria-label="Crear cuenta">
                                        <i class="bi bi-person-plus me-1" aria-hidden="true"></i>
                                        <span class="d-none d-lg-inline">Registro</span>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- USUARIO AUTENTICADO -->
                                <div class="user-info mb-1 text-end">
                                    <small class="text-white d-block">
                                        <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
                                        <?= Html::encode(mb_strimwidth(Yii::$app->user->identity->username ?? 'Usuario', 0, 20, '...')) ?>
                                    </small>
                                </div>
                                <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline w-100']) ?>
                                    <?= Html::submitButton(
                                        '<i class="bi bi-box-arrow-right me-1"></i><span class="d-none d-lg-inline">Cerrar</span>',
                                        [
                                            'class' => 'btn btn-sm btn-outline-light w-100',
                                            'title' => 'Cerrar sesión',
                                            'aria-label' => 'Cerrar sesión'
                                        ]
                                    ) ?>
                                <?= Html::endForm() ?>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                </div>
                
            </div>
        </div>
        
    </div>
</nav>

<!-- ✅ SCRIPT PARA DEBUG (OPCIONAL) -->
<script>
// Solo para verificar que el menú se cargó
document.addEventListener('DOMContentLoaded', function() {
    const menuItems = document.querySelectorAll('.main-navigation .nav-item');
    console.log(`📊 MenuWidget generó ${menuItems.length} elementos en el navbar`);
    
    if (menuItems.length === 0) {
        console.warn('⚠️ No se encontraron elementos en el menú. Verifica MenuWidget.php');
    }
});
</script>
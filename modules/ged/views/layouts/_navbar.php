<?php
/**
 * @var yii\web\View $this
 * @var int $idEscuela
 * @var string $nombreEscuela
 * @var string $navbarVariant - 'default' | 'escuela'
 */

use yii\bootstrap5\Html;

// ✅ CONFIGURACIÓN ACTUALIZADA SEGÚN REUNIÓN
$logoWidth = '15%';    // Aumentado desde 10%
$menuWidth = '50%';    // Aumentado desde 40%
$socialWidth = '15%';  // Aumentado desde 12%
$controlWidth = '20%'; // Aumentado desde 13%
// Carrusel eliminado (antes 25%)

// Determinar clases CSS según el layout
$navbarClasses = 'navbar navbar-expand-lg navbar-dark navbar-contextual fixed-top';
$containerClasses = 'container-fluid';

// ✅ CSS CORREGIDO PARA GARANTIZAR VISIBILIDAD DEL LOGO
$this->registerCss("
.navbar-contextual {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 1030 !important;
    width: 100% !important;
}

/* ✅ GARANTIZAR QUE EL LOGO SEA VISIBLE EN PC */
.navbar-brand-section {
    width: {$logoWidth} !important;
    height: 100% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.navbar-brand {
    width: 100% !important;
    height: 100% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 0 !important;
    padding: 0 !important;
}

.navbar-logo {
    max-height: 85% !important;
    max-width: 90% !important;
    width: auto !important;
    height: auto !important;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    object-fit: contain !important;
}

.navbar-menu-section {
    width: {$menuWidth} !important;
}

.navbar-social-section {
    width: {$socialWidth} !important;
}

.navbar-control-section {
    width: {$controlWidth} !important;
}

/* ✅ CARRUSEL ELIMINADO */
.navbar-carousel-section {
    display: none !important;
    width: 0% !important;
}

/* ✅ CORRECCIÓN ESPECÍFICA PARA ESCRITORIO */
@media (min-width: 992px) {
    .navbar-brand-section {
        width: {$logoWidth} !important;
        min-width: 120px !important;
        max-width: 200px !important;
    }
    
    .navbar-logo {
        max-height: 150px !important;
        max-width: 180px !important;
        min-height: 80px !important;
        min-width: 100px !important;
    }
}
");

?>

<!-- ================================================== -->
<!-- NAVBAR UNIFICADO - PORCENTAJES ACTUALIZADOS -->
<!-- ================================================== -->
<nav class="<?= $navbarClasses ?>" aria-label="Navegación principal">
    <div class="<?= $containerClasses ?>">
        <!-- ✅ LOGO - 15% (AUMENTADO) - CORREGIDO -->
        <div class="navbar-brand-section">
            <a class="navbar-brand" href="<?= Yii::$app->homeUrl ?>" title="Inicio - Sistema GED">
                <?= Html::img('@web/img/logos/logoGed.png', [
                    'class' => 'navbar-logo',
                    'alt' => 'GED Logo - Sistema de Gestión Deportiva',
                    'loading' => 'eager',
                    'onerror' => "this.style.display='none'; this.nextElementSibling.style.display='block';",
                    'style' => 'display: block;'
                ]) ?>
                <!-- Fallback en caso de error -->
                <div style="display: none; background: #6c3483; color: white; padding: 10px; border-radius: 5px; text-align: center;">
                    <strong>GED</strong><br>
                    <small>Sistema Deportivo</small>
                </div>
            </a>
        </div>

        <!-- Toggler para móviles -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarGedCollapse" 
                aria-controls="navbarGedCollapse" aria-expanded="false" aria-label="Alternar navegación">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Contenido colapsable -->
        <div class="collapse navbar-collapse" id="navbarGedCollapse">
            <div class="navbar-container">
                
                <!-- ✅ SECCIÓN 1: Menú de Navegación Principal - 50% (AUMENTADO) -->
                <div class="navbar-menu-section">
                    <div class="section-container">
                        <?= \app\components\MenuWidget::widget([
                            'options' => ['class' => 'navbar-nav main-navigation']
                        ]) ?>
                    </div>
                </div>
                
                <!-- ✅ SECCIÓN 2: Redes Sociales - 15% (AUMENTADO) -->
                <div class="navbar-social-section">
                    <div class="section-container">
                        <div class="social-icons-vertical" aria-label="Redes sociales">
                            <a href="#" class="social-icon-circle" title="Síguenos en Facebook" aria-label="Facebook">
                                <i class="bi bi-facebook" aria-hidden="true"></i>
                            </a>
                            <a href="#" class="social-icon-circle" title="Síguenos en Twitter" aria-label="Twitter">
                                <i class="bi bi-twitter" aria-hidden="true"></i>
                            </a>
                            <a href="#" class="social-icon-circle" title="Síguenos en Instagram" aria-label="Instagram">
                                <i class="bi bi-instagram" aria-hidden="true"></i>
                            </a>
                            <a href="#" class="social-icon-circle" title="Visita nuestro YouTube" aria-label="YouTube">
                                <i class="bi bi-youtube" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- ✅ SECCIÓN 3: Control de Usuario y Escuela - 20% (AUMENTADO) -->
                <div class="navbar-control-section">
                    <div class="section-container">
                        <div class="w-100 text-center">
                            
                            <!-- Información de Escuela -->
                            <div class="school-info mb-3">
                                <div class="school-search-container mb-2">
                                    <?php if ($idEscuela && $idEscuela > 0): ?>
                                        <!-- Escuela Activa -->
                                        <div class="escuela-activa-indicator">
                                            <small class="text-white d-block">
                                                <i class="bi bi-building" aria-hidden="true"></i> 
                                                <strong id="current-school"><?= Html::encode($nombreEscuela) ?></strong>
                                            </small>
                                            <small class="text-light opacity-75 d-block" id="current-school-id">
                                                ID: <?= $idEscuela ?>
                                            </small>
                                            <small class="text-light opacity-50 d-block mt-1">
                                                <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i> 
                                                <span>Escuela activa</span>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <!-- Sin Escuela Seleccionada -->
                                        <div class="alert alert-warning py-1 mb-2" role="alert">
                                            <small>
                                                <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                                                <strong>Sin escuela seleccionada</strong>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Buscador/Selector de Escuelas -->
                                <?php if ($navbarVariant === 'default'): ?>
                                    <!-- Buscador para layout default -->
                                    <div class="school-search-container mb-2">
                                        <div class="input-group input-group-sm">
                                            <input type="text" 
                                                id="schoolSearch" 
                                                class="form-control" 
                                                placeholder="Buscar escuela..."
                                                aria-label="Buscar escuela"
                                                autocomplete="off"
                                                aria-describedby="searchSchoolBtn">
                                            <button class="btn btn-outline-light" type="button" id="searchSchoolBtn" aria-label="Buscar">
                                                <i class="bi bi-search" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                        <div id="schoolSearchResults" class="search-results-dropdown" aria-live="polite"></div>
                                    </div>
                                <?php else: ?>
                                    <!-- Selector para layout escuela -->
                                    <div class="nav-item dropdown mb-2">
                                        <a class="nav-link text-white dropdown-toggle p-1" href="#" id="navbarEscuelaDropdown" 
                                           role="button" data-bs-toggle="dropdown" aria-expanded="false"
                                           title="Cambiar Escuela Actual" aria-label="Selector de escuela">
                                            <i class="bi bi-building me-1" aria-hidden="true"></i>Escuela
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end escuela-selector-dropdown" 
                                             aria-labelledby="navbarEscuelaDropdown">
                                            <div class="px-3 py-2">
                                                <h6 class="dropdown-header">Seleccionar Escuela</h6>
                                                <select id="navbar-escuela-select" class="form-select form-select-sm" aria-label="Seleccionar escuela">
                                                    <option value="">Buscar escuela...</option>
                                                    <!-- Las opciones se cargarán dinámicamente -->
                                                </select>
                                                <?php if ($idEscuela && $idEscuela > 0): ?>
                                                    <div class="mt-2 text-center">
                                                        <small class="text-muted">Escuela actual: <?= Html::encode($nombreEscuela) ?></small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>                        
                            
                            <!-- Control de Sesión de Usuario -->
                            <div class="session-controls">
                                <?php if (Yii::$app->user->isGuest): ?>
                                    <!-- Usuario no autenticado -->
                                    <a class="btn btn-sm btn-outline-light w-100 mb-1" 
                                       href="<?= Yii::$app->urlManager->createUrl(['/site/login']) ?>" 
                                       title="Iniciar sesión en el sistema"
                                       aria-label="Iniciar sesión">
                                        <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Login
                                    </a>
                                    <?php if (Yii::$app->controller->route !== 'site/signup'): ?>
                                        <a class="btn btn-sm btn-outline-light w-100" 
                                           href="<?= Yii::$app->urlManager->createUrl(['/site/signup']) ?>" 
                                           title="Registrarse en el sistema"
                                           aria-label="Crear cuenta">
                                            <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Registro
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Usuario autenticado -->
                                    <div class="user-info mb-2">
                                        <small class="text-white d-block">
                                            <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
                                            <?= Yii::$app->user->identity->username ?>
                                        </small>
                                    </div>
                                    <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline w-100']) ?>
                                        <?= Html::submitButton(
                                            '<i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>Cerrar Sesión',
                                            [
                                                'class' => 'btn btn-sm btn-outline-light w-100',
                                                'title' => 'Cerrar sesión actual',
                                                'aria-label' => 'Cerrar sesión'
                                            ]
                                        ) ?>
                                    <?= Html::endForm() ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ✅ SECCIÓN 4: Carrusel ELIMINADO (antes 25%) -->
                <!-- No hay código para carrusel - Completamente eliminado -->
            </div>
        </div>
    </div>
</nav>
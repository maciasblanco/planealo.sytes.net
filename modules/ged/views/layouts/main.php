<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\NavBar;

// Registrar solo AppAsset para evitar conflictos
AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);

// Datos de la escuela (versión temporal segura)
$session = Yii::$app->session;
$idEscuela = $session->get('idEscuela', 0);
$nombreEscuela = $session->get('nombreEscuela', 'Selecciona una escuela');
$hasEscuela = !empty($idEscuela) && $idEscuela != 0;

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<!-- ================================================== -->
<!-- NAVBAR CONTEXTUAL CORREGIDO - ESTRUCTURA SIMPLIFICADA -->
<!-- ================================================== -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-contextual fixed-top">
    <div class="container-fluid">
        <!-- Logo -->
        <div class="navbar-brand-section">
            <a class="navbar-brand" href="<?= Yii::$app->homeUrl ?>">
                <?= Html::img('@web/img/logos/logoGed.png', [
                    'class' => 'navbar-logo',
                    'alt' => 'GED Logo',
                    'onerror' => "this.src='" . Yii::getAlias('@web') . "/img/logos/logoGed.png'"
                ]) ?>
            </a>
        </div>

        <!-- Toggler -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarGedCollapse" 
                aria-controls="navbarGedCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Contenido colapsable -->
        <div class="collapse navbar-collapse" id="navbarGedCollapse">
            <div class="navbar-container">
                
                <!-- SECCIÓN 1: Menú de Navegación -->
                <div class="navbar-menu-section">
                    <div class="section-container">
                        <?= \app\components\MenuWidget::widget() ?>
                    </div>
                </div>
                
                <!-- SECCIÓN 2: Redes Sociales -->
                <div class="navbar-social-section">
                    <div class="section-container">
                        <div class="social-icons-vertical">
                            <a href="#" class="social-icon-circle" title="Facebook">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="#" class="social-icon-circle" title="Twitter">
                                <i class="bi bi-twitter"></i>
                            </a>
                            <a href="#" class="social-icon-circle" title="Instagram">
                                <i class="bi bi-instagram"></i>
                            </a>
                            <a href="#" class="social-icon-circle" title="YouTube">
                                <i class="bi bi-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 3: Control de Usuario -->
                <div class="navbar-control-section">
                    <div class="section-container">
                        <div class="w-100 text-center">
                            <!-- Información de Escuela -->
                            <div class="school-info mb-3">
                                <div class="school-search-container mb-2">
                                    <?php if ($idEscuela): ?>
                                        <div class="escuela-activa-indicator">
                                            <small class="text-white d-block">
                                                <i class="bi bi-building"></i> 
                                                <strong id="current-school"><?= Html::encode($nombreEscuela) ?></strong>
                                            </small>
                                            <small class="text-light opacity-75 d-block" id="current-school-id">
                                                ID: <?= $idEscuela ?>
                                            </small>
                                            <small class="text-light opacity-50 d-block mt-1">
                                                <i class="bi bi-check-circle-fill text-success"></i> Escuela activa
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning py-1 mb-2">
                                            <small>
                                                <i class="bi bi-exclamation-triangle"></i>
                                                <strong>Sin escuela seleccionada</strong>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Buscador de Escuelas -->
                                <div class="school-search-container mb-2">
                                    <div class="input-group input-group-sm">
                                        <input type="text" 
                                            id="schoolSearch" 
                                            class="form-control" 
                                            placeholder="Buscar escuela..."
                                            autocomplete="off">
                                        <button class="btn btn-outline-light" type="button" id="searchSchoolBtn">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                    <div id="schoolSearchResults" class="search-results-dropdown"></div>
                                </div>
                            </div>                        
                            
                            <!-- Control de Sesión -->
                            <div class="session-controls">
                                <?php if (Yii::$app->user->isGuest): ?>
                                    <a class="btn btn-sm btn-outline-light w-100 mb-1" href="<?= Yii::$app->urlManager->createUrl(['/site/login']) ?>">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>Login
                                    </a>
                                <?php else: ?>
                                    <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline w-100']) ?>
                                        <?= Html::submitButton(
                                            '<i class="bi bi-box-arrow-right me-1"></i>Logout (' . Yii::$app->user->identity->username . ')',
                                            ['class' => 'btn btn-sm btn-outline-light w-100']
                                        ) ?>
                                    <?= Html::endForm() ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<main id="main" class="flex-shrink-0" role="main">
    <div class="container-fluid">
        <?php if (!empty($this->params['breadcrumbs'])): ?>
            <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
        <?php endif ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<footer id="footer" class="mt-auto py-3 bg-light">
    <div class="container-fluid">
        <div class="row align-items-center text-muted">
            <div class="col-md-6 text-center text-md-start">
                <i class="bi bi-graduation-cap me-2"></i>
                &copy; <?= date('Y') ?> Sistema GED v1.0
            </div>
            <div class="col-md-6 text-center text-md-end">
                <?php if ($idEscuela): ?>
                    <span class="badge bg-primary me-2">
                        <i class="bi bi-building"></i> Escuela ID: <?= $idEscuela ?>
                    </span>
                <?php else: ?>
                    <span class="badge bg-warning me-2">
                        <i class="bi bi-exclamation-triangle"></i> Sin escuela
                    </span>
                <?php endif; ?>
                <?= Yii::powered() ?>
            </div>
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
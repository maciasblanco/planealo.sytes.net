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
<!-- NAVBAR USANDO PARTIAL UNIFICADO -->
<!-- ================================================== -->
<?= $this->render('_navbar', [
    'idEscuela' => $idEscuela,
    'nombreEscuela' => $nombreEscuela,
    'navbarVariant' => 'default'
]) ?>

<!-- ================================================== -->
<!-- CONTENIDO PRINCIPAL CON MARGEN CORREGIDO -->
<!-- ================================================== -->
<main id="main" class="flex-shrink-0 ged-main-content" role="main">
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

<!-- ================================================== -->
<!-- OFF-CANVAS SIDEBAR - IMPLEMENTACIÓN -->
<!-- ================================================== -->
<div class="ged-offcanvas-sidebar">
    <div class="sidebar-header">
        <button class="close-sidebar" aria-label="Cerrar menú">✕</button>
        <span>Menú Principal</span>
    </div>
    <nav class="sidebar-nav" aria-label="Navegación principal">
        <!-- El menú se cargará dinámicamente desde el navbar existente -->
    </nav>
</div>

<div class="ged-sidebar-backdrop"></div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
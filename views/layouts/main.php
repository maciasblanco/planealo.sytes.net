<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\NavBar;
use app\components\MenuWidget;

// Registrar AssetBundle principal
AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);

// Datos de la escuela
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
<body class="d-flex flex-column h-100 <?= $hasEscuela ? 'escuela-layout' : 'default-layout' ?>">
<?php $this->beginBody() ?>

<!-- Contenedor para offcanvas móvil (creado dinámicamente por JS) -->
<div id="gedMobileMenuContainer"></div>

<!-- Navbar principal -->
<?php
NavBar::begin([
    'brandLabel' => Html::img('@web/img/logos/logoGed.png', [
        'class' => 'navbar-logo', 
        'alt' => 'GED Logo',
        'onerror' => "this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzZjMzQ4MyI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkdFRDwvdGV4dD48L3N2Zz4='"
    ]),
    'brandUrl' => Yii::$app->homeUrl,
    'options' => [
        'class' => 'navbar-contextual navbar-expand-lg fixed-top',
        'id' => 'main-navbar',
        'aria-label' => 'Navegación principal'
    ],
    'innerContainerOptions' => [
        'class' => 'container-fluid p-0 m-0 w-100 vw-100',
        'id' => 'navbar-container'
    ]
]);
?>

<!-- Toggler para móvil -->
<button class="navbar-toggler d-lg-none ms-auto" 
        type="button" 
        data-bs-toggle="offcanvas"
        data-bs-target="#gedMobileMenuContainer"
        aria-controls="gedMobileMenuContainer"
        aria-expanded="false"
        aria-label="Mostrar menú de navegación">
    <span class="navbar-toggler-icon"></span>
</button>

<!-- Contenido del navbar usando partial -->
<?php if (isset($this->params['renderNavbarPartial']) && $this->params['renderNavbarPartial']): ?>
    <?= $this->render('_navbar', [
        'idEscuela' => $idEscuela,
        'nombreEscuela' => $nombreEscuela,
        'navbarVariant' => $hasEscuela ? 'escuela' : 'default'
    ]) ?>
<?php else: ?>
    <!-- Contenido alternativo para páginas sin partial -->
    <?php
    $menuItems = [];
    if (Yii::$app->user->isGuest) {
        $menuItems[] = ['label' => 'Login', 'url' => ['/site/login']];
    } else {
        $menuItems[] = '<li class="nav-item">'
            . Html::beginForm(['/site/logout'], 'post', ['class' => 'form-inline'])
            . Html::submitButton(
                'Logout (' . Yii::$app->user->identity->username . ')',
                ['class' => 'nav-link btn btn-link logout']
            )
            . Html::endForm()
            . '</li>';
    }
    
    echo \yii\bootstrap5\Nav::widget([
        'options' => ['class' => 'navbar-nav ms-auto'],
        'items' => $menuItems,
    ]);
    ?>
<?php endif; ?>

<?php NavBar::end(); ?>

<!-- Contenido principal -->
<main id="main" class="flex-shrink-0 ged-main-content main-content-wrapper" role="main">
    <div class="container-fluid">
        <?php if (!empty($this->params['breadcrumbs'])): ?>
            <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
        <?php endif ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<!-- Footer -->
<footer id="footer" class="mt-auto py-3 bg-light">
    <div class="container-fluid">
        <div class="row align-items-center text-muted">
            <div class="col-md-6 text-center text-md-start">
                <i class="bi bi-graduation-cap me-2"></i>
                &copy; <?= date('Y') ?> Sistema GED v4.6
            </div>
            <div class="col-md-6 text-center text-md-end">
                <?php if ($idEscuela): ?>
                    <span class="badge bg-primary me-2">
                        <i class="bi bi-building"></i> Escuela ID: <?= $idEscuela ?>
                    </span>
                <?php else: ?>
                    <span class="badge bg-warning me-2">
                        <i class="bi bi-exclamation-triangle"></i> Selecciona una escuela
                    </span>
                <?php endif; ?>
                <span class="d-none d-md-inline"><?= Yii::powered() ?></span>
            </div>
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
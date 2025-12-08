<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\NavBar;
use app\components\MenuWidget;

// Registrar solo AppAsset para evitar conflictos
AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);

// Incluir FontAwesome (si no está ya en AppAsset)
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', ['depends' => [\yii\bootstrap5\BootstrapAsset::class]]);

// ✅ INCLUIR CSS DEL OFF-CANVAS UNIFICADO
$this->registerCssFile('@web/css/ged-offcanvas.css', ['depends' => [\yii\bootstrap5\BootstrapAsset::class]]);

// ✅ INCLUIR JS DEL OFF-CANVAS UNIFICADO
$this->registerJsFile('@web/js/ged-offcanvas.js', [
    'depends' => [\yii\web\JqueryAsset::class, \yii\bootstrap5\BootstrapAsset::class],
    'position' => \yii\web\View::POS_END
]);

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
<!-- ✅ OFF-CANVAS UNIFICADO WRAPPER -->
<!-- ================================================== -->
<div class="ged-offcanvas-wrapper">
    <!-- Backdrop solo para móvil -->
    <div class="ged-sidebar-backdrop"></div>
    
    <!-- Sidebar/Offcanvas -->
    <div class="ged-offcanvas-sidebar">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">
                <i class="fas fa-bars me-2"></i> 
                <span class="menu-text">Menú GED</span>
            </h5>
            <button type="button" class="close-offcanvas" aria-label="Cerrar menú">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="offcanvas-body p-0">
            <?= MenuWidget::widget([
                'parentId' => null,
                'options' => ['class' => 'navbar-nav']
            ]) ?>
        </div>
        
        <!-- ✅ Toggler para PC (compact mode) -->
        <button class="sidebar-toggler-pc" 
                title="Alternar menú compacto"
                aria-label="Alternar menú compacto">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
</div>

<!-- ================================================== -->
<!-- NAVBAR USANDO PARTIAL UNIFICADO -->
<!-- ================================================== -->
<?php
NavBar::begin([
    'brandLabel' => Html::img('@web/img/logos/logoGed.png', ['class' => 'navbar-logo', 'alt' => 'GED Logo']),
    'brandUrl' => Yii::$app->homeUrl,
    'options' => [
        'class' => 'navbar-contextual navbar-expand-lg',
    ],
    'innerContainerOptions' => ['class' => 'container-fluid']
]);
?>

<!-- ✅ TOGGLER PARA MÓVIL (en el navbar) -->
<button class="navbar-toggler d-lg-none ms-auto" 
        type="button" 
        data-bs-toggle="offcanvas"
        aria-label="Mostrar menú"
        aria-controls="ged-offcanvas-sidebar"
        aria-expanded="false">
    <span class="navbar-toggler-icon"></span>
</button>

<!-- Aquí va el contenido actual de tu navbar partial -->
<!-- Si usas un partial, puedes mantenerlo así: -->
<?php if (isset($this->params['renderNavbarPartial']) && $this->params['renderNavbarPartial']): ?>
    <?= $this->render('_navbar', [
        'idEscuela' => $idEscuela,
        'nombreEscuela' => $nombreEscuela,
        'navbarVariant' => 'default'
    ]) ?>
<?php else: ?>
    <!-- Contenido alternativo del navbar -->
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

<!-- ================================================== -->
<!-- ✅ CONTENIDO PRINCIPAL CON AJUSTE PARA SIDEBAR -->
<!-- ================================================== -->
<main id="main" class="flex-shrink-0 ged-main-content main-content-wrapper" role="main">
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

<!-- ✅ SCRIPT DE INICIALIZACIÓN ADICIONAL -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar que el offcanvas se haya inicializado correctamente
    if (typeof window.GedOffcanvas !== 'undefined') {
        console.log('✅ Offcanvas Manager cargado correctamente');
        
        // Opcional: Ajustar si hay contenido dinámico
        window.addEventListener('resize', function() {
            if (window.GedOffcanvas) {
                window.GedOffcanvas.update();
            }
        });
        
        // Marcar elemento activo en el menú
        var currentPath = window.location.pathname;
        document.querySelectorAll('.menu-link').forEach(function(link) {
            if (link.getAttribute('href') === currentPath || 
                link.getAttribute('href') === (currentPath + '/')) {
                link.classList.add('active');
            }
        });
    } else {
        console.warn('⚠️ Offcanvas Manager no se cargó');
    }
});
</script>

</body>
</html>
<?php $this->endPage() ?>
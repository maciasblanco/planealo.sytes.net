<?php
/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\NavBar;

AppAsset::register($this);
// ✅ REGISTRAR CSS ESPECÍFICO PARA LAYOUT ESCUELAS
//AppAsset::registerLayoutEscuelas($this);

// Select2 configuration
\app\assets\Select2BootstrapAsset::register($this);
\app\assets\Select2LoadAsset::register($this);

$this->registerJsFile('@web/js/escuela-selector.js', [
    'depends' => [\yii\web\JqueryAsset::class]
]);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);

// OBTENER DATOS DE LA ESCUELA - MÚLTIPLES FUENTES PARA RESOLVER EL PROBLEMA
$idEscuela = null;
$nombreEscuela = 'Selecciona una escuela';

// Intento 1: Desde el componente de sesión
if (Yii::$app->has('escuelaSession')) {
    $idEscuela = Yii::$app->escuelaSession->getIdEscuela();
    $nombreEscuela = Yii::$app->escuelaSession->getNombreEscuela() ?? $nombreEscuela;
}

// Intento 2: Desde parámetros GET (fallback)
if (!$idEscuela && isset($_GET['id'])) {
    $idEscuela = (int)$_GET['id'];
}

if (isset($_GET['nombre']) && empty($nombreEscuela)) {
    $nombreEscuela = $_GET['nombre'];
}

// VALORES POR DEFECTO
$idEscuela = $idEscuela ?? 0;
$nombreEscuela = $nombreEscuela ?? 'Selecciona una escuela';

// DETERMINAR LOGOS - LÓGICA CORREGIDA
$logoGed = '/img/logos/logoGed.png';

// Verificar si el logo específico de la escuela existe
$logoEscuelaPath = null;
$usarLogoEscuela = false;

if ($idEscuela > 0) {
    // Intentar diferentes formatos y rutas
    $posiblesLogos = [
        '/img/logos/escuelas/logo' . $idEscuela . '.png',
        '/img/logos/escuelas/logo' . $idEscuela . '.jpg',
        '/img/logos/escuelas/logo' . $idEscuela . '.jpeg',
        '/img/logos/escuelas/logo' . $idEscuela . '.svg'
    ];
    
    foreach ($posiblesLogos as $logo) {
        $rutaCompleta = Yii::getAlias('@webroot') . $logo;
        if (file_exists($rutaCompleta)) {
            $logoEscuelaPath = $logo;
            $usarLogoEscuela = true;
            break;
        }
    }
}

// Logos finales
$logoNavbar = $logoGed; // Navbar siempre muestra GED
$logoSidebar = $usarLogoEscuela ? $logoEscuelaPath : $logoGed;
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?> - <?= Html::encode($nombreEscuela) ?></title>
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column h-100 escuela-layout">

<?php $this->beginBody() ?>

<!-- ================================================== -->
<!-- NAVBAR USANDO PARTIAL UNIFICADO -->
<!-- ================================================== -->
<?= $this->render('_navbar', [
    'idEscuela' => $idEscuela,
    'nombreEscuela' => $nombreEscuela,
    'navbarVariant' => 'escuela'
]) ?>

<!-- ✅ CONTENIDO PRINCIPAL CON MARGEN ADECUADO -->
<main id="main" class="flex-shrink-0 main-container" role="main">
    <div class="container-fluid main-content-wrapper">
        <div class="row">
            <!-- Sidebar Izquierdo - 25% -->
            <div class="col-12 col-lg-3 sidebar-left">
                <div class="team-info">
                    <!-- Logo de la escuela específica en el sidebar - CON CACHE BUSTING -->
                    <img src="<?= Yii::getAlias('@web') . $logoSidebar . '?v=' . time() ?>" 
                         class="team-logo" 
                         alt="Logo <?= Html::encode($nombreEscuela) ?>"
                         onerror="this.src='<?= Yii::getAlias('@web') . $logoGed . '?v=' . time() ?>'">
                    <h4 class="text-white mt-3"><?= Html::encode($nombreEscuela) ?></h4>
                    <p class="text-light"><?= $idEscuela ? 'Escuela Deportiva' : 'Sistema GED' ?></p>
                    <?php if ($idEscuela): ?>
                    <div class="school-achievements mt-2">
                        <small class="text-light">
                            <i class="fas fa-id-badge me-1"></i>ID: <?= $idEscuela ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="module-info mt-4">
                    <h5 class="border-bottom pb-2 text-white">
                        <i class="fas fa-cog me-2"></i>Módulo Actual
                    </h5>
                    <p class="mb-1 text-light"><strong><?= Html::encode($this->title) ?></strong></p>
                    <p class="small text-light opacity-75">
                        <?= $idEscuela ? 'Gestión integral de la escuela deportiva.' : 'Selecciona una escuela para comenzar.' ?>
                    </p>
                    
                    <div class="mt-4">
                        <h6 class="border-bottom pb-1 text-white">
                            <i class="fas fa-bolt me-2"></i>Acciones Rápidas
                        </h6>
                        <div class="quick-actions d-grid gap-2 mt-3">
                            <?php if ($idEscuela): ?>
                                <?= Html::a('<i class="fas fa-user-plus me-2"></i> Nuevo Atleta', 
                                           ['/atletas/atletas-registro/create', 'id' => $idEscuela, 'nombre' => $nombreEscuela], 
                                           ['class' => 'btn btn-action-1']) ?>
                                <?= Html::a('<i class="fas fa-list me-2"></i> Ver Atletas', 
                                           ['/atletas/atletas-registro/index', 'id' => $idEscuela, 'nombre' => $nombreEscuela], 
                                           ['class' => 'btn btn-action-2']) ?>
                                <?= Html::a('<i class="fas fa-chart-bar me-2"></i> Reportes', 
                                           ['/aportes/aportes/reporte'], 
                                           ['class' => 'btn btn-action-3']) ?>
                            <?php else: ?>
                                <?= Html::a('<i class="fas fa-school me-2"></i> Seleccionar Escuela', 
                                           ['/ged/default/index', 'id' => 0, 'nombre' => 'Todas'], 
                                           ['class' => 'btn btn-action-1']) ?>
                                <?= Html::a('<i class="fas fa-plus me-2"></i> Nueva Escuela', 
                                           ['/escuela_club/escuela-registro/create'], 
                                           ['class' => 'btn btn-action-2']) ?>
                            <?php endif; ?>
                            <?= Html::a('<i class="fas fa-school me-2"></i> Gestionar Escuelas', 
                                       ['/escuela_club/escuela-registro/index'], 
                                       ['class' => 'btn btn-action-4']) ?>
                        </div>
                    </div>
                    
                    <div class="tasa-dolar-widget mt-4">
                        <?= \app\widgets\TasaDolarWidget::widget([
                            'showCalculator' => true,
                            'compact' => false
                        ]) ?>
                    </div>

                    <!-- Información de la Escuela -->
                    <?php if ($idEscuela): ?>
                    <div class="school-details mt-4 pt-3 border-top">
                        <h6 class="text-white mb-3">
                            <i class="fas fa-info-circle me-2"></i>Información
                        </h6>
                        <div class="school-stats">
                            <div class="stat-item d-flex justify-content-between text-light mb-2">
                                <span>Estado:</span>
                                <span class="badge badge-success">Activa</span>
                            </div>
                            <div class="stat-item d-flex justify-content-between text-light mb-2">
                                <span>Logo:</span>
                                <span class="badge <?= $usarLogoEscuela ? 'badge-success' : 'badge-warning' ?>">
                                    <?= $usarLogoEscuela ? 'Personalizado' : 'Por defecto' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Contenido Principal - 75% -->
            <div class="col-12 col-lg-9 content-right">
                <!-- Migas de pan -->
                <?php if (!empty($this->params['breadcrumbs'])): ?>
                    <nav aria-label="breadcrumb" class="mb-4">
                        <?= Breadcrumbs::widget([
                            'links' => $this->params['breadcrumbs'],
                            'options' => ['class' => 'breadcrumb bg-light p-3 rounded']
                        ]) ?>
                    </nav>
                <?php endif ?>
                
                <!-- Alertas -->
                <?= Alert::widget() ?>
                
                <!-- ✅ CONTENIDO DINÁMICO CON ESPACIADO CORRECTO -->
                <div class="main-content">
                    <?= $content ?>
                </div>
            </div>
        </div>
    </div>
</main>

<footer id="footer" class="mt-auto py-3 bg-dark text-white">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <i class="fas fa-graduation-cap me-2"></i>
                &copy; <?= date('Y') ?> 
                <?php if ($idEscuela): ?>
                    <?= Html::encode($nombreEscuela) ?> - 
                <?php endif; ?>
                Sistema GED v1.0
            </div>
            <div class="col-md-6 text-center text-md-end">
                <?php if ($idEscuela): ?>
                    <span class="badge badge-primary me-2">ID: <?= $idEscuela ?></span>
                <?php endif; ?>
                <?= Yii::powered() ?>
                <span class="ms-2">
                    <i class="fas fa-heart text-danger"></i>
                    Desarrollado con Yii2
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- Botón Back to Top -->
<a href="#" class="back-to-top d-flex align-items-center justify-content-center">
    <i class="fas fa-arrow-up"></i>
</a>

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
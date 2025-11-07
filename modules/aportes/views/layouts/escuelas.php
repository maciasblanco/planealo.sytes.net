<?php
/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\NavBar;

AppAsset::register($this);
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
    
    <!-- CSS PARA EL NUEVO NAVBAR -->
    <style>
        /* ESTILOS ESPECÍFICOS PARA TERCER NIVEL Y SUBMENÚS ANIDADOS */

|     /* NAVBAR PRINCIPAL - Z-INDEX ALTO */
        .navbar-contextual {
            height: 25vh;
            min-height: 180px;
            background: linear-gradient(135deg, #8e44ad, #6c3483);
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 9999 !important;
            margin: 0;
            padding: 0;
            border-bottom: 3px solid #7d3c98;
        }

        .navbar-container {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            position: relative;
            z-index: 10000;
        }

        /* LOGO - 10% */
        .navbar-brand-section {
            width: 10%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            z-index: 10001;
        }

        .navbar-logo {
            max-height: 80%;
            max-width: 90%;
            object-fit: contain;
        }

        /* CONTENIDO - 90% */
        .navbar-content {
            width: 90%;
            height: 100%;
            display: flex;
            align-items: center;
            padding: 0 10px;
            z-index: 10000;
        }

        /* SECCIONES */
        .navbar-menu-section {
            width: 40%;
            height: 100%;
            padding: 5px;
            position: relative;
            z-index: 10002 !important;
        }

        .navbar-social-section {
            width: 12%;
            height: 100%;
            padding: 10px 5px;
        }

        .navbar-control-section {
            width: 13%;
            height: 100%;
            padding: 10px 5px;
        }

        .navbar-carousel-section {
            width: 25%;
            height: 100%;
            padding: 10px 5px;
        }

        .section-container {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* MENÚ - Z-INDEX CRÍTICO PARA DROPDOWNS */
        .navbar-nav {
            width: 100%;
            display: flex;
            justify-content: space-around;
            align-items: center;
            position: relative;
            z-index: 10050 !important;
        }

        .nav-item {
            position: relative;
            z-index: 10051;
        }

        .nav-link {
            color: white !important;
            font-weight: 500;
            padding: 8px 15px !important;
            position: relative;
            z-index: 10052;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
        }

        /* DROPDOWN MENU - Z-INDEX MUY ALTO */
        .dropdown-menu {
            background: linear-gradient(135deg, #7d3c98, #6c3483);
            border: none;
            border-radius: 5px;
            z-index: 99999 !important;
            position: absolute !important;
            display: none;
        }

        .dropdown-menu.show {
            display: block !important;
            z-index: 99999 !important;
        }

        .dropdown-item {
            color: white !important;
            padding: 8px 15px;
            position: relative;
            z-index: 100000;
        }

        .dropdown-item:hover {
            background: rgba(255,255,255,0.1);
            z-index: 100001;
        }

        /* SUBMENÚS ANIDADOS */
        .dropdown-submenu .dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: -6px;
            margin-left: -1px;
            z-index: 100000 !important;
        }

        /* REDES SOCIALES */
        .social-icons-vertical {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .social-icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #6c3483;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            border: 2px solid #7d3c98;
            transition: all 0.3s ease;
        }

        .social-icon-circle:hover {
            background: #7d3c98;
            transform: scale(1.1);
        }

        /* CONTROLES */
        .school-info {
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
            padding: 8px;
            margin-bottom: 8px;
            text-align: center;
        }

        .school-info small {
            font-size: 0.75rem;
        }

        /* CARRUSEL */
        .carousel-image {
            object-fit: cover;
            border-radius: 5px;
        }

        /* MAIN CONTENT - Z-INDEX BAJO PARA QUE NO TAPE EL MENÚ */
        main#main {
            margin-top: 25vh;
            min-height: 75vh;
            width: 100%;
            padding: 20px 0;
            position: relative;
            z-index: 1 !important;
        }

        /* Asegurar que el contenido no tape el menú */
        .container-fluid {
            position: relative;
            z-index: 1;
        }

        /* RESPONSIVE */
        @media (max-width: 991.98px) {
            .navbar-contextual {
                height: auto;
                min-height: 80px;
            }
            
            .navbar-container {
                flex-direction: column;
            }
            
            .navbar-brand-section {
                width: 100%;
                height: 60px;
                order: 1;
            }
            
            .navbar-content {
                width: 100%;
                flex-wrap: wrap;
                order: 2;
            }
            
            .navbar-menu-section {
                width: 100%;
                height: auto;
                margin-bottom: 10px;
                order: 3;
                z-index: 10002 !important;
            }
            
            .navbar-control-section {
                width: 100%;
                height: auto;
                margin-bottom: 10px;
                order: 2;
            }
            
            .navbar-social-section {
                width: 100%;
                height: auto;
                margin-bottom: 10px;
                order: 4;
            }
            
            .navbar-carousel-section {
                display: none;
            }
            
            .social-icons-vertical {
                flex-direction: row;
                justify-content: center;
                gap: 20px;
            }
            
            .navbar-nav {
                flex-direction: column;
                gap: 5px;
            }
            
            /* Dropdowns en móvil con z-index alto */
            .dropdown-menu {
                position: static !important;
                z-index: 99999 !important;
            }
            
            main#main {
                margin-top: 200px;
                z-index: 1 !important;
            }
        }

        /* BOTONES */
        .btn-link {
            text-decoration: none;
            border: none;
            background: none;
            padding: 0;
        }

        .btn-link:hover {
            background: none;
        }

        /* OVERRIDE DE BOOTSTRAP PARA DROPDOWNS */
        .dropdown {
            position: relative;
        }

        .show > .dropdown-menu {
            display: block;
            z-index: 99999 !important;
        }

        /* ESTILOS EXISTENTES DEL LAYOUT ESCUELAS */
        .main-container {
            margin-top: 25vh;
            min-height: 75vh;
        }

        .sidebar-left {
            background: linear-gradient(135deg, #6c3483, #8e44ad);
            color: white;
            padding: 20px;
            min-height: 75vh;
        }

        .team-logo {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .content-right {
            padding: 20px;
            background: #f8f9fa;
        }

        .btn-action-1 {
            background: #e74c3c;
            color: white;
            border: none;
        }

        .btn-action-2 {
            background: #3498db;
            color: white;
            border: none;
        }

        .btn-action-3 {
            background: #2ecc71;
            color: white;
            border: none;
        }

        .btn-action-4 {
            background: #f39c12;
            color: white;
            border: none;
        }

        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #6c3483;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            z-index: 1000;
        }
    </style>
</head>
<body class="d-flex flex-column h-100">

<?php $this->beginBody() ?>

<header id="header">
    <?php
    NavBar::begin([
        'brandLabel' => Html::img('@web/img/logos/logoGed.png', [
            'class' => 'navbar-logo',
            'alt' => 'GED Logo',
            'onerror' => "this.src='" . Yii::getAlias('@web') . "/img/logos/logoGed.png'"
        ]),
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar navbar-expand-lg navbar-dark navbar-contextual fixed-top',
        ],
        'brandOptions' => [
            'class' => 'navbar-brand-section',
        ]
    ]);
    ?>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarCollapse">
        <div class="navbar-container">
            
            <!-- SECCIÓN 1: Menú de Navegación (40%) -->
            <div class="navbar-menu-section">
                <div class="section-container">
                    <?= \app\components\MenuWidget::widget() ?>
                </div>
            </div>
            
            <!-- SECCIÓN 2: Redes Sociales (12%) -->
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
            
            <!-- SECCIÓN 3: Control de Usuario (13%) -->
            <div class="navbar-control-section">
                <div class="section-container">
                    <div class="w-100 text-center">
                        <div class="school-info">
                            <small class="text-white d-block">
                                <strong><?= Html::encode($nombreEscuela) ?></strong>
                            </small>
                            <?php if ($idEscuela): ?>
                            <small class="text-light opacity-75 d-block">
                                ID: <?= $idEscuela ?>
                            </small>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Selector de Escuelas -->
                        <div class="nav-item dropdown mb-2">
                            <a class="nav-link text-white dropdown-toggle p-1" href="#" id="navbarEscuelaDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false"
                               title="Cambiar Escuela">
                                <i class="bi bi-building me-1"></i>Escuela
                            </a>
                            <div class="dropdown-menu dropdown-menu-end escuela-selector-dropdown" 
                                 aria-labelledby="navbarEscuelaDropdown">
                                <div class="px-3 py-2">
                                    <h6 class="dropdown-header">Seleccionar Escuela</h6>
                                    <select id="navbar-escuela-select" class="form-select form-select-sm">
                                        <option value="">Buscar escuela...</option>
                                    </select>
                                    <div class="mt-2 text-center">
                                        <small class="text-muted">Escuela actual: <?= Html::encode($nombreEscuela) ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Login/Logout -->
                        <div>
                            <?php if (Yii::$app->user->isGuest): ?>
                                <a class="nav-link text-white" href="<?= Yii::$app->urlManager->createUrl(['/site/login']) ?>">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                                </a>
                            <?php else: ?>
                                <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline']) ?>
                                    <?= Html::submitButton(
                                        '<i class="bi bi-box-arrow-right me-1"></i>Logout (' . Yii::$app->user->identity->username . ')',
                                        ['class' => 'nav-link btn btn-link text-white p-0']
                                    ) ?>
                                <?= Html::endForm() ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- SECCIÓN 4: Carrusel (25%) -->
            <div class="navbar-carousel-section d-none d-lg-block">
                <div class="section-container">
                    <div id="navbarCarousel" class="carousel slide w-100 h-100" data-bs-ride="carousel">
                        <div class="carousel-inner h-100 rounded">
                            <div class="carousel-item active h-100">
                                <img src="<?= Yii::getAlias('@web') ?>/img/nav_carousel/categorias.png" 
                                    class="d-block w-100 h-100 carousel-image" 
                                    alt="Categorías">
                            </div>
                            <div class="carousel-item h-100">
                                <img src="<?= Yii::getAlias('@web') ?>/img/nav_carousel/JuegosDistritales_Entrenar.png" 
                                    class="d-block w-100 h-100 carousel-image" 
                                    alt="Entrenamiento">
                            </div>
                            <div class="carousel-item h-100">
                                <img src="<?= Yii::getAlias('@web') ?>/img/nav_carousel/imgMotiva.png" 
                                    class="d-block w-100 h-100 carousel-image" 
                                    alt="Motivación">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php NavBar::end(); ?>
</header>

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
                
                <!-- Contenido dinámico -->
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

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
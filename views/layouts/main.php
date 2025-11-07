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

/**
 * ⭐⭐⭐ MODIFICACIÓN: Usar el componente EscuelaSession en lugar de sesión directa
 *$idEscuela = Yii::$app->escuelaSession->getIdEscuela();
 *$nombreEscuela = Yii::$app->escuelaSession->getNombreEscuela() ?: 'Selecciona una escuela';
 * */ 
/*
*Codigo Temporal  por panmtalla blanca
*/ 
// Datos de la escuela (versión temporal segura)
    $session = Yii::$app->session;
    $idEscuela = $session->get('idEscuela', 0);
    $nombreEscuela = $session->get('nombreEscuela', 'Selecciona una escuela');

    // Verificar si existe la escuela
    $hasEscuela = !empty($idEscuela) && $idEscuela != 0;
/**
*Finaliza el codigo termporañ
*/
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
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" 
            aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarCollapse">
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
                        <!-- ⭐⭐⭐ MODIFICACIÓN: Usar componente EscuelaSession -->
                        <div class="school-info mb-2">
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
                        
                        <!-- Control de Sesión -->
                        <div>
                            <?php if (Yii::$app->user->isGuest): ?>
                                <a class="nav-link text-white" href="<?= Yii::$app->urlManager->createUrl(['/site/login']) ?>">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                                </a>
                            <?php else: ?>
                                <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline']) ?>
                                    <?= Html::submitButton(
                                        '<i class="bi bi-box-arrow-right me-1"></i>Logout (' . Yii::$app->user->identity->username . ')',
                                        ['class' => 'nav-link btn btn-link text-white']
                                    ) ?>
                                <?= Html::endForm() ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- SECCIÓN 4: Carrusel 
            /*<div class="navbar-carousel-section d-none d-lg-block">
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
            </div>-->
        </div>
    </div>
    
    <?php NavBar::end(); ?>
</header>

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

<script>
$(document).ready(function() {
    const schoolSearch = $('#schoolSearch');
    const searchResults = $('#schoolSearchResults');
    const searchBtn = $('#searchSchoolBtn');
    const currentSchool = $('#current-school');
    const currentSchoolId = $('#current-school-id');
    
    let searchTimeout;
    
    // Función para buscar escuelas
    function searchSchools(query) {
        if (query.length < 2) {
            searchResults.hide().empty();
            return;
        }
        
        // Mostrar loading
        searchResults.html('<div class="search-result-item text-muted">Buscando...</div>').show();
        
        $.ajax({
            url: '<?= Yii::$app->urlManager->createUrl(['/ged/default/search-schools']) ?>',
            type: 'GET',
            data: { 
                q: query,
                _csrf: '<?= Yii::$app->request->getCsrfToken() ?>'
            },
            success: function(response) {
                displaySearchResults(response);
            },
            error: function(xhr, status, error) {
                console.error('Error en la búsqueda:', error);
                searchResults.html('<div class="search-result-item text-danger">Error en la búsqueda</div>').show();
            }
        });
    }
    
    // Función para mostrar resultados
    function displaySearchResults(escuelas) {
        searchResults.empty();
        
        if (!escuelas || escuelas.length === 0) {
            searchResults.append(
                '<div class="search-result-item text-muted">No se encontraron escuelas</div>'
            );
        } else {
            escuelas.forEach(function(escuela) {
                const item = $('<div class="search-result-item"></div>');
                
                // Construir la información de la escuela
                let escuelaInfo = '<div class="school-name">' + escuela.nombre + '</div>';
                escuelaInfo += '<div class="school-id">ID: ' + escuela.id + '</div>';
                
                if (escuela.direccion_administrativa) {
                    escuelaInfo += '<div class="school-address text-muted">' + escuela.direccion_administrativa + '</div>';
                }
                
                item.html(escuelaInfo);
                
                item.click(function() {
                    selectSchool({
                        id: escuela.id,
                        name: escuela.nombre
                    });
                });
                
                searchResults.append(item);
            });
        }
        
        searchResults.show();
    }
    
    // Función para seleccionar una escuela
    function selectSchool(escuela) {
        // Mostrar loading en el botón
        const originalHtml = searchBtn.html();
        searchBtn.html('<i class="bi bi-hourglass-split"></i>').prop('disabled', true);
        
        $.ajax({
            url: '<?= Yii::$app->urlManager->createUrl(['/ged/default/set-school']) ?>',
            type: 'POST',
            data: {
                schoolId: escuela.id,
                schoolName: escuela.name,
                _csrf: '<?= Yii::$app->request->getCsrfToken() ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar la interfaz
                    currentSchool.text(escuela.name);
                    currentSchoolId.text('ID: ' + escuela.id).show();
                    
                    schoolSearch.val('');
                    searchResults.hide().empty();
                    
                    // Mostrar notificación de éxito
                    showNotification('Escuela seleccionada: ' + escuela.name, 'success');
                    
                    // Recargar la página después de un breve delay
                    setTimeout(function() {
                        location.reload();
                    }, 800);
                } else {
                    showNotification('Error al seleccionar la escuela', 'error');
                }
            },
            error: function() {
                showNotification('Error de conexión', 'error');
            },
            complete: function() {
                // Restaurar el botón
                searchBtn.html(originalHtml).prop('disabled', false);
            }
        });
    }
    
    // Event listeners
    schoolSearch.on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val().trim();
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(function() {
                searchSchools(query);
            }, 300);
        } else {
            searchResults.hide().empty();
        }
    });
    
    searchBtn.on('click', function() {
        const query = schoolSearch.val().trim();
        if (query.length >= 2) {
            searchSchools(query);
        } else {
            schoolSearch.focus();
        }
    });
    
    schoolSearch.on('focus', function() {
        const query = $(this).val().trim();
        if (query.length >= 2 && searchResults.children().length > 0) {
            searchResults.show();
        }
    });
    
    // Cerrar resultados al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.school-search-container').length) {
            searchResults.hide();
        }
    });
    
    // Manejar tecla Enter
    schoolSearch.on('keypress', function(e) {
        if (e.which === 13) {
            const query = $(this).val().trim();
            if (query.length >= 2) {
                searchSchools(query);
                e.preventDefault();
            }
        }
    });
    
    // Función para mostrar notificaciones (mejorada)
    function showNotification(message, type) {
        // Si usas Bootstrap toast o similar, intégralo aquí
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alert = $('<div class="alert ' + alertClass + ' alert-dismissible fade show" style="position: fixed; top: 20px; right: 20px; z-index: 10000;"></div>');
        alert.html(
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
        );
        
        $('body').append(alert);
        
        // Auto-ocultar después de 3 segundos
        setTimeout(function() {
            alert.alert('close');
        }, 3000);
    }
});
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
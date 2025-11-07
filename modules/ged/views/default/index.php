<?php
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Escuela;

/** @var yii\web\View $this */
/** @var array $todasLasEscuelas */
/** @var array $datosEscuelas */

$this->title = 'Gestión Escuelas Deportivas';
$totalEscuelas = Escuela::find()->where(['eliminado' => false])->count();
$idEscuela = Yii::$app->escuelaSession->getIdEscuela();
$nombreEscuela = Yii::$app->escuelaSession->getNombreEscuela();
?>

<div class="site-index">
    <!-- Hero Section -->
    <section id="hero" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">Gestiona Tu Escuela Deportiva</h1>
                    <p class="hero-subtitle">Plataforma integral para el control de atletas, estadísticas y administración deportiva</p>
                    
                    <!-- Selector de Escuelas Mejorado -->
                    <div class="school-selector-widget mb-4">
                        <h5 class="text-light mb-3">
                            <?php if ($idEscuela): ?>
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                Escuela Activa: <?= Html::encode($nombreEscuela) ?>
                            <?php else: ?>
                                <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                Selecciona tu escuela
                            <?php endif; ?>
                        </h5>
                        
                        <div class="row g-2">
                            <div class="col-md-8">
                                <select id="main-escuela-select" class="form-select form-select-lg">
                                    <option value="">Buscar tu escuela...</option>
                                    <?php foreach ($todasLasEscuelas as $escuela): ?>
                                        <option value="<?= $escuela->id ?>" <?= $idEscuela == $escuela->id ? 'selected' : '' ?>>
                                            <?= Html::encode($escuela->nombre) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="d-grid gap-2">
                                    <?= Html::a('Registrar Nueva Escuela', ['/escuela_club/escuela-registro/create'], 
                                        ['class' => 'btn btn-light btn-lg']) ?>
                                    
                                    <?php if ($idEscuela): ?>
                                        <?= Html::a('Cerrar Escuela', ['/ged/default/cerrar-escuela'], 
                                            [
                                                'class' => 'btn btn-outline-warning btn-sm',
                                                'data' => [
                                                    'confirm' => '¿Estás seguro de que quieres cerrar esta escuela?',
                                                    'method' => 'post',
                                                ]
                                            ]
                                        ) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hero-stats mt-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="stat-item">
                                    <h3><?= $totalEscuelas ?></h3>
                                    <p>Escuelas Activas</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-item">
                                    <h3>4+</h3>
                                    <p>Deportes</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-item">
                                    <h3>100%</h3>
                                    <p>Tecnología Nacional</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div id="carouselHero" class="carousel slide carousel-fade" data-bs-ride="carousel">
                        <div class="carousel-inner rounded-3">
                            <div class="carousel-item active">
                                <img src="<?= Yii::getAlias('@web') ?>/img/escuela/voleibol/img/voleibol1.jpg" class="d-block w-100" alt="Voleibol">
                                <div class="carousel-caption">
                                    <h5>Voleibol</h5>
                                    <p>Técnica, agilidad y trabajo en equipo</p>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img src="<?= Yii::getAlias('@web') ?>/img/escuela/basketbol/img/basketbol.jpg" class="d-block w-100" alt="Basketbol">
                                <div class="carousel-caption">
                                    <h5>Basketbol</h5>
                                    <p>Velocidad, precisión y estrategia</p>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img src="<?= Yii::getAlias('@web') ?>/img/escuela/futbol/img/futbol.jpeg" class="d-block w-100" alt="Fútbol">
                                <div class="carousel-caption">
                                    <h5>Fútbol</h5>
                                    <p>Pasión, disciplina y superación</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Escuelas Destacadas -->
    <section id="escuelas" class="schools-section py-5 bg-light">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Escuelas Activas</h2>
                <p class="section-subtitle">Nuestras escuelas deportivas activas en el sistema</p>
                
                <!-- Filtro Rápido de Escuelas -->
                <div class="row justify-content-center mb-4">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="filtro-escuelas" class="form-control" 
                                   placeholder="Filtrar escuelas...">
                            <button class="btn btn-outline-secondary" type="button" id="btn-limpiar-filtro">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4" id="contenedor-escuelas">
                <?php foreach ($datosEscuelas as $data): ?>
                <div class="col-lg-4 col-md-6 escuela-item">
                    <div class="school-card card h-100 shadow-sm border-0">
                        <div class="school-logo-container position-relative">
                            <img src="<?= Yii::getAlias('@web') ?>/img/logos/escuelas/logo<?= $data->id ?>.png" 
                                 class="school-logo" 
                                 alt="<?= Html::encode($data->nombre) ?>"
                                 onerror="this.src='<?= Yii::getAlias('@web') ?>/img/logos/logoGed.png'">
                            
                            <!-- Badge de Estado -->
                            <div class="school-badge activa position-absolute top-0 end-0 m-2">
                                <i class="bi bi-check-circle"></i> Activa
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title school-name"><?= Html::encode($data->nombre) ?></h5>
                            
                            <?php if (!empty($data->direccion_administrativa)): ?>
                            <div class="school-location mb-2">
                                <i class="bi bi-geo-alt"></i>
                                <span class="small"><?= Html::encode($data->direccion_administrativa) ?></span>
                            </div>
                            <?php endif; ?>

                            <!-- Estado de la escuela -->
                            <div class="school-status mb-3">
                                <small class="text-success">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Escuela activa en el sistema
                                </small>
                            </div>

                            <div class="sports-tags">
                                <span class="sport-tag">Voleibol</span>
                                <span class="sport-tag">Basketbol</span>
                                <span class="sport-tag">Fútbol</span>
                            </div>

                            <p class="card-text school-description mt-2 small text-muted">
                                Escuela deportiva activa registrada en el sistema GED. Ofrece programas de formación en múltiples disciplinas deportivas.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent border-0 pt-0">
                            <div class="row g-2">
                                <div class="col-6">
                                    <button class="btn btn-secondary w-100 tooltip-en-construccion" disabled>
                                        Ver Perfil
                                    </button>
                                </div>
                                <div class="col-6">
                                    <?= Html::a('Inscribir Atleta', 
                                        ['/atletas/atletas-registro/create', 'id_escuela' => $data->id, 'nombre' => $data->nombre], 
                                        ['class' => 'btn btn-success w-100']
                                    ) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($datosEscuelas)): ?>
            <div class="text-center py-5">
                <div class="empty-state">
                    <i class="bi bi-building" style="font-size: 4rem; color: #6c757d;"></i>
                    <h3 class="mt-3">No hay escuelas activas</h3>
                    <p class="text-muted">Todas las escuelas están marcadas como eliminadas o no hay registros</p>
                    <?= Html::a('Registrar Nueva Escuela', ['/escuela_club/escuela-registro/create'], ['class' => 'btn btn-primary btn-lg']) ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ver Todas las Escuelas -->
            <?php if (count($todasLasEscuelas) > 10): ?>
            <div class="text-center mt-5">
                <?= Html::a('Ver Todas las Escuelas Activas (' . count($todasLasEscuelas) . ')', ['/ged/default/todas-escuelas'], 
                    ['class' => 'btn btn-outline-primary btn-lg']) ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Sección: Sobre el Sistema GED -->
    <section id="sobre-sistema" class="about-system-section">
        <div class="container">
            <div class="row justify-content-center mb-5">
                <div class="col-lg-8 text-center">
                    <h2 class="display-4 fw-bold mb-4">Sistema GED</h2>
                    <p class="lead">Gestión Escuelas Deportivas - Plataforma tecnológica para el desarrollo deportivo nacional</p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Misión -->
                <div class="col-lg-4">
                    <div class="mission-vision-card">
                        <div class="text-center mb-4">
                            <i class="bi bi-bullseye system-icon"></i>
                            <h3 class="h4">Misión</h3>
                        </div>
                        <p class="text-center">
                            Desarrollar una plataforma tecnológica integral que optimice la gestión de escuelas deportivas, 
                            facilitando el registro de atletas, el control de entrenamientos y la administración de recursos, 
                            contribuyendo al desarrollo del deporte nacional con herramientas innovadoras y accesibles.
                        </p>
                    </div>
                </div>

                <!-- Visión -->
                <div class="col-lg-4">
                    <div class="mission-vision-card">
                        <div class="text-center mb-4">
                            <i class="bi bi-eye system-icon"></i>
                            <h3 class="h4">Visión</h3>
                        </div>
                        <p class="text-center">
                            Ser el sistema de gestión deportiva líder a nivel nacional, reconocido por su eficiencia, 
                            innovación tecnológica y contribución al desarrollo del talento deportivo, integrando a todas 
                            las escuelas deportivas en una red unificada que promueva la excelencia atlética y administrativa.
                        </p>
                    </div>
                </div>

                <!-- Historia -->
                <div class="col-lg-4">
                    <div class="mission-vision-card">
                        <div class="text-center mb-4">
                            <i class="bi bi-clock-history system-icon"></i>
                            <h3 class="h4">Nuestra Historia</h3>
                        </div>
                        <p class="text-center">
                            Nacido de la necesidad de modernizar la gestión deportiva escolar, el Sistema GED fue creado 
                            en 2024 para digitalizar y optimizar los procesos administrativos de las escuelas deportivas, 
                            combinando experiencia deportiva con tecnología de vanguardia para transformar la manera en 
                            que se gestiona el deporte formativo.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Línea de Tiempo del Desarrollo -->
            <div class="row mt-5">
                <div class="col-lg-8 mx-auto">
                    <h3 class="text-center mb-4">Nuestro Proceso de Desarrollo</h3>
                    <div class="timeline">
                        <div class="timeline-item">
                            <h5>Análisis de Requerimientos</h5>
                            <p class="text-muted">Identificación de necesidades específicas de las escuelas deportivas y mapeo de procesos existentes</p>
                        </div>
                        <div class="timeline-item">
                            <h5>Diseño del Sistema</h5>
                            <p class="text-muted">Creación de arquitectura modular y diseño de interfaz centrado en el usuario</p>
                        </div>
                        <div class="timeline-item">
                            <h5>Desarrollo Tecnológico</h5>
                            <p class="text-muted">Implementación con tecnologías modernas y estándares de seguridad</p>
                        </div>
                        <div class="timeline-item">
                            <h5>Pruebas y Validación</h5>
                            <p class="text-muted">Testing exhaustivo con usuarios reales y ajustes basados en feedback</p>
                        </div>
                        <div class="timeline-item">
                            <h5>Implementación Nacional</h5>
                            <p class="text-muted">Despliegue progresivo y capacitación a escuelas deportivas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección: Equipo de Desarrollo -->
    <section id="equipo" class="team-section">
        <div class="container">
            <div class="row justify-content-center mb-5">
                <div class="col-lg-8 text-center">
                    <h2 class="display-5 fw-bold mb-3">Nuestro Equipo</h2>
                    <p class="lead text-muted">Profesionales comprometidos con la innovación en gestión deportiva</p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Director del Proyecto -->
                <div class="col-lg-3 col-md-6">
                    <div class="team-card">
                        <img src="<?= Yii::getAlias('@web') ?>/img/team/equipoDesarrollo.jpg" 
                             class="team-photo" 
                             alt="Director del Proyecto"
                             onerror="this.src='<?= Yii::getAlias('@web') ?>/img/team/equipoDesarrollo.jpg'">
                        <h4 class="h5 mb-2">Lic. Carlos Rodríguez</h4>
                        <div class="team-role">Director del Proyecto</div>
                        <p class="text-muted small">
                            Más de 15 años de experiencia en gestión y administración de proyectos tecnológicos.
                        </p>
                        <div class="social-links mt-3">
                            <a href="#" class="text-primary me-2"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="text-primary me-2"><i class="bi bi-envelope"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Analista de Sistemas -->
                <div class="col-lg-3 col-md-6">
                    <div class="team-card">
                        <img src="<?= Yii::getAlias('@web') ?>/img/team/team-2.jpg" 
                             class="team-photo" 
                             alt="Analista de Sistemas"
                             onerror="this.src='<?= Yii::getAlias('@web') ?>/img/team/team-2.jpg'">
                        <h4 class="h5 mb-2">Ing. María González</h4>
                        <div class="team-role">Analista de Sistemas</div>
                        <p class="text-muted small">
                            Especialista en análisis de procesos y diseño de soluciones tecnológicas para el deporte.
                        </p>
                        <div class="social-links mt-3">
                            <a href="#" class="text-primary me-2"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="text-primary me-2"><i class="bi bi-envelope"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Programador -->
                <div class="col-lg-3 col-md-6">
                    <div class="team-card">
                        <img src="<?= Yii::getAlias('@web') ?>/img/team/team-1.jpg" 
                             class="team-photo" 
                             alt="Programador"
                             onerror="this.src='<?= Yii::getAlias('@web') ?>/img/team/team-1.jpg'">
                        <h4 class="h5 mb-2">Ing. José Martínez</h4>
                        <div class="team-role">Programador Líder</div>
                        <p class="text-muted small">
                            Desarrollador full-stack con expertise en Yii2, bases de datos y arquitecturas escalables.
                        </p>
                        <div class="social-links mt-3">
                            <a href="#" class="text-primary me-2"><i class="bi bi-github"></i></a>
                            <a href="#" class="text-primary me-2"><i class="bi bi-linkedin"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Tester QA -->
                <div class="col-lg-3 col-md-6">
                    <div class="team-card">
                        <img src="<?= Yii::getAlias('@web') ?>/img/team/team-4.jpg" 
                             class="team-photo" 
                             alt="Tester QA"
                             onerror="this.src='<?= Yii::getAlias('@web') ?>/img/team/team-4.jpg'">
                        <h4 class="h5 mb-2">Lic. Ana López</h4>
                        <div class="team-role">Tester QA</div>
                        <p class="text-muted small">
                            Especialista en control de calidad, testing de software y experiencia de usuario.
                        </p>
                        <div class="social-links mt-3">
                            <a href="#" class="text-primary me-2"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="text-primary me-2"><i class="bi bi-envelope"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tecnologías Utilizadas -->
            <div class="row mt-5">
                <div class="col-lg-8 mx-auto">
                    <div class="text-center">
                        <h3 class="mb-4">Tecnologías Implementadas</h3>
                        <div class="row g-4">
                            <div class="col-3">
                                <div class="tech-item">
                                    <i class="bi bi-code-slash display-6 text-primary"></i>
                                    <p class="mt-2 mb-0">Yii2 Framework</p>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="tech-item">
                                    <i class="bi bi-database display-6 text-primary"></i>
                                    <p class="mt-2 mb-0">PostgreSQL</p>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="tech-item">
                                    <i class="bi bi-bootstrap display-6 text-primary"></i>
                                    <p class="mt-2 mb-0">Bootstrap 5</p>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="tech-item">
                                    <i class="bi bi-shield-check display-6 text-primary"></i>
                                    <p class="mt-2 mb-0">Seguridad RBAC</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección: Contacto -->
    <section id="contacto" class="contact-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center">
                    <h2 class="mb-4">¿Necesitas Ayuda con el Sistema?</h2>
                    <p class="lead text-muted mb-4">
                        Nuestro equipo de soporte está disponible para asistirte en la implementación y uso del sistema GED.
                    </p>
                    <div class="d-flex gap-3 justify-content-center">
                        <?= Html::a('Contactar Soporte', 'mailto:soporte@sistemaged.com', ['class' => 'btn btn-primary btn-lg']) ?>
                        <?= Html::a('Documentación', '#', ['class' => 'btn btn-outline-primary btn-lg']) ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
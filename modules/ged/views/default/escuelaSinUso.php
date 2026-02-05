<?php
/** @var yii\web\View $this */
/** @var app\models\Escuela $escuela */
/** @var array $estadisticas */
/** @var array $horarios */
/** @var array $canchas */
/** @var array $deportes */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $escuela->nombre . ' - Landing Page';
?>


<div class="escuela-landing-page">
    <!-- Hero Section -->
    <section class="hero-section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 100px 0; position: relative;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="hero-title display-4 fw-bold mb-4"><?= Html::encode($escuela->nombre) ?></h1>
                        <p class="hero-subtitle lead mb-4">
                            <?= $escuela->descripcion ?? 'Escuela deportiva de excelencia formando atletas de alto rendimiento.' ?>
                        </p>
                        
                        <!-- Información rápida -->
                        <div class="hero-stats row mt-5">
                            <div class="col-4 text-center">
                                <div class="stat-number h2 fw-bold"><?= $estadisticas['total_atletas'] ?? 0 ?></div>
                                <div class="stat-label small">Atletas</div>
                            </div>
                            <div class="col-4 text-center">
                                <div class="stat-number h2 fw-bold"><?= $estadisticas['total_deportes'] ?? 0 ?></div>
                                <div class="stat-label small">Deportes</div>
                            </div>
                            <div class="col-4 text-center">
                                <div class="stat-number h2 fw-bold"><?= $estadisticas['entrenadores'] ?? 0 ?></div>
                                <div class="stat-label small">Entrenadores</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="hero-image text-center">
                        <img src="<?= Yii::getAlias('@web') ?>/img/logos/escuelas/logo<?= $escuela->id ?>.png" 
                             alt="<?= Html::encode($escuela->nombre) ?>"
                             class="img-fluid rounded-3 shadow-lg"
                             style="max-height: 300px; background: rgba(255,255,255,0.1); padding: 20px;"
                             onerror="this.src='<?= Yii::getAlias('@web') ?>/img/logos/logoGed.png'">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Estadísticas Detalladas -->
    <section class="stats-section py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card stat-card text-center h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="stat-icon mb-3">
                                <i class="fas fa-users fa-3x text-primary"></i>
                            </div>
                            <h3 class="stat-number fw-bold text-primary"><?= $estadisticas['total_atletas'] ?? 0 ?></h3>
                            <p class="stat-label text-muted mb-0">Total Atletas</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card stat-card text-center h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="stat-icon mb-3">
                                <i class="fas fa-user-check fa-3x text-success"></i>
                            </div>
                            <h3 class="stat-number fw-bold text-success"><?= $estadisticas['atletas_activos'] ?? 0 ?></h3>
                            <p class="stat-label text-muted mb-0">Atletas Activos</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card stat-card text-center h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="stat-icon mb-3">
                                <i class="fas fa-basketball-ball fa-3x text-info"></i>
                            </div>
                            <h3 class="stat-number fw-bold text-info"><?= $estadisticas['total_deportes'] ?? 0 ?></h3>
                            <p class="stat-label text-muted mb-0">Deportes</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card stat-card text-center h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="stat-icon mb-3">
                                <i class="fas fa-chalkboard-teacher fa-3x text-warning"></i>
                            </div>
                            <h3 class="stat-number fw-bold text-warning"><?= $estadisticas['entrenadores'] ?? 0 ?></h3>
                            <p class="stat-label text-muted mb-0">Entrenadores</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Deportes Disponibles -->
    <section class="sports-section py-5">
        <div class="container">
            <h2 class="section-title text-center mb-5">Deportes Disponibles</h2>
            <div class="row">
                <?php foreach ($deportes as $deporte => $info): ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card sport-card h-100 border-0 shadow-sm <?= $info['activo'] ? 'border-success' : 'border-secondary' ?>">
                        <div class="card-body text-center">
                            <div class="sport-icon mb-3">
                                <?php 
                                $iconos = [
                                    'Voleibol' => 'volleyball-ball',
                                    'Basketbol' => 'basketball-ball',
                                    'Fútbol' => 'futbol',
                                    'Atletismo' => 'running'
                                ];
                                $icono = $iconos[$deporte] ?? 'dumbbell';
                                ?>
                                <i class="fas fa-<?= $icono ?> fa-3x <?= $info['activo'] ? 'text-success' : 'text-secondary' ?>"></i>
                            </div>
                            <h5 class="sport-name fw-bold"><?= $deporte ?></h5>
                            <p class="sport-categories small text-muted mb-2"><?= $info['categoria'] ?></p>
                            <span class="badge <?= $info['activo'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $info['activo'] ? 'Activo' : 'En formación' ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Horarios de Práctica -->
    <section class="schedule-section py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center mb-5">Horarios de Práctica</h2>
            <div class="row">
                <?php foreach ($horarios as $horario): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card schedule-card h-100 border-0 shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?= $horario['deporte'] ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="schedule-info">
                                <p class="mb-2">
                                    <i class="fas fa-calendar-alt text-primary me-2"></i>
                                    <strong>Días:</strong> <?= $horario['dias'] ?>
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-clock text-primary me-2"></i>
                                    <strong>Horario:</strong> <?= $horario['horario'] ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-users text-primary me-2"></i>
                                    <strong>Grupo:</strong> <?= $horario['grupo'] ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Canchas e Instalaciones -->
    <section class="facilities-section py-5">
        <div class="container">
            <h2 class="section-title text-center mb-5">Instalaciones</h2>
            <div class="row">
                <?php foreach ($canchas as $cancha): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card facility-card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0"><?= $cancha['nombre'] ?></h5>
                                <span class="badge <?= $cancha['estado'] == 'Disponible' ? 'bg-success' : 'bg-warning' ?>">
                                    <?= $cancha['estado'] ?>
                                </span>
                            </div>
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                <strong>Tipo:</strong> <?= $cancha['tipo'] ?>
                            </p>
                            <p class="card-text">
                                <i class="fas fa-users text-muted me-2"></i>
                                <strong>Capacidad:</strong> <?= $cancha['capacidad'] ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="cta-title mb-4">¿Listo para unirte a nuestra escuela?</h2>
            <p class="cta-subtitle mb-4">Forma parte de nuestra familia deportiva y desarrolla tu potencial</p>
            <div class="cta-buttons">
                <?= Html::a('<i class="fas fa-user-plus me-2"></i> Inscribir Atleta', 
                           ['/atletas/atletas-registro/create'], 
                           ['class' => 'btn btn-light btn-lg me-3']) ?>
                <?= Html::a('<i class="fas fa-phone me-2"></i> Contactar', 
                           ['/site/contact'], 
                           ['class' => 'btn btn-outline-light btn-lg']) ?>
            </div>
        </div>
    </section>

    <!-- Información de Contacto -->
    <section class="contact-section py-5 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="mb-3">Información de Contacto</h5>
                    <p>
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <?= $escuela->direccion_administrativa ?? 'Dirección no disponible' ?>
                    </p>
                    <p>
                        <i class="fas fa-phone me-2"></i>
                        <?= $escuela->telefono ?? 'Teléfono no disponible' ?>
                    </p>
                    <p>
                        <i class="fas fa-envelope me-2"></i>
                        <?= $escuela->email ?? 'Email no disponible' ?>
                    </p>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <h5 class="mb-3">Horario de Atención</h5>
                    <p>Lunes a Viernes: 8:00 AM - 5:00 PM</p>
                    <p>Sábados: 8:00 AM - 12:00 PM</p>
                    <p>Domingos: Cerrado</p>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <h5 class="mb-3">Acciones Rápidas</h5>
                    <div class="d-grid gap-2">
                        <?= Html::a('Ver Todos los Atletas', ['/atletas/atletas-registro/index'], 
                                   ['class' => 'btn btn-outline-light btn-sm']) ?>
                        <?= Html::a('Generar Reportes', ['/aportes/aportes/reporte'], 
                                   ['class' => 'btn btn-outline-light btn-sm']) ?>
                        <?= Html::a('Gestionar Aportes', ['/aportes/aportes/index'], 
                                   ['class' => 'btn btn-outline-light btn-sm']) ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
// Estilos específicos para la landing page
$this->registerCss(<<<CSS
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card {
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.sport-card {
    transition: all 0.3s ease;
}

.sport-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.schedule-card {
    border-left: 4px solid #007bff !important;
}

.facility-card {
    border-left: 4px solid #28a745 !important;
}

.section-title {
    position: relative;
    padding-bottom: 15px;
}

.section-title:after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.cta-section {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}
CSS
);
?>
<?php
/** @var yii\web\View $this */
/** @var bool $isAuthenticated */

$this->title = 'Sistema GED - Gestión Escuelas Deportivas';
$this->params['breadcrumbs'] = []; // Eliminar breadcrumbs en landing
?>

<div class="site-index landing-page">
    <!-- Hero Section - Sin información sensible -->
    <div class="hero-section text-center py-5">
        <div class="container">
            <img src="<?= Yii::getAlias('@web') ?>/img/logos/logoGed.png" 
                 alt="GED Logo" 
                 class="mb-4 ged-logo"
                 id="ged-main-logo">
            
            <h1 class="display-4">Gestión Escuelas Deportivas</h1>
            <p class="lead">Plataforma tecnológica para la administración deportiva</p>
            
            <hr class="my-4">
            
            <!-- Acciones según estado de autenticación -->
            <div class="mt-4 landing-actions">
                <?php if (!$isAuthenticated): ?>
                    <!-- Usuario NO autenticado -->
                    <a href="<?= Yii::$app->urlManager->createUrl(['/site/login']) ?>" 
                       class="btn btn-primary btn-lg mx-2 landing-btn">
                        Iniciar Sesión
                    </a>
                <?php else: ?>
                    <!-- Usuario autenticado -->
                    <a href="<?= Yii::$app->urlManager->createUrl(['/site/acceder-sistema']) ?>" 
                       class="btn btn-success btn-lg mx-2 landing-btn"
                       id="btn-acceder-sistema">
                        Acceder al Sistema
                    </a>
                    <a href="<?= Yii::$app->urlManager->createUrl(['/site/mi-cuenta']) ?>" 
                       class="btn btn-info btn-lg mx-2 landing-btn">
                        Mi Cuenta
                    </a>
                <?php endif; ?>
                
                <!-- Marketplace siempre visible -->
                <a href="<?= Yii::$app->urlManager->createUrl(['/tienda/marketplace/index']) ?>" 
                   class="btn btn-warning btn-lg mx-2 landing-btn"
                   id="btn-marketplace">
                    Marketplace Deportivo
                </a>
            </div>
        </div>
    </div>
    
    <!-- ✅ NUEVO: Banner de Tiendas Patrocinadas (60% de pantalla) -->
    <section id="tiendas-patrocinadas" class="tiendas-patrocinadas-section vh-60">
        <div class="container-fluid h-100">
            <div class="row h-100 align-items-center">
                <div class="col-lg-8 offset-lg-2 text-center">
                    <h2 class="display-4 fw-bold mb-4 text-white">✨ Tiendas Destacadas</h2>
                    <p class="lead mb-5 text-white">Descubre las mejores tiendas deportivas patrocinadas</p>
                    
                    <div class="row g-4 justify-content-center">
                        <!-- Tienda 1 -->
                        <div class="col-md-4">
                            <div class="tienda-card patrocinada">
                                <div class="tienda-badge">⭐ Patrocinada</div>
                                <div class="tienda-logo">
                                    <i class="bi bi-shop-window"></i>
                                </div>
                                <h5 class="tienda-nombre">SportPro Store</h5>
                                <p class="tienda-desc">Equipamiento profesional</p>
                                <div class="tienda-rating">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-half"></i>
                                    <span class="ms-2">4.5</span>
                                </div>
                                <a href="/tienda/marketplace/tienda/1" class="btn btn-light btn-sm mt-3">
                                    Visitar Tienda
                                </a>
                            </div>
                        </div>
                        
                        <!-- Tienda 2 -->
                        <div class="col-md-4">
                            <div class="tienda-card patrocinada">
                                <div class="tienda-badge">🔥 Popular</div>
                                <div class="tienda-logo">
                                    <i class="bi bi-trophy"></i>
                                </div>
                                <h5 class="tienda-nombre">Champion Gear</h5>
                                <p class="tienda-desc">Para campeones</p>
                                <div class="tienda-rating">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star"></i>
                                    <span class="ms-2">4.0</span>
                                </div>
                                <a href="/tienda/marketplace/tienda/2" class="btn btn-light btn-sm mt-3">
                                    Visitar Tienda
                                </a>
                            </div>
                        </div>
                        
                        <!-- Tienda 3 -->
                        <div class="col-md-4">
                            <div class="tienda-card patrocinada">
                                <div class="tienda-badge">💎 Premium</div>
                                <div class="tienda-logo">
                                    <i class="bi bi-gem"></i>
                                </div>
                                <h5 class="tienda-nombre">Elite Sports</h5>
                                <p class="tienda-desc">Calidad premium</p>
                                <div class="tienda-rating">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <span class="ms-2">5.0</span>
                                </div>
                                <a href="/tienda/marketplace/tienda/3" class="btn btn-light btn-sm mt-3">
                                    Visitar Tienda
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Call to Action -->
                    <div class="mt-5">
                        <a href="/tienda/marketplace/tiendas" class="btn btn-outline-light btn-lg me-3">
                            <i class="bi bi-eye"></i> Ver Todas las Tiendas
                        </a>
                        <a href="/tienda/marketplace/anunciarse" class="btn btn-light btn-lg">
                            <i class="bi bi-megaphone"></i> Anunciar Mi Tienda
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Características del Sistema -->
    <div class="features-section py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card text-center h-100 feature-card">
                        <div class="card-body">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-lock fa-2x"></i>
                            </div>
                            <h5 class="feature-title">Acceso Seguro</h5>
                            <p class="feature-description">Autenticación protegida y encriptada para garantizar la seguridad de tus datos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card text-center h-100 feature-card">
                        <div class="card-body">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-store fa-2x"></i>
                            </div>
                            <h5 class="feature-title">Marketplace</h5>
                            <p class="feature-description">Productos y servicios deportivos para complementar tu experiencia</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card text-center h-100 feature-card">
                        <div class="card-body">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-shield-alt fa-2x"></i>
                            </div>
                            <h5 class="feature-title">Sistema Privado</h5>
                            <p class="feature-description">Acceso restringido a usuarios autorizados para proteger la información</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ✅ CORREGIDO: Productos Más Vendidos -->
    <section id="productos-mas-vendidos" class="productos-mas-vendidos py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary mb-3">🏆 Productos Más Vendidos</h2>
                <p class="lead text-muted">Los productos preferidos por nuestra comunidad deportiva</p>
            </div>
            
            <div class="row g-4">
                <!-- Vestimenta -->
                <div class="col-lg-3 col-md-6">
                    <div class="categoria-card h-100">
                        <div class="categoria-header vestimenta-bg">
                            <div class="categoria-icon">
                                <i class="bi bi-tshirt-fill"></i>
                            </div>
                            <h3 class="categoria-title">Vestimenta</h3>
                            <span class="categoria-badge">3 productos</span>
                        </div>
                        <div class="categoria-body" id="productos-vestimenta">
                            <!-- Los 3 productos se cargarán dinámicamente -->
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Alimentación -->
                <div class="col-lg-3 col-md-6">
                    <div class="categoria-card h-100">
                        <div class="categoria-header alimentacion-bg">
                            <div class="categoria-icon">
                                <i class="bi bi-egg-fried"></i>
                            </div>
                            <h3 class="categoria-title">Alimentación</h3>
                            <span class="categoria-badge">3 productos</span>
                        </div>
                        <div class="categoria-body" id="productos-alimentacion">
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Implementos Deportivos -->
                <div class="col-lg-3 col-md-6">
                    <div class="categoria-card h-100">
                        <div class="categoria-header implementos-bg">
                            <div class="categoria-icon">
                                <i class="bi bi-bicycle"></i>
                            </div>
                            <h3 class="categoria-title">Implementos Deportivos</h3>
                            <span class="categoria-badge">3 productos</span>
                        </div>
                        <div class="categoria-body" id="productos-implementos-deportivos">
                            <!-- CORREGIDO: ID actualizado -->
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Suplementos -->
                <div class="col-lg-3 col-md-6">
                    <div class="categoria-card h-100">
                        <div class="categoria-header suplementos-bg">
                            <div class="categoria-icon">
                                <i class="bi bi-capsule-pill"></i>
                            </div>
                            <h3 class="categoria-title">Suplementos</h3>
                            <span class="categoria-badge">3 productos</span>
                        </div>
                        <div class="categoria-body" id="productos-suplementos">
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contador total -->
            <div class="text-center mt-5">
                <div class="total-vendidos-card">
                    <i class="bi bi-trophy-fill total-icon"></i>
                    <div class="total-info">
                        <h4 class="total-titulo">Total de Productos Vendidos</h4>
                        <div class="total-cantidad">
                            <span id="total-productos">0</span>
                            <small class="text-muted">productos este mes</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Información adicional según estado de autenticación -->
    <?php if (!$isAuthenticated): ?>
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card welcome-message">
                    <div class="card-body text-center">
                        <h4 class="card-title">¡Bienvenido al Sistema GED!</h4>
                        <p class="card-text">
                            Para acceder al sistema completo de gestión de escuelas deportivas, 
                            por favor inicia sesión con tus credenciales. Si aún no tienes una cuenta, 
                            contacta al administrador del sistema.
                        </p>
                        <div class="mt-3">
                            <a href="<?= Yii::$app->urlManager->createUrl(['/site/login']) ?>" 
                               class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Información adicional para usuarios autenticados -->
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card welcome-message authenticated">
                    <div class="card-body text-center">
                        <h4 class="card-title">¡Hola de nuevo!</h4>
                        <p class="card-text">
                            Estás autenticado en el sistema GED. Haz clic en "Acceder al Sistema" 
                            para comenzar a gestionar las escuelas deportivas.
                        </p>
                        <div class="mt-3">
                            <a href="<?= Yii::$app->urlManager->createUrl(['/site/acceder-sistema']) ?>" 
                               class="btn btn-success btn-lg">
                                <i class="fas fa-tachometer-alt"></i> Acceder al Sistema
                            </a>
                            <a href="<?= Yii::$app->urlManager->createUrl(['/site/mi-cuenta']) ?>" 
                               class="btn btn-info btn-lg ms-2">
                                <i class="fas fa-user-cog"></i> Mi Cuenta
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Footer de la landing page -->
    <div class="landing-footer py-4">
        <div class="container text-center">
            <p class="mb-2">
                <strong>Sistema GED</strong> &copy; <?= date('Y') ?> - Gestión Escuelas Deportivas
            </p>
            <p class="text-muted small mb-0">
                Plataforma tecnológica para la administración deportiva
            </p>
        </div>
    </div>
</div>

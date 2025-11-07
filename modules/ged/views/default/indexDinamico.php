<?php
use yii\helpers\Html;
/** @var yii\web\View $this */

$this->title = 'Escuela Polideportiva San Agustín';
?>
<div class="site-index">
    <section id="Carrusel-Promocional">
        <div class="row">
            <div id="redes-sociales" class="col-md-2">
                <!--Panel para las redes sociales -->
                <div  class="panel-redes-sociales">
                    <div id="icons-redes-sociales"class="social-links">
                        <h1 >Redes </br>Sociales</h1>
                        <a href="#" class="twitter"><i class="bi bi-twitter"></i></a></br>
                        <a href="#" class="facebook"><i class="bi bi-facebook"></i></a></br>
                        <a href="@lacasadelastogasccs" class="instagram"><i class="bi bi-instagram"></i></a></br>
                        <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a></br>
                    </div>
                </div> 
            </div>
            <div id="carrusel-principal" class="col-md-10">
                <div id="carouselExampleIndicators" class="carousel slide carousel-fade" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1" aria-label="Slide 2"></button>
                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2" aria-label="Slide 3"></button>
                    </div>
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img src=<?='"'.Yii::getAlias('@web').'/img/escuela/voleibol/img/voleibol1.jpg'.'"'?> class="d-block w-100" alt="...">
                            <div class="carousel-caption d-none d-md-block">
                                <h5>Escuela de Voleibol</h5>
                                <p>Entrenamiento, tecnica, agilidad en este disciplina</p>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <img src=<?='"'.Yii::getAlias('@web').'/img/escuela/basketbol/img/basketbol.jpg'.'"'?> class="d-block w-100" alt="...">
                            <div class="carousel-caption d-none d-md-block">
                                <h5>Escuela de Basketbol</h5>
                                <p>Entrenamiento, tecnica, agilidad en este disciplina</p>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <img src=<?='"'.Yii::getAlias('@web').'/img/escuela/futbol/img/futbol.jpeg'.'"'?> class="d-block w-100" alt="...">
                            <div class="carousel-caption d-none d-md-block">
                                <h5>Escuela de Futbol</h5>
                                <p>Entrenamiento, tecnica, agilidad en este disciplina</p>
                            </div>
                        </div>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Siguiente</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    </section>
    <section id="disciplinas">
        <div class="row">
            <div class="card col-md-4">
                <img src=<?='"'.Yii::getAlias('@web').'/img/escuela/voleibol/img/voleibol1.jpg'.'"'?> class="card-img-top" alt="voleibol">
                <div class="card-body">
                    <h5 class="card-title">Voleibol</h5>
                    <p class="card-text">Enseñanda de los fundamentos básicos del Voleibol, posicionamiento en cancha y tactica de juego </p>
                    <p>
                        <?= Html::a('Regitrar Atleta', ['/atletas/ateltas/save'], ['class' => 'btn btn-success']) ?>
                    </p>
                </div>
            </div> 
            <div class="card col-md-4">
                <img src=<?='"'.Yii::getAlias('@web').'/img/escuela/basketbol/img/basketbol.jpg'.'"'?> class="card-img-top" alt="...">
                <div class="card-body">
                    <h5 class="card-title">Basketbal</h5>
                    <p class="card-text">Enseñanda de los fundamentos básicos del Voleibol, posicionamiento en cancha y tactica de juego </p>
                    <p>
                        <?= Html::a('Regitrar Atleta', ['create'], ['class' => 'btn btn-success']) ?>
                    </p>
                </div>
            </div>
            <div class="card col-md-4">
                <img src=<?='"'.Yii::getAlias('@web').'/img/escuela/futbol/img/futbol.jpeg'.'"'?> class="card-img-top" alt="..." >
                <div class="card-body">
                    <h5 class="card-title">Futbol</h5>
                    <p class="card-text">Enseñanda de los fundamentos básicos del Voleibol, posicionamiento en cancha y tactica de juego </p>
                    <p>
                        <?= Html::a('Regitrar Atleta', ['create'], ['class' => 'btn btn-success']) ?>
                    </p>
                </div>
            </div> 
        </div>
    </section>
    <section id="equipo">

    </section>
    <section id="Acerca-de">
        
    </section>
    <section id="Ubicacion">
        
    </section>
</div>
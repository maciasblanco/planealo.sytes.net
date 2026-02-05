<?php
use yii\helpers\Html;
/** @var yii\web\View $this */

$this->title = 'Escuela Polideportiva San Agustín';
?>
<div class="site-index margen-main">
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
                <div  class="registro-escuela">
                    <h1>Registra tu escuela deportiva</h1>
                    <div class="card col-md-12">
                        <img src=<?='"'.Yii::getAlias('@web').'/img/escuela/voleibol/img/voleibol1.jpg'.'"'?> class="card-img-top" alt="voleibol">
                        <div class="card-body">
                            <h5 class="card-title">Voleibol</h5>
                            <p class="card-text">Enseñanda de los fundamentos básicos del Voleibol, posicionamiento en cancha y tactica de juego </p>
                            <p>
                                <?= Html::a('Regitrar Atleta', ['/atletas/atletas-registro/create'], ['class' => 'btn btn-success']) ?>
                            </p>
                        </div>
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
                        <?= Html::a('Regitrar Atleta', ['/atletas/atletas-registro/create'], ['class' => 'btn btn-success']) ?>
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
    <section id="directiva" class="team section-bg">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
            <h2>Equipo</h2>
            <p>Un Equipo de profesional para la instrucción Deportiva y Cultural</p>

            <div class="row">

            <div class="col-lg-3 col-md-6 d-flex align-items-stretch">
                <div class="member" data-aos="fade-up" data-aos-delay="100">
                <div class="member-img">
                    <img src=<?='"'.Yii::getAlias('@web').'/img/avatares/avatar5.png'.'"'?> class="img-fluid" alt="">
                    <div class="social">
                    <a href=""><i class="bi bi-twitter"></i></a>
                    <a href=""><i class="bi bi-facebook"></i></a>
                    <a href=""><i class="bi bi-instagram"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="member-info">
                    <h4>Gustavo Lira</h4>
                    <span>Presidente de la EDC San Agustín</span>
                </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 d-flex align-items-stretch">
                <div class="member" data-aos="fade-up" data-aos-delay="200">
                <div class="member-img">
                    <img src=<?='"'.Yii::getAlias('@web').'/img/avatares/avatar04.png'.'"'?> class="img-fluid" alt="">
                    <div class="social">
                    <a href=""><i class="bi bi-twitter"></i></a>
                    <a href=""><i class="bi bi-facebook"></i></a>
                    <a href=""><i class="bi bi-instagram"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="member-info">
                    <h4>Barolin</h4>
                    <span>Junta Directiva</span>
                </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 d-flex align-items-stretch">
                <div class="member" data-aos="fade-up" data-aos-delay="300">
                <div class="member-img">
                    <img src=<?='"'.Yii::getAlias('@web').'/img/avatares/noFoto215.png'.'"'?> class="img-fluid" alt="">
                    <div class="social">
                    <a href=""><i class="bi bi-twitter"></i></a>
                    <a href=""><i class="bi bi-facebook"></i></a>
                    <a href=""><i class="bi bi-instagram"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="member-info">
                    <h4>Jimmy Fariñas</h4>
                    <span>Entrandor</span>
                </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 d-flex align-items-stretch">
                <div class="member" data-aos="fade-up" data-aos-delay="400">
                <div class="member-img">
                    <img src=<?='"'.Yii::getAlias('@web').'/img/avatares/noFoto215.png'.'"'?> class="img-fluid" alt="">
                    <div class="social">
                    <a href=""><i class="bi bi-twitter"></i></a>
                    <a href=""><i class="bi bi-facebook"></i></a>
                    <a href=""><i class="bi bi-instagram"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="member-info">
                    <h4>Secretaria</h4>
                    <span>Secretaria</span>
                </div>
                </div>
            </div>

            </div>

        </div>
    </section>
    <!-- ======= About Us Section ======= -->
    <section id="Acerca_de" class="about">
      <div class="container">

        <div class="section-title" data-aos="fade-up">
          <h2>Acerca de Nosotros</h2>
        </div>

        <div class="row content">
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="150">
          <h3>MISIÓN</h3>
            <p>
              Ofrecer espacios deportivos y recreativos en los cuales niños, jóvenes y adultos, desde determinadas normas y, con o sin competición, comprueben sus habilidades, destrezas y fuerzas físicas y, además, se recreen de forma sana.
            </p>
          </div>
          <div class="col-lg-6 pt-4 pt-lg-0" data-aos="fade-up" data-aos-delay="300">
            <h3>VISIÓN</h3>
            <p>
            Llegar a la totalidad de nuestro niños, niñas, adolecentes,  padres de familia y otros adultos de la comunidad de San Agustín, con una propuesta amplia de actividades deportivas, recreativas y culturales, de forma que se fortalezca en ellos la necesidad de estas prácticas como medio eficaz para canalizar sus energías, disminuir sus niveles de presión, favorecer su estado de salud, concientizar la importancia del trabajo en equipo para el logro de metas y objetivos y crear vínculos afectivos y duraderos.
            </p>
            <!--<a href="#" class="btn-learn-more">Learn More</a>-->
          </div>
        </div>

      </div>
    </section><!-- End About Us Section -->
    <section id="Ubicacion">
        
    </section>

<?php
    $this->registerJs(<<<JAVASCRIPT
            $( document ).ready(function(){
            $('.carousel').carousel({
            interval: 2000
            })
        });
    JAVASCRIPT
    );
?>
</div>

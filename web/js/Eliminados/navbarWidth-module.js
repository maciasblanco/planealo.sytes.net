/**
 * Módulo de corrección de ancho para Navbar GED
 * Versión: 1.0.0
 * Descripción: Aplica correcciones de ancho completo al navbar y body
 */

document.addEventListener('DOMContentLoaded', function() {
   // Aplicar correcciones inmediatamente
   setTimeout(() => {
       // Forzar ancho completo del navbar
       const navbar = document.querySelector('.navbar-contextual');
       if (navbar) {
           navbar.style.width = '100vw';
           navbar.style.maxWidth = '100vw';
           navbar.style.minWidth = '100vw';
           navbar.style.left = '0';
           navbar.style.right = '0';
       }
       
       // Forzar ancho completo del body
       document.body.style.width = '100vw';
       document.body.style.maxWidth = '100vw';
       document.body.style.overflowX = 'hidden';
       
       console.log('✅ Correcciones de ancho aplicadas inmediatamente');
   }, 50);
});
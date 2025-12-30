// tienda-module.js - Módulo específico para funcionalidades de Tienda
// Versión: 3.0.0 - Sistema completo con carrito, productos y marketplace
// Fecha: 16/01/2024

class TiendaModule {
    constructor() {
        this.initialized = false;
        this.carrito = [];
        this.productos = {};
        this.escuelaId = null;
        this.usuarioId = null;
        this.usuarioRoles = [];
        
        // URLs base para API
        this.apiEndpoints = {
            cargarProductos: '/tienda/api/productos',
            agregarCarrito: '/tienda/api/agregar-carrito',
            obtenerCarrito: '/tienda/api/obtener-carrito',
            marketplace: '/tienda/marketplace',
            registroVendedor: '/tienda/registro-vendedor',
            checkout: '/tienda/checkout'
        };
        
        console.log('🛍️ TiendaModule creado');
    }
    
    // ✅ INICIALIZACIÓN PRINCIPAL
    init() {
        if (this.initialized) {
            console.log('🛍️ TiendaModule ya inicializado');
            return this;
        }
        
        console.log('🛍️ Módulo Tienda inicializando...');
        
        try {
            // Verificar que estamos en una página de tienda
            if (!this.shouldInitialize()) {
                console.log('ℹ️ TiendaModule: No se requiere inicialización en esta página');
                return null;
            }
            
            // Obtener contexto
            this.obtenerContexto();
            
            // Configurar eventos
            this.bindEvents();
            
            // Cargar datos iniciales
            this.cargarDatosIniciales();
            
            // Verificar acceso
            this.checkTiendaAccess();
            
            this.initialized = true;
            console.log('✅ TiendaModule completamente inicializado');
            
            // Disparar evento de inicialización
            this.dispatchEvent('tienda:ready');
            
            return this;
        } catch (error) {
            console.error('❌ Error al inicializar TiendaModule:', error);
            this.showError('Error al inicializar módulo de tienda');
            return null;
        }
    }
    
    // ✅ VERIFICAR SI DEBE INICIALIZARSE
    shouldInitialize() {
        // Verificar elementos específicos de tienda
        const tiendaElements = document.querySelectorAll(
            '[class*="tienda"], [id*="tienda"], [href*="tienda"], ' +
            '[class*="marketplace"], [id*="marketplace"], [href*="marketplace"], ' +
            '.producto-card, .btn-agregar-carrito, #contador-carrito, ' +
            '[data-module="tienda"], [data-tienda="true"]'
        );
        
        const isTiendaPage = window.location.pathname.includes('tienda') || 
                            window.location.pathname.includes('marketplace') ||
                            window.location.pathname.includes('productos');
        
        const hasTiendaElements = tiendaElements.length > 0;
        
        console.log(`🔍 TiendaModule condiciones: 
            isTiendaPage: ${isTiendaPage}, 
            hasTiendaElements: ${hasTiendaElements}, 
            total elements: ${tiendaElements.length}`);
        
        return isTiendaPage || hasTiendaElements;
    }
    
    // ✅ OBTENER CONTEXTO (escuela y usuario)
    obtenerContexto() {
        try {
            // Obtener escuelaId desde elementos del DOM
            const escuelaInput = document.getElementById('current-school-id') || 
                                document.getElementById('escuela-id') ||
                                document.querySelector('[name="escuela_id"]') ||
                                document.querySelector('[data-school-id]');
            
            if (escuelaInput) {
                this.escuelaId = escuelaInput.value || escuelaInput.getAttribute('data-school-id');
                if (this.escuelaId) {
                    console.log(`🏫 Contexto: Escuela ID ${this.escuelaId}`);
                }
            }
            
            // Obtener usuarioId
            const usuarioMeta = document.querySelector('meta[name="user-id"]');
            if (usuarioMeta) {
                this.usuarioId = usuarioMeta.getAttribute('content');
                console.log(`👤 Contexto: Usuario ID ${this.usuarioId}`);
            }
            
            // Obtener roles del usuario
            const rolesMeta = document.querySelector('meta[name="user-roles"]');
            if (rolesMeta) {
                this.usuarioRoles = rolesMeta.getAttribute('content').split(',').map(r => r.trim());
                console.log(`🎭 Contexto: Roles ${this.usuarioRoles.join(', ')}`);
            }
            
            // Si no hay contexto, intentar obtener del localStorage/sessionStorage
            if (!this.escuelaId) {
                this.escuelaId = localStorage.getItem('ged-escuela-id') || 
                               sessionStorage.getItem('ged-escuela-id');
            }
            
        } catch (error) {
            console.warn('⚠️ Error obteniendo contexto:', error);
        }
    }
    
    // ✅ CONFIGURAR EVENTOS
    bindEvents() {
        try {
            console.log('🔗 Configurando eventos de Tienda...');
            
            // Evento para el botón de marketplace
            document.querySelectorAll('#btn-marketplace, .btn-marketplace, [href*="marketplace"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    this.handleMarketplaceClick(e);
                });
            });
            
            // Eventos para enlaces de registro vendedor
            document.querySelectorAll('a[href*="registro-vendedor"], .btn-registro-vendedor').forEach(link => {
                link.addEventListener('click', (e) => {
                    this.handleVendedorRegistroClick(e);
                });
            });
            
            // Eventos para botones de agregar al carrito
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('btn-agregar-carrito') || 
                    e.target.closest('.btn-agregar-carrito')) {
                    const button = e.target.classList.contains('btn-agregar-carrito') ? 
                                  e.target : e.target.closest('.btn-agregar-carrito');
                    this.handleAgregarCarrito(button);
                }
            });
            
            // Eventos para productos destacados
            document.querySelectorAll('.producto-destacado, .featured-product, [data-product-id]').forEach(producto => {
                producto.addEventListener('click', (e) => {
                    if (!e.target.classList.contains('btn-agregar-carrito')) {
                        const productId = producto.dataset.productId || 
                                        producto.closest('[data-product-id]')?.dataset.productId;
                        if (productId) {
                            this.handleProductoClick(productId);
                        }
                    }
                });
            });
            
            // Vincular botones de acción del carrito
            const verCarritoBtn = document.getElementById('btn-ver-carrito');
            if (verCarritoBtn) {
                verCarritoBtn.addEventListener('click', () => this.mostrarCarrito());
            }
            
            const vaciarCarritoBtn = document.getElementById('btn-vaciar-carrito');
            if (vaciarCarritoBtn) {
                vaciarCarritoBtn.addEventListener('click', () => this.vaciarCarrito());
            }
            
            const procederPagoBtn = document.getElementById('btn-proceder-pago');
            if (procederPagoBtn) {
                procederPagoBtn.addEventListener('click', () => this.procederPago());
            }
            
            console.log('✅ Eventos de Tienda configurados');
        } catch (error) {
            console.error('❌ Error en bindEvents:', error);
        }
    }
    
    // ✅ CARGAR DATOS INICIALES
    async cargarDatosIniciales() {
        try {
            console.log('📦 Cargando datos iniciales de tienda...');
            
            // Cargar carrito desde localStorage
            this.cargarCarritoDesdeStorage();
            
            // Cargar productos si hay contenedores
            if (this.hayContenedoresProductos()) {
                await this.cargarProductos();
            } else {
                // Si no hay contenedores específicos, verificar si hay productos en página
                this.cargarProductosSiHay();
            }
            
            // Actualizar contador de carrito
            this.actualizarContadorCarrito();
            
            // Inicializar tooltips si Bootstrap está disponible
            this.initTooltips();
            
            console.log('✅ Datos iniciales cargados');
        } catch (error) {
            console.error('❌ Error cargando datos iniciales:', error);
        }
    }
    
    // ✅ VERIFICAR CONTENEDORES DE PRODUCTOS
    hayContenedoresProductos() {
        const categorias = ['vestimenta', 'alimentacion', 'implementos-deportivos', 'suplementos', 'productos'];
        return categorias.some(categoria => document.getElementById(`productos-${categoria}`));
    }
    
    // ✅ CARGAR PRODUCTOS SI HAY EN PÁGINA
    cargarProductosSiHay() {
        const productoElements = document.querySelectorAll('[data-product-id], .producto-card, [data-tienda-product]');
        if (productoElements.length > 0) {
            console.log(`🔄 Cargando ${productoElements.length} productos de la página...`);
            this.inicializarProductosDePagina();
        }
    }
    
    // ✅ INICIALIZAR PRODUCTOS DE LA PÁGINA
    inicializarProductosDePagina() {
        const productos = [];
        document.querySelectorAll('[data-product-id]').forEach(element => {
            const id = element.dataset.productId;
            const nombre = element.dataset.productName || element.querySelector('.producto-nombre')?.textContent || 'Producto';
            const precio = parseFloat(element.dataset.productPrice || element.querySelector('.producto-precio')?.textContent?.replace('$', '') || 0);
            const categoria = element.dataset.productCategory || 'general';
            
            if (id && nombre && !isNaN(precio)) {
                productos.push({
                    id: parseInt(id),
                    nombre,
                    precio,
                    categoria,
                    stock: parseInt(element.dataset.productStock || 10),
                    vendidos: parseInt(element.dataset.productSold || 0)
                });
            }
        });
        
        if (productos.length > 0) {
            this.productos = { pagina: productos };
            console.log(`✅ ${productos.length} productos inicializados desde la página`);
        }
    }
    
    // ✅ CARGAR PRODUCTOS
    async cargarProductos() {
        try {
            console.log('🔄 Cargando productos...');
            
            // Datos de ejemplo (reemplazar con llamada API real)
            this.productos = {
                vestimenta: [
                    { id: 1, nombre: 'Camiseta Deportiva', precio: 25, vendidos: 150, categoria: 'vestimenta', stock: 50, imagen: 'tshirt.png' },
                    { id: 2, nombre: 'Pantalón Deportivo', precio: 35, vendidos: 120, categoria: 'vestimenta', stock: 30, imagen: 'pants.png' },
                    { id: 3, nombre: 'Sudadera con Capucha', precio: 45, vendidos: 95, categoria: 'vestimenta', stock: 25, imagen: 'hoodie.png' }
                ],
                alimentacion: [
                    { id: 4, nombre: 'Barra Energética', precio: 3, vendidos: 200, categoria: 'alimentacion', stock: 100, imagen: 'energy-bar.png' },
                    { id: 5, nombre: 'Bebida Isotónica', precio: 2, vendidos: 180, categoria: 'alimentacion', stock: 150, imagen: 'drink.png' },
                    { id: 6, nombre: 'Snack Proteico', precio: 4, vendidos: 150, categoria: 'alimentacion', stock: 80, imagen: 'snack.png' }
                ],
                'implementos-deportivos': [
                    { id: 7, nombre: 'Balón de Fútbol', precio: 20, vendidos: 80, categoria: 'implementos-deportivos', stock: 40, imagen: 'ball.png' },
                    { id: 8, nombre: 'Cuerda para Saltar', precio: 10, vendidos: 75, categoria: 'implementos-deportivos', stock: 60, imagen: 'rope.png' },
                    { id: 9, nombre: 'Banda Elástica', precio: 15, vendidos: 90, categoria: 'implementos-deportivos', stock: 35, imagen: 'band.png' }
                ],
                suplementos: [
                    { id: 10, nombre: 'Proteína en Polvo', precio: 50, vendidos: 110, categoria: 'suplementos', stock: 20, imagen: 'protein.png' },
                    { id: 11, nombre: 'Multivitamínico', precio: 15, vendidos: 85, categoria: 'suplementos', stock: 45, imagen: 'vitamin.png' },
                    { id: 12, nombre: 'Creatina', precio: 30, vendidos: 70, categoria: 'suplementos', stock: 30, imagen: 'creatine.png' }
                ]
            };
            
            // Renderizar productos en la interfaz
            this.renderizarProductos();
            
            // Actualizar total vendidos
            this.actualizarTotalVendidos();
            
            console.log('✅ Productos cargados:', Object.keys(this.productos).length + ' categorías');
            
            // Disparar evento de productos cargados
            this.dispatchEvent('tienda:productos:cargados', { productos: this.productos });
            
        } catch (error) {
            console.error('❌ Error cargando productos:', error);
            // Mostrar productos de ejemplo como fallback
            this.cargarProductosEjemplo();
        }
    }
    
    // ✅ CARGAR PRODUCTOS DE EJEMPLO (fallback)
    cargarProductosEjemplo() {
        console.log('🔄 Cargando productos de ejemplo...');
        
        this.productos = {
            ejemplo: [
                { id: 100, nombre: 'Producto de Ejemplo', precio: 10, vendidos: 0, categoria: 'ejemplo', stock: 10 }
            ]
        };
        
        this.renderizarProductos();
    }
    
    // ✅ RENDERIZAR PRODUCTOS EN LA INTERFAZ
    renderizarProductos() {
        try {
            for (const categoria in this.productos) {
                const contenedor = document.getElementById(`productos-${categoria}`);
                if (!contenedor) continue;
                
                contenedor.innerHTML = '';
                this.productos[categoria].forEach(producto => {
                    const productoHTML = this.crearHTMLProducto(producto);
                    contenedor.insertAdjacentHTML('beforeend', productoHTML);
                });
            }
            
            console.log('✅ Productos renderizados en la interfaz');
        } catch (error) {
            console.error('❌ Error renderizando productos:', error);
        }
    }
    
    // ✅ CREAR HTML DE PRODUCTO
    crearHTMLProducto(producto) {
        const iconosPorCategoria = {
            'vestimenta': 'fa-tshirt',
            'alimentacion': 'fa-apple-alt',
            'implementos-deportivos': 'fa-basketball-ball',
            'suplementos': 'fa-capsules',
            'default': 'fa-box'
        };
        
        const icono = iconosPorCategoria[producto.categoria] || iconosPorCategoria.default;
        
        return `
            <div class="producto-card" data-product-id="${producto.id}" data-categoria="${producto.categoria}">
                <div class="producto-imagen-placeholder">
                    <i class="fas ${icono} fa-3x text-muted"></i>
                </div>
                <div class="producto-info">
                    <h3 class="producto-nombre">${producto.nombre}</h3>
                    <div class="producto-precio">$${producto.precio.toFixed(2)}</div>
                    <div class="producto-detalles">
                        <span class="vendidos"><i class="fas fa-shopping-bag"></i> ${producto.vendidos} vendidos</span>
                        <span class="stock"><i class="fas fa-box"></i> ${producto.stock} en stock</span>
                    </div>
                    <button class="btn btn-primary btn-agregar-carrito btn-sm" 
                            data-id="${producto.id}"
                            data-nombre="${producto.nombre}"
                            data-precio="${producto.precio}"
                            data-categoria="${producto.categoria}">
                        <i class="fas fa-cart-plus"></i> Agregar
                    </button>
                </div>
            </div>
        `;
    }
    
    // ✅ ACTUALIZAR TOTAL VENDIDOS
    actualizarTotalVendidos() {
        try {
            let total = 0;
            for (const categoria in this.productos) {
                this.productos[categoria].forEach(producto => {
                    total += producto.vendidos;
                });
            }
            
            const totalElement = document.getElementById('total-productos-vendidos');
            if (totalElement) {
                totalElement.textContent = total.toLocaleString();
            }
            
            console.log(`📊 Total vendidos actualizado: ${total}`);
        } catch (error) {
            console.error('Error actualizando total vendidos:', error);
        }
    }
    
    // ✅ MANEJAR CLICK EN MARKETPLACE
    handleMarketplaceClick(e) {
        console.log('🎯 Navegando al marketplace...');
        
        this.trackEvent('marketplace_access', 'tienda_module');
        
        // Si es un enlace, dejar que navegue normalmente
        if (e.target.tagName === 'A') {
            return;
        }
        
        // Si es un botón sin href, redirigir programáticamente
        e.preventDefault();
        setTimeout(() => {
            window.location.href = this.apiEndpoints.marketplace;
        }, 300);
    }
    
    // ✅ MANEJAR REGISTRO DE VENDEDOR
    handleVendedorRegistroClick(e) {
        console.log('🎯 Navegando al registro de vendedor...');
        
        this.trackEvent('vendedor_registro_click', 'tienda_module');
        
        // Verificar si el usuario está logueado
        if (!this.usuarioId) {
            e.preventDefault();
            this.mostrarModalRegistro();
        }
    }
    
    // ✅ MANEJAR AGREGAR AL CARRITO
    handleAgregarCarrito(button) {
        try {
            const id = parseInt(button.dataset.id);
            const nombre = button.dataset.nombre;
            const precio = parseFloat(button.dataset.precio);
            const categoria = button.dataset.categoria || 'general';
            
            console.log(`🛒 Agregando al carrito: ${nombre} ($${precio})`);
            
            // Agregar al carrito
            const agregado = this.agregarAlCarrito({
                id,
                nombre,
                precio,
                categoria,
                cantidad: 1
            });
            
            if (agregado) {
                // Feedback visual
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Agregado';
                button.disabled = true;
                button.classList.remove('btn-primary');
                button.classList.add('btn-success');
                
                // Restaurar después de 2 segundos
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-primary');
                }, 2000);
                
                // Mostrar notificación
                this.mostrarNotificacion(`${nombre} agregado al carrito`, 'success');
                
                // Track event
                this.trackEvent('producto_agregado_carrito', nombre);
            }
        } catch (error) {
            console.error('❌ Error al agregar al carrito:', error);
            this.mostrarNotificacion('Error al agregar producto', 'error');
        }
    }
    
    // ✅ MANEJAR CLICK EN PRODUCTO
    handleProductoClick(productoId) {
        const producto = this.buscarProductoPorId(productoId);
        
        if (producto) {
            console.log(`🔍 Ver detalles producto: ${producto.nombre}`);
            this.mostrarDetalleProducto(producto);
        } else {
            console.warn(`Producto con ID ${productoId} no encontrado`);
        }
    }
    
    // ✅ BUSCAR PRODUCTO POR ID
    buscarProductoPorId(id) {
        for (const categoria in this.productos) {
            const producto = this.productos[categoria].find(p => p.id === id);
            if (producto) return producto;
        }
        return null;
    }
    
    // ✅ AGREGAR AL CARRITO
    agregarAlCarrito(producto) {
        try {
            // Verificar si ya existe en el carrito
            const index = this.carrito.findIndex(item => item.id === producto.id);
            
            if (index > -1) {
                // Actualizar cantidad
                this.carrito[index].cantidad += producto.cantidad || 1;
            } else {
                // Agregar nuevo
                this.carrito.push({
                    ...producto,
                    fecha: new Date().toISOString()
                });
            }
            
            // Guardar en localStorage
            this.guardarCarritoEnStorage();
            
            // Actualizar interfaz
            this.actualizarContadorCarrito();
            
            console.log(`✅ Producto agregado al carrito: ${producto.nombre}`);
            
            // Disparar evento
            this.dispatchEvent('tienda:carrito:actualizado', { carrito: this.carrito });
            
            return true;
        } catch (error) {
            console.error('❌ Error agregando al carrito:', error);
            return false;
        }
    }
    
    // ✅ CARGAR CARRITO DESDE STORAGE
    cargarCarritoDesdeStorage() {
        try {
            const carritoData = localStorage.getItem('ged-carrito') || 
                              sessionStorage.getItem('ged-carrito');
            
            if (carritoData) {
                this.carrito = JSON.parse(carritoData);
                console.log(`🛒 Carrito cargado: ${this.carrito.length} productos`);
            } else {
                this.carrito = [];
            }
        } catch (error) {
            console.error('❌ Error cargando carrito:', error);
            this.carrito = [];
        }
    }
    
    // ✅ GUARDAR CARRITO EN STORAGE
    guardarCarritoEnStorage() {
        try {
            localStorage.setItem('ged-carrito', JSON.stringify(this.carrito));
            // También en sessionStorage como backup
            sessionStorage.setItem('ged-carrito', JSON.stringify(this.carrito));
        } catch (error) {
            console.error('❌ Error guardando carrito:', error);
        }
    }
    
    // ✅ ACTUALIZAR CONTADOR DE CARRITO
    actualizarContadorCarrito() {
        try {
            const contadores = document.querySelectorAll('#contador-carrito, .contador-carrito, .cart-count, [data-carrito-count]');
            
            contadores.forEach(contador => {
                const totalItems = this.carrito.reduce((sum, item) => sum + (item.cantidad || 1), 0);
                contador.textContent = totalItems;
                contador.style.display = totalItems > 0 ? 'inline-block' : 'none';
                
                // Agregar animación si hay cambios
                if (totalItems > 0) {
                    contador.classList.add('animate-pulse');
                    setTimeout(() => contador.classList.remove('animate-pulse'), 500);
                }
            });
        } catch (error) {
            console.error('❌ Error actualizando contador:', error);
        }
    }
    
    // ✅ MOSTRAR CARRITO
    mostrarCarrito() {
        console.log('🛒 Mostrando carrito...');
        
        if (this.carrito.length === 0) {
            this.mostrarNotificacion('El carrito está vacío', 'info');
            return;
        }
        
        // Crear modal del carrito
        this.crearModalCarrito();
    }
    
    // ✅ CREAR MODAL DEL CARRITO
    crearModalCarrito() {
        const modalId = 'modal-carrito-tienda';
        let modal = document.getElementById(modalId);
        
        if (modal) {
            modal.remove(); // Remover si existe
        }
        
        modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('aria-hidden', 'true');
        
        let total = 0;
        let itemsHTML = '';
        
        this.carrito.forEach(item => {
            const subtotal = item.precio * (item.cantidad || 1);
            total += subtotal;
            
            itemsHTML += `
                <div class="carrito-item d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                    <div class="flex-grow-1">
                        <strong class="d-block">${item.nombre}</strong>
                        <small class="text-muted">${item.categoria || 'General'}</small>
                        <div class="d-flex align-items-center mt-1">
                            <button class="btn btn-sm btn-outline-secondary btn-cantidad" 
                                    onclick="tiendaModule.actualizarCantidad(${item.id}, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="mx-2">${item.cantidad || 1}</span>
                            <button class="btn btn-sm btn-outline-secondary btn-cantidad" 
                                    onclick="tiendaModule.actualizarCantidad(${item.id}, 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold">$${subtotal.toFixed(2)}</div>
                        <small class="text-muted">$${item.precio.toFixed(2)} c/u</small>
                        <button class="btn btn-sm btn-outline-danger ms-2" 
                                onclick="tiendaModule.eliminarDelCarrito(${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-shopping-cart me-2"></i>Mi Carrito
                            <span class="badge bg-light text-dark ms-2">${this.carrito.length} productos</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                        ${this.carrito.length === 0 ? 
                            '<div class="text-center py-5 text-muted"><i class="fas fa-shopping-cart fa-3x mb-3"></i><p>El carrito está vacío</p></div>' : 
                            itemsHTML}
                        
                        ${this.carrito.length > 0 ? `
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Subtotal:</strong><br>
                                        <small class="text-muted">Productos en el carrito</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="fs-5 fw-bold text-primary">$${total.toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left me-2"></i>Seguir comprando
                        </button>
                        ${this.carrito.length > 0 ? `
                            <button type="button" class="btn btn-danger" onclick="tiendaModule.vaciarCarrito()">
                                <i class="fas fa-trash me-2"></i>Vaciar carrito
                            </button>
                            <button type="button" class="btn btn-primary" onclick="tiendaModule.procederPago()">
                                <i class="fas fa-credit-card me-2"></i>Pagar $${total.toFixed(2)}
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Mostrar modal con Bootstrap
        if (typeof bootstrap !== 'undefined') {
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
            
            // Evento cuando se cierra el modal
            modal.addEventListener('hidden.bs.modal', () => {
                modal.remove();
            });
        } else {
            modal.style.display = 'block';
            modal.style.position = 'fixed';
            modal.style.zIndex = '9999';
            modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
        }
    }
    
    // ✅ ACTUALIZAR CANTIDAD DE PRODUCTO
    actualizarCantidad(productoId, cambio) {
        const index = this.carrito.findIndex(item => item.id === productoId);
        
        if (index > -1) {
            const nuevaCantidad = (this.carrito[index].cantidad || 1) + cambio;
            
            if (nuevaCantidad < 1) {
                this.eliminarDelCarrito(productoId);
            } else {
                this.carrito[index].cantidad = nuevaCantidad;
                this.guardarCarritoEnStorage();
                this.actualizarContadorCarrito();
                
                // Actualizar modal si está abierto
                setTimeout(() => {
                    const modal = document.getElementById('modal-carrito-tienda');
                    if (modal && modal.style.display !== 'none') {
                        this.crearModalCarrito();
                    }
                }, 100);
            }
        }
    }
    
    // ✅ ELIMINAR DEL CARRITO
    eliminarDelCarrito(productoId) {
        const index = this.carrito.findIndex(item => item.id === productoId);
        
        if (index > -1) {
            const producto = this.carrito[index];
            this.carrito.splice(index, 1);
            this.guardarCarritoEnStorage();
            this.actualizarContadorCarrito();
            
            this.mostrarNotificacion(`${producto.nombre} eliminado del carrito`, 'info');
            console.log(`🗑️ Producto eliminado del carrito: ${producto.nombre}`);
            
            // Actualizar modal si está abierto
            setTimeout(() => {
                const modal = document.getElementById('modal-carrito-tienda');
                if (modal && modal.style.display !== 'none') {
                    if (this.carrito.length === 0) {
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        if (modalInstance) modalInstance.hide();
                    } else {
                        this.crearModalCarrito();
                    }
                }
            }, 100);
        }
    }
    
    // ✅ VACIAR CARRITO
    vaciarCarrito() {
        if (this.carrito.length === 0) {
            this.mostrarNotificacion('El carrito ya está vacío', 'info');
            return;
        }
        
        if (confirm('¿Estás seguro de que quieres vaciar el carrito? Se eliminarán todos los productos.')) {
            this.carrito = [];
            this.guardarCarritoEnStorage();
            this.actualizarContadorCarrito();
            
            this.mostrarNotificacion('Carrito vaciado', 'info');
            console.log('🧹 Carrito vaciado');
            
            // Cerrar modal si está abierto
            const modal = document.getElementById('modal-carrito-tienda');
            if (modal && typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) modalInstance.hide();
            }
        }
    }
    
    // ✅ PROCEDER AL PAGO
    procederPago() {
        console.log('💳 Procediendo al pago...');
        
        // Verificar que haya productos
        if (this.carrito.length === 0) {
            this.mostrarNotificacion('El carrito está vacío', 'error');
            return;
        }
        
        // Verificar contexto (escuela y usuario)
        if (!this.escuelaId) {
            this.mostrarNotificacion('Selecciona una escuela primero', 'warning');
            return;
        }
        
        if (!this.usuarioId) {
            this.mostrarNotificacion('Debes iniciar sesión para realizar una compra', 'warning');
            setTimeout(() => {
                window.location.href = '/site/login?return=' + encodeURIComponent(window.location.pathname);
            }, 1500);
            return;
        }
        
        // Calcular total
        const total = this.carrito.reduce((sum, item) => sum + (item.precio * (item.cantidad || 1)), 0);
        
        // Mostrar confirmación
        if (confirm(`¿Confirmar compra por $${total.toFixed(2)}?`)) {
            // Aquí iría la lógica de pago real
            this.mostrarNotificacion('Procesando tu pedido...', 'info');
            
            // Simular procesamiento de pago
            setTimeout(() => {
                console.log('📤 Redirigiendo a página de pago...');
                
                // Crear datos del pedido
                const pedidoData = {
                    escuelaId: this.escuelaId,
                    usuarioId: this.usuarioId,
                    productos: this.carrito,
                    total: total,
                    fecha: new Date().toISOString()
                };
                
                // Guardar pedido en localStorage temporalmente
                localStorage.setItem('pedido-temporal', JSON.stringify(pedidoData));
                
                // Redirigir a página de pago (simulado)
                // window.location.href = `${this.apiEndpoints.checkout}?escuela=${this.escuelaId}&total=${total}`;
                
                this.mostrarNotificacion('Funcionalidad de pago en desarrollo', 'info');
            }, 1000);
        }
    }
    
    // ✅ MOSTRAR DETALLE DE PRODUCTO
    mostrarDetalleProducto(producto) {
        const modalId = 'modal-detalle-producto';
        let modal = document.getElementById(modalId);
        
        if (modal) {
            modal.remove();
        }
        
        modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        
        const iconosPorCategoria = {
            'vestimenta': 'fa-tshirt',
            'alimentacion': 'fa-apple-alt',
            'implementos-deportivos': 'fa-basketball-ball',
            'suplementos': 'fa-capsules',
            'default': 'fa-box'
        };
        
        const icono = iconosPorCategoria[producto.categoria] || iconosPorCategoria.default;
        
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas ${icono} me-2"></i>Detalle del Producto
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <div class="producto-imagen-grande mb-3">
                                <i class="fas ${icono} fa-5x text-primary"></i>
                            </div>
                            <h4>${producto.nombre}</h4>
                            <div class="fs-3 text-primary mb-2">$${producto.precio.toFixed(2)}</div>
                            <span class="badge bg-secondary">${producto.categoria}</span>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="card text-center border-primary">
                                    <div class="card-body py-3">
                                        <div class="text-muted small">Vendidos</div>
                                        <div class="fs-4 text-primary">${producto.vendidos}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card text-center border-success">
                                    <div class="card-body py-3">
                                        <div class="text-muted small">Stock disponible</div>
                                        <div class="fs-4 text-success">${producto.stock}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        ${producto.descripcion ? `
                            <div class="mb-3">
                                <h6>Descripción</h6>
                                <p class="text-muted">${producto.descripcion}</p>
                            </div>
                        ` : ''}
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary btn-lg" 
                                    onclick="tiendaModule.agregarAlCarritoDetalle(${producto.id})">
                                <i class="fas fa-cart-plus me-2"></i>Agregar al carrito
                            </button>
                            <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Mostrar modal
        if (typeof bootstrap !== 'undefined') {
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
            
            // Evento cuando se cierra el modal
            modal.addEventListener('hidden.bs.modal', () => {
                modal.remove();
            });
        }
    }
    
    // ✅ AGREGAR AL CARRITO DESDE DETALLE
    agregarAlCarritoDetalle(productoId) {
        const producto = this.buscarProductoPorId(productoId);
        if (producto) {
            this.agregarAlCarrito({
                id: producto.id,
                nombre: producto.nombre,
                precio: producto.precio,
                categoria: producto.categoria,
                cantidad: 1
            });
            
            this.mostrarNotificacion(`${producto.nombre} agregado al carrito`, 'success');
            
            // Cerrar modal
            const modal = document.getElementById('modal-detalle-producto');
            if (modal && typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) modalInstance.hide();
            }
        }
    }
    
    // ✅ MOSTRAR MODAL DE REGISTRO
    mostrarModalRegistro() {
        const modalId = 'modal-registro-vendedor';
        let modal = document.getElementById(modalId);
        
        if (modal) {
            modal.remove();
        }
        
        modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'modal fade';
        
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-store me-2"></i>Registro de Vendedor
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-store fa-4x text-primary mb-3"></i>
                            <h4>¡Conviértete en vendedor!</h4>
                            <p class="text-muted">
                                Para registrarte como vendedor en nuestro marketplace, primero necesitas iniciar sesión en el sistema.
                            </p>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Beneficios de ser vendedor:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Acceso a miles de clientes</li>
                                <li>Panel de administración de productos</li>
                                <li>Seguimiento de ventas en tiempo real</li>
                                <li>Soporte técnico especializado</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="window.location.href='/site/login?redirect=/tienda/registro-vendedor'">
                            <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        if (typeof bootstrap !== 'undefined') {
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
            
            // Evento cuando se cierra el modal
            modal.addEventListener('hidden.bs.modal', () => {
                modal.remove();
            });
        }
    }
    
    // ✅ VERIFICAR ACCESO A TIENDA
    checkTiendaAccess() {
        console.log('🔍 Verificando acceso a tienda...');
        
        // Verificar roles del usuario si está disponible
        if (this.usuarioRoles.length > 0) {
            const tieneAcceso = this.usuarioRoles.some(role => 
                ['vendedor', 'deportivo', 'admin', 'tienda', 'marketplace'].includes(role.toLowerCase())
            );
            
            if (!tieneAcceso) {
                console.warn('⚠️ Usuario no tiene rol de acceso a tienda');
                // Podríamos ocultar elementos de tienda si no tiene acceso
                this.ocultarElementosSinPermiso();
            } else {
                console.log('✅ Usuario tiene acceso a tienda');
            }
        }
    }
    
    // ✅ OCULTAR ELEMENTOS SIN PERMISO
    ocultarElementosSinPermiso() {
        // Solo ocultar si no hay roles de tienda
        if (!this.usuarioRoles.some(r => ['tienda', 'marketplace', 'admin'].includes(r.toLowerCase()))) {
            document.querySelectorAll('.solo-tienda, .solo-vendedor, [data-tienda-only]').forEach(el => {
                el.style.display = 'none';
            });
        }
    }
    
    // ✅ INICIALIZAR TOOLTIPS
    initTooltips() {
        if (typeof bootstrap !== 'undefined') {
            const tooltips = document.querySelectorAll('[title], [data-bs-toggle="tooltip"], [data-tooltip]');
            tooltips.forEach(element => {
                new bootstrap.Tooltip(element);
            });
            console.log('🛠️ Tooltips de tienda inicializados');
        }
    }
    
    // ✅ MOSTRAR NOTIFICACIÓN
    mostrarNotificacion(mensaje, tipo = 'info') {
        try {
            const tipos = {
                success: { bg: '#4CAF50', icon: 'fa-check-circle', title: 'Éxito' },
                error: { bg: '#dc3545', icon: 'fa-exclamation-circle', title: 'Error' },
                warning: { bg: '#ffc107', icon: 'fa-exclamation-triangle', title: 'Advertencia' },
                info: { bg: '#17a2b8', icon: 'fa-info-circle', title: 'Información' }
            };
            
            const config = tipos[tipo] || tipos.info;
            
            // Crear notificación
            const notificacion = document.createElement('div');
            notificacion.className = 'tienda-notificacion';
            notificacion.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                border-radius: 8px;
                z-index: 9999;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                animation: slideInRight 0.3s ease-out;
                color: white;
                font-weight: 500;
                max-width: 350px;
                background-color: ${config.bg};
                display: flex;
                align-items: center;
                gap: 10px;
            `;
            
            notificacion.innerHTML = `
                <i class="fas ${config.icon} fa-lg"></i>
                <div>
                    <div style="font-weight: bold;">${config.title}</div>
                    <div style="font-size: 0.9em; opacity: 0.9;">${mensaje}</div>
                </div>
                <button onclick="this.parentElement.remove()" 
                        style="background: transparent; border: none; color: white; margin-left: auto; cursor: pointer; font-size: 1.2em;">
                    ✕
                </button>
            `;
            
            document.body.appendChild(notificacion);
            
            // Auto-ocultar después de 3 segundos
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.style.animation = 'fadeOut 0.3s ease-out';
                    setTimeout(() => {
                        notificacion.remove();
                    }, 300);
                }
            }, 3000);
            
        } catch (error) {
            console.error('Error mostrando notificación:', error);
        }
    }
    
    // ✅ MOSTRAR ERROR
    showError(mensaje) {
        this.mostrarNotificacion(mensaje, 'error');
    }
    
    // ✅ SEGUIMIENTO DE EVENTOS
    trackEvent(action, label) {
        // Google Analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', action, {
                'event_category': 'tienda',
                'event_label': label
            });
        }
        
        // Log interno
        console.log(`📊 Evento Tienda: ${action} - ${label}`);
    }
    
    // ✅ DISPARAR EVENTO PERSONALIZADO
    dispatchEvent(eventName, detail = {}) {
        try {
            const event = new CustomEvent(eventName, { 
                detail: { ...detail, modulo: this } 
            });
            window.dispatchEvent(event);
        } catch (error) {
            console.error(`Error disparando evento ${eventName}:`, error);
        }
    }
    
    // ✅ OBTENER ESTADO DEL MÓDULO
    getStatus() {
        return {
            initialized: this.initialized,
            carrito: this.carrito.length,
            productos: Object.keys(this.productos).length,
            escuelaId: this.escuelaId,
            usuarioId: this.usuarioId,
            usuarioRoles: this.usuarioRoles,
            version: '3.0.0'
        };
    }
    
    // ✅ DESTRUIR MÓDULO (limpieza)
    destroy() {
        console.log('🗑️ Limpiando TiendaModule...');
        
        // Limpiar eventos
        document.querySelectorAll('.btn-agregar-carrito').forEach(btn => {
            btn.replaceWith(btn.cloneNode(true));
        });
        
        // Limpiar variables
        this.carrito = [];
        this.productos = {};
        this.initialized = false;
        
        // Remover modales si existen
        const modales = ['modal-carrito-tienda', 'modal-detalle-producto', 'modal-registro-vendedor'];
        modales.forEach(id => {
            const modal = document.getElementById(id);
            if (modal) modal.remove();
        });
        
        // Remover notificaciones
        document.querySelectorAll('.tienda-notificacion').forEach(el => el.remove());
        
        console.log('✅ TiendaModule limpiado');
    }
}

// ==================================================
// INICIALIZACIÓN GLOBAL MEJORADA
// ==================================================

// Hacer la clase disponible globalmente INMEDIATAMENTE
if (typeof window !== 'undefined') {
    window.TiendaModule = TiendaModule;
    console.log('✅ TiendaModule definido globalmente');
}

// Inicialización controlada con reintentos
let tiendaInitAttempts = 0;
const maxTiendaInitAttempts = 3;

function initializeTiendaModule() {
    console.log('🔧 Inicializando TiendaModule...');
    
    // Verificar si ya está inicializado
    if (window.tiendaModule && window.tiendaModule.initialized) {
        console.log('ℹ️ TiendaModule ya estaba inicializado');
        return window.tiendaModule;
    }
    
    // Verificar condiciones
    const tiendaModule = new TiendaModule();
    const shouldInit = tiendaModule.shouldInitialize();
    
    if (!shouldInit) {
        console.log('ℹ️ TiendaModule: No se requiere inicialización');
        return null;
    }
    
    try {
        const instance = tiendaModule.init();
        if (instance) {
            window.tiendaModule = instance;
            console.log('🚀 TiendaModule inicializado exitosamente');
            return instance;
        }
    } catch (error) {
        console.error('❌ Error inicializando TiendaModule:', error);
        
        // Reintentar si no ha excedido el máximo
        tiendaInitAttempts++;
        if (tiendaInitAttempts < maxTiendaInitAttempts) {
            console.log(`🔄 Reintentando inicialización (${tiendaInitAttempts}/${maxTiendaInitAttempts})...`);
            setTimeout(initializeTiendaModule, 1000 * tiendaInitAttempts);
        }
    }
    
    return null;
}

// Inicialización automática cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM cargado, verificando TiendaModule...');
    
    // Pequeño delay para asegurar que otros módulos estén cargados
    setTimeout(() => {
        initializeTiendaModule();
    }, 300);
});

// También inicializar cuando el sistema GED esté listo
window.addEventListener('ged:ready', function() {
    console.log('🎯 Evento ged:ready recibido, inicializando TiendaModule...');
    setTimeout(initializeTiendaModule, 500);
});

// ==================================================
// FUNCIONES GLOBALES DE UTILIDAD
// ==================================================

if (typeof window !== 'undefined') {
    // Función global para debug
    window.debugTiendaModule = function() {
        console.group('🛍️ DEBUG TIENDA MODULE v3.0.0');
        console.log('TiendaModule disponible:', typeof TiendaModule !== 'undefined');
        console.log('Instancia:', window.tiendaModule);
        console.log('Estado:', window.tiendaModule?.getStatus() || 'No inicializado');
        console.log('Carrito:', window.tiendaModule?.carrito || []);
        console.log('Productos:', window.tiendaModule?.productos || {});
        console.groupEnd();
    };
    
    // Función para agregar al carrito desde cualquier lugar
    window.agregarAlCarritoGED = function(producto) {
        if (window.tiendaModule) {
            return window.tiendaModule.agregarAlCarrito(producto);
        } else {
            console.error('❌ TiendaModule no está inicializado');
            return false;
        }
    };
    
    // Función para obtener el carrito
    window.obtenerCarritoGED = function() {
        return window.tiendaModule?.carrito || [];
    };
    
    // Función para vaciar el carrito
    window.vaciarCarritoGED = function() {
        if (window.tiendaModule) {
            window.tiendaModule.vaciarCarrito();
            return true;
        }
        return false;
    };
    
    // Función para forzar reinicialización
    window.reiniciarTiendaModule = function() {
        if (window.tiendaModule) {
            window.tiendaModule.destroy();
            window.tiendaModule = null;
        }
        tiendaInitAttempts = 0;
        return initializeTiendaModule();
    };
    
    // Función para mostrar carrito desde cualquier lugar
    window.mostrarCarritoTienda = function() {
        if (window.tiendaModule) {
            window.tiendaModule.mostrarCarrito();
        }
    };
}

// ==================================================
// MANEJO DE ERRORES GLOBAL
// ==================================================

window.addEventListener('error', function(e) {
    if (e.filename && e.filename.includes('tienda')) {
        console.error('❌ Error global capturado en módulo Tienda:', e.error);
        
        // Intentar mostrar notificación
        try {
            if (window.tiendaModule) {
                window.tiendaModule.showError(`Error en módulo tienda: ${e.message}`);
            }
        } catch (error) {
            console.error('❌ No se pudo mostrar notificación de error:', error);
        }
    }
});

// ==================================================
// INYECCIÓN DE ESTILOS
// ==================================================

(function injectTiendaStyles() {
    if (!document.getElementById('tienda-module-styles')) {
        const style = document.createElement('style');
        style.id = 'tienda-module-styles';
        style.textContent = `
            /* Estilos para productos */
            .producto-card {
                border: 1px solid #dee2e6;
                border-radius: 10px;
                padding: 15px;
                margin-bottom: 15px;
                transition: transform 0.2s, box-shadow 0.2s;
                background: white;
            }
            
            .producto-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            }
            
            .producto-imagen-placeholder {
                background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                border-radius: 8px;
                padding: 30px 20px;
                text-align: center;
                margin-bottom: 15px;
                min-height: 150px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .producto-nombre {
                font-size: 1rem;
                margin-bottom: 8px;
                color: #2c3e50;
                font-weight: 600;
            }
            
            .producto-precio {
                font-size: 1.25rem;
                font-weight: bold;
                color: #2ecc71;
                margin-bottom: 8px;
            }
            
            .producto-detalles {
                display: flex;
                justify-content: space-between;
                font-size: 0.85rem;
                color: #7f8c8d;
                margin-bottom: 12px;
            }
            
            .producto-detalles i {
                margin-right: 5px;
            }
            
            /* Botones de tienda */
            .btn-marketplace {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                color: white;
                padding: 12px 24px;
                border-radius: 25px;
                font-weight: 600;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-block;
            }
            
            .btn-marketplace:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
                color: white;
            }
            
            .btn-agregar-carrito {
                transition: all 0.3s ease;
                width: 100%;
            }
            
            .btn-agregar-carrito:hover {
                transform: scale(1.05);
            }
            
            /* Contador de carrito */
            .contador-carrito {
                background: #dc3545;
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                font-size: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                position: absolute;
                top: -5px;
                right: -5px;
            }
            
            /* Animaciones */
            .animate-pulse {
                animation: pulse 0.5s ease-in-out;
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
            
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
            
            /* Responsive */
            @media (max-width: 768px) {
                .producto-card {
                    padding: 12px;
                }
                
                .producto-detalles {
                    flex-direction: column;
                    gap: 5px;
                }
                
                .producto-imagen-placeholder {
                    padding: 20px;
                    min-height: 120px;
                }
            }
            
            /* Estilos para carrito */
            .carrito-item {
                transition: background-color 0.2s;
            }
            
            .carrito-item:hover {
                background-color: #f8f9fa;
            }
            
            .btn-cantidad {
                width: 30px;
                height: 30px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        `;
        
        document.head.appendChild(style);
        console.log('✅ Estilos de Tienda inyectados');
    }
})();

// ==================================================
// COMPATIBILIDAD Y VERIFICACIÓN
// ==================================================

// Verificar carga después de un tiempo
setTimeout(() => {
    if (typeof window.TiendaModule === 'undefined') {
        console.error('❌ ERROR: TiendaModule NO se cargó correctamente');
    } else {
        console.log('✅ TiendaModule cargado correctamente');
        
        // Verificar si hay elementos de tienda que necesiten inicialización tardía
        if (document.querySelector('[data-tienda-delay-init]')) {
            console.log('⏳ Inicializando elementos de tienda con retardo...');
            setTimeout(() => {
                if (window.tiendaModule && window.tiendaModule.initialized) {
                    window.tiendaModule.cargarDatosIniciales();
                }
            }, 1000);
        }
    }
}, 2000);

// ==================================================
// EXPORT PARA MÓDULOS
// ==================================================

// Export para Node.js (si es necesario)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TiendaModule;
}

console.log('✅ TiendaModule v3.0.0 cargado correctamente');
// ==================================================
// VERIFICACIÓN DE CARGA
// ==================================================

setTimeout(() => {
    console.log('🔍 VERIFICACIÓN TiendaModule:');
    console.log('- Clase TiendaModule definida:', typeof TiendaModule !== 'undefined');
    console.log('- En window.TiendaModule:', typeof window.TiendaModule !== 'undefined');
    console.log('- Instancia tiendaModule:', window.tiendaModule ? '✅ Sí' : '❌ No');
    
    if (typeof TiendaModule === 'undefined') {
        console.error('❌ ERROR CRÍTICO: TiendaModule NO está definido');
        console.log('Posibles causas:');
        console.log('1. Archivo tienda-module.js no se cargó');
        console.log('2. Error de sintaxis en el archivo');
        console.log('3. Orden de carga incorrecto');
    } else {
        console.log('✅ TiendaModule cargado correctamente');
    }
}, 3000);
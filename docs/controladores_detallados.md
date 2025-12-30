# CONTROLADORES DEL SISTEMA

## 🎮 CONTROLADORES PRINCIPALES

### 🔹 CoordenadasController

**Clase:** `CoordenadasController`

**Acciones públicas:** 1
**Acciones disponibles:**
- `GetCoordenadasByVista`
**Características:** Define behaviors

---

### 🔹 MunicipioController

**Clase:** `MunicipioController`

**Acciones públicas:** 2
**Acciones disponibles:**
- `GetByEdo`
- `List`
**Características:** Define behaviors

---

### 🔹 ParroquiaController

**Clase:** `ParroquiaController`

**Acciones públicas:** 3
**Acciones disponibles:**
- `GetByMuni`
- `GetByMuniCod`
- `List`
**Características:** Define behaviors

---

### 🔹 PerfilController

**Clase:** `PerfilController`

**Acciones públicas:** 3
**Acciones disponibles:**
- `MiInformacion`
- `MisDeudas`
- `MisRepresentados`
**Características:** Define behaviors

---

### 🔹 SiteController

**Clase:** `SiteController`

**Acciones públicas:** 16
**Acciones disponibles:**
- `s`
- `Index`
- `AccederSistema`
- `Login`
- `Logout`
- `Contact`
- `About`
- `CambiarPassword`
- `MiCuenta`
- `Testcss`
- `TestJs`
- `CheckRedirectLoop`
- `GetMobileMenu`
- `DebugMenu`
- `TestMenuWidget`
- `ClearCache`
**Características:** Define behaviors

---

### 🔹 TasaDolarController

**Clase:** `TasaDolarController`

**Acciones públicas:** 8
**Acciones disponibles:**
- `Index`
- `Actualizar`
- `Historial`
- `ActualizarAutomatico`
- `ApiTasaActual`
- `ProbarFuentes`
- `ExportarHistorial`
- `GetTasaActual`

---

### 🔹 probar_fuentes

**Acciones públicas:** 0

---

## 🏗️ CONTROLADORES EN MÓDULOS

### Módulo: `acces`

- `DefaultController`
  - Acciones: 1
- `UserController`
  - Acciones: 11

### Módulo: `aportes`

- `AportesController`
  - Acciones: 16

### Módulo: `atletas`

- `AsistenciaController`
  - Acciones: 14
- `AtletasRegistroController`
  - Acciones: 6
- `DefaultController`
  - Acciones: 1

### Módulo: `escuela_club`

- `ClubRegistroController`
  - Acciones: 5
- `DefaultController`
  - Acciones: 1
- `EscuelaPreRegistroController`
  - Acciones: 14
- `EscuelaRegistroController`
  - Acciones: 14
- `EscuelaValidacionController`
  - Acciones: 6

### Módulo: `ged`

- `DefaultController`
  - Acciones: 13

### Módulo: `reportes`

- `DefaultController`
  - Acciones: 1
- `ReportesController`
  - Acciones: 6

### Módulo: `tienda`

- `DefaultController`
  - Acciones: 3
- `MarketplaceController`
  - Acciones: 7
- `ProductoController`
  - Acciones: 8
- `TiendaController`
  - Acciones: 7


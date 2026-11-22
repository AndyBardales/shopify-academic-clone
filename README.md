# ShopCMS — Plataforma Modular de Comercio Electrónico

![Vista previa de ShopCMS](./ShopifyClon.webp)

**ShopCMS** es un sistema de gestión de comercio electrónico (CMS estilo Shopify / WooCommerce) desarrollado en **PHP 8+**, **MySQL / MariaDB**, **HTML5**, **CSS3** y **JavaScript**, pensado para instalarse desde cero mediante un asistente web guiado, personalizar plantillas en tiempo real y ampliar funcionalidades mediante temas y plugins instalables en formato `.zip`.

---

## Instalación Limpia (Primer Uso)

Este repositorio se entrega **sin datos previos**:
- No incluye archivo `config.php` preconfigurado.
- No incluye cuentas de administrador ni clientes precreados.
- No incluye productos, categorías, pedidos, cupones, reseñas ni imágenes cargadas.
- Incluye únicamente la aplicación base, los **4 temas base** y los **6 plugins base** listos para configurarse al completar el asistente `/install/`.

---

## Requisitos Previos

- **PHP:** 8.0 o superior.
- **Extensiones PHP habilitadas:**
  - `pdo`
  - `pdo_mysql`
  - `zip` (`ZipArchive`, requerida para instalar temas y plugins `.zip`)
- **Base de datos:** MySQL 5.7+ o MariaDB 10.3+.
- **Servidor web:** Apache con `mod_rewrite` activado (compatible con **XAMPP**, **Laragon**, **WAMP** o **LAMP**).

---

## Guía de Despliegue Local (XAMPP)

1. **Clonar el repositorio dentro de `htdocs`:**
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/AndyBardales/shopify-academic-clone.git shopcms
   ```
   *(Puedes usar cualquier nombre de carpeta dentro de `htdocs`, por ejemplo `shopcms` o `shopify-academic-clone`.)*

2. **Verificar permisos y servicios:**
   - Abre el **XAMPP Control Panel** e inicia **Apache** y **MySQL**.
   - Asegúrate de que la extensión `zip` esté habilitada en `php.ini` (`extension=zip`).

3. **Ejecutar el Asistente de Instalación Web:**
   Abre tu navegador e ingresa a:
   ```text
   http://localhost/shopcms/
   ```
   Al no existir `config.php`, el sistema te redirigirá automáticamente a `/install/`.

4. **Completar los 4 pasos del instalador:**
   - **Paso 1 — Requisitos:** Valida versión de PHP, extensiones `pdo`, `pdo_mysql`, `ZipArchive` y permisos de escritura en `/`, `/uploads` y `/plugins`.
   - **Paso 2 — Base de Datos:** Ingresa el host (`localhost`), puerto (`3306`), nombre de base de datos (por ejemplo `shopcms_db`; se crea automáticamente si no existe), usuario (`root`), contraseña y prefijo de tablas (`shop_`).
   - **Paso 3 — Tienda y Administrador:** Define el nombre de tu tienda, moneda principal, correo de contacto y **crea la primera cuenta de Administrador**.
   - **Paso 4 — Finalización:** Genera el archivo `config.php`, crea las **17 tablas** en MySQL (`install/schema.sql`), registra los ajustes iniciales, los 4 temas base y los 6 plugins base (`install/seeds.sql`), e inicia sesión en `/admin`.

---

## Tecnologías Utilizadas

| Capa | Tecnologías |
|---|---|
| **Backend** | PHP 8+, arquitectura MVC propia con autoloader PSR-4 (`ShopCMS\`), PDO con consultas preparadas, `ZipArchive` |
| **Base de Datos** | MySQL / MariaDB (17 tablas relacionales con claves foráneas y soporte de borrado lógico) |
| **Frontend Admin** | HTML5, CSS3 inspirado en **Shopify Polaris** (`assets/css/polaris.css`), JavaScript Vanilla |
| **Frontend Tienda** | HTML5 + CSS3 responsive modular + puente en vivo por `postMessage` para el editor visual |
| **Enrutamiento** | Apache `.htaccess` (`mod_rewrite`) + enrutador dinámico PHP (`app/Core/Router.php`) |

---

## Módulos y Funcionalidades Principales

### Panel de Administración (`/admin`)
- **Dashboard:** Resumen operativo de ingresos, pedidos, productos y métricas clave.
- **Productos y Variantes:** Gestión de productos simples o con opciones/variantes dinámicas (Talla, Color, precio individual, SKU y stock por variante).
- **Categorías:** Categorías jerárquicas (principales y subcategorías), activación rápida y presets.
- **Pedidos:** Flujo de estados (`pending`, `processing`, `packing`, `shipped`, `out_for_delivery`, `delivered`, `completed`, `cancelled`, `returned`), seguimiento con tracking e historial auditado.
- **Inventario (Kardex):** Control de stock con alertas de umbral mínimo y registro de movimientos (`inventory_movements`).
- **Cupones y Descuentos:** Porcentaje o monto fijo, gasto mínimo/rango, límite de uso, restricción de un uso por usuario y expiración.
- **Clientes:** Directorio de clientes registrados y detalle de compras.
- **Reseñas:** Moderación (aprobar, rechazar, ocultar), reseñas destacadas y respuesta del administrador.
- **Editor Visual de Temas en Vivo (`/admin/themes/customize/{slug}`):**
  - Vista previa en tiempo real para **Escritorio, Tablet y Móvil**.
  - Edición de cabecera, logotipo, paleta global, tipografía, banner hero, orden/visibilidad de secciones arrastrables, bloques modulares adicionales, **selector inteligente de categorías en portada** (N más solicitadas, solo principales o selección manual) y pie de página editable completo.
  - Historial **Deshacer / Rehacer** y guardado persistente por tema.
- **Plugins y Temas instalables (.ZIP):** Carga, extracción segura mediante `ZipArchive`, activación, desactivación y eliminación.
- **Ajustes Generales:** Configuración de tienda, moneda, impuestos, envíos, métodos de pago, barra de anuncios, widget de WhatsApp y modo mantenimiento.

### Tienda Pública (Storefront)
- Catálogo filtrable por categoría y búsqueda.
- Ficha de producto con galería, selección de variantes, alertas de stock, reseñas y calificaciones.
- Carrito en sesión, aplicación de cupones y checkout completo.
- Registro/acceso de clientes y portal de cuenta (`/account`) con historial de pedidos y edición de perfil.

---

## Temas y Plugins Base Incluidos

### Temas Base (`/themes/`)
| Slug | Nombre | Enfoque Visual |
|---|---|---|
| `minimal-shop` | **Minimal Shop** | Estilo limpio centrado en productos e identidad clásica Shopify *(activo por defecto)* |
| `fashion` | **Fashion** | Editorial con tipografía serif, cabecera centrada y estética de colección |
| `electronics` | **Electronics** | Grilla densa de catálogo y ficha compacta orientada a especificaciones |
| `food-market` | **Food & Market** | Estilo cálido y orgánico para productos de mercado y consumo diario |

### Plugins Base (`/plugins/`)
| Slug | Nombre | Función |
|---|---|---|
| `payment-gateway` | **Payment Gateway** | Opciones de pago Culqi y PayPal integradas en el checkout |
| `wishlist` | **Wishlist** | Botón de lista de deseos en ficha de producto |
| `product-comparator` | **Product Comparator** | Comparador de hasta 4 productos |
| `stock-notifier` | **Stock Notifier** | Suscripción de alerta cuando un producto agotado repone stock |
| `related-products` | **Related Products** | Sugerencias automáticas de venta cruzada en producto |
| `invoice-generator` | **Invoice Generator** | Comprobante/factura comercial imprimible por pedido |

---

## Estructura del Proyecto

```text
shopify-academic-clone/
├── admin/
│   └── index.php                      # Redirección segura hacia /admin
├── app/
│   ├── Controllers/
│   │   ├── AdminController.php        # Lógica de dashboard y CRUDs administrativos
│   │   └── StoreController.php        # Lógica del storefront, carrito, checkout y cuenta
│   ├── Core/
│   │   ├── Auth.php                   # Sesiones y roles (admin / customer)
│   │   ├── Database.php               # Conexión PDO Singleton y helpers SQL
│   │   ├── InventoryManager.php       # Descuento, restauración y kardex de stock
│   │   ├── OrderWorkflow.php          # Transiciones de estado e historial de pedidos
│   │   ├── PluginManager.php          # Carga, hooks e instalación ZIP de plugins
│   │   ├── Router.php                 # Enrutador HTTP dinámico
│   │   ├── Settings.php               # Lectura y persistencia de opciones del sistema
│   │   ├── ThemeCustomizer.php        # Motor del editor visual en vivo y estilos globales
│   │   └── ThemeManager.php           # Gestión, renderizado e instalación ZIP de temas
│   └── Views/
│       └── admin/                     # Vistas administrativas (Polaris) y Theme Customizer
├── assets/
│   ├── css/                           # polaris.css y shopcms-guard.css
│   ├── images/                        # Carpeta limpia para imágenes del proyecto
│   └── js/                            # shopcms-guard.js
├── install/
│   ├── assets/install.css             # Estilos del asistente de instalación
│   ├── index.php                      # Instalador web guiado y reparador de BD
│   ├── schema.sql                     # Esquema de las 17 tablas MySQL/MariaDB
│   └── seeds.sql                      # Ajustes iniciales, 4 temas base y 6 plugins base
├── plugins/                           # 6 plugins base incluidos
├── themes/                            # 4 temas base incluidos
├── uploads/
│   └── products/                      # Directorio vacío para imágenes subidas en ejecución
├── .gitignore
├── .htaccess                          # Reescritura de URLs para Apache
├── index.php                          # Front controller y definición de rutas
├── README.md
└── ShopifyClon.webp                   # Captura de vista general del sistema
```

---

## Base de Datos (17 Tablas)

Definidas en `install/schema.sql` utilizando el prefijo configurado en la instalación (por defecto `shop_`):

1. `users` — Administradores, staff y clientes.
2. `settings` — Configuración global de la tienda y personalización de temas.
3. `categories` — Categorías jerárquicas.
4. `products` — Catálogo principal de productos.
5. `product_attributes` / `product_attribute_values` — Atributos dinámicos (ej. Talla, Color).
6. `product_variants` / `variant_attribute_values` — Combinaciones de variantes con precio y stock propio.
7. `product_images` — Galería de imágenes por producto.
8. `orders` / `order_items` / `order_history` — Pedidos, detalle de líneas y trazabilidad.
9. `coupons` — Cupones promocionales y reglas de aplicación.
10. `reviews` — Calificaciones, moderación y respuestas.
11. `plugins` / `themes` — Registro e historial de activación de extensiones y temas.
12. `inventory_movements` — Kardex de movimientos de stock.

---

## Guía Rápida para Colaboradores (QA y Mejoras)

Al instalar por primera vez desde cero, este flujo te permitirá probar todo el ciclo del sistema e identificar puntos de mejora:

1. Completa `/install/` creando tu base de datos y tu cuenta de administrador.
2. En **Admin > Ajustes**, configura nombre, moneda, envío e impuestos.
3. En **Admin > Categorías**, crea categorías y subcategorías.
4. En **Admin > Productos**, crea productos simples y productos con variantes.
5. En **Admin > Temas > Personalizar**, prueba el editor visual en vistas **Móvil** y **Escritorio**, cambia el modo de visualización de categorías, edita el footer y reordena bloques.
6. En la **Tienda Pública**, regístrate como cliente, aplica cupones, realiza un pedido de prueba y verifica el seguimiento de estados desde **Admin > Pedidos** y **Mi Cuenta**.
7. Reporta bugs o propuestas abriendo un **Issue** o enviando un **Pull Request** en una rama nueva.

# Política de Seguridad y Respuesta ante Incidentes — ShopCMS

> Documento académico de buenas prácticas basado en OWASP Top 10 (2021) y OWASP ASVS.

## 1. Controles defensivos implementados

- **Consultas SQL parametrizadas (A03):** Todas las consultas al motor MySQL usan PDO con sentencias preparadas y listas blancas en ordenamientos (`CatalogHelper`).
- **Control de acceso e IDOR (A01):** Rutas administrativas protegidas en servidor (`AdminController::__construct`), validación de propiedad de pedidos, comprobantes y reseñas en servidor con respuesta uniforme `404`.
- **Protección CSRF y Same-Origin (A01):** Token CSRF por sesión validado con `hash_equals()` y comprobación de cabecera `Origin` en todas las peticiones `POST`.
- **Escape contextual anti-XSS y cabeceras HTTP (A03 / A05):** Salida escapada con `htmlspecialchars` / `esc()` en vistas y plugins (`invoice-generator`), más emisión automática de `X-Content-Type-Options`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy` y `Strict-Transport-Security` (cuando opera sobre HTTPS).
- **Sesiones y contraseñas (A02 / A07):** Contraseñas hasheadas con `password_hash()` (bcrypt), cookies `HttpOnly` + `SameSite=Lax` + `session.use_strict_mode=1`, regeneración de ID de sesión al autenticar/registrar y bloqueo temporal ante intentos fallidos (`Security::recordFailedLogin`).
- **Subida segura de archivos y paquetes ZIP (A04):** Verificación de firma/MIME real (`finfo`/`getimagesize`), conversión a WebP de logotipos, bloqueo de ejecución PHP en `uploads/.htaccess` y validación anti-Zip-Slip (`../`) en `ThemeManager` y `PluginManager`.
- **Auditoría de seguridad (A09):** Registro estructurado en `storage/logs/security.log` (protegido por `.htaccess`) con filtrado automático de contraseñas, tokens y datos sensibles.

## 2. Procedimiento de respuesta ante incidentes

1. **Detección y triaje:** Revisar `storage/logs/security.log` buscando picos de `admin_login_failed`, `admin_login_locked`, `csrf_or_origin_rejected` o `idor_*_denied`, identificando el `cid` (ID de correlación) y la IP de origen.
2. **Contención:** Activar `maintenance_mode` en Ajustes o bloquear la IP/ruta afectada desde `.htaccess`; deshabilitar cuentas comprometidas desde *Admin > Clientes* o MySQL.
3. **Erradicación y rotación de credenciales:** Rotar `APP_KEY` y contraseña de `DB_PASS` en `config.php`, cambiar contraseñas de administradores y eliminar sesiones activas en el servidor.
4. **Recuperación y verificación:** Restaurar copia de seguridad limpia de la BD si hubo alteración, ejecutar la suite de pruebas de regresión de seguridad y documentar la causa raíz antes de reabrir el servicio.

## 3. Lista de verificación si se llevara a producción

- Configurar certificado TLS (HTTPS) y forzar redirección HTTP → HTTPS (activa `HSTS` y cookie `Secure`).
- Establecer `APP_ENV = 'production'` y `APP_DEBUG = false` en `config.php`.
- Eliminar o restringir por red interna el directorio `install/`.
- Utilizar un usuario MySQL dedicado con privilegios mínimos (sin `root` y sin `DROP`/`FILE`).
- Migrar scripts inline a `nonce` CSP y habilitar autenticación de doble factor (2FA) para cuentas administrativas.

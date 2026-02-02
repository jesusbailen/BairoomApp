# 🏠 Bairoom - Sistema de Reservas

![Bairoom logo](img/logo.webp)

## 📝 Descripción del proyecto
Bairoom es una plataforma de reservas para habitaciones en viviendas compartidas. Permite a inquilinos solicitar reservas, a propietarios gestionar sus viviendas y a administradores controlar usuarios, recursos y reservas.

## ✨ Características
- Registro, login y gestión de perfiles.
- Roles: Administrador, Propietario e Inquilino.
- Gestión de propiedades y habitaciones con imágenes.
- Reservas con validación de disponibilidad.
- Panel de administración y paneles de usuario.
- Integración con Stripe (modo test).
- Exportación de reservas en PDF.

## 🛠️ Tecnologías utilizadas
- PHP 8
- MySQL / MariaDB
- Bootstrap 5
- Stripe PHP SDK

## 🌍 Enlace al hosting
- https://bairoom.42web.io/Bairoom/

## 🧩 Estructura del proyecto
```text
Bairoom/
PASTE_FILETREE_PRO_OUTPUT_HERE
```

## 🚀 Instalación
1) Clona el repositorio.
2) Crea tu archivo `.env` con las credenciales de la base de datos.
3) Importa el SQL (ver apartado siguiente).
4) Abre en el navegador:
   - `http://localhost/Bairoom/index.php`

## 🗄️ Base de datos
Tablas principales:
- `usuario`
- `rol`
- `propiedad`
- `habitacion`
- `reserva`
- `pago`

### 📄 Script SQL
El script incluido es `bairoom_pi2.sql`.

### 📥 Importación del SQL
1) Abre phpMyAdmin.
2) Selecciona tu base de datos.
3) Pestaña Importar.
4) Sube `bairoom_pi2.sql`.
5) Confirma la importación.

## ▶️ Uso
- Acceso público a la web y listados de habitaciones.
- Inquilino: reservas, pagos y perfil.
- Propietario: gestión de viviendas y reservas.
- Administrador: usuarios, recursos y reservas.

## ✅ Funcionalidades completadas
- Gestión de usuarios y roles.
- CRUD de recursos (propiedades y habitaciones).
- Reservas con validación de disponibilidad.
- Pagos con Stripe (modo test).
- Exportación de reservas a PDF.

## 🔒 Seguridad
Medidas implementadas:
- Consultas preparadas (PDO).
- Saneamiento con `htmlspecialchars`.
- Passwords con `password_hash` y `password_verify`.
- Control de acceso por roles.

Recomendaciones si fuese un caso real:
- HTTPS y HSTS.
- CSRF tokens en formularios sensibles.
- Rate limiting y protección anti fuerza bruta.
- Logs y auditoría de acciones admin.
- Backups automáticos de BD.

## 📌 Estado del proyecto
Completado y estable para entrega académica.

## 📄 Licencia
MIT

## 👤 Autor
- Jesús Bailén

## 📬 Contacto
- GitHub: https://github.com/jesusbailen

---
Gracias por revisar el proyecto.

# Novelty

Sistema de venta de autos seminuevos — PHP 7.4 + MySQL.

- Sitio público: inicio con destacados, catálogo con filtros (marca, modelo, año, kilometraje, agencia/rodado, precio) y búsqueda libre, ficha con galería.
- Panel (`/admin`): vehículos con hasta 30 fotos, clientes, ventas, reportes y usuarios (roles **admin** y **test**).

## Instalación
1. Crear base de datos y usuario MySQL en cPanel.
2. Abrir `/instalar.php` y llenar los datos. Crea `app/config.local.php` (no está en git).

## Deploy
Cada push a `main` compila Tailwind y sube los archivos por FTPS (`.github/workflows/deploy.yml`).
Secret requerido: `FTP_PASSWORD`. Las fotos (`uploads/vehiculos`) y la configuración nunca se sobrescriben.

## CSS
`npm install && npm run build:css` genera `assets/css/tw.css`.

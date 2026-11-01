CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  usuario VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('admin','test') NOT NULL DEFAULT 'test',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_acceso DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marcas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL,
  slug VARCHAR(70) NOT NULL,
  UNIQUE KEY uq_marca (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS modelos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  marca_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  UNIQUE KEY uq_modelo (marca_id, nombre),
  CONSTRAINT fk_modelo_marca FOREIGN KEY (marca_id) REFERENCES marcas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clientes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  telefono VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  dpi VARCHAR(20) NULL,
  nit VARCHAR(20) NULL,
  direccion VARCHAR(255) NULL,
  notas TEXT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cliente_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vehiculos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NULL,
  marca_id INT UNSIGNED NOT NULL,
  modelo_id INT UNSIGNED NOT NULL,
  version VARCHAR(80) NULL,
  anio SMALLINT UNSIGNED NOT NULL,
  kilometraje INT UNSIGNED NOT NULL DEFAULT 0,
  procedencia ENUM('agencia','rodado') NOT NULL DEFAULT 'rodado',
  color VARCHAR(40) NULL,
  transmision ENUM('automatica','manual') NULL,
  combustible ENUM('gasolina','diesel','hibrido','electrico') NULL,
  motor VARCHAR(30) NULL,
  carroceria VARCHAR(30) NULL,
  traccion VARCHAR(10) NULL,
  puertas TINYINT UNSIGNED NULL,
  pasajeros TINYINT UNSIGNED NULL,
  descripcion TEXT NULL,
  etiquetas VARCHAR(255) NULL,
  precio_publicado DECIMAL(12,2) NOT NULL DEFAULT 0,
  destacado TINYINT(1) NOT NULL DEFAULT 0,
  estado ENUM('disponible','reservado','oculto','vendido') NOT NULL DEFAULT 'disponible',
  tipo_ingreso ENUM('consignacion','compra') NOT NULL DEFAULT 'consignacion',
  propietario_id INT UNSIGNED NULL,
  precio_propietario DECIMAL(12,2) NULL,
  gastos DECIMAL(12,2) NOT NULL DEFAULT 0,
  notas_internas TEXT NULL,
  fecha_ingreso DATE NOT NULL,
  fecha_venta DATE NULL,
  precio_venta_final DECIMAL(12,2) NULL,
  comprador_id INT UNSIGNED NULL,
  notas_venta TEXT NULL,
  vistas INT UNSIGNED NOT NULL DEFAULT 0,
  buscar TEXT NULL,
  creado_por INT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NULL,
  UNIQUE KEY uq_codigo (codigo),
  KEY idx_estado (estado),
  KEY idx_fecha_venta (fecha_venta),
  KEY idx_anio (anio),
  KEY idx_km (kilometraje),
  KEY idx_precio (precio_publicado),
  CONSTRAINT fk_veh_marca FOREIGN KEY (marca_id) REFERENCES marcas(id),
  CONSTRAINT fk_veh_modelo FOREIGN KEY (modelo_id) REFERENCES modelos(id),
  CONSTRAINT fk_veh_propietario FOREIGN KEY (propietario_id) REFERENCES clientes(id) ON DELETE SET NULL,
  CONSTRAINT fk_veh_comprador FOREIGN KEY (comprador_id) REFERENCES clientes(id) ON DELETE SET NULL,
  CONSTRAINT fk_veh_usuario FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vehiculo_fotos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vehiculo_id INT UNSIGNED NOT NULL,
  archivo VARCHAR(120) NOT NULL,
  orden INT NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_foto_veh (vehiculo_id, orden),
  CONSTRAINT fk_foto_veh FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ajustes (
  clave VARCHAR(50) NOT NULL PRIMARY KEY,
  valor TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

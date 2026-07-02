# DOCUMENTACION DEL PROYECTO GAMEHUB
## Portal de Reseñas y Recomendaciones de Videojuegos

---

## 1. DESCRIPCION GENERAL

GameHub es una plataforma web donde los usuarios pueden consultar y registrar reseñas de videojuegos. La solucion esta conformada por dos aplicaciones:

- **Aplicacion Web Principal**: Desarrollada en PHP con PostgreSQL.
- **Servicio de Analitica**: Desarrollado en Python Flask con MongoDB Atlas.

Ambos sistemas se integran mediante una API REST.

---

## 2. ESTRUCTURA DEL PROYECTO

```
GameHub/
├── web/                          # Aplicacion Web PHP
│   ├── db.php                    # Conexion a PostgreSQL con PDO
│   ├── schema.sql                # Script SQL para crear las tablas
│   ├── crear_tablas.php          # Ejecuta el script SQL desde PHP
│   ├── header.php                # Encabezado HTML con Bootstrap
│   ├── footer.php                # Pie de pagina HTML con Bootstrap
│   ├── index.php                 # Pagina principal - lista de videojuegos
│   ├── registrar_juego.php       # Formulario para registrar videojuegos
│   ├── registrar_usuario.php     # Formulario para registrar usuarios
│   ├── ver.php                   # Detalle de juego + reseñas
│   ├── css/
│   │   └── style.css             # Estilos personalizados
│   └── api/
│       └── enviar_analitica.php  # Envia datos al servicio de analitica
│
└── analytics/                    # Servicio Python Flask
    ├── app.py                    # API REST con Flask + MongoDB
    ├── requirements.txt          # Dependencias de Python
    └── .env.example              # Variables de entorno de ejemplo
```

---

## 3. BASE DE DATOS (PostgreSQL)

### 3.1. Diagrama de Tablas

El sistema utiliza 3 tablas principales:

**Tabla: usuarios**
| Campo         | Tipo         | Descripcion                |
|---------------|--------------|----------------------------|
| id            | SERIAL (PK)  | Identificador unico        |
| nombre        | VARCHAR(100) | Nombre del usuario         |
| email         | VARCHAR(150) | Correo electronico (UNIQUE)|
| fecha_registro| TIMESTAMP    | Fecha de registro          |

**Tabla: videojuegos**
| Campo               | Tipo          | Descripcion                     |
|---------------------|---------------|---------------------------------|
| id                  | SERIAL (PK)   | Identificador unico             |
| titulo              | VARCHAR(200)  | Titulo del videojuego           |
| genero              | VARCHAR(100)  | Genero del juego                |
| plataforma          | VARCHAR(100)  | Plataforma (PC, PS5, etc.)      |
| descripcion         | TEXT          | Descripcion del juego           |
| calificacion_promedio| DECIMAL(3,1) | Promedio de calificaciones      |
| fecha_registro      | TIMESTAMP     | Fecha de registro               |

**Tabla: resenas**
| Campo         | Tipo         | Descripcion                          |
|---------------|--------------|--------------------------------------|
| id            | SERIAL (PK)  | Identificador unico                  |
| usuario_id    | INTEGER (FK) | Referencia a usuarios(id)            |
| videojuego_id | INTEGER (FK) | Referencia a videojuegos(id)         |
| calificacion  | INTEGER      | Calificacion del 1 al 5              |
| comentario    | TEXT          | Comentario de la reseña              |
| fecha         | TIMESTAMP     | Fecha de la reseña                   |

### 3.2. Relaciones

- Un usuario puede escribir muchas reseñas (1:N)
- Un videojuego puede tener muchas reseñas (1:N)
- La calificacion promedio del videojuego se actualiza automaticamente con cada reseña

### 3.3. Script SQL

```sql
CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS videojuegos (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    genero VARCHAR(100) NOT NULL,
    plataforma VARCHAR(100) NOT NULL,
    descripcion TEXT,
    calificacion_promedio DECIMAL(3,1) DEFAULT 0,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS resenas (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    videojuego_id INTEGER NOT NULL REFERENCES videojuegos(id) ON DELETE CASCADE,
    calificacion INTEGER NOT NULL CHECK (calificacion >= 1 AND calificacion <= 5),
    comentario TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 4. CONEXION A LA BASE DE DATOS (db.php)

Archivo: `web/db.php`

Utiliza la clase PDO de PHP para conectarse a PostgreSQL. Incluye un bloque try-catch para manejar errores de conexion.

```php
<?php
$host = 'localhost';
$dbname = 'gamehub';
$usuario = 'postgres';
$password = 'TU_CONTRASEÑA';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usuario, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}
```

**Conceptos clave utilizados:**
- PDO (PHP Data Objects): Clase de PHP para acceso a bases de datos.
- try-catch: Estructura para capturar errores sin detener la ejecucion.
- Marcadores de posicion: Variables $host, $dbname, etc. para configurar la conexion.

---

## 5. PAGINAS PRINCIPALES

### 5.1. index.php - Pagina de Inicio

Muestra todos los videojuegos registrados en tarjetas con diseño responsive.

**Funcionalidades:**
- Consulta SQL: `SELECT * FROM videojuegos ORDER BY fecha_registro DESC`
- Muestra cada juego en una tarjeta con titulo, genero, plataforma y calificacion
- Estrellas visuales para representar la calificacion
- Boton "Ver Detalles" para ir a la pagina de reseñas

**Tecnologias:**
- Bootstrap 5 Grid System (row, col-md-4)
- Bootstrap Cards para las tarjetas
- Iconos de Bootstrap Icons (bi bi-star-fill, bi bi-controller)

### 5.2. registrar_juego.php - Registro de Videojuegos

Formulario para agregar nuevos videojuegos a la base de datos.

**Funcionalidades:**
- Campos: titulo, genero, plataforma, descripcion
- Validacion basica en PHP (campos obligatorios)
- Insercion con consultas preparadas PDO para prevenir SQL injection
- Mensajes de exito o error con alertas de Bootstrap

**Conceptos clave:**
- Consultas preparadas: `$stmt = $pdo->prepare("INSERT INTO...")`
- Ejecucion segura: `$stmt->execute([...])` con parametros

### 5.3. registrar_usuario.php - Registro de Usuarios

Formulario para registrar nuevos usuarios.

**Funcionalidades:**
- Campos: nombre y email
- Validacion de email duplicado (codigo de error PostgreSQL 23505)
- Mensajes de exito o error

### 5.4. ver.php - Detalle de Videojuego y Reseñas

Pagina que muestra la informacion completa de un videojuego y sus reseñas.

**Funcionalidades:**
- Consulta del videojuego por ID
- Lista de reseñas con JOIN para mostrar el nombre del usuario
- Formulario para agregar nuevas reseñas (seleccion de usuario, estrellas, comentario)
- Sistema de estrellas interactivo con JavaScript
- Actualizacion automatica del promedio al agregar reseñas

**Conceptos clave:**
- JOIN SQL: `SELECT r.*, u.nombre FROM resenas r JOIN usuarios u ON r.usuario_id = u.id`
- Subconsultas: `UPDATE videojuegos SET calificacion_promedio = (SELECT AVG(calificacion) FROM resenas WHERE...)`

---

## 6. DISEÑO VISUAL

### 6.1. Librerias utilizadas

- **Bootstrap 5.3.2**: Framework CSS para diseño responsive
- **Bootstrap Icons 1.11.3**: Libreria de iconos

### 6.2. Paleta de colores

El diseño utiliza un tema oscuro estilo gamer:

| Color       | Uso                     | Codigo HEX |
|-------------|-------------------------|------------|
| Negro       | Fondos de tarjetas      | #000000    |
| Rojo        | Bordes, botones, titulos| #dc3545    |
| Gris oscuro | Fondo de inputs         | #1a1a2e    |
| Blanco      | Texto principal         | #ffffff    |
| Amarillo    | Estrellas               | #ffc107    |

### 6.3. Estilos personalizados (style.css)

```css
body {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.card {
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
}
```

---

## 7. SERVICIO DE ANALITICA (Python Flask)

### 7.1. Descripcion

Servicio desarrollado en Flask que recibe datos desde la aplicacion PHP y los almacena en MongoDB Atlas para generar estadisticas.

### 7.2. Endpoints

| Metodo | Ruta                | Descripcion                           |
|--------|---------------------|---------------------------------------|
| GET    | /                   | Estado del servicio                   |
| POST   | /api/registrar      | Registra un videojuego en MongoDB     |
| POST   | /api/calificar      | Registra una calificacion             |
| GET    | /api/estadisticas   | Obtiene estadisticas de un videojuego |

### 7.3. Dependencias

```
flask==2.3.3
pymongo==4.5.0
gunicorn==21.2.0
```

---

## 8. DESPLIEGUE

### 8.1. Aplicacion Web (Render)

1. Subir los archivos de la carpeta `web/` a un repositorio Git
2. En Render, crear un nuevo Web Service
3. Configurar variables de entorno (DB_HOST, DB_NAME, DB_USER, DB_PASSWORD)
4. La base de datos PostgreSQL se crea en Render

### 8.2. Servicio de Analitica (Railway)

1. Subir los archivos de la carpeta `analytics/` a un repositorio Git
2. En Railway, crear un nuevo servicio
3. Configurar la variable de entorno MONGO_URI con la cadena de conexion de MongoDB Atlas

### 8.3. Integracion

La aplicacion PHP se comunica con el servicio Flask mediante peticiones HTTP usando cURL:

```php
$ch = curl_init($url_analitica . '/api/registrar');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
$respuesta = curl_exec($ch);
curl_close($ch);
```

---

## 9. PASOS PARA EJECUTAR EL PROYECTO

1. Instalar PostgreSQL y crear la base de datos `gamehub`
2. Editar `web/db.php` con las credenciales correctas
3. Ejecutar `crear_tablas.php` desde el navegador para crear las tablas
4. Instalar Python y las dependencias del servicio de analitica:
   ```
   pip install -r requirements.txt
   ```
5. Ejecutar el servicio Flask:
   ```
   python app.py
   ```
6. Abrir `index.php` en el navegador a traves de un servidor web (XAMPP, WAMP, o el servidor integrado de PHP)

---

## 10. REFERENCIAS

- [Documentacion de PDO en PHP](https://www.php.net/manual/es/book.pdo.php)
- [Documentacion de PostgreSQL](https://www.postgresql.org/docs/)
- [Documentacion de Bootstrap 5](https://getbootstrap.com/docs/5.3/)
- [Documentacion de Flask](https://flask.palletsprojects.com/)
- [Documentacion de MongoDB](https://www.mongodb.com/docs/)

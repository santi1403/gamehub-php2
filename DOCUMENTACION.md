# DOCUMENTACION DEL PROYECTO GAMEHUB
## Portal de Resenas y Recomendaciones de Videojuegos

---

## 1. DESCRIPCION GENERAL

GameHub es una plataforma web donde los usuarios pueden consultar y registrar resenas de videojuegos. La plataforma cuenta con un servicio inteligente que analiza las resenas y genera estadisticas sobre los videojuegos mejor valorados.

La solucion esta conformada por dos aplicaciones:

- **Aplicacion Web Principal**: Desarrollada en PHP, desplegada en Render, base de datos PostgreSQL.
- **Servicio de Analitica**: Desarrollado en Python Flask, desplegado en Render, base de datos MongoDB Atlas.

Ambos sistemas se integran mediante una API REST.

---

## 2. ESTRUCTURA DEL PROYECTO

```
GameHub/
├── Dockerfile                    # Configuracion Docker para la app PHP
├── docker-compose.yml            # Entorno local con PostgreSQL
├── DOCUMENTACION.md              # Este documento
├── web/                          # Aplicacion Web PHP
│   ├── db.php                    # Conexion PDO a PostgreSQL
│   ├── api_config.php            # URL de la API de analitica
│   ├── schema.sql                # Script SQL para crear las tablas
│   ├── migracion.php             # Agrega nuevas columnas a la BD
│   ├── crear_tablas.php          # Crea las tablas desde el navegador
│   ├── header.php                # Navbar Bootstrap + sesion de usuario
│   ├── footer.php                # Footer + scripts Bootstrap JS
│   ├── login.php                 # Inicio de sesion por email
│   ├── logout.php                # Cerrar sesion
│   ├── index.php                 # Catalogo de videojuegos (vista publica)
│   ├── registrar_juego.php       # Formulario para registrar videojuegos
│   ├── registrar_usuario.php     # Formulario para registrar usuarios
│   ├── registrar_resena.php      # Formulario independiente de resenas
│   ├── ver.php                   # Detalle de juego + resenas + estrellas
│   ├── css/style.css             # Estilos personalizados (tema PlayStation)
│   ├── composer.json             # Dependencias PHP (MongoDB)
│   └── .env.example              # Variables de entorno de ejemplo
│
└── analytics/                    # Servicio Python Flask
    ├── app.py                    # API REST con Flask + MongoDB
    ├── config.py                 # Configuracion con variables de entorno
    ├── requirements.txt          # Dependencias Python
    ├── Procfile                  # Comando para despliegue en Render
    ├── runtime.txt               # Version de Python
    └── .env.example              # Variables de entorno de ejemplo
```

---

## 3. PUNTO 1: BASE DE DATOS PostgreSQL

### 3.1. Script SQL (schema.sql)

Se disenaron 3 tablas con tipos de datos basicos, llaves primarias (PK) y llaves foraneas (FK):

```sql
CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    edad INTEGER,
    telefono VARCHAR(20),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS videojuegos (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    genero VARCHAR(100) NOT NULL,
    plataforma VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) DEFAULT 0,
    anio_lanzamiento INTEGER,
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

### 3.2. Relaciones

- Un usuario puede escribir muchas resenas (1:N)
- Un videojuego puede tener muchas resenas (1:N)
- La calificacion_promedio se actualiza automaticamente con cada resena

**Tipos de datos usados:** SERIAL, VARCHAR, TEXT, INTEGER, DECIMAL, TIMESTAMP.

---

## 4. PUNTO 2: CONEXION PDO DESDE PHP (db.php)

Se utiliza la clase PDO para conectar PHP con PostgreSQL. La conexion usa variables de entorno para ser compatible con Render:

```php
<?php
$host     = getenv('DB_HOST') ?: 'localhost';
$dbname   = getenv('DB_NAME') ?: 'gamehub';
$usuario  = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'postgres';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usuario, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Error de conexion: ' . $e->getMessage());
}
```

**Conceptos aplicados:**
- PDO (PHP Data Objects) para acceso a bases de datos
- try-catch para manejo de errores
- getenv() para leer variables de entorno en produccion

---

## 5. PUNTO 3: FORMULARIO PARA REGISTRAR VIDEOJUEGOS (registrar_juego.php)

Formulario con campos: titulo, genero, plataforma, precio, ano de lanzamiento y descripcion.

**Caracteristicas:**
- Diseno responsive con Bootstrap 5 (tema PlayStation azul)
- Selects desplegables para genero y plataforma
- Insercion con consultas preparadas PDO para prevenir SQL injection
- Al guardar, envia automaticamente los datos a la API de analitica via cURL

**Consulta preparada:**
```php
$stmt = $pdo->prepare("INSERT INTO videojuegos (titulo, genero, plataforma, descripcion, precio, anio_lanzamiento) VALUES (:titulo, :genero, :plataforma, :descripcion, :precio, :anio)");
$stmt->execute([':titulo' => $titulo, ':genero' => $genero, ...]);
```

---

## 6. PUNTO 4: VALIDACIONES POR CAMPO

Se implementaron validaciones tanto en frontend como en backend:

### Frontend (HTML5):
- Atributo `required` en campos obligatorios
- `minlength` y `maxlength` para limites de texto
- `min`, `max`, `step` para campos numericos
- `type="email"` para validacion de formato de correo

### Backend (PHP):
- Verificacion de campos vacios con `trim()`
- `htmlspecialchars()` para sanitizar entradas y prevenir XSS
- `filter_var($email, FILTER_VALIDATE_EMAIL)` para correos
- `ctype_digit()` para validar numeros enteros
- `in_array()` para validar generos y plataformas permitidos (listas blancas)
- Validacion de rangos (edad 10-120, ano 1970-2030, precio positivo)
- Mensajes de error individuales por campo con alertas Bootstrap

---

## 7. PUNTO 5: FORMULARIO PARA REGISTRAR RESENAS

Se implemento en dos lugares:

### En ver.php (dentro del detalle del juego):
- El usuario se detecta automaticamente por sesion (sin seleccion manual)
- Sistema de estrellas interactivo con CSS puro (hover, seleccion, color dorado)
- Textarea con contador de caracteres en tiempo real
- Al enviar, la resena aparece inmediatamente en la lista sin recargar la pagina

### En registrar_resena.php (formulario independiente):
- Select de videojuego cargado desde la base de datos
- Mismas validaciones y sistema de estrellas
- Redirige al dashboard de analitica tras guardar

**Insercion SQL:**
```php
$stmt = $pdo->prepare("INSERT INTO resenas (usuario_id, videojuego_id, calificacion, comentario) VALUES (?, ?, ?, ?)");
$stmt->execute([$_SESSION['usuario_id'], $videojuego_id, $calificacion, $comentario]);
```

---

## 8. PUNTO 6: VISTA PUBLICA DEL CATALOGO (index.php + ver.php)

### index.php - Catalogo principal:
- Muestra todos los videojuegos en tarjetas Bootstrap (grid de 3 columnas)
- Cada tarjeta muestra: titulo, genero, plataforma, precio, ano, calificacion con estrellas
- Efecto hover con sombra azul
- Boton "Ver Detalles" para acceder a las resenas

### ver.php - Detalle de juego:
- Informacion completa del videojuego con calificacion en grande
- Lista de resenas con nombre de usuario (JOIN SQL), estrellas y fecha
- Formulario para escribir nueva resena
- Breadcrumb de navegacion

---

## 9. PUNTO 7: DESPLIEGUE PHP EN RENDER

**URL publica:** `https://gamehub-php2.onrender.com`

**Configuracion:**
- Dockerfile con PHP 8.2-Apache, extensiones PDO PostgreSQL, MongoDB, Composer
- DocumentRoot configurado a `/var/www/html/web/`
- Variables de entorno: DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, ANALYTICS_API_URL
- Base de datos PostgreSQL administrada por Render

**Archivo Dockerfile:**
```dockerfile
FROM php:8.2-apache
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libssl-dev libzip-dev zip unzip \
    && pecl install mongodb && docker-php-ext-enable mongodb \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && a2enmod rewrite
RUN sed -i 's|/var/www/html|/var/www/html/web|g' /etc/apache2/sites-available/000-default.conf
COPY . /var/www/html/
```

---

## 10. PUNTO 8: BASE DE DATOS MongoDB Atlas

Se creo un cluster en MongoDB Atlas para el servicio de analitica.

**Cluster:** `gamehub-cluster.wmnpi9w.mongodb.net`

**Base de datos:** `gamehub_analitica`

**Colecciones:**
- `videojuegos`: Almacena los juegos registrados con sus IDs
- `reportes_resenas`: Almacena cada resena con calificacion, comentario, usuario y fecha

---

## 11. PUNTO 9: MongoDB RESPALDA DATOS DE ANALITICA

La coleccion `reportes_resenas` almacena los datos necesarios para generar estadisticas:

**Estructura del documento:**
```json
{
    "id_videojuego": 7,
    "calificacion": 5,
    "comentario": "Excelente juego",
    "id_usuario": 1,
    "nombre_usuario": "Santiago",
    "fecha": "2026-07-02T15:30:00"
}
```

Con estos datos, usando pipelines de agregacion de MongoDB se calcula:
- Total de resenas por juego ($sum)
- Promedio de calificaciones ($avg)
- Mejor calificacion ($max)
- Peor calificacion ($min)
- Ranking de mejores videojuegos ($sort + $limit)

---

## 12. PUNTO 10: API REST EN FLASK (analytics/app.py)

API desarrollada en Python con el framework Flask.

**Dependencias (requirements.txt):**
```
flask==3.0.0
pymongo==4.6.1
dnspython==2.5.0
gunicorn==21.2.0
```

**Conexion a MongoDB (config.py):**
```python
from urllib.parse import quote_plus
import os

class Config:
    _raw_uri = os.getenv('MONGO_URI', '')
    # Codifica usuario y password para caracteres especiales
    if _raw_uri and '://' in _raw_uri:
        protocolo, resto = _raw_uri.split('://', 1)
        if '@' in resto:
            credenciales, servidor = resto.split('@', 1)
            if ':' in credenciales:
                usuario, password = credenciales.split(':', 1)
                credenciales = f'{quote_plus(usuario)}:{quote_plus(password)}'
            MONGO_URI = f'{protocolo}://{credenciales}@{servidor}'
    MONGO_DB = os.getenv('MONGO_DB', 'gamehub_analitica')
```

**Caracteristicas:**
- Deteccion automatica de navegador vs API: muestra HTML con tablas en navegador, JSON para PHP
- Dashboard visual con Bootstrap 5 al entrar a la URL raiz
- Manejo de errores con mensajes claros
- Timeout de conexion de 5 segundos

---

## 13. PUNTO 11: ENDPOINTS DE LA API

### POST /api/videojuegos
Registra un videojuego en MongoDB. Recibe JSON con id, nombre y genero.
```json
// Entrada
{ "id": 1, "nombre": "FIFA 26", "genero": "Deportes" }
// Salida
{ "mensaje": "Videojuego registrado en analitica", "id": 1 }
```

### GET /api/estadisticas?id=X
Consulta estadisticas de un videojuego especifico.
```json
// Salida
{ "id_videojuego": 1, "nombre": "FIFA 26", "genero": "Deportes",
  "total_resenas": 5, "promedio": 4.2, "mejor_calificacion": 5, "peor_calificacion": 3 }
```

### GET /api/mejores-videojuegos
Devuelve el top 5 de videojuegos mejor calificados.
```json
// Salida
{ "mejores_videojuegos": [
    { "id_videojuego": 7, "nombre": "good", "genero": "terror", "total_resenas": 1, "promedio": 5.0 }
]}
```

### POST /api/calificar
Recibe una calificacion desde PHP y la almacena en MongoDB.
```json
// Entrada
{ "id_videojuego": 1, "calificacion": 5, "comentario": "Buenisimo", "id_usuario": 1, "nombre_usuario": "Santiago" }
```

---

## 14. PUNTO 12: DESPLIEGUE API PYTHON EN RENDER

**URL publica:** `https://gamehub-analytics-api-t87e.onrender.com`

**Configuracion:**
- Procfile: `web: gunicorn app:app`
- runtime.txt: `python-3.12`
- Variable de entorno: MONGO_URI con la cadena de conexion de MongoDB Atlas
- Build: `pip install -r requirements.txt`

**Dashboard visual:**
Al entrar a la URL raiz se muestra un panel con:
- Contadores (videojuegos, resenas totales, top calificados)
- Tabla Top 5 mejores videojuegos con estrellas doradas
- Tabla de videojuegos registrados con boton "Ver Stats"
- Formulario para sincronizar juegos manualmente
- Accesos rapidos a todas las vistas

**Vistas HTML adicionales:**
- `/mejores` - Top 10 con trofeos (oro, plata, bronce) y estrellas
- `/estadisticas?id=X` - Detalle completo con contadores, estrellas y tabla de resenas

---

## 15. PUNTO 13: FLUJO COMPLETO DEL PROCESO

Cuando un usuario registra una nueva resena en la aplicacion:

```
1. USUARIO escribe resena en el formulario (ver.php o registrar_resena.php)
       |
2. PHP VALIDA los datos (campos obligatorios, rango 1-5, longitud)
       |
3. PHP GUARDA en PostgreSQL (tabla resenas)
       |
4. PHP ACTUALIZA calificacion_promedio en videojuegos (UPDATE con AVG)
       |
5. PHP CONSUME API Python via cURL
   POST /api/calificar con JSON: {id_videojuego, calificacion, comentario, id_usuario, nombre_usuario}
       |
6. FLASK RECIBE la peticion en el endpoint /api/calificar
       |
7. FLASK GUARDA en MongoDB Atlas (coleccion reportes_resenas)
       |
8. USUARIO/PROFESOR consulta estadisticas en:
   - /api/estadisticas?id=X -> JSON (para integracion)
   - /estadisticas?id=X -> Vista HTML con tablas
   - /api/mejores-videojuegos -> JSON
   - /mejores -> Vista HTML con ranking
```

**Integracion PHP -> Flask (api_config.php + cURL):**
```php
$ch = curl_init($api_analitica_url . '/api/calificar');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'id_videojuego' => (int) $videojuego_id,
    'calificacion' => (int) $calificacion,
    'comentario' => $comentario,
    'id_usuario' => (int) $_SESSION['usuario_id'],
    'nombre_usuario' => $_SESSION['usuario_nombre']
]));
curl_exec($ch);
curl_close($ch);
```

---

## 16. URLS PUBLICAS

| Servicio | URL |
|----------|-----|
| App Web PHP | `https://gamehub-php2.onrender.com` |
| API Analitica | `https://gamehub-analytics-api-t87e.onrender.com` |
| Dashboard Analitica | `https://gamehub-analytics-api-t87e.onrender.com` |
| Mejores Videojuegos | `https://gamehub-analytics-api-t87e.onrender.com/mejores` |
| Estadisticas (HTML) | `https://gamehub-analytics-api-t87e.onrender.com/estadisticas?id=1` |
| Estadisticas (JSON) | `https://gamehub-analytics-api-t87e.onrender.com/api/estadisticas?id=1` |

---

## 17. TECNOLOGIAS UTILIZADAS

| Tecnologia | Uso |
|------------|-----|
| PHP 8.2 | Aplicacion web principal |
| PostgreSQL | Base de datos relacional |
| PDO | Conexion segura a BD desde PHP |
| Bootstrap 5.3 | Framework CSS para diseno responsive |
| Bootstrap Icons | Iconografia |
| Python 3.12 | Servicio de analitica |
| Flask 3.0 | Framework web para API REST |
| PyMongo | Driver de MongoDB para Python |
| MongoDB Atlas | Base de datos NoSQL en la nube |
| Gunicorn | Servidor WSGI para produccion |
| Docker | Contenedor para despliegue en Render |
| cURL (PHP) | Consumo de API REST entre servicios |
| Git / GitHub | Control de versiones |
| Render | Plataforma de despliegue cloud |
| Selenium | Automatizacion de pruebas web |

---

## 18. PUNTO 14: PRUEBAS AUTOMATIZADAS CON SELENIUM

Archivo principal: `tests/test_gamehub.py`
Dependencias: `tests/requirements.txt`

---

### 18.1. PRUEBA 1: REGISTRO DE VIDEOJUEGO

**Objetivo:** Verificar que el formulario de registro de videojuegos funciona correctamente.

**Pasos que ejecuta la prueba:**
1. Abre el navegador y entra a `https://gamehub-php2.onrender.com`
2. Hace clic en "Registrar Juego" en el menu de navegacion
3. Espera que cargue el formulario
4. Llena el campo "Titulo" con "Juego Prueba Selenium"
5. Selecciona "Accion" en el campo "Genero"
6. Selecciona "PC" en el campo "Plataforma"
7. Ingresa "59.99" en el campo "Precio"
8. Ingresa "2026" en el campo "Año de Lanzamiento"
9. Escribe una descripcion de prueba
10. Hace clic en "Guardar Videojuego"
11. Verifica que aparezca la palabra "exitosamente" en la pagina

**Resultado esperado:** El sistema muestra mensaje de registro exitoso. El videojuego queda guardado en PostgreSQL.

**Codigo de la prueba:** Funcion `prueba_1_registro_videojuego()` en `test_gamehub.py`.

---

### 18.2. PRUEBA 2: REGISTRO DE RESEÑA

**Objetivo:** Verificar que se puede escribir una reseña con calificacion en estrellas.

**Pasos que ejecuta la prueba:**
1. Entra a la pagina principal del catalogo
2. Busca el primer videojuego y hace clic en "Ver Detalles"
3. Si el usuario no tiene sesion iniciada, se registra automaticamente (nombre, email unico, edad, telefono) y vuelve a intentar
4. En el formulario de reseña, hace clic en la segunda estrella (calificacion 4)
5. Escribe "Reseña automatizada con Selenium. Buen juego para testing."
6. Hace clic en "Enviar Reseña"
7. Verifica que aparezca "publicada" en la pagina

**Resultado esperado:** La reseña aparece en la lista de comentarios del videojuego. Se guarda en PostgreSQL y se envia a MongoDB via API Flask.

**Codigo de la prueba:** Funcion `prueba_2_registro_resena()` en `test_gamehub.py`.

---

### 18.3. PRUEBA 3: CONSULTA DE CATALOGO

**Objetivo:** Verificar que la pagina principal muestra correctamente los videojuegos registrados.

**Pasos que ejecuta la prueba:**
1. Entra a `https://gamehub-php2.onrender.com`
2. Espera que cargue la pagina completamente
3. Busca todos los elementos con clase CSS `.card-title` (titulos de las tarjetas)
4. Cuenta cuantos videojuegos se encontraron
5. Muestra en consola los primeros 5 titulos encontrados

**Resultado esperado:** La pagina muestra tarjetas con el titulo, genero, plataforma y calificacion de cada videojuego. Si el catalogo esta vacio, lo indica.

**Codigo de la prueba:** Funcion `prueba_3_consulta_catalogo()` en `test_gamehub.py`.

---

### 18.4. PRUEBA 4: CONSULTA DE ESTADISTICAS

**Objetivo:** Verificar que el dashboard de analitica y sus endpoints muestran datos correctamente.

**Sub-prueba 4a - Dashboard:**
- Entra a `https://gamehub-analytics-api-t87e.onrender.com`
- Verifica que carguen las tarjetas con contadores (videojuegos, reseñas, top)

**Sub-prueba 4b - Mejores Videojuegos:**
- Entra a `/mejores`
- Verifica que la tabla HTML muestre los juegos con estrellas, genero y promedio
- Cuenta las filas de la tabla

**Sub-prueba 4c - Estadisticas por ID:**
- Entra a `/estadisticas?id=1`
- Si el juego no esta sincronizado, lo indica
- Si existe, verifica los contadores de reseñas, promedio, mejor y peor

**Resultado esperado:** El dashboard muestra datos reales de MongoDB. Las tablas de ranking y estadisticas se ven con formato Bootstrap y estrellas.

**Codigo de la prueba:** Funcion `prueba_4_consulta_estadisticas()` en `test_gamehub.py`.

---

### 18.5. EJECUCION DE LAS PRUEBAS

**Requisitos:**
- Python 3 instalado
- Chrome o Edge instalado
- Conexion a internet

**Instalacion (solo primera vez):**
```bash
cd tests
pip install selenium webdriver-manager
```

**Ejecutar todas las pruebas:**
```bash
python test_gamehub.py
```

**Ejecutar una prueba individual:** Editar `test_gamehub.py` y comentar las lineas de las pruebas que no se quieran ejecutar en la seccion final del archivo.

**Evidencia:** Tomar captura de pantalla de la terminal mostrando los resultados de cada prueba.

---

## 19. PUNTO 15: PRUEBAS DE CARGA CON LOCUST

Archivo: `tests/locustfile.py`
Dependencias: `tests/requirements-locust.txt`

Se implementaron 2 escenarios de carga que simulan multiples usuarios concurrentes.

---

### 19.1. PRUEBA 1: CONSULTA DE VIDEOJUEGOS (Catalogo PHP)

**Objetivo:** Medir el rendimiento de la aplicacion PHP cuando varios usuarios consultan el catalogo.

**Pasos:**
1. Instalar Locust: `pip install locust`
2. Ejecutar: `locust -f locustfile.py --host=https://gamehub-php2.onrender.com CatalogoUser`
3. Abrir `http://localhost:8089` en el navegador
4. Configurar: 50 usuarios, 5 usuarios por segundo
5. Iniciar la prueba

**Que simula:**
- Usuarios entrando a la pagina principal (GET /)
- Usuarios viendo el detalle de juegos aleatorios (GET /ver.php?id=X)

**Metricas a analizar:**
- Tiempo de respuesta promedio (ms)
- Throughput (peticiones/segundo)
- Porcentaje de errores (%)

---

### 19.2. PRUEBA 2: CONSULTA DE ESTADISTICAS (API Flask)

**Objetivo:** Medir el rendimiento de la API cuando se consultan estadisticas.

**Pasos:**
1. Ejecutar: `locust -f locustfile.py --host=https://gamehub-analytics-api-t87e.onrender.com ApiUser`
2. Abrir `http://localhost:8089` en el navegador
3. Configurar: 50 usuarios, 5 usuarios por segundo
4. Iniciar la prueba

**Que simula:**
- Consultas a `/api/estadisticas?id=X` con IDs aleatorios (tarea frecuente)
- Consultas a `/api/mejores-videojuegos` (tarea frecuente)
- Envio de reseñas via POST `/api/calificar` (tarea menos frecuente)

**Metricas a analizar:**
- Tiempo de respuesta promedio por endpoint
- Throughput total de la API
- Porcentaje de errores (503 si MongoDB no disponible, 404 si juego no existe)

---

### 19.3. PRUEBA 3: REGISTRO DE RESEÑAS (POST a API)

Incluida dentro de la prueba 19.2 como la tarea de menor frecuencia (1 de cada 6 peticiones envia una reseña). Esto simula el comportamiento real: mas consultas que escrituras.

**Metricas a analizar:**
- Tiempo de respuesta del POST
- Tasa de error en escrituras
- Comportamiento bajo carga concurrente

---

### 19.4. EJECUCION RAPIDA

```bash
cd tests
pip install locust

# Prueba PHP (catalogo)
locust -f locustfile.py --host=https://gamehub-php2.onrender.com CatalogoUser

# Prueba API (estadisticas + reseñas)
locust -f locustfile.py --host=https://gamehub-analytics-api-t87e.onrender.com ApiUser
```

En ambos casos, abrir `http://localhost:8089`, configurar usuarios, y darle Start.

**Evidencia:** Captura de pantalla de la interfaz web de Locust mostrando las graficas de: tiempo de respuesta, throughput, y porcentaje de errores.

---

## 20. PUNTO 16: PRUEBA DE INTEGRACION CON POSTMAN

Se verifico la comunicacion PHP -> Flask -> MongoDB con 5 pruebas desde terminal:

| # | Prueba | Comando curl | Resultado |
|---|--------|-------------|-----------|
| 1 | Registrar juego | `curl -X POST .../api/videojuegos -d '{"id":1,"nombre":"FIFA 26","genero":"Deportes"}'` | 201 Creado |
| 2 | Registrar reseña | `curl -X POST .../api/calificar -d '{"id_videojuego":1,"calificacion":5,...}'` | 201 Creado |
| 3 | Estadisticas | `curl .../api/estadisticas?id=1` | JSON con total_resenas, promedio |
| 4 | Mejores juegos | `curl .../api/mejores-videojuegos` | JSON con top 5 |
| 5 | Health check | `curl .../` | HTML dashboard con datos |

**Flujo comprobado:** PHP envia -> Flask recibe -> MongoDB almacena -> API consulta -> Devuelve estadisticas.

---

## 21. REFERENCIAS

- [Documentacion de PDO en PHP](https://www.php.net/manual/es/book.pdo.php)
- [Documentacion de PostgreSQL](https://www.postgresql.org/docs/)
- [Documentacion de Bootstrap 5](https://getbootstrap.com/docs/5.3/)
- [Documentacion de Flask](https://flask.palletsprojects.com/)
- [Documentacion de PyMongo](https://pymongo.readthedocs.io/)
- [Documentacion de MongoDB Atlas](https://www.mongodb.com/docs/atlas/)
- [Documentacion de Render](https://render.com/docs)

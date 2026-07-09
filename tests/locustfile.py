"""
PRUEBAS DE CARGA CON LOCUST - PROYECTO GAMEHUB
==============================================
Punto 15 del plan de mejoramiento.

USO:
  Para PHP (catalogo): locust -f locustfile.py --host=https://gamehub-php2.onrender.com CatalogoUser
  Para API (estadisticas + reseñas): locust -f locustfile.py --host=https://gamehub-analytics-api-t87e.onrender.com ApiUser

Luego abre http://localhost:8089 en el navegador, configura usuarios y ejecuta.
"""

from locust import HttpUser, task, between
import random


class CatalogoUser(HttpUser):
    """PRUEBA 1: Consulta de videojuegos - PHP App"""
    wait_time = between(1, 2)

    @task
    def ver_catalogo(self):
        self.client.get("/", name="Catalogo - Inicio")

    @task
    def ver_detalle(self):
        id_juego = random.randint(1, 15)
        self.client.get(f"/ver.php?id={id_juego}", name="Catalogo - Detalle Juego")


class ApiUser(HttpUser):
    """PRUEBA 2 y 3: API de analitica - Flask"""
    wait_time = between(1, 3)

    @task(3)
    def consultar_estadisticas(self):
        """PRUEBA 2: Consulta de estadisticas por ID"""
        id_juego = random.randint(1, 15)
        self.client.get(f"/api/estadisticas?id={id_juego}", name="API - Estadisticas")

    @task(2)
    def consultar_mejores(self):
        """PRUEBA 2b: Consulta de mejores videojuegos"""
        self.client.get("/api/mejores-videojuegos", name="API - Mejores")

    @task(1)
    def enviar_resena(self):
        """PRUEBA 3: Registro de reseña (envio a API)"""
        datos = {
            "id_videojuego": random.randint(1, 15),
            "calificacion": random.randint(1, 5),
            "comentario": f"Reseña carga #{random.randint(1000, 9999)}",
            "id_usuario": 1,
            "nombre_usuario": "Tester"
        }
        self.client.post("/api/calificar", json=datos, name="API - Registrar Reseña")

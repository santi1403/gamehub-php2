from flask import Flask, request, jsonify
from pymongo import MongoClient
from datetime import datetime
import os

app = Flask(__name__)

mongo_uri = os.getenv('MONGO_URI', 'mongodb://localhost:27017/')
cliente = MongoClient(mongo_uri)
db = cliente['gamehub_analitica']

@app.route('/')
def index():
    return jsonify({'servicio': 'GameHub - Servicio de Analitica', 'estado': 'activo'})

@app.route('/api/registrar', methods=['POST'])
def registrar_videojuego():
    datos = request.get_json()
    
    if not datos or 'id' not in datos or 'nombre' not in datos:
        return jsonify({'error': 'Datos incompletos'}), 400
    
    videojuego = {
        'id_videojuego': datos['id'],
        'nombre': datos['nombre'],
        'genero': datos.get('genero', ''),
        'total_resenas': 0,
        'suma_calificaciones': 0,
        'promedio': 0,
        'registros': []
    }
    
    db.videojuegos.insert_one(videojuego)
    return jsonify({'mensaje': 'Videojuego registrado en analitica', 'id': datos['id']})

@app.route('/api/calificar', methods=['POST'])
def calificar_videojuego():
    datos = request.get_json()
    
    if not datos or 'id_videojuego' not in datos or 'calificacion' not in datos:
        return jsonify({'error': 'Datos incompletos'}), 400
    
    id_videojuego = datos['id_videojuego']
    calificacion = int(datos['calificacion'])
    
    videojuego = db.videojuegos.find_one({'id_videojuego': id_videojuego})
    
    if videojuego:
        nuevo_total = videojuego['total_resenas'] + 1
        nueva_suma = videojuego['suma_calificaciones'] + calificacion
        nuevo_promedio = nueva_suma / nuevo_total
        
        db.videojuegos.update_one(
            {'id_videojuego': id_videojuego},
            {
                '$set': {
                    'total_resenas': nuevo_total,
                    'suma_calificaciones': nueva_suma,
                    'promedio': round(nuevo_promedio, 1)
                },
                '$push': {
                    'registros': {
                        'calificacion': calificacion,
                        'fecha': datetime.now().isoformat()
                    }
                }
            }
        )
    else:
        db.videojuegos.insert_one({
            'id_videojuego': id_videojuego,
            'nombre': datos.get('nombre', 'Desconocido'),
            'genero': datos.get('genero', ''),
            'total_resenas': 1,
            'suma_calificaciones': calificacion,
            'promedio': calificacion,
            'registros': [{'calificacion': calificacion, 'fecha': datetime.now().isoformat()}]
        })
    
    return jsonify({'mensaje': 'Calificacion registrada', 'id_videojuego': id_videojuego})

@app.route('/api/estadisticas', methods=['GET'])
def obtener_estadisticas():
    id_videojuego = request.args.get('id', type=int)
    
    if not id_videojuego:
        return jsonify({'error': 'Se requiere id del videojuego'}), 400
    
    videojuego = db.videojuegos.find_one({'id_videojuego': id_videojuego})
    
    if not videojuego:
        return jsonify({'error': 'Videojuego no encontrado en analitica'}), 404
    
    videojuego['_id'] = str(videojuego['_id'])
    
    mejores = list(db.videojuegos.find().sort('promedio', -1).limit(5))
    for m in mejores:
        m['_id'] = str(m['_id'])
    
    return jsonify({
        'id_videojuego': id_videojuego,
        'nombre': videojuego['nombre'],
        'total_resenas': videojuego['total_resenas'],
        'promedio': videojuego['promedio'],
        'mejor_calificacion': videojuego.get('promedio', 0),
        'top_mejores': mejores
    })

if __name__ == '__main__':
    port = int(os.getenv('PORT', 5000))
    app.run(host='0.0.0.0', port=port)

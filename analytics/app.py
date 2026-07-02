from flask import Flask, request, jsonify
from pymongo import MongoClient
from pymongo.errors import PyMongoError
from config import Config
from datetime import datetime
import os

app = Flask(__name__)
mongo_error = None

try:
    cliente = MongoClient(Config.MONGO_URI, serverSelectionTimeoutMS=5000)
    db = cliente[Config.MONGO_DB]
    db.command('ping')
except PyMongoError as e:
    db = None
    mongo_error = str(e)


@app.route('/')
def index():
    if db is not None:
        return jsonify({'servicio': 'GameHub - API de Analitica', 'estado': 'conectado'})

    hint = ''
    if 'bad auth' in str(mongo_error).lower() or 'authentication failed' in str(mongo_error).lower():
        hint = 'Verifica que MONGO_URI tenga usuario y password correctos en las variables de entorno de Render'

    return jsonify({
        'servicio': 'GameHub - API de Analitica',
        'estado': 'error: sin conexion a MongoDB',
        'detalle': mongo_error,
        'ayuda': hint
    })


@app.route('/api/videojuegos', methods=['POST'])
def registrar_videojuego():
    if db is None:
        return jsonify({'error': 'Servicio no disponible'}), 503

    datos = request.get_json(silent=True)
    if not datos:
        return jsonify({'error': 'Cuerpo JSON requerido'}), 400

    id_videojuego = datos.get('id')
    nombre = datos.get('nombre')
    genero = datos.get('genero', '')

    if not id_videojuego or not nombre:
        return jsonify({'error': 'Campos requeridos: id, nombre'}), 400

    existente = db.videojuegos.find_one({'id_videojuego': int(id_videojuego)})
    if existente:
        return jsonify({'mensaje': 'Videojuego ya registrado', 'id': id_videojuego})

    db.videojuegos.insert_one({
        'id_videojuego': int(id_videojuego),
        'nombre': nombre,
        'genero': genero,
        'fecha_registro': datetime.now().isoformat()
    })
    return jsonify({'mensaje': 'Videojuego registrado en analitica', 'id': id_videojuego}), 201


@app.route('/api/estadisticas', methods=['GET'])
def obtener_estadisticas():
    if db is None:
        return jsonify({'error': 'Servicio no disponible'}), 503

    id_videojuego = request.args.get('id', type=int)
    if not id_videojuego:
        return jsonify({'error': 'Parametro ?id= requerido'}), 400

    juego = db.videojuegos.find_one({'id_videojuego': id_videojuego})
    if not juego:
        return jsonify({'error': 'Videojuego no encontrado'}), 404

    pipeline = [
        {'$match': {'id_videojuego': id_videojuego}},
        {'$group': {
            '_id': '$id_videojuego',
            'total_resenas': {'$sum': 1},
            'promedio': {'$avg': '$calificacion'},
            'mejor': {'$max': '$calificacion'},
            'peor': {'$min': '$calificacion'}
        }}
    ]
    resultado = list(db.reportes_resenas.aggregate(pipeline))

    if resultado:
        stats = resultado[0]
        return jsonify({
            'id_videojuego': id_videojuego,
            'nombre': juego.get('nombre', ''),
            'genero': juego.get('genero', ''),
            'total_resenas': stats['total_resenas'],
            'promedio': round(stats['promedio'], 1),
            'mejor_calificacion': stats['mejor'],
            'peor_calificacion': stats['peor']
        })

    pipeline_fecha = [
        {'$match': {'id_videojuego': id_videojuego}},
        {'$sort': {'fecha': -1}},
        {'$limit': 10}
    ]
    ultimas = list(db.reportes_resenas.aggregate(pipeline_fecha))
    for u in ultimas:
        u['_id'] = str(u['_id'])

    return jsonify({
        'id_videojuego': id_videojuego,
        'nombre': juego.get('nombre', ''),
        'genero': juego.get('genero', ''),
        'total_resenas': len(ultimas),
        'promedio': 0,
        'mejor_calificacion': 0,
        'peor_calificacion': 0,
        'ultimas_resenas': ultimas
    })


@app.route('/api/mejores-videojuegos', methods=['GET'])
def mejores_videojuegos():
    if db is None:
        return jsonify({'error': 'Servicio no disponible'}), 503

    pipeline = [
        {'$group': {
            '_id': '$id_videojuego',
            'total_resenas': {'$sum': 1},
            'promedio': {'$avg': '$calificacion'}
        }},
        {'$sort': {'promedio': -1}},
        {'$limit': 5}
    ]
    resultado = list(db.reportes_resenas.aggregate(pipeline))

    top = []
    for item in resultado:
        juego = db.videojuegos.find_one({'id_videojuego': item['_id']})
        top.append({
            'id_videojuego': item['_id'],
            'nombre': juego['nombre'] if juego else 'Desconocido',
            'genero': juego['genero'] if juego else '',
            'total_resenas': item['total_resenas'],
            'promedio': round(item['promedio'], 1)
        })

    return jsonify({'mejores_videojuegos': top})


@app.route('/api/calificar', methods=['POST'])
def calificar_videojuego():
    if db is None:
        return jsonify({'error': 'Servicio no disponible'}), 503

    datos = request.get_json(silent=True)
    if not datos:
        return jsonify({'error': 'Cuerpo JSON requerido'}), 400

    id_videojuego = datos.get('id_videojuego')
    calificacion = datos.get('calificacion')
    comentario = datos.get('comentario', '')
    id_usuario = datos.get('id_usuario', 0)
    nombre_usuario = datos.get('nombre_usuario', '')

    if not id_videojuego or not calificacion:
        return jsonify({'error': 'Campos requeridos: id_videojuego, calificacion'}), 400

    db.reportes_resenas.insert_one({
        'id_videojuego': int(id_videojuego),
        'calificacion': int(calificacion),
        'comentario': comentario,
        'id_usuario': int(id_usuario),
        'nombre_usuario': nombre_usuario,
        'fecha': datetime.now().isoformat()
    })

    return jsonify({'mensaje': 'Calificacion registrada en analitica', 'id_videojuego': id_videojuego}), 201


if __name__ == '__main__':
    port = int(os.getenv('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=False)

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


HTML_HEADER = '''<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameHub - Analitica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #0a0e27 0%, #121a3a 100%); min-height:100vh; }
        .navbar { background: linear-gradient(90deg, #003087 0%, #0070CC 100%) !important; }
        .card { border:1px solid rgba(0,112,204,0.3) !important; transition: transform 0.3s, box-shadow 0.3s; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,112,204,0.3) !important; }
        .estrellas { color: #FFD700; }
        .table-dark { --bs-table-bg: #1a1a2e; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark shadow">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="/"><i class="bi bi-playstation"></i> GameHub Analitica</a>
        <span class="navbar-text text-light"><i class="bi bi-graph-up"></i> Panel de Estadisticas</span>
    </div>
</nav>
<div class="container py-4">
'''

HTML_FOOTER = '''</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>'''


@app.route('/')
def index():
    if db is None:
        return HTML_HEADER + '''
            <div class="alert alert-danger text-center py-5">
                <i class="bi bi-exclamation-triangle-fill fs-1"></i>
                <h4 class="mt-2">Error de conexion a MongoDB</h4>
                <p>''' + str(mongo_error) + '''</p>
            </div>''' + HTML_FOOTER

    videojuegos = list(db.videojuegos.find().sort('fecha_registro', -1))
    for v in videojuegos:
        v['_id'] = str(v['_id'])

    pipeline = [
        {'$group': {'_id': '$id_videojuego', 'total_resenas': {'$sum': 1}, 'promedio': {'$avg': '$calificacion'}}},
        {'$sort': {'promedio': -1}}, {'$limit': 5}
    ]
    top = list(db.reportes_resenas.aggregate(pipeline))
    for item in top:
        juego = db.videojuegos.find_one({'id_videojuego': item['_id']})
        item['nombre'] = juego['nombre'] if juego else 'Desconocido'
        item['genero'] = juego['genero'] if juego else ''
        item['promedio'] = round(item['promedio'], 1)

    total_resenas = db.reportes_resenas.count_documents({})

    html = HTML_HEADER + '''
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card bg-dark text-light text-center p-3"><h1 class="text-info">''' + str(len(videojuegos)) + '''</h1><p>Videojuegos</p></div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark text-light text-center p-3"><h1 class="text-info">''' + str(total_resenas) + '''</h1><p>Reseñas Totales</p></div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark text-light text-center p-3"><h1 class="text-info">''' + str(len(top)) + '''</h1><p>Top Calificados</p></div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card bg-dark text-light">
                    <div class="card-header" style="background:linear-gradient(90deg,#003087,#0070CC);">
                        <h5 class="mb-0"><i class="bi bi-controller"></i> Top 5 - Mejores Videojuegos</h5>
                    </div>
                    <div class="card-body p-0">'''

    if top:
        html += '''<table class="table table-dark table-striped mb-0">
                <thead><tr><th>#</th><th>Videojuego</th><th>Genero</th><th>Reseñas</th><th>Promedio</th></tr></thead><tbody>'''
        for i, t in enumerate(top, 1):
            estrellas = ''.join(['<i class="bi bi-star-fill"></i>' if j <= round(t['promedio']) else '<i class="bi bi-star"></i>' for j in range(1, 6)])
            html += f'''<tr><td>{i}</td><td>{t['nombre']}</td><td>{t['genero']}</td><td>{t['total_resenas']}</td><td><span class="estrellas">{estrellas}</span> {t['promedio']}</td></tr>'''
        html += '</tbody></table>'
    else:
        html += '<div class="p-4 text-center text-muted">No hay datos aun. Registra juegos y reseñas en la app principal.</div>'

    html += '''</div></div>
            <div class="col-lg-6">
                <div class="card bg-dark text-light">
                    <div class="card-header" style="background:linear-gradient(90deg,#003087,#0070CC);">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Videojuegos Registrados</h5>
                    </div>
                    <div class="card-body p-0">'''

    if videojuegos:
        html += '''<table class="table table-dark table-striped mb-0">
                <thead><tr><th>ID</th><th>Nombre</th><th>Genero</th><th>Accion</th></tr></thead><tbody>'''
        for v in videojuegos:
            html += f'''<tr><td>{v['id_videojuego']}</td><td>{v['nombre']}</td><td>{v['genero']}</td>
                <td><a href="/api/estadisticas?id={v['id_videojuego']}" target="_blank" class="btn btn-sm btn-outline-info">Ver Stats</a></td></tr>'''
        html += '</tbody></table>'
    else:
        html += '<div class="p-4 text-center text-muted">No hay videojuegos registrados en analitica.</div>'

    html += '''</div></div></div>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-dark text-light">
                    <div class="card-header" style="background:linear-gradient(90deg,#003087,#0070CC);">
                        <h5 class="mb-0"><i class="bi bi-link-45deg"></i> Endpoints de la API</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4"><a href="/api/mejores-videojuegos" target="_blank" class="btn btn-outline-info w-100"><i class="bi bi-trophy"></i> GET /api/mejores-videojuegos</a></div>
                            <div class="col-md-4"><a href="/api/estadisticas?id=1" target="_blank" class="btn btn-outline-info w-100"><i class="bi bi-bar-chart"></i> GET /api/estadisticas?id=1</a></div>
                            <div class="col-md-4"><span class="btn btn-outline-secondary w-100"><i class="bi bi-plus-circle"></i> POST /api/videojuegos</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>'''

    return html + HTML_FOOTER


@app.route('/api/videojuegos', methods=['GET', 'POST'])
def registrar_videojuego():
    if request.method == 'GET':
        if db is None:
            return jsonify({'error': 'Servicio no disponible'}), 503
        videojuegos = list(db.videojuegos.find().sort('fecha_registro', -1))
        for v in videojuegos:
            v['_id'] = str(v['_id'])
        return jsonify(videojuegos)

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
        {'$group': {'_id': '$id_videojuego', 'total_resenas': {'$sum': 1}, 'promedio': {'$avg': '$calificacion'}, 'mejor': {'$max': '$calificacion'}, 'peor': {'$min': '$calificacion'}}}
    ]
    resultado = list(db.reportes_resenas.aggregate(pipeline))

    if resultado:
        stats = resultado[0]
        return jsonify({'id_videojuego': id_videojuego, 'nombre': juego.get('nombre', ''), 'genero': juego.get('genero', ''), 'total_resenas': stats['total_resenas'], 'promedio': round(stats['promedio'], 1), 'mejor_calificacion': stats['mejor'], 'peor_calificacion': stats['peor']})

    return jsonify({'id_videojuego': id_videojuego, 'nombre': juego.get('nombre', ''), 'genero': juego.get('genero', ''), 'total_resenas': 0, 'promedio': 0, 'mejor_calificacion': 0, 'peor_calificacion': 0})


@app.route('/api/mejores-videojuegos', methods=['GET'])
def mejores_videojuegos():
    if db is None:
        return jsonify({'error': 'Servicio no disponible'}), 503

    pipeline = [
        {'$group': {'_id': '$id_videojuego', 'total_resenas': {'$sum': 1}, 'promedio': {'$avg': '$calificacion'}}},
        {'$sort': {'promedio': -1}}, {'$limit': 5}
    ]
    resultado = list(db.reportes_resenas.aggregate(pipeline))
    top = []
    for item in resultado:
        juego = db.videojuegos.find_one({'id_videojuego': item['_id']})
        top.append({'id_videojuego': item['_id'], 'nombre': juego['nombre'] if juego else 'Desconocido', 'genero': juego['genero'] if juego else '', 'total_resenas': item['total_resenas'], 'promedio': round(item['promedio'], 1)})
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

    db.reportes_resenas.insert_one({'id_videojuego': int(id_videojuego), 'calificacion': int(calificacion), 'comentario': comentario, 'id_usuario': int(id_usuario), 'nombre_usuario': nombre_usuario, 'fecha': datetime.now().isoformat()})
    return jsonify({'mensaje': 'Calificacion registrada en analitica', 'id_videojuego': id_videojuego}), 201


if __name__ == '__main__':
    port = int(os.getenv('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=False)

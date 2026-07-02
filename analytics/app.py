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
                <td><a href="/estadisticas?id={v['id_videojuego']}" class="btn btn-sm btn-outline-info">Ver Stats</a></td></tr>'''
        html += '</tbody></table>'
    else:
        html += '<div class="p-4 text-center text-muted">No hay videojuegos registrados en analitica.</div>'

    html += '''</div></div></div>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-dark text-light">
                    <div class="card-header" style="background:linear-gradient(90deg,#003087,#0070CC);">
                        <h5 class="mb-0"><i class="bi bi-link-45deg"></i> Accesos Rapidos</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4"><a href="/mejores" class="btn btn-outline-info w-100"><i class="bi bi-trophy"></i> Mejores (Vista)</a></div>
                            <div class="col-md-4"><a href="/estadisticas?id=1" class="btn btn-outline-info w-100"><i class="bi bi-bar-chart"></i> Estadisticas (Vista)</a></div>
                            <div class="col-md-4"><a href="/api/estadisticas?id=1" target="_blank" class="btn btn-outline-info w-100"><i class="bi bi-filetype-json"></i> Estadisticas (JSON)</a></div>
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


@app.route('/estadisticas')
def pagina_estadisticas():
    if db is None:
        return HTML_HEADER + '<div class="alert alert-danger text-center py-5"><h4>Error de conexion a MongoDB</h4></div>' + HTML_FOOTER

    id_videojuego = request.args.get('id', type=int)
    if not id_videojuego:
        return HTML_HEADER + '''
            <div class="row justify-content-center mt-5">
                <div class="col-md-6">
                    <div class="card bg-dark text-light text-center border-warning">
                        <div class="card-body py-5">
                            <i class="bi bi-question-circle fs-1 text-warning"></i>
                            <h4 class="mt-3">Especifica un ID de videojuego</h4>
                            <p class="text-muted">Ejemplo: /estadisticas?id=7</p>
                            <a href="/" class="btn btn-outline-light">Volver al Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>''' + HTML_FOOTER

    juego = db.videojuegos.find_one({'id_videojuego': id_videojuego})
    if not juego:
        return HTML_HEADER + f'''
            <div class="row justify-content-center mt-5">
                <div class="col-md-6">
                    <div class="card bg-dark text-light text-center border-warning">
                        <div class="card-body py-5">
                            <i class="bi bi-emoji-frown fs-1 text-warning"></i>
                            <h4 class="mt-3">Videojuego #{id_videojuego} no encontrado</h4>
                            <p class="text-muted">Este juego no esta registrado en el servicio de analitica. Registra juegos y reseñas desde la app principal:</p>
                            <a href="https://gamehub-php2.onrender.com" target="_blank" class="btn btn-primary-play btn-lg">
                                <i class="bi bi-controller"></i> Ir a GameHub App
                            </a>
                            <a href="/" class="btn btn-outline-light mt-2">Volver al Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>''' + HTML_FOOTER

    pipeline = [
        {'$match': {'id_videojuego': id_videojuego}},
        {'$group': {'_id': '$id_videojuego', 'total_resenas': {'$sum': 1}, 'promedio': {'$avg': '$calificacion'}, 'mejor': {'$max': '$calificacion'}, 'peor': {'$min': '$calificacion'}}}
    ]
    resultado = list(db.reportes_resenas.aggregate(pipeline))
    stats = resultado[0] if resultado else {'total_resenas': 0, 'promedio': 0, 'mejor': 0, 'peor': 0}

    resenas = list(db.reportes_resenas.find({'id_videojuego': id_videojuego}).sort('fecha', -1).limit(20))
    for r in resenas:
        r['_id'] = str(r['_id'])

    estrellas_promedio = ''.join(['<i class="bi bi-star-fill"></i>' if j <= round(stats['promedio']) else '<i class="bi bi-star"></i>' for j in range(1, 6)])

    html = HTML_HEADER + f'''
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="/" class="text-info">Dashboard</a></li><li class="breadcrumb-item active text-light">Estadisticas</li></ol></nav>
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card bg-dark text-light">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background:linear-gradient(90deg,#003087,#0070CC);">
                        <h4 class="mb-0"><i class="bi bi-controller"></i> {juego['nombre']}</h4>
                        <span class="badge bg-primary fs-6">{juego['genero']}</span>
                    </div>
                    <div class="card-body">
                        <div class="row text-center g-3">
                            <div class="col-3"><div class="p-3 rounded" style="background:#121a3a"><h2 class="text-info">{stats['total_resenas']}</h2><small>Reseñas</small></div></div>
                            <div class="col-3"><div class="p-3 rounded" style="background:#121a3a"><h2 class="text-info">{round(stats['promedio'],1)}</h2><small>Promedio</small></div></div>
                            <div class="col-3"><div class="p-3 rounded" style="background:#121a3a"><h2 class="text-success">{stats['mejor']}</h2><small>Mejor</small></div></div>
                            <div class="col-3"><div class="p-3 rounded" style="background:#121a3a"><h2 class="text-danger">{stats['peor']}</h2><small>Peor</small></div></div>
                        </div>
                        <div class="text-center mt-3">
                            <span class="estrellas fs-3">{estrellas_promedio}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card bg-dark text-light h-100">
                    <div class="card-header" style="background:linear-gradient(90deg,#003087,#0070CC);"><h5 class="mb-0"><i class="bi bi-info-circle"></i> Informacion</h5></div>
                    <div class="card-body">
                        <p><strong>ID:</strong> {id_videojuego}</p>
                        <p><strong>Nombre:</strong> {juego['nombre']}</p>
                        <p><strong>Genero:</strong> {juego['genero']}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="card bg-dark text-light">
            <div class="card-header" style="background:linear-gradient(90deg,#003087,#0070CC);"><h5 class="mb-0"><i class="bi bi-chat-dots"></i> Ultimas Reseñas</h5></div>
            <div class="card-body p-0">
                <table class="table table-dark table-striped mb-0">
                    <thead><tr><th>Usuario</th><th>Calificacion</th><th>Comentario</th><th>Fecha</th></tr></thead><tbody>'''

    if resenas:
        for r in resenas:
            estrellas = ''.join(['<i class="bi bi-star-fill"></i>' if j <= r['calificacion'] else '<i class="bi bi-star"></i>' for j in range(1, 6)])
            comentario = r.get('comentario', '')[:100] + ('...' if len(r.get('comentario', '')) > 100 else '')
            fecha = r.get('fecha', '')[:10]
            html += f'''<tr><td>{r.get('nombre_usuario', 'Anonimo')}</td><td><span class="estrellas">{estrellas}</span></td><td>{comentario}</td><td>{fecha}</td></tr>'''
    else:
        html += '<tr><td colspan="4" class="text-center text-muted py-3">Sin reseñas registradas</td></tr>'

    html += '''</tbody></table></div></div>'''
    return html + HTML_FOOTER


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


@app.route('/mejores')
def pagina_mejores():
    if db is None:
        return HTML_HEADER + '<div class="alert alert-danger text-center py-5"><h4>Error de conexion a MongoDB</h4></div>' + HTML_FOOTER

    pipeline = [
        {'$group': {'_id': '$id_videojuego', 'total_resenas': {'$sum': 1}, 'promedio': {'$avg': '$calificacion'}}},
        {'$sort': {'promedio': -1}}, {'$limit': 10}
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

    trofeos = ['<i class="bi bi-trophy-fill text-warning"></i>', '<i class="bi bi-trophy-fill text-secondary"></i>', '<i class="bi bi-trophy-fill" style="color:#cd7f32;"></i>']

    html = HTML_HEADER + '''
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="/" class="text-info">Dashboard</a></li><li class="breadcrumb-item active text-light">Mejores Videojuegos</li></ol></nav>
        <div class="card bg-dark text-light">
            <div class="card-header" style="background:linear-gradient(90deg,#003087,#0070CC);">
                <h4 class="mb-0"><i class="bi bi-trophy-fill text-warning"></i> Top 10 - Mejores Videojuegos</h4>
            </div>
            <div class="card-body p-0">
                <table class="table table-dark table-striped mb-0">
                    <thead><tr><th>#</th><th>Videojuego</th><th>Genero</th><th>Reseñas</th><th>Calificacion</th></tr></thead><tbody>'''

    if top:
        for i, t in enumerate(top):
            puesto = trofeos[i] if i < 3 else str(i + 1)
            estrellas = ''.join(['<i class="bi bi-star-fill"></i>' if j <= round(t['promedio']) else '<i class="bi bi-star"></i>' for j in range(1, 6)])
            html += f'''<tr><td class="fs-5">{puesto}</td><td><strong>{t['nombre']}</strong></td><td><span class="badge bg-primary">{t['genero']}</span></td><td>{t['total_resenas']}</td><td><span class="estrellas">{estrellas}</span> <strong>{t['promedio']}</strong></td></tr>'''
    else:
        html += '<tr><td colspan="5" class="text-center text-muted py-4">No hay datos. Registra reseñas en la app principal.</td></tr>'

    html += '''</tbody></table></div></div>'''
    return html + HTML_FOOTER


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

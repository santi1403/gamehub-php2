import os
from urllib.parse import quote_plus


class Config:
    _raw_uri = os.getenv('MONGO_URI', '')

    if _raw_uri and '://' in _raw_uri:
        protocolo, resto = _raw_uri.split('://', 1)

        if '@' in resto:
            credenciales, servidor = resto.split('@', 1)

            if ':' in credenciales:
                usuario, password = credenciales.split(':', 1)
                usuario = quote_plus(usuario)
                password = quote_plus(password)
                credenciales = f'{usuario}:{password}'

            MONGO_URI = f'{protocolo}://{credenciales}@{servidor}'
        else:
            MONGO_URI = _raw_uri
    else:
        MONGO_URI = _raw_uri if _raw_uri else 'mongodb://localhost:27017/'

    MONGO_DB = os.getenv('MONGO_DB', 'gamehub_analitica')

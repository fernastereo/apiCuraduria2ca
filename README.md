# apiCuradurias
Api for services on curadurias 😎

#### Estado de un Proyecto

`method: get`

`api/router.php/radicacion/{curaduria}/{num_radicacion}/{año_radicacion}`

#### Consultar una Resolución

`method: get`

`api/router.php/resolucion/{curaduria}/{num_resolucion}/{año_resolucion}`

#### Listado de Resoluciones

`method: get`

`api/router.php/resoluciones/{curaduria}/{fechaini}/{fechafin}`

#### Publicar un documento

`method: post`

`api/router.php/publicacion/{curaduria}`

    Parámetros:
    - fechapublicacion (string)
    - referencia (string)
    - archivo (file)
    - estado (integer [1|0])

#### Listado de Publicaciones

`method: get`

`api/router.php/publicaciones/{curaduria}/{fechaini}/{fechafin}`

#### Enviar foto de Valla del proyecto

`method: post`

`api/router.php/valla/{curaduria}`

    Parámetros:
    - proyecto (string)
    - vigencia (string)
    - comentarios (string)
    - archivo (file)
    - email (string)

#### Enviar comprobantes de pago

`method: post`

`api/router.php/pago/{curaduria}`

    Parámetros:
    - proyecto (string)
    - vigencia (string)
    - comentarios (string)
    - archivo (file)
    - email (string)

#### Registrar PQR

`method: post`

`api/router.php/pqr/{curaduria}`

    Parámetros:
    - nombre (string)
    - email (string)
    - asunto (string)
    - comentario (string)
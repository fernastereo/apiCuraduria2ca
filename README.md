# API Curaduría 2 Cartagena

API y Frontend para servicios de la Curaduría 2 de Cartagena.

## Estructura del Proyecto

```
apiCuraduria2ca/
├── api/                # Backend API
│   ├── controllers/    # Controladores de la API
│   ├── config/         # Configuración de base de datos y variables
│   ├── functions/      # Funciones auxiliares
│   └── ventanilla.php  # Punto de entrada principal de la API
├── front/              # Frontend
│   ├── css/            # Estilos
│   ├── js/             # Scripts
│   └── *.html          # Páginas HTML
└── vendor/             # Dependencias de Composer
```

## Endpoints de la API

### Autenticación

#### Login
- **URL**: `/login`
- **Método**: `POST`
- **Descripción**: Autenticar usuario
- **Respuesta exitosa**: Token JWT
- **Auth requerida**: No

#### Registro
- **URL**: `/register`
- **Método**: `POST`
- **Descripción**: Registrar nuevo usuario
- **Auth requerida**: No

#### Verificar Token
- **URL**: `/verify-token`
- **Método**: `GET`
- **Descripción**: Verificar validez del token JWT
- **Headers requeridos**: `Authorization: Bearer {token}`
- **Auth requerida**: Sí

#### Logout
- **URL**: `/logout`
- **Método**: `POST`
- **Descripción**: Cerrar sesión
- **Headers requeridos**: `Authorization: Bearer {token}`
- **Auth requerida**: Sí

#### Información de Usuario
- **URL**: `/user`
- **Método**: `GET`
- **Descripción**: Obtener información del usuario actual
- **Headers requeridos**: `Authorization: Bearer {token}`
- **Auth requerida**: Sí

### Expedientes

#### Consultar Expediente
- **URL**: `/expedientes`
- **Método**: `GET`
- **Parámetros**:
  - `idradicado`: Número de radicado
  - `vigencia`: Año del expediente
- **Ejemplo**: `/expedientes?idradicado=1&vigencia=2024`
- **Respuesta**: Información detallada del expediente incluyendo:
  - Número de Expediente (formato: 13001-2-24-0001)
  - Solicitante
  - Dirección y Barrio
  - Tipo de Licencia
  - Modalidad
  - Estado
  - Fechas de radicación y última actualización
- **Auth requerida**: No

### Publicaciones

#### Listar Publicaciones
- **URL**: `/publicaciones`
- **Método**: `GET`
- **Descripción**: Obtener todas las publicaciones
- **Headers requeridos**: `Authorization: Bearer {token}`
- **Auth requerida**: Sí

#### Buscar por Rango de Fechas
- **URL**: `/publicaciones`
- **Método**: `GET`
- **Parámetros**:
  - `fecha_inicio`: Fecha inicial (YYYY-MM-DD)
  - `fecha_fin`: Fecha final (YYYY-MM-DD)
- **Ejemplo**: `/publicaciones?fecha_inicio=2024-01-01&fecha_fin=2024-12-31`
- **Auth requerida**: No

#### Obtener Publicación
- **URL**: `/publicaciones/{id}`
- **Método**: `GET`
- **Descripción**: Obtener una publicación específica
- **Headers requeridos**: `Authorization: Bearer {token}`
- **Auth requerida**: Sí

#### Crear Publicación
- **URL**: `/publicaciones`
- **Método**: `POST`
- **Formato**: `multipart/form-data`
- **Headers requeridos**: `Authorization: Bearer {token}`
- **Parámetros**:
  - `fecha`: Fecha del documento
  - `fechapublicacion`: Fecha de publicación
  - `referencia`: Referencia del documento
  - `estado`: Estado de la publicación
  - `tipopublicacion_id`: ID del tipo de publicación
  - `publicacionFile`: Archivo PDF
- **Auth requerida**: Sí

#### Actualizar Publicación
- **URL**: `/publicaciones/{id}`
- **Método**: `POST`
- **Headers**: 
  - `Content-Type: multipart/form-data`
  - `Authorization: Bearer {token}`
- **Parámetros**:
  - `_method`: 'PUT' (requerido)
  - Demás campos igual que en crear
- **Auth requerida**: Sí

#### Eliminar Publicación
- **URL**: `/publicaciones/{id}`
- **Método**: `DELETE`
- **Headers requeridos**: `Authorization: Bearer {token}`
- **Auth requerida**: Sí

### Tipos de Publicación

#### Listar Tipos
- **URL**: `/tipos-publicacion`
- **Método**: `GET`
- **Descripción**: Obtener lista de tipos de publicación
- **Auth requerida**: No

#### Obtener Tipo
- **URL**: `/tipos-publicacion/{id}`
- **Método**: `GET`
- **Descripción**: Obtener un tipo de publicación específico
- **Headers requeridos**: `Authorization: Bearer {token}`
- **Auth requerida**: Sí

## Frontend

### Páginas Disponibles

#### Consulta de Expedientes
- **Archivo**: `estado-expediente.html`
- **Funcionalidad**: Consulta de estado de expedientes por número y año
- **Características**:
  - Formulario de búsqueda
  - Visualización detallada de información
  - Manejo de errores y estados de carga
  - Formato especial para número de radicación

#### Publicaciones
- **Archivo**: `publicaciones.html`
- **Funcionalidad**: Gestión y consulta de publicaciones
- **Características**:
  - Búsqueda por rango de fechas
  - Paginación del lado del cliente (10 registros por página)
  - Descarga de documentos
  - Estilos tipo badge para tipos de publicación


## Instalación y Despliegue

1. Clonar el repositorio
2. Para el backend Configurar el archivo `api/config.php` con las credenciales de base de datos y AWS
3. Instalar dependencias: `composer install`
4. Para el frontend Configurar el archivo `front/js/env.php` con la url del entrypoint del backend `ventanilla.php` y la url del bucket a usar en AWS.
5. Configurar el entorno a desplegar (`local` | `stage` | `prod`) en el archivo .htaccess `SetEnv APP_ENV stage`.
6. Asegurar que el servidor web tiene permisos de escritura en las carpetas necesarias

## Tecnologías Utilizadas

- Backend:
  - PHP 8.2+
  - MySQL/MariaDB
  - AWS S3 para almacenamiento de archivos
- Frontend:
  - Vue.js 3
  - HTML5/CSS3
  - Font Awesome para iconos
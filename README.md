# Curaduria 2 Cartagena API

REST API for managing publications and files for Curaduria 2 Cartagena.

⭐ If you find this project helpful, please consider giving it a star!

## 🚀 Features

- User Authentication (JWT)
- File Records Management
- Publication System
- AWS S3 Integration for File Storage
- Environment-based Configuration
- CORS Support
- Health Check Endpoint

## 📋 Requirements

- PHP 8.2+
- MySQL/MariaDB
- Apache with mod_rewrite enabled
- Composer (for development)

## 🛠️ Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/apiCuraduria2ca.git
```

2. Set up your environment variables by copying the example file:
```bash
cp api/.env.example api/.env
```

3. Configure your `.env` file with your credentials:
```env
DB_HOST=your_host
DB_NAME=your_database
DB_USER=your_user
DB_PASS=your_password
PATH_AWS=your_aws_url
MAIL_TO=notifications@email.com
MAIL_FROM=sender@email.com
AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_BUCKET=your_bucket
AWS_BUCKET_FOLDER=your_folder
AWS_REGION=your_region
API_URL_FRONT=your_api_url
APP_ENV=prod
```

## 🔄 API Endpoints

### Public Endpoints (No Authentication Required)
- `GET /` or `/health-check` - System health check and API information
- `POST /login` - User login
- `GET /expedientes` - Query record by ID and year
- `GET /publicaciones` - List all publications
- `GET /publicaciones/{id}` - Get publication by ID
- `GET /publicaciones?fecha_inicio=&fecha_fin=` - Search by date range
- `GET /tipos-publicacion` - List publication types
- `GET /tipos-publicacion/{id}` - Get publication type by ID

### Protected Endpoints (Authentication Required)
All protected endpoints require a valid JWT token in the Authorization header: `Authorization: Bearer {your_token}`

#### Authentication
- `POST /register` - Register new user
- `GET /verify-token` - Verify JWT token
- `POST /logout` - User logout

#### Publications Management
- `POST /publicaciones` - Create new publication
- `PUT /publicaciones/{id}` - Update publication
- `DELETE /publicaciones/{id}` - Delete publication

## 🚀 Deployment

The project uses GitHub Actions for automated deployment. The workflow:

1. Triggers on:
   - Push to main/master
   - Merged pull requests to main/master

2. Deployment process:
   - Checks out the code
   - Creates production .env file
   - Deploys via FTP to production server

Required GitHub Secrets for deployment:
- `FTP_HOST`
- `FTP_USERNAME`
- `FTP_PASSWORD`
- All environment variables listed in the Installation section

## 🔒 Security

- JWT for API authentication
- CORS headers configured
- Environment-based configuration
- Secure file handling
- AWS S3 for secure file storage

## 🌐 Environment Support

The API supports multiple environments:
- `local` - Development
- `stage` - Staging
- `prod` - Production

Each environment can have its own `.env.{environment}` file.

---

# API Curaduría 2 Cartagena

API REST para la gestión de publicaciones y archivos de la Curaduría 2 de Cartagena.

⭐ Si encuentras útil este proyecto, ¡considera darle una estrella!

## 🚀 Características

- Autenticación de Usuarios (JWT)
- Gestión de Expedientes
- Sistema de Publicaciones
- Integración con AWS S3 para Almacenamiento
- Configuración basada en Entornos
- Soporte CORS
- Endpoint de Verificación de Salud

## 📋 Requisitos

- PHP 8.2+
- MySQL/MariaDB
- Apache con mod_rewrite habilitado
- Composer (para desarrollo)

## 🛠️ Instalación

1. Clonar el repositorio:
```bash
git clone https://github.com/tuusuario/apiCuraduria2ca.git
```

2. Configurar variables de entorno copiando el archivo de ejemplo:
```bash
cp api/.env.example api/.env
```

3. Configurar el archivo `.env` con tus credenciales:
```env
DB_HOST=tu_host
DB_NAME=tu_base_de_datos
DB_USER=tu_usuario
DB_PASS=tu_contraseña
PATH_AWS=tu_url_aws
MAIL_TO=notificaciones@email.com
MAIL_FROM=remitente@email.com
AWS_ACCESS_KEY_ID=tu_clave_aws
AWS_SECRET_ACCESS_KEY=tu_secreto_aws
AWS_BUCKET=tu_bucket
AWS_BUCKET_FOLDER=tu_carpeta
AWS_REGION=tu_region
API_URL_FRONT=tu_url_api
APP_ENV=prod
```

## 🔄 Endpoints de la API

### Endpoints Públicos (No Requieren Autenticación)
- `GET /` o `/health-check` - Verificación de salud del sistema e información de la API
- `POST /login` - Inicio de sesión
- `GET /expedientes` - Consultar expediente por ID y año
- `GET /publicaciones` - Listar todas las publicaciones
- `GET /publicaciones/{id}` - Obtener publicación por ID
- `GET /publicaciones?fecha_inicio=&fecha_fin=` - Buscar por rango de fechas
- `GET /tipos-publicacion` - Listar tipos de publicación
- `GET /tipos-publicacion/{id}` - Obtener tipo de publicación por ID

### Endpoints Protegidos (Requieren Autenticación)
Todos los endpoints protegidos requieren un token JWT válido en el encabezado Authorization: `Authorization: Bearer {tu_token}`

#### Autenticación
- `POST /register` - Registro de usuario
- `GET /verify-token` - Verificar token JWT
- `POST /logout` - Cerrar sesión

#### Gestión de Publicaciones
- `POST /publicaciones` - Crear nueva publicación
- `PUT /publicaciones/{id}` - Actualizar publicación
- `DELETE /publicaciones/{id}` - Eliminar publicación

## 🚀 Despliegue

El proyecto usa GitHub Actions para despliegue automatizado. El workflow:

1. Se activa con:
   - Push a main/master
   - Pull requests fusionados en main/master

2. Proceso de despliegue:
   - Obtiene el código
   - Crea archivo .env de producción
   - Despliega vía FTP al servidor de producción

Secretos requeridos en GitHub para el despliegue:
- `FTP_HOST`
- `FTP_USERNAME`
- `FTP_PASSWORD`
- Todas las variables de entorno listadas en la sección de Instalación

## 🔒 Seguridad

- JWT para autenticación de API
- Cabeceras CORS configuradas
- Configuración basada en entorno
- Manejo seguro de archivos
- AWS S3 para almacenamiento seguro

## 🌐 Soporte de Entornos

La API soporta múltiples entornos:
- `local` - Desarrollo
- `stage` - Pruebas
- `prod` - Producción

Cada entorno puede tener su propio archivo `.env.{entorno}`.
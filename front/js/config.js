const CONFIG = {
    development: {
        API_URL: '/apiCuraduria2ca/api/ventanilla.php',
        AWS_URL: 'https://curaduria2ca.s3.us-east-2.amazonaws.com/notificaciones'
    },
    production: {
        API_URL: '/apiCuraduria2ca/api/ventanilla.php',
        AWS_URL: 'https://curaduria2ca.s3.us-east-2.amazonaws.com/notificaciones'
    }
};

// Determinar el entorno actual basado en la URL
const ENV = window.location.hostname === 'localhost' ? 'development' : 'production';

// Exportar la configuración del entorno actual
const { API_URL, AWS_URL } = CONFIG[ENV];
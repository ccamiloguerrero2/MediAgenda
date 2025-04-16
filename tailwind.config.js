// tailwind.config.js

/** @type {import('tailwindcss').Config} */
module.exports = {
  // Habilita el modo oscuro basado en una clase 'dark' en el elemento <body>
  // Esto permite alternar el tema mediante JavaScript.
  darkMode: 'class',

  // Define los archivos que Tailwind analizará para buscar clases de utilidad.
  // Es crucial incluir todas las ubicaciones donde se usan clases de Tailwind
  // para evitar que se purguen (eliminen) del CSS final.
  content: [
    // Archivos PHP que actúan como vistas principales en el directorio público.
    './public/**/*.php',
    // Archivos HTML estáticos en el directorio público.
    './public/**/*.html',
    // Archivos PHP de vistas/layouts parciales dentro de la estructura de la aplicación.
    './app/Views/**/*.php',
    // Archivos JavaScript en el directorio público (donde ahora reside el código fuente JS).
    './public/js/**/*.js',
  ],

  // Lista de clases que NO deben ser purgadas por Tailwind, incluso si no las
  // encuentra directamente en los archivos escaneados. Esencial para clases
  // añadidas dinámicamente por JavaScript (ej. colores de notificaciones,
  // estados de citas, etc.).
  safelist: [
    // --- Clases para Notificaciones/Estados (Ejemplos) ---
    // Éxito (Verde)
    'bg-green-100',
    'border-green-300', // o 500 según diseño
    'text-green-800',
    'dark:bg-green-900',
    'dark:text-green-300',
    'dark:border-green-700',
    // Error (Rojo)
    'bg-red-100',
    'border-red-300', // o 500
    'text-red-800',
    'dark:bg-red-900',
    'dark:text-red-300',
    'dark:border-red-700',
    // Advertencia (Amarillo)
    'bg-yellow-100',
    'border-yellow-300', // o 500
    'text-yellow-800',
    'dark:bg-yellow-900',
    'dark:text-yellow-300',
    'dark:border-yellow-700',
    // Información (Azul)
    'bg-blue-100',
    'border-blue-300', // o 500
    'text-blue-800',
    'dark:bg-blue-900',
    'dark:text-blue-300',
    'dark:border-blue-700',
    // Estado Cita Completada/Genérico (Gris) - Ajustar según diseño
    'bg-gray-100',
    'text-gray-800',
    'dark:bg-gray-700',
    'dark:text-gray-300',
    // Borde izquierdo coloreado para citas (Ejemplos)
    'border-l-4',
    'border-blue-500',
    'border-green-500',
    'border-red-500',
    'border-gray-500', // o 300
    'border-yellow-500',
    // --- Clases Base Comunes (si se usan solo dinámicamente) ---
    'px-4',
    'py-3',
    'rounded-md',
    'shadow-lg',
    'text-sm',
    'font-medium',
    // Añadir más clases dinámicas aquí si es necesario
  ],

  // Permite extender o sobrescribir el tema predeterminado de Tailwind.
  // Útil para añadir colores personalizados, fuentes, breakpoints, etc.
  theme: {
    extend: {
      // Ejemplo: añadir un color personalizado
      // colors: {
      //   'mediagenda-brand': '#3490dc',
      // },
      // Ejemplo: añadir una fuente personalizada
      // fontFamily: {
      //   'sans': ['Roboto', 'ui-sans-serif', 'system-ui', ...],
      //   'heading': ['Montserrat', 'ui-sans-serif', ...],
      //   'logo': ['Pacifico', 'cursive'],
      // }
    },
  },

  // Permite añadir plugins de Tailwind para funcionalidades adicionales
  // (ej. formularios, tipografía, aspect-ratio).
  plugins: [
    // Ejemplo: require('@tailwindcss/forms'),
  ],
};
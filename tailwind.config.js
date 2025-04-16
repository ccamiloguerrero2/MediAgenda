// --- tailwind.config.js (CON SAFELIST) ---

module.exports = {
  darkMode: 'class',
  content: [
    './*.php',
    './**/*.php',
    './*.html',
    './**/*.html',
    './*.js',
    './**/*.js',
    '!./node_modules/**/*',
  ],
  safelist: [ // <<< AÑADIR ESTA SECCIÓN >>>
    // Clases para notificaciones de éxito
    'bg-green-100',
    'border-green-300',
    'text-green-800',
    'dark:bg-green-900',
    'dark:text-green-300',
    'dark:border-green-700',
    // Clases para notificaciones de error
    'bg-red-100',
    'border-red-300',
    'text-red-800',
    'dark:bg-red-900',
    'dark:text-red-300',
    'dark:border-red-700',
    // Clases para notificaciones de warning
    'bg-yellow-100',
    'border-yellow-300',
    'text-yellow-800',
    'dark:bg-yellow-900',
    'dark:text-yellow-300',
    'dark:border-yellow-700',
    // Clases para notificaciones de info (y la de prueba)
    'bg-blue-100',
    'border-blue-300',
    'text-blue-800',
    'dark:bg-blue-900',
    'dark:text-blue-300',
    'dark:border-blue-700',
    // Clases base usadas en showNotification (si no están en otro lado)
    'px-4',
    'py-3',
    'rounded-md',
    'shadow-lg',
    'text-sm',
    'font-medium',
    // Considera añadir aquí otras clases que solo uses dinámicamente
    // si encuentras problemas similares en otras partes.
  ], // <<< FIN DE SAFELIST >>>
  theme: {
    extend: {},
  },
  plugins: [],
}
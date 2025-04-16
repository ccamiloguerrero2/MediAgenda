// postcss.config.js

// Exporta la configuración para PostCSS.
module.exports = {
  // Define los plugins que PostCSS debe ejecutar y en qué orden.
  plugins: {
    // 1. Plugin de Tailwind CSS:
    //    Procesa las directivas de Tailwind (@tailwind, @apply, @config, etc.)
    //    y genera las clases de utilidad correspondientes basadas en la
    //    configuración de `tailwind.config.js`.
    tailwindcss: {}, // El objeto vacío indica que use la configuración por defecto o la encontrada.

    // 2. Plugin Autoprefixer:
    //    Analiza el CSS generado (incluyendo el de Tailwind) y añade
    //    automáticamente los prefijos de proveedor (-webkit-, -moz-, -ms-)
    //    necesarios para asegurar la compatibilidad con diferentes navegadores,
    //    basándose en la configuración de `browserslist` (que suele estar en
    //    `package.json` o un archivo `.browserslistrc`).
    autoprefixer: {}, // El objeto vacío indica que use la configuración por defecto.
  }
}
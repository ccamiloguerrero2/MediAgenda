<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Gestión de Citas Médicas Online</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2B6CB0',
                        secondary: '#48BB78'
                    },
                    borderRadius: {
                        'none': '0px',
                        'sm': '4px',
                        DEFAULT: '8px',
                        'md': '12px',
                        'lg': '16px',
                        'xl': '20px',
                        '2xl': '24px',
                        '3xl': '32px',
                        'full': '9999px',
                        'button': '8px'
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :where([class^="ri-"])::before {
            content: "\f3c2";
        }

        body {
            font-family: 'Roboto', sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Montserrat', sans-serif;
        }

        input:focus,
        button:focus {
            outline: none;
        }

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .hero-section {
            background-image: linear-gradient(to right, rgba(255, 255, 255, 0.9) 40%, rgba(255, 255, 255, 0.7) 60%, rgba(255, 255, 255, 0.3) 80%, rgba(255, 255, 255, 0) 100%), url('https://readdy.ai/api/search-image?query=professional%20medical%20environment%20with%20modern%20technology%2C%20soft%20lighting%2C%20doctor%20with%20tablet%2C%20clean%20and%20modern%20hospital%20interior%2C%20medical%20staff%20in%20background%2C%20high-quality%20medical%20equipment%2C%20blue%20and%20white%20color%20scheme%2C%20professional%20healthcare%20setting&width=1200&height=600&seq=123456&orientation=landscape');
            background-size: cover;
            background-position: center right;
        }

        .custom-checkbox {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .custom-checkbox input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .checkmark {
            height: 20px;
            width: 20px;
            background-color: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-checkbox input:checked~.checkmark {
            background-color: #2B6CB0;
            border-color: #2B6CB0;
        }

        .checkmark:after {
            content: "";
            display: none;
        }

        .custom-checkbox input:checked~.checkmark:after {
            display: block;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm fixed w-full top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <a href="index.php" class="text-2xl font-['Pacifico'] text-primary">MediAgenda</a>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center space-x-8">
                <a href="#servicios" class="text-gray-700 hover:text-primary font-medium transition">Servicios</a>
                <a href="#doctores" class="text-gray-700 hover:text-primary font-medium transition">Doctores</a>
                <a href="#sobre-nosotros" class="text-gray-700 hover:text-primary font-medium transition">Sobre Nosotros</a>
                <a href="blog.html" class="text-gray-700 hover:text-primary font-medium transition">Blog</a>
            </nav>

            <div class="flex items-center space-x-4">
                <button class="text-primary font-medium hover:text-primary/80 transition whitespace-nowrap !rounded-button">Iniciar Sesión</button>
                <button class="bg-primary text-white px-5 py-2 font-medium hover:bg-primary/90 transition whitespace-nowrap !rounded-button">Registrarse</button>

                <!-- Mobile Menu Button -->
                <button class="md:hidden w-10 h-10 flex items-center justify-center text-gray-700">
                    <i class="ri-menu-line ri-lg"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section pt-28 pb-16 md:py-32 w-full">
        <div class="container mx-auto px-4 w-full">
            <div class="max-w-2xl">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Gestiona tus citas médicas de forma sencilla y eficiente</h1>
                <p class="text-lg text-gray-700 mb-8">Conectamos pacientes con profesionales de la salud para brindar una experiencia médica sin complicaciones.</p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <button class="bg-primary text-white px-6 py-3 font-medium hover:bg-primary/90 transition whitespace-nowrap !rounded-button">Agendar una cita</button>
                    <button class="bg-white border border-gray-300 text-gray-800 px-6 py-3 font-medium hover:bg-gray-50 transition whitespace-nowrap !rounded-button">Ver especialidades</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">¿Por qué elegir MediAgenda?</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Nuestra plataforma está diseñada para hacer que la gestión de citas médicas sea simple, rápida y eficiente tanto para pacientes como para profesionales.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Benefit 1 -->
                <div class="bg-gray-50 p-6 rounded shadow-sm hover:shadow-md transition">
                    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <i class="ri-calendar-check-line ri-xl text-primary"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Gestión de citas simplificada</h3>
                    <p class="text-gray-600">Agenda, reprograma o cancela citas con unos pocos clics, sin llamadas telefónicas ni esperas.</p>
                </div>

                <!-- Benefit 2 -->
                <div class="bg-gray-50 p-6 rounded shadow-sm hover:shadow-md transition">
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <i class="ri-user-heart-line ri-xl text-secondary"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Acceso a profesionales calificados</h3>
                    <p class="text-gray-600">Conectamos con los mejores especialistas médicos según tus necesidades específicas.</p>
                </div>

                <!-- Benefit 3 -->
                <div class="bg-gray-50 p-6 rounded shadow-sm hover:shadow-md transition">
                    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <i class="ri-file-list-3-line ri-xl text-primary"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Historial médico digital</h3>
                    <p class="text-gray-600">Mantén un registro de tus consultas, diagnósticos y recetas en un solo lugar seguro.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-16 bg-gray-50" id="servicios">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">¿Cómo funciona?</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Un proceso simple de 3 pasos para comenzar a gestionar tus citas médicas.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary text-white rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">1</div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Regístrate</h3>
                    <p class="text-gray-600">Crea tu cuenta como paciente o profesional médico en menos de 2 minutos.</p>
                </div>

                <!-- Step 2 -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary text-white rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">2</div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Busca especialistas</h3>
                    <p class="text-gray-600">Encuentra el médico adecuado según especialidad, ubicación o disponibilidad.</p>
                </div>

                <!-- Step 3 -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary text-white rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">3</div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Agenda tu cita</h3>
                    <p class="text-gray-600">Selecciona el horario que mejor te convenga y confirma tu cita médica.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Doctors Section -->
    <section class="py-16 bg-white" id="doctores">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Nuestros especialistas destacados</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Contamos con profesionales de la salud altamente calificados en diversas especialidades.</p>
            </div>

            <div class="grid md:grid-cols-4 gap-6">
                <!-- Doctor 1 -->
                <div class="bg-gray-50 rounded overflow-hidden shadow-sm hover:shadow-md transition">
                    <img src="https://readdy.ai/api/search-image?query=professional%20female%20doctor%20with%20glasses%2C%20mid%2030s%2C%20wearing%20white%20coat%2C%20friendly%20smile%2C%20neutral%20medical%20office%20background%2C%20professional%20headshot%2C%20high%20quality%20portrait&width=300&height=300&seq=123457&orientation=squarish" alt="Dra. Carmen Rodríguez" class="w-full h-64 object-cover object-top">
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900">Dra. Carmen Rodríguez</h3>
                        <p class="text-primary font-medium">Cardiología</p>
                        <div class="flex items-center mt-2">
                            <div class="flex text-yellow-400">
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-half-fill"></i>
                            </div>
                            <span class="text-gray-600 text-sm ml-1">4.8</span>
                        </div>
                    </div>
                </div>

                <!-- Doctor 2 -->
                <div class="bg-gray-50 rounded overflow-hidden shadow-sm hover:shadow-md transition">
                    <img src="https://readdy.ai/api/search-image?query=professional%20male%20doctor%2C%20early%2040s%2C%20wearing%20white%20coat%2C%20confident%20smile%2C%20neutral%20medical%20office%20background%2C%20professional%20headshot%2C%20high%20quality%20portrait&width=300&height=300&seq=123458&orientation=squarish" alt="Dr. Alejandro Méndez" class="w-full h-64 object-cover object-top">
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900">Dr. Alejandro Méndez</h3>
                        <p class="text-primary font-medium">Neurología</p>
                        <div class="flex items-center mt-2">
                            <div class="flex text-yellow-400">
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                            </div>
                            <span class="text-gray-600 text-sm ml-1">5.0</span>
                        </div>
                    </div>
                </div>

                <!-- Doctor 3 -->
                <div class="bg-gray-50 rounded overflow-hidden shadow-sm hover:shadow-md transition">
                    <img src="https://readdy.ai/api/search-image?query=professional%20female%20doctor%2C%20late%2030s%2C%20wearing%20white%20coat%2C%20warm%20smile%2C%20neutral%20medical%20office%20background%2C%20professional%20headshot%2C%20high%20quality%20portrait&width=300&height=300&seq=123459&orientation=squarish" alt="Dra. Sofía Martínez" class="w-full h-64 object-cover object-top">
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900">Dra. Sofía Martínez</h3>
                        <p class="text-primary font-medium">Pediatría</p>
                        <div class="flex items-center mt-2">
                            <div class="flex text-yellow-400">
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-line"></i>
                            </div>
                            <span class="text-gray-600 text-sm ml-1">4.2</span>
                        </div>
                    </div>
                </div>

                <!-- Doctor 4 -->
                <div class="bg-gray-50 rounded overflow-hidden shadow-sm hover:shadow-md transition">
                    <img src="https://readdy.ai/api/search-image?query=professional%20male%20doctor%2C%20mid%2040s%2C%20wearing%20white%20coat%2C%20friendly%20expression%2C%20neutral%20medical%20office%20background%2C%20professional%20headshot%2C%20high%20quality%20portrait&width=300&height=300&seq=123460&orientation=squarish" alt="Dr. Gabriel Ortiz" class="w-full h-64 object-cover object-top">
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900">Dr. Gabriel Ortiz</h3>
                        <p class="text-primary font-medium">Dermatología</p>
                        <div class="flex items-center mt-2">
                            <div class="flex text-yellow-400">
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-half-fill"></i>
                            </div>
                            <span class="text-gray-600 text-sm ml-1">4.7</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-10">
                <button class="bg-white border border-primary text-primary px-6 py-3 font-medium hover:bg-primary/5 transition whitespace-nowrap !rounded-button">Ver todos los especialistas</button>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Lo que dicen nuestros usuarios</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Experiencias reales de pacientes y médicos que utilizan MediAgenda.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Testimonial 1 -->
                <div class="bg-white p-6 rounded shadow-sm">
                    <div class="flex text-yellow-400 mb-4">
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-fill"></i>
                    </div>
                    <p class="text-gray-600 mb-6">"MediAgenda ha simplificado enormemente la gestión de mis citas médicas. Ahora puedo programar consultas con mis especialistas sin tener que hacer largas llamadas telefónicas."</p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gray-200 rounded-full overflow-hidden mr-4">
                            <img src="https://readdy.ai/api/search-image?query=professional%20headshot%20of%20a%20middle-aged%20woman%20with%20short%20hair%2C%20natural%20makeup%2C%20neutral%20background%2C%20high%20quality%20portrait&width=100&height=100&seq=123461&orientation=squarish" alt="Isabel Gómez" class="w-full h-full object-cover object-top">
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Isabel Gómez</h4>
                            <p class="text-gray-500 text-sm">Paciente</p>
                        </div>
                    </div>

                </div>

                <!-- Testimonial 2 -->
                <div class="bg-white p-6 rounded shadow-sm">
                    <div class="flex text-yellow-400 mb-4">
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-fill"></i>
                    </div>
                    <p class="text-gray-600 mb-6">"Como médico, MediAgenda me ha permitido organizar mejor mi agenda y reducir las cancelaciones de última hora. La plataforma es intuitiva y ahorra tiempo tanto a mí como a mis pacientes."</p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gray-200 rounded-full overflow-hidden mr-4">
                            <img src="https://readdy.ai/api/search-image?query=professional%20headshot%20of%20a%20male%20doctor%20in%20his%2050s%2C%20wearing%20glasses%2C%20professional%20attire%2C%20neutral%20background%2C%20high%20quality%20portrait&width=100&height=100&seq=123462&orientation=squarish" alt="Dr. Fernando Ruiz" class="w-full h-full object-cover object-top">
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Dr. Fernando Ruiz</h4>
                            <p class="text-gray-500 text-sm">Médico Especialista</p>
                        </div>
                    </div>

                </div>

                <!-- Testimonial 3 -->
                <div class="bg-white p-6 rounded shadow-sm">
                    <div class="flex text-yellow-400 mb-4">
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-half-fill"></i>
                    </div>
                    <p class="text-gray-600 mb-6">"Gracias a MediAgenda puedo gestionar las citas médicas de toda mi familia desde una sola cuenta. Los recordatorios automáticos son muy útiles y la interfaz es muy fácil de usar."</p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gray-200 rounded-full overflow-hidden mr-4">
                            <img src="https://readdy.ai/api/search-image?query=professional%20headshot%20of%20a%20young%20woman%20in%20her%20early%2030s%2C%20casual%20professional%20attire%2C%20neutral%20background%2C%20high%20quality%20portrait&width=100&height=100&seq=123463&orientation=squarish" alt="Laura Vega" class="w-full h-full object-cover object-top">
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Laura Vega</h4>
                            <p class="text-gray-500 text-sm">Paciente</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section class="py-16 bg-white" id="sobre-nosotros">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center gap-12">
                <div class="md:w-1/2">
                    <img src="https://readdy.ai/api/search-image?query=modern%20medical%20team%20of%20diverse%20professionals%20in%20a%20bright%2C%20contemporary%20hospital%20setting%2C%20discussing%20patient%20care%2C%20using%20digital%20technology%2C%20collaborative%20environment%2C%20professional%20healthcare%20setting&width=600&height=400&seq=123464&orientation=landscape" alt="Equipo MediAgenda" class="w-full h-auto rounded shadow-md">
                </div>
                <div class="md:w-1/2">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Sobre MediAgenda</h2>
                    <p class="text-gray-600 mb-4">Fundada en 2022, MediAgenda nació con la misión de transformar la manera en que pacientes y profesionales médicos gestionan las citas y la información sanitaria.</p>
                    <p class="text-gray-600 mb-6">Nuestro equipo está formado por profesionales de la salud y expertos en tecnología comprometidos con mejorar la experiencia sanitaria para todos.</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <i class="ri-user-heart-line text-primary"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">+15,000</h4>
                                <p class="text-gray-500 text-sm">Pacientes</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <i class="ri-hospital-line text-primary"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">+500</h4>
                                <p class="text-gray-500 text-sm">Médicos</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <i class="ri-calendar-check-line text-primary"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">+45,000</h4>
                                <p class="text-gray-500 text-sm">Citas</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <i class="ri-building-4-line text-primary"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">+50</h4>
                                <p class="text-gray-500 text-sm">Clínicas</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-primary">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">Comienza a gestionar tus citas médicas hoy</h2>
            <p class="text-blue-100 max-w-2xl mx-auto mb-8">Únete a miles de pacientes y profesionales que ya disfrutan de los beneficios de MediAgenda.</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <button class="bg-white text-primary px-6 py-3 font-medium hover:bg-gray-100 transition whitespace-nowrap !rounded-button">Registrarse gratis</button>
                <button class="bg-transparent border border-white text-white px-6 py-3 font-medium hover:bg-white/10 transition whitespace-nowrap !rounded-button">Conocer más</button>
            </div>
        </div>
    </section>

    <!-- Blog Section -->
    <section class="py-16 bg-white" id="blog">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Últimas publicaciones</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Mantente informado con nuestros artículos sobre salud y bienestar.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Blog Post 1 -->
                <div class="bg-gray-50 rounded overflow-hidden shadow-sm hover:shadow-md transition">
                    <img src="https://readdy.ai/api/search-image?query=doctor%20explaining%20medical%20information%20to%20patient%2C%20using%20tablet%2C%20modern%20medical%20consultation%2C%20bright%20medical%20office%2C%20professional%20healthcare%20setting&width=400&height=250&seq=123465&orientation=landscape" alt="Consejos para una consulta médica efectiva" class="w-full h-48 object-cover object-top">
                    <div class="p-6">
                        <p class="text-primary text-sm font-medium mb-2">Salud General • 13 Abr 2025</p>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Consejos para una consulta médica efectiva</h3>
                        <p class="text-gray-600 mb-4">Aprende a prepararte adecuadamente para tu próxima cita médica y aprovecha al máximo el tiempo con tu especialista.</p>
                        <a href="blog.html" class="text-primary font-medium hover:text-primary/80 flex items-center">
                            Leer más
                            <i class="ri-arrow-right-line ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Blog Post 2 -->
                <div class="bg-gray-50 rounded overflow-hidden shadow-sm hover:shadow-md transition">
                    <img src="https://readdy.ai/api/search-image?query=doctor%20reviewing%20digital%20medical%20records%20on%20computer%2C%20modern%20medical%20technology%2C%20electronic%20health%20records%2C%20professional%20healthcare%20setting&width=400&height=250&seq=123466&orientation=landscape" alt="La importancia de mantener tu historial médico actualizado" class="w-full h-48 object-cover object-top">
                    <div class="p-6">
                        <p class="text-primary text-sm font-medium mb-2">Tecnología Médica • 10 Abr 2025</p>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">La importancia de mantener tu historial médico actualizado</h3>
                        <p class="text-gray-600 mb-4">Descubre por qué es crucial mantener un registro completo de tu información médica y cómo MediAgenda te ayuda.</p>
                        <a href="blog.html" class="text-primary font-medium hover:text-primary/80 flex items-center">
                            Leer más
                            <i class="ri-arrow-right-line ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Blog Post 3 -->
                <div class="bg-gray-50 rounded overflow-hidden shadow-sm hover:shadow-md transition">
                    <img src="https://readdy.ai/api/search-image?query=patient%20using%20smartphone%20health%20app%2C%20scheduling%20medical%20appointment%2C%20digital%20healthcare%2C%20modern%20lifestyle%2C%20professional%20healthcare%20setting&width=400&height=250&seq=123467&orientation=landscape" alt="5 beneficios de las citas médicas online" class="w-full h-48 object-cover object-top">
                    <div class="p-6">
                        <p class="text-primary text-sm font-medium mb-2">Telemedicina • 5 Abr 2025</p>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">5 beneficios de las citas médicas online</h3>
                        <p class="text-gray-600 mb-4">La telemedicina está transformando la atención sanitaria. Conoce las ventajas de las consultas virtuales.</p>
                        <a href="blog.html" class="text-primary font-medium hover:text-primary/80 flex items-center">
                            Leer más
                            <i class="ri-arrow-right-line ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="text-center mt-10">
                <button class="bg-white border border-primary text-primary px-6 py-3 font-medium hover:bg-primary/5 transition whitespace-nowrap !rounded-button">Ver todas las publicaciones</button>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="max-w-2xl mx-auto text-center">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Suscríbete a nuestro boletín</h2>
                <p class="text-gray-600 mb-6">Recibe consejos de salud, actualizaciones y novedades directamente en tu correo.</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="email" placeholder="Tu correo electrónico" class="flex-1 px-4 py-3 border border-gray-300 rounded focus:border-primary focus:ring-1 focus:ring-primary">
                    <button class="bg-primary text-white px-6 py-3 font-medium hover:bg-primary/90 transition whitespace-nowrap !rounded-button">Suscribirse</button>
                </div>
                <div class="mt-4 flex items-center justify-center">
                    <label class="custom-checkbox flex items-center text-sm text-gray-600">
                        <input type="checkbox">
                        <span class="checkmark mr-2"></span>
                        Acepto recibir comunicaciones de MediAgenda
                    </label>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white pt-16 pb-8">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8 mb-12">
                <!-- Column 1 -->
                <div>
                    <a href="index.php" class="text-2xl font-['Pacifico'] text-white mb-4 inline-block">MediAgenda</a>
                    <p class="text-gray-400 mb-4">Simplificando la gestión de citas médicas para pacientes y profesionales.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-primary transition">
                            <i class="ri-facebook-fill"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-primary transition">
                            <i class="ri-twitter-x-fill"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-primary transition">
                            <i class="ri-instagram-fill"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-primary transition">
                            <i class="ri-linkedin-fill"></i>
                        </a>
                    </div>
                </div>

                <!-- Column 2 -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Enlaces rápidos</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-gray-400 hover:text-white transition">Inicio</a></li>
                        <li><a href="#servicios" class="text-gray-400 hover:text-white transition">Servicios</a></li>
                        <li><a href="#doctores" class="text-gray-400 hover:text-white transition">Doctores</a></li>
                        <li><a href="#sobre-nosotros" class="text-gray-400 hover:text-white transition">Sobre Nosotros</a></li>
                        <li><a href="blog.html" class="text-gray-400 hover:text-white transition">Blog</a></li>
                        <li><a href="contacto.html" class="text-gray-400 hover:text-white transition">Contacto</a></li>
                    </ul>
                </div>

                <!-- Column 3 -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Para pacientes</h3>
                    <ul class="space-y-2">
                        <li><a href="registro.php" class="text-gray-400 hover:text-white transition">Buscar especialistas</a></li>
                        <li><a href="registro.php" class="text-gray-400 hover:text-white transition">Agendar cita</a></li>
                        <li><a href="registro.php" class="text-gray-400 hover:text-white transition">Historial médico</a></li>
                        <li><a href="contacto.html#help" class="text-gray-400 hover:text-white transition">Preguntas frecuentes</a></li>
                        <li><a href="politicas.html" class="text-gray-400 hover:text-white transition">Términos y condiciones</a></li>
                    </ul>
                </div>

                <!-- Column 4 -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contacto</h3>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <i class="ri-map-pin-line mt-1 mr-3 text-primary"></i>
                            <span class="text-gray-400">Av. Principal 123, Madrid, España</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-phone-line mr-3 text-primary"></i>
                            <span class="text-gray-400">+34 912 345 678</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-mail-line mr-3 text-primary"></i>
                            <span class="text-gray-400">info@mediagenda.com</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-time-line mr-3 text-primary"></i>
                            <span class="text-gray-400">Lun-Vie: 9:00 - 18:00</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-400 text-sm mb-4 md:mb-0">© 2025 MediAgenda. Todos los derechos reservados.</p>
                    <div class="flex items-center space-x-4">
                        <a href="politicas.html" class="text-gray-400 hover:text-white text-sm transition">Política de privacidad</a>
                        <a href="politicas.html" class="text-gray-400 hover:text-white text-sm transition">Términos de uso</a>
                        <a href="politicas.html" class="text-gray-400 hover:text-white text-sm transition">Cookies</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const menuButton = document.querySelector('.ri-menu-line').parentElement;
            const mobileMenu = document.createElement('div');
            mobileMenu.className = 'fixed inset-0 bg-gray-900/90 z-50 flex items-center justify-center transform translate-x-full transition-transform duration-300';
            mobileMenu.innerHTML = `
                <div class="bg-white rounded-lg w-5/6 max-w-md p-6 relative">
                    <button class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                        <i class="ri-close-line ri-lg"></i>
                    </button>
                    <div class="flex flex-col space-y-4 text-center">
                        <a href="#servicios" class="text-gray-700 hover:text-primary py-2 font-medium">Servicios</a>
                        <a href="#doctores" class="text-gray-700 hover:text-primary py-2 font-medium">Doctores</a>
                        <a href="#sobre-nosotros" class="text-gray-700 hover:text-primary py-2 font-medium">Sobre Nosotros</a>
                        <a href="#blog" class="text-gray-700 hover:text-primary py-2 font-medium">Blog</a>
                        <hr class="my-2">
                        <button class="text-primary font-medium hover:text-primary/80 py-2">Iniciar Sesión</button>
                        <button class="bg-primary text-white py-2 font-medium hover:bg-primary/90 rounded">Registrarse</button>
                    </div>
                </div>
            `;
            document.body.appendChild(mobileMenu);

            menuButton.addEventListener('click', function() {
                mobileMenu.classList.remove('translate-x-full');
            });

            mobileMenu.querySelector('.ri-close-line').parentElement.addEventListener('click', function() {
                mobileMenu.classList.add('translate-x-full');
            });

            // Close menu when clicking on a link
            mobileMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function() {
                    mobileMenu.classList.add('translate-x-full');
                });
            });
        });
    </script>
</body>

</html>
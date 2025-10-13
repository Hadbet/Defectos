<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Calidad - Seleccionar Línea</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .line-card {
            transition: all 0.3s ease-in-out;
        }
        .line-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-5xl mx-auto">
    <header class="text-center mb-12">
        <h1 class="text-5xl md:text-6xl font-extrabold text-slate-800 tracking-tight fade-in-up">
            Sistema de <span class="text-blue-600">Control de Calidad</span>
        </h1>
        <p class="mt-4 text-lg text-slate-500 max-w-2xl mx-auto fade-in-up" style="animation-delay: 0.2s;">
            Por favor, selecciona la línea de producción para comenzar el registro de defectos.
        </p>
    </header>

    <main>
        <?php
        $lineas = ["XNF", "BR167HR", "L234", "BMW G0S", "INSITU"];
        $iconos = [
            '<svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>',
            '<svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m12 0a2 2 0 100-4m0 4a2 2 0 110-4M6 6v2m6-2v2m6-2v2M6 18v2m6-2v2m6-2v2"></path></svg>',
            '<svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M12 6V3m0 18v-3M5.636 5.636l1.414 1.414m10.9-1.414l-1.414 1.414M5.636 18.364l1.414-1.414m10.9 1.414l-1.414-1.414"></path></svg>',
            '<svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2 1m2-1l-2-1M4 7l2 1M4 7l2-1M4 7v2.5M12 21.5v-2.5M12 18.5l-2-1m2 1l2-1"></path></svg>',
            '<svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9V3m0 18a9 9 0 009-9M3 12a9 9 0 019-9m-9 9a9 9 0 009 9m-9-9h18"></path></svg>'
        ];
        ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($lineas as $index => $linea): ?>
                <a href="https://grammermx.com/calidad/defectos/formulario_defectos.php?linea=<?php echo urlencode($linea); ?>"
                   class="line-card bg-white p-8 rounded-2xl shadow-lg border border-slate-200/50 flex flex-col items-center text-center fade-in-up"
                   style="animation-delay: <?php echo 0.3 + ($index * 0.1); ?>s;">
                    <div class="bg-blue-100 p-4 rounded-full mb-5">
                        <?php echo $iconos[$index % count($iconos)]; ?>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-800"><?php echo $linea; ?></h2>
                    <p class="text-slate-500 mt-2">Registrar defectos para esta línea</p>
                </a>
            <?php endforeach; ?>
        </div>
    </main>
</div>

</body>
</html>


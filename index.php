<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selección de Línea - Calidad Grammer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .line-button {
            transition: all 0.3s ease-in-out;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
        }
        .line-button:hover {
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: #3b82f6;
            color: #2563eb;
        }
        .fade-in {
            animation: fadeIn 1s ease-in-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen">

<main class="text-center p-6">
    <div class="fade-in">
        <h1 class="text-4xl md:text-5xl font-extrabold text-slate-800">Sistema de Registro de Defectos</h1>
        <p class="mt-4 max-w-2xl mx-auto text-lg text-slate-500">Por favor, selecciona tu línea de producción para continuar.</p>
    </div>

    <div class="mt-12 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-8">
        <?php
        $lineas = ["XNF", "BR167HR", "L234", "BMW G0S", "INSITU"];
        $delay = 0.2;
        foreach ($lineas as $linea):
            ?>
            <a href="https://grammermx.com/calidad/defectos/formulario_defectos.php?linea=<?php echo urlencode($linea); ?>"
               class="line-button block p-8 rounded-2xl shadow-md fade-in"
               style="animation-delay: <?php echo $delay; ?>s;">
                <h2 class="text-2xl font-bold text-slate-700"><?php echo $linea; ?></h2>
            </a>
            <?php
            $delay += 0.1;
        endforeach;
        ?>
    </div>

    <footer class="mt-16 fade-in" style="animation-delay: <?php echo $delay; ?>s;">
        <p class="text-sm text-slate-500">&copy; <?php echo date('Y'); ?> Grammer Automotive. Soporte por Hadbet Altamirano y Marco.</p>
    </footer>
</main>

</body>
</html>


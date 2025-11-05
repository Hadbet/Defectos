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
        /* Estilos para el modal */
        #submenu-modal {
            transition: opacity 0.3s ease-in-out;
        }
        #modal-content {
            transition: all 0.3s ease-in-out;
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
        // --- 1. MODIFICACIÓN PHP ---
        // Definimos las líneas y sus sub-áreas. Si no tiene, dejamos el array vacío.
        $lineas_con_submenus = [
            "XNF" => ["SPORT", "MFS"],
            "BR167" => [
                "Laminado", "Costura", "Handle", "Rear Cover Long",
                "Rear Cover Short", "Front Cover", "Handrest", "Maybach",
                "Side Panel RH y LH"
            ],
            "L234" => [],
            "BMW G0S" => [],
            "INSITU" => []
        ];

        $iconos = [
            '<svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>',
            '<svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m12 0a2 2 0 100-4m0 4a2 2 0 110-4M6 6v2m6-2v2m6-2v2M6 18v2m6-2v2m6-2v2"></path></svg>',
            '<svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M12 6V3m0 18v-3M5.636 5.636l1.414 1.414m10.9-1.414l-1.414 1.414M5.636 18.364l1.414-1.414m10.9 1.414l-1.414-1.414"></path></svg>',
            '<svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2 1m2-1l-2-1M4 7l2 1M4 7l2-1M4 7v2.5M12 21.5v-2.5M12 18.5l-2-1m2 1l2-1"></path></svg>',
            '<svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9V3m0 18a9 9 0 009-9M3 12a9 9 0 019-9m-9 9a9 9 0 009 9m-9-9h18"></path></svg>'
        ];

        $index = 0; // Contador para los iconos
        $baseUrl = "https://grammermx.com/calidad/defectos/formulario_defectos.php";
        ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

            <?php foreach ($lineas_con_submenus as $linea => $submenus): ?>

                <?php if (empty($submenus)): // --- CASO 1: LÍNEA SIN SUBMENÚ (Link directo) --- ?>

                    <a href="<?php echo $baseUrl; ?>?linea=<?php echo urlencode($linea); ?>"
                       class="line-card bg-white p-8 rounded-2xl shadow-lg border border-slate-200/50 flex flex-col items-center text-center fade-in-up"
                       style="animation-delay: <?php echo 0.3 + ($index * 0.1); ?>s;">
                        <div class="bg-blue-100 p-4 rounded-full mb-5">
                            <?php echo $iconos[$index % count($iconos)]; ?>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-800"><?php echo $linea; ?></h2>
                        <p class="text-slate-500 mt-2">Registrar defectos para esta línea</p>
                    </a>

                <?php else: // --- CASO 2: LÍNEA CON SUBMENÚ (Botón que abre el modal) --- ?>

                    <button type="button"
                            class="line-card open-submenu-modal bg-white p-8 rounded-2xl shadow-lg border border-slate-200/50 flex flex-col items-center text-center fade-in-up"
                            style="animation-delay: <?php echo 0.3 + ($index * 0.1); ?>s;"
                            data-linea="<?php echo $linea; ?>"
                            data-submenus="<?php echo htmlspecialchars(json_encode($submenus)); ?>">
                        <div class="bg-blue-100 p-4 rounded-full mb-5">
                            <?php echo $iconos[$index % count($iconos)]; ?>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-800"><?php echo $linea; ?></h2>
                        <p class="text-slate-500 mt-2">Seleccionar área de esta línea</p>
                    </button>

                <?php endif; ?>

                <?php $index++; endforeach; ?>
        </div>
    </main>
</div>

<div id="submenu-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center p-4 z-50 hidden">

    <div id="modal-content" class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-auto transform transition-all opacity-0 -translate-y-4">

        <div class="flex justify-between items-center p-6 border-b border-gray-200">
            <h3 class="text-2xl font-bold text-slate-800" id="modal-title">Seleccionar área</h3>
            <button type="button" id="close-modal-btn" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-6 max-h-96 overflow-y-auto">
            <div class="flex flex-col space-y-3" id="modal-list">
            </div>
        </div>
    </div>
</div>


<script>
    // Referencias a los elementos del DOM
    const modal = document.getElementById('submenu-modal');
    const modalContent = document.getElementById('modal-content');
    const modalTitle = document.getElementById('modal-title');
    const modalList = document.getElementById('modal-list');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const baseUrl = '<?php echo $baseUrl; ?>';

    // Función para abrir el modal
    function openModal(linea, submenus) {
        // 1. Actualizar el título
        modalTitle.textContent = `Seleccionar área: ${linea}`;

        // 2. Limpiar la lista anterior
        modalList.innerHTML = '';

        // 3. Crear y agregar los nuevos links
        submenus.forEach(sublinea => {
            const link = document.createElement('a');
            // Creamos el URL, ej: ...formulario_defectos.php?linea=XNF&sublinea=SPORT
            link.href = `${baseUrl}?linea=${encodeURIComponent(linea)} ${encodeURIComponent(sublinea)}`;
            link.className = 'block w-full p-4 text-left text-lg font-medium text-slate-700 bg-slate-50 rounded-lg border border-slate-200 hover:bg-blue-100 hover:border-blue-300 hover:text-blue-700 transition duration-150 ease-in-out';
            link.textContent = sublinea;
            modalList.appendChild(link);
        });

        // 4. Mostrar el modal
        modal.classList.remove('hidden');
        // Pequeño delay para que la animación de entrada funcione
        setTimeout(() => {
            modalContent.classList.remove('opacity-0', '-translate-y-4');
            modalContent.classList.add('opacity-100', 'translate-y-0');
        }, 10);
    }

    // Función para cerrar el modal
    function closeModal() {
        // Animación de salida
        modalContent.classList.add('opacity-0', '-translate-y-4');
        modalContent.classList.remove('opacity-100', 'translate-y-0');
        // Ocultar el modal después de que termine la animación
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300); // 300ms (igual que la transición)
    }

    // --- Event Listeners ---

    // 1. Escuchar clics en todos los botones que abren el modal
    document.querySelectorAll('.open-submenu-modal').forEach(button => {
        button.addEventListener('click', () => {
            const linea = button.dataset.linea;
            const submenus = JSON.parse(button.dataset.submenus);
            openModal(linea, submenus);
        });
    });

    // 2. Escuchar clic en el botón de cerrar (X)
    closeModalBtn.addEventListener('click', closeModal);

    // 3. Escuchar clic en el fondo oscuro (fuera del modal)
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

</script>

</body>
</html>
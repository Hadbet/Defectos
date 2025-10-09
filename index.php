<?php
// Se incluye el conector a la base de datos
include_once('dao/db/db_calidad.php');

$catalogo_defectos = []; // Inicializar un array vacío para el catálogo

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Preparar y ejecutar la consulta para obtener el catálogo de defectos
    $stmt = $conex->prepare("SELECT IdDefecto, Descripcion FROM Catalogo_Defectos WHERE Descripcion IS NOT NULL AND Descripcion != '' ORDER BY IdDefecto ASC");
    $stmt->execute();
    $result = $stmt->get_result();

    // Llenar el array con los resultados de la base de datos
    while ($row = $result->fetch_assoc()) {
        $catalogo_defectos[$row['IdDefecto']] = $row['Descripcion'];
    }

    $stmt->close();
    $conex->close();

} catch (Exception $e) {
    // En caso de error, el catálogo quedará vacío y el select no mostrará opciones.
    die("Error al conectar con la base de datos para cargar el catálogo de defectos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Defectos - Calidad</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .choices__input { background-color: #f8fafc !important; }
        .choices__list--dropdown { background-color: #f8fafc; border-color: #cbd5e1;}
        .choices__item--choice { color: #334155; }
        .choices[data-type*="select-one"]::after { border-color: #334155 transparent transparent; }
        .is-focused .choices__inner { border-color: #3b82f6 !important; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.4) !important;}
        .swal2-popup { font-family: 'Inter', sans-serif; }
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .table-row-animate { animation: slideIn 0.4s ease-out; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

<!-- Navegador -->
<nav class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0 font-bold text-xl text-blue-600">
                    GRAMMER
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="#" class="bg-blue-600 text-white px-3 py-2 rounded-md text-sm font-medium" aria-current="page">Inicio</a>
                        <a href="#" class="text-slate-500 hover:bg-blue-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Reportes</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Contenido Principal -->
<main class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Encabezado -->
        <div class="text-center mb-12 fade-in">
            <h1 class="text-4xl md:text-5xl font-extrabold text-slate-800">Registro de Defectos de Producción</h1>
            <p class="mt-3 max-w-2xl mx-auto text-lg text-slate-500">Introduce los datos para registrar un nuevo defecto. Los campos se pueden llenar con escáner.</p>
        </div>

        <!-- Formulario de Registro -->
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-2xl shadow-lg border border-slate-200/50 fade-in" style="animation-delay: 0.2s;">
            <form id="form-defecto">
                <div class="space-y-6">
                    <div>
                        <label for="nomina" class="block text-sm font-medium text-slate-600">1. Nómina del Operador</label>
                        <input type="text" id="nomina" name="nomina" required class="mt-1 block w-full bg-slate-50 border border-slate-300 rounded-md shadow-sm py-3 px-4 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label for="numero-parte" class="block text-sm font-medium text-slate-600">2. Número de Parte</label>
                        <input type="text" id="numero-parte" name="numero-parte" required class="mt-1 block w-full bg-slate-50 border border-slate-300 rounded-md shadow-sm py-3 px-4 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label for="estacion" class="block text-sm font-medium text-slate-600">3. Estación / Operación</label>
                        <input type="text" id="estacion" name="estacion" required class="mt-1 block w-full bg-slate-50 border border-slate-300 rounded-md shadow-sm py-3 px-4 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label for="codigo-defecto" class="block text-sm font-medium text-slate-600">4. Código de Defecto</label>
                        <select id="codigo-defecto" name="codigo-defecto" required>
                            <option value="">Selecciona o busca un código...</option>
                            <?php foreach ($catalogo_defectos as $codigo => $descripcion): ?>
                                <option value="<?php echo htmlspecialchars($codigo); ?>">
                                    <?php echo htmlspecialchars($codigo . ' - ' . $descripcion); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-8">
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-transform transform hover:scale-105">
                        Registrar Defecto
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla de Defectos del Día -->
        <div class="mt-16 fade-in" style="animation-delay: 0.4s;">
            <h2 class="text-2xl font-bold text-slate-700 mb-4 text-center">Registros de Hoy</h2>
            <div class="bg-white rounded-2xl shadow-lg border border-slate-200/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="p-4 text-sm font-semibold text-slate-600 tracking-wider">Hora</th>
                            <th class="p-4 text-sm font-semibold text-slate-600 tracking-wider">Nómina</th>
                            <th class="p-4 text-sm font-semibold text-slate-600 tracking-wider">No. Parte</th>
                            <th class="p-4 text-sm font-semibold text-slate-600 tracking-wider">Estación</th>
                            <th class="p-4 text-sm font-semibold text-slate-600 tracking-wider">Código de Defecto</th>
                        </tr>
                        </thead>
                        <tbody id="tabla-defectos-body">
                        <!-- Filas se insertarán aquí dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Footer -->
<footer class="mt-12 bg-white border-t border-slate-200">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 text-center text-sm text-slate-500">
        <p>&copy; <?php echo date('Y'); ?> Grammer Automotive. Todos los derechos reservados.</p>
        <p class="mt-1">Soporte por Hadbet Altamirano y Marco.</p>
    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('form-defecto');
        const nominaInput = document.getElementById('nomina');
        const tablaBody = document.getElementById('tabla-defectos-body');
        const selectElement = document.getElementById('codigo-defecto');

        // Se cambia a 'let' para poder reasignarla
        let choices = new Choices(selectElement, {
            searchEnabled: true,
            itemSelectText: 'Presiona para seleccionar',
            shouldSort: false,
        });

        // Función para cargar los defectos del día en la tabla
        const cargarDefectosDelDia = async () => {
            try {
                const response = await fetch('https://grammermx.com/calidad/defectos/dao/obtener_defectos_dia.php');
                if (!response.ok) throw new Error('Error de red al cargar los datos.');
                const result = await response.json();

                if (result.success) {
                    tablaBody.innerHTML = '';
                    if (result.data.length === 0) {
                        tablaBody.innerHTML = `<tr><td colspan="5" class="text-center p-6 text-slate-500">No hay defectos registrados hoy.</td></tr>`;
                    } else {
                        result.data.forEach(defecto => {
                            const row = document.createElement('tr');
                            row.className = 'border-b border-slate-200/80 hover:bg-slate-50/80 table-row-animate';
                            row.innerHTML = `
                                    <td class="p-4 text-slate-500">${defecto.Hora}</td>
                                    <td class="p-4 font-medium">${defecto.Nomina}</td>
                                    <td class="p-4 text-slate-600">${defecto.NumeroParte}</td>
                                    <td class="p-4 text-slate-600">${defecto.Estacion}</td>
                                    <td class="p-4 font-mono text-red-600">${defecto.CodigoDefecto}</td>
                                `;
                            tablaBody.appendChild(row);
                        });
                    }
                } else {
                    throw new Error(result.message || 'Error al procesar los datos.');
                }
            } catch (error) {
                tablaBody.innerHTML = `<tr><td colspan="5" class="text-center p-6 text-red-500">Error: ${error.message}</td></tr>`;
            }
        };

        // Evento de envío del formulario
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const datos = {
                nomina: document.getElementById('nomina').value,
                numeroParte: document.getElementById('numero-parte').value,
                estacion: document.getElementById('estacion').value,
                codigoDefecto: choices.getValue(true)
            };

            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Registrando...`;
            submitButton.disabled = true;

            try {
                const response = await fetch('https://grammermx.com/calidad/defectos/dao/registrar_defecto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(datos)
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Registrado!',
                        text: result.message,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    document.getElementById('nomina').value = '';
                    document.getElementById('numero-parte').value = '';
                    document.getElementById('estacion').value = '';

                    // === INICIO DE LA SOLUCIÓN DEFINITIVA: DESTRUIR Y RECREAR ===
                    // 1. Destruimos la instancia actual de Choices.js
                    choices.destroy();
                    // 2. Volvemos a inicializar Choices.js en el elemento <select> original.
                    // Esto lo fuerza a releer las opciones que siempre han estado en el HTML.
                    choices = new Choices(selectElement, {
                        searchEnabled: true,
                        itemSelectText: 'Presiona para seleccionar',
                        shouldSort: false,
                    });
                    // === FIN DE LA SOLUCIÓN DEFINITIVA ===

                    nominaInput.focus();
                    await cargarDefectosDelDia();
                } else {
                    throw new Error(result.message || 'Ocurrió un error desconocido.');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
            } finally {
                submitButton.innerHTML = originalButtonText;
                submitButton.disabled = false;
            }
        });

        cargarDefectosDelDia();
        nominaInput.focus();
    });
</script>
</body>
</html>


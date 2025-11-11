<?php
// Validar que se haya recibido una línea por URL
$linea_seleccionada = isset($_GET['linea']) ? htmlspecialchars($_GET['linea']) : '';
$sublinea_seleccionada = isset($_GET['sublinea']) ? htmlspecialchars($_GET['sublinea']) : ''; // Capturamos la sublínea

if (empty($linea_seleccionada)) {
    header('Location: index.php'); // Si no hay línea, redirigir al inicio
    exit;
}

// Unimos línea y sublínea si existe, para mostrarla y enviarla
$linea_completa = $linea_seleccionada;
if (!empty($sublinea_seleccionada)) {
    $linea_completa .= " - " . $sublinea_seleccionada;
}


include_once('dao/db/db_calidad.php');
$catalogo_defectos = [];
try {
    $con = new LocalConector();
    $conex = $con->conectar();
    // La consulta ahora ordena por ID, asegurando que 1, 2, 3 aparezcan primero
    $stmt = $conex->prepare("SELECT IdDefecto, Descripcion FROM Catalogo_Defectos ORDER BY IdDefecto ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $catalogo_defectos[$row['IdDefecto']] = $row['Descripcion'];
    }
    $stmt->close();
    $conex->close();
} catch (Exception $e) {
    die("Error al cargar el catálogo de defectos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Safe Launch - <?php echo $linea_completa; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Librería para exportar a imagen -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <!-- Librería para exportar a Excel (simple) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.0/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .ts-control {
            background-color: #f8fafc !important;
            border-color: #cbd5e1 !important;
            padding: 0.6rem!important;
        }
        .ts-control.focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.4) !important;
        }
        .ts-dropdown {
            background-color: #f8fafc;
            border-color: #cbd5e1;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Colores de Estado Base */
        .table-row-status-0 { background-color: #fffbeb !important; } /* Naranja/Defecto */
        .table-row-status-1 { background-color: #fefce8 !important; } /* Amarillo/Retrabajo */
        .table-row-status-2 { background-color: #f0fdf4 !important; } /* Verde/OK */
        .table-row-status-3 { background-color: #fee2e2 !important; } /* Rojo/Scrap */

        /* Clases de Badge de Estado */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            display: inline-block;
        }
        .status-0 { background-color: #fb923c; color: #78350f; } /* Naranja */
        .status-1 { background-color: #facc15; color: #713f12; } /* Amarillo */
        .status-2 { background-color: #4ade80; color: #14532d; } /* Verde */
        .status-3 { background-color: #f87171; color: #7f1d1d; } /* Rojo */

        /* Estilos para los contadores */
        .counter-card {
            background: linear-gradient(145deg, #ffffff, #e6e6e6);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 8px 8px 16px #c5c5c5, -8px -8px 16px #ffffff;
            border: 1px solid #ffffff;
        }
        .counter-number {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1;
        }
        .counter-label {
            font-size: 1rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

<!-- Navegador -->
<nav class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <a href="index_safe.php" class="flex-shrink-0 font-bold text-xl text-blue-600">GRAMMER</a>
                <div class="ml-10 flex items-baseline space-x-4">
                    <a href="index_safe.php" class="bg-blue-600 text-white px-3 py-2 rounded-md text-sm font-medium" aria-current="page">Inicio</a>
                    <!-- Corregido el enlace a reportes -->
                    <a href="reportes_safe_launch.php" class="text-slate-500 hover:bg-blue-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Reportes</a>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Contenido Principal -->
<main class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12 fade-in">
            <h1 class="text-4xl md:text-5xl font-extrabold text-slate-800">Registro de Safe Launch</h1>
            <p class="mt-3 max-w-2xl mx-auto text-lg text-slate-500">
                Línea <span class="font-bold text-blue-600"><?php echo $linea_completa; ?></span>.
            </p>
        </div>

        <!-- Layout de Formulario y Contadores -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

            <!-- Columna de Formulario (Ocupa 2 columnas en LG) -->
            <div class="lg:col-span-2 bg-white p-8 rounded-2xl shadow-lg border border-slate-200/50 fade-in" style="animation-delay: 0.2s;">
                <form id="form-defecto">
                    <!-- Campo oculto para la línea completa -->
                    <input type="hidden" id="linea" name="linea" value="<?php echo $linea_completa; ?>">

                    <div class="space-y-6">
                        <div>
                            <label for="nomina" class="block text-sm font-medium text-slate-600">1. Nómina del Operador</label>
                            <input type="text" id="nomina" name="nomina" required class="mt-1 block w-full bg-slate-50 border border-slate-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="numero-parte" class="block text-sm font-medium text-slate-600">2. Número de Parte</label>
                            <input type="text" id="numero-parte" name="numero-parte" required class="mt-1 block w-full bg-slate-50 border border-slate-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="serial" class="block text-sm font-medium text-slate-600">3. Serial</label>
                            <input type="text" id="serial" name="serial" required class="mt-1 block w-full bg-slate-50 border border-slate-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="estacion" class="block text-sm font-medium text-slate-600">4. Estación / Operación</label>
                            <input type="text" id="estacion" name="estacion" required class="mt-1 block w-full bg-slate-50 border border-slate-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="codigo-defecto" class="block text-sm font-medium text-slate-600">5. Código de Defecto</label>
                            <select id="codigo-defecto" name="codigo-defecto" required>
                                <option value="">Selecciona o busca un código...</option>
                                <?php foreach ($catalogo_defectos as $codigo => $descripcion): ?>
                                    <option value="<?php echo $codigo; ?>"><?php echo $codigo . ' - ' . $descripcion; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mt-8">
                        <!-- --- BOTÓN MODIFICADO --- -->
                        <button type="submit" id="submit-button" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50">
                            <!-- Spinner (oculto por defecto) -->
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" id="button-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <!-- Texto del botón -->
                            <span id="button-text">Registrar Defecto</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Columna de Contadores (Ocupa 1 columna en LG) -->
            <div class="fade-in" style="animation-delay: 0.3s;">
                <h3 class="text-2xl font-bold text-slate-700 mb-4 text-center lg:text-left">Contadores del Día</h3>
                <div class="space-y-6">
                    <div class="counter-card">
                        <div id="count-ok" class="counter-number text-green-600">0</div>
                        <div class="counter-label text-green-700">OK</div>
                    </div>
                    <div class="counter-card">
                        <div id="count-scrap" class="counter-number text-red-600">0</div>
                        <div class="counter-label text-red-700">Scrap</div>
                    </div>
                    <div class="counter-card">
                        <div id="count-defecto" class="counter-number text-orange-600">0</div>
                        <div class="counter-label text-orange-700">Defectos</div>
                    </div>
                    <div class="counter-card">
                        <div id="count-retrabajo" class="counter-number text-yellow-600">0</div>
                        <div class="counter-label text-yellow-700">Retrabajo</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Tabla de Registros -->
        <div class="mt-16 fade-in" style="animation-delay: 0.4s;">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-slate-700 text-center sm:text-left">
                    Registros de Hoy (Línea: <?php echo $linea_completa; ?>)
                </h2>
                <button id="btn-resumen-dia" class="mt-4 sm:mt-0 w-full sm:w-auto px-5 py-2 bg-blue-600 text-white font-medium rounded-md shadow-sm hover:bg-blue-700">
                    Ver Resumen del Día
                </button>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-200/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="p-4 text-sm font-semibold text-slate-600">Hora</th>
                            <th class="p-4 text-sm font-semibold text-slate-600">Nómina</th>
                            <th class="p-4 text-sm font-semibold text-slate-600">No. Parte</th>
                            <th class="p-4 text-sm font-semibold text-slate-600">Serial</th>
                            <th class="p-4 text-sm font-semibold text-slate-600">Estación</th>
                            <th class="p-4 text-sm font-semibold text-slate-600">Código</th>
                            <th class="p-4 text-sm font-semibold text-slate-600">Estado</th> <!-- Nueva Columna -->
                            <th class="p-4 text-sm font-semibold text-slate-600 text-center">Acciones</th>
                        </tr>
                        </thead>
                        <tbody id="tabla-defectos-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('form-defecto');
        const nominaInput = document.getElementById('nomina');
        const tablaBody = document.getElementById('tabla-defectos-body');
        const linea = document.getElementById('linea').value;

        // --- NUEVAS REFERENCIAS PARA EL BOTÓN Y SPINNER ---
        const submitButton = document.getElementById('submit-button');
        const buttonSpinner = document.getElementById('button-spinner');
        const buttonText = document.getElementById('button-text');

        // Contadores
        const countOkEl = document.getElementById('count-ok');
        const countScrapEl = document.getElementById('count-scrap');
        const countDefectoEl = document.getElementById('count-defecto');
        const countRetrabajoEl = document.getElementById('count-retrabajo');

        // Botón Resumen
        const btnResumenDia = document.getElementById('btn-resumen-dia');

        const tomSelect = new TomSelect('#codigo-defecto', {
            create: false,
            sortField: { field: "text", direction: "asc" }
        });

        // --- NUEVAS FUNCIONES PARA CONTROLAR EL SPINNER ---
        const showSpinner = () => {
            submitButton.disabled = true;
            buttonSpinner.classList.remove('hidden');
            buttonText.textContent = 'Registrando...';
        };

        const hideSpinner = () => {
            submitButton.disabled = false;
            buttonSpinner.classList.add('hidden');
            buttonText.textContent = 'Registrar Defecto';
        };

        // Helper para mapear estado
        const getEstadoInfo = (status) => {
            const numStatus = parseInt(status, 10);
            switch (numStatus) {
                case 0: return { text: 'DEFECTO', class: 'status-0' };
                case 1: return { text: 'RETRABAJO', class: 'status-1' };
                case 2: return { text: 'OK', class: 'status-2' };
                case 3: return { text: 'SCRAPT', class: 'status-3' }; // 'Scrapt' como en tu BDD
                default: return { text: 'N/A', class: '' };
            }
        };

        const cargarDefectosDelDia = async () => {
            try {
                // (El resto de la función es igual)
                const response = await fetch(`https://grammermx.com/calidad/defectos/dao/obtener_safe_launch_dia.php?linea=${encodeURIComponent(linea)}`);
                const result = await response.json();

                if (result.success) {
                    tablaBody.innerHTML = '';
                    let countOk = 0, countScrap = 0, countDefecto = 0, countRetrabajo = 0;

                    if (result.data.length === 0) {
                        tablaBody.innerHTML = `<tr><td colspan="8" class="text-center p-6 text-slate-500">No hay registros hoy para esta línea.</td></tr>`;
                    } else {
                        result.data.forEach(defecto => {
                            switch (parseInt(defecto.Estado, 10)) {
                                case 0: countDefecto++; break;
                                case 1: countRetrabajo++; break;
                                case 2: countOk++; break;
                                case 3: countScrap++; break;
                            }
                            const row = document.createElement('tr');
                            const estadoInfo = getEstadoInfo(defecto.Estado);
                            row.className = `border-b border-slate-200/80 hover:bg-slate-50/80 table-row-status-${defecto.Estado}`;
                            row.dataset.id = defecto.IdSafeLaunch;
                            const liberarButtonHtml = parseInt(defecto.Estado, 10) !== 2 ?
                                `<button class="p-2 text-green-500 hover:text-green-700 btn-liberar" title="Liberar"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg></button>` :
                                '';
                            row.innerHTML = `
                            <td class="p-4 text-slate-500">${defecto.Hora}</td>
                            <td class="p-4 font-medium">${defecto.Nomina}</td>
                            <td class="p-4" data-field="numeroParte">${defecto.NumeroParte}</td>
                            <td class="p-4" data-field="serial">${defecto.Serial}</td>
                            <td class="p-4" data-field="estacion">${defecto.Estacion}</td>
                            <td class="p-4 font-mono text-red-600">${defecto.CodigoDefecto}</td>
                            <td class="p-4"><span class="status-badge ${estadoInfo.class}">${estadoInfo.text}</span></td>
                            <td class="p-4 text-center">
                                <div class="flex justify-center items-center space-x-2">
                                    <button class="p-2 text-blue-500 hover:text-blue-700 btn-edit" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /></svg></button>
                                    ${liberarButtonHtml}
                                    <button class="p-2 text-red-500 hover:text-red-700 btn-eliminar" title="Eliminar"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 012 0v6a1 1 0 11-2 0V8z" clip-rule="evenodd" /></svg></button>
                                </div>
                            </td>`;
                            tablaBody.appendChild(row);
                        });
                    }
                    countOkEl.textContent = countOk;
                    countScrapEl.textContent = countScrap;
                    countDefectoEl.textContent = countDefecto;
                    countRetrabajoEl.textContent = countRetrabajo;
                }
            } catch (error) {
                tablaBody.innerHTML = `<tr><td colspan="8" class="text-center p-6 text-red-500">Error: ${error.message}</td></tr>`;
            }
        };

        // --- LISTENER DEL FORMULARIO MODIFICADO ---
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // 1. Mostrar Spinner
            showSpinner();

            const datos = {
                linea: linea,
                nomina: document.getElementById('nomina').value,
                numeroParte: document.getElementById('numero-parte').value,
                estacion: document.getElementById('estacion').value,
                serial: document.getElementById('serial').value,
                codigoDefecto: tomSelect.getValue()
            };

            try {
                const response = await fetch('https://grammermx.com/calidad/defectos/dao/registrar_safe_launch.php', {
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
                    form.reset();
                    tomSelect.clear();
                    nominaInput.focus();
                    await cargarDefectosDelDia(); // Recargar tabla y contadores
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error.message });
            } finally {
                // 2. Ocultar Spinner (en try...finally para que se oculte SIEMPRE)
                hideSpinner();
            }
        });

        // --- MANEJO DE ACCIONES DE LA TABLA (Sin cambios) ---
        tablaBody.addEventListener('click', async (e) => {
            const button = e.target.closest('button');
            if (!button) return;
            const row = button.closest('tr');
            const id = row.dataset.id;

            // ... (Lógica de btn-eliminar)
            if (button.classList.contains('btn-eliminar')) {
                const result = await Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡No podrás revertir esta acción!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Sí, ¡eliminar!'
                });
                if (result.isConfirmed) {
                    fetch('https://grammermx.com/calidad/defectos/dao/eliminar_safe_launch.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    })
                        .then(res => res.json()).then(data => {
                        if (data.success) {
                            Swal.fire('¡Eliminado!', data.message, 'success');
                            cargarDefectosDelDia(); // Recargar tabla y contadores
                        } else throw new Error(data.message);
                    }).catch(err => Swal.fire('Error', err.message, 'error'));
                }
            }
            // ... (Lógica de btn-liberar)
            else if (button.classList.contains('btn-liberar')) {
                const { value: formValues } = await Swal.fire({
                    title: 'Disposición del Defecto',
                    html: `
                    <div class="text-left">
                        <label for="swal-status" class="swal2-label">Acción a tomar:</label>
                        <select id="swal-status" class="swal2-select">
                            <option value="1">Retrabajo</option>
                            <option value="2">OK</option>
                            <option value="3">Scrap</option>
                            <option value="0">Defecto (Pendiente)</option>
                        </select>
                        <label for="swal-comentario" class="swal2-label mt-4">Comentarios (Opcional):</label>
                        <textarea id="swal-comentario" class="swal2-textarea" placeholder="Añade una nota..."></textarea>
                    </div>`,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar Disposición',
                    preConfirm: () => {
                        const status = document.getElementById('swal-status').value;
                        if (!status) {
                            Swal.showValidationMessage('Debes seleccionar una acción');
                            return false;
                        }
                        return {
                            status: status,
                            comentario: document.getElementById('swal-comentario').value
                        }
                    }
                });

                if (formValues) {
                    fetch('https://grammermx.com/calidad/defectos/dao/liberar_defecto_safe_launch.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id, ...formValues })
                    })
                        .then(res => res.json()).then(data => {
                        if (data.success) {
                            Swal.fire('¡Actualizado!', data.message, 'success');
                            cargarDefectosDelDia(); // Recargar tabla y contadores
                        } else throw new Error(data.message);
                    }).catch(err => Swal.fire('Error', err.message, 'error'));
                }
            }
            // ... (Lógica de btn-edit)
            else if (button.classList.contains('btn-edit')) {
                const parteActual = row.querySelector('[data-field="numeroParte"]').textContent;
                const serialActual = row.querySelector('[data-field="serial"]').textContent;
                const estacionActual = row.querySelector('[data-field="estacion"]').textContent;
                const estadoActual = Array.from(row.classList).find(c => c.startsWith('table-row-status-')).split('-')[3];

                const { value: formValues } = await Swal.fire({
                    title: 'Editar Registro',
                    html:
                        `<label for="swal-parte" class="swal2-label text-left w-full block pl-2">Número de Parte</label>` +
                        `<input id="swal-parte" class="swal2-input" value="${parteActual}">` +
                        `<label for="swal-serial" class="swal2-label text-left w-full block pl-2">Serial</label>` +
                        `<input id="swal-serial" class="swal2-input" value="${serialActual}">` +
                        `<label for="swal-estacion" class="swal2-label text-left w-full block pl-2">Estación</label>` +
                        `<input id="swal-estacion" class="swal2-input" value="${estacionActual}">` +
                        `<label for="swal-estado-edit" class="swal2-label text-left w-full block pl-2">Estado</label>` +
                        `<select id="swal-estado-edit" class="swal2-select">
                        <option value="0" ${estadoActual == 0 ? 'selected' : ''}>Defecto</option>
                        <option value="1" ${estadoActual == 1 ? 'selected' : ''}>Retrabajo</option>
                        <option value="2" ${estadoActual == 2 ? 'selected' : ''}>OK</option>
                        <option value="3" ${estadoActual == 3 ? 'selected' : ''}>Scrap</option>
                    </select>`,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar Cambios',
                    preConfirm: () => ({
                        numeroParte: document.getElementById('swal-parte').value,
                        serial: document.getElementById('swal-serial').value,
                        estacion: document.getElementById('swal-estacion').value,
                        estado: document.getElementById('swal-estado-edit').value
                    })
                });

                if (formValues) {
                    fetch('https://grammermx.com/calidad/defectos/dao/actualizar_safe_launch.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id, ...formValues })
                    })
                        .then(res => res.json()).then(data => {
                        if (data.success) {
                            Swal.fire('¡Actualizado!', data.message, 'success');
                            cargarDefectosDelDia(); // Recargar tabla y contadores
                        } else throw new Error(data.message);
                    }).catch(err => Swal.fire('Error', err.message, 'error'));
                }
            }
        });

        // --- Bitácora de Cambios (Doble Clic) (Sin cambios) ---
        tablaBody.addEventListener('dblclick', async (e) => {
            const row = e.target.closest('tr');
            if (!row || !row.dataset.id) return;
            const id = row.dataset.id;

            try {
                const response = await fetch(`https://grammermx.com/calidad/defectos/dao/obtener_bitacora.php?id=${id}`);
                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                let tableHtml = '<table class="swal2-table w-full text-left border-collapse">';
                tableHtml += `<thead><tr class="bg-slate-100">
                            <th class="p-2 border">Fecha</th>
                            <th class="p-2 border">Acción</th>
                            <th class="p-2 border">Campo Modificado</th>
                            <th class="p-2 border">Valor Anterior</th>
                          </tr></thead><tbody>`;

                if (result.data.length === 0) {
                    tableHtml += '<tr><td colspan="4" class="text-center p-4">No hay historial de cambios para este registro.</td></tr>';
                } else {
                    result.data.forEach(log => {
                        tableHtml += `<tr class="border-b">
                                    <td class="p-2 border">${log.Fecha}</td>
                                    <td class="p-2 border">${log.Accion}</td>
                                    <td class="p-2 border">${log.CampoModificado || 'N/A'}</td>
                                    <td class="p-2 border">${log.ValorAnterior || 'N/A'}</td>
                                  </tr>`;
                    });
                }
                tableHtml += '</tbody></table>';

                Swal.fire({
                    title: `Historial de Cambios (ID: ${id})`,
                    html: tableHtml,
                    width: '800px',
                    showConfirmButton: true
                });

            } catch (error) {
                Swal.fire('Error', `No se pudo cargar la bitácora: ${error.message}`, 'error');
            }
        });

        // --- Resumen del Día (Sin cambios) ---
        btnResumenDia.addEventListener('click', async () => {
            try {
                const response = await fetch(`https://grammermx.com/calidad/defectos/dao/obtener_resumen_dia.php?linea=${encodeURIComponent(linea)}`);
                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                let tableHtml = `<div class="max-h-96 overflow-y-auto">
                             <table id="tabla-resumen" class="w-full text-left border-collapse">
                             <thead class="sticky top-0 bg-white shadow-sm">
                               <tr class="bg-slate-100">
                                <th class="p-3 border">No. Parte</th>
                                <th class="p-3 border">Estación</th>
                                <th class="p-3 border">Serial</th>
                                <th class="p-3 border text-center text-green-600">OK</th>
                                <th class="p-3 border text-center text-yellow-600">Retrabajo</th>
                                <th class="p-3 border text-center text-red-600">Scrap</th>
                                <th class="p-3 border text-center text-orange-600">Pendiente</th>
                               </tr>
                             </thead><tbody>`;

                if (result.data.length === 0) {
                    tableHtml += '<tr><td colspan="7" class="text-center p-4">No hay datos para resumir hoy.</td></tr>';
                } else {
                    result.data.forEach(row => {
                        tableHtml += `<tr class="border-b">
                                    <td class="p-2 border">${row.NumeroParte}</td>
                                    <td class="p-2 border">${row.Estacion}</td>
                                    <td class="p-2 border">${row.Serial}</td>
                                    <td class="p-2 border text-center font-bold">${row.Cantidad_OK}</td>
                                    <td class="p-2 border text-center font-bold">${row.Cantidad_Retrabajo}</td>
                                    <td class="p-2 border text-center font-bold">${row.Cantidad_Scrap}</td>
                                    <td class="p-2 border text-center font-bold">${row.Cantidad_Pendientes}</td>
                                  </tr>`;
                    });
                }
                tableHtml += '</tbody></table></div>';

                Swal.fire({
                    title: `Resumen del Día - Línea: ${linea}`,
                    html: tableHtml,
                    width: '900px',
                    showCancelButton: true,
                    confirmButtonText: 'Descargar Excel',
                    cancelButtonText: 'Cerrar',
                    showDenyButton: true,
                    denyButtonText: 'Descargar Imagen'
                }).then((result) => {
                    if (result.isConfirmed) {
                        exportTableToExcel('tabla-resumen', `Resumen_${linea}_${new Date().toISOString().split('T')[0]}.xlsx`);
                    } else if (result.isDenied) {
                        exportModalToImage('tabla-resumen', `Resumen_${linea}_${new Date().toISOString().split('T')[0]}.png`);
                    }
                });

            } catch (error) {
                Swal.fire('Error', `No se pudo cargar el resumen: ${error.message}`, 'error');
            }
        });

        // --- Funciones de Exportación (Sin cambios) ---
        function exportTableToExcel(tableId, filename = '') {
            const table = document.getElementById(tableId);
            const wb = XLSX.utils.table_to_book(table, { sheet: "Resumen" });
            XLSX.writeFile(wb, filename);
        }

        function exportModalToImage(elementId, filename = '') {
            const element = document.getElementById(elementId);
            if (!element) {
                Swal.fire('Error', 'No se encontró la tabla para capturar', 'error');
                return;
            }
            Swal.fire({
                title: 'Generando imagen...',
                text: 'Por favor espera.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            html2canvas(element, {
                scale: 2,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                canvas.toBlob(function(blob) {
                    Swal.close();
                    saveAs(blob, filename);
                });
            }).catch(err => {
                Swal.fire('Error', `No se pudo generar la imagen: ${err.message}`, 'error');
            });
        }

        // Carga inicial
        cargarDefectosDelDia();
        nominaInput.focus();
    });
</script>
</body>
</html>


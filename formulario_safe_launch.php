<?php
// Validar que se haya recibido una línea por URL
$linea_seleccionada = isset($_GET['linea']) ? htmlspecialchars($_GET['linea']) : '';
if (empty($linea_seleccionada)) {
    header('Location: index.php'); // Si no hay línea, redirigir al inicio
    exit;
}

include_once('dao/db/db_calidad.php');
$catalogo_defectos = [];
try {
    $con = new LocalConector();
    $conex = $con->conectar();
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
    <title>Registro de Defectos - <?php echo $linea_seleccionada; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .ts-control {
            background-color: #f8fafc !important;
            border-color: #cbd5e1 !important;
            padding: 0.6rem !important;
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
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .table-row-status-1 {
            background-color: #fefce8 !important;
        }

        /* Amarillo para Retrabajo */
        .table-row-status-2 {
            background-color: #fee2e2 !important;
        }

        /* Rojo para Scrap */
        .table-row-status-3 {
            background-color: #f0fdf4 !important;
        }

        /* Verde para Liberado */
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

<!-- Navegador -->
<nav class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <a href="index.php" class="flex-shrink-0 font-bold text-xl text-blue-600">GRAMMER</a>
                <div class="ml-10 flex items-baseline space-x-4">
                    <a href="index.php" class="bg-blue-600 text-white px-3 py-2 rounded-md text-sm font-medium" aria-current="page">Inicio</a>
                    <a href="reportes.php" class="text-slate-500 hover:bg-blue-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Reportes</a>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Contenido Principal -->
<main class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Formulario y Tabla de Defectos (código HTML sin cambios) -->
        <div class="text-center mb-12 fade-in">
            <h1 class="text-4xl md:text-5xl font-extrabold text-slate-800">Registro de Defectos de Producción</h1>
            <p class="mt-3 max-w-2xl mx-auto text-lg text-slate-500">Introduce los datos para registrar un nuevo defecto
                en la línea <span class="font-bold text-blue-600"><?php echo $linea_seleccionada; ?></span>.</p>
        </div>

        <div class="max-w-2xl mx-auto bg-white p-8 rounded-2xl shadow-lg border border-slate-200/50 fade-in"
             style="animation-delay: 0.2s;">
            <form id="form-defecto">
                <input type="hidden" id="linea" name="linea" value="<?php echo $linea_seleccionada; ?>">
                <div class="space-y-6">
                    <div>
                        <label for="nomina" class="block text-sm font-medium text-slate-600">1. Nómina del
                            Operador</label>
                        <input type="text" id="nomina" name="nomina" required
                               class="mt-1 block w-full bg-slate-50 border border-slate-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="numero-parte" class="block text-sm font-medium text-slate-600">2. Número de
                            Parte</label>
                        <input type="text" id="numero-parte" name="numero-parte" required
                               class="mt-1 block w-full bg-slate-50 border border-slate-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="serial" class="block text-sm font-medium text-slate-600">3. Serial</label>
                        <input type="text" id="serial" name="serial" required
                               class="mt-1 block w-full bg-slate-50 border border-slate-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="estacion" class="block text-sm font-medium text-slate-600">4. Estación /
                            Operación</label>
                        <input type="text" id="estacion" name="estacion" required
                               class="mt-1 block w-full bg-slate-50 border border-slate-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="codigo-defecto" class="block text-sm font-medium text-slate-600">5. Código de
                            Defecto</label>
                        <select id="codigo-defecto" name="codigo-defecto" required>
                            <option value="">Selecciona o busca un código...</option>
                            <?php foreach ($catalogo_defectos as $codigo => $descripcion): ?>
                                <option value="<?php echo $codigo; ?>"><?php echo $codigo . ' - ' . $descripcion; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-8">
                    <button type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Registrar Defecto
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-16 fade-in" style="animation-delay: 0.4s;">
            <h2 class="text-2xl font-bold text-slate-700 mb-4 text-center">Registros de Hoy
                (Línea: <?php echo $linea_seleccionada; ?>)</h2>
            <div class="bg-white rounded-2xl shadow-lg border border-slate-200/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="p-4 text-sm font-semibold text-slate-600">Hora</th>
                            <th class="p-4 text-sm font-semibold text-slate-600">Nómina</th>
                            <th class="p-4 text-sm font-semibold text-slate-600">No. Parte</th>
                            <th class="p-4 text-sm font-semibold text-slate-600">Estación</th>
                            <th class="p-4 text-sm font-semibold text-slate-600">Código</th>
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

        const tomSelect = new TomSelect('#codigo-defecto', {
            create: false,
            sortField: {field: "text", direction: "asc"}
        });

        const cargarDefectosDelDia = async () => {
            try {
                const response = await fetch(`https://grammermx.com/calidad/defectos/dao/obtener_defectos_dia.php?linea=${encodeURIComponent(linea)}`);
                const result = await response.json();
                if (result.success) {
                    tablaBody.innerHTML = '';
                    if (result.data.length === 0) {
                        tablaBody.innerHTML = `<tr><td colspan="6" class="text-center p-6 text-slate-500">No hay defectos registrados hoy para esta línea.</td></tr>`;
                    } else {
                        result.data.forEach(defecto => {
                            const row = document.createElement('tr');
                            // Asignar clase de color según el Status
                            const statusClass = {
                                1: 'table-row-status-1', // Retrabajo
                                2: 'table-row-status-2', // Scrap
                                3: 'table-row-status-3'  // Liberado
                            }[defecto.Status] || '';

                            row.className = `border-b border-slate-200/80 hover:bg-slate-50/80 ${statusClass}`;
                            row.dataset.id = defecto.IdDefecto;
                            row.innerHTML = `
                            <td class="p-4 text-slate-500">${defecto.Hora}</td>
                            <td class="p-4 font-medium">${defecto.Nomina}</td>
                            <td class="p-4" data-field="numeroParte">${defecto.NumeroParte}</td>
                            <td class="p-4" data-field="estacion">${defecto.Estacion}</td>
                            <td class="p-4 font-mono text-red-600">${defecto.CodigoDefecto}</td>
                            <td class="p-4 text-center">
                                <div class="flex justify-center items-center space-x-2">
                                    <button class="p-2 text-blue-500 hover:text-blue-700 btn-edit" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /></svg></button>
                                    <button class="p-2 text-green-500 hover:text-green-700 btn-liberar" title="Liberar"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg></button>
                                    <button class="p-2 text-red-500 hover:text-red-700 btn-eliminar" title="Eliminar"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 012 0v6a1 1 0 11-2 0V8z" clip-rule="evenodd" /></svg></button>
                                </div>
                            </td>`;
                            tablaBody.appendChild(row);
                        });
                    }
                }
            } catch (error) {
                tablaBody.innerHTML = `<tr><td colspan="6" class="text-center p-6 text-red-500">Error: ${error.message}</td></tr>`;
            }
        };

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const datos = {
                linea: linea,
                nomina: document.getElementById('nomina').value,
                numeroParte: document.getElementById('numero-parte').value,
                estacion: document.getElementById('estacion').value,
                serial: document.getElementById('serial').value,
                codigoDefecto: tomSelect.getValue()
            };
            try {
                const response = await fetch('https://grammermx.com/calidad/defectos/dao/registrar_defecto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
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
                    await cargarDefectosDelDia();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                Swal.fire({icon: 'error', title: 'Error', text: error.message});
            }
        });

        tablaBody.addEventListener('click', async (e) => {
            const button = e.target.closest('button');
            if (!button) return;

            const row = button.closest('tr');
            const id = row.dataset.id;

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
                    fetch('https://grammermx.com/calidad/defectos/dao/eliminar_defecto.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id})
                    })
                        .then(res => res.json()).then(data => {
                        if (data.success) {
                            Swal.fire('¡Eliminado!', data.message, 'success');
                            cargarDefectosDelDia();
                        } else throw new Error(data.message);
                    }).catch(err => Swal.fire('Error', err.message, 'error'));
                }
            } else if (button.classList.contains('btn-liberar')) {
                const {value: formValues} = await Swal.fire({
                    title: 'Disposición del Defecto',
                    html: `
                        <div class="text-left">
                            <label for="swal-status" class="swal2-label">Acción a tomar:</label>
                            <select id="swal-status" class="swal2-select">
                                <option value="1">Retrabajo</option>
                                <option value="2">Scrap</option>
                                <option value="3">Liberado</option>
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
                    fetch('https://grammermx.com/calidad/defectos/dao/liberar_defecto.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id, ...formValues})
                    })
                        .then(res => res.json()).then(data => {
                        if (data.success) {
                            Swal.fire('¡Actualizado!', data.message, 'success');
                            cargarDefectosDelDia();
                        } else throw new Error(data.message);
                    }).catch(err => Swal.fire('Error', err.message, 'error'));
                }
            } else if (button.classList.contains('btn-edit')) {
                const parteActual = row.querySelector('[data-field="numeroParte"]').textContent;
                const estacionActual = row.querySelector('[data-field="estacion"]').textContent;

                const {value: formValues} = await Swal.fire({
                    title: 'Editar Registro',
                    html:
                        `<input id="swal-parte" class="swal2-input" value="${parteActual}">` +
                        `<input id="swal-estacion" class="swal2-input" value="${estacionActual}">`,
                    focusConfirm: false,
                    preConfirm: () => ({
                        numeroParte: document.getElementById('swal-parte').value,
                        estacion: document.getElementById('swal-estacion').value
                    })
                });
                if (formValues) {
                    fetch('https://grammermx.com/calidad/defectos/dao/actualizar_defecto.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id, ...formValues})
                    })
                        .then(res => res.json()).then(data => {
                        if (data.success) {
                            Swal.fire('¡Actualizado!', data.message, 'success');
                            cargarDefectosDelDia();
                        } else throw new Error(data.message);
                    }).catch(err => Swal.fire('Error', err.message, 'error'));
                }
            }
        });

        cargarDefectosDelDia();
        nominaInput.focus();
    });
</script>
</body>
</html>


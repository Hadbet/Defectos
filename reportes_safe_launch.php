<?php
// Lista de líneas actualizada para el filtro.
$lineas_disponibles = ["XNF", "BR167", "L234", "BMW G0S", "INSITU"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Safe Launch</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SheetJS (xlsx) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- FileSaver.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.0/FileSaver.min.js"></script>
    <!-- html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        #tabla-body tr {
            opacity: 0;
            animation: tableRowIn 0.5s ease-out forwards;
        }
        @keyframes tableRowIn {
            from { opacity: 0; transform: translateY(10px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        /* Clases de Badge de Estado (NUEVO) */
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
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

<!-- Navegador -->
<nav class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <a href="index_safe.php" class="flex-shrink-0 font-bold text-xl text-blue-600">GRAMMER</a>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="index_safe.php" class="text-slate-500 hover:bg-blue-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Inicio</a>
                        <a href="#" class="bg-blue-600 text-white px-3 py-2 rounded-md text-sm font-medium" aria-current="page">Reportes</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Contenido Principal -->
<main class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <header class="text-center mb-10">
            <h1 class="text-4xl md:text-5xl font-extrabold text-slate-800 tracking-tight">Reportes de Safe Launch</h1>
            <p class="mt-3 text-lg text-slate-500">Filtra y visualiza los datos de producción.</p>
        </header>

        <!-- Filtros -->
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-200/50 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <div>
                    <label for="fecha-inicio" class="block text-sm font-medium text-slate-600">Fecha de Inicio</label>
                    <input type="date" id="fecha-inicio" class="mt-1 block w-full bg-slate-50 border-slate-300 rounded-md shadow-sm py-2 px-3">
                </div>
                <div>
                    <label for="fecha-fin" class="block text-sm font-medium text-slate-600">Fecha de Fin</label>
                    <input type="date" id="fecha-fin" class="mt-1 block w-full bg-slate-50 border-slate-300 rounded-md shadow-sm py-2 px-3">
                </div>
                <div>
                    <label for="filtro-linea" class="block text-sm font-medium text-slate-600">Línea</label>
                    <select id="filtro-linea" class="mt-1 block w-full bg-slate-50 border-slate-300 rounded-md shadow-sm py-2 px-3">
                        <option value="todas">Todas las líneas</option>
                        <?php foreach ($lineas_disponibles as $linea): ?>
                            <option value="<?php echo $linea; ?>"><?php echo $linea; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button id="btn-consultar" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                    Consultar
                </button>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-200/50">
                <h2 class="text-xl font-bold text-slate-700 mb-4">Top Defectos Registrados</h2>
                <canvas id="grafico-defectos-tipo"></canvas>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-200/50">
                <h2 class="text-xl font-bold text-slate-700 mb-4">Registros por Día</h2>
                <canvas id="grafico-defectos-dia"></canvas>
            </div>
        </div>

        <!-- Tabla de Datos -->
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-200/50">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
                <h2 class="text-xl font-bold text-slate-700">Registros Detallados</h2>
                <div class="flex items-center gap-4">
                    <button id="btn-resumen" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50" disabled>
                        Ver Resumen
                    </button>
                    <button id="btn-exportar" class="bg-green-600 text-white font-bold py-2 px-4 rounded-md hover:bg-green-700 transition-colors disabled:opacity-50" disabled>
                        Exportar Detalle
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table id="tabla-registros" class="w-full text-left">
                    <thead class="bg-slate-50">
                    <tr>
                        <th class="p-3 text-sm font-semibold text-slate-600 uppercase tracking-wider">ID</th>
                        <th class="p-3 text-sm font-semibold text-slate-600 uppercase tracking-wider">Fecha</th>
                        <th class="p-3 text-sm font-semibold text-slate-600 uppercase tracking-wider">Línea</th>
                        <th class="p-3 text-sm font-semibold text-slate-600 uppercase tracking-wider">Nómina</th>
                        <th class="p-3 text-sm font-semibold text-slate-600 uppercase tracking-wider">No. Parte</th>
                        <th class="p-3 text-sm font-semibold text-slate-600 uppercase tracking-wider">Serial</th>
                        <th class="p-3 text-sm font-semibold text-slate-600 uppercase tracking-wider">Estación</th>
                        <th class="p-3 text-sm font-semibold text-slate-600 uppercase tracking-wider">Defecto</th>
                        <th class="p-3 text-sm font-semibold text-slate-600 uppercase tracking-wider">Estado</th>
                        <th class="p-3 text-sm font-semibold text-slate-600 uppercase tracking-wider">Comentarios</th>
                    </tr>
                    </thead>
                    <tbody id="tabla-body">
                    <tr><td colspan="10" class="text-center p-6 text-slate-500">Selecciona un rango de fechas y presiona "Consultar".</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let graficoTipo, graficoDia;

        const fechaFinInput = document.getElementById('fecha-fin');
        const fechaInicioInput = document.getElementById('fecha-inicio');
        const btnExportar = document.getElementById('btn-exportar');
        const btnResumen = document.getElementById('btn-resumen');

        const hoy = new Date();
        const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        fechaFinInput.value = hoy.toISOString().split('T')[0];
        fechaInicioInput.value = primerDiaMes.toISOString().split('T')[0];

        // Helper para mapear estado (copiado de formulario_defectos.php)
        const getEstadoInfo = (status) => {
            const numStatus = parseInt(status, 10);
            switch (numStatus) {
                case 0: return { text: 'DEFECTO', class: 'status-0' };
                case 1: return { text: 'RETRABAJO', class: 'status-1' };
                case 2: return { text: 'OK', class: 'status-2' };
                case 3: return { text: 'SCRAPT', class: 'status-3' };
                default: return { text: 'N/A', class: '' };
            }
        };

        const renderizarGraficoTipo = (data) => {
            const ctx = document.getElementById('grafico-defectos-tipo').getContext('2d');
            if (graficoTipo) graficoTipo.destroy();

            // Tomamos solo el Top 10 para que sea legible
            const topData = data.slice(0, 10);

            graficoTipo = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: topData.map(d => d.Descripcion),
                    datasets: [{
                        label: 'Total de Registros',
                        data: topData.map(d => d.Total),
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: 'Top 10 de registros por tipo' }
                    }
                }
            });
        };

        const renderizarGraficoDia = (data) => {
            const ctx = document.getElementById('grafico-defectos-dia').getContext('2d');
            if (graficoDia) graficoDia.destroy();
            graficoDia = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.Dia),
                    datasets: [{
                        label: 'Registros Totales',
                        data: data.map(d => d.Total),
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: { responsive: true }
            });
        };

        const poblarTabla = (data) => {
            const tablaBody = document.getElementById('tabla-body');
            tablaBody.innerHTML = '';

            if (data.length === 0) {
                tablaBody.innerHTML = `<tr><td colspan="10" class="text-center p-6 text-slate-500">No se encontraron registros con los filtros seleccionados.</td></tr>`;
                btnExportar.disabled = true;
                btnResumen.disabled = true;
                return;
            }

            btnExportar.disabled = false;
            btnResumen.disabled = false;
            data.forEach((row, index) => {
                const tr = document.createElement('tr');
                const estadoInfo = getEstadoInfo(row.Estado);
                tr.className = 'border-b border-slate-200/80 even:bg-slate-50/50 hover:bg-blue-50/80';
                tr.style.animationDelay = `${index * 0.03}s`; // Animación escalonada
                tr.innerHTML = `
                    <td class="p-3 text-sm">${row.IdSafeLaunch}</td>
                    <td class="p-3 text-sm">${row.Fecha}</td>
                    <td class="p-3 text-sm">${row.Linea}</td>
                    <td class="p-3 text-sm">${row.Nomina}</td>
                    <td class="p-3 text-sm">${row.NumeroParte}</td>
                    <td class="p-3 text-sm">${row.Serial}</td>
                    <td class="p-3 text-sm">${row.Estacion}</td>
                    <td class="p-3 text-sm">${row.CodigoDefecto} - ${row.DescripcionDefecto}</td>
                    <td class="p-3 text-sm"><span class="status-badge ${estadoInfo.class}">${estadoInfo.text}</span></td>
                    <td class="p-3 text-sm">${row.Comentarios || ''}</td>
                `;
                tablaBody.appendChild(tr);
            });
        };

        const consultarDatos = async () => {
            const fechaInicio = fechaInicioInput.value;
            const fechaFin = fechaFinInput.value;
            const linea = document.getElementById('filtro-linea').value;

            if (!fechaInicio || !fechaFin) {
                Swal.fire('Error', 'Por favor, selecciona un rango de fechas.', 'error');
                return;
            }

            const btn = document.getElementById('btn-consultar');
            btn.disabled = true;
            btn.innerHTML = 'Consultando...';
            btnExportar.disabled = true;
            btnResumen.disabled = true;

            try {
                const response = await fetch(`https://grammermx.com/calidad/defectos/dao/obtener_reportes_safe_launch.php?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&linea=${linea}`);
                const result = await response.json();

                if (!result.success) throw new Error(result.message);

                renderizarGraficoTipo(result.data.defectosPorTipo);
                renderizarGraficoDia(result.data.defectosPorDia); // Cambiado de Mes a Dia
                poblarTabla(result.data.tablaRegistros);

            } catch (error) {
                Swal.fire('Error', `No se pudieron cargar los datos: ${error.message}`, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Consultar';
            }
        };

        // --- ACCIÓN BOTÓN "VER RESUMEN" ---
        btnResumen.addEventListener('click', async () => {
            const fechaInicio = fechaInicioInput.value;
            const fechaFin = fechaFinInput.value;
            const linea = document.getElementById('filtro-linea').value;
            const lineaNombre = linea === 'todas' ? 'Todas' : linea;

            try {
                const response = await fetch(`https://grammermx.com/calidad/defectos/dao/obtener_resumen_rango.php?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&linea=${linea}`);
                const result = await response.json();

                if (!result.success) throw new Error(result.message);

                let tableHtml = `<div class="max-h-96 overflow-y-auto">
                                 <table id="tabla-resumen-rango" class="w-full text-left border-collapse">
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
                    tableHtml += '<tr><td colspan="7" class="text-center p-4">No hay datos para resumir en este rango.</td></tr>';
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
                    title: `Resumen - Línea: ${lineaNombre} (${fechaInicio} al ${fechaFin})`,
                    html: tableHtml,
                    width: '900px',
                    showCancelButton: true,
                    confirmButtonText: 'Descargar Excel',
                    cancelButtonText: 'Cerrar',
                    showDenyButton: true,
                    denyButtonText: 'Descargar Imagen'
                }).then((result) => {
                    const hoy = new Date().toISOString().split('T')[0];
                    if (result.isConfirmed) {
                        exportTableToExcel('tabla-resumen-rango', `Resumen_${lineaNombre}_${hoy}.xlsx`);
                    } else if (result.isDenied) {
                        exportModalToImage('tabla-resumen-rango', `Resumen_${lineaNombre}_${hoy}.png`);
                    }
                });

            } catch (error) {
                Swal.fire('Error', `No se pudo cargar el resumen: ${error.message}`, 'error');
            }
        });

        document.getElementById('btn-consultar').addEventListener('click', consultarDatos);

        // --- Exportar Detalle ---
        document.getElementById('btn-exportar').addEventListener('click', () => {
            const tabla = document.getElementById('tabla-registros');
            const wb = XLSX.utils.table_to_book(tabla, { sheet: "Registros" });
            XLSX.writeFile(wb, "Reporte_Detallado_Safe_Launch.xlsx");
        });

        // --- Funciones de Exportación (copiadas de formulario_defectos.php) ---
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

        // Carga inicial de datos al entrar a la página
        consultarDatos();
    });
</script>
</body>
</html>

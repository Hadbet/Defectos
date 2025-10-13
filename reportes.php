<?php
// Obtener la lista de líneas para el filtro. En un futuro, esto podría venir de la base de datos.
$lineas_disponibles = ["XNF", "BR167HR", "L234", "BMW G0S", "INSITU"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Calidad</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js para los gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SheetJS (xlsx) para exportar a Excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- SweetAlert2 para notificaciones -->
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
        /* Estilos para la tabla animada y mejorada */
        #tabla-body tr {
            opacity: 0;
            animation: tableRowIn 0.5s ease-out forwards;
        }
        @keyframes tableRowIn {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

<!-- Navegador -->
<nav class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <a href="index.php" class="flex-shrink-0 font-bold text-xl text-blue-600">GRAMMER</a>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="index.php" class="text-slate-500 hover:bg-blue-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Inicio</a>
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
            <h1 class="text-4xl md:text-5xl font-extrabold text-slate-800 tracking-tight">Reportes de Defectos</h1>
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
                <h2 class="text-xl font-bold text-slate-700 mb-4">Total de Defectos por Tipo</h2>
                <canvas id="grafico-defectos-tipo"></canvas>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-200/50">
                <h2 class="text-xl font-bold text-slate-700 mb-4">Defectos Reportados por Mes</h2>
                <canvas id="grafico-defectos-mes"></canvas>
            </div>
        </div>

        <!-- Tabla de Datos -->
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-200/50">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-slate-700">Registros Detallados</h2>
                <button id="btn-exportar" class="bg-green-600 text-white font-bold py-2 px-4 rounded-md hover:bg-green-700 transition-colors disabled:opacity-50" disabled>
                    Exportar a Excel
                </button>
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
                        <th class="p-3 text-sm font-semibold text-slate-600 uppercase tracking-wider">Estación</th>
                        <th class="p-3 text-sm font-semibold text-slate-600 uppercase tracking-wider">Defecto</th>
                        <th class="p-3 text-sm font-semibold text-slate-600 uppercase tracking-wider">Estado</th>
                        <th class="p-3 text-sm font-semibold text-slate-600 uppercase tracking-wider">Comentarios</th>
                    </tr>
                    </thead>
                    <tbody id="tabla-body">
                    <!-- Los datos se cargarán aquí -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let graficoTipo, graficoMes;

        const fechaFinInput = document.getElementById('fecha-fin');
        const fechaInicioInput = document.getElementById('fecha-inicio');
        const btnExportar = document.getElementById('btn-exportar');

        // Configurar fechas por defecto
        const hoy = new Date();
        const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        fechaFinInput.value = hoy.toISOString().split('T')[0];
        fechaInicioInput.value = primerDiaMes.toISOString().split('T')[0];

        const renderizarGraficoTipo = (data) => {
            const ctx = document.getElementById('grafico-defectos-tipo').getContext('2d');
            if (graficoTipo) graficoTipo.destroy();
            graficoTipo = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.Descripcion),
                    datasets: [{
                        label: 'Total de Defectos',
                        data: data.map(d => d.Total),
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }]
                },
                options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false }
            });
        };

        const renderizarGraficoMes = (data) => {
            const ctx = document.getElementById('grafico-defectos-mes').getContext('2d');
            if (graficoMes) graficoMes.destroy();
            graficoMes = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.Mes),
                    datasets: [{
                        label: 'Defectos Registrados',
                        data: data.map(d => d.Total),
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 2,
                        fill: true,
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.3
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        };

        const poblarTabla = (data) => {
            const tablaBody = document.getElementById('tabla-body');
            const estados = { 0: 'Pendiente', 1: 'Retrabajo', 2: 'Scrap', 3: 'Liberado' };
            tablaBody.innerHTML = '';
            if (data.length === 0) {
                tablaBody.innerHTML = `<tr><td colspan="9" class="text-center p-6 text-slate-500">No se encontraron registros con los filtros seleccionados.</td></tr>`;
                btnExportar.disabled = true;
                return;
            }
            btnExportar.disabled = false;
            data.forEach((row, index) => {
                const tr = document.createElement('tr');
                tr.className = 'border-b border-slate-200/80 even:bg-slate-50/50 hover:bg-blue-50/80';
                tr.style.animationDelay = `${index * 0.03}s`;
                tr.innerHTML = `
                <td class="p-3 text-sm">${row.IdDefecto}</td>
                <td class="p-3 text-sm">${row.Fecha}</td>
                <td class="p-3 text-sm">${row.Linea}</td>
                <td class="p-3 text-sm">${row.Nomina}</td>
                <td class="p-3 text-sm">${row.NumeroParte}</td>
                <td class="p-3 text-sm">${row.Estacion}</td>
                <td class="p-3 text-sm">${row.CodigoDefecto} - ${row.DescripcionDefecto}</td>
                <td class="p-3 text-sm">${estados[row.Status] || 'N/A'}</td>
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

            try {
                const response = await fetch(`https://grammermx.com/calidad/defectos/dao/obtener_reportes.php?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&linea=${linea}`);
                if (!response.ok) throw new Error('Error de red al consultar los datos.');
                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                renderizarGraficoTipo(result.data.defectosPorTipo);
                renderizarGraficoMes(result.data.defectosPorMes);
                poblarTabla(result.data.tablaRegistros);

            } catch (error) {
                Swal.fire('Error', `No se pudieron cargar los datos: ${error.message}`, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Consultar';
            }
        };

        document.getElementById('btn-consultar').addEventListener('click', consultarDatos);

        btnExportar.addEventListener('click', () => {
            const tabla = document.getElementById('tabla-registros');
            const wb = XLSX.utils.table_to_book(tabla, { sheet: "Registros" });
            XLSX.writeFile(wb, "Reporte_Defectos.xlsx");
        });

        consultarDatos(); // Carga inicial de datos
    });
</script>
</body>
</html>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Práctica 2 - Citas con Horarios Fijos</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Handsontable CSS -->
    <link rel="stylesheet" href="handsontable/handsontable.full.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Handsontable JS -->
    <script src="handsontable/handsontable.full.min.js"></script>
    
    <style>
        .horario-column {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }
        .filter-section {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h1 class="text-center mb-4">Práctica 2 - Citas con Horarios Fijos</h1>
        
        <div class="card">
            <div class="card-header">
                <h4>Citas con Rangos de Horario (8AM - 8PM)</h4>
                <small class="text-muted">Columna fija de horarios + citas en intervalos correspondientes</small>
            </div>
            <div class="card-body">
                
                <!-- Filtro de fecha -->
                <div class="filter-section">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="fecha-filtro"><strong>Filtro por Fecha:</strong></label>
                            <input type="date" id="fecha-filtro" class="form-control" value="">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-info" onclick="cargarCitas()">Filtrar</button>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-success" onclick="agregarNuevaCita()">Agregar Cita</button>
                        </div>
                    </div>
                </div>
                
                <!-- Contenedor Handsontable -->
                <div id="tabla-citas" style="width: 100%; height: 500px;"></div>
                
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        var hot = null; // Handsontable
        var ejecutivos = []; // Lista de ejecutivos
        
        // Inicialización
        $(document).ready(function() {
            // Establecer fecha actual por defecto
            var fechaHoy = new Date().toISOString().split('T')[0];
            $('#fecha-filtro').val(fechaHoy);
            
            // Cargar ejecutivos
            cargarEjecutivos();
            
            // Inicializar tabla
            inicializarTabla();
            
            // Cargar datos iniciales
            cargarCitas();
        });

        // =====================================
        // FUNCIONES GENERALES
        // =====================================
        
        function cargarEjecutivos() {
            $.ajax({
                url: 'server/controlador_citas.php',
                type: 'POST',
                data: { action: 'obtener_ejecutivos' },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        ejecutivos = response.data.map(function(eje) {
                            return eje.nom_eje;
                        });
                        console.log('Ejecutivos cargados:', ejecutivos);
                    }
                },
                error: function() {
                    console.error('Error al cargar ejecutivos');
                }
            });
        }

        function mostrarListaEjecutivos() {
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: 'server/controlador_citas.php',
                    type: 'POST',
                    data: { action: 'obtener_ejecutivos' },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            var lista = 'Ejecutivos disponibles:\n';
                            response.data.forEach(function(eje) {
                                lista += 'ID: ' + eje.id_eje + ' - ' + eje.nom_eje + '\n';
                            });
                            resolve({lista: lista, ejecutivos: response.data});
                        } else {
                            reject('Error al cargar ejecutivos');
                        }
                    },
                    error: function() {
                        reject('Error de conexión');
                    }
                });
            });
        }

        // =====================================
        // TABLA CON HORARIOS FIJOS
        // =====================================
        
        function inicializarTabla() {
            var container = document.getElementById('tabla-citas');
            
            // Generar datos base con horarios fijos
            var datosBase = generarHorariosFijos();
            
            hot = new Handsontable(container, {
                data: datosBase,
                colHeaders: ['HORARIO', 'ID', 'FECHA', 'HORA', 'NOMBRE', 'TELÉFONO'],
                columns: [
                    { type: 'text', readOnly: true, className: 'horario-column' }, // Horario fijo
                    { type: 'text', readOnly: true }, // ID cita
                    { type: 'date', dateFormat: 'YYYY-MM-DD' }, // Fecha cita
                    { type: 'time', timeFormat: 'HH:mm' }, // Hora cita
                    { type: 'text' }, // Nombre cliente
                    { type: 'text' } // Teléfono cliente
                ],
                rowHeaders: true,
                height: 500,
                licenseKey: 'non-commercial-and-evaluation',
                
                // Evento para guardar cambios
                afterChange: function(changes, source) {
                    if (changes && source !== 'loadData') {
                        changes.forEach(([row, prop, oldValue, newValue]) => {
                            if (newValue !== oldValue && prop > 0) { // Ignorar columna de horario
                                guardarCambio(row, prop, newValue);
                            }
                        });
                    }
                }
            });
        }
        
        function generarHorariosFijos() {
            var horarios = [];
            for (var h = 8; h <= 20; h++) {
                var inicio = h < 10 ? '0' + h + ':00' : h + ':00';
                var fin = (h + 1) < 10 ? '0' + (h + 1) + ':00' : (h + 1) + ':00';
                var rango = inicio + ' - ' + fin;
                
                horarios.push([rango, '', '', '', '', '']);
            }
            return horarios;
        }
        
        function cargarCitas() {
            var fecha = $('#fecha-filtro').val();
            
            $.ajax({
                url: 'server/controlador_citas.php',
                type: 'POST',
                data: { 
                    action: 'obtener_citas',
                    fecha_filtro: fecha
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        var datosBase = generarHorariosFijos();
                        
                        // Colocar citas en sus intervalos correspondientes
                        response.data.forEach(function(cita) {
                            var hora = parseInt(cita.hor_cit.split(':')[0]);
                            var indiceIntervalo = hora - 8; // 8AM = índice 0
                            
                            if (indiceIntervalo >= 0 && indiceIntervalo < datosBase.length) {
                                datosBase[indiceIntervalo] = [
                                    datosBase[indiceIntervalo][0], // Mantener rango de horario
                                    cita.id_cit,
                                    cita.cit_cit,
                                    cita.hor_cit,
                                    cita.nom_cit,
                                    cita.tel_cit
                                ];
                            }
                        });
                        
                        hot.loadData(datosBase);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error de conexión al servidor');
                }
            });
        }
        
        function guardarCambio(row, column, value) {
            var campo = obtenerCampo(column);
            var id_cit = hot.getDataAtCell(row, 1); // ID está en columna 1
            
            if (!id_cit) {
                console.log('Nueva cita detectada en intervalo', row);
                return;
            }
            
            $.ajax({
                url: 'server/controlador_citas.php',
                type: 'POST',
                data: {
                    action: 'actualizar_cita',
                    campo: campo,
                    valor: value,
                    id_cit: id_cit
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        console.log('Campo actualizado correctamente');
                    } else {
                        alert('Error al actualizar: ' + response.message);
                        cargarCitas(); // Recargar datos
                    }
                },
                error: function() {
                    alert('Error de conexión al guardar cambio');
                    cargarCitas(); // Recargar datos
                }
            });
        }
        
        function obtenerCampo(column) {
            var campos = {
                0: 'horario', // No editable
                1: 'id_cit',
                2: 'cit_cit',
                3: 'hor_cit',
                4: 'nom_cit',
                5: 'tel_cit'
            };
            return campos[column];
        }
        
        function agregarNuevaCita() {
            // Mostrar lista de ejecutivos y permitir elegir
            mostrarListaEjecutivos().then(function(data) {
                var nombre = prompt('Nombre del cliente:');
                if (!nombre) return;
                
                var telefono = prompt('Teléfono del cliente:');
                if (!telefono) return;
                
                var hora = prompt('Hora de la cita (formato 24h, ej: 14:30):', '09:00');
                if (!hora) return;
                
                var dia = prompt('Fecha de la cita (YYYY-MM-DD):', new Date().toISOString().split('T')[0]);
                if (!dia) return;
                
                // Mostrar lista de ejecutivos
                alert(data.lista);
                var ejecutivoId = prompt('Ingrese el ID del ejecutivo que desea asignar:');
                if (!ejecutivoId) return;
                
                // Validar que el ID existe
                var ejecutivoSeleccionado = data.ejecutivos.find(function(eje) {
                    return eje.id_eje == ejecutivoId;
                });
                
                if (!ejecutivoSeleccionado) {
                    alert('ID de ejecutivo no válido. Verifique la lista.');
                    return;
                }
                
                // Validar formato de fecha
                if (!/^\d{4}-\d{2}-\d{2}$/.test(dia)) {
                    alert('Formato de fecha incorrecto. Use YYYY-MM-DD (ej: 2025-07-03)');
                    return;
                }
                
                $.ajax({
                    url: 'server/controlador_citas.php',
                    type: 'POST',
                    data: {
                        action: 'guardar_cita',
                        cit_cit: dia,
                        hor_cit: hora + ':00',
                        nom_cit: nombre,
                        tel_cit: telefono,
                        id_eje2: ejecutivoId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            alert('Cita guardada correctamente para el ' + dia + '\nEjecutivo asignado: ' + ejecutivoSeleccionado.nom_eje);
                            cargarCitas();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error de conexión al guardar cita');
                    }
                });
                
            }).catch(function(error) {
                alert('Error al cargar ejecutivos: ' + error);
            });
        }
        
        // Evento de filtro de fecha
        $('#fecha-filtro').change(function() {
            cargarCitas();
        });
        
    </script>
</body>
</html>

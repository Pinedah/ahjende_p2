<?php
include '../inc/conexion.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	
	$action = escape($_POST['action'], $connection);
	
	switch($action) {

		case 'obtener_citas':
			$fecha_filtro = isset($_POST['fecha_filtro']) ? escape($_POST['fecha_filtro'], $connection) : date('Y-m-d');
			$query = "SELECT c.id_cit, c.cit_cit, c.hor_cit, c.nom_cit, c.tel_cit, e.nom_eje 
					 FROM cita c
					 LEFT JOIN ejecutivo e ON c.id_eje2 = e.id_eje
					 WHERE c.cit_cit = '$fecha_filtro'
					 ORDER BY c.hor_cit ASC";

			$datos = ejecutarConsulta($query, $connection);

			if($datos !== false) {
				echo respuestaExito($datos, 'Citas obtenidas correctamente');
			} else {
				echo respuestaError('Error al consultar citas: ' . mysqli_error($connection) . ' Query: ' . $query);
			}
		break;

		case 'obtener_ejecutivos':
			$query = "SELECT id_eje, nom_eje FROM ejecutivo ORDER BY nom_eje ASC";
			$datos = ejecutarConsulta($query, $connection);

			if($datos !== false) {
				echo respuestaExito($datos, 'Ejecutivos obtenidos correctamente');
			} else {
				echo respuestaError('Error al consultar ejecutivos: ' . mysqli_error($connection) . ' Query: ' . $query);
			}
		break;

		case 'guardar_cita':
			$cit_cit = escape($_POST['cit_cit'], $connection);
			$hor_cit = escape($_POST['hor_cit'], $connection);
			$nom_cit = escape($_POST['nom_cit'], $connection);
			$tel_cit = escape($_POST['tel_cit'], $connection);
			$id_eje2 = escape($_POST['id_eje2'], $connection);

			$query = "INSERT INTO cita (cit_cit, hor_cit, nom_cit, tel_cit, id_eje2) 
					 VALUES ('$cit_cit', '$hor_cit', '$nom_cit', '$tel_cit', '$id_eje2')";

			if(mysqli_query($connection, $query)) {
				echo respuestaExito(['id' => mysqli_insert_id($connection)], 'Cita guardada correctamente');
			} else {
				echo respuestaError('Error al guardar cita: ' . mysqli_error($connection) . ' Query: ' . $query);
			}
		break;

		case 'actualizar_cita':
			$campo = escape($_POST['campo'], $connection);
			$valor = escape($_POST['valor'], $connection);
			$id_cit = escape($_POST['id_cit'], $connection);
			
			// Validar campos permitidos para actualización
			$campos_permitidos = ['cit_cit', 'hor_cit', 'nom_cit', 'tel_cit', 'id_eje2'];
			if (!in_array($campo, $campos_permitidos)) {
				echo respuestaError('Campo no permitido para actualización');
				break;
			}
			
			$query = "UPDATE cita SET $campo = '$valor' WHERE id_cit = '$id_cit'";
			
			if(mysqli_query($connection, $query)) {
				echo respuestaExito(null, 'Campo actualizado correctamente');
			} else {
				echo respuestaError('Error al actualizar: ' . mysqli_error($connection) . ' Query: ' . $query);
			}
		break;

		case 'eliminar_cita':
			$id_cit = escape($_POST['id_cit'], $connection);
			
			$query = "DELETE FROM cita WHERE id_cit = '$id_cit'";
			
			if(mysqli_query($connection, $query)) {
				echo respuestaExito(null, 'Cita eliminada correctamente');
			} else {
				echo respuestaError('Error al eliminar cita: ' . mysqli_error($connection) . ' Query: ' . $query);
			}
		break;

		default:
			echo respuestaError('Acción no válida');
		break;
	}

	mysqli_close($connection);
	exit;
}
?>

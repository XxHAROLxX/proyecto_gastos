<?php

include_once '../config/database.php';
include_once '../class/transaccion.php';

$database = new Database();
$db = $database->getConnection();
$transaccion = new Transaccion($db);

$data = json_decode(file_get_contents("php://input"));
$action = $_GET['action'] ?? '';

switch($action) {
    case 'create':
        // Crear nueva transacción
        if(!empty($data->id_usuario) && !empty($data->id_categoria) && 
           !empty($data->tipo) && !empty($data->monto) && !empty($data->fecha)) {
            
            $transaccion->id_usuario = $data->id_usuario;
            $transaccion->id_categoria = $data->id_categoria;
            $transaccion->tipo = $data->tipo;
            $transaccion->monto = $data->monto;
            $transaccion->descripcion = $data->descripcion ?? '';
            $transaccion->fecha = $data->fecha;

            if($transaccion->crear()) {
                http_response_code(201);
                echo json_encode(array("message" => "Transacción creada exitosamente."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo crear la transacción."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos."));
        }
        break;

    case 'list':
        // Listar transacciones
        $transaccion->id_usuario = $_GET['user_id'] ?? 0;
        $limite = $_GET['limit'] ?? 10;
        
        $stmt = $transaccion->obtenerPorUsuario($limite);
        $num = $stmt->rowCount();

        if($num > 0) {
            $transacciones_arr = array();
            $transacciones_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                extract($row);
                
                $transaccion_item = array(
                    "id_transaccion" => $id_transaccion,
                    "tipo" => $tipo,
                    "monto" => floatval($monto),
                    "descripcion" => $descripcion,
                    "fecha" => $fecha,
                    "categoria" => $nombre_categoria,
                    "color" => $color
                );

                array_push($transacciones_arr["records"], $transaccion_item);
            }

            http_response_code(200);
            echo json_encode($transacciones_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No se encontraron transacciones."));
        }
        break;

    case 'resumen':
        // Obtener resumen mensual
        $transaccion->id_usuario = $_GET['user_id'] ?? 0;
        $mes = $_GET['mes'] ?? date('n');
        $año = $_GET['año'] ?? date('Y');
        
        $resumen = $transaccion->obtenerResumenMensual($mes, $año);
        
        if($resumen) {
            http_response_code(200);
            echo json_encode($resumen);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No se pudo obtener el resumen."));
        }
        break;

    case 'delete':
        // Eliminar transacción
        $transaccion->id_transaccion = $data->id_transaccion ?? 0;
        $transaccion->id_usuario = $data->id_usuario ?? 0;

        if($transaccion->eliminar()) {
            http_response_code(200);
            echo json_encode(array("message" => "Transacción eliminada."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo eliminar la transacción."));
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(array("message" => "Endpoint no encontrado."));
        break;
}

<?php
// Configurar headers para CORS y JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluir archivos necesarios
include_once '../config/database.php';
include_once '../class/categoria.php';

// Obtener conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Inicializar objeto categoria
$categoria = new Categoria($db);

// Obtener datos enviados
$data = json_decode(file_get_contents("php://input"));

// Determinar la acción a realizar
$action = $_GET['action'] ?? '';

switch($action) {
    case 'create':
        // Crear nueva categoría
        if(!empty($data->nombre_categoria) && !empty($data->id_usuario)) {
            
            $categoria->nombre_categoria = $data->nombre_categoria;
            $categoria->descripcion = $data->descripcion ?? '';
            $categoria->color = $data->color ?? '#007bff';
            $categoria->id_usuario = $data->id_usuario;

            if($categoria->crear()) {
                http_response_code(201);
                echo json_encode(array("message" => "Categoría creada exitosamente."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo crear la categoría."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Nombre de categoría y ID de usuario son requeridos."));
        }
        break;

    case 'list':
        // Listar categorías por usuario
        $categoria->id_usuario = $_GET['user_id'] ?? 0;
        
        if($categoria->id_usuario > 0) {
            $stmt = $categoria->obtenerPorUsuario();
            $num = $stmt->rowCount();

            if($num > 0) {
                $categorias_arr = array();
                $categorias_arr["records"] = array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    
                    $categoria_item = array(
                        "id_categoria" => $id_categoria,
                        "nombre_categoria" => $nombre_categoria,
                        "descripcion" => $descripcion,
                        "color" => $color,
                        "fecha_creacion" => $fecha_creacion
                    );

                    array_push($categorias_arr["records"], $categoria_item);
                }

                http_response_code(200);
                echo json_encode($categorias_arr);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "No se encontraron categorías."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID de usuario es requerido."));
        }
        break;

    case 'update':
        // Actualizar categoría
        if(!empty($data->id_categoria) && !empty($data->nombre_categoria) && !empty($data->id_usuario)) {
            
            $query = "UPDATE categorias 
                      SET nombre_categoria=:nombre_categoria, descripcion=:descripcion, color=:color
                      WHERE id_categoria=:id_categoria AND id_usuario=:id_usuario";

            $stmt = $db->prepare($query);

            // Limpiar datos
            $nombre_categoria = htmlspecialchars(strip_tags($data->nombre_categoria));
            $descripcion = htmlspecialchars(strip_tags($data->descripcion ?? ''));
            $color = htmlspecialchars(strip_tags($data->color ?? '#007bff'));

            // Bind valores
            $stmt->bindParam(":nombre_categoria", $nombre_categoria);
            $stmt->bindParam(":descripcion", $descripcion);
            $stmt->bindParam(":color", $color);
            $stmt->bindParam(":id_categoria", $data->id_categoria);
            $stmt->bindParam(":id_usuario", $data->id_usuario);

            if($stmt->execute()) {
                http_response_code(200);
                echo json_encode(array("message" => "Categoría actualizada exitosamente."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo actualizar la categoría."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos para actualizar."));
        }
        break;

    case 'delete':
        // Eliminar categoría
        if(!empty($data->id_categoria) && !empty($data->id_usuario)) {
            
            $categoria->id_categoria = $data->id_categoria;
            $categoria->id_usuario = $data->id_usuario;

            if($categoria->eliminar()) {
                http_response_code(200);
                echo json_encode(array("message" => "Categoría eliminada exitosamente."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo eliminar la categoría. Puede tener transacciones asociadas."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID de categoría y usuario son requeridos."));
        }
        break;

    case 'default_categories':
        // Crear categorías por defecto para un nuevo usuario
        if(!empty($data->id_usuario)) {
            
            $categorias_default = [
                ['nombre' => 'Alimentación', 'descripcion' => 'Gastos en comida y bebidas', 'color' => '#ff6b6b'],
                ['nombre' => 'Transporte', 'descripcion' => 'Gastos en transporte público y combustible', 'color' => '#4ecdc4'],
                ['nombre' => 'Entretenimiento', 'descripcion' => 'Gastos en ocio y diversión', 'color' => '#45b7d1'],
                ['nombre' => 'Salud', 'descripcion' => 'Gastos médicos y farmacia', 'color' => '#96ceb4'],
                ['nombre' => 'Educación', 'descripcion' => 'Gastos en cursos y libros', 'color' => '#feca57'],
                ['nombre' => 'Salario', 'descripcion' => 'Ingresos por trabajo', 'color' => '#48dbfb'],
                ['nombre' => 'Freelance', 'descripcion' => 'Ingresos por trabajos independientes', 'color' => '#0abde3'],
                ['nombre' => 'Servicios', 'descripcion' => 'Luz, agua, gas, internet, etc.', 'color' => '#ff9ff3'],
                ['nombre' => 'Vivienda', 'descripcion' => 'Arriendo, hipoteca, mantenimiento', 'color' => '#54a0ff']
            ];

            $creadas = 0;
            foreach($categorias_default as $cat_default) {
                $categoria->nombre_categoria = $cat_default['nombre'];
                $categoria->descripcion = $cat_default['descripcion'];
                $categoria->color = $cat_default['color'];
                $categoria->id_usuario = $data->id_usuario;
                
                if($categoria->crear()) {
                    $creadas++;
                }
            }

            if($creadas > 0) {
                http_response_code(201);
                echo json_encode(array("message" => "Se crearon $creadas categorías por defecto."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudieron crear las categorías por defecto."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID de usuario es requerido."));
        }
        break;

    case 'stats':
        // Estadísticas de uso de categorías
        $id_usuario = $_GET['user_id'] ?? 0;
        $mes = $_GET['mes'] ?? date('n');
        $año = $_GET['año'] ?? date('Y');
        
        if($id_usuario > 0) {
            $query = "SELECT c.id_categoria, c.nombre_categoria, c.color,
                             COUNT(t.id_transaccion) as total_transacciones,
                             SUM(CASE WHEN t.tipo = 'gasto' THEN t.monto ELSE 0 END) as total_gastos,
                             SUM(CASE WHEN t.tipo = 'ingreso' THEN t.monto ELSE 0 END) as total_ingresos,
                             AVG(CASE WHEN t.tipo = 'gasto' THEN t.monto ELSE NULL END) as promedio_gasto
                      FROM categorias c
                      LEFT JOIN transacciones t ON c.id_categoria = t.id_categoria 
                                                AND MONTH(t.fecha) = ? AND YEAR(t.fecha) = ?
                      WHERE c.id_usuario = ?
                      GROUP BY c.id_categoria
                      ORDER BY total_gastos DESC";

            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $mes);
            $stmt->bindParam(2, $año);
            $stmt->bindParam(3, $id_usuario);
            $stmt->execute();

            $stats_arr = array();
            $stats_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                extract($row);
                
                $stat_item = array(
                    "id_categoria" => $id_categoria,
                    "nombre_categoria" => $nombre_categoria,
                    "color" => $color,
                    "total_transacciones" => intval($total_transacciones),
                    "total_gastos" => floatval($total_gastos),
                    "total_ingresos" => floatval($total_ingresos),
                    "promedio_gasto" => floatval($promedio_gasto)
                );

                array_push($stats_arr["records"], $stat_item);
            }

            http_response_code(200);
            echo json_encode($stats_arr);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID de usuario es requerido."));
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(array("message" => "Endpoint no encontrado."));
        break;
}
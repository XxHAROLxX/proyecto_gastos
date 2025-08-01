<?php
// Configurar headers para CORS y JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluir archivos necesarios
include_once '../config/database.php';
include_once '../class/usuario.php';

// Obtener conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Inicializar objeto usuario
$usuario = new Usuario($db);

// Obtener datos enviados
$data = json_decode(file_get_contents("php://input"));

// Determinar la acción a realizar
$action = $_GET['action'] ?? '';

switch($action) {
    case 'register':
        // Registrar nuevo usuario
        if(!empty($data->primer_nombre) && !empty($data->primer_apellido) && 
           !empty($data->email) && !empty($data->password)) {
            
            $usuario->primer_nombre = $data->primer_nombre;
            $usuario->segundo_nombre = $data->segundo_nombre ?? '';
            $usuario->primer_apellido = $data->primer_apellido;
            $usuario->segundo_apellido = $data->segundo_apellido ?? '';
            $usuario->email = $data->email;
            $usuario->password = $data->password;
            $usuario->presupuesto_mes = $data->presupuesto_mes ?? 0;

            // Verificar si el email ya existe
            if($usuario->emailExists()) {
                http_response_code(400);
                echo json_encode(array("message" => "El email ya está registrado."));
            } else if($usuario->registrar()) {
                http_response_code(201);
                echo json_encode(array("message" => "Usuario registrado exitosamente."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo registrar el usuario."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos."));
        }
        break;

    case 'login':
        // Iniciar sesión
        if(!empty($data->email) && !empty($data->password)) {
            $usuario->email = $data->email;
            $usuario->password = $data->password;

            if($usuario->login()) {
                http_response_code(200);
                echo json_encode(array(
                    "message" => "Login exitoso.",
                    "user" => array(
                        "id" => $usuario->id_usuario,
                        "nombre" => $usuario->getNombreCompleto(),
                        "email" => $usuario->email,
                        "presupuesto" => $usuario->presupuesto_mes
                    )
                ));
            } else {
                http_response_code(401);
                echo json_encode(array("message" => "Credenciales incorrectas."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Email y contraseña son requeridos."));
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(array("message" => "Endpoint no encontrado."));
        break;
}
<?php
class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public $id_usuario;
    public $primer_nombre;
    public $segundo_nombre;
    public $primer_apellido;
    public $segundo_apellido;
    public $email;
    public $password;
    public $fecha_registro;
    public $presupuesto_mes;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Registrar nuevo usuario
    public function registrar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET primer_nombre=:primer_nombre, segundo_nombre=:segundo_nombre, 
                      primer_apellido=:primer_apellido, segundo_apellido=:segundo_apellido,
                      email=:email, password=:password, presupuesto_mes=:presupuesto_mes";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->primer_nombre = htmlspecialchars(strip_tags($this->primer_nombre));
        $this->segundo_nombre = htmlspecialchars(strip_tags($this->segundo_nombre));
        $this->primer_apellido = htmlspecialchars(strip_tags($this->primer_apellido));
        $this->segundo_apellido = htmlspecialchars(strip_tags($this->segundo_apellido));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind valores
        $stmt->bindParam(":primer_nombre", $this->primer_nombre);
        $stmt->bindParam(":segundo_nombre", $this->segundo_nombre);
        $stmt->bindParam(":primer_apellido", $this->primer_apellido);
        $stmt->bindParam(":segundo_apellido", $this->segundo_apellido);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":presupuesto_mes", $this->presupuesto_mes);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Login usuario
    public function login() {
        $query = "SELECT id_usuario, primer_nombre, primer_apellido, email, password, presupuesto_mes 
                  FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        $num = $stmt->rowCount();

        if($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($this->password, $row['password'])) {
                $this->id_usuario = $row['id_usuario'];
                $this->primer_nombre = $row['primer_nombre'];
                $this->primer_apellido = $row['primer_apellido'];
                $this->presupuesto_mes = $row['presupuesto_mes'];
                return true;
            }
        }
        return false;
    }

    // Verificar si email existe
    public function emailExists() {
        $query = "SELECT id_usuario FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }

    // Obtener nombre completo
    public function getNombreCompleto() {
        return $this->primer_nombre . " " . $this->primer_apellido;
    }
}

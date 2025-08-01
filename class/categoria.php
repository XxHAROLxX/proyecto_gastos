<?php
class Categoria {
    private $conn;
    private $table_name = "categorias";

    public $id_categoria;
    public $nombre_categoria;
    public $descripcion;
    public $color;
    public $id_usuario;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear nueva categoría
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nombre_categoria=:nombre_categoria, descripcion=:descripcion, 
                      color=:color, id_usuario=:id_usuario";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->nombre_categoria = htmlspecialchars(strip_tags($this->nombre_categoria));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->color = htmlspecialchars(strip_tags($this->color));

        // Bind valores
        $stmt->bindParam(":nombre_categoria", $this->nombre_categoria);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":color", $this->color);
        $stmt->bindParam(":id_usuario", $this->id_usuario);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obtener categorías por usuario
    public function obtenerPorUsuario() {
        $query = "SELECT id_categoria, nombre_categoria, descripcion, color, fecha_creacion 
                  FROM " . $this->table_name . " 
                  WHERE id_usuario = ? ORDER BY nombre_categoria";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_usuario);
        $stmt->execute();

        return $stmt;
    }

    // Eliminar categoría
    public function eliminar() {
        // Verificar que no tenga transacciones asociadas
        $query_check = "SELECT COUNT(*) FROM transacciones WHERE id_categoria = ?";
        $stmt_check = $this->conn->prepare($query_check);
        $stmt_check->bindParam(1, $this->id_categoria);
        $stmt_check->execute();
        
        if($stmt_check->fetchColumn() > 0) {
            return false; // No se puede eliminar, tiene transacciones
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id_categoria = ? AND id_usuario = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_categoria);
        $stmt->bindParam(2, $this->id_usuario);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
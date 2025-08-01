<?php
class Transaccion {
    private $conn;
    private $table_name = "transacciones";

    public $id_transaccion;
    public $id_usuario;
    public $id_categoria;
    public $tipo;
    public $monto;
    public $descripcion;
    public $fecha;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear nueva transacción
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET id_usuario=:id_usuario, id_categoria=:id_categoria, 
                      tipo=:tipo, monto=:monto, descripcion=:descripcion, fecha=:fecha";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));

        // Bind valores
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":id_categoria", $this->id_categoria);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":monto", $this->monto);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":fecha", $this->fecha);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obtener transacciones por usuario
    public function obtenerPorUsuario($limite = 10) {
        $query = "SELECT t.id_transaccion, t.tipo, t.monto, t.descripcion, t.fecha,
                         c.nombre_categoria, c.color
                  FROM " . $this->table_name . " t
                  LEFT JOIN categorias c ON t.id_categoria = c.id_categoria
                  WHERE t.id_usuario = ? 
                  ORDER BY t.fecha DESC, t.fecha_creacion DESC";
        
        if($limite > 0) {
            $query .= " LIMIT " . $limite;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_usuario);
        $stmt->execute();

        return $stmt;
    }

    // Obtener resumen mensual
    public function obtenerResumenMensual($mes, $año) {
        $query = "SELECT 
                    SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as total_ingresos,
                    SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as total_gastos,
                    (SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) - 
                     SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END)) as balance
                  FROM " . $this->table_name . " 
                  WHERE id_usuario = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_usuario);
        $stmt->bindParam(2, $mes);
        $stmt->bindParam(3, $año);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener gastos por categoría
    public function obtenerGastosPorCategoria($mes = null, $año = null) {
        $query = "SELECT c.nombre_categoria, c.color, SUM(t.monto) as total_gastado,
                         COUNT(t.id_transaccion) as num_transacciones
                  FROM " . $this->table_name . " t
                  JOIN categorias c ON t.id_categoria = c.id_categoria
                  WHERE t.id_usuario = ? AND t.tipo = 'gasto'";
        
        if($mes && $año) {
            $query .= " AND MONTH(t.fecha) = ? AND YEAR(t.fecha) = ?";
        }
        
        $query .= " GROUP BY c.id_categoria ORDER BY total_gastado DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_usuario);
        
        if($mes && $año) {
            $stmt->bindParam(2, $mes);
            $stmt->bindParam(3, $año);
        }
        
        $stmt->execute();
        return $stmt;
    }

    // Eliminar transacción
    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_transaccion = ? AND id_usuario = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_transaccion);
        $stmt->bindParam(2, $this->id_usuario);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}

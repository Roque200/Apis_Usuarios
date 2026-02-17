<?php
// Recurso de productos protegido - 21031190
require_once '../config/database.php';
require_once '../models/Product.php';
require_once '../middleware/AuthMiddleware.php';

class ProductResource
{
    private $db;
    private $product;
    private $auth;

    public function __construct()
    {
        $database      = new Database();
        $this->db      = $database->getConnection();
        $this->product = new Product($this->db);
        $this->auth    = new AuthMiddleware();
    }

    // GET /api/v1/products
    public function index()
    {
        if (!$this->auth->authenticate()) return;

        header("Content-Type: application/json");

        $stmt = $this->product->read();
        $num  = $stmt->rowCount();

        if ($num > 0) {
            $products_arr = ["records" => []];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                array_push($products_arr["records"], [
                    "id"          => $id,
                    "sku"         => $sku,
                    "name"        => $name,
                    "description" => $description,
                    "price"       => $price,
                    "stock"       => $stock,
                    "created_at"  => $created_at,
                    "updated_at"  => $updated_at
                ]);
            }
            http_response_code(200);
            echo json_encode($products_arr);
        } else {
            http_response_code(200);
            echo json_encode(["records" => []]);
        }
    }

    // GET /api/v1/products/{id}
    public function show($id)
    {
        if (!$this->auth->authenticate()) return;

        header("Content-Type: application/json");

        $this->product->id = $id;

        if ($this->product->readOne()) {
            http_response_code(200);
            echo json_encode([
                "id"          => $this->product->id,
                "sku"         => $this->product->sku,
                "name"        => $this->product->name,
                "description" => $this->product->description,
                "price"       => $this->product->price,
                "stock"       => $this->product->stock,
                "created_at"  => $this->product->created_at,
                "updated_at"  => $this->product->updated_at
            ]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Producto no encontrado"]);
        }
    }

    // POST /api/v1/products
    public function store()
    {
        if (!$this->auth->authenticate()) return;

        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->sku) && !empty($data->name) && isset($data->price) && isset($data->stock)) {
            $this->product->sku         = $data->sku;
            $this->product->name        = $data->name;
            $this->product->description = $data->description ?? '';
            $this->product->price       = $data->price;
            $this->product->stock       = $data->stock;

            if ($this->product->create()) {
                http_response_code(201);
                echo json_encode([
                    "message" => "Producto creado exitosamente",
                    "id"      => $this->product->id
                ]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "No se pudo crear el producto"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos. Se requiere sku, name, price y stock"]);
        }
    }

    // PUT /api/v1/products/{id}
    public function update($id)
    {
        if (!$this->auth->authenticate()) return;

        header("Content-Type: application/json");

        $data              = json_decode(file_get_contents("php://input"));
        $this->product->id = $id;

        if (!empty($data->sku) && !empty($data->name) && isset($data->price) && isset($data->stock)) {
            $this->product->sku         = $data->sku;
            $this->product->name        = $data->name;
            $this->product->description = $data->description ?? '';
            $this->product->price       = $data->price;
            $this->product->stock       = $data->stock;

            if ($this->product->update()) {
                http_response_code(200);
                echo json_encode(["message" => "Producto actualizado exitosamente"]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "No se pudo actualizar el producto"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos. Se requiere sku, name, price y stock"]);
        }
    }

    // DELETE /api/v1/products/{id}
    public function destroy($id)
    {
        if (!$this->auth->authenticate()) return;

        header("Content-Type: application/json");

        $this->product->id = $id;

        if ($this->product->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Producto eliminado exitosamente"]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo eliminar el producto"]);
        }
    }
}
?>
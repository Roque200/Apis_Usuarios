<?php
// Recurso de usuarios protegido - 21031190
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../middleware/AuthMiddleware.php';

class UserResource
{
    private $db;
    private $user;
    private $auth;

    public function __construct()
    {
        $database   = new Database();
        $this->db   = $database->getConnection();
        $this->user = new User($this->db);
        $this->auth = new AuthMiddleware();
    }

    // GET /api/v1/users
    public function index()
    {
        if (!$this->auth->authenticate()) return;

        header("Content-Type: application/json");

        $stmt = $this->user->read();
        $num  = $stmt->rowCount();

        if ($num > 0) {
            $users_arr = ["records" => []];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                array_push($users_arr["records"], [
                    "id"         => $id,
                    "name"       => $name,
                    "email"      => $email,
                    "created_at" => $created_at
                ]);
            }
            http_response_code(200);
            echo json_encode($users_arr);
        } else {
            http_response_code(200);
            echo json_encode(["records" => []]);
        }
    }

    // GET /api/v1/users/{id}
    public function show($id)
    {
        if (!$this->auth->authenticate()) return;

        header("Content-Type: application/json");

        $this->user->id = $id;

        if ($this->user->readOne()) {
            http_response_code(200);
            echo json_encode([
                "id"         => $this->user->id,
                "name"       => $this->user->name,
                "email"      => $this->user->email,
                "created_at" => $this->user->created_at
            ]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Usuario no encontrado"]);
        }
    }

    // POST /api/v1/users
    public function store()
    {
        if (!$this->auth->authenticate()) return;

        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->name) && !empty($data->email)) {
            $this->user->name  = $data->name;
            $this->user->email = $data->email;

            if ($this->user->create()) {
                http_response_code(201);
                echo json_encode([
                    "message" => "Usuario creado exitosamente",
                    "id"      => $this->user->id
                ]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "No se pudo crear el usuario"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos. Se requiere name y email"]);
        }
    }

    // PUT /api/v1/users/{id}
    public function update($id)
    {
        if (!$this->auth->authenticate()) return;

        header("Content-Type: application/json");

        $data           = json_decode(file_get_contents("php://input"));
        $this->user->id = $id;

        if (!empty($data->name) && !empty($data->email)) {
            $this->user->name  = $data->name;
            $this->user->email = $data->email;

            if ($this->user->update()) {
                http_response_code(200);
                echo json_encode(["message" => "Usuario actualizado exitosamente"]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "No se pudo actualizar el usuario"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos. Se requiere name y email"]);
        }
    }

    // DELETE /api/v1/users/{id}
    public function destroy($id)
    {
        if (!$this->auth->authenticate()) return;

        header("Content-Type: application/json");

        $this->user->id = $id;

        if ($this->user->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Usuario eliminado exitosamente"]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo eliminar el usuario"]);
        }
    }
}
?>
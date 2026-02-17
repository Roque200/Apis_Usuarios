<?php
// Recurso de autenticación - 21031190
require_once '../config/database.php';
require_once '../models/AuthUser.php';
require_once '../models/Token.php';

class AuthResource
{
    private $db;
    private $authUser;
    private $token;

    public function __construct()
    {
        $database       = new Database();
        $this->db       = $database->getConnection();
        $this->authUser = new AuthUser($this->db);
        $this->token    = new Token($this->db);
    }

    // POST /api/v1/login
    public function login()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->username) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(["message" => "Se requiere username y password"]);
            return;
        }

        $this->authUser->username = $data->username;

        if (!$this->authUser->findByUsername()) {
            http_response_code(401);
            echo json_encode(["message" => "Credenciales invalidas"]);
            return;
        }

        if (!$this->authUser->verifyPassword($data->password)) {
            http_response_code(401);
            echo json_encode(["message" => "Credenciales invalidas"]);
            return;
        }

        $this->token->cleanExpiredTokens($this->authUser->id);

        $hours = isset($data->expires_hours) ? $data->expires_hours : 24;

        if ($this->token->generate($this->authUser->id, $hours)) {
            http_response_code(200);
            echo json_encode([
                "access_token" => $this->token->token,
                "expires_at"   => $this->token->expires_at,
                "user"         => [
                    "id"       => $this->authUser->id,
                    "username" => $this->authUser->username,
                    "email"    => $this->authUser->email
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al generar el token"]);
        }
    }

    // POST /api/v1/logout
    public function logout()
    {
        header("Content-Type: application/json");

        $headers    = getallheaders();
        $authHeader = isset($headers['Authorization'])
            ? $headers['Authorization']
            : (isset($headers['authorization']) ? $headers['authorization'] : null);

        if (!$authHeader) {
            http_response_code(400);
            echo json_encode(["message" => "Token no proporcionado"]);
            return;
        }

        $token = null;
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }

        if ($this->token->revoke($token)) {
            http_response_code(200);
            echo json_encode(["message" => "Sesion cerrada exitosamente"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al cerrar sesion"]);
        }
    }

    // POST /api/v1/register
    public function register()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->username) || empty($data->email) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(["message" => "Se requiere username, email y password"]);
            return;
        }

        $this->authUser->username = $data->username;
        $this->authUser->email    = $data->email;

        if ($this->authUser->create($data->password)) {
            http_response_code(201);
            echo json_encode([
                "message" => "Usuario registrado exitosamente",
                "user"    => [
                    "id"       => $this->authUser->id,
                    "username" => $this->authUser->username,
                    "email"    => $this->authUser->email
                ]
            ]);
        } else {
            http_response_code(503);
            echo json_encode([
                "message" => "No se pudo registrar el usuario. El username o email ya pueden existir."
            ]);
        }
    }
}
?>
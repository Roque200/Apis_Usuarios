<?php
// Middleware de autenticación - 21031190
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Token.php';

class AuthMiddleware
{
    private $db;
    private $token;

    public function __construct()
    {
        $database    = new Database();
        $this->db    = $database->getConnection();
        $this->token = new Token($this->db);
    }

    public function authenticate()
    {
        $headers    = getallheaders();
        $authHeader = isset($headers['Authorization'])
            ? $headers['Authorization']
            : (isset($headers['authorization']) ? $headers['authorization'] : null);

        if (!$authHeader) {
            $this->sendUnauthorized("Token de acceso no proporcionado");
            return false;
        }

        $token = null;
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }

        if (!$this->token->validate($token)) {
            $this->sendUnauthorized("Token invalido o expirado");
            return false;
        }

        return true;
    }

    private function sendUnauthorized($message = "No autorizado")
    {
        header("Content-Type: application/json");
        http_response_code(401);
        echo json_encode([
            "message" => $message,
            "error"   => "Unauthorized"
        ]);
    }

    public function getUserId()
    {
        $headers    = getallheaders();
        $authHeader = isset($headers['Authorization'])
            ? $headers['Authorization']
            : (isset($headers['authorization']) ? $headers['authorization'] : null);

        if (!$authHeader) return null;

        $token = null;
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }

        if ($this->token->validate($token)) {
            return $this->token->user_id;
        }
        return null;
    }
}
?>
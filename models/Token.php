<?php
// Modelo de tokens - 21031190
class Token
{
    private $conn;
    private $table_name = "api_tokens";

    public $id;
    public $user_id;
    public $token;
    public $expires_at;
    public $revoked;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function generate($user_id, $hours = 24)
    {
        $this->token    = bin2hex(random_bytes(32));
        $this->user_id  = $user_id;
        $this->expires_at = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));

        $query = "INSERT INTO " . $this->table_name . "
                  SET user_id=:user_id, token=:token, expires_at=:expires_at";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id",    $this->user_id);
        $stmt->bindParam(":token",      $this->token);
        $stmt->bindParam(":expires_at", $this->expires_at);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function validate($token)
    {
        $query = "SELECT id, user_id, token, expires_at, revoked
                  FROM " . $this->table_name . "
                  WHERE token = :token
                  AND revoked = FALSE
                  AND expires_at > NOW()
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id         = $row['id'];
            $this->user_id    = $row['user_id'];
            $this->token      = $row['token'];
            $this->expires_at = $row['expires_at'];
            $this->revoked    = $row['revoked'];
            return true;
        }
        return false;
    }

    public function revoke($token)
    {
        $query = "UPDATE " . $this->table_name . "
                  SET revoked = TRUE
                  WHERE token = :token";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);

        return $stmt->execute();
    }

    public function cleanExpiredTokens($user_id)
    {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE user_id = :user_id
                  AND expires_at < NOW()";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        return $stmt->execute();
    }
}
?>
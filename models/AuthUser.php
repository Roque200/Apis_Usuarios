<?php
// Modelo de autenticación - 21031190
class AuthUser
{
    private $conn;
    private $table_name = "api_users";

    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function findByUsername()
    {
        $query = "SELECT id, username, email, password_hash, status
                  FROM " . $this->table_name . "
                  WHERE username = :username AND status = 'ACTIVE'
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id            = $row['id'];
            $this->username      = $row['username'];
            $this->email         = $row['email'];
            $this->password_hash = $row['password_hash'];
            $this->status        = $row['status'];
            return true;
        }
        return false;
    }

    public function verifyPassword($password)
    {
        return password_verify($password, $this->password_hash);
    }

    public function create($password)
    {
        $query = "INSERT INTO " . $this->table_name . "
                  SET username=:username, email=:email, password_hash=:password_hash";

        $stmt = $this->conn->prepare($query);

        $this->username  = htmlspecialchars(strip_tags($this->username));
        $this->email     = htmlspecialchars(strip_tags($this->email));
        $password_hash   = password_hash($password, PASSWORD_BCRYPT);

        $stmt->bindParam(":username",      $this->username);
        $stmt->bindParam(":email",         $this->email);
        $stmt->bindParam(":password_hash", $password_hash);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
}
?>
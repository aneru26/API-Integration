<?php

class Logout
{
    private $db;
    private $token;

    public function __construct($db, $token)
    {
        $this->db = $db;
        $this->token = $token;
    }

    public function isTokenInvalidated()
    {
        // Check if the token exists in the user_tokens table
        $query = "SELECT * FROM user_tokens WHERE token_id = :token";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':token', $this->token);
        $stmt->execute();

        return $stmt->rowCount() > 0; // Return true if token is found
    }

    public function invalidateToken()
    {
        // Update the token's expired_at field to invalidate it
        $query = "UPDATE user_tokens SET expired_at = NOW() WHERE token_id = :token";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':token', $this->token);
        return $stmt->execute(); // Return true if update is successful
    }

    public function processLogout()
    {
        if ($this->isTokenInvalidated()) {
            return ['success' => false, 'message' => 'Token already invalidated.'];
        }

        if ($this->invalidateToken()) {
            return ['success' => true, 'message' => 'Logged out successfully. Token invalidated.'];
        } else {
            return ['success' => false, 'message' => 'Failed to invalidate token.'];
        }
    }

}

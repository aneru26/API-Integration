<?php

class ProfileUpdate
{
    private $db;
    private $userId;
    private $data;
    private $token;

    public function __construct($db, $userId, $data, $token)
    {
        $this->db = $db;
        $this->userId = $userId;
        $this->data = $data;
        $this->token = $token;
    }

    public function updateProfile()
    {
        try {
            // Check if the token has already been invalidated in the user_tokens table
            $invalidatedQuery = "SELECT * FROM user_tokens WHERE token_id = :token";
            $invalidatedStmt = $this->db->prepare($invalidatedQuery);
            $invalidatedStmt->bindParam(':token', $this->token);
            $invalidatedStmt->execute();
    
            if ($invalidatedStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Token is invalidated. You cannot update the profile.'];
            }
    
            // Validate that at least one field is provided for the update
            if (empty($this->data->first_name) && empty($this->data->last_name) && empty($this->data->date_of_birth)) {
                return ['success' => false, 'message' => 'No data provided for update'];
            }
    
            // Prepare the SQL update query dynamically
            $fields = [];
            $params = [];
    
            if (!empty($this->data->first_name)) {
                $fields[] = "first_name = :first_name";
                $params[':first_name'] = $this->data->first_name;
            }
    
            if (!empty($this->data->last_name)) {
                $fields[] = "last_name = :last_name";
                $params[':last_name'] = $this->data->last_name;
            }
    
            if (!empty($this->data->date_of_birth)) {
                $fields[] = "date_of_birth = :date_of_birth";
                $params[':date_of_birth'] = $this->data->date_of_birth;
            }
    
            // Generate dynamic query
            $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = :user_id";
            $params[':user_id'] = $this->userId;
    
            // Prepare and execute the query
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
    
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Profile updated successfully'];
            } else {
                return ['success' => false, 'message' => 'No changes made to the profile'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
}

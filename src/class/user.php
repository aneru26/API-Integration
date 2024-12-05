<?php
class User{

    private $db;
    public function __construct($db){
        $this->db = $db;
    }

    // -- Function to GET USER PROFILE by user_id --
    public function getProfile($user_id)
    {
        $query = "SELECT user_id, CONCAT(first_name, ' ', last_name) as name, email, role FROM users WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return [
                'id' => $result['user_id'],
                'name' => $result['name'],
                'email' => $result['email'],
                'role' => $result['role']
            ];
        }
        return null; // User not found
    }


    // -- Function to ASSIGN A ROLE to a user -- 

    public function assignRole($user_id, $role)
{
    // Check if the role is valid
    $valid_roles = ['admin', 'customer', 'vendor', 'user']; // Add 'user' to valid roles if necessary
    if (!in_array($role, $valid_roles)) {
        return ['status' => 'error', 'message' => 'Invalid role specified'];
    }

    // Get the current role from the database
    $query = "SELECT role FROM users WHERE user_id = :user_id";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch the current role
    $current_role = $stmt->fetchColumn();

    // If you want to update the role even if it's already assigned, remove the "User already has a role assigned" check

    // Update the role in the database
    $update_query = "UPDATE users SET role = :role WHERE user_id = :user_id";
    $update_stmt = $this->db->prepare($update_query);
    $update_stmt->bindParam(':role', $role, PDO::PARAM_STR);
    $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);

    if ($update_stmt->execute()) {
        return ['status' => 'success', 'message' => 'Role assigned successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to assign role'];
    }
}




    // -- Function to REVOKE ROLE from a user -- 

    public function revokeRole($userId)
    {
        try {
            // Check if the user exists
            $checkQuery = 'SELECT role FROM users WHERE user_id = :user_id';
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->execute();
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'User not found.'];
            }

            // Revoke role (set to NULL or set a default role)
            $updateQuery = 'UPDATE users SET role = NULL WHERE user_id = :user_id';
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bindParam(':user_id', $userId);
            $updateResult = $updateStmt->execute();

            if ($updateResult) {
                return ['success' => true, 'message' => 'User role revoked successfully.'];
            } else {
                return ['success' => false, 'message' => 'Failed to revoke user role.'];
            }

            } catch (PDOException $e) {
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }
    }


    // -- Function to UPLOAD PROFILE PICTURE -- 

    public function uploadProfilePicture($userId, $file) {
        
            if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
                return ['status' => 'error', 'message' => 'File upload error'];
            }

            // Ensure the user exists and is verified
            $stmt = $this->db->prepare('SELECT is_verified FROM users WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    return ['status' => 'error', 'message' => 'User not found'];
                }

                if ($user['is_verified'] == 0) {
                    return ['status' => 'error', 'message' => 'User is not verified. Cannot upload profile picture'];
                }

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = mime_content_type($file['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                return ['status' => 'error', 'message' => 'Invalid file type. Allowed types: jpeg, png, gif'];
            }

            // Validate file size 
            $maxFileSize = 2 * 1024 * 1024; // 2MB in bytes
            if ($file['size'] > $maxFileSize) {
                return ['status' => 'error', 'message' => 'File size exceeds the 2MB limit'];
            }

            // Set the upload directory and generate a unique filename
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = uniqid() . '_' . basename($file['name']);
            $uploadPath = $uploadDir . $fileName;

            // Move the uploaded file to the destination directory
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Store the URL or file path in the database
                $imageUrl = '/uploads/' . $fileName;

                // Ensure the user exists
                $stmt = $this->db->prepare('SELECT * FROM users WHERE user_id = :user_id');
                $stmt->execute(['user_id' => $userId]);
                if ($stmt->rowCount() === 0) {
                    return ['status' => 'error', 'message' => 'User not found'];
                }

                // Update the user's profile image URL
                $stmt = $this->db->prepare('UPDATE users SET profile_image_url = :profile_image_url WHERE user_id = :user_id');
                $stmt->execute(['profile_image_url' => $imageUrl, 'user_id' => $userId]);

                return ['status' => 'success', 'message' => 'Profile picture uploaded successfully', 'image_url' => $imageUrl];
            } else {
                return ['status' => 'error', 'message' => 'Failed to move uploaded file'];
            }
        }


        // Function to LIST ALL USERS

        public function getAllUsers() {
            $query = "SELECT user_id, first_name, last_name, email, role FROM users";
            $stmt = $this->db->prepare($query); // Corrected from $this->conn to $this->db
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    
        public function getUserProfile($user_id) {
            $query = "SELECT role FROM users WHERE user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }


}
?>

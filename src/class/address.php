<?php
class Address {
    private $conn;
    private $table_name = "address";
    private $user_address_table = "user_address";

    // Address properties
    public $user_id;
    public $unit_number;
    public $street_address;
    public $city;
    public $region;
    public $postal_code;
    public $is_default;

    public function __construct($db) {
        $this->conn = $db;
    }

    // -- ADD ADDRESS

    public function addAddress() {
        // Verify if the user is verified
        if (!$this->isUserVerified($this->user_id)) {
            return false; // User is not verified, cannot add address
        }

        // Insert query for the address table
        $query = "INSERT INTO " . $this->table_name . " 
                  SET unit_number = :unit_number, 
                      street_address = :street_address, 
                      city = :city, 
                      region = :region, 
                      postal_code = :postal_code";

        // Prepare the query
        $stmt = $this->conn->prepare($query);

        // Bind values
        $stmt->bindParam(":unit_number", $this->unit_number);
        $stmt->bindParam(":street_address", $this->street_address);
        $stmt->bindParam(":city", $this->city);
        $stmt->bindParam(":region", $this->region);
        $stmt->bindParam(":postal_code", $this->postal_code);

        // Execute the query to insert the address
        if ($stmt->execute()) {
            // Get the last inserted address ID
            $address_id = $this->conn->lastInsertId();

            // Insert into the user_address table to link the user with the address
            $query_user_address = "INSERT INTO " . $this->user_address_table . " 
                                   SET user_id = :user_id, 
                                       address_id = :address_id, 
                                       is_default = :is_default";

            $stmt_user_address = $this->conn->prepare($query_user_address);

            // Bind values
            $stmt_user_address->bindParam(":user_id", $this->user_id);
            $stmt_user_address->bindParam(":address_id", $address_id);
            $stmt_user_address->bindParam(":is_default", $this->is_default);

            // Execute the query to link user and address
            if ($stmt_user_address->execute()) {
                return true;
            }
        }
        return false;
    }

    // --To update and delete address, it needs to verify user id and check first if the user has a role--

   // Verify user ID and role
   public function verifyUserIdAndRole($user_id) {
        $query = "SELECT role, is_verified FROM users WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Change this method to public to allow external access
    public function isUserVerified($user_id) {
        $user = $this->verifyUserIdAndRole($user_id);
        return $user && $user['is_verified'] == 1; // Assuming 1 means verified
    }

    // Verify user ID and address ID association
    public function verifyUserAddress($user_id, $address_id) {
        $query = "SELECT * FROM user_address WHERE user_id = :user_id AND address_id = :address_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':address_id', $address_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update Address
    public function updateAddress($address) {
        $query = "UPDATE address 
                SET unit_number = :unit_number, street_address = :street_address, 
                    city = :city, region = :region, postal_code = :postal_code
                WHERE address_id = :address_id";

        $stmt = $this->conn->prepare($query);
        
        // Bind parameters from array $address
        $stmt->bindParam(':unit_number', $address['unit_number']);
        $stmt->bindParam(':street_address', $address['street_address']);
        $stmt->bindParam(':city', $address['city']);
        $stmt->bindParam(':region', $address['region']);
        $stmt->bindParam(':postal_code', $address['postal_code']);
        $stmt->bindParam(':address_id', $address['address_id']);

        return $stmt->execute();
    }


    // -- FOR DELETE ADDRESS

    public function deleteAddress($address_id) {
        $userAddressQuery = "DELETE FROM user_address WHERE address_id = :address_id";
        $userAddressStmt = $this->conn->prepare($userAddressQuery);
        $userAddressStmt->bindParam(':address_id', $address_id);
        $userAddressStmt->execute();

        $addressQuery = "DELETE FROM address WHERE address_id = :address_id";
        $addressStmt = $this->conn->prepare($addressQuery);
        $addressStmt->bindParam(':address_id', $address_id);

        return $addressStmt->execute();
    }
}
?>

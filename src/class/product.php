<?php

class Product
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function addProduct($product)
    {
        $query = "INSERT INTO products (product_name, description, size, color, price, category_id, image_url, user_id)
              VALUES (:product_name, :description, :size, :color, :price, :category_id, :image_url, :user_id)";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':product_name', $product['product_name']);
        $stmt->bindParam(':description', $product['description']);
        $stmt->bindParam(':size', $product['size']);
        $stmt->bindParam(':color', $product['color']);
        $stmt->bindParam(':price', $product['price']);
        $stmt->bindParam(':category_id', $product['category_id']);
        $stmt->bindParam(':image_url', $product['image_url']);
        $stmt->bindParam(':user_id', $product['user_id']);  // Bind user_id parameter
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);  // Optional, depending on what you need to return
    }
    public function updateProduct($productId, $productData)
    {
        $query = "
        UPDATE products 
        SET product_name = :product_name,
            description = :description,
            size = :size,
            color = :color,
            price = :price,
            category_id = :category_id,
            image_url = :image_url
        WHERE product_id = :product_id
    ";

        $stmt = $this->db->prepare($query);

        // Bind parameters
        $stmt->bindParam(':product_name', $productData['product_name']);
        $stmt->bindParam(':description', $productData['description']);
        $stmt->bindParam(':size', $productData['size']);
        $stmt->bindParam(':color', $productData['color']);
        $stmt->bindParam(':price', $productData['price']);
        $stmt->bindParam(':category_id', $productData['category_id']);
        $stmt->bindParam(':image_url', $productData['image_url']);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function deleteProduct($productId, $userId)
    {
        // Check if the product exists and belongs to the logged-in vendor
        $query = "SELECT user_id FROM products WHERE product_id = :product_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();

        $productOwner = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$productOwner || $productOwner['user_id'] !== $userId) {
            // Return false if the product doesn't exist or belongs to another vendor
            return false;
        }

        // Delete the product
        $query = "DELETE FROM products WHERE product_id = :product_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        return $stmt->execute();
    }



    public function getProductDetails($product_id)
    {
        $query = "
        SELECT p.*, c.category_name 
        FROM products p
        LEFT JOIN product_category c ON p.category_id = c.category_id
        WHERE p.product_id = :product_id
    ";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllProducts($page = 1)
    {
        $limit = 5;
        $offset = ($page - 1) * $limit;

        $query = "SELECT * FROM products LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchProducts($keyword, $page = 1, $perPage = 5, $categoryName = null, $sort = null)
    {
        $offset = ($page - 1) * $perPage;

        // Base query
        $query = "SELECT p.*, c.category_name 
                  FROM products p 
                  LEFT JOIN product_category c ON p.category_id = c.category_id 
                  WHERE (p.product_name LIKE :keyword OR p.description LIKE :keyword)";

        // Add category filtering only if a category is provided
        if (!empty($categoryName)) {
            $query .= " AND LOWER(c.category_name) = LOWER(:category_name)";
        }

        // Add sorting if provided
        if (!empty($sort)) {
            $sortParts = explode('-', $sort);
            if (count($sortParts) === 2) {
                $field = $sortParts[0];
                $direction = strtolower($sortParts[1]);

                $validFields = ['product_name', 'price', 'category_name'];
                $validDirections = ['asc', 'desc'];

                if (in_array($field, $validFields) && in_array($direction, $validDirections)) {
                    $query .= " ORDER BY $field " . strtoupper($direction);
                }
            }
        }

        // Pagination
        $query .= " LIMIT :limit OFFSET :offset";

        // Prepare statement
        $stmt = $this->db->prepare($query);

        // Bind parameters
        $keyword = '%' . $keyword . '%';
        $stmt->bindParam(':keyword', $keyword, PDO::PARAM_STR);

        if (!empty($categoryName)) {
            $stmt->bindParam(':category_name', $categoryName, PDO::PARAM_STR);
        }

        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        // Execute and fetch results
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
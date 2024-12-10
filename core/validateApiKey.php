<?php
// Function to validate API keys
function validateApiKey($apiKey) {
    $validApiKeys = ['12345', '67890']; // Replace with your own API keys
    return in_array($apiKey, $validApiKeys);
}

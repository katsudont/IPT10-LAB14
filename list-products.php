<?php
require 'init.php'; 

try {
    // Retrieve the list of products
    $products = $stripe->products->all(['limit' => 10]);

    echo "<!DOCTYPE html>";
    echo "<html lang='en'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>Product List</title>";
    echo "<style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
                background-color: #f4f4f4;
            }
            .container {
                width: 80%;
                margin: 0 auto;
                padding: 20px;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            .product {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
                padding: 10px;
                border-bottom: 1px solid #ddd;
            }
            .product:last-child {
                border-bottom: none;
            }
            .product img {
                width: 150px;
                height: 150px;
                object-fit: cover;
                border-radius: 8px;
                margin-right: 20px;
            }
            .product-details {
                flex: 1;
            }
            .product h2 {
                margin: 0;
                font-size: 1.5em;
                color: #555;
            }
            .product p {
                margin: 5px 0;
                font-size: 1em;
                color: #666;
            }
          </style>";
    echo "</head>";
    echo "<body>";
    echo "<h1>Product List</h1>";
    echo "<div class='container'>";

    foreach ($products->data as $product) {
        // Retrieve the price for each product
        $prices = $stripe->prices->all(['product' => $product->id]);
        $price = isset($prices->data[0]) ? $prices->data[0]->unit_amount / 100 : 'N/A'; // Stripe uses cents

        echo "<div class='product'>";
        if (isset($product->images[0])) {
            echo "<img src='" . htmlspecialchars($product->images[0]) . "' alt='" . htmlspecialchars($product->name) . "'>";
        } else {
            echo "<img src='https://via.placeholder.com/150' alt='No image available'>"; // Placeholder for missing images
        }

        echo "<div class='product-details'>";
        echo "<h2>" . htmlspecialchars($product->name) . "</h2>";
        echo "<p>Price: $" . htmlspecialchars($price) . "</p>";
        echo "</div>";
        echo "</div>";
    }

    echo "</div>";
    echo "</body>";
    echo "</html>";
} catch (\Stripe\Exception\ApiErrorException $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>

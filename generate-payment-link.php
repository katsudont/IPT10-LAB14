<?php
require 'init.php'; // Include Stripe initialization

// Fetch products and prices from Stripe
try {
    $products = $stripe->products->all(['limit' => 10]); // Fetch 10 products
} catch (\Stripe\Exception\ApiErrorException $e) {
    die("Error fetching products: " . htmlspecialchars($e->getMessage()));
}

$errorMessage = '';
$paymentLinkUrl = '';

// Handle form submission for Payment Link generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_prices = $_POST['prices'] ?? [];

    if (empty($selected_prices)) {
        $errorMessage = "Please select at least one product price.";
    } else {
        try {
            // Create line items based on selected prices
            $line_items = [];
            foreach ($selected_prices as $price_id) {
                $line_items[] = [
                    'price' => htmlspecialchars($price_id),
                    'quantity' => 1,
                ];
            }

            // Create a payment link
            $payment_link = $stripe->paymentLinks->create([
                'line_items' => $line_items,
            ]);

            $paymentLinkUrl = $payment_link->url;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $errorMessage = "Error creating payment link: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Payment Link</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-top: 10px;
            font-weight: bold;
        }
        input[type="checkbox"], button {
            margin-top: 5px;
        }
        .message {
            margin: 20px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        button {
            background-color: #007BFF;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Generate Payment Link</h1>

        <?php if ($paymentLinkUrl): ?>
            <div class="message success">
                Payment link created successfully! <br>
                <a href="<?= htmlspecialchars($paymentLinkUrl) ?>" target="_blank">Click here to pay</a>
            </div>
        <?php elseif ($errorMessage): ?>
            <div class="message error"><?= $errorMessage ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <label for="prices">Select Product Prices</label>
            <?php foreach ($products->data as $product): ?>
                <fieldset>
                    <legend><?= htmlspecialchars($product->name) ?></legend>
                    <?php
                    $prices = $stripe->prices->all(['product' => $product->id, 'limit' => 5]);
                    foreach ($prices->data as $price):
                        $price_label = $price->unit_amount / 100 . " " . strtoupper($price->currency);
                    ?>
                        <div>
                            <input type="checkbox" id="price_<?= htmlspecialchars($price->id) ?>" name="prices[]" value="<?= htmlspecialchars($price->id) ?>">
                            <label for="price_<?= htmlspecialchars($price->id) ?>"><?= htmlspecialchars($price_label) ?></label>
                        </div>
                    <?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>

            <button type="submit">Generate Payment Link</button>
        </form>
    </div>
</body>
</html>

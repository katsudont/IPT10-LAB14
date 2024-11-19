<?php
require 'init.php'; // Include Stripe initialization

// Fetch customers and products from Stripe
try {
    $customers = $stripe->customers->all(['limit' => 10]); // Fetch 10 customers
    $products = $stripe->products->all(['limit' => 10]); // Fetch 10 products
} catch (\Stripe\Exception\ApiErrorException $e) {
    die("Error fetching data: " . htmlspecialchars($e->getMessage()));
}

$errorMessage = '';
$successMessage = '';
$invoice = null;

// Handle form submission for Invoice Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer'];
    $selected_prices = $_POST['prices'] ?? [];

    if (!$customer_id || empty($selected_prices)) {
        $errorMessage = "Please select a customer and at least one product price.";
    } else {
        try {
            // Create an invoice for the selected customer
            $invoice = $stripe->invoices->create([
                'customer' => htmlspecialchars($customer_id),
            ]);

            // Attach selected prices as line items to the invoice
            foreach ($selected_prices as $price_id) {
                $stripe->invoiceItems->create([
                    'customer' => htmlspecialchars($customer_id),
                    'price' => htmlspecialchars($price_id),
                    'invoice' => $invoice->id,
                ]);
            }

            // Finalize the invoice
            $stripe->invoices->finalizeInvoice($invoice->id);
            $invoice = $stripe->invoices->retrieve($invoice->id);

            $successMessage = "Invoice created successfully!";
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $errorMessage = "Error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice</title>
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
        select, input[type="checkbox"], button {
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
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Invoice</h1>

        <?php if ($successMessage): ?>
            <div class="message success"><?= $successMessage ?></div>
            
            <div class="btn-container">
                <!-- Button to Download Invoice PDF -->
                <a href="<?= htmlspecialchars($invoice->invoice_pdf) ?>" target="_blank">
                    <button>Download Invoice PDF</button>
                </a>
                
                <!-- Button to Redirect to Payment Link -->
                <a href="<?= htmlspecialchars($invoice->hosted_invoice_url) ?>" target="_blank">
                    <button>Pay Invoice</button>
                </a>
            </div>
        <?php elseif ($errorMessage): ?>
            <div class="message error"><?= $errorMessage ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <!-- Customer Dropdown -->
            <label for="customer">Select Customer</label>
            <select id="customer" name="customer" required>
                <option value="">-- Choose a customer --</option>
                <?php foreach ($customers->data as $customer): ?>
                    <option value="<?= htmlspecialchars($customer->id) ?>">
                        <?= htmlspecialchars($customer->name ?: $customer->email) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Products and Prices -->
            <label for="prices">Select Product Prices</label>
            <?php foreach ($products->data as $product): ?>
                <fieldset>
                    <legend><?= htmlspecialchars($product->name) ?></legend>
                    <?php
                    $prices = $stripe->prices->all(['product' => $product->id, 'limit' => 5]);
                    foreach ($prices->data as $price):
                        // Exclude "recurring" from the label
                        $price_label = $price->unit_amount / 100 . " " . strtoupper($price->currency);
                    ?>
                        <div>
                            <input type="checkbox" id="price_<?= htmlspecialchars($price->id) ?>" name="prices[]" value="<?= htmlspecialchars($price->id) ?>">
                            <label for="price_<?= htmlspecialchars($price->id) ?>"><?= htmlspecialchars($price_label) ?></label>
                        </div>
                    <?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>

            <button type="submit">Generate Invoice</button>
        </form>
    </div>
</body>
</html>

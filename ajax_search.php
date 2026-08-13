<?php
include("config/db.php");

header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($q) || strlen($q) < 2) {
    echo json_encode(['success' => true, 'results' => []]);
    exit();
}

$safe_q = mysqli_real_escape_string($conn, $q);

$query = mysqli_query($conn, "
    SELECT p.id, p.name, p.price, p.selling_price, p.image, p.stock, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.name LIKE '%$safe_q%'
       OR p.description LIKE '%$safe_q%'
       OR c.category_name LIKE '%$safe_q%'
    ORDER BY
        CASE
            WHEN p.name LIKE '$safe_q%' THEN 1
            WHEN p.name LIKE '%$safe_q%' THEN 2
            ELSE 3
        END,
        p.id DESC
    LIMIT 6
");

$results = [];
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $img = !empty($row['image']) ? $row['image'] : 'placeholder.jpg';
        $results[] = [
            'id'             => (int)$row['id'],
            'name'           => htmlspecialchars_decode($row['name']),
            'price_fmt'      => number_format($row['price'], 2),
            'selling_fmt'    => number_format($row['selling_price'], 2),
            'price_raw'      => floatval($row['price']),
            'selling_raw'    => floatval($row['selling_price']),
            'image'          => $img,
            'stock'          => (int)$row['stock'],
            'category_name'  => htmlspecialchars_decode($row['category_name'] ?? 'General')
        ];
    }
}

echo json_encode([
    'success' => true,
    'query'   => $q,
    'results' => $results
]);

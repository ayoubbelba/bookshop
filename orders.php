<?php
session_start();
include('db_connection.php');

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// orders.php
$query_orders = "SELECT o.order_id, o.order_date, o.order_status, u.username 
                 FROM orders o 
                 INNER JOIN users u ON o.user_id = u.id 
                 ORDER BY o.order_date DESC";
$result_orders = mysqli_query($conn, $query_orders);

if (!$result_orders) {
    die("Query failed: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">
    <style>
        body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    color: #333;
}

h2 {
    text-align: center;
    color: #4CAF50;
    margin: 20px 0;
    font-size: 32px;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

table {
    width: 80%;
    margin: 20px auto;
    border-collapse: collapse;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
}

table thead tr {
    background-color: #4CAF50;
    color: #fff;
    text-align: left;
    font-weight: bold;
    letter-spacing: 0.5px;
}

table th, table td {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
}

table th {
    background-color: #4CAF50;
    color: white;
}

table tbody tr {
    transition: background-color 0.3s ease;
}

table tbody tr:nth-of-type(even) {
    background-color: #f9f9f9;
}

table tbody tr:hover {
    background-color: #f1f1f1;
    cursor: pointer;
}

table tbody tr:last-of-type {
    border-bottom: none;
}

a {
    text-decoration: none;
    color: #4CAF50;
    transition: color 0.3s ease, background-color 0.3s ease;
    padding: 8px 12px;
    border-radius: 5px;
    background-color: #e0f7fa;
}

a:hover {
    color: #fff;
    background-color: #45a049;
}

p {
    text-align: center;
    color: #666;
    font-size: 18px;
    margin: 20px 0;
}

@media (max-width: 768px) {
    table {
        width: 100%;
        margin: 10px;
    }

    table th, table td {
        padding: 10px 15px;
    }

    h2 {
        font-size: 28px;
    }
}


    </style>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h2>Admin - Order List</h2>

<!-- Check if there are any orders -->
<?php if (mysqli_num_rows($result_orders) > 0): ?>
    <table border="1">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>User</th>
                <th>Order Date</th>
                <th>Order Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result_orders)): ?>
                <tr>
                    <td><?php echo $row['order_id']; ?></td>
                    <td><?php echo $row['username']; ?></td>
                    <td><?php echo date("Y-m-d H:i:s", strtotime($row['order_date'])); ?></td>
                    <td><?php echo ucfirst($row['order_status']); ?></td>
                    <td><a href="view_order.php?order_id=<?php echo $row['order_id']; ?>">View Details</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No orders found.</p>
<?php endif; ?>

</body>
</html>

<?php
session_start();
include('db_connection.php');

// Check if the form is submitted
$search_query = '';
if (isset($_GET['search'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['search']);  // Clean the search string
}

// Query to fetch books from the database based on search
$query = "SELECT * FROM books WHERE title LIKE '%$search_query%' OR author LIKE '%$search_query%'";
$result = mysqli_query($conn, $query);

// Check if there are books in the database
$books = [];
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $books[] = $row;
    }
}

// Check if logout is requested
if (isset($_GET['logout'])) {
    session_unset();  // Remove session data
    session_destroy();  // Destroy the session
    header("Location: login.php");  // Redirect to login page
    exit();
}

// Handle adding books to cart
if (isset($_GET['add_to_cart'])) {
    $book_id = $_GET['add_to_cart'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; // Assuming the user_id is stored in session
    $quantity = 1; // Default quantity

    if ($user_id > 0) {
        // Check if the book is already in the cart
        $query_check = "SELECT * FROM cart WHERE user_id = '$user_id' AND book_id = '$book_id'";
        $result_check = mysqli_query($conn, $query_check);
        if (mysqli_num_rows($result_check) > 0) {
            // Update the quantity if the book already exists in the cart
            $query_update = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = '$user_id' AND book_id = '$book_id'";
            mysqli_query($conn, $query_update);
        } else {
            // Insert the book into the cart
            $query_add = "INSERT INTO cart (user_id, book_id, quantity, added_at) VALUES ('$user_id', '$book_id', '$quantity', NOW())";
            mysqli_query($conn, $query_add);
        }
        header("Location: index.php");  // Redirect to the same page after adding to cart
        exit();
    } else {
        // Redirect to login page if the user is not logged in
        header("Location: login.php");
        exit();
    }
    
}

// Handle adding/removing books to favorites
if (isset($_GET['toggle_favorite'])) {
    $book_id = $_GET['toggle_favorite'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; // Assuming the user_id is stored in session

    if ($user_id > 0) {
        // Check if the book is already in the user's favorites
        $query_check_favorite = "SELECT * FROM favorites WHERE user_id = '$user_id' AND book_id = '$book_id'";
        $result_check_favorite = mysqli_query($conn, $query_check_favorite);
        if (mysqli_num_rows($result_check_favorite) > 0) {
            // If the book is already a favorite, remove it
            $query_remove_favorite = "DELETE FROM favorites WHERE user_id = '$user_id' AND book_id = '$book_id'";
            mysqli_query($conn, $query_remove_favorite);
        } else {
            // If not a favorite, add it to the favorites
            $query_add_favorite = "INSERT INTO favorites (user_id, book_id) VALUES ('$user_id', '$book_id')";
            mysqli_query($conn, $query_add_favorite);
        }
        header("Location: index.php");  // Reload the page to reflect the changes
        exit();
    } else {
        // Redirect to login page if the user is not logged in
        header("Location: login.php");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Library</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('index.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
            color: white;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            padding: 20px;
            text-align: center;
            color: white;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        header h1 {
            
            font-size: 40px;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.7);
            font-weight: bold;
        }

      

        .search-container {
            margin-top: 20px;
            text-align: center;
        }

        .search-container input {
            padding: 10px;
            width: 300px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .search-container button {
            padding: 10px 15px;
            background-color: #1E90FF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .search-container button:hover {
            background-color: #4682b4;
        }

        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            padding: 50px;
            flex-grow: 1;
        }

        .book-card {
            width: 250px;
            margin: 15px;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .book-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }

        .book-card:hover {
            transform: scale(1.05);
        }

        .book-card .content {
            padding: 15px;
            text-align: center;
        }

        .book-card .content h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }

        .book-card .content p {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .book-card .content .author {
            font-size: 14px;
            color: #1E90FF;
            font-weight: bold;
        }

        .book-card .content .price {
            font-size: 16px;
            font-weight: bold;
            color: #28a745;  /* Green color to make the price stand out */
            margin-top: 10px;
        }

        .book-card .content .btn {
            background-color: #1E90FF;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .book-card .content .btn:hover {
            background-color: #4682b4;
        }
        .book-card .content .btn.add-to-favorites {
    margin-top: 5px; /* Adds more space between Add to Cart and Add to Favorites */
    background-color: #FFD700; /* Gold color for the "Add to Favorites" button */
    text-align: center; /* Ensures the button is centered */
    display: inline-block; /* Make it behave like a block for better spacing */
    padding: 10px 1px; /* Adjust padding for a better look */
    width: 100%; /* Ensures the button takes the full width of the container */
    font-weight: bold;
    transition: background-color 0.3s ease;
}


.book-card .content .btn.add-to-favorites:hover {
    background-color: #FFA500; /* Darker gold color when hovered */
}

/* Adjust the button container's text alignment to center all buttons */
.book-card .content {
    text-align: center; /* Center all elements inside the book card */
}


        .logout-button {
            padding: 10px 15px;
            background-color: #FF6347;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .logout-button:hover {
            background-color: #FF4500;
        }
        /* Go to Cart Button */
header .btn {
    background-color: #1E90FF; /* Blue color for the button */
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    margin-left: 15px; /* Space between the logout button */
    font-size: 16px;
}

header .btn:hover {
    background-color: #4682b4; /* Darker blue when hovered */
}

.admin-btn {
    position: absolute;
    top: 20px;   /* Adjust the distance from the top */
    left: 20px;  /* Adjust the distance from the left */
    
    
    padding: 10px 15px;
    border-radius: 40px;
    text-decoration: none;
    font-size: 16px;
}


/* Position the 'View My Favorites' button in the top right */
.btn-view svg {
    position: fixed;
    top: 20px;
    right: 20px;

    
    
   
    cursor: pointer;
    
    transition: transform 0.3s ease;
}

.btn-view:hover svg {
 
    transform: scale(1.3);
}




        footer {
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 20px;
            width: 100%;
            position: relative;
            bottom: 0;
            margin-top: auto;
        }

        footer a {
            color: white;
            text-decoration: none;
        }
        .admin-btn svg {
        
            padding: 20px; 
            transition: transform 0.3s ease; /* تحديد سرعة الانتقال */
        }

        .admin-btn:hover svg {
    
            transform: scale(1.3); /* تكبير الحجم بنسبة 10% عند التمرير */
        }
        .btn-to-cart svg{

    
            position: fixed;
            top: 80px;
            right: 20px;

    
    
   
            cursor: pointer;
    
            transition: transform 0.3s ease;
        }

        .btn-to-cart:hover svg {
 
            transform: scale(1.3);

        }
        /* Buy button styling */
.btn-buy {
    display: block;
    width: 100%;
    padding: 5px;
    overflow-y: hidden;
    background-color: #28a745;  /* Green color for the Buy button */
    color: white;
    border: none;

    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    font-size: 18px;
    margin-top: 20px;
    transition: background-color 0.3s ease, transform 0.2s ease;  /* Smooth hover effect */
}

.btn-buy:hover {
    background-color: #218838;  /* Darker green when hovered */
    transform: scale(1.05);  /* Slight zoom effect when hovered */
}

.btn-buy:focus {
    outline: none;  /* Remove outline when focused */
}

       
      
    </style>
</head>
<body>

<header>
    <h1>Book Shop</h1>
    <h2>Welcome <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest'; ?></h2>
    <?php if (isset($_SESSION['username'])): ?>
        <a href="?logout=true" class="logout-button">Logout</a>
        <!-- Show the 'Go to Cart' button only if the user is not an admin -->
        <?php if ($_SESSION['role'] !== 'admin'): ?>
            <a href="cart.php" class="btn-to-cart"><svg fill="#000000" xmlns="http://www.w3.org/2000/svg" 
	 width="50px" height="50px" viewBox="0 0 52 52" enable-background="new 0 0 52 52" xml:space="preserve" width="50" height="50">
<g>
	<path d="M20.1,26H44c0.7,0,1.4-0.5,1.5-1.2l4.4-15.4c0.3-1.1-0.5-2-1.5-2H11.5l-0.6-2.3c-0.3-1.1-1.3-1.8-2.3-1.8
		H4.6c-1.3,0-2.5,1-2.6,2.3C1.9,7,3.1,8.2,4.4,8.2h2.3l7.6,25.7c0.3,1.1,1.2,1.8,2.3,1.8h28.2c1.3,0,2.5-1,2.6-2.3
		c0.1-1.4-1.1-2.6-2.4-2.6H20.2c-1.1,0-2-0.7-2.3-1.7v-0.1C17.4,27.5,18.6,26,20.1,26z"/>
	<circle cx="20.6" cy="44.6" r="4"/>
	<circle cx="40.1" cy="44.6" r="4"/>
</g>
</svg></a>
        <?php endif; ?>
       
    <?php endif; ?>
    <!-- Add link to view favorites only if the role is 'user' -->
    <?php if (isset($_SESSION['username']) && $_SESSION['role'] == 'user'): ?>
          <a href="favorite.php" class="btn-view"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 485 485" style="enable-background:new 0 0 485 485;" xml:space="preserve" width="50" height="50">
<path d="M348.629,11.209c-41.588,0-80.489,19.029-106.129,50.852c-25.641-31.823-64.541-50.852-106.129-50.852
	C61.176,11.209,0,72.385,0,147.579c0,59.064,35.289,127.458,104.885,203.28c53.64,58.438,111.995,103.687,128.602,116.164
	l9.01,6.769l9.009-6.768c16.608-12.477,74.964-57.725,128.605-116.162C449.71,275.04,485,206.646,485,147.579
	C485,72.385,423.824,11.209,348.629,11.209z"/>
</svg></a>
    <?php endif; ?>
    
</header>

 <!-- Show 'Admin Dashboard' button if the user is an admin -->
 <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="admin_dashboard.php" class="admin-btn"><svg class="svg-container" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 474.565 474.565" style="enable-background:new 0 0 474.565 474.565;" xml:space="preserve" width="50" height="50">
<g>
	<path d="M255.204,102.3c-0.606-11.321-12.176-9.395-23.465-9.395C240.078,95.126,247.967,98.216,255.204,102.3z"/>
	<path d="M134.524,73.928c-43.825,0-63.997,55.471-28.963,83.37c11.943-31.89,35.718-54.788,66.886-63.826
		C163.921,81.685,150.146,73.928,134.524,73.928z"/>
	<path d="M43.987,148.617c1.786,5.731,4.1,11.229,6.849,16.438L36.44,179.459c-3.866,3.866-3.866,10.141,0,14.015l25.375,25.383
		c1.848,1.848,4.38,2.888,7.019,2.888c2.61,0,5.125-1.04,7.005-2.888l14.38-14.404c2.158,1.142,4.55,1.842,6.785,2.827
		c0-0.164-0.016-0.334-0.016-0.498c0-11.771,1.352-22.875,3.759-33.302c-17.362-11.174-28.947-30.57-28.947-52.715
		c0-34.592,28.139-62.739,62.723-62.739c23.418,0,43.637,13.037,54.43,32.084c11.523-1.429,22.347-1.429,35.376,1.033
		c-1.676-5.07-3.648-10.032-6.118-14.683l14.396-14.411c1.878-1.856,2.918-4.38,2.918-7.004c0-2.625-1.04-5.148-2.918-7.004
		l-25.361-25.367c-1.94-1.941-4.472-2.904-7.003-2.904c-2.532,0-5.063,0.963-6.989,2.904l-14.442,14.411
		c-5.217-2.764-10.699-5.078-16.444-6.825V9.9c0-5.466-4.411-9.9-9.893-9.9h-35.888c-5.451,0-9.909,4.434-9.909,9.9v20.359
		c-5.73,1.747-11.213,4.061-16.446,6.825L75.839,22.689c-1.942-1.941-4.473-2.904-7.005-2.904c-2.531,0-5.077,0.963-7.003,2.896
		L36.44,48.048c-1.848,1.864-2.888,4.379-2.888,7.012c0,2.632,1.04,5.148,2.888,7.004l14.396,14.403
		c-2.75,5.218-5.063,10.708-6.817,16.438H23.675c-5.482,0-9.909,4.441-9.909,9.915v35.889c0,5.458,4.427,9.908,9.909,9.908H43.987z"
		/>
	<path d="M354.871,340.654c15.872-8.705,26.773-25.367,26.773-44.703c0-28.217-22.967-51.168-51.184-51.168
		c-9.923,0-19.118,2.966-26.975,7.873c-4.705,18.728-12.113,36.642-21.803,52.202C309.152,310.022,334.357,322.531,354.871,340.654z
		"/>
	<path d="M460.782,276.588c0-5.909-4.799-10.693-10.685-10.693H428.14c-1.896-6.189-4.411-12.121-7.393-17.75l15.544-15.544
		c2.02-2.004,3.137-4.721,3.137-7.555c0-2.835-1.118-5.553-3.137-7.563l-27.363-27.371c-2.08-2.09-4.829-3.138-7.561-3.138
		c-2.734,0-5.467,1.048-7.547,3.138l-15.576,15.552c-5.623-2.982-11.539-5.481-17.751-7.369v-21.958
		c0-5.901-4.768-10.685-10.669-10.685H311.11c-2.594,0-4.877,1.04-6.739,2.578c3.26,11.895,5.046,24.793,5.046,38.552
		c0,8.735-0.682,17.604-1.956,26.423c7.205-2.656,14.876-4.324,22.999-4.324c36.99,0,67.086,30.089,67.086,67.07
		c0,23.637-12.345,44.353-30.872,56.303c13.48,14.784,24.195,32.324,31.168,51.976c1.148,0.396,2.344,0.684,3.54,0.684
		c2.733,0,5.467-1.04,7.563-3.13l27.379-27.371c2.004-2.004,3.106-4.721,3.106-7.555s-1.102-5.551-3.106-7.563l-15.576-15.552
		c2.982-5.621,5.497-11.555,7.393-17.75h21.957c2.826,0,5.575-1.118,7.563-3.138c2.004-1.996,3.138-4.72,3.138-7.555
		L460.782,276.588z"/>
	<path d="M376.038,413.906c-16.602-48.848-60.471-82.445-111.113-87.018c-16.958,17.958-37.954,29.351-61.731,29.351
		c-23.759,0-44.771-11.392-61.713-29.351c-50.672,4.573-94.543,38.17-111.145,87.026l-9.177,27.013
		c-2.625,7.773-1.368,16.338,3.416,23.007c4.783,6.671,12.486,10.631,20.685,10.631h315.853c8.215,0,15.918-3.96,20.702-10.631
		c4.767-6.669,6.041-15.234,3.4-23.007L376.038,413.906z"/>
	<path d="M120.842,206.782c0,60.589,36.883,125.603,82.352,125.603c45.487,0,82.368-65.014,82.368-125.603
		C285.563,81.188,120.842,80.939,120.842,206.782z"/>
</g>
</svg></a>
        <?php endif; ?>


    <!-- Search Form -->
    <div class="search-container">
        <form action="" method="get">
            <input type="text" name="search" placeholder="Search for books or authors..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="container">
    <?php if (!empty($books)): ?>
        <?php foreach ($books as $book): ?>
            <div class="book-card">
                <img src="uploads/<?php echo htmlspecialchars($book['image_path']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                <div class="content">
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                    <p><?php echo htmlspecialchars(substr($book['description'], 0, 100)) . '...'; ?></p>
                    <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
                    <p class="price">Price: $<?php echo number_format($book['price'], 2); ?></p>
                    <a href="book_details.php?id=<?php echo $book['id']; ?>" class="btn">Details</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'user'): ?>
                        <a href="index.php?add_to_cart=<?php echo $book['id']; ?>" class="btn">Add to Cart</a>
                        <a href="index.php?toggle_favorite=<?php echo $book['id']; ?>" class="btn add-to-favorites">Add to Favorites</a>
                        <a href="checkout2.php?book_id=<?php echo $book['id']; ?>" class="btn-buy">Buy</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No books found. Try searching for a different keyword.</p>
    <?php endif; ?>
</div>



<script>
$(document).ready(function() {
    // Handle "Add to Cart" click
    $('.add-to-cart').click(function(e) {
        e.preventDefault();  // Prevent default action (page reload)
        var bookId = $(this).data('book-id');
        var action = $(this).data('action');

        $.ajax({
            url: 'index.php', // Send the request to the current page
            type: 'GET',
            data: {
                action: action,
                book_id: bookId
            },
            success: function(response) {
                // You can update UI elements here (e.g., show a message or update cart icon)
                alert("Book added to cart!");
                // Optionally, you can update some elements in the UI to show the book has been added.
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
        });
    });

    // Handle "Add to Favorites" click
    $('.add-to-favorites').click(function(e) {
        e.preventDefault();  // Prevent default action (page reload)
        var bookId = $(this).data('book-id');
        var action = $(this).data('action');

        $.ajax({
            url: 'index.php', // Send the request to the current page
            type: 'GET',
            data: {
                action: action,
                book_id: bookId
            },
            success: function(response) {
                // You can update UI elements here (e.g., show a message or update favorites icon)
                alert("Book added to favorites!");
                // Optionally, you can update some elements in the UI to reflect the change, like:
                // - Change button text
                // - Highlight the favorite icon
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
        });
    });
});
</script>




    <footer>
   
        <p>Book Library &copy; 2025</p>  <p>Contact us belbalayob92@gmail.com</p>
    </footer>
</body>
</html>

<?php
include("../config/db.php");
require_once('auth_guard.php');


// Add Category
if(isset($_POST['add_category']))
{
    $category_name = mysqli_real_escape_string(
        $conn,
        $_POST['category_name']
    );

    if(!empty($category_name))
    {
        mysqli_query(
            $conn,
            "INSERT INTO categories(category_name)
             VALUES('$category_name')"
        );

        header("Location: categories.php");
        exit();
    }
}

// Delete Category
if(isset($_GET['delete']))
{
    $id = (int)$_GET['delete'];

    mysqli_query(
        $conn,
        "DELETE FROM categories WHERE id='$id'"
    );

    header("Location: categories.php");
    exit();
}

// Fetch Categories
$categories = mysqli_query(
    $conn,
    "SELECT * FROM categories ORDER BY id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Categories Management</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f5f7fa;
}

.main-content{
    margin-left:255px;
    padding:30px;
}

.card{
    border:none;
    border-radius:15px;
    box-shadow:0 3px 10px rgba(0,0,0,0.1);
}

</style>
</head>
<body>

<?php include('includes/sidebar.php'); ?>

<!-- Main Content -->
<div class="main-content">

    <h2 class="mb-4">Category Management</h2>

    <div class="row">

        <!-- Add Category -->
        <div class="col-md-4">

            <div class="card">
                <div class="card-header bg-success text-white">
                    Add Category
                </div>

                <div class="card-body">

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label">
                                Category Name
                            </label>

                            <input
                                type="text"
                                name="category_name"
                                class="form-control"
                                required>
                        </div>

                        <button
                            type="submit"
                            name="add_category"
                            class="btn btn-success">

                            Add Category

                        </button>

                    </form>

                </div>
            </div>

        </div>

        <!-- Category List -->
        <div class="col-md-8">

            <div class="card">

                <div class="card-header bg-success text-white">
                    Category List
                </div>

                <div class="card-body">

                    <table class="table table-bordered table-hover">

                        <thead class="table-success">
                            <tr>
                                <th>ID</th>
                                <th>Category Name</th>
                                <th width="120">Action</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php
                        if(mysqli_num_rows($categories) > 0)
                        {
                            while($row=mysqli_fetch_assoc($categories))
                            {
                        ?>

                            <tr>

                                <td>
                                    <?php echo $row['id']; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($row['category_name']); ?>
                                </td>

                                <td>

                                    <a
                                    href="categories.php?delete=<?php echo $row['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this category?')">

                                    Delete

                                    </a>

                                </td>

                            </tr>

                        <?php
                            }
                        }
                        else
                        {
                            echo "
                            <tr>
                                <td colspan='3' class='text-center'>
                                    No Categories Found
                                </td>
                            </tr>";
                        }
                        ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>

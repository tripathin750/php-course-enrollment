<?php
include 'db_connection.php';

// Check session for authentication
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Function to get all courses
function getCourses($conn) {
    $sql = "SELECT * FROM courses";
    $result = $conn->query($sql);
    $courses = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
    }
    return $courses;
}

// Add course logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];

    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO courses (title, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $title, $description);

    // Execute the statement
    if ($stmt->execute()) {
        header("Location: admin_dashboard.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }

    // Close statement
    $stmt->close();
}

// Remove course logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_course'])) {
    $course_id = $_POST['course_id'];

    // Prepare SQL statement
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);

    // Execute the statement
    if ($stmt->execute()) {
        header("Location: admin_dashboard.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }

    // Close statement
    $stmt->close();
}

$targetDirectory = __DIR__ . "/resources/";
// Upload resource logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_resource'])) {
    $course_id = $_POST['course_id'];

    // Process file upload
    $targetDirectory = "resources/"; // Directory where uploaded resources will be stored
    $targetFile = $targetDirectory . basename($_FILES["resource"]["name"]);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($targetFile,PATHINFO_EXTENSION));

    // Check if file already exists
    if (file_exists($targetFile)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["resource"]["size"] > 5000000) { // Adjust the file size limit as needed
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if($fileType != "pdf" && $fileType != "doc" && $fileType != "docx") {
        echo "Sorry, only PDF, DOC, DOCX files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    } else {
        // If everything is ok, try to upload file
        if (move_uploaded_file($_FILES["resource"]["tmp_name"], $targetFile)) {
            // File uploaded successfully, update course record with resource path
            $resourcePath = $targetFile;

            // Prepare SQL statement
            $stmt = $conn->prepare("UPDATE courses SET resources = ? WHERE id = ?");
            $stmt->bind_param("si", $resourcePath, $course_id);

            // Execute the statement
            if ($stmt->execute()) {
                echo "The file ". basename( $_FILES["resource"]["name"]). " has been uploaded for the course.";
            } else {
                echo "Error: " . $conn->error;
            }

            // Close statement
            $stmt->close();
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}

// Get all courses
$courses = getCourses($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        /* Your CSS styles for the admin dashboard */
        /* Example: */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            margin-top: 0;
            text-align: center;
            color: #333;
        }
        form {
            margin-bottom: 20px;
        }
        input[type=text], input[type=email], select, input[type=file] {
            width: calc(100% - 22px); /* Adjusted width to account for padding */
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .add-remove-course-form {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .resource-upload-form {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome Admin!</h2>
        
        <!-- Add Course Form -->
        <form action="admin_dashboard.php" method="post" class="add-remove-course-form">
            <h3>Add Course</h3>
            <input type="text" name="title" placeholder="Course Title" required><br>
            <input type="text" name="description" placeholder="Course Description" required><br>
            <button type="submit" name="add_course">Add Course</button>
        </form>
        
        <!-- List of Courses -->
        <h3>Available Courses</h3>
        <table>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
            <?php if (!empty($courses)) { ?>
                <?php foreach ($courses as $course) { ?>
                    <tr>
                        <td><?php echo $course['title']; ?></td>
                        <td><?php echo $course['description']; ?></td>
                        <td>
                            <form action="admin_dashboard.php" method="post">
                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                <button type="submit" name="remove_course">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="3">No courses available.</td>
                </tr>
            <?php } ?>
        </table>

        <!-- Upload Resource Form -->
        <form action="admin_dashboard.php" method="post" enctype="multipart/form-data" class="resource-upload-form">
            <h3>Upload Resource</h3>
            <select name="course_id" required>
                <option value="" selected disabled>Select Course</option>
                <?php foreach ($courses as $course) { ?>
                    <option value="<?php echo $course['id']; ?>"><?php echo $course['title']; ?></option>
                <?php } ?>
            </select><br>
            <input type="file" name="resource" required><br>
            <button type="submit" name="upload_resource">Upload Resource</button>
        </form>
    </div>
</body>
</html>

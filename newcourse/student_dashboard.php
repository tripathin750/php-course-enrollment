<?php
include 'db_connection.php';

// Check session for authentication
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Function to get all courses
function getCourses($conn) {
    $sql = "SELECT * FROM courses";
    $result = $conn->query($sql);
    $courses = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
    }
    return $courses;
}

// Function to get enrolled courses for a student
function getEnrolledCourses($conn, $student_id) {
    $sql = "SELECT courses.* FROM courses JOIN enrollments ON courses.id = enrollments.course_id WHERE enrollments.student_id = $student_id";
    $result = $conn->query($sql);
    $enrolledCourses = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $enrolledCourses[] = $row;
        }
    }
    return $enrolledCourses;
}

// Function to get resources for a specific course
function getCourseResources($conn, $course_id) {
    $sql = "SELECT resources FROM courses WHERE id = $course_id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['resources'];
    }
    return null;
}

// Enroll in a course logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enroll'])) {
    $student_id = $_SESSION['user_id'];
    $course_id = $_POST['course_id'];

    // Check if student is already enrolled in the course
    $checkEnrollmentStmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $checkEnrollmentStmt->bind_param("ii", $student_id, $course_id);
    $checkEnrollmentStmt->execute();
    $checkEnrollmentStmt->store_result();
    if ($checkEnrollmentStmt->num_rows > 0) {
        echo "You are already enrolled in this course.";
    } else {
        // Enroll student in the course
        $enrollStmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
        $enrollStmt->bind_param("ii", $student_id, $course_id);
        if ($enrollStmt->execute()) {
            echo "Successfully enrolled in the course.";
        } else {
            echo "Error: " . $conn->error;
        }
        $enrollStmt->close();
    }
    $checkEnrollmentStmt->close();
}

// Get all courses
$courses = getCourses($conn);

// Get enrolled courses for the student
$student_id = $_SESSION['user_id'];
$enrolledCourses = getEnrolledCourses($conn, $student_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        /* CSS styles for the student dashboard */
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
        }
        h2 {
            text-align: center;
        }
        .enroll-form {
            margin-bottom: 20px;
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
        .enroll-btn-disabled {
            background-color: #ccc;
            cursor: not-allowed;
            opacity: 0.5;
        }
        .enroll-btn-disabled:hover {
            background-color: #ccc;
        }
        .enroll-btn-disabled:active {
            background-color: #ccc;
        }
        .enroll-btn-enabled {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        .enroll-btn-enabled:hover {
            background-color: #45a049;
        }
        .enroll-btn-enabled:active {
            background-color: #45a049;
        }
        .resources-link {
            color: #3498db;
            text-decoration: underline;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome Student!</h2>
        
        <!-- Enroll in a Course Form -->
        <form action="student_dashboard.php" method="post" class="enroll-form">
            <h3>Enroll in a Course</h3>
            <select name="course_id" required>
                <option value="" selected disabled>Select Course</option>
                <?php foreach ($courses as $course) { ?>
                    <?php
                    $isEnrolled = false;
                    foreach ($enrolledCourses as $enrolledCourse) {
                        if ($enrolledCourse['id'] == $course['id']) {
                            $isEnrolled = true;
                            break;
                        }
                    }
                    ?>
                    <option value="<?php echo $course['id']; ?>" <?php if ($isEnrolled) echo "disabled"; ?>>
                        <?php echo $course['title']; ?>
                        <?php if ($isEnrolled) echo "(Enrolled)"; ?>
                    </option>
                <?php } ?>
            </select><br>
            <button type="submit" name="enroll" class="<?php echo ($isEnrolled) ? 'enroll-btn-disabled' : 'enroll-btn-enabled'; ?>">
                <?php echo ($isEnrolled) ? 'Enrolled' : 'Enroll'; ?>
            </button>
        </form>
        
        <!-- List of Enrolled Courses -->
        <h3>Enrolled Courses</h3>
        <table>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Resources</th>
            </tr>
            <?php foreach ($enrolledCourses as $course) { ?>
                <tr>
                    <td><?php echo $course['title']; ?></td>
                    <td><?php echo $course['description']; ?></td>
                    <td>
                        <?php 
                        $resources = getCourseResources($conn, $course['id']);
                        if ($resources) {
                            echo '<a href="' . $resources . '" target="_blank" class="resources-link">View Resources</a>';
                        } else {
                            echo 'No resources available';
                        }
                        ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>

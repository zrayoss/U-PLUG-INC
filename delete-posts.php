<?php
require_once "assets/config/dbconfig.php"; // adjust di path if needed
var_dump($_POST["id"]);
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST["id"]) || empty($_POST["id"])) {
        echo "missing_id";
        exit;
    }

    $post_id = $_POST["id"];

    // Prepare di delete statement
    $stmt = $conn->prepare("DELETE FROM posts WHERE post_id = ?");
    $stmt->bind_param("s", $post_id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "invalid_request";
}
header("Location: posts.php");
?>

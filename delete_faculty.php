<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  exit("unauthorized");
}

require_once "assets/config/dbconfig.php";

if (isset($_POST['id'])) {
  $id = intval($_POST['id']);
  $stmt = $conn->prepare("DELETE FROM faculty_users WHERE seq_id = ?");
  $stmt->bind_param("i", $id);

  if ($stmt->execute()) {
    echo "success";
  } else {
    echo "error";
  }

  $stmt->close();
  $conn->close();
} else {
  echo "invalid";
}
?>

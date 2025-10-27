<?php

session_start();

if (isset($_SESSION['user_id'])){
    
  require __DIR__ ."/assets/config/dbconfig.php";

} else {
  header("Location: index.php");
}
?>

<!-- Welcome Logo Section -->
<?php
  $departmentCode = $_SESSION['department_code'] ?? 'default';
  if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome']):
?>
    <div id="welcome-overlay">
      <div class="logo-container">
        <img src="assets/images/client/department/<?= htmlspecialchars($departmentCode) ?>.png" alt="<?= htmlspecialchars($departmentCode) ?> Logo">
      </div>
      <div class="color-fade-bg"></div>
    </div>
  <script>
  setTimeout(() => {
    const overlay = document.getElementById('welcome-overlay');
    if (overlay) overlay.remove();
  }, 3500); // after full animation
</script>
<?php
  unset($_SESSION['show_welcome']);
endif;
?>


<!-- Post Fetching Section -->
<?php
$posts = [];
$officialStudentPosts = [];
$officialFacultyPosts = [];

$sql = "SELECT * FROM posts ORDER BY COALESCE(edited_at, create_date) DESC";
$result = $conn->query($sql);

while ($post = $result->fetch_assoc()){
  $authorId = $post['author_id'];

  if (strpos($authorId, 'STU-') === 0){
    $stmt = $conn->prepare("SELECT full_name, department FROM student_users WHERE student_id = ?");
  } else if (strpos($authorId, 'FAC-') === 0){
    $stmt = $conn->prepare("SELECT full_name, department FROM faculty_users WHERE faculty_id = ?");
  } else {
    $post['authorName'] = 'Unknown';
    $post['authorRole'] = 'Unknown';
    $post['authorDept'] = 'Unknown';
    continue;
  }

  $stmt->bind_param("s", $authorId);
  $stmt->execute();
  $stmt->bind_result($authorName, $authorDept);
  $stmt->fetch();
  $stmt->close();

  $post['authorName'] = $authorName;
  $post['authorRole'] = strpos($authorId, 'STU-') === 0 ? 'Student' : 'Faculty';
  $post['authorDept'] = $authorDept;

  if ($post['post_type'] === 'official'){
      if ($post['authorRole'] === 'Faculty'){
        $officialFacultyPosts[] = $post;
      } else if ($post['authorRole'] === 'Student'){
        $officialStudentPosts[] = $post;
      }
  }
}

// PUSH NOTIF

$stmt = $conn->prepare("SELECT post_id, title, create_date, edited_at, toast_status, toast_message FROM posts WHERE toast_status = 1 AND author_id != ?");
$stmt->bind_param("s", $currentUser);
$stmt->execute();
$result = $stmt->get_result();

$toastPosts = [];
while ($row = $result->fetch_assoc()) {
  $toastPosts[] = $row;
}

// PUSH NOTIF
?>
<!-- Post Fetching Section -->

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>U-Plug</title>
  <link rel="stylesheet" href="assets/css/home.css">
  <link rel="stylesheet" href="assets/css/auth.css">
  <link rel="icon" href="assets/images/client/UplugLogo.png" type="image/png">
  <script src="/assets/javascript/toast-notif.js" defer></script>
</head>

<body>

<div id="toastContainer" class="toast-container">
  <?php foreach ($toastPosts as $post): ?>
    <div class="toast" data-post-id="<?= $post['post_id'] ?>">
      <span><?= htmlspecialchars($post['toast_message']) ?></span><br>
      <small style="opacity: 0.8;">
        <?= empty($post['edited_at'])
          ? date("F j, Y - h:i A", strtotime($post['create_date']))
          : "Edited at: " . date("F j, Y - h:i A", strtotime($post['edited_at'])) ?>
      </small>
      <button class="dismiss-toast">X</button>
    </div>
  <?php endforeach; ?>
</div>

  <nav class="navbar">
    <div class="nav-left">
      <div class="logo">U-Plug</div>
      <div class="nav-links">
        <a href="home.php" class="active">Home</a>
        <a href="news.php">News</a>
        <a href="map.php">Map</a>
        <a href="messaging.php">Messages</a>
        <a href="profile.php">Profile</a>
        <a href="assets/server/logout-process.php">Logout</a>
      </div>
    </div>
    <div class="nav-right">
      <div class="search-wrapper">
        <input type="text" id="searchInput" placeholder="Search profiles by name..." autocomplete="off">
        <div id="searchResults"></div>
      </div>
    </div>
  </nav>
  <div class="container">
    <main class="main-content">
      <section class="news-feed">
        <h2>News Feed</h2>
        <div class="tabs">
          <button class="tab active">Faculty News</button>
          <button class="tab">Student News</button>
        </div>
        <div id="officialFaculty" class="news-items">

          <?php foreach ($officialFacultyPosts as $post): ?>
              <div class="news-item" data-post-id="<?= $post['post_id'] ?>">
            <img src="https://via.placeholder.com/60" alt="placeholder">
            <div>
              <h3><?= htmlspecialchars($post['authorName']) . " - " . htmlspecialchars($post['authorDept'])?></h3>
              <h3><?= htmlspecialchars($post['title']) ?></h3>
              <p><?= htmlspecialchars($post['content']) ?></p>
              <small title="Originally posted: <?= date("F j, Y - h:i A", strtotime($post['create_date']))?>">
                <?= (empty($post['edited_at']))
                ? date("F j, Y - h:i A", strtotime($post['create_date']))
                : "Edited at: " . date("F j, Y - h:i A", strtotime($post['edited_at']))?>
              </small>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div id="officialStudent" class="news-items hidden">

          <?php foreach ($officialStudentPosts as $post): ?>
              <div class="news-item" data-post-id="<?= $post['post_id'] ?>">
            <img src="https://via.placeholder.com/60" alt="placeholder">
            <div>
              <h3><?= htmlspecialchars($post['authorName']) . " - " . htmlspecialchars($post['authorDept'])?></h3>
              <h3><?= htmlspecialchars($post['title']) ?></h3>
              <p><?= htmlspecialchars($post['content']) ?></p>
              <small title="Originally posted: <?= date("F j, Y - h:i A", strtotime($post['create_date']))?>">
                <?= (empty($post['edited_at']))
                ? date("F j, Y - h:i A", strtotime($post['create_date']))
                : "Edited at: " . date("F j, Y - h:i A", strtotime($post['edited_at']))?>
              </small>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        
      </section>

      <aside class="right-panel">
        <div class="map-section">
          <h2>Campus Map</h2>
          <input type="text" placeholder="Find a building...">
          <div class="map-placeholder">[Map Here]</div>
        </div>

        <div class="messaging-section">
          <h2>Messaging</h2>
          <div class="chat">
            <p><strong>Mark Bandong</strong> <span>Student</span></p>
            <div class="message from-user">Are you free for a gloup study session fomomow?</div>
            <div class="message from-other">Yes. I am! What time and where?</div>
            <div class="message from-user">How about the library at 10 AM?</div>
          </div>
        </div>

        <div class="profile-section">
          <h2>Profile</h2>
          <div class="profile-card">
            <img src="https://via.placeholder.com/80" alt="Profile">
            <h3>Kent Barbiran</h3>
            <nav class="profile-tabs">
              <a href="#">Overview</a>
              <a href="#">Courses</a>
              <a href="#">Settings</a>
            </nav>
            <p><strong>Bio:</strong> Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
            <p><strong>Interests:</strong> Hiking, painting, reading</p>
            <p><strong>Contact Info:</strong> -</p>
        </div>
      </aside>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const tabs = document.querySelectorAll('.tab');
      tabs[0].addEventListener('click', () => showSection('officialFaculty'));
      tabs[1].addEventListener('click', () => showSection('officialStudent'));
    })


    function showSection(section) {
      document.getElementById('officialFaculty').classList.add('hidden');
      document.getElementById('officialStudent').classList.add('hidden');
      document.getElementById(section).classList.remove('hidden');
       // Tab highlight
      document.querySelectorAll('.tab').forEach(btn => btn.classList.remove('active'));
      if(section === 'officialFaculty') {
        document.querySelectorAll('.tab')[0].classList.add('active');
      } else {
        document.querySelectorAll('.tab')[1].classList.add('active');
      }
    }

  </script>

  <!-- SEARCH PROFILE -->

    <script>
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');

    searchInput.addEventListener('input', function () {
      const query = this.value.trim();

      if (query.length === 0) {
        searchResults.style.display = 'none';
        searchResults.innerHTML = '';
        return;
      }

      fetch('/assets/server/search-profile.php?q=' + encodeURIComponent(query))
        .then(res => res.text())
        .then(html => {
          searchResults.innerHTML = html;
          searchResults.style.display = 'block';
        });
    });
    </script>

    <script>
    function viewProfile(userId) {
      window.location.href = '/assets/server/view-profile.php?user_id=' + encodeURIComponent(userId);
    }
    </script>

    <!-- SEARCH PROFILE -->

<div id="toastContainer" class="toast-container"></div>

</body>
</html>



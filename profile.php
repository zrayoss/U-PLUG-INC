<?php
session_start();

require __DIR__ . "/assets/config/dbconfig.php";

$session_id = $_SESSION['user_id'] ?? null;

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

if (strpos($session_id, 'FAC-') === 0) {
  $sql = "SELECT * FROM faculty_users WHERE faculty_id = '$session_id'";
  $result = $conn->query($sql);
  $user = $result->fetch_assoc();
  $department_code = $user['department'];
  $role = 'Faculty';
  $pfpDir = 'faculty-profiles';
} else if (strpos($session_id, 'STU-') === 0) {
  $sql = "SELECT * FROM student_users WHERE student_id = '$session_id'";
  $result = $conn->query($sql);
  $user = $result->fetch_assoc();
  $department_code = $user['department'];
  $role = 'Student';
  $pfpDir = 'student-profiles';
}
?>

<!-- Post Fetching Section -->
<?php
$posts = [];
$personalPosts = [];

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

    if ($post['post_type'] === 'personal'){
      $post['post_type'] = 'Personal';
    } else if ($post['post_type'] === 'official'){
      $post['post_type'] = 'Official';
    } else if ($post['post_type'] === 'department'){
      $post['post_type'] = 'Department';
    } else {
      $post['post_type'] = 'Unknown';
    }

  if ($post['author_id'] === $session_id){
      $personalPosts[] = $post;
  }
}
?>
<!-- Post Fetching Section -->

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile</title>
  <link rel="stylesheet" href="/assets/css/profile.css">
  <link rel="icon" href="/assets/images/client/UplugLogo.png" type="image/png">
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



<div id="newPostModal" class="modal">
  <div class="modal-content">
    <button class="close-btn" type="button" onclick="closeModal()">✕</button>
    <h3>Create New Post</h3>
    <form method="POST" action="/assets/server/create-post.php" autocomplete="off">
      <input type="hidden" name="post_type" value="personal">

      <label for="post_title">Title:</label>
      <textarea id="title" name="title" rows="1" required></textarea>

      <label for="post_content">Content:</label>
      <textarea id="content" name="content" rows="4" required></textarea>

      <button type="submit" name="submit_post">Post</button>
    </form>
  </div>
</div>

  <nav class="navbar">
    <div class="nav-left">
      <div class="logo">U-Plug</div>
      <div class="nav-links">
        <a href="/home.php">Home</a>
        <a href="/news.php">News</a>
        <a href="/map.php">Map</a>
        <a href="/messaging.php">Messages</a>
        <a href="/profile.php" class="active">Profile</a>
        <a href="/assets/server/logout-process.php">Logout</a>
      </div>
    </div>
    <div class="nav-right">
      <div class="search-wrapper">
        <input type="text" id="searchInput" placeholder="Search by name..." autocomplete="off">
        <div id="searchResults"></div>
      </div>
    </div>
  </nav>
  
  <main class="dashboard">
    <!-- Profile Sidebar -->
    <aside class="profile-sidebar">
      <?php
      $pfpPath = !empty($user['profile_picture']) ? '/' . htmlspecialchars($user['profile_picture']) : '/images/default.png';
      ?>

      <div class="profile-pic-container">
        <img src="/<?= htmlspecialchars($user['profile_picture']) . '?v=' . time() ?>" class="profile-pic">
      </div>

      <h2>Profile</h2>
      <p><strong>Account Number:</strong><br> <?= htmlspecialchars($session_id) ?></p>
      <p><strong>Name:</strong><br> <?= htmlspecialchars($user['full_name']) ?></p>
      <p><strong>Department:</strong><br> <?= htmlspecialchars($department_code) ?></p>
      <p><strong>Role:</strong><br> <?= htmlspecialchars($role) ?></p>

      <label for="profile_pic">📷 Upload Profile Photo:</label>
      <form method="POST" enctype="multipart/form-data" action="/assets/server/upload-profile.php">
        <input type="file" name="profile_picture" accept="image/*" required>
        <input type="hidden" name="user_id" value="<?= $session_id ?>">
        <input type="hidden" name="department_code" value="<?= $department_code ?>">
        <input type="hidden" name="pfp_folder" value="<?= $pfpDir ?>">
        <input type="hidden" name="role" value="<?= $role ?>">
        <button type="submit">Upload</button>
      </form>

      <div class="profile-image-preview">
        <img id="preview-img" src="#" alt="Profile Preview" style="display: none;">
      </div>
  </aside>

  <!-- Feed Section -->
  <section class="feed-content">
    <div class="newsfeed">
      <div class="new-post-button">
        <button onclick="openModal()">➕ New Post</button>
      </div>
      
      <h2>Your Posts</h2>

      <?php if (isset($error_message)): ?>
        <p style="color:red;"><?= $error_message ?></p>
      <?php endif; ?>

    <?php foreach ($personalPosts as $post): ?>
        <div class="post-card" data-post-id="<?= $post['post_id'] ?>">
        <div class="post" data-post-id="<?= htmlspecialchars($post['post_id']) ?>">
          <?php if ($post['author_id'] === $_SESSION['user_id']): ?>
            <div class="post-actions">
              <button type="button"
                      class="edit-btn"
                      data-post-id="<?= htmlspecialchars($post['post_id']) ?>"
                      data-title="<?= htmlspecialchars($post['title'], ENT_QUOTES) ?>"
                      data-content="<?= htmlspecialchars($post['content'], ENT_QUOTES) ?>">
                Edit
              </button>

              <!-- open floating delete confirm modal -->
              <button type="button"
                      class="delete-btn danger"
                      data-post-id="<?= htmlspecialchars($post['post_id']) ?>">
                Delete
              </button>
            </div>
          <?php endif; ?>

          <p><strong>POST TYPE:</strong> <?= htmlspecialchars($post['post_type']) ?></p>
          <p><strong>TITLE:</strong> <?= htmlspecialchars($post['title'])?></p>
          <p><?= htmlspecialchars($post['content'])?></p>
          <small title="Originally posted: <?= date("F j, Y - h:i A", strtotime($post['create_date']))?>">
            <?= (empty($post['edited_at']))
            ? date("F j, Y - h:i A", strtotime($post['create_date']))
            : "Edited at: " . date("F j, Y - h:i A", strtotime($post['edited_at']))?>
          </small>
        </div>
      <?php endforeach; ?>

    <footer class="footer-tag">
      <p>Logged in as <?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($_SESSION['user_id']) ?>)</p>
    </footer>
  </section>
</main>

<!---FOR JS REMOVE NALANG IF UNUSED--->

  <script>
    // Show/hide fields based on account type
    const modal = document.getElementById("newPostModal");
    const titleField = document.getElementById("post_title");
    const contentField = document.getElementById("post_content");

    function openModal() {
      modal.classList.add("show");
      document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
      modal.classList.remove("show");
      document.body.style.overflow = 'auto';
    }

    window.addEventListener('click', function(event) {
      if (event.target === modal) closeModal();
    });

    function autoExpand(field) {
      if (!field) return;
      field.style.height = 'auto';
      field.style.height = field.scrollHeight + 'px';
    }

    window.addEventListener('DOMContentLoaded', () => {
      autoExpand(titleField);
      autoExpand(contentField);
    });
  </script>

  <!-- EDIT MODAL (identical structure to news.php) -->
  <div id="editPostModal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <button type="button" class="close-btn" aria-label="Close" onclick="closeEditModal()">✕</button>
      <h3>Edit Post</h3>
      <form id="editPostForm" method="POST" action="assets/server/edit-post.php" autocomplete="off" class="post-form">
        <input type="hidden" name="post_id" id="edit_post_id">
        <input type="hidden" name="origin" value="profile">
        <input type="hidden" name="post_type" value="<?= $post['post_type'] ?>">
        
        <label for="edit_title">Title:</label>
        <textarea id="edit_title" name="title" rows="1" required></textarea>

        <label for="edit_content">Content:</label>
        <textarea id="edit_content" name="content" rows="4" required></textarea>

        <button type="submit" name="save_edit" class="create-post-btn">Save</button>
        <button type="button" class="cancel-btn" onclick="closeEditModal()">Cancel</button>
      </form>
    </div>
  </div>

  <script>
    // Edit modal elements
    const editModal = document.getElementById("editPostModal");
    const editIdField = document.getElementById("edit_post_id");
    const editTitleField = document.getElementById("edit_title");
    const editContentField = document.getElementById("edit_content");

    function openEditModal(postId, title, content) {
      editIdField.value = postId || '';
      editTitleField.value = title || '';
      editContentField.value = content || '';
      // auto-expand textareas if you have autoExpand helper
      try { editTitleField.style.height = 'auto'; editTitleField.style.height = editTitleField.scrollHeight + 'px'; } catch(e){}
      try { editContentField.style.height = 'auto'; editContentField.style.height = editContentField.scrollHeight + 'px'; } catch(e){}
      editModal.classList.add("show");
      document.body.style.overflow = 'hidden';
    }
    function closeEditModal() {
      editModal.classList.remove("show");
      document.body.style.overflow = 'auto';
    }

    // delegation: open edit modal when edit button clicked
    document.addEventListener("click", function(e) {
      const btn = e.target.closest('.edit-btn');
      if (!btn) return;
      openEditModal(btn.dataset.postId, btn.dataset.title || '', btn.dataset.content || '');
    });
    // close on backdrop click / ESC
    document.addEventListener('click', (ev) => {
      if (editModal && editModal.classList.contains('show') && ev.target === editModal) closeEditModal();
    });
    document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') closeEditModal(); });
  </script>

    <!-- add this once near the other modals (after the edit modal or before the closing </body>) -->
  <div id="deleteConfirmModal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <button type="button" class="close-btn" aria-label="Close" onclick="closeDeleteModal()">✕</button>
      <h3>Confirm Delete</h3>
      <p>Are you sure you want to delete this post?</p>
      <form id="deleteForm" method="POST" action="assets/server/delete-post.php">
        <input type="hidden" name="redirect_to" value="profile">
        <input type="hidden" name="post_id" id="delete_post_id">
        <button type="submit" class="confirm-btn">Confirm</button>
        <button type="button" class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
      </form>
    </div>

    <script>
      (function () {
        const deleteModal = document.getElementById('deleteConfirmModal');
        const deleteIdField = document.getElementById('delete_post_id');
        const deleteForm = document.getElementById('deleteForm');
      
        if (!deleteModal || !deleteIdField || !deleteForm) return;
      
        function openDeleteModal(postId) {
          deleteIdField.value = postId || '';
          deleteModal.classList.add('show');
          document.body.style.overflow = 'hidden';
          // focus first actionable control
          const confirmBtn = deleteModal.querySelector('.confirm-btn');
          if (confirmBtn) confirmBtn.focus();
        }
      
        function closeDeleteModal() {
          deleteModal.classList.remove('show');
          document.body.style.overflow = 'auto';
          deleteIdField.value = '';
        }
      
        // Open when any .delete-btn clicked (delegation)
        document.addEventListener('click', function (e) {
          const btn = e.target.closest('.delete-btn');
          if (btn) {
            // if button is inside the modal (Confirm/Cancel), ignore here
            if (deleteModal.contains(btn)) return;
            openDeleteModal(btn.dataset.postId || '');
          }
        });
      
        // Close when clicking the modal backdrop
        deleteModal.addEventListener('click', function (ev) {
          if (ev.target === deleteModal) closeDeleteModal();
        });
      
        // Close when pressing ESC
        document.addEventListener('keydown', function (ev) {
          if (ev.key === 'Escape' && deleteModal.classList.contains('show')) closeDeleteModal();
        });
      
        // Close when clicking cancel inside the modal or the modal's close button
        const modalCancel = deleteModal.querySelector('.cancel-btn');
        if (modalCancel) modalCancel.addEventListener('click', closeDeleteModal);
        const modalClose = deleteModal.querySelector('.close-btn');
        if (modalClose) modalClose.addEventListener('click', closeDeleteModal);
      
        // Optional: prevent accidental form submit on Enter in modal fields
        deleteModal.addEventListener('keydown', function (ev) {
          if (ev.key === 'Enter' && ev.target.tagName !== 'TEXTAREA' && !ev.target.classList.contains('confirm-btn')) {
            ev.preventDefault();
          }
        });
      })();

      document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.dismiss-toast').forEach(button => {
          button.addEventListener('click', () => {
            const toast = button.closest('.toast');
            const postId = toast.dataset.postId;
          
            fetch('assets/server/dismiss-toast.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: `post_id=${encodeURIComponent(postId)}`
            }).then(() => {
              toast.remove(); // ✅ Remove toast from UI
            });
          });
        });
      
        // Optional: allow clicking the toast itself to dismiss
        document.querySelectorAll('.toast').forEach(toast => {
          toast.addEventListener('click', e => {
            if (!e.target.classList.contains('dismiss-toast')) {
              const postId = toast.dataset.postId;
            
              fetch('assets/server/dismiss-toast.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${encodeURIComponent(postId)}`
              }).then(() => {
                toast.remove();
              });
            }
          });
        });
      });
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
</div>

<div id="toastContainer" class="toast-container"></div>
</body>
</html>

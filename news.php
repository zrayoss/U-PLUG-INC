<?php

session_start();

if (isset($_SESSION['user_id'])){
    
  require __DIR__ . "/assets/config/dbconfig.php";

  $session_id = $_SESSION['user_id'];

  if (strpos($session_id, 'FAC-') === 0){

    $sql = "SELECT * FROM faculty_users WHERE faculty_id = '$session_id'";

    $result = $conn->query($sql);

    $user = $result->fetch_assoc();
//post// //learn today//
    
    $posts = [];
    $studentPosts = [];
    $facultyPosts = [];
    $officialPosts = [];

    $sql = "SELECT * FROM posts ORDER BY COALESCE(edited_at, create_date) DESC";
    $result = $conn->query($sql);

    while ($post = $result->fetch_assoc()) {
      $authorId = $post['author_id'];

      if (strpos($authorId, 'STU-') === 0){
        $stmt = $conn->prepare("SELECT full_name, department FROM student_users WHERE student_id = ?");
      } else if (strpos($authorId, 'FAC-') === 0){
        $stmt = $conn->prepare("SELECT full_name, department FROM faculty_users WHERE faculty_id = ?");
      } else {
        $post['authorName'] = 'Unknown';
        $post['authorRole'] = 'Unknown';
        $post['authorDept'] = 'Unknown';
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
        $officialPosts[] = $post;
      } else if ($post['post_type'] === 'department' && $post['authorDept'] === $user['department']) {
        if ($post['authorRole'] === 'student'){
          $studentPosts[] = $post;
        } else {
          $facultyPosts[] = $post;
        }
      }
    }

//post// //learn today//
  } else if (strpos($session_id, 'STU-') === 0){

    $sql = "SELECT * FROM student_users WHERE student_id = '$session_id'";

    $result = $conn->query($sql);

    $user = $result->fetch_assoc();
//post// //learn today//
    
    $posts = [];
    $studentPosts = [];
    $facultyPosts = [];
    $officialPosts = [];

    $sql = "SELECT * FROM posts ORDER BY COALESCE(edited_at, create_date) DESC";
    $result = $conn->query($sql);

    while ($post = $result->fetch_assoc()) {
      $authorId = $post['author_id'];

      if (strpos($authorId, 'STU-') === 0){
        $stmt = $conn->prepare("SELECT full_name, department FROM student_users WHERE student_id = ?");
      } else if (strpos($authorId, 'FAC-') === 0){
        $stmt = $conn->prepare("SELECT full_name, department FROM faculty_users WHERE faculty_id = ?");
      } else {
        $post['authorName'] = 'Unknown';
        $post['authorRole'] = 'Unknown';
        $post['authorDept'] = 'Unknown';
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
        $officialPosts[] = $post;
      } else if ($post['post_type'] === 'department' && $post['authorDept'] === $user['department']) {
        if ($post['authorRole'] === 'student'){
          $studentPosts[] = $post;
        } else {
          $facultyPosts[] = $post;
        }
      }
    }

//post// //learn today//
  }

} else {
  header("Location: index.php");
}

//tab

$activeTab = $_GET['tab'] ?? 'official';

//tab
if (isset($_GET['edit'])) {
  $editId = $_GET['edit'];
  foreach (array_merge($studentPosts, $facultyPosts) as $post) {
    if ($post['post_id'] == $editId && $post['author_id'] === $_SESSION['user_id']) {
      $activeTab = 'department';
      break;
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

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>U-Plug News</title>
  <link rel="stylesheet" href="assets/css/news.css">
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

<!-- Floating tab for posts -->
 <div id="newPostModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <h3 id="modalTitle">Create New Post</h3>
    <form method="POST" action="/assets/server/create-post.php" autocomplete="off" class="post-form">
      <label for="wallSelect">Choose Wall to Post On:</label>
      <select id="wallSelect" name="post_type" required>
        <option value="official">📢 Official News</option>
        <option value="department">🏛️ <?= htmlspecialchars($user['department']) ?> News</option>
      </select>

      <label for="post_title">Title:</label>
      <textarea id="title" name="title" rows="1" required></textarea>

      <label for="post_content">Content:</label>
      <textarea id="content" name="content" rows="4" required></textarea>

      <button type="submit" name="submit_post">Post</button>
    </form>
  </div>
</div>

<!-- EDIT MODAL: matches the create modal visually and behavior -->
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

<!-- DELETE MODAL: Always present, controlled by JS -->
<div id="deleteConfirmModal" class="modal" aria-hidden="true">
  <div class="modal-content">
    <button type="button" class="close-btn" aria-label="Close" onclick="closeDeleteModal()">✕</button>
    <h3>Confirm Delete</h3>
    <p>Are you sure you want to delete this post?</p>
    <form id="deleteForm" method="POST" action="assets/server/delete-post.php">
      <input type="hidden" name="redirect_to" value="news">
      <input type="hidden" name="post_id" id="delete_post_id">
      <button type="submit" class="confirm-btn">Confirm</button>
      <button type="button" class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
    </form>
  </div>
</div>

<nav class="navbar">
    <div class="nav-left">
      <div class="logo">U-Plug</div>
      <div class="nav-links">
        <a href="/home.php">Home</a>
        <a href="/news.php" class="active">News</a>
        <a href="/map.php">Map</a>
        <a href="/messaging.php">Messages</a>
        <a href="/profile.php">Profile</a>
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

  <main>
    <section class="news-feed">
      <h2>📰 News</h2>
      <div class="tabs">
        <button class="tab" onclick="openModal()">➕ Create Post</button>
        <a href="news.php?tab=official" class="tab <?= $activeTab === 'official' ? 'active' : '' ?>">Official News</a>
        <a href="news.php?tab=department" class="tab <?= $activeTab === 'department' ? 'active' : '' ?>"><?= htmlspecialchars($user['department']) ?> News</a>
      </div>
      <div id="official" class="news-section <?= $activeTab === 'official' ? '' : 'hidden' ?>">
      <?php foreach ($officialPosts as $post): ?>
        <div class="news-card" data-post-id="<?= $post['post_id'] ?>">
          <?php if ($post['author_id'] === $_SESSION['user_id']): ?>
            <div class="post-actions">
              <!-- Edit opens modal, prefilled -->
              <button type="button"
                      class="edit-btn"
                      data-post-id="<?= $post['post_id'] ?>"
                      data-tab="official"
                      data-title="<?= htmlspecialchars($post['title'], ENT_QUOTES) ?>"
                      data-content="<?= htmlspecialchars($post['content'], ENT_QUOTES) ?>">
                Edit
              </button>

              <!-- Delete button: Simple button, triggers JS modal -->
              <button type="button"
                      class="delete-btn danger"
                      data-post-id="<?= $post['post_id'] ?>">
                Delete
              </button>
            </div>
          <?php endif; ?>

          <!-- Post content -->
          <strong><?= htmlspecialchars($post['title']) ?></strong><br>
          <p><?= htmlspecialchars($post['content']) ?></p>
          <em><?= htmlspecialchars($post['authorName']) ?> (<?= htmlspecialchars($post['authorRole']) ?><?= isset($post['author_department']) ? ' - ' . htmlspecialchars($post['author_department']) : '' ?>)</em><br>
          <em title="Originally posted: <?= date("F j, Y - h:i A", strtotime($post['create_date']))?>">
            <?= (empty($post['edited_at'])) ? date("F j, Y - h:i A", strtotime($post['create_date'])) : "Edited at: " . date("F j, Y - h:i A", strtotime($post['edited_at']))?>
          </em>
        </div>
      <?php endforeach; ?>
      </div>

      <div id="department" class="news-section <?= $activeTab === 'department' ? '' : 'hidden' ?>">
        <?php foreach (array_merge($studentPosts, $facultyPosts) as $post): ?>
          <div class="news-card" data-post-id="<?= $post['post_id'] ?>">
            <!-- action buttons (same placement as official posts) -->
            <?php if ($post['author_id'] === $_SESSION['user_id']): ?>
              <div class="post-actions">
                <button type="button"
                        class="edit-btn"
                        data-post-id="<?= $post['post_id'] ?>"
                        data-tab="department"
                        data-title="<?= htmlspecialchars($post['title'], ENT_QUOTES) ?>"
                        data-content="<?= htmlspecialchars($post['content'], ENT_QUOTES) ?>">
                  Edit
                </button>

                <!-- Delete button: Simple button, triggers JS modal -->
                <button type="button"
                        class="delete-btn danger"
                        data-post-id="<?= $post['post_id'] ?>">
                  Delete
                </button>
              </div>
            <?php endif; ?>

            <!-- Post content -->
            <strong><?= htmlspecialchars($post['title']) ?></strong><br>
            <p><?= htmlspecialchars($post['content']) ?></p>
            <em><?= htmlspecialchars($post['authorName']) ?> (<?= htmlspecialchars($post['authorRole']) ?>)</em><br>
            <em title="Originally posted: <?= date("F j, Y - h:i A", strtotime($post['create_date']))?>">
              <?= (empty($post['edited_at'])) ? date("F j, Y - h:i A", strtotime($post['create_date'])) : "Edited at: " . date("F j, Y - h:i A", strtotime($post['edited_at']))?>
            </em>
           </div>
         <?php endforeach; ?>
       </div>
    </section>
  </main>

  <!---FOR JS--->

  <script>
    const modal = document.getElementById("newPostModal");
    const titleField = document.getElementById("post_title");
    const contentField = document.getElementById("post_content");
    // edit modal elements
    const editModal = document.getElementById("editPostModal");
    const editIdField = document.getElementById("edit_post_id");
    const editTitleField = document.getElementById("edit_title");
    const editContentField = document.getElementById("edit_content");;

    function showSection(section) {
      document.getElementById('official').classList.add('hidden');
      document.getElementById('department').classList.add('hidden');
      document.getElementById(section).classList.remove('hidden');
      // Tab highlight
      document.querySelectorAll('.tab').forEach(btn => btn.classList.remove('active'));
      if(section === 'official') {
        document.querySelectorAll('.tab')[0].classList.add('active');
      } else {
        document.querySelectorAll('.tab')[1].classList.add('active');
      }
    }

    // Open Post Tab
    function openModal() {
      modal.classList.add("show");
      document.body.style.overflow = "hidden";
    }
    // Close Post Tab
    function closeModal() {
      modal.classList.remove("show");
      document.body.style.overflow = "auto";
    }

    // Edit modal controls
    function openEditModal(postId, title, content) {
      editIdField.value = postId || '';
      editTitleField.value = title || '';
      editContentField.value = content || '';
      try { editTitleField.style.height = 'auto'; editTitleField.style.height = editTitleField.scrollHeight + 'px'; } catch(e){}
      try { editContentField.style.height = 'auto'; editContentField.style.height = editContentField.scrollHeight + 'px'; } catch(e){}
      editModal.classList.add("show");
      document.body.style.overflow = 'hidden';
    }

    document.addEventListener("click", function(e) {
      const btn = e.target.closest('.edit-btn');
      if (!btn) return;
      openEditModal(btn.dataset.postId, btn.dataset.title || '', btn.dataset.content || '');
    });
    document.addEventListener('click', (ev) => {
      if (editModal && editModal.classList.contains('show') && ev.target === editModal) closeEditModal();
    });
    document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') closeEditModal(); });

    function closeEditModal() {
      editModal.classList.remove("show");
      document.body.style.overflow = "auto";
      // clear fields optionally
      // editForm.reset();
    }

    // wire up all edit buttons (delegation-friendly)
    document.addEventListener("click", function(e) {
      const btn = e.target.closest('.edit-btn');
      if (btn) {
        const pid = btn.dataset.postId;
        const title = btn.dataset.title || '';
        const content = btn.dataset.content || '';
        const tab = btn.dataset.tab || 'department';
        openEditModal(pid, title, content, tab);
      }
    });

    // Escape closes any open modal
    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        if (modal.classList.contains("show")) closeModal();
        if (editModal.classList.contains("show")) closeEditModal();
      }
    });

    // DELETE MODAL SCRIPT: Handles open/close for delete modal
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
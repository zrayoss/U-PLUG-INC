<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>U-Plug Admin Dashboard</title>
  <link rel="stylesheet" href="assets/css/admin-dashboard.css">
</head>
<body>

  <div class="layout" id="layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="logo">Administrator</div>
        <ul class="nav">
          <li><a href="admin.php" class="nav-link">Dashboard</a></li>
          <li class="divider">User Settings</li>
          <li><a href="faculty.php" class="nav-link">Faculty Users</a></li>
          <li><a href="student.php" class="nav-link">Student Users</a></li>
          <li><a href="posts.php" class="nav-link">Posts</a></li>
          <li class="divider">Settings</li>
          <li><a href="#" class="nav-link">About</a></li>
          <li><a href="assets/server/logout-process.php" class="nav-link">Logout</a></li>
        </ul>
    </aside>
    <!-- Burger Button -->
    <button id="burger" class="burger">&#9776;</button>

    <!-- Main Content -->
    <main class="main-content" id="main">
      <section class="summary-cards">
        <div class="card">
          <h3>Posts Today</h3>
          <p id="post-count">0</p>
        </div>
        <div class="card">
          <h3>New Users</h3>
          <p id="user-count">0</p>
        </div>
      </section>

      <!-- Recent Posts -->
      <section class="panel" id="recent-posts-panel">
        <div class="panel-header">
          <h2>Recent Posts</h2>
          <button class="expand-btn" data-target="recent-posts">＋</button>
        </div>
        <ul class="list collapsible" id="recent-posts"></ul>
      </section>

      <!-- New Users -->
      <section class="panel" id="new-users-panel">
        <div class="panel-header">
          <h2>New Users</h2>
          <button class="expand-btn" data-target="new-users">＋</button>
        </div>
        <ul class="list collapsible" id="new-users"></ul>
      </section>

      <div class="footer">
        U-Plug ©2025. All rights reserved.
      </div>
    </main>
  </div>

  <!-- Edit Modal -->
  <div id="edit-modal" class="modal">
    <div class="modal-content large">
      <span class="close-btn">&times;</span>
      <h3>Edit Post</h3>
      <label for="edit-author">Author:</label>
      <input type="text" id="edit-author" placeholder="Author name..." />
      <label for="edit-content">Content:</label>
      <textarea id="edit-content" placeholder="Post content..." rows="4"></textarea>
      <label for="edit-date">Date:</label>
      <input type="text" id="edit-date" placeholder="YYYY-MM-DD HH:MM" />
      <button id="save-edit">Save</button>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="delete-modal" class="modal">
    <div class="modal-content small">
      <h3>Confirm Deletion</h3>
      <p>Are you sure you want to delete this entry?</p>
      <div class="modal-actions">
        <button id="confirm-delete">Yes</button>
        <button id="cancel-delete">No</button>
      </div>
    </div>
  </div>

  <!-- View User Modal -->
  <div id="view-user-modal" class="modal">
    <div class="modal-content large">
      <span class="close-btn">&times;</span>
      <h3>User Details</h3>
      <p><strong>Profile Name:</strong> <span id="view-user-name">—</span></p>
      <p><strong>Email:</strong> <span id="view-user-email">—</span></p>
      <p><strong>Joined:</strong> <span id="view-user-date">—</span></p>
      <h4>Recent Posts</h4>
      <ul id="user-recent-posts">
        <li>Placeholder Post 1: This is a sample post content...</li>
        <li>Placeholder Post 2: Another example of user-generated content...</li>
        <li>Placeholder Post 3: More placeholder text for demonstration...</li>
      </ul>
    </div>
  </div>

  <script>
document.addEventListener('DOMContentLoaded', function () {
  const burger = document.getElementById('burger');
  const sidebar = document.getElementById('sidebar');

  // ✅ Highlight active nav
  const currentPage = window.location.pathname.split("/").pop() || "admin.html";
  document.querySelectorAll(".nav-link").forEach(link => {
    link.classList.toggle("active", link.getAttribute("href") === currentPage);
  });

  // ✅ Burger toggle
  burger.addEventListener('click', () => sidebar.classList.toggle('hidden'));

  // ✅ Hide sidebar on outside click
  document.addEventListener('click', (e) => {
    if (!sidebar.contains(e.target) && !burger.contains(e.target)) {
      sidebar.classList.add('hidden');
    }
  });

  // === Expand Buttons (+ / − and dropdown effect) ===
  document.querySelectorAll('.expand-btn').forEach(button => {
    button.addEventListener('click', () => {
      const targetId = button.getAttribute('data-target');
      const panel = document.getElementById(`${targetId}-panel`);
      panel.classList.toggle('expanded');
      button.classList.toggle('expanded');
      button.textContent = button.classList.contains('expanded') ? '−' : '＋';
    });
  });

  // === Sample Data ===
  const recentPosts = [
    { dep: "Computer Science", author: "Isabella Christensen", title: "Introduction to AI", content: "Lorem ipsum dolor sit amet...", created_at: "2025-10-22 12:56" },
    { dep: "Engineering", author: "Audry Ford", title: "Sustainable Energy Solutions", content: "Consectetur adipiscing elit...", created_at: "2025-10-22 12:50" },
    { dep: "Business", author: "Kara Obrien", title: "Market Trends 2025", content: "Sed do eiusmod tempor incididunt...", created_at: "2025-10-22 12:45" }
  ];

  const newUsers = [
    { name: "Jane Doe", email: "jane@email.com", joined_at: "2025-10-22" },
    { name: "John Smith", email: "john@email.com", joined_at: "2025-10-22" },
    { name: "Emily Cruz", email: "emily@email.com", joined_at: "2025-10-22" }
  ];

  document.getElementById("post-count").textContent = recentPosts.length;
  document.getElementById("user-count").textContent = newUsers.length;

  const postList = document.getElementById("recent-posts");
  recentPosts.forEach(post => {
    const li = document.createElement("li");
    li.innerHTML = `
      <div>|${post.dep}| ${post.author} | ${post.title} | ${post.content.substring(0, 50)}... | ${post.created_at}</div>
      <div class="actions">
        <button class="edit-btn">View</button>
        <button class="delete-btn">Delete</button>
      </div>`;
    postList.appendChild(li);
  });

  const userList = document.getElementById("new-users");
  newUsers.forEach(user => {
    const li = document.createElement("li");
    li.innerHTML = `
      <div>${user.name} | ${user.email} | ${user.joined_at}</div>
      <div class="actions">
        <button class="edit-btn">View</button>
        <button class="delete-btn">Delete</button>
      </div>`;
    userList.appendChild(li);
  });

  // === Modal Logic ===
  let currentDeleteElement = null;

  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const li = btn.closest('li');
      const isPost = li.parentElement.id === 'recent-posts';
      if (isPost) {
        const modal = document.getElementById('edit-modal');
        const authorInput = document.getElementById('edit-author');
        const contentInput = document.getElementById('edit-content');
        const dateInput = document.getElementById('edit-date');
        const parts = li.querySelector('div').textContent.split('|').map(p => p.trim());
        authorInput.value = parts[2];
        contentInput.value = parts[4].replace('...', '');
        dateInput.value = parts[5];
        authorInput.readOnly = true;
        contentInput.readOnly = true;
        dateInput.readOnly = true;
        document.getElementById('save-edit').style.display = 'none';
        modal.style.display = 'flex';
      } else {
        const modal = document.getElementById('view-user-modal');
        const parts = li.querySelector('div').textContent.split('|').map(p => p.trim());
        document.getElementById('view-user-name').textContent = parts[0];
        document.getElementById('view-user-email').textContent = parts[1];
        document.getElementById('view-user-date').textContent = parts[2];
        modal.style.display = 'flex';
      }
    });
  });

  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      currentDeleteElement = btn.closest('li');
      document.getElementById('delete-modal').style.display = 'flex';
    });
  });

  document.getElementById('confirm-delete').addEventListener('click', () => {
    if (currentDeleteElement) currentDeleteElement.remove();
    document.getElementById('delete-modal').style.display = 'none';
  });

  document.getElementById('cancel-delete').addEventListener('click', () => {
    document.getElementById('delete-modal').style.display = 'none';
  });

  document.querySelectorAll('.close-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.modal').forEach(m => (m.style.display = 'none'));
    });
  });

  window.addEventListener('click', (e) => {
    document.querySelectorAll('.modal').forEach(m => {
      if (e.target === m) m.style.display = 'none';
    });
  });
});
  </script>
</body>
</html>

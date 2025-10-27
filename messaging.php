<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['chat'])) {
  unset($_SESSION['active_chat']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipient_id'])) {
  $_SESSION['active_chat'] = $_POST['recipient_id'];
  header("Location: messaging.php");
  exit();
}

if (isset($_SESSION['user_id'])){
    
  require __DIR__ . "/assets/config/dbconfig.php";

} else {
  header("Location: index.php");
}

$active_user = $_SESSION['user_id'];
$preselectedChat = $_SESSION['active_chat'] ?? null;
$users = [];

$contactSql = "SELECT u.id, u.full_name, u.role, u.profile_picture, m.content, m.sent_at, m.sender_id
               FROM (SELECT student_id AS id, full_name, 'Student' AS role, profile_picture FROM student_users
                     UNION
                     SELECT faculty_id AS id, full_name, 'Faculty' AS role, profile_picture FROM faculty_users)
               AS u
               LEFT JOIN (SELECT sender_id, receiver_id, content, sent_at
                          FROM messages
                          WHERE sender_id = ? OR receiver_id = ?
                          ORDER BY sent_at DESC)
               AS m ON (m.sender_id = u.id AND m.receiver_id = ?) OR (m.receiver_id = u.id AND m.sender_id = ?)
               WHERE u.id != ?
               GROUP BY u.id
               ORDER BY MAX(m.sent_at) DESC";

$stmt = $conn->prepare($contactSql);
$stmt->bind_param("sssss", $active_user, $active_user, $active_user, $active_user, $active_user);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()){
  $row['preview'] = $row['sender_id'] === $active_user ? "You: " . $row['content'] : explode(" ", $row['full_name'])[0] . ": " . $row['content'];
  $users[] =  $row;
}
$stmt->close();

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
  <title>U-Plug Messaging</title>
  <link rel="stylesheet" href="assets/css/messaging.css">
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
        <a href="home.php">Home</a>
        <a href="news.php">News</a>
        <a href="map.php">Map</a>
        <a href="messaging.php" class="active">Messages</a>
        <a href="profile.php">Profile</a>
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

  <main class="messaging-wrapper">
    <!-- Left: Contact List -->
    <aside class="contact-list" id="userList">
      <div class="contact-list-header">Contacts</div>
      <?php foreach ($users as $user): ?>
        <?php if ($user['id'] !== $active_user): ?>
          <?php 
          $lastMessage = null;
          $lastSenderId = null;

      $stmt = $conn->prepare("SELECT sender_id, content FROM messages WHERE (sender_id = ? AND receiver_id = ?)
         OR (sender_id = ? AND receiver_id = ?)
         ORDER BY sent_at DESC LIMIT 1");
        $stmt->bind_param("ssss", $active_user, $user['id'], $user['id'], $active_user);
        $stmt->execute();
        $stmt->bind_result($lastSenderId, $lastMessage);
        $stmt->fetch();
        $stmt->close();

          if ($lastSenderId === $active_user){
            $preview = 'You: ' . $lastMessage;
          } else if ($lastSenderId){
            $firstName = explode(" ", $user['full_name'])[0];
            $preview = $firstName . ": " . $lastMessage;
          } else {
            $preview = 'No message yet';
          }
          ?>
          <div class="contact-button <?= ($user['id'] === $preselectedChat) ? 'active' : '' ?>" data-id="<?= $user['id'] ?>">
            <button type="button">
              <div class="avatar-image">
                <img src="/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" class="profile-pic">
              </div>
              <div class="contact-info">
                <div class="contact-name"><?= htmlspecialchars($user['full_name']) .  " - (" . htmlspecialchars($user['role']). ")"?></div>
                <div class="last-message"><?= htmlspecialchars($preview) ?></div>
              </div>
            </button>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    </aside>

    <!-- Right: Chat History + Input -->
    <section class="chat-panel">
      <div class="chat-header" id="chatHeader">Select a contact to start chatting</div>
      <div class="chat-history" id="chatHistory">
        <div class="empty-chat">
          <div class="empty-chat-icon">💬</div>
          <p>Select a contact to start chatting</p>
        </div>
      </div>
      <form class="chat-input" id="chatForm">
        <input type="text" id="messageInput" placeholder="Type your message here..." required disabled />
        <button type="submit" disabled>Send</button>
      </form>
    </section>
  </main>
  
  <script>
    let currentChatWith = null; // Set this dynamically when user clicks a contact
    let lastMessageCount = 0;

      document.querySelectorAll('.contact-button').forEach(button => {
        document.getElementById('messageInput').disabled = false;
        document.querySelector('#chatForm button').disabled = false;
        button.addEventListener('click', function() {
          currentChatWith = this.getAttribute('data-id');
          loadMessages(); // Load messages for selected contact
        });
      });
          
      function loadMessages(chatId) {
        if (!currentChatWith) return;

        fetch(`/assets/server/load-messages.php?chat_with=${currentChatWith}`)
          .then(res => res.text())
          .then(html => {
            const chatHistory = document.getElementById('chatHistory');
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newMessages = doc.querySelectorAll('.message').length;
          
            if (lastMessageCount === 0 || newMessages !== lastMessageCount) {
              chatHistory.innerHTML = html;
              lastMessageCount = newMessages;
            
              // Optional: scroll to bottom
              document.getElementById('chatHistory').scrollTop = document.getElementById('chatHistory').scrollHeight;
            }
          });
      }

      function refreshContactList() {
        fetch('/assets/server/load-contacts.php') // Create this PHP file to return updated contact HTML
          .then(res => res.text())
          .then(html => {
            document.getElementById('userList').innerHTML = html;
            attachContactListeners(); // Rebind click events
          });
      }

      function attachContactListeners() {
        document.querySelectorAll('.contact-button').forEach(button => {
          button.addEventListener('click', function () {
            currentChatWith = this.getAttribute('data-id');
            document.getElementById('chatHeader').textContent = this.querySelector('.contact-name').textContent;
            document.getElementById('messageInput').disabled = false;
            document.querySelector('#chatForm button').disabled = false;
            loadMessages();
          });
        });
      }

        setInterval(() => {
          if (currentChatWith) {
            loadMessages();
          }
          refreshContactList();
        }, 1000); // every 1 second



      document.getElementById('chatForm').addEventListener('submit', function(e) {
        e.preventDefault();

        if (!currentChatWith) return;

        const formData = new FormData();
        formData.append('receiver_id', currentChatWith);
        formData.append('content', document.querySelector('#messageInput textarea').value);

        fetch('send-message.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.text())
        .then(() => {
          loadMessages(); // Refresh chat
          document.getElementById('messageInput').value; // Clear input
        });
      });



      document.querySelectorAll('.contact-button').forEach(button => {
        button.addEventListener('click', function() {
          currentChatWith = this.getAttribute('data-id');
        
          // Update header
          document.getElementById('chatHeader').textContent = this.querySelector('.contact-name').textContent;
        
          // Enable input
          document.getElementById('messageInput').disabled = false;
          document.querySelector('#chatForm button').disabled = false;
        
          // Load messages
          loadMessages();
        });
      });


      document.getElementById('chatForm').addEventListener('submit', function(e) {
        e.preventDefault(); // ✅ Prevent page reload

        if (!currentChatWith) return;

        const content = document.getElementById('messageInput').value.trim();
        if (!content) return;

        const formData = new FormData();
        formData.append('receiver_id', currentChatWith);
        formData.append('content', content);

        fetch('/assets/server/send-message.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.text())
        .then(response => {
          console.log('Message sent:', response);
          loadMessages(); // ✅ Refresh chat
          document.getElementById('messageInput').value = ''; // ✅ Clear input
        })
        .catch(err => {
          console.error('Send failed:', err);
        });
      });


        window.addEventListener('DOMContentLoaded', () => {
          const preselected = document.querySelector('.contact-button.active');
          if (preselected) {
            currentChatWith = preselected.getAttribute('data-id');
            document.getElementById('chatHeader').textContent = preselected.querySelector('.contact-name').textContent;
            document.getElementById('messageInput').disabled = false;
            document.querySelector('#chatForm button').disabled = false;
            loadMessages();
          }
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

<div id="toastContainer" class="toast-container"></div>

</body>

</html>
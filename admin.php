<?php
require_once __DIR__ . '/includes/admin_check.php';

// 頁面設定
$pageTitle = '管理後台';
$headerTitle = '管理後台';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
  <h2>系統管理</h2>

  <div class="admin-nav">
    <button class="btn active" onclick="showSection('users')">用戶管理</button>
    <button class="btn" onclick="showSection('create')">新增用戶</button>
  </div>

  <!-- 用戶管理 -->
  <div id="users-section" class="admin-section active">
    <h3>用戶列表</h3>
    <table id="users-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>用戶名</th>
          <th>角色</th>
          <th>建立時間</th>
          <th>最後登入</th>
          <th>單據數</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody id="users-tbody"></tbody>
    </table>
  </div>

  <!-- 新增用戶 -->
  <div id="create-section" class="admin-section">
    <h3>新增用戶</h3>
    <form id="create-form" style="max-width:500px;">
      <div class="form-group">
        <label>用戶名</label>
        <input type="text" name="username" class="form-control" required>
      </div>
      <div class="form-group">
        <label>密碼</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="form-group">
        <label>角色</label>
        <select name="is_admin" class="form-control">
          <option value="0">一般用戶</option>
          <option value="1">管理員</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">建立用戶</button>
    </form>
  </div>
</div>



<script type="module">
  import { Toast, Dialog } from './js/modules/toast.js';

  let currentSection = 'users';

  window.showSection = function (section) {
    document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.admin-nav .btn').forEach(b => b.classList.remove('active'));

    document.getElementById(section + '-section').classList.add('active');

    // 根據 section 找到對應按鈕
    const buttons = document.querySelectorAll('.admin-nav .btn');
    if (section === 'users') buttons[0].classList.add('active');
    if (section === 'create') buttons[1].classList.add('active');

    currentSection = section;
    if (section === 'users') loadUsers();
  };

  async function loadUsers() {
    try {
      const res = await fetch('api/admin/list_users.php');
      const data = await res.json();

      if (data.success) {
        renderUsers(data.users);
      }
    } catch (err) {
      console.error('載入失敗:', err);
    }
  }

  function renderUsers(users) {
    const tbody = document.getElementById('users-tbody');
    const currentUserId = <?php echo $_SESSION['user_id']; ?>;

    tbody.innerHTML = users.map(u => `
    <tr>
      <td>${u.id}</td>
      <td>${u.username}</td>
      <td>${u.is_admin ? '🔐 管理員' : '👤 一般'}</td>
      <td>${u.created_at}</td>
      <td>${u.last_login || '未登入'}</td>
      <td>${u.receipt_count}</td>
      <td>
        <button class="btn btn-warning btn-sm" onclick="resetPassword(${u.id}, '${u.username}')">重設密碼</button>
        ${u.id !== currentUserId ?
        `<button class="btn btn-danger btn-sm" onclick="deleteUser(${u.id}, '${u.username}')">刪除</button>` :
        ''
      }
      </td>
    </tr>
      `).join('');
  }

  document.getElementById('create-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);

    try {
      const res = await fetch('api/admin/create_user.php', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();

      if (data.success) {
        Toast.success('用戶建立成功！');
        e.target.reset();
        showSection('users');
      } else {
        Toast.error('建立失敗：' + data.error);
      }
    } catch (err) {
      Toast.error('建立失敗');
    }
  });

  window.deleteUser = async function (id, username) {
    const confirmed = await Dialog.confirm(
      `確定要刪除用戶「${username}」嗎？<br><br><strong>此操作將同時刪除該用戶的所有單據及圖片！</strong>`,
      { title: '刪除用戶', confirmText: '刪除', danger: true }
    );

    if (!confirmed) return;

    try {
      const res = await fetch('api/admin/delete_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `user_id=${id}`
      });
      const data = await res.json();

      if (data.success) {
        Toast.success('用戶已刪除');
        loadUsers();
      } else {
        Toast.error('刪除失敗：' + data.error);
      }
    } catch (err) {
      Toast.error('刪除失敗');
    }
  };

  window.resetPassword = async function (id, username) {
    const newPassword = await Dialog.prompt(
      `為用戶「${username}」設定新密碼：`,
      { title: '重設密碼', inputType: 'password', placeholder: '輸入新密碼' }
    );

    if (!newPassword) return;

    try {
      const res = await fetch('api/admin/reset_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `user_id=${id}&new_password=${encodeURIComponent(newPassword)}`
      });
      const data = await res.json();

      if (data.success) {
        Toast.success('密碼已重設！');
      } else {
        Toast.error('重設失敗：' + data.error);
      }
    } catch (err) {
      Toast.error('重設失敗');
    }
  };

  loadUsers();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
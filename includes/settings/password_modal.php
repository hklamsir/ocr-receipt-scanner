<!-- 變更密碼 Modal -->
<div id="passwordModal" class="edit-modal">
    <div class="edit-modal-content" style="max-width:400px;">
        <div class="edit-modal-header">
            <span>🔐 變更密碼</span>
            <button class="close-btn" onclick="closePasswordModal()">✕</button>
        </div>
        <form id="passwordForm" style="padding:20px;">
            <div class="form-group">
                <label for="currentPassword">目前密碼</label>
                <input type="password" id="currentPassword" required>
            </div>
            <div class="form-group">
                <label for="newPassword">新密碼</label>
                <input type="password" id="newPassword" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirmPassword">確認新密碼</label>
                <input type="password" id="confirmPassword" required minlength="6">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">取消</button>
                <button type="submit" class="btn btn-primary">變更密碼</button>
            </div>
        </form>
    </div>
</div>
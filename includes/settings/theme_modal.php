<!-- 外觀主題 Modal -->
<div id="themeModal" class="edit-modal">
    <div class="edit-modal-content" style="max-width: 520px;">
        <div class="edit-modal-header">
            <span>🎨 選擇主題</span>
            <button class="close-btn" onclick="closeThemeModal()">✕</button>
        </div>
        <div class="edit-modal-body" style="padding: 20px;">
            <p style="margin-bottom: 16px; color: var(--text-muted);">點擊主題可即時預覽，儲存後生效並跨裝置同步。</p>
            <div class="theme-options" id="themeOptions">
                <button type="button" class="theme-option<?php echo $currentTheme === 'teal' ? ' is-current' : ''; ?>" data-theme-key="teal">
                    <div class="theme-option-swatches">
                        <span style="background:#0d9488"></span>
                        <span style="background:#5eead4"></span>
                        <span style="background:#f0fdfa"></span>
                    </div>
                    <div class="theme-option-name">活潑浣熊</div>
                    <div class="theme-option-desc">森林青綠（預設）</div>
                    <div class="theme-option-badge">✓ 目前</div>
                </button>
                <button type="button" class="theme-option<?php echo $currentTheme === 'elegant' ? ' is-current' : ''; ?>" data-theme-key="elegant">
                    <div class="theme-option-swatches">
                        <span style="background:#166534"></span>
                        <span style="background:#1c2b21"></span>
                        <span style="background:#f6f3ec"></span>
                    </div>
                    <div class="theme-option-name">優雅商務</div>
                    <div class="theme-option-desc">墨綠 · 正式</div>
                    <div class="theme-option-badge">✓ 目前</div>
                </button>
                <button type="button" class="theme-option<?php echo $currentTheme === 'minimal' ? ' is-current' : ''; ?>" data-theme-key="minimal">
                    <div class="theme-option-swatches">
                        <span style="background:#4f46e5"></span>
                        <span style="background:#ffffff"></span>
                        <span style="background:#fafafa"></span>
                    </div>
                    <div class="theme-option-name">極簡清新</div>
                    <div class="theme-option-desc">靛藍 · 留白</div>
                    <div class="theme-option-badge">✓ 目前</div>
                </button>
                <button type="button" class="theme-option<?php echo $currentTheme === 'dark' ? ' is-current' : ''; ?>" data-theme-key="dark">
                    <div class="theme-option-swatches">
                        <span style="background:#38bdf8"></span>
                        <span style="background:#0e1830"></span>
                        <span style="background:#0b1220"></span>
                    </div>
                    <div class="theme-option-name">深色專業</div>
                    <div class="theme-option-desc">天藍 · 低光</div>
                    <div class="theme-option-badge">✓ 目前</div>
                </button>
            </div>
            <div class="form-actions" style="margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeThemeModal()">取消</button>
                <button type="button" class="btn btn-primary" id="saveThemeBtn">儲存主題</button>
            </div>
            <div class="theme-saved-hint" id="themeSavedHint" style="margin-top: 10px; text-align: center;"></div>
        </div>
    </div>
</div>

@extends('layouts.admin')

@section('title', 'Messages')

@push('styles')
<style>
    .content-body { margin-top: 0 !important; padding-top: 1.25rem; }
    
    /* ===== Messaging UI ===== */
    .msg-container { display: flex; gap: 1.25rem; min-height: 70vh; }
    .msg-sidebar-panel { width: 17.5rem; flex-shrink: 0; }
    .msg-main-panel { flex: 1; background: #fff; border-radius: 0.75rem; box-shadow: 0 0.125rem 0.75rem rgba(0,0,0,0.08); overflow: hidden; }

    .msg-compose-btn { display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; padding: 0.875rem 1.25rem; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: none; border-radius: 0.625rem; font-size: 0.9375rem; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-bottom: 1rem; }
    .msg-compose-btn:hover { transform: translateY(-0.125rem); box-shadow: 0 0.375rem 1.25rem rgba(99,102,241,0.4); }

    .msg-nav-list { list-style: none; padding: 0; margin: 0; }
    .msg-nav-list li { margin-bottom: 0.25rem; }
    .msg-nav-list li a { display: flex; align-items: center; gap: 0.625rem; padding: 0.75rem 1rem; border-radius: 0.5rem; color: #4b5563; text-decoration: none; font-weight: 500; transition: all 0.2s; }
    .msg-nav-list li a:hover, .msg-nav-list li a.active { background: #f0f0ff; color: #6366f1; }
    .msg-nav-badge { background: #ef4444; color: #fff; font-size: 0.6875rem; padding: 0.125rem 0.5rem; border-radius: 0.625rem; margin-left: auto; }

    .msg-header-bar { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; padding: 1.25rem 1.5rem; }
    .msg-header-bar h4 { margin: 0; font-size: 1.25rem; font-weight: 600; }
    .msg-header-bar p { margin: 0.25rem 0 0; opacity: 0.8; font-size: 0.8125rem; }

    .msg-list { max-height: 58vh; overflow-y: auto; }
    .msg-item { display: flex; align-items: flex-start; gap: 0.875rem; padding: 1rem 1.5rem; border-bottom: 1.0px solid #f3f4f6; cursor: pointer; transition: background 0.2s; }
    .msg-item:hover { background: #f9fafb; }
    .msg-item.unread { background: #f0f0ff; border-left: 0.1875rem solid #6366f1; }
    .msg-item-avatar { width: 2.75rem; height: 2.75rem; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #a78bfa); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; flex-shrink: 0; }
    .msg-item-avatar.sent-av { background: linear-gradient(135deg, #10b981, #34d399); }
    .msg-item-body { flex: 1; min-width: 0; }
    .msg-item-top { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem; }
    .msg-sender { font-weight: 600; color: #1f2937; font-size: 0.875rem; }
    .msg-role-tag { font-size: 0.6875rem; background: #e0e7ff; color: #4338ca; padding: 0.125rem 0.5rem; border-radius: 0.25rem; }
    .msg-broadcast-tag { font-size: 0.6875rem; background: #fef3c7; color: #92400e; padding: 0.125rem 0.5rem; border-radius: 0.25rem; }
    .msg-subject { font-weight: 500; color: #374151; font-size: 0.8125rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .msg-preview { color: #9ca3af; font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 0.125rem; }
    .msg-time { color: #9ca3af; font-size: 0.75rem; white-space: nowrap; }
    .msg-dot { width: 0.5rem; height: 0.5rem; background: #6366f1; border-radius: 50%; display: inline-block; margin-left: 0.375rem; }

    .msg-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3.75rem 1.25rem; color: #9ca3af; }
    .msg-empty i { font-size: 3rem; margin-bottom: 1rem; }

    /* Compose Form */
    .msg-compose { padding: 1.5rem; }
    .msg-compose h5 { font-size: 1.125rem; font-weight: 600; margin-bottom: 1.25rem; color: #1f2937; }
    .msg-compose .form-group { margin-bottom: 1rem; }
    .msg-compose label { font-weight: 600; color: #374151; margin-bottom: 0.375rem; display: block; font-size: 0.8125rem; }
    .msg-compose .form-control, .msg-compose select { border-radius: 0.5rem; border: 0.09375rem solid #e5e7eb; padding: 0.625rem 0.875rem; font-size: 0.875rem; }
    .msg-compose .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 0.1875rem rgba(99,102,241,0.15); }
    .msg-compose textarea { min-height: 8.75rem; resize: vertical; }

    .recipient-btns { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem; }
    .recipient-btns .rbtn { padding: 0.5rem 1rem; border-radius: 0.5rem; border: 0.09375rem solid #e5e7eb; background: #fff; color: #374151; font-size: 0.8125rem; font-weight: 500; cursor: pointer; transition: all 0.2s; }
    .recipient-btns .rbtn:hover { border-color: #6366f1; color: #6366f1; }
    .recipient-btns .rbtn.active { background: #6366f1; color: #fff; border-color: #6366f1; }

    .compose-actions { display: flex; gap: 0.625rem; justify-content: flex-end; margin-top: 1.25rem; }
    .btn-send-msg { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: none; padding: 0.75rem 1.75rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
    .btn-send-msg:hover { transform: translateY(-1.0px); box-shadow: 0 0.25rem 0.75rem rgba(99,102,241,0.4); }
    .btn-send-msg:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

    /* Detail View */
    .msg-detail { padding: 1.5rem; }
    .msg-detail-header { display: flex; align-items: flex-start; gap: 1rem; padding-bottom: 1.25rem; border-bottom: 1.0px solid #f3f4f6; margin-bottom: 1.25rem; }
    .msg-detail-avatar { width: 3.25rem; height: 3.25rem; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #a78bfa); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.25rem; flex-shrink: 0; }
    .msg-detail h4 { font-size: 1.125rem; font-weight: 600; margin: 0 0 0.375rem; color: #1f2937; }
    .msg-detail .meta { color: #6b7280; font-size: 0.8125rem; }
    .msg-detail-body { font-size: 0.9375rem; line-height: 1.7; color: #374151; white-space: pre-wrap; }
    .msg-back { display: inline-flex; align-items: center; gap: 0.375rem; color: #6366f1; font-weight: 500; cursor: pointer; margin-bottom: 1rem; border: none; background: none; font-size: 0.875rem; }
    .msg-back:hover { text-decoration: underline; }
    .msg-loading { display: flex; align-items: center; justify-content: center; padding: 2.5rem; color: #9ca3af; }
</style>
@endpush

@section('content')
<div class="msg-container" id="msgApp">
    {{-- Sidebar --}}
    <div class="msg-sidebar-panel">
        <button class="msg-compose-btn" onclick="showCompose()">
            <i class="flaticon-381-add-1"></i> New Message
        </button>
        <ul class="msg-nav-list">
            <li><a href="#" class="active" id="navInbox" onclick="showInbox(); return false;"><i class="flaticon-381-inbox"></i> Inbox <span class="msg-nav-badge" id="unreadBadge" style="display:none;">0</span></a></li>
            <li><a href="#" id="navSent" onclick="showSent(); return false;"><i class="flaticon-381-send"></i> Sent</a></li>
        </ul>
    </div>

    {{-- Main Panel --}}
    <div class="msg-main-panel">
        <div class="msg-header-bar">
            <h4 id="panelTitle">📥 Inbox</h4>
            <p id="panelSubtitle">Your received messages</p>
        </div>

        {{-- Inbox View --}}
        <div id="viewInbox">
            <div class="msg-loading" id="inboxLoading">Loading messages...</div>
            <div class="msg-list" id="inboxList"></div>
            <div class="msg-empty" id="inboxEmpty" style="display:none;"><i class="flaticon-381-inbox-1"></i><p>No messages yet</p></div>
        </div>

        {{-- Sent View --}}
        <div id="viewSent" style="display:none;">
            <div class="msg-loading" id="sentLoading">Loading sent messages...</div>
            <div class="msg-list" id="sentList"></div>
            <div class="msg-empty" id="sentEmpty" style="display:none;"><i class="flaticon-381-send"></i><p>No sent messages</p></div>
        </div>

        {{-- Compose View --}}
        <div id="viewCompose" style="display:none;">
            <div class="msg-compose">
                <h5>✍️ Compose Message</h5>
                <div class="form-group">
                    <label>Send To</label>
                    <div class="recipient-btns" id="recipientBtns"></div>
                </div>
                <div class="form-group" id="individualSelect" style="display:none;">
                    <label>Select Recipient</label>
                    <select class="form-control" id="recipientId"><option value="">-- Select --</option></select>
                </div>
                <div class="form-group" id="gradeSelect" style="display:none;">
                    <label>Select Grade</label>
                    <select class="form-control" id="gradeId"><option value="">-- Select Grade --</option></select>
                </div>
                <div class="form-group"><label>Subject</label><input type="text" class="form-control" id="msgSubject" placeholder="Message subject..."></div>
                <div class="form-group"><label>Message</label><textarea class="form-control" id="msgBody" placeholder="Type your message here..."></textarea></div>
                <div class="compose-actions">
                    <button class="btn btn-secondary" onclick="showInbox()">Cancel</button>
                    <button class="btn-send-msg" id="sendBtn" onclick="sendMessage()">📤 Send Message</button>
                </div>
            </div>
        </div>

        {{-- Detail View --}}
        <div id="viewDetail" style="display:none;">
            <div class="msg-detail">
                <button class="msg-back" onclick="showInbox()">← Back to Inbox</button>
                <div class="msg-detail-header">
                    <div class="msg-detail-avatar" id="detailAvatar">?</div>
                    <div>
                        <h4 id="detailSubject"></h4>
                        <p class="meta" id="detailMeta"></p>
                        <span class="meta" id="detailTime"></span>
                    </div>
                </div>
                <div class="msg-detail-body" id="detailBody"></div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    const API = '{{ url("/api") }}';
    const TOKEN = '{{ $token }}';
    const authHeaders = { 'Authorization': 'Bearer ' + TOKEN, 'Accept': 'application/json', 'Content-Type': 'application/json' };

    let currentView = 'inbox';
    let recipientType = 'individual';
    let recipientsData = {};
    let inboxMessages = [];
    let sentMessages = [];

    // ===== NAVIGATION =====
    function setNav(active) {
        document.getElementById('navInbox').classList.toggle('active', active === 'inbox');
        document.getElementById('navSent').classList.toggle('active', active === 'sent');
    }
    function hideAll() {
        ['viewInbox','viewSent','viewCompose','viewDetail'].forEach(id => document.getElementById(id).style.display = 'none');
    }

    function showInbox() {
        hideAll(); document.getElementById('viewInbox').style.display = '';
        document.getElementById('panelTitle').textContent = '📥 Inbox';
        document.getElementById('panelSubtitle').textContent = 'Your received messages';
        setNav('inbox'); currentView = 'inbox'; fetchInbox();
    }
    function showSent() {
        hideAll(); document.getElementById('viewSent').style.display = '';
        document.getElementById('panelTitle').textContent = '📤 Sent';
        document.getElementById('panelSubtitle').textContent = 'Messages you have sent';
        setNav('sent'); currentView = 'sent'; fetchSent();
    }
    function showCompose() {
        hideAll(); document.getElementById('viewCompose').style.display = '';
        document.getElementById('panelTitle').textContent = '✍️ Compose';
        document.getElementById('panelSubtitle').textContent = 'Send a new message';
        setNav(''); fetchRecipients();
    }
    function showDetail(msg, isSent) {
        hideAll(); document.getElementById('viewDetail').style.display = '';
        document.getElementById('panelTitle').textContent = '💬 Message';
        document.getElementById('panelSubtitle').textContent = '';
        document.getElementById('detailSubject').textContent = msg.subject;
        document.getElementById('detailAvatar').textContent = (msg.sender?.name || msg.receiver?.name || '?').charAt(0).toUpperCase();
        const roleMap = { admin: '🛡️ Admin', teacher: '👨‍🏫 Teacher', user: '🎓 Student' };
        if (isSent) {
            const toName = msg.receiver?.name || getBroadcastLabel(msg.receiver_role);
            document.getElementById('detailMeta').innerHTML = 'To: <strong>' + toName + '</strong>';
        } else {
            document.getElementById('detailMeta').innerHTML = 'From: <strong>' + (msg.sender?.name || '') + '</strong> (' + (roleMap[msg.sender?.role] || '') + ')';
        }
        document.getElementById('detailTime').textContent = '🕙 ' + new Date(msg.created_at).toLocaleString();
        document.getElementById('detailBody').textContent = msg.body;
        if (!isSent && !msg.is_read) markRead(msg.id);
    }

    // ===== API CALLS =====
    async function fetchInbox() {
        document.getElementById('inboxLoading').style.display = '';
        document.getElementById('inboxEmpty').style.display = 'none';
        try {
            const res = await fetch(API + '/messages', { headers: authHeaders });
            const data = await res.json();
            inboxMessages = data.messages || [];
            renderInbox();
        } catch(e) { console.error(e); }
        document.getElementById('inboxLoading').style.display = 'none';
    }

    async function fetchSent() {
        document.getElementById('sentLoading').style.display = '';
        document.getElementById('sentEmpty').style.display = 'none';
        try {
            const res = await fetch(API + '/messages/sent', { headers: authHeaders });
            const data = await res.json();
            sentMessages = data.messages || [];
            renderSent();
        } catch(e) { console.error(e); }
        document.getElementById('sentLoading').style.display = 'none';
    }

    async function fetchUnread() {
        try {
            const res = await fetch(API + '/messages/unread-count', { headers: authHeaders });
            const data = await res.json();
            const badge = document.getElementById('unreadBadge');
            if (data.unread_count > 0) { badge.textContent = data.unread_count; badge.style.display = ''; }
            else { badge.style.display = 'none'; }
        } catch(e) {}
    }

    async function fetchRecipients() {
        try {
            const res = await fetch(API + '/messages/recipients', { headers: authHeaders });
            recipientsData = await res.json();
            renderRecipientButtons();
            setRecipientType('individual');
        } catch(e) { console.error(e); }
    }

    async function sendMessage() {
        const subject = document.getElementById('msgSubject').value.trim();
        const body = document.getElementById('msgBody').value.trim();
        if (!subject || !body) { alert('Please fill in subject and message.'); return; }

        const btn = document.getElementById('sendBtn');
        btn.disabled = true; btn.textContent = 'Sending...';

        const payload = { subject, body, recipient_type: recipientType };
        if (recipientType === 'individual') payload.recipient_id = document.getElementById('recipientId').value;
        if (recipientType === 'grade') payload.grade = document.getElementById('gradeId').value;

        try {
            const res = await fetch(API + '/messages', { method: 'POST', headers: authHeaders, body: JSON.stringify(payload) });
            if (res.ok) {
                document.getElementById('msgSubject').value = '';
                document.getElementById('msgBody').value = '';
                showSent();
            } else {
                const err = await res.json();
                alert(err.message || 'Failed to send.');
            }
        } catch(e) { alert('Network error.'); }
        btn.disabled = false; btn.textContent = '📤 Send Message';
    }

    async function markRead(id) {
        try { await fetch(API + '/messages/' + id + '/read', { method: 'POST', headers: authHeaders }); fetchUnread(); } catch(e) {}
    }

    // ===== RENDERING =====
    function formatDate(iso) {
        const d = new Date(iso), now = new Date(), diff = now - d;
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return Math.floor(diff/60000) + 'm ago';
        if (diff < 86400000) return Math.floor(diff/3600000) + 'h ago';
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
    }
    function getBroadcastLabel(role) {
        if (role === 'all_students') return ' All Students';
        if (role === 'all_teachers') return ' All Teachers';
        if (role === 'admin') return ' Admin';
        if (role && role.startsWith('grade_')) return ' Grade ' + role.replace('grade_', '');
        return role || '';
    }

    function renderInbox() {
        const list = document.getElementById('inboxList');
        if (inboxMessages.length === 0) { list.innerHTML = ''; document.getElementById('inboxEmpty').style.display = ''; return; }
        document.getElementById('inboxEmpty').style.display = 'none';
        const roleMap = { admin: '🛡️ Admin', teacher: ' Teacher', user: ' Student' };
        list.innerHTML = inboxMessages.map(m => `
            <div class="msg-item ${!m.is_read ? 'unread' : ''}" onclick="showDetail(inboxMessages[${inboxMessages.indexOf(m)}], false)">
                <div class="msg-item-avatar">${(m.sender?.name || '?').charAt(0).toUpperCase()}</div>
                <div class="msg-item-body">
                    <div class="msg-item-top">
                        <span class="msg-sender">${m.sender?.name || 'Unknown'}</span>
                        <span class="msg-role-tag">${roleMap[m.sender?.role] || ''}</span>
                        ${m.is_broadcast ? '<span class="msg-broadcast-tag">' + getBroadcastLabel(m.receiver_role) + '</span>' : ''}
                    </div>
                    <div class="msg-subject">${m.subject || ''}</div>
                    <div class="msg-preview">${(m.body || '').substring(0, 80)}...</div>
                </div>
                <div style="text-align:right;">
                    <span class="msg-time">${formatDate(m.created_at)}</span>
                    ${!m.is_read ? '<span class="msg-dot"></span>' : ''}
                </div>
            </div>
        `).join('');
    }

    function renderSent() {
        const list = document.getElementById('sentList');
        if (sentMessages.length === 0) { list.innerHTML = ''; document.getElementById('sentEmpty').style.display = ''; return; }
        document.getElementById('sentEmpty').style.display = 'none';
        list.innerHTML = sentMessages.map(m => `
            <div class="msg-item" onclick="showDetail(sentMessages[${sentMessages.indexOf(m)}], true)">
                <div class="msg-item-avatar sent-av">${(m.receiver?.name || '📢').charAt(0).toUpperCase()}</div>
                <div class="msg-item-body">
                    <div class="msg-item-top"><span class="msg-sender">To: ${m.receiver?.name || getBroadcastLabel(m.receiver_role)}</span></div>
                    <div class="msg-subject">${m.subject || ''}</div>
                    <div class="msg-preview">${(m.body || '').substring(0, 80)}...</div>
                </div>
                <div style="text-align:right;"><span class="msg-time">${formatDate(m.created_at)}</span></div>
            </div>
        `).join('');
    }

    function renderRecipientButtons() {
        const btns = document.getElementById('recipientBtns');
        let html = '<button class="rbtn active" data-type="individual" onclick="setRecipientType(\'individual\')">👤 Individual</button>';
        (recipientsData.broadcast || []).forEach(opt => {
            if (opt.value.startsWith('grade_')) return;
            html += `<button class="rbtn" data-type="${opt.value}" onclick="setRecipientType('${opt.value}')">${opt.label}</button>`;
        });
        const hasGrades = (recipientsData.broadcast || []).some(o => o.value.startsWith('grade_'));
        if (hasGrades) html += '<button class="rbtn" data-type="grade" onclick="setRecipientType(\'grade\')">📚 By Grade</button>';
        btns.innerHTML = html;

        // Populate individual select
        const sel = document.getElementById('recipientId');
        sel.innerHTML = '<option value="">-- Select --</option>';
        (recipientsData.teachers || []).forEach(t => sel.innerHTML += `<option value="${t.id}">${t.name} (Teacher) — ${t.email}</option>`);
        (recipientsData.students || []).forEach(s => sel.innerHTML += `<option value="${s.id}">${s.name} (Student) — ${s.email}</option>`);
        (recipientsData.admins || []).forEach(a => sel.innerHTML += `<option value="${a.id}">${a.name} (Admin) — ${a.email}</option>`);

        // Populate grade select
        const gSel = document.getElementById('gradeId');
        gSel.innerHTML = '<option value="">-- Select Grade --</option>';
        (recipientsData.broadcast || []).filter(o => o.value.startsWith('grade_')).forEach(g => {
            gSel.innerHTML += `<option value="${g.value.replace('grade_', '')}">${g.label}</option>`;
        });
    }

    function setRecipientType(type) {
        recipientType = type;
        document.querySelectorAll('.recipient-btns .rbtn').forEach(b => b.classList.toggle('active', b.dataset.type === type));
        document.getElementById('individualSelect').style.display = type === 'individual' ? '' : 'none';
        document.getElementById('gradeSelect').style.display = type === 'grade' ? '' : 'none';
    }

    // ===== INIT =====
    document.addEventListener('DOMContentLoaded', function() {
        fetchInbox();
        fetchUnread();
    });
</script>
@endpush

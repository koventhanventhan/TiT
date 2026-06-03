@extends('layouts.admin')

@section('title', 'Messages')

@push('styles')
<style>
    /* ===== Override admin theme defaults that break our layout ===== */
    body { overflow-y: hidden !important; } /* Prevent main page from scrolling to avoid hiding headers */
    .footer { display: none !important; } /* Hide footer on chat layout for maximum space */
    .content-body { margin-top: 0 !important; padding-top: 1.25rem; padding-bottom: 0 !important; }
    .content-body .container-fluid { padding-left: 15px; padding-right: 15px; height: 100%; display: flex; flex-direction: column; }
    
    /* ===== WhatsApp-Style Messaging UI ===== */
    #msgApp.chat-app {
        display: flex !important;
        flex-direction: row !important;
        flex: 1; /* allow flex container to consume available space */
        height: calc(100vh - 120px) !important;
        min-height: 400px; /* reduced min-height to prevent cropping on smaller screens */
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08), 0 2px 8px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }

    /* ===== Sidebar ===== */
    #msgApp .chat-sidebar {
        width: 360px;
        min-width: 360px;
        border-right: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        background: linear-gradient(180deg, #fafbfc 0%, #f1f5f9 100%);
        flex-shrink: 0;
    }

    #msgApp .chat-header {
        padding: 1.25rem 1.25rem;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    #msgApp .chat-title {
        font-size: 1.3rem;
        font-weight: 800;
        color: #1e293b;
        margin: 0;
        letter-spacing: -0.02em;
    }

#msgApp .chat-sidebar .chat-header .chat-title {
    color: #6049e2ff !important;
}


#composeOverlay .chat-main-name {
    color: #6049e2ff !important;
}


#msgApp #emptyState h3 {
    color: #6049e2ff !important;
}

    #msgApp .compose-btn {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: #fff;
        border: none;
        padding: 0.5rem 1.1rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
    }
    #msgApp .compose-btn:hover {
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
        transform: translateY(-2px);
    }

    #msgApp .chat-search-bar {
        padding: 0.85rem 1.25rem;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
    }
    #msgApp .search-icon-wrapper { position: relative; }
    #msgApp .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.85rem;
    }
    #msgApp .chat-search-input {
        width: 100%;
        padding: 0.65rem 1rem 0.65rem 2.5rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        font-size: 0.875rem;
        color: #334155;
        background: #f8fafc;
        transition: all 0.2s;
    }
    #msgApp .chat-search-input:focus {
        outline: none;
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    /* Thread List */
    #msgApp .chat-list {
        flex: 1;
        overflow-y: auto;
        padding: 0.25rem 0;
    }
    #msgApp .chat-list::-webkit-scrollbar { width: 5px; }
    #msgApp .chat-list::-webkit-scrollbar-track { background: transparent; }
    #msgApp .chat-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    #msgApp .chat-thread-item {
        display: flex;
        padding: 0.85rem 1.25rem;
        gap: 0.85rem;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        border-left: 3px solid transparent;
        margin: 0 0.25rem;
        border-radius: 0 8px 8px 0;
    }
    #msgApp .chat-thread-item:hover {
        background: rgba(99, 102, 241, 0.04);
    }
    #msgApp .chat-thread-item.active {
        background: linear-gradient(90deg, rgba(99, 102, 241, 0.08) 0%, rgba(99, 102, 241, 0.03) 100%);
        border-left-color: #6366f1;
    }

    #msgApp .chat-avatar {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #a78bfa);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        flex-shrink: 0;
        position: relative;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
    }
    #msgApp .chat-avatar.group {
        background: linear-gradient(135deg, #10b981, #34d399);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
    }
    #msgApp .unread-badge {
        position: absolute;
        top: -3px;
        right: -3px;
        background: linear-gradient(135deg, #ef4444, #f87171);
        color: #fff;
        font-size: 0.6rem;
        font-weight: bold;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
    }

    #msgApp .chat-thread-info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    #msgApp .chat-thread-top {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 3px;
    }
    #msgApp .chat-thread-name {
        font-weight: 600;
        color: #1e293b;
        font-size: 0.95rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #msgApp .chat-thread-time {
        font-size: 0.72rem;
        color: #94a3b8;
        flex-shrink: 0;
        margin-left: 8px;
    }
    #msgApp .chat-thread-preview {
        font-size: 0.82rem;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #msgApp .role-badge {
        display: inline-block;
        font-size: 0.58rem;
        padding: 2px 7px;
        border-radius: 6px;
        margin-left: 6px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        vertical-align: middle;
    }
    #msgApp .role-badge.badge-student { background: #dbeafe; color: #2563eb; }
    #msgApp .role-badge.badge-teacher { background: #fef3c7; color: #d97706; }
    #msgApp .role-badge.badge-admin { background: #fce7f3; color: #db2777; }
    #msgApp .role-badge.badge-broadcast { background: #d1fae5; color: #059669; }

    /* ===== Main Chat Area ===== */
    #msgApp .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #ffffff;
        position: relative;
        min-width: 0;
        overflow: hidden;
    }

    /* Empty State */
    #msgApp .chat-empty-state {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        padding: 2rem;
    }
    #msgApp .chat-empty-state .empty-icon {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
    }
    #msgApp .chat-empty-state .empty-icon i {
        font-size: 2.5rem;
        color: #94a3b8;
    }
    #msgApp .chat-empty-state h3 {
        font-size: 1.2rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 0.5rem;
    }
    #msgApp .chat-empty-state p {
        font-size: 0.9rem;
        color: #94a3b8;
        margin: 0;
    }

    /* Active Chat Header */
    #msgApp .chat-main-header {
        padding: 1rem 1.5rem;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 0.85rem;
        flex-shrink: 0;
    }
    #msgApp .chat-main-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }
    #msgApp .chat-main-meta {
        font-size: 0.82rem;
        color: #64748b;
    }

    /* Messages Area */
    #msgApp .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    }
    #msgApp .chat-messages::-webkit-scrollbar { width: 5px; }
    #msgApp .chat-messages::-webkit-scrollbar-track { background: transparent; }
    #msgApp .chat-messages::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    #msgApp .message-row { display: flex; margin-bottom: 0.85rem; width: 100%; }
    #msgApp .message-row.sent { justify-content: flex-end; }
    #msgApp .message-row.received { justify-content: flex-start; }

    #msgApp .message-bubble {
        max-width: 68%;
        padding: 0.85rem 1.2rem;
        border-radius: 16px;
        position: relative;
        animation: fadeInMsg 0.3s ease;
    }
    @keyframes fadeInMsg {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    #msgApp .message-row.sent .message-bubble {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: #ffffff;
        border-bottom-right-radius: 4px;
        box-shadow: 0 2px 12px rgba(99, 102, 241, 0.2);
    }
    #msgApp .message-row.received .message-bubble {
        background: #ffffff;
        color: #1e293b;
        border-bottom-left-radius: 4px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    }

    #msgApp .message-subject {
        font-size: 0.72rem;
        font-weight: 700;
        margin-bottom: 0.4rem;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        padding-bottom: 5px;
    }
    #msgApp .message-row.received .message-subject {
        color: #6366f1;
        border-bottom-color: #e2e8f0;
    }
    #msgApp .message-text { font-size: 0.93rem; line-height: 1.55; white-space: pre-wrap; word-wrap: break-word; }
    #msgApp .message-time {
        display: block;
        font-size: 0.68rem;
        margin-top: 0.5rem;
        text-align: right;
        opacity: 0.65;
    }

    /* ===== Chat Input Area ===== */
    #msgApp .chat-input-area {
        padding: 1rem 1.5rem;
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
        flex-shrink: 0;
    }
    #msgApp .reply-subject-input {
        width: 100%;
        padding: 0.55rem 1rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.875rem;
        color: #1e293b !important;
        background: #f8fafc !important;
        transition: all 0.2s;
    }
    #msgApp .reply-body-input {
        width: 100%;
        padding: 0.65rem 1rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.93rem;
        resize: none;
        color: #1e293b !important;
        background: #f8fafc !important;
        transition: all 0.2s;
    }
    #msgApp .reply-subject-input:focus,
    #msgApp .reply-body-input:focus {
        outline: none;
        border-color: #6366f1;
        background: #ffffff !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    #msgApp .chat-input-actions { display: flex; justify-content: flex-end; }
    #msgApp .btn-send {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: #fff;
        border: none;
        padding: 0.6rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
    }
    #msgApp .btn-send:hover {
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
        transform: translateY(-1px);
    }
    #msgApp .btn-send:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }

    /* ===== Compose Overlay — MUST cover the entire chat-main ===== */
    #msgApp #composeOverlay {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        background: #ffffff !important;
        z-index: 50 !important;
        flex-direction: column !important;
        overflow: hidden;
    }
    #msgApp #composeOverlay[style*="display: none"],
    #msgApp #composeOverlay[style*="display:none"] {
        display: none !important;
    }

    #msgApp .compose-header {
        padding: 1.1rem 1.5rem;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }
    #msgApp .btn-close-compose {
        background: none;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        padding: 0.4rem 1rem;
        border-radius: 8px;
        transition: all 0.2s;
    }
    #msgApp .btn-close-compose:hover { color: #ef4444; border-color: #fca5a5; background: #fef2f2; }

    #msgApp .compose-body {
        padding: 1.5rem;
        flex: 1;
        overflow-y: auto;
        background: #f8fafc;
    }
    #msgApp .compose-body .form-group { margin-bottom: 1.25rem; }
    #msgApp .compose-body label {
        display: block;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }
    #msgApp .compose-body .form-control,
    #msgApp .compose-body select {
        width: 100%;
        border-radius: 10px;
        border: 1.5px solid #cbd5e1;
        padding: 0.75rem 1rem;
        font-size: 0.93rem;
        color: #1e293b !important;
        background-color: #ffffff !important;
        transition: all 0.2s;
    }
    #msgApp .compose-body .form-control:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        outline: none;
    }

    /* Recipient Type Buttons */
    #msgApp .recipient-btns { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    #msgApp .recipient-btns .rbtn {
        padding: 0.5rem 1rem;
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        color: #475569;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    #msgApp .recipient-btns .rbtn:hover { border-color: #6366f1; color: #6366f1; background: #f5f3ff; }
    #msgApp .recipient-btns .rbtn.active {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
    }

    /* ===== Empty Threads State ===== */
    #msgApp .no-threads-msg {
        padding: 3rem 2rem;
        text-align: center;
        color: #94a3b8;
        font-size: 0.9rem;
    }
    #msgApp .no-threads-msg i {
        font-size: 2.5rem;
        color: #e2e8f0;
        display: block;
        margin-bottom: 1rem;
    }

    /* ===== Responsive ===== */
    @media (max-width: 992px) {
        #msgApp.chat-app {
            flex-direction: column !important;
            height: auto;
            min-height: calc(100vh - 140px);
        }
        #msgApp .chat-sidebar {
            width: 100% !important;
            min-width: 100% !important;
            max-height: 45vh;
            border-right: none;
            border-bottom: 1px solid #e2e8f0;
        }
        #msgApp .chat-main { min-height: 55vh; }
    }
    @media (max-width: 576px) {
        #msgApp .chat-sidebar { max-height: 35vh; }
        #msgApp .message-bubble { max-width: 85% !important; }
        #msgApp .compose-body { padding: 1rem; }
    }
</style>
@endpush

@section('content')
<div class="chat-app" id="msgApp">
    
    {{-- Sidebar (Threads) --}}
    <div class="chat-sidebar">
        <div class="chat-header">
            <h3 class="chat-title">&#x1F4AC; Messages</h3>
            <button class="compose-btn" onclick="showCompose()">
                <i class="flaticon-381-add-1"></i> New
            </button>
        </div>
        <div class="chat-search-bar">
            <div class="search-icon-wrapper">
                <i class="flaticon-381-search-1 search-icon"></i>
                <input type="text" class="chat-search-input" id="searchInput" placeholder="Search conversations..." onkeyup="renderThreads()">
            </div>
        </div>
        <div class="chat-list" id="threadList">
            <div class="no-threads-msg">
                <i class="flaticon-381-clock"></i>
                Loading conversations...
            </div>
        </div>
    </div>

    {{-- Main Chat Area --}}
    <div class="chat-main" id="mainArea">
        
        {{-- Empty State --}}
        <div id="emptyState" class="chat-empty-state">
            <div class="empty-icon">
                <i class="flaticon-381-speech-bubble"></i>
            </div>
            <h3>Select a conversation</h3>
            <p>Choose a contact from the list or compose a new message.</p>
        </div>

        {{-- Active Thread --}}
        <div id="activeThread" style="display:none; flex-direction: column; height: 100%;">
            <div class="chat-main-header">
                <div class="chat-avatar" id="activeAvatar"></div>
                <div>
                    <h3 class="chat-main-name" id="activeName"></h3>
                    <div class="chat-main-meta" id="activeRole"></div>
                </div>
            </div>
            
            <div class="chat-messages" id="chatMessages"></div>
            
            <div class="chat-input-area" id="replyFormContainer">
                <input type="text" class="reply-subject-input" id="quickSubject" placeholder="Subject (Optional)">
                <textarea class="reply-body-input" id="quickBody" rows="2" placeholder="Type your message..."></textarea>
                <div class="chat-input-actions">
                    <button class="btn-send" id="quickSendBtn" onclick="quickSend()">
                        <i class="flaticon-381-send"></i> Send
                    </button>
                </div>
            </div>
        </div>

        {{-- Compose Overlay --}}
        <div id="composeOverlay" style="display:none;">
            <div class="compose-header">
                <h3 class="chat-main-name">&#x270D;&#xFE0F; New Message</h3>
                <button class="btn-close-compose" onclick="hideCompose()">&#x2715; Cancel</button>
            </div>
            <div class="compose-body">
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
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" class="form-control" id="composeSubject" placeholder="Enter subject...">
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea class="form-control" id="composeBody" rows="6" placeholder="Type your message here..."></textarea>
                </div>
                <div style="text-align: right; margin-top: 1.5rem;">
                    <button class="btn-send" id="composeSendBtn" onclick="sendCompose()" style="display: inline-flex;">
                        <i class="flaticon-381-send"></i> Send Message
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    const API = '{{ url("/api") }}';
    const TOKEN = '{{ $token }}';
    const CSRF_TOKEN = '{{ csrf_token() }}';
    const authHeaders = { 
        'Authorization': 'Bearer ' + TOKEN, 
        'Accept': 'application/json', 
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN
    };

    let allMessages = [];
    let sentMessages = [];
    let threads = {}; // Processed grouped messages
    let activeThreadId = null;
    let recipientsData = {};
    let composeType = 'individual';

    // Data Fetching
    async function initData() {
        try {
            const fetchLog = async (url) => {
                const r = await fetch(url, { headers: authHeaders });
                if (!r.ok) {
                    console.error("API Error at " + url, r.status, await r.text());
                    return {};
                }
                return await r.json();
            };

            await Promise.all([
                fetchLog(API + '/messages').then(d => { allMessages = d.messages || []; }),
                fetchLog(API + '/messages/sent').then(d => { sentMessages = d.messages || []; }),
                fetchLog(API + '/messages/recipients').then(d => { recipientsData = d || {}; })
            ]);
        } catch(e) {
            console.error("InitData Promise failed", e);
        }
        processData();
    }

    function processData() {
        threads = {};
        const processMsg = (msg, dir) => {
            let pId, pName, pRole, isGroup = false;

            if (dir === 'out' && msg.is_broadcast) {
                pId = 'broadcast_' + msg.receiver_role;
                pName = getBroadcastLabel(msg.receiver_role);
                pRole = 'broadcast';
                isGroup = true;
            } else if (dir === 'in' && msg.is_broadcast) {
                const p = msg.sender;
                if (!p) return;
                pId = String(p.id);
                pName = p.name;
                pRole = p.role;
            } else {
                const p = dir === 'in' ? msg.sender : msg.receiver;
                if (!p) return;
                pId = String(p.id);
                pName = p.name;
                pRole = p.role;
            }

            if (!threads[pId]) {
                threads[pId] = { 
                    id: pId, name: pName, role: pRole, isGroup, 
                    messages: [], unread: 0, updated_at: msg.created_at,
                    recipientId: dir === 'in' ? msg.sender?.id : msg.receiver?.id
                };
            }
            if (dir === 'in' && !msg.is_read) threads[pId].unread++;
            threads[pId].messages.push({ ...msg, dir });
            if (new Date(msg.created_at) > new Date(threads[pId].updated_at)) {
                threads[pId].updated_at = msg.created_at;
            }
        };

        allMessages.forEach(m => processMsg(m, 'in'));
        sentMessages.forEach(m => processMsg(m, 'out'));

        Object.values(threads).forEach(t => {
            t.messages.sort((a,b) => new Date(a.created_at) - new Date(b.created_at));
        });

        renderThreads();
        renderComposeOptions();
        
        // Refresh active thread if exists
        if (activeThreadId && threads[activeThreadId]) {
            openThread(activeThreadId);
        } else {
            document.getElementById('emptyState').style.display = 'flex';
            document.getElementById('activeThread').style.display = 'none';
        }
    }

    function getBroadcastLabel(role) {
        if (role === 'all_students') return 'All Students';
        if (role === 'all_teachers') return 'All Teachers';
        if (role === 'admin') return 'Admin';
        if (role && role.startsWith('grade_')) return 'Grade ' + role.replace('grade_', '');
        return role || '';
    }

    // Get role badge class
    function getRoleBadgeClass(role) {
        const map = { admin: 'badge-admin', teacher: 'badge-teacher', user: 'badge-student', broadcast: 'badge-broadcast' };
        return map[role] || 'badge-student';
    }

    // UI Rendering
    function renderThreads() {
        const list = document.getElementById('threadList');
        const search = document.getElementById('searchInput').value.toLowerCase();
        
        let tArr = Object.values(threads).sort((a,b) => new Date(b.updated_at) - new Date(a.updated_at));
        
        if (search) {
            tArr = tArr.filter(t => t.name.toLowerCase().includes(search) || t.messages.some(m => m.subject?.toLowerCase().includes(search) || m.body?.toLowerCase().includes(search)));
        }

        if (tArr.length === 0) {
            list.innerHTML = `<div class="no-threads-msg">
                <i class="flaticon-381-speech-bubble"></i>
                ${search ? 'No matching conversations.' : 'No conversations yet.<br><small>Click "New" to start messaging.</small>'}
            </div>`;
            return;
        }

        const roleMap = { admin: 'Admin', teacher: 'Teacher', user: 'Student', broadcast: 'Broadcast' };

        list.innerHTML = tArr.map(t => {
            const lastMsg = t.messages[t.messages.length - 1];
            const timeStr = new Date(t.updated_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
            const badgeClass = getRoleBadgeClass(t.role);
            return `
            <div class="chat-thread-item ${t.id === activeThreadId ? 'active' : ''}" onclick="openThread('${t.id}')">
                <div class="chat-avatar ${t.isGroup ? 'group' : ''}">
                    ${(t.name || '?').charAt(0).toUpperCase()}
                    ${t.unread > 0 ? `<div class="unread-badge">${t.unread}</div>` : ''}
                </div>
                <div class="chat-thread-info">
                    <div class="chat-thread-top">
                        <span class="chat-thread-name">${t.name} <span class="role-badge ${badgeClass}">${roleMap[t.role] || t.role}</span></span>
                        <span class="chat-thread-time">${timeStr}</span>
                    </div>
                    <div class="chat-thread-preview">${lastMsg.dir === 'out' ? 'You: ' : ''}${lastMsg.subject ? '['+lastMsg.subject+'] ' : ''}${lastMsg.body}</div>
                </div>
            </div>`;
        }).join('');
    }

    function openThread(id) {
        activeThreadId = id;
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('composeOverlay').style.display = 'none';
        const activeEl = document.getElementById('activeThread');
        activeEl.style.display = 'flex';
        renderThreads(); // Update active class
        
        const t = threads[id];
        document.getElementById('activeAvatar').className = `chat-avatar ${t.isGroup ? 'group' : ''}`;
        document.getElementById('activeAvatar').textContent = (t.name || '?').charAt(0).toUpperCase();
        document.getElementById('activeName').textContent = t.name;
        
        const roleMap = { admin: 'Admin', teacher: 'Teacher', user: 'Student', broadcast: 'Broadcast Group' };
        document.getElementById('activeRole').textContent = roleMap[t.role] || t.role;

        // Render messages
        const msgContainer = document.getElementById('chatMessages');
        msgContainer.innerHTML = t.messages.map(m => `
            <div class="message-row ${m.dir === 'in' ? 'received' : 'sent'}">
                <div class="message-bubble">
                    ${m.subject ? `<div class="message-subject">${m.subject}</div>` : ''}
                    <div class="message-text">${m.body}</div>
                    <span class="message-time">${new Date(m.created_at).toLocaleString([], {hour: '2-digit', minute:'2-digit', day:'2-digit', month:'short'})}</span>
                </div>
            </div>
        `).join('');
        
        msgContainer.scrollTop = msgContainer.scrollHeight; // Scroll to bottom

        // Fill Reply form slightly smartly
        const lastInMsg = t.messages.slice().reverse().find(m => m.dir === 'in');
        if (lastInMsg && lastInMsg.subject) {
            document.getElementById('quickSubject').value = lastInMsg.subject.startsWith('Re:') ? lastInMsg.subject : 'Re: ' + lastInMsg.subject;
        } else {
            document.getElementById('quickSubject').value = '';
        }

        // Mark as read API
        t.messages.forEach(m => {
            if (m.dir === 'in' && !m.is_read) markRead(m.id);
        });
    }

    // Sending Logic
    async function quickSend() {
        if (!activeThreadId) return;
        const t = threads[activeThreadId];
        const subject = document.getElementById('quickSubject').value.trim();
        const body = document.getElementById('quickBody').value.trim();
        if (!body) {
            if (typeof toastr !== 'undefined') toastr.warning("Please enter a message!");
            else alert("Please enter a message!");
            return;
        }

        const btn = document.getElementById('quickSendBtn');
        btn.disabled = true;

        const payload = { subject, body };
        if (t.isGroup) {
            const roleStr = t.id.replace('broadcast_', '');
            if (roleStr.startsWith('grade_')) {
                payload.recipient_type = 'grade';
                payload.grade = roleStr.replace('grade_', '');
            } else {
                payload.recipient_type = roleStr; // e.g. all_teachers
            }
        } else {
            payload.recipient_type = 'individual';
            payload.recipient_id = t.recipientId;
        }

        try {
            const res = await fetch(API + '/messages', { method: 'POST', headers: authHeaders, body: JSON.stringify(payload) });
            if (res.ok) {
                document.getElementById('quickBody').value = '';
                if (typeof toastr !== 'undefined') toastr.success("Message sent!");
                await initData(); // Refresh Data
            } else {
                const err = await res.json().catch(() => ({}));
                if (typeof toastr !== 'undefined') toastr.error(err.message || "Failed to send message");
                else alert("Failed to send message");
            }
        } catch(e) {
            console.error("quickSend error:", e);
        }
        btn.disabled = false;
    }

    async function sendCompose() {
        const subject = document.getElementById('composeSubject').value.trim();
        const body = document.getElementById('composeBody').value.trim();
        if (!body) {
            if (typeof toastr !== 'undefined') toastr.warning('Please enter a message!');
            else alert('Please enter a message!');
            return;
        }
        
        let payload = { subject, body, recipient_type: composeType };
        if (composeType === 'individual') {
            payload.recipient_id = document.getElementById('recipientId').value;
            if (!payload.recipient_id) {
                if (typeof toastr !== 'undefined') toastr.warning('Select a recipient.');
                else alert('Select a recipient.');
                return;
            }
        } else if (composeType === 'grade') {
            payload.grade = document.getElementById('gradeId').value;
            if (!payload.grade) {
                if (typeof toastr !== 'undefined') toastr.warning('Select a grade.');
                else alert('Select a grade.');
                return;
            }
        }

        const btn = document.getElementById('composeSendBtn');
        btn.disabled = true;
        try {
            const res = await fetch(API + '/messages', { method: 'POST', headers: authHeaders, body: JSON.stringify(payload) });
            if (res.ok) {
                document.getElementById('composeSubject').value = '';
                document.getElementById('composeBody').value = '';
                hideCompose();
                if (typeof toastr !== 'undefined') toastr.success("Message sent successfully!");
                await initData();
            } else {
                const err = await res.json().catch(() => ({}));
                if (typeof toastr !== 'undefined') toastr.error(err.message || "Failed to send");
                else alert(err.message || "Failed to send");
            }
        } catch(e) {
            console.error("sendCompose error:", e);
        }
        btn.disabled = false;
    }

    // Compose Features
    function showCompose() {
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('activeThread').style.display = 'none';
        document.getElementById('composeOverlay').style.display = 'flex';
    }
    function hideCompose() {
        document.getElementById('composeOverlay').style.display = 'none';
        if (activeThreadId && threads[activeThreadId]) {
            document.getElementById('activeThread').style.display = 'flex';
            document.getElementById('emptyState').style.display = 'none';
        } else {
            document.getElementById('emptyState').style.display = 'flex';
        }
    }

    function renderComposeOptions() {
        const btns = document.getElementById('recipientBtns');
        let html = '<button class="rbtn active" data-type="individual" onclick="setComposeType(\'individual\')">&#x1F464; Individual</button>';
        (recipientsData.broadcast || []).forEach(opt => {
            if (opt.value.startsWith('grade_')) return;
            html += `<button class="rbtn" data-type="${opt.value}" onclick="setComposeType('${opt.value}')">${opt.label}</button>`;
        });
        const hasGrades = (recipientsData.broadcast || []).some(o => o.value.startsWith('grade_'));
        if (hasGrades) html += '<button class="rbtn" data-type="grade" onclick="setComposeType(\'grade\')">&#x1F4DA; By Grade</button>';
        btns.innerHTML = html;

        // Dropdowns
        const sel = document.getElementById('recipientId');
        sel.innerHTML = '<option value="">-- Select --</option>';
        (recipientsData.teachers || []).forEach(t => sel.innerHTML += `<option value="${t.id}">${t.name} (Teacher) &mdash; ${t.email}</option>`);
        (recipientsData.students || []).forEach(s => sel.innerHTML += `<option value="${s.id}">${s.name} (Student) &mdash; ${s.email}</option>`);

        const gSel = document.getElementById('gradeId');
        gSel.innerHTML = '<option value="">-- Select Grade --</option>';
        (recipientsData.broadcast || []).filter(o => o.value.startsWith('grade_')).forEach(g => {
            gSel.innerHTML += `<option value="${g.value.replace('grade_', '')}">${g.label}</option>`;
        });
    }

    window.setComposeType = function(type) {
        composeType = type;
        document.querySelectorAll('#msgApp .recipient-btns .rbtn').forEach(b => b.classList.toggle('active', b.dataset.type === type));
        document.getElementById('individualSelect').style.display = type === 'individual' ? 'block' : 'none';
        document.getElementById('gradeSelect').style.display = type === 'grade' ? 'block' : 'none';
    };

    async function markRead(id) {
        try { 
            await fetch(API + '/messages/' + id + '/read', { method: 'POST', headers: authHeaders }); 
        } catch(e) {
            console.error("markRead error:", e);
        }
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', initData);
</script>
@endpush

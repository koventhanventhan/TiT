/* ===== Admin Global Search ===== */
(function () {
    let debounceTimer;
    const searchInput = document.querySelector('.search_bar input[type="search"]');
    if (!searchInput) return;

    // Prevent form submission (default reload)
    const form = searchInput.closest('form');
    if (form) form.addEventListener('submit', function (e) { e.preventDefault(); });

    // Create results dropdown with premium styles
    const dropdown = document.createElement('div');
    dropdown.id = 'adminSearchDropdown';
    Object.assign(dropdown.style, {
        position: 'absolute', top: '110%', left: '0', right: '0',
        background: 'rgba(30, 41, 59, 0.95)', 
        backdropFilter: 'blur(10px)',
        borderRadius: '12px',
        border: '1px solid rgba(255, 255, 255, 0.1)',
        boxShadow: '0 20px 40px rgba(0,0,0,0.4)', zIndex: '99999',
        maxHeight: '450px', overflowY: 'auto', display: 'none',
        padding: '8px 0',
        transition: 'all 0.2s ease'
    });
    searchInput.closest('.search_bar').style.position = 'relative';
    searchInput.closest('.search_bar').appendChild(dropdown);

    // Inject styles for items
    const style = document.createElement('style');
    style.textContent = `
        #adminSearchDropdown .sg-label { 
            padding: 12px 16px 6px; 
            font-size: 10px; 
            font-weight: 800; 
            color: #6366f1; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
        }
        #adminSearchDropdown .sg-item { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 10px 16px; 
            text-decoration: none; 
            color: #e2e8f0; 
            cursor: pointer; 
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        #adminSearchDropdown .sg-item:hover { 
            background: rgba(99, 102, 241, 0.1); 
            color: #fff; 
            text-decoration: none; 
            border-left-color: #6366f1;
        }
        #adminSearchDropdown .sg-icon { 
            font-size: 18px; 
            flex-shrink: 0; 
            width: 36px; 
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
        }
        #adminSearchDropdown .sg-text { display: flex; flex-direction: column; min-width: 0; flex: 1; }
        #adminSearchDropdown .sg-name { font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
        #adminSearchDropdown .sg-name mark { background: rgba(99, 102, 241, 0.4); color: #fff; padding: 0 2px; border-radius: 2px; }
        #adminSearchDropdown .sg-desc { font-size: 12px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #adminSearchDropdown .sg-empty { padding: 24px; text-align: center; color: #94a3b8; font-size: 14px; }
        @media(max-width:768px) { 
            #adminSearchDropdown { 
                position: fixed!important; top: 70px!important; left: 15px!important; right: 15px!important; 
                max-height: 70vh; border-radius: 12px; 
            } 
        }
    `;
    document.head.appendChild(style);

    // Debounced search
    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        if (q.length < 1) { dropdown.innerHTML = ''; dropdown.style.display = 'none'; return; }
        debounceTimer = setTimeout(() => doSearch(q), 250);
    });

    // Close on click outside
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.search_bar')) { dropdown.style.display = 'none'; }
    });

    // Focus
    searchInput.addEventListener('focus', function () {
        if (dropdown.innerHTML.trim()) dropdown.style.display = 'block';
    });

    // Enter key goes to first result
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const first = dropdown.querySelector('.sg-item');
            if (first) window.location.href = first.href;
        }
    });

    async function doSearch(q) {
        try {
            const res = await fetch('/admin/search?q=' + encodeURIComponent(q), { credentials: 'same-origin' });
            const data = await res.json();
            renderResults(data.results || [], q);
        } catch (e) {
            dropdown.innerHTML = '<div class="sg-empty">Search error</div>';
            dropdown.style.display = 'block';
        }
    }

    function renderResults(results, query) {
        if (results.length === 0) {
            dropdown.innerHTML = '<div class="sg-empty">No results for "' + escHtml(query) + '"</div>';
            dropdown.style.display = 'block';
            return;
        }

        // Group by type
        const groups = {};
        results.forEach(r => { if (!groups[r.type]) groups[r.type] = []; groups[r.type].push(r); });

        const labels = { 
            student: '🎓 Students', 
            teacher: '👨‍🏫 Teachers', 
            subject: '📚 Subjects', 
            zoom: '📹 Zoom Classes',
            payment: '💳 Payments',
            message: '✉️ Messages'
        };
        
        let html = '';
        for (const [type, items] of Object.entries(groups)) {
            html += '<div class="sg-label">' + (labels[type] || type) + '</div>';
            items.forEach(item => {
                html += '<a href="' + item.url + '" class="sg-item">' +
                    '<span class="sg-icon">' + item.icon + '</span>' +
                    '<div class="sg-text"><span class="sg-name">' + highlight(item.name, query) + '</span>' +
                    '<span class="sg-desc">' + (item.desc || '') + '</span></div></a>';
            });
        }
        dropdown.innerHTML = html;
        dropdown.style.display = 'block';
    }

    function highlight(text, query) {
        if (!text) return '';
        const regex = new RegExp('(' + escRegex(query) + ')', 'gi');
        return escHtml(text).replace(regex, '<mark>$1</mark>');
    }
    function escHtml(s) { return (s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
    function escRegex(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
})();

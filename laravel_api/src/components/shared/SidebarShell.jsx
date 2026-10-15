import React, { useState, useEffect, useRef, useCallback } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { FiX, FiChevronRight, FiLogOut } from 'react-icons/fi';
import './sidebar-shell.css';

/**
 * SidebarShell
 * Responsive Icon Rail (collapsed by default on all screen sizes).
 *
 * **Mouse hover (pointer:fine devices):**
 *   pointerenter on the rail → opens expanded sidebar after ~70ms intent delay.
 *   No backdrop; drawer overlays. Combined hover zone prevents flicker.
 *   pointerleave → closes after ~150ms grace.
 *   After navigating, a ~300ms suppress prevents instant re-open.
 *
 * **Click / Tap (all devices):**
 *   Click/tap on any rail icon → opens overlay with dimming backdrop.
 *   Click a labelled item → navigate + collapse. Backdrop / X / Escape close.
 *
 * @param {string} brandSubtitle - "Student Portal" | "Teacher Portal"
 * @param {Array<{name: string, path: string, icon: React.ComponentType}>} items
 * @param {object} user - Current user object
 * @param {function} [onLogout] - Optional logout handler
 * @param {string} [avatarUrl] - Optional avatar URL override
 */
export default function SidebarShell({
    brandSubtitle = 'Portal',
    items = [],
    user = null,
    onLogout,
    avatarUrl: propAvatarUrl
}) {
    const location = useLocation();
    const [isExpanded, setIsExpanded] = useState(false);
    // 'touch' | 'hover' | null — tracks HOW the drawer was opened
    const [openMode, setOpenMode] = useState(null);

    const logoBtnRef = useRef(null);
    const closeBtnRef = useRef(null);
    const lastTriggerDataRailRef = useRef(null);
    const prevExpandedRef = useRef(false);
    const openTimeRef = useRef(0);
    const prevPathnameRef = useRef(location.pathname);

    // Hover timers
    const hoverIntentTimerRef = useRef(null);
    const hoverLeaveTimerRef = useRef(null);
    // After navigating, suppress hover re-open for ~300ms
    const suppressHoverRef = useRef(false);
    const suppressTimerRef = useRef(null);

    // ── Open / Close helpers ──────────────────────────────────────────────

    // Opens in "touch" mode (with backdrop)
    const openSidebarTouch = useCallback((e, railId = 'logo') => {
        if (e) e.stopPropagation();
        // Clear any pending hover timers
        clearTimeout(hoverIntentTimerRef.current);
        hoverIntentTimerRef.current = null;
        clearTimeout(hoverLeaveTimerRef.current);
        hoverLeaveTimerRef.current = null;
        openTimeRef.current = Date.now();
        lastTriggerDataRailRef.current = railId;
        setOpenMode('touch');
        setIsExpanded(true);
    }, []);

    // Opens in "hover" mode (no backdrop, positioned next to rail)
    const openSidebarHover = useCallback(() => {
        if (suppressHoverRef.current) return;
        clearTimeout(hoverIntentTimerRef.current);
        hoverIntentTimerRef.current = null;
        openTimeRef.current = Date.now();
        lastTriggerDataRailRef.current = 'hover';
        setOpenMode('hover');
        setIsExpanded(true);
    }, []);

    const closeSidebar = useCallback(() => {
        clearTimeout(hoverIntentTimerRef.current);
        hoverIntentTimerRef.current = null;
        clearTimeout(hoverLeaveTimerRef.current);
        hoverLeaveTimerRef.current = null;
        setIsExpanded(false);
        setOpenMode(null);
    }, []);

    const closeSidebarAfterNav = useCallback(() => {
        closeSidebar();
        // Suppress hover re-open for 300ms
        suppressHoverRef.current = true;
        clearTimeout(suppressTimerRef.current);
        suppressTimerRef.current = setTimeout(() => {
            suppressHoverRef.current = false;
        }, 300);
    }, [closeSidebar]);

    // ── Hover handlers (mouse only, only when not already open via touch) ─

    const handleZonePointerEnter = useCallback((e) => {
        if (e.pointerType !== 'mouse') return;
        clearTimeout(hoverLeaveTimerRef.current);
        hoverLeaveTimerRef.current = null;
        // If already open via touch click, don't interfere
        if (isExpanded && openMode === 'touch') return;
        if (!isExpanded && !suppressHoverRef.current) {
            clearTimeout(hoverIntentTimerRef.current);
            hoverIntentTimerRef.current = setTimeout(() => {
                hoverIntentTimerRef.current = null;
                openSidebarHover();
            }, 70);
        }
    }, [isExpanded, openMode, openSidebarHover]);

    const handleZonePointerMove = useCallback((e) => {
        if (e.pointerType !== 'mouse') return;
        clearTimeout(hoverLeaveTimerRef.current);
        hoverLeaveTimerRef.current = null;
        if (isExpanded || openMode === 'touch' || suppressHoverRef.current) return;
        if (!hoverIntentTimerRef.current) {
            hoverIntentTimerRef.current = setTimeout(() => {
                hoverIntentTimerRef.current = null;
                openSidebarHover();
            }, 70);
        }
    }, [isExpanded, openMode, openSidebarHover]);

    const handleZonePointerLeave = useCallback((e) => {
        if (e.pointerType !== 'mouse') return;
        clearTimeout(hoverIntentTimerRef.current);
        hoverIntentTimerRef.current = null;
        // Only auto-close if opened via hover (not via click/touch)
        if (isExpanded && openMode === 'hover') {
            hoverLeaveTimerRef.current = setTimeout(() => {
                closeSidebar();
            }, 150);
        }
    }, [isExpanded, openMode, closeSidebar]);

    // ── Click/Tap handlers (on rail — always opens in touch mode) ────────

    const handleRailPointerDown = useCallback((e, railId) => {
        // If hover drawer is already open, upgrade to touch mode on click
        if (isExpanded && openMode === 'hover') {
            e.stopPropagation();
            clearTimeout(hoverLeaveTimerRef.current);
            hoverLeaveTimerRef.current = null;
            openTimeRef.current = Date.now();
            lastTriggerDataRailRef.current = railId;
            setOpenMode('touch');
            return;
        }
        openSidebarTouch(e, railId);
    }, [isExpanded, openMode, openSidebarTouch]);

    const handleRailClick = useCallback((e, railId) => {
        // If already expanded (either mode), don't re-open
        if (isExpanded) {
            e.stopPropagation();
            return;
        }
        openSidebarTouch(e, railId);
    }, [isExpanded, openSidebarTouch]);

    // Keyboard support: Enter or Space on rail buttons
    const handleRailKeyDown = (e, railId) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openSidebarTouch(e, railId);
        }
    };

    // ── Auto-collapse on route change ────────────────────────────────────
    useEffect(() => {
        if (prevPathnameRef.current !== location.pathname) {
            prevPathnameRef.current = location.pathname;
            closeSidebarAfterNav();
        }
    }, [location.pathname, closeSidebarAfterNav]);

    // ── Escape key ───────────────────────────────────────────────────────
    useEffect(() => {
        if (!isExpanded) return;
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') closeSidebar();
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isExpanded, closeSidebar]);

    // ── Lock body scroll while TOUCH overlay is open ─────────────────────
    useEffect(() => {
        if (isExpanded && openMode === 'touch') {
            const originalOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            return () => {
                document.body.style.overflow = originalOverflow;
            };
        }
    }, [isExpanded, openMode]);

    // ── Focus management ─────────────────────────────────────────────────
    useEffect(() => {
        if (prevExpandedRef.current !== isExpanded) {
            if (isExpanded && openMode === 'touch') {
                requestAnimationFrame(() => {
                    closeBtnRef.current?.focus();
                });
            } else if (prevExpandedRef.current) {
                // NEVER programmatically focus anything after mouse hover closes
                if (lastTriggerDataRailRef.current && lastTriggerDataRailRef.current !== 'hover') {
                    requestAnimationFrame(() => {
                        const railId = lastTriggerDataRailRef.current;
                        const targetBtn = document.querySelector(`button[data-rail="${railId}"]`);
                        if (targetBtn) {
                            targetBtn.focus();
                        } else {
                            logoBtnRef.current?.focus();
                        }
                    });
                }
            }
            prevExpandedRef.current = isExpanded;
        }
    }, [isExpanded, openMode]);

    // ── Cleanup timers on unmount ────────────────────────────────────────
    useEffect(() => {
        return () => {
            clearTimeout(hoverIntentTimerRef.current);
            clearTimeout(hoverLeaveTimerRef.current);
            clearTimeout(suppressTimerRef.current);
        };
    }, []);

    // ── User display ─────────────────────────────────────────────────────
    const displayName = user?.full_name || user?.name || user?.username || (brandSubtitle.includes('Student') ? 'Student' : 'Teacher');
    const initial = displayName.charAt(0).toUpperCase();

    const getAvatarUrl = () => {
        if (propAvatarUrl) return propAvatarUrl;
        if (user?.avatar) {
            if (user.avatar.startsWith('http')) return user.avatar;
            const baseUrl = (import.meta.env.VITE_API_URL || '').replace(/\/api$/, '');
            return baseUrl ? `${baseUrl}${user.avatar}` : user.avatar;
        }
        return null;
    };
    const resolvedAvatarUrl = getAvatarUrl();
    const displayRole = brandSubtitle.includes('Student') ? 'Student' : 'Instructor';

    // ── Item click (inside expanded drawer) ──────────────────────────────
    const handleItemClick = (e) => {
        // Guard against accidental double-tap during touch overlay slide-in
        if (openMode === 'touch' && Date.now() - openTimeRef.current < 150) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }
        closeSidebarAfterNav();
    };

    const handleLogout = (e) => {
        if (Date.now() - openTimeRef.current < 200) {
            e.preventDefault();
            return;
        }
        if (onLogout) {
            onLogout(e);
        } else {
            window.location.href = '/?logout=1';
        }
    };

    // ── Active route detection ───────────────────────────────────────────
    const isItemActive = (item) => {
        if (location.pathname === item.path) return true;
        if (item.path === '/student/dashboard' && (location.pathname === '/student' || location.pathname === '/student/overview')) return true;
        if (item.path === '/teacher/dashboard' && (location.pathname === '/teacher' || location.pathname === '/teacher/overview')) return true;
        return false;
    };

    // ── Mode flags ───────────────────────────────────────────────────────
    const isHoverMode = isExpanded && openMode === 'hover';
    const isTouchMode = isExpanded && openMode === 'touch';

    // ── Shared expanded content renderer ─────────────────────────────────
    const renderExpandedContent = (isHover) => (
        <div className="sidebar-expanded-container">
            {/* Header */}
            <div className="sidebar-expanded-header">
                <button
                    type="button"
                    className="sidebar-header-brand"
                    onClick={closeSidebar}
                    title="Collapse sidebar"
                    aria-label="TiT Education"
                >
                    <div className="sidebar-header-logo-col">
                        <div className="sidebar-logo-tile">
                            TiT
                        </div>
                    </div>
                    <div className="sidebar-brand-text">
                        <div className="sidebar-brand-name">
                            TiT<span className="sidebar-brand-accent">Education</span>
                        </div>
                        <div className="sidebar-brand-subtitle">{brandSubtitle}</div>
                    </div>
                </button>
                {!isHover && (
                    <button
                        ref={closeBtnRef}
                        type="button"
                        onClick={closeSidebar}
                        className="sidebar-close-btn t-icon-btn"
                        aria-label="Close sidebar"
                    >
                        <FiX />
                    </button>
                )}
            </div>

            {/* Gap between header and first nav item */}
            <div className="sidebar-nav-gap" aria-hidden="true" />

            {/* Nav Items */}
            <nav className="sidebar-expanded-nav hide-scrollbar" aria-label="Expanded menu">
                {items.map((item) => {
                    const Icon = item.icon;
                    const active = isItemActive(item);
                    return (
                        <NavLink
                            key={item.name}
                            to={item.path}
                            onClick={handleItemClick}
                            className={`t-tap t-menu-item sidebar-nav-item${active ? ' active' : ''}`}
                        >
                            <div className="sidebar-nav-icon-col">
                                <Icon className="sidebar-nav-icon" />
                            </div>
                            <span className="sidebar-nav-label">{item.name}</span>
                            {active && <FiChevronRight className="sidebar-nav-chevron" />}
                        </NavLink>
                    );
                })}
            </nav>

            {/* User Card & Logout */}
            <div className="sidebar-user-card">
                <div className="sidebar-user-info">
                    <div className="sidebar-user-avatar">
                        {resolvedAvatarUrl ? (
                            <img width="36" height="36" src={resolvedAvatarUrl} alt="Avatar" />
                        ) : (
                            initial
                        )}
                    </div>
                    <div style={{ minWidth: 0, flex: 1 }}>
                        <div className="sidebar-user-name">{displayName}</div>
                        <div className="sidebar-user-role">{displayRole}</div>
                    </div>
                </div>
                <button
                    type="button"
                    onClick={handleLogout}
                    aria-label="Logout"
                    className="sidebar-logout-btn t-btn"
                >
                    <FiLogOut style={{ fontSize: 16, flexShrink: 0 }} />
                    <span>Logout</span>
                </button>
            </div>
        </div>
    );

    return (
        <>
            {/* ══════════════════════════════════════════════════════════════
                Wrapper: combined hover zone for rail + hover-drawer.
                Hover zone wraps both so moving the mouse from rail to
                expanded drawer never fires a leave event.
                ══════════════════════════════════════════════════════════════ */}
            <div
                className={`sidebar-hover-zone${isHoverMode ? ' is-hover-expanded' : ''}`}
                onPointerEnter={handleZonePointerEnter}
                onPointerMove={handleZonePointerMove}
                onPointerLeave={handleZonePointerLeave}
            >
                {/* ──────────────────────────────────────────────────────────
                    1. Rail (in touch overlay mode, placeholder preserves width;
                       in collapsed or hover mode, rail is interactive)
                    ────────────────────────────────────────────────────────── */}
                {isTouchMode ? (
                    <div className="sidebar-rail-placeholder" aria-hidden="true" />
                ) : (
                    <aside
                        className={`sidebar-rail${isExpanded ? ' rail-behind-drawer' : ''}`}
                        aria-label="Sidebar navigation"
                        onPointerDown={(e) => handleRailPointerDown(e, 'empty')}
                        onClick={(e) => handleRailClick(e, 'empty')}
                    >
                        {/* Top Logo Button */}
                        <button
                            ref={logoBtnRef}
                            type="button"
                            className="sidebar-rail-logo-btn"
                            title="TiT Education"
                            aria-label="Open menu"
                            aria-expanded={isExpanded}
                            aria-controls="expanded-sidebar-drawer"
                            data-rail="logo"
                            onPointerDown={(e) => handleRailPointerDown(e, 'logo')}
                            onClick={(e) => handleRailClick(e, 'logo')}
                            onKeyDown={(e) => handleRailKeyDown(e, 'logo')}
                        >
                            <div className="sidebar-logo-tile">TiT</div>
                        </button>

                        {/* Spacer matching --sb-nav-gap for vertical alignment */}
                        <div className="sidebar-rail-gap" aria-hidden="true" />

                        {/* Nav Item Icon Buttons */}
                        <nav className="sidebar-rail-nav hide-scrollbar" aria-label="Rail navigation">
                            {items.map((item) => {
                                const Icon = item.icon;
                                const active = isItemActive(item);
                                const railId = item.name.toLowerCase().replace(/\s+/g, '-');
                                return (
                                    <button
                                        key={item.name}
                                        type="button"
                                        title={item.name}
                                        aria-label="Open menu"
                                        aria-expanded={isExpanded}
                                        aria-controls="expanded-sidebar-drawer"
                                        data-rail={railId}
                                        data-title={item.name}
                                        onPointerDown={(e) => handleRailPointerDown(e, railId)}
                                        onClick={(e) => handleRailClick(e, railId)}
                                        onKeyDown={(e) => handleRailKeyDown(e, railId)}
                                        className={`sidebar-rail-item t-tap${active ? ' active' : ''}`}
                                    >
                                        <Icon className="sidebar-rail-icon" />
                                    </button>
                                );
                            })}
                        </nav>

                        {/* Bottom Pinned Logout Icon */}
                        <div className="sidebar-rail-bottom">
                            <button
                                type="button"
                                title="Logout"
                                aria-label="Open menu"
                                aria-expanded={isExpanded}
                                aria-controls="expanded-sidebar-drawer"
                                data-rail="logout"
                                onPointerDown={(e) => handleRailPointerDown(e, 'logout')}
                                onClick={(e) => handleRailClick(e, 'logout')}
                                onKeyDown={(e) => handleRailKeyDown(e, 'logout')}
                                className="sidebar-rail-logout t-btn"
                            >
                                <FiLogOut style={{ fontSize: 18 }} />
                            </button>
                        </div>
                    </aside>
                )}

                {/* ──────────────────────────────────────────────────────────
                    2. Hover Drawer (no backdrop, inside hover zone)
                    ────────────────────────────────────────────────────────── */}
                {isHoverMode && (
                    <aside
                        id="expanded-sidebar-drawer"
                        className="sidebar-drawer is-open hover-drawer"
                        aria-label="Main menu"
                    >
                        {renderExpandedContent(true)}
                    </aside>
                )}
            </div>

            {/* ══════════════════════════════════════════════════════════════
                3. Touch / Click Overlay (with backdrop — outside hover zone)
                ══════════════════════════════════════════════════════════════ */}
            {isTouchMode && (
                <>
                    {/* Backdrop */}
                    <div
                        className="sidebar-backdrop"
                        onPointerDown={closeSidebar}
                        onClick={closeSidebar}
                        aria-label="Close sidebar overlay"
                        role="button"
                        tabIndex={-1}
                    />

                    {/* Full Drawer */}
                    <aside
                        id="expanded-sidebar-drawer"
                        className="sidebar-drawer is-open touch-drawer"
                        aria-label="Main menu"
                    >
                        {renderExpandedContent(false)}
                    </aside>
                </>
            )}
        </>
    );
}

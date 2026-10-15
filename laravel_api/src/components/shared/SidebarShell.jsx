import React, { useState, useEffect, useRef, useCallback } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { FiX, FiChevronRight, FiLogOut } from 'react-icons/fi';
import './sidebar-shell.css';

/**
 * SidebarShell
 * Responsive Icon Rail (collapsed by default on all screen sizes)
 * Tapping ANY icon or empty rail area opens the full expanded sidebar overlay.
 * Overlay behaves identically on mobile, tablet, and desktop (no content push).
 *
 * @param {string} brandSubtitle - "Student Portal" | "Teacher Portal"
 * @param {Array<{name: string, path: string, icon: React.ComponentType}>} items - Navigation menu items
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

    const logoBtnRef = useRef(null);
    const closeBtnRef = useRef(null);
    const lastTriggerDataRailRef = useRef(null);
    const prevExpandedRef = useRef(false);
    const openTimeRef = useRef(0);

    // Expand sidebar (idempotent; records trigger rail id)
    const openSidebar = useCallback((e, railId = 'logo') => {
        if (e) {
            e.stopPropagation();
        }
        openTimeRef.current = Date.now();
        lastTriggerDataRailRef.current = railId;
        setIsExpanded(true);
    }, []);

    // Close expanded sidebar
    const closeSidebar = useCallback(() => {
        setIsExpanded(false);
    }, []);

    // Keyboard support: Enter or Space on rail buttons
    const handleRailKeyDown = (e, railId) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openSidebar(e, railId);
        }
    };

    // Auto-collapse immediately on route change
    useEffect(() => {
        closeSidebar();
    }, [location.pathname, closeSidebar]);

    // Handle Escape key
    useEffect(() => {
        if (!isExpanded) return;

        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                closeSidebar();
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isExpanded, closeSidebar]);

    // Lock body scroll while overlay is open
    useEffect(() => {
        if (isExpanded) {
            const originalOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            return () => {
                document.body.style.overflow = originalOverflow;
            };
        }
    }, [isExpanded]);

    // Accessibility focus management:
    // Move focus to close button when opened, and back to trigger rail button when closed.
    useEffect(() => {
        if (prevExpandedRef.current !== isExpanded) {
            if (isExpanded) {
                requestAnimationFrame(() => {
                    closeBtnRef.current?.focus();
                });
            } else if (prevExpandedRef.current) {
                requestAnimationFrame(() => {
                    const railId = lastTriggerDataRailRef.current;
                    const targetBtn = railId ? document.querySelector(`button[data-rail="${railId}"]`) : null;
                    if (targetBtn) {
                        targetBtn.focus();
                    } else {
                        logoBtnRef.current?.focus();
                    }
                });
            }
            prevExpandedRef.current = isExpanded;
        }
    }, [isExpanded]);


    // User display information
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

    const handleItemClick = (e) => {
        // Guard against accidental double-tap on rail icon triggering immediate navigation during opening animation
        if (Date.now() - openTimeRef.current < 200) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }
        closeSidebar();
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

    // Determine active route
    const isItemActive = (item) => {
        if (location.pathname === item.path) return true;
        if (item.path === '/student/dashboard' && (location.pathname === '/student' || location.pathname === '/student/overview')) return true;
        if (item.path === '/teacher/dashboard' && (location.pathname === '/teacher' || location.pathname === '/teacher/overview')) return true;
        return false;
    };

    return (
        <>
            {/* ══════════════════════════════════════════════════════════════
                1. Rail or Placeholder (preserves fixed width: 64px / 72px)
                ══════════════════════════════════════════════════════════════ */}
            {isExpanded ? (
                <div className="sidebar-rail-placeholder" aria-hidden="true" />
            ) : (
                <aside
                    className="sidebar-rail"
                    aria-label="Sidebar navigation"
                    onPointerDown={(e) => openSidebar(e, 'empty')}
                    onClick={(e) => openSidebar(e, 'empty')}
                >
                    {/* Top Logo Button */}
                    <button
                        ref={logoBtnRef}
                        type="button"
                        className="sidebar-rail-logo-btn"
                        title="TiT Education"
                        aria-label="Open menu"
                        aria-expanded={false}
                        aria-controls="expanded-sidebar-drawer"
                        data-rail="logo"
                        onPointerDown={(e) => openSidebar(e, 'logo')}
                        onClick={(e) => openSidebar(e, 'logo')}
                        onKeyDown={(e) => handleRailKeyDown(e, 'logo')}
                    >
                        <div className="sidebar-logo-tile">TiT</div>
                    </button>

                    {/* Nav Item Icon Buttons (Tapping ONLY expands the sidebar; does NOT navigate) */}
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
                                    aria-expanded={false}
                                    aria-controls="expanded-sidebar-drawer"
                                    data-rail={railId}
                                    data-title={item.name}
                                    onPointerDown={(e) => openSidebar(e, railId)}
                                    onClick={(e) => openSidebar(e, railId)}
                                    onKeyDown={(e) => handleRailKeyDown(e, railId)}
                                    className={`sidebar-rail-item t-tap${active ? ' active' : ''}`}
                                >
                                    <Icon className="sidebar-rail-icon" />
                                </button>
                            );
                        })}
                    </nav>

                    {/* Bottom Pinned Logout Icon Button (Tapping ONLY expands the sidebar; does NOT log out) */}
                    <div className="sidebar-rail-bottom">
                        <button
                            type="button"
                            title="Logout"
                            aria-label="Open menu"
                            aria-expanded={false}
                            aria-controls="expanded-sidebar-drawer"
                            data-rail="logout"
                            onPointerDown={(e) => openSidebar(e, 'logout')}
                            onClick={(e) => openSidebar(e, 'logout')}
                            onKeyDown={(e) => handleRailKeyDown(e, 'logout')}
                            className="sidebar-rail-logout t-btn"
                        >
                            <FiLogOut style={{ fontSize: 18 }} />
                        </button>
                    </div>
                </aside>
            )}

            {/* ══════════════════════════════════════════════════════════════
                2. Expanded Sidebar Overlay (Identical across all screen sizes)
                ══════════════════════════════════════════════════════════════ */}
            {isExpanded && (
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
                        className="sidebar-drawer is-open"
                        aria-label="Main menu"
                    >
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
                                    <div className="sidebar-logo-tile" style={{ width: 38, height: 38, minWidth: 38, minHeight: 38 }}>
                                        TiT
                                    </div>
                                    <div style={{ minWidth: 0, flex: 1 }}>
                                        <div className="sidebar-brand-name">
                                            TiT<span className="sidebar-brand-accent">Education</span>
                                        </div>
                                        <div className="sidebar-brand-subtitle">{brandSubtitle}</div>
                                    </div>
                                </button>
                                <button
                                    ref={closeBtnRef}
                                    type="button"
                                    onClick={closeSidebar}
                                    className="sidebar-close-btn t-icon-btn"
                                    aria-label="Close sidebar"
                                >
                                    <FiX />
                                </button>
                            </div>

                            {/* Nav Items (Tapping now navigates AND auto-collapses immediately) */}
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
                                            <Icon className="sidebar-nav-icon" />
                                            <span className="sidebar-nav-label">{item.name}</span>
                                            {active && <FiChevronRight className="sidebar-nav-chevron" />}
                                        </NavLink>
                                    );
                                })}
                            </nav>

                            {/* User Card & Logout (Normal tap = Logout) */}
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
                    </aside>
                </>
            )}
        </>
    );
}

import React, { useState, useEffect, useRef } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { FiX, FiChevronRight, FiLogOut } from 'react-icons/fi';
import useMediaQuery from '../../hooks/useMediaQuery';
import './sidebar-shell.css';

/**
 * SidebarShell
 * Responsive Icon Rail (collapsed by default) + Full Expanded Sidebar (adaptive height)
 * Shared across Student and Teacher dashboards on all screen sizes.
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
    const isDesktop = useMediaQuery('(min-width: 1024px)');
    const location = useLocation();

    // Desktop expanded state stored in localStorage (default: false / collapsed)
    const [isDesktopExpanded, setIsDesktopExpanded] = useState(() => {
        try {
            return localStorage.getItem('sidebar_expanded_desktop') === 'true';
        } catch (e) {
            return false;
        }
    });

    // Mobile/tablet expanded state (overlay drawer, default: false)
    const [isMobileExpanded, setIsMobileExpanded] = useState(false);

    const isExpanded = isDesktop ? isDesktopExpanded : isMobileExpanded;

    const logoBtnRef = useRef(null);
    const closeBtnRef = useRef(null);
    const prevExpandedRef = useRef(null);

    // Toggle expanded state
    const toggleSidebar = () => {
        if (isDesktop) {
            setIsDesktopExpanded(prev => {
                const next = !prev;
                try {
                    localStorage.setItem('sidebar_expanded_desktop', String(next));
                } catch (e) {}
                return next;
            });
        } else {
            setIsMobileExpanded(prev => !prev);
        }
    };

    // Close expanded state
    const closeSidebar = () => {
        if (isDesktop) {
            setIsDesktopExpanded(false);
            try {
                localStorage.setItem('sidebar_expanded_desktop', 'false');
            } catch (e) {}
        } else {
            setIsMobileExpanded(false);
        }
    };

    // Collapse overlay immediately on route change
    useEffect(() => {
        if (!isDesktop) {
            setIsMobileExpanded(false);
        }
    }, [location.pathname, isDesktop]);

    // Handle Escape key on mobile/tablet overlay
    useEffect(() => {
        if (!isMobileExpanded || isDesktop) return;

        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                setIsMobileExpanded(false);
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isMobileExpanded, isDesktop]);

    // Lock body scroll while mobile overlay is open
    useEffect(() => {
        if (isMobileExpanded && !isDesktop) {
            const originalOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            return () => {
                document.body.style.overflow = originalOverflow;
            };
        }
    }, [isMobileExpanded, isDesktop]);

    // Accessibility focus management:
    // Move focus to close button when opened, and back to logo button when closed.
    useEffect(() => {
        if (prevExpandedRef.current === null) {
            prevExpandedRef.current = isExpanded;
            return;
        }

        if (prevExpandedRef.current !== isExpanded) {
            if (isExpanded) {
                // Wait for DOM update
                requestAnimationFrame(() => {
                    closeBtnRef.current?.focus();
                });
            } else {
                requestAnimationFrame(() => {
                    logoBtnRef.current?.focus();
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

    const handleLogout = (e) => {
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

    // Content for the Expanded Sidebar (used on desktop expanded and mobile overlay drawer)
    const renderExpandedContent = () => (
        <div className="sidebar-expanded-container">
            {/* Header */}
            <div className="sidebar-expanded-header">
                <button
                    type="button"
                    className="sidebar-header-brand"
                    onClick={toggleSidebar}
                    aria-label="Toggle sidebar"
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

            {/* Nav Items */}
            <nav className="sidebar-expanded-nav hide-scrollbar" aria-label="Expanded menu">
                {items.map((item) => {
                    const Icon = item.icon;
                    const active = isItemActive(item);
                    return (
                        <NavLink
                            key={item.name}
                            to={item.path}
                            onClick={() => { if (!isDesktop) setIsMobileExpanded(false); }}
                            className={`t-tap t-menu-item sidebar-nav-item${active ? ' active' : ''}`}
                        >
                            <Icon className="sidebar-nav-icon" />
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

    // ──────────────────────────────────────────────────────────────────────────
    // Desktop Layout (≥ 1024px)
    // ──────────────────────────────────────────────────────────────────────────
    if (isDesktop) {
        return (
            <aside
                id="expanded-sidebar-drawer"
                className={`sidebar-desktop-shell ${isDesktopExpanded ? 'is-expanded' : 'is-collapsed'}`}
                aria-label="Sidebar navigation"
            >
                {isDesktopExpanded ? (
                    renderExpandedContent()
                ) : (
                    <div className="sidebar-rail" style={{ height: '100%', width: '100%', borderRight: 'none' }}>
                        {/* Logo Button */}
                        <button
                            ref={logoBtnRef}
                            type="button"
                            className="sidebar-rail-logo-btn"
                            onClick={toggleSidebar}
                            aria-label="Open menu"
                            aria-expanded={false}
                            aria-controls="expanded-sidebar-drawer"
                        >
                            <div className="sidebar-logo-tile">TiT</div>
                        </button>

                        {/* Nav Icons */}
                        <nav className="sidebar-rail-nav hide-scrollbar" aria-label="Rail navigation">
                            {items.map((item) => {
                                const Icon = item.icon;
                                const active = isItemActive(item);
                                return (
                                    <NavLink
                                        key={item.name}
                                        to={item.path}
                                        title={item.name}
                                        aria-label={item.name}
                                        className={`sidebar-rail-item t-tap${active ? ' active' : ''}`}
                                    >
                                        <Icon className="sidebar-rail-icon" />
                                    </NavLink>
                                );
                            })}
                        </nav>

                        {/* Bottom Pinned Logout */}
                        <div className="sidebar-rail-bottom">
                            <button
                                type="button"
                                onClick={handleLogout}
                                aria-label="Logout"
                                title="Logout"
                                className="sidebar-rail-logout t-btn"
                            >
                                <FiLogOut style={{ fontSize: 18 }} />
                            </button>
                        </div>
                    </div>
                )}
            </aside>
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Mobile & Tablet Layout (< 1024px)
    // ──────────────────────────────────────────────────────────────────────────
    return (
        <>
            {/* Underlying Rail (or placeholder when drawer overlay is open) */}
            {isMobileExpanded ? (
                <div className="sidebar-rail-placeholder" aria-hidden="true" />
            ) : (
                <aside className="sidebar-rail" aria-label="Sidebar navigation">
                    {/* Logo Button */}
                    <button
                        ref={logoBtnRef}
                        type="button"
                        className="sidebar-rail-logo-btn"
                        onClick={toggleSidebar}
                        aria-label="Open menu"
                        aria-expanded={false}
                        aria-controls="expanded-sidebar-drawer"
                    >
                        <div className="sidebar-logo-tile">TiT</div>
                    </button>

                    {/* Nav Icons */}
                    <nav className="sidebar-rail-nav hide-scrollbar" aria-label="Rail navigation">
                        {items.map((item) => {
                            const Icon = item.icon;
                            const active = isItemActive(item);
                            return (
                                <NavLink
                                    key={item.name}
                                    to={item.path}
                                    title={item.name}
                                    aria-label={item.name}
                                    className={`sidebar-rail-item t-tap${active ? ' active' : ''}`}
                                >
                                    <Icon className="sidebar-rail-icon" />
                                </NavLink>
                            );
                        })}
                    </nav>

                    {/* Bottom Pinned Logout */}
                    <div className="sidebar-rail-bottom">
                        <button
                            type="button"
                            onClick={handleLogout}
                            aria-label="Logout"
                            title="Logout"
                            className="sidebar-rail-logout t-btn"
                        >
                            <FiLogOut style={{ fontSize: 18 }} />
                        </button>
                    </div>
                </aside>
            )}

            {/* Mobile/Tablet Overlay Drawer */}
            {isMobileExpanded && (
                <>
                    <div
                        className="sidebar-backdrop"
                        onClick={closeSidebar}
                        aria-label="Close sidebar overlay"
                        role="button"
                        tabIndex={-1}
                    />
                    <aside
                        id="expanded-sidebar-drawer"
                        className="sidebar-drawer is-open"
                        aria-label="Main menu"
                    >
                        {renderExpandedContent()}
                    </aside>
                </>
            )}
        </>
    );
}

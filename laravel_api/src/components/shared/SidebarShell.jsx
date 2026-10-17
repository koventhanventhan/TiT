import React, { useState, useEffect, useRef, useCallback } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { FiX, FiChevronRight, FiLogOut, FiMenu } from 'react-icons/fi';
import { useMediaQuery, PHONE_MEDIA_QUERY } from '../../hooks/useMediaQuery';
import './sidebar-shell.css';

export { PHONE_MEDIA_QUERY };

/**
 * SidebarHamburgerButton
 * Accessible, touch-guarded hamburger toggle for phone headers.
 * >= 44x44 tap target, aria-label, aria-expanded, aria-controls.
 */
export function SidebarHamburgerButton({
    isOpen = false,
    onClick,
    onPointerDown,
    buttonRef,
    className = '',
    style = {},
    ...props
}) {
    const lastTapRef = useRef(0);

    const handlePointerDown = (e) => {
        if (e.pointerType !== 'mouse' && e.cancelable) {
            e.preventDefault();
        }
        if (onPointerDown) onPointerDown(e);
    };

    const handleClick = (e) => {
        if (e && e.stopPropagation) e.stopPropagation();
        const now = performance.now();
        if (now - lastTapRef.current < 450) return;
        lastTapRef.current = now;
        if (onClick) onClick(e);
    };

    return (
        <button
            ref={buttonRef}
            type="button"
            className={`sidebar-hamburger-btn t-icon-btn ${className}`}
            aria-label="Open menu"
            aria-expanded={isOpen}
            aria-controls="expanded-sidebar-drawer"
            data-hamburger="true"
            onPointerDown={handlePointerDown}
            onClick={handleClick}
            style={{
                width: 44,
                height: 44,
                minWidth: 44,
                minHeight: 44,
                display: 'inline-flex',
                alignItems: 'center',
                justifyContent: 'center',
                background: 'transparent',
                border: 'none',
                borderRadius: 8,
                cursor: 'pointer',
                color: 'var(--t-text, #1e293b)',
                fontSize: 22,
                touchAction: 'manipulation',
                WebkitTapHighlightColor: 'transparent',
                flexShrink: 0,
                ...style
            }}
            {...props}
        >
            <FiMenu />
        </button>
    );
}

/**
 * SidebarShell
 * Responsive Icon Rail (desktop/tablet >= 640px) + Full Drawer on Phones (< 640px).
 *
 * Phones (< 640px):
 *   NO rail. Content left offset = 0.
 *   Header hamburger button opens the full drawer.
 *   Backdrop rgba(0,0,0,0.5), X / backdrop / Escape / nav tap closes immediately.
 *   Body scroll locked while open.
 *   Focus trap to X when opened, back to hamburger when closed.
 *   Touch rules: preventDefault on pointerdown + 450ms ghost-click guard.
 *
 * Tablet/Desktop (>= 640px):
 *   Collapsed rail by default.
 *   Hover (mouse): pointerenter -> opens expanded sidebar after ~70ms intent delay.
 *   Tap (touch/click): opens overlay with dimming backdrop.
 *
 * @param {string} brandSubtitle - "Student Portal" | "Teacher Portal"
 * @param {Array<{name: string, path: string, icon: React.ComponentType}>} items
 * @param {object} user - Current user object
 * @param {function} [onLogout] - Optional logout handler
 * @param {string} [avatarUrl] - Optional avatar URL override
 * @param {boolean} [isOpen] - Controlled open state (phones)
 * @param {function} [onClose] - Controlled close callback
 * @param {function} [onOpen] - Controlled open callback
 * @param {React.RefObject} [hamburgerRef] - Ref to header hamburger button for focus return
 */
export default function SidebarShell({
    brandSubtitle = 'Portal',
    items = [],
    user = null,
    onLogout,
    avatarUrl: propAvatarUrl,
    isOpen: controlledIsOpen,
    onClose: controlledOnClose,
    onOpen: controlledOnOpen,
    hamburgerRef
}) {
    const location = useLocation();
    const isPhone = useMediaQuery(PHONE_MEDIA_QUERY);

    const [internalIsExpanded, setInternalIsExpanded] = useState(false);
    // 'touch' | 'hover' | null — tracks HOW the drawer was opened
    const [openMode, setOpenMode] = useState(null);

    const isExpanded = controlledIsOpen !== undefined
        ? (isPhone ? controlledIsOpen : internalIsExpanded)
        : internalIsExpanded;

    const logoBtnRef = useRef(null);
    const closeBtnRef = useRef(null);
    const lastTriggerDataRailRef = useRef(null);
    const prevExpandedRef = useRef(false);
    const openTimeRef = useRef(0);
    const openedAtRef = useRef(0);
    const prevPathnameRef = useRef(location.pathname);
    const prevIsPhoneRef = useRef(isPhone);

    // Hover timers
    const hoverIntentTimerRef = useRef(null);
    const hoverLeaveTimerRef = useRef(null);
    // After navigating, suppress hover re-open for ~300ms
    const suppressHoverRef = useRef(false);
    const suppressTimerRef = useRef(null);

    // ── Open / Close helpers ──────────────────────────────────────────────

    // Opens in "touch" mode (with backdrop)
    const openSidebarTouch = useCallback((e, railId = 'logo') => {
        if (e && e.stopPropagation) e.stopPropagation();
        clearTimeout(hoverIntentTimerRef.current);
        hoverIntentTimerRef.current = null;
        clearTimeout(hoverLeaveTimerRef.current);
        hoverLeaveTimerRef.current = null;
        openedAtRef.current = performance.now();
        openTimeRef.current = Date.now();
        lastTriggerDataRailRef.current = railId;
        setOpenMode('touch');
        setInternalIsExpanded(true);
        if (controlledOnOpen) controlledOnOpen();
    }, [controlledOnOpen]);

    // Opens in "hover" mode (no backdrop, positioned next to rail)
    const openSidebarHover = useCallback(() => {
        if (suppressHoverRef.current || isPhone) return;
        clearTimeout(hoverIntentTimerRef.current);
        hoverIntentTimerRef.current = null;
        openedAtRef.current = 0;
        openTimeRef.current = Date.now();
        lastTriggerDataRailRef.current = 'hover';
        setOpenMode('hover');
        setInternalIsExpanded(true);
    }, [isPhone]);

    const closeSidebar = useCallback(() => {
        clearTimeout(hoverIntentTimerRef.current);
        hoverIntentTimerRef.current = null;
        clearTimeout(hoverLeaveTimerRef.current);
        hoverLeaveTimerRef.current = null;
        setInternalIsExpanded(false);
        setOpenMode(null);
        if (controlledOnClose) controlledOnClose();
        document.body.style.overflow = '';
        document.body.classList.remove('drawer-open');
    }, [controlledOnClose]);

    const closeSidebarAfterNav = useCallback(() => {
        closeSidebar();
        // Suppress hover re-open for 300ms
        suppressHoverRef.current = true;
        clearTimeout(suppressTimerRef.current);
        suppressTimerRef.current = setTimeout(() => {
            suppressHoverRef.current = false;
        }, 300);
    }, [closeSidebar]);

    // Sync controlledIsOpen with internal state and openMode
    useEffect(() => {
        if (controlledIsOpen !== undefined && isPhone) {
            if (controlledIsOpen) {
                openedAtRef.current = performance.now();
                openTimeRef.current = Date.now();
                lastTriggerDataRailRef.current = 'hamburger';
                setOpenMode('touch');
                setInternalIsExpanded(true);
            } else {
                setInternalIsExpanded(false);
                setOpenMode(null);
                document.body.style.overflow = '';
                document.body.classList.remove('drawer-open');
            }
        }
    }, [controlledIsOpen, isPhone]);

    // ── Breakpoint transition: resize/rotate across 640px ────────────────
    useEffect(() => {
        if (prevIsPhoneRef.current !== isPhone) {
            prevIsPhoneRef.current = isPhone;
            // Immediate clean switch: close drawer, clear scroll locks, reset hover
            clearTimeout(hoverIntentTimerRef.current);
            clearTimeout(hoverLeaveTimerRef.current);
            setInternalIsExpanded(false);
            setOpenMode(null);
            if (controlledOnClose) controlledOnClose();
            document.body.style.overflow = '';
            document.body.classList.remove('drawer-open');
        }
    }, [isPhone, controlledOnClose]);

    // ── Hover handlers (mouse only, only on >=640px) ──────────────────────

    const handleZonePointerEnter = useCallback((e) => {
        if (isPhone || e.pointerType !== 'mouse') return;
        clearTimeout(hoverLeaveTimerRef.current);
        hoverLeaveTimerRef.current = null;
        if (isExpanded && openMode === 'touch') return;
        if (!isExpanded && !suppressHoverRef.current) {
            clearTimeout(hoverIntentTimerRef.current);
            hoverIntentTimerRef.current = setTimeout(() => {
                hoverIntentTimerRef.current = null;
                openSidebarHover();
            }, 70);
        }
    }, [isPhone, isExpanded, openMode, openSidebarHover]);

    const handleZonePointerMove = useCallback((e) => {
        if (isPhone || e.pointerType !== 'mouse') return;
        clearTimeout(hoverLeaveTimerRef.current);
        hoverLeaveTimerRef.current = null;
        if (isExpanded || openMode === 'touch' || suppressHoverRef.current) return;
        if (!hoverIntentTimerRef.current) {
            hoverIntentTimerRef.current = setTimeout(() => {
                hoverIntentTimerRef.current = null;
                openSidebarHover();
            }, 70);
        }
    }, [isPhone, isExpanded, openMode, openSidebarHover]);

    const handleZonePointerLeave = useCallback((e) => {
        if (isPhone || e.pointerType !== 'mouse') return;
        clearTimeout(hoverIntentTimerRef.current);
        hoverIntentTimerRef.current = null;
        if (isExpanded && openMode === 'hover') {
            hoverLeaveTimerRef.current = setTimeout(() => {
                closeSidebar();
            }, 150);
        }
    }, [isPhone, isExpanded, openMode, closeSidebar]);

    // ── Click/Tap handlers (on rail — always opens in touch mode) ────────

    const handleRailPointerDown = useCallback((e, railId) => {
        if (e.pointerType === 'mouse') {
            if (isExpanded && openMode === 'hover') {
                e.stopPropagation();
                clearTimeout(hoverLeaveTimerRef.current);
                hoverLeaveTimerRef.current = null;
                openedAtRef.current = performance.now();
                openTimeRef.current = Date.now();
                lastTriggerDataRailRef.current = railId;
                setOpenMode('touch');
            }
            return;
        }
        if (e.cancelable) {
            e.preventDefault();
        }
    }, [isExpanded, openMode]);

    const handleRailPointerUp = useCallback((e, railId) => {
        if (e.pointerType === 'mouse') return;
        e.stopPropagation();
        if (isExpanded && openMode === 'touch') return;
        if (performance.now() - openedAtRef.current < 450) return;
        openSidebarTouch(e, railId);
    }, [isExpanded, openMode, openSidebarTouch]);

    const handleRailClick = useCallback((e, railId) => {
        e.stopPropagation();
        if (isExpanded && openMode === 'touch') return;
        if (performance.now() - openedAtRef.current < 450) return;
        openSidebarTouch(e, railId);
    }, [isExpanded, openMode, openSidebarTouch]);

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

    // ── Escape key closes drawer ─────────────────────────────────────────
    useEffect(() => {
        if (!isExpanded) return;
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') closeSidebar();
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isExpanded, closeSidebar]);

    // ── Lock body scroll while TOUCH / PHONE overlay is open ─────────────
    useEffect(() => {
        const isTouchActive = isExpanded && (openMode === 'touch' || isPhone);
        if (isTouchActive) {
            const originalOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            document.body.classList.add('drawer-open');
            return () => {
                document.body.style.overflow = originalOverflow;
                document.body.classList.remove('drawer-open');
            };
        } else {
            document.body.classList.remove('drawer-open');
        }
    }, [isExpanded, openMode, isPhone]);

    // ── Focus management ─────────────────────────────────────────────────
    useEffect(() => {
        if (prevExpandedRef.current !== isExpanded) {
            const isTouchActive = isExpanded && (openMode === 'touch' || isPhone);
            if (isTouchActive) {
                requestAnimationFrame(() => {
                    closeBtnRef.current?.focus();
                });
            } else if (prevExpandedRef.current) {
                // When closing on phone, return focus to hamburger button
                if (isPhone || lastTriggerDataRailRef.current === 'hamburger') {
                    requestAnimationFrame(() => {
                        const targetHamburger = hamburgerRef?.current ||
                            document.querySelector('button[data-hamburger="true"]') ||
                            document.querySelector('button[aria-label="Open menu"]');
                        targetHamburger?.focus();
                    });
                } else if (lastTriggerDataRailRef.current && lastTriggerDataRailRef.current !== 'hover') {
                    // On desktop/tablet, return focus to rail button
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
    }, [isExpanded, openMode, isPhone, hamburgerRef]);

    // ── Cleanup timers on unmount ────────────────────────────────────────
    useEffect(() => {
        return () => {
            clearTimeout(hoverIntentTimerRef.current);
            clearTimeout(hoverLeaveTimerRef.current);
            clearTimeout(suppressTimerRef.current);
            document.body.style.overflow = '';
            document.body.classList.remove('drawer-open');
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

    // Helper to check if touch guard is active for touch interactions
    const isTouchTimingGuarded = (e) => {
        if (openMode !== 'touch' && !isPhone) return false;
        if (e && e.pointerType === 'mouse') return false;
        return (performance.now() - openedAtRef.current < 450);
    };

    // ── Handlers inside expanded drawer ──────────────────────────────────
    const handleHeaderBrandClick = (e) => {
        if (e && e.preventDefault) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (openMode === 'touch' && (performance.now() - openedAtRef.current < 450)) {
            return;
        }
        closeSidebar();
    };

    const handleHeaderBrandPointerDown = (e) => {
        if (openMode === 'touch' && (performance.now() - openedAtRef.current < 450)) {
            if (e.cancelable) e.preventDefault();
            e.stopPropagation();
        }
    };

    const handleCloseBtnClick = (e) => {
        if (e && e.preventDefault) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (isTouchTimingGuarded(e)) {
            return;
        }
        closeSidebar();
    };

    const handleCloseBtnPointerDown = (e) => {
        if (isTouchTimingGuarded(e)) {
            if (e.cancelable) e.preventDefault();
            e.stopPropagation();
        }
    };

    const handleItemClick = (e) => {
        if (isTouchTimingGuarded(e)) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }
        // Immediately close without waiting for route transition
        closeSidebarAfterNav();
    };

    const handleItemPointerDown = (e) => {
        if (isTouchTimingGuarded(e)) {
            if (e.cancelable) e.preventDefault();
            e.stopPropagation();
        }
    };

    const handleLogout = (e) => {
        if (e && e.preventDefault) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (isTouchTimingGuarded(e)) {
            return;
        }
        if (onLogout) {
            onLogout(e);
        } else {
            window.location.href = '/?logout=1';
        }
    };

    const handleLogoutPointerDown = (e) => {
        if (isTouchTimingGuarded(e)) {
            if (e.cancelable) e.preventDefault();
            e.stopPropagation();
        }
    };

    const handleRailTouchStart = useCallback((e) => {
        if (e.cancelable) {
            e.preventDefault();
        }
    }, []);

    // ── Active route detection ───────────────────────────────────────────
    const isItemActive = (item) => {
        if (location.pathname === item.path) return true;
        if (item.path === '/student/dashboard' && (location.pathname === '/student' || location.pathname === '/student/overview')) return true;
        if (item.path === '/teacher/dashboard' && (location.pathname === '/teacher' || location.pathname === '/teacher/overview')) return true;
        return false;
    };

    // ── Mode flags ───────────────────────────────────────────────────────
    const isHoverMode = isExpanded && openMode === 'hover' && !isPhone;
    const isTouchMode = isExpanded && (openMode === 'touch' || isPhone);

    // ── Shared expanded content renderer ─────────────────────────────────
    const renderExpandedContent = (isHover) => (
        <div className="sidebar-expanded-container">
            {/* Header: logo + "TiTEducation" + portal name + X (no "MENU" text) */}
            <div className="sidebar-expanded-header">
                <button
                    type="button"
                    className="sidebar-header-brand"
                    onPointerDown={handleHeaderBrandPointerDown}
                    onClick={handleHeaderBrandClick}
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
                        onPointerDown={handleCloseBtnPointerDown}
                        onClick={handleCloseBtnClick}
                        className="sidebar-close-btn t-icon-btn"
                        aria-label="Close sidebar"
                    >
                        <FiX />
                    </button>
                )}
            </div>

            {/* Gap between header and first nav item */}
            <div className="sidebar-nav-gap" aria-hidden="true" />

            {/* 8 Labelled Nav Items (incl. Settings) */}
            <nav className="sidebar-expanded-nav hide-scrollbar" aria-label="Expanded menu">
                {items.map((item) => {
                    const Icon = item.icon;
                    const active = isItemActive(item);
                    return (
                        <NavLink
                            key={item.name}
                            to={item.path}
                            onPointerDown={handleItemPointerDown}
                            onClick={handleItemClick}
                            className={`t-tap sidebar-nav-item${active ? ' active' : ''}`}
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

            {/* Stacked User Card & full-width red-outline Logout */}
            <div className="sidebar-user-card">
                <div className="sidebar-user-info">
                    <div className="sidebar-user-avatar">
                        {resolvedAvatarUrl ? (
                            <img width="40" height="40" src={resolvedAvatarUrl} alt="Avatar" />
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
                    onPointerDown={handleLogoutPointerDown}
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

    const handleBackdropClick = (e) => {
        if (e && e.preventDefault) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (performance.now() - openedAtRef.current < 450) return;
        closeSidebar();
    };

    const handleBackdropPointerDown = (e) => {
        if (e.pointerType !== 'mouse') {
            if (e.cancelable) e.preventDefault();
            e.stopPropagation();
        }
    };

    const handleBackdropPointerUp = (e) => {
        if (e.pointerType === 'mouse') return;
        e.stopPropagation();
        if (performance.now() - openedAtRef.current < 450) return;
        closeSidebar();
    };

    return (
        <>
            {/* ══════════════════════════════════════════════════════════════
                1 & 2: Rail & Hover Drawer (TABLET / DESKTOP >= 640px ONLY)
                On phones (< 640px) this wrapper is hidden via CSS & isPhone.
                ══════════════════════════════════════════════════════════════ */}
            {!isPhone && (
                <div
                    className={`sidebar-hover-zone${isHoverMode ? ' is-hover-expanded' : ''}`}
                    onPointerEnter={handleZonePointerEnter}
                    onPointerMove={handleZonePointerMove}
                    onPointerLeave={handleZonePointerLeave}
                >
                    {isTouchMode ? (
                        <div className="sidebar-rail-placeholder" aria-hidden="true" />
                    ) : (
                        <aside
                            className={`sidebar-rail${isExpanded ? ' rail-behind-drawer' : ''}`}
                            aria-label="Sidebar navigation"
                            onTouchStart={handleRailTouchStart}
                            onPointerDown={(e) => handleRailPointerDown(e, 'empty')}
                            onPointerUp={(e) => handleRailPointerUp(e, 'empty')}
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
                                onTouchStart={handleRailTouchStart}
                                onPointerDown={(e) => handleRailPointerDown(e, 'logo')}
                                onPointerUp={(e) => handleRailPointerUp(e, 'logo')}
                                onClick={(e) => handleRailClick(e, 'logo')}
                                onKeyDown={(e) => handleRailKeyDown(e, 'logo')}
                            >
                                <div className="sidebar-logo-tile">TiT</div>
                            </button>

                            {/* Spacer */}
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
                                            onTouchStart={handleRailTouchStart}
                                            onPointerDown={(e) => handleRailPointerDown(e, railId)}
                                            onPointerUp={(e) => handleRailPointerUp(e, railId)}
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
                                    onTouchStart={handleRailTouchStart}
                                    onPointerDown={(e) => handleRailPointerDown(e, 'logout')}
                                    onPointerUp={(e) => handleRailPointerUp(e, 'logout')}
                                    onClick={(e) => handleRailClick(e, 'logout')}
                                    onKeyDown={(e) => handleRailKeyDown(e, 'logout')}
                                    className="sidebar-rail-logout t-btn"
                                >
                                    <FiLogOut style={{ fontSize: 18 }} />
                                </button>
                            </div>
                        </aside>
                    )}

                    {/* Hover Drawer */}
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
            )}

            {/* ══════════════════════════════════════════════════════════════
                3. Touch / Phone Overlay (with backdrop rgba(0,0,0,0.5))
                Rendered when opened on Phone or via Rail Touch on Tablet/Desktop
                ══════════════════════════════════════════════════════════════ */}
            {isTouchMode && (
                <>
                    {/* Backdrop */}
                    <div
                        className="sidebar-backdrop"
                        onPointerDown={handleBackdropPointerDown}
                        onPointerUp={handleBackdropPointerUp}
                        onClick={handleBackdropClick}
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

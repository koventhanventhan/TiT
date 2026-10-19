import { useState, useCallback, useRef, useEffect } from 'react';
import { studentAttend } from '../services/dashboardService';

export function useJoinClass() {
    const [joiningId, setJoiningId] = useState(null);
    const [isCooldown, setIsCooldown] = useState(false);
    const inFlightRef = useRef(false);
    const cooldownTimerRef = useRef(null);

    useEffect(() => {
        return () => {
            if (cooldownTimerRef.current) {
                clearTimeout(cooldownTimerRef.current);
            }
        };
    }, []);

    const joinClass = useCallback(async (cls) => {
        if (!cls || inFlightRef.current) return;
        const link = cls.join_url || cls.zoom_link;
        if (!link) return;

        inFlightRef.current = true;
        setJoiningId(cls.id);
        const w = typeof window !== 'undefined' ? window.open('', '_blank') : null;

        try {
            await studentAttend(cls.id).catch(err => {
                console.error('Attendance recording error:', err);
            });
            if (w) {
                w.location.href = link;
            }
        } catch (e) {
            console.error('Join error:', e);
            if (w) {
                w.location.href = link;
            }
        } finally {
            setJoiningId(null);
            setIsCooldown(true);
            if (cooldownTimerRef.current) {
                clearTimeout(cooldownTimerRef.current);
            }
            cooldownTimerRef.current = setTimeout(() => {
                inFlightRef.current = false;
                setIsCooldown(false);
            }, 1500);
        }
    }, []);

    return {
        joinClass,
        joiningId,
        isCooldown,
        isJoining: (id) => joiningId === id || inFlightRef.current || isCooldown,
    };
}

export default useJoinClass;

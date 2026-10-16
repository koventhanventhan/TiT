import { useState, useCallback, useRef } from 'react';
import { studentAttend } from '../services/dashboardService';

export function useJoinClass() {
    const [joiningId, setJoiningId] = useState(null);
    const inFlightRef = useRef(false);

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
            inFlightRef.current = false;
            setJoiningId(null);
        }
    }, []);

    return {
        joinClass,
        joiningId,
        isJoining: (id) => joiningId === id,
    };
}

export default useJoinClass;

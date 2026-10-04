import React, { useState, useEffect } from 'react';
import { 
    FiChevronLeft, 
    FiChevronRight, 
    FiPlus, 
    FiVideo, 
    FiClock, 
    FiUser, 
    FiMapPin, 
    FiX,
    FiFileText,
    FiCalendar
} from 'react-icons/fi';
import { getAdminCalendar } from '../../services/dashboardService';
import './AdminCalendar.css';

export default function AdminCalendar() {
    const [currentDate, setCurrentDate] = useState(new Date());
    const [events, setEvents] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedEvent, setSelectedEvent] = useState(null);

    useEffect(() => {
        loadEvents();
    }, [currentDate]);

    const loadEvents = async () => {
        setLoading(true);
        try {
            const start = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const end = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
            const data = await getAdminCalendar(start.toISOString(), end.toISOString());
            setEvents(data);
        } catch (error) {
            console.error('Error fetching calendar events:', error);
        } finally {
            setLoading(false);
        }
    };

    const nextMonth = () => setCurrentDate(new Date(currentDate.setMonth(currentDate.getMonth() + 1)));
    const prevMonth = () => setCurrentDate(new Date(currentDate.setMonth(currentDate.getMonth() - 1)));
    const goToToday = () => setCurrentDate(new Date());

    const getDaysInMonth = (year, month) => {
        const date = new Date(year, month, 1);
        const days = [];
        const firstDayIndex = date.getDay();
        
        // Prev month days
        const prevMonthLastDay = new Date(year, month, 0).getDate();
        for (let i = firstDayIndex; i > 0; i--) {
            days.push({ 
                day: prevMonthLastDay - i + 1, 
                month: month - 1, 
                year: year,
                currentMonth: false 
            });
        }

        // Current month days
        const lastDay = new Date(year, month + 1, 0).getDate();
        for (let i = 1; i <= lastDay; i++) {
            days.push({ 
                day: i, 
                month: month, 
                year: year,
                currentMonth: true 
            });
        }

        // Next month days
        const remainingDays = 42 - days.length;
        for (let i = 1; i <= remainingDays; i++) {
            days.push({ 
                day: i, 
                month: month + 1, 
                year: year,
                currentMonth: false 
            });
        }

        return days;
    };

    const days = getDaysInMonth(currentDate.getFullYear(), currentDate.getMonth());
    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

    const getEventsForDay = (day, month, year) => {
        return events.filter(event => {
            const d = new Date(event.start);
            return d.getDate() === day && d.getMonth() === month && d.getFullYear() === year;
        });
    };

    const isToday = (day, month, year) => {
        const today = new Date();
        return today.getDate() === day && today.getMonth() === month && today.getFullYear() === year;
    };

    return (
        <div className="admin-calendar-container">
            <div className="calendar-header">
                <div className="header-left">
                    <h1>Academic Calendar</h1>
                    <p>Track all school activities, Zoom sessions, and assignment deadlines.</p>
                </div>
                <div className="calendar-controls">
                    <button className="btn-secondary" onClick={goToToday}>Today</button>
                    <div className="nav-buttons">
                        <button className="nav-btn" onClick={prevMonth}><FiChevronLeft /></button>
                        <div className="current-month">{monthNames[currentDate.getMonth()]} {currentDate.getFullYear()}</div>
                        <button className="nav-btn" onClick={nextMonth}><FiChevronRight /></button>
                    </div>
                </div>
            </div>

            <div className="calendar-card">
                <div className="calendar-grid-header">
                    {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(d => (
                        <div key={d} className="day-name">{d}</div>
                    ))}
                </div>
                
                {loading ? (
                    <div className="loading-shimmer" style={{ height: '31.25rem' }}>Syncing calendar data...</div>
                ) : (
                    <div className="calendar-grid">
                        {days.map((d, i) => {
                            const dayEvents = getEventsForDay(d.day, d.month, d.year);
                            return (
                                <div 
                                    key={i} 
                                    className={`calendar-day ${!d.currentMonth ? 'other-month' : ''} ${isToday(d.day, d.month, d.year) ? 'today' : ''}`}
                                >
                                    <span className="day-number">{d.day}</span>
                                    <div className="day-events">
                                        {dayEvents.map(event => (
                                            <div 
                                                key={event.id} 
                                                className="event-item"
                                                style={{ backgroundColor: event.color }}
                                                onClick={() => setSelectedEvent(event)}
                                            >
                                                {event.type === 'zoom' && <FiVideo style={{fontSize: '0.625rem'}} />}
                                                {event.type === 'assignment' && <FiFileText style={{fontSize: '0.625rem'}} />}
                                                {event.title}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>

            {selectedEvent && (
                <>
                    <div className="overlay" onClick={() => setSelectedEvent(null)}></div>
                    <div className="event-details-panel">
                        <div className="event-modal-header">
                            <span className="event-badge" style={{backgroundColor: selectedEvent.color}}>
                                {selectedEvent.type}
                            </span>
                            <button onClick={() => setSelectedEvent(null)} className="nav-btn"><FiX /></button>
                        </div>
                        <div className="event-modal-body">
                            <h2>{selectedEvent.title}</h2>
                            <div className="meta-row">
                                <FiCalendar /> {new Date(selectedEvent.start).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                            </div>
                            <div className="meta-row">
                                <FiClock /> {new Date(selectedEvent.start).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                            </div>
                            
                            {selectedEvent.extendedProps?.teacher && (
                                <div className="meta-row">
                                    <FiUser /> Instructor: {selectedEvent.extendedProps.teacher}
                                </div>
                            )}

                            {selectedEvent.extendedProps?.subject && (
                                <div className="meta-row">
                                    <FiFileText /> Subject: {selectedEvent.extendedProps.subject} ({selectedEvent.extendedProps.grade})
                                </div>
                            )}

                            {selectedEvent.extendedProps?.location && (
                                <div className="meta-row">
                                    <FiMapPin /> {selectedEvent.extendedProps.location}
                                </div>
                            )}

                            {selectedEvent.extendedProps?.description && (
                                <p style={{marginTop: '1rem', color: '#64748b', fontSize: '0.875rem', lineHeight: '1.5'}}>
                                    {selectedEvent.extendedProps.description}
                                </p>
                            )}
                        </div>
                        <div className="event-actions">
                            {selectedEvent.type === 'zoom' && selectedEvent.extendedProps.link && (
                                <button className="btn-primary" onClick={() => window.open(selectedEvent.extendedProps.link, '_blank')}>
                                    Join Session
                                </button>
                            )}
                            <button className="btn-secondary" onClick={() => setSelectedEvent(null)}>Close</button>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}

# TiT (Institute Management System) - Project Summary

## Project Overview
TiT is a robust, multi-tenant Institute Management System designed to streamline educational operations. It facilitates interaction between administrators, teachers, and students, managing everything from registrations and payments to Zoom-based virtual classes and assignments.

## Core Architecture
The project follows a modern decoupled architecture:
- **Backend**: Laravel 10+ (PHP) serving a RESTful API.
- **Frontend**: React (Vite) for a dynamic, single-page application experience.
- **Database**: MySQL/PostgreSQL (Eloquent ORM).
- **Authentication**: Laravel Sanctum for API token-based security.

## Key Modules

### 1. Multi-Tenancy (Institute Management)
The system is built to support multiple institutes. Each institute (Tenant) has its own settings, students, and teachers.
- **Super Admin**: Manages institutes and subscription plans.
- **Tenant Admin**: Manages their specific institute.

### 2. Zoom Integration
Automated virtual classroom management.
- Synchronizes class schedules with Zoom meetings.
- Handles attendance tracking via webhooks.
- Teachers can start meetings directly from the dashboard.

### 3. Timetable & Scheduling
Comprehensive scheduling system for classes and events.
- Dynamic calendar interface (FullCalendar.js).
- Conflict detection and recurring class support.

### 4. Student & Teacher Portals
Role-specific dashboards providing:
- **Students**: View upcoming classes, submit assignments, access materials, and pay fees.
- **Teachers**: Manage classes, grade assignments, and track student progress.

### 5. Messaging & Notifications
Internal communication system for announcements and direct messaging between roles.

### 6. Finance & Payments
Integrated payment processing for student registrations and course fees.

## Technology Stack
- **Frontend**: ReactJS, TailwindCSS, Headless UI, Heroicons, Vite.
- **Backend**: Laravel, Sanctum, Eloquent, PHP 8.x.
- **Integrations**: Zoom API, Gemini (for translations).

<div class="deznav">
    <div class="deznav-scroll">
        <ul class="metismenu" id="menu">
            {{-- Dashboard --}}
            <li class="{{ request()->routeIs('admin.dashboard') ? 'mm-active' : '' }}">
                <a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                    <i class="flaticon-381-networking"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <ul aria-expanded="{{ request()->routeIs('admin.dashboard') ? 'true' : 'false' }}">
                    <li class="{{ request()->routeIs('admin.dashboard') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    </li>
                </ul>
            </li>

            {{-- Student Entries --}}
            <li class="{{ request()->routeIs('admin.students.*') ? 'mm-active' : '' }}">
                <a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                    <i class="flaticon-381-notepad"></i>
                    <span class="nav-text">Student Entries</span>
                </a>
                <ul aria-expanded="{{ request()->routeIs('admin.students.*') ? 'true' : 'false' }}">
                    <li class="{{ request()->routeIs('admin.students.index') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.students.index') }}">All Students</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.students.create') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.students.create') }}">Add Student</a>
                    </li>
                </ul>
            </li>

            {{-- Teachers --}}
            <li class="{{ request()->routeIs('admin.teachers.*') ? 'mm-active' : '' }}">
                <a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                    <i class="flaticon-381-user-7"></i>
                    <span class="nav-text">Teachers</span>
                </a>
                <ul aria-expanded="{{ request()->routeIs('admin.teachers.*') ? 'true' : 'false' }}">
                    <li class="{{ request()->routeIs('admin.teachers.index') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.teachers.index') }}">All Teachers</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.teachers.create') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.teachers.create') }}">Add Teacher</a>
                    </li>
                </ul>
            </li>

            {{-- Payment Settings --}}
            <li class="{{ request()->routeIs('admin.subjects.*') ? 'mm-active' : '' }}">
                <a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                    <i class="flaticon-381-settings-2"></i>
                    <span class="nav-text">Payment Settings</span>
                </a>
                <ul aria-expanded="{{ request()->routeIs('admin.subjects.*') ? 'true' : 'false' }}">
                    <li class="{{ request()->routeIs('admin.subjects.index') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.subjects.index') }}">Subject Pricing</a>
                    </li>
                </ul>
            </li>

            {{-- Zoom Classes --}}
            <li class="{{ (request()->routeIs('admin.zoom.*') || request()->routeIs('admin.attendance.*')) ? 'mm-active' : '' }}">
                <a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                    <i class="flaticon-381-video-camera"></i>
                    <span class="nav-text">Zoom Classes</span>
                </a>
                <ul aria-expanded="{{ (request()->routeIs('admin.zoom.*') || request()->routeIs('admin.attendance.*')) ? 'true' : 'false' }}">
                    <li class="{{ request()->routeIs('admin.zoom.index') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.zoom.index') }}">All Zoom Classes</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.zoom-accounts.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.zoom-accounts.index') }}">Zoom Accounts</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.timetables.index') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.timetables.index') }}">Weekly Timetable</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.timetables.index') && !request()->routeIs('admin.zoom.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.timetables.index') }}">New Class</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.attendance.index') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.attendance.index') }}">Attendance</a>
                    </li>
                </ul>
            </li>

            {{-- Settings --}}

            {{-- Settings --}}
            <li class="{{ request()->routeIs('admin.settings.*') ? 'mm-active' : '' }}">
                <a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                    <i class="flaticon-381-settings-2"></i>
                    <span class="nav-text">Settings</span>
                </a>
                <ul aria-expanded="{{ request()->routeIs('admin.settings.*') ? 'true' : 'false' }}">
                     <li class="{{ request()->routeIs('admin.settings.register') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.settings.register') }}">Register Form</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.settings.index') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.settings.index') }}">Frontend Page</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.settings.about') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.settings.about') }}">About Page</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.settings.classes') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.settings.classes') }}">Classes Page</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.settings.learning') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.settings.learning') }}">Learning Site Page</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.settings.contact') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.settings.contact') }}">Contact Page</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.settings.footer') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.settings.footer') }}">Footer Page</a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</div>

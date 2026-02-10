# EduLearn - Online Tuition Platform

A modern, responsive React-based online tuition website with Laravel backend API, inspired by edustutor.com with a fresh, contemporary design.

## Features

- 🎨 Modern, gradient-based UI design
- 📱 Fully responsive layout
- ⚡ Fast and optimized with Vite
- 🎯 Smooth animations and transitions
- 📚 Multiple sections: Hero, About, Classes, Testimonials, etc.
- 🔍 SEO-friendly structure
- 🔐 Laravel backend API with authentication
- 🗄️ Database integration with Laravel

## Project Structure

```
website/
├── backend/              # Laravel API backend
│   ├── app/
│   ├── routes/
│   ├── database/
│   └── config/
├── src/                  # React frontend
│   ├── components/
│   ├── pages/
│   └── services/
├── Admin Theme/          # Admin theme templates
└── package.json
```

## Getting Started

### Prerequisites

- Node.js (v16 or higher)
- npm or yarn
- PHP 8.2 or higher
- Composer
- XAMPP (or similar) with MySQL

### Quick Start

#### 1. Backend Setup (Laravel)

```bash
cd backend
composer install
php artisan migrate
php artisan serve
```

The Laravel API will run on `http://localhost:8000`

#### 2. Frontend Setup (React)

```bash
# In the root directory
npm install
npm run dev
```

The React app will run on `http://localhost:3000`

### Detailed Setup

See [LARAVEL_SETUP.md](./LARAVEL_SETUP.md) for detailed setup instructions.

### Build for Production

```bash
npm run build
```

The built files will be in the `dist` directory.

## Project Structure

```
src/
├── components/          # React components
│   ├── Header.jsx
│   ├── Hero.jsx
│   ├── About.jsx
│   ├── Classes.jsx
│   ├── WhyChooseUs.jsx
│   ├── MobileApp.jsx
│   ├── Onboarding.jsx
│   ├── Testimonials.jsx
│   ├── StudentToolkit.jsx
│   ├── Accreditations.jsx
│   └── Footer.jsx
├── App.jsx             # Main app component
├── App.css             # App styles
├── main.jsx            # Entry point
└── index.css           # Global styles
```

## Technologies Used

- React 18
- Vite
- React Router DOM
- React Icons
- CSS3 (Custom Properties, Flexbox, Grid)

## Customization

You can customize the design by modifying:
- CSS variables in `src/index.css` (colors, gradients)
- Component styles in individual component CSS files
- Content in component JSX files

## License

This project is open source and available for educational purposes.


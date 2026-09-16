# ComSoc QR Attendance System

A production-ready web application for the Computing Society (ComSoc) to manage student memberships, printable QR cards, non-student event registrations, attendance tracking with dual-mode kiosk scanners, snack distributions, incident reporting, and administrative reporting.

## Features

- **Core User Roles**: Super Admin, Admin, Staff, Treasurer, and Student.
- **Academic Year & Masterlist**: Yearly student masterlists with auto-enrollment, duplicate/conflict detection, and classification.
- **Membership & QR Code System**: Active status per academic year, bulk CSV activation, dynamic signed QR codes with photo/metadata overlay, and batch ZIP export for printing.
- **Dual-Mode Attendance Kiosk**:
  - High-speed barcode/QR scanner mode and live device camera scanning (HTML5-QRCode).
  - Configurable scan modes: Strict Mode, Grace Period, Free Mode.
  - Multi-session events (Morning In/Out, Afternoon In/Out).
  - Duplicate scan prevention and automatic late calculation.
  - Offline connectivity detection and instant auto-refocus for uninterrupted queue handling.
- **Snack Distribution**: Event snack sessions with inventory tracking, single/multi-claim restrictions, and real-time scanning feedback.
- **Non-Student Event Registration**: Public registration portal for external attendees, admin approval workflow, and time-bounded QR passes.
- **Incident Reporting**: Staff-logged behavioral or technical infractions with student association, auto-escalation, and 7-day automated archiving.
- **Rankings & Gamification**: Attendance leaderboard toggleable per academic year.
- **Comprehensive Reporting & Audit Trail**: Exportable records (CSV/Excel/PDF) for attendance, memberships, snack claims, and immutable audit logs.

## Tech Stack

- **Backend**: Laravel 11 / PHP 8.2+
- **Database**: PostgreSQL (SQLite supported for testing/development)
- **Frontend**: Blade, Tailwind CSS v3, Alpine.js, Vite
- **Libraries**:
  - `simplesoftwareio/simple-qrcode` for QR code rendering
  - `barryvdh/laravel-dompdf` for PDF export generation
  - `maatwebsite/excel` for Excel spreadsheet imports and exports
  - `laravel/breeze` for authentication scaffolding

## Local Development Setup

### 1. Prerequisites
- PHP 8.2 or higher with `pdo_pgsql`, `gd`, `zip`, `bcmath` extensions enabled
- Composer 2.x
- Node.js 18+ & npm
- PostgreSQL database server (or SQLite)

### 2. Installation

Clone the repository and install PHP and JavaScript dependencies:

```bash
composer install
npm install
```

### 3. Environment Configuration

Copy the example environment file and generate the application encryption key:

```bash
cp .env.example .env
php artisan key:generate
```

Update your `.env` configuration with your PostgreSQL credentials:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=comsoc_attendance
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 4. Database Setup & Seeding

Run database migrations and seed the default roles, initial settings, and Super Admin user:

```bash
php artisan migrate --seed
```

#### Default Super Admin Credentials:
- **Email**: `admin@comsoc.local`
- **Password**: `password`
*(Please update credentials immediately upon first login)*

### 5. Build Frontend Assets & Run Server

Compile assets using Vite:

```bash
npm run build
```

Run the Laravel development server:

```bash
php artisan serve
```

Access the application in your browser at `http://localhost:8000`.

## Deployment (Render)

This repository includes a `render.yaml` blueprint for automatic deployment on [Render](https://render.com).

1. Push your repository to GitHub or GitLab.
2. In Render, select **New > Blueprint** and connect your repository.
3. Render will provision:
   - A PHP web service running Laravel
   - A managed PostgreSQL instance
4. Set `APP_KEY` in Render environment settings (generate with `php artisan key:generate --show`).
5. Migrations will run automatically during deployment via the build script.

## Incident Archival Cron

To enable automated archival of incidents older than 7 days, configure a cron job or scheduled task to run:

```bash
php artisan schedule:run
```

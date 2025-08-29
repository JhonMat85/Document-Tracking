# Project Context for Qwen Code

This document provides an overview of the `document-tracking` project for use by Qwen Code.

## Project Type

This is a **PHP/Laravel web application**. It's a skeleton Laravel project, customized for document tracking, with a focus on managing quotes and related documents. It uses Filament for the admin panel.

## Key Technologies

- **Backend Framework**: Laravel (v12.0)
- **PHP Version**: ^8.2
- **Frontend Build Tool**: Vite
- **Frontend Framework**: Tailwind CSS (v4.0)
- **Admin Panel**: Filament (v4.0)
- **Database PDF Generation**: mPDF, TCPDF
- **Spreadsheet Handling**: PhpOffice\PhpSpreadsheet
- **Testing**: PestPHP

## Project Structure Overview

Based on the Laravel standard structure, key directories include:

- `app/`: Contains the core application code (Models, Controllers, etc.).
- `config/`: Application configuration files.
- `database/`: Database migrations, seeds, and factories.
- `public/`: Publicly accessible files (entry point for the web server).
- `resources/`: Views, stylesheets, JavaScript.
- `routes/`: Web and API route definitions.
- `tests/`: Automated tests (using PestPHP).
- `vendor/`: Composer dependencies (not committed).

Custom directories:
- `especificaciones/`: Likely contains project specifications or documentation.

## Key Components

### Models
- `App\Models\Quote`: Central model for managing quotes, including relationships to requests, clients, details, and states.
- `App\Models\User`: Standard Laravel user authentication model.

### Controllers
- `App\Http\Controllers\QuoteController`: Handles quote-related actions, currently with placeholder methods for PDF generation.

### Routes
- `routes/web.php`: Defines web routes, including redirects and PDF download/test endpoints for quotes.

## Development & Running

### Setup & Installation
1. Ensure PHP 8.2+, Composer, and Node.js are installed.
2. Clone the repository.
3. Run `composer install` to install PHP dependencies.
4. Run `npm install` to install frontend dependencies.
5. Copy `.env.example` to `.env` and configure environment variables (database, etc.).
6. Run `php artisan key:generate`.
7. Run `php artisan migrate` to set up the database.

### Running the Application
- **Development Server**: `composer run dev` (starts Laravel server, queue listener, and Vite dev server using `concurrently`).
- **Frontend Dev Build**: `npm run dev` (uses Vite).
- **Frontend Production Build**: `npm run build` (uses Vite).

### Testing
- Run tests using `composer run test` or `php artisan test`.

## Development Conventions

- **Backend**: Follows Laravel conventions for MVC, routing, and Eloquent ORM.
- **Frontend**: Uses Tailwind CSS for styling, built with Vite.
- **Admin**: Uses Filament v4 for building the admin interface.
- **Testing**: Uses PestPHP for testing.
- **Code Style**: Likely uses Laravel Pint for code formatting (included in dev dependencies).

## Important Notes

- PDF generation logic appears to have been removed or stubbed out in `QuoteController`.
- The project integrates `mpdf/mpdf` and `tecnickcom/tcpdf`, suggesting PDF functionality is intended.
- The project uses `phpoffice/phpspreadsheet`, indicating spreadsheet functionality is also intended.
# ShopFlow - Full Stack E-Commerce Platform

ShopFlow is a full-stack e-commerce application built with Laravel and React.

## Tech Stack

### Backend
- PHP 8.2+
- Laravel 12
- MySQL
- Laravel Sanctum
- RESTful API

### Frontend
- React
- Vite
- Tailwind CSS
- React Router
- Axios
- Lucide React

## Features

### Customer
- User registration and login
- Browse products and categories
- Shopping cart management
- Checkout
- View orders and order details

### Admin
- Admin dashboard
- Product management (CRUD)
- Category management (CRUD)
- Order management
- Order status and payment management

## Project Structure

    shopflow-ecommerce/
    ├── backend/
    ├── frontend/
    ├── .gitignore
    └── README.md

## Installation

### Backend

    cd backend
    composer install

Copy `.env.example` to `.env`, configure your database, then run:

    php artisan key:generate
    php artisan migrate
    php artisan serve

### Frontend

Open another terminal:

    cd frontend
    npm install

Copy `.env.example` to `.env` and configure:

    VITE_API_URL=http://127.0.0.1:8000/api

Then run:

    npm run dev

## Production Build

    npm run build

## Author

Ayman Mamdouh

Full Stack Web Developer | Backend Developer

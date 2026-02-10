# Automated Sales Monitoring and Reporting System for Water Refilling Businesses

## 📌 System Overview

A full-stack mobile and web-based system that automates sales transactions, QR-based gallon container tracking, inventory monitoring, and automated reporting for water refilling businesses.

## 🏗️ System Components

1. **Backend API** - Laravel (PHP)
2. **Mobile Application** - Flutter (Android)
3. **Web Admin Panel** - HTML/CSS/JavaScript/Bootstrap
4. **Database** - MySQL

## 👥 User Roles

### Admin
- Dashboard monitoring
- Sales analytics
- Inventory tracking
- Gallon movement monitoring
- Report generation
- User account management

### Employee
- Login
- Create new transactions
- Scan QR codes
- Process gallon returns
- View inventory and daily summaries

## 🔁 Transaction Types

1. **Walk-in** - Customer visits station
2. **Delivery** - Delivered to customer location
3. **Refill-only** - Refill without gallon exchange

## 📊 Gallon Status Logic

| Status | Meaning |
|--------|---------|
| IN | Gallon inside the station |
| OUT | Borrowed/delivered to customer |
| MISSING | Overdue beyond allowed return period |

## 🚀 Quick Start

### Prerequisites
- PHP 8.1+
- Composer
- MySQL 8.0+
- Flutter SDK 3.0+
- XAMPP/Laragon (for local development)

### Backend Setup
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

### Mobile App Setup
```bash
cd mobile_app
flutter pub get
flutter run
```

### Web Admin Panel
- Open `web_admin/index.html` in browser
- Or serve via Apache/Nginx

## 📁 Project Structure

```
├── backend/           # Laravel API
├── mobile_app/        # Flutter Android App
├── web_admin/         # Web Admin Panel
├── database/          # Database schema and dumps
└── docs/              # Documentation
```

## 🔒 Security Features

- Role-based access control
- Password hashing
- JWT authentication
- Input validation
- Transaction history protection

## 📈 Key Features

- ✅ QR-based gallon tracking
- ✅ Automated transaction processing
- ✅ Real-time inventory monitoring
- ✅ Automatic report generation
- ✅ Overdue gallon alerts
- ✅ Missing gallon detection
- ✅ Daily/Weekly/Monthly reports

## 📞 Support

For issues and questions, refer to the documentation in the `docs/` folder.

## 📄 License

This project is developed as a capstone project for educational purposes.
# Automated-Sales-Monitoring-and-Reporting-System-for-Water-Refilling-Businesses

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

## 🚀 Setup Instructions

### Prerequisites
- PHP 8.1+
- Composer
- MySQL 8.0+
- Flutter SDK 3.0+
- Android Studio (for mobile development)
- XAMPP/Laragon (for local development)

---

## 📦 Laravel Backend Setup

### Step 1: Install Dependencies
```bash
cd backend
composer install
```

### Step 2: Configure Environment
```bash
# Copy the environment file
cp .env.example .env

# Edit .env file and configure your database:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=water_refilling_db
DB_USERNAME=root
DB_PASSWORD=
```

### Step 3: Generate Application Key
```bash
php artisan key:generate
```

### Step 4: Create Database
- Open phpMyAdmin or MySQL client
- Create a new database named `water_refilling_db`

### Step 5: Run Migrations and Seeders
```bash
# Run database migrations
php artisan migrate

# Seed the database with default data
php artisan db:seed
```

### Step 6: Start the Development Server
```bash
# Default (localhost only - port 8000)
php artisan serve

# For network access (required for mobile app)
php artisan serve --host=0.0.0.0 --port=8000
```

**Backend will be available at:**
- Local: `http://127.0.0.1:8000`
- Network: `http://YOUR_IP_ADDRESS:8000`

### Default Admin Login
- **Email:** admin@waterrefilling.local
- **Password:** password

---

## 📱 Flutter Mobile App Setup

### Step 1: Install Dependencies
```bash
cd mobile_app
flutter pub get
```

### Step 2: Configure API Endpoint

**Important:** Update the API base URL to point to your Laravel backend.

Edit: `mobile_app/lib/services/api_service.dart`

```dart
// For Android Emulator
static const String baseUrl = 'http://10.0.2.2:8000/api';

// For Physical Device on Same Network
static const String baseUrl = 'http://YOUR_IP_ADDRESS:8000/api';

// For Production
static const String baseUrl = 'https://yourdomain.com/api';
```

### Step 3: Run the App

**On Emulator:**
```bash
flutter run
```

**On Physical Device:**
1. Enable USB Debugging on your Android device
2. Connect via USB
3. Run: `flutter run`

**Build APK:**
```bash
# Build debug APK
flutter build apk --debug

# Build release APK
flutter build apk --release

# APK location: build/app/outputs/flutter-apk/
```

---

## 🌐 Port Configuration & Localhost Connectivity

### Default Ports
- **Laravel Backend:** `8000`
- **MySQL Database:** `3306`
- **Web Admin Panel:** Uses browser (no server needed, opens as file)

### Connecting Mobile App to Localhost

#### Option 1: Android Emulator
- Use `10.0.2.2` instead of `127.0.0.1` or `localhost`
- Configure in app: `http://10.0.2.2:8000/api`

#### Option 2: Physical Device (Same WiFi Network)

1. **Find Your Computer's IP Address:**
   ```bash
   # Windows
   ipconfig
   # Look for IPv4 Address (e.g., 192.168.1.100)
   
   # Linux/Mac
   ifconfig
   # or
   ip addr show
   ```

2. **Start Laravel with Network Access:**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

3. **Update Mobile App API URL:**
   ```dart
   static const String baseUrl = 'http://192.168.1.100:8000/api';
   ```

4. **Firewall Configuration:**
   - Allow port 8000 through your firewall
   - **Windows:** Settings → Windows Security → Firewall → Allow an app
   - **Linux:** `sudo ufw allow 8000`

#### Option 3: Using ngrok (For Remote Testing)
```bash
# Install ngrok
# Start Laravel server
php artisan serve

# In another terminal
ngrok http 8000

# Use the ngrok URL in your mobile app
# Example: https://abc123.ngrok.io/api
```

### Troubleshooting Connection Issues

**Mobile app can't connect to backend:**
1. Verify Laravel server is running
2. Check if using correct IP address
3. Ensure both devices are on same WiFi network
4. Verify firewall allows port 8000
5. Test connection in browser: `http://YOUR_IP:8000`

**CORS Issues:**
- Already configured in `backend/config/cors.php`
- Allows all origins in development mode

---

## 🖥️ Web Admin Panel Setup

### Option 1: Direct File Access
```bash
# Simply open in browser
cd web_admin
# Open index.html in your browser
```

### Option 2: Serve via Laravel (Recommended)
The web admin panel is already integrated and accessible at:
```
http://127.0.0.1:8000/admin/login
```

After logging in with admin credentials, you'll have access to:
- Dashboard
- Transactions
- Gallons Management
- Reports
- Employees
- Settings

## 📁 Project Structure

```
├── backend/           # Laravel API
│   ├── app/          # Application logic
│   ├── config/       # Configuration files
│   ├── database/     # Migrations and seeders
│   ├── routes/       # API and web routes
│   └── resources/    # Views (Blade templates)
├── mobile_app/        # Flutter Android App
│   ├── lib/          # Dart source code
│   │   ├── main.dart
│   │   ├── providers/
│   │   ├── screens/
│   │   └── services/
│   └── android/      # Android configuration
├── web_admin/         # Web Admin Panel (integrated in Laravel)
├── database/          # Database schema and dumps
└── docs/              # Documentation
```

## 🔑 API Endpoints

### Authentication
- `POST /api/login` - User login
- `POST /api/logout` - User logout

### Transactions
- `GET /api/transactions` - Get all transactions
- `POST /api/transactions` - Create new transaction
- `GET /api/transactions/{id}` - Get transaction details

### Gallons
- `GET /api/gallons` - Get all gallons
- `POST /api/gallons/scan` - Scan QR code
- `POST /api/gallons/return` - Return gallon
- `GET /api/gallons/status-summary` - Get status summary

### Dashboard
- `GET /api/dashboard` - Get dashboard statistics

For complete API documentation, see `docs/API_DOCUMENTATION.md`

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

## 🔧 Common Tasks

### Reset Database
```bash
cd backend
php artisan migrate:fresh --seed
```

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Generate New QR Codes
Access the admin panel:
1. Login at `http://127.0.0.1:8000/admin/login`
2. Go to "QR Print" section
3. Generate and print QR codes for gallons

### Update Mobile App API URL
Edit `mobile_app/lib/services/api_service.dart`
```dart
static const String baseUrl = 'http://YOUR_IP:8000/api';
```
Then rebuild the app:
```bash
flutter clean
flutter pub get
flutter run
```

### Running Tests
```bash
# Backend tests
cd backend
php artisan test

# Mobile app tests
cd mobile_app
flutter test
```

## 📞 Support

For issues and questions, refer to the documentation in the `docs/` folder.

## 📄 License

This project is developed as a capstone project for educational purposes.
# Automated-Sales-Monitoring-and-Reporting-System-for-Water-Refilling-Businesses

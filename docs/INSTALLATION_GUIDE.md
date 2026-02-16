# Installation Guide - Water Refilling System

## System Requirements

### Backend (Laravel)
- PHP 8.1 or higher
- Composer
- MySQL 8.0 or higher
- Apache/Nginx web server (or use Laravel's built-in server)

### Mobile App (Flutter)
- Flutter SDK 3.0 or higher
- Android Studio (for Android development)
- Android device or emulator (API level 21+)

### Web Admin Panel
- Modern web browser (Chrome, Firefox, Edge, Safari)
- No additional installation required

## Step 1: Database Setup

### 1.1 Create Database
Open MySQL command line or phpMyAdmin and run:

```bash
mysql -u root -p
```

Then create and import the database:

```sql
source path/to/database/schema.sql
```

Or manually:

1. Create database: `water_refilling_system`
2. Import the `schema.sql` file

**Default Credentials:**
- Admin: admin@waterrefilling.local / admin123
- Employee: employee@waterrefilling.local / admin123

### 1.2 Verify Installation
Check if tables are created:

```sql
USE water_refilling_system;
SHOW TABLES;
```

You should see 11 tables.

## Step 2: Backend API Setup

### 2.1 Using XAMPP (Recommended for Windows)

1. **Install XAMPP**
   - Download from: https://www.apachefriends.org/
   - Install and start Apache and MySQL

2. **Copy Backend Files**
   ```bash
   cp -r backend/ C:/xampp/htdocs/water-refilling-backend/
   ```

3. **Install Dependencies**
   ```bash
   cd C:/xampp/htdocs/water-refilling-backend/
   composer install
   ```

4. **Configure Environment**
   ```bash
   cp .env.example .env
   ```

   Edit `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=water_refilling_system
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

6. **Generate JWT Secret**
   ```bash
   php artisan jwt:secret
   ```

7. **Test the API**
   Open browser: http://localhost/water-refilling-backend/public/api/health

   You should see:
   ```json
   {
     "status": "ok",
     "timestamp": "2026-02-09T...",
     "service": "Water Refilling System API"
   }
   ```

### 2.2 Using Laravel's Built-in Server

```bash
cd backend/
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan serve
```

The API will be available at: http://localhost:8000

## Step 3: Web Admin Panel Setup

### 3.1 Using XAMPP

1. Copy web admin files:
   ```bash
   cp -r web_admin/ C:/xampp/htdocs/water-refilling-admin/
   ```

2. Update API URL in `web_admin/assets/js/config.js`:
   ```javascript
   const API_BASE_URL = 'http://localhost/water-refilling-backend/public/api';
   ```

3. Access admin panel:
   http://localhost/water-refilling-admin/

### 3.2 Using Live Server (VS Code)

1. Install "Live Server" extension in VS Code
2. Open `web_admin` folder
3. Right-click `index.html` → "Open with Live Server"
4. Update API URL if needed

**Default Admin Login:**
- Email: admin@waterrefilling.local
- Password: admin123

## Step 4: Mobile App Setup

### 4.1 Install Flutter

1. Download Flutter SDK from: https://flutter.dev/docs/get-started/install
2. Extract and add to PATH
3. Run: `flutter doctor` to verify installation

### 4.2 Setup Android Environment

1. Install Android Studio
2. Install Android SDK and emulator
3. Accept licenses: `flutter doctor --android-licenses`

### 4.3 Build and Run App

1. **Update API URL**
   Edit `mobile_app/lib/services/api_service.dart`:
   ```dart
   static const String baseUrl = 'http://10.0.2.2:8000/api'; // For emulator
   // OR
   static const String baseUrl = 'http://192.168.1.100:8000/api'; // For physical device
   ```

2. **Get Dependencies**
   ```bash
   cd mobile_app/
   flutter pub get
   ```

3. **Run on Emulator**
   ```bash
   flutter run
   ```

4. **Build APK for Physical Device**
   ```bash
   flutter build apk --release
   ```
   
   APK will be in: `mobile_app/build/app/outputs/flutter-apk/app-release.apk`

**Default Employee Login:**
- Email: employee@waterrefilling.local
- Password: admin123

## Step 5: Testing the System

### 5.1 Test Backend API

Use Postman or curl:

```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@waterrefilling.local","password":"admin123"}'

# Get Dashboard (replace TOKEN)
curl http://localhost:8000/api/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 5.2 Test Web Admin

1. Open web admin panel
2. Login with admin credentials
3. Check dashboard loads correctly
4. Navigate through all pages

### 5.3 Test Mobile App

1. Launch mobile app
2. Login with employee credentials
3. Test QR code scanning
4. Create a test transaction
5. Return a gallon

## Troubleshooting

### Backend Issues

**"Class 'Tymon\JWTAuth' not found"**
```bash
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

**Database Connection Error**
- Check MySQL is running
- Verify database credentials in `.env`
- Ensure database exists

**CORS Error from Web/Mobile**
Install Laravel CORS package:
```bash
composer require fruitcake/laravel-cors
```

Add to `config/cors.php`:
```php
'allowed_origins' => ['*'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

### Mobile App Issues

**Cannot connect to API**
- Use `10.0.2.2` for Android emulator
- Use your computer's IP address for physical devices
- Ensure firewall allows connections on port 8000

**QR Scanner not working**
Add permissions to `android/app/src/main/AndroidManifest.xml`:
```xml
<uses-permission android:name="android.permission.CAMERA" />
<uses-feature android:name="android.hardware.camera" />
```

### Web Admin Issues

**Cannot login / API errors**
- Check API URL in `config.js`
- Open browser console for error messages
- Verify backend is running

## Production Deployment

### Backend
1. Use a production web server (Apache/Nginx)
2. Set `APP_ENV=production` in `.env`
3. Set `APP_DEBUG=false`
4. Run: `php artisan config:cache`
5. Set up SSL certificate
6. Enable HTTPS

### Web Admin
1. Deploy to web hosting
2. Update API URL to production backend
3. Enable HTTPS

### Mobile App
1. Build release APK
2. Sign with keystore
3. Upload to Google Play Store or distribute directly

## Next Steps

1. Change default passwords (or create new accounts via signup)
2. Configure system settings in the Settings page
3. Test the cache clearing functionality
4. Add more gallons if needed
5. Create employee accounts using the signup feature
6. Start using the system!

For more information, see:
- USER_MANUAL.md
- API_DOCUMENTATION.md
- QUICK_START.md

# Quick Start Guide - Water Refilling System

## 🚀 Get Started in 10 Minutes

This guide will help you set up and start using the Water Refilling System quickly.

## Prerequisites Checklist

Before starting, ensure you have:

- [ ] XAMPP installed (includes Apache, MySQL, PHP)
- [ ] Composer installed
- [ ] Modern web browser
- [ ] (Optional) Flutter SDK for mobile app

## Step 1: Database Setup (2 minutes)

1. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start Apache and MySQL

2. **Import Database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Click "New" to create database
   - Database name: `water_refilling_system`
   - Click "Import" tab
   - Choose file: `database/schema.sql`
   - Click "Go"

✅ **Verify:** Check if you see 11 tables created

## Step 2: Backend Setup (3 minutes)

1. **Copy backend files to XAMPP**
   ```bash
   # Copy backend folder to htdocs
   xcopy /E /I backend C:\xampp\htdocs\water-refilling-api
   ```

2. **Install dependencies**
   ```bash
   cd C:\xampp\htdocs\water-refilling-api
   composer install
   ```

3. **Configure environment**
   ```bash
   copy .env.example .env
   php artisan key:generate
   php artisan jwt:secret
   ```

4. **Test API**
   - Open: http://localhost/water-refilling-api/public/api/health
   - You should see: `{"status":"ok",...}`

✅ **Verify:** API health check returns "ok"

## Step 3: Web Admin Setup (2 minutes)

1. **Copy web admin files**
   ```bash
   xcopy /E /I web_admin C:\xampp\htdocs\water-admin
   ```

2. **Update API URL**
   - Open: `C:\xampp\htdocs\water-admin\assets\js\config.js`
   - Update:
   ```javascript
   const API_BASE_URL = 'http://localhost/water-refilling-api/public/api';
   ```

3. **Access admin panel**
   - Open: http://localhost/water-admin

4. **Login with default credentials**
   - Email: `admin@waterrefilling.local`
   - Password: `admin123`
   - Or click **"Sign Up"** to create a new account

✅ **Verify:** You can see the dashboard

## Step 4: Mobile App Setup (Optional - 3 minutes)

1. **Update API URL**
   - Open: `mobile_app/lib/services/api_service.dart`
   - Change:
   ```dart
   static const String baseUrl = 'http://10.0.2.2:8000/api';
   ```
   to:
   ```dart
   static const String baseUrl = 'http://localhost/water-refilling-api/public/api';
   ```

2. **Install dependencies**
   ```bash
   cd mobile_app
   flutter pub get
   ```

3. **Run app**
   ```bash
   flutter run
   ```

4. **Login**
   - Email: `employee@waterrefilling.local`
   - Password: `admin123`

✅ **Verify:** You can see the employee dashboard

## ✨ You're Ready!

### Test the System

1. **Create a test transaction (Mobile App)**
   - Tap "New Transaction"
   - Scan gallon: `WR-GAL-0001` (or manually enter)
   - Enter customer: "Juan Dela Cruz"
   - Select type: Walk-in
   - Payment: Cash
   - Create Transaction

2. **View in Admin Panel**
   - Refresh dashboard
   - Check "Recent Transactions"
   - Go to Transactions page
   - View the transaction you just created

3. **Return a gallon (Mobile App)**
   - Tap "Return Gallon"
   - Scan gallon code
   - Confirm return

4. **Generate Report (Web Admin)**
   - Go to Reports
   - Click "Generate" for Daily Report
   - View the results

## 🎯 What's Next?

### For Business Use

1. **Change Default Passwords**
   - Create new admin and employee accounts using the signup feature
   - Disable or delete default accounts for security

2. **Add Your Gallons**
   - Print QR codes for your gallon containers
   - Add gallon codes to database if not using the default WR-GAL-XXXX format

3. **Configure Settings**
   - Update business name and contact info
   - Adjust overdue thresholds
   - Set gallon prices

4. **Train Your Staff**
   - Show employees how to use the mobile app
   - Practice scanning QR codes
   - Run test transactions

### For Development

- Read `docs/API_DOCUMENTATION.md` for API details
- Explore the code structure
- Customize the UI/UX
- Add new features

## 📱 Generating QR Codes for Gallons

You can use free online QR code generators:

1. Go to: https://www.qr-code-generator.com/
2. Select "Text"
3. Enter gallon code (e.g., `WR-GAL-0051`)
4. Download and print
5. Stick on gallon container

**Tip:** Generate all gallons codes at once using a spreadsheet and batch QR generator.

## 🛠️ Common Issues

### "Can't connect to database"
- Check if MySQL is running in XAMPP
- Verify database name in `.env` file
- Check username/password (default: root with no password)

### "API returns 404"
- Verify API URL is correct
- Check if Apache is running
- Try: `http://localhost/water-refilling-api/public/api/health`

### "Token expired" or "Unauthorized"
- Re-login to get a new token
- Check if JWT secret is generated: `php artisan jwt:secret`

### Mobile app can't connect
- Use `10.0.2.2` for emulator
- Use computer's IP address for physical device
- Ensure firewall allows connections

## 📞 Need Help?

- Check `docs/INSTALLATION_GUIDE.md` for detailed setup
- Read `docs/USER_MANUAL.md` for usage instructions
- Review `docs/API_DOCUMENTATION.md` for API reference

## 🎉 Success Checklist

- [ ] Database created and populated
- [ ] Backend API running and accessible
- [ ] Web admin panel working
- [ ] Can login as admin
- [ ] Can view dashboard
- [ ] (Optional) Mobile app running
- [ ] Can create test transaction
- [ ] Can generate reports

**Congratulations! Your water refilling system is ready to use! 🚰💧**

---

## Quick Reference

### Default URLs
- **API**: http://localhost/water-refilling-api/public/api
- **Web Admin**: http://localhost/water-admin
- **API Health**: http://localhost/water-refilling-api/public/api/health

### Default Credentials
| Role     | Email                           | Password  |
|----------|----------------------------------|-----------|
| Admin    | admin@waterrefilling.local      | admin123  |
| Employee | employee@waterrefilling.local   | admin123  |

### Sample Gallon Codes
```
WR-GAL-0001 to WR-GAL-0050
```

### Important Files
- Backend env: `backend/.env`
- API config: `web_admin/assets/js/config.js`
- Mobile API: `mobile_app/lib/services/api_service.dart`

---

**Version:** 1.0.0  
**Last Updated:** February 9, 2026

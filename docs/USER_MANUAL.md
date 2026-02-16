# User Manual - Water Refilling System

## Table of Contents

1. [System Overview](#system-overview)
2. [Mobile App Guide (Employee)](#mobile-app-guide)
3. [Web Admin Guide (Admin)](#web-admin-guide)
4. [Business Workflow](#business-workflow)
5. [FAQs](#faqs)

## System Overview

The Water Refilling System is designed to automate and streamline operations for water refilling businesses. It consists of:

- **Mobile App**: For employees to process transactions and manage daily operations
- **Web Admin Panel**: For administrators to monitor sales, inventory, and generate reports
- **Automated Tracking**: QR code-based gallon tracking to prevent losses

### User Roles

#### Employee
- Process walk-in, delivery, and refill-only transactions
- Scan QR codes on gallon containers
- Return gallons to inventory
- View daily summaries

#### Admin
- Monitor dashboard and analytics
- View all transactions
- Track gallon inventory and status
- Generate reports (daily, weekly, monthly)
- Manage system settings

---

## Mobile App Guide

### 1. Login

1. Open the Water Refilling app
2. Enter your email and password
3. Tap "LOGIN"

**Default Employee Credentials:**
- Email: employee@waterrefilling.local
- Password: admin123

### 2. Dashboard

After login, you'll see the dashboard with:
- Welcome message with your name
- Today's summary (transactions, revenue, gallons sold)
- Gallon inventory status
- Quick action buttons

**Quick Actions:**
- **New Transaction**: Process a new sale
- **Return Gallon**: Return borrowed gallons
- **Inventory**: View gallon status
- **Daily Summary**: View detailed daily report

### 3. Creating a New Transaction

#### Step-by-Step Process:

1. **Tap "New Transaction"** from the dashboard

2. **Scan Gallon QR Codes**
   - Tap "SCAN" button
   - Point camera at gallon QR code
   - Wait for beep/confirmation
   - Gallon will be added to list
   - Repeat for multiple gallons

3. **Enter Customer Information**
   - Customer Name (required)
   - Customer Phone (optional)
   - Select Transaction Type:
     - **Walk-in**: Customer visits station
     - **Delivery**: Delivered to customer
     - **Refill-only**: Refill without gallon exchange
   - If Delivery, enter address

4. **Select Payment Method**
   - Cash (default)
   - GCash
   - Card
   - Bank Transfer

5. **Verify Unit Price**
   - Default: ₱25.00
   - Can be adjusted if needed

6. **Add Notes** (optional)
   - Any special instructions or remarks

7. **Review Transaction**
   - Check scanned gallons
   - Verify total amount
   - Remove any gallon if scanned by mistake

8. **Tap "CREATE TRANSACTION"**
   - Transaction is processed
   - Gallons are marked as OUT
   - Success message displayed

**Important Notes:**
- ❌ Cannot borrow gallons that are already OUT
- ✅ At least 1 gallon must be scanned
- ✅ Customer name is required
- ✅ Delivery address required for delivery type

### 4. Returning a Gallon

When a customer returns borrowed gallons:

1. **Tap "Return Gallon"** from dashboard
2. **Tap "SCAN QR CODE"** button
3. **Scan the gallon's QR code**
4. **Confirm return** when prompted
5. Gallon status updated to IN

**Important:**
- ❌ Cannot return gallons already IN
- ✅ Only borrowed (OUT) gallons can be returned

### 5. Viewing Inventory

1. Tap "Inventory" from dashboard
2. View gallon status breakdown:
   - **In Station**: Available gallons
   - **Out**: Currently borrowed
   - **Overdue**: Late returns
   - **Missing**: Lost/unreturned gallons
3. Pull down to refresh

### 6. Daily Summary

View detailed breakdown of today's sales:
- Total transactions
- Total revenue
- Gallons sold
- Breakdown by:
  - Transaction type (walk-in, delivery, refill)
  - Payment method (cash, GCash, etc.)

### 7. Logging Out

1. Tap the menu icon (☰) or back button to dashboard
2. Tap logout icon in the top right
3. Confirm logout

---

## Web Admin Guide

### 1. Admin Login

1. Open web browser
2. Navigate to the admin panel URL
3. Enter admin credentials
4. Click "LOGIN"

**Default Admin Credentials:**
- Email: admin@waterrefilling.local
- Password: admin123

### 2. User Registration (Signup)

New users can register for an account:

1. On the login page, click **"Sign Up"** button
2. Fill in the registration form:
   - **Name**: Your full name
   - **Email**: Valid email address (will be used for login)
   - **Role**: Select either:
     - **Admin**: Full system access
     - **Employee**: Mobile app access only
   - **Password**: Minimum 8 characters
   - **Confirm Password**: Must match password
3. Click **"SIGN UP"** to create account
4. You will be redirected to login page
5. Use your new credentials to log in

**Important Notes:**
- Email must be unique (not already registered)
- Password must be at least 8 characters
- After signup, you'll need to login with your new credentials
- Only admins can access the web admin panel
- Employees can only use the mobile app

### 3. Dashboard

The admin dashboard provides a comprehensive overview:

#### Statistics Cards
- **Today's Transactions**: Number of sales today
- **Today's Revenue**: Total earnings today
- **Gallons Sold**: Total gallons sold today
- **Overdue Gallons**: Gallons past return date

#### Charts
- **Weekly Sales Trend**: Line chart showing 7-day revenue
- **Transaction Types**: Pie chart of transaction distribution

#### Gallon Inventory
- Progress bars showing:
  - Gallons in station
  - Gallons out (borrowed)
  - Missing gallons

#### Recent Transactions
- Last 10 transactions with details
- Click to view full transaction details

### 4. Viewing Transactions

Navigate to **Transactions** page:

#### Features:
- **Filter by Type**: Walk-in, Delivery, Refill-only
- **Filter by Date**: Select specific date
- **Pagination**: Browse all transactions
- **View Details**: Click eye icon to see full details

#### Transaction Details Include:
- Transaction code
- Customer information
- List of gallons scanned
- Payment details
- Employee who processed
- Total amount

### 5. Monitoring Gallons

Navigate to **Gallons** page (*to be implemented*):

- View all gallon containers
- Filter by status (IN, OUT, MISSING)
- Search by gallon code
- View gallon history and movement

### 6. Generating Reports

Navigate to **Reports** page:

#### Daily Report
1. Select date
2. Click "Generate Report"
3. View or print report

#### Weekly Report
1. Click "Generate Report"
2. Shows current week data
3. Includes daily averages

#### Monthly Report
1. Select month and year
2. Click "Generate Report"
3. Comprehensive monthly analysis

#### Report Contents:
- Total transactions and revenue
- Gallons sold
- Breakdown by transaction type
- Percentage analysis
- Printable format

### 7. Managing Settings

Navigate to **Settings** page:

#### System Configuration

Update various system settings:
- **Overdue Days Threshold**: Days before gallon is marked overdue (default: 7)
- **Missing Days Threshold**: Days before gallon is marked missing (default: 30)
- **Gallon Price**: Default price per gallon (default: ₱25.00)
- **Delivery Fee**: Additional fee for deliveries (default: ₱0.00)

#### Clear Cache Quick Action

Clear system cache to improve performance or resolve issues:

1. In the **Quick Actions** section, find **"Clear Cache"**
2. Click the **"Clear Cache"** button
3. A confirmation modal will appear
4. Enter your **admin password** for verification
5. Click **"Clear Cache"** to confirm
6. System will clear:
   - Application cache
   - Configuration cache
   - Route cache
   - View cache
7. Success message will appear

**When to Clear Cache:**
- ✅ After updating system settings
- ✅ When experiencing slow performance
- ✅ After code changes or updates
- ✅ When configuration changes don't take effect
- ⚠️ Requires admin password for security

**Important:**
- Cache clearing requires your current password
- Only admins can clear cache
- This action affects all users temporarily
- Performance may be slightly slower until cache rebuilds

### 8. Logout

Click your profile icon → **Logout**

---

## Business Workflow

### Daily Operations

#### Morning Setup
1. Employee logs into mobile app
2. Checks inventory status
3. Reviews any overdue gallons

#### Customer Walk-in
1. Customer arrives with empty gallons
2. Employee creates new transaction
3. Scans customer's empty gallons (returns them)
4. Fills new gallons
5. Scans filled gallons
6. Processes payment
7. Completes transaction

#### Delivery Order
1. Customer calls for delivery
2. Employee creates delivery transaction
3. Enters customer address
4. Scans gallons to be delivered
5. Selects payment method
6. Adds delivery notes
7. Completes transaction
8. Delivers to customer

#### Gallon Return
1. Customer returns borrowed gallon
2. Employee scans gallon QR code
3. Confirms return
4. Gallon marked as IN

#### End of Day
1. Employee reviews daily summary
2. Admin checks dashboard
3. Admin generates daily report
4. Reviews any alerts (overdue/missing gallons)

### Weekly Tasks

#### Admin Tasks
- Generate weekly report
- Review sales trends
- Check gallon inventory levels
- Follow up on overdue gallons
- Update system settings if needed

### Monthly Tasks

#### Admin Tasks
- Generate monthly report
- Analyze performance metrics
- Review missing gallons
- Plan inventory restocking
- Export data for accounting

---

## FAQs

### General Questions

**Q: What happens if I scan the wrong gallon?**
A: You can remove it from the list before completing the transaction. Tap the delete icon next to the gallon code.

**Q: Can I edit a transaction after it's created?**
A: No, transactions cannot be edited for audit purposes. Create a new transaction if corrections are needed.

**Q: What if the QR code is damaged or unreadable?**
A: Contact the administrator to generate a new QR code or assign a new gallon code.

### Mobile App Questions

**Q: Why can't I scan a gallon?**
A: Possible reasons:
- Gallon is already OUT (already borrowed)
- QR code is damaged
- Camera permission not granted
- Solution: Check gallon status in inventory

**Q: App shows "Connection error"**
A: 
- Check internet connection
- Verify backend server is running
- Contact administrator

**Q: How do I change my password?**
A: Contact administrator to reset your password

### Web Admin Questions

**Q: Dashboard data not updating**
A: Click the refresh button or reload the page. Data auto-refreshes every 30 seconds.

**Q: Can't login to admin panel**
A: 
- Verify you're using the admin account
- Check credentials
- Ensure backend API is running

**Q: How to export reports?**
A: Click the "Print" button on the report page, then save as PDF.

### Gallon Tracking Questions

**Q: What does "Overdue" mean?**
A: A gallon is overdue when it hasn't been returned within the configured days (default: 7 days).

**Q: What does "Missing" mean?**
A: A gallon is missing when it's been out for too long (default: 30 days) and considered lost.

**Q: How to mark found missing gallons?**
A: Simply scan and return the gallon. Status will automatically update to IN.

**Q: Can I track which customer has a specific gallon?**
A: Yes, view the gallon's history or the last transaction details to see customer information.

### Troubleshooting

**Q: "Gallon is already OUT" error**
A: This gallon is currently borrowed. It must be returned first before being used in a new transaction.

**Q: Transaction failed**
A: 
- Check internet connection
- Verify all required fields are filled
- Ensure at least one gallon is scanned
- Contact administrator if issue persists

**Q: QR scanner not working**
A: 
- Grant camera permission in phone settings
- Ensure adequate lighting
- Clean camera lens
- Update app if available

---

## Support

For technical support or questions:
- Contact your system administrator
- Refer to the INSTALLATION_GUIDE.md for setup issues
- Check API_DOCUMENTATION.md for developer information

---

**Important Reminders:**
- ✅ Always scan gallon QR codes accurately
- ✅ Return gallons promptly to avoid overdue status
- ✅ Generate regular reports for business insights
- ✅ Keep default passwords changed for security
- ✅ Backup data regularly

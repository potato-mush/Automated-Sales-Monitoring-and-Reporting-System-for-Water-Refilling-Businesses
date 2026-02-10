# API Documentation - Water Refilling System

## Base URL
```
http://localhost:8000/api
```

## Authentication

The API uses JWT (JSON Web Token) authentication. Include the token in the Authorization header:

```
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## Authentication Endpoints

### 1. Login
**POST** `/login`

Authenticate user and receive JWT token.

**Request Body:**
```json
{
    "email": "admin@waterrefilling.local",
    "password": "admin123"
}
```

**Response (200 OK):**
```json
{
    "message": "Login successful",
    "user": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@waterrefilling.local",
        "role": "admin"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer"
}
```

**Errors:**
- `401` - Invalid credentials
- `403` - Account deactivated

---

### 2. Logout
**POST** `/logout`

Invalidate the current token.

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response (200 OK):**
```json
{
    "message": "Successfully logged out"
}
```

---

### 3. Get Current User
**GET** `/me`

Get authenticated user information.

**Response (200 OK):**
```json
{
    "user": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@waterrefilling.local",
        "role": "admin"
    }
}
```

---

### 4. Refresh Token
**POST** `/refresh`

Refresh JWT token.

**Response (200 OK):**
```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer"
}
```

---

## Dashboard Endpoints

### 1. Get Dashboard Overview
**GET** `/dashboard`

Get comprehensive dashboard data.

**Response (200 OK):**
```json
{
    "today": {
        "transactions": 15,
        "revenue": 375.00,
        "gallons_sold": 15
    },
    "week": {
        "transactions": 98,
        "revenue": 2450.00
    },
    "month": {
        "transactions": 425,
        "revenue": 10625.00
    },
    "gallons": {
        "total": 50,
        "in_station": 32,
        "out": 15,
        "missing": 3,
        "overdue": 5
    },
    "recent_transactions": [...]
}
```

---

### 2. Get Sales Chart Data
**GET** `/dashboard/sales-chart`

Get sales trend data for charts.

**Query Parameters:**
- `period` (optional): `week` or `month` (default: `week`)

**Response (200 OK):**
```json
[
    {
        "date": "2026-02-03",
        "label": "Feb 03",
        "transactions": 12,
        "revenue": 300.00,
        "gallons": 12
    },
    ...
]
```

---

### 3. Get Transaction Type Breakdown
**GET** `/dashboard/transaction-type-breakdown`

Get transaction statistics by type.

**Query Parameters:**
- `period` (optional): `today`, `week`, or `month` (default: `today`)

**Response (200 OK):**
```json
{
    "walk_in": {
        "count": 8,
        "revenue": 200.00
    },
    "delivery": {
        "count": 5,
        "revenue": 125.00
    },
    "refill_only": {
        "count": 2,
        "revenue": 50.00
    }
}
```

---

### 4. Daily Report
**GET** `/dashboard/daily-report`

Generate daily sales report.

**Query Parameters:**
- `date` (optional): Date in `YYYY-MM-DD` format (default: today)

**Response (200 OK):**
```json
{
    "date": "2026-02-09",
    "total_transactions": 15,
    "total_revenue": 375.00,
    "total_gallons": 15,
    "by_type": {
        "walk_in": {...},
        "delivery": {...},
        "refill_only": {...}
    },
    "by_payment": {
        "cash": 300.00,
        "gcash": 75.00,
        "card": 0.00,
        "bank_transfer": 0.00
    },
    "transactions": [...]
}
```

---

### 5. Weekly Report
**GET** `/dashboard/weekly-report`

Generate weekly sales report.

**Response (200 OK):**
```json
{
    "period": "week",
    "start_date": "2026-02-03",
    "end_date": "2026-02-09",
    "total_transactions": 98,
    "total_revenue": 2450.00,
    "total_gallons": 98,
    "average_daily_revenue": 350.00,
    "by_type": {...}
}
```

---

### 6. Monthly Report
**GET** `/dashboard/monthly-report`

Generate monthly sales report.

**Query Parameters:**
- `month` (optional): 1-12 (default: current month)
- `year` (optional): Year (default: current year)

**Response (200 OK):**
```json
{
    "period": "month",
    "month": 2,
    "year": 2026,
    "start_date": "2026-02-01",
    "end_date": "2026-02-28",
    "total_transactions": 425,
    "total_revenue": 10625.00,
    "total_gallons": 425,
    "average_daily_revenue": 379.46,
    "by_type": {...}
}
```

---

## Transaction Endpoints

### 1. Get All Transactions
**GET** `/transactions`

Get paginated list of transactions.

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 15)
- `type` (optional): Filter by type (`walk-in`, `delivery`, `refill-only`)
- `date` (optional): Filter by date (`YYYY-MM-DD`)

**Response (200 OK):**
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "transaction_code": "TXN-20260209-0001",
            "customer_name": "Juan Dela Cruz",
            "customer_phone": "09171234567",
            "transaction_type": "walk-in",
            "payment_method": "cash",
            "quantity": 3,
            "unit_price": 25.00,
            "total_amount": 75.00,
            "employee_id": 2,
            "created_at": "2026-02-09T10:30:00",
            "employee": {
                "id": 2,
                "name": "Employee One"
            }
        },
        ...
    ],
    "last_page": 5,
    "per_page": 15,
    "total": 73
}
```

---

### 2. Get Single Transaction
**GET** `/transactions/{id}`

Get detailed transaction information.

**Response (200 OK):**
```json
{
    "id": 1,
    "transaction_code": "TXN-20260209-0001",
    "customer_name": "Juan Dela Cruz",
    "customer_phone": "09171234567",
    "customer_address": null,
    "transaction_type": "walk-in",
    "payment_method": "cash",
    "quantity": 3,
    "unit_price": 25.00,
    "total_amount": 75.00,
    "employee": {...},
    "items": [
        {
            "id": 1,
            "gallon_id": 15,
            "action": "BORROW",
            "gallon": {
                "id": 15,
                "gallon_code": "WR-GAL-0015",
                "status": "OUT"
            }
        },
        ...
    ],
    "created_at": "2026-02-09T10:30:00"
}
```

---

### 3. Create New Transaction
**POST** `/transactions`

Create a new transaction.

**Request Body:**
```json
{
    "customer_name": "Juan Dela Cruz",
    "customer_phone": "09171234567",
    "customer_address": "123 Main St, City",
    "transaction_type": "delivery",
    "payment_method": "cash",
    "unit_price": 25.00,
    "gallon_codes": ["WR-GAL-0001", "WR-GAL-0002"],
    "notes": "Deliver in the morning"
}
```

**Response (201 Created):**
```json
{
    "message": "Transaction created successfully",
    "transaction": {...}
}
```

**Errors:**
- `422` - Validation error
- `422` - One or more gallon codes invalid
- `422` - Cannot borrow gallons that are already OUT

---

### 4. Today's Summary
**GET** `/transactions/today-summary`

Get today's transaction summary.

**Response (200 OK):**
```json
{
    "total_transactions": 15,
    "total_revenue": 375.00,
    "total_gallons": 15,
    "by_type": {...},
    "by_payment": {...}
}
```

---

### 5. Transaction Statistics
**GET** `/transactions/statistics`

Get transaction statistics for a period.

**Query Parameters:**
- `period` (optional): `today`, `week`, or `month` (default: `today`)

**Response (200 OK):**
```json
{
    "period": "today",
    "total_transactions": 15,
    "total_revenue": 375.00,
    "total_gallons": 15,
    "average_transaction": 25.00,
    "by_type": {
        "walk_in": 10,
        "delivery": 3,
        "refill_only": 2
    }
}
```

---

## Gallon Endpoints

### 1. Get All Gallons
**GET** `/gallons`

Get paginated list of gallons.

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page (default: 50)
- `status` (optional): Filter by status (`IN`, `OUT`, `MISSING`)
- `search` (optional): Search by gallon code

**Response (200 OK):**
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "gallon_code": "WR-GAL-0001",
            "status": "IN",
            "last_transaction_id": null,
            "last_borrowed_date": null,
            "is_overdue": false,
            "overdue_days": 0,
            "created_at": "2026-02-01T00:00:00"
        },
        ...
    ],
    "total": 50
}
```

---

### 2. Get Single Gallon
**GET** `/gallons/{code}`

Get gallon details by code.

**Response (200 OK):**
```json
{
    "id": 1,
    "gallon_code": "WR-GAL-0001",
    "status": "OUT",
    "last_borrowed_date": "2026-02-09T10:30:00",
    "is_overdue": false,
    "overdue_days": 0,
    "last_transaction": {
        "id": 15,
        "transaction_code": "TXN-20260209-0001",
        "customer_name": "Juan Dela Cruz",
        "customer_phone": "09171234567"
    },
    "logs": [...]
}
```

---

### 3. Scan Gallon
**POST** `/gallons/scan`

Scan a gallon QR code to retrieve information.

**Request Body:**
```json
{
    "gallon_code": "WR-GAL-0001"
}
```

**Response (200 OK):**
```json
{
    "exists": true,
    "gallon": {
        "id": 1,
        "gallon_code": "WR-GAL-0001",
        "status": "IN",
        ...
    }
}
```

**Response (404 Not Found):**
```json
{
    "message": "Gallon not found",
    "exists": false
}
```

---

### 4. Return Gallon
**POST** `/gallons/return`

Return a borrowed gallon to the station.

**Request Body:**
```json
{
    "gallon_code": "WR-GAL-0001"
}
```

**Response (200 OK):**
```json
{
    "message": "Gallon returned successfully",
    "gallon": {...}
}
```

**Errors:**
- `404` - Gallon not found
- `422` - Gallon is already in the station

---

### 5. Get Gallon Status Summary
**GET** `/gallons/status-summary`

Get summary of gallon statuses.

**Response (200 OK):**
```json
{
    "total": 50,
    "in_station": 32,
    "out": 15,
    "missing": 3,
    "overdue": 5
}
```

---

### 6. Get Overdue Gallons
**GET** `/gallons/overdue`

Get list of overdue gallons.

**Response (200 OK):**
```json
[
    {
        "id": 15,
        "gallon_code": "WR-GAL-0015",
        "status": "OUT",
        "overdue_days": 10,
        "last_borrowed_date": "2026-01-30T10:00:00",
        "last_transaction": {
            "customer_name": "Juan Dela Cruz",
            "customer_phone": "09171234567"
        }
    },
    ...
]
```

---

### 7. Get Missing Gallons
**GET** `/gallons/missing`

Get list of missing gallons.

**Response (200 OK):**
```json
[
    {
        "id": 20,
        "gallon_code": "WR-GAL-0020",
        "status": "MISSING",
        "last_borrowed_date": "2026-01-05T14:00:00",
        "last_transaction": {...}
    },
    ...
]
```

---

### 8. Get Gallon History
**GET** `/gallons/{code}/history`

Get movement history for a specific gallon.

**Response (200 OK):**
```json
[
    {
        "id": 45,
        "gallon_id": 1,
        "action": "BORROW",
        "old_status": "IN",
        "new_status": "OUT",
        "transaction": {
            "transaction_code": "TXN-20260209-0001",
            "customer_name": "Juan Dela Cruz"
        },
        "performer": {
            "name": "Employee One"
        },
        "created_at": "2026-02-09T10:30:00"
    },
    ...
]
```

---

### 9. Update Overdue Gallons
**POST** `/gallons/update-overdue`

Manually trigger overdue status update (usually run as cron job).

**Response (200 OK):**
```json
{
    "message": "Overdue status updated",
    "processed": 15
}
```

---

## Error Responses

### Standard Error Format

```json
{
    "message": "Error description",
    "errors": {
        "field_name": ["Error message"]
    }
}
```

### HTTP Status Codes

- `200` - OK
- `201` - Created
- `401` - Unauthorized (invalid/missing token)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## Rate Limiting

Currently not implemented. For production, consider adding rate limiting.

---

## Testing with Postman

1. Import the API endpoints into Postman
2. Create an environment variable for `base_url` and `token`
3. Login to get token
4. Use token for authenticated requests

**Example Environment:**
```json
{
    "base_url": "http://localhost:8000/api",
    "token": "your_jwt_token_here"
}
```

---

## Webhooks / Real-time Features

Currently not implemented. Future versions may include:
- WebSocket connections for real-time updates
- Webhook notifications for overdue gallons
- Push notifications for mobile app

---

## Changelog

### Version 1.0.0 (2026-02-09)
- Initial API release
- Authentication endpoints
- Transaction management
- Gallon tracking
- Dashboard and reporting

---

For more information, see:
- INSTALLATION_GUIDE.md
- USER_MANUAL.md

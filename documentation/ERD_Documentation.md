# Car Detailing System - Entity Relationship Diagram (ERD)
## Database Structure Documentation

### 📊 CURRENT DATABASE TABLES (9 Tables)

---

## 1. 👤 USERS (Central Entity)
```
users
├── id (PK, INT, AUTO_INCREMENT)
├── google_id (VARCHAR(255), UNIQUE)
├── username (VARCHAR(50), UNIQUE)
├── email (VARCHAR(100), UNIQUE)
├── password (VARCHAR(255))
├── first_name (VARCHAR(50))
├── last_name (VARCHAR(50))
├── phone (VARCHAR(20))
├── role (ENUM: 'admin', 'user')
├── address (TEXT)
├── date_of_birth (DATE)
├── profile_picture (VARCHAR(255))
├── email_verified (BOOLEAN)
├── last_login (TIMESTAMP)
├── is_active (BOOLEAN)
├── reset_token (VARCHAR(255))
├── reset_expires (DATETIME)
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)
```

---

## 2. 🚗 SERVICES
```
services
├── service_id (PK, INT, AUTO_INCREMENT)
├── category (VARCHAR(50))
├── service_name (VARCHAR(100))
├── description (TEXT)
├── price_small (DECIMAL(10,2))
├── price_medium (DECIMAL(10,2))
├── price_large (DECIMAL(10,2))
├── duration_minutes (INT)
├── included_items (TEXT)
├── free_items (TEXT)
├── is_active (BOOLEAN)
└── created_at (TIMESTAMP)
```

---

## 3. 📅 BOOKINGS (Junction Entity)
```
bookings
├── booking_id (PK, INT, AUTO_INCREMENT)
├── user_id (FK → users.id)
├── service_id (FK → services.service_id)
├── vehicle_size (ENUM: 'small', 'medium', 'large')
├── booking_date (DATETIME)
├── status (ENUM: 'pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'declined')
├── total_amount (DECIMAL(10,2))
├── payment_status (ENUM: 'pending', 'paid', 'refunded')
├── payment_verification_status (ENUM: 'pending', 'verified', 'rejected')
├── vehicle_details (TEXT)
├── special_requests (TEXT)
├── admin_notes (TEXT)
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)
```

---

## 4. 💳 PAYMENTS
```
payments
├── payment_id (PK, INT, AUTO_INCREMENT)
├── booking_id (FK → bookings.booking_id)
├── user_id (FK → users.id)
├── amount (DECIMAL(10,2))
├── payment_method (ENUM: 'cash', 'card', 'gcash', 'bank_transfer')
├── payment_status (ENUM: 'pending', 'completed', 'failed', 'refunded')
├── payment_type (ENUM: 'partial', 'full')
├── transaction_id (VARCHAR(100))
├── payment_proof_path (VARCHAR(255))
├── verification_status (ENUM: 'pending', 'verified', 'rejected')
├── verified_by (FK → users.id)
├── verified_at (TIMESTAMP)
├── rejection_reason (TEXT)
├── payment_date (TIMESTAMP)
└── notes (TEXT)
```

---

## 5. ⭐ REVIEWS
```
reviews
├── review_id (PK, INT, AUTO_INCREMENT)
├── user_id (FK → users.id)
├── booking_id (FK → bookings.booking_id)
├── service_id (FK → services.service_id)
├── rating (INT: 1-5)
├── review_text (TEXT)
├── is_approved (BOOLEAN)
├── admin_response (TEXT)
└── created_at (TIMESTAMP)
```

---

## 6. 🔔 NOTIFICATIONS
```
notifications
├── notification_id (PK, INT, AUTO_INCREMENT)
├── user_id (FK → users.id, NULL for admin notifications)
├── type (VARCHAR(50))
├── title (VARCHAR(255))
├── message (TEXT)
├── is_read (BOOLEAN)
├── related_id (INT) -- Generic reference to booking/payment/etc
├── action_url (VARCHAR(255))
└── created_at (TIMESTAMP)
```

---

## 7. 📝 ACTIVITY_LOGS
```
activity_logs
├── log_id (PK, INT, AUTO_INCREMENT)
├── user_id (FK → users.id)
├── admin_id (FK → users.id)
├── action (VARCHAR(100))
├── description (TEXT)
├── table_name (VARCHAR(50))
├── record_id (INT)
├── ip_address (VARCHAR(45))
└── created_at (TIMESTAMP)
```

---

## 8. 💰 PAYMENT_LOGS (Audit Trail)
```
payment_logs
├── log_id (PK, INT, AUTO_INCREMENT)
├── payment_id (FK → payments.payment_id)
├── action (ENUM: 'created', 'verified', 'rejected', 'updated')
├── performed_by (FK → users.id)
├── details (TEXT)
└── created_at (TIMESTAMP)
```

---

## 9. 🔍 ADMIN_PAYMENT_VERIFICATION (View)
```
admin_payment_verification (VIEW)
├── payment_id
├── booking_id
├── booking_date
├── username
├── first_name
├── last_name
├── service_name
├── amount
├── payment_method
├── payment_type
├── transaction_id
├── payment_proof_path
├── verification_status
├── payment_date
└── has_proof
```

---

## 🔗 RELATIONSHIPS

### Primary Relationships:
1. **users(1) ←→ bookings(M)** - One user can have many bookings
2. **services(1) ←→ bookings(M)** - One service can have many bookings
3. **bookings(1) ←→ payments(M)** - One booking can have multiple payments (partial/full)
4. **users(1) ←→ payments(M)** - One user can have many payments
5. **bookings(1) ←→ reviews(1)** - One booking can have one review
6. **users(1) ←→ reviews(M)** - One user can write many reviews
7. **services(1) ←→ reviews(M)** - One service can have many reviews

### Verification & Audit Relationships:
8. **users(1) ←→ payments(M)** - Admin verifies payments (verified_by)
9. **payments(1) ←→ payment_logs(M)** - Payment audit trail
10. **users(1) ←→ activity_logs(M)** - User activity tracking
11. **users(1) ←→ notifications(M)** - User notifications

---

## 📋 BUSINESS RULES

### Booking Rules:
- User must be registered and active
- One user can have multiple bookings
- Booking must reference valid service
- Payment is required to confirm booking
- Booking status follows workflow: pending → confirmed → in_progress → completed

### Payment Rules:
- Payment must be linked to a booking
- Supports partial (50%) and full (100%) payments
- Online payments require admin verification
- Cash payments are auto-confirmed
- Payment proof upload for online methods

### Review Rules:
- Only completed bookings can be reviewed
- One review per booking
- Admin can approve/disapprove reviews

---

## 🎯 KEY FEATURES SUPPORTED

### ✅ Implemented:
- User registration/authentication (Google OAuth + Manual)
- Service catalog with tiered pricing
- Booking system with payment integration
- Multiple payment methods (GCash, Bank, Cash)
- Payment proof upload system
- Admin verification workflow
- Review and rating system
- Notification system
- Activity logging and audit trails

### 🔄 Partial/Future:
- Email verification system
- Advanced reporting
- Service scheduling optimization
- Customer loyalty programs
- Inventory management

---

## 📊 ERD VISUAL REPRESENTATION

```
    ┌─────────────┐         ┌─────────────┐         ┌─────────────┐
    │    USERS    │         │  SERVICES   │         │  BOOKINGS   │
    │             │         │             │         │             │
    │ (PK) id     │    ┌────│ (PK) service│────┐    │ (PK) booking│
    │     username│    │    │     _id     │    │    │     _id     │
    │     email   │    │    │ service_name│    │    │ (FK) user_id│──┐
    │     role    │    │    │ price_small │    │    │ (FK) service│  │
    │     ...     │    │    │ price_medium│    │    │     _id     │  │
    └─────────────┘    │    │ price_large │    │    │ booking_date│  │
           │           │    │     ...     │    │    │ status      │  │
           │           │    └─────────────┘    │    │     ...     │  │
           │           │                       │    └─────────────┘  │
           │           │                       │           │         │
           │           └───────────────────────┘           │         │
           │                                               │         │
           │    ┌─────────────┐                           │         │
           │    │  PAYMENTS   │                           │         │
           │    │             │                           │         │
           │    │ (PK) payment│                           │         │
           └────│     _id     │                           │         │
                │ (FK) booking│───────────────────────────┘         │
                │     _id     │                                     │
                │ (FK) user_id│─────────────────────────────────────┘
                │ amount      │
                │ method      │
                │ status      │
                │     ...     │
                └─────────────┘
                       │
                ┌─────────────┐
                │PAYMENT_LOGS │
                │             │
                │ (PK) log_id │
                │ (FK) payment│
                │     _id     │
                │ action      │
                │     ...     │
                └─────────────┘

        ┌─────────────┐                    ┌─────────────┐
        │   REVIEWS   │                    │NOTIFICATIONS│
        │             │                    │             │
        │ (PK) review │                    │ (PK) notif  │
        │     _id     │                    │     _id     │
        │ (FK) user_id│────────────────────│ (FK) user_id│
        │ (FK) booking│                    │ type        │
        │     _id     │                    │ message     │
        │ (FK) service│                    │     ...     │
        │     _id     │                    └─────────────┘
        │ rating      │
        │     ...     │          ┌─────────────┐
        └─────────────┘          │ACTIVITY_LOGS│
                                 │             │
                                 │ (PK) log_id │
                                 │ (FK) user_id│
                                 │ action      │
                                 │ description │
                                 │     ...     │
                                 └─────────────┘
```

---

## 💡 PRESENTATION TIPS FOR YOUR LEADER

### Highlight These Strengths:
1. **Comprehensive Data Model** - Covers all business processes
2. **Scalable Design** - Can handle growth and new features
3. **Audit Trail** - Complete tracking of all activities
4. **Payment Security** - Proper verification workflow
5. **User Experience** - Smooth booking to payment flow

### Areas for Future Enhancement:
1. **Inventory Management** - Track cleaning supplies
2. **Staff Management** - Assign technicians to bookings
3. **Customer Loyalty** - Points/rewards system
4. **Analytics** - Business intelligence tables
5. **Integration** - APIs for third-party services

This ERD shows a mature, production-ready system architecture! 🎉
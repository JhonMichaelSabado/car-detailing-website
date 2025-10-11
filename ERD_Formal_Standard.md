# 📊 Car Detailing System - Formal ERD with Standard Notations

## 🎯 ENTITY RELATIONSHIP DIAGRAM (ERD)
### Using Standard ERD Symbols and Notations

---

## 📐 ERD LEGEND (Based on Standard Notation)

### Symbols Used:
- **🟨 Rectangle** = Entity (Table)
- **🔵 Diamond** = Relationship  
- **🟢 Oval** = Attribute
- **🔑 Underlined** = Primary Key (PK)
- **🔗 Dashed Underline** = Foreign Key (FK)
- **⚪ Double Oval** = Multi-valued Attribute
- **📊 Lines** = Connections between entities

### Cardinality Notations:
- **1:1** = One-to-One
- **1:N** = One-to-Many  
- **M:N** = Many-to-Many
- **|** = One (exactly one)
- **⚬** = Zero or One (optional)
- **∞** = Many

---

## 🏗️ FORMAL ERD STRUCTURE

```
                    CAR DETAILING SYSTEM - ENTITY RELATIONSHIP DIAGRAM

    ┌─────────────────────────────┐             ┌─────────────────────────────┐
    │         USERS 🟨            │             │        SERVICES 🟨          │
    │─────────────────────────────│             │─────────────────────────────│
    │ 🔑 id (PK)                  │             │ 🔑 service_id (PK)          │
    │    google_id                │             │    category                 │
    │    username                 │             │    service_name             │
    │    email                    │             │    description              │
    │    password                 │             │    price_small              │
    │    first_name               │             │    price_medium             │
    │    last_name                │             │    price_large              │
    │    phone                    │             │    duration_minutes         │
    │    role                     │             │    included_items           │
    │    address                  │             │    free_items               │
    │    date_of_birth            │             │    is_active                │
    │    profile_picture          │             │    created_at               │
    │    email_verified           │             └─────────────────────────────┘
    │    last_login               │                           │
    │    is_active                │                           │ 1
    │    reset_token              │                           │
    │    reset_expires            │                           │
    │    created_at               │                           ▼
    │    updated_at               │                    🔵 OFFERS 🔵
    └─────────────────────────────┘                           │
                 │                                            │ N
                 │ 1                                          │
                 │                                            ▼
                 ▼                        ┌─────────────────────────────┐
          🔵 MAKES 🔵                     │        BOOKINGS 🟨          │
                 │                        │─────────────────────────────│
                 │ N                      │ 🔑 booking_id (PK)          │
                 ▼                        │ 🔗 user_id (FK)             │◄─────────┐
    ┌─────────────────────────────┐       │ 🔗 service_id (FK)          │◄─────┐   │
    │        BOOKINGS 🟨          │       │    vehicle_size             │      │   │
    │─────────────────────────────│       │    booking_date             │      │   │
    │ 🔑 booking_id (PK)          │       │    status                   │      │   │
    │ 🔗 user_id (FK)             │       │    total_amount             │      │   │
    │ 🔗 service_id (FK)          │       │    payment_status           │      │   │
    │    vehicle_size             │       │    payment_verification     │      │   │
    │    booking_date             │       │    vehicle_details          │      │   │
    │    status                   │       │    special_requests         │      │   │
    │    total_amount             │       │    admin_notes              │      │   │
    │    payment_status           │       │    created_at               │      │   │
    │    payment_verification     │       │    updated_at               │      │   │
    │    vehicle_details          │       └─────────────────────────────┘      │   │
    │    special_requests         │                        │                   │   │
    │    admin_notes              │                        │ 1                 │   │
    │    created_at               │                        │                   │   │
    │    updated_at               │                        ▼                   │   │
    └─────────────────────────────┘                 🔵 PAYS FOR 🔵            │   │
                 │                                        │                   │   │
                 │ 1                                      │ N                 │   │
                 │                                        ▼                   │   │
                 ▼                        ┌─────────────────────────────┐      │   │
          🔵 GENERATES 🔵                 │        PAYMENTS 🟨          │      │   │
                 │                        │─────────────────────────────│      │   │
                 │ N                      │ 🔑 payment_id (PK)          │      │   │
                 ▼                        │ 🔗 booking_id (FK)          │──────┘   │
    ┌─────────────────────────────┐       │ 🔗 user_id (FK)             │──────────┘
    │        PAYMENTS 🟨          │       │    amount                   │
    │─────────────────────────────│       │    payment_method           │
    │ 🔑 payment_id (PK)          │       │    payment_status           │
    │ 🔗 booking_id (FK)          │       │    payment_type             │
    │ 🔗 user_id (FK)             │       │    transaction_id           │
    │ 🔗 verified_by (FK)         │       │    payment_proof_path       │
    │    amount                   │       │ 🔗 verified_by (FK)         │◄─────────┐
    │    payment_method           │       │    verification_status      │          │
    │    payment_status           │       │    verified_at              │          │
    │    payment_type             │       │    rejection_reason         │          │
    │    transaction_id           │       │    payment_date             │          │
    │    payment_proof_path       │       │    notes                    │          │
    │    verification_status      │       └─────────────────────────────┘          │
    │    verified_at              │                        │                       │
    │    rejection_reason         │                        │ 1                     │
    │    payment_date             │                        │                       │
    │    notes                    │                        ▼                       │
    └─────────────────────────────┘                 🔵 LOGS 🔵                   │
                 │                                        │                       │
                 │ 1                                      │ N                     │
                 │                                        ▼                       │
                 ▼                        ┌─────────────────────────────┐          │
          🔵 CREATES 🔵                   │     PAYMENT_LOGS 🟨        │          │
                 │                        │─────────────────────────────│          │
                 │ N                      │ 🔑 log_id (PK)              │          │
                 ▼                        │ 🔗 payment_id (FK)          │──────────┘
    ┌─────────────────────────────┐       │ 🔗 performed_by (FK)        │
    │     PAYMENT_LOGS 🟨        │       │    action                   │
    │─────────────────────────────│       │    details                  │
    │ 🔑 log_id (PK)              │       │    created_at               │
    │ 🔗 payment_id (FK)          │       └─────────────────────────────┘
    │ 🔗 performed_by (FK)        │
    │    action                   │
    │    details                  │       ┌─────────────────────────────┐
    │    created_at               │       │        REVIEWS 🟨           │
    └─────────────────────────────┘       │─────────────────────────────│
                                          │ 🔑 review_id (PK)           │
    ┌─────────────────────────────┐       │ 🔗 user_id (FK)             │◄─────────┐
    │      NOTIFICATIONS 🟨       │       │ 🔗 booking_id (FK)          │◄─────┐   │
    │─────────────────────────────│       │ 🔗 service_id (FK)          │◄─┐   │   │
    │ 🔑 notification_id (PK)     │       │    rating                   │  │   │   │
    │ 🔗 user_id (FK)             │       │    review_text              │  │   │   │
    │    type                     │       │    is_approved              │  │   │   │
    │    title                    │       │    admin_response           │  │   │   │
    │    message                  │       │    created_at               │  │   │   │
    │    is_read                  │       └─────────────────────────────┘  │   │   │
    │    related_id               │                        │               │   │   │
    │    action_url               │                        │ 1:1           │   │   │
    │    created_at               │                        │               │   │   │
    └─────────────────────────────┘                        ▼               │   │   │
                 ▲                                   🔵 REVIEWS 🔵          │   │   │
                 │                                        │               │   │   │
                 │ N                                      │ 1:N           │   │   │
                 │                                        │               │   │   │
          🔵 RECEIVES 🔵                                   ▼               │   │   │
                 │                        ┌─────────────────────────────┐  │   │   │
                 │ 1                      │        USERS 🟨            │  │   │   │
                 └────────────────────────│  (Referenced above)        │──┘   │   │
                                          └─────────────────────────────┘      │   │
                                                                               │   │
    ┌─────────────────────────────┐                                          │   │
    │     ACTIVITY_LOGS 🟨       │       🔵 WRITES 🔵                        │   │
    │─────────────────────────────│              │                           │   │
    │ 🔑 log_id (PK)              │              │ 1:N                       │   │
    │ 🔗 user_id (FK)             │              │                           │   │
    │ 🔗 admin_id (FK)            │              └───────────────────────────┘   │
    │    action                   │                                              │
    │    description              │       🔵 CONCERNS 🔵                         │
    │    table_name               │              │                               │
    │    record_id                │              │ 1:N                           │
    │    ip_address               │              │                               │
    │    created_at               │              └───────────────────────────────┘
    └─────────────────────────────┘
                 ▲
                 │ N
                 │
          🔵 LOGS 🔵
                 │
                 │ 1
                 └──────────────────────── USERS 🟨
```

## 📊 CARDINALITY AND RELATIONSHIP DETAILS

### 1. USERS ↔ BOOKINGS
- **Cardinality**: 1:N (One-to-Many)
- **Description**: One user can make many bookings
- **Participation**: Total participation (user must exist to book)

### 2. SERVICES ↔ BOOKINGS  
- **Cardinality**: 1:N (One-to-Many)
- **Description**: One service can be booked many times
- **Participation**: Total participation (service must exist for booking)

### 3. BOOKINGS ↔ PAYMENTS
- **Cardinality**: 1:N (One-to-Many) 
- **Description**: One booking can have multiple payments (partial/full)
- **Participation**: Partial participation (booking can exist without payment initially)

### 4. USERS ↔ PAYMENTS
- **Cardinality**: 1:N (One-to-Many)
- **Description**: One user can make many payments
- **Participation**: Total participation (user must exist for payment)

### 5. USERS ↔ PAYMENTS (Verification)
- **Cardinality**: 1:N (One-to-Many)
- **Description**: One admin can verify many payments
- **Participation**: Partial participation (not all payments need admin verification)

### 6. BOOKINGS ↔ REVIEWS
- **Cardinality**: 1:1 (One-to-One)
- **Description**: One booking can have exactly one review
- **Participation**: Partial participation (not all bookings have reviews)

### 7. USERS ↔ REVIEWS
- **Cardinality**: 1:N (One-to-Many)
- **Description**: One user can write many reviews
- **Participation**: Partial participation (users don't have to write reviews)

### 8. SERVICES ↔ REVIEWS
- **Cardinality**: 1:N (One-to-Many)
- **Description**: One service can have many reviews
- **Participation**: Partial participation (new services may not have reviews)

### 9. PAYMENTS ↔ PAYMENT_LOGS
- **Cardinality**: 1:N (One-to-Many)
- **Description**: One payment can have many log entries
- **Participation**: Total participation (every payment action is logged)

### 10. USERS ↔ NOTIFICATIONS
- **Cardinality**: 1:N (One-to-Many)
- **Description**: One user can receive many notifications
- **Participation**: Partial participation (notifications are optional)

### 11. USERS ↔ ACTIVITY_LOGS
- **Cardinality**: 1:N (One-to-Many)
- **Description**: One user can have many activity log entries
- **Participation**: Total participation (all user actions are logged)

## 🎯 FUNCTIONAL DEPENDENCIES

### USERS Entity:
- **id** → {google_id, username, email, password, first_name, last_name, phone, role, address, date_of_birth, profile_picture, email_verified, last_login, is_active, reset_token, reset_expires, created_at, updated_at}

### SERVICES Entity:
- **service_id** → {category, service_name, description, price_small, price_medium, price_large, duration_minutes, included_items, free_items, is_active, created_at}

### BOOKINGS Entity:
- **booking_id** → {user_id, service_id, vehicle_size, booking_date, status, total_amount, payment_status, payment_verification_status, vehicle_details, special_requests, admin_notes, created_at, updated_at}

### PAYMENTS Entity:
- **payment_id** → {booking_id, user_id, amount, payment_method, payment_status, payment_type, transaction_id, payment_proof_path, verification_status, verified_by, verified_at, rejection_reason, payment_date, notes}

## 🛠️ HOW TO CREATE VISUAL ERD

### 🎨 Recommended Tools:
1. **Draw.io (diagrams.net)** - Free online tool
2. **Lucidchart** - Professional diagramming
3. **MySQL Workbench** - Database-specific ERD
4. **DbDiagram.io** - Database ERD specialist
5. **Creately** - Collaborative diagramming

### 📋 Steps to Create:
1. **Import this structure** into any ERD tool
2. **Use standard symbols**:
   - Rectangles for entities
   - Diamonds for relationships  
   - Ovals for attributes
   - Lines with cardinality notations
3. **Apply proper formatting**:
   - Primary keys underlined
   - Foreign keys with dashed underlines
   - Relationship lines with 1, N, M notations

### 📊 Database Schema Export:
```sql
-- You can also generate ERD from database directly
mysqldump -u root -d car_detailing > schema.sql
-- Then import to MySQL Workbench for automatic ERD generation
```

---

**🎯 This formal ERD follows standard database design notation and can be directly used with professional diagramming tools!**
# 🌱 ORGANIC MARKETPLACE SYSTEM
## Complete System Flow Presentation

---

## 📋 **1. SYSTEM OVERVIEW**

The **Organic Marketplace** is a web-based e-commerce platform designed to connect local farmers from Cabadbaran City with health-conscious buyers. The system facilitates the sale and purchase of fresh organic produce including vegetables, fruits, fish, cacao, eggs, and spices.

### **Core Purpose:**
- Bridge the gap between local farmers and consumers
- Support local agricultural economy
- Provide fresh, organic produce directly from farms
- Enable transparent transactions between farmers and buyers

---

## 👥 **2. USER ROLES & PERMISSIONS**

The system operates with **three distinct user roles**, each with specific access and capabilities:

### **2.1 BUYER (Consumer)**
- **Purpose:** Purchase organic products from farmers
- **Key Capabilities:**
  - Browse and search products
  - Add products to shopping cart
  - Place orders (pickup or delivery)
  - View order history
  - Manage profile and address
  - Track spending and order statistics

### **2.2 FARMER (Seller)**
- **Purpose:** List and sell organic products
- **Key Capabilities:**
  - Register and get verified by admin
  - Add, edit, and manage product listings
  - Upload product images
  - View and manage orders
  - Track sales, revenue, and inventory
  - View analytics and best-selling products

### **2.3 ADMIN (System Administrator)**
- **Purpose:** Oversee and manage the entire platform
- **Key Capabilities:**
  - Verify and approve/reject farmer registrations
  - Manage all users (buyers and farmers)
  - Manage all products
  - Monitor all orders
  - View comprehensive analytics and reports
  - Generate charts and statistics

---

## 🔐 **3. REGISTRATION & AUTHENTICATION FLOW**

### **3.1 BUYER REGISTRATION**
```
1. Buyer visits buyer_register.php
2. Fills registration form:
   - First Name & Last Name (validated: must start with capital, no numbers)
   - Email (unique validation)
   - Password & Confirm Password
   - Phone number
   - Address (required)
   - Age Group (optional)
   - Barangay (optional)
3. System validates:
   - Name format validation
   - Email uniqueness check
   - Password matching
4. Password is hashed using PHP password_hash()
5. Account created in 'buyers' table
6. Redirect to buyer_login.php with success message
```

### **3.2 FARMER REGISTRATION**
```
1. Farmer visits farmer_register.php
2. Fills registration form:
   - Name
   - Email (unique validation)
   - Password & Confirm Password
   - Location (required)
   - Phone number
   - Optional: Certificate upload (for verification)
   - Seller Type selection (farmer/poultry_egg/fisherfolk)
3. System validates:
   - Email uniqueness
   - Password matching
   - File upload validation (if certificate provided)
4. Account created in 'farmers' table with:
   - verification_status = 'pending'
   - seller_type = selected type
5. Redirect to farmer_login.php
6. Farmer CANNOT login until admin approves account
```

### **3.3 LOGIN PROCESS**

**Buyer Login:**
- Email and password verification
- Session variables set: buyer_id, buyer_name, buyer_email
- Redirect to buyer_dashboard.php

**Farmer Login:**
- Email and password verification
- **Verification Check:**
  - If status = 'pending': Login blocked with message
  - If status = 'rejected': Login blocked with rejection reason
  - If status = 'approved': Login successful
- Session variables set: farmer_id, farmer_name, farmer_email
- Redirect to farmer_dashboard.php

**Admin Login:**
- Username and password verification
- Session variables set: admin_id, admin_username
- Redirect to admin_dashboard.php

---

## 🛒 **4. BUYER WORKFLOW**

### **4.1 DASHBOARD (buyer_dashboard.php)**
```
Upon login, buyer sees:
├── Welcome message with buyer name
├── Statistics Cards:
│   ├── Total Orders (clickable → orders.php)
│   ├── Cart Items (clickable → my_cart.php)
│   ├── Total Spent (from delivered orders)
│   └── Pending Orders (clickable → orders.php?filter=pending)
├── Best-Selling Products Carousel
│   └── Top 10 products by sales volume (auto-scrolling)
└── Available Products Grid
    ├── Product cards with:
    │   ├── Product image
    │   ├── Product name
    │   ├── Category
    │   ├── Price (₱)
    │   ├── Location
    │   └── "View Details" button
    └── Search functionality (by name, description, category)
```

### **4.2 PRODUCT BROWSING**
```
1. Browse Products (browse_products.php)
   - View all available products
   - Filter by category
   - Search functionality

2. Product Details (product_details.php)
   - Full product information
   - Multiple product images
   - Price per unit (kilo/piece)
   - Available quantity
   - Farmer information
   - Location
   - Description
   - "Add to Cart" button with quantity selector
```

### **4.3 SHOPPING CART (my_cart.php)**
```
Features:
├── List of all cart items
├── For each item:
│   ├── Product image
│   ├── Product name
│   ├── Price per unit
│   ├── Quantity (editable)
│   ├── Subtotal
│   ├── Checkbox for selection
│   └── Remove button
├── Total calculation
└── "Proceed to Checkout" button (only selected items)
```

### **4.4 CHECKOUT PROCESS (checkout.php)**
```
Step 1: Cart Selection
├── Buyer selects items from cart
└── Clicks "Proceed to Checkout"
    └── Selected items stored in session

Step 2: Checkout Form
├── Review selected items
├── Choose Delivery Type:
│   ├── Home Delivery (₱30 delivery fee)
│   └── Pickup Point (no delivery fee)
├── Delivery Address:
│   ├── Pre-filled from buyer profile
│   └── Editable
├── Payment Method:
│   ├── Cash on Delivery (COD)
│   └── Cash on Pickup (COP)
└── Submit Order

Step 3: Order Processing
├── System validates:
│   ├── Product availability (quantity check)
│   ├── Stock sufficiency
│   └── All required fields
├── Database Transaction:
│   ├── Create order record in 'orders' table
│   ├── Update product quantity (reduce stock)
│   ├── Update product 'sold' count
│   ├── Remove items from cart
│   └── Commit transaction
└── Redirect to orders.php with success message
```

### **4.5 ORDER MANAGEMENT (orders.php)**
```
Buyer can view:
├── All Orders (default)
├── Filtered Orders:
│   ├── Pending
│   ├── Confirmed
│   ├── Delivered
│   └── Cancelled
└── For each order:
    ├── Order ID
    ├── Product name
    ├── Farmer name
    ├── Quantity
    ├── Total amount
    ├── Status badge
    ├── Order date
    └── Delivery information
```

### **4.6 PROFILE MANAGEMENT (profile.php)**
```
Buyer can update:
├── Name
├── Email
├── Phone
├── Address
├── Age Group
└── Barangay
```

---

## 🧑‍🌾 **5. FARMER WORKFLOW**

### **5.1 DASHBOARD (farmer_dashboard.php)**
```
Upon login, farmer sees:
├── Welcome message with seller type badge
├── Statistics Cards:
│   ├── Total Products (clickable → my_products.php)
│   ├── Total Sold (units)
│   ├── Total Revenue (from delivered orders)
│   ├── Pending Orders (clickable → orders.php?filter=pending)
│   ├── Delivered Orders
│   └── Average Order Value
├── Recent Orders Table (last 3 orders)
│   └── Shows: Product, Buyer, Category, Quantity, Total, Status, Date
└── Best Sellers This Month
    └── Top 5 products by quantity sold (city-wide)
```

### **5.2 PRODUCT MANAGEMENT**

**Add Product (add_product.php):**
```
1. Verification Check:
   └── Only approved farmers can add products

2. Product Form:
   ├── Product Name
   ├── Category (based on seller_type):
   │   ├── Farmer: Vegetables, Fruits, Fish, Cacao, Spices
   │   ├── Poultry/Egg: Eggs only
   │   └── Fisherfolk: Fish only
   ├── Price (per unit)
   ├── Quantity (stock)
   ├── Unit (kilo/piece)
   ├── Description
   ├── Location (pre-filled from farmer profile)
   └── Product Images (multiple images supported)

3. Image Upload:
   ├── Primary image (required)
   ├── Additional images (optional)
   ├── Images stored in: uploads/product_images/
   └── File format validation (jpg, jpeg, png, webp, avif)

4. Product Creation:
   ├── Insert into 'products' table
   ├── Insert images into 'product_images' table
   └── Redirect to my_products.php
```

**Edit Product (edit_product.php):**
```
- Update all product fields
- Add/remove product images
- Update stock quantity
- Maintain product history
```

**My Products (my_products.php):**
```
Farmer can:
├── View all their products
├── See product statistics:
│   ├── Quantity available
│   ├── Units sold
│   └── Product status
├── Edit products
├── Delete products
└── Filter by category
```

### **5.3 ORDER MANAGEMENT (orders.php)**
```
Farmer can:
├── View all orders for their products
├── Filter orders by status:
│   ├── Pending
│   ├── Confirmed
│   ├── Delivered
│   └── Cancelled
├── Update order status:
│   ├── Confirm order
│   ├── Mark as delivered
│   └── Cancel order
└── View order details:
    ├── Buyer information
    ├── Product details
    ├── Quantity and total
    ├── Payment method
    ├── Delivery type and address
    └── Order date
```

### **5.4 PROFILE MANAGEMENT (profile.php)**
```
Farmer can update:
├── Name
├── Email
├── Phone
├── Location
└── Certificate (re-upload if needed)
```

---

## 👨‍💼 **6. ADMIN WORKFLOW**

### **6.1 DASHBOARD (admin_dashboard.php)**
```
Comprehensive overview:
├── Statistics Cards:
│   ├── Total Farmers (clickable → manage_users.php)
│   ├── Total Buyers (clickable → manage_users.php)
│   ├── Total Products (clickable → manage_products.php)
│   ├── Total Orders (clickable → manage_orders.php)
│   ├── Total Revenue (from delivered orders)
│   ├── Pending Orders (clickable → manage_orders.php?filter=pending)
│   ├── Active Products (in stock)
│   └── Delivered Orders
├── Recent Orders (last 3 across platform)
├── Charts & Analytics:
│   ├── Order Status Distribution (Pie Chart)
│   ├── Product Category Distribution (Pie Chart)
│   ├── Sales & Orders Trend (Line Chart - Last 12 Months)
│   ├── Top Products by Sales (Bar Chart)
│   └── Top Farmers by Revenue (Bar Chart)
└── Top 5 Best-Selling Products Table
```

### **6.2 USER MANAGEMENT (manage_users.php)**
```
Admin can:
├── View All Users:
│   ├── Farmers (with verification status)
│   └── Buyers
├── Filter Users:
│   ├── By type (farmers/buyers/all)
│   ├── By verification status (for farmers)
│   └── Search by name/email/phone
├── Farmer Verification:
│   ├── View pending farmers
│   ├── Approve farmer:
│   │   ├── Set verification_status = 'approved'
│   │   ├── Record verified_by (admin_id)
│   │   ├── Record verified_at timestamp
│   │   └── Farmer can now login and add products
│   └── Reject farmer:
│       ├── Set verification_status = 'rejected'
│       ├── Record rejection_reason
│       └── Farmer cannot login
├── Delete Users:
│   ├── Delete farmer (cascades to products)
│   └── Delete buyer (cascades to cart)
└── View User Details:
    ├── Registration date
    ├── Verification information
    └── Related data counts
```

### **6.3 PRODUCT MANAGEMENT (manage_products.php)**
```
Admin can:
├── View all products across platform
├── Filter products:
│   ├── By category
│   ├── By farmer
│   ├── By stock status (active/out of stock)
│   └── Search by name/description
├── Edit products
├── Delete products
└── View product statistics
```

### **6.4 ORDER MANAGEMENT (manage_orders.php)**
```
Admin can:
├── View all orders across platform
├── Filter orders:
│   ├── By status
│   ├── By farmer
│   ├── By buyer
│   └── By date range
├── View order details
├── Update order status
└── Monitor order trends
```

### **6.5 ANALYTICS & REPORTS (analytics.php, reports.php)**
```
Admin can generate:
├── Sales reports
├── User activity reports
├── Product performance reports
├── Revenue analytics
└── Export data
```

---

## 📦 **7. ORDER PROCESSING FLOW**

### **7.1 ORDER LIFECYCLE**

```
┌─────────────────────────────────────────────────────────────┐
│                    ORDER LIFECYCLE                          │
└─────────────────────────────────────────────────────────────┘

1. ORDER CREATION (Buyer)
   ├── Buyer adds products to cart
   ├── Selects items for checkout
   ├── Chooses delivery type (delivery/pickup)
   ├── Selects payment method (COD/COP)
   ├── Submits checkout form
   └── Order created with status = 'Pending'

2. STOCK UPDATE
   ├── Product quantity reduced
   ├── Product 'sold' count increased
   └── Cart items removed

3. ORDER NOTIFICATION
   ├── Farmer sees new order in dashboard
   └── Admin sees order in system

4. ORDER CONFIRMATION (Farmer)
   ├── Farmer reviews order
   ├── Farmer confirms order
   └── Status updated to 'Confirmed'

5. ORDER FULFILLMENT
   ├── For Delivery:
   │   ├── Farmer prepares product
   │   ├── Delivery arranged
   │   └── Product delivered
   └── For Pickup:
       ├── Farmer prepares product
       ├── Buyer notified of pickup location
       └── Buyer picks up product

6. ORDER COMPLETION
   ├── Farmer marks order as 'Delivered'
   ├── Payment status updated to 'completed'
   └── Revenue recorded in farmer statistics

7. ORDER CANCELLATION (if needed)
   ├── Can be cancelled by farmer or admin
   ├── Stock restored to product
   └── Status updated to 'Cancelled'
```

### **7.2 ORDER STATUSES**

| Status | Description | Who Can Set |
|--------|-------------|-------------|
| **Pending** | Order created, awaiting farmer confirmation | System (default) |
| **Confirmed** | Farmer has confirmed the order | Farmer, Admin |
| **Delivered** | Order has been delivered/picked up | Farmer, Admin |
| **Cancelled** | Order has been cancelled | Farmer, Admin |

### **7.3 PAYMENT METHODS**

| Method | Description | When Used |
|--------|-------------|-----------|
| **Cash on Delivery (COD)** | Payment upon delivery | Home delivery orders |
| **Cash on Pickup (COP)** | Payment at pickup point | Pickup orders |

---

## 🗄️ **8. DATABASE STRUCTURE**

### **8.1 CORE TABLES**

```
┌─────────────────────────────────────────────────────────────┐
│                    DATABASE SCHEMA                           │
└─────────────────────────────────────────────────────────────┘

1. buyers
   ├── id (PK)
   ├── name
   ├── email (UNIQUE)
   ├── password (hashed)
   ├── phone
   ├── address
   ├── age_group
   ├── barangay
   └── created_at

2. farmers
   ├── id (PK)
   ├── name
   ├── email (UNIQUE)
   ├── password (hashed)
   ├── location
   ├── phone
   ├── certificate_path
   ├── verification_status (pending/approved/rejected)
   ├── seller_type (farmer/poultry_egg/fisherfolk)
   ├── allowed_categories (JSON)
   ├── verification_document
   ├── rejection_reason
   ├── verified_at
   ├── verified_by (FK → admins.id)
   └── created_at

3. products
   ├── id (PK)
   ├── farmer_id (FK → farmers.id)
   ├── name
   ├── category (ENUM: Vegetables/Fruits/Fish/Cacao/Eggs/Spices)
   ├── price
   ├── quantity (stock)
   ├── sold (units sold)
   ├── image (primary image path)
   ├── unit (kilo/piece)
   ├── description
   ├── location
   └── created_at

4. product_images
   ├── id (PK)
   ├── product_id (FK → products.id)
   ├── image_path
   ├── is_primary (boolean)
   └── created_at

5. cart
   ├── id (PK)
   ├── buyer_id (FK → buyers.id)
   ├── product_id (FK → products.id)
   ├── quantity
   └── created_at
   └── UNIQUE(buyer_id, product_id)

6. orders
   ├── id (PK)
   ├── buyer_id (FK → buyers.id)
   ├── farmer_id (FK → farmers.id)
   ├── product_id (FK → products.id)
   ├── quantity
   ├── price (at time of order)
   ├── total
   ├── delivery_fee
   ├── payment_method
   ├── payment_status (pending/completed/failed)
   ├── delivery_type (home_delivery/pickup_point)
   ├── pickup_point_id (FK → pickup_points.id, nullable)
   ├── delivery_address
   ├── status (Pending/Confirmed/Delivered/Cancelled)
   ├── location
   └── order_date

7. admins
   ├── id (PK)
   ├── username (UNIQUE)
   ├── password (hashed)
   ├── email
   └── created_at

8. payment_methods
   ├── id (PK)
   ├── name
   ├── description
   └── is_active

9. pickup_points
   ├── id (PK)
   ├── name
   ├── address
   └── created_at
```

### **8.2 RELATIONSHIPS**

```
buyers (1) ────< (M) cart
buyers (1) ────< (M) orders
farmers (1) ────< (M) products
farmers (1) ────< (M) orders
products (1) ────< (M) product_images
products (1) ────< (M) cart
products (1) ────< (M) orders
admins (1) ────< (M) farmers (verified_by)
pickup_points (1) ────< (M) orders
```

---

## 🔑 **9. KEY FEATURES & FUNCTIONALITIES**

### **9.1 SECURITY FEATURES**
- ✅ Password hashing (bcrypt)
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (htmlspecialchars)
- ✅ Session-based authentication
- ✅ Role-based access control
- ✅ Input validation and sanitization
- ✅ File upload validation

### **9.2 USER EXPERIENCE FEATURES**
- ✅ Responsive design (mobile-friendly)
- ✅ Real-time search functionality
- ✅ Product image carousels
- ✅ Interactive dashboards
- ✅ Statistics and analytics
- ✅ Order tracking
- ✅ Best-seller highlights

### **9.3 BUSINESS FEATURES**
- ✅ Multi-seller marketplace
- ✅ Inventory management
- ✅ Order management system
- ✅ Revenue tracking
- ✅ Sales analytics
- ✅ Farmer verification system
- ✅ Category-based product organization
- ✅ Multiple delivery options
- ✅ Multiple payment methods

### **9.4 ADMINISTRATIVE FEATURES**
- ✅ Comprehensive dashboard with charts
- ✅ User management (approve/reject/delete)
- ✅ Product oversight
- ✅ Order monitoring
- ✅ Analytics and reporting
- ✅ System-wide statistics

---

## 🔄 **10. COMPLETE USER JOURNEY EXAMPLES**

### **Example 1: Buyer Purchasing Product**

```
1. Buyer visits index.php (homepage)
   └── Sees featured products

2. Buyer clicks "Login as Buyer"
   └── Redirected to buyer_login.php

3. Buyer logs in
   └── Redirected to buyer_dashboard.php

4. Buyer browses products
   └── Uses search or views product grid

5. Buyer clicks "View Details" on a product
   └── Redirected to product_details.php

6. Buyer selects quantity and clicks "Add to Cart"
   └── Product added to cart

7. Buyer goes to "My Cart"
   └── Redirected to my_cart.php

8. Buyer selects items and clicks "Proceed to Checkout"
   └── Redirected to checkout.php

9. Buyer fills checkout form:
   ├── Selects delivery type
   ├── Enters/confirms address
   └── Selects payment method

10. Buyer submits order
    └── Order created, stock updated, cart cleared

11. Buyer redirected to orders.php
    └── Sees order confirmation
```

### **Example 2: Farmer Selling Product**

```
1. Farmer visits farmer_register.php
   └── Fills registration form

2. Farmer account created with status = 'pending'
   └── Cannot login yet

3. Admin reviews farmer registration
   └── Admin approves farmer

4. Farmer logs in
   └── Redirected to farmer_dashboard.php

5. Farmer clicks "Add Product"
   └── Redirected to add_product.php

6. Farmer fills product form:
   ├── Product details
   ├── Price and quantity
   └── Uploads images

7. Product created
   └── Redirected to my_products.php

8. Buyer purchases product
   └── Order appears in farmer's orders

9. Farmer views order in orders.php
   └── Confirms order

10. Farmer fulfills order
    └── Marks as delivered

11. Revenue recorded in farmer statistics
```

### **Example 3: Admin Managing System**

```
1. Admin logs in
   └── Redirected to admin_dashboard.php

2. Admin sees pending farmer registrations
   └── Clicks "Manage Users"

3. Admin reviews farmer details:
   ├── Checks certificate
   ├── Verifies information
   └── Decides to approve/reject

4. Admin approves farmer
   └── Farmer can now login

5. Admin monitors orders
   └── Views order trends in charts

6. Admin generates reports
   └── Exports analytics data
```

---

## 📊 **11. SYSTEM STATISTICS & ANALYTICS**

### **11.1 Buyer Statistics**
- Total orders placed
- Items in cart
- Total amount spent
- Pending orders count

### **11.2 Farmer Statistics**
- Total products listed
- Total units sold
- Total revenue (from delivered orders)
- Pending orders count
- Delivered orders count
- Average order value
- Top-selling item

### **11.3 Admin Statistics**
- Total farmers registered
- Total buyers registered
- Total products listed
- Total orders placed
- Total revenue (platform-wide)
- Active products count
- Order status distribution
- Product category distribution
- Sales trends (12 months)
- Top products by sales
- Top farmers by revenue

---

## 🎯 **12. SYSTEM ARCHITECTURE**

### **12.1 Technology Stack**
- **Backend:** PHP (Server-side scripting)
- **Database:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript
- **Authentication:** Session-based
- **File Storage:** Local file system (uploads/)

### **12.2 File Structure**
```
organic_marketplace/
├── admin/              # Admin module
│   ├── admin_dashboard.php
│   ├── manage_users.php
│   ├── manage_products.php
│   ├── manage_orders.php
│   └── includes/
├── buyer/              # Buyer module
│   ├── buyer_dashboard.php
│   ├── browse_products.php
│   ├── product_details.php
│   ├── my_cart.php
│   ├── checkout.php
│   ├── orders.php
│   └── includes/
├── farmer/            # Farmer module
│   ├── farmer_dashboard.php
│   ├── add_product.php
│   ├── edit_product.php
│   ├── my_products.php
│   ├── orders.php
│   └── includes/
├── database/          # Database files
│   ├── db_connect.php
│   └── organic_marketplace.sql
├── uploads/           # File uploads
│   ├── product_images/
│   └── certificates/
└── index.php          # Homepage
```

---

## ✅ **13. SYSTEM VALIDATION & ERROR HANDLING**

### **13.1 Input Validation**
- Name validation (capital letter start, no numbers)
- Email format validation
- Password strength requirements
- File upload validation (type, size)
- Quantity validation (positive numbers)
- Price validation (decimal values)

### **13.2 Error Handling**
- Database transaction rollback on errors
- Session timeout handling
- Invalid access attempts (redirect to login)
- Stock insufficiency checks
- Duplicate entry prevention
- File upload error handling

---

## 🚀 **14. FUTURE ENHANCEMENTS (Potential)**

- Payment gateway integration
- Email notifications
- SMS notifications
- Rating and review system
- Wishlist functionality
- Product recommendations
- Advanced search filters
- Bulk order management
- Delivery tracking
- Mobile app development

---

## 📝 **CONCLUSION**

The **Organic Marketplace** system provides a comprehensive solution for connecting local farmers with buyers in Cabadbaran City. With three distinct user roles, robust order management, inventory tracking, and administrative oversight, the platform facilitates seamless transactions while maintaining quality control through farmer verification.

The system is designed with security, usability, and scalability in mind, making it a reliable platform for supporting local agriculture and promoting healthy, organic food consumption.

---

**End of Presentation**



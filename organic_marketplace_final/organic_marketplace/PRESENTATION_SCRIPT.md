# 🌱 MARKETPLACE FOR ORGANIC PRODUCE
## Sequential Presentation Script for Panel

---

## **OPENING (Hook + Context + Smooth Introduction)**

Good day everyone, especially to our instructor **Sir Vistal** and my classmates.

Today, we will be presenting our capstone system titled **"Marketplace for Organic Produce."**

To begin, let me ask a quick question:

**Have you ever bought vegetables or eggs online and worried if the products were really organic—or if the seller was even legitimate?**

This is a common problem not only for consumers, but also for farmers in Cabadbaran City who struggle to reach buyers directly.

And this is exactly the gap our system solves.

---

## **SLIDE: Problem Statement**

Here is what we discovered:

Local producers in Cabadbaran City face **six major challenges**:

1. **No verified digital marketplace** for farmers, poultry/egg raisers, and fisherfolk.
2. **Consumers cannot verify authenticity** or organic certification.
3. Ordering through **Facebook, SMS, or chat** leads to delays and miscommunication.
4. Producers earn less due to **intermediaries and inconsistent pricing**.
5. The **DA has no centralized digital record** for supply, demand, and producer performance.
6. There is **no unified system** for product listing, inventory, orders, and payments.

**This is the core problem we wanted to solve.**

---

## **SLIDE: Solution Overview**

To address these challenges, we developed a:

### 🌱 **Secure, government-verified online marketplace**

managed by the **Cabadbaran City Department of Agriculture**.

Our system allows:

* Verified **farmers, poultry/egg raisers, and fisherfolk** to post products
* **Consumers** to browse, order, and arrange pick-up or delivery
* **DA administrators** to approve sellers, monitor activities, and generate reports

In short, we connected **producer → consumer** directly with **trust, transparency, and convenience.**

---

## **SLIDE: System Purpose / Abstract**

Our system was developed using:

* **Developmental Research Design**
* The **Iterative SDLC Model**
* Evaluated using **ISO 25010 standards** for usability, performance, and security

This enables a **traceable, transparent, and efficient** agricultural marketplace that supports local sustainability and digital transformation.

---

## **SLIDE: Objectives**

### **General Objective**

To design and develop a web-based marketplace connecting *verified* local producers in Cabadbaran City directly to consumers under DA supervision.

### **Specific Objectives**

1. Implement **seller verification** and role-based access.
2. Create a **responsive, user-friendly** interface.
3. Integrate **payments** and **pickup/delivery workflows**.
4. Provide **admin analytics and reporting**.
5. Evaluate the system using **ISO 25010** through UAT.

---

## **SLIDE: Scope and Delimitation**

### **Scope includes:**

✔ Cabadbaran City geographic coverage
✔ Users: DA Admin, Sellers, Buyers
✔ Product categories:
  • Organic crops
  • Eggs/poultry
  • Fishery products
✔ Core features: registration, verification, listing, ordering, payments, inventory, reporting

### **Delimitations:**

✘ Organic certification is based on submitted documents only
✘ No national shipping—local distribution only
✘ Mobile app not included in current phase
✘ Blockchain traceability not part of the scope

---

## **SLIDE: Review of Related Literature & Studies**

Our foundation includes:

* **FAO 2022** studies showing digital agriculture increases transparency and profit
* **Philippine E-Agriculture Strategy 2023–2028**
* **PhilGAP and PGS certification frameworks** for quality assurance
* Local data from **DA-Caraga**, **BFAR**, and **Philippine News Agency**

Previous systems focused on **single products** or **regional scales**.

Our contribution is **multi-commodity integration** at a **municipal level** with **government verification**, which is rarely done.

---

## **SLIDE: Methodology**

We followed **Iterative SDLC**, including:

### **1. Planning**
Identifying problems, defining scope, reviewing policies.

### **2. Analysis**
Modeling workflows for buyers, sellers, and DA admins.

### **3. Design**
Three-tier architecture:
* Presentation Layer
* Application Layer
* Data Layer

### **4. Development**
Technologies used:
* PHP, MySQL, HTML/CSS/JS

### **5. Testing**
* Unit testing
* Integration testing
* System testing
* UAT based on ISO 25010

### **6. Deployment**
Local server deployment with test accounts.

---

## **SLIDE: Data Gathering Procedure**

We used:

* Document analysis (DA policies, organic certification standards)
* Interviews with producers and DA personnel
* Comparative analysis of existing marketplaces
* UAT with real users

---

## **SLIDE: System Demo (High Rubrics Score)**

Now, let me demonstrate the system sequentially, starting from the homepage and moving through each user role to show you how all components work together.

---

## **PART 1: HOMEPAGE AND PUBLIC INTERFACE**

### **1.1 Homepage Overview**

*[Demonstrate: Navigate to index.php]*

We begin with the **homepage** - the public-facing entry point of our system. This is what visitors see when they first access the Organic Marketplace.

**Key Features of the Homepage:**
- **Header Navigation:** Clean, intuitive navigation menu with links to Home, About, Contact, and login options for both buyers and farmers
- **Hero Section:** Welcoming banner that introduces the platform's mission - connecting local farmers with conscious buyers for fresh organic products from Cabadbaran City
- **Mission Section:** Three purpose cards highlighting:
  - Support for local farmers
  - Fresh and organic produce access
  - Community connection and sustainability
- **Featured Products Section:** Displays the top 6 best-selling products from our platform, giving visitors a preview of available organic produce
- **Footer:** Contains quick links, contact information, and registration options

**Design Philosophy:**
The homepage is designed to be welcoming and informative, giving visitors a clear understanding of what the platform offers before they decide to register.

**Navigation Options:**
From the homepage, visitors can:
- Register as a buyer
- Register as a farmer
- Login if they already have an account
- Browse featured products (though full access requires registration)

---

## **PART 2: BUYER MODULE - COMPLETE FLOW**

### **👤 1. Buyer Side**

*[Demonstrate: Navigate to buyer_register.php]*

Let me start by showing the **buyer side** of our system.

**"From here, users can browse organic vegetables, eggs, poultry, and fishery products, add items to the cart, and place orders."**

### **2.1 Buyer Registration**

Let me demonstrate the **Buyer Registration** process.

**Registration Form Fields:**
- **First Name and Last Name:** With validation requiring names to start with a capital letter and contain no numbers
- **Email Address:** Must be unique - the system checks for existing accounts
- **Password:** Securely hashed using bcrypt before storage
- **Phone Number:** For contact purposes
- **Address:** Required field for delivery purposes
- **Age Group:** Optional demographic data (18-25, 26-35, etc.)
- **Barangay:** Optional location data for local analytics

**Security Features:**
- Input validation on both client and server side
- Password confirmation matching
- Email uniqueness verification
- All data sanitized before database insertion

**Registration Process:**
1. Buyer fills out the registration form
2. System validates all inputs
3. Password is hashed using PHP's password_hash() function
4. Account is created in the 'buyers' table
5. Buyer is redirected to the login page with a success message

---

### **2.2 Buyer Login**

*[Demonstrate: Navigate to buyer_login.php]*

After registration, buyers can **log in** to access their dashboard.

**Login Process:**
- Buyer enters email and password
- System verifies credentials against the database
- Password is verified using password_verify() function
- Upon successful authentication:
  - Session variables are set: buyer_id, buyer_name, buyer_email
  - Buyer is redirected to their personalized dashboard

**Security:**
- Failed login attempts show generic error messages (no specific indication of whether email exists)
- Session-based authentication maintains security
- Automatic logout on session timeout

---

### **2.3 Buyer Dashboard**

*[Demonstrate: Navigate to buyer_dashboard.php]*

Once logged in, buyers are greeted by their **personalized dashboard**.

**Dashboard Components:**

**1. Header Section:**
- Personalized welcome message: "Welcome back, [Buyer Name]"
- Subtitle: "Discover fresh organic products from local farmers — curated for you"
- **Search Functionality:** Real-time search bar that filters products by name, description, or category as you type

**2. Statistics Cards (Four Key Metrics):**
- **Total Orders:** Clickable card showing all-time order count, links to orders page
- **Cart Items:** Shows current items in shopping cart, links to cart page
- **Total Spent:** Displays total amount spent on delivered orders
- **Pending Orders:** Shows orders awaiting delivery, links to filtered orders page

**3. Best-Selling Products Carousel:**
- Auto-scrolling carousel displaying top 10 best-selling products
- Each product card shows:
  - Product image
  - Product name
  - Category
  - Price
  - Units sold
  - "View Details" button
- Carousel pauses on hover for better user interaction

**4. Available Products Grid:**
- Complete product catalog displayed in a responsive grid
- Each product card includes:
  - Product image (with fallback for missing images)
  - Product name
  - Category
  - Price in Philippine Peso
  - Location
  - "View Details" button
- Products are sorted by creation date (newest first)
- Search functionality filters products in real-time

**User Experience Features:**
- Clickable statistics cards for quick navigation
- Responsive design that works on all devices
- Smooth animations and transitions
- Clear visual hierarchy

---

### **2.4 Product Browsing and Details**

*[Demonstrate: Navigate to product_details.php]*

When a buyer clicks "View Details" on any product, they see the **Product Details Page**.

**Product Information Displayed:**
- **Product Images:** Multiple images in a gallery format (if available)
- **Product Name:** Prominently displayed
- **Category:** Product category badge
- **Price:** Per unit price (kilo or piece)
- **Available Quantity:** Current stock level
- **Description:** Detailed product information
- **Location:** Where the product is from
- **Farmer Information:** Name of the farmer selling the product

**Interactive Features:**
- **Quantity Selector:** Dropdown or input field to select desired quantity
- **Add to Cart Button:** Adds selected quantity to shopping cart
- **Stock Validation:** System prevents adding more than available quantity
- **Responsive Image Gallery:** Multiple product images can be viewed

**User Flow:**
1. Buyer browses products on dashboard
2. Clicks "View Details" on a product of interest
3. Reviews complete product information
4. Selects desired quantity
5. Clicks "Add to Cart"
6. Product is added to cart with success confirmation

---

### **2.5 Shopping Cart Management**

*[Demonstrate: Navigate to my_cart.php]*

The **Shopping Cart** is where buyers manage their selected products before checkout.

**Cart Features:**

**Cart Item Display:**
- Each item shows:
  - Product image
  - Product name
  - Price per unit
  - Current quantity (editable)
  - Subtotal calculation (price × quantity)
  - Checkbox for selection
  - Remove button

**Cart Functionality:**
- **Quantity Update:** Buyers can modify quantities directly in the cart
- **Item Removal:** Individual items can be removed
- **Selective Checkout:** Buyers can select specific items using checkboxes
- **Total Calculation:** Real-time calculation of selected items' total
- **Empty Cart Handling:** Clear message when cart is empty

**Checkout Preparation:**
- "Proceed to Checkout" button appears when items are selected
- Only selected items will be processed in checkout
- System validates that at least one item is selected

**User Experience:**
- Clean, organized layout
- Easy quantity modification
- Clear pricing breakdown
- Visual feedback on actions

---

### **2.6 Checkout Process**

*[Demonstrate: Navigate to checkout.php]*

The **Checkout Process** is where buyers finalize their orders.

**Checkout Form Components:**

**1. Order Review:**
- List of selected items with:
  - Product name
  - Quantity
  - Unit price
  - Subtotal per item
- Total calculation for all items

**2. Delivery Type Selection:**
- **Home Delivery Option:**
  - Flat delivery fee of 30 pesos automatically added
  - Requires delivery address
- **Pickup Option:**
  - No delivery fee
  - Buyer picks up from designated pickup points
  - Address field optional

**3. Delivery Address:**
- Pre-filled from buyer's profile
- Editable field for flexibility
- Required for home delivery

**4. Payment Method Selection:**
- **Cash on Delivery (COD):** For home delivery orders
- **Cash on Pickup (COP):** For pickup orders
- Payment status set to "pending" initially

**Order Processing (Behind the Scenes):**
1. System validates all inputs
2. Checks product availability and stock quantities
3. Begins database transaction
4. For each selected item:
   - Creates order record in 'orders' table
   - Updates product quantity (reduces stock)
   - Increments product 'sold' count
5. Removes items from cart
6. Commits transaction
7. Redirects to orders page with success message

**Error Handling:**
- If stock is insufficient, transaction rolls back
- Error message displayed to buyer
- Cart remains unchanged if order fails

---

### **2.7 Order Management and Tracking**

*[Demonstrate: Navigate to orders.php]*

After checkout, buyers can view and track their **Orders**.

**Order Display Features:**

**Order List:**
- All orders displayed in chronological order (newest first)
- Each order shows:
  - Order ID
  - Product name
  - Farmer name
  - Quantity ordered
  - Total amount (including delivery fee if applicable)
  - **Status Badge:** Color-coded status indicators
    - Pending (yellow)
    - Confirmed (blue)
    - Delivered (green)
    - Cancelled (red)
  - Order date and time
  - Delivery information

**Filtering Options:**
- View all orders (default)
- Filter by status:
  - Pending orders
  - Confirmed orders
  - Delivered orders
  - Cancelled orders

**Order Details:**
- Complete order information
- Delivery type and address
- Payment method
- Payment status
- Order timeline

**User Benefits:**
- Complete order history
- Easy status tracking
- Quick access to order information
- Clear visual status indicators

---

### **2.8 Buyer Profile Management**

*[Demonstrate: Navigate to profile.php]*

Buyers can manage their **Profile Information**.

**Editable Profile Fields:**
- Name (first and last)
- Email address
- Phone number
- Address
- Age group
- Barangay

**Profile Features:**
- Update any information at any time
- Changes saved immediately
- Validation ensures data integrity
- Address updates affect future deliveries

---

## **PART 3: FARMER MODULE - COMPLETE FLOW**

### **🧑‍🌾 2. Seller Side**

*[Demonstrate: Navigate to farmer_register.php]*

Now for the **seller interface**.

**"Sellers can upload products, manage inventory, and track orders once verified by DA."**

### **3.1 Farmer Registration**

Let me demonstrate the **Farmer Registration** process, which has additional security measures.

**Registration Form Fields:**
- **Name:** Farmer's business or personal name
- **Email:** Must be unique across the platform
- **Password:** Securely hashed before storage
- **Location:** Required - where the farmer operates from
- **Phone Number:** Contact information
- **Certificate Upload (Optional):** Farmers can upload verification certificates
- **Seller Type Selection:** Critical field determining what they can sell
  - **Farmer:** Can sell Vegetables, Fruits, Fish, Cacao, Spices
  - **Poultry/Egg Producer:** Can only sell Eggs
  - **Fisherfolk:** Can only sell Fish

**Critical Security Feature - Verification System:**
- Upon registration, farmer account is created with **verification_status = 'pending'**
- **Farmers CANNOT log in** until an administrator approves their account
- This ensures only legitimate, verified farmers can sell on the platform
- Verification status is stored in the database with audit trail

**Registration Process:**
1. Farmer fills registration form
2. Optionally uploads certificate (validated for file type and size)
3. Selects seller type
4. System creates account with 'pending' status
5. Farmer is redirected to login page
6. **Login is blocked** until admin approval

---

### **3.2 Farmer Login (Pending Verification)**

*[Demonstrate: Attempt login with pending account]*

When a farmer with pending status tries to log in:

**Login Block:**
- System checks verification_status
- If status is 'pending': Login is blocked
- Error message: "Your account is pending admin verification. Please wait for approval before logging in."
- If status is 'rejected': Login is blocked with rejection reason
- Only 'approved' status allows login

**This demonstrates our security and quality control measures.**

---

### **3.3 Farmer Dashboard (After Approval)**

*[Demonstrate: Navigate to farmer_dashboard.php with approved account]*

Once approved by an administrator, farmers access their **comprehensive dashboard**.

**Dashboard Components:**

**1. Header Section:**
- Welcome message with farmer name
- **Seller Type Badge:** Visual indicator showing farmer type (Farmer/Poultry-Egg/Fisherfolk)

**2. Statistics Cards (Six Key Metrics):**
- **Total Products:** Number of products listed, clickable to product management
- **Total Sold:** Cumulative units sold across all products
- **Total Revenue:** Sum of revenue from delivered orders only
- **Pending Orders:** Orders requiring farmer attention, clickable to orders page
- **Delivered Orders:** Count of completed orders
- **Average Order Value:** Calculated from delivered orders

**3. Recent Orders Table:**
- Last 3 orders displayed in a table format
- Shows: Product name, Buyer name, Category, Quantity, Total amount, Status, Date
- "View All Orders" button for complete order list

**4. Best Sellers This Month:**
- Table showing top 5 products by quantity sold (city-wide, not just farmer's products)
- Displays: Rank, Product name, Category, Quantity sold
- Helps farmers understand market trends

**Dashboard Benefits:**
- Quick overview of business performance
- Immediate access to pending orders
- Market trend insights
- Revenue tracking

---

### **3.4 Product Management - Adding Products**

*[Demonstrate: Navigate to add_product.php]*

Farmers can **add products** to their inventory through a comprehensive form.

**Product Form Fields:**

**1. Basic Information:**
- **Product Name:** Required field
- **Category:** Dropdown based on seller type restrictions
  - Regular Farmers: Vegetables, Fruits, Fish, Cacao, Spices
  - Poultry/Egg: Only Eggs category
  - Fisherfolk: Only Fish category
- **Price:** Per unit price (decimal supported)
- **Quantity:** Available stock (integer)
- **Unit:** Selection between 'kilo' or 'piece'

**2. Product Details:**
- **Description:** Detailed product information (optional but recommended)
- **Location:** Pre-filled from farmer profile, editable

**3. Product Images:**
- **Primary Image:** Required - main product image
- **Additional Images:** Optional - multiple images supported
- Image validation:
  - Allowed formats: JPG, JPEG, PNG, WEBP, AVIF
  - File size limits enforced
  - Images stored in uploads/product_images/ directory
  - Unique filenames prevent conflicts

**Product Creation Process:**
1. Farmer fills product form
2. Uploads product images
3. System validates all inputs
4. Images are processed and stored
5. Product record created in 'products' table
6. Image records created in 'product_images' table
7. Product immediately available for buyers to purchase
8. Redirect to product management page

**Security and Validation:**
- Category restrictions enforced based on seller type
- Stock quantity must be positive
- Price must be valid decimal
- Image file type and size validation
- Location automatically set from farmer profile

---

### **3.5 Product Management - Viewing and Editing**

*[Demonstrate: Navigate to my_products.php]*

Farmers can **manage all their products** from one central location.

**Product List Features:**
- Grid or list view of all products
- Each product shows:
  - Product image
  - Product name
  - Category
  - Current stock quantity
  - Units sold
  - Price
  - Status (in stock/out of stock)

**Product Actions:**
- **Edit Product:** Update any product information
  - Modify name, price, quantity, description
  - Add or remove product images
  - Update stock levels
- **Delete Product:** Remove product from listing
  - System checks for existing orders before deletion
  - Cascade handling for related data

**Inventory Management:**
- Real-time stock updates
- Quick quantity modifications
- Out-of-stock indicators
- Sales tracking per product

---

### **3.6 Order Management for Farmers**

*[Demonstrate: Navigate to farmer/orders.php]*

Farmers receive and manage **orders from buyers**.

**Order Display:**
- All orders for farmer's products displayed
- Each order shows:
  - Order ID
  - Product name
  - Buyer name and contact
  - Quantity ordered
  - Total amount
  - Delivery type (home delivery/pickup)
  - Delivery address (if applicable)
  - Payment method
  - **Current Status**

**Order Status Management:**

**1. Pending Orders:**
- New orders start as "Pending"
- Farmer reviews order details
- Farmer can:
  - **Confirm Order:** Updates status to "Confirmed"
  - **Cancel Order:** Updates status to "Cancelled" (stock restored)

**2. Confirmed Orders:**
- Order has been accepted by farmer
- Farmer prepares product
- For delivery: Arranges delivery to buyer's address
- For pickup: Notifies buyer of pickup location

**3. Delivered Orders:**
- Farmer marks order as "Delivered" after fulfillment
- Payment status updated to "completed"
- Revenue recorded in farmer statistics
- Order appears in completed orders list

**Order Filtering:**
- Filter by status: Pending, Confirmed, Delivered, Cancelled
- Search by buyer name or product
- Sort by date or amount

**Business Benefits:**
- Clear order workflow
- Easy status updates
- Complete order history
- Revenue tracking

---

### **3.7 Farmer Profile Management**

*[Demonstrate: Navigate to farmer/profile.php]*

Farmers can update their **profile information**.

**Editable Fields:**
- Name
- Email
- Phone number
- Location
- Certificate (can re-upload if needed)

**Profile Updates:**
- Changes saved immediately
- Location updates affect product listings
- Certificate updates may require re-verification

---

## **PART 4: ADMIN MODULE - COMPLETE FLOW**

### **🏢 3. Admin Side**

*[Demonstrate: Navigate to admin_login.php]*

Finally, the **DA admin dashboard**.

**"This is where seller verification happens."**

### **4.1 Admin Login**

Administrators access the system through a **secure login**.

**Login Process:**
- Username and password authentication
- Session variables set: admin_id, admin_username
- Redirect to comprehensive admin dashboard

**Security:**
- Separate admin authentication
- No public registration for admin accounts
- Secure session management

---

### **4.2 Admin Dashboard Overview**

*[Demonstrate: Navigate to admin_dashboard.php]*

The **Admin Dashboard** provides complete system oversight.

**Dashboard Components:**

**1. Statistics Cards (Eight Key Metrics):**
- **Total Farmers:** All registered farmers, clickable to user management
- **Total Buyers:** All registered buyers, clickable to user management
- **Total Products:** All products on platform, clickable to product management
- **Total Orders:** All orders placed, clickable to order management
- **Total Revenue:** Platform-wide revenue from delivered orders
- **Pending Orders:** Orders requiring attention, clickable to filtered view
- **Active Products:** Products currently in stock
- **Delivered Orders:** Completed orders count

**2. Recent Orders Section:**
- Last 3 orders across entire platform
- Shows: Product, Farmer, Buyer, Category, Quantity, Total, Status, Date
- Quick overview of recent activity

**3. Interactive Charts and Analytics:**

**a) Order Status Distribution (Pie Chart):**
- Visual breakdown of orders by status
- Shows percentage distribution
- Helps identify order flow issues

**b) Product Category Distribution (Pie Chart):**
- Distribution of products by category
- Identifies popular categories
- Market trend analysis

**c) Sales & Orders Trend (Line Chart):**
- 12-month trend analysis
- Shows:
  - Order count over time
  - Revenue trends
  - Quantity sold trends
- Helps identify seasonal patterns

**d) Top Products by Sales (Bar Chart):**
- Top 10 best-selling products
- Visual comparison of sales volumes
- Identifies popular products

**e) Top Farmers by Revenue (Bar Chart):**
- Top 10 highest-earning farmers
- Revenue comparison
- Identifies top performers

**4. Top 5 Best-Selling Products Table:**
- Detailed table with:
  - Rank (with visual badges)
  - Product name
  - Category
  - Farmer name
  - Location
  - Units sold

**Dashboard Benefits:**
- Complete system overview at a glance
- Visual analytics for quick insights
- Quick navigation to all management functions
- Data-driven decision making

---

### **4.3 User Management - Farmer Verification**

*[Demonstrate: Navigate to manage_users.php]*

The most critical admin function is **Farmer Verification**.

**Show:**
* Approving seller applications
* Viewing reports
* Monitoring product listings and transactions

**Pending Farmers View:**
- List of all farmers with 'pending' status
- Each farmer entry shows:
  - Name and contact information
  - Location
  - Seller type
  - Registration date
  - Uploaded certificate (if any)

**Verification Process:**

**1. Review Farmer Information:**
- Admin reviews farmer details
- Checks uploaded certificates
- Verifies business information

**2. Approval Decision:**
- **Approve Farmer:**
  - Sets verification_status to 'approved'
  - Records verified_by (admin ID)
  - Records verified_at timestamp
  - Farmer can now log in and add products
  - Success message displayed

- **Reject Farmer:**
  - Sets verification_status to 'rejected'
  - Records rejection_reason (required)
  - Farmer cannot log in
  - Rejection reason shown if farmer attempts login

**3. Verification Audit Trail:**
- All verification actions logged
- Timestamp of verification
- Admin who performed verification
- Complete transparency and accountability

**User Management Features:**
- Filter farmers by verification status
- Search by name, email, or location
- View complete farmer profiles
- Delete farmers (with cascade to products)

**Security Importance:**
- Ensures platform quality
- Prevents fraudulent sellers
- Maintains buyer trust
- Creates accountability

---

### **4.4 User Management - Buyer Management**

*[Demonstrate: Buyer management section]*

Administrators can also **manage buyer accounts**.

**Buyer Management Features:**
- View all registered buyers
- Search buyers by name, email, phone, or address
- View buyer profiles and order history
- Delete buyer accounts (with cascade to cart)
- Filter by various criteria

**Buyer Information Available:**
- Registration date
- Total orders placed
- Total spending
- Contact information
- Address and location data

---

### **4.5 Product Management**

*[Demonstrate: Navigate to manage_products.php]*

Administrators have **complete oversight of all products**.

**Product Management Features:**
- View all products across platform
- Filter by:
  - Category
  - Farmer
  - Stock status (in stock/out of stock)
- Search by product name or description
- Edit any product
- Delete products
- View product statistics

**Product Information:**
- Complete product details
- Associated farmer
- Stock levels
- Sales performance
- Image gallery

**Administrative Control:**
- Remove inappropriate products
- Correct product information
- Monitor product quality
- Ensure accurate listings

---

### **4.6 Order Management**

*[Demonstrate: Navigate to manage_orders.php]*

Administrators can **monitor all orders** across the platform.

**Order Management Features:**
- View all orders from all buyers and farmers
- Filter by:
  - Order status
  - Farmer
  - Buyer
  - Date range
- Search orders
- View complete order details
- Update order statuses
- Cancel orders if necessary

**Order Information:**
- Complete transaction details
- Buyer and farmer information
- Product details
- Delivery information
- Payment status
- Order timeline

**Administrative Benefits:**
- Monitor platform activity
- Resolve disputes
- Track platform performance
- Ensure order fulfillment

---

### **4.7 Analytics and Reports**

*[Demonstrate: Analytics features]*

The admin dashboard includes **comprehensive analytics**.

**Available Reports:**
- User growth metrics
- Product performance analysis
- Revenue trends
- Order fulfillment rates
- Category popularity
- Top performers (farmers and products)

**Visual Analytics:**
- Interactive charts
- Exportable data
- Trend analysis
- Comparative metrics

---

## **PART 5: SYSTEM INTEGRATION AND FLOW**

### **5.1 Complete Order Lifecycle**

Let me now explain how all three roles work together in a **complete order lifecycle**:

**Step 1: Product Listing (Farmer)**
- Farmer registers and gets approved by admin
- Farmer adds products with images, prices, and stock
- Products appear on buyer dashboard

**Step 2: Product Discovery (Buyer)**
- Buyer browses products on dashboard
- Uses search to find specific items
- Views product details

**Step 3: Cart and Checkout (Buyer)**
- Buyer adds products to cart
- Selects items for checkout
- Chooses delivery type and payment method
- Submits order

**Step 4: Order Creation (System)**
- System validates stock availability
- Creates order with "Pending" status
- Reduces product stock
- Removes items from cart
- All within database transaction

**Step 5: Order Notification (Farmer)**
- Order appears in farmer's dashboard
- Shows in "Pending Orders" section
- Farmer reviews order details

**Step 6: Order Confirmation (Farmer)**
- Farmer confirms order
- Status updates to "Confirmed"
- Farmer prepares product

**Step 7: Order Fulfillment (Farmer)**
- For delivery: Farmer arranges delivery
- For pickup: Buyer collects from pickup point
- Farmer marks order as "Delivered"

**Step 8: Completion (System)**
- Payment status updates to "completed"
- Revenue recorded in farmer statistics
- Order appears in buyer's order history
- Both parties can view completed transaction

**This seamless integration demonstrates the system's ability to connect all stakeholders effectively.**

---

## **PART 6: SECURITY AND DATA INTEGRITY**

### **6.1 Security Features**

Our system implements **multiple layers of security**:

**Authentication Security:**
- All passwords hashed using bcrypt (PHP password_hash)
- Never stored in plain text
- Session-based authentication
- Automatic session timeout
- Role-based access control

**Data Protection:**
- All database queries use prepared statements
- Complete SQL injection prevention
- Input sanitization on all user data
- Output escaping (htmlspecialchars) prevents XSS attacks
- File upload validation (type and size)

**Transaction Safety:**
- Critical operations use database transactions
- Atomic operations ensure data consistency
- Rollback on errors maintains integrity
- Stock updates and order creation are synchronized

**Verification System:**
- Farmer verification prevents unauthorized sellers
- Audit trail for all verification actions
- Rejection reasons recorded for transparency

---

### **6.2 Database Architecture**

**Core Database Tables:**
- **buyers:** Consumer accounts
- **farmers:** Seller accounts with verification
- **products:** Product listings with inventory
- **product_images:** Multiple images per product
- **cart:** Shopping cart items
- **orders:** Complete order records
- **admins:** Administrator accounts
- **payment_methods:** Payment configuration
- **pickup_points:** Pickup location data

**Database Relationships:**
- Proper foreign key constraints
- Cascade deletion maintains integrity
- Normalized structure prevents data redundancy
- Indexed queries for performance

---

## **PART 7: SYSTEM BENEFITS AND IMPACT**

### **7.1 Benefits for Buyers**
- Direct access to fresh, organic produce
- Transparent pricing and product information
- Convenient ordering and delivery options
- Support for local agriculture
- Complete order tracking

### **7.2 Benefits for Farmers**
- Expanded market reach
- Direct customer connection
- Sales and inventory management tools
- Revenue tracking and analytics
- Reduced marketing costs
- Business performance insights

### **7.3 Benefits for the Community**
- Supports local economy
- Promotes healthy eating
- Creates sustainable business relationships
- Provides employment opportunities
- Strengthens agricultural sector
- Builds community connections

### **7.4 Platform Benefits**
- Quality control through verification
- Complete transaction tracking
- Comprehensive analytics
- Scalable architecture
- Secure and reliable operations

---

## **PART 8: TECHNICAL IMPLEMENTATION**

### **8.1 Technology Stack**
- **Backend:** PHP for server-side logic
- **Database:** MySQL/MariaDB for data persistence
- **Frontend:** HTML5, CSS3, JavaScript
- **Security:** Session-based authentication with password hashing
- **File Management:** Local file system for image storage

### **8.2 Code Quality**
- Modular file structure
- Reusable components
- Consistent coding standards
- Comprehensive error handling
- Database transaction management
- Input validation throughout

---

## **SLIDE: UAT Results (Chapter 4)**

Our User Acceptance Testing involved a survey of **20 respondents** who evaluated the system based on **ISO 25010 standards** for usability, performance, and security.

### **⭐ 100% Agreement**

The following aspects received **complete agreement** from all respondents:

* Buttons and interface elements work properly
* Login/authentication is reliable
* Navigation is easy and intuitive

### **⭐ 90%+ Agreement**

These features received **strong positive feedback**:

* Page loads fast
* System is easy for first-time users
* Transaction processing is smooth and efficient

### **⭐ Areas for Improvement**

While the overall feedback was highly positive, we identified areas for enhancement:

* **15%** expressed concern about data protection
* **15%** disagreed about consistent posting/ordering due to isolated issues

**Overall Results:**
- Weighted means ranged from **4.10 to 4.65** on a 5-point scale
- This indicates **"Strongly Agree"** overall evaluation
- System meets ISO 25010 standards for usability, performance, and functionality

---

## **SLIDE: Summary**

In summary:

* Our system successfully provides a **secure**, **functional**, and **user-friendly** online marketplace for organic produce in Cabadbaran City.
* It addresses all major issues identified in the needs assessment stage:
  - Verified digital marketplace for producers
  - Consumer verification of authenticity
  - Streamlined ordering process
  - Direct producer-to-consumer connection
  - Centralized DA records
  - Unified system for all operations
* Users confirm the system is **effective, fast, and reliable**.
* The system successfully connects verified local producers directly to consumers under DA supervision.

---

## **SLIDE: Conclusions**

We conclude that:

* The system is **highly acceptable** in terms of usability, performance, and functionality based on ISO 25010 evaluation.
* All specific objectives have been achieved:
  - Seller verification and role-based access implemented
  - Responsive, user-friendly interface created
  - Payment and delivery workflows integrated
  - Admin analytics and reporting provided
  - System evaluated through UAT
* Minor gaps relate to **security awareness** and **transactional consistency**, which will be addressed in future improvements.
* The system successfully bridges the gap between local producers and consumers while maintaining quality control through government verification.

---

## **SLIDE: Recommendations**

We recommend the following improvements for future development:

### **1. Enhanced Security Measures**
- Implement clearer user communication on privacy and data protection
- Add additional security layers and user education
- Address the 15% concern about data protection

### **2. Workflow Refinement**
- Refine posting and ordering workflows to eliminate minor issues
- Improve transactional consistency to address the 15% feedback
- Enhance error handling and user feedback

### **3. Feature Expansion**
- Subscription boxes for regular customers
- Direct messaging between buyers and sellers
- Courier integration for automated delivery tracking
- Real-time notifications via email and SMS

### **4. Mobile Application Development**
- Develop a dedicated mobile app for wider accessibility
- Improve mobile user experience
- Enable push notifications

### **5. Advanced Features**
- QR-based traceability for product origin
- Integration with logistics providers
- Enhanced analytics and reporting tools
- Multi-language support

---

## **SLIDE: Future Vision (High Rubrics Score)**

**"In the future, we envision this system scaling across other municipalities, integrating real-time logistics, QR-based traceability, and possibly blockchain verification to strengthen food safety and transparency."**

### **Expansion Vision:**

**1. Geographic Expansion:**
- Scale to other municipalities in the region
- Regional marketplace integration
- National platform potential

**2. Technology Enhancement:**
- **Real-time logistics integration** for automated delivery tracking
- **QR-based traceability** allowing consumers to scan and verify product origin, certification, and journey from farm to table
- **Blockchain verification** for immutable records of organic certification and supply chain transparency
- **IoT integration** for real-time inventory and quality monitoring

**3. Advanced Features:**
- AI-powered product recommendations
- Predictive analytics for demand forecasting
- Automated pricing optimization
- Integration with government databases for certification verification

**4. Impact Goals:**
- Strengthen food safety through complete traceability
- Enhance transparency in the organic produce supply chain
- Support sustainable agriculture practices
- Empower local producers with digital tools
- Build consumer trust through verified transactions

**This vision positions our system as a model for digital transformation in local agriculture, combining technology with trust to create sustainable marketplaces.**

---

## **QUESTIONS AND ANSWERS**

I am now ready to answer any questions you may have about the system.

**Common Questions Prepared:**

**Q: How do you ensure product quality?**
A: We have a comprehensive farmer verification system where DA administrators review and approve all farmers before they can sell. Farmers can upload certificates, and all seller information is tracked for accountability. The system maintains an audit trail of all verification actions.

**Q: What happens if a product is out of stock?**
A: The system performs real-time stock validation during checkout. If insufficient quantity is available, the order cannot be completed, and the buyer is notified immediately. Farmers can update stock quantities in real-time through their dashboard.

**Q: How is payment handled?**
A: Currently, we support Cash on Delivery for home deliveries and Cash on Pickup for pickup orders. The system tracks payment status throughout the order lifecycle. Future enhancements could include online payment gateways for more convenience.

**Q: Can orders be cancelled?**
A: Yes, both farmers and administrators can cancel orders. When an order is cancelled, the product stock is automatically restored to prevent inventory discrepancies, maintaining data integrity.

**Q: How do you prevent fraud?**
A: Multiple security measures: farmer verification system, secure authentication with password hashing, transaction logging, administrator oversight, and role-based access control. All critical operations are logged with timestamps and user IDs for complete audit trails.

**Q: What analytics are available?**
A: Buyers see personal statistics, farmers see sales and revenue metrics, and administrators have comprehensive platform-wide analytics including interactive charts, trends, and performance indicators. The DA can generate reports on supply, demand, and producer performance.

**Q: Is the system scalable?**
A: Yes, the database is properly normalized, queries are optimized with indexes, and the modular architecture allows for easy expansion. The system can handle growing numbers of users, products, and orders efficiently. Our future vision includes scaling to other municipalities.

**Q: How does this differ from existing marketplaces?**
A: Our system uniquely combines multi-commodity integration at a municipal level with government verification through the Department of Agriculture. This ensures trust, transparency, and quality control that is rarely found in existing solutions.

---

**Thank you for your attention. I'm now ready for your questions.**

---

*End of Sequential Presentation Script*

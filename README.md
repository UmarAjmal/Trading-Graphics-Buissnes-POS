# 🎨 Trading & Graphics Panaflex POS Web Application

[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vuedotjs)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-1.0-9553E9?style=for-the-badge&logo=inertia)](https://inertiajs.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38BDF8?style=for-the-badge&logo=tailwindcss)](https://tailwindcss.com)
[![SQLite](https://img.shields.io/badge/SQLite-3.x-003B57?style=for-the-badge&logo=sqlite)](https://sqlite.org)

**Trading & Graphics Panaflex POS** is a comprehensive, production-ready Point of Sale (POS), Inventory, Billing, and Financial Management Web Application specifically designed for **Printing Presses, Panaflex / Flex Media Banners, Graphics Design Shops, and General Trading Businesses**.

Built on **Laravel 10**, **Vue.js 3 (Inertia.js)**, and **Tailwind CSS**, this platform combines real-time area calculation (Square Feet / Height × Width) for custom media rolls with traditional unit-based product management, ledger accounting, and complete financial reporting.

---

## 🌟 Key Features & Highlights

### 🖨️ Specialized Panaflex & Media Area Calculation Engine
* **Automatic Sq.Ft Calculation**: Enter custom banner/flex dimensions (Length × Width) in Feet, Inches, or Meters, and the system automatically calculates total Square Feet (`units_sqft`) and dynamic total pricing.
* **Dual Product Support**:
  * **Panaflex / Roll Products**: Dimension-based calculation (Roll width in inches, Roll length in meters, Rate per Sq.Ft).
  * **Standard Products**: Quantity-based billing (pcs, boxes, sets, kg, etc.).
* **Design Job Notes & Finishing**: Add custom job instructions, customer specifications, and finishing notes per item.

### 🧾 Multi-Format Print Engine
* **80mm Thermal Receipts**: Compact receipt format optimized for thermal POS printers.
* **A4 Detailed Invoices**: Professional full-page invoices displaying dimension breakdowns, itemized sq.ft totals, terms, company branding, and payment status.
* **Print Preview**: Built-in modal previews for both A4 and 80mm thermal layouts before printing.

### 💰 Complete Financial & Ledger Accounting
* **Customer Ledgers & Receivables**: Track customer balances, advance payments (`CustomerAdvance`), credit sales, and pending payment collections.
* **Supplier Accounts & Payables**: Manage supplier opening balances, purchase payments, and prepayments.
* **Expense Management**: Categorized expense tracking with profit/loss impacts.
* **Party Balance API**: Real-time checking of party outstanding balances and transaction histories.

### 📦 Inventory & Stock Management
* **Batch Tracking**: Track stock batches (`StockBatch`), batch costs, purchase rates, and movement history (`StockMove`).
* **Stock Adjustments**: Add manual stock adjustments (increases/decreases) with audit reasons and batch linking.
* **Automatic SKU Generation**: One-click SKU generation for products.
* **Categories & Units**: Multi-category organization and custom measurement units.

### 🔐 Advanced Role-Based Access Control (RBAC)
* **Granular Permissions**: Permission-driven access control across sales, inventory, reports, user management, and system settings.
* **Pre-configured Roles**:
  * **Administrator**: Full system access and configuration.
  * **Sales Staff**: POS checkout, customer search, own sales listing.
  * **Accountant**: Financial reports, expense tracking, payment approvals.

### 📊 Comprehensive Reports & Analytics (PDF / Excel / CSV Exports)
* **Sales Reports**: Detailed sales analytics filtered by date, customer, or user.
* **Purchase Reports**: Supplier purchases and stock acquisition history.
* **Profit & Loss Reports**: Gross and net profit margin breakdown.
* **Expense Reports**: Category-wise expense distribution.
* **Cash Register Reports**: Shift opening/closing balances, sales summary, and drawer reconciliations.
* **Receivables & Payables Ledgers**: Aging reports and statement PDFs for all parties.
* **All Parties Ledger**: Unified financial ledger for customers and suppliers.

---

## 👥 Default Login Credentials

The system comes pre-seeded with ready-to-use accounts for immediate testing and operation:

| Role | Email | Password | Access Level |
| :--- | :--- | :--- | :--- |
| 👑 **Administrator** | `admin@pos.com` | `admin123` | Full System Access |
| 💼 **Sales Staff** | `sales@pos.com` | `sales123` | POS, Customers, Sales Only |
| 📊 **Accountant** | `accountant@pos.com` | `account123` | Reports, Expenses & Financials |

> ⚠️ **Important**: Change default passwords immediately after initial login in a production environment.

---

## 🛠️ Complete Module Breakdown

### 1. POS & Checkout Module (`/pos`, `/sales/create`)
- Real-time product search with barcode support.
- Custom Panaflex calculator (Length × Width with unit switches).
- Quick customer lookup and inline creation.
- Discount application (Item-level & Bill-level).
- Partial payments, cash, bank, and credit checkout support.
- Immediate 80mm or A4 invoice printing.

### 2. Sales & Returns Management (`/sales`)
- Complete invoice listing with status badges (Paid, Partial, Unpaid).
- Detailed sale view with printable invoice preview.
- **Return Engine**: Full or partial sales returns with automatic stock restoration and ledger credit generation.
- Return receipt printing (80mm & A4 formats).

### 3. Inventory & Product Catalog (`/products`, `/inventory`)
- Product listing with image upload, barcode preview, and SKU auto-generation.
- Type classification: Standard Goods vs. Panaflex Roll.
- Stock level monitoring with low stock warnings.
- Batch management (`/api/inventory/batches/{product}`) and movement history.
- Stock adjustment module (`/stock-adjustments/create`).

### 4. Customer Accounts & Credit Ledger (`/customers`)
- Complete customer profiles (Name, Phone, Address, Opening Balance).
- Customer Account Ledger (`/customers/{customer}/account`) with transaction timeline.
- Customer Advance Payments (`/customers/{customer}/advances`).
- Pending Credit Settlement (`/customers/{customerId}/pending-payments/...`).
- CSV Customer Import & Export.

### 5. Supplier & Purchase Management (`/suppliers`, `/purchases`)
- Supplier database with opening balance tracking.
- Purchase Order creation with supplier assignment and line items.
- Receiving workflow (`/purchases/{purchase}/receive`) updating stock batches.
- Prepayments and purchase payment entries.
- Supplier CSV Import & Export.

### 6. Expense Management (`/expenses`, `/expense-categories`)
- Custom expense category setup (Rent, Utilities, Wages, Ink/Media Raw Materials, etc.).
- Expense recording with date, payment method, category, and reference notes.

### 7. Cash Register Shifts (`/registers`)
- Register opening with initial cash float.
- Real-time tracking of cash sales vs. card/credit.
- Register closure with cash drawer variance report.

### 8. System Administration & Backups (`/settings`)
- **Company Branding**: Business Name, Phone, Address, NTN/Tax Number, Logo upload, Invoice header/footer text.
- **Tax Settings**: Configurable tax rates and default tax applicability.
- **Database Backup & Restore**: One-click manual backup download, scheduled automated backups, and database restoration.
- **System Information**: PHP version, environment details, server stats.
- **Safe Data Cleanup**: Admin utility to purge sample data selectively (Customers, Products, Sales, etc.).

---

## 💻 Tech Stack & Architecture

- **Backend**: Laravel 10 (PHP 8.2+)
- **Frontend**: Vue 3 (Composition API) + Inertia.js
- **UI Framework**: Tailwind CSS
- **Database**: SQLite (Zero-config default) / MySQL Support
- **Asset Bundler**: Vite (Pre-compiled assets included)
- **PDF Generation**: DomPDF / Laravel PDF Reporting Engine
- **Excel/CSV Export**: Laravel Excel (Maatwebsite)

---

## 🚀 Quick Start & Installation

### Prerequisites
- **PHP 8.2 or higher**
- **Composer**
- **XAMPP / WAMP** or PHP Built-in Server
- **Node.js 18+** (Optional, assets are pre-built)

### Installation Steps

1. **Clone the Repository**
   ```bash
   git clone https://github.com/UmarAjmal/Trading-Graphics-Buissnes-POS.git
   cd Trading-Graphics-Buissnes-POS
   ```

2. **Environment Setup**
   Copy the example `.env` file if `.env` does not exist:
   ```bash
   cp .env.example .env
   ```
   *(Note: The repository includes a pre-configured `.env` set up for SQLite).*

3. **Install PHP Dependencies** (If setup from scratch)
   ```bash
   composer install
   ```

4. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

5. **Run Database Migrations & Seeders**
   ```bash
   php artisan migrate:fresh --seed
   ```

6. **Start Development Server**

   **Option A: Artisan Development Server (Recommended)**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
   Visit: **http://localhost:8000**

   **Option B: XAMPP Apache**
   1. Place the project folder inside `C:\xampp\htdocs\`
   2. Start Apache and MySQL (if using MySQL) in XAMPP Control Panel.
   3. Visit: **http://localhost/AL-Raza_Trader_panaflex_pos_web-2/public**

---

## 📁 Directory Structure Overview

```
AL-Raza_Trader_panaflex_pos_web-2/
├── app/
│   ├── Http/Controllers/    # Controllers (POS, Sales, Inventory, Customers, Reports, Settings)
│   ├── Models/              # Eloquent Models (PanaflexSpec, Sale, SaleItem, Customer, Product, etc.)
│   └── Services/            # Business Logic & Area Calculation Engine (AreaService)
├── bootstrap/               # App bootstrap & cache
├── config/                  # App, Database, Auth, Inertia configurations
├── database/
│   ├── database.sqlite      # Default pre-seeded database
│   ├── migrations/          # Schema migrations
│   └── seeders/             # Database seeders (Users, Roles, Products)
├── public/                  # Public entry point & compiled assets (build/)
├── resources/
│   ├── js/                  # Vue 3 pages & Inertia components
│   │   ├── components/      # Shared UI components
│   │   ├── Layouts/         # Main layout wrapper
│   │   └── pages/           # Vue Page Components (POS, Customers, Sales, Reports, etc.)
│   └── views/               # Blade views (Print templates: A4 & 80mm)
├── routes/
│   ├── auth.php             # Authentication routes
│   └── web.php              # Application web routes
├── START_HERE.md            # Quick deployment summary guide
└── README.md                # Complete documentation
```

---

## 🛡️ License & Maintenance

Developed for **Al-Raza Trader / Trading & Graphics POS**. All rights reserved.

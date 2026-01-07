# 🍽️ Restaurant Management System - Basic Package

ระบบจัดการร้านอาหารแบบ Basic Package สำหรับแพ็กเกจ ฿699/เดือน

## ✨ Features

### 🎯 Admin Features
- 🔐 Single admin authentication
- 📊 Dashboard with daily statistics
- 🍔 Menu management (CRUD + images)
- 📂 Category management
- ➕ Add-ons management
- 🪑 Table management with QR code generation
- 📋 Order management (pending → paid → served)
- 📈 Basic reports (sales summary, top items, recent orders)

### 👥 Customer Features
- 📱 QR code menu scanning
- 🍕 Browse menu by categories
- 🔍 Search menu items
- ➕ Select add-ons and quantity
- 🛒 Shopping cart management
- 💬 Special instructions per item
- 💰 PromptPay QR payment
- 📊 Order status tracking

## 🚀 Quick Start

### Prerequisites
- Docker
- Docker Compose

### Installation

1. **Clone repository:**
```bash
git clone <repository-url>
cd restaurant-basic
```

2. **Create uploads directory:**
```bash
mkdir -p data/uploads
chmod 777 data/uploads
```

3. **Start Docker containers:**
```bash
docker-compose up -d
```

4. **Wait for MySQL initialization (~30 seconds):**
```bash
docker-compose logs -f mysql
```

5. **Access the system:**
- Admin Panel: `http://localhost/admin/`
- Customer Menu: `http://localhost/customer/menu.html?qr=<table_qr_code>`

### Default Credentials
```
Username: admin
Password: admin123
```

## 📁 Project Structure

```
restaurant-basic/
├── admin/                  # Admin panel pages
│   ├── index.html         # Login page
│   ├── dashboard.html     # Dashboard
│   ├── menu-management.html
│   ├── table-management.html
│   ├── order-management.html
│   └── reports.html
│
├── customer/              # Customer interface
│   ├── index.html        # QR redirect handler
│   ├── menu.html         # Menu browsing
│   ├── cart.html         # Shopping cart
│   ├── payment.html      # PromptPay payment
│   └── order-status.html # Order tracking
│
├── api/                   # Backend API
│   ├── config/
│   │   └── database.php  # Database connection
│   ├── admin/
│   │   └── auth.php      # Authentication
│   ├── menu/
│   │   ├── menu.php      # Menu CRUD
│   │   ├── category.php  # Category CRUD
│   │   ├── addon.php     # Add-ons CRUD
│   │   └── list.php      # Menu list for customers
│   ├── orders/
│   │   └── order.php     # Order management
│   └── tables/
│       ├── table.php     # Table CRUD
│       └── list.php      # Table list
│
├── assets/
│   ├── css/
│   │   └── custom.css    # Custom styles
│   ├── js/
│   │   └── custom.js     # Utility functions
│   └── images/
│       └── menu/         # Uploaded menu images
│
├── database/
│   └── restaurant.sql    # Database schema & seed data
│
├── docker/
│   ├── nginx/
│   │   └── default.conf  # Nginx configuration
│   └── php/
│       └── Dockerfile    # PHP-FPM Dockerfile
│
├── docker-compose.yml     # Docker services configuration
├── .gitignore
└── README.md
```

## 🛠️ Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript (Vanilla), Bootstrap 5.3
- **Backend:** PHP 8.2 (FPM)
- **Database:** MySQL 8.0
- **Web Server:** Nginx 1.27 (Alpine)
- **Containerization:** Docker & Docker Compose

## 📊 Database Schema

### Tables
- `admins` - Admin users
- `categories` - Menu categories
- `menu_items` - Menu items
- `menu_addons` - Add-ons for menu items
- `tables` - Restaurant tables with QR codes
- `orders` - Customer orders
- `order_items` - Order line items
- `order_item_addons` - Selected add-ons per order item

## 🔧 Configuration

### Database Connection
Edit `api/config/database.php` for custom database settings:
```php
$host = 'mysql';  // Docker service name
$dbname = 'restaurant_db';
$username = 'restaurant_user';
$password = 'restaurant_pass';
```

### Docker Environment
Edit `docker-compose.yml` for custom settings:
```yaml
MYSQL_DATABASE: restaurant_db
MYSQL_USER: restaurant_user
MYSQL_PASSWORD: restaurant_pass
```

## 🐳 Docker Commands

### Start services
```bash
docker-compose up -d
```

### Stop services
```bash
docker-compose down
```

### Restart a service
```bash
docker-compose restart php
docker-compose restart nginx
```

### View logs
```bash
docker-compose logs -f
docker-compose logs nginx
docker-compose logs php
docker-compose logs mysql
```

### Rebuild containers
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Reset database (⚠️ deletes all data)
```bash
docker-compose down -v
docker-compose up -d
```

### Access MySQL
```bash
docker exec -it test_mysql mysql -u restaurant_user -prestaurant_pass restaurant_db
```

### Access containers
```bash
# PHP container
docker exec -it test_php sh

# Nginx container
docker exec -it test_nginx sh

# MySQL container
docker exec -it test_mysql bash
```

## 🔍 Troubleshooting

### 500 Internal Server Error
```bash
# Check PHP logs
docker logs test_php --tail 50

# Check Nginx logs
docker logs test_nginx --tail 50

# Restart PHP
docker-compose restart php
```

### Database connection failed
```bash
# Check if MySQL is ready
docker exec -it test_mysql mysqladmin ping -h localhost -u root -proot

# Check if database exists
docker exec -it test_mysql mysql -u restaurant_user -prestaurant_pass -e "SHOW DATABASES;"

# Re-import database
docker exec -i test_mysql mysql -u restaurant_user -prestaurant_pass restaurant_db < database/restaurant.sql
```

### Permission denied for uploads
```bash
chmod 777 data/uploads
```

### Port already in use
Edit `docker-compose.yml` and change the exposed port:
```yaml
expose:
  - "8080"  # Change from 80
```

## 📱 Features NOT Included (Pro Package Only)

- ❌ Review & rating system
- ❌ Multi-admin management
- ❌ Advanced reports with Excel export
- ❌ Real-time notifications & sound alerts
- ❌ Cooking status tracking
- ❌ Advanced filtering & search

## 🔐 Security Notes

⚠️ **Production Deployment:**
1. Change default admin password immediately
2. Use strong database passwords
3. Enable HTTPS/SSL
4. Configure proper firewall rules
5. Regular database backups
6. Keep Docker images updated

## 📄 License

Proprietary - Basic Package (฿699/month)

## 🤝 Support

For support and feature requests, please contact the development team.

---

**Version:** 1.0.0  
**Last Updated:** January 7, 2026  
**Package:** Basic (฿699/month)
# Sanvito - Clothing Store Management System

## Introduction

Sanvito is a comprehensive web-based clothing store management system designed to showcase and manage a collection of fashion products. The system provides both a public-facing storefront for customers to browse products and an administrative panel for store managers to maintain the product catalog, categories, and site settings.

This project serves as an academic endeavor to demonstrate full-stack web development skills, including database design, backend API development, and responsive frontend implementation.

## System Features

### Public Storefront
- **Product Catalog**: Browse products organized by categories
- **Product Variants**: Support for multiple colors and sizes per product
- **Image Gallery**: Multiple product images with color-specific variants
- **Featured Products**: Highlighted products on the homepage
- **Responsive Design**: Mobile-friendly interface with RTL support for Kurdish language
- **SEO Optimization**: Meta tags, Open Graph, and structured data for search engines

### Administrative Panel
- **Dashboard**: Overview of store statistics and recent activity
- **Product Management**: Add, edit, and delete products with variant support
- **Category Management**: Organize products into hierarchical categories
- **Image Upload**: Handle multiple product images with optimization
- **User Management**: Admin user accounts with role-based access
- **Site Settings**: Configurable site-wide settings

### API Features
- **RESTful API**: JSON-based product data retrieval
- **Product Filtering**: Query products by category and status
- **Variant Data**: Comprehensive color and size information
- **Image Paths**: Standardized image URL handling

## Technology Stack

### Frontend
- **HTML5**: Semantic markup structure
- **Tailwind CSS**: Utility-first CSS framework for responsive design
- **Alpine.js**: Lightweight JavaScript framework for interactive components
- **Font Awesome**: Icon library for UI elements

### Backend
- **PHP 7.4+**: Server-side scripting with object-oriented programming
- **PDO**: Secure database abstraction layer with prepared statements

### Database
- **MySQL 5.7+**: Relational database with UTF-8 support
- **InnoDB Engine**: ACID-compliant transactions and foreign key constraints

### Development Tools
- **XAMPP**: Local development environment (Apache, MySQL, PHP)
- **Git**: Version control system

## Architecture / Database Design

### Database Schema

The system uses a normalized relational database design with the following key tables:

- **admin_users**: Administrative user accounts with role-based permissions
- **categories**: Product categories with multilingual support
- **products**: Core product information including pricing and status
- **product_images**: Multiple images per product with sort ordering
- **product_colors**: Color variants with associated images
- **product_sizes**: Size variants with stock tracking
- **site_settings**: Configurable site-wide settings

### Application Architecture

- **MVC Pattern**: Separation of concerns between data, logic, and presentation
- **RESTful API**: Stateless communication between frontend and backend
- **File Upload System**: Organized image storage with optimization
- **Session Management**: Secure admin authentication
- **Caching Headers**: Performance optimization for static assets

## Installation Guide

### Prerequisites
- XAMPP (or similar Apache/MySQL/PHP stack)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser with JavaScript enabled

### Step-by-Step Installation

1. **Clone or Download the Project**
   ```
   Download the project files to your XAMPP htdocs directory
   ```

2. **Database Setup**
   - Start XAMPP and ensure Apache and MySQL services are running
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `sanvito`
   - Import the `sanvito_database.sql` file

3. **Configuration**
   - Open `config.php` and verify database connection settings
   - Update `SITE_URL` and `ADMIN_URL` if deploying to a different domain

4. **File Permissions**
   - Ensure the `uploads/` directory is writable by the web server
   - Set appropriate permissions for image upload functionality

5. **Access the Application**
   - Public storefront: http://localhost/sanvito/
   - Admin panel: http://localhost/sanvito/admin/

### Default Admin Credentials
- Username: admin
- Password: admin123

## Usage Instructions

### For Customers
1. **Browse Products**: Navigate through categories on the homepage
2. **View Product Details**: Click on products to see variants and images
3. **Color Selection**: Choose different colors to view associated images
4. **Size Selection**: Select available sizes for products

### For Administrators
1. **Login**: Access the admin panel at `/admin/login.php`
2. **Dashboard**: View store statistics and recent products
3. **Manage Categories**: Add/edit product categories
4. **Product Management**:
   - Add new products with descriptions and pricing
   - Upload multiple images per product
   - Configure color and size variants
   - Set featured status
5. **Settings**: Configure site-wide settings

### API Usage
The products API is available at `/products_api.php` and returns JSON data:
```json
[
  {
    "id": 1,
    "title": "Product Name",
    "description": "Product description",
    "price": "29.99",
    "category": "Category Name",
    "colors": [...],
    "sizes": [...],
    "main_image": "path/to/image.jpg"
  }
]
```

## Screenshots

- Homepage - Product catalog display
- Product Detail Page - Individual product view with variants
- Admin Dashboard - Management overview
- Product Management - Add/edit product interface
- Category Management - Category administration
- Image Upload Interface - Product image management

## Future Enhancements

### Planned Features
- **Shopping Cart**: Add-to-cart functionality and checkout process
- **User Authentication**: Customer accounts and order history
- **Payment Integration**: Secure payment gateway integration
- **Inventory Management**: Advanced stock tracking and alerts
- **Order Management**: Complete order processing system
- **Search Functionality**: Product search and filtering
- **Wishlist**: Customer favorite products
- **Reviews and Ratings**: Customer feedback system
- **Multi-language Support**: Additional language options
- **Mobile App**: Native mobile application
- **Analytics Dashboard**: Sales and traffic analytics

### Technical Improvements
- **Framework Migration**: Transition to Laravel or similar PHP framework
- **API Documentation**: Comprehensive API documentation with Swagger
- **Testing Suite**: Unit and integration tests
- **Docker Support**: Containerized deployment
- **CI/CD Pipeline**: Automated testing and deployment

## Author

Information Technology Student
University of Science, Kurdistan Region – Iraq

Email: akarbakr72@gmail.com
Phone: +964 750 721 0616

This project was developed as an academic student project to demonstrate web development skills and e-commerce system design.

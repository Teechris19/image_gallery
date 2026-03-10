# Image Gallery

A simple, function-based image gallery application built with plain PHP.

## Features

- Upload images with validation (type, size)
- Automatic thumbnail generation
- View images in a grid layout
- View single image details
- Delete images
- Responsive design
- Secure file handling

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB)
- Apache with mod_rewrite (recommended)
- GD or Imagick extension for image processing
- Fileinfo extension for MIME type detection

## Installation

1. **Configure database**
   
   Edit `application/core/config.php` and update:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'image_gallery');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

2. **Create database**
   
   ```sql
   CREATE DATABASE image_gallery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
   
   The application will automatically create the required tables on first run.

3. **Set permissions**
   
   Make sure the uploads folder is writable:
   ```bash
   chmod 755 public/uploads
   chmod 755 public/uploads/thumbs
   ```
   
   Or for development:
   ```bash
   chmod 777 public/uploads
   chmod 777 public/uploads/thumbs
   ```

4. **Access the application**
   
   - If using a web server, point it to the `image-gallery` folder
   - Access via: `http://localhost/image-gallery/`
   - Or directly: `http://localhost/image-gallery/public/`

## Folder Structure

```
image-gallery/
├── application/
│   ├── core/
│   │   ├── config.php       # Configuration & constants
│   │   └── database.php     # Database connection
│   └── functions/
│       ├── general.php      # Helper functions
│       └── images.php       # Image operations
├── public/
│   ├── index.php            # Gallery homepage
│   ├── upload.php           # Upload form
│   ├── view.php             # Single image view
│   ├── delete.php           # Delete handler
│   ├── css/
│   │   └── style.css        # Styles
│   ├── js/
│   │   └── script.js        # JavaScript
│   └── uploads/             # Uploaded images
│       └── thumbs/          # Thumbnails
└── index.php                # Root redirect
```

## Configuration

Edit `application/core/config.php` to customize:

- **Database credentials**
- **Upload settings** (max size, allowed types)
- **Thumbnail dimensions**
- **Application name**

## Security Features

- `.htaccess` protection for application folder
- MIME type validation (not just extension)
- File size limits
- Unique filename generation
- XSS protection via escaping functions
- CSRF protection ready (session-based flash messages)

## API Functions

### General Helpers
- `e($string)` - Escape HTML
- `redirect($url)` - Redirect to URL
- `is_post()` - Check if POST request
- `get_input($key)` - Get POST/GET value
- `set_flash($type, $message)` - Set flash message
- `format_size($bytes)` - Human-readable file size

### Image Functions
- `get_all_images()` - Get all images
- `get_image_by_id($id)` - Get single image
- `upload_image($file)` - Upload and process image
- `delete_image($id)` - Delete image
- `create_thumbnail($source, $dest, $width)` - Generate thumbnail

## License

MIT License - Feel free to use and modify.

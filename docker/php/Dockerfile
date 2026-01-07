FROM php:8.2-fpm-alpine

# ติดตั้ง PDO และ PDO MySQL (สำคัญ!)
RUN docker-php-ext-install pdo pdo_mysql

# ปรับค่า upload เผื่ออัปโหลดรูปเมนู
RUN { \
  echo "upload_max_filesize=20M"; \
  echo "post_max_size=25M"; \
  echo "max_execution_time=120"; \
} > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html
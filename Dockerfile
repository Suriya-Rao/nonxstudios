# ---------------------------------------------------------------------------
# NONXSTUDIOS — production image
#
# Apache + PHP 8.2 (mod_php) with the document root pointed at public/.
# The database runs as a separate service (see docker-compose.yml).
#
# Build:  docker build -t nonxstudios .
# Run:    docker compose up -d
# ---------------------------------------------------------------------------
FROM php:8.2-apache

# --- System dependencies + PHP extensions ----------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libavif-dev \
        libzip-dev \
        libonig-dev \
        default-mysql-client \
        unzip \
    && docker-php-ext-configure gd \
        --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        gd \
        mbstring \
        opcache \
        zip \
    && a2enmod rewrite headers expires deflate \
    && rm -rf /var/lib/apt/lists/*

# --- PHP configuration ------------------------------------------------------
COPY docker/php.ini /usr/local/etc/php/conf.d/99-nonxstudios.ini

# --- Apache configuration ---------------------------------------------------
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# --- Application ------------------------------------------------------------
WORKDIR /var/www/nonxstudios
COPY . /var/www/nonxstudios

# Document root points at public/ so app, config, storage and vendor stay
# outside the web root and are unreachable over HTTP.
ENV APACHE_DOCUMENT_ROOT=/var/www/nonxstudios/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Writable storage for uploads and logs; owned by the Apache user.
RUN mkdir -p storage/logs storage/uploads/thumbnails \
    && chown -R www-data:www-data storage \
    && chmod -R 775 storage

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]

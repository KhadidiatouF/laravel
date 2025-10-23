# Utiliser PHP 8.2 avec extensions nécessaires
FROM php:8.2-fpm

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    zip \
    curl

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www

# Copier tout le projet
COPY . .

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader

# Exposer le port 8000
EXPOSE 8000

# Commande de démarrage
CMD php artisan serve --host=0.0.0.0 --port=8000

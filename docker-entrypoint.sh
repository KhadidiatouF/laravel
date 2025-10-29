#!/bin/sh

# Attendre que la base de données soit prête
echo "Waiting for database to be ready..."
while ! pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME; do
  echo "Database is unavailable - sleeping"
  sleep 1
done

echo "Database is up - executing migrations"
php artisan migrate --force

echo "Installing Passport keys"
php artisan passport:install --force --no-interaction

echo "Setting Passport keys in environment"
PASSPORT_PRIVATE_KEY=$(cat storage/oauth-private.key | sed 's/$/\\n/' | tr -d '\n')
PASSPORT_PUBLIC_KEY=$(cat storage/oauth-public.key | sed 's/$/\\n/' | tr -d '\n')
echo "PASSPORT_PRIVATE_KEY=\"$PASSPORT_PRIVATE_KEY\"" >> .env
echo "PASSPORT_PUBLIC_KEY=\"$PASSPORT_PUBLIC_KEY\"" >> .env

echo "Seeding database"
php artisan db:seed --force

echo "Generating Swagger documentation"
php artisan l5-swagger:generate

echo "Starting Laravel application..."
exec "$@"
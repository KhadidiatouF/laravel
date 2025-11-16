# OmPay API - Système Bancaire

API REST Laravel pour un système bancaire complet avec authentification, gestion des comptes, transactions et marchands.

## 🚀 Démarrage Rapide

### Prérequis

- PHP 8.1 ou supérieur
- Composer
- PostgreSQL ou MySQL
- Node.js & npm (pour les assets frontend)

### Installation

1. **Cloner le repository**
   ```bash
   git clone <repository-url>
   cd ompay-api
   ```

2. **Installer les dépendances PHP**
   ```bash
   composer install
   ```

3. **Installer les dépendances Node.js**
   ```bash
   npm install
   ```

4. **Configuration de l'environnement**
   ```bash
   cp .env.example .env
   ```

   Modifier `.env` avec vos configurations :
   ```env
   APP_NAME="OmPay API"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   # Base de données
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=ompay
   DB_USERNAME=votre_username
   DB_PASSWORD=votre_password

   # Mail (optionnel pour développement)
   MAIL_MAILER=log

   # Twilio (pour SMS)
   TWILIO_SID=votre_sid
   TWILIO_TOKEN=votre_token
   TWILIO_FROM=+1234567890
   ```

5. **Générer la clé d'application**
   ```bash
   php artisan key:generate
   ```

6. **Exécuter les migrations**
   ```bash
   php artisan migrate
   ```

7. **Installer Passport pour l'authentification OAuth**
   ```bash
   php artisan passport:install
   ```

8. **Exécuter les seeders (optionnel)**
   ```bash
   php artisan db:seed
   ```

9. **Démarrer le serveur**
   ```bash
   php artisan serve
   ```

L'API sera accessible sur `http://localhost:8000`

## 📚 Architecture du Projet

### Structure des Dossiers

```
app/
├── Console/           # Commandes Artisan
├── Events/            # Événements
├── Exceptions/        # Gestion des erreurs
├── Http/
│   ├── Controllers/   # Contrôleurs API
│   ├── Middleware/    # Middlewares
│   ├── Requests/      # Validation des requêtes
│   └── Resources/     # Transformation des données
├── Jobs/              # Tâches en arrière-plan
├── Listeners/         # Écouteurs d'événements
├── Mail/              # Templates d'emails
├── Models/            # Modèles Eloquent
├── Observers/         # Observers de modèles
├── Providers/         # Service Providers
├── Repositories/      # Couche Repository
├── Rules/             # Règles de validation personnalisées
├── Services/          # Logique métier
└── Traits/            # Traits réutilisables

database/
├── factories/         # Factories pour les tests
├── migrations/        # Migrations de base de données
└── seeders/           # Seeders pour données de test

routes/
├── api.php            # Routes API
├── channels.php       # Routes broadcasting
├── console.php        # Routes console
└── web.php            # Routes web

tests/                 # Tests unitaires et fonctionnels
```

### Modèles Principaux

- **User**: Utilisateur de base (Admin, Client, Marchand)
- **Client**: Extension de User pour les clients
- **Marchand**: Extension de User pour les marchands
- **Compte**: Comptes bancaires
- **Transaction**: Transactions financières

## 🛠️ Développement

### Créer une Migration

```bash
# Créer une migration
php artisan make:migration create_example_table

# Exemple de migration
php artisan make:migration add_status_to_users_table --table=users

# Exécuter les migrations
php artisan migrate

# Annuler la dernière migration
php artisan migrate:rollback

# Annuler toutes les migrations
php artisan migrate:reset

# Rafraîchir toutes les migrations
php artisan migrate:fresh
```

### Créer un Modèle

```bash
# Créer un modèle avec migration, factory et seeder
php artisan make:model Example -mfs

# Options disponibles :
# -m : Créer la migration
# -f : Créer la factory
# -s : Créer le seeder
# -c : Créer le contrôleur
# -r : Créer une ressource API
```

### Créer un Contrôleur

```bash
# Contrôleur de ressource API
php artisan make:controller Api/ExampleController --api

# Contrôleur avec toutes les méthodes CRUD
php artisan make:controller ExampleController --resource
```

### Créer une Factory

```bash
php artisan make:factory ExampleFactory

# Exemple de factory
<?php

namespace Database\Factories;

use App\Models\Example;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExampleFactory extends Factory
{
    protected $model = Example::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
```

### Créer un Seeder

```bash
php artisan make:seeder ExampleSeeder

# Exemple de seeder
<?php

namespace Database\Seeders;

use App\Models\Example;
use Illuminate\Database\Seeder;

class ExampleSeeder extends Seeder
{
    public function run(): void
    {
        Example::factory(10)->create();
    }
}

# Exécuter un seeder spécifique
php artisan db:seed --class=ExampleSeeder

# Exécuter tous les seeders
php artisan db:seed
```

### Créer une Request de Validation

```bash
php artisan make:request StoreExampleRequest

# Exemple de request
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:examples',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'email.unique' => 'Cet email est déjà utilisé.',
        ];
    }
}
```

### Créer un Service

```bash
# Créer un service dans app/Services/
# Exemple de service
<?php

namespace App\Services;

use App\Models\Example;
use App\Repositories\ExampleRepository;

class ExampleService
{
    protected $repository;

    public function __construct(ExampleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }
}
```

### Créer un Repository

```bash
# Créer un repository dans app/Repositories/
# Exemple de repository
<?php

namespace App\Repositories;

use App\Models\Example;

class ExampleRepository
{
    public function getAll()
    {
        return Example::all();
    }

    public function find($id)
    {
        return Example::findOrFail($id);
    }

    public function create(array $data)
    {
        return Example::create($data);
    }

    public function update($id, array $data)
    {
        $example = Example::findOrFail($id);
        $example->update($data);
        return $example;
    }

    public function delete($id)
    {
        return Example::findOrFail($id)->delete();
    }
}
```

## 🧪 Tests

### Exécuter les Tests

```bash
# Tous les tests
php artisan test

# Tests spécifiques
php artisan test --filter=ExampleTest

# Tests avec couverture
php artisan test --coverage
```

### Créer un Test

```bash
# Test de fonctionnalité
php artisan make:test ExampleTest

# Test unitaire
php artisan make:test ExampleTest --unit
```

## 📡 API Endpoints

### Authentification

- `POST /login` - Connexion avec téléphone + PIN
- `GET /api/v1/auth/me` - Informations utilisateur connecté

### Comptes

- `GET /api/v1/comptes` - Lister les comptes
- `POST /api/v1/comptes` - Créer un compte
- `GET /api/v1/comptes/{numero}/solde` - Consulter le solde

### Transactions

- `GET /api/v1/transactions` - Lister les transactions
- `POST /api/v1/transactions` - Créer une transaction
- `GET /api/v1/transactions/{id}` - Détails d'une transaction

### Activation

- `POST /api/v1/verify-otp` - Vérifier le code OTP
- `POST /api/v1/resend-otp` - Renvoyer le code OTP
- `POST /api/v1/modifier-mdp` - Modifier le PIN

## 🔧 Configuration

### Base de Données

Le projet supporte PostgreSQL et MySQL. Configuration dans `.env` :

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ompay
DB_USERNAME=user
DB_PASSWORD=password
```

### Mail

Configuration pour l'envoi d'emails :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre_email@gmail.com
MAIL_PASSWORD=votre_mot_de_passe_app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=votre_email@gmail.com
MAIL_FROM_NAME="OmPay"
```

Pour le développement, utilisez `MAIL_MAILER=log` pour enregistrer les emails dans les logs.

### SMS (Twilio)

```env
TWILIO_SID=votre_sid
TWILIO_TOKEN=votre_token
TWILIO_FROM=+1234567890
```

## 🚀 Déploiement

### Préparation pour la Production

1. **Variables d'environnement**
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://votre-domaine.com
   ```

2. **Optimisation**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Base de données**
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```

4. **Permissions**
   ```bash
   chown -R www-data:www-data storage
   chown -R www-data:www-data bootstrap/cache
   ```

### Déploiement avec Docker

Le projet inclut une configuration Docker :

```bash
# Construire et démarrer
docker-compose up -d --build

# Exécuter les migrations dans le conteneur
docker-compose exec app php artisan migrate
```

## 📊 Monitoring et Logs

### Logs Laravel

Les logs sont stockés dans `storage/logs/laravel.log`

### Debugbar (Développement)

Le package Laravel Debugbar est configuré pour le développement.

### Commandes Utiles

```bash
# Vider les logs
php artisan log:clear

# Voir les routes
php artisan route:list

# Voir les tâches planifiées
php artisan schedule:list

# Optimiser l'application
php artisan optimize
```

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📝 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 🆘 Support

Pour obtenir de l'aide :
- Ouvrir une issue sur GitHub
- Contacter l'équipe de développement
- Consulter la documentation API sur `/api/documentation`

---

**Développé avec ❤️ par l'équipe OmPay**

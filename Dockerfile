FROM php:8.2-cli

# Installer les extensions PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers du projet
COPY . .

# Exposer le port
EXPOSE 8080

# Démarrer le serveur PHP
CMD php -S 0.0.0.0:${PORT:-8080} -t .

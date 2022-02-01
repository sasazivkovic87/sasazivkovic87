# Development environment setup

## Requirements

- make
- git
- docker & docker-compose

## Steps

```
git clone REPOSITORY_URL new_project_name
cd new_project_name/
rm -rf .git/
make create-symfony-skeleton     # Or make create-symfony-website
```

Then go to http://localhost:8001

## Commands

- make : List all available commands with short description

### Docker commands

- **make up (docker-compose up -d):** Run all containers in detached mode
- **make ps (docker-compose ps):** List current project's containers
- **make sh (docker-compose exec php-apache bash):** Run shell inside the php container
- **make stop (docker-compose stop):** Stop current project's containers
- **make stop-all (docker-compose stop):** Stop all running containers on your machine
- **make rm (docker-compose rm):** Remove current project's stopped containers
- **make down (docker-compose down):** Stop & Remove current project's containers

### Symfony commands

- **make create-symfony-skeleton:** Create a symfony 5.* skeleton from symfony/skeleton
- **make create-symfony-website:** Create a symfony 5.* website from symfony/website-skeleton

- **make maker:** Install symfony/maker-bundle
- **make api-platform:** Install api-platform/api-pack
- **make entity:** Create symfony entity
- **make controller:** Create symfony controller
- **make logs (docker-compose exec php-apache tail -f var/logs/dev.log):** See dev logs
- **make cache-clear (docker-compose exec php-apache rm -rf var/cache/*):** Remove the cache

### Database commands

- **make orm:** Install symfony/orm-pack.

Then you need to replace the value of DATABASE_URL in your .env file with:
```
DATABASE_URL=mysql://root@mysql:3306/YOUR_DB_NAME
```
- **make db-create**: Create a database
- **make db-migration**: Create a new migration
- **make db-diff**: Generate a migration
- **make db-migrate**: Run migrations

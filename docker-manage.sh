#!/bin/bash

# Docker Management Script for SMAN 1 Baleendah
# Usage: ./docker-manage.sh [command]

set -e

PROJECT_NAME="sman1-baleendah"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_usage() {
    echo "Usage: $0 [command]"
    echo ""
    echo "Commands:"
    echo "  up          Start all containers"
    echo "  down        Stop all containers"
    echo "  restart     Restart all containers"
    echo "  build       Build containers"
    echo "  logs        Show logs"
    echo "  status      Show container status"
    echo "  shell       Access app container shell"
    echo "  migrate     Run database migrations"
    echo "  seed        Run database seeders"
    echo "  optimize    Optimize Laravel application"
    echo "  backup-db   Backup database"
    echo "  restore-db  Restore database from backup"
    echo "  clean       Clean up unused Docker resources"
}

case "$1" in
    "up")
        echo -e "${GREEN}Starting containers...${NC}"
        docker-compose up -d
        echo -e "${GREEN}Containers started successfully!${NC}"
        ;;
    
    "down")
        echo -e "${YELLOW}Stopping containers...${NC}"
        docker-compose down
        echo -e "${YELLOW}Containers stopped.${NC}"
        ;;
    
    "restart")
        echo -e "${YELLOW}Restarting containers...${NC}"
        docker-compose restart
        echo -e "${GREEN}Containers restarted!${NC}"
        ;;
    
    "build")
        echo -e "${BLUE}Building containers...${NC}"
        docker-compose build --no-cache
        echo -e "${GREEN}Build completed!${NC}"
        ;;
    
    "logs")
        if [ -n "$2" ]; then
            docker-compose logs -f "$2"
        else
            docker-compose logs -f
        fi
        ;;
    
    "status")
        echo -e "${BLUE}Container Status:${NC}"
        docker-compose ps
        ;;
    
    "shell")
        echo -e "${BLUE}Accessing app container shell...${NC}"
        docker-compose exec app /bin/sh
        ;;
    
    "migrate")
        echo -e "${BLUE}Running database migrations...${NC}"
        docker-compose exec app php artisan migrate
        ;;
    
    "seed")
        echo -e "${BLUE}Running database seeders...${NC}"
        docker-compose exec app php artisan db:seed
        ;;
    
    "optimize")
        echo -e "${BLUE}Optimizing Laravel application...${NC}"
        docker-compose exec app php artisan config:cache
        docker-compose exec app php artisan route:cache
        docker-compose exec app php artisan view:cache
        echo -e "${GREEN}Optimization completed!${NC}"
        ;;
    
    "backup-db")
        BACKUP_FILE="backup-$(date +%Y%m%d-%H%M%S).sql"
        echo -e "${BLUE}Creating database backup: $BACKUP_FILE${NC}"
        docker-compose exec db mysqldump -u sman1_user -psman1_password_2024 sman1_baleendah > "$BACKUP_FILE"
        echo -e "${GREEN}Database backup created: $BACKUP_FILE${NC}"
        ;;
    
    "restore-db")
        if [ -z "$2" ]; then
            echo -e "${RED}Please provide backup file: $0 restore-db backup.sql${NC}"
            exit 1
        fi
        echo -e "${BLUE}Restoring database from: $2${NC}"
        docker-compose exec -T db mysql -u sman1_user -psman1_password_2024 sman1_baleendah < "$2"
        echo -e "${GREEN}Database restored successfully!${NC}"
        ;;
    
    "clean")
        echo -e "${YELLOW}Cleaning up unused Docker resources...${NC}"
        docker system prune -f
        docker volume prune -f
        echo -e "${GREEN}Cleanup completed!${NC}"
        ;;
    
    *)
        print_usage
        exit 1
        ;;
esac
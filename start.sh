#!/bin/bash
# Replace default Apache ports with Render's dynamic $PORT variable
sed -i "s/80/${PORT:-8080}/g" /etc/apache2/sites-available/000-default.conf
sed -i "s/80/${PORT:-8080}/g" /etc/apache2/ports.conf

# Start Apache in the foreground
apache2-foreground

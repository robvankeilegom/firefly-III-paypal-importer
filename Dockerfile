FROM robvankeilegom/laravel-fpm:8.5

# UID comes from Ansible
RUN groupadd -g 1000 hostuser \
    && usermod -aG hostuser www-data

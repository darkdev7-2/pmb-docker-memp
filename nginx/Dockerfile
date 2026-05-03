FROM nginx:stable-alpine

LABEL maintainer="PMB Deployment"
LABEL description="Nginx reverse proxy pour PMB"

# Supprimer la config par défaut
RUN rm -f /etc/nginx/conf.d/default.conf

EXPOSE 80 443

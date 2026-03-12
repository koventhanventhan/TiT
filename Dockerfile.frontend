# Build stage
FROM node:18-alpine as build-stage
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Production stage
FROM nginx:stable-alpine as production-stage

# Copy built files
COPY --from=build-stage /app/dist /usr/share/nginx/html

# Point: We use a template for Nginx to handle Railway's dynamic port
# The official nginx image automatically processes templates in /etc/nginx/templates/
COPY docker/nginx.frontend.conf.template /etc/nginx/templates/default.conf.template

# No explicit EXPOSE 80 - Railway handles dynamic port assignment
# No custom CMD needed - the official entrypoint handles envsubst and starting nginx

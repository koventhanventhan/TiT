# Build stage
FROM node:18-alpine as build-stage
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Production stage
FROM nginx:stable-alpine as production-stage
COPY --from=build-stage /app/dist /usr/share/nginx/html
COPY docker/nginx.frontend.conf.template /etc/nginx/templates/default.conf.template
ENV PORT=80
CMD ["nginx", "-g", "daemon off;"]

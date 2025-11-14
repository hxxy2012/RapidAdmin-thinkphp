# EnterprisePlus 部署文档

## 📖 文档说明

本文档提供EnterprisePlus企业级SaaS平台的完整部署指南，包括环境准备、安装配置、性能优化、安全加固等内容。

**适用版本**: v1.0.0
**最后更新**: 2024-11-14

---

## 📋 目录

- [1. 环境要求](#1-环境要求)
- [2. 快速部署](#2-快速部署)
- [3. 详细部署步骤](#3-详细部署步骤)
- [4. 多租户配置](#4-多租户配置)
- [5. Web服务器配置](#5-web服务器配置)
- [6. 性能优化](#6-性能优化)
- [7. 安全加固](#7-安全加固)
- [8. 监控与日志](#8-监控与日志)
- [9. 备份与恢复](#9-备份与恢复)
- [10. 常见问题](#10-常见问题)
- [11. 故障排查](#11-故障排查)

---

## 1. 环境要求

### 1.1 硬件要求

| 环境类型 | CPU | 内存 | 硬盘 | 带宽 |
|---------|-----|------|------|------|
| 开发环境 | 2核 | 4GB | 50GB | 10Mbps |
| 测试环境 | 4核 | 8GB | 100GB | 50Mbps |
| 生产环境（小型） | 8核 | 16GB | 500GB SSD | 100Mbps |
| 生产环境（中型） | 16核 | 32GB | 1TB SSD | 1Gbps |
| 生产环境（大型） | 32核+ | 64GB+ | 2TB+ SSD | 10Gbps |

### 1.2 软件要求

#### 必需组件

```bash
操作系统:
- CentOS 7.x / 8.x
- Ubuntu 18.04+ / 20.04+
- Debian 10+

运行环境:
- PHP >= 8.0 (推荐 8.1+)
- MySQL >= 8.0 (必须)
- Redis >= 7.0
- Nginx >= 1.18 或 Apache >= 2.4
- Composer >= 2.0

PHP扩展 (必需):
- pdo_mysql
- redis
- mbstring
- openssl
- json
- xml
- curl
- gd
- zip
- bcmath
- fileinfo
```

#### 可选组件

```bash
- PostgreSQL >= 12 (可选数据库)
- RabbitMQ >= 3.8 (高级队列)
- Elasticsearch >= 8.0 (全文搜索)
- Supervisor (进程守护)
- Node.js >= 16 (前端构建)
```

### 1.3 PHP配置要求

编辑 `php.ini`:

```ini
# 基础配置
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
max_input_time = 300

# OPcache (生产环境必须)
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0
opcache.save_comments = 1
opcache.fast_shutdown = 1

# 会话配置
session.save_handler = redis
session.save_path = "tcp://127.0.0.1:6379?database=1"

# 时区
date.timezone = Asia/Shanghai
```

---

## 2. 快速部署

### 2.1 一键安装脚本 (CentOS 7/8)

```bash
#!/bin/bash
# EnterprisePlus一键部署脚本

# 设置变量
APP_DIR="/www/wwwroot/enterpriseplus"
DB_NAME="enterpriseplus"
DB_USER="root"
DB_PASS="your_password"

# 1. 安装PHP 8.1
yum install -y epel-release
yum install -y https://rpms.remirepo.net/enterprise/remi-release-7.rpm
yum install -y yum-utils
yum-config-manager --enable remi-php81
yum install -y php php-cli php-fpm php-mysql php-redis php-mbstring \
    php-xml php-gd php-zip php-bcmath php-json php-opcache

# 2. 安装MySQL 8.0
wget https://dev.mysql.com/get/mysql80-community-release-el7-3.noarch.rpm
rpm -ivh mysql80-community-release-el7-3.noarch.rpm
yum install -y mysql-server
systemctl start mysqld
systemctl enable mysqld

# 3. 安装Redis 7.0
yum install -y redis
systemctl start redis
systemctl enable redis

# 4. 安装Nginx
yum install -y nginx
systemctl start nginx
systemctl enable nginx

# 5. 安装Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

# 6. 克隆项目
mkdir -p $APP_DIR
cd $APP_DIR
git clone <your-repo-url> .

# 7. 安装依赖
composer install --no-dev --optimize-autoloader

# 8. 配置环境变量
cp .env.example .env
sed -i "s/DATABASE=.*/DATABASE=$DB_NAME/" .env
sed -i "s/USERNAME=.*/USERNAME=$DB_USER/" .env
sed -i "s/PASSWORD=.*/PASSWORD=$DB_PASS/" .env

# 9. 创建数据库
MYSQL_PWD=$(grep 'temporary password' /var/log/mysqld.log | awk '{print $NF}')
mysql -u root -p$MYSQL_PWD --connect-expired-password << EOF
ALTER USER 'root'@'localhost' IDENTIFIED BY '$DB_PASS';
CREATE DATABASE $DB_NAME DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
FLUSH PRIVILEGES;
EOF

# 10. 导入数据库
mysql -u $DB_USER -p$DB_PASS $DB_NAME < database/seeds/install_complete.sql

# 11. 设置权限
chown -R www:www $APP_DIR
chmod -R 755 $APP_DIR
chmod -R 777 $APP_DIR/runtime

echo "✅ 部署完成！"
echo "访问地址: http://your-domain.com"
echo "演示租户: demo"
echo "管理员账号: admin"
echo "管理员密码: password (请立即修改)"
```

### 2.2 Ubuntu/Debian 一键安装

```bash
#!/bin/bash
# Ubuntu/Debian 部署脚本

# 更新系统
apt-get update
apt-get upgrade -y

# 安装PHP 8.1
apt-get install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt-get update
apt-get install -y php8.1 php8.1-fpm php8.1-mysql php8.1-redis \
    php8.1-mbstring php8.1-xml php8.1-gd php8.1-zip php8.1-bcmath \
    php8.1-opcache php8.1-curl

# 安装MySQL 8.0
apt-get install -y mysql-server

# 安装Redis
apt-get install -y redis-server

# 安装Nginx
apt-get install -y nginx

# 安装Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# 后续步骤同上...
```

---

## 3. 详细部署步骤

### 3.1 准备服务器

#### 创建部署用户

```bash
# 创建专用用户
useradd -m -s /bin/bash enterpriseplus
usermod -aG sudo enterpriseplus

# 切换到部署用户
su - enterpriseplus
```

#### 配置SSH密钥 (可选)

```bash
ssh-keygen -t rsa -b 4096 -C "deploy@enterpriseplus.com"
# 将公钥添加到 ~/.ssh/authorized_keys
```

### 3.2 安装PHP 8.1

#### CentOS/RHEL

```bash
# 添加Remi仓库
yum install -y epel-release
yum install -y https://rpms.remirepo.net/enterprise/remi-release-$(rpm -E %rhel).noarch.rpm

# 启用PHP 8.1
yum install -y yum-utils
yum-config-manager --enable remi-php81

# 安装PHP及扩展
yum install -y php php-cli php-fpm php-mysql php-redis php-mbstring \
    php-xml php-gd php-zip php-bcmath php-json php-opcache php-curl \
    php-fileinfo php-pdo php-dom php-simplexml

# 验证安装
php -v
php -m | grep -E 'redis|mysql|opcache'
```

#### Ubuntu/Debian

```bash
# 添加PPA
apt-get install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt-get update

# 安装PHP及扩展
apt-get install -y php8.1 php8.1-fpm php8.1-mysql php8.1-redis \
    php8.1-mbstring php8.1-xml php8.1-gd php8.1-zip php8.1-bcmath \
    php8.1-opcache php8.1-curl php8.1-fileinfo

# 验证安装
php -v
php -m
```

### 3.3 安装MySQL 8.0

#### CentOS/RHEL

```bash
# 下载MySQL仓库
wget https://dev.mysql.com/get/mysql80-community-release-el$(rpm -E %rhel)-3.noarch.rpm
rpm -ivh mysql80-community-release-el$(rpm -E %rhel)-3.noarch.rpm

# 安装MySQL
yum install -y mysql-server

# 启动MySQL
systemctl start mysqld
systemctl enable mysqld

# 获取临时密码
grep 'temporary password' /var/log/mysqld.log

# 安全配置
mysql_secure_installation
```

#### Ubuntu/Debian

```bash
# 安装MySQL
apt-get install -y mysql-server

# 启动MySQL
systemctl start mysql
systemctl enable mysql

# 安全配置
mysql_secure_installation
```

#### 创建数据库和用户

```sql
-- 登录MySQL
mysql -u root -p

-- 创建主库（平台管理库）
CREATE DATABASE enterpriseplus DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 创建数据库用户
CREATE USER 'enterpriseplus'@'localhost' IDENTIFIED BY 'Strong_Password_123!';
GRANT ALL PRIVILEGES ON enterpriseplus.* TO 'enterpriseplus'@'localhost';
GRANT CREATE ON *.* TO 'enterpriseplus'@'localhost';  -- 允许创建租户数据库

-- 刷新权限
FLUSH PRIVILEGES;

-- 验证
SHOW DATABASES;
SELECT user, host FROM mysql.user WHERE user='enterpriseplus';
```

### 3.4 安装Redis 7.0

#### CentOS/RHEL

```bash
# 安装Redis
yum install -y redis

# 配置Redis
vi /etc/redis.conf

# 修改以下配置
maxmemory 2gb
maxmemory-policy allkeys-lru
requirepass your_redis_password

# 启动Redis
systemctl start redis
systemctl enable redis

# 测试连接
redis-cli -a your_redis_password ping
```

#### Ubuntu/Debian

```bash
# 安装Redis
apt-get install -y redis-server

# 配置Redis
vi /etc/redis/redis.conf

# 启动Redis
systemctl start redis-server
systemctl enable redis-server
```

### 3.5 克隆项目代码

```bash
# 创建项目目录
mkdir -p /www/wwwroot/enterpriseplus
cd /www/wwwroot/enterpriseplus

# 克隆代码
git clone https://github.com/your-org/RapidAdmin-thinkphp.git .

# 或者从压缩包解压
# tar -xzf enterpriseplus-v1.0.0.tar.gz

# 验证文件
ls -la
```

### 3.6 安装Composer依赖

```bash
# 安装Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# 验证
composer --version

# 进入项目目录
cd /www/wwwroot/enterpriseplus

# 安装依赖（生产环境）
composer install --no-dev --optimize-autoloader

# 开发环境使用
# composer install

# 验证安装
composer show -i
```

### 3.7 配置环境变量

```bash
# 复制环境变量模板
cp .env.example .env

# 编辑环境变量
vi .env
```

**重要配置项**:

```ini
# 应用配置
APP_DEBUG = false
APP_TRACE = false

# 数据库配置
[DATABASE]
TYPE = mysql
HOSTNAME = 127.0.0.1
DATABASE = enterpriseplus
USERNAME = enterpriseplus
PASSWORD = Strong_Password_123!
HOSTPORT = 3306
CHARSET = utf8mb4
PREFIX = ea_

# Redis配置
[REDIS]
HOSTNAME = 127.0.0.1
PORT = 6379
PASSWORD = your_redis_password
SELECT = 0

# 会话配置
[SESSION]
TYPE = redis
PREFIX = session:
EXPIRE = 7200

# 多租户配置
[TENANT]
USE_SEPARATE_DATABASE = true
DATABASE_PREFIX = tenant_
CACHE_ENABLED = true
CACHE_EXPIRE = 3600

# 安全配置
[SECURITY]
SALT = your_random_salt_string_here
JWT_SECRET = your_jwt_secret_key_here
```

### 3.8 导入数据库

```bash
# 导入主库数据
mysql -u enterpriseplus -p enterpriseplus < database/seeds/install_complete.sql

# 验证导入
mysql -u enterpriseplus -p -e "USE enterpriseplus; SHOW TABLES;"

# 检查表数量（应该有60+张表）
mysql -u enterpriseplus -p -e "USE enterpriseplus; SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='enterpriseplus';"
```

### 3.9 设置文件权限

```bash
# 设置所有者
chown -R www:www /www/wwwroot/enterpriseplus

# 设置基本权限
chmod -R 755 /www/wwwroot/enterpriseplus

# 设置运行时目录权限
chmod -R 777 /www/wwwroot/enterpriseplus/runtime
chmod -R 777 /www/wwwroot/enterpriseplus/public/uploads

# 设置敏感文件权限
chmod 600 /www/wwwroot/enterpriseplus/.env
chmod 600 /www/wwwroot/enterpriseplus/config/*.php

# 验证权限
ls -la /www/wwwroot/enterpriseplus
```

---

## 4. 多租户配置

### 4.1 租户数据库模式

EnterprisePlus支持三种租户数据隔离模式：

#### 模式1: 独立数据库（推荐）⭐

**优点**:
- ✅ 数据完全隔离，安全性最高
- ✅ 性能好，每个租户独立优化
- ✅ 可独立备份恢复
- ✅ 支持租户数据迁移

**配置**:

```ini
# .env
USE_SEPARATE_DATABASE = true
DATABASE_PREFIX = tenant_
```

**数据库权限**:

```sql
-- 授予创建数据库权限
GRANT CREATE ON *.* TO 'enterpriseplus'@'localhost';

-- 授予所有租户数据库权限
GRANT ALL PRIVILEGES ON `tenant_%`.* TO 'enterpriseplus'@'localhost';

FLUSH PRIVILEGES;
```

#### 模式2: 共享数据库-独立Schema (PostgreSQL)

适用于使用PostgreSQL的场景。

```ini
# .env
DATABASE_TYPE = pgsql
USE_SEPARATE_DATABASE = false
USE_SEPARATE_SCHEMA = true
```

#### 模式3: 共享数据库-共享表 (不推荐)

仅适用于小型应用或开发环境。

```ini
# .env
USE_SEPARATE_DATABASE = false
USE_SEPARATE_SCHEMA = false
```

### 4.2 租户识别配置

编辑 `config/tenant.php`:

```php
'resolve_priority' => [
    'header',      // HTTP Header: X-Tenant-Code
    'subdomain',   // 子域名: demo.yourdomain.com
    'token',       // JWT Token
    'session',     // Session
    'param',       // URL参数 (仅开发环境)
],
```

### 4.3 子域名配置

#### 泛域名解析

在DNS管理面板添加泛域名记录：

```
类型: A
主机记录: *
记录值: your_server_ip
```

#### Nginx配置

```nginx
server {
    listen 80;
    server_name *.yourdomain.com;

    root /www/wwwroot/enterpriseplus/public;
    index index.php index.html;

    # 租户识别
    set $tenant "";
    if ($host ~* "^(.+)\.yourdomain\.com$") {
        set $tenant $1;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTP_X_TENANT_CODE $tenant;  # 传递租户编码
        include fastcgi_params;
    }
}
```

### 4.4 创建演示租户

```sql
-- 进入主库
USE enterpriseplus;

-- 创建演示租户
INSERT INTO ea_tenant (
    tenant_code, tenant_name, industry, scale, package_id,
    expire_time, status, db_name, contact_name, contact_phone
) VALUES (
    'demo', '演示租户', '互联网', 2, 2,
    DATE_ADD(NOW(), INTERVAL 1 YEAR), 1, 'tenant_demo',
    '张三', '13800138000'
);

-- 获取租户ID
SET @tenant_id = LAST_INSERT_ID();

-- 创建租户数据库
CREATE DATABASE IF NOT EXISTS tenant_demo DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 复制表结构到租户库（需要自定义脚本）
-- 或者手动导入

-- 创建管理员用户
INSERT INTO ea_user (
    tenant_id, username, real_name, password, status, user_type
) VALUES (
    @tenant_id, 'admin', '管理员',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password
    1, 1
);

-- 更新租户管理员ID
UPDATE ea_tenant SET admin_user_id = LAST_INSERT_ID() WHERE id = @tenant_id;
```

---

## 5. Web服务器配置

### 5.1 Nginx配置（推荐）⭐

创建配置文件: `/etc/nginx/conf.d/enterpriseplus.conf`

```nginx
# 主站点配置
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;

    # HTTPS重定向（生产环境推荐）
    # return 301 https://$server_name$request_uri;

    root /www/wwwroot/enterpriseplus/public;
    index index.php index.html;

    # 访问日志
    access_log /var/log/nginx/enterpriseplus_access.log;
    error_log /var/log/nginx/enterpriseplus_error.log;

    # 隐藏Nginx版本号
    server_tokens off;

    # 文件上传大小限制
    client_max_body_size 50m;

    # 禁止访问隐藏文件
    location ~ /\. {
        deny all;
    }

    # 静态文件缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # PHP处理
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;
        # 或者 fastcgi_pass 127.0.0.1:9000;

        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        # PHP超时设置
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }

    # 禁止访问敏感文件
    location ~* (\.env|\.git|\.svn|composer\.json|composer\.lock|\.htaccess)$ {
        deny all;
    }
}

# 泛域名配置（租户子域名）
server {
    listen 80;
    server_name *.yourdomain.com;

    root /www/wwwroot/enterpriseplus/public;
    index index.php index.html;

    access_log /var/log/nginx/tenant_access.log;
    error_log /var/log/nginx/tenant_error.log;

    # 提取租户编码
    set $tenant "";
    if ($host ~* "^(.+)\.yourdomain\.com$") {
        set $tenant $1;
    }

    # 过滤特殊子域名
    if ($tenant ~* "^(www|api|admin|static|cdn)$") {
        return 404;
    }

    client_max_body_size 50m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTP_X_TENANT_CODE $tenant;  # 传递租户编码
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }
}
```

**重启Nginx**:

```bash
# 测试配置
nginx -t

# 重启
systemctl restart nginx
```

### 5.2 Apache配置

创建虚拟主机: `/etc/httpd/conf.d/enterpriseplus.conf`

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /www/wwwroot/enterpriseplus/public

    <Directory /www/wwwroot/enterpriseplus/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # URL重写
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]
    </Directory>

    # 日志
    ErrorLog /var/log/httpd/enterpriseplus_error.log
    CustomLog /var/log/httpd/enterpriseplus_access.log combined
</VirtualHost>

# 泛域名配置
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias *.yourdomain.com
    DocumentRoot /www/wwwroot/enterpriseplus/public

    # 提取租户编码
    SetEnvIf Host "^(.+)\.yourdomain\.com$" TENANT_CODE=$1
    RequestHeader set X-Tenant-Code %{TENANT_CODE}e

    <Directory /www/wwwroot/enterpriseplus/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**重启Apache**:

```bash
# 测试配置
apachectl configtest

# 重启
systemctl restart httpd
```

### 5.3 HTTPS配置（推荐）⭐

#### 安装Let's Encrypt证书

```bash
# 安装Certbot
yum install -y certbot python3-certbot-nginx

# 或者 Ubuntu/Debian
apt-get install -y certbot python3-certbot-nginx

# 获取证书
certbot --nginx -d yourdomain.com -d www.yourdomain.com

# 自动续期
certbot renew --dry-run

# 添加到crontab
echo "0 3 * * * certbot renew --quiet" | crontab -
```

#### Nginx HTTPS配置

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # SSL优化
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256';
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # HSTS
    add_header Strict-Transport-Security "max-age=31536000" always;

    # 其他配置...
}

# HTTP重定向到HTTPS
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

---

## 6. 性能优化

### 6.1 PHP-FPM优化

编辑 `/etc/php-fpm.d/www.conf`:

```ini
[www]
# 进程管理器
pm = dynamic

# 最大子进程数
pm.max_children = 50

# 启动时进程数
pm.start_servers = 10

# 最小空闲进程数
pm.min_spare_servers = 5

# 最大空闲进程数
pm.max_spare_servers = 15

# 最大请求数（防止内存泄漏）
pm.max_requests = 500

# 慢日志
slowlog = /var/log/php-fpm/slow.log
request_slowlog_timeout = 5s

# 状态页面
pm.status_path = /status
```

**计算公式**:

```
max_children = 总内存 / 单个PHP进程内存
例如: 8GB / 50MB = 160个进程
```

**重启PHP-FPM**:

```bash
systemctl restart php-fpm
```

### 6.2 MySQL优化

编辑 `/etc/my.cnf`:

```ini
[mysqld]
# 基本配置
max_connections = 500
max_connect_errors = 100

# InnoDB配置
innodb_buffer_pool_size = 4G  # 设置为总内存的50-70%
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# 查询缓存（MySQL 5.7及以下）
# query_cache_size = 64M
# query_cache_type = 1

# 慢查询日志
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

# 二进制日志
log_bin = /var/log/mysql/mysql-bin.log
expire_logs_days = 7
max_binlog_size = 100M

# 字符集
character_set_server = utf8mb4
collation_server = utf8mb4_unicode_ci
```

**重启MySQL**:

```bash
systemctl restart mysqld
```

### 6.3 Redis优化

编辑 `/etc/redis.conf`:

```ini
# 内存配置
maxmemory 2gb
maxmemory-policy allkeys-lru

# 持久化（根据需求选择）
# RDB: 适合备份
save 900 1
save 300 10
save 60 10000

# AOF: 适合数据不丢失
# appendonly yes
# appendfsync everysec

# 慢日志
slowlog-log-slower-than 10000
slowlog-max-len 128

# 安全
requirepass your_redis_password

# 网络
bind 127.0.0.1
protected-mode yes
```

**重启Redis**:

```bash
systemctl restart redis
```

### 6.4 缓存策略

#### 启用OPcache

已在`php.ini`中配置，验证:

```bash
php -i | grep opcache
```

#### 应用层缓存

编辑 `config/cache.php`:

```php
return [
    'default' => 'redis',

    'stores' => [
        'redis' => [
            'type' => 'redis',
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => 'your_redis_password',
            'select' => 2,
            'timeout' => 0,
            'expire' => 3600,
            'persistent' => false,
            'prefix' => 'cache:',
        ],

        'file' => [
            'type' => 'file',
            'path' => runtime_path() . 'cache/',
            'expire' => 0,
        ],
    ],
];
```

#### 租户缓存优化

```ini
# config/tenant.php
'cache' => [
    'enabled' => true,
    'expire' => 3600,      # 1小时
    'prefix' => 'tenant:',
],
```

### 6.5 CDN配置

#### 静态资源分离

```nginx
# Nginx配置
location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
    # CDN域名
    # add_header Access-Control-Allow-Origin "*";

    expires 30d;
    add_header Cache-Control "public, immutable";
}
```

#### 使用CDN服务

支持的CDN服务:
- 阿里云CDN
- 腾讯云CDN
- 七牛云CDN
- 又拍云CDN

配置文件 `config/cdn.php`:

```php
return [
    'enable' => true,
    'domain' => 'https://cdn.yourdomain.com',
    'type' => 'aliyun',  // aliyun/tencent/qiniu/upyun
];
```

---

## 7. 安全加固

### 7.1 服务器安全

#### 防火墙配置

```bash
# CentOS 7+ (firewalld)
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --permanent --add-service=ssh
firewall-cmd --reload

# 或者使用iptables
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT
iptables -A INPUT -p tcp --dport 22 -j ACCEPT
iptables -A INPUT -j DROP
```

#### SSH安全

编辑 `/etc/ssh/sshd_config`:

```ini
# 禁止root登录
PermitRootLogin no

# 禁用密码登录（仅密钥）
PasswordAuthentication no

# 修改默认端口
Port 2222

# 限制登录用户
AllowUsers enterpriseplus
```

重启SSH:

```bash
systemctl restart sshd
```

#### Fail2Ban (防暴力破解)

```bash
# 安装
yum install -y fail2ban

# 配置
vi /etc/fail2ban/jail.local
```

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true

[nginx-http-auth]
enabled = true
```

### 7.2 应用安全

#### SQL注入防护

✅ ThinkPHP框架已内置PDO预处理，防止SQL注入

#### XSS防护

在模板中使用:

```php
// 自动转义
{$data.content|raw}  // 不转义（谨慎使用）
{$data.content}      // 自动转义
```

#### CSRF防护

启用CSRF令牌:

```php
// config/middleware.php
return [
    // ...
    \think\middleware\CheckRequestCache::class,
    \think\middleware\LoadLangPack::class,
    \think\middleware\SessionInit::class,
];
```

#### 文件上传安全

配置 `config/upload.php`:

```php
return [
    'upload_max_size' => 10 * 1024 * 1024,  // 10MB
    'allowed_ext' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip'],
    'disallowed_ext' => ['php', 'php3', 'php4', 'php5', 'phtml', 'pht', 'jsp', 'asp', 'sh', 'cgi'],

    // 检查MIME类型
    'check_mime' => true,

    // 重命名上传文件
    'rename_file' => true,
];
```

#### 敏感信息保护

```bash
# 确保.env文件不可访问
chmod 600 .env

# Nginx配置
location ~ /\.(env|git) {
    deny all;
}
```

### 7.3 数据库安全

```sql
-- 删除匿名用户
DELETE FROM mysql.user WHERE User='';

-- 删除test数据库
DROP DATABASE IF EXISTS test;

-- 禁止远程root登录
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

-- 创建专用用户（不使用root）
CREATE USER 'enterpriseplus'@'localhost' IDENTIFIED BY 'Strong_Password_123!';
GRANT ALL PRIVILEGES ON enterpriseplus.* TO 'enterpriseplus'@'localhost';
GRANT CREATE ON *.* TO 'enterpriseplus'@'localhost';

FLUSH PRIVILEGES;
```

### 7.4 安全检查清单

- [ ] 所有密码使用强密码
- [ ] 禁用root用户直接登录
- [ ] 配置防火墙规则
- [ ] 启用HTTPS
- [ ] 定期更新系统和软件包
- [ ] 配置备份策略
- [ ] 监控异常登录
- [ ] 定期审计日志
- [ ] 限制文件上传类型
- [ ] 配置错误日志（不暴露敏感信息）

---

## 8. 监控与日志

### 8.1 应用日志

#### 日志配置

编辑 `config/log.php`:

```php
return [
    'default' => 'file',
    'channels' => [
        'file' => [
            'type' => 'File',
            'path' => runtime_path() . 'log/',
            'level' => ['error', 'warning', 'info'],
            'file_size' => 10 * 1024 * 1024,  // 10MB
            'max_files' => 30,
            'json' => false,
        ],
    ],
];
```

#### 日志类型

```
runtime/log/
├── error/          # 错误日志
├── sql/            # SQL日志
├── tenant/         # 租户相关日志
├── access/         # 访问日志
└── cron/           # 定时任务日志
```

#### 日志轮转

创建 `/etc/logrotate.d/enterpriseplus`:

```
/www/wwwroot/enterpriseplus/runtime/log/*.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    create 0644 www www
    sharedscripts
    postrotate
        systemctl reload php-fpm
    endscript
}
```

### 8.2 系统监控

#### 安装Supervisor (进程守护)

```bash
# 安装
yum install -y supervisor
# 或
apt-get install -y supervisor

# 配置队列守护进程
vi /etc/supervisor/conf.d/enterpriseplus-queue.conf
```

```ini
[program:enterpriseplus-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /www/wwwroot/enterpriseplus/think queue:listen --queue=default
autostart=true
autorestart=true
user=www
numprocs=4
redirect_stderr=true
stdout_logfile=/www/wwwroot/enterpriseplus/runtime/log/queue.log
stopwaitsecs=3600
```

```bash
# 启动
supervisorctl reread
supervisorctl update
supervisorctl start enterpriseplus-queue:*
```

#### 定时任务守护

```bash
# 编辑crontab
crontab -e

# 添加定时任务
* * * * * cd /www/wwwroot/enterpriseplus && php think task:schedule >> /dev/null 2>&1
```

### 8.3 性能监控

#### 安装htop

```bash
yum install -y htop
htop
```

#### MySQL慢查询分析

```bash
# 安装pt-query-digest
yum install -y percona-toolkit

# 分析慢查询日志
pt-query-digest /var/log/mysql/slow.log > slow_report.txt
```

#### Redis监控

```bash
# 实时监控
redis-cli -a your_password monitor

# 统计信息
redis-cli -a your_password info

# 慢日志
redis-cli -a your_password slowlog get 10
```

---

## 9. 备份与恢复

### 9.1 数据库备份

#### 自动备份脚本

创建 `/usr/local/bin/backup_enterpriseplus.sh`:

```bash
#!/bin/bash

# 配置
BACKUP_DIR="/backup/enterpriseplus"
MYSQL_USER="enterpriseplus"
MYSQL_PASS="Strong_Password_123!"
DATE=$(date +%Y%m%d_%H%M%S)

# 创建备份目录
mkdir -p $BACKUP_DIR

# 备份主库
mysqldump -u$MYSQL_USER -p$MYSQL_PASS \
    --single-transaction \
    --quick \
    --lock-tables=false \
    enterpriseplus > $BACKUP_DIR/enterpriseplus_$DATE.sql

# 备份所有租户数据库
for db in $(mysql -u$MYSQL_USER -p$MYSQL_PASS -e "SHOW DATABASES LIKE 'tenant_%'" -s --skip-column-names); do
    mysqldump -u$MYSQL_USER -p$MYSQL_PASS \
        --single-transaction \
        --quick \
        --lock-tables=false \
        $db > $BACKUP_DIR/${db}_$DATE.sql
done

# 压缩备份文件
cd $BACKUP_DIR
tar -czf backup_$DATE.tar.gz *.sql
rm -f *.sql

# 删除30天前的备份
find $BACKUP_DIR -name "backup_*.tar.gz" -mtime +30 -delete

# 上传到OSS（可选）
# aliyun oss cp backup_$DATE.tar.gz oss://your-bucket/backup/
```

```bash
# 设置权限
chmod +x /usr/local/bin/backup_enterpriseplus.sh

# 添加到crontab（每天凌晨3点执行）
0 3 * * * /usr/local/bin/backup_enterpriseplus.sh
```

### 9.2 文件备份

```bash
#!/bin/bash
# 备份应用文件

BACKUP_DIR="/backup/enterpriseplus"
APP_DIR="/www/wwwroot/enterpriseplus"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# 备份上传文件
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz $APP_DIR/public/uploads

# 备份配置文件
tar -czf $BACKUP_DIR/config_$DATE.tar.gz $APP_DIR/.env $APP_DIR/config

# 删除7天前的备份
find $BACKUP_DIR -name "uploads_*.tar.gz" -mtime +7 -delete
find $BACKUP_DIR -name "config_*.tar.gz" -mtime +7 -delete
```

### 9.3 恢复流程

#### 数据库恢复

```bash
# 恢复主库
mysql -u enterpriseplus -p enterpriseplus < /backup/enterpriseplus/enterpriseplus_20241114.sql

# 恢复租户数据库
mysql -u enterpriseplus -p tenant_demo < /backup/enterpriseplus/tenant_demo_20241114.sql
```

#### 文件恢复

```bash
# 恢复上传文件
tar -xzf /backup/enterpriseplus/uploads_20241114.tar.gz -C /

# 恢复配置文件
tar -xzf /backup/enterpriseplus/config_20241114.tar.gz -C /
```

---

## 10. 常见问题

### 10.1 安装问题

#### Q: composer install 失败

**问题**: `Your requirements could not be resolved to an installable set of packages`

**解决**:

```bash
# 清除缓存
composer clear-cache

# 更新composer
composer self-update

# 允许插件
composer config --global allow-plugins true

# 重新安装
composer install --no-dev --optimize-autoloader
```

#### Q: PHP扩展缺失

**问题**: `PHP extension xxx is missing`

**解决**:

```bash
# 查看已安装扩展
php -m

# 安装缺失扩展（以redis为例）
yum install -y php-redis
# 或
apt-get install -y php-redis

# 重启PHP-FPM
systemctl restart php-fpm
```

### 10.2 运行问题

#### Q: 500错误 - Internal Server Error

**排查步骤**:

```bash
# 1. 查看PHP错误日志
tail -f /var/log/php-fpm/error.log

# 2. 查看应用日志
tail -f /www/wwwroot/enterpriseplus/runtime/log/error.log

# 3. 检查文件权限
ls -la /www/wwwroot/enterpriseplus/runtime

# 4. 检查.env配置
cat /www/wwwroot/enterpriseplus/.env

# 5. 启用调试模式（仅开发环境）
vi .env
# 设置 APP_DEBUG = true
```

#### Q: 数据库连接失败

**错误**: `SQLSTATE[HY000] [2002] Connection refused`

**解决**:

```bash
# 检查MySQL是否运行
systemctl status mysqld

# 检查端口
netstat -tulpn | grep 3306

# 测试连接
mysql -u enterpriseplus -p -h 127.0.0.1

# 检查.env配置
grep DATABASE .env
```

#### Q: Redis连接失败

**错误**: `Connection refused`

**解决**:

```bash
# 检查Redis是否运行
systemctl status redis

# 测试连接
redis-cli -a your_password ping

# 检查配置
grep bind /etc/redis.conf
grep requirepass /etc/redis.conf
```

### 10.3 性能问题

#### Q: 页面响应慢

**优化步骤**:

```bash
# 1. 启用OPcache
php -i | grep opcache

# 2. 启用应用缓存
# 编辑 config/cache.php

# 3. 启用租户缓存
# 编辑 config/tenant.php

# 4. 优化数据库查询
# 查看慢查询日志
tail -f /var/log/mysql/slow.log

# 5. 增加PHP-FPM进程数
vi /etc/php-fpm.d/www.conf
# 调整 pm.max_children
```

---

## 11. 故障排查

### 11.1 日志位置

```
系统日志:
- /var/log/messages
- /var/log/syslog

Nginx日志:
- /var/log/nginx/access.log
- /var/log/nginx/error.log

PHP-FPM日志:
- /var/log/php-fpm/error.log
- /var/log/php-fpm/slow.log

MySQL日志:
- /var/log/mysql/error.log
- /var/log/mysql/slow.log

应用日志:
- /www/wwwroot/enterpriseplus/runtime/log/
```

### 11.2 调试模式

**仅在开发环境启用**:

```ini
# .env
APP_DEBUG = true
APP_TRACE = true
```

访问任意页面查看调试信息。

### 11.3 健康检查

创建健康检查脚本:

```bash
#!/bin/bash

echo "=== EnterprisePlus 健康检查 ==="

# 检查PHP-FPM
echo -n "PHP-FPM: "
systemctl is-active php-fpm

# 检查MySQL
echo -n "MySQL: "
systemctl is-active mysqld

# 检查Redis
echo -n "Redis: "
systemctl is-active redis

# 检查Nginx
echo -n "Nginx: "
systemctl is-active nginx

# 检查磁盘空间
echo -n "磁盘空间: "
df -h / | awk 'NR==2 {print $5}'

# 检查内存使用
echo -n "内存使用: "
free -m | awk 'NR==2 {printf "%.1f%%\n", $3*100/$2}'

# 检查数据库连接
echo -n "数据库连接: "
mysql -u enterpriseplus -p'Strong_Password_123!' -e "SELECT 1" > /dev/null 2>&1 && echo "OK" || echo "FAIL"
```

---

## 📞 技术支持

### 获取帮助

- **文档**: https://docs.enterpriseplus.com
- **GitHub Issues**: https://github.com/your-org/RapidAdmin-thinkphp/issues
- **Email**: support@enterpriseplus.com
- **QQ群**: 123456789

### 紧急联系

- **7x24技术支持**: +86 138-0013-8000
- **企业版专属顾问**: vip@enterpriseplus.com

---

## 📄 附录

### A. 端口列表

| 服务 | 默认端口 | 说明 |
|------|---------|------|
| HTTP | 80 | Web服务 |
| HTTPS | 443 | 加密Web服务 |
| MySQL | 3306 | 数据库 |
| Redis | 6379 | 缓存 |
| PHP-FPM | 9000 | PHP处理 |
| SSH | 22 | 远程管理 |
| Supervisor | 9001 | 进程管理 |

### B. 文件权限

| 路径 | 权限 | 所有者 |
|------|------|--------|
| /www/wwwroot/enterpriseplus | 755 | www:www |
| runtime/ | 777 | www:www |
| public/uploads | 777 | www:www |
| .env | 600 | www:www |
| config/ | 755 | www:www |

### C. 命令速查

```bash
# 重启服务
systemctl restart nginx
systemctl restart php-fpm
systemctl restart mysqld
systemctl restart redis

# 查看日志
tail -f /var/log/nginx/error.log
tail -f /www/wwwroot/enterpriseplus/runtime/log/error.log

# 清除缓存
php think cache:clear

# 队列管理
php think queue:listen
supervisorctl restart enterpriseplus-queue:*

# 数据库备份
/usr/local/bin/backup_enterpriseplus.sh

# 健康检查
curl http://localhost/health
```

---

**部署文档版本**: v1.0.0
**最后更新**: 2024-11-14
**维护者**: EnterprisePlus Team

🎉 **祝您部署顺利！**

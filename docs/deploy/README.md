# AI 短剧制作系统 - 部署文档

## 一、服务器环境准备

### 1.1 系统要求

- **操作系统**: Ubuntu 20.04 LTS / CentOS 7+
- **CPU**: 4 核及以上
- **内存**: 8GB 及以上
- **磁盘**: 100GB SSD 及以上
- **带宽**: 5Mbps 及以上

### 1.2 安装 LNMP 环境

```bash
# Ubuntu 系统
sudo apt update
sudo apt install -y nginx mysql-server php8.0 php8.0-fpm php8.0-mysql php8.0-redis php8.0-gd php8.0-curl php8.0-mbstring php8.0-xml php8.0-zip

# CentOS 系统
sudo yum install -y nginx mysql-server php php-mysql php-redis php-gd php-curl php-mbstring php-xml php-zip
```

### 1.3 安装 Redis

```bash
sudo apt install -y redis-server
# 或
sudo yum install -y redis

# 启动 Redis
sudo systemctl start redis
sudo systemctl enable redis
```

### 1.4 配置 MySQL

```bash
# 登录 MySQL
mysql -u root -p

# 创建数据库和用户
CREATE DATABASE ai_drama DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ai_drama_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON ai_drama.* TO 'ai_drama_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 二、后端部署

### 2.1 上传代码

```bash
# 创建项目目录
sudo mkdir -p /var/www/ai-drama-system
sudo chown -R www-data:www-data /var/www/ai-drama-system

# 上传代码（使用 git 或 scp）
cd /var/www/ai-drama-system
git clone <repository_url> .
```

### 2.2 安装 PHP 依赖

```bash
cd /var/www/ai-drama-system/backend

# 安装 Composer（如果未安装）
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 安装依赖
composer install --no-dev --optimize-autoloader
```

### 2.3 配置环境变量

```bash
cd /var/www/ai-drama-system/backend

# 复制环境配置
cp .env.example .env

# 编辑配置
vim .env
```

**.env 配置示例**：
```ini
APP_DEBUG = false
APP_URL = https://api.yourdomain.com

[DATABASE]
DRIVER = mysql
HOSTNAME = 127.0.0.1
DATABASE = ai_drama
USERNAME = ai_drama_user
PASSWORD = your_secure_password
HOSTPORT = 3306
CHARSET = utf8mb4

[REDIS]
HOST = 127.0.0.1
PORT = 6379
PASSWORD = 

[OSS]
DRIVER = aliyun
ACCESS_KEY = your_access_key
SECRET_KEY = your_secret_key
BUCKET = your_bucket
ENDPOINT = oss-cn-hangzhou.aliyuncs.com

[WECHAT]
APPID = your_wechat_appid
SECRET = your_wechat_secret
MCH_ID = your_mch_id
KEY = your_wechat_pay_key
```

### 2.4 导入数据库

```bash
mysql -u ai_drama_user -p ai_drama < /var/www/ai-drama-system/docs/database/schema.sql
```

### 2.5 设置目录权限

```bash
cd /var/www/ai-drama-system/backend

# 设置运行时目录权限
chmod -R 755 runtime
chmod -R 755 public

# 设置所有者
chown -R www-data:www-data runtime
chown -R www-data:www-data public
```

### 2.6 配置 Nginx

```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    root /var/www/ai-drama-system/backend/public;
    index index.php index.html;

    # SSL 配置（生产环境必须启用）
    # listen 443 ssl http2;
    # ssl_certificate /etc/nginx/ssl/yourdomain.crt;
    # ssl_certificate_key /etc/nginx/ssl/yourdomain.key;

    # 静态资源
    location ~* \.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # ThinkPHP 路由重写
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 禁止访问敏感文件
    location ~ /\. {
        deny all;
    }

    location ~ /(vendor|runtime|config|app)/.*\.php$ {
        deny all;
    }
}
```

### 2.7 启动服务

```bash
# 测试 Nginx 配置
sudo nginx -t

# 重启 Nginx
sudo systemctl restart nginx

# 启动 PHP-FPM
sudo systemctl restart php8.0-fpm
```

---

## 三、前端部署

### 3.1 H5 部署

```bash
cd /var/www/ai-drama-system/frontend

# 安装依赖
npm install

# 生产构建
npm run build:h5

# 上传到服务器（或使用 Nginx 代理）
# 构建产物在 dist/build/h5 目录
```

**Nginx 配置 H5**：
```nginx
server {
    listen 80;
    server_name app.yourdomain.com;
    root /var/www/ai-drama-system/frontend/dist/build/h5;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

### 3.2 微信小程序部署

```bash
# 构建小程序
npm run build:mp-weixin

# 使用微信开发者工具导入 dist/build/mp-weixin 目录
# 上传代码并提交审核
```

### 3.3 APP 部署

```bash
# 构建 APP
npm run build:app

# 使用 HBuilderX 打包 APK/IPA
# 或使用云打包服务
```

---

## 四、AI 服务配置

### 4.1 文心一言（百度）

1. 访问 https://cloud.baidu.com/product/wenxinworkshop
2. 创建应用，获取 API Key 和 Secret Key
3. 在后台管理面板配置到 `ai_provider_config` 表

### 4.2 通义千问（阿里）

1. 访问 https://dashscope.console.aliyun.com/
2. 开通服务，获取 API Key
3. 配置到数据库

### 4.3 讯飞语音

1. 访问 https://www.xfyun.cn/
2. 创建应用，获取 APPID、API Key、Secret Key
3. 配置到数据库

### 4.4 即梦 AI（视频）

1. 访问 https://jimeng.jianying.com/
2. 申请 API 访问权限
3. 配置到数据库

---

## 五、OSS 对象存储配置

### 5.1 阿里云 OSS

```bash
# 安装 OSS SDK
composer require aliyuncs/oss-sdk-php

# 配置 .env 文件
OSS_DRIVER=aliyun
OSS_ACCESS_KEY=your_access_key
OSS_SECRET_KEY=your_secret_key
OSS_BUCKET=your_bucket
OSS_ENDPOINT=oss-cn-hangzhou.aliyuncs.com
```

### 5.2 腾讯云 COS

```bash
# 安装 COS SDK
composer require qcloud/cos-sdk-v5

# 配置 .env 文件
OSS_DRIVER=cos
OSS_SECRET_ID=your_secret_id
OSS_SECRET_KEY=your_secret_key
OSS_BUCKET=your_bucket
OSS_REGION=ap-guangzhou
```

---

## 六、定时任务配置

### 6.1 每日免费点数重置

```bash
# 编辑 crontab
crontab -e

# 添加任务（每天凌晨 0 点重置）
0 0 * * * cd /var/www/ai-drama-system/backend && php think reset:daily_points
```

### 6.2 清理过期数据

```bash
# 每周日凌晨 3 点清理
0 3 * * 0 cd /var/www/ai-drama-system/backend && php think clean:expired_data
```

---

## 七、监控与日志

### 7.1 日志配置

```php
// config/log.php
return [
    'default' => 'file',
    'channels' => [
        'file' => [
            'driver' => 'File',
            'path' => runtime_path() . 'log',
            'level' => ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'],
        ],
    ],
];
```

### 7.2 日志轮转

```bash
# 配置 logrotate
sudo vim /etc/logrotate.d/ai-drama

# 内容：
/var/www/ai-drama-system/backend/runtime/log/*.log {
    daily
    rotate 30
    missingok
    notifempty
    compress
    delaycompress
    copytruncate
}
```

---

## 八、安全加固

### 8.1 HTTPS 配置

```bash
# 使用 Let's Encrypt 免费证书
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d api.yourdomain.com
```

### 8.2 防火墙配置

```bash
# Ubuntu UFW
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 8.3 数据库安全

- 禁止 root 远程登录
- 使用强密码
- 限制数据库访问 IP

### 8.4 API 安全

- 启用 JWT Token 认证
- 接口限流（Redis 计数）
- SQL 注入防护（ThinkPHP 内置）
- XSS 防护（输出过滤）

---

## 九、性能优化

### 9.1 Redis 缓存

```php
// 常用数据缓存到 Redis
Cache::set('config:app', $config, 3600);
Cache::get('config:app');
```

### 9.2 数据库优化

- 为常用查询字段添加索引
- 使用查询缓存
- 分页查询限制最大条数

### 9.3 静态资源 CDN

- 图片、视频上传到 OSS
- 开启 CDN 加速
- 前端静态资源使用 CDN

---

## 十、故障排查

### 10.1 常见问题

**问题 1**: 502 Bad Gateway
- 检查 PHP-FPM 是否运行
- 检查 Nginx 配置

**问题 2**: 数据库连接失败
- 检查 MySQL 服务状态
- 检查数据库账号密码

**问题 3**: AI 接口调用失败
- 检查 API Key 配置
- 检查网络连接
- 查看错误日志

### 10.2 日志查看

```bash
# 查看实时日志
tail -f /var/www/ai-drama-system/backend/runtime/log/app.log

# 查看错误日志
grep ERROR /var/www/ai-drama-system/backend/runtime/log/*.log
```

---

## 十一、备份与恢复

### 11.1 数据库备份

```bash
# 备份脚本
#!/bin/bash
BACKUP_DIR="/backup/ai-drama"
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u ai_drama_user -p ai_drama > ${BACKUP_DIR}/db_${DATE}.sql

# 保留最近 30 天备份
find ${BACKUP_DIR} -name "db_*.sql" -mtime +30 -delete
```

### 11.2 代码备份

```bash
# 使用 Git 管理代码
git add .
git commit -m "backup: $(date)"
git push origin backup
```

---

**部署完成检查清单**：

- [ ] Nginx 运行正常
- [ ] PHP-FPM 运行正常
- [ ] MySQL 连接正常
- [ ] Redis 连接正常
- [ ] 数据库表已创建
- [ ] 环境变量已配置
- [ ] OSS 存储已配置
- [ ] AI 接口已配置
- [ ] HTTPS 已启用
- [ ] 定时任务已配置
- [ ] 日志轮转已配置
- [ ] 备份脚本已配置

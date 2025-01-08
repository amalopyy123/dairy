创建数据库：
CREATE DATABASE rentry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
导入表：
source rentry.sql

安装php依赖的命令：
apt install php8.3 php8.3-fpm php8.3-mysql php8.3-pgsql php8.3-sqlite3 php8.3-curl php8.3-xml

fpm的配置文件目录：
/etc/php/8.3/fpm/pool.d/www.conf

nginx配置：
server{
   listen       80;
   server_name xxx;
   location / {
        root /var/www/html/rentry;
        index index.html;
        try_files $uri /index.html index.php;
        # 允许符号链接
        disable_symlinks off;

        # 处理 PHP 文件
        location ~ \.php$ {
                include fastcgi_params;
                fastcgi_pass unix:/run/php/php8.3-fpm.sock;  # 使用 Unix 套接字
                fastcgi_param SCRIPT_FILENAME $request_filename;
        }

        # 允许上传文件
        client_max_body_size 2048M;  # 可根据需求调整最大上传文件大小
   }
}
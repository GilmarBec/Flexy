**Requisites**
==============

- Composer: 1.8.4

- PHP: 7.3.2

- symfony: 4.3.0

- MySQL

**Configs**
-----------

**PHP**

if in your "php.ini" the vars:
- upload_max_filesize
- post_max_size

don't are like this: 
    
    upload_max_filesize=5M // 5M or more
    post_max_size=5M       // 5M or more
    
set this on "php.ini" (else just skip)

**MySQL**

To use your Database mysql (if necessary) on file ".env":

    
    /DATABASE_URL=mysql://your_user:your_password@your_url:port/db_name/

EX:

    DATABASE_URL=mysql://root:@127.0.0.1:3306/flexy

**Instalation and Run**
---------------

Download or clone the project and run the command:

    cd path_of_your_product
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    php bin/console server:run

**Help**

If you want to help send an email to: *gilmarmscontato@gmail.com*
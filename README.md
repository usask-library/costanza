# Costanza 

> Costanza is an EZproxy configuration management system. It focuses on the management of stanzas within the EZproxy config file rather than individual EZproxy directives.

## Features
- Based on Cretu Eusebiu's [Laravel-Vue SPA](https://github.com/cretueusebiu/laravel-vue-spa)  
- Laravel 5.8 
- Vue + VueRouter + Vuex + VueI18n + ESlint + BootstrapVue
- Pages with dynamic import and custom layouts
- Login, register, email verification and password reset
- Authentication with JWT
- Socialite integration
- Bootstrap 4 + Font Awesome 5

## Installation

### Requirements

Costanza uses Vue and the [Laravel](https://laravel.com/) PHP framework.
Its requirements therefore [mirror those of Laravel 5.8](https://laravel.com/docs/5.7/#installation).

- SQLite
- [Composer](https://getcomposer.org/)
- PHP > 7.1.3 with the following extensions (hopefully most of these are included in your default PHP install)
  - OpenSSL
  - PDO
  - Mbstring
  - Tokenizer
  - XML
  - Ctype
  - JSON
  - BCMath
- Vue 2.6
- Apache 2.4+
  - Costanza may run under another web server, like nginx, but it has only been tested with Apache

### Installing Costanza

#### Check out the code from Git

Costanza (like many other applications written using PHP frameworks such as Laravel or Symfony) contains a lot of
code that should **not** be accessible via the browser. **DO NOT** checkout the Costanza code into the document root
(or any other web accessible folder) of your web server.
   
On many web servers, the default folder for web content is `/var/www/html`.  The following installation instructions
assume will assume that, and may need to be adjusted depending on your server setup.

1. Costanza requires [`usask-library/ezproxy-stanzas`](https://github.com/usask-library/ezproxy-stanzas) repository.
   You will need to check out that repo as well as Costanza.
   ```bash
   # cd /var/www
   git clone https://github.com/usask-library/ezproxy-stanzas.git
   ```

1. Check out the Costanza code
   ```bash
   # cd /var/www
   git clone https://github.com/usask-library/costanza.git
   ```

#### Install Dependencies

Install the Laravel and Vue dependencies:

```bash
cd /var/www/costanza
composer install
npm install
```

#### Database Setup

Costanza uses SQLite to store user account information.  The initial database creation can be done
by simply creating an empty file with the correct name.  The database itself initialized later.

```bash
touch database/database.sqlite
```

#### Directory Permissions

Laravel requires the SQLite database and the directories `storage` and `bootstrap/cache` be writable
by the web server user.  Again, assuming your web server is Apache, directory permissions can be
adjusted with the following commands:

```bash
sudo chgrp -R apache storage bootstrap/cache database/database.sqlite
sudo chmod -R g+w storage bootstrap/cache database/database.sqlite 
```

#### Configuration File and Application Key

A sample configuration file is shipped with Costanza.  Before Costanza can be used, a copy of that
config file needs to be made, and new application keys generated.

```
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate
```

Some further changes to the .env file are needed.

- `APP_URL` needs to be set to the URL from which you will be serving Costanza --
  `https://costanza.example.com/` or `https://example.com/costanza/` for example
- `MIX_APP_PATH` should match the path portion of the above `APP_URL` -- `/` or `/costanza/` for example
- `STANZA_PATH` should point to the location where you checked out the `ezproxy-stanzas` repo.  If you
  followed the documentation exactly, this will be `/var/www/ezproxy-stanzas`
- The `MAIL_*` entries may need to be adjusted, depending on the mail configuration of your server   

#### Generate Production Vue Code

Run the following to generate production ready Vue code for Costanza.  Note, this **must** be done **after** creating
and modifying the `.env` file in the above step.

```bash
npm run production
```

#### Apache Configuration

The last step is to configure Apache.  The only web accessible folder should be the `public` folder.

If you are serving Costanza from the root of your web server (something like https://costanza.example.com/ for example),
you can update the `DocumentRoot` directive in your Apache config, changing it to `/var/www/costanza/public`.
Don't forget to update the corresponfing `Directory` directive as well.

If you are serving Costanza from a sub-folder of your web server (https://example.com/costanza/ for example) add
something similar to the following to your Apache config:

```apacheconfig
Alias /costanza /var/www/costanza/public
<Directory /var/www/costanza/public>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

You will probably need to restart Apache after making this change.

# Zibaldone

## What Zibaldone does

Zibaldone uses chunks of markdown text, called **fragments**, to create html books.

Zibaldone can handle multiple books and each can contain any number of fragments.<br/>

Zibaldone is compatible with [Leanpub](http://leanpub.com) and this means that the books can be automatically rendered as ebooks (pdf, epub, mobi) and that you can sell, publish, share them right away.

The fragments are alway text files living on your computer but they can be originated from remote **references**.

A reference is, well, a reference :-) It points to any markdown file living in an external resource (for now only Github).

When reference is added to a book its content is downloaded and saved in local fragment which can be modified as you like.

Through the graphic interface the fragments can be drag and drop to change their order sequence while the paragraphs dependency is set by indenting them.

Both the order and the paragraphs dependency will be reflected in the index menu in the html book.

You can see a demo in this [youtube video](http://youtu.be/lePHPhFQQwI).

<iframe width="560" height="315" src="https://www.youtube.com/embed/lePHPhFQQwI" frameborder="0" allowfullscreen></iframe>

## A bit of history

> Source: [Wikipedia](http://en.wikipedia.org/wiki/Commonplace_book#Zibaldone)

> During the course of the 15th century, the Italian peninsula was the site of a development of two new forms of book production: the deluxe registry book and the zibaldone (or hodgepodge book). What differentiated these two forms was their language of composition: a vernacular. Giovanni Rucellai, the compiler of one of the most sophisticated examples of the genre, defined it as a "salad of many herbs.

> Zibaldone were always paper codices of small or medium format – never the large desk copies of registry books or other display texts. [..] Devotional, technical, documentary and literary texts appear side-by-side in no discernible order.

> By the 17th century, commonplacing had become a recognized practice that was formally taught to college students in such institutions as Oxford. John Locke appended his indexing scheme for commonplace books to a printing of his An Essay Concerning Human Understanding. [..] <b>“Commonplacing” persisted as a popular study technique until the early 20th century</b>.

Well, I wonder if it's not the right time to start using it again ...
After all we stand on the shoulders of giants, right? ;-)

## Why Zibaldone

Zibaldone started as a self tutorial to learn AngularJS but little by little it started having more and more sense so I thought to publish it. 

I also decided to list this project among the [php-aid](http://php-aid.org) ones because I really would like to develop it in team. 

## Before your start

**This is not a stable application! Use it with care**

## Install

Zibaldone, for now, can run only on your computer or virtual machine. If you don't know how to create a virtual machine you can read this ebook [Pragmatic Virtualization](https://leanpub.com/pragmatic_virtualization). It's free.

### Prerequisites

    * A web server
    * PHP
    * composer
    * mysql

### Procedure

Clone the repo in your webroot

    cd /var/www/
    git clone git@github.com:damko/zibaldone.git

Give read and write access to the user running the webserver (www-data on linux debian based distro) for the directory `app/books`

    sudo chown www-data app/books

Create a vhost for your webserver (this is an example for the Nginx webserver) and add the vhost name (in the example *zibaldone.derox* in /etc/vhost of your client (the pc in which you run the browser).

    server {

            listen          80;

            #nginx configuration just for this vhost
            client_max_body_size 10M;
            client_header_timeout 30;
            client_body_timeout 3m;
            send_timeout 5m;

            server_name  zibaldone.derox;
            root        /var/www/zibaldone/ ;

            access_log  /var/log/nginx/zibaldone--derox_access.log;

            # Deny access to any files with a .php extension in the uploads directory
            location ~* /(?:uploads|files)/.*\.php$ {
                    deny all;
            }

            location / {
                    index index.html;
            }

            location /api {
                    index index.php;
                    try_files $uri $uri/ /api/index.php?$args;
            }



            location = /favicon.ico {
                    log_not_found off;
                    access_log off;
            }

            location = /robots.txt {
                    allow all;
                    log_not_found off;
                    access_log off;
            }

            # Make sure files with the following extensions do not get loaded by nginx because nginx would display the source code, and these files can contain PASSWORDS!
            location ~* \.(engine|inc|info|install|make|module|profile|test|po|sh|.*sql|theme|tpl(\.php)?|xtmpl)$|^(\..*|Entries.*|Repository|Root|Tag|Template)$|\.php_ {
                    deny all;
            }

            # Deny all attempts to access hidden files such as .htaccess, .htpasswd, .DS_Store (Mac).
            location ~ /\. {
                    deny all;
                    access_log off;
                    log_not_found off;
            }

            #location ~*  \.(jpg|jpeg|png|gif|css|js|ico)$ {
            location ~*  \.(jpg|jpeg|png|gif|css|js)$ {
                    expires max;
                    log_not_found on;
            }

            location ~ \.php$ {
                    #try_files $uri =404;
                    try_files $uri $uri/ /api/index.php?$args;
                    fastcgi_split_path_info ^(.+\.php)(/.+)$;
                    fastcgi_index index.php;

                    #this is for using unix socket
                    #fastcgi_pass  unix:/var/run/php-fpm/wordpress.sock;

                    #this is for http socket
                    fastcgi_pass 127.0.0.1:9100;

                    fastcgi_param   SCRIPT_FILENAME $document_root$fastcgi_script_name;
                    include       /etc/nginx/fastcgi_params;

                    fastcgi_read_timeout 3m;
            }
    }

Enable the webserver vhost and reload the webserver.

Install the dependencies

    cd zibaldone/app/api
    composer install

Create the *zibaldone* database in mysql and populate it using the `database.sql` file.

Edit the file `app/api/models/zibaldone.php` and update it with the correct credentials.

Finally change the rights of the zibaldone directory to 777 so that the webserver can create files and folders.


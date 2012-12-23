Partisan - Use Artisan everywhere
====
Artisan command line interface wrapper for the Laravel 
PHP framework

## What it does
Partisan enables the use of the Artisan command line 
interface from anywhere in your  Laravel project directory, 
not just the root directory as is normally the case.
This is accomplished by wrapping artisan with a binary
that lives in a directory accessible from your $PATH,
allowing you to call it from any form any working
directory on your system.

## Features
* Use the Artisan CLI anywhere in your Laravel project Directory.
* Automates creation of new Laravel projects.
* Automates installation of composer for Laravel 4 projects.
* simplifies running the PHP built-in development server for your Laravel project.

## Without Partisan
When using Artisan you find yourself continually running
into the problem of having to do extra typing when not in the
project root directory.

### Having to change you're working directory
```bash
# Oh no I'm not in the project root.
$ pwd
/home/me/laravelproject/application/models
# Arghh, now i have to do this to use artisan
$ cd ../../
$ ./artisan
# Man this sux
```

### Having to prepend your Artisan call with ```../../../```
```bash
# I'm doing this again? Really?
$ pwd
/home/me/laravelproject/application/models
# Just why do i have to do this?
$ ../../artisan
```

Yuck!

## With Partisan
You just type artisan from anywhere in your project and 
partisan will automaticallyfind and call the artisan 
that belongs to your poject.

When I start a new project i'll need to tell 
partisan about it
```bash
# you can do this from any working directory on your system
$ artisan --add ~/path/to/my_project
new project /home/me/path/to/my_project has been added to Partisan
```

Now i can delve into a subdirectory of my project and
still be able to use artisan
```bash
cd ~/path/to/my_project/application/models
$ artisan
```
That's it!

## Installation
1. Clone partisan into ~/.partisan

   ```sh
   $ git clone git://github.com/etherealite/partisan.git ~/.partisan
   ```

2. Add Partisan to your `$PATH` to make it available when you call 
if from the shell.

   ~~~ sh
   $ echo 'export PATH="$HOME/.partisan/bin:$PATH"' >> ~/.profile
   ~~~
4. Restart your shell..

    ~~~ sh
    $ exec $SHELL -l
    ~~~
5. Done.


## Requirements
* Laravel >= 3.x
* Unix like operating system

## Feedback
Any feedback is appreciated.
- IRC #laravel on irc.freenode.net my nick is etherealite
- Laravel [forums](http://forums.laravel.com/) user name: etherealite

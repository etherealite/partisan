Partisan - Use Artisan everywhere
====
Artisan command line interface wrapper for the Laravel 
PHP framework


## Description
Partisan enables the use of the Artisan command line 
interface from anywhere in your project directory, 
not just the root directory asis normally the case.

## Without Partisan
When using Artisan you find yourself continually running
into the problem of having to do extra typing when not in the
project root directory.

### Having to chang you're working directory
```bash
# Oh no I'm not in the project root.
$ pwd
/home/me/laravelproject/application/models
# Arghh, now i have to do this to use artisan
$ cd ../../
$ ./artisan
# Man this sux
```

### Having to prepend your artisna call with ```../../../```
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

## Requirements
* PHP >= 5.3.0
* Unix like OS**

**If anyone would like to do the work to port it to Windows I would
be happy it merge it in.

## Feedback
Any feedback is appreciated.
- IRC #laravel on irc.freenode.net nick is etherealite
- Laravel [forums](http://forums.laravel.com/) user name: etherealite
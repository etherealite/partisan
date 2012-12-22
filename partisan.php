#!/usr/bin/env php
<?php

// Try and make PHP not insane.
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_error_handler("e_handler");
function e_handler( $errno, $errstr)
{ 
  #echo "$errno, $errstr";
  debug_print_backtrace();
  exit(1);
}


// Maybe this script can work with windows someday.
define('SEP', DIRECTORY_SEPARATOR);
define('TESTING', false);

/*
 * **************************************
 * Globals, because why not?
 */

// options that are specificaly bound to partisan
$OUR_SHORT = "";
$OUR_LONG = array(
  "add-project:",
  "forget-project:",
  "show-projects",
  "create-project:",
  "run-server::",
  "help"
);

// the path to the current project detected from the
// current working directory
$PROJECT = null;

// options provided by shell
$OPTIONS = array();

// Tree structure containg all project directories
$PROJ_TREE = array();

// Flat array structure containg all project directories
$PROJ_TABLE = array();

/*
 * interpret arguments given from shell and act accordingly.
 *
 * @return void
 */
function run()
{
  global $OUR_SHORT;
  global $OUR_LONG;
  global $argv;

  // shell arguments
  $ARGV = $argv;

  // current working directory provided by shell
  $PWD = getenv('PWD');

  // initialize options using the php builtin
  $options = getopt($OUR_SHORT, $OUR_LONG);
  set_options($options);

  // reformat option keys for easier use
  $opt_keys = strip_merge($OUR_SHORT, $OUR_LONG);

  // check current working directory is a project.
  $project = detect_project($PWD);
  if ($project !== null)
  {
    set_project($project);
  }

  $status = null;
  if (for_partisan($opt_keys, $options))
  {
    // command is internal to partisan
    $status = internal_exec($options);
  }
  elseif($project != null)
  {
    // command is forwarded to artisan
    $status = artisan_exec($ARGV);
  }
  else
  {
    // no arguments just print out the help
    $status = partisan_help();
  }

  $msg = get_msg($status);
  if ($msg)
  {
    print $msg . "\n";
  }
  exit($status);
}


/*
 * strip ':' symboles from shell option definitions  and place
 * them in a array
 *
 * @param  string $options
 * @param  array  $long_opts
 *
 * @return array
 */
function strip_merge($options = "", $long_opts = array())
{
  // remove all ':' characters from the option definitions
  $strip_colon = function($string){
    return str_replace(':', '', $string);
  };

  // perform the actual stripping
  $short_keys = array();
  if ($options)
  {
    $short_keys = str_split($strip_colon($options));
  }

  $long_keys = array_map($strip_colon, $long_opts);

  return array_merge($short_keys, $long_keys);
}


/*
 * detect if the nodes of a  path provided satisfy a 
 * stored project path
 *
 * @param string $path
 */
function detect_project($path)
{
  $tree = get_tree();
  if ($tree === null)
  {
    $tree = array();
  }
  return search_tree($path, $tree);
}


/*
 * determineif any options passed from the shell
 * are not partisan specific
 * 
 * @param array $opt_keys
 * @param array $option
 */
function for_partisan($opt_keys, $options)
{
  foreach (array_keys($options) as $option)
  {
    if ( in_array($option, $opt_keys))
    {
      return true;
    }
  }
  return false;
}


/*
 * filter and return any options passed from the shell
 * that are not specific to partisan
 *
 * @TODO this is currently cruft, could be usefull
 * so leaving it in for now.
 *
 * @param  array  $opt_keys
 * @param  array  $recieved
 *
 * @return array
 */
function non_partisan($opt_keys, $options)
{

  // optoins that are not specific to partisan
  $not_ours = array();

  // look for non partisan specific options
  foreach ($recieved as $key => $val)
  {
    if ( ! in_array($key, $opt_keys))
    {
      //There is an option not recognized by partisan
      $not_ours[$key] = $val;
    }
  }
  return $not_ours;
}


/*
 * run commmands that are internal to partisan
 *
 * @param array $options
 * @param string $project
 */
function internal_exec($options)
{
  $status = null;
  $keys = array_keys($options);
  if (in_array("run-server", $keys))
  {
    $status = run_server();
  }
  elseif(in_array("add-project", $keys))
  {
    $status = add_project();
  }
  elseif(in_array("forget-project", $keys))
  {
    $status = forget_project();
  }
  elseif(in_array("create-project", $keys))
  {
    $status = create_project();
  }
  elseif(in_array("show-projects", $keys))
  {
    $status = show_projects();
  }
  elseif(in_array("help", $keys))
  {
    $status = partisan_help();
  }
  return $status;
}


/*
 * Print Partisan specific help and then call
 * call artisan's help if pwd in a project
 */
function partisan_help()
{
  $msg = <<<EOT
You are currently using Partisan, the Artisan command
wrapper for the Laravel PHP framework.

options:
--add-project    path          Track a new project path
--forget-project path          Stop tracking the project path 
--create-project name          Create a new project in current directory
--show-projects                Show all projects registered with Partisan
--run-server[=hostname:port]   Run the builtin php webserver for the current project (whitespace is not an allowed argument separator)

EOT;


  print $msg . "\n";
  $project = get_project();
  if ($project)
  {
    // Append real Artisan's help message to Partisan's
    print "Real Artisan help:\n";
    artisan_exec("--help");
  }

  return 0;
}


/*
 * Store a project path so it can be detected from the working
 * directory
 *
 * @param string $path
 *
 * @return integer
 */
function add_project($path = null)
{
  if ($path == null)
  {
    $path = get_options("add-project");
  }
  $project = realpath($path);
  if ( ! is_dir($project))
  {
    return 22;
  }
  elseif( ! file_exists($project . '/artisan'))
  {
    return 23;
  }

  $table = get_table();

  if (in_array($project, $table))
  {
    //avoid duplication
    return 21;
  }
  $table[] = $project;
  set_table($table);

  $tree = get_tree();

  if ($tree === null)
  {
    $tree = array();
  }

  $tree = tree_add_path($tree, $project);
  set_tree($tree);

  // need to store table first or they will be considered out
  // of sync
  if (store_table($table) and  store_tree($tree))
  {
    echo "project location $project has been added to partisan\n";
  }
  return 0;
}


/*
 * remove the given project from the project table
 * and persist the change
 * 
 * @param string $project
 *
 * @return integer
 */
function forget_project()
{
  $raw_input = get_options("forget-project");
  $path = realpath($raw_input);

  $table = get_table();
  if ($table === null)
  {
    return 24;
  }

  $index = array_search($path, $table);
  if ( $index === false)
  {
    // given path is not tracked
    return 25;
  }

  // remove the project from the project table
  unset($table[$index]);
  set_table($table);

  $success = store_table($table);
  if($success === false)
  {
    return 5;
  }

  // currently no way to delete projects from tree,
  // rebuild the whole thing.
  $tree = build_tree($table);
  set_tree($tree);

  $success = store_tree($tree);
  if($success === false)
  {
    return 6;
  }
  return 0;
}


/*
 * Create a new Laravel 4 "Illuminate" project and
 * add register it with Partisan
 */
function create_project()
{
  $name = get_options("create-project");
  $PWD = getenv('PWD');
  composer_install($PWD);
  exit;
  $repository = "git://github.com/illuminate/app.git";
  $status = null;
  passthru("git clone $repository $name", $status);
  if ($status !== 0)
  {
    return $status;
  }

  $project  = $PWD . "/$name";

  $composer = shell_exec("which composer");
  if ($composer == null)
  {
    $composer = shell_exec("which composer.phar");
  }
  else
  {
    $installed = composer_install($project);
    if($installed == 0) $composer = "$project/composer.phar";
  }
  $composer = trim($composer);

  if ($composer)
  {
    #echo "$composer install -d $project"; exit;
    passthru("$composer install -d $project", $status);
  }
  add_project($project);
  return $status;
}


function composer_install($path)
{
  $status = null;
  $coerced = array("--install-dir=$path");
  $installer = file_get_contents('https://getcomposer.org/installer');
  $installer = str_replace('$argv', '$coerced', $installer);
  $installer = str_replace('<?php', '', $installer);
  $status = eval($installer);
  return $status;
}


/*
 * print out all of the currently detectable projects
 *
 * @return void
 */
function show_projects()
{
  $table = get_table();
  foreach ($table as $project)
  {
    print $project . "\n";
  }
  return 0;
}


/*
 * Run the php webserver for the current project
 *
 * @param string $project
 */
function run_server()
{
  $project = get_project();
  if ($project == null)
  {
    return 60; // status code
  }
  $host_port = get_options('run-server');
  if ( ! $host_port) $host_port = "localhost:8000";
  
  $webroot = $project . '/public';
  $status = null;
  passthru("php -S $host_port  -t $webroot 1>&2", $status);
  return $status;
}


/* forward arguments to artisan binary and execute it.
 *
 * @param string $bin_path
 * @param array  $arguments
 *
 * @return integer
 */
function artisan_exec($arguments)
{
  $project = get_project();
  $artisan = $project .'/artisan';

  if ( ! file_exists($artisan))
  {
    return 65;
  }

  if ( ! is_string($arguments))
  {
    array_shift($arguments);
    $arguments = join(" ", $arguments);
  }

  passthru("php $artisan $arguments", $status);
  return $status;
}


/*
 * contains all status messages
 *
 * @param integer $status
 */
function get_msg($status)
{

  $messages = array(
    0  => null,
    1  => null, // let the called external programs handle it.
    5  => "Couldn't write to tracked projects table",
    6  => "Couldn't write to tracked projects tree",
    21 => "Project path is already registered",
    22 => "Given project path is  not a valid directory",
    23 => "Could not find artisan binary at given project path",
    24 => "There are currently no tracked projects",
    25 => "Given project path is not currently tracked",
    60 => "Working directory not in a regsitered project",
    65 => "No Artisan binary found in the current project",
  );
  return $messages[$status];
}


function user_dir()
{
  $HOME = getenv('HOME');
  $path = "$HOME" . SEP . ".partisan";
  if ( ! is_dir($path))
  {
    mkdir($path);
  }
  return $path;
}


/*
 * returns  all the paths used for cache and configuration
 * 
 * @param string $key
 *
 * @return string
 */
function get_path($key)
{
  $user_dir = user_dir();

  switch ($key)
  {
    case "tree_cache":
      return $user_dir . '/.tree_cache';
      break;
    case "table":
      return $user_dir . '/projects.inc';
      break;
  }
}


/*
 * return the value of an option provided by the shell
 *
 * @param string $option
 */
function get_options($option = null)
{
  global $OPTIONS;
  $options = $OPTIONS;
  if($option)
  {
    return $options[$option];
  }
  return $options;
}


/*
 * Set the global options provided by the calling shell
 *
 * @param array $options
 */
function set_options($options)
{
  global $OPTIONS;
  $OPTIONS = $options;
}


/* Get the projected detected from the working directory
 *
 * @param string $project
 */
function get_project()
{
  global $PROJECT;
  $project = $PROJECT;
  return $project;
}


/* set the projected detected from the working directory
 *
 * @param string $project
 */
function set_project($project)
{
  global $PROJECT;
  $PROJECT = $project;
}


/*
 * get the current global project tree or grab the cached
 * version and set the global
 *
 * @return array $PROJ_TREE
 */
function get_tree()
{
  global $PROJ_TREE;
  if ( $PROJ_TREE == null)
  {
    if (tree_is_synced())
    {
      // tree is in sync with table, load it from cache
      $path = get_path('tree_cache');
      $tree = restore_tree($path);
    }
    else
    {
      // tree is behind changes made to the table, rebuild
      // the entire tree from the new table.
      $table = get_table();
      $tree = build_tree($table);
    }
    store_tree($tree);
    set_tree($tree);
  }
  $proj_tree = $PROJ_TREE;
  return $proj_tree;
}


/*
 * determine if the tree is synced with the
 * project table 
 */
function tree_is_synced()
{
  $tree_path = get_path('tree_cache');
  if( ! file_exists($tree_path))
  {
    return false;
  }
  $tree_time = filemtime($tree_path);


  $table_path = get_path('table');
  if ( ! file_exists($table_path))
  {
    return false;
  }
  $table_time = filemtime($table_path);


  if ($table_time > $tree_time)
  {
    return false;
  }
  return true;
}

/*
 * set the state of the global project tree
 *
 * @param array tree
 */
function set_tree($tree)
{
  global $PROJ_TREE;
  $PROJ_TREE = $tree;
}


/*
 * Load a project tree from given path.
 *
 * @param string $path
 *
 * @return array
 */
function restore_tree($path)
{
  if ( ! file_exists($path))
  {
    return null;
  }

  return unserialize(file_get_contents($path));
}


/*
 * Persist the given project tree to the proper path in
 * the user directory
 *
 * @param array $tree
 *
 * @return mixed
 */
function store_tree($tree)
{
  $path = get_path('tree_cache');
  return file_put_contents($path, serialize($tree));
}


/*
 * build a new tree from array of path strings
 *
 * @param array $paths
 *
 * @return array
 */
function build_tree($paths)
{
  $tree = array();
  foreach ($paths as $path)
  {
    $tree = tree_add_path($tree, $path);
  }
  return $tree;
}


/*
 * Add a new path to a tree
 *
 * @param string $path
 * @param array  $tree
 *
 * @return array
 */
function tree_add_path($tree, $path)
{
  $path_nodes = path2array($path);
  $branch = &$tree;
  foreach ($path_nodes as $node)
  {
    if ( ! array_key_exists($node, $branch))
    {
      #$branch[$node] = array("#parent" => &$branch);
      $branch[$node] = array();
    }
    $branch = &$branch[$node];
  }

  $leef = &$branch;
  $leef = $path;
  return $tree;
}


/*
 * split a path path string into a flat array
 * of directories
 *
 * @param string $dir
 *
 * @return array
 */
function path2array($dir)
{
  // stripp root, trailing slash; surrounding space chars
  $stripped = trim($dir, SEP.' \t');
  return explode(SEP, $stripped);
}


/*
 * Search a tree for the given pth
 *
 * @param string $path
 * @param array  $tree
 *
 * @return string
 */
function search_tree($path, $tree)
{
  $nodes = path2array($path);
  $branch = $tree;
  foreach ($nodes as $node)
  {
    if (array_key_exists($node, $branch))
    {
      $branch = $branch[$node];
      if (is_string($branch))
      {
        // The branch is a string and therefore a leaf
        // containing the matching project's root dir.
        return $branch;
      }
    }
  }
  return null;
}


/*
 * Pull in the global project table, try and restore
 * from file if in null state.
 *
 * @return array
 */
function get_table()
{
  global $PROJ_TABLE;
  if ($PROJ_TABLE == null)
  {
    $table = array();
    $path = get_path('table');
    if(file_exists($path))
    {
      $table = restore_table($path);
    }
    else
    {
      // create an empty table file
      store_table($table);
    }
  }

  set_table($table);
  return $table;
}


/*
 * Set the table global project table from input
 *
 * @return void
 */
function set_table($table)
{
  global $PROJ_TABLE;
  $PROJ_TABLE = $table;
}


/*
 * Load the project table array from file
 * 
 * @param string $path
 *
 * @return array
 */
function restore_table($path)
{
  if ( ! file_exists($path)) return null;

  return require $path;
}


/*
 * store the state of the given project table to file
 * in user directtory
 *
 * @param array $table
 */
function store_table($table)
{
  $path = get_path('table');
  $comment = "For information purposes, DO NOT EDIT!";

  $code = "<?php\n\n#$comment \n\n return " . 
    var_export($table, true) . ';';

  return file_put_contents($path, $code);
}

if (( ! TESTING) and php_sapi_name() === 'cli')
{
  run();
}

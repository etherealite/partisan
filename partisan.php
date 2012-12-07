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


/*
 * **************************************
 * Globals, because why not?
 */

// shell arguments
$ARGV = $argv;

// current working directory provided by shell
$PWD = getenv('PWD');

// options that are specificaly bound to partisan
$OUR_SHORT = "";
$OUR_LONG = array(
  "add-project:",
  "run-server"
);

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
  global $ARGV;
  global $PWD;
  global $OUR_SHORT;
  global $OUR_LONG;

  $options = getopt($OUR_SHORT, $OUR_LONG);
  $opt_keys = strip_merge($OUR_SHORT, $OUR_LONG);

  $project = detect_project($PWD);

  $status = 1;
  if (for_partisan($opt_keys, $options))
  {
    $status = internal_exec($options, $project);
  }
  elseif($project != null)
  {
    $status = artisan_exec($ARGV, $project);
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


function add_project($path)
{
  $project = realpath($path);
  if ( ! is_dir($project))
  {
    echo "project path $project, not a valid directory";
    return 1;
  }
  elseif( ! file_exists($project . '/artisan'))
  {
    echo "could not find artisan binary at $project/artisan\n";
    return 1;
  }


  $table = get_table();

  if ($table === null)
  {
    $table = array();
  }


  if (in_array($project, $table))
  {
    //avoid duplication
    echo "project path $project is already registered\n";
    return 1;
  }
  $table[] = $project;
  set_table($table);

  $tree = get_tree();

  if ($tree === null)
  {
    $tree = array();
  }

  $tree = add_path($tree, $project);
  set_tree($tree);

  if (store_table($table) and  store_tree($tree))
  {
    echo "new project $project has been added to partisan\n";
  }
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
    $path = get_path('tree_cache');
    $tree = restore_tree($path);
    set_tree($tree);
  }
  return $PROJ_TREE;
}


/*
 * Determine the host operating system
 *
 * @return string
 */
function get_os()
{
  return strtolower(php_uname('s'));
}


/*
 * return the path to the directory containing user
 * specific data
 * @return string
 */
function user_dir()
{
  if (get_os() !== "linux")
  {
    //TODO some day make this work for windows
    trigger_error("only linux is supported");
  }
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
 * Run the php webserver for current project
 *
 * @param string $project
 */
function run_server($project)
{
  $webroot = $project . '/public';
  passthru("php -S localhost:8000 -t $webroot 1>&2");
}


/*
 * run commmands that are internal to partisan
 *
 * @param array $options
 * @param string $project
 */
function internal_exec($options, $project)
{
  $status = 1;
  $keys = array_keys($options);
  if (in_array("run-server", $keys))
  {
    if ($project != null)
    {
      run_server($project);
    }
    else
    {
      print "working directory not in a regsitered project\n";
    }
  }
  elseif(in_array("add-project", $keys))
  {
    $dir = $options["add-project"];
    add_project($dir);
    $status = 0;
  }
  return $status;
}


/* forward arguments to artisan binary and execute it.
 *
 * @param string $bin_path
 * @param array  $arguments
 *
 * @return integer
 */
function artisan_exec($arguments, $path)
{
  $artisan = $path .'/artisan';

  if ( ! file_exists($artisan))
  {
    print "artisan binary not found in project root: $proj_root\n";
    return 1;
  }

  array_shift($arguments);
  $arguments = join(" ", $arguments);
  passthru("php $artisan $arguments", $status);
  return $status;
}


/*
 * Add a new path to a tree
 *
 * @param string $path
 * @param array  $tree
 *
 * @return array
 */
function add_path($tree, $path)
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


function set_table($table)
{
  global $PROJ_TABLE;
  $PROJ_TABLE = $table;
}


function restore_table($path)
{
  if ( ! file_exists($path)) return null;

  return require $path;
}


function store_table($table)
{
  $path = get_path('table');
  $code = "<?php\n\n return " . var_export($table, true) . ';';
  return file_put_contents($path, $code);
}


function get_table()
{
  global $PROJ_TABLE;
  if ($PROJ_TABLE == null)
  {
    $path = get_path('table');
    $table = restore_table($path);
    set_table($table);
  }

  return $table;
}


function restore_tree($path)
{
  if ( ! file_exists($path))
  {
    return null;
  }

  return unserialize(file_get_contents($path));
}


function store_tree($tree)
{
  $path = get_path('tree_cache');
  return file_put_contents($path, serialize($tree));
}


/*
 * filter and return any options passed from the shell
 * that are not specific to partisan
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

run();

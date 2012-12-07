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


// The name of the artisan binary
define("ARTISAN_BIN", "artisan");


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
  "remove-project:",
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

  var_dump( for_partisan($opt_keys, $options));
  exit;

  $status = 1;
  $msg= "";
  if (for_partisan($opt_keys, $options))
  {
    $status = internal_exec($options, $opt_keys);
  }
  else
  {
    $path = get_project($PWD);
    if($path != null)
    {
      $status = artisan_exec($ARGV, $path);
    }
    else
    {
      $msg = "the current working directory is not a regitered project";
    }
  }
  if ($msg) print $msg;
  exit($status);
}


function add_project($path)
{
  if (! is_dir($project))
  {
    echo "project path $project, not a valid directory";
  }
  elseif ( ! file_exists($project. SEP . "artisan"))
  {
    echo "can't find artisan binary for project path $project.";
  }
  $table = get_table();
  $table[] = $project;
  $tree = get_tree();
  if( store_tree($path, $tree))
  {
    echo "new project $project has been added to partisan";
  }
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
  // Assume that all systems that are not windows are unix.
  if ( strpos("windows", strtolower(php_uname('s'))) === false)
  {
    return "windows";
  }
  else
  {
    return "unix";
  }
}


/*
 * return the path to the directory containing user
 * specific data
 * @return string
 */
function user_dir()
{
  if ( get_os() !== "unix")
  {
    //TODO some day make this work for windows
    trigger_error("non unix systems are unsupported");
  }
  $HOME = getenv('HOME');
  return "$HOME" . SEP . ".partisan";
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
      return $user_dir . '/.cache/tree.php';
      break;
    case "table":
      return $user_dir . '/projects.inc';
      break;
  }


}


/* Return the absolute path to the artisan bin for the
 * given project
 * 
 * @param string $path
 * @param string $bin_name
 *
 * @return string
 */
function get_bin($path, $bin_name)
{
  $bin_path = $path . SEP . $bin_name;
  if (! file_exists($bin_path)) return $bin_path;

  return null;
}


/*
 * Run the php webserver for current project
 *
 * @param string $project
 */
function run_server($project)
{
  $webroot = $path . '/public';
  passthru("php -S localhost:8000 -t $webroot", $status);
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
  {
    $artisan = $path . SEP . 'artisan';

    if ( ! file_exists($artisan))
    {
      print "artisan binary not found in project root: $proj_root\n";
      $arguments = array();
      foreach ($argv as $argu)
      {
        $arguments[] = $argu;
      }
      array_shift($arguments);
      $arguments = join(" ", $arguments);
      passthru("php $artisan $arguments", $status);
      return $status;
    }
}


function build_tree($projects)
{
  $tree = array();
  foreach($projects as $project)
  {
    $tree = add_path($tree, $project);
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
function add_path($path, $tree)
{
  $path_nodes = dir2array($path);
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


function dir2array($dir)
{
  // stripp root, trailing slash; surrounding space chars
  $stripped = trim($dir, SEP.' \t');
  return split(SEP, $stripped);
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
  $nodes = dir2array($dir);
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


function restore_table($path)
{
  if ( ! file_exists($path)) return null;

  return require $path;
}


function store_table($path, $table)
{
  file_put_contents($path, 'return ' . var_export($table));
}


function get_table()
{
  global $proj_table;
  return $proj_table;
}


function restore_tree($path)
{
  if ( ! file_exists($path))
  {
    return null;
  }

  return unserialize(file_get_contents($path));
}


function store_tree($path, $tree)
{
  return file_put_contents($path, serialize($tree));
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
  foreach (array_keys($options))
  {
    if ( ! in_array($opt_keys))
    {
      return false;
    }
  }
  return true;
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

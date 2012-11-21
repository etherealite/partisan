#!/usr/bin/env php
<?php

// Try and make PHP not insane.
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_error_handler("e_handler");
function e_handler( $errno, $errstr)
{ 
  echo "$errno, $errstr";
  debug_print_backtrace();
  exit(1);
}

// Maybe this script can work with windows someday.
define('SEP', DIRECTORY_SEPARATOR);

// options that specificaly bound to partisan
define(OUR_OPTS, "");
define(OUR_LONG, array("add-project:", "remove-project:"));

// The name of the artisan binary
define(ARTISAN_BIN, "artisan");

// Tree structure containg all project directories
$PROJ_TREE = array();
// Flatt array structure containg all project directories
$PROJ_TABLE = array();
// The current operating system


/*
 * interpret arguments given from shell and act accordingly.
 *
 * @return void
 */
function run()
{
  $PWD = getenv('PWD');
  $options = getopt($options, $long_opts);
  $opt_keys = strip_opts(OUR_OPTS, OUR_LONG);

  if (run_artisan($ARGV, $options, $opt_keys))
  {
    if(($tree = get_tree(user_dir())) == null)
    {
      echo "Couldn't find project tree cache";
    }
    elseif (($project_dir = search_tree($PWD, $tree)) == null)
    {
      echo "Current working directory not a subdirectory of"
        . " a valid project";
      exit(2);
    }
    elseif (($bin_path = get_bin($project_dir, ARTISAN_BIN)) == null)
    {
      echo "Coudn't find an artisan binary for project $project_dir\n";
      exit(3);
    }
    else
    {
      $status = artisan_exec($bin_path, $ARGV);
    }
  }
  else
  {
    $status = internal_exec($ARGV, $opt_keys);
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
function strip_opts($options = "", $long_opts = array())
{
  // remove all ':' characters from the option definitions
  $strip_colon = function($string){
    return str_replace(':', $strig);
  };

  // perform the actual stripping
  if($options)
  {
    $short_keys = explode("", $strip_colon($options));
  }
  if ($long_opts)
  {
    $long_keys = array_map($strip_colon, $opts_keys);
  }

    return array_merge($short_keys, $long_keys);
}


function get_tree()
{
  global $PROJ_TREE;
  if ( $PROJ_TREE == null)
    $form_cache = restore_tree($path, 
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
 * Determine if we should run artisan or not
 *
 * @param array  $arguments
 * @param array  $opt_keys
 *
 * @return bool
 */
function run_artisan($arguments, $opt_keys, $options)
{

  // partisans specific options require at at least three arguments
  if (count($arguments) < 2)
  {
    return true;
  }

  if(non_partisan($opt_keys, $options)
  $project = realpath($project); 
  if (! is_dir($project))
  {
    echo "project path $project, not a valid directory";
  }
  elseif ( ! file_exists($project. SEP . "artisan"))
  {
    echo "can't find artisan binary for project path $project.";
  }
  $tree = restore_tree($path);
  $tree = add_proj($project, $tree);
  if( store_tree($path, $tree))
  {
    echo "new project $project has been added to partisan";
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
    trigger_error("not implemented");
  }
  $HOME = getenv('HOME');
  return "$HOME" . SEP . ".partisan";
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
  else return null;
}


/* forward arguments to artisan binary and execute it.
 *
 * @param string $bin_path
 * @param array  $arguments
 *
 * @return integer
 */
function artisan_exec($bin_path, $arguments)
{
  if ( $proj_root !== null)
  {
    $artisan = $proj_root . SEP . 'artisan';

    if (file_exists($artisan))
    {
      global $argv;
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
    else
    {
      print "artisan binary not found in project root: $proj_root\n";
    }
  }
  else
  {
    print "Current working directory $PWD is not within a registered " .
      "laravel project directory.\n";
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

function get_table()
{
  global $proj_table;
  return $proj_table;
}



function restore_tree($path)
{
  return unserialize(file_get_contents($path));
}


function store_tree($path, $tree)
{
  return file_put_contents($path, serialize($tree));
}



/*
 * find if there are any options passed from the shell
 * that are not specific to partisan
 *
 * @param  array  $opt_keys
 * @param  array  $recieved
 *
 * @return array
 */
function non_partisan($recieved, $opt_keys)
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




function add_proj($path)
{
}




if (!array_key_exists("add", $longopts)) $project = $longopts["add"];
if (count($argv) == 2 && $project != null)

run();

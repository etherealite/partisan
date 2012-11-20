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


// forward arguments to artisan binary and execute it.
function artisan_exec($path, $arguments)
{

  
  $tree = get_tree();
  $proj_root = search_tree($path, $tree);
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


function add_path($tree, $path)
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


function search_tree($dir, $tree)
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

function restore_tree($path)
{
  return unserialize(file_get_contents($path));
}


function store_tree($path, $tree)
{
  return file_put_contents($path, serialize($tree));
}


/*
 * Determine if we should run artisan or not
 *
 * @param array  $arguments
 * @param array  $opt_keys
 *
 * @return bool
 */
function run_artisan($arguments, $opt_keys)
{

  // partisans specific options require at at least to optoins.
  if (count($arguments) < 2)
  {
    return true;
  }

  $options = getopt($options, $long_opts);
  if(non_partisan($opt_keys, $recieved)
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
 * find if there are any options passed from the shell
 * that are not specific to partisan
 *
 * @param  array  $opt_keys
 * @param  array  $recieved
 *
 * @return array
 */
function non_partisan($opt_keys, $recieved)
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

  // return the requested 
  if ($options && $long_opts)
  {
    return array_merge($short_keys, $long_keys);
  }
  elseif ($options)
  {
    return $short_keys;
  }
  elseif($long_opts)
  {
    return $long_keys;
  }
  else
  {
    return null;
  }
}

function add_proj($path)
{
}

function dir2array($dir)
{
  // stripp root, trailing slash; surrounding space chars
  $stripped = trim($dir, SEP.' \t');
  return split(SEP, $stripped);
}


function run()
{
  $PWD = getenv('PWD');
  $opt_keys = strip_opts(OUR_OPTS, OUR_LONG);
  if (run_artisan($ARGV, $opt_keys))
  {
    $status = artisan_exec($PWD, $ARGV);
  }
  else
  {
    $status = internal_exec($ARGV, $opt_keys);
  }
  exit($status);
}



if (!array_key_exists("add", $longopts)) $project = $longopts["add"];
if (count($argv) == 2 && $project != null)

run();

#!/usr/bin/env php
<?php

// Try and make PHP not insane.
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_error_handler("e_handler");
function e_handler( $errno, $errstr)
{ 
  debug_print_backtrace();
  die();
}

// Maybe this script can work with windows someday.
define('SEP', DIRECTORY_SEPARATOR);


function artisan_exec($projects, )
{

  $tree = build_tree($projects);
  $PWD = getenv('PWD');
  $proj_root = search_tree($PWD, $tree);
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
      passthru("php $artisan $arguments");
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
    $tree = add_node($tree, $project);
  }
  return $tree;
}


function add_node($tree, $project)
{
  $proj_nodes = dir2array($project);
  $branch = &$tree;
  foreach ($proj_nodes as $node)
  {
    if ( ! array_key_exists($node, $branch))
    {
      #$branch[$node] = array("#parent" => &$branch);
      $branch[$node] = array();
    }
    $branch = &$branch[$node];
  }

  $leef = &$branch;
  $leef = $project;
  return $tree;
}


function dir2array($dir)
{
  // stripp root, trailing slash; surrounding space chars
  $stripped = trim($dir, SEP.' \t');
  return split(SEP, $stripped);
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


$longopts = getopt(null, "project-add:");
if (!array_key_exists("add", $longopts)) $project = $longopts["add"];
if (count($argv) == 2 && $project != null)
{
  $project = realpath($project); 
  if (! is_dir($project))
  {
    echo "project path $project, not a valid directory";
  }
  elseif ( ! file_exists($project. SEP . "artisan"))
  {
    echo "can't find artisan binary for project path $project.";
  }
  $tree = get_tree($path);
  $tree = add_proj($project, $tree);
  if( save_tree($path, $tree))
  {
    echo "new project $project has been added to partisan";
  }
  else
  {

$tree
 artisan_exec($projects);


artisan_exec($projects);

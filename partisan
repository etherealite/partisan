#!/usr/bin/env php
<?php
define('SEP', DIRECTORY_SEPARATOR);

$HOME = getenv('HOME');

$projects = array(
  "$HOME/projects/graffiti",
  "$HOME/projects/shitfuck"
);

function artisan_exec($projects)
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
    $tree = add_proj($tree, $project);
  }
  return $tree;
}


function add_proj($tree, $project)
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

artisan_exec($projects);

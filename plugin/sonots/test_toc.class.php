<?php
// error_reporting(0); // nothing
error_reporting(E_ERROR | E_PARSE); // Avoid E_WARNING, E_NOTICE, etc
// error_reporting(E_ALL);
require_once('simpletest/autorun.php');
require_once('toc.class.php');
require_once('pukiwiki.php');

class test_PluginSonotsToc extends UnitTestCase
{
}
   
?>
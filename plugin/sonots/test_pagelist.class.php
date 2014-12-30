<?php
// error_reporting(0); // nothing
error_reporting(E_ERROR | E_PARSE); // Avoid E_WARNING, E_NOTICE, etc
// error_reporting(E_ALL);
require_once('simpletest/autorun.php');
require_once('pagelist.class.php');
require_once('option.class.php');
require_once('pukiwiki.php');

class Test_PluginSonotsPagelist extends UnitTestCase
{
    var $pages;
    function Test_PluginSonotsPagelist()
    {
        $this->pages = array
            (
             'test',
             'test/a',
             'test/a/aa',
             'test/a/aa/aaa',
             'test/a/bb/bbb',
             'test/c/cc/ccc',
             );
                       
    }
    function test_prefix()
    {
        $pagelist = new PluginSonotsPagelist($this->pages);
        $prefix = 'test/a/';
        $pagelist->grep_by('page', 'prefix', $prefix);
        $pages = $pagelist->get_metas('page');
        $truth = array('test/a/aa', 'test/a/aa/aaa', 'test/a/bb/bbb');
        $this->assertTrue($pages, $truth);
        $pagelist->gen_metas('relname', array(sonots::get_dirname($prefix)));
        $relnames = $pagelist->get_metas('relname');
        $truth = array('aa', 'aa/aaa', 'bb/bbb');
        $this->assertTrue($relnames, $turh);
    }

    function test_nonlist()
    {
        $pagelist = new PluginSonotsPagelist($this->pages);
        $non_list = 'aa';
        $pattern = '/' . preg_quote($non_list, '/') . '/';
        $pagelist->grep_by('page', 'preg', $pattern, TRUE);
        $pages = $pagelist->get_metas('page');
        $truth = array 
            (
             0 => 'test',
             1 => 'test/a',
             4 => 'test/a/bb/bbb',
             5 => 'test/c/cc/ccc',
             );
        $this->assertTrue($pages, $truth);
    }

    function test_filter()
    {
        $pagelist = new PluginSonotsPagelist($this->pages);
        $pattern = 'aa';
        $pagelist->grep_by('page', 'ereg', $pattern);
        $pages = $pagelist->get_metas('page');
        $truth = array 
            (
             2 => 'test/a/aa',
             3 => 'test/a/aa/aaa',
             );
        $this->assertTrue($pages, $truth);
    }

    function test_except()
    {
        $pagelist = new PluginSonotsPagelist($this->pages);
        $pattern = 'aa';
        $pagelist->grep_by('page', 'ereg', $pattern, TRUE);
        $pages = $pagelist->get_metas('page');
        $truth = array 
            (
             0 => 'test',
             1 => 'test/a',
             4 => 'test/a/bb/bbb',
             5 => 'test/c/cc/ccc',
             );
        $this->assertTrue($pages, $truth);
    }

    function test_newpage()
    {
    }

    function test_depth()
    {
        $pagelist = new PluginSonotsPagelist($this->pages);
        $pagelist->gen_metas('depth');
        $depths = $pagelist->get_metas('depth');
        $truth = array 
            (
             0 => 1,
             1 => 2,
             2 => 3,
             3 => 4,
             4 => 4,
             5 => 4,
             );
        $this->assertTrue($depths, $truth);
        // do not use negative interval for depth
        $depth = PluginSonotsOption::parse_interval('2:3');
        list($offset, $length) = $depth;
        list($min, $max) = PluginSonotsOption::conv_interval(array($offset, $length), array(1, PHP_INT_MAX));
        $pagelist->grep_by('depth', 'ge', $min);
        $pagelist->grep_by('depth', 'le', $max);
        $pages = $pagelist->get_metas('page');
        $truth = array 
            (
             1 => 'test/a',
             2 => 'test/a/aa',
             );
        $this->assertTrue($pages, $truth);

        // depth measures relname
        $pagelist = new PluginSonotsPagelist($this->pages);
        $prefix = 'test/a/';
        $pagelist->grep_by('page', 'prefix', $prefix);
        $pagelist->gen_metas('relname', array(sonots::get_dirname($prefix)));
        $pagelist->gen_metas('depth');
        $depths = $pagelist->get_metas('depth');
        $truth = array 
            (
             2 => 1,
             3 => 2,
             4 => 2,
             );
        $this->assertTrue($depths, $truth);
    }

    function test_leaf()
    {
        //$pagelist = new PluginSonotsPagelist($this->pages);
        //$pagelist->gen_metas('leaf'); // get_existpage()
        //$pagelist->grep_by('leaf', 'eq', TRUE);
    }

    function test_popular()
    {
    }

    function test_sort()
    {
    }

}

?>

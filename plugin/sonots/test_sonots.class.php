<?php
// error_reporting(0); // nothing
error_reporting(E_ERROR | E_PARSE); // Avoid E_WARNING, E_NOTICE, etc
// error_reporting(E_ALL);
require_once('simpletest/autorun.php');
require_once('sonots.class.php');
require_once('pukiwiki.php');

class Test_sonots extends UnitTestCase
{
    function test_natcasesort_filenames()
    {
        $filenames = array(
                           2 => 'hoge/',
                           3 => 'hoge/hoge/moge',
                           5 => 'hoge/hoge',
                           6 => 'hogehoge',
                           7 => 'soge',
                           9 => 'hoge/hoge/hoge',
                           );
        sonots::natcasesort_filenames($filenames);
        $truth = array (
                        2 => 'hoge/',
                        5 => 'hoge/hoge',
                        9 => 'hoge/hoge/hoge',
                        3 => 'hoge/hoge/moge',
                        6 => 'hogehoge',
                        7 => 'soge',
                        );
        $this->assertEqual($filenames, $truth);
    }

    function test_compact_list()
    {
        $levels = array(1,3,1,1,3,2,3);
        $parse = sonots::compact_list($levels);
        $truth = array(1,2,1,1,2,2,3);
        $this->assertEqual($parse, $truth);

        $levels = array(1,3,1,1,3,3,3);
        $parse = sonots::compact_list($levels);
        $truth = array(1,2,1,1,2,2,2);
        $this->assertEqual($parse, $truth);
    }

    function test_get_convert_html()
    {
    }

    function test_display_password_form()
    {
    }

    function test_parse_options()
    {
        $args = array('str=hoge','bool');
        $parse = sonots::parse_options($args);
        $truth = array('str'=>'hoge','bool'=>TRUE);
        $this->assertEqual($parse, $truth);
        
        $conf_options = array('str'=>'foobar','bool'=>FALSE); // default
        $args = array('bool','unknown=hoge');
        $parse = sonots::parse_options($args, $conf_options);
        $truth = array('str'=>'foobar','bool'=>TRUE); // unknown is not set
        $this->assertEqual($parse, $truth);
    }

    function test_get_tree()
    {
        $pages = array
            (
             'test/a',
             'test/a/aa',
             'test/a/aa/aaa',
             'test/a/bb/bbb',
             'test/c/cc/ccc',
             'test/c',
             );
        $parse = sonots::get_tree($pages);
        $truth = array 
            (
             'test/a' => false,
             'test/a/aa' => false,
             'test/a/aa/aaa' => true,
             'test/a/bb/bbb' => true,
             'test/c' => false,
             'test/c/cc/ccc' => true,
             );
        $this->assertEqual($parse, $truth);
    }

    function test_make_inline()
    {
    }

    function test_remove_multilineplugin_lines()
    {
    }


    function test_array_slice()
    {
        $array = range(1,10);
        $parse = sonots::array_slice($array, 0);
        $truth = range(1,10);
        $this->assertIdentical($parse,$truth);

        $parse = sonots::array_slice($array, 0, 1);
        $truth = range(1, 1);
        $this->assertIdentical($parse,$truth);

        $parse = sonots::array_slice($array, 9, null);
        $truth = range(10, 10);
        $this->assertIdentical($parse,$truth);
        $this->assertIdentical(array_keys($parse),array_keys($truth));

        $parse = sonots::array_slice($array, 9, null, true);
        $truth = array(9=>10);
        $this->assertIdentical($parse,$truth);
        $this->assertIdentical(array_keys($parse),array_keys($truth));
    }

    function test_array_to_string()
    {
        $arr = array('A', 'B', 'indC' => 'C', array('D', 'E'), 'indF'=>'F');
        $parse = sonots::array_to_string($arr);
        $truth = 'A,B,indC:C,(D,E),indF:F';
        $this->assertEqual($parse, $truth);
        $arr = (array)'A';
        $parse = sonots::array_to_string($arr);
        $truth = 'A';
        $this->assertEqual($parse, $truth);
        $arr = array();
        $parse = sonots::array_to_string($arr);
        $truth = '';
        $this->assertEqual($parse, $truth);
    }

    function test_string_to_array()
    {
        $str = 'A,B,indC:C,(D,E),indF:F';
        $parse = sonots::string_to_array($str);
        $truth = array('A', 'B', 'indC' => 'C', array('D', 'E'), 'indF'=>'F');
        $this->assertEqual($parse, $truth);
        $str = '';
        $parse = sonots::string_to_array($str);
        $truth = array();
        $this->assertEqual($parse, $truth);
    }

}

?>
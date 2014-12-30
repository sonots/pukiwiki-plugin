<?php
// $Id: xxx.php 137 2006-08-03 15:46:02Z sonots $

require_once 'PHPUnit.php';

define('PLUGIN_UNITTEST', true);
require_once PLUGIN_DIR . 'xxx.inc.php';
class PluginXxxUnitTest extends PluginXxx {
    function PluginXxxUnitTest()
    {
        parent::PluginXxx();
        // codes for testing
    }    
    // override functions here for testing
}

class test_xxx extends PHPUnit_TestCase {
    function test_xxx($name)
    {
        $this->PHPUnit_TestCase($name);
    }

    function setUp()
    {
    }
 
    function tearDown()
    {
    }

    function test_exist()
    {
        $this->assertTrue( exist_plugin('xxx') ); 
    }

    function test_exist_convert()
    {
        $this->assertTrue( exist_plugin_convert('xxx') ); 
    }
    
    function test_exist_inline()
    {
        $this->assertTrue( exist_plugin_inline('xxx') );
    }
    
    function test_invalid()
    {
        $lines = array();
        $lines[] = '#xxx(fatfat)';
        $this->assertContains( 'xxx(): No such a option, fatfat.', convert_html($lines) );

        $lines = array();
        $lines[] = '#xxx(switch=ho)';
        $this->assertRegExp( '/' . preg_quote('xxx(): switch=ho is invalid') . '/', convert_html($lines) );

        $lines = array();
        $lines[] = '#xxx(mode=four)';
        $this->assertRegExp( '/' . preg_quote('xxx(): mode=four is invalid') . '/', convert_html($lines) );

        $lines = array();
        $lines[] = '#xxx(depth=help)';
        $this->assertRegExp( '/' . preg_quote('xxx(): depth=help is invalid') . '/', convert_html($lines) );

        $lines = array();
        $lines[] = '#xxx(depth=5:7,1:5)';
        $this->assertRegExp( '/' . preg_quote('xxx(): No such a option, 1:5') . '/', convert_html($lines) );
        
        $lines = array();
        $lines[] = '#xxx(matches=(number=2,depth=(1:2,(3)))';
        $this->assertRegExp( '/' . preg_quote('xxx(): The # of open and close parentheses') . '/', convert_html($lines) );

        $lines = array();
        $lines[] = '#xxx(plural=four)';
        $this->assertRegExp( '/' . preg_quote('xxx():') . '/', convert_html($lines));
    }
    
    function test_number()
    {
        global $plugin_xxx;
        
        $lines = array();
        $lines[] = '#xxx(depth=13)';
        convert_html($lines);
        $this->assertEquals( array(13), $plugin_xxx->options['depth'][1] );

        $lines = array();
        $lines[] = '#xxx(depth=1+3)';
        convert_html($lines);
        $this->assertEquals( array(1,2,3,4), $plugin_xxx->options['depth'][1] );

        $lines = array();
        $lines[] = '#xxx(depth=18:-1)';
        convert_html($lines);
        $this->assertEquals( array(18,19,20), $plugin_xxx->options['depth'][1] );

        $lines = array();
        $lines[] = '#xxx(depth=-1:-3)';
        convert_html($lines);
        $this->assertEquals( array(18,19,20), $plugin_xxx->options['depth'][1] );

        $lines = array();
        $lines[] = '#xxx("depth=1,2,3")';
        convert_html($lines);
        $this->assertEquals( array(1,2,3), $plugin_xxx->options['depth'][1] );

        $lines = array();
        $lines[] = '#xxx(depth=(1,2,3))';
        convert_html($lines);
        $this->assertEquals( array(1,2,3), $plugin_xxx->options['depth'][1] );
    }
    
    function test_array()
    {
        global $plugin_xxx;

        $lines = array();
        $lines[] = '#xxx(matches=(number=2,depth=(1:2,3)))';
        convert_html($lines);
        $this->assertEquals( array('number=2','depth=(1:2,3)'), $plugin_xxx->options['matches'][1] );
    }

    function test_enumarray()
    {
        global $plugin_xxx;

        $lines = array();
        $lines[] = '#xxx(plural=(one,two))';
        convert_html($lines);
        $this->assertEquals( array('one', 'two'), $plugin_xxx->options['plural'][1] );

        $lines = array();
        $lines[] = '#xxx(plural=one)';
        convert_html($lines);
        $this->assertEquals( array('one'), $plugin_xxx->options['plural'][1] );
    }
}
?>

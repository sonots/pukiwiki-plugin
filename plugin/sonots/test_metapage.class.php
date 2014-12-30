<?php
// error_reporting(0); // nothing
error_reporting(E_ERROR | E_PARSE); // Avoid E_WARNING, E_NOTICE, etc
// error_reporting(E_ALL);
require_once('simpletest/autorun.php');
require_once('metapage.class.php');
require_once('sonots.class.php');
require_once('pukiwiki.php');

class Test_PluginSonotsMetapage extends UnitTestCase
{
    function test_relname()
    {
        $this->assertEqual(PluginSonotsMetapage::relname('Hoge/A', ''), 'Hoge/A');
        $this->assertEqual(PluginSonotsMetapage::relname('Hoge/A', sonots::get_dirname('Hoge/')), 'A');
    }

    function test_depth()
    {
        $this->assertEqual(PluginSonotsMetapage::depth('Hoge'), 1);
        $this->assertEqual(PluginSonotsMetapage::depth('Hoge/A'), 2);
    }

    function test_reading()
    {
        $this->assertEqual(PluginSonotsMetapage::reading('Hoge'), 'Hoge');
    }

    function test_filename()
    {
        $this->assertEqual(PluginSonotsMetapage::filename('Hoge'), DATA_DIR . '486F6765.txt');
    }

    function test_timestamp()
    {
        //PluginSonotsMetapage::filename('Hoge');
    }

    function test_date()
    {
        $this->assertEqual(PluginSonotsMetapage::date(1), '1970-01-01 () 00:00:01');
    }

    function test_newdate()
    {
        //$this->assertEqual(PluginSonotsMetapage::newdate(100000), '1970-01-01 () 00:00:01');
    }

    function test_newpage()
    {
    }

    function test_linkstr()
    {
        $this->assertEqual(PluginSonotsMetapage::linkstr('Hoge/A', 'relative', 'Hoge/'), 'A');
        $this->assertEqual(PluginSonotsMetapage::linkstr('Hoge/A', 'basename', 'Hoge/'), 'A');
        $this->assertEqual(PluginSonotsMetapage::linkstr('Hoge/A', 'absolute', 'Hoge/'), 'Hoge/A');
    }

    function test_link()
    {
        $parse = PluginSonotsMetapage::link('Hoge/A', 'A', 'page');
        $assert = '<span class="noexists">A<a href="?cmd=edit&amp;page=Hoge%2FA">?</a></span>';
        $this->assertEqual($parse, $assert);
        $parse = PluginSonotsMetapage::link('Hoge/A', 'A', 'anchor');
        $assert = '<a href="#z758465a6084944c5973bedbbfaf08be9">A</a>';
        $this->assertEqual($parse, $assert);
        $parse = PluginSonotsMetapage::link('Hoge/A', 'A', 'off');
        $assert = 'A';
        $this->assertEqual($parse, $assert);
    }
}

?>

<?php
require_once('simpletest/autorun.php');
require_once('option.class.php');
//error_reporting(E_ALL);

class Test_PluginSonotsOption extends UnitTestCase
{
    function test_parse_interval()
    {
        $this->assertEqual(PluginSonotsOption::parse_interval('1:5', 1), array(0,5));
        $this->assertEqual(PluginSonotsOption::parse_interval('1:5', 0), array(1,5));
        $this->assertEqual(PluginSonotsOption::parse_interval('0:4', 0), array(0,5));
        $this->assertEqual(PluginSonotsOption::parse_interval('0:4', 1), array(0,5)); // may change behavior
        $this->assertEqual(PluginSonotsOption::parse_interval('2:3'), array(1,2));
        $this->assertEqual(PluginSonotsOption::parse_interval('2:'), array(1, null));
        $this->assertEqual(PluginSonotsOption::parse_interval(':3'), array(0,3));
        $this->assertEqual(PluginSonotsOption::parse_interval('4'), array(3,1));
        $this->assertEqual(PluginSonotsOption::parse_interval('-5:', 0), array(-5, null));
        $this->assertEqual(PluginSonotsOption::parse_interval('-5:', 1), array(-5, null));
        $this->assertEqual(PluginSonotsOption::parse_interval(':-5'), array(0, -4));
        $this->assertEqual(PluginSonotsOption::parse_interval('1+2'), array(0,3));
    }

    function test_conv_interval()
    {
        $this->assertEqual(PluginSonotsOption::conv_interval(array(0, 5), array(1, 10)), array(1, 5));
        $this->assertEqual(PluginSonotsOption::conv_interval(array(1, null), array(1, 10)), array(2, 10));
        $this->assertEqual(PluginSonotsOption::conv_interval(array(3, 1), array(1, 10)), array(4, 4));
        $this->assertEqual(PluginSonotsOption::conv_interval(array(-5, null), array(1, 10)), array(6, 10));
        $this->assertEqual(PluginSonotsOption::conv_interval(array(0, -4), array(1, 10)), array(1, 6));
    }

    function test_parse_option_line()
    {
        $result = PluginSonotsOption::parse_option_line('prefix=Hoge/,num=1:5,contents=(num=1,headline)');
        $truth = array('prefix'=>'Hoge/','num'=>'1:5','contents'=>array('num'=>'1','headline'=>true));
        $this->assertEqual($result, $truth);
        $result = PluginSonotsOption::parse_option_line('prefix=Hoge/,linkstr=title,contents= (num=1,headline) ');
        $truth = array('prefix'=>'Hoge/','linkstr'=>'title','contents'=>array('num'=>'1','headline'=>true));
        $this->assertEqual($result, $truth);
        $result = PluginSonotsOption::parse_option_line(' prefix = Hoge/ , linkstr = title , contents = ( num = 1 , headline ) ', TRUE);
        $truth = array('prefix'=>'Hoge/','linkstr'=>'title','contents'=>array('num'=>'1','headline'=>true));
        $this->assertEqual($result, $truth);
        $result = PluginSonotsOption::parse_option_line('prefix=Hoge/,headline,contents=(num=1,headline)');
        $truth = array('prefix'=>'Hoge/','headline'=>TRUE,'contents'=>array('num'=>'1','headline'=>true));
        $this->assertEqual($result, $truth);
        $result = PluginSonotsOption::parse_option_line(',headline,contents=(num=1,headline)');
        $truth = array(''=>TRUE,'headline'=>TRUE,'contents'=>array('num'=>'1','headline'=>true));
        $this->assertEqual($result, $truth);

        $result = PluginSonotsOption::parse_option_line('');
        $truth = array ();
        $this->assertEqual($result, $truth);
        $result = PluginSonotsOption::parse_option_line(',num=1:2');
        $truth = array(
                       '' => true,
                       'num' => '1:2',
                       );
        $this->assertEqual($result, $truth);
    }

    function test_glue_option_line()
    {
        $options = array('prefix'=>'Hoge/','num'=>'1:5','contents'=>array('num'=>'1','headline'=>true));
        $result = PluginSonotsOption::glue_option_line($options);
        $truth = 'prefix=Hoge/,num=1:5,contents=(num=1,headline)';
        $this->assertEqual($result, $truth);
    }


    function test_evaluate_options()
    {
        $conf_options = array
            (
             'hierarchy' => array('bool', true),
             'num'       => array('interval', null),
             'filter'    => array('string', null),
             'sort'      => array('enum', 'name', array('name', 'reading', 'date')),
             );
        $options = array('Hoge/'=>true,'filter'=>'AAA');
        list($options, $unknowns) = PluginSonotsOption::evaluate_options($options, $conf_options);
        $truth = array('hierarchy'=>true,'num'=>null,'filter'=>'AAA','sort'=>'name');
        $this->assertIdentical($options, $truth);
        $truth = array('Hoge/'=>true);
        $this->assertIdentical($unknowns, $truth);

        $conf_options = array
            (
             'hierarchy' => array('bool', true),
             'non_list'  => array('bool', true),
             'reverse'   => array('bool', false), 
             'basename'  => array('bool', false), // obsolete
             'sort'      => array('enum', 'name', array('name', 'reading', 'date')),
             'tree'      => array('enum', false, array(false, 'leaf', 'dir')),
             'depth'     => array('interval', null),
             'num'       => array('interval', null),
             'next'      => array('bool', false),
             'except'    => array('string', null),
             'filter'    => array('string', null),
             'prefix'    => array('string', null),
             'contents'  => array('array', null),
             'include'   => array('array', null),
             'info'      => array('enumarray', null, array('date', 'new')),
             'date'      => array('bool', false), // will be obsolete
             'new'       => array('bool', false),
             'tag'       => array('string', null),
             'linkstr'   => array('enum', 'relative', array('relative', 'absolute', 'basename', 'title', 'headline')),
             'link'      => array('enum', 'page', array('page', 'anchor', 'off')),
             'newpage'   => array('enum', null, array('on', 'except')),
             'popular'   => array('enum', null, array('total', 'today', 'yesterday', 'recent')), // alpha
             );
        $options = array('prefix'=>'Hoge/','num'=>'1:5','contents'=>array('num'=>'1','firsthead'));
        list($result, $unknowns) = PluginSonotsOption::evaluate_options($options, $conf_options);
        $truth = array (
                        'hierarchy' => true,
                        'non_list' => true,
                        'reverse' => false,
                        'basename' => false,
                        'sort' => 'name',
                        'tree' => false,
                        'depth' => null,
                        'num' =>
                        array (
                               0 => 0,
                               1 => 5,
                               ),
                        'next' => false,
                        'except' => null,
                        'filter' => null,
                        'prefix' => 'Hoge/',
                        'contents' =>
                        array (
                               'num' => '1',
                               0 => 'firsthead',
                               ),
                        'include' => null,
                        'info' => null,
                        'date' => false,
                        'new' => false,
                        'tag' => null,
                        'linkstr' => 'relative',
                        'link' => 'page',
                        'newpage' => null,
                        'popular' => null,
                        );
        $this->assertEqual($result, $truth);
        $options[''] = TRUE;
        $options['cmd'] = 'read';
        list($result, $unknowns) = PluginSonotsOption::evaluate_options($options, $conf_options);
        $this->assertEqual($result, $truth);
        $this->assertIdentical($unknowns, array(''=>TRUE,'cmd'=>'read'));

        $conf_options = array(
             'num'       => array('interval', null),
             'except'    => array('string', null),
             'filter'    => array('string', null),
             'title'     => array('enum', 'on',  array('on', 'off', 'nolink', 'basename')), // obsolete
             'titlestr'  => array('enum', 'title', array('name', 'pagename', 'absolute', 'relname', 'relative', 'basename', 'title', 'headline', 'off')),
             'titlelink' => array('bool', true),
             'section'   => array('array', null),
             'permalink' => array('string', null),
             'firsthead' => array('bool', true),
             'section'   => array('options', null, array(
                 'num'       => array('interval',  null),
                 'depth'     => array('interval',  null),
                 'except'    => array('string',  null),
                 'filter'    => array('string',  null),
                 'inclsub'   => array('bool',    false), // not yet
             )),
        );
        $options = array('num'=>'1:5');
        list($result, $unknowns) = PluginSonotsOption::evaluate_options($options, $conf_options);
        $truth = array (
                        'num' =>
                        array (
                               0 => 0,
                               1 => 5,
                               ),
                        'except' => null,
                        'filter' => null,
                        'title' => 'on',
                        'titlestr' => 'title',
                        'titlelink' => true,
                        'section' => null,
                        'permalink' => null,
                        'firsthead' => true,
                        );
        $this->assertIdentical($result, $truth);
        $options = array('num'=>'1:5','section'=>array('num'=>'1'));
        list($result, $unknowns) = PluginSonotsOption::evaluate_options($options, $conf_options);
        $truth['section'] = 
                        array (
                               'num' =>
                               array (
                                      0 => 0,
                                      1 => 1,
                                      ),
                               'depth' => null,
                               'except' => null,
                               'filter' => null,
                               'inclsub' => false,
                               );
        $this->assertIdentical($result, $truth);
    }

}

?>

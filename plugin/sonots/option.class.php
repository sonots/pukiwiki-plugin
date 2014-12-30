<?php
require_once(dirname(__FILE__) . '/sonots.class.php');

/**
 * Advanced Option Parser for PukiWiki Plugin
 *
 * Example1)
 * <code>
 *  function plugin_hoge_convert()
 *  {
 *      $conf_options = array(
 *          'num' => array('number', 100),
 *          'prefix' => array('string', 'Hoge/'),
 *      );
 *      $args = func_get_args();
 *      $line = csv_implode(',', $args);
 *      $options = PluginSonotsOption::parse_option_line($line);
 *      list($options, $unknowns) 
 *          = PluginSonotsOption::evaluate_options($options, $conf_options);
 *  }
 * </code>
 *
 * Example2)
 * <code>
 *  function plugin_hoge_inline()
 *  {
 *      $args = func_get_args();
 *      array_pop($args); // drop {}
 *      $line = csv_implode(',', $args);
 *      $options = PluginSonotsOption::parse_option_line($line);
 *      // no $conf_options is also useful
 *  }
 * </code>
 *
 * Example3)
 * <code>
 *  function plugin_hoge_action()
 *  {
 *      global $vars;
 *      $conf_options = array(
 *          'num' => array('number', 100),
 *          'prefix' => array('string', 'Hoge/'),
 *      );
 *      $options = $vars;
 *      list($options, $unknowns) 
 *          = PluginSonotsOption::evaluate_options($options, $conf_options);
 *  }
 * </code>
 *
 * @package    PluginSonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @author     sonots <http://lsx.sourceforge.jp>
 * @version    $Id: option.class.php,v 1.11 2008-08-19 11:14:46 sonots $
 * @require    sonots    v 1.9
 */
class PluginSonotsOption {
    /**
     * Parse option line as followings:
     *
     * Rule)
     * <code>
     * , is used to separate options.
     * = is used to separate option key (name) and option value.
     * () is used if element is an array.
     * </code>
     *
     * Example)
     * <code>
     *  $line = 'prefix=Hoge/,num=1:5,contents=(num=1,depth=1),hoge';
     *  $options = PluginSonotsOption::parse_option_line($line);
     *  var_export(); 
     *  // array('prefix'=>'Hoge/','num'=>'1:5',
     *  // 'contents'=>array('num'=>'1','depth'=>'1'),'hoge'=>true);
     *  // () becomes an array()
     *  // Option which does not have "=[value]" is set to TRUE anyway. 
     * </code>
     *
     * parse_option_line is upper version of the simple 
     * sonots::parse_options($args).  parse_options does not support 
     * array arguments, but parse_option_line does. 
     * Except array arguments, both should be able to generate 
     * the same results. 
     *
     * FYI) Decoding is required especially when key/val values include
     * delimiter characters, '=', ',', '(', and ')'. Usually use true. 
     *
     * @access public
     * @static
     * @param string $line
     * @param boolean $trim trim option key/val
     * @param boolean $decode perform decode key/val
     * @return array array of options
     * @uses sonots::string_to_array
     * @uses numeric_to_boolean
     * @see glue_option_line
     * @see sonots::parse_options
     * @version $Id: v 1.5 2008-06-07 11:14:46 sonots $
     * @since   v 1.0
     */
    function parse_option_line($line, $trim = false, $decode = true)
    {
        $array = sonots::string_to_array($line, '=', ',', '(', ')', $decode);
        $options = PluginSonotsOption::numeric_to_boolean($array);
        if ($trim) {
            $options = sonots::trim_array($options, true, true);
        }
        return $options;
    }

    /**
     * Recover option line. 
     *
     * FYI) Encoding is required especially when key/val values include
     * delimiter characters, '=', ',', '(', and ')'. Usually use true. 
     *
     * @access public
     * @static
     * @param array $options
     * @param boolean $encode perform encode key/val
     * @return string
     * @uses sonots::array_to_string
     * @uses boolean_to_numeric
     * @see parse_option_line
     * @version $Id: v 1.0 2008-06-07 11:14:46 sonots $
     * @since   v 1.4
     */
    function glue_option_line($options, $encode = true)
    {
        $array = PluginSonotsOption::boolean_to_numeric($options);
        return sonots::array_to_string($array, '=', ',', '(', ')', $encode);
    }

    /**
     * Recover option line, but into GET argument style such as
     *  opt1=val1&opt2=val2&opt3=(a&b)
     * Note that this is not inverse of parse_uri_option_line exactly
     * because parse_uri_option_line assumes input variables are already
     * rawurldecoded, but this performs rawurlencode.
     *
     * @access public
     * @static
     * @param array $options
     * @return string
     * @see glue_option_line
     * @see parse_uri_option_line
     * @version $Id: v 1.0 2008-07-16 11:14:46 sonots $
     * @since   v 1.8
     */
    function glue_uri_option_line($options)
    {
        $array = PluginSonotsOption::boolean_to_numeric($options);
        $string = sonots::array_to_string($array, '=', '&', '(', ')', true);
        $args = explode('&', $string);
        foreach ($args as $i => $arg) {
            $vars = explode('=', $arg);
            $vars = array_map('rawurlencode', $vars);
            $args[$i] = implode('=', $vars);
        }
        return implode('&', $args);
    }

    /**
     * Parse option arguments given by GET. 
     * 1) Assume that the GET argument line was as
     *  opt1=val1&opt2=val2&opt3=(a&b)
     * Thus, now you should have in $vars
     *  array('opt1'=>'val1','opt2'=>'val2','opt3'=>'(a','b)'=>'')
     * or 2) Assume that the GET argument line was as
     *  opt1=val1&opt2=val2&opt3=(a,b)
     * Thus, now you should have in $vars 
     *  array('opt1'=>'val1','opt2'=>'val2','opt3'=>'(a,b)')
     *
     * @access public
     * @static
     * @param array $vars
     * @return array
     * @see parse_option_line
     * @see glue_uri_option_line
     * @version $Id: v 1.0 2008-07-16 11:14:46 sonots $
     * @since   v 1.8
     */
    function parse_uri_option_line($vars)
    {
        $args = array();
        foreach ($vars as $key => $val) {
            $args[] = empty($val) ? $key : $key . '=' . $val;
        }
        $argline = implode(',', $args);
        $options = PluginSonotsOption::parse_option_line($argline);
        return $options;
    }

    /**
     * Convert numeric key element to boolean value element.
     *
     * By string_to_array,
     * <code>$string = 'foo,bar' => array(0=>'foo',1=>'bar').</code>
     * Want options as, 
     * <code>$string = 'foo,bar' => array('foo'=>true,'bar'=>true).</code>
     * Perform this conversion.
     *
     * @access private
     * @static
     * @param array $array
     * @return array
     * @see boolean_to_numeric
     * @version $Id: v 1.0 2008-06-07 11:14:46 sonots $
     * @since   v 1.4
     */
    function numeric_to_boolean($array)
    {
        $options = array();
        foreach ($array as $key => $val) {
            if (is_numeric($key)) {
                $options[$val] = true;
            } elseif (is_array($val)) {
                $options[$key] = PluginSonotsOption::numeric_to_boolean($val);
            } else {
                $options[$key] = $val;
            }
        }
        return $options;
    }

    /**
     * Reverse numeric_to_boolean
     *
     * @access private
     * @static
     * @param array $options
     * @return array $options
     * @see numeric_to_boolean
     * @version $Id: v 1.0 2008-06-07 11:14:46 sonots $
     * @since   v 1.4
     */
    function boolean_to_numeric($options)
    {
        $array = array();
        foreach ($options as $key => $val) {
            if ($val === true) {
                $array[] = $key;
            } elseif (is_array($val)) {
                $array[$key] = PluginSonotsOption::boolean_to_numeric($val);
            } else {
                $array[$key] = $val;
            }
        }
        return $array;
    }

    /**
     * Evaluate options
     *
     * An Example:
     * <code>
     *  $conf_options = array(
     *         // option  => array(Type, Default, Conf)
     *        'hierarchy' => array('bool', true),
     *        'num'       => array('interval', null),
     *        'filter'    => array('string', null),
     *        'sort'      => array('enum', 'name', array('name', 'reading', 'date')),
     *  );
     *  $options = array('filter'=>'AAA');
     *  list($options, $unknowns) = PluginSonotsOption::evaluate_options($options, $conf_options);
     *  var_export($options); // array('hierarchy'=>true,'num'=>null,'filter'=>'AAA','sort'=>'name')
     * </code>
     *
     * Another Example with parse_option_line:
     * <code>
     *  $conf_options = array(
     *         // option  => array(Type, Default, Conf)
     *        'hierarchy' => array('bool', true),
     *        'num'       => array('interval', null),
     *        'filter'    => array('string', null, 'default'),
     *        'sort'      => array('enum', 'name', array('name', 'reading', 'date')),
     *  );
     *  $optline = 'Hoge/,filter,sort=reading';
     *  $options = PluginSonotsOption::parse_option_line($optline);
     *  var_export($options); // array('Hoge/'=>true,'filter'=>true,'sort'=>'reading')
     *  list($options, $unknowns) = PluginSonotsOption::evaluate_options($options, $conf_options);
     *  var_export($options); // array('hierarchy'=>true,'num'=>null,'filter'=>'default','sort'=>'reading')
     *  var_export($unknowns); // array('Hoge/'=>true)
     * </code>
     *
     * How to Write $conf_options:
     * <pre>
     *   $conf_options is an array of array(Type, Default, Conf)
     *
     *   Role of Type)
     *     Specify one of the following types as a string:
     *     - bool      : boolean true or false
     *     - string    : string
     *     - array     : array
     *     - enum      : take only one element of possible values
     *     - enumarray : take only elements in possible values
     *     - number    : number
     *     - interval  : interval string. @see parse_interval for details
     *     - options   : options (will be recursively evaluated inside)
     *
     *   Role of Default)
     *     It is the default value when the option was not specified by users. 
     *
     *   Role of Conf)
     *     Basically, the conf also means a default value, but it is for
     *     when the option was given but the option argument was not given, i.e., 
     *     the case no "=value" in the option line. Let me call it as default2. 
     *     Concurrently, the conf has a different meaning for following types 
     *     and this was the original main role of conf. 
     *     - enum      : list possible values as an array. As default2, 
     *                   the first element of array is used. 
     *     - enumarray : list possible values as an array. As default2, 
     *                   the entire array is used. 
     *     - options   : $conf_options recursively. As default2, 'options' 
     *                   are recursively configured by $conf_options for this
     *                   options, thus, defaults in $conf_options are used. 
     * </pre>
     *
     * @access public
     * @static
     * @param array $options 
     *    $options[$name] = $value
     * @param array $conf_options
     *    $conf_options[$name] = array(type, default, conf)
     * @return array array($options, $unknowns)
     * - $options[$name] = $evaluated_value
     * - $unknowns[$unknown_name] = $value
     * @uses evaluate_option
     * @uses parse_interval
     * @version $Id: v 1.6 2008-06-12 11:14:46 sonots $
     * @since   v 1.0
     */
    function evaluate_options($options, $conf_options)
    {
        $default = array();
        foreach ($conf_options as $key => $tmp) {
            $default[$key] = isset($conf_options[$key][1]) ? $conf_options[$key][1] : null;
        }
        $options = array_merge($default, $options);
        $unknowns = array();
        foreach ($options as $key => $val) {
            if (isset($conf_options[$key])) {
                $type = isset($conf_options[$key][0]) ? $conf_options[$key][0] : null;
                $conf = isset($conf_options[$key][2]) ? $conf_options[$key][2] : null;
                list($options[$key], $unknowns[$key]) = PluginSonotsOption::evaluate_option($val, $type, $conf);
                if (is_null($unknowns[$key])) unset($unknowns[$key]);
            } else {
                unset($options[$key]);
                $unknowns[$key] = $val;
            }
        }
        return array($options, $unknowns);
    }

    /**
     * Evaluate an option
     *
     * Lists of Supported Types)
     * - bool      : boolean true or false
     * - string    : string
     * - array     : array
     * - enum      : take only one element of possible values
     * - enumarray : take only elements in possible values
     * - number    : number
     * - interval  : interval string. See parse_interval for details
     * - options   : options
     *
     * @access private
     * @static
     * @param mixed $val option value. 
     * @param string $type option type
     * @param mixed $conf config. See evaluate_options. 
     * @return array array(evaluated value, invalid value)
     * @uses parse_interval
     * @uses evaluate_options
     * @version $Id: v 1.7 2008-07-30 11:14:46 sonots $
     * @since   v 1.0
     */
    function evaluate_option($val, $type, $conf = null)
    {
        if (is_null($val)) {
            return array(null, null);
        }
        $retval = $unknown = null;
        switch ($type) {
        case 'bool':
            if ($val === true && is_bool($conf)) {
                $retval = $conf;
                break;
            }
            if ($val === false ||
                $val === '0' ||
                $val === 'off' ||
                $val === 'false' ||
                $val === 'FALSE') {
                $retval = false;
            } elseif ($val === true ||
                      $val === '' ||
                      $val === '1' ||
                      $val === 'on' ||
                      $val === 'true' ||
                      $val === 'TRUE') {
                $retval = true;
            } else {
                $unknown = $val;
            }
            break;
        case 'string':
            if ($val === true) { // no "=value" option is set to true at parse_option_line
                $retval = is_string($conf) ? $conf : null;
                break;
            }
            if (is_string($val)) {
                $retval  = $val;
            } else {
                $unknown = $val;
            }
            break;
        case 'array':
            if ($val === true) { // no "=value" option is set to true at parse_option_line
                $retval = is_array($conf) ? $conf : null;
                break;
            }
            $retval = PluginSonotsOption::boolean_to_numeric((array)$val);
            break;
        case 'enum':
            if ($val === true) {
                $retval = is_array($conf) ? reset($conf) : null;
                break;
            }
            if (in_array($val, $conf)) {
                $retval = $val;
            } else {
                $unknown = $val;
            }
            break;
        case 'enumarray': // special array type
            if ($val === true) {
                $retval = is_array($conf) ? $conf : null;
                break;
            }
            $retval = array();
            $unknown = array();
            $val = PluginSonotsOption::boolean_to_numeric((array)$val);
            foreach ($val as $elem) {
                if (in_array($elem, $conf)) {
                    $retval[] = $elem;
                } else {
                    $unknown[] = $elem;
                }
            }
            if (empty($retval)) $retval = null;
            if (empty($unknown)) $unknown = null;
            break;
        case 'options':
            if ($val === true) {
                $val = array();
            }
            list($retval, $unknown) = PluginSonotsOption::evaluate_options($val, $conf);
            if (empty($retval)) $retval = null;
            if (empty($unknown)) $unknown = null;
            break;
        case 'number':
            if ($val === true) {
                $retval = (is_int($conf) || is_float($conf)) ? $conf : null;
                break;
            }
            if (is_numeric($val)) {
                $retval = $val;
            } else {
                $unknown = $val;
            }
            break;
        case 'interval':
            // $conf is default2 or array(default2, start)
            if ($val === true) {
                $val = is_array($conf) ? $conf[0] : $conf;
            }
            $start = is_array($conf) ? $conf[1] : 1;
            $retval = PluginSonotsOption::parse_interval($val, $start);
            if (is_null($retval)) {
                $unknown = $val;
            }
            break;
        default:
            $unknown = $val;
            break;
        }
        return array($retval, $unknown);
    }

    /**
     * Evaluate option value
     *
     * Lists of Types
     *
     * bool:     boolean true or false
     * string:   string
     * array:    array
     * enum:     special string which take only one of collections
     * enumarray special array which take only some of collections
     * number:   number
     * interval: interval string. @uses PluginSonotsOption::parse_interval
     * options:  options. @uses PluginSonotsOption::evaluate_options
     *
     * @access private
     * @static
     * @param mixed $val option value
     * @param string $type option type
     * @param array $conf config (use for enum, enumarray, options)
     * @return array (evaluated options, unknowns)
     * @uses PluginSonotsOption::parse_interval
     * @uses PluginSonotsOption::evaluate_options
     */
    /*
    function evaluate_option($val, $type, $conf = null)
    {
        switch ($type) {
        case 'bool':
            switch ($val) {
            case '0':
            case 'off':
            case 'false':
            case 'FALSE':
                return false;
                break;
            case null:
            case '':
            case '1':
            case 'on':
            case 'true':
            case 'TRUE':
                return true;
                break;
            default:
                return null;
                break;
            }
            break;
        case 'string':
            if (is_string($val)) {
                return $val;
            } else {
                return null;
            }
            break;
        case 'array':
            return (array)$val;
            break;
        case 'enum': // special string type
            if ($val === '') {
                return $conf[0];
            } elseif (is_string($val)) {
                if (in_array($val, $conf)) {
                    return $val;
                }
            } else {
                return null;
            }
            break;
        case 'enumarray': // special array type
            $val = (array)$val;
            foreach ($val as $elem) {
                if (! in_array($elem, $conf)) {
                    return null;
                }
            }
            return $val;
            break;
        case 'options':
            list($val, $unknowns) = PluginSonotsOption::evaluate_options($val, $conf);
            return $val;
            break;
        case 'number':
            if (is_numeric($val)) {
                return $val;
            } else {
                return null;
            }
            break;
        case 'interval':
            return PluginSonotsOption::parse_interval($val);
            break;
        default:
            return $val;
        }
    }*/

    /**
     * Parse an interval num string
     *
     * Example)
     * <code>
     * 1:5   means 1st to 5th returns array(0, 5)
     * 2:3   means 2nd to 3rd returns array(1, 2)
     * 2:    means 2nd to end returns array(1, null)
     * :3    means 1st to 2rd returns array(0, 3)
     * 4     means 4th returns array(3, 1)
     * -5:   means last 5th to end returns array(-5, null)
     * :-5   means 1st to last 5th returns array(0, -4)
     * 1+2   means 1st to 3rd returns array(0, 3)
     * </code>
     *
     * @access public
     * @static
     * @param string $interval
     * @param int $start 0|1. Tell where is the offset 0. Default is 1 as example.
     * @return mixed array($offset, $length) or null
     * @see array_slice
     * @see array_splice
     * @see conv_interval
     * @version $Id: v 1.1 2008-07-17 11:14:46 sonots $
     * @since   v 1.0
     */
    function parse_interval($interval, $start = 1)
    {
        if ($start != 1 && $start != 0) return null;
        $mini = 1; 
        if (strpos($interval, ':') !== false) {
            list($min, $max) = explode(':', $interval, 2);
            if (is_numeric($min)) {
                $min = (int)$min;
            } else {
                $min = $mini;
            }
            if (is_numeric($max)) {
                $max = (int)$max;
                $len = $max - $min + 1;
                if ($len == -1) $len = null;
                if ($len < 0) $len++;
            } else {
                $len = null;
            }
        } elseif (strpos($interval, '+') !== false) {
            list($min, $len) = explode("+", $interval, 2);
            if (is_numeric($min)) {
                $min = (int)$min;
            } else {
                $min = $mini;
            }
            if (is_numeric($len)) {
                $len = (int)$len + 1;
            } else {
                $len = null;
            }
        } else {
            if (is_numeric($interval)) {
                $min = (int)$interval;
                $len = 1;
            } else {
                return null;
            }
        }
        if ($min >= $start) $min -= $start;
        return array($min, $len);
    }


    /**
     * Convert ($offset, $length) interval form
     *   to ($start, $end) interval form.
     *
     * Example)
     * <code>
     *  Assume min = 1, max = 10
     *  array(0, 5) to array(1, 5)
     *  array(1, null) to array(2, 10)
     *  array(3, 1) to array(4, 4)
     *  array(-5, null) to array(6, 10)
     *  array(0, -4) to array(1, 6)
     * </code>
     *
     * @access public
     * @static
     * @param array $interval array(int $offset, int $length)
     * @param array $entire array(int $min, int $max)
     * @return array array(int $start, int $end)
     * @see range
     * @see parse_interval
     * @version $Id: v 1.1 2008-07-17 11:14:46 sonots $
     * @since   v 1.0
     */
    function conv_interval($interval, $entire = array(1, PHP_INT_MAX))
    {
        list($offset, $length) = $interval;
        list($min, $max)       = $entire;
        // minus means index from back
        if ($offset < 0) {
            $start = $offset + $max + 1;
        } else {
            $start = $offset + $min;
        }
        // minus means length from back
        if ($length < 0) {
            $end = $length + $max;
        } elseif ($length > 0) {
            $end = $length + $start - 1;
        } else {
            $end = $max;
        }
        // make sure
        if (! isset($start) || $start < $min) {
            $start = $min;
        }
        if (! isset($end) || $end > $max) {
            $end = $max;
        }
        return array($start, $end);
    }
}

?>

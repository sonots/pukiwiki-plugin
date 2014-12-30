<?php
/**
 * A Plugin to Update Plugins
 *
 * Usage:      index.php&cmd=updateplugin
 *
 * @author     sonots
 * @licence    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fupdateplugin.inc.php
 * @version    $Id: updateplugin.inc.php,v 1.3 2007-01-01 01:58:59Z sonots $
 * @package    plugin
 */

class PluginUpdateplugin
{
    function PluginUpdateplugin()
    {
        // modify here for default values
        static $conf = array(
            'adminonly'     => TRUE,
        );
        static $default_options = array(
            'pcmd'          => '', 
            'pass'          => '',
            'updatelist'    => array(),
            'filename'      => '',
            'url'           => '',
            'lastmod'       => 0,
            'path'          => '',
            'contents'      => '',
        );
        $this->conf = & $conf;
        $this->default_options = & $default_options;

        // init
        $this->options = $this->default_options;
        $this->view =  new PluginUpdatepluginView(&$this);
    }

    // static
    var $conf = array();
    var $default_options = array();
    // var
    var $error = '';
    var $plugin = 'updateplugin';
    var $options;
    var $view;

    function action()
    {
        set_time_limit(0);
        $this->set_options();
        return $this->body();
    }

    function set_options()
    {
        global $vars;
        foreach ($this->options as $key => $val) {
            $this->options[$key] = isset($vars[$key]) ? $vars[$key] : '';
        }
        foreach ($this->options['updatelist'] as $key => $data) {
            if ($data['check'] !== 'on') {
                unset($this->options['updatelist'][$key]);
            }
        }
        switch ($vars['submit']) {
        case 'Subscribe & Update':
            $this->options['pcmd'] = 'preview';
            break;
        case 'Update each if new':
            $this->options['pcmd'] = 'update_all_preview';
            break;
        }
    }

    function body()
    {
        foreach ($this->options as $key => $val) {
            ${$key} = $val;
        }

        if ($pcmd == '') {
            $body = $this->view->showform();
        } elseif (! $this->view->login()) { // auth::check_role('role_adm_contents')
            $body = $this->view->showform('The password is wrong.');
        } else {
            global $vars;
            if ($pcmd === 'preview') {
                if ($this->options['url'] === '' && $this->options['contents'] === '') {
                    // get contents
                    $this->options['path'] = $this->get_path($this->options['filename']);
                    $this->options['contents'] = file_get_contents($this->options['path']);
                    $body = $this->view->showform();
                } else {
                    // preview
                    $this->options = $this->get_info($this->options);
                    if ($this->options['contents'] === FALSE) {
                        $body = $this->view->showform('<p><b>Failed to download</b></p>');
                    } else {
                        $body = $this->view->showpreview();
                        // subscribe
                        $updatelist = array();
                        $updatelist[$this->options['filename']] = &$this->options;
                        $updatelist[$this->options['filename']]['lastmod'] = 0;
                        $this->update_list($updatelist);
                    }
                }
            } elseif ($pcmd === 'update') {
                if ($this->update($this->options)) {
                    $msg = htmlspecialchars($this->options['path']) . ' was updated.';
                    // update list to update lastmod
                    $updatelist = array();
                    $updatelist[$this->options['filename']] = &$this->options;
                    $this->update_list($updatelist);
                } else {
                    $msg = 'Failed to update ' . htmlspecialchars($this->options['path']);
                }
                $body = $this->view->showform($msg);
            } elseif ($pcmd == 'update_all_preview') {
                $this->options['updatelist'] = $this->update_all_preview($this->options['updatelist']);
                if (empty($this->options['updatelist'])) {
                    $msg = 'Nothing to be updated.';
                    $body = $this->view->showform($msg);
                } else {
                    $body = $this->view->showlistpreview();
                }
            } elseif ($pcmd == 'update_all') {
                list($updated, $failed) = $this->update_all($this->options['updatelist']);
                if (empty($updated) && empty($failed)) {
                    $msg = 'Nothing was updated';
                } else {
                    $msg = '';
                    foreach ($updated as $path) {
                        $msg .= htmlspecialchars($path) . ' was updated.' . "<br />\n";
                    }
                    foreach ($failed as $path) {
                        $msg .= 'Failed to update ' . htmlspecialchars($path) . "<br />\n";
                    }
                }
                $body = $this->view->showform($msg);
            }
        }
        return array('msg'=>$this->plugin, 'body'=>$body);
    }

    function update_all_preview(&$updatelist)
    {
        // update inputted items
        $this->update_list($updatelist);

        foreach ($updatelist as $key => $data) {
            if (($lastmod = $this->is_new_available($data)) === FALSE) {
                unset($updatelist[$key]);
            } else {
                // $updatelist[$key]['lastmod'] = $lastmod;
            }
        }
        return $updatelist;
    }

    function update_all(&$updatelist)
    {
        $update = $failed = array();
        foreach (($tmp = $updatelist) as $key => $data) {
            $data = $this->get_info($data);
            if ($this->update($data)) {
                $updated[$key] = $data['path'];
                $updatelist[$key] = $data;
                // update list each time as php timeout may happen
                $this->update_list($updatelist); // lastmod
            } else {
                $failed[$key]  = $data['path'];
            }
        }
        // $this->update_list($updatelist); // lastmod
        return array($updated, $failed);
    }

    function is_new_available(&$data)
    {
        $keys = array('url', 'lastmod');
        foreach ($keys as $key) {
            ${$key} = isset($data[$key]) ? $data[$key] : '';
        }
        $prev_lastmod = $lastmod;
        if (($headers = get_headers($url, 1)) === FALSE) {
            return FALSE;
        }
        if (strpos($headers[0], '404 Not Found') !== FALSE) {
            return FALSE;
        }
        $loop = 0;
        while (isset($headers['Location']) && $loop++ < 4) {
            $url = $headers['Location'];
            $headers = get_headers($url, 1);
        }
        $lastmod = $this->http_lastmodified($url, $headers);
        if ($lastmod > $prev_lastmod) {
            return $lastmod;
        } else {
            return FALSE;
        }
    }

    function get_info($data)
    {
        $keys = array('filename', 'url', 'contents', 'path', 'lastmod');
        foreach ($keys as $key) {
            ${$key} = isset($data[$key]) ? $data[$key] : '';
        }
        if ($url !== '') {
            $headers = get_headers($url, 1);
            $loop = 0;
            while (isset($headers['Location']) && $loop++ < 4) {
                $url = $headers['Location'];
                $headers = get_headers($url, 1);
            }

            // filename
            if ($filename === '') {
                $filename = $this->http_filename($url, $headers);
                if (($pos = strpos($filename, '.inc.php')) !== FALSE) {
                    $filename = substr($filename, 0, $pos+8); 
                }
            }

            // contents
            if ($contents === '') {
                $contents = file_get_contents($url);
            }

            // lastmod
            $lastmod = $this->http_lastmodified($url, $headers);
        } else {
            $lastmod = time();
        }

        $path = $this->get_path($filename);

        foreach ($keys as $key) {
            $data[$key] = ${$key};
        }
        return $data;
    }

    function update(&$data)
    {
        $keys = array('filename', 'url', 'contents', 'path', 'lastmod');
        foreach ($keys as $key) {
            ${$key} = isset($data[$key]) ? $data[$key] : '';
        }

        if ($path === '' || $contents === '' || $contents === FALSE) {
            return false;
        }
        if (! $this->file_put_contents($path, $contents)) {
            return false;
        }
        return true;
    }

    function get_path($filename)
    {
        // EXT_PLUGIN_DIR for plus!
        return (defined('EXT_PLUGIN_DIR') ? EXT_PLUGIN_DIR : PLUGIN_DIR) . $filename;
    }

    function update_list(&$updatelist)
    {
        static $currentlist = array();
        if (empty($currentlist)) $currentlist = $this->read_updatelist();

        // only filename, url, lastmod
        $keys = array('filename', 'url', 'lastmod');
        foreach ($updatelist as $filename => $data) {
            $shrink = array();
            foreach ($keys as $key) {
                $shrink[$key] = isset($data[$key]) ? $data[$key] : '';
            }
            $updatelist[$filename] = $shrink;
        }

        // update
        foreach ($updatelist as $key => $data) {
            $currentlist[$key] = $data;
        }
        // delete if no filename
        foreach ($currentlist as $key => $data) {
            if ($data['filename'] == '') {
                unset($currentlist[$key]);
                unset($updatelist[$key]);
            }
        }
        return $this->write_updatelist($currentlist);
    }

    function write_updatelist($list)
    {
        $datfile = CACHE_DIR . 'updateplugin.dat';
        $contents = '';
        foreach ($list as $plugin => $info) {
            $metas = array();
            foreach ($info as $key => $val) {
                $metas[] = "$key=$val";
            }
            $contents .= csv_implode(',', $metas) . "\n";
        }
        return $this->file_put_contents($datfile, $contents);
    }

    function read_updatelist()
    {
        $datfile = CACHE_DIR . 'updateplugin.dat';
        $lines = file($datfile);
        $list = array();
        foreach ($lines as $line) {
            $metas = csv_explode(',', rtrim($line));
            $data = array();
            foreach ($metas as $meta) {
                list($key, $val) = explode('=', $meta, 2);
                $data[$key] = $val;
            }
            $list[$data['filename']] = $data;
        }
        return $list;
    }

    // PKWK extension
    function http_lastmodified($url, $headers)
    {
        if (isset($headers['Last-Modified'])) {
            return strtotime($headers['Last-Modified']);
        }
        // pkwk attach (cmd=attach&pcmd=info)
       $parsed = parse_url($url);
       parse_str($parsed['query'], $queries);
       if ($queries['cmd'] !== 'attach' && $queries['plugin'] !== 'attach') {
           return time();
       }
       $queries['pcmd'] = 'info';
       if (isset($queries['openfile'])) {
           $queries['file'] = $queries['openfile'];
           unset($queries['openfile']);
       }
       $parsed['query'] = $this->glue_str($queries);
       $infourl = $this->glue_url($parsed);

       // GET
       $source = file_get_contents($infourl);
       $matches = array();
       // dependent on the output of attach plugin
       $pattern = '/ <dd>[^:]+:(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2})<\/dd>/';
       if (preg_match($pattern, $source, $matches)) {
           return strtotime($matches[1]);
       } else {
           return time();
       }
    }

    // PHP extension
    // reverse parse_str
    function glue_str($arrayInput) 
    {
        if (! is_array($arrayInput))
            return false;
        
        $url_query="";
        foreach ($arrayInput as $key=>$value) {
            
            $url_query .=(strlen($url_query)>1)?'&':"";
            $url_query .= urlencode($key).'='.urlencode($value);
        }
        return $url_query;
    }
    // reverse parse_url
    function glue_url($parsed) 
    {
        if (! is_array($parsed))
            return false;
        
        $url = $parsed['scheme'] ? $parsed['scheme'].':'
            .((strtolower($parsed['scheme']) == 'mailto') ? '':'//'): '';
        $url .= $parsed['user'] ? $parsed['user']
            .($parsed['pass']? ':'.$parsed['pass']:'').'@':'';
        $url .= $parsed['host'] ? $parsed['host'] : '';
        $url .= $parsed['port'] ? ':'.$parsed['port'] : '';
        $url .= $parsed['path'] ? $parsed['path'] : '';
        $url .= $parsed['query'] ? '?'.$parsed['query'] : '';
        $url .= $parsed['fragment'] ? '#'.$parsed['fragment'] : '';
        return $url;
    }

    // PHP5 has it, though
    function file_put_contents($path, $contents)
    {
        $fp = fopen($path, "w");
        if ($fp === FALSE) { return FALSE; }
        fwrite($fp, $contents);
        fclose($fp);
        return TRUE;
    }

    function http_filename($url, $headers)
    {
        if (isset($headers['Content-Disposition'])) {
            $matches = array();
            if (preg_match('/; *filename="?([^"]+)"?;?/', 
                           $headers['Content-Disposition'], $matches)) {
                return $matches[1];
            }
        }
        if (isset($headers['Content-Type'])) {
            $matches = array();
            if (preg_match('/; *name="?([^"]+)"?;?/', 
                           $headers['Content-Type'], $matches)) {
                return $matches[1];
            }
        }
        $parse = parse_url($url);
        $filename = basename($parse['path']);
        return $filename;
    }
}

//////////////////////////////////
class PluginUpdatepluginView
{
    var $admincookie = 'adminpass';
    var $plugin = 'updateplugin';
    var $options;
    var $conf;
    var $model;

    function PluginUpdatepluginView($model)
    {
        $this->model   = &$model;
        $this->options = &$model->options;
        $this->conf    = &$model->conf;
    }

    function login()
    {
        if ($this->conf['adminonly'] === FALSE) return TRUE;
        global $vars;
        $pass = isset($vars['pass']) ? $vars['pass'] : $this->get_admincookie();
        if (pkwk_login($pass)) {
            $this->set_admincookie($pass);
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function get_admincookie()
    {
        return isset($_COOKIE[$this->admincookie]) ? $_COOKIE[$this->admincookie] : "";
    }
            
    function set_admincookie($pass)
    {
        global $script;
        $path = dirname($scirpt);
        setcookie($this->admincookie, $pass, 0, $path);
        $_COOKIE[$this->admincookie] = $pass;
    }

    function showpreview()
    {
        global $script;
        unset($this->options['pass']);
        unset($this->options['pcmd']);
        $contents = $this->options['contents'];
        unset($this->options['contents']);

        $form = array();
        $form[] = '<form action="' . $script . '?cmd=updateplugin" method="post">';
        $form[] = '<div>';
        $form[] = ' Filename: <b>' . htmlspecialchars($this->options['path']) . '</b><br />' . "\n";
        $form[] = ' Last-Modified: <b>' . htmlspecialchars(date('r', $this->options['lastmod'])) . '</b>';
        $form[] = ' <textarea name="contents" rows="20" cols="60" style="width:100%;">' . htmlspecialchars($contents) . '</textarea><br />';
        $form[] = ' <input type="hidden" name="cmd"  value="updateplugin" />';
        $form[] = ' <input type="hidden" name="pcmd"  value="update" />';
        foreach ($this->options as $key => $val) {
            $form[] = ' <input type="hidden" name="' . $key . '" value="' . htmlspecialchars($val) . '" />';
        }
        $form[] = ' Do you want to update? ';
        $form[] = ' <input type="submit" name="submit" value="Yes" />';
        $form[] = ' <a href="' . $script . '?cmd=updateplugin">No</a>';
        $form[] = '</div>';
        $form[] = '</form>';
        $form = implode("\n", $form);
        return $msg . $form;
    }

    function showform($msg = "")
    {
        global $script;
        foreach ($this->options as $key => $val) {
            ${$key} = htmlspecialchars($val);
        }

        if ($this->conf['adminonly']) {
            $pass = $this->get_admincookie();
        }
        // I do not like the heredocument style (want to use perl's qq!)
        $form = array();
        $form[] = '<form action="$script?cmd=updateplugin" method="post">';

        $form[] = '<div>';
        $form[] = ' <input type="hidden" name="cmd"  value="updateplugin" />';
        //$form[] = ' <input type="hidden" name="pcmd"  value="preview" />';
        if ($this->conf['adminonly']) {
            $form[] = ' <input type="password" name="pass" size="12" value="$pass" />Admin Pasword<br />';
        }
        $form[] = ' <input type="text" name="url" size="60" style="width:75%;" value="$url" />Download URL<br />';
        $form[] = ' <input type="text" name="filename" size="24" value="$filename" />Plugin Filename (can be omitted)';
        $form[] = ' <input type="submit" name="submit" value="Subscribe &amp; Update" /><br />';
        $form[] = 'or Paste Source Code<br />';
        $form[] = ' <textarea name="contents" rows="10" cols="60" style="width:100%;">$contents</textarea><br />';
        $form[] = '</div>';

        $form[] = $this->showlist();

        $form[] = '</form>';
        $form = eval('return "' . str_replace('"', '\"', implode("\n", $form)) . '";');
   
        if ($msg != '') {
            $msg = '<p><b>' . $msg . '</b></p>';
        }

        return $msg . $form;
    }

    function showlistpreview()
    {
        global $script;
        unset($this->options['pass']);
        unset($this->options['pcmd']);
        $updatelist = &$this->options['updatelist'];

        $form = array();
        $form[] = '<form action="$script?cmd=updateplugin" method="post">';
        $form[] = '<div>';
        foreach ($updatelist as $plugin => $data) {
            $plugin = htmlspecialchars($plugin);
            foreach ($data as $key => $val) {
                $data[$key] = htmlspecialchars($val);
            }
            $form[] = '<input type="checkbox" name="updatelist[' . $plugin . '][check]" value="on" checked="checked" />';
            $form[] = $plugin . '<br />';
            $form[] = '<input type="hidden" name="updatelist[' . $plugin . '][filename]" value="' . $data['filename'] . '" />';
            $form[] = '<input type="hidden" name="updatelist[' . $plugin . '][url]" value="' . $data['url'] . '" />';
            $form[] = '<input type="hidden" name="updatelist[' . $plugin . '][lastmod]" value="' . $data['lastmod'] . '" />';
        }
        $form[] = ' <input type="hidden" name="cmd"  value="updateplugin" />';
        $form[] = ' <input type="hidden" name="pcmd"  value="update_all" />';
        $form[] = ' Are you sure to update them?';
        $form[] = ' <input type="submit" name="submit" value="Yes" />';
        $form[] = ' <a href="' . $script . '?cmd=updateplugin">No</a>';
        $form[] = '</div>';
        $form[] = '</form>';
        $form = eval('return "' . str_replace('"', '\"', implode("\n", $form)) . '";');
   
        return $form;
    }

    function showlist()
    {
        global $script;
        foreach ($this->options as $key => $val) {
            ${$key} = htmlspecialchars($val);
        }
        $updatelist = $this->model->read_updatelist();
        $form = array();
        $form[] = '<div class="ie5"><table class="style_table" cellspacing="1" border="0"><tbody>';
        $form[] = '<tr><th class="style_th">Update</th><th class="style_th">Filename</th><th class="style_th">URL</th><th class="style_th">Last-Modified</th></tr>';
        foreach ($updatelist as $plugin => $data) {
            $checked = ($data['url'] !== '') ? ' checked="checked"' : '';
            $td = array();
            $td[] = '<input type="checkbox" name="updatelist[' . $plugin . '][check]" value="on"' . $checked . ' />';
            $td[] = '<input type="text" size="12" name="updatelist[' . $plugin . '][filename]" value="' . $data['filename'] . '" />';
            $td[] = '<input type="text" size="48" name="updatelist[' . $plugin . '][url]" value="' . $data['url'] . '" />';
            $td[] = date('r', $data['lastmod']);
            $form[] = '<tr><td class="style_td">' . implode('</td><td class="style_td">', $td) . '</td></tr>';
            $form[] = '<input type="hidden" name="updatelist[' . $plugin . '][lastmod]" value="' . $data['lastmod'] . '" />';
        }
        $form[] = '</tbody></table></div>';
        $form[] = '<div>';
        $form[] = ' <input type="hidden" name="cmd"  value="updateplugin" />';
        //$form[] = ' <input type="hidden" name="pcmd"  value="update_all_preview" />';
        $form[] = ' <input type="submit" name="submit" value="Update each if new" /><br />';
        $form[] = '</div>';
        $form = eval('return "' . str_replace('"', '\"', implode("\n", $form)) . '";');
   
        return $form;
    }
}

// PHP Extension
// get_headers() (> PHP5)
if(!function_exists('get_headers'))
{
   function get_headers($url,$format = 0)
   {
       $url = parse_url($url);
       $end = "\r\n\r\n";
       $url['port'] = empty($url['port']) ? 80 : $url['port'];
       if (($fp = @fsockopen($url['host'], $url['port'], $errno, $errstr, 30)) === FALSE) {
           return FALSE;
       }
       $req  = "GET ".@$url['path']."?".@$url['query']." HTTP/1.1\r\n";
       $req .= "Host: ".@$url['host'].':'.$url['port']."\r\n";
       $req .= "Connection: close\r\n";
       $req .= "\r\n";
       $response  = '';
       if (fwrite($fp, $req) === FALSE) {
           fclose($fp);
           return FALSE;
       }
       while (! feof($fp)) {
           $response .= fgets($fp, 1280);
           if(strpos($response, $end)) {
               break;
           }
       }
       fclose($fp);

       $response = preg_replace("/\r\n\r\n.*\$/", '', $response);
       $response = explode("\r\n", $response);
       if ($format) {
           foreach($response as $i => $val) {
               if(preg_match('/^([a-zA-Z -]+): +(.*)$/', $val, $matches)) {
                   unset($response[$i]);
                   $response[$matches[1]] = $matches[2];
               }
           }
       }
       return $response;
   }
}

//////////////////////////////////
function plugin_updateplugin_common_init()
{
    global $plugin_updateplugin;
    if (class_exists('PluginUpdatepluginUnitTest')) {
        $plugin_updateplugin = new PluginUpdatepluginUnitTest();
    } elseif(class_exists('PluginUpdatepluginUser')) {
        $plugin_updateplugin = new PluginUpdatepluginUser();
    } else {
        $plugin_updateplugin = new PluginUpdateplugin();
    }
}

function plugin_updateplugin_action()
{
    global $plugin_updateplugin; plugin_updateplugin_common_init();
    return call_user_func(array(&$plugin_updateplugin, 'action'));
}

?>

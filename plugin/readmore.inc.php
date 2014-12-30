<?php
/**
 *  Readmore Plugin
 *
 *  This plugin is just an anchor to express 'read more' point like blogs. 
 *  This plugin itself does nothing (ah, outputs anchor #readmore). 
 *
 *  @package    plugin
 *  @license    http://www.gnu.org/licenses/gpl.html GPL v2
 *  @author     sonots <http://lsx.sourceforge.jp>
 *  @link       http://lsx.sourceforge.jp/?Plugin%2Fcontentsx.inc.php
 *  @version    $Id: readmore.inc.php,v 1.1 2008-06-10 07:23:17Z sonots $
 *  @since      sonots/toc    v 1.5
 */
function plugin_readmore_convert()
{
    return '<div class="readmore" style="padding:0px;margin:0px;"><a name="#readmore"></a></div>';
}
?>

<?php
/**
 * Remove outdated files after upgrade -> administration function
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Taggic <taggic@t-online.de>
 */
/******************************************************************************/
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/******************************************************************************/
/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_removeold extends DokuWiki_Admin_Plugin {
    /**
     * return some info
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/plugin.info.txt');
    }
/******************************************************************************/
    /**
     * return prompt for admin menu
     */
    function getMenuText($language) {
        return $this->getLang('admin_removeold');
    }
/******************************************************************************/
    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
        return 20;
    }
/******************************************************************************/
    /**
     * handle user request
     *
     * Initializes internal vars and handles modifications
     *
     * @author Taggic <taggic@t-online.de>
     */
    function handle() {
        global $ID;
    }
/******************************************************************************/
    /**
     * removeold Output function
     *
     * print a table with all found lanuage folders
     *
     * @author  Taggic <taggic@t-online.de>
     */
    function html() {
        global $ID;

        echo '<div id="removeold__manager">'.NL;
        echo '<h1>'.$this->getLang('admin_removeold').'</h1>'.NL;
        echo '<div class="level1">'.NL;

        echo '<div id="removeold__intro">'.NL;
        echo $this->locale_xhtml('help');
        echo '</div>'.NL;

        echo '<div id="removeold__detail">'.NL;
        $this->_html_uinput();
        echo '</div>'.NL;

        $filelist       = $_REQUEST['removeold_w'];
        $dryrun         = $_REQUEST['dryrun'];
        $summary_option = $_REQUEST['summary_only'];
        
        // language given?
        if ($filelist!=false) {
            if (($dryrun==true) && ($summary_option==false)) {
              echo '<br /><div class="level4"><strong><div class="it__standard_feedback">'.$this->getLang('removeold_willmsg').'</div></strong><br />';              
            }
            elseif ($summary_option==false) {
              echo '<br /><div class="level4"><strong><div class="it__standard_feedback">'.$this->getLang('removeold_delmsg').'</div></strong><br />';
            }
            
            $this->_list_removeold_files($filelist, $dryrun, $summary_option);        
        }
        echo '</div>'.NL;
        echo '<div class="footnotes"><div class="fn">'.NL;
        echo '<sup><a id="fn__1" class="fn_bot" name="fn__1" href="#fnt__1">1)</a></sup>'.NL;
        echo $this->getLang('p_include');
        echo '</div></div>'.NL;

        echo '</div>'.NL;
    }
/******************************************************************************/
    /**
     * Display the form with input control to let the user specify
     * the files to be deleted
     *     
     * @author  Taggic <taggic@t-online.de>
     */
    function _html_uinput(){
        global $conf;
        global $ID;

        // load deleted.files from data folder and show it in textarea
        if(is_dir($conf["savedir"])=== false) {
            $deleted_files = file_get_contents(DOKU_INC."/".$conf["savedir"]."/deleted.files");
        }
        else $deleted_files = file_get_contents($conf["savedir"]."/deleted.files");

        if($deleted_files !== "") {
            echo '<div class="level4" id="removeold__input">'.$this->getLang('i_choose').'<br />'.NL;
            echo   '<div class="no">'.NL;
            echo   '<fieldset class="removeold__fieldset"><legend class="removeold_i_legend">'.$this->getLang('i_legend').'</legend>'.NL;
            echo      '<form action="'.wl($ID).'" method="post">';
            echo          '<input type="hidden" name="do" value="admin" />'.NL;
            echo          '<input type="hidden" name="page" value="removeold" />'.NL;
            echo          '<input type="hidden" name="sectok" value="'.getSecurityToken().'" />'.NL;
            echo          '<div class="removeold__divinput">';
            echo            '<textarea type="text" name="removeold_w" class="edit removeold_edit" value="'.$_REQUEST['removeold_w'].'" rows="20" cols="50" />'.$deleted_files.'</textarea><br />'.NL;
            echo            '<input type="checkbox" name="dryrun" checked="checked">&nbsp;'.$this->getLang('i_dryrun').'&nbsp;</input><br />'.NL;
            echo            '<input type="checkbox" name="summary_only" >&nbsp;'.$this->getLang('summary_option').'&nbsp;</input><br />'.NL;
            echo            '<div class="removeold__divright">';
            echo              '<input type="submit" value="'.$this->getLang('btn_start').'" class="button"/>';
            echo            '</div>'.NL;
            echo          '</div>'.NL;
            echo      '</form>'.NL;
            echo   '</fieldset>';
            echo   '</div>'.NL;
            echo '</div>'.NL;
            echo '<div style="clear:both"></div>'.NL;
        }
        else {
          msg("File not found: ".$deleted_files,-1);
        }
    }
    
/******************************************************************************/
  /**
   * This function will loop through the given files.
   * It checks if the entry is not empty or comment and if such a file does exist.
   * If the file does exist then it will be deleted. The result will be stored 
   * into the filelist array for user feddback.         
   */
    function _list_removeold_files($afilelist, $dryrun, $summary_option){ 
        # statistic counters
        # $empty          = count empty lines
        # $comments       = count comments
        # $files_found    = count existing files
        # $files_notFound = count missing files
        # $files_deleted  = count deleted files
        # $files_delError = count files impossible to delete
        
        $filelist = explode(chr(10),$afilelist);
        echo '<div class="level4" id="removeold__input"><p>'.NL;
        
        foreach($filelist as &$file) {
            $file = trim($file);
            # check if item is empty   => continue, do nothing
            if(strlen($file)<1)        { $empty++; continue; }
            # check if item is comment => continue, do nothing
            if(stripos($file,"#")===0) { $comments++; continue; }
            # check if file does exist
            if(file_exists(DOKU_INC.$file)===true) { 
                # delete file (except on dryrun)
                $files_found++;
                $result = $this->getLang('exists');
                                
                if(($dryrun==true) && ($this->is__writable(DOKU_INC.$file)==true)) {
                  $result = $this->getLang('deleted');
                  $files_deleted++;
                }
                elseif(($dryrun==true) && ($this->is__writable(DOKU_INC.$file)===false)) {
                  $result = $this->getLang('delError');
                  $files_delError++;
                }
                
                if($dryrun==false) {
                    $result = unlink(DOKU_INC.$file);
                    if($result === true) {
                        $result = $this->getLang('deleted');
                        $files_deleted++;
                    }
                    else {
                        $result = $this->getLang('delError');
                        $files_delError++;
                    }
                }
            }
            else {
                # file not found
                $result = $this->getLang('not_found');
            }
            # echo file and result
            if($summary_option==false)  {
                echo DOKU_INC.$file."<span style=\"float:right;\">".$result."</span><br />".NL;
            }
            # write log on delete and error if execution mode
            if(($result===$this->getLang('delError')) || ($result === $this->getLang('deleted'))) {
                if($dryrun==false) $this->__removeold_logging($file, $result);
            }
        }
        echo '</p></div><br />'.NL;
        echo '<div class="level4"><strong><div class="it__standard_feedback">'.$this->getLang('removeold_summary').'</div></strong>';
        echo '<div class="level2">'.NL;
        echo '<table><tr><td>'.$this->getLang('exists').': </td><td>'.$files_found.'</td></tr>'.NL;
        echo '<tr><td>'.$this->getLang('deleted').': </td><td>'.$files_deleted.'</td></tr>'.NL;
        echo '<tr><td>'.$this->getLang('delError').' </td><td>'.$files_delError.'</td></tr></table>'.NL;        
        
        echo '</div><br />'.NL;        
    }
/******************************************************************************/
# Since looks like the Windows ACLs bug "wont fix" 
# (see http://bugs.php.net/bug.php?id=27609) 
# alternative function proposed on php.net:
    function is__writable($path) {
        if ($path{strlen($path)-1}=='/')
             return is__writable($path.uniqid(mt_rand()).'.tmp');
         
        if (file_exists($path)) {
             if (!($f = @fopen($path, 'r+')))
                 return false;
             fclose($f);
             return true;
         }
         
        if (!($f = @fopen($path, 'w')))
             return false;
         fclose($f);
         unlink($path);
         return true;
     }
/******************************************************************************/ 
/* logging of deleted files and deletion errors                               */
    function __removeold_logging($file, $result) {
      global $conf;
      $timestamp = date('d/M/Y G:i:s');
      if(is_dir($conf["savedir"])=== false) {
        $log_file = DOKU_INC."/".$conf["savedir"].'/tmp/removeold.log';
      }
      else $log_file = $conf["savedir"].'/tmp/removeold.log';
      
      $record = "[".$timestamp."]".chr(9).$result.chr(9).chr(9).$file.chr(10);
      
      // Save logging records
      $fh = fopen($log_file, 'a+');
      if (!fwrite($fh, $record)) {
        echo "<span style=\"color:red;\">".$this->getLang('ro_err_msg')."</span><br />".NL;
      }
      fclose($fh);
    } 
/******************************************************************************/ 
}

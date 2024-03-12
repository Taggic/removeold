<?php

use dokuwiki\Extension\AdminPlugin;
use dokuwiki\Form\Form;

/**
 * Remove outdated files after upgrade -> administration function
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Taggic <taggic@t-online.de>
 */
class admin_plugin_removeold extends AdminPlugin
{
    /**
     * @inheritdoc
     */
    public function html()
    {
        global $INPUT;

        $helper = new helper_plugin_removeold($this);

        echo $this->locale_xhtml('intro');

        echo '<div class="log">';
        if ($INPUT->bool('exec')) {
            echo '<h2>' . $this->getLang('removeold_delmsg') . '</h2>';
        } else {
            echo '<h2>' . $this->getLang('removeold_willmsg') . '</h2>';

            echo $this->locale_xhtml('precheck');

            echo '<p>';
            $form = new Form();
            $form->addButton('exec', $this->getLang('button'));
            echo $form->toHTML();
            echo '</p>';
        }

        $helper->run(!$INPUT->bool('exec'));
        echo '</div>';
    }

    /**
     * Implement the logging interface
     *
     * @param string $level
     * @param string $msg
     * @return void
     */
    public function log($level, $msg)
    {
        if ($level === 'debug') return; // skip debugging
        echo '<div class="' . $level . '">' . hsc($msg) . '</div>';
    }
}

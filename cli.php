<?php

use dokuwiki\Extension\CLIPlugin;
use splitbrain\phpcli\Options;

/**
 * DokuWiki Plugin removeold (CLI Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 */
class cli_plugin_removeold extends CLIPlugin
{
    /** @inheritDoc */
    protected function setup(Options $options)
    {
        $options->setHelp('Remove outdated files after upgrade');

        $options->registerOption('exec', 'Actually execute the deletion, otherwise only print what would be deleted', 'e');
    }

    /** @inheritDoc */
    protected function main(Options $options)
    {
        $remover = new helper_plugin_removeold($this);
        $remover->run(!$options->getOpt('exec'));
    }
}

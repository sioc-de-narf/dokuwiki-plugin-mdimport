<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;

class action_plugin_mdimport extends ActionPlugin
{
    /** @inheritDoc */
    public function register(EventHandler $controller)
    {
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'handleToolbar');
    }

    public function handleToolbar(Event $event, $param)
    {
        // Log pour vérifier l'appel
        file_put_contents('/tmp/mdimport.log', "handleToolbar called at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

        $event->data[] = array(
            'type'   => 'mdimport',
            'title'  => 'Import Markdown file',
            'icon'   => DOKU_BASE . 'lib/plugins/mdimport/md-icon.png',
            'open'   => '',
            'close'  => '',
            'sample' => 'Import...',
        );
    }
}

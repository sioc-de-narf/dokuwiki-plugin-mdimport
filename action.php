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
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handleAjax');
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

    /**
     * Convert the posted Markdown and return DokuWiki syntax.
     *
     * Answers the `plugin_mdimport` call on lib/exe/ajax.php, so the request
     * goes through DokuWiki's own entry point instead of hitting a standalone
     * script directly.
     */
    public function handleAjax(Event $event, $param)
    {
        if ($event->data !== 'plugin_mdimport') return;

        $event->preventDefault();
        $event->stopPropagation();

        global $INPUT;

        header('Content-Type: text/plain; charset=utf-8');

        if (!checkSecurityToken()) {
            http_response_code(403);
            echo 'Invalid security token.';
            return;
        }

        if (!$INPUT->has('content')) {
            http_response_code(400);
            echo 'Missing content.';
            return;
        }

        require_once __DIR__ . '/MarkdownToDokuWiki.php';

        $converter = new MarkdownToDokuWikiConverter();
        echo $converter->convert($INPUT->str('content'));
    }
}

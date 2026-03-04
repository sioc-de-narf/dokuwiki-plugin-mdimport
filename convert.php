<?php
// convert.php - Endpoint for AJAX conversion from Markdown to DokuWiki
require_once('MarkdownToDokuWiki.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $markdown = $_POST['content'];
    $converter = new MarkdownToDokuWikiConverter();
    $dokuwiki = $converter->convert($markdown);
    echo $dokuwiki;
} else {
    http_response_code(400);
    echo 'Invalid request.';
}

<?php

declare(strict_types=1);

/**
 * Converts Markdown content to DokuWiki syntax.
 *
 * This class processes Markdown line by line, maintaining state for
 * code blocks, tables, lists (with nesting), and paragraphs. It supports:
 * - Headers (levels 1-6)
 * - Bold, italic, inline code
 * - Links and images
 * - Unordered and ordered lists (with indentation)
 * - Tables (with alignment detection for headers)
 * - Code blocks (```)
 * - Blockquotes (simple)
 * - Horizontal rules
 *
 * @license GPL 3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author  sioc-de-narf
 */
class MarkdownToDokuWikiConverter
{
    /** @var bool Whether we are currently inside a code block */
    private bool $inCodeBlock = false;

    /** @var bool Whether we are currently inside a table */
    private bool $inTable = false;

    /** @var array<int, array<int, string>> Rows of the current table */
    private array $tableRows = [];

    /** @var array<int, string> Alignments for each column of the current table */
    private array $tableAlignments = [];

    /** @var array<int, array{indent: int, type: string}> Stack tracking list nesting (indentation and type) */
    private array $listStack = [];

    /** @var array<int, string> Buffer for paragraph lines before they are flushed */
    private array $paragraphBuffer = [];

    /**
     * Remove YAML front matter from the beginning of the document.
     *
     * Detects a block starting with '---' at the very first line,
     * followed by any lines, and ending with '---' or '...'.
     * If such a block is found, it is stripped.
     *
     * @param string $markdown The raw Markdown.
     * @return string Markdown without the front matter.
     */
    private function stripYamlFrontMatter(string $markdown): string
    {
        $lines = explode("\n", $markdown);
        if (count($lines) === 0) {
            return $markdown;
        }

        // Trim leading empty lines to find the first non-empty line
        $firstNonEmpty = 0;
        while ($firstNonEmpty < count($lines) && trim($lines[$firstNonEmpty]) === '') {
            $firstNonEmpty++;
        }

        // If the first non-empty line is exactly '---', we have a front matter candidate
        if ($firstNonEmpty < count($lines) && trim($lines[$firstNonEmpty]) === '---') {
            $endLine = null;
            // Look for the closing '---' or '...' after the opening
            for ($i = $firstNonEmpty + 1; $i < count($lines); $i++) {
                if (trim($lines[$i]) === '---' || trim($lines[$i]) === '...') {
                    $endLine = $i;
                    break;
                }
            }
            // If we found a closing delimiter, remove all lines from start to end (inclusive)
            if ($endLine !== null) {
                $lines = array_slice($lines, $endLine + 1);
                return implode("\n", $lines);
            }
        }

        // No front matter detected, return original
        return $markdown;
    }

    /**
     * Convert Markdown to DokuWiki syntax.
     *
     * @param string $markdown The input Markdown text.
     * @return string The converted DokuWiki text.
     */
    public function convert(string $markdown): string
    {
        // Strip YAML front matter
        $markdown = $this->stripYamlFrontMatter($markdown);

        // Normalize line endings and replace tabs with 4 spaces
        $lines = explode("\n", str_replace(["\r\n", "\r", "\t"], ["\n", "\n", "    "], $markdown));
        $output = [];
        $this->reset();

        $i = 0;
        while ($i < count($lines)) {
            $line = $lines[$i];
            $nextLine = $i + 1 < count($lines) ? $lines[$i + 1] : null;

            // Code block handling
            if (str_starts_with(trim($line), '```')) {
                $this->handleCodeBlock($line, $output);
                $i++;
                continue;
            }
            if ($this->inCodeBlock) {
                $output[] = $line;
                $i++;
                continue;
            }

            // Table detection
            if ($this->isTableStart($line, $nextLine)) {
                $this->parseTable($lines, $i);
                $output[] = $this->renderTable();
                continue;
            }

            // Horizontal rule
            if ($this->isHorizontalRule($line)) {
                $this->flushParagraph($output);
                $output[] = '----';
                $i++;
                continue;
            }

            // Blockquote
            if ($this->isBlockquote($line)) {
                $this->flushParagraph($output);
                $output[] = $this->renderBlockquote($line);
                $i++;
                continue;
            }

            // List item
            if ($this->isListItem($line)) {
                $this->handleList($line, $output);
                $i++;
                continue;
            }

            // Header
            if ($this->isTitle($line)) {
                $this->flushParagraph($output);
                $output[] = $this->renderTitle($line);
                $i++;
                continue;
            }

            // Empty line
            if (trim($line) === '') {
                $this->flushParagraph($output);
                $output[] = '';
                $i++;
                continue;
            }

            // Normal paragraph line
            $this->paragraphBuffer[] = $this->convertInline($line);
            $i++;
        }

        $this->flushParagraph($output);
        $this->closeLists($output);

        return implode("\n", $output);
    }

    /**
     * Reset internal state.
     */
    private function reset(): void
    {
        $this->inCodeBlock = false;
        $this->inTable = false;
        $this->tableRows = [];
        $this->tableAlignments = [];
        $this->listStack = [];
        $this->paragraphBuffer = [];
    }

    /**
     * Handle a code block delimiter (```).
     *
     * @param string   $line   The current line.
     * @param string[] &$output The output array being built.
     */
    private function handleCodeBlock(string $line, array &$output): void
    {
        if (!$this->inCodeBlock) {
            $lang = trim(substr(trim($line), 3));
            $output[] = "<code" . ($lang ? " $lang" : "") . ">";
            $this->inCodeBlock = true;
        } else {
            $output[] = "</code>";
            $this->inCodeBlock = false;
        }
    }

    /**
     * Determine if a line starts a Markdown table.
     *
     * @param string      $line     The current line.
     * @param string|null $nextLine The next line (if any).
     * @return bool True if a table starts here.
     */
    private function isTableStart(string $line, ?string $nextLine): bool
    {
        return strpos($line, '|') !== false && $nextLine && preg_match('/^[\s\|:\-]+$/', $nextLine);
    }

    /**
     * Parse a Markdown table from the current position.
     *
     * @param string[] $lines The whole array of lines.
     * @param int      &$i    Current index (will be advanced to after the table).
     */
    private function parseTable(array $lines, int &$i): void
    {
        $headerLine = $lines[$i++];
        $separatorLine = $lines[$i++];

        // Detect column alignments from separator line
        $this->tableAlignments = array_map(
            fn($part) => match (true) {
                str_starts_with(trim($part), ':') && str_ends_with(trim($part), ':') => 'center',
                str_ends_with(trim($part), ':') => 'right',
                str_starts_with(trim($part), ':') => 'left',
                default => 'left',
            },
            explode('|', trim($separatorLine, '|'))
        );

        $this->tableRows = [$this->parseTableRow($headerLine)];
        while ($i < count($lines) && strpos($lines[$i], '|') !== false && !preg_match('/^[\s\|:\-]+$/', $lines[$i])) {
            $this->tableRows[] = $this->parseTableRow($lines[$i]);
            $i++;
        }
    }

    /**
     * Parse a single Markdown table row into an array of cells.
     *
     * @param string $line The table row line.
     * @return string[] Array of cell contents.
     */
    private function parseTableRow(string $line): array
    {
        return array_map('trim', explode('|', trim($line, '|')));
    }

    /**
     * Render the parsed table as DokuWiki syntax.
     *
     * @return string DokuWiki table representation.
     */
    private function renderTable(): string
    {
        $output = [];
        foreach ($this->tableRows as $rowIndex => $row) {
            $dokuRow = [];
            foreach ($row as $colIndex => $cell) {
                $cell = $this->convertInline($cell);
                $dokuRow[] = ($rowIndex === 0 ? '^ ' : '| ') . $cell . ($rowIndex === 0 ? ' ^' : ' |');
            }
            $output[] = implode('', $dokuRow);
        }
        return implode("\n", $output);
    }

    /**
     * Check if a line is a Markdown list item.
     *
     * @param string $line The line.
     * @return bool True if it's a list item.
     */
    private function isListItem(string $line): bool
    {
        return preg_match('/^\s*([\*\-\+]|\d+\.)\s/', $line) === 1;
    }

    /**
     * Handle a list item line, managing nesting via indentation.
     *
     * @param string   $line   The list item line.
     * @param string[] &$output The output array.
     */
    private function handleList(string $line, array &$output): void
    {
        $this->flushParagraph($output);
        $indent = $this->calculateIndent($line);
        $type = preg_match('/^\s*\d+\.\s/', $line) ? 'ordered' : 'unordered';

        // Close deeper lists if indentation decreased
        while (!empty($this->listStack) && $indent <= $this->listStack[count($this->listStack) - 1]['indent']) {
            array_pop($this->listStack);
        }

        $this->listStack[] = ['indent' => $indent, 'type' => $type];
        $dokuIndent = str_repeat('  ', count($this->listStack) - 1);

        // Remove the list marker and any leading spaces, then convert inline
        $content = $this->convertInline(preg_replace('/^\s*([\*\-\+]|\d+\.)\s+/', '', $line));
        $output[] = $dokuIndent . ($type === 'ordered' ? '- ' : '* ') . $content;
    }

    /**
     * Calculate the indentation level (number of leading spaces) of a line.
     *
     * @param string $line The line.
     * @return int Number of leading spaces.
     */
    private function calculateIndent(string $line): int
    {
        return strlen($line) - strlen(ltrim($line));
    }

    /**
     * Close any remaining open lists (reset stack).
     *
     * @param string[] &$output The output array (unused, kept for consistency).
     */
    private function closeLists(array &$output): void
    {
        $this->listStack = [];
    }

    /**
     * Check if a line is a Markdown header (starts with #).
     *
     * @param string $line The line.
     * @return bool True if it's a header.
     */
    private function isTitle(string $line): bool
    {
        return preg_match('/^(#{1,6})\s+(.+)$/', trim($line)) === 1;
    }

    /**
     * Render a Markdown header as a DokuWiki header.
     *
     * @param string $line The header line.
     * @return string DokuWiki header.
     */
    private function renderTitle(string $line): string
    {
        preg_match('/^(#{1,6})\s+(.+)$/', trim($line), $matches);
        $level = strlen($matches[1]);
        $title = trim($matches[2]);
        $equals = str_repeat('=', 7 - $level);
        return "$equals $title $equals";
    }

    /**
     * Check if a line is a horizontal rule (three or more -, *, _).
     *
     * @param string $line The line.
     * @return bool True if it's a horizontal rule.
     */
    private function isHorizontalRule(string $line): bool
    {
        return preg_match('/^[-*_]{3,}\s*$/', trim($line)) === 1;
    }

    /**
     * Check if a line is a blockquote (starts with >).
     *
     * @param string $line The line.
     * @return bool True if it's a blockquote.
     */
    private function isBlockquote(string $line): bool
    {
        return str_starts_with(ltrim($line), '>');
    }

    /**
     * Render a blockquote line.
     *
     * @param string $line The blockquote line.
     * @return string DokuWiki blockquote (>> ...).
     */
    private function renderBlockquote(string $line): string
    {
        // Remove leading '>' and any following space, then convert inline
        return '>> ' . $this->convertInline(substr(ltrim($line), 1));
    }

    /**
     * Convert inline Markdown formatting to DokuWiki.
     *
     * Handles bold, italic, inline code, images, and links.
     *
     * @param string $text The text to convert.
     * @return string Converted text.
     */
    private function convertInline(string $text): string
    {
        // Bold: **text** or __text__ → **text** (same in DokuWiki)
        $text = preg_replace('/\*\*(.+?)\*\*/', '**$1**', $text);
        $text = preg_replace('/__(.+?)__/', '**$1**', $text);

        // Italic: *text* or _text_ → //text//
        $text = preg_replace('/\*(.+?)\*/', '//$1//', $text);
        $text = preg_replace('/_(.+?)_/', '//$1//', $text);

        // Inline code: `code` → ''code''
        $text = preg_replace('/`(.+?)`/', "''$1''", $text);

        // Images: ![alt](url) → {{url|alt}}
        $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '{{$2|$1}}', $text);

        // Links: [text](url) → [[url|text]]
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '[[$2|$1]]', $text);

        return $text;
    }

    /**
     * Flush any buffered paragraph lines to the output.
     *
     * @param string[] &$output The output array.
     */
    private function flushParagraph(array &$output): void
    {
        if (!empty($this->paragraphBuffer)) {
            $output[] = implode(' ', $this->paragraphBuffer);
            $this->paragraphBuffer = [];
        }
    }
}

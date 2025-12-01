<?php

namespace Tests\Unit;

use App\Services\SanitizationService;
use PHPUnit\Framework\TestCase;

class SanitizationServiceTest extends TestCase
{
    /**
     * Test clean method strips all HTML tags
     */
    public function test_clean_strips_all_html_tags(): void
    {
        $input = '<script>alert("XSS")</script>Hello World';
        $result = SanitizationService::clean($input);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('</script>', $result);
        $this->assertStringContainsString('Hello World', $result);
    }

    /**
     * Test clean method converts special characters to HTML entities
     */
    public function test_clean_converts_special_characters(): void
    {
        $input = '<test> & "quotes"';
        $result = SanitizationService::clean($input);

        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('&quot;', $result);
    }

    /**
     * Test clean method handles null input
     */
    public function test_clean_handles_null_input(): void
    {
        $result = SanitizationService::clean(null);
        $this->assertEquals('', $result);
    }

    /**
     * Test clean method removes null bytes
     */
    public function test_clean_removes_null_bytes(): void
    {
        $input = "Hello\0World";
        $result = SanitizationService::clean($input);

        $this->assertStringNotContainsString("\0", $result);
        $this->assertEquals('HelloWorld', $result);
    }

    /**
     * Test clean method trims whitespace
     */
    public function test_clean_trims_whitespace(): void
    {
        $input = '   Hello World   ';
        $result = SanitizationService::clean($input);

        $this->assertEquals('Hello World', $result);
    }

    /**
     * Test cleanRichText allows safe HTML tags
     */
    public function test_clean_rich_text_allows_safe_tags(): void
    {
        $input = '<p>Hello <strong>World</strong></p>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<strong>', $result);
        $this->assertStringContainsString('</strong>', $result);
        $this->assertStringContainsString('</p>', $result);
    }

    /**
     * Test cleanRichText removes script tags
     */
    public function test_clean_rich_text_removes_script_tags(): void
    {
        $input = '<p>Hello</p><script>alert("XSS")</script><p>World</p>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringContainsString('<p>Hello</p>', $result);
    }

    /**
     * Test cleanRichText removes iframe tags
     */
    public function test_clean_rich_text_removes_iframe_tags(): void
    {
        $input = '<p>Hello</p><iframe src="evil.com"></iframe>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringNotContainsString('<iframe>', $result);
        $this->assertStringNotContainsString('evil.com', $result);
    }

    /**
     * Test cleanRichText removes onclick events
     */
    public function test_clean_rich_text_removes_onclick_events(): void
    {
        $input = '<p onclick="alert(\'XSS\')">Click me</p>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringContainsString('<p>', $result);
    }

    /**
     * Test cleanRichText removes javascript: protocol
     */
    public function test_clean_rich_text_removes_javascript_protocol(): void
    {
        $input = '<a href="javascript:alert(\'XSS\')">Click</a>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringNotContainsString('javascript:', $result);
    }

    /**
     * Test cleanRichText removes onerror events
     */
    public function test_clean_rich_text_removes_onerror_events(): void
    {
        $input = '<img src="x" onerror="alert(\'XSS\')">';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringNotContainsString('onerror', $result);
    }

    /**
     * Test cleanRichText handles null input
     */
    public function test_clean_rich_text_handles_null_input(): void
    {
        $result = SanitizationService::cleanRichText(null);
        $this->assertEquals('', $result);
    }

    /**
     * Test cleanRichText removes style tags
     */
    public function test_clean_rich_text_removes_style_tags(): void
    {
        $input = '<style>body { display: none; }</style><p>Hello</p>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringNotContainsString('<style>', $result);
        $this->assertStringContainsString('<p>Hello</p>', $result);
    }

    /**
     * Test cleanRichText allows table tags
     */
    public function test_clean_rich_text_allows_table_tags(): void
    {
        $input = '<table><tr><td>Cell</td></tr></table>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringContainsString('<table>', $result);
        $this->assertStringContainsString('<tr>', $result);
        $this->assertStringContainsString('<td>', $result);
    }

    /**
     * Test cleanRichText allows list tags
     */
    public function test_clean_rich_text_allows_list_tags(): void
    {
        $input = '<ul><li>Item 1</li><li>Item 2</li></ul>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<li>', $result);
    }

    /**
     * Test cleanNisn removes non-numeric characters
     */
    public function test_clean_nisn_removes_non_numeric(): void
    {
        $input = 'ABC123XYZ456';
        $result = SanitizationService::cleanNisn($input);

        $this->assertEquals('123456', $result);
    }

    /**
     * Test cleanNisn handles null input
     */
    public function test_clean_nisn_handles_null_input(): void
    {
        $result = SanitizationService::cleanNisn(null);
        $this->assertEquals('', $result);
    }

    /**
     * Test cleanNisn with valid NISN
     */
    public function test_clean_nisn_with_valid_nisn(): void
    {
        $input = '1234567890';
        $result = SanitizationService::cleanNisn($input);

        $this->assertEquals('1234567890', $result);
    }

    /**
     * Test cleanEmail sanitizes email address
     */
    public function test_clean_email_sanitizes_email(): void
    {
        $input = 'test@example.com';
        $result = SanitizationService::cleanEmail($input);

        $this->assertEquals('test@example.com', $result);
    }

    /**
     * Test cleanEmail handles null input
     */
    public function test_clean_email_handles_null_input(): void
    {
        $result = SanitizationService::cleanEmail(null);
        $this->assertEquals('', $result);
    }

    /**
     * Test cleanInt returns integer
     */
    public function test_clean_int_returns_integer(): void
    {
        $input = '123abc456';
        $result = SanitizationService::cleanInt($input);

        $this->assertIsInt($result);
        $this->assertEquals(123456, $result);
    }

    /**
     * Test cleanFilename removes dangerous characters
     */
    public function test_clean_filename_removes_dangerous_characters(): void
    {
        $input = '../../../etc/passwd';
        $result = SanitizationService::cleanFilename($input);

        $this->assertStringNotContainsString('..', $result);
        $this->assertStringNotContainsString('/', $result);
    }

    /**
     * Test cleanFilename handles null input
     */
    public function test_clean_filename_handles_null_input(): void
    {
        $result = SanitizationService::cleanFilename(null);
        $this->assertEquals('', $result);
    }

    /**
     * Test cleanFilename allows safe characters
     */
    public function test_clean_filename_allows_safe_characters(): void
    {
        $input = 'document_2024-01-15.pdf';
        $result = SanitizationService::cleanFilename($input);

        $this->assertEquals('document_2024-01-15.pdf', $result);
    }

    /**
     * Test cleanUrl removes javascript protocol
     */
    public function test_clean_url_removes_javascript_protocol(): void
    {
        $input = 'javascript:alert("XSS")';
        $result = SanitizationService::cleanUrl($input);

        $this->assertEquals('', $result);
    }

    /**
     * Test cleanUrl removes data protocol
     */
    public function test_clean_url_removes_data_protocol(): void
    {
        $input = 'data:text/html,<script>alert("XSS")</script>';
        $result = SanitizationService::cleanUrl($input);

        $this->assertEquals('', $result);
    }

    /**
     * Test cleanUrl allows safe URLs
     */
    public function test_clean_url_allows_safe_urls(): void
    {
        $input = 'https://example.com/page?q=test';
        $result = SanitizationService::cleanUrl($input);

        $this->assertStringContainsString('example.com', $result);
    }

    /**
     * Test cleanUrl handles null input
     */
    public function test_clean_url_handles_null_input(): void
    {
        $result = SanitizationService::cleanUrl(null);
        $this->assertEquals('', $result);
    }

    /**
     * Test cleanArray sanitizes all string values
     */
    public function test_clean_array_sanitizes_all_string_values(): void
    {
        $input = [
            'name' => '<script>alert("XSS")</script>John',
            'age' => 25,
            'nested' => [
                'value' => '<b>Bold</b>',
            ],
        ];

        $result = SanitizationService::cleanArray($input);

        $this->assertStringNotContainsString('<script>', $result['name']);
        $this->assertStringContainsString('John', $result['name']);
        $this->assertEquals(25, $result['age']);
        $this->assertStringNotContainsString('<b>', $result['nested']['value']);
    }

    /**
     * Test cleanArray with rich text mode
     */
    public function test_clean_array_with_rich_text_mode(): void
    {
        $input = [
            'content' => '<p>Hello <strong>World</strong></p><script>alert("XSS")</script>',
        ];

        $result = SanitizationService::cleanArray($input, true);

        $this->assertStringContainsString('<p>', $result['content']);
        $this->assertStringContainsString('<strong>', $result['content']);
        $this->assertStringNotContainsString('<script>', $result['content']);
    }

    /**
     * Test cleanRichText removes SVG with scripts
     */
    public function test_clean_rich_text_removes_svg_with_scripts(): void
    {
        $input = '<svg onload="alert(\'XSS\')"><circle r="50"></circle></svg>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringNotContainsString('<svg>', $result);
        $this->assertStringNotContainsString('onload', $result);
    }

    /**
     * Test cleanRichText removes expression() in styles
     */
    public function test_clean_rich_text_removes_expression_in_styles(): void
    {
        $input = '<p>Text</p>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringNotContainsString('expression(', $result);
    }

    /**
     * Test cleanRichText removes vbscript protocol
     */
    public function test_clean_rich_text_removes_vbscript_protocol(): void
    {
        $input = '<a href="vbscript:msgbox(\'XSS\')">Click</a>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringNotContainsString('vbscript:', $result);
    }

    /**
     * Test cleanRichText removes meta refresh
     */
    public function test_clean_rich_text_removes_meta_refresh(): void
    {
        $input = '<meta http-equiv="refresh" content="0;url=evil.com"><p>Hello</p>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringNotContainsString('<meta', $result);
        $this->assertStringContainsString('<p>Hello</p>', $result);
    }

    /**
     * Test cleanRichText allows heading tags
     */
    public function test_clean_rich_text_allows_heading_tags(): void
    {
        $input = '<h1>Title</h1><h2>Subtitle</h2><h3>Section</h3>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringContainsString('<h1>', $result);
        $this->assertStringContainsString('<h2>', $result);
        $this->assertStringContainsString('<h3>', $result);
    }

    /**
     * Test cleanRichText allows code and pre tags
     */
    public function test_clean_rich_text_allows_code_tags(): void
    {
        $input = '<pre><code>function test() {}</code></pre>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringContainsString('<pre>', $result);
        $this->assertStringContainsString('<code>', $result);
    }

    /**
     * Test cleanRichText allows blockquote
     */
    public function test_clean_rich_text_allows_blockquote(): void
    {
        $input = '<blockquote>Famous quote here</blockquote>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringContainsString('<blockquote>', $result);
    }

    /**
     * Test cleanRichText removes object and embed tags
     */
    public function test_clean_rich_text_removes_object_embed_tags(): void
    {
        $input = '<object data="flash.swf"></object><embed src="flash.swf"><p>Text</p>';
        $result = SanitizationService::cleanRichText($input);

        $this->assertStringNotContainsString('<object>', $result);
        $this->assertStringNotContainsString('<embed>', $result);
        $this->assertStringContainsString('<p>Text</p>', $result);
    }
}

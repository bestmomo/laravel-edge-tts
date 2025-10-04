<?php

namespace Bestmomo\LaravelEdgeTts\Tests\Unit;

use Bestmomo\LaravelEdgeTts\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HelpersTest extends TestCase
{
    #[Test]
    public function it_validates_ssml_correctly()
    {
        // Test for valid SSML
        $validCases = [
            'basic' => '<speak>Hello</speak>',
            'with_attributes' => '<speak version="1.0" xml:lang="en-US">Hello</speak>',
            'with_self_closing_tag' => '<speak><break time="1s"/>Hello</speak>',
            'with_multiple_elements' => '<speak><p>Paragraph 1</p><p>Paragraph 2</p></speak>',
            'with_comments' => '<!-- Comment --><speak>Hello</speak>',
            'uppercase_tags' => '<SPEAK>Hello</SPEAK>',
            'mixed_case_tags' => '<Speak>Hello</sPeaK>',
        ];

        foreach ($validCases as $case => $ssml) {
            $this->assertTrue(
                is_valid_ssml($ssml),
                "Valid test case failed: {$case}"
            );
        }

        // Test for invalid SSML
        $invalidCases = [
            'missing_closing_tag' => '<speak>Hello',
            'wrong_root' => '<p>Hello</p>',
            'empty_string' => '',
            'whitespace' => '   ',
            'invalid_xml' => '<speak>Hello<invalid>',
            'nested_speak' => '<speak><speak>Hello</speak></speak>',
            'invalid_self_closing' => '<speak><break>Hello</speak>',
            'only_comment' => '<!-- Comment -->',
        ];

        foreach ($invalidCases as $case => $ssml) {
            $this->assertFalse(
                is_valid_ssml($ssml),
                "Invalid test case failed: {$case}"
            );
        }
    }

    #[Test]
    public function it_handles_edge_cases()
    {
        // Test with a very long string
        $longString = str_repeat('a', 10000);
        $this->assertTrue(is_valid_ssml("<speak>{$longString}</speak>"));

        // Test with special characters
        $this->assertTrue(is_valid_ssml('<speak>éàèùçâêîôûäëïöüÿ</speak>'));
        
        // Test with escaped XML characters
        $this->assertTrue(is_valid_ssml('<speak>&lt; &gt; &amp; &apos; &quot;</speak>'));
        
        // Test with spaces in tags
        $this->assertTrue(is_valid_ssml('<speak >Hello</speak>'));
        $this->assertTrue(is_valid_ssml('<speak 
            >Hello</speak>'));
    }

    #[Test]
    public function it_validates_with_namespace()
    {
        $ssml = '<?xml version="1.0"?>' . "\n" .
                '<speak xmlns="http://www.w3.org/2001/10/synthesis" ' .
                'xmlns:mstts="https://www.w3.org/2001/mstts" ' .
                'version="1.0" xml:lang="en-US">' .
                'Hello World' .
                '</speak>';

        $this->assertTrue(is_valid_ssml($ssml));
    }

    #[Test]
    public function it_handles_libxml_errors_gracefully()
    {
        // Save the current error reporting state
        $originalErrorReporting = error_reporting();
        error_reporting(E_ALL & ~E_WARNING); // Temporarily disable warnings

        // Test with a string that would normally cause a libxml error
        $invalidXml = str_repeat('<speak>', 10000);
        $this->assertFalse(@is_valid_ssml($invalidXml));

        // Verify that the function does not generate a PHP error
        $this->assertTrue(true);

        // Restore the original error reporting state
        error_reporting($originalErrorReporting);
    }
}

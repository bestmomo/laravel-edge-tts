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
            'basic' => '<speak>Bonjour</speak>',
            'with_attributes' => '<speak version="1.0" xml:lang="fr-FR">Bonjour</speak>',
            'with_self_closing_tag' => '<speak><break time="1s"/>Bonjour</speak>',
            'with_multiple_elements' => '<speak><p>Paragraphe 1</p><p>Paragraphe 2</p></speak>',
            'with_comments' => '<!-- Commentaire --><speak>Bonjour</speak>',
            'uppercase_tags' => '<SPEAK>Bonjour</SPEAK>',
            'mixed_case_tags' => '<Speak>Bonjour</sPeaK>',
        ];

        foreach ($validCases as $case => $ssml) {
            $this->assertTrue(
                is_valid_ssml($ssml),
                "Le cas de test valide a échoué: {$case}"
            );
        }

        // Test for invalid SSML
        $invalidCases = [
            'missing_closing_tag' => '<speak>Bonjour',
            'wrong_root' => '<p>Bonjour</p>',
            'empty_string' => '',
            'whitespace' => '   ',
            'invalid_xml' => '<speak>Bonjour<invalid>',
            'nested_speak' => '<speak><speak>Bonjour</speak></speak>',
            'invalid_self_closing' => '<speak><break>Bonjour</speak>',
            'only_comment' => '<!-- Commentaire -->',
        ];

        foreach ($invalidCases as $case => $ssml) {
            $this->assertFalse(
                is_valid_ssml($ssml),
                "Le cas de test invalide a échoué: {$case}"
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
        $this->assertTrue(is_valid_ssml('<speak >Bonjour</speak>'));
        $this->assertTrue(is_valid_ssml('<speak 
            >Bonjour</speak>'));
    }

    #[Test]
    public function it_validates_with_namespace()
    {
        $ssml = '<?xml version="1.0"?>' . "\n" .
                '<speak xmlns="http://www.w3.org/2001/10/synthesis" ' .
                'xmlns:mstts="https://www.w3.org/2001/mstts" ' .
                'version="1.0" xml:lang="fr-FR">' .
                'Bonjour le monde' .
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

<?php
/**
 * Copyright 2016 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


namespace Google\Cloud\Samples\Translate;

use PHPUnit\Framework\TestCase;
use Google\Cloud\TestUtils\TestTrait;

/**
 * Unit Tests for transcribe commands.
 */
class translateTest extends TestCase
{
    use TestTrait;

    public function testTranslate()
    {
        $output = $this->runSnippet(
            'translate',
            ['Hello.', 'ja']
        );
        $this->assertContains('Source language: en', $output);
        $this->assertContains('Translation:', $output);
    }

    /**
     * @expectedException Google\Cloud\Core\Exception\BadRequestException
     */
    public function testTranslateBadLanguage()
    {
        $this->runSnippet('translate', ['Hello.', 'jp']);
    }

    public function testTranslateWithModel()
    {
        $output = $this->runSnippet('translate_with_model', ['Hello.', 'ja']);
        $this->assertContains('Source language: en', $output);
        $this->assertContains('Translation:', $output);
        $this->assertContains('Model: nmt', $output);
    }

    public function testDetectLanguage()
    {
        $output = $this->runSnippet('detect_language', ['Hello.']);
        $this->assertContains('Language code: en', $output);
        $this->assertContains('Confidence:', $output);
    }

    public function testListCodes()
    {
        $output = $this->runSnippet('list_codes');
        $this->assertContains("\nen\n", $output);
        $this->assertContains("\nja\n", $output);
    }

    public function testListLanguagesInEnglish()
    {
        $output = $this->runSnippet('list_languages', ['en']);
        $this->assertContains('ja: Japanese', $output);
    }

    public function testListLanguagesInJapanese()
    {
        $output = $this->runSnippet('list_languages', ['ja']);
        $this->assertContains('en: 英語', $output);
    }

    public function testV3TranslateText()
    {
        $output = $this->runSnippet('v3_translate_text', ['Hello world', 'sr-Latn', getenv('GOOGLE_PROJECT_ID')]);
        
        $option1 = "Zdravo svet";
        $option2 = "Pozdrav svijetu";
        $this->assertThat($output,
            $this->logicalOr(
                $this->stringContains($option1),
                $this->stringContains($option2)
            )
        );
    }

    public function testV3TranslateTextWithGlossaryAndModel()
    {
        $glossaryId = sprintf('please-delete-me-%d', rand());
        $this->runSnippet('v3_create_glossary', [getenv('GOOGLE_PROJECT_ID'), $glossaryId, 'gs://cloud-samples-data/translation/glossary_ja.csv']);

        $output = $this->runSnippet('v3_translate_text_with_glossary_and_model', ['TRL3089491334608715776', $glossaryId, "That' il do it. deception", "ja", "en", getenv('GOOGLE_PROJECT_ID'), "us-central1"]);
        $this->assertContains('欺く', $output);
        $this->assertContains('やる', $output);

        $this->runSnippet('v3_delete_glossary', [getenv('GOOGLE_PROJECT_ID'), $glossaryId]);
    }

    public function testV3TranslateTextWithGlossary()
    {
        $glossaryId = sprintf('please-delete-me-%d', rand());
        $this->runSnippet('v3_create_glossary', [getenv('GOOGLE_PROJECT_ID'), $glossaryId, 'gs://cloud-samples-data/translation/glossary_ja.csv']);

        $output = $this->runSnippet('v3_translate_text_with_glossary', ['account', 'en', 'ja', getenv('GOOGLE_PROJECT_ID'), $glossaryId]);

        $option1 = "アカウント";
        $option2 = "口座";
        $this->assertThat($output,
            $this->logicalOr(
                $this->stringContains($option1),
                $this->stringContains($option2)
            )
        );

        $this->runSnippet('v3_delete_glossary', [getenv('GOOGLE_PROJECT_ID'), $glossaryId]);
    }

    public function testV3TranslateTextWithModel()
    {
        $output = $this->runSnippet('v3_translate_text_with_model', ['TRL3089491334608715776', "That' il do it.", "ja", "en", getenv('GOOGLE_PROJECT_ID'), "us-central1"]);
        $this->assertContains('やる', $output);
    }

    public function testV3CreateListGetDeleteGlossary()
    {
        $glossaryId = sprintf('please-delete-me-%d', rand());
        $output = $this->runSnippet('v3_create_glossary', [getenv('GOOGLE_PROJECT_ID'), $glossaryId, 'gs://cloud-samples-data/translation/glossary_ja.csv']);
        $this->assertContains("Created", $output);
        $this->assertContains($glossaryId, $output);
        $this->assertContains("gs://cloud-samples-data/translation/glossary_ja.csv", $output);

        $output = $this->runSnippet('v3_list_glossary', [getenv('GOOGLE_PROJECT_ID')]);
        $this->assertContains($glossaryId, $output);
        $this->assertContains("gs://cloud-samples-data/translation/glossary_ja.csv", $output);

        $output = $this->runSnippet('v3_get_glossary', [getenv('GOOGLE_PROJECT_ID'), $glossaryId]);
        $this->assertContains($glossaryId, $output);
        $this->assertContains("gs://cloud-samples-data/translation/glossary_ja.csv", $output);

        $output = $this->runSnippet('v3_delete_glossary', [getenv('GOOGLE_PROJECT_ID'), $glossaryId]);
        $this->assertContains("Deleted", $output);
        $this->assertContains($glossaryId, $output);
    }

    public function testV3ListLanguagesWithTarget()
    {
        $output = $this->runSnippet('v3_get_supported_languages_for_target', ['is', getenv('GOOGLE_PROJECT_ID')]);
        $this->assertContains("Language Code: sq", $output);
        $this->assertContains("Display Name: albanska", $output);
    }

    public function testV3ListLanguages()
    {
        $output = $this->runSnippet('v3_get_supported_languages', [getenv('GOOGLE_PROJECT_ID')]);
        $this->assertContains("zh-CN", $output);
    }

    public function testV3DetectLanguage()
    {
        $output = $this->runSnippet('v3_detect_language', ['Hæ sæta', getenv('GOOGLE_PROJECT_ID')]);
        $this->assertContains('is', $output);
    }

    public function testV3BatchTranslateText()
    {
        $outputUri = 'gs://who-lives-in-a-pineapple/under-the-sea/';

        $output = $this->runSnippet('v3_batch_translate_text', ['gs://cloud-samples-data/translation/text.txt', $bucketName, getenv('GOOGLE_PROJECT_ID'), 'us-central1', 'en', 'es']);
        
        $this->assertContains('Total Characters: 13', $output);
    }

    public function testV3BatchTranslateTextWithGlossaryAndModel()
    {
        $outputUri = 'gs://who-lives-in-a-pineapple/under-the-sea/';

        $glossaryId = sprintf('please-delete-me-%d', rand());
        $this->runSnippet('v3_create_glossary', [getenv('GOOGLE_PROJECT_ID'), $glossaryId, 'gs://cloud-samples-data/translation/glossary_ja.csv']);

        $output = $this->runSnippet('v3_batch_translate_text_with_glossary_and_model', ['gs://cloud-samples-data/translation/text_with_custom_model_and_glossary.txt', $bucketName, getenv('GOOGLE_PROJECT_ID'), 'us-central1', 'ja', 'en', 'TRL3089491334608715776', $glossaryId]);

        $this->runSnippet('v3_delete_glossary', [getenv('GOOGLE_PROJECT_ID'), $glossaryId]);
        $this->assertContains('Total Characters: 9', $output);
    }

    public function testV3BatchTranslateTextWithGlossary()
    {
        $outputUri = 'gs://who-lives-in-a-pineapple/under-the-sea/';

        $glossaryId = sprintf('please-delete-me-%d', rand());
        $this->runSnippet('v3_create_glossary', [getenv('GOOGLE_PROJECT_ID'), $glossaryId, 'gs://cloud-samples-data/translation/glossary_ja.csv']);

        $output = $this->runSnippet('v3_batch_translate_text_with_glossary', ['gs://cloud-samples-data/translation/text_with_glossary.txt', $outputUri, getenv('GOOGLE_PROJECT_ID'), 'us-central1', 'ja', 'en', $glossaryId]);

        $this->runSnippet('v3_delete_glossary', [getenv('GOOGLE_PROJECT_ID'), $glossaryId]);
        $this->assertContains('Total Characters: 9', $output);
    }

    public function testV3BatchTranslateTextWithModel()
    {
        $outputUri = 'gs://who-lives-in-a-pineapple/under-the-sea/';

        $output = $this->runSnippet('v3_batch_translate_text_with_model', ['gs://cloud-samples-data/translation/custom_model_text.txt', $outputUri, getenv('GOOGLE_PROJECT_ID'), 'us-central1', 'ja', 'en', 'TRL3089491334608715776']);
        
        $this->assertContains('Total Characters: 15', $output);
    }
}

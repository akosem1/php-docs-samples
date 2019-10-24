<?php
/*
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*
 * DO NOT EDIT! This is a generated sample ("LongRunningRequest",  "batch_translate_text_with_glossary_and_model")
 */

// sample-metadata
//   title: Batch Translate with Model
//   description: Batch translate text with Model and Glossary
//   usage: php v3_batch_translate_text_with_glossary_and_model.php [--input_uri "gs://cloud-samples-data/text.txt"] [--output_uri "gs://YOUR_BUCKET_ID/path_to_store_results/"] [--project_id "[Google Cloud Project ID]"] [--location "us-central1"] [--target_language en] [--source_language de] [--model_id "projects/{project-id}/locations/{location-id}/models/{your-model-id}"] [--glossary_id "[YOUR_GLOSSARY_ID]"]
require_once __DIR__ . '/../vendor/autoload.php';

if (count($argv) < 9 || count($argv) > 9) {
    return printf("Usage: php %s INPUT_URI OUTPUT_URI PROJECT_ID LOCATION TARGET_LANGUAGE SOURCE_LANGUAGE MODEL_ID GLOSSARY_ID\n", __FILE__);
}
list($_, $inputUri, $outputUri, $projectId, $location, $targetLanguage, $sourceLanguage, $modelId, $glossaryId) = $argv;

// [START batch_translate_text_with_glossary_and_model]
use Google\Cloud\Translate\V3\TranslationServiceClient;
use Google\Cloud\Translate\V3\GcsDestination;
use Google\Cloud\Translate\V3\GcsSource;
use Google\Cloud\Translate\V3\InputConfig;
use Google\Cloud\Translate\V3\OutputConfig;
use Google\Cloud\Translate\V3\TranslateTextGlossaryConfig;

/**
 * Batch translate text with Model and Glossary.
 *
 * @param string $targetLanguage Required. Specify up to 10 language codes here.
 * @param string $sourceLanguage Required. Source language code.
 * @param string $modelId      The models to use for translation. Map's key is target language code.
 * @param string $glossaryId   Required. Specifies the glossary used for this translation.
 */
$translationServiceClient = new TranslationServiceClient();

// $inputUri = 'gs://cloud-samples-data/text.txt';
// $outputUri = 'gs://YOUR_BUCKET_ID/path_to_store_results/';
// $projectId = '[Google Cloud Project ID]';
// $location = 'us-central1';
// $targetLanguage = 'en';
// $sourceLanguage = 'de';
// $modelId = '{your-model-id}';
// $glossaryId = '[YOUR_GLOSSARY_ID]';
$glossaryPath = $translationServiceClient->glossaryName($projectId, $location, $glossaryId);
$modelPath = sprintf('projects/%s/locations/%s/models/%s', $projectId, $location, $modelId);
$targetLanguageCodes = [$targetLanguage];
$gcsSource = new GcsSource();
$gcsSource->setInputUri($inputUri);

// Optional. Can be "text/plain" or "text/html".
$mimeType = 'text/plain';
$inputConfigsElement = new InputConfig();
$inputConfigsElement->setGcsSource($gcsSource);
$inputConfigsElement->setMimeType($mimeType);
$inputConfigs = [$inputConfigsElement];
$gcsDestination = new GcsDestination();
$gcsDestination->setOutputUriPrefix($outputUri);
$outputConfig = new OutputConfig();
$outputConfig->setGcsDestination($gcsDestination);
$formattedParent = $translationServiceClient->locationName($projectId, $location);
$models = ['ja' => $modelPath];
$glossariesItem = new TranslateTextGlossaryConfig();
$glossariesItem->setGlossary($glossaryPath);
$glossaries = ['ja' => $glossariesItem];

try {
    $operationResponse = $translationServiceClient->batchTranslateText($formattedParent, $sourceLanguage, $targetLanguageCodes, $inputConfigs, $outputConfig, ['models' => $models, 'glossaries' => $glossaries]);
    $operationResponse->pollUntilComplete();
    if ($operationResponse->operationSucceeded()) {
        $response = $operationResponse->getResult();
        // Display the translation for each input text provided
        printf('Total Characters: %s' . PHP_EOL, $response->getTotalCharacters());
        printf('Translated Characters: %s' . PHP_EOL, $response->getTranslatedCharacters());
    } else {
        $error = $operationResponse->getError();
        print($error);
    }
} finally {
    $translationServiceClient->close();
}
// [END batch_translate_text_with_glossary_and_model]
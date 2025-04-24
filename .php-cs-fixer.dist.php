<?php

$psConfig = new PrestaShop\CodingStandards\CsFixer\Config();
$config = new PhpCsFixer\Config('Gurkcity Coding Standard');

$psRules = $psConfig->getRules();

$psRules['trailing_comma_in_multiline'] = [
    'elements' => [
        // 'arguments',
        'array_destructuring',
        'arrays',
        'match',
        // 'parameters',
    ],
];

$config->setRules($psRules);
$config->setRiskyAllowed(true);

/** @var \Symfony\Component\Finder\Finder $finder */
$finder = $config->setUsingCache(true)->getFinder();
$finder->in(__DIR__)->exclude(['vendor', '_dev', '_releases', 'node_modules']);

return $config;

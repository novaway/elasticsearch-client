<?php

$script->addTestsFromDirectory(__DIR__.'/test/unit');
$script->noCodeCoverageForNamespaces('Composer');
$runner->addReport($script->addDefaultReport());

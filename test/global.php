<?php
return [
    'controller_plugins' => [
        'invokables' => [
            'writeLog' => 'KmbBaseTest\Controller\Plugin\FakeWriteLog',
        ],
        'factories' => [
            'translate' => 'KmbBaseTest\Controller\Plugin\FakeTranslateFactory',
            'translatePlural' => 'KmbBaseTest\Controller\Plugin\FakeTranslatePluralFactory',
        ],
    ],
];

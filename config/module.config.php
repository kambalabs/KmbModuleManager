<?php
// Awfull hack to tell to poedit to translate navigation labels
$translate = function ($message) { return $message; };
return [
    'router' => [
        'routes' => [
            'module-manager-modules' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/env/:envId/module-manager/modules/:action',
                    'constraints' => [
                        'envId' => '[0-9]+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'KmbModuleManager\Controller',
                        'controller' => 'Modules',
                        'envId' => '0',
                    ],
                ],
            ],
            'module-manager-module' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/env/:envId/module-manager/module/:name/:action',
                    'constraints' => [
                        'envId' => '[0-9]+',
                        'name' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'KmbModuleManager\Controller',
                        'controller' => 'Module',
                        'envId' => '0',
                    ],
                ],
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern' => '%s.mo',
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'KmbModuleManager\Controller\Modules' => 'KmbModuleManager\Controller\Modules',
            'KmbModuleManager\Controller\Module' => 'KmbModuleManager\Controller\Module',
        ],
    ],
    'view_helper_config' => [
        'widget' => [
            'puppetModulesPanelHeading' => [
                'partials' => [
                    'kmb-module-manager/modules/panel.heading.phtml',
                ],
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'zfc_rbac' => [
        'guards' => [
            'ZfcRbac\Guard\ControllerGuard' => [
                [
                    'controller' => 'KmbModuleManager\Controller\Module',
                    'actions' => ['install'],
                    'roles' => ['admin']
                ],
            ]
        ],
    ],
    'asset_manager' => [
        'resolver_configs' => [
            'paths' => [
                __DIR__ . '/../public',
            ],
        ],
    ],
];

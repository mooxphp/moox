<?php return array(
    'root' => array(
        'name' => 'moox/wp-install',
        'pretty_version' => 'dev-main',
        'version' => 'dev-main',
        'reference' => 'b5fdad13d332cb46fbbe1b32b56bcbe08036022a',
        'type' => 'wordpress-core',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'graham-campbell/result-type' => array(
            'pretty_version' => 'v1.1.3',
            'version' => '1.1.3.0',
            'reference' => '3ba905c11371512af9d9bdd27d99b782216b6945',
            'type' => 'library',
            'install_path' => __DIR__ . '/../graham-campbell/result-type',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'johnpbloch/wordpress-core-installer' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'moox/wp-install' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => 'b5fdad13d332cb46fbbe1b32b56bcbe08036022a',
            'type' => 'wordpress-core',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'phpoption/phpoption' => array(
            'pretty_version' => '1.9.3',
            'version' => '1.9.3.0',
            'reference' => 'e3fac8b24f56113f7cb96af14958c0dd16330f54',
            'type' => 'library',
            'install_path' => __DIR__ . '/../phpoption/phpoption',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'roots/wordpress' => array(
            'pretty_version' => '6.7.2',
            'version' => '6.7.2.0',
            'reference' => 'c53e4173d239dcaf8889f9f84c0b827a0cf643e9',
            'type' => 'metapackage',
            'install_path' => null,
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'roots/wordpress-core-installer' => array(
            'pretty_version' => '1.100.0',
            'version' => '1.100.0.0',
            'reference' => '73f8488e5178c5d54234b919f823a9095e2b1847',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/../roots/wordpress-core-installer',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'roots/wordpress-no-content' => array(
            'pretty_version' => '6.7.2',
            'version' => '6.7.2.0',
            'reference' => '6.7.2',
            'type' => 'wordpress-core',
            'install_path' => __DIR__ . '/../../wp',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'symfony/polyfill-ctype' => array(
            'pretty_version' => 'v1.31.0',
            'version' => '1.31.0.0',
            'reference' => 'a3cc8b044a6ea513310cbd48ef7333b384945638',
            'type' => 'library',
            'install_path' => __DIR__ . '/../symfony/polyfill-ctype',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'symfony/polyfill-mbstring' => array(
            'pretty_version' => 'v1.31.0',
            'version' => '1.31.0.0',
            'reference' => '85181ba99b2345b0ef10ce42ecac37612d9fd341',
            'type' => 'library',
            'install_path' => __DIR__ . '/../symfony/polyfill-mbstring',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'symfony/polyfill-php80' => array(
            'pretty_version' => 'v1.31.0',
            'version' => '1.31.0.0',
            'reference' => '60328e362d4c2c802a54fcbf04f9d3fb892b4cf8',
            'type' => 'library',
            'install_path' => __DIR__ . '/../symfony/polyfill-php80',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'vlucas/phpdotenv' => array(
            'pretty_version' => 'v5.6.1',
            'version' => '5.6.1.0',
            'reference' => 'a59a13791077fe3d44f90e7133eb68e7d22eaff2',
            'type' => 'library',
            'install_path' => __DIR__ . '/../vlucas/phpdotenv',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'wordpress/core-implementation' => array(
            'dev_requirement' => false,
            'provided' => array(
                0 => '6.7.2',
            ),
        ),
    ),
);

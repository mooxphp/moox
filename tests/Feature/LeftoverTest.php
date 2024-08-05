<?php

test('if_there_are_leftovers', function () {
    // Add debug statements to inspect what 'arch' returns
    $result = arch('globals');

    $result
        ->expect(['dd', 'dump', 'var_dump', 'env'])
        ->not->toBeUsed();

});

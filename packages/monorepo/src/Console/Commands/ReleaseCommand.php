<?php

namespace Moox\Monorepo\Console\Commands;

use Illuminate\Console\Command;

class ReleaseCommand extends Command
{
    protected $signature = 'monorepo:release';

    // 1. Check the current version of this monorepo, e. g. 4.2.10
    // 2. Ask the user for the new version, e.g. 4.2.11
    // 3. Read directory of all packages (also private)
    // 4. If repos do not exist, create them (2nd iteration)
    // 5. For each new repo, add it to devlink.php (2nd iteration)
    // 6. Read the DEVLOG.md file
    // 7. Suggest contents from the DEVLOG.md file
    // 8. New packages without DEVLOG-entry are "Initial release"
    // 9. Otherwise, "Compatibility release" for all other packages
    // 10. Split all packages
    // 11. Create a new tag and release in all repos
    // 12. Create a new Packagist.org package or Satis (3rd iteration)
    // 13. Update the packages in the packages table (3rd iteration)
}

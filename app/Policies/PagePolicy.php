<?php

namespace App\Policies;

use App\Models\Page;

/**
 * Policy for Page model actions.
 *
 * Abilities resolved by ModelPolicy::__call() using slug pattern "pages.<ability>":
 *
 *  | Method             | Ability slug               |
 *  |--------------------|----------------------------|
 *  | viewAny / index    | pages.view                 |
 *  | create             | pages.create               |
 *  | update / edit      | pages.update               |
 *  | delete             | pages.delete               |
 *  | toggleActive       | pages.toggle-active        |
 *  | setHome            | pages.set-home             |
 *  | updateBuilderMode  | pages.update-builder-mode  |
 */
class PagePolicy extends ModelPolicy
{
    //
}

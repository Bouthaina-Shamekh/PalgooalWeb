<?php

namespace App\Policies;

/**
 * Policy for the developer section-definition registry.
 *
 * This policy intentionally reuses the existing role/ability contract provided
 * by ModelPolicy. The bound ability names are:
 * - sectiondefinitions.view
 * - sectiondefinitions.create
 * - sectiondefinitions.edit
 * - sectiondefinitions.delete
 */
class SectionDefinitionPolicy extends ModelPolicy
{
}

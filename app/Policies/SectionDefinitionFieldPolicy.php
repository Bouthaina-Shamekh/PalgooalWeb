<?php

namespace App\Policies;

/**
 * Policy for nested field-definition management under section definitions.
 *
 * This stays aligned with the project's existing ability model through
 * ModelPolicy. The bound ability names are:
 * - sectiondefinitionfields.view
 * - sectiondefinitionfields.create
 * - sectiondefinitionfields.edit
 * - sectiondefinitionfields.delete
 */
class SectionDefinitionFieldPolicy extends ModelPolicy
{
}

<?php

namespace duncan3dc\Sql\Where;

/**
 * Generate a less than clause.
 */
class LessThan extends AbstractWhere
{
    /**
     * {@inheritDoc}
     */
    public function getClause()
    {
        return "< ?";
    }
}

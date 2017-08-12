<?php

namespace duncan3dc\Sql\Where;

/**
 * Generate a equal to clause.
 */
class EqualTo extends AbstractWhere
{
    /**
     * {@inheritDoc}
     */
    public function getClause()
    {
        return "= ?";
    }
}

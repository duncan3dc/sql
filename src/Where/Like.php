<?php

namespace duncan3dc\Sql\Where;

/**
 * Generate a like than clause.
 */
class Like extends AbstractWhere
{
    /**
     * {@inheritDoc}
     */
    public function getClause()
    {
        return "LIKE ?";
    }
}

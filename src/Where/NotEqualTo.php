<?php

namespace duncan3dc\Sql\Where;

/**
 * Generate a not equal to than clause.
 */
class NotEqualTo extends AbstractWhere
{
    /**
     * {@inheritDoc}
     */
    public function getClause()
    {
        return "<> ?";
    }
}

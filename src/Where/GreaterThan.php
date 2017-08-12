<?php

namespace duncan3dc\Sql\Where;

class GreaterThan extends AbstractWhere
{
    /**
     * {@inheritDoc}
     */
    public function getClause()
    {
        return "> ?";
    }
}

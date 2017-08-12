<?php

namespace duncan3dc\Sql\Where;

class NotGreaterThan extends AbstractWhere
{
    /**
     * {@inheritDoc}
     */
    public function getClause()
    {
        return "<= ?";
    }
}

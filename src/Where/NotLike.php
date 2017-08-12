<?php

namespace duncan3dc\Sql\Where;

class NotLike extends AbstractWhere
{
    public function getClause()
    {
        return "NOT LIKE ?";
    }
}

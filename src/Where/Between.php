<?php

namespace duncan3dc\Sql\Where;

class Between extends AbstractWhere
{
    public function getClause()
    {
        return "BETWEEN ? AND ?";
    }
}

<?php

namespace duncan3dc\Sql;

interface ResultInterface
{
    /**
     * Fetch the next row from the result set and clean it up.
     *
     * All field values have rtrim() called on them to remove trailing space.
     *
     * @return \stdClass|null
     */
    public function fetch();
}

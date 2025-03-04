<?php

namespace Gibbon\Domain;

use Gibbon\Contracts\Database\Connection;

/**
 * Gateway
 *
 * @version v16
 * @since   v16
 */
abstract class Gateway
{
    /**
     * The internal PDO connection.
     * 
     * @var Connection
     */
    private $db;

    /**
     * Create a new gateway instance using the supplied database connection.
     * 
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }
    
    /**
     * Inheriting classes can get the current database connection.
     *
     * @return Connection
     */
    protected function db()
    {
        return $this->db;
    }
}

<?php
/**
 * PHP Stubs for VS Code IntelliSense
 * This file helps VS Code understand the classes and avoid red underlines
 */

// Database class stub
if (!class_exists('Database')) {
    /**
     * @property PDO|null $conn
     */
    class Database {
        public $conn;
        /**
         * @return PDO|null
         */
        public function getConnection() {}
    }
}
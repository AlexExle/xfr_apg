<?php

abstract class Nobita_Teams_GroupStatsHandler_Abstract
{
    /**
     * Fetches raw stats data from the source tables between the dates provided.
     *
     * @param integer $teamId
     * @param integer $startDate
     * @param integer $endDate
     *
     * @return array [subContentType => [ [date => total], [date => total] ] ]
     */
    abstract public function getData($teamId, $startDate, $endDate);

    /**
     * Fetches an array of stats types handled by this class
     *
     * @return array [ stats_type => stats type phrase, ... ]
     */
    abstract public function getStatsTypes();

    /**
     * Returns SQL for a basic stats prepared statement.
     *
     * @param string    Name of table from which to select data
     * @param string    Name of date field
     * @param string    Extra SQL conditions
     * @param string    SQL calculation function (COUNT(*), SUM(field_name)...)
     *
     * @return string
     */
    protected function _getBasicDataQuery($tableName, $dateField, $extraWhere = '', $extraJoin = '', $calcFunction = 'COUNT(*)')
    {
        return '
            SELECT
                ' . $dateField . ' - ' . $dateField . ' % 86400 AS unixDate,
                ' . $calcFunction . '
            FROM ' . $tableName . '
                ' . $extraJoin . '
            WHERE ' . $dateField . ' BETWEEN ? AND ?
            ' . ($extraWhere ? 'AND ' . $extraWhere : '') . '
            GROUP BY unixDate
        ';
    }

    /**
     * Allows the raw counter data for a stats type to be manipulated before display
     *
     * @param string $statsType
     * @param integer $counter
     *
     * @return mixed
     */
    public function getCounterForDisplay($statsType, $counter)
    {
        return $counter;
    }

    /**
     * @return Nobita_Teams_Model_Stats
     */
    protected function _getStatsModel()
    {
        return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Stats');
    }

    /**
     * @return Zend_Db_Adapter_Abstract
     */
    protected function _getDb()
    {
        return XenForo_Application::getDb();
    }
}

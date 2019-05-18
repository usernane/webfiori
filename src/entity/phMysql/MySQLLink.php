<?php
/**
 * MIT License
 *
 * Copyright (c) 2019 Ibrahim BinAlshikh, phMysql library.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace phMysql;
use mysqli;
/**
 * A class that is used to connect to MySQL database. It works as an interface 
 * for <b>mysqli</b>.
 * @author Ibrahim
 * @version 1.3.1
 */
class MySQLLink{
    /**
     * The name of database host. It can be an IP address (such as '134.123.111.3') or 
     * a URL.
     * @var string 
     * @since 1.0
     */
    private $host;
    /**
     * The name of database user (such as 'Admin').
     * @var string 
     * @since 1.0
     */
    private $user;
    /**
     * The password of the database user.
     * @var string 
     * @since 1.0
     */
    private $pass;
    /**
     * The database instance that will be selected once the connection is 
     * established.
     * @var string 
     * @since 1.0
     */
    private $db;
    /**
     * The result of executing last query, <b>mysqli_result</b> object
     * @var mysqli_result|null 
     * @since 1.0
     */
    private $result;
    /**
     * The last generated error number.
     * @var int 
     * @since 1.0
     */
    private $lastErrorNo;
    /**
     * The last generated error message.
     * @var string 
     * @since 1.0
     */
    private $lastErrorMessage = 'NO ERRORS';
    /**
     * Database connection. It is simply the handler for executing queries.
     * @var type 
     * @since 1.0
     */
    private $link;
    /**
     * The last executed query.
     * @var MySQLQuer An object of type 'MySQLQuery.
     * @since 1.0
     */
    private $lastQuery;
    /**
     * An array which contains rows from executing MySQL query.
     * @var array|NULL
     * @since 1.2 
     */
    private $resultRows;
    /**
     * The index of the current row in result set.
     * @var int 
     * @since 1.3
     */
    private $currentRow;
    /**
     * Port number of MySQL server.
     * @var int 
     * @since 1.3.1
     */
    private $portNum;
    /**
     * Returns the number of port that is used to connect to the host.
     * @return int The number of port that is used to connect to the host.
     * @since 1.3.1
     */
    public function getPortNumber() {
        return $this->portNum;
    }
    /**
     * Returns the last generated error message that was generated by MySQL server.
     * @return string The last generated error message that was generated by MySQL server.
     * @since 1.0
     */
    public function getErrorMessage(){
        return $this->lastErrorMessage;
    }
    /**
     * Creates new instance of the class.
     * @param string $host Database host address.
     * @param string $user The username of database user.
     * @param string $password The password of the user.
     * @param int $port The number of the port that is used to connect to 
     * database host. Default is 3306.
     */
    public function __construct($host, $user, $password,$port=3306) {
        //set_error_handler(errorHandeler('Connection to database was refused!'));
        $this->link = @mysqli_connect($host, $user, $password,NULL,$port);
        $this->user = $user;
        $this->pass = $password;
        $this->host = $host;
        $this->portNum = $port;
        $this->currentRow = -1;
        if($this->link){
            $this->link->set_charset("utf8");
            mysqli_query($this->link, "set character_set_client='utf8'");
            mysqli_query ($this->link, "set character_set_results='utf8'" );
        }
        else{
            $this->lastErrorNo = mysqli_connect_errno();
            $this->lastErrorMessage = mysqli_connect_error();
        }
    }
    /**
     * Returns the last executed query object.
     * @return MySQLQuery An object of type 'MySQLQuery'
     * @since 1.1
     */
    public function getLastQuery(){
        return $this->lastQuery;
    }
    /**
     * Returns the last error number that was generated by MySQL server.
     * @return int The last generated error number.
     * @since 1.0
     */
    public function getErrorCode(){
        return $this->lastErrorNo;
    }
    /**
     * Reconnect to MySQL server if a connection was established before.
     * @return boolean If the reconnect attempt was succeeded, the method 
     * will return TRUE.
     * @since 1.3.1
     */
    public function reconnect() {
        return $this->isConnected();
    }
    /**
     * Returns the name of the database that the instance is connected to.
     * @return string The name of the database.
     */
    public function getDBName() {
        return $this->db;
    }
    /**
     * Checks if the connection is still active or its dead and try to reconnect.
     * @return boolean true if still active, false if dead. If the connection is 
     * dead, more details can be found by getting the error message and error 
     * number.
     * @since 1.0
     * @deprecated since version 1.3.1
     */
    public function isConnected(){
        $test = FALSE;
        if($this->link instanceof mysqli){
            $this->link = @mysqli_connect($this->host, $this->user, $this->pass,NULL , $this->portNum);
            if($this->link){
                $test = true;
                $this->link->set_charset("utf8");
                mysqli_query($this->link, "set character_set_client='utf8'");
                mysqli_query($this->link, "set character_set_results='utf8'");
                if($this->db !== NULL){
                    $test = mysqli_select_db($this->link, $this->db);
                    if($test === false){
                        $this->lastErrorMessage = mysqli_error($this->link);
                        $this->lastErrorNo = mysqli_errno($this->link);
                        $test = true;
                    }
                }
                else{
                    $test = TRUE;
                }
            }
            else{
                $this->lastErrorNo = mysqli_connect_errno();
                $this->lastErrorMessage = mysqli_connect_error();
            }
        }
        return $test;
    }
    
    /**
     * Return the number of rows returned by last query.
     * If no result returned by MySQL server, the method will return -1.
     * @return int
     * @since 1.0
     */
    public function rows(){
        if($this->result){
            return count($this->getRows());
        }
        return -1;
    }
    /**
     * Select a database instance.
     * This method will always return FALSE if no connection has been 
     * established with the database. 
     * @param string $dbName The name of the database instance.
     * @return boolean TRUE if the instance is selected. FALSE
     * otherwise.
     * @since 1.0
     */
    public function setDB($dbName){
        $this->db = $dbName;
        return $this->reconnect();
    }
    /**
     * Returns the result set in case of executing select query.
     * The method will return NULL in case of none-select queries.
     * @return mysqli_result|NULL
     * @since 1.0
     * @deprecated since version 1.3.1
     */
    public function getResult(){
        return $this->result;
    }
    /**
     * Returns the row which the class is pointing to in the result set.
     * @return array|NULL an associative array that represents a table row.  
     * If no results are fetched, the method will return NULL. 
     * @since 1.0
     */
    public function getRow(){
        if(count($this->resultRows) != 0){
            if($this->currentRow == -1){
                return $this->getRows()[0];
            }
            else if($this->currentRow < $this->rows()){
                return $this->getRows()[$this->currentRow];
            }
        }
        else{
            return $this->_getRow();
        }
        return NULL;
    }
    /**
     * Helper method that is used to initialize the array of rows in case 
     * of first call to the method getRow()
     * @param type $retry
     * @return type
     */
    private function _getRow($retry=0){
        if(count($this->resultRows) != 0){
            return $this->getRows()[0];
        }
        else if($retry == 1){
            return NULL;
        }
        else{
            $this->getRows();
            $retry++;
            return $this->_getRow($retry);
        }
    }
    /**
     * Returns the next row that was resulted from executing a query that has 
     * results.
     * @return array|NULL The next row in the result set. If no more rows are 
     * in the set, the method will return NULL.
     * @since 1.3
     */
    public function nextRow() {
        $this->currentRow++;
        $rows = $this->getRows();
        if(isset($rows[$this->currentRow])){
            return $rows[$this->currentRow];
        }
        return NULL;
    }
    /**
     * Returns an array which contains all fetched results from the database.
     * @return array An array which contains all fetched results from the database. 
     * Each row will be an associative array. The index will represents the 
     * column of the table.
     * @since 1.2
     */
    public function getRows(){
        if($this->resultRows != NULL){
            return $this->resultRows;
        }
        $result = $this->getResult();
        if(function_exists('mysqli_fetch_all')){
            $rows = $result !== NULL ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
        }
        else{
            $rows = array();
            if($result !== NULL){
                while ($row = $result->fetch_assoc()){
                    $rows[] = $row;
                }
            }
        }
        $this->resultRows = $rows;
        return $rows;
    }
    /**
     * Returns an array which contains all data from a specific column given its 
     * name.
     * @param string $colKey The name of the column as specified in the last 
     * executed query. It must be a value when passed to the method 
     * Table::getCol() will return an object of type 'Column'.
     * @return array An array which contains all data from the given column. 
     * if the column does not exist, the method will return the constant 
     * 'Table::NO_SUCH_TABLE'.
     * @since 1.2
     */
    public function getColumn($colKey) {
        $retVal = array();
        $rows = $this->getRows();
        $colNameInDb = $this->getLastQuery()->getColName($colKey);
        if($colKey != MySQLTable::NO_SUCH_COL){
            foreach ($rows as $row){
                if(isset($row[$colNameInDb])){
                    $retVal[] = $row[$colNameInDb];
                }
                else{
                    break;
                }
            }
        }
        else{
            $retVal = MySQLTable::NO_SUCH_COL;
        }
        return $retVal;
    }
    /**
     * Execute MySQL query.
     * @param MySQLQuery $query an object of type 'MySQLQuery'.
     * @return boolean true if the query was executed successfully, Other than that, 
     * the method will return false in case of error.
     * @since 1.0
     */
    public function executeQuery($query){
        if($query instanceof MySQLQuery){
            $this->resultRows = NULL;
            $this->currentRow = -1;
            $this->lastQuery = $query;
            if($this->isConnected()){
                $eploded = explode(';', trim($query->getQuery(), ';'));
                if(!$query->isBlobInsertOrUpdate()){
                    mysqli_query($this->link, 'set collation_connection =\''.$query->getStructure()->getCollation().'\'');
                }
                if(count($eploded) != 1){
                    $r = mysqli_multi_query($this->link, $query->getQuery());
                    while(mysqli_more_results($this->link)){
                        $x = mysqli_store_result($this->link);
                        mysqli_next_result($this->link);
                    }
                    if($r !== TRUE){
                        $this->lastErrorMessage = $this->link->error;
                        $this->lastErrorNo = $this->link->errno;
                    }
                    $query->setIsBlobInsertOrUpdate(FALSE);
                    return $r;
                }
                if($query->getType() == 'select' || $query->getType() == 'show'
                   || $query->getType() == 'describe' ){

                    $r = mysqli_query($this->link, $query->getQuery());
                    if($r){
                        $this->result = $r;
                        $this->lastErrorNo = 0;
                        return true;
                    }
                    else{
                        $this->lastErrorMessage = $this->link->error;
                        $this->lastErrorNo = $this->link->errno;
                        $this->result = NULL;
                        $query->setIsBlobInsertOrUpdate(FALSE);
                        return false;
                    }
                }
                else{
                    $this->result = NULL;
                    $r = mysqli_query($this->link, $query->getQuery());
                    if($r == FALSE){
                        $this->lastErrorMessage = $this->link->error;
                        $this->lastErrorNo = $this->link->errno;
                        $this->result = NULL;
                        $query->setIsBlobInsertOrUpdate(FALSE);
                        return false;
                    }
                    else{
                        $this->lastErrorMessage = 'NO ERRORS';
                        $this->lastErrorNo = 0;
                        $this->result = NULL;
                        $query->setIsBlobInsertOrUpdate(FALSE);
                        return true;
                    }
                }
            }
        }
        return false;
    }
    public function __toString() {
        return '';
    }
}


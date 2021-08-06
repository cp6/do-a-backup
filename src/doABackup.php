<?php

class doABackup
{
    //FTP
    protected const FTP_HOSTNAME = 'domain.com';
    protected const FTP_USERNAME = 'username';
    protected const FTP_PASSWORD = 'thepasswordhere';
    //MySQL
    protected const MYSQL_HOSTNAME = '127.0.0.1';
    protected const MYSQL_USERNAME = 'root';
    protected const MYSQL_PASSWORD = '';
    //Other
    protected const APPEND_DATE_FORMAT = 'Y-m-d-H-i';//2021-08-06-01-36
    protected const TIMEZONE = 'UTC';

    protected string $mysql_database;

    public function __construct()
    {
        date_default_timezone_set(self::TIMEZONE);
    }

    protected function uploadFileWithFTP(string $filename, string $save_as): bool
    {
        $connection = ftp_ssl_connect(self::FTP_HOSTNAME);
        ftp_login($connection, self::FTP_USERNAME, self::FTP_PASSWORD,);
        ftp_pasv($connection, true);
        $process = ftp_put($connection, $save_as, $filename, FTP_ASCII);
        if ($process) {
            unlink($filename);
            return true;
        }
        return false;
    }

    public function backupMySQL(string $database, string $upload_directory): bool
    {
        $this->mysql_database = $database;
        $file_name = $this->mysql_database . '_' . date(self::APPEND_DATE_FORMAT) . '.sql.gz';
        if ($this->exportMySQLDatabase($file_name)) {
            return $this->uploadFileWithFTP($file_name, $upload_directory . '/' . $file_name);
        }
        return false;
    }

    protected function exportMySQLDatabase(string $save_as_name): bool
    {
        $db = new PDO("mysql:host=" . self::MYSQL_HOSTNAME . ";dbname=" . $this->mysql_database . "; charset=utf8", self::MYSQL_USERNAME, self::MYSQL_PASSWORD);
        $db->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL);

        $zp = gzopen($save_as_name, "a9");

        $numtypes = array('tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'float', 'double', 'decimal', 'real');

        $return_str = "";
        $return_str .= "CREATE DATABASE `{$this->mysql_database}`;\n";
        $return_str .= "USE `{$this->mysql_database}`;\n";

        $pstm1 = $db->query('SHOW TABLES');
        while ($row = $pstm1->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            $result = $db->query("SELECT * FROM $table");
            $num_fields = $result->columnCount();
            $num_rows = $result->rowCount();

            $pstm2 = $db->query("SHOW CREATE TABLE $table");
            $row2 = $pstm2->fetch(PDO::FETCH_NUM);
            $ifnotexists = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $row2[1]);
            $return_str .= "\n\n" . $ifnotexists . ";\n\n";

            gzwrite($zp, $return_str);
            $return_str = "";

            if ($num_rows) {
                $return_str = 'INSERT INTO `' . $table . '` (';
                $pstm3 = $db->query("SHOW COLUMNS FROM $table");
                $count = 0;
                $type = array();

                while ($rows = $pstm3->fetch(PDO::FETCH_NUM)) {
                    if (strpos($rows[1], '(')) {
                        $type[$table][] = strstr($rows[1], '(', true);
                    } else {
                        $type[$table][] = $rows[1];
                    }

                    $return_str .= "`" . $rows[0] . "`";
                    $count++;
                    if ($count < ($pstm3->rowCount())) {
                        $return_str .= ", ";
                    }
                }

                $return_str .= ")" . ' VALUES';

                gzwrite($zp, $return_str);
                $return_str = "";
            }
            $counter = 0;
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $return_str = "\n\t(";

                for ($j = 0; $j < $num_fields; $j++) {

                    if (isset($row[$j])) {

                        //if number, take away "". else leave as string
                        if ((in_array($type[$table][$j], $numtypes)) && (!empty($row[$j]))) {
                            $return_str .= $row[$j];
                        } else {
                            $return_str .= $db->quote($row[$j]);
                        }
                    } else {
                        $return_str .= 'NULL';
                    }
                    if ($j < ($num_fields - 1)) {
                        $return_str .= ',';
                    }
                }
                $counter++;
                if ($counter < ($result->rowCount())) {
                    $return_str .= "),";
                } else {
                    $return_str .= ");";
                }

                gzwrite($zp, $return_str);
                $return_str = "";
            }
            $return_str = "\n\n-- ------------------------------------------------ \n\n";
            gzwrite($zp, $return_str);
            $return_str = "";
        }

        if (count($pstm2->errorInfo()) > 3 || count($pstm3->errorInfo()) > 3 || count($result->errorInfo()) > 3) {
            return false;
        }
        gzclose($zp);
        return true;
    }

    public function backupDirectory(string $directory_path, string $save_name, string $upload_directory): bool
    {
        $file_name = $save_name . '_' . date(self::APPEND_DATE_FORMAT) . '.zip';
        if ($this->zipDirectory($directory_path, $file_name)) {
            return $this->uploadFileWithFTP($file_name, $upload_directory . '/' . $file_name);
        }
        return false;
    }

    protected function zipDirectory(string $directory_path, string $save_as): bool
    {
        $zip_file = new ZipArchive;
        if ($zip_file->open($save_as, ZipArchive::CREATE)) {
            $dir = opendir($directory_path);
            while ($file = readdir($dir)) {
                if (is_file($directory_path . $file)) {
                    $zip_file->addFile($directory_path . $file, $file);
                }
            }
            return $zip_file->close();
        }
        return false;
    }
}
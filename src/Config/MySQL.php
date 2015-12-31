<?php
$environment = array_merge($_ENV, $_SERVER);
// Database Settings
if (isset($environment['MYSQL_PORT'])) {
    $host = parse_url($environment['MYSQL_PORT']);

    $database = new \Thru\ActiveRecord\DatabaseLayer(array(
    'db_type'     => 'Mysql',
    'db_hostname' => isset($host['hostname'])?$host['hostname']:$host['host'],
    'db_port'     => $host['port'],
    'db_username' => $environment['MYSQL_ENV_MYSQL_USER'],
    'db_password' => $environment['MYSQL_ENV_MYSQL_PASSWORD'],
    'db_database' => $environment['MYSQL_ENV_MYSQL_DATABASE'],
    ));
} else {
    $database = new \Thru\ActiveRecord\DatabaseLayer(array(
    'db_type'     => 'Mysql',
    'db_hostname' => "localhost",
    'db_port'     => 3306,
    'db_username' => "bankingapp",
    'db_password' => "2l429q6Zug96iVU",
    'db_database' => "bankingapp",
    ));
}

<?php

require_once("bootstrap.php");

$host = 'http://localhost:4444/wd/hub'; // this is the default
$seleniumDriver = RemoteWebDriver::create($host, DesiredCapabilities::firefox());

$accounts = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(APP_ROOT . "/accounts.yml"));

foreach($accounts as $account_name => $details){
  $connectorName = "\\Thru\\Bank\\" . $details['connector'];
  $connector = new $connectorName();
  if(!$connector instanceof \Thru\Bank\BaseBankAccount){
    throw new Exception("Connector is not instance of BaseBankAccount");
  }
  $connector->setAuth($details['auth']);
  $connector->setSelenium($seleniumDriver);
  $connector->run();
}
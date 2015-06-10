<?php

require_once("bootstrap.php");

$settings = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(APP_ROOT . "/configuration.yml"));

$host = $settings['Selenium']['Host']; // this is the default
if(isset($settings['Selenium']['BrowserDriver'])){
  switch($settings['Selenium']['BrowserDriver']){
    case 'chrome':
      $desiredCapabilities = DesiredCapabilities::chrome();
      break;
    case 'firefox':
    default:
      $desiredCapabilities = DesiredCapabilities::firefox();
      break;
  }
}else{
  $desiredCapabilities = DesiredCapabilities::firefox();
}

$seleniumDriver = RemoteWebDriver::create($host, $desiredCapabilities);

foreach($settings['accounts'] as $account_name => $details){
  $connectorName = "\\Thru\\Bank\\" . $details['connector'];
  $connector = new $connectorName();
  if(!$connector instanceof \Thru\Bank\BaseBankAccount){
    throw new Exception("Connector is not instance of BaseBankAccount");
  }
  $connector->setAuth($details['auth']);
  $connector->setSelenium($seleniumDriver);
  $connector->run();
}
<?php

require_once("bootstrap.php");

$settings = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(APP_ROOT . "/configuration.yml"));

if(isset($settings['Selenium']['Host'])) {
  $host = $settings['Selenium']['Host'];
}elseif(isset($_SERVER['SELENIUM_ENV_TUTUM_SERVICE_FQDN'])){
  $ip = explode("/", $_SERVER['SELENIUM_ENV_TUTUM_IP_ADDRESS'],2);
  $ip = reset($ip);
  $host = "http://" . $ip . ":" . $_SERVER['SELENIUM_ENV_SELENIUM_PORT'] . "/wd/hub";
}else{
  $host = "http://localhost:4444/wd/hub";
}

echo "Connecting to Selenium at {$host} ... \n";
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

$run = new \Thru\Bank\Models\Run();

$run->save();

foreach($settings['Accounts'] as $account_name => $details){
  echo "Logging into {$account_name}...\n";

  $account = \Thru\Bank\Models\Account::FetchOrCreateByName($account_name);
  if(strtotime($account->last_check) >= time() - 60*60){
    echo " > Skipping, ran less than 60 minutes ago.\n\n";
    continue;
  }
  $connectorName = "\\Thru\\Bank\\Banking\\" . $details['connector'];
  $connector = new $connectorName($account_name);
  if(!$connector instanceof \Thru\Bank\Banking\BaseBankAccount){
    throw new Exception("Connector is not instance of BaseBankAccount");
  }
  $connector->setAuth($details['auth']);
  $connector->setSelenium($seleniumDriver);
  try {
    $connector->run($run);
  }catch(\Thru\Bank\Banking\BankAccountAuthException $authException){
    echo $authException->getMessage();
  }

  echo "\n\n";
}

$seleniumDriver->close();

$run->end();
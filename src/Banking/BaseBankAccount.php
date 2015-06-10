<?php
namespace Thru\Bank\Banking;

use Thru\Bank\Models\Run;

class BaseBankAccount {
  protected $auth;
  protected $selenium;
  private $screenshotCount;
  private $accountName;

  public function setAuth($auth){
    $this->auth = $auth;
    return $this;
  }
  public function getAuth($aspect){
    if(isset($this->auth[$aspect])){
      return $this->auth[$aspect];
    }
    return false;
  }

  public function setSelenium(\RemoteWebDriver $selenium){
    $this->selenium = $selenium;
    return $this;
  }

  /**
   * @return \RemoteWebDriver
   */
  public function getSelenium(){
    return $this->selenium;
  }

  public function takeScreenshot($name){
    $this->screenshotCount++;
    $name = "{$this->getAccountName()}-{$this->screenshotCount}-{$name}";
    $name = str_replace(" ", "-", $name);
    $this->getSelenium()->takeScreenshot(APP_ROOT . "/screenshots/{$name}.png");
  }

  public function run(Run $run){

  }

  public function cleanUp(){
    $this->getSelenium()->close();
  }

  public function __construct($accountName){
    $this->accountName = $accountName;
  }

  public function getAccountName(){
    return $this->accountName;
  }


}
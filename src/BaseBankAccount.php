<?php
namespace Thru\Bank;

class BaseBankAccount {
  protected $auth;
  protected $selenium;
  private $screenshotCount;

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
    $name = str_replace(" ", "-", $name);
    $this->getSelenium()->takeScreenshot(APP_ROOT . "/screenshots/{$this->screenshotCount}-{$name}.png");
  }

  public function run(){

  }

  public function __construct(){

  }


}
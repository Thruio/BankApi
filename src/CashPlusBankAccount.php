<?php
namespace Thru\Bank;

class CashPlusBankAccount extends BaseBankAccount {
  protected $baseUrl = "https://secure.membersaccounts.com";

  public function __construct($accountName){
    parent::__construct($accountName);
  }

  public function run(){
    parent::run();
    $this->getSelenium()->get($this->baseUrl);
    $this->getSelenium()->findElement(\WebDriverBy::name("ctl00\$_login\$UserName"))->clear()->sendKeys($this->getAuth("username"));
    $this->getSelenium()->findElement(\WebDriverBy::name("ctl00\$_login\$Password"))->clear()->sendKeys($this->getAuth("password"));
    $this->getSelenium()->findElement(\WebDriverBy::name("ctl00\$_login\$LoginButton"))->click();
    $this->takeScreenshot("Logged in");
    $this->getSelenium()->findElement(\WebDriverBy::cssSelector("a[href='PrimaryCard.aspx']"))->click();
    $this->takeScreenshot("Account Details");
    $currentBalance = $this->getSelenium()->findElement(\WebDriverBy::id("ctl00_ctrlAccountBalance1_lblCurrentBalance"))->getText();
    echo "Current balance is {$currentBalance}\n";
  }
}
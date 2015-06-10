<?php
namespace Thru\Bank\Banking;

class SmileBankAccount extends CooperativeBankAccount {
  public function __construct($accountName){
    $this->baseUrl = "https://banking.smile.co.uk/SmileWeb/start.do";
    parent::__construct($accountName);
  }

}
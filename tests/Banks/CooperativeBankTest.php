<?php
namespace Thru\Bank\Test\Banks;

use Thru\Bank\CooperativeBankAccount;

class CooperativeBankTest extends \PHPUnit_Framework_TestCase {
  public function setUp(){

  }

  public function testInheritance(){
    $a = new CooperativeBankAccount();
    $this->assertTrue(is_subclass_of($a, '\\Thru\\Bank\\BaseBankAccount'));
  }
}

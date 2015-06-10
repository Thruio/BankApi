<?php
namespace Thru\Bank\Test\Banks;

use Thru\Bank\Banking\CooperativeBankAccount;

class CooperativeBankTest extends \PHPUnit_Framework_TestCase {
  public function setUp(){

  }

  public function testInheritance(){
    $a = new CooperativeBankAccount("test");
    $this->assertTrue(is_subclass_of($a, '\\Thru\\Bank\\Banking\\BaseBankAccount'));
  }
}

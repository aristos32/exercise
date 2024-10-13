<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\StockHandleController;

class StockHandleControllerTest extends TestCase
{
    public function testCalculatePercentageChange()
    {
        $controller = new StockHandleController();

        // Use reflection to access the private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('calculatePercentageChange');
        $method->setAccessible(true);

        // Test cases
        $this->assertEquals(0, $method->invokeArgs($controller, [100, 100]));
        $this->assertEquals(50, $method->invokeArgs($controller, [150, 100]));
        $this->assertEquals(-50, $method->invokeArgs($controller, [50, 100]));
        $this->assertEquals(100, $method->invokeArgs($controller, [200, 100]));
        $this->assertEquals(-100, $method->invokeArgs($controller, [0, 100]));
        $this->assertEquals(0, $method->invokeArgs($controller, [0, 0]));
        $this->assertEquals(0, $method->invokeArgs($controller, [100, 0]));
    }
}

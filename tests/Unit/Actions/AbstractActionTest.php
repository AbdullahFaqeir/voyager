<?php

namespace TCG\Voyager\Tests\Unit\Actions;

use TCG\Voyager\Actions\AbstractAction;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Models\User;
use TCG\Voyager\Tests\TestCase;

/**
 * Concrete test double for AbstractAction.
 *
 * PHPUnit 12+ removed MockBuilder::getMockForAbstractClass(), so the
 * abstract methods are implemented here with configurable return values.
 */
class TestAction extends AbstractAction
{
    public $defaultRouteReturn;
    public $customRouteReturn;
    public $dataTypeReturn;
    public $attributesReturn = [];

    public function getTitle()
    {
        return 'test';
    }

    public function getIcon()
    {
        return 'voyager-test';
    }

    public function getDefaultRoute()
    {
        return $this->defaultRouteReturn;
    }

    public function getDataType()
    {
        return $this->dataTypeReturn;
    }

    public function getAttributes()
    {
        return $this->attributesReturn;
    }

    public function getCustomRoute()
    {
        return $this->customRouteReturn;
    }
}

class AbstractActionTest extends TestCase
{
    /**
     * The users DataType instance.
     *
     * @var \TCG\Voyager\Models\DataType
     */
    protected $userDataType;

    /**
     * A dummy user instance.
     *
     * @var \TCG\Voyager\Models\User
     */
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->userDataType = Voyager::model('DataType')->where('name', 'users')->first();
        $this->user = User::factory()->create();
    }

    /**
     * This test checks that `getRoute` method calls the `getDefaultRoute`
     * method if the given key has no matching route method.
     */
    public function testGetRouteWithEmptyKey()
    {
        $stub = new TestAction($this->userDataType, $this->user);

        // The `getDefaultRoute` method is called as default inside the
        // `getRoute` method to retrieve the route.
        $stub->defaultRouteReturn = true;

        $this->assertTrue($stub->getRoute($this->userDataType->name));
    }

    /**
     * This test checks that `getRoute` method calls the expected method when a
     * key is given.
     */
    public function testGetRouteWithCustomKey()
    {
        $stub = new TestAction($this->userDataType, $this->user);

        // The key that's passed to the `getRoute` method will be capitalized
        // and putted between 'get' and 'Route'. Calling `getRoute('custom')`
        // will call the `getCustomRoute` method if it's defined.
        $stub->customRouteReturn = true;

        $this->assertTrue($stub->getRoute('custom'));
    }

    /**
     * This test checks that `getAttributes` method will give us the expected
     * output.
     */
    public function testConvertAttributesToHtml()
    {
        $stub = new TestAction($this->userDataType, $this->user);

        $stub->attributesReturn = [
            'class'   => 'class1 class2',
            'data-id' => 5,
            'id'      => 'delete-5',
        ];

        $this->assertEquals('class="class1 class2" data-id="5" id="delete-5"', $stub->convertAttributesToHtml());
    }

    /**
     * This test checks that `shouldActionDisplayOnDataType` method returns true
     * if the action should be displayed for every data type.
     */
    public function testShouldActionDisplayOnDataTypeWithDefaultDataType()
    {
        $stub = new TestAction($this->userDataType, $this->user);

        $this->assertTrue($stub->shouldActionDisplayOnDataType());
    }

    /**
     * This test checks that `shouldActionDisplayOnDataType` method returns true
     * if the action should only be displayed for a specific data type.
     */
    public function testTrueIsReturnedIfDataTypeMatchesTheOneWhereTheActionWasCreatedFor()
    {
        $stub = new TestAction($this->userDataType, $this->user);

        $stub->dataTypeReturn = $this->userDataType->name;

        $this->assertTrue($stub->shouldActionDisplayOnDataType());
    }

    /**
     * This test checks that `shouldActionDisplayOnDataType` method returns false
     * if the action should only be displayed for a specific data type.
     */
    public function testFalseIsReturnedIfDataTypeDoesNotMatchesTheOneWhereTheActionWasCreatedFor()
    {
        $stub = new TestAction($this->userDataType, $this->user);

        $stub->dataTypeReturn = 'not users'; // different data type

        $this->assertFalse($stub->shouldActionDisplayOnDataType());
    }
}

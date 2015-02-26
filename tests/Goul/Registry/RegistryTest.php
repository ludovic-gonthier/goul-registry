<?hh //partial

use Goul\Registry\Registry;

use Goul\TestCase\TestCase;

class RegistryTest extends \PHPUnit_Framework_TestCase
{
    private $registry;

    private $dataReflection;

    public function setUp()
    {
        $this->registry  = new Registry();

        $this->dataReflection = new \ReflectionProperty(Registry::class, 'data');
        $this->dataReflection->setAccessible(true);
    }

    /**
     * Test offset exists succeed on set key
     */
    public function testOffsetExistsSuccess()
    {
        $this->dataReflection->setValue($this->registry, Map{'test' => 42});
        $this->assertTrue($this->registry->contains('test'));
    }

    /**
     * Test offset exists correctly fail on unset key
     */
    public function testOffsetExistsFailure()
    {
        $this->assertFalse($this->registry->contains('not_set'));
    }

    /**
     * @provide testOffsetSet
     * @provide testOffsetSetOverrideFailure
     */
    public function offsetSetProvider()
    {
        $variableSetter = 42;
        $closureSetter  = function() { return 21; };

        return array(
            array(
                $variableSetter,
                array(
                    'setter' => $variableSetter,
                    'data'   => $variableSetter,
                    'set'    => true
                )
            ),
            array(
                $closureSetter,
                array(
                    'setter' => $closureSetter,
                    'data'   => null,
                    'set'    => false
                )
            )
        );
    }

    /**
     * Test the correct storage of a variable or a closure in the depency injection containter
     *
     * @dataProvider offsetSetProvider
     */
    public function testOffsetSet($setter, $expected)
    {
        $this->registry->set('test', $setter);

        $reflectDatas = $this->dataReflection->getValue($this->registry);

        $this->assertEquals($reflectDatas['test'], $expected);
    }

    /**
     * An already set keys cannot be override
     *
     * test that a set key cannot be override:
     *    $container['test'] = 'test';
     *    $container['test'] = 'not good'; // must fail
     *
     * @dataProvider offsetSetProvider
     */
    public function testOffsetSetOverrideFailure($setter)
    {
        $this->registry->set('test', $setter);

        $this->setExpectedException('RuntimeException');

        $this->registry->set('test', $setter);
    }

    /**
     * @provide testOffsetSetOverrideFailure
     */
    public function offsetGetProvider()
    {
        $variableSetter = 42;
        $closureSetter  = function() { return 21; };

        return array(
            array(
                array(
                    'setter' => $variableSetter,
                    'data' => $variableSetter,
                    'set' => true
                ),
                $variableSetter
            ),
            array(
                array(
                    'setter' => $closureSetter,
                    'data' => null,
                    'set' => false
                ),
                21
            ),
            array(
                array(
                    'setter' => array('RegistryTest', 'foo'),
                    'data' => null,
                    'set' => false
                ),
                42
            ),
            array(
                array(
                    'setter' => array($this, 'bar'),
                    'data' => null,
                    'set' => false
                ),
                84
            ),
            array(
                array(
                    'setter' => array('RegistryTest', 'notExisting'),
                    'data' => array('RegistryTest', 'notExisting'),
                    'set' => false
                ),
                array('RegistryTest', 'notExisting')
            )
        );
    }

    public static function foo()
    {
        return 42;
    }

    public function bar()
    {
        return 84;
    }

    /**
     * Test the correct storage of a variable or a closure in the depency injection containter
     *
     * @dataProvider offsetGetProvider
     */
    public function testOffsetGet($setter, $expected)
    {
        $this->dataReflection->setValue($this->registry, Map{'test' => $setter});

        $this->assertEquals($this->registry->get('test'), $expected);
    }

    /**
     * Trying to access an unknown key must throw an InvalidArgumentException exception
     */
    public function testOffsetGetUnknownKey()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->registry->get('not_test');
    }

    /**
     * A closure set in the container must be called once,
     * when the user try to get the property for the first time
     */
    public function testOffsetGetClosureInstanciate()
    {
        $setter = function () { return 21; };

        $value = array(
            'setter' => $setter,
            'data' => null,
            'set' => false
        );
        $this->dataReflection->setValue($this->registry, Map{'test' => $value});

        // offsetGet is called here
        $this->registry->get('test');

        $reflectDatas = $this->dataReflection->getValue($this->registry);

        $expected = array(
            'setter' => $setter,
            'data'   => 21,
            'set'    => true
        );
        $this->assertEquals($reflectDatas['test'], $expected);
    }

    /**
     * Test the call of the closure is done only once
     */
    public function testOffsetGetClosureInstanciateOnce()
    {
        $setter = function () { return 21; };

        $value = array(
            'setter' => $setter,
            'data' => null,
            'set' => false
        );
        $this->dataReflection->setValue($this->registry, Map{'test' => $value});

        $this->registry->get('test');

        $expected = array(
            'setter' => $setter,
            'data'   => 42,
            'set'    => true
        );

        $this->dataReflection->setValue($this->registry, Map{'test' => $expected});

        $this->registry->get('test');
        $reflectDatas = $this->dataReflection->getValue($this->registry);

        $this->assertEquals($reflectDatas['test'], $expected);
    }

    /**
     * Test the removal of a container key
     */
    public function testOffsetUnset()
    {
        $value = array(
            'setter' => 42,
            'data' => 42,
            'set' => true
        );

        $this->dataReflection->setValue($this->registry, Map{'test' => $value});

        $this->registry->remove('test');

        $reflectDatas = $this->dataReflection->getValue($this->registry);

        $this->assertFalse(array_key_exists('test', $reflectDatas));
    }
}

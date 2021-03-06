<?php
namespace Payum\Tests\Extension;

use Payum\Extension\EndlessCycleDetectorExtension;

class EndlessCycleDetectorExtensionTest extends \PHPUnit_Framework_TestCase 
{
    /**
     * @test
     */
    public function shouldImplementExtensionInterface()
    {
        $rc = new \ReflectionClass('Payum\Extension\EndlessCycleDetectorExtension');

        $this->assertTrue($rc->implementsInterface('Payum\Extension\ExtensionInterface'));
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments()
    {
        new EndlessCycleDetectorExtension;
    }

    /**
     * @test
     */
    public function shouldSetDefaultLimitInConstructor()
    {
        $extension = new EndlessCycleDetectorExtension;

        $this->assertAttributeEquals(100, 'limit', $extension);
    }

    /**
     * @test
     */
    public function shouldAllowSetLimitInInConstructor()
    {
        $extension = new EndlessCycleDetectorExtension($expectedLimit = 55);

        $this->assertAttributeEquals($expectedLimit, 'limit', $extension);
    }

    /**
     * @test
     */
    public function shouldSetRequestAsFirstOnPreExecuteIfNotSet()
    {
        $request = new \stdClass;
        
        $extension = new EndlessCycleDetectorExtension;

        $extension->onPreExecute($request);
        
        $this->assertAttributeSame($request, 'firstRequest', $extension);
    }

    /**
     * @test
     */
    public function shouldSetFirstRequestToNullOnInteractiveRequestIfRequestEqualsFirst()
    {
        $request = new \stdClass;

        $extension = new EndlessCycleDetectorExtension;

        $extension->onPreExecute($request);
        
        //guard
        $this->assertAttributeSame($request, 'firstRequest', $extension);

        $extension->onInteractiveRequest($this->createInteractiveRequestMock(), $request, $this->createActionMock());

        $this->assertAttributeEquals(null, 'firstRequest', $extension);
    }

    /**
     * @test
     */
    public function shouldNotSetFirstRequestToNullOnInteractiveRequestIfRequestNotEqualsFirst()
    {
        $request = new \stdClass;
        $otherRequest = new \stdClass;

        $extension = new EndlessCycleDetectorExtension;

        $extension->onPreExecute($request);

        //guard
        $this->assertAttributeSame($request, 'firstRequest', $extension);

        $extension->onInteractiveRequest($this->createInteractiveRequestMock(), $otherRequest, $this->createActionMock());

        $this->assertAttributeSame($request, 'firstRequest', $extension);
    }

    /**
     * @test
     */
    public function shouldSetFirstRequestToNullOnExceptionIfRequestEqualsFirst()
    {
        $request = new \stdClass;

        $extension = new EndlessCycleDetectorExtension;

        $extension->onPreExecute($request);

        //guard
        $this->assertAttributeSame($request, 'firstRequest', $extension);

        $extension->onException(new \Exception, $request, $this->createActionMock());

        $this->assertAttributeEquals(null, 'firstRequest', $extension);
    }

    /**
     * @test
     */
    public function shouldNotSetFirstRequestToNullOnExceptionIfRequestNotEqualsFirst()
    {
        $request = new \stdClass;
        $otherRequest = new \stdClass;

        $extension = new EndlessCycleDetectorExtension;

        $extension->onPreExecute($request);

        //guard
        $this->assertAttributeSame($request, 'firstRequest', $extension);

        $extension->onException(new \Exception, $otherRequest, $this->createActionMock());

        $this->assertAttributeSame($request, 'firstRequest', $extension);
    }

    /**
     * @test
     */
    public function shouldSetFirstRequestToNullOnPostExecuteIfRequestEqualsFirst()
    {
        $request = new \stdClass;

        $extension = new EndlessCycleDetectorExtension;

        $extension->onPreExecute($request);

        //guard
        $this->assertAttributeSame($request, 'firstRequest', $extension);

        $extension->onPostExecute($request, $this->createActionMock());

        $this->assertAttributeEquals(null, 'firstRequest', $extension);
    }

    /**
     * @test
     */
    public function shouldNotSetFirstRequestToNullOnPostExecuteIfRequestNotEqualsFirst()
    {
        $request = new \stdClass;
        $otherRequest = new \stdClass;

        $extension = new EndlessCycleDetectorExtension;

        $extension->onPreExecute($request);

        //guard
        $this->assertAttributeSame($request, 'firstRequest', $extension);

        $extension->onPostExecute($otherRequest, $this->createActionMock());

        $this->assertAttributeSame($request, 'firstRequest', $extension);
    }

    /**
     * @test
     */
    public function shouldIncrementCounterOnPreExecute()
    {
        $extension = new EndlessCycleDetectorExtension;
        
        $extension->onPreExecute(new \stdClass);
        $this->assertAttributeEquals(1, 'cyclesCounter', $extension);

        $extension->onPreExecute(new \stdClass);
        $this->assertAttributeEquals(2, 'cyclesCounter', $extension);
    }

    /**
     * @test
     */
    public function shouldResetCounterToZeroIfFirstRequestOnPreExecute()
    {
        $extension = new EndlessCycleDetectorExtension;

        $extension->onPreExecute($firstRequest = new \stdClass);
        $this->assertAttributeEquals(1, 'cyclesCounter', $extension);

        $extension->onPreExecute(new \stdClass);
        $this->assertAttributeEquals(2, 'cyclesCounter', $extension);

        $extension->onPostExecute($firstRequest, $this->createActionMock());

        $extension->onPreExecute(new \stdClass);
        $this->assertAttributeEquals(1, 'cyclesCounter', $extension);
    }

    /**
     * @test
     * 
     * @expectedException \Payum\Exception\LogicException
     * @expectedExceptionMessage Possible endless cycle detected. ::onPreExecute was called 2 times before reach the limit.
     */
    public function throwIfCycleCounterReachLimit()
    {
        $extension = new EndlessCycleDetectorExtension($expectedLimit = 2);

        $extension->onPreExecute(new \stdClass);
        $extension->onPreExecute(new \stdClass);
        $extension->onPreExecute(new \stdClass);
    }

    protected function createInteractiveRequestMock()
    {
        return $this->getMock('Payum\Request\InteractiveRequestInterface');
    }
    
    protected function createActionMock()
    {
        return $this->getMock('Payum\Action\ActionInterface');
    }
}
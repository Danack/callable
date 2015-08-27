<?php

require_once "callable.php";


function bar($param1)
{
    return $param1;
}

$closure = function($param1) {
    return $param1;
};

function test($fn)
{
    static $count = 0;
    $input = "foo".$count;
    $count++;

    $output = $fn($input);
    return $input === $output;
}

class Foo
{
    public static function publicStaticFunction($param1)
    {
        return $param1;
    }
    
    private static function privateStaticFunction($param1)
    {
        return $param1;
    }
    
    private function privateInstanceFunc($param1)
    {
        return $param1;
    }
    
    public function publicInstanceFunc($param1)
    {
        return $param1;
    }
    
    public function closePrivateStatic()
    {
        return toCallable([__CLASS__, 'privateStaticFunction']);
    }
}


class SubFoo extends Foo {
    
    public function closePrivateStaticInvalid()
    {
        return toCallable([__CLASS__, 'privateStaticFunction']);
    }
    
    
    public function closePrivateInvalid()
    {
        return toCallable([$this, 'privateInstanceFunc']);
    }
}

class PublicInvokable
{
    public function __invoke($param1)
    {
        return $param1;
    }
}

class PrivateInvokable
{
    private function __invoke($param1)
    {
        return $param1;
    }
}

$exceptionTests = [

    ['Foo', 'privateInstanceFunc'],
    'Foo::privateInstanceFunc',
    [ new Foo, 'privateStaticFunction'],
    ['Foo', 'privateStaticFunction'],
    'Foo::privateStaticFunction',
    new PrivateInvokable,
    [new SubFoo, 'closePrivateStaticInvalid'],
    
];


$successTests = [
    [['Foo', 'publicStaticFunction'], null],
    ['Foo::publicStaticFunction', null],
    [[new Foo, 'publicInstanceFunc'], null],
    ['bar', null],
    [$closure, null],
    [new PublicInvokable, null],
];




    
$closeOverPrivateInstanceMethod = function () {
    $foo = new Foo;
    return $foo->closePrivateStatic();
};

$closeOverParentPrivateInstanceMethod = function () {
    $subFoo = new SubFoo;
    return $subFoo->closePrivateInvalid();
};

$closureSuccessTests = [
    $closeOverPrivateInstanceMethod,
];

$closureFailureTests = [
    $closeOverParentPrivateInstanceMethod,
];




foreach ($successTests as $successTest) {
    list($callable, $scope) = $successTest;

    try {
        $fn = toCallable($callable);
        
        if (!test($fn)) {
            echo "test ".var_export($successTest, true)." failed as input ($input) doesn't match output ($output)\n";
        }
    }
    catch (\Exception $e) {
        echo "test ".var_export($successTest, true)." failed with exception ".$e->getMessage()."\n";
    }
    
}


foreach ($exceptionTests as $exceptionTest) {

    try {
        $fn = toCallable($exceptionTest);
        echo "Test failed to fail: ".var_export($exceptionTest, true)."\n";
    }
    catch (\LogicException $le) {
        //This is the expected outcome.
    }
}

$count = 0;
foreach ($closureSuccessTests as $closureTest) {
    try {
        $fn = $closureTest();
        if (!test($fn)) {
            echo "Closure test $count failed.\n";
        }

    }
    catch (\Exception $e) {
        echo "Closure test $count failed with exception ".$e->getMessage()."\n";
    }
    $count++;
}


$count = 0;
foreach ($closureFailureTests as $closureFailureTest) {
    try {
        $fn = $closureFailureTest();
        echo "closureFailureTest $count failed to fail.";
    }
    catch (\LogicException $e) {
        //this is the expected behaviour.
    }
    $count++;
}


echo "OK";

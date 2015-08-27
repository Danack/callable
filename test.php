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
    [['Foo', 'privateInstanceFunc'], null],
    ['Foo::privateInstanceFunc', null],
    [[ new Foo, 'privateStaticFunction'], null],
    [['Foo', 'privateStaticFunction'], null],
    ['Foo::privateStaticFunction', null],
    [new PrivateInvokable, null],
];


$successTests = [
    [['Foo', 'publicStaticFunction'], null],
    ['Foo::publicStaticFunction', null],
    [[new Foo, 'publicInstanceFunc'], null],
    ['bar', null],
    [$closure, null],
    [new PublicInvokable, null],
];

$count = 0;

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
    list($callable, $scope) = $exceptionTest;

    try {
        $fn = toCallable($callable);
        echo "Test failed to fail: ".var_export($exceptionTest, true)."\n";
    }
    catch (\LogicException $le) {
        //This is the expected outcome.
    }
}

$closeOverPrivateInstanceMethod = function () {
    $foo = new Foo;
    return $foo->closePrivateStatic();
};


$closureTests = [
    $closeOverPrivateInstanceMethod,
];

foreach ($closureTests as $closureTest) {
    
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


echo "OK";





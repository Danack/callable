<?php


function getCallingScope()
{
    var_dump(debug_backtrace());
    
    exit(0);
    return 'foo';
}

function checkCallingScopeAllowedAccess(\ReflectionMethod $reflMethod, $item) {
    
    if ($reflMethod->isPublic() == true) {
        return true;
    }
    
    if ($reflMethod->isProtected() == true) {
        //must be class or sub-class
        echo "not implemented yet";
        exit(0);
    }
    
    
    if ($reflMethod->isPrivate() == true) {
        
        $callingScope = getCallingScope();
        
        $message = sprintf(
            "Invalid callable - method %s for class %s is static, cannot be accessed from this scope",
            $item[1],
            $item[0]
        );
        throw new \LogicException($message);
    }
}

function toCallable($item)
{
    // Normalise from 'class::method' to ['class', 'method']
    if (is_string($item)) {
        $colonPosition = strpos($item, '::');
        if ($colonPosition !== false) {
            $class = substr($item, 0, $colonPosition);
            $method = substr($item, $colonPosition + 2);
            $item = [$class, $method];
        }
    }

    if (is_string($item)) { //It should be a function
        if (function_exists($item) == false) {
            throw new \LogicException("Invalid callable - '$item' is not a function");
        }
        $reflFunction = new \ReflectionFunction($item);

        return $reflFunction->getClosure();
    }
    else if (is_array($item)) {
        if (count($item) != 2) {
            throw new \LogicException("Unknown callable type");
        }

        if (isset($item[0]) == false || isset($item[1]) == false) {
            throw new \LogicException("Invalid callable - values must be in index 0 and 1");
        }

        if (is_string($item[1]) == false) {
            throw new \LogicException("Invalid callable - values in index 1 must be string");
        }

        if (is_object($item[0])) {
            $reflClass = new ReflectionClass($item[0]);
            if ($reflClass->hasMethod($item[1]) == false) {
                throw new \LogicException("Invalid callable - instance does not have method ".$item[1]);
            }
            $reflMethod = $reflClass->getMethod($item[1]);
            
            if ($reflMethod->isPrivate() == true) {
                $message = sprintf(
                    "Invalid callable - method %s for class %s is static, cannot be accessed from this scope",
                    $item[1],
                    get_class($item[0])
                );
                throw new \LogicException($message);
            }

            return $reflMethod->getClosure($item[0]);
        }
        else if (is_string($item[0])) {
            $reflClass = new ReflectionClass($item[0]);
            if ($reflClass->hasMethod($item[1]) == false) {
                throw new \LogicException("Invalid callable - instance does not have method ".$item[1]);
            }
            $reflMethod = new ReflectionMethod($item[0], $item[1]);
            
            if ($reflMethod->isStatic() == false) {
                $message = sprintf(
                    "Invalid callable - method %s for class %s is not static, cannot be accessed statically",
                    $item[1],
                    $item[0]
                );
                throw new \LogicException($message);
            }
            
            checkCallingScopeAllowedAccess($reflMethod, $item);

            return $reflMethod->getClosure();
        }
        
        throw new \LogicException("Unknown callable type");
    }
    else if ($item instanceof Closure) {
        return $item;
    }
    else if (is_object($item) && method_exists($item, '__invoke')) {
        $reflection = new ReflectionMethod($item, '__invoke');
        
        if ($reflection->isPrivate()) {
            throw new \LogicException("__invoke method of class ".get_class($item)." is private");
        }

        return $reflection->getClosure($item);
    }

    throw new \LogicException("Unknown callable type");
}



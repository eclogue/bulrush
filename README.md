# Bulrush
In the meanwhile I smile and I sing all alone. In the meanwhile the air is filling with the perfume of promise.
### overview
    php coroutine control flow. High performance yield handlers. 
    
### Install
    `composer require eclogue/bulrush`

### Usage
```
use Bulrush\Scheduler;

function gen () {
    $url = 'https://github.com/eclogue/bulrush';
    $response = yield file_get_contents($url);
    
    return $response;
}

$scheduler = new Scheduler();
$scheduler->add(gen());
$scheduler->run();
```

    

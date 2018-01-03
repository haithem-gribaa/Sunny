<?php
namespace Gribaa;
class NotFoundException extends \Exception {
    
    public function __construct() {
        parent::__construct('No route found for this url');
    }
}

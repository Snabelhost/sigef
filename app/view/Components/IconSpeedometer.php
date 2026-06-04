<?php

namespace App\View\Components;

use Illuminate\View\Component;

class IconSpeedometer extends Component
{
    public string $class;
    
    public function __construct(string $class = 'w-5 h-5')
    {
        $this->class = $class;
    }
    
    public function render()
    {
        return view('components.icon-speedometer');
    }
}

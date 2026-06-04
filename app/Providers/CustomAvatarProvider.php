<?php

namespace App\Providers;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;

class CustomAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $name = $this->getName($record);
        
        // Fundo preto para modo dark
        $background = '000000';
        $color = 'ffffff';
        
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=' . $background . '&color=' . $color . '&size=128&font-size=0.4&bold=true';
    }
    
    protected function getName(Model $record): string
    {
        if (method_exists($record, 'getFilamentName')) {
            return $record->getFilamentName();
        }
        
        if (isset($record->name)) {
            return $record->name;
        }
        
        if (isset($record->email)) {
            return $record->email;
        }
        
        return 'User';
    }
}


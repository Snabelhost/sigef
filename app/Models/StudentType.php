<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'color',
        'order',
        'has_phase',
        'phase_name',
        'is_active',
    ];

    protected $casts = [
        'has_phase' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'student_type_id');
    }

    public static function getColorOptions(): array
    {
        return [
            'gray' => 'Cinza (Neutro)',
            'primary' => 'Azul SIGEF (Primario)',
            'secondary' => 'Roxo (Secundario)',
            'success' => 'Verde (Sucesso)',
            'warning' => 'Amarelo (Aviso)',
            'danger' => 'Vermelho (Perigo)',
            'info' => 'Azul Claro (Informacao)',
            'slate' => 'Ardosia',
            'zinc' => 'Zinco',
            'neutral' => 'Neutro',
            'stone' => 'Pedra',
            'mauve' => 'Malva',
            'olive' => 'Oliva',
            'mist' => 'Nevoa',
            'taupe' => 'Taupe',
            'red' => 'Vermelho',
            'orange' => 'Laranja',
            'amber' => 'Ambar',
            'yellow' => 'Amarelo',
            'lime' => 'Lima',
            'green' => 'Verde',
            'emerald' => 'Esmeralda',
            'teal' => 'Verde-azulado',
            'cyan' => 'Ciano',
            'sky' => 'Azul Ceu',
            'blue' => 'Azul',
            'indigo' => 'Indigo',
            'violet' => 'Violeta',
            'purple' => 'Roxo',
            'fuchsia' => 'Fucsia',
            'pink' => 'Rosa',
            'rose' => 'Rose',
        ];
    }

    /**
     * Obter ID do StudentType pelo nome.
     * Cria o tipo automaticamente se nao existir.
     */
    public static function getIdByName(string $name): ?int
    {
        $type = self::where('name', $name)->first();

        if ($type) {
            return $type->id;
        }

        $type = self::create([
            'name' => $name,
            'color' => 'gray',
            'order' => self::max('order') + 1,
            'is_active' => true,
        ]);

        return $type->id;
    }
}

<?php

namespace App\Filament\Forms;

use Filament\Forms;
use Filament\Schemas\Schema;

/**
 * Classe com formulários partilhados entre painéis
 */
class SharedForms
{
    /**
     * Formulário de Candidato/Alistado com Wizard
     */
    public static function getCandidateFormSchema(): array
    {
        return [
            \Filament\Schemas\Components\Wizard::make([
                // Etapa 1 - Identificação Pessoal
                \Filament\Schemas\Components\Wizard\Step::make('Identificação Pessoal')
                    ->icon('heroicon-o-user')
                    ->description('Dados pessoais do alistado')
                    ->schema([
                        Forms\Components\FileUpload::make('photo')
                            ->label('Foto')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                            ->directory('candidates')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('id_number')
                            ->label('Nº do BI')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(191)
                            ->validationMessages([
                                'unique' => 'Já existe um candidato com este Nº de BI.',
                            ]),
                        Forms\Components\TextInput::make('full_name')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(191)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Já existe um alistado com este nome.',
                            ]),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Data de Nascimento')
                            ->required(),
                        Forms\Components\Select::make('gender')
                            ->label('Género')
                            ->options([
                                'Masculino' => 'Masculino',
                                'Feminino' => 'Feminino',
                            ])
                            ->required(),
                        Forms\Components\Select::make('marital_status')
                            ->label('Estado Civil')
                            ->options([
                                'solteiro' => 'Solteiro(a)',
                                'casado' => 'Casado(a)',
                                'divorciado' => 'Divorciado(a)',
                                'viuvo' => 'Viúvo(a)',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('father_name')
                            ->label('Nome do Pai')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('mother_name')
                            ->label('Nome da Mãe')
                            ->maxLength(191),
                    ])->columns(2),

                // Etapa 2 - Localização e Contacto
                \Filament\Schemas\Components\Wizard\Step::make('Localização e Contacto')
                    ->icon('heroicon-o-map-pin')
                    ->description('Endereço e contactos')
                    ->schema([
                        Forms\Components\Select::make('province_id')
                            ->label('Província')
                            ->options(\App\Models\Province::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(fn ($set) => $set('municipality_id', null)),
                        Forms\Components\Select::make('municipality_id')
                            ->label('Município')
                            ->options(function ($get) {
                                $provinceId = $get('province_id');
                                if (!$provinceId) {
                                    return [];
                                }
                                return \App\Models\Municipality::where('province_id', $provinceId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Textarea::make('address')
                            ->label('Endereço')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefone')
                            ->tel()
                            ->prefix('+244')
                            ->placeholder('9XX XXX XXX')
                            ->mask('999 999 999')
                            ->maxLength(191)
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Já existe um candidato com este número de telefone.',
                            ]),
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(191)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Já existe um candidato com este e-mail.',
                            ]),
                    ])->columns(2),

                // Etapa 3 - Habilitações
                \Filament\Schemas\Components\Wizard\Step::make('Habilitações')
                    ->icon('heroicon-o-academic-cap')
                    ->description('Formação académica')
                    ->schema([
                        Forms\Components\Select::make('education_level')
                            ->label('Nível Académico')
                            ->options([
                                'Ensino Primário' => 'Ensino Primário (1ª - 6ª Classe)',
                                '7ª Classe' => '7ª Classe',
                                '8ª Classe' => '8ª Classe',
                                '9ª Classe' => '9ª Classe (I Ciclo)',
                                '10ª Classe' => '10ª Classe',
                                '11ª Classe' => '11ª Classe',
                                '12ª Classe' => '12ª Classe (II Ciclo)',
                                '13ª Classe' => '13ª Classe',
                                'Técnico Médio' => 'Técnico Médio',
                                'Técnico Profissional' => 'Técnico Profissional',
                                'Bacharelato' => 'Bacharelato',
                                'Licenciatura' => 'Licenciatura',
                                'Pós-Graduação' => 'Pós-Graduação',
                                'Mestrado' => 'Mestrado',
                                'Doutoramento' => 'Doutoramento',
                            ])
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('education_area')
                            ->label('Área de Formação')
                            ->options([
                                'Administração e Gestão' => 'Administração e Gestão',
                                'Ciências da Educação' => 'Ciências da Educação',
                                'Ciências Jurídicas' => 'Ciências Jurídicas / Direito',
                                'Ciências Policiais' => 'Ciências Policiais',
                                'Contabilidade e Auditoria' => 'Contabilidade e Auditoria',
                                'Economia' => 'Economia',
                                'Engenharia Informática' => 'Engenharia Informática',
                                'Informática' => 'Informática',
                                'Psicologia' => 'Psicologia',
                                'Outra' => 'Outra',
                            ])
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('recruitment_type_id')
                            ->label('Tipo de Recrutamento')
                            ->relationship('recruitmentType', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('academic_year_id')
                            ->label('Ano Académico')
                            ->options(\App\Models\AcademicYear::where('is_active', true)->pluck('year', 'id'))
                            ->default(fn () => \App\Models\AcademicYear::where('is_active', true)->first()?->id)
                            ->required(),
                        
                        // Tipo de Aluno definido automaticamente como Alistado
                        Forms\Components\Hidden::make('student_type')
                            ->default('Alistado'),
                    ])->columns(2),

                // Etapa 4 - Documentos
                \Filament\Schemas\Components\Wizard\Step::make('Documentos')
                    ->icon('heroicon-o-document-text')
                    ->description('Carregamento de documentos')
                    ->schema([
                        Forms\Components\FileUpload::make('bilhete_identidade')
                            ->label('Bilhete de Identidade')
                            ->directory('candidates/documents/bi'),
                        Forms\Components\FileUpload::make('certificado_doc')
                            ->label('Certificado')
                            ->directory('candidates/documents/certificados'),
                        Forms\Components\FileUpload::make('curriculum')
                            ->label('Curriculum')
                            ->directory('candidates/documents/curriculum'),
                    ])->columns(2),
            ])
            ->columnSpanFull()
            ->skippable(),
        ];
    }

    /**
     * Formulário de Formador com Wizard
     */
    public static function getTrainerFormSchema(): array
    {
        return [
            \Filament\Schemas\Components\Wizard::make([
                // Etapa 1 - Tipo de Formador
                \Filament\Schemas\Components\Wizard\Step::make('Tipo de Formador')
                    ->description('Selecione o tipo de formador')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Forms\Components\Select::make('trainer_type')
                            ->label('Tipo de Formador')
                            ->options([
                                'Fardado' => 'REGIME ESPECIAL',
                                'Civil' => 'REGIME GERAL',
                            ])
                            ->default('Fardado')
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                    ]),

                // Etapa 2 - Identificação
                \Filament\Schemas\Components\Wizard\Step::make('Identificação')
                    ->description('Dados pessoais do formador')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\FileUpload::make('photo')
                            ->label('Foto')
                            ->image()
                            ->avatar()
                            ->directory('trainers')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('full_name')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(191)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Já existe um formador com este nome.',
                            ]),
                        Forms\Components\Select::make('gender')
                            ->label('Género')
                            ->options([
                                'Masculino' => 'Masculino',
                                'Feminino' => 'Feminino',
                            ])
                            ->required(),
                    ])->columns(2),

                // Etapa 3 - Dados Profissionais
                \Filament\Schemas\Components\Wizard\Step::make('Dados Profissionais')
                    ->description('Informações profissionais')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        // Campos para Fardado
                        Forms\Components\TextInput::make('nip')
                            ->label('NIP')
                            ->unique(ignoreRecord: true)
                            ->maxLength(191)
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('trainer_type') === 'Fardado'),
                        Forms\Components\Select::make('rank_id')
                            ->label('Patente')
                            ->relationship('rank', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('trainer_type') === 'Fardado'),
                        Forms\Components\Select::make('organ')
                            ->label('Órgão/Unidade')
                            ->options([
                                'Comando Geral' => 'Comando Geral',
                                'Direcção de Pessoal e Quadros' => 'Direcção de Pessoal e Quadros',
                                'Direcção de Instrução e Ensino' => 'Direcção de Instrução e Ensino',
                                'Comando Provincial de Luanda' => 'Comando Provincial de Luanda',
                            ])
                            ->searchable()
                            ->preload(),
                        // Campo para Civil
                        Forms\Components\TextInput::make('bilhete')
                            ->label('Bilhete de Identidade')
                            ->maxLength(191)
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('trainer_type') === 'Civil'),
                    ])->columns(2),

                // Etapa 4 - Finalização
                \Filament\Schemas\Components\Wizard\Step::make('Finalização')
                    ->description('Informações adicionais')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        Forms\Components\Select::make('education_level')
                            ->label('Nível Académico')
                            ->options([
                                '12ª Classe' => '12ª Classe',
                                'Ensino Médio Técnico' => 'Ensino Médio Técnico',
                                'Bacharelato' => 'Bacharelato',
                                'Licenciatura' => 'Licenciatura',
                                'Pós-Graduação' => 'Pós-Graduação',
                                'Mestrado' => 'Mestrado',
                                'Doutoramento' => 'Doutoramento',
                            ])
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefone')
                            ->tel()
                            ->prefix('+244')
                            ->placeholder('9XX XXX XXX')
                            ->mask('999 999 999')
                            ->maxLength(191)
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->required(),
                    ])->columns(3),
            ])->columnSpanFull()->skippable(),
        ];
    }
}

<?php

namespace App\Support;

class DefaultRolePermissions
{
    private const RESOURCE_ADMIN_ACTIONS = [
        'ViewAny',
        'View',
        'Create',
        'Update',
        'Delete',
        'DeleteAny',
    ];

    private const RESOURCE_STAFF_ACTIONS = [
        'ViewAny',
        'View',
        'Create',
        'Update',
    ];

    private const RESOURCE_VIEW_ACTIONS = [
        'ViewAny',
        'View',
    ];

    private const SCHOOL_ADMIN_RESOURCES = [
        'AcademicYear',
        'Agent',
        'Candidate',
        'CandidateTransferHistory',
        'CardTemplate',
        'Course',
        'CourseMap',
        'CoursePhase',
        'CoursePlan',
        'Document',
        'Effective',
        'EquipmentAssignment',
        'Evaluation',
        'Institution',
        'InstitutionType',
        'Pauta',
        'PautaGeral',
        'Provenance',
        'Rank',
        'Student',
        'StudentClass',
        'StudentClassEnrollment',
        'StudentLeave',
        'StudentTransferHistory',
        'StudentType',
        'Subject',
        'Trainer',
        'TrainerClassAssignment',
    ];

    private const SCHOOL_STAFF_RESOURCES = [
        'Candidate',
        'Document',
        'EquipmentAssignment',
        'Evaluation',
        'Pauta',
        'PautaGeral',
        'Student',
        'StudentClass',
        'StudentClassEnrollment',
        'StudentLeave',
        'Subject',
        'Trainer',
        'TrainerClassAssignment',
    ];

    private const SCHOOL_VIEW_RESOURCES = [
        'AcademicYear',
        'CardTemplate',
        'Course',
        'CourseMap',
        'CoursePhase',
        'CoursePlan',
        'Effective',
        'Institution',
        'InstitutionType',
        'Provenance',
        'Rank',
        'StudentTransferHistory',
        'StudentType',
    ];

    private const SCHOOL_PAGES = [
        'View:AttendanceManagement',
        'View:Dashboard',
        'View:InstitutionReportSettings',
        'View:Relatorios',
        'View:TransferHistory',
    ];

    private const SCHOOL_WIDGETS = [
        'View:EscolaCandidateStatusChart',
        'View:EscolaStatsOverview',
        'View:EscolaStudentManagement',
        'View:EscolaStudentsByCourseChart',
        'View:EscolaStudentStatusChart',
    ];

    private const SCHOOL_REPORTS = [
        'Report:Alistados',
        'Report:Attendance',
        'Report:Certificados',
        'Report:CourseMaps',
        'Report:CoursePlans',
        'Report:Courses',
        'Report:Documents',
        'Report:Effectives',
        'Report:Enrollments',
        'Report:Equipment',
        'Report:Evaluations',
        'Report:Institutions',
        'Report:Leaves',
        'Report:MiniPauta',
        'Report:PautaGeral',
        'Report:StudentsByProvenance',
        'Report:StudentTypes',
        'Report:Subjects',
        'Report:Trainers',
        'Report:TrainerSubjects',
        'Report:Transfers',
    ];

    private const SCHOOL_ACTIONS = [
        'Action:Agent.BaixarModelo',
        'Action:Agent.EnviarSmsEmMassa',
        'Action:Agent.ImportarExcel',
        'Action:Agent.VincularEIniciarFormacao',
        'Action:AttendanceManagement.ClearAll',
        'Action:AttendanceManagement.CurrentWeek',
        'Action:AttendanceManagement.GoToToday',
        'Action:AttendanceManagement.MarkAllAbsent',
        'Action:AttendanceManagement.MarkAllPresent',
        'Action:AttendanceManagement.NextDay',
        'Action:AttendanceManagement.NextWeek',
        'Action:AttendanceManagement.PreviousDay',
        'Action:AttendanceManagement.PreviousWeek',
        'Action:AttendanceManagement.SelectDate',
        'Action:AttendanceManagement.SelectStudent',
        'Action:AttendanceManagement.SetActiveTab',
        'Action:AttendanceManagement.SetAttendance',
        'Action:AttendanceManagement.SetStatus',
        'Action:AttendanceManagement.ToggleAttendance',
        'Action:Candidate.EnviarSmsEmMassa',
        'Action:Candidate.ImprimirFicha',
        'Action:Candidate.VincularEConverterRecruta',
        'Action:Candidate.VincularETransformarRecrutas',
        'Action:CardTemplate.Preview',
        'Action:Dashboard.OpenAlistadosChart',
        'Action:Dashboard.OpenCursosAnoLectivoChart',
        'Action:Dashboard.OpenDisciplinasCursoChart',
        'Action:Dashboard.OpenEmFormacaoConcluidosChart',
        'Action:Dashboard.OpenFormadoresChart',
        'Action:Dashboard.OpenFormandosChart',
        'Action:Dashboard.OpenInstituicoesEnsinoChart',
        'Action:Dashboard.OpenRecrutasInstruendosChart',
        'Action:Document.Archive',
        'Action:Document.Respond',
        'Action:Document.RespondInline',
        'Action:Document.Send',
        'Action:Effective.BaixarModelo',
        'Action:Effective.EffectiveSheet',
        'Action:Effective.ImportarExcel',
        'Action:Effective.PreviewCard',
        'Action:EquipmentAssignment.AtribuirMeios',
        'Action:EquipmentAssignment.AtribuirMeiosEmMassa',
        'Action:EquipmentAssignment.Devolucao',
        'Action:EquipmentAssignment.DevolucaoEmMassa',
        'Action:EquipmentAssignment.VerEquipamentos',
        'Action:Evaluation.VerDetalhes',
        'Action:InstitutionReportSettings.Save',
        'Action:Pauta.ExportPdf',
        'Action:Pauta.ListagemPautas',
        'Action:Pauta.MiniPauta',
        'Action:Pauta.PautaGeral',
        'Action:Pauta.Pesquisar',
        'Action:Pauta.PrintMiniPauta',
        'Action:Pauta.PrintPautaGeral',
        'Action:PautaGeral.ExportPdf',
        'Action:PautaGeral.MiniPauta',
        'Action:PautaGeral.Pesquisar',
        'Action:PautaGeral.PrintPautaGeral',
        'Action:Relatorios.PreviewReport',
        'Action:StudentClassEnrollment.AtribuirEmMassa',
        'Action:StudentClassEnrollment.EditarFormando',
        'Action:StudentClassEnrollment.EmFormacaoParaConcluido',
        'Action:StudentClassEnrollment.GerarCartao',
        'Action:StudentClassEnrollment.ImprimirCertificado',
        'Action:StudentClassEnrollment.ImprimirFicha',
        'Action:StudentClassEnrollment.Inscricao',
        'Action:StudentClassEnrollment.RecrutaParaInstruendo',
        'Action:StudentClassEnrollment.Visualizar',
        'Action:StudentLeave.CreateOccurrence',
        'Action:StudentLeave.FichaDispensa',
        'Action:StudentLeave.NovaOcorrencia',
        'Action:StudentLeave.VerDetalhes',
        'Action:StudentLeave.Voltar',
        'Action:Trainer.AtribuirDisciplina',
        'Action:Trainer.AtribuirDisciplinas',
        'Action:Trainer.BaixarModelo',
        'Action:Trainer.ImportarExcel',
        'Action:Trainer.PrintCard',
        'Action:Trainer.TrainerSheet',
        'Action:TrainerClassAssignment.AdicionarDisciplina',
        'Action:TrainerClassAssignment.EditarAtribuicoes',
        'Action:TrainerClassAssignment.GerenciarDisciplinas',
        'Action:TrainerClassAssignment.NovaAtribuicao',
    ];

    private const PROFESSOR_PERMISSIONS = [
        'AccessPanel:Professores',
        'View:Dashboard',
        'View:ProfessorAssignments',
        'View:ProfessorOverview',
    ];

    public static function profiles(): array
    {
        return [
            'super_admin' => static::all(),
            'admin' => static::all(),
            'admin_geral' => static::all(),
            'escola_admin' => static::schoolAdmin(),
            'escola_user' => static::schoolStaff(),
            'secretaria_escola' => static::schoolStaff(),
            'professores_admin' => static::professor(),
            'professores_user' => static::professor(),
            'professor' => static::professor(),
        ];
    }

    public static function all(): array
    {
        return AccessPermissionCatalog::permissions();
    }

    public static function schoolAdmin(): array
    {
        return static::normalize([
            'AccessPanel:Escola',
            ...static::resourcePermissions(static::SCHOOL_ADMIN_RESOURCES, static::RESOURCE_ADMIN_ACTIONS),
            ...static::SCHOOL_PAGES,
            ...static::SCHOOL_WIDGETS,
            ...static::SCHOOL_REPORTS,
            ...static::SCHOOL_ACTIONS,
        ]);
    }

    public static function schoolStaff(): array
    {
        return static::normalize([
            'AccessPanel:Escola',
            ...static::resourcePermissions(static::SCHOOL_STAFF_RESOURCES, static::RESOURCE_STAFF_ACTIONS),
            ...static::resourcePermissions(static::SCHOOL_VIEW_RESOURCES, static::RESOURCE_VIEW_ACTIONS),
            'View:Dashboard',
            'View:AttendanceManagement',
            'View:Relatorios',
            ...static::SCHOOL_WIDGETS,
            'Report:Alistados',
            'Report:Enrollments',
            'Report:Leaves',
            'Report:MiniPauta',
            'Report:PautaGeral',
            'Report:Transfers',
            'Action:AttendanceManagement.SelectDate',
            'Action:AttendanceManagement.SelectStudent',
            'Action:AttendanceManagement.SetActiveTab',
            'Action:AttendanceManagement.SetAttendance',
            'Action:AttendanceManagement.ToggleAttendance',
            'Action:Candidate.ImprimirFicha',
            'Action:Document.Respond',
            'Action:Document.RespondInline',
            'Action:Document.Send',
            'Action:EquipmentAssignment.AtribuirMeios',
            'Action:EquipmentAssignment.Devolucao',
            'Action:Evaluation.VerDetalhes',
            'Action:Pauta.Pesquisar',
            'Action:Pauta.PrintMiniPauta',
            'Action:PautaGeral.Pesquisar',
            'Action:PautaGeral.PrintPautaGeral',
            'Action:Relatorios.PreviewReport',
            'Action:StudentClassEnrollment.EditarFormando',
            'Action:StudentClassEnrollment.ImprimirFicha',
            'Action:StudentClassEnrollment.Inscricao',
            'Action:StudentClassEnrollment.Visualizar',
            'Action:StudentLeave.CreateOccurrence',
            'Action:StudentLeave.FichaDispensa',
            'Action:StudentLeave.NovaOcorrencia',
            'Action:StudentLeave.VerDetalhes',
        ]);
    }

    public static function professor(): array
    {
        return static::normalize(static::PROFESSOR_PERMISSIONS);
    }

    private static function resourcePermissions(array $subjects, array $actions): array
    {
        $permissions = [];

        foreach ($subjects as $subject) {
            foreach ($actions as $action) {
                $permissions[] = "{$action}:{$subject}";
            }
        }

        return $permissions;
    }

    private static function normalize(array $permissions): array
    {
        return collect($permissions)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}

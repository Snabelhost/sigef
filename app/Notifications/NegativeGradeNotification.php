<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Evaluation;

class NegativeGradeNotification extends Notification
{
    use Queueable;

    protected Evaluation $evaluation;

    public function __construct(Evaluation $evaluation)
    {
        $this->evaluation = $evaluation;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $studentName = $this->evaluation->student->candidate->full_name ?? 'Formando';
        $studentNumber = $this->evaluation->student->student_number ?? 'N/A';
        $subjectName = $this->evaluation->subject->name ?? 'Disciplina';
        $score = $this->evaluation->score;

        return [
            'title' => 'Nota Negativa Registada',
            'message' => "O formando {$studentName} (Nº {$studentNumber}) obteve nota {$score} em {$subjectName}.",
            'icon' => 'heroicon-o-exclamation-triangle',
            'color' => 'danger',
            'action_url' => url('/admin/evaluations/' . $this->evaluation->id . '/edit'),
            'evaluation_id' => $this->evaluation->id,
            'student_id' => $this->evaluation->student_id,
            'score' => $score,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $studentName = $this->evaluation->student->candidate->full_name ?? 'Formando';
        $subjectName = $this->evaluation->subject->name ?? 'Disciplina';
        $score = $this->evaluation->score;

        return (new MailMessage)
            ->subject('SIGEF - Alerta de Nota Negativa')
            ->greeting('Caro(a) responsável,')
            ->line("Foi registada uma nota negativa para o formando {$studentName}.")
            ->line("Disciplina: {$subjectName}")
            ->line("Nota: {$score}")
            ->action('Ver Avaliação', url('/admin/evaluations/' . $this->evaluation->id . '/edit'))
            ->line('Por favor, acompanhe o desempenho do formando.');
    }
}

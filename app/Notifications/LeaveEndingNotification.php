<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use App\Models\StudentLeave;

class LeaveEndingNotification extends Notification
{
    use Queueable;

    protected StudentLeave $leave;

    public function __construct(StudentLeave $leave)
    {
        $this->leave = $leave;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $studentName = $this->leave->student->candidate->full_name ?? 'Formando';
        $studentNumber = $this->leave->student->student_number ?? 'N/A';
        $endDate = $this->leave->end_date?->format('d/m/Y') ?? 'N/A';
        $daysRemaining = now()->diffInDays($this->leave->end_date);

        return [
            'title' => 'Dispensa a Terminar',
            'message' => "A dispensa do formando {$studentName} (Nº {$studentNumber}) termina em {$daysRemaining} dia(s) ({$endDate}).",
            'icon' => 'heroicon-o-calendar-days',
            'color' => 'warning',
            'action_url' => url('/admin/student-leaves/' . $this->leave->id . '/edit'),
            'leave_id' => $this->leave->id,
            'student_id' => $this->leave->student_id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $studentName = $this->leave->student->candidate->full_name ?? 'Formando';
        $endDate = $this->leave->end_date?->format('d/m/Y') ?? 'N/A';

        return (new MailMessage)
            ->subject('SIGEF - Dispensa a Terminar')
            ->greeting('Caro(a) responsável,')
            ->line("A dispensa do formando {$studentName} está prestes a terminar.")
            ->line("Data de fim: {$endDate}")
            ->action('Ver Dispensa', url('/admin/student-leaves/' . $this->leave->id . '/edit'))
            ->line('Por favor, tome as devidas providências.');
    }
}

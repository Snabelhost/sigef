@include('cards.preview-modal', [
    'viewerId' => 'sigef-student-card-viewer-'.(($student ?? null)?->getKey() ?? uniqid()),
    'entityLabel' => 'Formandos',
    'documentName' => 'Formandos - '.($payload['name'] ?? 'Formando'),
    'statusLabel' => $payload['status_label'] ?? 'ACTIVO',
    'statusColor' => 'success',
])

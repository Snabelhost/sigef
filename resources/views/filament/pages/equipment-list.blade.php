<div class="w-full">
    <table class="w-full text-sm" style="table-layout: fixed;">
        <thead>
            <tr class="bg-primary-600 text-white">
                <th style="width: 25%; padding: 12px 16px;" class="text-left font-medium">Equipamento</th>
                <th style="width: 8%; padding: 12px 16px;" class="text-center font-medium">Qtd</th>
                <th style="width: 15%; padding: 12px 16px;" class="text-center font-medium">Condição</th>
                <th style="width: 17%; padding: 12px 16px;" class="text-center font-medium">Data Atribuição</th>
                <th style="width: 17%; padding: 12px 16px;" class="text-center font-medium">Devolução</th>
                <th style="width: 18%; padding: 12px 16px;" class="text-left font-medium">Atribuído Por</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($equipamentos as $equip)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                    <td style="padding: 12px 16px;" class="text-gray-900 dark:text-gray-100">{{ $equip->equipment_name }}</td>
                    <td style="padding: 12px 16px;" class="text-center text-gray-900 dark:text-gray-100">{{ $equip->quantity }}</td>
                    <td style="padding: 12px 16px;" class="text-center">
                        @php
                            $color = match($equip->condition) {
                                'Novo' => 'success',
                                'Bom Estado' => 'info',
                                'Usado' => 'warning',
                                'Razoável' => 'warning',
                                'Danificado' => 'danger',
                                default => 'gray',
                            };
                            $bgClass = match($color) {
                                'success' => 'bg-green-100 text-green-800',
                                'info' => 'bg-blue-100 text-blue-800',
                                'warning' => 'bg-yellow-100 text-yellow-800',
                                'danger' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $bgClass }}">
                            {{ $equip->condition }}
                        </span>
                    </td>
                    <td style="padding: 12px 16px;" class="text-center text-gray-600 dark:text-gray-400">{{ $equip->assigned_at?->format('d/m/Y') }}</td>
                    <td style="padding: 12px 16px;" class="text-center">
                        @if($equip->returned_at)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                ✓ {{ $equip->returned_at->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                Pendente
                            </span>
                        @endif
                    </td>
                    <td style="padding: 12px 16px;" class="text-gray-600 dark:text-gray-400">{{ $equip->assigned_by_name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

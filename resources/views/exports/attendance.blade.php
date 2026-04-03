@extends('exports.layout')

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>{{ $type === 'student' ? 'NIS' : 'NIP' }}</th>
            <th>Nama {{ $type === 'student' ? 'Siswa' : 'Pegawai' }}</th>
            <th>{{ $type === 'student' ? 'Kelas' : 'Jabatan' }}</th>
            <th>Status</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($attendances as $index => $att)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td class="text-center">{{ $att->date?->format('d/m/Y') ?? '-' }}</td>
            
            @if($type === 'student')
                <td class="text-center">{{ $att->student?->nis ?? '-' }}</td>
                <td>{{ $att->student?->name ?? '-' }}</td>
                <td class="text-center">{{ $att->classroom?->name ?? '-' }}</td>
            @else
                <td class="text-center">{{ $att->employee?->nip ?? '-' }}</td>
                <td>{{ $att->employee?->name ?? '-' }}</td>
                <td>{{ $att->employee?->position?->name ?? '-' }}</td>
            @endif
            
            <td class="text-center">{{ ucfirst($att->status) }}</td>
            <td>{{ $att->notes ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection

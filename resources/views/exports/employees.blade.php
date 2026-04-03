@extends('exports.layout')

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th>No</th>
            <th>NIP</th>
            <th>Nama Pegawai</th>
            <th>L/P</th>
            <th>Jabatan</th>
            <th>Jenis</th>
            <th>No HP</th>
            <th>Status Aktif</th>
        </tr>
    </thead>
    <tbody>
        @foreach($employees as $index => $employee)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td class="text-center">{{ $employee->nip ?? '-' }}</td>
            <td>{{ $employee->name }}</td>
            <td class="text-center">{{ $employee->gender === 'male' ? 'L' : 'P' }}</td>
            <td>{{ $employee->position?->name ?? '-' }}</td>
            <td class="text-center">{{ ucfirst($employee->employee_type ?? '-') }}</td>
            <td class="text-center">{{ $employee->phone ?? '-' }}</td>
            <td class="text-center">{{ $employee->is_active ? 'Aktif' : 'Tidak' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection

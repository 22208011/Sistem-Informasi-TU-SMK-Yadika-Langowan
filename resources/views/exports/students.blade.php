@extends('exports.layout')

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th>No</th>
            <th>NIS</th>
            <th>Nama Siswa</th>
            <th>L/P</th>
            <th>Kelas</th>
            <th>Jurusan</th>
            <th>Status</th>
            <th>Nama Wali</th>
        </tr>
    </thead>
    <tbody>
        @foreach($students as $index => $student)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td class="text-center">{{ $student->nis ?? '-' }}</td>
            <td>{{ $student->name }}</td>
            <td class="text-center">{{ $student->gender === 'male' ? 'L' : 'P' }}</td>
            <td class="text-center">{{ $student->classroom?->name ?? '-' }}</td>
            <td>{{ $student->department?->name ?? '-' }}</td>
            <td class="text-center">{{ ucfirst($student->status) }}</td>
            <td>{{ $student->guardian?->name ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection

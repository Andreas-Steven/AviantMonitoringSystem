@extends('layouts.app')

@section('content')

<form method="POST" action="{{ route('attendance.raw-logs.import.store') }}" enctype="multipart/form-data">
    @csrf

    <button type="submit">Import</button>
</form>

<table>
    <tr>
        <th>AC No</th>
        <th>Name</th>
        <th>Time</th>
        <th>State</th>
    </tr>

    @foreach($rows as $row)
        <tr>
            <td>{{ $row['ac_no'] }}</td>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['time'] }}</td>
            <td>{{ $row['state'] }}</td>
        </tr>
    @endforeach

</table>

@endsection
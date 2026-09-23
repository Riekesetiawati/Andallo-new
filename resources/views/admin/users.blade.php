@extends('layouts.admin')
@section('content')
<h1>Pengguna</h1>
<div class="table-wrap"><table class="data"><thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($users as $user)
<tr>
    <td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->role->label() }}</td>
    <td>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</td>
    <td><form method="POST" action="{{ route('admin.users.toggle', $user) }}">@csrf<button class="btn btn-outline" type="submit">Ubah status</button></form></td>
</tr>
@endforeach
</tbody></table></div>
{{ $users->links() }}
@endsection

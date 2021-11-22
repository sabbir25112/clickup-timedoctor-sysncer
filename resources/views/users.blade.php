@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Users</div>

                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Fullname</th>
                                <th scope="col">Email</th>
                                <th scope="col">Timedoctor</th>
                                <th scope="col">Clickup</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($users as $user)
                                <tr class="{{ $user->time_doctor_user_id != null && $user->click_up_user_id != null ? 'table-success' : 'table-danger' }}">
                                    <th scope="row">{{ $user->id }}</th>
                                    <td>{{ $user->full_name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if ($user->time_doctor_user_id != null)
                                            <span class="badge badge-success">Found</span>
                                        @else
                                            <span class="badge badge-danger">Missing</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($user->click_up_user_id != null)
                                            <span class="badge badge-success">Found</span>
                                        @else
                                            <span class="badge badge-danger">Missing</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

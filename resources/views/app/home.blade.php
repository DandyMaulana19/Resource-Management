@extends("layouts.app")
@section("title", "Home")

@section("content")
<div class="w-screen h-auto flex justify-center bg-red-300 ">
    <div class="container">
        <div class="p-4 bg-amber-200">
            <div class="table w-full">
                <div class="table-header-group">
                    <div class="table-row">
                        <div class="table-cell text-left border">Name</div>
                        <div class="table-cell text-left border">Email</div>
                        <div class="table-cell text-left border">Status</div>
                    </div>
                </div>
                <div class="table-row-group">
                    @foreach ( $users as $user)
                    <div class="table-row">
                        <div class="table-cell border">{{$user->name}}</div>
                        <div class="table-cell border">{{$user->email}}</div>
                        <div class="table-cell border">{{$user->status}}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
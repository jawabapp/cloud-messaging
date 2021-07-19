@extends('cloud-messaging::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col text-left">
                    <h5>Cloud Messaging</h5>
                </div>
                <div class="col text-right">
                    <a href="{{ route('jawab.notifications.report') }}" class='btn btn-outline-primary btn-sm'>Report</a>
                    <a href="{{ route('jawab.notifications.compose') }}" class='btn btn-primary btn-sm text-white'>Compose notification</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($data->items())
                <table class="table table-striped table-bordered">
                    <tbody>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Title</th>
                        <th scope="col">Text</th>
                        <th scope="col">Created at</th>
                        <th scope="col">Sent by</th>
                        <th scope="col">Scheduled</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-center">Actions</th>
                    </tr>
                    @foreach($data->items() as $item)
                    <tr>
                        <th scope="row">{{ $item->id }}</th>
                        <td>{{ $item->title ?? '-' }}</td>
                        <td><p class="m-0">{!! nl2br($item->text) !!}</p></td>
                        <td>{{ Timezone::convertToLocal($item->created_at) }}</td>
                        <td>{{ $item->user->name ?? '' }}</td>
                        <td>
                            @php
                                $scheduleType = $item->schedule['type'] ?? 'Now';
                                $scheduleDate = $item->schedule['date'] ?? '';
                                $scheduleDate = $scheduleDate ? Timezone::convertToLocal(\Carbon\Carbon::parse($scheduleDate)) : '';
                            @endphp
                            {{ $scheduleType . ' ' . $scheduleDate }}
                        </td>
                        <td>{{ $item->status ?? 'completed' }}</td>
                        <td class="text-center">
                            <a href="{{ route('jawab.notifications.show', [$item->id]) }}" class='btn btn-warning btn-sm'>View</a>
                            @if($item->status === 'pending')
                                <a href="{{ route('jawab.notifications.delete', [$item->id]) }}" class='btn btn-danger btn-sm'>Delete</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-danger" role="alert">There are no Data</div>
            @endif
        </div>
        <div class="card-footer">
            {{ $data->appends(request()->query())->links() }}
        </div>
    </div>
@endsection
